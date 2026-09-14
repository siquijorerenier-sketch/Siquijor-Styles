<?php

require_once "php/database.php";

$sql = "SELECT
            category_id,
            product_name,
            description,
            price,
            image,
            stock
        FROM products
        ORDER BY product_name ASC";

$result = $conn->query($sql);

if (!$result) {
    die("Error loading products: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Shop | Siquijor Styles</title>

    <link
        rel="stylesheet"
        href="style.css">

    <link
        href="https://fonts.googleapis.com/css2?family=Parisienne&family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet">

</head>

<body>


<header class="header">

    <div class="logo-area">
    </div>


    <nav class="navigation">

        <a href="index.php">
            Home
        </a>

        <a
            href="products.php"
            class="active">
            Shop
        </a>

        <a href="index.php#collections">
            Collections
            <span>⌄</span>
        </a>

        <a href="about.html">
            About
        </a>

        <a href="contact.html">
            Contact
        </a>

    </nav>


    <div class="header-tools">

        <button
            class="header-icon"
            onclick="openSearch()"
            title="Search">

            ⌕

        </button>


        <button
            class="header-icon"
            onclick="openProfile()"
            title="Profile">

            ♙

        </button>


        <button
            class="header-icon cart-button"
            onclick="openCart()"
            title="Cart">

            🛒

            <span id="cartCount">
                0
            </span>

        </button>


        <button
            class="login-button"
            onclick="openLogin()">

            Log In

        </button>


        <button
            class="signup-button"
            onclick="openSignup()">

            Sign Up

        </button>

    </div>

</header>


<main>


<section class="featured-section shop-page-section">


    <div class="organic-shape featured-shape-left"></div>

    <div class="organic-shape featured-shape-right"></div>


    <div class="section-top">

        <div>

            <p class="eyebrow">
                SIQUIJOR STYLES
            </p>

            <h2>
                Shop All
                <br>
                <span>Products</span>
            </h2>

            <p>
                Discover island-inspired pieces
                made for every mood, moment,
                and adventure.
            </p>

        </div>

    </div>


    <div class="product-grid" id="productGrid">


        <?php while ($product = $result->fetch_assoc()): ?>


            <article
                class="product-card"

                data-category="<?= htmlspecialchars($product['category_id']) ?>"

                data-name="<?= htmlspecialchars($product['product_name']) ?>"

                data-price="<?= htmlspecialchars($product['price']) ?>"

                data-stock="<?= htmlspecialchars($product['stock']) ?>">


                <div class="product-image image-placeholder">


                    <?php if (!empty($product['image'])): ?>

                        <img
                            src="images/<?= htmlspecialchars($product['image']) ?>"
                            alt="<?= htmlspecialchars($product['product_name']) ?>">

                    <?php else: ?>

                        <span>
                            PRODUCT IMAGE
                        </span>

                    <?php endif; ?>


                    <button
                        class="favorite"
                        onclick="favoriteProduct(this)">

                        ♡

                    </button>

                </div>


                <div class="product-info">


                    <h3>
                        <?= htmlspecialchars($product['product_name']) ?>
                    </h3>


                    <strong class="price">

                        ₱<?= number_format($product['price'], 2) ?>

                    </strong>


                    <!-- STOCK -->

                    <?php if ($product['stock'] > 0): ?>

                        <p class="product-stock">

                            <?= htmlspecialchars($product['stock']) ?>

                            remaining

                        </p>

                    <?php else: ?>

                        <p class="product-stock out-of-stock">

                            Out of Stock

                        </p>

                    <?php endif; ?>


                    <!-- ADD TO CART -->

                    <?php if ($product['stock'] > 0): ?>

                        <button
                            class="add-cart"

                            onclick="addToCart(
                                '<?= htmlspecialchars($product['product_name'], ENT_QUOTES) ?>',
                                <?= $product['price'] ?>,
                                <?= $product['stock'] ?>
                            )">

                            🛒 Add to Cart

                        </button>

                    <?php else: ?>

                        <button
                            class="add-cart"
                            disabled>

                            Out of Stock

                        </button>

                    <?php endif; ?>


                    <!-- QUICK VIEW -->

                    <button
                        class="quick-view"

                        onclick="quickView(
                            '<?= htmlspecialchars($product['product_name'], ENT_QUOTES) ?>',
                            <?= $product['price'] ?>
                        )">

                        ◉ Quick View

                    </button>


                </div>


            </article>


        <?php endwhile; ?>


    </div>

</section>

</main>


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

        <a href="products.php">
            All Products
        </a>

        <a href="index.php#collections">
            Collections
        </a>

        <a href="products.php">
            New Arrivals
        </a>

    </div>


    <div class="footer-column">

        <h4>
            ABOUT
        </h4>

        <a href="about.html">
            Our Story
        </a>

        <a href="contact.html">
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


<!-- CART MODAL -->

<div
    id="cartModal"
    class="modal">

    <div class="modal-box cart-box">

        <button
            class="close-modal"
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
                ₱0
            </strong>

        </div>

        <button
            class="checkout-button"
            onclick="checkout()">

            CHECKOUT

        </button>

    </div>

</div>


<!-- LOGIN MODAL -->

<div
    id="loginModal"
    class="modal">

    <div class="modal-box account-box">

        <button
            class="close-modal"
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

            <button onclick="switchToSignup()">
                Sign Up
            </button>

        </p>

    </div>

</div>


<!-- SIGN UP MODAL -->

<div
    id="signupModal"
    class="modal">

    <div class="modal-box account-box">

        <button
            class="close-modal"
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

            <button onclick="switchToLogin()">
                Log In
            </button>

        </p>

    </div>

</div>


<!-- PROFILE MODAL -->

<div
    id="profileModal"
    class="modal">

    <div class="modal-box profile-box">

        <button
            class="close-modal"
            onclick="closeModal('profileModal')">

            ×

        </button>
<div class="profile-avatar">
    <i class="fa-regular fa-user"></i>
</div>

        <h2 id="profileName">
            Guest User
        </h2>

        <p id="profileEmail">
            Log in to view your account.
        </p>

        <div class="profile-menu">

            <button>
                My Orders
            </button>

            <button>
                Account Settings
            </button>

            <button>
                Wishlist
            </button>

            <button onclick="logout()">
                Log Out
            </button>

        </div>

    </div>

</div>


<!-- SEARCH -->

<div
    id="searchModal"
    class="modal">

    <div class="modal-box search-box">

        <button
            class="close-modal"
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


<!-- QUICK VIEW -->

<div
    id="quickViewModal"
    class="modal">

    <div class="modal-box quick-view-box">

        <button
            class="close-modal"
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
            ₱0
        </strong>

        <button
            id="quickAddButton"
            class="checkout-button">

            ADD TO CART

        </button>

    </div>

</div>


<script src="js/script.js"></script>

</body>

</html>