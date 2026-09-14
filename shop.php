<?php

require_once __DIR__ . "/php/session.php";

require_once "php/database.php";

header("Content-Type: text/html; charset=UTF-8");


/* =========================================================
   CURRENT USER
========================================================= */

$logged_in =
    !empty($_SESSION["logged_in"]) &&
    !empty($_SESSION["user_id"]);

$current_user = null;
$user_id = 0;

if ($logged_in) {

    $user_id =
        (int) $_SESSION["user_id"];

    $user_stmt =
        $conn->prepare(
            "SELECT id, full_name, email
             FROM users
             WHERE id = ?
             LIMIT 1"
        );

    if ($user_stmt) {

        $user_stmt->bind_param(
            "i",
            $user_id
        );

        $user_stmt->execute();

        $user_result =
            $user_stmt->get_result();

        if (
            $user_result &&
            $user_result->num_rows > 0
        ) {

            $current_user =
                $user_result->fetch_assoc();

        }

        $user_stmt->close();
    }
}


/* =========================================================
   PRODUCTS
========================================================= */

$products = [];

$product_stmt =
    $conn->prepare(
        "SELECT
            id,
            category_id,
            product_name,
            description,
            price,
            image,
            stock
         FROM products
         ORDER BY id ASC"
    );


if ($product_stmt) {

    $product_stmt->execute();

    $product_result =
        $product_stmt->get_result();

    while (
        $row =
        $product_result->fetch_assoc()
    ) {

        $products[] =
            $row;

    }

    $product_stmt->close();
}


/* =========================================================
   RATINGS
========================================================= */

$ratings = [

    1 => "4.8",
    2 => "4.6",
    3 => "4.7",
    4 => "4.9",
    5 => "4.5",
    6 => "4.8",
    7 => "4.7",
    8 => "4.6",
    9 => "4.9",
    10 => "4.7",
    11 => "4.8",
    12 => "4.5"

];


/* =========================================================
   CATEGORY
========================================================= */

function productCategory($id)
{

    $categories = [

        1 => "Tops",
        2 => "Shirts",
        3 => "Bottoms",
        4 => "Dresses",
        5 => "Accessories",
        6 => "Swimwear",
        7 => "Outerwear",
        8 => "Accessories",
        9 => "Dresses",
        10 => "Bottoms",
        11 => "Shirts",
        12 => "Accessories"

    ];


    return
        $categories[$id]
        ?? "Island Wear";
}


/* =========================================================
   CART COUNT
========================================================= */

$cart_count = 0;

if ($logged_in) {

    $cart_stmt =
        $conn->prepare(
            "SELECT
                COALESCE(
                    SUM(ci.quantity),
                    0
                ) AS total_items
             FROM cart c
             INNER JOIN cart_items ci
                ON ci.cart_id = c.id
             WHERE c.user_id = ?"
        );


    if ($cart_stmt) {

        $cart_stmt->bind_param(
            "i",
            $user_id
        );

        $cart_stmt->execute();

        $cart_result =
            $cart_stmt->get_result();

        if (
            $cart_result &&
            $cart_row =
            $cart_result->fetch_assoc()
        ) {

            $cart_count =
                (int)
                $cart_row["total_items"];

        }

        $cart_stmt->close();
    }
}


/* =========================================================
   CATEGORY FROM URL
========================================================= */

$selected_category =
    trim(
        $_GET["category"] ?? ""
    );


