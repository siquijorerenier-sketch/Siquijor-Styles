<?php

session_start();

require_once "php/database.php";


/* ==================================================
   CURRENT USER
================================================== */

$logged_in =
    !empty($_SESSION["logged_in"]) &&
    !empty($_SESSION["user_id"]);

$current_user = null;

$user_id = 0;


if ($logged_in) {

    $user_id =
        (int) $_SESSION["user_id"];


    $user_stmt = $conn->prepare(
        "SELECT
            id,
            full_name,
            email
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

        } else {

            $logged_in = false;

            unset(
                $_SESSION["logged_in"],
                $_SESSION["user_id"],
                $_SESSION["full_name"],
                $_SESSION["email"]
            );

        }


        $user_stmt->close();

    }

}


/* ==================================================
   CART COUNT
================================================== */

$cart_count = 0;


if ($logged_in) {

    $cart_stmt = $conn->prepare(
        "SELECT
            COALESCE(SUM(ci.quantity), 0) AS total_items
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
                (int) $cart_row["total_items"];

        }


        $cart_stmt->close();

    }

}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>About Us | Siquijor Styles</title>


    <link
        rel="stylesheet"
        href="css/style.css">

        <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <link
        href="https://fonts.googleapis.com/css2?family=Parisienne&family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet">


    <style>

        /* ==================================================
           ABOUT PAGE
        ================================================== */

        .about-hero {
            min-height: 430px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 80px 8%;
        }

        .about-hero-shape-one {
            position: absolute;
            width: 520px;
            height: 400px;
            left: -170px;
            top: -100px;
            background: #dceceb;
            border-radius: 58% 42% 63% 37% / 42% 57% 43% 58%;
            pointer-events: none;
        }

        .about-hero-shape-two {
            position: absolute;
            width: 420px;
            height: 350px;
            right: -150px;
            bottom: -120px;
            background: #f1d9b3;
            border-radius: 43% 57% 38% 62% / 55% 40% 60% 45%;
            pointer-events: none;
        }

        .about-hero-content {
            position: relative;
            z-index: 2;
            max-width: 760px;
        }

        .about-hero-content .eyebrow {
            margin-bottom: 12px;
        }

        .about-hero-content h1 {
            font-family: "Playfair Display", serif;
            font-size: clamp(42px, 6vw, 72px);
            line-height: 1;
            color: var(--teal);
            margin-bottom: 20px;
        }

        .about-script {
            font-family: "Parisienne", cursive;
            font-size: 34px;
            color: var(--orange);
            margin-bottom: 18px;
        }

        .about-hero-content p {
            max-width: 600px;
            margin: auto;
            color: #687e83;
            font-size: 13px;
            line-height: 1.9;
        }


        .about-story {
            position: relative;
            overflow: hidden;
            min-height: 420px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            gap: 70px;
            padding: 70px 8%;
            background: #ffffff;
        }

        .about-story-shape {
            position: absolute;
            width: 550px;
            height: 500px;
            left: -240px;
            bottom: -230px;
            background: #dceceb;
            border-radius: 58% 42% 65% 35% / 45% 60% 40% 55%;
            pointer-events: none;
        }

        .about-story-content {
            position: relative;
            z-index: 2;
        }

        .about-story-content h2 {
            font-family: "Playfair Display", serif;
            font-size: 38px;
            line-height: 1.1;
            color: var(--teal);
            margin: 10px 0 18px;
        }

        .about-story-content .script-text {
            font-family: "Parisienne", cursive;
            font-size: 29px;
            color: var(--orange);
            margin-bottom: 15px;
        }

        .about-story-content p {
            max-width: 520px;
            color: #687e83;
            font-size: 11px;
            line-height: 1.9;
            margin-bottom: 12px;
        }

        .about-values {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .about-value {
            background: #f8f5ee;
            padding: 35px 25px;
            text-align: center;
            border-radius: 55% 45% 50% 50% / 45% 55% 45% 55%;
            min-height: 210px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .about-value span {
            font-size: 28px;
            color: var(--orange);
            margin-bottom: 12px;
        }

        .about-value h3 {
            font-family: "Playfair Display", serif;
            font-size: 17px;
            color: var(--teal);
            margin-bottom: 9px;
        }

        .about-value p {
            font-size: 9px;
            line-height: 1.7;
            color: #687e83;
        }


        @media (max-width: 800px) {

            .about-story {
                grid-template-columns: 1fr;
                gap: 35px;
            }

            .about-values {
                grid-template-columns: 1fr;
            }

            .about-hero {
                min-height: 380px;
            }

        }

    </style>

</head>


<body>


<header class="header">


    <div class="logo-area">

        <a href="index.php">

            SIQUIJOR STYLES

        </a>

    </div>


    <nav class="navigation">


        <a href="index.php">

            Home

        </a>


        <a href="shop.php">

            Shop

        </a>


        <a href="index.php#collections">

            Collections

            <span>⌄</span>

        </a>


        <a
            href="about.php"
            class="active">

            About

        </a>


        <a href="contact.php">

            Contact

        </a>


    </nav>


    <div class="header-tools">


        <!-- SEARCH -->

        <button
            class="header-icon"
            type="button"
            onclick="openSearch()"
            title="Search">

            ⌕

        </button>


        <!-- PROFILE -->

        <?php if ($logged_in && $current_user): ?>
<!-- PROFILE -->

<a
    class="header-icon profile-button"
    href="<?= $logged_in ? 'orders.php' : 'login.php' ?>"
    <?= !$logged_in ? 'onclick="saveReturnPage()"' : '' ?>
    title="My Profile"
    aria-label="My Profile">

    <i class="fa-regular fa-user"></i>

</a>

        <?php else: ?>

            <a
                class="header-icon"
                href="login.php"
                onclick="saveReturnPage()"
                title="Profile">

                ♙

            </a>

        <?php endif; ?>


        <!-- CART -->

 <a
    class="header-icon cart-button"
    href="cart.php"
    title="Cart"
    aria-label="Shopping Cart">

    <i class="fa-solid fa-cart-shopping"></i>

    <span id="cartCount">
        <?= $cart_count ?>
    </span>

</a>


        <!-- LOGIN / LOGOUT -->

        <?php if ($logged_in && $current_user): ?>

            <a
                class="login-button"
                href="orders.php">

                <?= htmlspecialchars(
                    $current_user["full_name"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </a>


            <a
                class="signup-button"
                href="logout.php">

                Log Out

            </a>

        <?php else: ?>

            <a
                class="login-button"
                href="login.php"
                onclick="saveReturnPage()">

                Log In

            </a>


            <a
                class="signup-button"
                href="signup.php"
                onclick="saveReturnPage()">

                Sign Up

            </a>

        <?php endif; ?>


    </div>

</header>



<main>


<!-- ==================================================
     SECTION 1 — THE ISLAND BEHIND THE STYLE
================================================== -->

<section class="about-hero">


    <div class="about-hero-shape-one"></div>

    <div class="about-hero-shape-two"></div>


    <div class="about-hero-content">


        <p class="eyebrow">

            THE STORY BEHIND THE STYLE

        </p>


        <h1>

            Born From<br>

            <span>
                Island Soul
            </span>

        </h1>


        <div class="about-script">

            Inspired by Siquijor

        </div>


        <p>

            Siquijor Styles is inspired by the quiet beauty of
            island life — warm afternoons, ocean breezes,
            natural textures, and a slower way of living.

        </p>

    </div>

</section>



<!-- ==================================================
     SECTION 2 — MADE WITH INTENTION
================================================== -->

<section class="about-story">


    <div class="about-story-shape"></div>


    <div class="about-story-content">


        <p class="eyebrow">

            MADE WITH INTENTION

        </p>


        <h2>

            STYLE THAT<br>

            FEELS LIKE HOME

        </h2>


        <div class="script-text">

            Made for Everywhere

        </div>


        <p>

            We believe clothing should feel effortless.
            Our pieces are inspired by the relaxed character
            of Siquijor and designed to become part of
            everyday life.

        </p>


        <p>

            From slow island mornings to evenings by the sea,
            Siquijor Styles is about feeling comfortable,
            confident, and completely yourself.

        </p>


    </div>



    <div class="about-values">


        <div class="about-value">

            <span>

                ✦

            </span>


            <h3>

                Island Inspired

            </h3>


            <p>

                Colors, textures, and moods inspired by
                the beauty of Siquijor.

            </p>

        </div>



        <div class="about-value">

            <span>

                ♡

            </span>


            <h3>

                Everyday Ease

            </h3>


            <p>

                Relaxed pieces designed to move naturally
                with everyday life.

            </p>

        </div>



        <div class="about-value">

            <span>

                ∞

            </span>


            <h3>

                Timeless Style

            </h3>


            <p>

                Simple pieces made to stay beautiful
                beyond the season.

            </p>

        </div>


    </div>

</section>

</main>



<!-- ==================================================
     FOOTER
================================================== -->

<footer class="footer">


    <div class="footer-brand">

        <div class="footer-logo-slot"></div>


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


        <a href="#">

            FAQs

        </a>

    </div>



    <div class="footer-column">


        <h4>

            QUICK LINKS

        </h4>


        <a href="#">

            Privacy Policy

        </a>


        <a href="#">

            Terms & Conditions

        </a>


        <a href="#">

            Shipping Information

        </a>

    </div>



    <div class="footer-column">


        <h4>

            FOLLOW US

        </h4>


        <a href="#">

            Facebook

        </a>


        <a href="#">

            Instagram

        </a>


        <a href="#">

            TikTok

        </a>

    </div>


</footer>



<div class="copyright">

    © 2026 Siquijor Styles.
    All rights reserved.

</div>



<!-- ==================================================
     CART MODAL
================================================== -->

<div
    id="cartModal"
    class="modal">


    <div class="modal-box cart-box">


        <button
            class="close-modal"
            type="button"
            onclick="closeModal('cartModal')">

            ×

        </button>


        <h2>

            Your Cart

        </h2>


        <div id="cartItems">


            <p class="empty-cart">

                Your cart is empty.

            </p>


        </div>


        <div class="cart-total">


            <strong>

                Total

            </strong>


            <strong id="cartTotal">

                ₱0.00

            </strong>


        </div>


        <button
            class="checkout-button"
            type="button"
            onclick="checkout()">

            CHECKOUT

        </button>


    </div>

</div>



<!-- ==================================================
     LOGIN MODAL
================================================== -->

<div
    id="loginModal"
    class="modal">


    <div class="modal-box account-box">


        <button
            class="close-modal"
            type="button"
            onclick="closeModal('loginModal')">

            ×

        </button>


        <h2>

            Welcome Back

        </h2>


        <p>

            Log in to your Siquijor Styles account.

        </p>


        <form onsubmit="login(event)">


            <input
                id="loginEmail"
                type="email"
                placeholder="Email Address"
                required>


            <input
                id="loginPassword"
                type="password"
                placeholder="Password"
                required>


            <button
                type="submit"
                class="checkout-button">

                LOG IN

            </button>


        </form>


        <p class="account-switch">


            Don't have an account?


            <button
                type="button"
                onclick="switchToSignup()">

                Sign Up

            </button>


        </p>


    </div>

</div>



<!-- ==================================================
     SIGN UP MODAL
================================================== -->

<div
    id="signupModal"
    class="modal">


    <div class="modal-box account-box">


        <button
            class="close-modal"
            type="button"
            onclick="closeModal('signupModal')">

            ×

        </button>


        <h2>

            Create Account

        </h2>


        <p>

            Join the Siquijor Styles community.

        </p>


        <form onsubmit="signup(event)">


            <input
                id="signupName"
                type="text"
                placeholder="Full Name"
                required>


            <input
                id="signupEmail"
                type="email"
                placeholder="Email Address"
                required>


            <input
                id="signupPassword"
                type="password"
                placeholder="Password"
                required>


            <input
                id="signupConfirm"
                type="password"
                placeholder="Confirm Password"
                required>


            <button
                type="submit"
                class="checkout-button">

                CREATE ACCOUNT

            </button>


        </form>


        <p class="account-switch">


            Already have an account?


            <button
                type="button"
                onclick="switchToLogin()">

                Log In

            </button>


        </p>


    </div>

</div>



<!-- ==================================================
     PROFILE MODAL
================================================== -->

<div
    id="profileModal"
    class="modal">


    <div class="modal-box profile-box">


        <button
            class="close-modal"
            type="button"
            onclick="closeModal('profileModal')">

            ×

        </button>


<div class="profile-avatar">
    <i class="fa-regular fa-user"></i>
</div>


        <?php if ($logged_in && $current_user): ?>


            <h2 id="profileName">

                <?= htmlspecialchars(
                    $current_user["full_name"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </h2>


            <p id="profileEmail">

                <?= htmlspecialchars(
                    $current_user["email"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </p>


            <div class="profile-menu">


                <button
                    type="button"
                    onclick="window.location.href='orders.php'">

                    My Orders

                </button>


                <button
                    type="button">

                    Account Settings

                </button>


                <button
                    type="button">

                    Wishlist

                </button>


                <button
                    type="button"
                    onclick="logout()">

                    Log Out

                </button>


            </div>


        <?php else: ?>


            <h2 id="profileName">

                Guest User

            </h2>


            <p id="profileEmail">

                Log in to view your account.

            </p>


            <div class="profile-menu">


                <button
                    type="button"
                    onclick="saveReturnPage(); window.location.href='login.php'">

                    Log In

                </button>


                <button
                    type="button"
                    onclick="saveReturnPage(); window.location.href='signup.php'">

                    Create Account

                </button>


            </div>


        <?php endif; ?>


    </div>

</div>



<!-- ==================================================
     SEARCH MODAL
================================================== -->

<div
    id="searchModal"
    class="modal">


    <div class="modal-box search-box">


        <button
            class="close-modal"
            type="button"
            onclick="closeModal('searchModal')">

            ×

        </button>


        <h2>

            Search

        </h2>


        <input
            id="searchInput"
            type="text"
            placeholder="Search products..."
            oninput="searchProducts()">


        <div id="searchResults"></div>


    </div>

</div>



<!-- ==================================================
     QUICK VIEW MODAL
================================================== -->

<div
    id="quickViewModal"
    class="modal">


    <div class="modal-box quick-view-box">


        <button
            class="close-modal"
            type="button"
            onclick="closeModal('quickViewModal')">

            ×

        </button>


        <div class="quick-image image-placeholder">


            <span>

                PRODUCT IMAGE

            </span>


        </div>


        <h2 id="quickProductName">

            Product

        </h2>


        <strong id="quickProductPrice">

            ₱0.00

        </strong>


        <button
            id="quickAddButton"
            class="checkout-button"
            type="button">

            ADD TO CART

        </button>


    </div>

</div>



<script src="js/script.js"></script>


</body>

</html>