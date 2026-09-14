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
   VARIABLES
========================================================= */

$product_name = "";
$description = "";
$category_id = "";
$price = "";
$stock = "";

$error = "";

$success = false;


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
       HANDLE IMAGE UPLOAD
    ====================================================== */

    $image_name = null;

    if (
        $error === "" &&
        isset($_FILES["image"]) &&
        $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {

        $file = $_FILES["image"];


        /* -------------------------------------------------
           CHECK UPLOAD ERROR
        ------------------------------------------------- */

        if ($file["error"] !== UPLOAD_ERR_OK) {

            $error = "There was a problem uploading the image.";

        }


        /* -------------------------------------------------
           MAXIMUM FILE SIZE
           5 MB
        ------------------------------------------------- */

        elseif ($file["size"] > 5 * 1024 * 1024) {

            $error = "The product image must be 5 MB or smaller.";

        }


        /* -------------------------------------------------
           CHECK MIME TYPE
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

                $error = "Only JPG, PNG, and WebP images are allowed.";

            }
        }


        /* -------------------------------------------------
           GENERATE SAFE FILE NAME
        ------------------------------------------------- */

        if ($error === "") {

            $extension_map = [
                "image/jpeg" => "jpg",
                "image/png" => "png",
                "image/webp" => "webp"
            ];

            $extension = $extension_map[$mime_type];

            $image_name =
                "product_" .
                time() .
                "_" .
                bin2hex(random_bytes(5)) .
                "." .
                $extension;


            $upload_directory = dirname(__DIR__) . "/images/";

            $upload_path =
                $upload_directory .
                $image_name;


            /* -------------------------------------------------
               MAKE SURE IMAGE DIRECTORY EXISTS
            ------------------------------------------------- */

            if (!is_dir($upload_directory)) {

                $error = "The images folder does not exist.";

            }


            /* -------------------------------------------------
               MOVE FILE
            ------------------------------------------------- */

            elseif (
                !move_uploaded_file(
                    $file["tmp_name"],
                    $upload_path
                )
            ) {

                $error = "Unable to save the uploaded image.";

                $image_name = null;
            }
        }
    }


    /* =====================================================
       INSERT PRODUCT
    ====================================================== */

    if ($error === "") {

        $price_value = (float) $price;

        $stmt = $conn->prepare("
            INSERT INTO products
            (
                category_id,
                product_name,
                description,
                price,
                image,
                stock
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");


        if (!$stmt) {

            $error =
                "Unable to prepare the product database query.";

            /* ---------------------------------------------
               REMOVE UPLOADED IMAGE IF INSERT PREPARATION
               FAILED
            --------------------------------------------- */

            if ($image_name !== null) {

                $uploaded_file =
                    dirname(__DIR__) .
                    "/images/" .
                    $image_name;

                if (is_file($uploaded_file)) {

                    unlink($uploaded_file);
                }
            }

        } else {

            $stmt->bind_param(
                "issdsi",
                $category_id,
                $product_name,
                $description,
                $price_value,
                $image_name,
                $stock
            );


            if ($stmt->execute()) {

                $success = true;


                /* -----------------------------------------
                   CLEAR FORM
                ----------------------------------------- */

                $product_name = "";

                $description = "";

                $category_id = "";

                $price = "";

                $stock = "";

            } else {

                $error =
                    "Unable to add the product. "
                    . $stmt->error;


                /* -----------------------------------------
                   REMOVE UPLOADED IMAGE IF DATABASE INSERT
                   FAILED
                ----------------------------------------- */

                if ($image_name !== null) {

                    $uploaded_file =
                        dirname(__DIR__) .
                        "/images/" .
                        $image_name;

                    if (is_file($uploaded_file)) {

                        unlink($uploaded_file);
                    }
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
        Add Product | Siquijor Styles
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
           FILE INPUT
        ================================================= */

        .form-group input[type="file"] {

            height: auto;

            padding: 10px;

            cursor: pointer;
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

            <img
                src="../images/logo.png"
                alt="Siquijor Styles"
            >

        </div>


        <div class="admin-badge">

            <strong>
                <?= e($_SESSION["full_name"] ?? "Administrator") ?>
            </strong>

            <span>
                Administrator
            </span>

        </div>


        <div class="nav-title">
            Management
        </div>


        <nav class="admin-nav">


            <a href="index.php">

                <span class="nav-icon">⌂</span>

                Dashboard

            </a>


            <a
                href="products.php"
                class="active"
            >

                <span class="nav-icon">▣</span>

                Products

            </a>


            <a href="users.php">

                <span class="nav-icon">♙</span>

                Customers

            </a>


            <a href="orders.php">

                <span class="nav-icon">▤</span>

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


        <!-- TOPBAR -->

        <div class="topbar">


            <div>

                <h1>
                    Add Product
                </h1>

                <p>
                    Add a new product to the Siquijor Styles catalog.
                </p>

            </div>


            <a
                href="products.php"
                class="back-link"
            >
                ← Back to Products
            </a>


        </div>


        <!-- =================================================
             FORM CARD
        ================================================== -->

        <section class="form-card">


            <h2>
                Product Information
            </h2>


            <p class="form-intro">

                Enter the details below. Products added here will
                appear in your store's product catalog.

            </p>


            <!-- =================================================
                 SUCCESS MESSAGE
            ================================================== -->

            <?php if ($success): ?>

                <div class="message success-message">

                    Product added successfully.

                    <br>

                    You can add another product or return to
                    the product management page.

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
                 PRODUCT FORM
            ================================================== -->

            <form
                method="POST"
                action=""
                enctype="multipart/form-data"
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
                            placeholder="Enter product name"
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
                                    <?= ((string) $category_id === (string) $category["id"])
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
                            placeholder="0.00"
                            required
                        >


                        <div class="help-text">

                            Enter the price in Philippine pesos.

                        </div>

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
                            placeholder="0"
                            required
                        >


                        <div class="help-text">

                            Set the number of items currently available.

                        </div>

                    </div>


                    <!-- IMAGE -->

                    <div class="form-group">

                        <label for="image">

                            Product Image

                        </label>


                        <input
                            type="file"
                            id="image"
                            name="image"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >


                        <div class="help-text">

                            JPG, PNG, or WebP. Maximum file size: 5 MB.

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
                            placeholder="Describe the product..."
                        ><?= e($description) ?></textarea>

                        <div class="checkout-tip">

    <i class="fa-solid fa-location-dot"></i>

                    <span>
                        <strong>Delivery tip:</strong>
                        Enter your complete address including your barangay,
                        municipality, and province to help prevent delivery delays.
                    </span>

                </div>


                        <div class="help-text">

                            Give customers useful information about the product.

                        </div>

                    </div>


                </div>


                <!-- FORM ACTIONS -->

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
                        Add Product
                    </button>


                </div>


            </form>


        </section>


    </main>


</div>


</body>

</html>