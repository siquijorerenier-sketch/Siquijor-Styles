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

    $user_id = (int) $_SESSION["user_id"];

    $user_stmt = $conn->prepare(
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

        $user_result = $user_stmt->get_result();

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
   FEATURED PRODUCTS
========================================================= */

$products = [];

$product_stmt = $conn->prepare(
    "SELECT
        id,
        category_id,
        product_name,
        description,
        price,
        image,
        stock
     FROM products
     ORDER BY id ASC
     LIMIT 5"
);

if ($product_stmt) {

    $product_stmt->execute();

    $product_result =
        $product_stmt->get_result();

    while (
        $row =
        $product_result->fetch_assoc()
    ) {

        $products[] = $row;
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

    $cart_stmt = $conn->prepare(
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
        Siquijor Styles
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


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >


    <style>

        /* =====================================================
           HEADER LOGO
        ===================================================== */

        .logo-area {
            display: flex;
            align-items: center;
        }

        .logo-area a {
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .logo-area img {
            width: auto;
            height: 52px;
            display: block;
            object-fit: contain;
        }


        /* =====================================================
           STORY CIRCLES HIDDEN
        ===================================================== */

        .story-circles {
            display: none !important;
        }


        /* =====================================================
           HERO IMAGE
        ===================================================== */

        .hero-image {
            overflow: hidden;
            position: relative;
        }

        .hero-image img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            object-position: center center;
        }


        /* =====================================================
           STORY IMAGE
        ===================================================== */

        .story-image {
            overflow: hidden;
            position: relative;
        }

        .story-image img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            object-position: center center;
        }


        /* =====================================================
           PRODUCT IMAGE
        ===================================================== */

        .product-image {
            overflow: hidden;
            position: relative;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: contain;
            object-position: center center;
        }


        /* =====================================================
           REVIEW PHOTO
        ===================================================== */

        .review-photo {
            overflow: hidden;
            position: relative;
            flex-shrink: 0;
        }

        .review-photo img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            object-position: center center;
        }


        /* =====================================================
           COLLECTION IMAGE
        ===================================================== */

        .collection-image {
            overflow: hidden;
            position: relative;
        }

        .collection-image img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            object-position: center center;
        }


        /* =====================================================
           RESPONSIVE LOGO
        ===================================================== */

        @media (max-width: 768px) {

            .logo-area img {
                height: 44px;
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
            <img src="images/logo.png" alt="Siquijor Styles">
        </a>
    </div>

    <nav class="navigation">
        <a href="index.php" class="active">Home</a>
        <a href="shop.php">Shop</a>
        <a href="#collections">Collections <span>⌄</span></a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
        <a href="cart.php">Cart</a>
        <?php if ($logged_in): ?>
            <a href="orders.php">My Orders</a>
        <?php endif; ?>
    </nav>

    <div class="header-tools">
        <?php if ($logged_in && $current_user): ?>
            <a class="login-button" href="orders.php"><?= e($current_user["full_name"]) ?></a>
            <a class="signup-button" href="logout.php">Log Out</a>
        <?php else: ?>
            <a class="login-button" href="login.php">Log In</a>
            <a class="signup-button" href="signup.php">Sign Up</a>
        <?php endif; ?>
    </div>

</header>



<main>


<!-- =========================================================
     HERO
========================================================= -->

<section
    id="home"
    class="hero-section"
>


    <div
        class="organic-shape hero-shape-one">
    </div>


    <div
        class="organic-shape hero-shape-two">
    </div>


    <div
        class="palm-decoration palm-left">

        ☘

    </div>


    <div class="hero-content">


        <p class="eyebrow">

            ISLAND INSPIRED

        </p>


        <h1>

            Timeless Style,

            <br>

            <span>
                Island Soul
            </span>

        </h1>


        <p>

            Discover our bestsellers,
            inspired by the beauty and culture
            of Siquijor Island.

        </p>


        <div class="hero-buttons">


            <a
                href="shop.php"
                class="primary-button"
            >

                Shop Now →

            </a>


            <a
                href="#collections"
                class="secondary-button"
            >

                Explore Collections

            </a>


        </div>


    </div>


    <div class="hero-image">


        <img
            src="images/timeless_time.png"
            alt="Siquijor Island"
        >


    </div>


    <div class="hero-dots">

        <span class="active"></span>
        <span></span>
        <span></span>

    </div>


</section>



<!-- =========================================================
     BENEFITS
========================================================= -->

<section class="benefits-section">


    <div class="benefit">


        <div class="benefit-icon">

            ♡

        </div>


        <div>

            <strong>
                Island Inspired
            </strong>

            <small>
                thoughtfully designed
            </small>

        </div>


    </div>


    <div class="benefit">


        <div class="benefit-icon">

            ✦

        </div>


        <div>

            <strong>
                Quality Pieces
            </strong>

            <small>
                made to last
            </small>

        </div>


    </div>


    <div class="benefit">


        <div class="benefit-icon">

            ◇

        </div>


        <div>

            <strong>
                Easy Returns
            </strong>

            <small>
                within 7 days
            </small>

        </div>


    </div>


    <div class="benefit">


        <div class="benefit-icon">

            ♧

        </div>


        <div>

            <strong>
                Customer Support
            </strong>

            <small>
                we're here to help
            </small>

        </div>


    </div>


</section>



<!-- =========================================================
     SHOP BY CATEGORY
========================================================= -->

<section class="category-section">


    <p class="section-eyebrow">
        EXPLORE
    </p>


    <h2>
        Shop by Category
    </h2>


    <div class="categories">


        <button
            class="category-item"
            type="button"
            onclick="window.location.href='shop.php?category=Tops'"
        >

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
            onclick="window.location.href='shop.php?category=Bottoms'"
        >

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
            onclick="window.location.href='shop.php?category=Dresses'"
        >

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
            onclick="window.location.href='shop.php?category=Accessories'"
        >

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
            onclick="window.location.href='shop.php?category=Swimwear'"
        >

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
            onclick="window.location.href='shop.php?category=Shirts'"
        >

            <div class="category-icon">
                ♙
            </div>

            <p>
                Shirts
            </p>

        </button>


    </div>


</section>



<!-- =========================================================
     OUR STORY
========================================================= -->

<section
    id="about"
    class="story-section"
>


    <div
        class="organic-shape story-shape">
    </div>


    <div class="story-image">


        <img
            src="images/Rooted_in_Siquijor.jpg"
            alt="Rooted in Siquijor"
        >


    </div>


    <div class="story-content">


        <p class="eyebrow">

            OUR STORY

        </p>


        <h2>

            ROOTED IN

            <br>

            SIQUIJOR

        </h2>


        <div class="script-text">

            MADE FOR EVERYWHERE

        </div>


        <p>

            Inspired by the island's natural beauty,
            culture, and relaxed way of life.

        </p>


        <a
            href="about.php"
            class="primary-button"
        >

            Learn More About Us

        </a>


    </div>


    <div class="story-circles">

        <span></span>
        <span></span>
        <span></span>
        <span></span>

    </div>


</section>



<!-- =========================================================
     FEATURED PRODUCTS
========================================================= -->

<section
    id="featured"
    class="featured-section"
>


    <div
        class="organic-shape featured-shape-left">
    </div>


    <div
        class="organic-shape featured-shape-right">
    </div>


    <div class="section-top">


        <div>


            <p class="eyebrow">

                FEATURED PRODUCTS

            </p>


            <h2>

                Island Vibes,

                <br>

                <span>
                    Everyday Style
                </span>

            </h2>


            <p>

                Discover our bestsellers,
                inspired by the beauty and
                culture of Siquijor Island.

            </p>


        </div>


        <a
            href="shop.php"
            class="view-products"
        >

            View All Products →

        </a>


    </div>



    <div class="product-grid">


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

                $description =
                    $product["description"]
                    ?? "";

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
                   HOME IMAGE OVERRIDES
                ================================================== */

                if ($product_id === 3) {

                    $image =
                        "tropicaSunsetmaxiDress.jpg";
                }

                if ($product_id === 4) {

                    $image =
                        "SunSetDress.jpg";
                }


                $rating =
                    $ratings[
                        $product_id
                    ]
                    ?? "4.8";

                ?>



                <article
                    class="product-card"
                    data-product-id="<?= $product_id ?>"
                    data-category="<?= e(
                        productCategory(
                            $product_id
                        )
                    ) ?>"
                    data-name="<?= e(
                        $product_name
                    ) ?>"
                    data-price="<?= $price ?>"
                    data-stock="<?= $stock ?>"
                    data-original-stock="<?= $stock ?>"
                >


                    <div class="product-image">


                        <?php if (
                            $image !== ""
                        ): ?>


                            <img
                                src="images/<?= e($image) ?>"
                                alt="<?= e(
                                    $product_name
                                ) ?>"
                            >


                        <?php else: ?>


                            <div class="image-placeholder">

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



                    <div class="product-info">


                        <h3>

                            <?= e(
                                $product_name
                            ) ?>

                        </h3>


                        <div class="rating">

                            ★★★★★

                            <span>

                                (<?= e(
                                    $rating
                                ) ?>)

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



