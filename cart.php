<?php

require_once __DIR__ . "/php/session.php";
require_once __DIR__ . "/php/database.php";

header("Content-Type: text/html; charset=UTF-8");

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

/* ==================================================
   ESCAPE HELPER
================================================== */

function e($value): string
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
        Cart | Siquijor Styles
    </title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Parisienne&family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet"
    >

    <style>

        .cart-page-container {
            width: min(1180px, 92%);
            margin: 0 auto;
            display: grid;
            grid-template-columns:
                minmax(0, 1fr)
                360px;
            gap: 30px;
            align-items: start;
        }

        .cart-page-items {
            min-width: 0;
        }

        .cart-page-summary {
            position: sticky;
            top: 110px;
            background: #fff;
            border: 1px solid #e8e0d5;
            border-radius: 20px;
            padding: 26px;
            box-shadow:
                0 15px 40px rgba(39, 76, 79, .08);
        }

        .cart-page-summary h2 {
            margin: 0 0 24px;
            color: #285e61;
            font-family: "Playfair Display", serif;
            font-size: 25px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 13px;
            color: #73888b;
            font-size: 12px;
        }

        .summary-row strong {
            color: #29464a;
        }

        .summary-divider {
            margin: 20px 0;
            border-top: 1px dashed #d9d0c4;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 22px;
        }

        .summary-total span {
            font-size: 13px;
            font-weight: 600;
            color: #29464a;
        }

        .summary-total strong {
            color: #285e61;
            font-family: "Playfair Display", serif;
            font-size: 25px;
        }

        .checkout-button {
            width: 100%;
            border: 0;
            border-radius: 12px;
            padding: 14px 18px;
            background:
                linear-gradient(
                    135deg,
                    #285e61,
                    #19494c
                );
            color: #fff;
            font-family: "Poppins", sans-serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .04em;
            cursor: pointer;
            transition: .2s ease;
        }

        .checkout-button:hover {
            transform: translateY(-2px);
            box-shadow:
                0 9px 20px rgba(40, 94, 97, .18);
        }

        .checkout-button:disabled {
            opacity: .5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .continue-shopping {
            display: block;
            margin-top: 15px;
            text-align: center;
            color: #285e61;
            text-decoration: none;
            font-size: 11px;
        }

        .continue-shopping:hover {
            text-decoration: underline;
        }

        .empty-cart {
            background: #fff;
            border: 1px solid #e8e0d5;
            border-radius: 20px;
            padding: 50px 30px;
            text-align: center;
            box-shadow:
                0 15px 40px rgba(39, 76, 79, .06);
        }

        .empty-cart h2 {
            margin: 0 0 8px;
            color: #285e61;
            font-family: "Playfair Display", serif;
        }

        .empty-cart p {
            margin: 0 0 20px;
            color: #73888b;
            font-size: 12px;
        }

        .empty-cart a {
            display: inline-block;
            width: auto;
            text-decoration: none;
        }

        .cart-login-message {
            background: #fff;
            border: 1px solid #e8e0d5;
            border-radius: 20px;
            padding: 50px 30px;
            text-align: center;
        }

        .cart-login-message h2 {
            margin: 0 0 10px;
            color: #285e61;
            font-family: "Playfair Display", serif;
        }

        .cart-login-message p {
            margin: 0 0 22px;
            color: #73888b;
            font-size: 12px;
        }

        .cart-login-message a {
            display: inline-block;
            width: auto;
            text-decoration: none;
        }

        .cart-item {
            display: grid;
            grid-template-columns:
                100px
                minmax(0, 1fr)
                auto;
            gap: 18px;
            align-items: center;
            background: #fff;
            border: 1px solid #e8e0d5;
            border-radius: 18px;
            padding: 16px;
            margin-bottom: 14px;
            box-shadow:
                0 10px 28px rgba(39, 76, 79, .05);
        }

        .cart-item-image {
            width: 100px;
            height: 115px;
            border-radius: 13px;
            object-fit: cover;
            background: #eee8de;
        }

        .cart-item-info {
            min-width: 0;
        }

        .cart-item-name {
            color: #285e61;
            font-family: "Playfair Display", serif;
            font-size: 19px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .cart-item-price {
            color: #73888b;
            font-size: 11px;
            margin-bottom: 12px;
        }

        .cart-item-stock {
            color: #73888b;
            font-size: 10px;
            margin-top: 7px;
        }

        .cart-item-stock.low {
            color: #b06b3d;
        }

        .cart-item-stock.out {
            color: #a8463b;
        }

        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quantity-button {
            width: 30px;
            height: 30px;
            border: 1px solid #d8d0c4;
            border-radius: 8px;
            background: #fffdf9;
            color: #285e61;
            cursor: pointer;
            font-size: 14px;
        }

        .quantity-button:hover {
            border-color: #285e61;
        }

        .quantity-button:disabled {
            opacity: .45;
            cursor: not-allowed;
        }

        .quantity-value {
            min-width: 28px;
            text-align: center;
            color: #29464a;
            font-size: 12px;
            font-weight: 600;
        }

        .cart-item-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }

        .cart-item-subtotal {
            color: #285e61;
            font-family: "Playfair Display", serif;
            font-size: 19px;
            font-weight: 600;
            white-space: nowrap;
        }

        .remove-button,
        .cart-clear-button {
            border: 0;
            background: transparent;
            color: #a8463b;
            cursor: pointer;
            font-family: "Poppins", sans-serif;
            font-size: 10px;
        }

        .remove-button:hover,
        .cart-clear-button:hover {
            text-decoration: underline;
        }

        .cart-message {
            display: none;
            margin-bottom: 15px;
            padding: 12px 15px;
            border-radius: 10px;
            font-size: 11px;
        }

        .cart-message.show {
            display: block;
        }

        .cart-message.error {
            background: #fff0ed;
            border: 1px solid #f0c9c1;
            color: #a8463b;
        }

        .cart-message.success {
            background: #ebf7f1;
            border: 1px solid #c7e4d5;
            color: #2d735c;
        }

        @media (max-width: 900px) {

            .cart-page-container {
                grid-template-columns: 1fr;
            }

            .cart-page-summary {
                position: static;
            }
        }

        @media (max-width: 650px) {

            .cart-item {
                grid-template-columns:
                    75px
                    minmax(0, 1fr);
            }

            .cart-item-image {
                width: 75px;
                height: 95px;
            }

            .cart-item-right {
                grid-column: 2;
                align-items: flex-start;
            }

            .cart-item-subtotal {
                font-size: 17px;
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

        <a href="contact.php">
            Contact
        </a>

        <a
            href="cart.php"
            class="active"
        >
            Cart
        </a>

        <?php if ($logged_in): ?>

            <a href="orders.php">
                My Orders
            </a>

        <?php endif; ?>

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
            >
                Admin Dashboard
            </a>

        <?php endif; ?>

        <?php if (
            $logged_in &&
            $current_user
        ): ?>

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

        <?php if (!$logged_in): ?>

            <div class="cart-page-container">

                <div class="cart-login-message">

                    <h2>
                        Please log in
                    </h2>

                    <p>
                        You need to be logged in
                        to view your cart and
                        continue to checkout.
                    </p>

                    <a
                        href="login.php?return=cart.php"
                        class="checkout-button"
                    >
                        LOG IN TO CONTINUE
                    </a>

                </div>

            </div>

        <?php else: ?>

            <div class="cart-page-container">

                <div>

                    <div
                        id="cartMessage"
                        class="cart-message"
                    ></div>

                    <div
                        id="cartPageItems"
                        class="cart-page-items"
                    >

                        <div class="empty-cart">

                            <p>
                                Loading your cart...
                            </p>

                        </div>

                    </div>

                </div>

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

                    <div class="summary-total">

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
                        onclick="cartPageCheckout()"
                    >
                        PROCEED TO CHECKOUT
                    </button>

                    <a
                        href="shop.php"
                        class="continue-shopping"
                    >
                        ← Continue Shopping
                    </a>

                </div>

            </div>

        <?php endif; ?>

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

<script>

function showCartMessage(
    text,
    type = "error"
) {

    const message =
        document.getElementById(
            "cartMessage"
        );

    if (!message) {
        return;
    }

    message.textContent = text;

    message.className =
        "cart-message show " + type;

}

function clearCartMessage() {

    const message =
        document.getElementById(
            "cartMessage"
        );

    if (!message) {
        return;
    }

    message.textContent = "";

    message.className =
        "cart-message";

}

async function getCartResponse(
    formData
) {

    const response =
        await fetch(
            "php/cart_process.php",
            {
                method: "POST",
                body: formData,
                credentials: "same-origin",
                cache: "no-store"
            }
        );

    const responseText =
        await response.text();

    let data;

    try {

        data =
            JSON.parse(
                responseText
            );

    } catch (error) {

        console.error(
            "cart_process.php returned:",
            responseText
        );

        throw new Error(
            "The cart server returned an unexpected response. Please check php/cart_process.php."
        );

    }

    if (!data.success) {

        throw new Error(
            data.message ||
            "The cart request could not be completed."
        );

    }

    return data;

}

function getCartImageSource(image) {

    const value = String(image || "").trim();

    if (!value) {
        return "";
    }

    if (
        value.startsWith("http://") ||
        value.startsWith("https://") ||
        value.startsWith("data:") ||
        value.startsWith("/images/") ||
        value.startsWith("images/") ||
        value.startsWith("../images/")
    ) {
        return value;
    }

    return "images/" + value.replace(/^\/+/, "");

}

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

    clearCartMessage();

    try {

        const formData =
            new FormData();

        formData.append(
            "action",
            "get"
        );

        const data =
            await getCartResponse(
                formData
            );

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
                        Add some island-inspired
                        pieces before checking out.
                    </p>

                    <a
                        href="shop.php"
                        class="checkout-button"
                    >
                        CONTINUE SHOPPING
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

        let total =
            0;

        let quantity =
            0;

        container.innerHTML =
            "";

        items.forEach(
            (item) => {

                const productId =
                    Number(
                        item.product_id
                    );

                const itemQuantity =
                    Number(
                        item.quantity
                    );

                const price =
                    Number(
                        item.price
                    );

                const stock =
                    Number(
                        item.stock
                    );

                const subtotal =
                    price *
                    itemQuantity;

                total +=
                    subtotal;

                quantity +=
                    itemQuantity;

                let stockText =
                    "In stock";

                let stockClass =
                    "";

                if (stock <= 0) {

                    stockText =
                        "Out of stock";

                    stockClass =
                        "out";

                } else if (
                    stock <= 5
                ) {

                    stockText =
                        stock +
                        " left";

                    stockClass =
                        "low";

                } else {

                    stockText =
                        stock +
                        " available";

                }

                let imageHtml =
                    `
                        <div
                            class="cart-item-image"
                        ></div>
                    `;

                if (
                    item.image
                ) {

                    const imageSource =
                        getCartImageSource(
                            item.image
                        );

                    imageHtml =
                        `
                            <img
                                class="cart-item-image"
                                src="${escapeHtml(
                                    imageSource
                                )}"
                                alt="${escapeHtml(
                                    item.product_name
                                )}"
                                onerror="
                                    this.style.display='none';
                                    this.nextElementSibling.style.display='flex';
                                "
                            >
                            <div
                                class="cart-item-image"
                                style="display:none; align-items:center; justify-content:center; color:#8a8177; font-size:11px; text-align:center; padding:10px;"
                            >
                                Image unavailable
                            </div>
                        `;

                }

                const minusDisabled =
                    itemQuantity <= 1
                        ? "disabled"
                        : "";

                const plusDisabled =
                    stock <= 0 ||
                    itemQuantity >= stock
                        ? "disabled"
                        : "";

                const itemHtml = `

                    <div
                        class="cart-item"
                        data-product-id="${productId}"
                    >

                        ${imageHtml}

                        <div class="cart-item-info">

                            <div class="cart-item-name">
                                ${escapeHtml(
                                    item.product_name
                                )}
                            </div>

                            <div class="cart-item-price">
                                ₱${formatPrice(price)}
                                each
                            </div>

                            <div class="cart-item-controls">

                                <button
                                    type="button"
                                    class="quantity-button"
                                    ${minusDisabled}
                                    onclick="
                                        updateCartQuantity(
                                            ${productId},
                                            ${itemQuantity - 1}
                                        )
                                    "
                                >
                                    −
                                </button>

                                <span
                                    class="quantity-value"
                                >
                                    ${itemQuantity}
                                </span>

                                <button
                                    type="button"
                                    class="quantity-button"
                                    ${plusDisabled}
                                    onclick="
                                        updateCartQuantity(
                                            ${productId},
                                            ${itemQuantity + 1}
                                        )
                                    "
                                >
                                    +
                                </button>

                            </div>

                            <div
                                class="cart-item-stock ${stockClass}"
                            >
                                ${stockText}
                            </div>

                        </div>

                        <div class="cart-item-right">

                            <div
                                class="cart-item-subtotal"
                            >
                                ₱${formatPrice(
                                    subtotal
                                )}
                            </div>

                            <button
                                type="button"
                                class="remove-button"
                                onclick="
                                    removeCartItem(
                                        ${productId}
                                    )
                                "
                            >
                                Remove
                            </button>

                        </div>

                    </div>

                `;

                container.insertAdjacentHTML(
                    "beforeend",
                    itemHtml
                );

            }
        );

        itemCount.textContent =
            quantity;

        subtotalElement.textContent =
            "₱" +
            formatPrice(total);

        totalElement.textContent =
            "₱" +
            formatPrice(total);

        checkoutButton.disabled =
            false;

    } catch (error) {

        console.error(
            error
        );

        container.innerHTML = `

            <div class="empty-cart">

                <h2>
                    Unable to load cart
                </h2>

                <p>
                    ${escapeHtml(
                        error.message ||
                        "Something went wrong."
                    )}
                </p>

                <button
                    type="button"
                    class="checkout-button"
                    onclick="loadDatabaseCartPage()"
                    style="max-width:220px;"
                >
                    TRY AGAIN
                </button>

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

    }

}

async function updateCartQuantity(
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
        quantity < 1
    ) {

        await removeCartItem(
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

        await getCartResponse(
            formData
        );

        await loadDatabaseCartPage();

    } catch (error) {

        console.error(
            error
        );

        showCartMessage(
            error.message ||
            "Unable to update cart."
        );

    }

}

async function removeCartItem(
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

        await getCartResponse(
            formData
        );

        await loadDatabaseCartPage();

    } catch (error) {

        console.error(
            error
        );

        showCartMessage(
            error.message ||
            "Unable to remove item."
        );

    }

}

async function clearCart() {

    if (
        !confirm(
            "Remove all items from your cart?"
        )
    ) {
        return;
    }

    try {

        const formData =
            new FormData();

        formData.append(
            "action",
            "clear"
        );

        await getCartResponse(
            formData
        );

        await loadDatabaseCartPage();

    } catch (error) {

        console.error(
            error
        );

        showCartMessage(
            error.message ||
            "Unable to clear cart."
        );

    }

}

async function cartPageCheckout() {

    const button =
        document.getElementById(
            "cartPageCheckout"
        );

    if (!button) {
        return;
    }

    button.disabled =
        true;

    button.textContent =
        "CHECKING CART...";

    try {

        const formData =
            new FormData();

        formData.append(
            "action",
            "get"
        );

        const data =
            await getCartResponse(
                formData
            );

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

            throw new Error(
                "Your cart is empty."
            );

        }

        window.location.href =
            "checkout.php";

    } catch (error) {

        console.error(
            "Checkout validation error:",
            error
        );

        showCartMessage(
            error.message ||
            "Unable to continue to checkout."
        );

        button.disabled =
            false;

        button.textContent =
            "PROCEED TO CHECKOUT";

    }

}

function escapeHtml(
    value
) {

    return String(value)
        .replace(
            /&/g,
            "&amp;"
        )
        .replace(
            /</g,
            "&lt;"
        )
        .replace(
            />/g,
            "&gt;"
        )
        .replace(
            /"/g,
            "&quot;"
        )
        .replace(
            /'/g,
            "&#039;"
        );

}

function formatPrice(
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

document.addEventListener(
    "DOMContentLoaded",
    () => {

        loadDatabaseCartPage();

    }
);

</script>

</body>

</html>