/* =========================================================
   ESCAPE
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
        Shop | Siquijor Styles
    </title>


    <!-- CSS -->

    <link
        rel="stylesheet"
        href="css/style.css"
    >


    <!-- GOOGLE FONTS -->

    <link
        href="https://fonts.googleapis.com/css2?family=Parisienne&family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet"
    >


    <!-- ICONS -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >


    <style>

        /* =====================================================
           SHOP IMAGE DISPLAY
        ===================================================== */

        .shop-product-image {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
        }

        .shop-product-image-box {
            position: relative;
            width: 100%;
            height: 190px;
            overflow: hidden;
            background: #d9e9e8;
        }

        .shop-product-image-box .favorite {
            z-index: 5;
        }


        /* =====================================================
           KEEP NAVIGATION CLICKABLE
        ===================================================== */

        .header {
            position: sticky;
            top: 0;
            z-index: 9999;
        }

        .navigation {
            position: relative;
            z-index: 10000;
        }

        .navigation a {
            position: relative;
            z-index: 10001;
            cursor: pointer;
        }

        .header-tools {
            position: relative;
            z-index: 10000;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 600px) {

            .shop-product-image-box {
                height: 160px;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     HEADER
========================================================= -->

<header class="header">


    <div class="logo-area">

        <a href="index.php">

            SIQUIJOR STYLES

        </a>

    </div>


    <nav class="navigation">

        <a href="index.php">Home</a>

        <a href="shop.php" class="active">Shop</a>

        <a href="index.php#collections">Collections <span>⌄</span></a>

        <a href="about.php">About</a>

        <a href="contact.php">Contact</a>

        <?php if ($logged_in): ?>
            <a href="orders.php">My Orders</a>
        <?php endif; ?>

        <a href="cart.php">Cart</a>

    </nav>


    <div class="header-tools">

        <?php if (
            $logged_in &&
            !empty($_SESSION["role"]) &&
            $_SESSION["role"] === "admin"
        ): ?>

            <a
                class="login-button"
                href="http://localhost/Website/admin/index.php"
                title="Admin Dashboard"
            >
                Admin Dashboard
            </a>

        <?php endif; ?>

        <?php if ($logged_in && $current_user): ?>

            <a
                class="signup-button"
                href="logout.php"
            >
                Log Out
            </a>

        <?php else: ?>

            <a
                class="login-button"
                href="login.php"
            >
                Log In
            </a>

            <a
                class="signup-button"
                href="signup.php"
            >
                Sign Up
            </a>

        <?php endif; ?>

    </div>

</header>



<!-- =========================================================
     MAIN
========================================================= -->

<main>


<section
    id="shop"
    class="featured-section shop-page-section">


    <div
        class="organic-shape featured-shape-left">
    </div>


    <div
        class="organic-shape featured-shape-right">
    </div>


    <!-- =====================================================
         SHOP HEADING
    ====================================================== -->

    <div class="section-top">


        <div>

            <p class="eyebrow">

                SIQUIJOR STYLES

            </p>


            <h2>

                Shop All

                <br>

                <span>
                    Products
                </span>

            </h2>


            <p>

                Discover island-inspired pieces
                made for every mood, moment,
                and adventure.

            </p>

        </div>


    </div>



    <!-- =====================================================
         CATEGORY FILTERS
    ====================================================== -->

    <div
        class="categories shop-categories"
    >


        <button
            class="category-item"
            type="button"
            onclick="showAllProducts()">

            <div class="category-icon">

                ✦

            </div>

            <p>
                All
            </p>

        </button>


        <button
            class="category-item"
            type="button"
            onclick="
                filterProducts('Tops')
            ">

            <div class="category-icon">

                ♡

            </div>

            <p>
                Tops
            </p>

        </button>


        <button
            class="category-item"
            type="button"
            onclick="
                filterProducts('Bottoms')
            ">

            <div class="category-icon">

                ♧

            </div>

            <p>
                Bottoms
            </p>

        </button>


        <button
            class="category-item"
            type="button"
            onclick="
                filterProducts('Dresses')
            ">

            <div class="category-icon">

                ♢

            </div>

            <p>
                Dresses
            </p>

        </button>


        <button
            class="category-item"
            type="button"
            onclick="
                filterProducts('Accessories')
            ">

            <div class="category-icon">

                □

            </div>

            <p>
                Accessories
            </p>

        </button>


        <button
            class="category-item"
            type="button"
            onclick="
                filterProducts('Swimwear')
            ">

            <div class="category-icon">

                ◇

            </div>

            <p>
                Swimwear
            </p>

        </button>


        <button
            class="category-item"
            type="button"
            onclick="
                filterProducts('Shirts')
            ">

            <div class="category-icon">

                ♙

            </div>

            <p>
                Shirts
            </p>

        </button>


    </div>



    <!-- =====================================================
         PRODUCT GRID
    ====================================================== -->

    <div
        class="product-grid"
        id="productGrid"
    >


        <?php if (
            empty($products)
        ): ?>


            <div
                class="modal-box"
                style="
                    grid-column:1 / -1;
                    margin:auto;
                "
            >

                <h3>
                    No Products Available
                </h3>


                <p>
                    There are currently no
                    products in the database.
                </p>

            </div>


        <?php else: ?>


            <?php foreach (
                $products as $product
            ): ?>


                <?php

                $product_id =
                    (int)
                    $product["id"];

                $product_name =
                    $product["product_name"];

                $price =
                    (float)
                    $product["price"];

                $stock =
                    (int)
                    $product["stock"];

                $image =
                    trim(
                        $product["image"]
                        ?? ""
                    );


                /* =================================================
                   SHOP IMAGE OVERRIDES
                   Exact filenames requested
                ================================================== */

                if ($product_id === 3) {

                    $image =
                        "IslandWarpSkirt.jpg";

                }

                if ($product_id === 6) {

                    $image =
                        "IlsandSwimSet.jpg";

                }

                if ($product_id === 8) {

                    $image =
                        "SiquijorSheelNecklace.jpg";

                }


                $rating =
                    $ratings[
                        $product_id
                    ]
                    ?? "4.8";

                $category =
                    productCategory(
                        $product_id
                    );

                ?>


                <article
                    class="product-card"
                    data-product-id="<?= $product_id ?>"
                    data-category="<?= e($category) ?>"
                    data-name="<?= e($product_name) ?>"
                    data-price="<?= $price ?>"
                    data-stock="<?= $stock ?>"
                    data-original-stock="<?= $stock ?>"
                >


                    <!-- =================================================
                         REAL PRODUCT PICTURE
                    ================================================== -->

                    <div
                        class="shop-product-image-box"
                    >


                        <?php if (
                            $image !== ""
                        ): ?>


                            <img
                                src="images/<?= e($image) ?>"
                                alt="<?= e($product_name) ?>"
                                class="shop-product-image"
                            >


                        <?php else: ?>


                            <div
                                class="image-placeholder"
                                style="
                                    width:100%;
                                    height:100%;
                                "
                            >

                                PRODUCT IMAGE

                            </div>


                        <?php endif; ?>


                        <button
                            class="favorite"
                            type="button"
                            onclick="favoriteProduct(this)"
                            aria-label="Favorite"
                        >

                            ♡

                        </button>


                    </div>



                    <!-- =================================================
                         PRODUCT DETAILS
                    ================================================== -->

                    <div class="product-info">


                        <h3>

                            <?= e(
                                $product_name
                            ) ?>

                        </h3>


                        <div class="rating">

                            ★★★★★

                            <span>

                                (
                                <?= e(
                                    $rating
                                ) ?>
                                )

                            </span>

                        </div>


                        <strong class="price">

                            ₱<?= number_format(
                                $price,
                                2
                            ) ?>

                        </strong>


                        <?php if (
                            $stock > 0
                        ): ?>


                            <div
                                class="product-stock"
                            >

                                Stock:
                                <?= $stock ?>

                            </div>


                            <button
                                class="add-cart"
                                type="button"
                                onclick='addToCart(
                                    <?= $product_id ?>,
                                    <?= json_encode(
                                        $product_name
                                    ) ?>,
                                    <?= $price ?>,
                                    <?= $stock ?>
                                )'
                            >

                                🛒 Add to Cart

                            </button>


                        <?php else: ?>


                            <div
                                class="
                                    product-stock
                                    out-of-stock
                                "
                            >

                                Out of Stock

                            </div>


                            <button
                                class="add-cart"
                                type="button"
                                disabled
                            >

                                OUT OF STOCK

                            </button>


                        <?php endif; ?>


                        <button
                            class="quick-view"
                            type="button"
                            onclick='quickView(
                                <?= $product_id ?>,
                                <?= json_encode(
                                    $product_name
                                ) ?>,
                                <?= $price ?>,
                                <?= $stock ?>
                            )'
                        >

                            ◉ Quick View

                        </button>


                    </div>


                </article>


            <?php endforeach; ?>


        <?php endif; ?>


    </div>


