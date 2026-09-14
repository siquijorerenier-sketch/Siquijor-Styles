    <?php

    session_start();

    require_once "php/database.php";

    header("Content-Type: text/html; charset=UTF-8");


    /* ==================================================
    CURRENT USER
    ================================================== */

    $logged_in = !empty($_SESSION["logged_in"]) && !empty($_SESSION["user_id"]);

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

        <title>Cart | Siquijor Styles</title>


        <link
            rel="stylesheet"
            href="css/style.css">

            <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


        <link
            href="https://fonts.googleapis.com/css2?family=Parisienne&family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap"
            rel="stylesheet">

    </head>


    <body>


    <!-- ==================================================
        HEADER
    ================================================== -->

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
    class="header-icon cart-button"
    href="cart.php"
    title="Cart"
    aria-label="Shopping Cart">

    <i class="fa-solid fa-cart-shopping"></i>

    <span id="cartCount">
        <?= $cart_count ?>
    </span>

</a>

    <i class="fa-regular fa-user"></i>

</a>

            <?php endif; ?>


            <!-- CART -->

            <a
                class="header-icon cart-button"
                href="cart.php"
                title="Cart">

                🛒

                <span id="cartCount">
                    <?= $cart_count ?>
                </span>

            </a>


            <!-- LOGIN / ACCOUNT -->

            <?php if ($logged_in && $current_user): ?>

                <a
                    class="login-button"
                    href="orders.php">

                    <?= htmlspecialchars(
                        $current_user["full_name"]
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
                    href="login.php">

                    Log In

                </a>


                <a
                    class="signup-button"
                    href="signup.php">

                    Sign Up

                </a>

            <?php endif; ?>


        </div>

    </header>



    <!-- ==================================================
        CART PAGE
    ================================================== -->

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

                    Your

                    <br>

                    <span>
                        Cart
                    </span>

                </h2>


                <p>
                    Review your island-inspired
                    pieces before checkout.
                </p>

            </div>

        </div>



        <!-- ==================================================
            CART CONTENT
        ================================================== -->

        <div class="cart-page-container">


            <div
                id="cartPageItems"
                class="cart-page-items">

                <div class="empty-cart">

                    <p>
                        Loading your cart...
                    </p>

                </div>

            </div>



            <!-- ==================================================
                CART SUMMARY
            ================================================== -->

            <div class="cart-page-summary">


                <h2>
                    Order Summary
                </h2>


                <div class="summary-row">

                    <span>
                        Items
                    </span>

                    <strong id="cartPageItemCount">
                        0
                    </strong>

                </div>


                <div class="summary-row">

                    <span>
                        Subtotal
                    </span>

                    <strong id="cartPageSubtotal">
                        ₱0.00
                    </strong>

                </div>


                <div class="summary-divider"></div>


                <span class="total-label">
                    Total to Pay
                </span>
                <div class="summary-total checkout-summary-total"></div>

                    <span>
                        Total
                    </span>

                    <strong id="cartPageTotal">
                        ₱0.00
                    </strong>

                </div>


                <button
                    id="cartPageCheckout"
                    class="checkout-button"
                    type="button"
                    onclick="cartPageCheckout()">

                    PROCEED TO CHECKOUT

                </button>


                <a
                    href="shop.php"
                    class="continue-shopping">

                    ← Continue Shopping

                </a>

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


                <a href="signup.php">
                    Sign Up
                </a>

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


                <a href="login.php">
                    Log In
                </a>

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
                        $current_user["full_name"]
                    ) ?>

                </h2>


                <p id="profileEmail">

                    <?= htmlspecialchars(
                        $current_user["email"]
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
                        onclick="window.location.href='login.php'">

                        Log In

                    </button>


                    <button
                        type="button"
                        onclick="window.location.href='signup.php'">

                        Create Account

                    </button>


                </div>


            <?php endif; ?>


        </div>

    </div>



    <!-- ==================================================
        DATABASE CART PAGE JAVASCRIPT
    ================================================== -->

    <script src="js/script.js"></script>


    <script>


    /* ==================================================
    LOAD DATABASE CART
    ================================================== */

    async function loadDatabaseCartPage() {

        const container =
            document.getElementById(
                "cartPageItems"
            );


        const itemCount =
            document.getElementById(
                "cartPageItemCount"
            );


        const subtotalElement =
            document.getElementById(
                "cartPageSubtotal"
            );


        const totalElement =
            document.getElementById(
                "cartPageTotal"
            );


        const checkoutButton =
            document.getElementById(
                "cartPageCheckout"
            );


        if (!container) {
            return;
        }


        try {

            const formData =
                new FormData();


            formData.append(
                "action",
                "get"
            );


            const response =
                await fetch(
                    "php/cart_process.php",
                    {
                        method: "POST",
                        body: formData,
                        credentials: "same-origin"
                    }
                );


            const responseText =
                await response.text();


            let data;


            try {

                data =
                    JSON.parse(responseText);

            } catch (error) {

                console.error(
                    "Cart page response:",
                    responseText
                );


                container.innerHTML = `

                    <div class="empty-cart">

                        <h2>
                            Unable to load cart
                        </h2>

                        <p>
                            The server returned an invalid response.
                        </p>

                    </div>

                `;

                return;

            }


            if (!data.success) {


                if (
                    data.message &&
                    data.message.toLowerCase().includes("log in")
                ) {

                    container.innerHTML = `

                        <div class="empty-cart">

                            <h2>
                                Please log in
                            </h2>

                            <p>
                                Log in to view your shopping cart.
                            </p>

                            <a
                                href="login.php"
                                class="checkout-button">

                                LOG IN

                            </a>

                        </div>

                    `;


                    itemCount.textContent =
                        "0";


                    subtotalElement.textContent =
                        "₱0.00";


                    totalElement.textContent =
                        "₱0.00";


                    checkoutButton.disabled =
                        true;


                    return;

                }


                throw new Error(
                    data.message ||
                    "Unable to load cart."
                );

            }


            let items = [];


            if (
                data.data &&
                Array.isArray(
                    data.data.cart
                )
            ) {

                items =
                    data.data.cart;

            }


            if (
                items.length === 0
            ) {

                container.innerHTML = `

                    <div class="empty-cart">

                        <h2>
                            Your cart is empty
                        </h2>

                        <p>
                            Discover something beautiful
                            from our island collection.
                        </p>

                        <a
                            href="shop.php"
                            class="checkout-button">

                            SHOP PRODUCTS

                        </a>

                    </div>

                `;


                itemCount.textContent =
                    "0";


                subtotalElement.textContent =
                    "₱0.00";


                totalElement.textContent =
                    "₱0.00";


                checkoutButton.disabled =
                    true;


                return;

            }


            let totalItems = 0;
            let totalPrice = 0;


            container.innerHTML = "";


            items.forEach(function (item) {


                const productId =
                    Number(
                        item.product_id ??
                        item.productId ??
                        item.id ??
                        0
                    );


                const quantity =
                    Number(
                        item.quantity
                    ) || 0;


                const price =
                    Number(
                        item.price
                    ) || 0;


                const stock =
                    Number(
                        item.stock
                    );


                const name =
                    item.product_name ??
                    item.name ??
                    "Product";


                const image =
                    item.image ??
                    "";


                const itemTotal =
                    quantity * price;


                totalItems +=
                    quantity;


                totalPrice +=
                    itemTotal;


                const element =
                    document.createElement(
                        "div"
                    );


                element.className =
                    "cart-page-item";


                element.innerHTML = `

                    <div class="cart-page-item-info">


                        ${
                            image
                            ? `
                                <img
                                    src="images/${escapeCartPageHTML(image)}"
                                    alt="${escapeCartPageHTML(name)}"
                                    class="cart-item-image"
                                    onerror="this.style.display='none';">
                            `
                            : ""
                        }


                        <h3>

                            ${escapeCartPageHTML(name)}

                        </h3>


                        <p>

                            ₱${formatCartPagePrice(price)}
                            each

                        </p>


                        <p class="cart-stock">

                            ${
                                stock > 0
                                ? stock + " available"
                                : "Out of Stock"
                            }

                        </p>

                    </div>


                    <div class="cart-page-quantity">


                        <button
                            type="button"
                            onclick="cartPageChangeQuantity(
                                ${productId},
                                ${quantity - 1}
                            )">

                            −

                        </button>


                        <span>
                            ${quantity}
                        </span>


                        <button
                            type="button"
                            onclick="cartPageChangeQuantity(
                                ${productId},
                                ${quantity + 1}
                            )">

                            +

                        </button>

                    </div>


                    <div class="cart-page-item-total">


                        <strong>

                            ₱${formatCartPagePrice(itemTotal)}

                        </strong>


                        <button
                            type="button"
                            class="remove-cart"
                            onclick="cartPageRemoveItem(
                                ${productId}
                            )">

                            Remove

                        </button>

                    </div>

                `;


                container.appendChild(
                    element
                );

            });


            itemCount.textContent =
                totalItems;


            subtotalElement.textContent =
                "₱" +
                formatCartPagePrice(
                    totalPrice
                );


            totalElement.textContent =
                "₱" +
                formatCartPagePrice(
                    totalPrice
                );


            checkoutButton.disabled =
                false;


            const headerCount =
                document.getElementById(
                    "cartCount"
                );


            if (headerCount) {

                headerCount.textContent =
                    totalItems;

            }


        } catch (error) {

            console.error(
                "Cart page error:",
                error
            );


            container.innerHTML = `

                <div class="empty-cart">

                    <h2>
                        Unable to load cart
                    </h2>

                    <p>
                        ${escapeCartPageHTML(
                            error.message ||
                            "Please try again."
                        )}
                    </p>

                </div>

            `;


            checkoutButton.disabled =
                true;

        }

    }


    /* ==================================================
    CHANGE DATABASE CART QUANTITY
    ================================================== */

    async function cartPageChangeQuantity(
        productId,
        quantity
    ) {

        productId =
            Number(productId);


        quantity =
            Number(quantity);


        if (
            !productId ||
            productId <= 0
        ) {

            return;

        }


        if (
            quantity <= 0
        ) {

            await cartPageRemoveItem(
                productId
            );

            return;

        }


        try {

            const formData =
                new FormData();


            formData.append(
                "action",
                "update"
            );


            formData.append(
                "product_id",
                productId
            );


            formData.append(
                "quantity",
                quantity
            );


            const response =
                await fetch(
                    "php/cart_process.php",
                    {
                        method: "POST",
                        body: formData,
                        credentials: "same-origin"
                    }
                );


            const responseText =
                await response.text();


            const data =
                JSON.parse(
                    responseText
                );


            if (!data.success) {

                alert(
                    data.message ||
                    "Unable to update cart."
                );

                return;

            }


            await loadDatabaseCartPage();


        } catch (error) {

            console.error(
                "Quantity update error:",
                error
            );


            alert(
                "Unable to update your cart."
            );

        }

    }


    /* ==================================================
    REMOVE DATABASE CART ITEM
    ================================================== */

    async function cartPageRemoveItem(
        productId
    ) {

        productId =
            Number(productId);


        if (
            !productId ||
            productId <= 0
        ) {

            return;

        }


        try {

            const formData =
                new FormData();


            formData.append(
                "action",
                "remove"
            );


            formData.append(
                "product_id",
                productId
            );


            const response =
                await fetch(
                    "php/cart_process.php",
                    {
                        method: "POST",
                        body: formData,
                        credentials: "same-origin"
                    }
                );


            const responseText =
                await response.text();


            const data =
                JSON.parse(
                    responseText
                );


            if (!data.success) {

                alert(
                    data.message ||
                    "Unable to remove item."
                );

                return;

            }


            await loadDatabaseCartPage();


        } catch (error) {

            console.error(
                "Remove cart item error:",
                error
            );


            alert(
                "Unable to remove the item."
            );

        }

    }


    /* ==================================================
    CHECKOUT FROM CART PAGE
    ================================================== */

    async function cartPageCheckout() {

        const itemCount =
            document.getElementById(
                "cartPageItemCount"
            );


        if (
            !itemCount ||
            Number(itemCount.textContent) <= 0
        ) {

            alert(
                "Your cart is empty."
            );

            return;

        }


        window.location.href =
            "checkout.php";

    }


    /* ==================================================
    PRICE FORMAT
    ================================================== */

    function formatCartPagePrice(
        value
    ) {

        return Number(
            value || 0
        ).toLocaleString(
            "en-PH",
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );

    }


    /* ==================================================
    HTML ESCAPE
    ================================================== */

    function escapeCartPageHTML(
        value
    ) {

        const div =
            document.createElement(
                "div"
            );


        div.textContent =
            value ?? "";


        return div.innerHTML;

    }


    /* ==================================================
    LOAD CART PAGE
    ================================================== */

    document.addEventListener(
        "DOMContentLoaded",
        function () {

            loadDatabaseCartPage();

        }
    );

    </script>


    </body>

    </html>