<!-- =========================================================
     REVIEWS
========================================================= -->

<section class="reviews-section">


    <p class="eyebrow">

        HAPPY CUSTOMERS

    </p>


    <h2>

        Loved by Our Customers

    </h2>


    <p class="reviews-intro">

        Real words from people who love
        Siquijor Styles.

    </p>


    <div class="reviews-grid">


        <!-- MARIA -->

        <article class="review-card">


            <div class="review-header">


                <div class="review-photo">


                    <img
                        src="images/Maria_Santos.jpg"
                        alt="Maria Santos"
                    >


                </div>


                <div>


                    <h3>
                        Maria Santos
                    </h3>


                    <div class="review-stars">

                        ★★★★★

                    </div>


                </div>


            </div>


            <p>

                I absolutely love the
                island-inspired designs.
                The quality is beautiful and
                the clothes are very comfortable.

            </p>


        </article>



        <!-- JAMES -->

        <article class="review-card">


            <div class="review-header">


                <div class="review-photo">


                    <img
                        src="images/Jame_Cruz.jpg"
                        alt="James Cruz"
                    >


                </div>


                <div>


                    <h3>
                        James Cruz
                    </h3>


                    <div class="review-stars">

                        ★★★★★

                    </div>


                </div>


            </div>


            <p>

                The perfect combination of
                simple style and island vibes.
                I would definitely order again.

            </p>


        </article>



        <!-- ANA -->

        <article class="review-card">


            <div class="review-header">


                <div class="review-photo">


                    <img
                        src="images/Ana_Reyes.jpg"
                        alt="Ana Reyes"
                    >


                </div>


                <div>


                    <h3>
                        Ana Reyes
                    </h3>


                    <div class="review-stars">

                        ★★★★★

                    </div>


                </div>


            </div>


            <p>

                Beautiful pieces and very easy
                to order. It feels like bringing
                a little part of Siquijor home.

            </p>


        </article>


    </div>


