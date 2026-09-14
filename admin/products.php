<?php

require_once __DIR__ . "/../php/session.php";
require_once __DIR__ . "/../php/database.php";

/* =========================================================
   ADMIN ACCESS CHECK
========================================================= */

if (
    empty($_SESSION["logged_in"]) ||
    empty($_SESSION["user_id"]) ||
    empty($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: login.php");
    exit;
}


/* =========================================================
   ESCAPE FUNCTION
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}


/* =========================================================
   ADMIN NAME
========================================================= */

$admin_name = $_SESSION["full_name"] ?? "Administrator";


/* =========================================================
   DELETE PRODUCT
   Uses POST instead of GET.
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["delete_product"])
) {

    $product_id = filter_input(
        INPUT_POST,
        "product_id",
        FILTER_VALIDATE_INT
    );

    if (!$product_id || $product_id <= 0) {

        header("Location: products.php?error=invalid_product");
        exit;
    }


    /* -----------------------------------------------------
       Check whether this product has already been used
       in an order.
    ----------------------------------------------------- */

    $check_stmt = $conn->prepare(
        "
        SELECT COUNT(*) AS total
        FROM order_items
        WHERE product_id = ?
        "
    );

    if ($check_stmt) {

        $check_stmt->bind_param(
            "i",
            $product_id
        );

        $check_stmt->execute();

        $check_result =
            $check_stmt->get_result();

        $check_row =
            $check_result->fetch_assoc();

        $check_stmt->close();

        $order_item_count =
            (int) ($check_row["total"] ?? 0);

    } else {

        header("Location: products.php?error=delete_failed");
        exit;
    }


    /*
     * Do not delete a product that already exists
     * in customer order history.
     */
    if ($order_item_count > 0) {

        header(
            "Location: products.php?error=product_in_orders"
        );

        exit;
    }


    /* -----------------------------------------------------
       Get image filename before deleting the product.
    ----------------------------------------------------- */

    $image_name = null;

    $image_stmt = $conn->prepare(
        "
        SELECT image
        FROM products
        WHERE id = ?
        LIMIT 1
        "
    );

    if ($image_stmt) {

        $image_stmt->bind_param(
            "i",
            $product_id
        );

        $image_stmt->execute();

        $image_result =
            $image_stmt->get_result();

        $image_row =
            $image_result->fetch_assoc();

        if ($image_row) {
            $image_name =
                $image_row["image"];
        }

        $image_stmt->close();
    }


    /* -----------------------------------------------------
       Delete product.
    ----------------------------------------------------- */

    $delete_stmt = $conn->prepare(
        "
        DELETE FROM products
        WHERE id = ?
        "
    );

    if (!$delete_stmt) {

        header("Location: products.php?error=delete_failed");
        exit;
    }

    $delete_stmt->bind_param(
        "i",
        $product_id
    );

    $delete_stmt->execute();

    $deleted =
        $delete_stmt->affected_rows > 0;

    $delete_stmt->close();


    /* -----------------------------------------------------
       Delete image file if the product was deleted.
       Only delete files inside the project's images folder.
    ----------------------------------------------------- */

    if (
        $deleted &&
        !empty($image_name)
    ) {

        $image_name =
            basename($image_name);

        $image_path =
            __DIR__ .
            DIRECTORY_SEPARATOR .
            ".." .
            DIRECTORY_SEPARATOR .
            "images" .
            DIRECTORY_SEPARATOR .
            $image_name;

        if (
            is_file($image_path)
        ) {
            @unlink($image_path);
        }
    }


    if ($deleted) {

        header(
            "Location: products.php?success=deleted"
        );

    } else {

        header(
            "Location: products.php?error=delete_failed"
        );
    }

    exit;
}


/* =========================================================
   STATUS MESSAGES
========================================================= */

$success_message = "";
$error_message = "";

if (
    isset($_GET["success"]) &&
    $_GET["success"] === "deleted"
) {

    $success_message =
        "Product deleted successfully.";
}

if (
    isset($_GET["error"]) &&
    $_GET["error"] === "product_in_orders"
) {

    $error_message =
        "This product cannot be deleted because it is already part of an existing order.";

} elseif (
    isset($_GET["error"]) &&
    $_GET["error"] === "invalid_product"
) {

    $error_message =
        "Invalid product selected.";

} elseif (
    isset($_GET["error"]) &&
    $_GET["error"] === "delete_failed"
) {

    $error_message =
        "The product could not be deleted.";
}


