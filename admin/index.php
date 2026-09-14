<?php

require_once __DIR__ . "/../php/session.php";

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
   ADMIN INFORMATION
========================================================= */

$admin_name = $_SESSION["full_name"] ?? "Administrator";


/* =========================================================
   DASHBOARD STATISTICS
========================================================= */

$total_products = 0;
$total_users = 0;
$total_orders = 0;
$total_revenue = 0.00;


/* ---------------------------------------------------------
   TOTAL PRODUCTS
--------------------------------------------------------- */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM products
");

if ($result) {

    $row = $result->fetch_assoc();

    $total_products = (int) ($row["total"] ?? 0);

    $result->free();
}


/* ---------------------------------------------------------
   TOTAL USERS
--------------------------------------------------------- */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'customer'
");

if ($result) {

    $row = $result->fetch_assoc();

    $total_users = (int) ($row["total"] ?? 0);

    $result->free();
}


/* ---------------------------------------------------------
   TOTAL ORDERS
--------------------------------------------------------- */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
");

if ($result) {

    $row = $result->fetch_assoc();

    $total_orders = (int) ($row["total"] ?? 0);

    $result->free();
}


/* ---------------------------------------------------------
   TOTAL REVENUE
--------------------------------------------------------- */

$result = $conn->query("
    SELECT COALESCE(SUM(total_amount), 0) AS total
    FROM orders
    WHERE status <> 'Cancelled'
");

if ($result) {

    $row = $result->fetch_assoc();

    $total_revenue = (float) ($row["total"] ?? 0);

    $result->free();
}


/* =========================================================
   RECENT ORDERS
========================================================= */

$recent_orders = [];

$result = $conn->query("
    SELECT
        o.id,
        o.total_amount,
        o.status,
        o.created_at,
        u.full_name,
        u.email
    FROM orders o
    INNER JOIN users u
        ON u.id = o.user_id
    ORDER BY o.created_at DESC
    LIMIT 8
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $recent_orders[] = $row;

    }

    $result->free();
}


/* =========================================================
   LOW STOCK PRODUCTS
========================================================= */

$low_stock_products = [];

$result = $conn->query("
    SELECT
        id,
        product_name,
        price,
        stock
    FROM products
    ORDER BY stock ASC, product_name ASC
    LIMIT 8
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $low_stock_products[] = $row;

    }

    $result->free();
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
   STATUS CLASS
========================================================= */

