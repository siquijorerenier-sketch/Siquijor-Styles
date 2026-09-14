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
   LOAD CUSTOMERS
========================================================= */

$customers = [];

$sql = "
    SELECT
        u.id,
        u.full_name,
        u.email,
        u.created_at,
        COUNT(DISTINCT o.id) AS total_orders,
        COALESCE(
            SUM(
                CASE
                    WHEN o.status <> 'Cancelled'
                    THEN o.total_amount
                    ELSE 0
                END
            ),
            0
        ) AS total_spent
    FROM users u
    LEFT JOIN orders o
        ON o.user_id = u.id
    WHERE u.role = 'customer'
    GROUP BY
        u.id,
        u.full_name,
        u.email,
        u.created_at
    ORDER BY u.id DESC
";

$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $customers[] = $row;
    }

    $result->free();
}


/* =========================================================
   CUSTOMER STATISTICS
========================================================= */

$total_customers = count($customers);

$total_customer_orders = 0;

$total_customer_spending = 0.00;


foreach ($customers as $customer) {

    $total_customer_orders += (int) $customer["total_orders"];

    $total_customer_spending += (float) $customer["total_spent"];
}


/* =========================================================
   ADMIN NAME
========================================================= */

$admin_name = $_SESSION["full_name"] ?? "Administrator";

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
        Customers | Siquijor Styles Admin
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

            align-items: flex-start;

            gap: 20px;

            margin-bottom: 25px;
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
           SUMMARY
        ================================================= */

        .summary-grid {

            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 16px;

            margin-bottom: 24px;
        }


        .summary-card {

            background: #ffffff;

            border: 1px solid #e5ddd5;

            border-radius: 14px;

            padding: 20px;

            box-shadow:
                0 5px 18px
                rgba(47,42,37,0.04);
        }


        .summary-label {

            margin-bottom: 8px;

            color: #837971;

            font-size: 12px;
        }


        .summary-value {

            font-size: 28px;

            font-weight: 700;
        }


        /* =================================================
           PANEL
        ================================================= */

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


        .panel-header span {

            color: #8b8078;

            font-size: 12px;
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

            min-width: 850px;

            border-collapse: collapse;
        }


        th {

            padding: 13px 17px;

            text-align: left;

            font-size: 11px;

            text-transform: uppercase;

            letter-spacing: 0.5px;

            color: #877d74;

            background: #faf8f5;

            border-bottom: 1px solid #eee7e0;
        }


        td {

            padding: 15px 17px;

            font-size: 13px;

            border-bottom: 1px solid #f0ebe5;

            vertical-align: middle;
        }


        tbody tr:last-child td {

            border-bottom: none;
        }


        .customer-id {

            color: #938980;

            font-size: 11px;
        }


        .customer-name {

            font-weight: 700;

            margin-bottom: 4px;
        }


        .customer-email {

            color: #8f857c;

            font-size: 12px;
        }


        .customer-date {

            color: #6f655d;

            font-size: 12px;

            white-space: nowrap;
        }


        .orders-count {

            font-weight: 700;
        }


        .spent {

            font-weight: 700;

            white-space: nowrap;
        }


        /* =================================================
           EMPTY STATE
        ================================================= */

        .empty-state {

            padding: 55px 25px;

            text-align: center;

            color: #8f857c;

            font-size: 13px;
        }


        .empty-state strong {

            display: block;

            margin-bottom: 7px;

            color: #4d443d;

            font-size: 17px;
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 1000px) {

            .summary-grid {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
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

                grid-template-columns: 1fr;
            }


            .admin-nav {

                grid-template-columns: 1fr;
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


        <div class="topbar">


            <div>

                <h1>
                    Customers
                </h1>

                <p>
                    View registered customer accounts and their order activity.
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
             SUMMARY CARDS
        ================================================== -->

        <section class="summary-grid">


            <div class="summary-card">

                <div class="summary-label">
                    Total Customers
                </div>

                <div class="summary-value">
                    <?= number_format($total_customers) ?>
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Total Customer Orders
                </div>

                <div class="summary-value">
                    <?= number_format($total_customer_orders) ?>
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Customer Spending
                </div>

                <div class="summary-value">
                    ₱<?= number_format($total_customer_spending, 2) ?>
                </div>

            </div>


        </section>


        <!-- =================================================
             CUSTOMER TABLE
        ================================================== -->

        <section class="panel">


            <div class="panel-header">

                <h2>
                    Registered Customers
                </h2>


                <span>
                    <?= number_format($total_customers) ?>
                    customers
                </span>

            </div>


            <?php if (!empty($customers)): ?>


                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Customer
                                </th>

                                <th>
                                    Joined
                                </th>

                                <th>
                                    Orders
                                </th>

                                <th>
                                    Total Spent
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach ($customers as $customer): ?>


                                <tr>


                                    <!-- CUSTOMER -->

                                    <td>

                                        <div class="customer-name">

                                            <?= e(
                                                $customer["full_name"]
                                            ) ?>

                                        </div>


                                        <div class="customer-email">

                                            <?= e(
                                                $customer["email"]
                                            ) ?>

                                        </div>


                                        <div class="customer-id">

                                            Customer ID:
                                            #<?= (int) $customer["id"] ?>

                                        </div>

                                    </td>


                                    <!-- JOINED -->

                                    <td>

                                        <div class="customer-date">

                                            <?= e(
                                                date(
                                                    "M d, Y",
                                                    strtotime(
                                                        $customer["created_at"]
                                                    )
                                                )
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- ORDERS -->

                                    <td>

                                        <div class="orders-count">

                                            <?= number_format(
                                                (int) $customer["total_orders"]
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- TOTAL SPENT -->

                                    <td class="spent">

                                        ₱<?= number_format(
                                            (float) $customer["total_spent"],
                                            2
                                        ) ?>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <div class="empty-state">

                    <strong>
                        No customers yet
                    </strong>

                    No customer accounts have been registered.

                </div>


            <?php endif; ?>


        </section>


    </main>


</div>


</body>

</html>