</section>



<!-- =========================================================
     COLLECTIONS
========================================================= -->

<section
    id="collections"
    class="collections-section"
>


    <div
        class="organic-shape collection-shape">
    </div>


    <div class="collection-intro">


        <p class="eyebrow">

            SHOP BY COLLECTION

        </p>


        <h2>

            Find Your Perfect

            <span>
                Look
            </span>

        </h2>


        <p>

            Explore our curated collections,
            made for every mood, moment,
            and island adventure.

        </p>


        <a
            href="shop.php"
            class="orange-button"
        >

            Shop All Collections →

        </a>


    </div>



    <div class="collection-grid">


        <!-- ISLAND WEAR -->

        <article class="collection-card">


            <div class="collection-image">


                <img
                    src="images/Island_Wear.jpg"
                    alt="Island Wear"
                >


            </div>


            <div class="collection-overlay">


                <h3>
                    Island Wear
                </h3>


                <a href="shop.php">

                    Shop Now →

                </a>


            </div>


        </article>



        <!-- TOPS -->

        <article class="collection-card">


            <div class="collection-image">


                <img
                    src="images/Tops.jpg"
                    alt="Tops"
                >


            </div>


            <div class="collection-overlay">


                <h3>
                    Tops
                </h3>


                <a
                    href="shop.php?category=Tops"
                >

                    Shop Now →

                </a>


            </div>


        </article>



        <!-- BOTTOMS -->

        <article class="collection-card">


            <div class="collection-image">


                <img
                    src="images/Bottoms.jpg"
                    alt="Bottoms"
                >


            </div>


            <div class="collection-overlay">


                <h3>
                    Bottoms
                </h3>


                <a
                    href="shop.php?category=Bottoms"
                >

                    Shop Now →

                </a>


            </div>


        </article>



        <!-- DRESSES -->

        <article class="collection-card">


            <div class="collection-image">


                <img
                    src="images/dresses.jpg"
                    alt="Dresses"
                >


            </div>


            <div class="collection-overlay">


                <h3>
                    Dresses
                </h3>


                <a
                    href="shop.php?category=Dresses"
                >

                    Shop Now →

                </a>


            </div>


        </article>



        <!-- ACCESSORIES -->

        <article class="collection-card">


            <div class="collection-image">


                <img
                    src="images/accesories.jpg"
                    alt="Accessories"
                >


            </div>


            <div class="collection-overlay">


                <h3>
                    Accessories
                </h3>


                <a
                    href="shop.php?category=Accessories"
                >

                    Shop Now →

                </a>


            </div>


        </article>



        <!-- NEW ARRIVALS -->

        <article class="collection-card">


            <div class="collection-image">


                <img
                    src="images/New_Arrival.jpg"
                    alt="New Arrivals"
                >


            </div>


            <div class="collection-overlay">


                <h3>
                    New Arrivals
                </h3>


                <a href="shop.php">

                    Shop Now →

                </a>


            </div>


        </article>


    </div>


