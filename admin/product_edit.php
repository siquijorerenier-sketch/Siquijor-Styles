<?php

session_start();

require_once "../php/database.php";


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
   GET PRODUCT ID
========================================================= */

$product_id = (int) ($_GET["id"] ?? $_POST["product_id"] ?? 0);

if ($product_id <= 0) {
    header("Location: products.php");
    exit;
}


/* =========================================================
   LOAD PRODUCT
========================================================= */

$stmt = $conn->prepare("
    SELECT
        id,
        category_id,
        product_name,
        description,
        price,
        image,
        stock
    FROM products
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Unable to load the product.");
}

$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();

if (!$result || $result->num_rows !== 1) {

    $stmt->close();

    header("Location: products.php");
    exit;
}

$product = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   VARIABLES
========================================================= */

$product_name = $product["product_name"];
$description = $product["description"] ?? "";
$category_id = (int) $product["category_id"];
$price = $product["price"];
$stock = (int) $product["stock"];
$current_image = $product["image"] ?? "";

$error = "";
$success = false;


/* =========================================================
   LOAD CATEGORIES
========================================================= */

$categories = [];

$result = $conn->query("
    SELECT
        id,
        category_name
    FROM categories
    ORDER BY category_name ASC
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $categories[] = $row;
    }

    $result->free();
}