function statusClass($status)
{
    return match ($status) {

        "Pending" => "status-pending",

        "Processing" => "status-processing",

        "Shipped" => "status-shipped",

        "Delivered" => "status-delivered",

        "Cancelled" => "status-cancelled",

        default => "status-default"
    };
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
        Admin Dashboard | Siquijor Styles
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


        .logout-link {

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


        .logout-link:hover {

            background:
                rgba(255,255,255,0.08);
        }


        /* =================================================
           MAIN CONTENT
        ================================================= */

        .main-content {

            flex: 1;

            min-width: 0;

            padding: 30px;
        }


        .topbar {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 28px;
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

            padding: 11px 16px;

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
           STAT CARDS
        ================================================= */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 18px;

            margin-bottom: 28px;
        }


        .stat-card {

            background: #ffffff;

            border: 1px solid #e5ddd5;

            border-radius: 15px;

            padding: 22px;

            box-shadow:
                0 5px 18px
                rgba(47,42,37,0.04);
        }


        .stat-label {

            color: #81766d;

            font-size: 13px;

            margin-bottom: 10px;
        }


        .stat-value {

            font-size: 30px;

            font-weight: 700;

            line-height: 1.1;
        }


        .stat-note {

            margin-top: 9px;

            color: #978c83;

            font-size: 12px;
        }


        /* =================================================
           TWO COLUMN SECTIONS
        ================================================= */

        .dashboard-grid {

            display: grid;

            grid-template-columns:
                minmax(0, 1.7fr)
                minmax(300px, 1fr);

            gap: 22px;

            margin-bottom: 22px;
        }


        .panel {

            background: #ffffff;

            border: 1px solid #e5ddd5;

            border-radius: 15px;

            overflow: hidden;

            box-shadow:
                0 5px 18px
                rgba(47,42,37,0.04);
        }


        .panel-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            padding: 20px 22px;

            border-bottom: 1px solid #eee7e0;
        }


        .panel-header h2 {

            margin: 0;

            font-size: 18px;
        }


        .panel-header a {

            color: #765f49;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;
        }


        .panel-body {

            padding: 0;
        }


        /* =================================================
           TABLE
        ================================================= */

        .table-wrapper {

            width: 100%;

            overflow-x: auto;
        }


        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 650px;
        }


        th {

            padding: 13px 18px;

            text-align: left;

            font-size: 11px;

            text-transform: uppercase;

            letter-spacing: 0.5px;

            color: #877d74;

            background: #faf8f5;

            border-bottom: 1px solid #eee7e0;
        }


        td {

            padding: 14px 18px;

            border-bottom: 1px solid #f0ebe5;

            font-size: 13px;

            vertical-align: middle;
        }


        tbody tr:last-child td {

            border-bottom: none;
        }


        .customer-name {

            font-weight: 700;

            margin-bottom: 3px;
        }


        .customer-email {

            font-size: 11px;

            color: #948a81;
        }


        .order-total {

            font-weight: 700;
        }


        .status {

            display: inline-block;

            padding: 6px 9px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 700;
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


        .status-default {

            background: #eeeeee;

            color: #666666;
        }


        .empty-state {

            padding: 35px 20px;

            text-align: center;

            color: #92877e;

            font-size: 13px;
        }


        /* =================================================
           LOW STOCK
        ================================================= */

        .stock-list {

            padding: 10px 0;
        }


        .stock-item {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            padding: 15px 22px;

            border-bottom: 1px solid #f0ebe5;
        }


        .stock-item:last-child {

            border-bottom: none;
        }


        .stock-name {

            font-weight: 700;

            font-size: 13px;

            margin-bottom: 5px;
        }


        .stock-price {

            color: #90867d;

            font-size: 11px;
        }


        .stock-number {

            min-width: 48px;

            text-align: center;

            padding: 7px 9px;

            border-radius: 8px;

            background: #fff0e7;

            color: #9a5532;

            font-size: 12px;

            font-weight: 700;
        }


        /* =================================================
           QUICK ACTIONS
        ================================================= */

        .quick-actions {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 12px;

            padding: 20px;
        }


        .action-card {

            display: block;

            padding: 18px;

            border-radius: 11px;

            border: 1px solid #e6ded6;

            background: #fcfaf8;

            color: #413a34;

            text-decoration: none;

            transition:
                transform 0.2s ease,
                border-color 0.2s ease,
                background 0.2s ease;
        }


        .action-card:hover {

            transform: translateY(-2px);

            background: #ffffff;

            border-color: #cdbba9;
        }


        .action-icon {

            font-size: 22px;

            margin-bottom: 10px;
        }


        .action-title {

            font-size: 13px;

            font-weight: 700;

            margin-bottom: 5px;
        }


        .action-text {

            font-size: 11px;

            color: #958a81;

            line-height: 1.45;
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 1100px) {

            .stats-grid {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .dashboard-grid {

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

            .stats-grid {

                grid-template-columns: 1fr;
            }


            .topbar {

                align-items: flex-start;

                flex-direction: column;
            }


            .quick-actions {

                grid-template-columns: 1fr;
            }


            .admin-nav {

                grid-template-columns: 1fr;
            }


            .main-content {

                padding: 15px;
            }


            .topbar h1 {

                font-size: 25px;
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
        <img src="../images/logo.png" alt="Siquijor Styles">
    </div>

    <div class="admin-badge">
        <strong><?= e($admin_name) ?></strong>
        <span>Administrator</span>
    </div>

    <div class="nav-title">
        Management
    </div>

    <nav class="admin-nav">

        <a href="http://localhost/Website/admin/index.php">
            <span class="nav-icon">⌂</span>
            Dashboard
        </a>

        <a href="http://localhost/Website/admin/products.php">
            <span class="nav-icon">▣</span>
            Products
        </a>

        <a href="http://localhost/Website/admin/users.php">
            <span class="nav-icon">♙</span>
            Customers
        </a>

        <a href="http://localhost/Website/admin/orders.php">
            <span class="nav-icon">▤</span>
            Orders
        </a>

    </nav>

    <div class="sidebar-bottom">

        <a
            href="http://localhost/Website/index.php"
            class="logout-link">
            View Website
        </a>

        <a
            href="http://localhost/Website/admin/logout.php"
            class="logout-link"
            style="margin-top: 8px;">
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
                    Admin Dashboard
                </h1>

                <p>
                    Welcome back,
                    <?= e($admin_name) ?>.
                    Here's an overview of your store.
                </p>

            </div>


            <a
                href="../index.php"
                class="view-site"
            >
                View Website →
            </a>

        </div>


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <section class="stats-grid">


            <div class="stat-card">

                <div class="stat-label">
                    Total Products
                </div>

                <div class="stat-value">
                    <?= number_format($total_products) ?>
                </div>

                <div class="stat-note">
                    Products in your catalog
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    Customers
                </div>

                <div class="stat-value">
                    <?= number_format($total_users) ?>
                </div>

                <div class="stat-note">
                    Registered customer accounts
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    Orders
                </div>

                <div class="stat-value">
                    <?= number_format($total_orders) ?>
                </div>

                <div class="stat-note">
                    All recorded orders
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    Revenue
                </div>

                <div class="stat-value">
                    ₱<?= number_format($total_revenue, 2) ?>
                </div>

                <div class="stat-note">
                    Excluding cancelled orders
                </div>

            </div>


        </section>


        <!-- =================================================
             RECENT ORDERS + LOW STOCK
        ================================================== -->

        <section class="dashboard-grid">


            <!-- RECENT ORDERS -->

            <div class="panel">

                <div class="panel-header">

                    <h2>
                        Recent Orders
                    </h2>

                    <a href="orders.php">
                        View All
                    </a>

                </div>


                <div class="panel-body">

                    <?php if (!empty($recent_orders)): ?>

                        <div class="table-wrapper">

                            <table>

                                <thead>

                                    <tr>

                                        <th>
                                            Customer
                                        </th>

                                        <th>
                                            Order
                                        </th>

                                        <th>
                                            Total
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php foreach ($recent_orders as $order): ?>

                                        <tr>

                                            <td>

                                                <div class="customer-name">
                                                    <?= e($order["full_name"]) ?>
                                                </div>

                                                <div class="customer-email">
                                                    <?= e($order["email"]) ?>
                                                </div>

                                            </td>


                                            <td>
                                                #<?= (int) $order["id"] ?>
                                            </td>


                                            <td class="order-total">

                                                ₱<?= number_format(
                                                    (float) $order["total_amount"],
                                                    2
                                                ) ?>

                                            </td>


                                            <td>

                                                <span
                                                    class="status <?= e(statusClass($order["status"])) ?>"
                                                >
                                                    <?= e($order["status"]) ?>
                                                </span>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php else: ?>

                        <div class="empty-state">
                            No orders have been placed yet.
                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- LOW STOCK -->

            <div class="panel">

                <div class="panel-header">

                    <h2>
                        Stock Overview
                    </h2>

                    <a href="products.php">
                        Manage
                    </a>

                </div>


                <div class="stock-list">

                    <?php if (!empty($low_stock_products)): ?>

                        <?php foreach ($low_stock_products as $product): ?>

                            <div class="stock-item">

                                <div>

                                    <div class="stock-name">
                                        <?= e($product["product_name"]) ?>
                                    </div>

                                    <div class="stock-price">

                                        ₱<?= number_format(
                                            (float) $product["price"],
                                            2
                                        ) ?>

                                    </div>

                                </div>


                                <div class="stock-number">

                                    <?= (int) $product["stock"] ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="empty-state">
                            No products found.
                        </div>

                    <?php endif; ?>

                </div>

            </div>


        </section>


        <!-- =================================================
             QUICK ACTIONS
        ================================================== -->

        <section class="panel">

            <div class="panel-header">

                <h2>
                    Quick Actions
                </h2>

            </div>


            <div class="quick-actions">


                <a
                    href="product_add.php"
                    class="action-card"
                >

                    <div class="action-icon">
                        ＋
                    </div>

                    <div class="action-title">
                        Add Product
                    </div>

                    <div class="action-text">
                        Add a new product to your store catalog.
                    </div>

                </a>


                <a
                    href="products.php"
                    class="action-card"
                >

                    <div class="action-icon">
                        ✎
                    </div>

                    <div class="action-title">
                        Manage Products
                    </div>

                    <div class="action-text">
                        Update prices, descriptions, stock, images, and categories.
                    </div>

                </a>


                <a
                    href="orders.php"
                    class="action-card"
                >

                    <div class="action-icon">
                        ✓
                    </div>

                    <div class="action-title">
                        Manage Orders
                    </div>

                    <div class="action-text">
                        Review purchases and update customer order status.
                    </div>

                </a>


            </div>

        </section>


    </main>

</div>

</body>

</html>