</section>



<!-- =========================================================
     NEWSLETTER
========================================================= -->

<section class="newsletter-section">


    <div
        class="newsletter-shape">
    </div>


    <div class="newsletter-content">


        <p class="eyebrow">

            STAY CONNECTED

        </p>


        <h2>

            Be Part of the
            Siquijor Styles Community

        </h2>


        <p>

            Get the latest updates,
            exclusive offers,
            and island-inspired looks.

        </p>


        <form
            class="newsletter-form"
            onsubmit="subscribe(event)"
        >


            <input
                id="newsletterEmail"
                type="email"
                placeholder="Enter your email address"
                required
            >


            <button
                type="submit"
            >

                Subscribe

            </button>


        </form>


    </div>


    <div class="social-links">


        <a href="#">
            f
        </a>


        <a href="#">
            ◎
        </a>


        <a href="#">
            ♪
        </a>


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


        <a href="#collections">
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



    <!-- =====================================================
         LEGAL
    ====================================================== -->

    <div class="footer-column">


        <h4>
            LEGAL
        </h4>


        <a href="privacy-policy.php">
            Privacy Policy
        </a>


        <a href="terms-conditions.php">
            Terms &amp; Conditions
        </a>


    </div>


</footer>



<div class="copyright">

    © <?= date("Y") ?>
    Siquijor Styles.
    All rights reserved.

    <span style="margin: 0 8px;">|</span>

    <a
        href="privacy-policy.php"
        style="text-decoration: none;"
    >
        Privacy Policy
    </a>

    <span style="margin: 0 8px;">|</span>

    <a
        href="terms-conditions.php"
        style="text-decoration: none;"
    >
        Terms &amp; Conditions
    </a>

</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script src="js/script.js"></script>


</body>

</html>