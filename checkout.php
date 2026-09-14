    <?php
    session_start();
    require_once __DIR__ . "/php/database.php";

    if (empty($_SESSION["logged_in"]) || empty($_SESSION["user_id"])) {
        header("Location: login.php?return=checkout.php");
        exit;
    }

    $user_id = (int) $_SESSION["user_id"];

    function e($value): string {
        return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
    }

    $user_stmt = $conn->prepare(
        "SELECT id, full_name, email
        FROM users
        WHERE id = ?
        LIMIT 1"
    );

    if (!$user_stmt) {
        die("Unable to load account information: " . e($conn->error));
    }

    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();

    $user = $user_stmt->get_result()->fetch_assoc();

    $user_stmt->close();

    if (!$user) {
        session_unset();
        session_destroy();

        header("Location: login.php");
        exit;
    }

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
            ON c.id = ci.cart_id
        INNER JOIN products p
            ON p.id = ci.product_id
        WHERE c.user_id = ?
        ORDER BY ci.id ASC"
    );

    if (!$cart_stmt) {
        die("Unable to load your cart: " . e($conn->error));
    }

    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();

    $cart_result = $cart_stmt->get_result();

    $cart_items = [];
    $total_amount = 0.00;
    $total_quantity = 0;

    while ($row = $cart_result->fetch_assoc()) {

        $row["product_id"] = (int) $row["product_id"];
        $row["quantity"] = (int) $row["quantity"];
        $row["price"] = (float) $row["price"];
        $row["stock"] = (int) $row["stock"];

        $row["subtotal"] = round(
            $row["price"] * $row["quantity"],
            2
        );

        $total_amount += $row["subtotal"];
        $total_quantity += $row["quantity"];

        $cart_items[] = $row;
    }

    $cart_stmt->close();

    $total_amount = round($total_amount, 2);

    if (!$cart_items) {
        header("Location: cart.php");
        exit;
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

        <title>Checkout | Siquijor Styles</title>

        <link
            rel="preconnect"
            href="https://fonts.googleapis.com"
        >

        <link
            rel="preconnect"
            href="https://fonts.gstatic.com"
            crossorigin
        >

        <link
            href="https://fonts.googleapis.com/css2?family=Parisienne&family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap"
            rel="stylesheet"
        >

        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        >

        <style>

            :root {
                --teal: #285e61;
                --teal-dark: #19494c;
                --teal-soft: #e7f0ed;
                --sand: #f7f1e7;
                --cream: #fffaf2;
                --gold: #d6ab63;
                --coral: #d97a68;
                --text: #29464a;
                --muted: #73888b;
                --border: #e5ddd0;
                --white: #ffffff;
                --danger: #a8463b;
                --success: #2d735c;
                --shadow: 0 18px 55px rgba(39, 76, 79, .10);
            }

            * {
                box-sizing: border-box;
            }

            html {
                scroll-behavior: smooth;
            }

            body {
                margin: 0;
                font-family: "Poppins", sans-serif;
                color: var(--text);

                background:
                    radial-gradient(
                        circle at 10% 0%,
                        rgba(214,171,99,.11),
                        transparent 28%
                    ),
                    linear-gradient(
                        180deg,
                        #fffdf8 0%,
                        #f6f1e8 100%
                    );

                min-height: 100vh;
            }

            .checkout-topbar {
                background: rgba(255,255,255,.94);
                border-bottom: 1px solid var(--border);

                position: sticky;
                top: 0;

                z-index: 20;

                backdrop-filter: blur(10px);
            }

            .topbar-inner {
                width: min(1180px, 92%);
                min-height: 76px;

                margin: auto;

                display: flex;
                align-items: center;
                justify-content: space-between;

                gap: 20px;
            }

            .brand {
                display: flex;
                align-items: center;

                gap: 12px;

                text-decoration: none;
                color: var(--teal);
            }

            .brand-mark {
                width: 42px;
                height: 42px;

                border-radius: 50%;

                background: var(--teal);
                color: white;

                display: grid;
                place-items: center;

                box-shadow:
                    0 8px 20px rgba(40,94,97,.2);
            }

            .brand-text strong {
                display: block;

                font-family: "Playfair Display", serif;

                font-size: 20px;
                line-height: 1;
            }

            .brand-text span {
                font-size: 10px;
                color: var(--muted);

                letter-spacing: .14em;
                text-transform: uppercase;
            }

            .secure {
                font-size: 12px;
                color: var(--muted);

                display: flex;
                align-items: center;

                gap: 8px;
            }

            .secure i {
                color: var(--success);
            }

            .page {
                width: min(1180px, 92%);

                margin: 40px auto 70px;
            }

            .heading {
                margin-bottom: 28px;
            }

            .eyebrow {
                color: var(--gold);

                font-size: 11px;
                font-weight: 700;

                letter-spacing: .18em;
                text-transform: uppercase;

                margin-bottom: 8px;
            }

            .heading h1 {
                margin: 0;

                font-family: "Playfair Display", serif;

                color: var(--teal);

                font-size: clamp(34px, 5vw, 52px);

                line-height: 1.05;
            }

            .heading p {
                margin: 10px 0 0;

                color: var(--muted);

                font-size: 13px;
            }

            .checkout-grid {
                display: grid;

                grid-template-columns:
                    minmax(0, 1fr)
                    390px;

                gap: 26px;

                align-items: start;
            }

            .card {
                background: rgba(255,255,255,.96);

                border: 1px solid var(--border);

                border-radius: 22px;

                box-shadow: var(--shadow);

                overflow: hidden;
            }

            .form-card {
                padding: 30px;
            }

            .section + .section {
                margin-top: 34px;

                padding-top: 30px;

                border-top: 1px solid var(--border);
            }

            .section-title {
                display: flex;
                align-items: center;

                gap: 12px;

                margin-bottom: 20px;
            }

            .step {
                width: 34px;
                height: 34px;

                border-radius: 50%;

                background: var(--teal);
                color: white;

                display: grid;
                place-items: center;

                font-size: 13px;
                font-weight: 700;

                box-shadow:
                    0 7px 16px rgba(40,94,97,.2);
            }

            .section-title h2 {
                margin: 0;

                font-family: "Playfair Display", serif;

                color: var(--teal);

                font-size: 23px;
            }

            .section-title p {
                margin: 3px 0 0;

                color: var(--muted);

                font-size: 11px;
            }

            .account-note {
                display: flex;

                gap: 10px;

                align-items: flex-start;

                background: var(--teal-soft);
                color: #456b6d;

                border: 1px solid #d5e5e0;

                padding: 13px 15px;

                border-radius: 12px;

                font-size: 11px;

                line-height: 1.65;

                margin-bottom: 22px;
            }

            .account-note i {
                color: var(--teal);

                margin-top: 3px;
            }

            .form-row {
                display: grid;

                grid-template-columns: 1fr 1fr;

                gap: 15px;
            }

            .field {
                margin-bottom: 16px;
            }

            .field label {
                display: block;

                margin: 0 0 7px;

                font-size: 11px;
                font-weight: 600;

                color: #456267;
            }

            .field input,
            .field textarea,
            .field select {
                width: 100%;

                padding: 13px 14px;

                border: 1px solid #d8d4ca;

                border-radius: 12px;

                background: #fffdf9;

                color: var(--text);

                font: inherit;

                font-size: 12px;

                outline: none;

                transition: .2s ease;
            }

            .field input:focus,
            .field textarea:focus,
            .field select:focus {
                border-color: var(--teal);

                box-shadow:
                    0 0 0 4px rgba(40,94,97,.10);

                background: white;
            }

            .field textarea {
                min-height: 112px;

                resize: vertical;
            }

            .payment-options {
                display: grid;

                grid-template-columns: 1fr 1fr;

                gap: 14px;
            }

            .payment-choice {
                position: relative;
            }

            .payment-choice input {
                position: absolute;

                opacity: 0;

                pointer-events: none;
            }

            .payment-choice label {
                min-height: 112px;

                display: flex;
                align-items: center;

                gap: 12px;

                padding: 16px;

                border: 1px solid #ddd7cc;

                border-radius: 16px;

                cursor: pointer;

                background: #fffdf9;

                transition: .2s ease;
            }

            .payment-choice label:hover {
                transform: translateY(-2px);

                border-color: #b9c9c7;
            }

            .payment-choice input:checked + label {
                border: 2px solid var(--teal);

                background: var(--teal-soft);
            }

            .pay-icon {
                width: 44px;
                height: 44px;

                border-radius: 12px;

                background: var(--teal);
                color: white;

                display: grid;
                place-items: center;

                flex-shrink: 0;
            }

            .pay-copy {
                flex: 1;
            }

            .pay-copy strong {
                display: block;

                color: var(--teal-dark);

                font-size: 12px;

                margin-bottom: 3px;
            }

            .pay-copy span {
                display: block;

                color: var(--muted);

                font-size: 10px;

                line-height: 1.5;
            }

            .check-dot {
                width: 18px;
                height: 18px;

                border: 2px solid #a9b6b4;

                border-radius: 50%;

                position: relative;
            }

            .payment-choice input:checked
            + label
            .check-dot {
                border-color: var(--teal);
            }

            .payment-choice input:checked
            + label
            .check-dot::after {
                content: "";

                position: absolute;

                inset: 3px;

                border-radius: 50%;

                background: var(--teal);
            }

            .online-box {
                display: none;

                margin-top: 16px;

                padding: 19px;

                background: #fbf8f2;

                border: 1px dashed #d5c9b6;

                border-radius: 16px;
            }

            .online-box.show {
                display: block;
            }

            .instructions {
                display: none;

                margin: 13px 0 0;

                padding: 13px;

                border-radius: 12px;

                background: white;

                border-left: 4px solid var(--gold);

                font-size: 10px;

                line-height: 1.65;

                color: #61777a;
            }

            .instructions.show {
                display: block;
            }

            .upload-note {
                font-size: 10px;

                color: var(--muted);

                margin-top: 6px;
            }

            .message {
                display: none;

                border-radius: 12px;

                padding: 13px 15px;

                margin-top: 18px;

                font-size: 11px;

                line-height: 1.55;
            }

            .message.show {
                display: block;
            }

            .message.error {
                background: #fff0ed;

                border: 1px solid #f0c9c1;

                color: var(--danger);
            }

            .message.success {
                background: #ebf7f1;

                border: 1px solid #c7e4d5;

                color: var(--success);
            }

            .place-btn {
                width: 100%;

                margin-top: 20px;

                border: 0;

                border-radius: 13px;

                padding: 15px 18px;

                background:
                    linear-gradient(
                        135deg,
                        var(--teal),
                        var(--teal-dark)
                    );

                color: white;

                font: inherit;

                font-size: 12px;

                font-weight: 700;

                cursor: pointer;

                box-shadow:
                    0 10px 24px rgba(40,94,97,.22);

                transition: .2s ease;
            }

            .place-btn:hover {
                transform: translateY(-2px);
            }

            .place-btn:disabled {
                opacity: .6;

                cursor: wait;

                transform: none;
            }

            .back-link {
                display: inline-flex;

                align-items: center;

                gap: 7px;

                margin-top: 14px;

                color: var(--teal);

                text-decoration: none;

                font-size: 11px;
            }

            .summary-card {
                position: sticky;

                top: 98px;
            }

            .summary-head {
                padding: 24px;

                background:
                    linear-gradient(
                        135deg,
                        var(--teal),
                        #367377
                    );

                color: white;
            }

            .summary-head h2 {
                margin: 0;

                font-family: "Playfair Display", serif;

                font-size: 24px;
            }

            .summary-head p {
                margin: 5px 0 0;

                opacity: .82;

                font-size: 10px;
            }

            .summary-items {
                padding: 10px 22px;

                max-height: 390px;

                overflow-y: auto;
            }

            .summary-item {
                display: grid;

                grid-template-columns:
                    62px
                    1fr
                    auto;

                gap: 11px;

                align-items: center;

                padding: 14px 0;

                border-bottom: 1px solid #eee7dd;
            }

            .summary-item:last-child {
                border-bottom: 0;
            }

            .summary-image {
                width: 62px;
                height: 62px;

                border-radius: 12px;

                object-fit: cover;

                background: #eee8de;
            }

            .summary-name {
                font-size: 11px;

                font-weight: 600;

                color: var(--teal-dark);

                line-height: 1.4;
            }

            .summary-meta {
                margin-top: 4px;

                font-size: 9px;

                color: var(--muted);
            }

            .summary-price {
                font-size: 11px;

                font-weight: 700;

                color: var(--teal);

                white-space: nowrap;
            }

            .totals {
                padding: 18px 22px 24px;

                border-top: 1px solid var(--border);

                background: #fffdf9;
            }

            .total-row {
                display: flex;

                justify-content: space-between;

                gap: 16px;

                font-size: 10px;

                color: var(--muted);

                margin-bottom: 8px;
            }

            .grand-total {
                display: flex;

                justify-content: space-between;

                align-items: center;

                gap: 16px;

                margin-top: 14px;

                padding-top: 14px;

                border-top: 1px dashed #d8d0c3;
            }

            .grand-total strong:first-child {
                font-size: 12px;

                color: var(--text);
            }

            .grand-total strong:last-child {
                font-family: "Playfair Display", serif;

                color: var(--teal);

                font-size: 25px;
            }

            .summary-trust {
                padding: 14px 22px 20px;

                text-align: center;

                font-size: 9px;

                color: var(--muted);
            }

            .summary-trust i {
                color: var(--success);

                margin-right: 4px;
            }

            @media (max-width: 900px) {

                .checkout-grid {
                    grid-template-columns: 1fr;
                }

                .summary-card {
                    position: static;

                    order: -1;
                }

                .summary-items {
                    max-height: none;
                }
            }

            @media (max-width: 600px) {

                .page {
                    width: 94%;

                    margin-top: 25px;
                }

                .topbar-inner {
                    width: 94%;
                }

                .secure {
                    display: none;
                }

                .form-card {
                    padding: 21px;
                }

                .form-row,
                .payment-options {
                    grid-template-columns: 1fr;
                }

                .heading h1 {
                    font-size: 36px;
                }
            }

        </style>

    </head>

    <body>

    <header class="checkout-topbar">

        <div class="topbar-inner">

            <a
                class="brand"
                href="index.php"
            >

                <span class="brand-mark">
                    <i class="fa-solid fa-water"></i>
                </span>

                <span class="brand-text">

                    <strong>
                        Siquijor Styles
                    </strong>

                    <span>
                        Island wear & lifestyle
                    </span>

                </span>

            </a>

            <div class="secure">

                <i class="fa-solid fa-shield-halved"></i>

                Secure checkout

            </div>

        </div>

    </header>

    <main class="page">

        <div class="heading">

            <div class="eyebrow">
                Almost there
            </div>

            <h1>
                Complete your order
            </h1>

            <p>
                Review your items, enter your delivery details,
                and choose your payment method.
            </p>

        </div>

        <div class="checkout-grid">

            <section class="card form-card">

                <form
                    id="checkoutForm"
                    enctype="multipart/form-data"
                    novalidate
                >

                    <div class="section">

                        <div class="section-title">

                            <span class="step">
                                1
                            </span>

                            <div>

                                <h2>
                                    Delivery information
                                </h2>

                                <p>
                                    Where should we send your order?
                                </p>

                            </div>

                        </div>

                        <div class="account-note">

                            <i class="fa-solid fa-circle-info"></i>

                            <span>
                                Your account name and email are filled
                                in automatically. Please check your phone
                                number and complete delivery address before
                                placing the order.
                            </span>

                        </div>

                        <div class="form-row">

                            <div class="field">

                                <label for="full_name">
                                    Full name
                                </label>

                                <input
                                    id="full_name"
                                    name="full_name"
                                    type="text"
                                    value="<?= e($user['full_name']) ?>"
                                    autocomplete="name"
                                    required
                                >

                            </div>

                            <div class="field">

                                <label for="phone">
                                    Phone number
                                </label>

                                <input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    placeholder="09XXXXXXXXX"
                                    autocomplete="tel"
                                    maxlength="20"
                                    required
                                >

                            </div>

                        </div>

                        <div class="field">

                            <label for="email">
                                Email address
                            </label>

                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="<?= e($user['email']) ?>"
                                autocomplete="email"
                                required
                            >

                        </div>

                        <div class="field">

                            <label for="address">
                                Complete delivery address
                            </label>

                            <textarea
                                id="address"
                                name="address"
                                placeholder="House/lot number, street, barangay, municipality/city, province"
                                autocomplete="street-address"
                                required
                            ></textarea>

                        </div>

                    </div>

                    <div class="section">

                        <div class="section-title">

                            <span class="step">
                                2
                            </span>

                            <div>

                                <h2>
                                    Payment method
                                </h2>

                                <p>
                                    Choose the most convenient way to pay.
                                </p>

                            </div>

                        </div>

                        <div class="payment-options">

                            <div class="payment-choice">

                                <input
                                    type="radio"
                                    id="cod"
                                    name="payment_method"
                                    value="Cash on Delivery"
                                    checked
                                >

                                <label for="cod">

                                    <span class="pay-icon">
                                        <i class="fa-solid fa-truck-fast"></i>
                                    </span>

                                    <span class="pay-copy">

                                        <strong>
                                            Cash on Delivery
                                        </strong>

                                        <span>
                                            Pay in cash when your order arrives.
                                        </span>

                                    </span>

                                    <span class="check-dot"></span>

                                </label>

                            </div>

                            <div class="payment-choice">

                                <input
                                    type="radio"
                                    id="online"
                                    name="payment_method"
                                    value="Online Payment"
                                >

                                <label for="online">

                                    <span class="pay-icon">
                                        <i class="fa-solid fa-wallet"></i>
                                    </span>

                                    <span class="pay-copy">

                                        <strong>
                                            Online Payment
                                        </strong>

                                        <span>
                                            Pay first and upload your payment receipt.
                                        </span>

                                    </span>

                                    <span class="check-dot"></span>

                                </label>

                            </div>

                        </div>

                        <div
                            class="online-box"
                            id="onlineBox"
                        >

                            <div class="field">

                                <label for="payment_platform">
                                    Payment platform
                                </label>

                                <select
                                    id="payment_platform"
                                    name="payment_platform"
                                >

                                    <option value="">
                                        Select payment platform
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

                            </div>

                            <div
                                class="instructions"
                                id="instructions"
                            ></div>

                            <div
                                class="field"
                                style="margin-top:14px;margin-bottom:0;"
                            >

                                <label for="payment_receipt">
                                    Payment receipt
                                </label>

                                <input
                                    id="payment_receipt"
                                    name="payment_receipt"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp,application/pdf"
                                >

                                <div class="upload-note">
                                    JPG, PNG, WEBP, or PDF only.
                                    Maximum file size: 5 MB.
                                </div>

                            </div>

                        </div>

                    </div>

                    <div
                        id="checkoutMessage"
                        class="message"
                        role="alert"
                        aria-live="polite"
                    ></div>

                    <button
                        class="place-btn"
                        id="placeOrderButton"
                        type="submit"
                    >

                        <i class="fa-solid fa-lock"></i>

                        &nbsp;&nbsp;

                        Place Order · ₱<?= number_format($total_amount, 2) ?>

                    </button>

                    <a
                        class="back-link"
                        href="cart.php"
                    >

                        <i class="fa-solid fa-arrow-left"></i>

                        Return to cart

                    </a>

                </form>

            </section>

            <aside class="card summary-card">

                <div class="summary-head">

                    <h2>
                        Order summary
                    </h2>

                    <p>
                        <?= $total_quantity ?>
                        item<?= $total_quantity === 1 ? '' : 's' ?>
                        in your cart
                    </p>

                </div>

                <div class="summary-items">

                    <?php foreach ($cart_items as $item): ?>

                        <div class="summary-item">

                            <?php if (!empty($item['image'])): ?>

                                <img
                                    class="summary-image"
                                    src="<?= e($item['image']) ?>"
                                    alt="<?= e($item['product_name']) ?>"
                                    onerror="this.style.visibility='hidden'"
                                >

                            <?php else: ?>

                                <div class="summary-image"></div>

                            <?php endif; ?>

                            <div>

                                <div class="summary-name">
                                    <?= e($item['product_name']) ?>
                                </div>

                                <div class="summary-meta">

                                    ₱<?= number_format($item['price'], 2) ?>

                                    ×

                                    <?= $item['quantity'] ?>

                                </div>

                            </div>

                            <div class="summary-price">

                                ₱<?= number_format($item['subtotal'], 2) ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

                <div class="totals">

                    <div class="total-row">

                        <span>
                            Subtotal
                        </span>

                        <strong>
                            ₱<?= number_format($total_amount, 2) ?>
                        </strong>

                    </div>

                    <div class="total-row">

                        <span>
                            Shipping
                        </span>

                        <strong>
                            Calculated by store
                        </strong>

                    </div>

                    <div class="grand-total">

                        <strong>
                            Order total
                        </strong>

                        <strong>
                            ₱<?= number_format($total_amount, 2) ?>
                        </strong>

                    </div>

                </div>

                <div class="summary-trust">

                    <i class="fa-solid fa-shield-halved"></i>

                    Your checkout information is securely submitted.

                </div>

            </aside>

        </div>

    </main>

    <script>

    const form =
        document.getElementById('checkoutForm');

    const onlineRadio =
        document.getElementById('online');

    const codRadio =
        document.getElementById('cod');

    const onlineBox =
        document.getElementById('onlineBox');

    const platform =
        document.getElementById('payment_platform');

    const receipt =
        document.getElementById('payment_receipt');

    const instructions =
        document.getElementById('instructions');

    const messageBox =
        document.getElementById('checkoutMessage');

    const submitButton =
        document.getElementById('placeOrderButton');

    const defaultButtonHtml =
        submitButton.innerHTML;


    function setMessage(
        text,
        type = 'error'
    ) {

        messageBox.textContent = text;

        messageBox.className =
            'message show ' + type;

        messageBox.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
    }


    function clearMessage() {

        messageBox.textContent = '';

        messageBox.className = 'message';
    }


    function updatePaymentUI() {

        const isOnline =
            onlineRadio.checked;

        onlineBox.classList.toggle(
            'show',
            isOnline
        );

        platform.required =
            isOnline;

        receipt.required =
            isOnline;

        if (!isOnline) {

            platform.value = '';

            receipt.value = '';

            instructions.textContent = '';

            instructions.classList.remove(
                'show'
            );
        }
    }


    function updateInstructions() {

        const copy = {

            GCash:
                'Send the exact order total to the official Siquijor Styles GCash account, then upload the successful payment receipt below.',

            Maya:
                'Send the exact order total to the official Siquijor Styles Maya account, then upload the successful payment receipt below.',

            BDO:
                'Transfer the exact order total to the official Siquijor Styles BDO account, then upload the successful transfer receipt below.',

            BPI:
                'Transfer the exact order total to the official Siquijor Styles BPI account, then upload the successful transfer receipt below.',

            UnionBank:
                'Transfer the exact order total to the official Siquijor Styles UnionBank account, then upload the successful transfer receipt below.'
        };

        if (!copy[platform.value]) {

            instructions.classList.remove(
                'show'
            );

            instructions.textContent = '';

            return;
        }

        instructions.textContent =
            copy[platform.value];

        instructions.classList.add(
            'show'
        );
    }


    onlineRadio.addEventListener(
        'change',
        updatePaymentUI
    );

    codRadio.addEventListener(
        'change',
        updatePaymentUI
    );

    platform.addEventListener(
        'change',
        updateInstructions
    );


    form.addEventListener(
        'submit',
        async (event) => {

            event.preventDefault();

            clearMessage();

            if (!form.checkValidity()) {

                form.reportValidity();

                return;
            }

            const phone =
                document
                    .getElementById('phone')
                    .value
                    .trim();

            if (
                !/^[0-9+()\-\s]{7,20}$/.test(phone)
            ) {

                setMessage(
                    'Please enter a valid phone number.'
                );

                return;
            }

            if (onlineRadio.checked) {

                if (!platform.value) {

                    setMessage(
                        'Please select an online payment platform.'
                    );

                    return;
                }

                if (!receipt.files.length) {

                    setMessage(
                        'Please upload your payment receipt.'
                    );

                    return;
                }

                if (
                    receipt.files[0].size >
                    5 * 1024 * 1024
                ) {

                    setMessage(
                        'Payment receipt must not exceed 5 MB.'
                    );

                    return;
                }
            }

            submitButton.disabled = true;

            submitButton.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i>&nbsp;&nbsp;Processing your order...';

            try {

                const response =
                    await fetch(
                        'php/order_process.php',
                        {
                            method: 'POST',

                            body:
                                new FormData(form),

                            credentials:
                                'same-origin'
                        }
                    );

                const raw =
                    await response.text();

                let result;

                try {

                    result =
                        JSON.parse(raw);

                } catch (_) {

                    console.error(
                        'Invalid response:',
                        raw
                    );

                    throw new Error(
                        'The server returned an invalid response. Check PHP error logs if this continues.'
                    );
                }

                if (
                    !response.ok ||
                    !result.success
                ) {

                    throw new Error(
                        result.message ||
                        'Unable to place your order.'
                    );
                }

                setMessage(
                    result.message ||
                    'Order placed successfully!',
                    'success'
                );

                setTimeout(
                    () => {
                        window.location.href =
                            'orders.php';
                    },
                    700
                );

            } catch (error) {

                console.error(error);

                setMessage(
                    error.message ||
                    'Something went wrong while placing your order.'
                );

                submitButton.disabled =
                    false;

                submitButton.innerHTML =
                    defaultButtonHtml;
            }
        }
    );


    updatePaymentUI();

    </script>

    </body>

    </html>