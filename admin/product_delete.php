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
   GET PRODUCT ID
========================================================= */

$product_id = (int) ($_GET["id"] ?? 0);

if ($product_id <= 0) {
    header("Location: products.php");
    exit;
}


/* =========================================================
   FIND PRODUCT
========================================================= */

$stmt = $conn->prepare("
    SELECT
        id,
        product_name,
        image
    FROM products
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Unable to prepare product query.");
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
   DELETE PRODUCT
========================================================= */

/*
   order_items.product_id uses ON DELETE RESTRICT.

   Therefore a product that already appears in an order
   cannot be deleted directly from the products table.

   In that case we show a clear message instead.
*/

$stmt = $conn->prepare("
    DELETE FROM products
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Unable to prepare delete query.");
}

$stmt->bind_param("i", $product_id);


if ($stmt->execute()) {

    $deleted_rows = $stmt->affected_rows;

    $stmt->close();


    if ($deleted_rows === 1) {

        /* ---------------------------------------------
           DELETE PRODUCT IMAGE

           Only delete images with the generated
           "product_" naming convention.

           This protects your existing project images.
        --------------------------------------------- */

        $image_name = basename(
            (string) ($product["image"] ?? "")
        );

        if (
            $image_name !== "" &&
            strpos($image_name, "product_") === 0
        ) {

            $image_path =
                dirname(__DIR__) .
                "/images/" .
                $image_name;

            if (is_file($image_path)) {

                unlink($image_path);
            }
        }


        header("Location: products.php?deleted=1");
        exit;

    }

    header("Location: products.php");
    exit;

}


/* =========================================================
   DELETE FAILED
========================================================= */

$error_message = $stmt->error;

$stmt->close();


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
        Cannot Delete Product | Siquijor Styles
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

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 20px;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f5f1eb;

            color: #2f2a25;
        }


        .card {

            width: 100%;

            max-width: 550px;

            padding: 32px;

            background: #ffffff;

            border: 1px solid #e5ddd5;

            border-radius: 16px;

            box-shadow:
                0 15px 40px
                rgba(47,42,37,0.10);

            text-align: center;
        }


        .icon {

            width: 58px;

            height: 58px;

            margin: 0 auto 18px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background: #fff0e0;

            color: #9a6835;

            font-size: 26px;

            font-weight: 700;
        }


        h1 {

            margin: 0 0 10px;

            font-size: 24px;
        }


        .product-name {

            margin: 15px 0;

            padding: 12px;

            border-radius: 9px;

            background: #faf8f5;

            border: 1px solid #eee7e0;

            font-weight: 700;
        }


        p {

            margin: 10px 0;

            color: #7e746c;

            font-size: 13px;

            line-height: 1.55;
        }


        .error-box {

            margin-top: 20px;

            padding: 14px;

            border-radius: 10px;

            background: #fff0f0;

            border: 1px solid #e0b0b0;

            color: #9d4040;

            font-size: 12px;

            line-height: 1.5;

            text-align: left;
        }


        .button {

            display: inline-block;

            margin-top: 22px;

            padding: 12px 19px;

            border-radius: 9px;

            background: #2f2a25;

            color: #ffffff;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;
        }


        .button:hover {

            background: #4a4036;
        }

    </style>

</head>


<body>


<div class="card">


    <div class="icon">
        !
    </div>


    <h1>
        Product Cannot Be Deleted
    </h1>


    <div class="product-name">

        <?= e($product["product_name"]) ?>

    </div>


    <p>

        This product could not be deleted.

        It may already be connected to an existing customer
        order. Products used in previous orders are protected
        so the order history remains intact.

    </p>


    <div class="error-box">

        <?= e($error_message) ?>

    </div>


    <a
        href="products.php"
        class="button"
    >
        ← Back to Products
    </a>


</div>


</body>

</html>