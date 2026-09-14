```php
<?php

session_start();

require_once __DIR__ . "/php/database.php";

header("Content-Type: text/html; charset=UTF-8");

/* =========================================================
   LOGIN CHECK
========================================================= */

if (
    empty($_SESSION["logged_in"]) ||
    empty($_SESSION["user_id"])
) {
    header("Location: login.php?return=orders.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

/* =========================================================
   HELPER
========================================================= */

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

/* =========================================================
   CANCEL ORDER
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["cancel_order"])
) {

    $order_id = (int) ($_POST["order_id"] ?? 0);

    if ($order_id <= 0) {
        header("Location: orders.php?error=Invalid order.");
        exit;
    }

    $conn->begin_transaction();

    try {

        /* =================================================
           LOCK THE ORDER
        ================================================= */

        $order_stmt = $conn->prepare("
            SELECT
                id,
                status,
                created_at
            FROM orders
            WHERE id = ?
              AND user_id = ?
            LIMIT 1
            FOR UPDATE
        ");

        if (!$order_stmt) {
            throw new Exception(
                "Unable to access the order."
            );
        }

        $order_stmt->bind_param(
            "ii",
            $order_id,
            $user_id
        );

        if (!$order_stmt->execute()) {
            $order_stmt->close();

            throw new Exception(
                "Unable to access the order."
            );
        }

        $order_result = $order_stmt->get_result();

        if (
            !$order_result ||
            $order_result->num_rows === 0
        ) {

            $order_stmt->close();

            throw new Exception(
                "Order not found."
            );
        }

        $order = $order_result->fetch_assoc();

        $order_stmt->close();

        /* =================================================
           CHECK STATUS
        ================================================= */

        if ($order["status"] !== "Pending") {

            throw new Exception(
                "Only pending orders can be cancelled."
            );
        }

        /* =================================================
           CHECK 7-DAY PERIOD
        ================================================= */

        $created_at = strtotime(
            $order["created_at"]
        );

        $now = time();

        $elapsed = $now - $created_at;

        $five_days =
            5 * 24 * 60 * 60;

        $seven_days =
            7 * 24 * 60 * 60;

        if ($elapsed < 0) {

            throw new Exception(
                "Invalid order creation time."
            );
        }

        if ($elapsed >= $seven_days) {

            throw new Exception(
                "The cancellation period has expired."
            );
        }

        if ($elapsed >= $five_days) {

            throw new Exception(
                "Cancellation is locked during days 6 and 7."
            );
        }

        /* =================================================
           GET ORDER ITEMS
        ================================================= */

        $items_stmt = $conn->prepare("
            SELECT
                product_id,
                quantity
            FROM order_items
            WHERE order_id = ?
            FOR UPDATE
        ");

        if (!$items_stmt) {

            throw new Exception(
                "Unable to load order items."
            );
        }

        $items_stmt->bind_param(
            "i",
            $order_id
        );

        if (!$items_stmt->execute()) {

            $items_stmt->close();

            throw new Exception(
                "Unable to load order items."
            );
        }

        $items_result =
            $items_stmt->get_result();

        /* =================================================
           RESTORE STOCK
        ================================================= */

        while (
            $item =
            $items_result->fetch_assoc()
        ) {

            $product_id =
                (int) $item["product_id"];

            $quantity =
                (int) $item["quantity"];

            if (
                $product_id <= 0 ||
                $quantity <= 0
            ) {
                continue;
            }

            $stock_stmt = $conn->prepare("
                UPDATE products
                SET stock = stock + ?
                WHERE id = ?
            ");

            if (!$stock_stmt) {

                $items_stmt->close();

                throw new Exception(
                    "Unable to restore product stock."
                );
            }

            $stock_stmt->bind_param(
                "ii",
                $quantity,
                $product_id
            );

            if (!$stock_stmt->execute()) {

                $stock_stmt->close();
                $items_stmt->close();

                throw new Exception(
                    "Unable to restore product stock."
                );
            }

            $stock_stmt->close();
        }

        $items_stmt->close();

        /* =================================================
           CANCEL ORDER
        ================================================= */

        $cancel_stmt = $conn->prepare("
            UPDATE orders
            SET status = 'Cancelled'
            WHERE id = ?
              AND user_id = ?
              AND status = 'Pending'
        ");

        if (!$cancel_stmt) {

            throw new Exception(
                "Unable to cancel the order."
            );
        }

        $cancel_stmt->bind_param(
            "ii",
            $order_id,
            $user_id
        );

        if (
            !$cancel_stmt->execute() ||
            $cancel_stmt->affected_rows !== 1
        ) {

            $cancel_stmt->close();

            throw new Exception(
                "The order could not be cancelled."
            );
        }

        $cancel_stmt->close();

        /* =================================================
           COMMIT
        ================================================= */

        $conn->commit();

        header(
            "Location: orders.php?cancelled=1"
        );

        exit;

    } catch (Throwable $e) {

        $conn->rollback();

        header(
            "Location: orders.php?error=" .
            urlencode($e->getMessage())
        );

        exit;
    }
}

/* =========================================================
   REMOVE CANCELLED ORDER
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["remove_order"])
) {

    $order_id =
        (int) ($_POST["order_id"] ?? 0);

    if ($order_id <= 0) {

        header(
            "Location: orders.php?error=Invalid order."
        );

        exit;
    }

    $conn->begin_transaction();

    try {

        /* =================================================
           VERIFY CANCELLED ORDER
        ================================================= */

        $check_stmt = $conn->prepare("
            SELECT
                id,
                status
            FROM orders
            WHERE id = ?
              AND user_id = ?
            LIMIT 1
            FOR UPDATE
        ");

        if (!$check_stmt) {

            throw new Exception(
                "Unable to access the order."
            );
        }

        $check_stmt->bind_param(
            "ii",
            $order_id,
            $user_id
        );

        if (!$check_stmt->execute()) {

            $check_stmt->close();

            throw new Exception(
                "Unable to access the order."
            );
        }

        $check_result =
            $check_stmt->get_result();

        if (
            !$check_result ||
            $check_result->num_rows === 0
        ) {

            $check_stmt->close();

            throw new Exception(
                "Order not found."
            );
        }

        $check_order =
            $check_result->fetch_assoc();

        $check_stmt->close();

        /* =================================================
           ONLY CANCELLED ORDERS CAN BE REMOVED
        ================================================= */

        if (
            $check_order["status"] !== "Cancelled"
        ) {

            throw new Exception(
                "Only cancelled orders can be removed."
            );
        }

        /* =================================================
           DELETE ORDER ITEMS FIRST
        ================================================= */

        $delete_items_stmt =
            $conn->prepare("
                DELETE FROM order_items
                WHERE order_id = ?
            ");

        if (!$delete_items_stmt) {

            throw new Exception(
                "Unable to remove order items."
            );
        }

        $delete_items_stmt->bind_param(
            "i",
            $order_id
        );

        if (
            !$delete_items_stmt->execute()
        ) {

            $delete_items_stmt->close();

            throw new Exception(
                "Unable to remove order items."
            );
        }

        $delete_items_stmt->close();

        /* =================================================
           DELETE ORDER
        ================================================= */

        $delete_order_stmt =
            $conn->prepare("
                DELETE FROM orders
                WHERE id = ?
                  AND user_id = ?
                  AND status = 'Cancelled'
            ");

        if (!$delete_order_stmt) {

            throw new Exception(
                "Unable to remove the order."
            );
        }

        $delete_order_stmt->bind_param(
            "ii",
            $order_id,
            $user_id
        );

        if (
            !$delete_order_stmt->execute()
        ) {

            $delete_order_stmt->close();

            throw new Exception(
                "Unable to remove the order."
            );
        }

        if (
            $delete_order_stmt->affected_rows !== 1
        ) {

            $delete_order_stmt->close();

            throw new Exception(
                "The order could not be removed."
            );
        }

        $delete_order_stmt->close();

        /* =================================================
           COMMIT
        ================================================= */

        $conn->commit();

        header(
            "Location: orders.php?removed=1"
        );

        exit;

    } catch (Throwable $e) {

        $conn->rollback();

        header(
            "Location: orders.php?error=" .
            urlencode($e->getMessage())
        );

        exit;
    }
}

/* =========================================================
   GET USER
========================================================= */

$user_stmt = $conn->prepare("
    SELECT
        full_name,
        email
    FROM users
    WHERE id = ?
    LIMIT 1
");

if (!$user_stmt) {

    die(
        "Unable to load user information."
    );
}

$user_stmt->bind_param(
    "i",
    $user_id
);

$user_stmt->execute();

$user_result =
    $user_stmt->get_result();

$user =
    $user_result->fetch_assoc();

$user_stmt->close();

if (!$user) {

    session_destroy();

    header(
        "Location: login.php"
    );

    exit;
}

/* =========================================================
   GET ORDERS
========================================================= */

$order_stmt = $conn->prepare("
    SELECT
        id,
        shipping_name,
        shipping_email,
        shipping_phone,
        shipping_address,
        total_amount,
        status,
        payment_method,
        payment_status,
        created_at
    FROM orders
    WHERE user_id = ?
    ORDER BY id DESC
");

if (!$order_stmt) {

    die(
        "Unable to load orders."
    );
}

$order_stmt->bind_param(
    "i",
    $user_id
);

$order_stmt->execute();

$orders_result =
    $order_stmt->get_result();

/* =========================================================
   SERVER TIME
========================================================= */

$server_now = time();

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
        My Orders | Siquijor Styles
    </title>

    <!-- GOOGLE FONTS -->

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
        href="https://fonts.googleapis.com/css2?family=Parisienne&family=Playfair+Display:wght@400;500;600&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet"
    >

    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >

    <!-- MAIN CSS -->

    <link
        rel="stylesheet"
        href="css/style.css"
    >

    <style>

        /* =====================================================
           ORDERS PAGE
        ===================================================== */

        .orders-page {

            background: #f8f5ee;

            padding:
                65px 0 90px;

            min-height:
                700px;
        }

        .orders-heading {

            width:
                min(1180px, 90%);

            margin:
                0 auto 40px;

            text-align:
                center;
        }

        .orders-heading .eyebrow {

            margin-bottom:
                10px;
        }

        .orders-heading h1 {

            margin:
                0;

            font-family:
                "Playfair Display",
                serif;

            font-size:
                clamp(36px, 4vw, 52px);

            font-weight:
                500;

            font-style:
                italic;

            color:
                var(--teal);
        }

        .orders-heading p:last-child {

            margin:
                12px auto 0;

            color:
                #637a7d;

            font-size:
                13px;
        }

        .orders-container {

            width:
                min(1000px, 90%);

            margin:
                0 auto;
        }

        .orders-list {

            display:
                flex;

            flex-direction:
                column;

            gap:
                25px;
        }

        .order-card {

            background:
                #ffffff;

            border:
                1px solid #e5ddd2;

            border-radius:
                18px;

            padding:
                28px;

            box-shadow:
                0 8px 25px
                rgba(34, 72, 82, 0.07);
        }

        .order-header {

            display:
                flex;

            justify-content:
                space-between;

            align-items:
                center;

            gap:
                20px;

            padding-bottom:
                18px;

            margin-bottom:
                20px;

            border-bottom:
                1px solid #e6dfd4;
        }

        .order-header h2 {

            margin:
                0;

            font-family:
                "Playfair Display",
                serif;

            color:
                var(--teal);

            font-size:
                24px;
        }

        .order-status {

            display:
                inline-flex;

            align-items:
                center;

            padding:
                7px 14px;

            border-radius:
                20px;

            background:
                #eef5f3;

            color:
                var(--teal);

            font-size:
                11px;

            font-weight:
                600;

            text-transform:
                capitalize;
        }

        .order-status.cancelled {

            background:
                #f8eaea;

            color:
                #9b3b3b;
        }

        .order-details {

            display:
                grid;

            grid-template-columns:
                1fr 1fr;

            gap:
                10px 30px;

            margin-bottom:
                20px;
        }

        .order-detail {

            color:
                #637579;

            font-size:
                12px;

            line-height:
                1.6;
        }

        .order-detail strong {

            color:
                #294f59;
        }

        .order-items-title {

            margin:
                20px 0 12px;

            color:
                var(--teal);

            font-size:
                14px;

            font-weight:
                600;
        }

        .order-items {

            display:
                flex;

            flex-direction:
                column;

            gap:
                8px;

            border-top:
                1px solid #ece5dc;

            border-bottom:
                1px solid #ece5dc;

            padding:
                12px 0;
        }

        .order-item {

            display:
                flex;

            justify-content:
                space-between;

            align-items:
                center;

            gap:
                20px;

            padding:
                8px 0;

            color:
                #5f7479;

            font-size:
                12px;
        }

        .order-item strong {

            color:
                var(--teal);

            font-weight:
                600;
        }

        .order-item span {

            white-space:
                nowrap;
        }

        .order-total {

            display:
                flex;

            justify-content:
                space-between;

            align-items:
                center;

            margin-top:
                18px;
        }

        .order-total span {

            color:
                #526a70;

            font-size:
                13px;

            font-weight:
                500;
        }

        .order-total strong {

            color:
                var(--teal);

            font-size:
                21px;
        }

        /* =====================================================
           MESSAGES
        ===================================================== */

        .order-success-message {

            margin-bottom:
                25px;

            padding:
                13px 16px;

            border-radius:
                10px;

            background:
                #edf8f2;

            border:
                1px solid #b9dfc8;

            color:
                #286440;

            font-size:
                12px;
        }

        .order-error-message {

            margin-bottom:
                25px;

            padding:
                13px 16px;

            border-radius:
                10px;

            background:
                #fff0f0;

            border:
                1px solid #e2baba;

            color:
                #8b3232;

            font-size:
                12px;
        }

        /* =====================================================
           PENDING WARNING
        ===================================================== */

        .order-warning {

            margin:
                20px 0;

            padding:
                18px;

            border:
                1px solid #e6d5a8;

            border-radius:
                14px;

            background:
                #fff9e8;
        }

        .order-warning.locked {

            background:
                #f7eeee;

            border-color:
                #e2c2c2;
        }

        .order-warning-header {

            display:
                flex;

            align-items:
                flex-start;

            gap:
                14px;
        }

        .order-warning-icon {

            width:
                38px;

            height:
                38px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            flex-shrink:
                0;

            border-radius:
                50%;

            background:
                #f3df9b;

            color:
                #765c14;
        }

        .order-warning.locked
        .order-warning-icon {

            background:
                #e7caca;

            color:
                #8b3232;
        }

        .order-warning-content {

            flex:
                1;
        }

        .order-warning-title {

            margin:
                0 0 5px;

            color:
                #6c5718;

            font-size:
                14px;

            font-weight:
                600;
        }

        .order-warning.locked
        .order-warning-title {

            color:
                #8b3232;
        }

        .order-warning-text {

            margin:
                0;

            color:
                #756b4c;

            font-size:
                12px;

            line-height:
                1.6;
        }

        .order-warning.locked
        .order-warning-text {

            color:
                #805f5f;
        }

        /* =====================================================
           COUNTDOWN
        ===================================================== */

        .order-countdown {

            display:
                inline-flex;

            align-items:
                center;

            gap:
                8px;

            margin-top:
                14px;

            padding:
                8px 12px;

            border-radius:
                8px;

            background:
                #ffffff;

            border:
                1px solid #e4d8b9;

            color:
                #635019;

            font-size:
                12px;

            font-weight:
                600;
        }

        .order-warning.locked
        .order-countdown {

            border-color:
                #ddc1c1;

            color:
                #8b3232;
        }

        /* =====================================================
           CANCEL BUTTON
        ===================================================== */

        .cancel-order-form {

            margin-top:
                14px;
        }

        .cancel-order-button {

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            gap:
                8px;

            padding:
                10px 16px;

            border:
                none;

            border-radius:
                8px;

            background:
                #a84d4d;

            color:
                #ffffff;

            font-family:
                "Poppins",
                sans-serif;

            font-size:
                12px;

            font-weight:
                500;

            cursor:
                pointer;

            transition:
                0.2s ease;
        }

        .cancel-order-button:hover {

            background:
                #8e3d3d;

            transform:
                translateY(-1px);
        }

        .cancel-order-button.locked,
        .cancel-order-button:disabled {

            background:
                #d7d1ca;

            color:
                #8b8580;

            cursor:
                not-allowed;

            transform:
                none;
        }

        /* =====================================================
           REMOVE CANCELLED ORDER
        ===================================================== */

        .remove-order-area {

            display:
                flex;

            justify-content:
                flex-end;

            margin-top:
                18px;

            padding-top:
                18px;

            border-top:
                1px solid #ece5dc;
        }

        .remove-order-button {

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            gap:
                8px;

            padding:
                10px 16px;

            border:
                1px solid #d8bcbc;

            border-radius:
                8px;

            background:
                #fff7f7;

            color:
                #9b3b3b;

            font-family:
                "Poppins",
                sans-serif;

            font-size:
                12px;

            font-weight:
                500;

            cursor:
                pointer;

            transition:
                0.2s ease;
        }

        .remove-order-button:hover {

            background:
                #9b3b3b;

            border-color:
                #9b3b3b;

            color:
                #ffffff;

            transform:
                translateY(-1px);
        }

        /* =====================================================
           EMPTY
        ===================================================== */

        .orders-empty {

            background:
                #ffffff;

            border:
                1px solid #e5ddd2;

            border-radius:
                18px;

            padding:
                65px 30px;

            text-align:
                center;

            box-shadow:
                0 8px 25px
                rgba(34, 72, 82, 0.07);
        }

        .orders-empty-icon {

            color:
                var(--teal);

            font-size:
                44px;

            margin-bottom:
                18px;
        }

        .orders-empty h2 {

            margin-bottom:
                10px;

            color:
                var(--teal);

            font-family:
                "Playfair Display",
                serif;
        }

        .orders-empty p {

            margin-bottom:
                25px;

            color:
                #6c7c80;

            font-size:
                12px;
        }

        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 700px) {

            .orders-page {

                padding:
                    45px 0 70px;
            }

            .order-card {

                padding:
                    20px;
            }

            .order-details {

                grid-template-columns:
                    1fr;
            }

            .order-header {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }

            .order-item {

                align-items:
                    flex-start;

                flex-direction:
                    column;

                gap:
                    4px;
            }

            .order-total strong {

                font-size:
                    19px;
            }

            .remove-order-area {

                justify-content:
                    stretch;
            }

            .remove-order-button {

                width:
                    100%;
            }
        }

    </style>

</head>

<body>

<!-- =========================================================
     HEADER
========================================================= -->

<header class="header">

    <a
        href="index.php"
        class="logo-area"
    >

        <img
            src="images/logo.png"
            alt="Siquijor Styles"
            style="
                max-width:100%;
                max-height:55px;
                object-fit:contain;
            "
            onerror="
                this.style.display='none';
                this.parentElement.innerHTML=
                '<strong style=\"font-family:Playfair Display,serif;font-size:20px;color:#176d86;\">Siquijor Styles</strong>';
            "
        >

    </a>

    <nav class="navigation">

        <a href="index.php">
            HOME
        </a>

        <a href="shop.php">
            SHOP
        </a>

        <a href="about.php">
            ABOUT
        </a>

        <a href="contact.php">
            CONTACT
        </a>

        <a
            href="orders.php"
            class="active"
        >
            ORDERS
        </a>

    </nav>

    <div class="header-tools">

        <!-- PROFILE -->

        <a
            class="header-icon profile-button"
            href="orders.php"
            title="My Profile"
            aria-label="My Profile"
        >

            <i class="fa-regular fa-user"></i>

        </a>

        <!-- CART -->

        <a
            href="cart.php"
            class="header-icon cart-button"
            aria-label="Cart"
            title="Cart"
        >

            <i class="fa-solid fa-bag-shopping"></i>

        </a>

        <!-- LOGOUT -->

        <a
            href="logout.php"
            class="login-button"
        >
            LOGOUT
        </a>

    </div>

</header>

<!-- =========================================================
     MAIN
========================================================= -->

<main>

<section class="orders-page">

    <div class="orders-heading">

        <p class="eyebrow">
            SIQUIJOR STYLES
        </p>

        <h1>
            My Orders
        </h1>

        <p>
            View your orders and delivery information.
        </p>

    </div>

    <div class="orders-container">

        <!-- =================================================
             SUCCESS MESSAGE
        ================================================= -->

        <?php if (
            isset($_GET["cancelled"])
        ): ?>

            <div class="order-success-message">

                <i class="fa-solid fa-circle-check"></i>

                Your order has been cancelled successfully.
                The purchased quantity has been returned to stock.

            </div>

        <?php endif; ?>

        <?php if (
            isset($_GET["removed"])
        ): ?>

            <div class="order-success-message">

                <i class="fa-solid fa-trash"></i>

                The cancelled order has been removed
                from your order history.

            </div>

        <?php endif; ?>

        <!-- =================================================
             ERROR MESSAGE
        ================================================= -->

        <?php if (
            isset($_GET["error"])
        ): ?>

            <div class="order-error-message">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?= e($_GET["error"]) ?>

            </div>

        <?php endif; ?>

        <!-- =================================================
             EMPTY
        ================================================= -->

        <?php if (
            $orders_result->num_rows === 0
        ): ?>

            <div class="orders-empty">

                <div class="orders-empty-icon">

                    <i class="fa-solid fa-receipt"></i>

                </div>

                <h2>
                    No Orders Yet
                </h2>

                <p>
                    You haven't placed any orders yet.
                </p>

                <a
                    href="shop.php"
                    class="primary-button"
                >
                    START SHOPPING
                </a>

            </div>

        <?php else: ?>

            <div class="orders-list">

                <?php while (
                    $order =
                    $orders_result->fetch_assoc()
                ): ?>

                    <?php

                    $created_timestamp =
                        strtotime(
                            $order["created_at"]
                        );

                    $elapsed =
                        $server_now -
                        $created_timestamp;

                    $five_days =
                        5 * 24 * 60 * 60;

                    $seven_days =
                        7 * 24 * 60 * 60;

                    $can_cancel =
                        $order["status"] === "Pending" &&
                        $elapsed >= 0 &&
                        $elapsed < $five_days;

                    $cancellation_locked =
                        $order["status"] === "Pending" &&
                        $elapsed >= $five_days;

                    $seconds_remaining =
                        max(
                            0,
                            $seven_days -
                            max(0, $elapsed)
                        );

                    ?>

                    <article
                        class="order-card"
                        data-order-created="<?= $created_timestamp ?>"
                        data-order-id="<?= (int) $order["id"] ?>"
                    >

                        <!-- =================================================
                             ORDER HEADER
                        ================================================= -->

                        <div class="order-header">

                            <h2>

                                Order #<?= (int)
                                    $order["id"] ?>

                            </h2>

                            <span
                                class="order-status
                                <?= strtolower(
                                    $order["status"]
                                ) === "cancelled"
                                    ? "cancelled"
                                    : ""
                                ?>"
                            >

                                <?= e(
                                    $order["status"]
                                ) ?>

                            </span>

                        </div>

                        <!-- =================================================
                             CUSTOMER DETAILS
                        ================================================= -->

                        <div class="order-details">

                            <div class="order-detail">

                                <strong>
                                    Customer:
                                </strong>

                                <?= e(
                                    $order["shipping_name"]
                                ) ?>

                            </div>

                            <div class="order-detail">

                                <strong>
                                    Email:
                                </strong>

                                <?= e(
                                    $order["shipping_email"]
                                ) ?>

                            </div>

                            <div class="order-detail">

                                <strong>
                                    Phone:
                                </strong>

                                <?= e(
                                    $order["shipping_phone"]
                                ) ?>

                            </div>

                            <div class="order-detail">

                                <strong>
                                    Delivery Address:
                                </strong>

                                <?= nl2br(
                                    e(
                                        $order[
                                            "shipping_address"
                                        ]
                                    )
                                ) ?>

                            </div>

                            <div class="order-detail">

                                <strong>
                                    Payment:
                                </strong>

                                <?= e(
                                    $order["payment_method"]
                                ) ?>

                            </div>

                            <div class="order-detail">

                                <strong>
                                    Order Date:
                                </strong>

                                <?= e(
                                    date(
                                        "F j, Y g:i A",
                                        $created_timestamp
                                    )
                                ) ?>

                            </div>

                        </div>

                        <!-- =================================================
                             PENDING WARNING
                        ================================================= -->

                        <?php if (
                            $order["status"] === "Pending"
                        ): ?>

                            <div
                                class="
                                    order-warning
                                    <?= $cancellation_locked
                                        ? "locked"
                                        : ""
                                    ?>"
                                data-warning
                            >

                                <div
                                    class="order-warning-header"
                                >

                                    <div
                                        class="order-warning-icon"
                                    >

                                        <i
                                            class="
                                                fa-solid
                                                fa-triangle-exclamation
                                            "
                                        ></i>

                                    </div>

                                    <div
                                        class="order-warning-content"
                                    >

                                        <p
                                            class="
                                                order-warning-title
                                            "
                                            data-warning-title
                                        >

                                            <?php if (
                                                $cancellation_locked
                                            ): ?>

                                                Cancellation Locked

                                            <?php else: ?>

                                                Pending Order

                                            <?php endif; ?>

                                        </p>

                                        <p
                                            class="
                                                order-warning-text
                                            "
                                            data-warning-text
                                        >

                                            <?php if (
                                                $cancellation_locked
                                            ): ?>

                                                Your order is now in
                                                the final processing
                                                period. Cancellation
                                                is no longer available
                                                during days 6–7.

                                            <?php else: ?>

                                                You may cancel this
                                                pending order during
                                                the first 5 days.
                                                Cancellation becomes
                                                locked during days 6–7.

                                            <?php endif; ?>

                                        </p>

                                        <div
                                            class="
                                                order-countdown
                                            "
                                            data-countdown
                                        >

                                            <i
                                                class="
                                                    fa-regular
                                                    fa-clock
                                                "
                                            ></i>

                                            <span
                                                data-countdown-text
                                            >

                                                <?php

                                                $days =
                                                    floor(
                                                        $seconds_remaining /
                                                        86400
                                                    );

                                                $hours =
                                                    floor(
                                                        (
                                                            $seconds_remaining %
                                                            86400
                                                        ) /
                                                        3600
                                                    );

                                                $minutes =
                                                    floor(
                                                        (
                                                            $seconds_remaining %
                                                            3600
                                                        ) /
                                                        60
                                                    );

                                                $seconds =
                                                    $seconds_remaining %
                                                    60;

                                                ?>

                                                <?= $days ?>d
                                                <?= $hours ?>h
                                                <?= $minutes ?>m
                                                <?= $seconds ?>s

                                            </span>

                                        </div>

                                        <!-- =================================================
                                             CANCEL BUTTON
                                        ================================================= -->

                                        <?php if (
                                            $can_cancel
                                        ): ?>

                                            <form
                                                method="POST"
                                                class="cancel-order-form"
                                                onsubmit="
                                                    return confirm(
                                                        'Are you sure you want to cancel this order?'
                                                    );
                                                "
                                            >

                                                <input
                                                    type="hidden"
                                                    name="order_id"
                                                    value="<?= (int)
                                                        $order["id"] ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="cancel_order"
                                                    value="1"
                                                >

                                                <button
                                                    type="submit"
                                                    class="cancel-order-button"
                                                    data-cancel-button
                                                >

                                                    <i
                                                        class="
                                                            fa-solid
                                                            fa-ban
                                                        "
                                                    ></i>

                                                    Cancel Order

                                                </button>

                                            </form>

                                        <?php else: ?>

                                            <button
                                                type="button"
                                                class="
                                                    cancel-order-button
                                                    locked
                                                "
                                                disabled
                                                data-cancel-button
                                            >

                                                <i
                                                    class="
                                                        fa-solid
                                                        fa-lock
                                                    "
                                                ></i>

                                                Cancellation Locked

                                            </button>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </div>

                        <?php endif; ?>

                        <!-- =================================================
                             CANCELLED ORDER REMOVE BUTTON
                        ================================================= -->

                        <?php if (
                            $order["status"] === "Cancelled"
                        ): ?>

                            <div
                                class="remove-order-area"
                            >

                                <form
                                    method="POST"
                                    onsubmit="
                                        return confirm(
                                            'Are you sure you want to permanently remove this cancelled order from your order history? This cannot be undone.'
                                        );
                                    "
                                >

                                    <input
                                        type="hidden"
                                        name="order_id"
                                        value="<?= (int)
                                            $order["id"] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="remove_order"
                                        value="1"
                                    >

                                    <button
                                        type="submit"
                                        class="remove-order-button"
                                    >

                                        <i
                                            class="
                                                fa-solid
                                                fa-trash
                                            "
                                        ></i>

                                        Remove Order

                                    </button>

                                </form>

                            </div>

                        <?php endif; ?>

                        <!-- =================================================
                             ITEMS
                        ================================================= -->

                        <div class="order-items-title">
                            Items
                        </div>

                        <?php

                        $item_stmt =
                            $conn->prepare("
                                SELECT
                                    product_name,
                                    quantity,
                                    price
                                FROM order_items
                                WHERE order_id = ?
                                ORDER BY id ASC
                            ");

                        ?>

                        <div class="order-items">

                            <?php if (
                                $item_stmt
                            ): ?>

                                <?php

                                $current_order_id =
                                    (int)
                                    $order["id"];

                                $item_stmt->bind_param(
                                    "i",
                                    $current_order_id
                                );

                                $item_stmt->execute();

                                $items =
                                    $item_stmt->get_result();

                                ?>

                                <?php if (
                                    $items->num_rows > 0
                                ): ?>

                                    <?php while (
                                        $item =
                                        $items->fetch_assoc()
                                    ): ?>

                                        <div
                                            class="order-item"
                                        >

                                            <strong>

                                                <?= e(
                                                    $item[
                                                        "product_name"
                                                    ]
                                                ) ?>

                                            </strong>

                                            <span>

                                                <?= (int)
                                                    $item[
                                                        "quantity"
                                                    ] ?>

                                                ×

                                                ₱<?= number_format(
                                                    (float)
                                                    $item[
                                                        "price"
                                                    ],
                                                    2
                                                ) ?>

                                            </span>

                                        </div>

                                    <?php endwhile; ?>

                                <?php else: ?>

                                    <div
                                        class="order-item"
                                    >

                                        <span>
                                            No item details found.
                                        </span>

                                    </div>

                                <?php endif; ?>

                                <?php
                                $item_stmt->close();
                                ?>

                            <?php else: ?>

                                <div
                                    class="order-item"
                                >

                                    <span>
                                        Unable to load order items.
                                    </span>

                                </div>

                            <?php endif; ?>

                        </div>

                        <!-- =================================================
                             TOTAL
                        ================================================= -->

                        <div class="order-total">

                            <span>
                                Order Total
                            </span>

                            <strong>

                                ₱<?= number_format(
                                    (float)
                                    $order["total_amount"],
                                    2
                                ) ?>

                            </strong>

                        </div>

                    </article>

                <?php endwhile; ?>

            </div>

        <?php endif; ?>

    </div>

</section>

</main>

<script>

/* =========================================================
   PENDING ORDER COUNTDOWN
========================================================= */

const SEVEN_DAYS =
    7 * 24 * 60 * 60;

const FIVE_DAYS =
    5 * 24 * 60 * 60;


/* =========================================================
   FORMAT COUNTDOWN
========================================================= */

function formatCountdown(seconds) {

    seconds =
        Math.max(
            0,
            Math.floor(seconds)
        );

    const days =
        Math.floor(
            seconds / 86400
        );

    const hours =
        Math.floor(
            (seconds % 86400) / 3600
        );

    const minutes =
        Math.floor(
            (seconds % 3600) / 60
        );

    const secs =
        seconds % 60;

    return (
        days +
        "d " +
        hours +
        "h " +
        minutes +
        "m " +
        secs +
        "s"
    );
}


/* =========================================================
   UPDATE COUNTDOWNS
========================================================= */

function updateOrderCountdowns() {

    const now =
        Math.floor(
            Date.now() / 1000
        );

    document
        .querySelectorAll(
            ".order-card[data-order-created]"
        )
        .forEach(function(card) {

            const warning =
                card.querySelector(
                    "[data-warning]"
                );

            if (!warning) {
                return;
            }

            const created =
                parseInt(
                    card.dataset.orderCreated,
                    10
                );

            if (!created) {
                return;
            }

            const elapsed =
                now - created;

            const remaining =
                Math.max(
                    0,
                    SEVEN_DAYS -
                    Math.max(
                        0,
                        elapsed
                    )
                );

            const countdownText =
                card.querySelector(
                    "[data-countdown-text]"
                );

            const title =
                card.querySelector(
                    "[data-warning-title]"
                );

            const text =
                card.querySelector(
                    "[data-warning-text]"
                );

            const countdown =
                card.querySelector(
                    "[data-countdown]"
                );

            const cancelButton =
                card.querySelector(
                    "[data-cancel-button]"
                );

            if (
                countdownText
            ) {

                countdownText.textContent =
                    formatCountdown(
                        remaining
                    );
            }

            /* =================================================
               FIRST 5 DAYS
            ================================================= */

            if (
                elapsed >= 0 &&
                elapsed < FIVE_DAYS
            ) {

                warning.classList.remove(
                    "locked"
                );

                if (title) {

                    title.textContent =
                        "Pending Order";
                }

                if (text) {

                    text.textContent =
                        "You may cancel this pending order during the first 5 days. Cancellation becomes locked during days 6–7.";
                }

                if (countdown) {

                    countdown.style.display =
                        "inline-flex";
                }

                if (
                    cancelButton &&
                    cancelButton.disabled
                ) {

                    window.location.reload();
                }

                return;
            }

            /* =================================================
               DAYS 6–7
            ================================================= */

            if (
                elapsed >= FIVE_DAYS &&
                elapsed < SEVEN_DAYS
            ) {

                warning.classList.add(
                    "locked"
                );

                if (title) {

                    title.textContent =
                        "Cancellation Locked";
                }

                if (text) {

                    text.textContent =
                        "Your order is now in the final processing period. Cancellation is no longer available during days 6–7.";
                }

                if (countdown) {

                    countdown.style.display =
                        "inline-flex";
                }

                if (
                    cancelButton &&
                    !cancelButton.disabled
                ) {

                    window.location.reload();
                }

                return;
            }

            /* =================================================
               AFTER 7 DAYS
            ================================================= */

            if (
                elapsed >= SEVEN_DAYS
            ) {

                warning.classList.add(
                    "locked"
                );

                if (title) {

                    title.textContent =
                        "Processing Period Complete";
                }

                if (text) {

                    text.textContent =
                        "The 7-day pending period has ended. Cancellation is no longer available.";
                }

                if (countdownText) {

                    countdownText.textContent =
                        "0d 0h 0m 0s";
                }

                if (cancelButton) {

                    cancelButton.disabled =
                        true;

                    cancelButton.classList.add(
                        "locked"
                    );

                    cancelButton.innerHTML =
                        '<i class="fa-solid fa-lock"></i> Cancellation Locked';
                }
            }

        });
}


/* =========================================================
   START COUNTDOWN
========================================================= */

updateOrderCountdowns();

setInterval(
    updateOrderCountdowns,
    1000
);

</script>

</body>

</html>

<?php

$order_stmt->close();

?>
```
