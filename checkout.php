<?php

session_start();

require_once __DIR__ . "/php/database.php";

/* =========================================================
   CHECK LOGIN
========================================================= */

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true ||
    !isset($_SESSION["user_id"])
) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

/* =========================================================
   GET USER
========================================================= */

$user_stmt = $conn->prepare(
    "SELECT id, full_name, email
     FROM users
     WHERE id = ?
     LIMIT 1"
);

if (!$user_stmt) {
    die("Unable to load account information.");
}

$user_stmt->bind_param("i", $user_id);

if (!$user_stmt->execute()) {
    $user_stmt->close();
    die("Unable to load account information.");
}

$user_result = $user_stmt->get_result();

if (!$user_result || $user_result->num_rows === 0) {
    $user_stmt->close();

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}

$user = $user_result->fetch_assoc();

$user_stmt->close();

/* =========================================================
   LOAD CART
========================================================= */

$cart_items = [];

$cart_stmt = $conn->prepare(
    "SELECT
        ci.id AS cart_item_id,
        ci.product_id,
        ci.quantity,
        p.product_name,
        p.price,
        p.image,
        p.stock
     FROM cart_items ci
     INNER JOIN cart c
        ON ci.cart_id = c.id
     INNER JOIN products p
        ON ci.product_id = p.id
     WHERE c.user_id = ?
     ORDER BY ci.id ASC"
);

if (!$cart_stmt) {
    die("Unable to load your cart.");
}

$cart_stmt->bind_param("i", $user_id);

if (!$cart_stmt->execute()) {
    $cart_stmt->close();
    die("Unable to load your cart.");
}

$cart_result = $cart_stmt->get_result();

$total_amount = 0;

while ($row = $cart_result->fetch_assoc()) {

    $row["cart_item_id"] = (int) $row["cart_item_id"];
    $row["product_id"] = (int) $row["product_id"];
    $row["quantity"] = (int) $row["quantity"];
    $row["price"] = (float) $row["price"];
    $row["stock"] = (int) $row["stock"];

    $row["subtotal"] =
        $row["price"] * $row["quantity"];

    $total_amount += $row["subtotal"];

    $cart_items[] = $row;
}

$cart_stmt->close();

$total_amount = round($total_amount, 2);

/* =========================================================
   EMPTY CART
========================================================= */

if (empty($cart_items)) {
    header("Location: cart.php");
    exit;
}

/* =========================================================
   ESCAPE
========================================================= */

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

<title>Siquijor Styles | Checkout</title>

<style>

/* =========================================================
   RESET
========================================================= */

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family:
        Inter,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        Arial,
        sans-serif;

    background: #f6f7f9;
    color: #171717;
}

/* =========================================================
   HEADER
========================================================= */

.checkout-header {
    background: #111;
    color: #fff;
    padding: 18px 0;
}

.header-inner {
    width: min(1180px, 92%);
    margin: auto;

    display: flex;
    align-items: center;
    justify-content: space-between;
}

.brand {
    font-size: 21px;
    font-weight: 800;
    letter-spacing: .3px;
}

.secure-checkout {
    font-size: 13px;
    color: #cfcfcf;

    display: flex;
    align-items: center;
    gap: 7px;
}

/* =========================================================
   MAIN
========================================================= */

.container {
    width: min(1180px, 92%);
    margin: 38px auto 60px;
}

.page-heading {
    margin-bottom: 28px;
}

.page-heading h1 {
    margin: 0 0 7px;

    font-size: 32px;
    line-height: 1.2;
    letter-spacing: -.5px;
}

.page-heading p {
    margin: 0;
    color: #707070;
    font-size: 15px;
}

/* =========================================================
   CHECKOUT GRID
========================================================= */

.checkout-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 390px;
    gap: 25px;
    align-items: start;
}

/* =========================================================
   CARDS
========================================================= */

.card {
    background: #fff;
    border: 1px solid #e7e7e7;
    border-radius: 16px;
    box-shadow: 0 6px 25px rgba(0, 0, 0, .04);
}

.card-section {
    padding: 25px;
    border-bottom: 1px solid #ededed;
}

