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
   GET USER INFORMATION
========================================================= */

$user_stmt = $conn->prepare(
    "SELECT full_name, email
     FROM users
     WHERE id = ?
     LIMIT 1"
);

if (!$user_stmt) {
    die("Unable to load user information.");
}

$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();

$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

$user_stmt->close();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

/* =========================================================
   GET ORDERS
========================================================= */

$order_stmt = $conn->prepare(
    "SELECT
        id,
        shipping_name,
        shipping_email,
        shipping_phone,
        shipping_address,
        total_amount,
        status
     FROM orders
     WHERE user_id = ?
     ORDER BY id DESC"
);

if (!$order_stmt) {
    die("Unable to load orders.");
}

$order_stmt->bind_param("i", $user_id);
$order_stmt->execute();

$orders_result = $order_stmt->get_result();

/* =========================================================
   ESCAPE HELPER
========================================================= */

function e($value)
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

    <title>My Orders | Siquijor Styles</title>


    <!-- GOOGLE FONTS -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
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


    <!-- CSS -->

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
            padding: 65px 0 90px;
            min-height: 700px;
        }

        .orders-heading {
            width: min(1180px, 90%);
            margin: 0 auto 40px;
            text-align: center;
        }

        .orders-heading .eyebrow {
            margin-bottom: 10px;
        }

        .orders-heading h1 {
            margin: 0;
            font-family: "Playfair Display", serif;
            font-size: clamp(36px, 4vw, 52px);
            font-weight: 500;
            font-style: italic;
            color: var(--teal);
        }

        .orders-heading p:last-child {
            margin: 12px auto 0;
            color: #637a7d;
            font-size: 13px;
        }

        .orders-container {
            width: min(1000px, 90%);
            margin: 0 auto;
        }

        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .order-card {
            background: #ffffff;
            border: 1px solid #e5ddd2;
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 8px 25px rgba(34, 72, 82, 0.07);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding-bottom: 18px;
            margin-bottom: 20px;
            border-bottom: 1px solid #e6dfd4;
        }

        .order-header h2 {
            margin: 0;
            font-family: "Playfair Display", serif;
            color: var(--teal);
            font-size: 24px;
        }

        .order-status {
            display: inline-flex;
            align-items: center;
            padding: 7px 14px;
            border-radius: 20px;
            background: #eef5f3;
            color: var(--teal);
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 30px;
            margin-bottom: 20px;
        }

        .order-detail {
            color: #637579;
            font-size: 12px;
            line-height: 1.6;
        }

        .order-detail strong {
            color: #294f59;
        }

        .order-items-title {
            margin: 20px 0 12px;
            color: var(--teal);
            font-size: 14px;
            font-weight: 600;
        }

        .order-items {
            display: flex;
            flex-direction: column;
            gap: 8px;
            border-top: 1px solid #ece5dc;
            border-bottom: 1px solid #ece5dc;
            padding: 12px 0;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 8px 0;
            color: #5f7479;
            font-size: 12px;
        }

        .order-item strong {
            color: var(--teal);
            font-weight: 600;
        }

        .order-item span {
            white-space: nowrap;
        }

        .order-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 18px;
        }

        .order-total span {
            color: #526a70;
            font-size: 13px;
            font-weight: 500;
        }

        .order-total strong {
            color: var(--teal);
            font-size: 21px;
        }

        .orders-empty {
            background: #ffffff;
            border: 1px solid #e5ddd2;
            border-radius: 18px;
            padding: 65px 30px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(34, 72, 82, 0.07);
        }

        .orders-empty-icon {
            color: var(--teal);
            font-size: 44px;
            margin-bottom: 18px;
        }

        .orders-empty h2 {
            margin-bottom: 10px;
            color: var(--teal);
            font-family: "Playfair Display", serif;
        }

        .orders-empty p {
            margin-bottom: 25px;
            color: #6c7c80;
            font-size: 12px;
        }

        @media (max-width: 700px) {

            .orders-page {
                padding: 45px 0 70px;
            }

            .order-card {
                padding: 20px;
            }

            .order-details {
                grid-template-columns: 1fr;
            }

            .order-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .order-item {
                align-items: flex-start;
                flex-direction: column;
                gap: 4px;
            }

            .order-total strong {
                font-size: 19px;
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

        <a
            href="cart.php"
            class="header-icon cart-button"
            aria-label="Cart"
        >

            <i class="fa-solid fa-bag-shopping"></i>

        </a>


        <a
            href="logout.php"
            class="login-button"
        >
            LOGOUT
        </a>

    </div>

</header>



<!-- =========================================================
     ORDERS PAGE
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


        <?php if ($orders_result->num_rows === 0): ?>


            <!-- EMPTY ORDERS -->

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


            <!-- ORDERS -->

            <div class="orders-list">


                <?php while (
                    $order = $orders_result->fetch_assoc()
                ): ?>


                    <article class="order-card">


                        <!-- ORDER HEADER -->

                        <div class="order-header">

                            <h2>
                                Order #<?= (int) $order["id"] ?>
                            </h2>

                            <span class="order-status">
                                <?= e($order["status"]) ?>
                            </span>

                        </div>



                        <!-- CUSTOMER DETAILS -->

                        <div class="order-details">


                            <div class="order-detail">

                                <strong>
                                    Customer:
                                </strong>

                                <?= e($order["shipping_name"]) ?>

                            </div>


                            <div class="order-detail">

                                <strong>
                                    Email:
                                </strong>

                                <?= e($order["shipping_email"]) ?>

                            </div>


                            <div class="order-detail">

                                <strong>
                                    Phone:
                                </strong>

                                <?= e($order["shipping_phone"]) ?>

                            </div>


                            <div class="order-detail">

                                <strong>
                                    Delivery Address:
                                </strong>

                                <?= nl2br(
                                    e($order["shipping_address"])
                                ) ?>

                            </div>


                        </div>



                        <!-- ITEMS TITLE -->

                        <div class="order-items-title">

                            Items

                        </div>



                        <?php

                        /*
                         * Use product_name stored in order_items.
                         *
                         * This is safer for order history because
                         * the product may be deleted later.
                         */

                        $item_stmt = $conn->prepare(
                            "SELECT
                                product_name,
                                quantity,
                                price
                             FROM order_items
                             WHERE order_id = ?
                             ORDER BY id ASC"
                        );

                        if (!$item_stmt) {
                            echo '
                                <div class="order-items">
                                    <div class="order-item">
                                        <span>
                                            Unable to load order items.
                                        </span>
                                    </div>
                                </div>
                            ';

                            continue;
                        }

                        $order_id = (int) $order["id"];

                        $item_stmt->bind_param(
                            "i",
                            $order_id
                        );

                        $item_stmt->execute();

                        $items = $item_stmt->get_result();

                        ?>


                        <div class="order-items">


                            <?php if ($items->num_rows > 0): ?>


                                <?php while (
                                    $item = $items->fetch_assoc()
                                ): ?>


                                    <div class="order-item">

                                        <strong>

                                            <?= e(
                                                $item["product_name"]
                                            ) ?>

                                        </strong>


                                        <span>

                                            <?= (int)
                                                $item["quantity"]
                                            ?>

                                            ×

                                            ₱<?= number_format(
                                                (float)
                                                $item["price"],
                                                2
                                            ) ?>

                                        </span>

                                    </div>


                                <?php endwhile; ?>


                            <?php else: ?>


                                <div class="order-item">

                                    <span>
                                        No item details found.
                                    </span>

                                </div>


                            <?php endif; ?>


                        </div>


                        <?php

                        $item_stmt->close();

                        ?>


                        <!-- ORDER TOTAL -->

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



<!-- =========================================================
     FOOTER
========================================================= -->

<footer class="footer">


    <div class="footer-brand">

        <div class="footer-logo-slot">

            <strong
                style="
                    font-family:'Playfair Display',serif;
                    font-size:20px;
                    color:#176d86;
                "
            >
                Siquijor Styles
            </strong>

        </div>


        <p>

            Island-inspired fashion and accessories
            rooted in the beauty of Siquijor.

        </p>

    </div>



    <div class="footer-column">

        <h4>
            SHOP
        </h4>

        <a href="shop.php">
            All Products
        </a>

        <a href="cart.php">
            Cart
        </a>

    </div>



    <div class="footer-column">

        <h4>
            COMPANY
        </h4>

        <a href="about.php">
            About Us
        </a>

        <a href="contact.php">
            Contact
        </a>

    </div>



    <div class="footer-column">

        <h4>
            ACCOUNT
        </h4>

        <a href="orders.php">
            My Orders
        </a>

        <a href="logout.php">
            Logout
        </a>

    </div>



    <div class="footer-column">

        <h4>
            HELP
        </h4>

        <a href="contact.php">
            Support
        </a>

        <a href="contact.php">
            Delivery
        </a>

    </div>


</footer>



<div class="copyright">

    © <?= date("Y") ?> Siquijor Styles.

    All rights reserved.

</div>



<?php

$order_stmt->close();

?>

</body>

</html>