/* =========================================================
   PROCESS FORM
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_name = trim($_POST["product_name"] ?? "");

    $description = trim($_POST["description"] ?? "");

    $category_id = (int) ($_POST["category_id"] ?? 0);

    $price = trim($_POST["price"] ?? "");

    $stock = (int) ($_POST["stock"] ?? 0);


    /* =====================================================
       VALIDATION
    ====================================================== */

    if ($product_name === "") {

        $error = "Please enter a product name.";

    } elseif (mb_strlen($product_name) > 150) {

        $error = "Product name must not exceed 150 characters.";

    } elseif ($category_id <= 0) {

        $error = "Please select a category.";

    } elseif ($price === "" || !is_numeric($price)) {

        $error = "Please enter a valid product price.";

    } elseif ((float) $price < 0) {

        $error = "Product price cannot be negative.";

    } elseif ($stock < 0) {

        $error = "Stock cannot be negative.";
    }


    /* =====================================================
       CHECK CATEGORY
    ====================================================== */

    if ($error === "") {

        $category_check = $conn->prepare("
            SELECT id
            FROM categories
            WHERE id = ?
            LIMIT 1
        ");

        if (!$category_check) {

            $error = "Unable to verify the selected category.";

        } else {

            $category_check->bind_param(
                "i",
                $category_id
            );

            $category_check->execute();

            $category_result = $category_check->get_result();

            if (
                !$category_result ||
                $category_result->num_rows !== 1
            ) {

                $error = "The selected category does not exist.";
            }

            $category_check->close();
        }
    }


    /* =====================================================
       IMAGE HANDLING
    ====================================================== */

    $new_image_name = $current_image;
    $uploaded_new_image = false;

    if (
        $error === "" &&
        isset($_FILES["image"]) &&
        $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {

        $file = $_FILES["image"];


        /* -------------------------------------------------
           UPLOAD ERROR
        ------------------------------------------------- */

        if ($file["error"] !== UPLOAD_ERR_OK) {

            $error = "There was a problem uploading the image.";

        }


        /* -------------------------------------------------
           FILE SIZE
        ------------------------------------------------- */

        elseif ($file["size"] > 5 * 1024 * 1024) {

            $error = "The product image must be 5 MB or smaller.";

        }


        /* -------------------------------------------------
           MIME TYPE
        ------------------------------------------------- */

        else {

            $allowed_types = [
                "image/jpeg",
                "image/png",
                "image/webp"
            ];

            $finfo = new finfo(FILEINFO_MIME_TYPE);

            $mime_type = $finfo->file($file["tmp_name"]);


            if (!in_array($mime_type, $allowed_types, true)) {

                $error =
                    "Only JPG, PNG, and WebP images are allowed.";
            }
        }


        /* -------------------------------------------------
           SAVE NEW IMAGE
        ------------------------------------------------- */

        if ($error === "") {

            $extension_map = [
                "image/jpeg" => "jpg",
                "image/png" => "png",
                "image/webp" => "webp"
            ];

            $extension = $extension_map[$mime_type];

            $new_image_name =
                "product_" .
                time() .
                "_" .
                bin2hex(random_bytes(5)) .
                "." .
                $extension;


            $upload_directory =
                dirname(__DIR__) . "/images/";


            $upload_path =
                $upload_directory .
                $new_image_name;


            if (!is_dir($upload_directory)) {

                $error = "The images folder does not exist.";

            } elseif (
                !move_uploaded_file(
                    $file["tmp_name"],
                    $upload_path
                )
            ) {

                $error = "Unable to save the uploaded image.";

                $new_image_name = $current_image;

            } else {

                $uploaded_new_image = true;
            }
        }
    }


    /* =====================================================
       UPDATE PRODUCT
    ====================================================== */

    if ($error === "") {

        $price_value = (float) $price;


        $stmt = $conn->prepare("
            UPDATE products
            SET
                category_id = ?,
                product_name = ?,
                description = ?,
                price = ?,
                image = ?,
                stock = ?
            WHERE id = ?
            LIMIT 1
        ");


        if (!$stmt) {

            $error =
                "Unable to prepare the product update.";

        } else {

            $stmt->bind_param(
                "issdsii",
                $category_id,
                $product_name,
                $description,
                $price_value,
                $new_image_name,
                $stock,
                $product_id
            );


            if ($stmt->execute()) {

                $success = true;


                /* -----------------------------------------
                   REMOVE OLD IMAGE AFTER SUCCESSFUL UPDATE
                ----------------------------------------- */

                if (
                    $uploaded_new_image &&
                    $current_image !== "" &&
                    $current_image !== $new_image_name
                ) {

                    $old_image_path =
                        dirname(__DIR__) .
                        "/images/" .
                        basename($current_image);

                    if (is_file($old_image_path)) {

                        unlink($old_image_path);
                    }
                }


                $current_image = $new_image_name;

            } else {

                $error =
                    "Unable to update the product. "
                    . $stmt->error;


                /* -----------------------------------------
                   REMOVE NEW IMAGE IF DATABASE UPDATE FAILED
                ----------------------------------------- */

                if ($uploaded_new_image) {

                    $new_image_path =
                        dirname(__DIR__) .
                        "/images/" .
                        basename($new_image_name);

                    if (is_file($new_image_path)) {

                        unlink($new_image_path);
                    }

                    $new_image_name = $current_image;
                }
            }

            $stmt->close();
        }
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
        Edit Product | Siquijor Styles
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

            border-bottom: 1px solid
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

            transform: translateX(2px);
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

            border: 1px solid
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
           MAIN
        ================================================= */

        .main-content {

            flex: 1;

            min-width: 0;

            padding: 30px;
        }


        .topbar {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

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


        .back-link {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 11px 15px;

            border-radius: 9px;

            background: #ffffff;

            border: 1px solid #ded5cc;

            color: #51483f;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;
        }


        .back-link:hover {

            background: #faf7f3;
        }


        /* =================================================
           FORM CARD
        ================================================= */

        .form-card {

            max-width: 900px;

            background: #ffffff;

            border: 1px solid #e5ddd5;

            border-radius: 16px;

            padding: 28px;

            box-shadow:
                0 5px 18px
                rgba(47,42,37,0.04);
        }


        .form-card h2 {

            margin: 0 0 7px;

            font-size: 20px;
        }


        .form-intro {

            margin: 0 0 25px;

            color: #8a8078;

            font-size: 13px;

            line-height: 1.5;
        }


        /* =================================================
           MESSAGES
        ================================================= */

        .message {

            margin-bottom: 22px;

            padding: 14px 15px;

            border-radius: 10px;

            font-size: 13px;

            line-height: 1.5;
        }


        .error-message {

            background: #fff0f0;

            border: 1px solid #e1adad;

            color: #9e3e3e;
        }


        .success-message {

            background: #eaf7ed;

            border: 1px solid #acd3b3;

            color: #367246;
        }


        /* =================================================
           FORM GRID
        ================================================= */

        .form-grid {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 20px;
        }


        .form-group {

            display: flex;

            flex-direction: column;
        }


        .form-group.full-width {

            grid-column: 1 / -1;
        }


        .form-group label {

            margin-bottom: 8px;

            font-size: 13px;

            font-weight: 700;

            color: #4a423b;
        }


        .required {

            color: #a14949;
        }


        .form-group input,
        .form-group select,
        .form-group textarea {

            width: 100%;

            border: 1px solid #d9d0c7;

            border-radius: 9px;

            padding: 12px 13px;

            font-family: inherit;

            font-size: 14px;

            color: #322c27;

            background: #ffffff;

            outline: none;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }


        .form-group input,
        .form-group select {

            height: 46px;
        }


        .form-group textarea {

            min-height: 130px;

            resize: vertical;

            line-height: 1.5;
        }


        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {

            border-color: #9d7958;

            box-shadow:
                0 0 0 3px
                rgba(157,121,88,0.12);
        }


        .help-text {

            margin-top: 7px;

            color: #9a9088;

            font-size: 11px;

            line-height: 1.45;
        }


        /* =================================================
           CURRENT IMAGE
        ================================================= */

        .current-image-box {

            margin-top: 10px;

            padding: 12px;

            border: 1px solid #e6ded6;

            border-radius: 10px;

            background: #faf8f5;
        }


        .current-image {

            display: block;

            width: 130px;

            height: 130px;

            object-fit: cover;

            border-radius: 9px;

            border: 1px solid #ded5cc;

            background: #f3eee8;
        }


        .no-image {

            width: 130px;

            height: 130px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 9px;

            border: 1px solid #ded5cc;

            background: #f3eee8;

            color: #948980;

            font-size: 11px;

            text-align: center;
        }


        /* =================================================
           FORM ACTIONS
        ================================================= */

        .form-actions {

            display: flex;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 28px;

            padding-top: 22px;

            border-top: 1px solid #eee7e0;
        }


        .button {

            border: none;

            border-radius: 9px;

            padding: 12px 18px;

            font-size: 13px;

            font-weight: 700;

            text-decoration: none;

            cursor: pointer;

            transition:
                transform 0.2s ease,
                background 0.2s ease;
        }


        .cancel-button {

            background: #f1ede8;

            color: #5b5149;
        }


        .cancel-button:hover {

            background: #e9e2da;

            transform: translateY(-1px);
        }


        .submit-button {

            background: #2f2a25;

            color: #ffffff;
        }


        .submit-button:hover {

            background: #4b4138;

            transform: translateY(-1px);
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

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

            .main-content {

                padding: 15px;
            }


            .topbar {

                flex-direction: column;
            }


            .topbar h1 {

                font-size: 25px;
            }


            .form-card {

                padding: 20px;
            }


            .form-grid {

                grid-template-columns: 1fr;
            }


            .form-group.full-width {

                grid-column: auto;
            }


            .admin-nav {

                grid-template-columns: 1fr;
            }


            .form-actions {

                flex-direction: column-reverse;
            }


            .button {

                width: 100%;

                text-align: center;
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
        <img src="../images/logo.png" alt="Siquijor Styles">
    </div>

    <div class="admin-badge">
        <strong><?= e($admin_name) ?></strong>
        <span>Administrator</span>
    </div>

    <div class="nav-title">
        Management
    </div>

    <nav class="admin-nav">

        <a href="http://localhost/Website/admin/index.php">
            <span class="nav-icon">⌂</span>
            Dashboard
        </a>

        <a href="http://localhost/Website/admin/products.php">
            <span class="nav-icon">▣</span>
            Products
        </a>

        <a href="http://localhost/Website/admin/users.php">
            <span class="nav-icon">♙</span>
            Customers
        </a>

        <a href="http://localhost/Website/admin/orders.php">
            <span class="nav-icon">▤</span>
            Orders
        </a>

    </nav>

    <div class="sidebar-bottom">

        <a
            href="http://localhost/Website/index.php"
            class="logout-link">
            View Website
        </a>

        <a
            href="http://localhost/Website/admin/logout.php"
            class="logout-link"
            style="margin-top: 8px;">
            Log Out
        </a>

    </div>

</aside>

    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">


        <div class="topbar">


            <div>

                <h1>
                    Edit Product
                </h1>

                <p>
                    Update the product information below.
                </p>

            </div>


            <a
                href="products.php"
                class="back-link"
            >
                ← Back to Products
            </a>


        </div>


        <section class="form-card">


            <h2>
                Product Information
            </h2>


            <p class="form-intro">

                Change the product details, stock, price, category,
                or image. Leave the image unchanged to keep the
                current product image.

            </p>


            <!-- =================================================
                 SUCCESS MESSAGE
            ================================================== -->

            <?php if ($success): ?>

                <div class="message success-message">

                    Product updated successfully.

                </div>

            <?php endif; ?>


            <!-- =================================================
                 ERROR MESSAGE
            ================================================== -->

            <?php if ($error !== ""): ?>

                <div class="message error-message">

                    <?= e($error) ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 EDIT FORM
            ================================================== -->

            <form
                method="POST"
                action=""
                enctype="multipart/form-data"
            >


                <input
                    type="hidden"
                    name="product_id"
                    value="<?= $product_id ?>"
                >


                <div class="form-grid">


                    <!-- PRODUCT NAME -->

                    <div class="form-group full-width">

                        <label for="product_name">

                            Product Name
                            <span class="required">*</span>

                        </label>


                        <input
                            type="text"
                            id="product_name"
                            name="product_name"
                            maxlength="150"
                            value="<?= e($product_name) ?>"
                            required
                        >

                    </div>


                    <!-- CATEGORY -->

                    <div class="form-group">

                        <label for="category_id">

                            Category
                            <span class="required">*</span>

                        </label>


                        <select
                            id="category_id"
                            name="category_id"
                            required
                        >

                            <option value="">
                                Select category
                            </option>


                            <?php foreach ($categories as $category): ?>

                                <option
                                    value="<?= (int) $category["id"] ?>"
                                    <?= $category_id === (int) $category["id"]
                                        ? "selected"
                                        : "" ?>
                                >

                                    <?= e($category["category_name"]) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- PRICE -->

                    <div class="form-group">

                        <label for="price">

                            Price
                            <span class="required">*</span>

                        </label>


                        <input
                            type="number"
                            id="price"
                            name="price"
                            min="0"
                            step="0.01"
                            value="<?= e($price) ?>"
                            required
                        >

                    </div>


                    <!-- STOCK -->

                    <div class="form-group">

                        <label for="stock">

                            Stock Quantity
                            <span class="required">*</span>

                        </label>


                        <input
                            type="number"
                            id="stock"
                            name="stock"
                            min="0"
                            step="1"
                            value="<?= e($stock) ?>"
                            required
                        >


                        <div class="help-text">

                            Set to 0 when the product is unavailable.

                        </div>

                    </div>


                    <!-- IMAGE -->

                    <div class="form-group">

                        <label for="image">

                            Replace Product Image

                        </label>


                        <input
                            type="file"
                            id="image"
                            name="image"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >


                        <div class="help-text">

                            JPG, PNG, or WebP. Maximum 5 MB.
                            Leave empty to keep the current image.

                        </div>

                    </div>


                    <!-- CURRENT IMAGE -->

                    <div class="form-group">

                        <label>
                            Current Image
                        </label>


                        <div class="current-image-box">


                            <?php if ($current_image !== ""): ?>

                                <img
                                    src="../images/<?= e($current_image) ?>"
                                    alt="<?= e($product_name) ?>"
                                    class="current-image"
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


                        </div>

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="form-group full-width">

                        <label for="description">

                            Description

                        </label>


                        <textarea
                            id="description"
                            name="description"
                        ><?= e($description) ?></textarea>

                        <div class="checkout-tip">

    <i class="fa-solid fa-location-dot"></i>

                    <span>
                        <strong>Delivery tip:</strong>
                        Enter your complete address including your barangay,
                        municipality, and province to help prevent delivery delays.
                    </span>

                </div>


                    </div>


                </div>


                <!-- ACTIONS -->

                <div class="form-actions">


                    <a
                        href="products.php"
                        class="button cancel-button"
                    >
                        Cancel
                    </a>


                    <button
                        type="submit"
                        class="button submit-button"
                    >
                        Save Changes
                    </button>


                </div>


            </form>


        </section>


    </main>


</div>


</body>

</html>