/* =========================================================
   LOAD PRODUCTS
========================================================= */

$products = [];

$sql = "
    SELECT
        p.id,
        p.category_id,
        p.product_name,
        p.description,
        p.price,
        p.image,
        p.stock,
        p.created_at,
        c.category_name
    FROM products p
    LEFT JOIN categories c
        ON c.id = p.category_id
    ORDER BY p.id ASC
";

$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $products[] = $row;
    }

    $result->free();
}


/* =========================================================
   TOTAL PRODUCTS
========================================================= */

$total_products =
    count($products);


/* =========================================================
   TOTAL STOCK
========================================================= */

$total_stock = 0;

foreach ($products as $product) {

    $total_stock +=
        (int) $product["stock"];
}


/* =========================================================
   LOW STOCK COUNT
========================================================= */

$low_stock_count = 0;

foreach ($products as $product) {

    if (
        (int) $product["stock"] <= 5 &&
        (int) $product["stock"] > 0
    ) {

        $low_stock_count++;
    }
}


/* =========================================================
   OUT OF STOCK COUNT
========================================================= */

$out_of_stock_count = 0;

foreach ($products as $product) {

    if (
        (int) $product["stock"] <= 0
    ) {

        $out_of_stock_count++;
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Manage Products | Siquijor Styles
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }


        body {
            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f5f1eb;

            color: #2f2a25;
        }


        /* =================================================
           LAYOUT
        ================================================= */

        .admin-layout {
            min-height: 100vh;

            display: flex;
        }


        /* =================================================
           SIDEBAR
        ================================================= */

        .sidebar {
            width: 250px;

            flex-shrink: 0;

            background: #2f2a25;

            color: #ffffff;

            padding: 26px 18px;

            display: flex;

            flex-direction: column;

            min-height: 100vh;
        }


        .sidebar-logo {
            display: flex;

            justify-content: center;

            align-items: center;

            padding-bottom: 25px;

            margin-bottom: 20px;

            border-bottom:
                1px solid
                rgba(255,255,255,0.12);
        }


        .sidebar-logo img {
            width: auto;

            max-width: 180px;

            height: 55px;

            object-fit: contain;
        }


        .admin-badge {
            padding: 10px 12px;

            border-radius: 10px;

            background:
                rgba(255,255,255,0.08);

            margin-bottom: 22px;

            text-align: center;
        }


        .admin-badge strong {
            display: block;

            font-size: 14px;

            margin-bottom: 4px;
        }


        .admin-badge span {
            font-size: 11px;

            opacity: 0.65;

            text-transform: uppercase;

            letter-spacing: 0.7px;
        }


        .nav-title {
            margin: 0 10px 10px;

            font-size: 10px;

            text-transform: uppercase;

            letter-spacing: 1px;

            opacity: 0.5;
        }


        .admin-nav {
            display: flex;

            flex-direction: column;

            gap: 6px;
        }


        .admin-nav a {
            display: flex;

            align-items: center;

            gap: 10px;

            padding: 12px 13px;

            border-radius: 9px;

            color: #ffffff;

            text-decoration: none;

            font-size: 14px;

            transition:
                background 0.2s ease,
                transform 0.2s ease;
        }


        .admin-nav a:hover {
            background:
                rgba(255,255,255,0.10);

            transform:
                translateX(2px);
        }


        .admin-nav a.active {
            background:
                rgba(255,255,255,0.14);

            font-weight: 700;
        }


        .nav-icon {
            width: 22px;

            text-align: center;

            font-size: 17px;
        }


        .sidebar-bottom {
            margin-top: auto;

            padding-top: 20px;
        }


        .bottom-link {
            display: block;

            text-align: center;

            padding: 11px;

            border:
                1px solid
                rgba(255,255,255,0.20);

            border-radius: 9px;

            color: #ffffff;

            text-decoration: none;

            font-size: 13px;
        }


        .bottom-link:hover {
            background:
                rgba(255,255,255,0.08);
        }


        .bottom-link + .bottom-link {
            margin-top: 8px;
        }


        /* =================================================
           MAIN CONTENT
        ================================================= */

        .main-content {
            flex: 1;

            min-width: 0;

            padding: 30px;
        }


        .topbar {
            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 25px;
        }


        .topbar h1 {
            margin: 0 0 7px;

            font-size: 30px;
        }


        .topbar p {
            margin: 0;

            color: #7b7168;

            font-size: 14px;
        }


        .add-product-button {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding: 12px 18px;

            border-radius: 9px;

            background: #2f2a25;

            color: #ffffff;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;

            transition:
                background 0.2s ease,
                transform 0.2s ease;
        }


        .add-product-button:hover {
            background: #4a4036;

            transform:
                translateY(-1px);
        }


        /* =================================================
           SUMMARY CARDS
        ================================================= */

        .summary-grid {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 16px;

            margin-bottom: 24px;
        }


        .summary-card {
            background: #ffffff;

            border:
                1px solid
                #e5ddd5;

            border-radius: 14px;

            padding: 20px;

            box-shadow:
                0 5px 18px
                rgba(47,42,37,0.04);
        }


        .summary-label {
            font-size: 12px;

            color: #837971;

            margin-bottom: 8px;
        }


        .summary-value {
            font-size: 28px;

            font-weight: 700;
        }


        /* =================================================
           MESSAGES
        ================================================= */

        .message {
            margin-bottom: 20px;

            padding: 13px 16px;

            border-radius: 9px;

            font-size: 13px;
        }


        .success-message {
            background: #e7f5ea;

            border:
                1px solid
                #c8e8cf;

            color: #3e7449;
        }


        .error-message {
            background: #fff0f0;

            border:
                1px solid
                #f1cccc;

            color: #a34b4b;
        }


        /* =================================================
           PRODUCT PANEL
        ================================================= */

        .panel {
            background: #ffffff;

            border:
                1px solid
                #e5ddd5;

            border-radius: 15px;

            overflow: hidden;

            box-shadow:
                0 5px 18px
                rgba(47,42,37,0.04);
        }


        .panel-header {
            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            padding: 20px 22px;

            border-bottom:
                1px solid
                #eee7e0;
        }


        .panel-header h2 {
            margin: 0;

            font-size: 18px;
        }


        .panel-header span {
            color: #8b8078;

            font-size: 12px;
        }


        .table-wrapper {
            width: 100%;

            overflow-x: auto;
        }


        table {
            width: 100%;

            border-collapse: collapse;

            min-width: 950px;
        }


        th {
            padding: 13px 16px;

            text-align: left;

            font-size: 11px;

            text-transform: uppercase;

            letter-spacing: 0.5px;

            color: #877d74;

            background: #faf8f5;

            border-bottom:
                1px solid
                #eee7e0;
        }


        td {
            padding: 15px 16px;

            border-bottom:
                1px solid
                #f0ebe5;

            font-size: 13px;

            vertical-align: middle;
        }


        tbody tr:last-child td {
            border-bottom: none;
        }


        /* =================================================
           PRODUCT IMAGE
        ================================================= */

        .product-image {
            width: 64px;

            height: 64px;

            border-radius: 9px;

            object-fit: cover;

            display: block;

            border:
                1px solid
                #e5ddd5;

            background: #f4efe9;
        }


        .no-image {
            width: 64px;

            height: 64px;

            border-radius: 9px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #f3eee8;

            color: #948980;

            font-size: 10px;

            text-align: center;

            border:
                1px solid
                #e5ddd5;
        }


        /* =================================================
           PRODUCT INFORMATION
        ================================================= */

        .product-name {
            font-weight: 700;

            color: #332e29;

            margin-bottom: 4px;
        }


        .product-description {
            max-width: 290px;

            color: #938880;

            font-size: 11px;

            line-height: 1.45;
        }


        .category-name {
            display: inline-block;

            padding: 6px 9px;

            border-radius: 20px;

            background: #f1ece6;

            color: #66594e;

            font-size: 11px;

            font-weight: 600;
        }


        .price {
            font-weight: 700;

            white-space: nowrap;
        }


        /* =================================================
           STOCK
        ================================================= */

        .stock-badge {
            display: inline-block;

            min-width: 42px;

            text-align: center;

            padding: 7px 9px;

            border-radius: 8px;

            font-size: 11px;

            font-weight: 700;
        }


        .stock-good {
            background: #e7f5ea;

            color: #3e7449;
        }


        .stock-low {
            background: #fff1da;

            color: #966b1c;
        }


        .stock-out {
            background: #ffe8e8;

            color: #a34b4b;
        }


        /* =================================================
           ACTION BUTTONS
        ================================================= */

        .actions {
            display: flex;

            align-items: center;

            gap: 7px;
        }


        .action-button {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-width: 60px;

            padding: 8px 10px;

            border-radius: 7px;

            text-decoration: none;

            font-size: 11px;

            font-weight: 700;

            transition:
                background 0.2s ease,
                transform 0.2s ease;
        }


        .edit-button {
            background: #eee8df;

            color: #675747;
        }


        .edit-button:hover {
            background: #e5ddd2;

            transform:
                translateY(-1px);
        }


        .delete-button {
            background: #fff0f0;

            color: #a54c4c;

            border: none;

            cursor: pointer;

            font-family: inherit;
        }


        .delete-button:hover {
            background: #ffe2e2;

            transform:
                translateY(-1px);
        }


        /* =================================================
           EMPTY STATE
        ================================================= */

        .empty-state {
            padding: 50px 25px;

            text-align: center;

            color: #8f857c;

            font-size: 13px;
        }


        .empty-state strong {
            display: block;

            margin-bottom: 7px;

            color: #4d443d;

            font-size: 17px;
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 1100px) {

            .summary-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }


        @media (max-width: 800px) {

            .admin-layout {
                display: block;
            }


            .sidebar {
                width: 100%;

                min-height: auto;

                padding: 18px;
            }


            .sidebar-logo {
                justify-content: flex-start;

                margin-bottom: 15px;

                padding-bottom: 15px;
            }


            .admin-nav {
                display: grid;

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }


            .sidebar-bottom {
                margin-top: 15px;
            }


            .main-content {
                padding: 20px;
            }
        }


        @media (max-width: 600px) {

            .summary-grid {
                grid-template-columns: 1fr;
            }


            .topbar {
                flex-direction: column;

                align-items: flex-start;
            }


            .admin-nav {
                grid-template-columns: 1fr;
            }


            .main-content {
                padding: 15px;
            }


            .topbar h1 {
                font-size: 25px;
            }
        }

    </style>

</head>


<body>


<div class="admin-layout">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar">

        <div class="sidebar-logo">

            <img
                src="../images/logo.png"
                alt="Siquijor Styles"
            >

        </div>


        <div class="admin-badge">

            <strong>
                <?= e($admin_name) ?>
            </strong>

            <span>
                Administrator
            </span>

        </div>


        <div class="nav-title">
            Management
        </div>


        <nav class="admin-nav">

            <a
                href="index.php"
            >

                <span class="nav-icon">
                    ⌂
                </span>

                Dashboard

            </a>


            <a
                href="products.php"
                class="active"
            >

                <span class="nav-icon">
                    ▣
                </span>

                Products

            </a>


            <a
                href="users.php"
            >

                <span class="nav-icon">
                    ♙
                </span>

                Customers

            </a>


            <a
                href="orders.php"
            >

                <span class="nav-icon">
                    ▤
                </span>

                Orders

            </a>

        </nav>


        <div class="sidebar-bottom">

            <a
                href="../index.php"
                class="bottom-link"
            >
                View Website
            </a>


            <a
                href="logout.php"
                class="bottom-link"
            >
                Log Out
            </a>

        </div>

    </aside>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">


        <!-- TOP BAR -->

        <div class="topbar">

            <div>

                <h1>
                    Products
                </h1>

                <p>
                    Manage your Siquijor Styles product catalog.
                </p>

            </div>


            <a
                href="product_add.php"
                class="add-product-button"
            >

                <span>
                    ＋
                </span>

                Add Product

            </a>

        </div>


        <!-- =================================================
             MESSAGES
        ================================================== -->

        <?php if ($success_message !== ""): ?>

            <div class="message success-message">

                <?= e($success_message) ?>

            </div>

        <?php endif; ?>


        <?php if ($error_message !== ""): ?>

            <div class="message error-message">

                <?= e($error_message) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             SUMMARY
        ================================================== -->

        <section class="summary-grid">


            <div class="summary-card">

                <div class="summary-label">
                    Total Products
                </div>

                <div class="summary-value">
                    <?= number_format($total_products) ?>
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Total Stock
                </div>

                <div class="summary-value">
                    <?= number_format($total_stock) ?>
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Low Stock
                </div>

                <div class="summary-value">
                    <?= number_format($low_stock_count) ?>
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Out of Stock
                </div>

                <div class="summary-value">
                    <?= number_format($out_of_stock_count) ?>
                </div>

            </div>


        </section>


        <!-- =================================================
             PRODUCT TABLE
        ================================================== -->

        <section class="panel">


            <div class="panel-header">

                <div>

                    <h2>
                        Product Catalog
                    </h2>

                </div>


                <span>

                    <?= number_format($total_products) ?>

                    products

                </span>

            </div>


            <div class="table-wrapper">


                <?php if (!empty($products)): ?>


                    <table>


                        <thead>

                            <tr>

                                <th>
                                    Image
                                </th>

                                <th>
                                    Product
                                </th>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Price
                                </th>

                                <th>
                                    Stock
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach ($products as $product): ?>


                                <?php

                                $stock =
                                    (int) $product["stock"];


                                if ($stock <= 0) {

                                    $stock_class =
                                        "stock-out";

                                } elseif ($stock <= 5) {

                                    $stock_class =
                                        "stock-low";

                                } else {

                                    $stock_class =
                                        "stock-good";
                                }

                                ?>


                                <tr>


                                    <!-- IMAGE -->

                                    <td>

                                        <?php if (!empty($product["image"])): ?>

                                            <img
                                                class="product-image"
                                                src="../images/<?= e($product["image"]) ?>"
                                                alt="<?= e($product["product_name"]) ?>"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                            >

                                            <div
                                                class="no-image"
                                                style="display:none;"
                                            >
                                                Image unavailable
                                            </div>

                                        <?php else: ?>

                                            <div class="no-image">
                                                No image
                                            </div>

                                        <?php endif; ?>

                                    </td>


                                    <!-- PRODUCT -->

                                    <td>

                                        <div class="product-name">

                                            <?= e(
                                                $product["product_name"]
                                            ) ?>

                                        </div>


                                        <?php if (!empty($product["description"])): ?>

                                            <div class="product-description">

                                                <?= e(
                                                    mb_strimwidth(
                                                        $product["description"],
                                                        0,
                                                        100,
                                                        "..."
                                                    )
                                                ) ?>

                                            </div>

                                        <?php endif; ?>

                                    </td>


                                    <!-- CATEGORY -->

                                    <td>

                                        <?php if (!empty($product["category_name"])): ?>

                                            <span class="category-name">

                                                <?= e(
                                                    $product["category_name"]
                                                ) ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="category-name">
                                                Unassigned
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- PRICE -->

                                    <td class="price">

                                        ₱<?= number_format(
                                            (float) $product["price"],
                                            2
                                        ) ?>

                                    </td>


                                    <!-- STOCK -->

                                    <td>

                                        <span
                                            class="stock-badge <?= e($stock_class) ?>"
                                        >

                                            <?= number_format($stock) ?>

                                        </span>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td>

                                        <div class="actions">


                                            <!-- EDIT -->

                                            <a
                                                href="product_edit.php?id=<?= (int) $product["id"] ?>"
                                                class="action-button edit-button"
                                            >
                                                Edit
                                            </a>


                                            <!-- DELETE -->

                                            <form
                                                method="POST"
                                                action="products.php"
                                                style="margin:0;"
                                                onsubmit="return confirm('Are you sure you want to delete this product? This action cannot be undone.');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="product_id"
                                                    value="<?= (int) $product["id"] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="delete_product"
                                                    class="action-button delete-button"
                                                >
                                                    Delete
                                                </button>

                                            </form>


                                        </div>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>


                    </table>


                <?php else: ?>


                    <div class="empty-state">

                        <strong>
                            No products found
                        </strong>

                        Your product catalog is currently empty.

                        <br>
                        <br>


                        <a
                            href="product_add.php"
                            class="add-product-button"
                        >

                            ＋ Add Your First Product

                        </a>

                    </div>


                <?php endif; ?>


            </div>


        </section>


    </main>


</div>


</body>

</html>