.card-section:last-child {
    border-bottom: none;
}

.card-title {
    display: flex;
    align-items: center;
    gap: 11px;

    margin: 0 0 21px;

    font-size: 18px;
    font-weight: 750;
}

.step-number {
    width: 28px;
    height: 28px;

    border-radius: 50%;

    background: #111;
    color: #fff;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 13px;
    font-weight: 700;
}

/* =========================================================
   ACCOUNT NOTICE
========================================================= */

.account-notice {
    background: #f7f7f7;
    border: 1px solid #e8e8e8;

    border-radius: 11px;

    padding: 13px 15px;
    margin-bottom: 21px;

    font-size: 13px;
    color: #666;
}

/* =========================================================
   FORM
========================================================= */

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.form-group {
    margin-bottom: 17px;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-group label {
    display: block;

    margin-bottom: 7px;

    font-size: 13px;
    font-weight: 700;
    color: #333;
}

.form-group input,
.form-group textarea,
.platform-select {
    width: 100%;

    border: 1px solid #d9d9d9;
    border-radius: 10px;

    padding: 12px 13px;

    background: #fff;
    color: #222;

    font-family: inherit;
    font-size: 14px;

    outline: none;

    transition:
        border-color .2s,
        box-shadow .2s;
}

.form-group input:focus,
.form-group textarea:focus,
.platform-select:focus {
    border-color: #111;

    box-shadow:
        0 0 0 3px rgba(0, 0, 0, .06);
}

.form-group textarea {
    min-height: 105px;
    resize: vertical;
}

/* =========================================================
   PAYMENT OPTIONS
========================================================= */

.payment-options {
    display: grid;
    gap: 12px;
}

.payment-option {
    position: relative;
}

.payment-option input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.payment-option label {
    display: flex;
    align-items: center;
    gap: 13px;

    padding: 16px;

    border: 1px solid #ddd;
    border-radius: 12px;

    cursor: pointer;

    transition:
        border-color .2s,
        background .2s,
        box-shadow .2s;
}

.payment-option label:hover {
    border-color: #aaa;
}

.payment-option input:checked + label {
    border-color: #111;
    background: #fafafa;

    box-shadow:
        0 0 0 1px #111;
}

.payment-icon {
    width: 42px;
    height: 42px;

    border-radius: 10px;

    background: #111;
    color: #fff;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 17px;
    font-weight: 800;

    flex-shrink: 0;
}

.payment-details {
    flex: 1;
}

.payment-title {
    font-size: 14px;
    font-weight: 750;

    margin-bottom: 3px;
}

.payment-description {
    font-size: 12px;
    color: #777;
}

.radio-circle {
    width: 19px;
    height: 19px;

    border: 2px solid #bbb;
    border-radius: 50%;

    position: relative;
}

.payment-option input:checked + label .radio-circle {
    border-color: #111;
}

.payment-option input:checked + label .radio-circle::after {
    content: "";

    position: absolute;

    width: 9px;
    height: 9px;

    background: #111;
    border-radius: 50%;

    top: 3px;
    left: 3px;
}

/* =========================================================
   ONLINE PAYMENT PANEL
========================================================= */

.online-payment-box {
    display: none;

    margin-top: 16px;

    border: 1px solid #dedede;
    border-radius: 13px;

    background: #fafafa;

    padding: 18px;
}

.online-payment-box.show {
    display: block;
}

.online-heading {
    margin-bottom: 15px;
}

.online-heading h3 {
    margin: 0 0 5px;

    font-size: 15px;
}

.online-heading p {
    margin: 0;

    font-size: 12px;
    color: #777;
    line-height: 1.5;
}

/* =========================================================
   PLATFORM
========================================================= */

.platform-label {
    font-size: 13px;
    font-weight: 700;

    margin-bottom: 7px;

    display: block;
}

.platform-select {
    cursor: pointer;
}

/* =========================================================
   PAYMENT INSTRUCTIONS
========================================================= */

.payment-instructions {
    display: none;

    margin-top: 15px;

    background: #fff;

    border: 1px solid #e2e2e2;
    border-radius: 11px;

    padding: 15px;
}

.payment-instructions.show {
    display: block;
}

.payment-instructions h4 {
    margin: 0 0 8px;

    font-size: 14px;
}

.payment-instructions p {
    margin: 5px 0;

    color: #666;

    font-size: 12px;
    line-height: 1.5;
}

.instruction-note {
    margin-top: 12px !important;

    padding: 10px;

    background: #f5f5f5;
    border-radius: 8px;
}

/* =========================================================
   RECEIPT
========================================================= */

.receipt-area {
    margin-top: 16px;
}

.receipt-area label {
    display: block;

    margin-bottom: 7px;

    font-size: 13px;
    font-weight: 700;
}

.receipt-input {
    width: 100%;

    padding: 11px;

    border: 1px dashed #aaa;
    border-radius: 10px;

    background: #fff;

    font-size: 13px;
}

.receipt-help {
    margin: 7px 0 0;

    color: #888;

    font-size: 11px;
}

/* =========================================================
   ORDER SUMMARY
========================================================= */

.summary-card {
    position: sticky;
    top: 20px;
}

.summary-header {
    padding: 22px 22px 18px;

    border-bottom: 1px solid #eee;
}

.summary-header h2 {
    margin: 0 0 4px;

    font-size: 18px;
}

.summary-header p {
    margin: 0;

    font-size: 12px;
    color: #888;
}

.summary-items {
    padding: 5px 22px;
}

.order-item {
    display: flex;
    align-items: center;

    gap: 12px;

    padding: 15px 0;

    border-bottom: 1px solid #eee;
}

.order-item:last-child {
    border-bottom: none;
}

.product-image {
    width: 65px;
    height: 65px;

    border-radius: 10px;

    object-fit: cover;

    background: #eee;

    flex-shrink: 0;
}

.product-info {
    flex: 1;
    min-width: 0;
}

.product-name {
    font-size: 13px;
    font-weight: 700;

    margin-bottom: 5px;

    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.product-meta {
    color: #888;
    font-size: 12px;
}

.product-subtotal {
    font-size: 13px;
    font-weight: 750;

    white-space: nowrap;
}

.summary-total {
    margin: 0 22px;

    padding: 19px 0;

    border-top: 1px solid #ddd;

    display: flex;
    justify-content: space-between;
    align-items: center;
}

.total-label {
    font-size: 14px;
    color: #666;
}

.total-price {
    font-size: 24px;
    font-weight: 800;
}

/* =========================================================
   BUTTON
========================================================= */

.summary-action {
    padding: 0 22px 22px;
}

.checkout-button {
    width: 100%;

    border: none;
    border-radius: 11px;

    padding: 15px;

    background: #111;
    color: #fff;

    font-size: 14px;
    font-weight: 750;

    cursor: pointer;

    transition:
        transform .15s,
        opacity .15s;
}

.checkout-button:hover {
    transform: translateY(-1px);
    opacity: .92;
}

.checkout-button:disabled {
    opacity: .55;
    cursor: not-allowed;
    transform: none;
}

.back-link {
    display: block;

    margin-top: 13px;

    text-align: center;

    color: #666;

    text-decoration: none;

    font-size: 13px;
}

.back-link:hover {
    color: #111;
}

/* =========================================================
   SECURITY
========================================================= */

.security-note {
    text-align: center;

    padding: 15px 22px 21px;

    color: #999;

    font-size: 11px;
}

/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 850px) {

    .checkout-grid {
        grid-template-columns: 1fr;
    }

    .summary-card {
        position: static;
        order: -1;
    }

    .container {
        width: 94%;
        margin-top: 25px;
    }

}

@media (max-width: 550px) {

    .header-inner {
        width: 94%;
    }

    .secure-checkout {
        display: none;
    }

    .page-heading h1 {
        font-size: 27px;
    }

    .card-section {
        padding: 20px;
    }

    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .summary-items {
        padding-left: 18px;
        padding-right: 18px;
    }

    .summary-total {
        margin-left: 18px;
        margin-right: 18px;
    }

    .summary-action {
        padding-left: 18px;
        padding-right: 18px;
    }

}

</style>

</head>

<body>

<!-- =====================================================
     HEADER
====================================================== -->

<header class="checkout-header">

    <div class="header-inner">

        <div class="brand">
            Siquijor Styles
        </div>

        <div class="secure-checkout">
            🔒 Secure Checkout
        </div>

    </div>

</header>

<!-- =====================================================
     MAIN
====================================================== -->

<main class="container">

    <div class="page-heading">

        <h1>Checkout</h1>

        <p>
            Complete your delivery details and choose how you want to pay.
        </p>

    </div>

    <div class="checkout-grid">

        <!-- =================================================
             LEFT
        ================================================== -->

        <div class="card">

            <!-- DELIVERY -->

            <div class="card-section">

                <h2 class="card-title">

                    <span class="step-number">1</span>

                    Delivery Information

                </h2>

                <div class="account-notice">

                    Your account information has been loaded automatically.
                    Please make sure your delivery details are correct.

                </div>

                <form
                    id="checkoutForm"
                    onsubmit="submitOrder(event)"
                    enctype="multipart/form-data"
                >

                    <div class="form-row">

                        <div class="form-group">

                            <label for="full_name">
                                Full Name
                            </label>

                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                value="<?= e($user["full_name"]) ?>"
                                required
                            >

                        </div>

                        <div class="form-group">

                            <label for="phone">
                                Phone Number
                            </label>

                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                placeholder="09XXXXXXXXX"
                                required
                            >

                        </div>

                    </div>

                    <div class="form-group">

                        <label for="email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= e($user["email"]) ?>"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="address">
                            Delivery Address
                        </label>

                        <textarea
                            id="address"
                            name="address"
                            placeholder="House number, street, barangay, municipality, province..."
                            required
                        ></textarea>

                    </div>

                    <!-- PAYMENT -->

                    <h2
                        class="card-title"
                        style="margin-top:30px;"
                    >

                        <span class="step-number">2</span>

                        Payment Method

                    </h2>

                    <div class="payment-options">

                        <!-- COD -->

                        <div class="payment-option">

                            <input
                                type="radio"
                                id="cod"
                                name="payment_method"
                                value="Cash on Delivery"
                                checked
                                onchange="changePaymentMethod()"
                            >

                            <label for="cod">

                                <span class="payment-icon">
                                    ₱
                                </span>

                                <span class="payment-details">

                                    <span class="payment-title">
                                        Cash on Delivery
                                    </span>

                                    <span class="payment-description">
                                        Pay when your order arrives.
                                    </span>

                                </span>

                                <span class="radio-circle"></span>

                            </label>

                        </div>

                        <!-- ONLINE -->

                        <div class="payment-option">

                            <input
                                type="radio"
                                id="online"
                                name="payment_method"
                                value="Online Payment"
                                onchange="changePaymentMethod()"
                            >

                            <label for="online">

                                <span class="payment-icon">
                                    ₱
                                </span>

                                <span class="payment-details">

                                    <span class="payment-title">
                                        Online Payment
                                    </span>

                                    <span class="payment-description">
                                        Pay using an online banking or payment platform.
                                    </span>

                                </span>

                                <span class="radio-circle"></span>

                            </label>

                        </div>

                    </div>

                    <!-- ONLINE PAYMENT -->

                    <div
                        id="onlinePaymentBox"
                        class="online-payment-box"
                    >

                        <div class="online-heading">

                            <h3>
                                Choose your payment platform
                            </h3>

                            <p>
                                Select a platform below. Payment instructions
                                will appear before you upload your receipt.
                            </p>

                        </div>

                        <label
                            class="platform-label"
                            for="payment_platform"
                        >
                            Online Payment Platform
                        </label>

                        <select
                            id="payment_platform"
                            name="payment_platform"
                            class="platform-select"
                            onchange="changePaymentPlatform()"
                        >

                            <option value="">
                                Select a platform
                            </option>

                            <option value="GCash">
                                GCash
                            </option>

                            <option value="Maya">
                                Maya
                            </option>

                            <option value="BDO">
                                BDO Online Banking
                            </option>

                            <option value="BPI">
                                BPI Online Banking
                            </option>

                            <option value="UnionBank">
                                UnionBank Online Banking
                            </option>

                        </select>

                        <!-- INSTRUCTIONS -->

                        <div
                            id="paymentInstructions"
                            class="payment-instructions"
                        >

                            <h4 id="instructionTitle">
                                Payment Instructions
                            </h4>

                            <p id="instructionText">
                            </p>

                            <p class="instruction-note">

                                <strong>Important:</strong>
                                Do not upload a receipt until you have
                                completed your payment.

                            </p>

                        </div>

                        <!-- RECEIPT -->

                        <div class="receipt-area">

                            <label for="payment_receipt">

                                Payment Receipt

                            </label>

                            <input
                                type="file"
                                id="payment_receipt"
                                name="payment_receipt"
                                class="receipt-input"
                                accept="image/jpeg,image/png,image/webp,application/pdf"
                            >

                            <p class="receipt-help">

                                Accepted formats:
                                JPG, PNG, WEBP, or PDF.

                            </p>

                        </div>

                    </div>

                    <button
                        type="submit"
                        class="checkout-button"
                        id="placeOrderButton"
                        style="margin-top:25px;"
                    >
                        Place Order
                    </button>

                </form>

                <a
                    href="cart.php"
                    class="back-link"
                >
                    ← Return to Cart
                </a>

            </div>

        </div>

        <!-- =================================================
             RIGHT / ORDER SUMMARY
        ================================================== -->

        <div class="card summary-card">

            <div class="summary-header">

                <h2>
                    Order Summary
                </h2>

                <p>
                    <?= count($cart_items) ?>
                    <?= count($cart_items) === 1 ? "item" : "items" ?>
                    in your order
                </p>

            </div>

            <div class="summary-items">

                <?php foreach ($cart_items as $item): ?>

                    <div class="order-item">

                        <?php if (!empty($item["image"])): ?>

                            <img
                                class="product-image"
                                src="<?= e($item["image"]) ?>"
                                alt="<?= e($item["product_name"]) ?>"
                                onerror="this.style.display='none';"
                            >

                        <?php else: ?>

                            <div class="product-image"></div>

                        <?php endif; ?>

                        <div class="product-info">

                            <div class="product-name">

                                <?= e($item["product_name"]) ?>

                            </div>

                            <div class="product-meta">

                                ₱<?= number_format($item["price"], 2) ?>

                                ×

                                <?= $item["quantity"] ?>

                            </div>

                        </div>

                        <div class="product-subtotal">

                            ₱<?= number_format($item["subtotal"], 2) ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

            <div class="summary-total">

                <span class="total-label">
                    Total
                </span>

                <span class="total-price">
                    ₱<?= number_format($total_amount, 2) ?>
                </span>

            </div>

            <div class="summary-action">

                <button
                    type="button"
                    class="checkout-button"
                    onclick="document.getElementById('checkoutForm').requestSubmit();"
                    id="summaryOrderButton"
                >
                    Place Order · ₱<?= number_format($total_amount, 2) ?>
                </button>

                <a
                    href="cart.php"
                    class="back-link"
                >
                    Edit Cart
                </a>

            </div>

            <div class="security-note">

                🔒 Your order information is securely submitted.

            </div>

        </div>

    </div>

</main>

<script>

/* =========================================================
   PAYMENT METHOD
========================================================= */

function changePaymentMethod() {

    const onlineRadio =
        document.getElementById("online");

    const onlineBox =
        document.getElementById("onlinePaymentBox");

    const platform =
        document.getElementById("payment_platform");

    const receipt =
        document.getElementById("payment_receipt");

    if (onlineRadio.checked) {

        onlineBox.classList.add("show");

        platform.required = true;

        receipt.required = true;

    } else {

        onlineBox.classList.remove("show");

        platform.required = false;

        receipt.required = false;

        platform.value = "";

        receipt.value = "";

        document
            .getElementById("paymentInstructions")
            .classList.remove("show");
    }
}

/* =========================================================
   PAYMENT PLATFORM
========================================================= */

function changePaymentPlatform() {

    const platform =
        document.getElementById("payment_platform").value;

    const instructions =
        document.getElementById("paymentInstructions");

    const title =
        document.getElementById("instructionTitle");

    const text =
        document.getElementById("instructionText");

    if (platform === "") {

        instructions.classList.remove("show");

        return;
    }

    instructions.classList.add("show");

    title.textContent =
        platform + " Payment";

    /*
     * IMPORTANT:
     * These instructions intentionally do NOT contain
     * fake account numbers, QR codes, or bank details.
     *
     * Replace the text later with your real store
     * payment information.
     */

    if (platform === "GCash") {

        text.textContent =
            "Open GCash and send the order total to the official Siquijor Styles GCash account. Keep your successful transaction receipt.";

    } else if (platform === "Maya") {

        text.textContent =
            "Open Maya and send the order total to the official Siquijor Styles Maya account. Keep your successful transaction receipt.";

    } else if (platform === "BDO") {

        text.textContent =
            "Log in to BDO Online Banking and transfer the order total to the official Siquijor Styles BDO account. Keep your successful transaction receipt.";

    } else if (platform === "BPI") {

        text.textContent =
            "Log in to BPI Online Banking and transfer the order total to the official Siquijor Styles BPI account. Keep your successful transaction receipt.";

    } else if (platform === "UnionBank") {

        text.textContent =
            "Log in to UnionBank Online Banking and transfer the order total to the official Siquijor Styles UnionBank account. Keep your successful transaction receipt.";

    }

}

/* =========================================================
   SUBMIT ORDER
========================================================= */

async function submitOrder(event) {

    event.preventDefault();

    const form =
        document.getElementById("checkoutForm");

    const button =
        document.getElementById("placeOrderButton");

    const summaryButton =
        document.getElementById("summaryOrderButton");

    const fullName =
        document.getElementById("full_name").value.trim();

    const email =
        document.getElementById("email").value.trim();

    const phone =
        document.getElementById("phone").value.trim();

    const address =
        document.getElementById("address").value.trim();

    const paymentMethod =
        document.querySelector(
            'input[name="payment_method"]:checked'
        ).value;

    const platform =
        document.getElementById("payment_platform").value;

    const receipt =
        document.getElementById("payment_receipt");

    /* DELIVERY VALIDATION */

    if (
        fullName === "" ||
        email === "" ||
        phone === "" ||
        address === ""
    ) {

        alert(
            "Please complete all delivery details."
        );

        return;
    }

    /* ONLINE PAYMENT VALIDATION */

    if (paymentMethod === "Online Payment") {

        if (platform === "") {

            alert(
                "Please select an online payment platform."
            );

            return;
        }

        if (receipt.files.length === 0) {

            alert(
                "Please upload your payment receipt."
            );

            return;
        }
    }

    button.disabled = true;

    summaryButton.disabled = true;

    button.textContent =
        "Processing Order...";

    summaryButton.textContent =
        "Processing Order...";

    try {

        const formData =
            new FormData(form);

        const response =
            await fetch(
                "php/order_process.php",
                {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                }
            );

        const responseText =
            await response.text();

        console.log(
            "Server response:",
            responseText
        );

        let result;

        try {

            result =
                JSON.parse(responseText);

        } catch (jsonError) {

            console.error(
                "Invalid server response:",
                responseText
            );

            throw new Error(
                "The server returned an invalid response."
            );
        }

        if (!result.success) {

            throw new Error(
                result.message ||
                "Unable to place the order."
            );
        }

        alert(
            result.message ||
            "Your order has been placed successfully!"
        );

        window.location.href =
            "orders.php";

    } catch (error) {

        console.error(
            "Checkout error:",
            error
        );

        alert(
            error.message ||
            "Something went wrong while placing your order."
        );

        button.disabled = false;

        summaryButton.disabled = false;

        button.textContent =
            "Place Order";

        summaryButton.textContent =
            "Place Order · ₱<?= number_format($total_amount, 2) ?>";
    }
}

/* =========================================================
   INITIAL STATE
========================================================= */

changePaymentMethod();

</script>

</body>

</html>