</section>


</main>



<!-- =========================================================
     SEARCH MODAL
========================================================= -->

<div
    id="searchModal"
    class="modal"
>


    <div
        class="modal-box search-box"
    >


        <button
            class="close-modal"
            type="button"
            onclick="
                closeModal('searchModal')
            "
        >

            ×

        </button>


        <h2>
            Search
        </h2>


        <p>
            Search the Siquijor Styles collection.
        </p>


        <input
            id="searchInput"
            type="text"
            placeholder="Search products..."
            oninput="searchProducts()"
        >


        <div
            id="searchResults"
        ></div>


    </div>

</div>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer class="footer">


    <div class="footer-brand">

        <div class="footer-logo-slot">

            <strong
                style="
                    font-family:'Playfair Display',serif;
                    font-size:20px;
                    color:#176d86;
                "
            >

                SIQUIJOR STYLES

            </strong>

        </div>


        <p>

            Island-inspired fashion,
            rooted in Siquijor.

        </p>

    </div>


    <div class="footer-column">

        <h4>
            SHOP
        </h4>


        <a href="shop.php">
            All Products
        </a>


        <a href="index.php#collections">
            Collections
        </a>


        <a href="shop.php">
            New Arrivals
        </a>

    </div>


    <div class="footer-column">

        <h4>
            ABOUT
        </h4>


        <a href="about.php">
            Our Story
        </a>


        <a href="contact.php">
            Contact
        </a>

    </div>


    <div class="footer-column">

        <h4>
            ACCOUNT
        </h4>


        <a href="orders.php">
            My Orders
        </a>


        <a href="cart.php">
            Cart
        </a>

    </div>


    <div class="footer-column">

        <h4>
            HELP
        </h4>


        <a href="contact.php">
            Support
        </a>


        <a href="contact.php">
            Delivery
        </a>

    </div>


</footer>


<div class="copyright">

    © <?= date("Y") ?>
    Siquijor Styles.
    All rights reserved.

</div>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script src="js/script.js"></script>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const category =
            <?= json_encode(
                $selected_category
            ) ?>;


        if (
            category &&
            typeof filterProducts === "function"
        ) {

            filterProducts(category);

        }

    }
);

</script>


</body>
</html>