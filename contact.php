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

    $user_id = (int) $_SESSION["user_id"];

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

    <title>Contact Us | Siquijor Styles</title>


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
           CONTACT PAGE
        ================================================== */

        .contact-intro {
            min-height: 430px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            padding: 70px 8%;
        }

        .contact-shape-left {
            position: absolute;
            width: 500px;
            height: 430px;
            left: -200px;
            top: -100px;
            background: #dceceb;
            border-radius: 48% 52% 63% 37% / 58% 40% 60% 42%;
            pointer-events: none;
        }

        .contact-shape-right {
            position: absolute;
            width: 470px;
            height: 390px;
            right: -180px;
            bottom: -150px;
            background: #f1d9b3;
            border-radius: 62% 38% 45% 55% / 40% 58% 42% 60%;
            pointer-events: none;
        }

        .contact-intro-content {
            width: 100%;
            max-width: 1100px;
            margin: auto;
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
        }

        .contact-intro h1 {
            font-family: "Playfair Display", serif;
            font-size: clamp(43px, 6vw, 70px);
            line-height: 1;
            color: var(--teal);
            margin: 10px 0 18px;
        }

        .contact-intro h1 span {
            font-family: "Parisienne", cursive;
            font-weight: normal;
            color: var(--orange);
        }

        .contact-intro-text p {
            color: #687e83;
            font-size: 11px;
            line-height: 1.9;
            max-width: 480px;
        }

        .contact-details {
            background: rgba(255, 255, 255, 0.82);
            padding: 40px;
            border-radius: 42% 58% 50% 50% / 50% 42% 58% 50%;
        }

        .contact-detail {
            margin-bottom: 22px;
        }

        .contact-detail:last-child {
            margin-bottom: 0;
        }

        .contact-detail h3 {
            font-family: "Playfair Display", serif;
            color: var(--teal);
            font-size: 16px;
            margin-bottom: 5px;
        }

        .contact-detail p {
            color: #687e83;
            font-size: 10px;
        }


        .contact-form-section {
            position: relative;
            overflow: hidden;
            background: #ffffff;
            padding: 75px 8%;
        }

        .contact-form-shape {
            position: absolute;
            width: 600px;
            height: 500px;
            right: -260px;
            top: -180px;
            background: #dceceb;
            border-radius: 52% 48% 40% 60% / 45% 55% 45% 55%;
            pointer-events: none;
        }

        .contact-form-wrapper {
            max-width: 800px;
            margin: auto;
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .contact-form-wrapper h2 {
            font-family: "Playfair Display", serif;
            color: var(--teal);
            font-size: 38px;
            margin: 8px 0 10px;
        }

        .contact-form-wrapper .script-text {
            font-family: "Parisienne", cursive;
            color: var(--orange);
            font-size: 30px;
            margin-bottom: 15px;
        }

        .contact-form-wrapper > p {
            color: #687e83;
            font-size: 10px;
            margin-bottom: 35px;
        }

        .contact-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            text-align: left;
        }

        .contact-field {
            display: flex;
            flex-direction: column;
        }

        .contact-field.full {
            grid-column: 1 / -1;
        }

        .contact-field label {
            color: var(--teal);
            font-size: 10px;
            margin-bottom: 7px;
            font-weight: 500;
        }

        .contact-field input,
        .contact-field textarea {
            width: 100%;
            border: 1px solid #d8d1c6;
            background: #f8f5ee;
            padding: 13px 15px;
            outline: none;
            color: var(--text);
            font-size: 10px;
            font-family: inherit;
        }

        .contact-field textarea {
            min-height: 140px;
            resize: vertical;
        }

        .contact-field input:focus,
        .contact-field textarea:focus {
            border-color: var(--light-teal);
        }

        .contact-submit {
            grid-column: 1 / -1;
            text-align: center;
            margin-top: 10px;
        }

        .contact-submit button {
            border: none;
            background: var(--teal);
            color: white;
            padding: 13px 38px;
            font-size: 10px;
            letter-spacing: 1px;
            cursor: pointer;
        }

        .contact-submit button:hover {
            background: var(--dark-teal);
        }


        @media (max-width: 800px) {

            .contact-intro-content {
                grid-template-columns: 1fr;
                gap: 35px;
            }

            .contact-intro {
                padding: 60px 7%;
            }

            .contact-form {
                grid-template-columns: 1fr;
            }

            .contact-field.full,
            .contact-submit {
                grid-column: auto;
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

        <a href="about.php">
            About
        </a>

        <a
            href="contact.php"
            class="active">

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

</a>>


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
     SECTION 1 — LET'S CONNECT
================================================== -->

<section class="contact-intro">


    <div class="contact-shape-left"></div>

    <div class="contact-shape-right"></div>


    <div class="contact-intro-content">


        <div class="contact-intro-text">


            <p class="eyebrow">

                SAY HELLO

            </p>


            <h1>

                Let's<br>

                <span>

                    Connect

                </span>

            </h1>


            <p>

                Whether you have a question about an order,
                want to know more about our collections,
                or simply want to say hello, we'd love
                to hear from you.

            </p>


        </div>


        <div class="contact-details">


            <div class="contact-detail">

                <h3>
                    Email
                </h3>

                <p>
                    hello@siquijorstyles.com
                </p>

            </div>


            <div class="contact-detail">

                <h3>
                    Location
                </h3>

                <p>
                    Siquijor Island, Philippines
                </p>

            </div>


            <div class="contact-detail">

                <h3>
                    Follow Along
                </h3>

                <p>
                    Facebook · Instagram · TikTok
                </p>

            </div>


        </div>

    </div>

</section>



<!-- ==================================================
     SECTION 2 — SEND US A MESSAGE
================================================== -->

<section class="contact-form-section">


    <div class="contact-form-shape"></div>


    <div class="contact-form-wrapper">


        <p class="eyebrow">

            WE'RE HERE TO HELP

        </p>


        <h2>

            Send Us a Message

        </h2>


        <div class="script-text">

            We'd love to hear from you

        </div>


        <p>

            Fill out the form below and we'll get back to you.

        </p>


        <form
            class="contact-form"
            onsubmit="sendMessage(event)">


            <div class="contact-field">

                <label for="contactName">

                    NAME

                </label>

                <input
                    id="contactName"
                    type="text"
                    placeholder="Your name"
                    required>

            </div>


            <div class="contact-field">

                <label for="contactEmail">

                    EMAIL

                </label>

                <input
                    id="contactEmail"
                    type="email"
                    placeholder="Your email address"
                    required>

            </div>


            <div class="contact-field full">

                <label for="contactSubject">

                    SUBJECT

                </label>

                <input
                    id="contactSubject"
                    type="text"
                    placeholder="What can we help you with?"
                    required>

            </div>


            <div class="contact-field full">

                <label for="contactMessage">

                    MESSAGE

                </label>

                <textarea
                    id="contactMessage"
                    placeholder="Write your message here..."
                    required></textarea>

            </div>

            <div class="checkout-tip">

    <i class="fa-solid fa-location-dot"></i>

                <span>
                    <strong>Delivery tip:</strong>
                    Enter your complete address including your barangay,
                    municipality, and province to help prevent delivery delays.
                </span>

            </div>


            <div class="contact-submit">

                <button type="submit">

                    SEND MESSAGE →

                </button>

            </div>


        </form>

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

    <a href="privacy.php">
        Privacy Policy
    </a>

    <a href="terms.php">
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



<!-- ==================================================
     JAVASCRIPT
================================================== -->

<script src="js/script.js"></script>


</body>

</html>