<?php

session_start();

require_once "../php/database.php";


/* =========================================================
   ADMIN ACCESS CHECK
========================================================= */

if (
    empty($_SESSION["logged_in"]) ||
    empty($_SESSION["user_id"]) ||
    empty($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: login.php");
    exit;
}

/* =========================================================
   ESCAPE FUNCTION
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}


/* =========================================================
   ALLOWED ORDER STATUSES
========================================================= */

$allowed_statuses = [
    "Pending",
    "Processing",
    "Shipped",
    "Delivered",
    "Cancelled"
];


/* =========================================================
   UPDATE ORDER STATUS
========================================================= */

$message = "";
$message_type = "";


if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "update_status"
) {

    $order_id = (int) ($_POST["order_id"] ?? 0);

    $new_status = trim(
        $_POST["status"] ?? ""
    );


    if ($order_id <= 0) {

        $message = "Invalid order.";
        $message_type = "error";

    } elseif (!in_array($new_status, $allowed_statuses, true)) {

        $message = "Invalid order status.";
        $message_type = "error";

    } else {

        $stmt = $conn->prepare("
            UPDATE orders
            SET status = ?
            WHERE id = ?
            LIMIT 1
        ");


        if (!$stmt) {

            $message =
                "Unable to prepare the order update.";

            $message_type = "error";

        } else {

            $stmt->bind_param(
                "si",
                $new_status,
                $order_id
            );


            if ($stmt->execute()) {

                if ($stmt->affected_rows > 0) {

                    $message =
                        "Order #" .
                        $order_id .
                        " status updated to " .
                        $new_status .
                        ".";

                    $message_type = "success";

                } else {

                    $message =
                        "No order was changed. The order may already have that status.";

                    $message_type = "error";
                }

            } else {

                $message =
                    "Unable to update the order status.";

                $message_type = "error";
            }


            $stmt->close();
        }
    }
}


/* =========================================================
   LOAD ORDERS
========================================================= */

$orders = [];


$sql = "
    SELECT
        o.id,
        o.user_id,
        o.total_amount,
        o.status,
        o.shipping_name,
        o.shipping_email,
        o.shipping_phone,
        o.shipping_address,
        o.notes,
        o.created_at,
        o.updated_at,
        u.full_name AS customer_name,
        u.email AS customer_email
    FROM orders o
    INNER JOIN users u
        ON u.id = o.user_id
    ORDER BY o.created_at DESC, o.id DESC
";


$result = $conn->query($sql);


if ($result) {

    while ($row = $result->fetch_assoc()) {

        $orders[] = $row;
    }

    $result->free();
}


/* =========================================================
   LOAD ORDER ITEMS
========================================================= */

$order_items = [];


if (!empty($orders)) {

    $order_ids = [];

    foreach ($orders as $order) {

        $order_ids[] = (int) $order["id"];
    }


    /*
       Build a safe integer-only IN() list.
    */

    $order_id_list = implode(
        ",",
        array_map(
            "intval",
            $order_ids
        )
    );


    $items_sql = "
        SELECT
            oi.id,
            oi.order_id,
            oi.product_id,
            oi.product_name,
            oi.price,
            oi.quantity,
            oi.subtotal
        FROM order_items oi
        WHERE oi.order_id IN ($order_id_list)
        ORDER BY oi.order_id DESC, oi.id ASC
    ";


    $items_result = $conn->query($items_sql);


    if ($items_result) {

        while ($item = $items_result->fetch_assoc()) {

            $order_id = (int) $item["order_id"];

            if (!isset($order_items[$order_id])) {

                $order_items[$order_id] = [];
            }


            $order_items[$order_id][] = $item;
        }


        $items_result->free();
    }
}


/* =========================================================
   ORDER STATISTICS
========================================================= */

$total_orders = count($orders);

$pending_orders = 0;

$processing_orders = 0;

$shipped_orders = 0;

$delivered_orders = 0;

$cancelled_orders = 0;

$total_revenue = 0.00;


foreach ($orders as $order) {

    $status = $order["status"];


    switch ($status) {

        case "Pending":

            $pending_orders++;

            break;


        case "Processing":

            $processing_orders++;

            break;


        case "Shipped":

            $shipped_orders++;

            break;


        case "Delivered":

            $delivered_orders++;

            break;


        case "Cancelled":

            $cancelled_orders++;

            break;
    }


    if ($status !== "Cancelled") {

        $total_revenue +=
            (float) $order["total_amount"];
    }
}


/* =========================================================
   ADMIN NAME
========================================================= */

$admin_name =
    $_SESSION["full_name"] ?? "Administrator";

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
        Orders | Siquijor Styles Admin
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }


        body {

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f5f1eb;

            color: #2f2a25;
        }


        /* =================================================
           LAYOUT
        ================================================= */

        .admin-layout {

            min-height: 100vh;

            display: flex;
        }


        /* =================================================
           SIDEBAR
        ================================================= */

        .sidebar {

            width: 250px;

            flex-shrink: 0;

            background: #2f2a25;

            color: #ffffff;

            padding: 26px 18px;

            display: flex;

            flex-direction: column;

            min-height: 100vh;
        }


        .sidebar-logo {

            display: flex;

            justify-content: center;

            align-items: center;

            padding-bottom: 25px;

            margin-bottom: 20px;

            border-bottom: 1px solid
                rgba(255,255,255,0.12);
        }


        .sidebar-logo img {

            width: auto;

            max-width: 180px;

            height: 55px;

            object-fit: contain;
        }


        .admin-badge {

            padding: 10px 12px;

            border-radius: 10px;

            background:
                rgba(255,255,255,0.08);

            margin-bottom: 22px;

            text-align: center;
        }


        .admin-badge strong {

            display: block;

            font-size: 14px;

            margin-bottom: 4px;
        }


        .admin-badge span {

            font-size: 11px;

            opacity: 0.65;

            text-transform: uppercase;

            letter-spacing: 0.7px;
        }


        .nav-title {

            margin: 0 10px 10px;

            font-size: 10px;

            text-transform: uppercase;

            letter-spacing: 1px;

            opacity: 0.5;
        }


        .admin-nav {

            display: flex;

            flex-direction: column;

            gap: 6px;
        }


        .admin-nav a {

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 12px 13px;

            border-radius: 9px;

            color: #ffffff;

            text-decoration: none;

            font-size: 14px;

            transition:
                background 0.2s ease,
                transform 0.2s ease;
        }


        .admin-nav a:hover {

            background:
                rgba(255,255,255,0.10);

            transform: translateX(2px);
        }


        .admin-nav a.active {

            background:
                rgba(255,255,255,0.14);

            font-weight: 700;
        }


        .nav-icon {

            width: 22px;

            text-align: center;

            font-size: 17px;
        }


        .sidebar-bottom {

            margin-top: auto;

            padding-top: 20px;
        }


        .bottom-link {

            display: block;

            text-align: center;

            padding: 11px;

            border: 1px solid
                rgba(255,255,255,0.20);

            border-radius: 9px;

            color: #ffffff;

            text-decoration: none;

            font-size: 13px;
        }


        .bottom-link:hover {

            background:
                rgba(255,255,255,0.08);
        }


        .bottom-link + .bottom-link {

            margin-top: 8px;
        }


        /* =================================================
           MAIN
        ================================================= */

        .main-content {

            flex: 1;

            min-width: 0;

            padding: 30px;
        }


        .topbar {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;

            margin-bottom: 24px;
        }


        .topbar h1 {

            margin: 0 0 7px;

            font-size: 30px;
        }


        .topbar p {

            margin: 0;

            color: #7b7168;

            font-size: 14px;
        }


        .view-site {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 11px 15px;

            border-radius: 9px;

            background: #ffffff;

            border: 1px solid #ded5cc;

            color: #51483f;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;
        }


        .view-site:hover {

            background: #faf7f3;
        }


        /* =================================================
           SUMMARY CARDS
        ================================================= */

        .summary-grid {

            display: grid;

            grid-template-columns:
                repeat(6, minmax(0, 1fr));

            gap: 12px;

            margin-bottom: 24px;
        }


        .summary-card {

            background: #ffffff;

            border: 1px solid #e5ddd5;

            border-radius: 13px;

            padding: 17px;

            box-shadow:
                0 5px 18px
                rgba(47,42,37,0.04);
        }


        .summary-label {

            color: #837971;

            font-size: 11px;

            margin-bottom: 8px;
        }


        .summary-value {

            font-size: 24px;

            font-weight: 700;
        }


        /* =================================================
           MESSAGES
        ================================================= */

        .message {

            margin-bottom: 20px;

            padding: 14px 15px;

            border-radius: 10px;

            font-size: 13px;

            line-height: 1.5;
        }


        .success-message {

            background: #eaf7ed;

            border: 1px solid #acd3b3;

            color: #367246;
        }


        .error-message {

            background: #fff0f0;

            border: 1px solid #e1adad;

            color: #9e3e3e;
        }


        /* =================================================
           ORDERS LIST
        ================================================= */

        .orders-list {

            display: flex;

            flex-direction: column;

            gap: 18px;
        }


        .order-card {

            background: #ffffff;

            border: 1px solid #e5ddd5;

            border-radius: 15px;

            overflow: hidden;

            box-shadow:
                0 5px 18px
                rgba(47,42,37,0.04);
        }


        .order-header {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;

            padding: 20px 22px;

            background: #faf8f5;

            border-bottom: 1px solid #eee7e0;
        }


        .order-number {

            font-size: 18px;

            font-weight: 700;

            margin-bottom: 6px;
        }


        .order-date {

            color: #8b8078;

            font-size: 12px;
        }


        .order-customer {

            margin-top: 8px;

            color: #655b53;

            font-size: 13px;
        }


        .order-customer strong {

            color: #3e3731;
        }


        /* =================================================
           STATUS
        ================================================= */

        .status {

            display: inline-block;

            padding: 7px 11px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 700;

            white-space: nowrap;
        }


        .status-pending {

            background: #fff3d9;

            color: #946b18;
        }


        .status-processing {

            background: #e8f1ff;

            color: #3f639c;
        }


        .status-shipped {

            background: #eee8ff;

            color: #69529a;
        }


        .status-delivered {

            background: #e5f7ea;

            color: #3e7b4c;
        }


        .status-cancelled {

            background: #ffe8e8;

            color: #a54c4c;
        }


        /* =================================================
           ORDER BODY
        ================================================= */

        .order-body {

            padding: 20px 22px;
        }


        .order-grid {

            display: grid;

            grid-template-columns:
                minmax(0, 1.5fr)
                minmax(280px, 1fr);

            gap: 24px;
        }


        /* =================================================
           ITEMS
        ================================================= */

        .section-title {

            margin: 0 0 12px;

            font-size: 14px;

            font-weight: 700;

            color: #4c443d;
        }


        .item-list {

            display: flex;

            flex-direction: column;

            gap: 9px;
        }


        .item {

            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                auto
                auto;

            gap: 14px;

            align-items: center;

            padding: 11px 12px;

            border-radius: 9px;

            background: #faf8f5;

            border: 1px solid #eee7e0;
        }


        .item-name {

            font-size: 13px;

            font-weight: 700;
        }


        .item-details {

            margin-top: 4px;

            color: #91867e;

            font-size: 11px;
        }


        .item-quantity {

            color: #6d625a;

            font-size: 12px;

            white-space: nowrap;
        }


        .item-subtotal {

            font-size: 13px;

            font-weight: 700;

            white-space: nowrap;
        }


        .order-total {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-top: 15px;

            padding-top: 15px;

            border-top: 1px solid #eee7e0;
        }


        .order-total-label {

            font-size: 13px;

            font-weight: 700;
        }


        .order-total-value {

            font-size: 20px;

            font-weight: 700;
        }


        /* =================================================
           CUSTOMER / SHIPPING
        ================================================= */

        .info-box {

            padding: 15px;

            border-radius: 10px;

            background: #faf8f5;

            border: 1px solid #eee7e0;

            margin-bottom: 12px;
        }


        .info-label {

            margin-bottom: 5px;

            color: #938980;

            font-size: 10px;

            text-transform: uppercase;

            letter-spacing: 0.6px;
        }


        .info-value {

            color: #4c443d;

            font-size: 13px;

            line-height: 1.55;

            white-space: pre-line;
        }


        .info-value a {

            color: #765f49;

            text-decoration: none;
        }


        .info-value a:hover {

            text-decoration: underline;
        }


        /* =================================================
           STATUS FORM
        ================================================= */

        .status-form {

            display: flex;

            align-items: center;

            gap: 10px;

            margin-top: 12px;

            padding-top: 15px;

            border-top: 1px solid #eee7e0;
        }


        .status-form select {

            flex: 1;

            min-width: 0;

            height: 42px;

            padding: 0 11px;

            border: 1px solid #d8d0c7;

            border-radius: 8px;

            background: #ffffff;

            color: #3d362f;

            font-family: inherit;

            font-size: 12px;

            outline: none;
        }


        .status-form select:focus {

            border-color: #9d7958;

            box-shadow:
                0 0 0 3px
                rgba(157,121,88,0.10);
        }


        .status-button {

            height: 42px;

            padding: 0 15px;

            border: none;

            border-radius: 8px;

            background: #2f2a25;

            color: #ffffff;

            font-family: inherit;

            font-size: 12px;

            font-weight: 700;

            cursor: pointer;
        }


        .status-button:hover {

            background: #4b4138;
        }


        /* =================================================
           EMPTY
        ================================================= */

        .empty-state {

            padding: 60px 25px;

            text-align: center;

            background: #ffffff;

            border: 1px solid #e5ddd5;

            border-radius: 15px;

            color: #8f857c;

            font-size: 13px;
        }


        .empty-state strong {

            display: block;

            margin-bottom: 7px;

            color: #4d443d;

            font-size: 18px;
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 1200px) {

            .summary-grid {

                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }

        }


        @media (max-width: 1000px) {

            .order-grid {

                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 800px) {

            .admin-layout {

                display: block;
            }


            .sidebar {

                width: 100%;

                min-height: auto;

                padding: 18px;
            }


            .sidebar-logo {

                justify-content: flex-start;

                margin-bottom: 15px;

                padding-bottom: 15px;
            }


            .admin-nav {

                display: grid;

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }


            .sidebar-bottom {

                margin-top: 15px;
            }


            .main-content {

                padding: 20px;
            }

        }


        @media (max-width: 600px) {

            .main-content {

                padding: 15px;
            }


            .topbar {

                flex-direction: column;
            }


            .topbar h1 {

                font-size: 25px;
            }


            .summary-grid {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }


            .admin-nav {

                grid-template-columns: 1fr;
            }


            .order-header {

                flex-direction: column;
            }


            .item {

                grid-template-columns: 1fr;
            }


            .status-form {

                flex-direction: column;

                align-items: stretch;
            }


            .status-form select,
            .status-button {

                width: 100%;
            }

        }

    </style>

</head>


<body>


<div class="admin-layout">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar">


        <div class="sidebar-logo">

            <img
                src="../images/logo.png"
                alt="Siquijor Styles"
            >

        </div>


        <div class="admin-badge">

            <strong>
                <?= e($admin_name) ?>
            </strong>

            <span>
                Administrator
            </span>

        </div>


        <div class="nav-title">
            Management
        </div>


        <nav class="admin-nav">


            <a href="index.php">

                <span class="nav-icon">⌂</span>

                Dashboard

            </a>


            <a href="products.php">

                <span class="nav-icon">▣</span>

                Products

            </a>


            <a href="users.php">

                <span class="nav-icon">♙</span>

                Customers

            </a>


            <a
                href="orders.php"
                class="active"
            >

                <span class="nav-icon">▤</span>

                Orders

            </a>


        </nav>


        <div class="sidebar-bottom">


            <a
                href="../index.php"
                class="bottom-link"
            >
                View Website
            </a>


            <a
                href="logout.php"
                class="bottom-link"
            >
                Log Out
            </a>


        </div>


    </aside>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">


        <!-- TOPBAR -->

        <div class="topbar">


            <div>

                <h1>
                    Orders
                </h1>

                <p>
                    Review customer purchases and manage order status.
                </p>

            </div>


            <a
                href="../index.php"
                class="view-site"
            >
                View Store →
            </a>


        </div>


        <!-- =================================================
             MESSAGE
        ================================================== -->

        <?php if ($message !== ""): ?>

            <div
                class="message <?= $message_type === "success"
                    ? "success-message"
                    : "error-message" ?>"
            >

                <?= e($message) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             SUMMARY
        ================================================== -->

        <section class="summary-grid">


            <div class="summary-card">

                <div class="summary-label">
                    Total Orders
                </div>

                <div class="summary-value">
                    <?= number_format($total_orders) ?>
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Pending
                </div>

                <div class="summary-value">
                    <?= number_format($pending_orders) ?>
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Processing
                </div>

                <div class="summary-value">
                    <?= number_format($processing_orders) ?>
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Shipped
                </div>

                <div class="summary-value">
                    <?= number_format($shipped_orders) ?>
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Delivered
                </div>

                <div class="summary-value">
                    <?= number_format($delivered_orders) ?>
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Revenue
                </div>

                <div class="summary-value">
                    ₱<?= number_format($total_revenue, 2) ?>
                </div>

            </div>


        </section>


        <!-- =================================================
             ORDERS
        ================================================== -->

        <?php if (!empty($orders)): ?>


            <div class="orders-list">


                <?php foreach ($orders as $order): ?>


                    <?php

                    $order_id =
                        (int) $order["id"];

                    $status =
                        $order["status"];


                    $status_class = match ($status) {

                        "Pending" =>
                            "status-pending",

                        "Processing" =>
                            "status-processing",

                        "Shipped" =>
                            "status-shipped",

                        "Delivered" =>
                            "status-delivered",

                        "Cancelled" =>
                            "status-cancelled",

                        default =>
                            ""
                    };


                    ?>


                    <article class="order-card">


                        <!-- =================================
                             ORDER HEADER
                        ================================== -->

                        <div class="order-header">


                            <div>


                                <div class="order-number">

                                    Order #<?= $order_id ?>

                                </div>


                                <div class="order-date">

                                    Placed:

                                    <?= e(
                                        date(
                                            "F d, Y • h:i A",
                                            strtotime(
                                                $order["created_at"]
                                            )
                                        )
                                    ) ?>

                                </div>


                                <div class="order-customer">

                                    Customer:

                                    <strong>
                                        <?= e(
                                            $order["customer_name"]
                                        ) ?>
                                    </strong>

                                    —
                                    <?= e(
                                        $order["customer_email"]
                                    ) ?>

                                </div>


                            </div>


                            <div>

                                <span
                                    class="status <?= e($status_class) ?>"
                                >
                                    <?= e($status) ?>
                                </span>

                            </div>


                        </div>


                        <!-- =================================
                             ORDER BODY
                        ================================== -->

                        <div class="order-body">


                            <div class="order-grid">


                                <!-- ITEMS -->

                                <div>


                                    <h3 class="section-title">
                                        Ordered Items
                                    </h3>


                                    <?php if (
                                        !empty(
                                            $order_items[$order_id]
                                        )
                                    ): ?>


                                        <div class="item-list">


                                            <?php foreach (
                                                $order_items[$order_id]
                                                as $item
                                            ): ?>


                                                <div class="item">


                                                    <div>

                                                        <div class="item-name">

                                                            <?= e(
                                                                $item["product_name"]
                                                            ) ?>

                                                        </div>


                                                        <div class="item-details">

                                                            ₱<?= number_format(
                                                                (float) $item["price"],
                                                                2
                                                            ) ?>

                                                            each

                                                        </div>

                                                    </div>


                                                    <div class="item-quantity">

                                                        ×
                                                        <?= number_format(
                                                            (int) $item["quantity"]
                                                        ) ?>

                                                    </div>


                                                    <div class="item-subtotal">

                                                        ₱<?= number_format(
                                                            (float) $item["subtotal"],
                                                            2
                                                        ) ?>

                                                    </div>


                                                </div>


                                            <?php endforeach; ?>


                                        </div>


                                    <?php else: ?>


                                        <div class="item">

                                            <div class="item-name">
                                                No order items found.
                                            </div>

                                        </div>


                                    <?php endif; ?>


                                    <div class="order-total">


                                        <span class="order-total-label">
                                            Order Total
                                        </span>


                                        <span class="order-total-value">

                                            ₱<?= number_format(
                                                (float) $order["total_amount"],
                                                2
                                            ) ?>

                                        </span>


                                    </div>


                                    <!-- STATUS UPDATE -->

                                    <form
                                        method="POST"
                                        class="status-form"
                                    >


                                        <input
                                            type="hidden"
                                            name="action"
                                            value="update_status"
                                        >


                                        <input
                                            type="hidden"
                                            name="order_id"
                                            value="<?= $order_id ?>"
                                        >


                                        <select
                                            name="status"
                                            aria-label="Update order status"
                                        >


                                            <?php foreach (
                                                $allowed_statuses
                                                as $allowed_status
                                            ): ?>

                                                <option
                                                    value="<?= e($allowed_status) ?>"
                                                    <?= $status === $allowed_status
                                                        ? "selected"
                                                        : "" ?>
                                                >

                                                    <?= e($allowed_status) ?>

                                                </option>

                                            <?php endforeach; ?>


                                        </select>


                                        <button
                                            type="submit"
                                            class="status-button"
                                        >
                                            Update Status
                                        </button>


                                    </form>


                                </div>


                                <!-- CUSTOMER / SHIPPING -->

                                <div>


                                    <h3 class="section-title">
                                        Customer & Shipping
                                    </h3>


                                    <div class="info-box">


                                        <div class="info-label">
                                            Customer
                                        </div>


                                        <div class="info-value">

                                            <?= e(
                                                $order["customer_name"]
                                            ) ?>


                                            <br>


                                            <a
                                                href="mailto:<?= e(
                                                    $order["customer_email"]
                                                ) ?>"
                                            >

                                                <?= e(
                                                    $order["customer_email"]
                                                ) ?>

                                            </a>


                                        </div>


                                    </div>


                                    <?php if (
                                        !empty(
                                            $order["shipping_name"]
                                        )
                                    ): ?>


                                        <div class="info-box">


                                            <div class="info-label">
                                                Shipping Name
                                            </div>


                                            <div class="info-value">

                                                <?= e(
                                                    $order["shipping_name"]
                                                ) ?>

                                            </div>


                                        </div>


                                    <?php endif; ?>


                                    <?php if (
                                        !empty(
                                            $order["shipping_phone"]
                                        )
                                    ): ?>


                                        <div class="info-box">


                                            <div class="info-label">
                                                Phone
                                            </div>


                                            <div class="info-value">

                                                <?= e(
                                                    $order["shipping_phone"]
                                                ) ?>

                                            </div>


                                        </div>


                                    <?php endif; ?>


                                    <?php if (
                                        !empty(
                                            $order["shipping_address"]
                                        )
                                    ): ?>


                                        <div class="info-box">


                                            <div class="info-label">
                                                Shipping Address
                                            </div>


                                            <div class="info-value">

                                                <?= e(
                                                    $order["shipping_address"]
                                                ) ?>

                                            </div>


                                        </div>


                                    <?php endif; ?>


                                    <?php if (
                                        !empty(
                                            $order["notes"]
                                        )
                                    ): ?>


                                        <div class="info-box">


                                            <div class="info-label">
                                                Customer Notes
                                            </div>


                                            <div class="info-value">

                                                <?= e(
                                                    $order["notes"]
                                                ) ?>

                                            </div>


                                        </div>


                                    <?php endif; ?>


                                </div>


                            </div>


                        </div>


                    </article>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <div class="empty-state">


                <strong>
                    No Orders Yet
                </strong>


                Customer purchases will appear here
                after an order is successfully placed.


            </div>


        <?php endif; ?>


    </main>


</div>


</body>

</html>