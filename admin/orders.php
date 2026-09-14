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

        $transaction_started = false;

        try {

            /*
             * Use a transaction so the order status and
             * product stock are changed together.
             */
            $conn->begin_transaction();
            $transaction_started = true;


            /*
             * Lock the order while changing its status.
             */
            $stmt = $conn->prepare("
                SELECT id, status
                FROM orders
                WHERE id = ?
                LIMIT 1
                FOR UPDATE
            ");

            if (!$stmt) {
                throw new Exception(
                    "Unable to prepare the order lookup."
                );
            }

            $stmt->bind_param(
                "i",
                $order_id
            );


            if (!$stmt->execute()) {

                $stmt->close();

                throw new Exception(
                    "Unable to load the order."
                );
            }


            $result = $stmt->get_result();

            $order = $result->fetch_assoc();

            $stmt->close();


            if (!$order) {

                throw new Exception(
                    "Order not found."
                );
            }


            $old_status = $order["status"];


            /*
             * Nothing needs to be changed if the status
             * is already the requested status.
             */
            if ($old_status === $new_status) {

                $conn->commit();

                $message =
                    "Order #" .
                    $order_id .
                    " is already " .
                    $new_status .
                    ".";

                $message_type = "success";

            } else {


                /* =================================================
                   CANCEL ORDER
                   Restore the ordered quantity to product stock.
                ================================================= */

                if (
                    $new_status === "Cancelled" &&
                    $old_status !== "Cancelled"
                ) {

                    $stmt = $conn->prepare("
                        SELECT
                            product_id,
                            quantity
                        FROM order_items
                        WHERE order_id = ?
                    ");

                    if (!$stmt) {
                        throw new Exception(
                            "Unable to prepare the order items lookup."
                        );
                    }


                    $stmt->bind_param(
                        "i",
                        $order_id
                    );


                    if (!$stmt->execute()) {

                        $stmt->close();

                        throw new Exception(
                            "Unable to load the order items."
                        );
                    }


                    $items_result =
                        $stmt->get_result();


                    /*
                     * Prepare stock restoration query.
                     */
                    $stock_stmt = $conn->prepare("
                        UPDATE products
                        SET stock = stock + ?
                        WHERE id = ?
                    ");


                    if (!$stock_stmt) {

                        $stmt->close();

                        throw new Exception(
                            "Unable to prepare the stock restoration."
                        );
                    }


                    while (
                        $item =
                        $items_result->fetch_assoc()
                    ) {

                        $product_id =
                            (int) $item["product_id"];

                        $quantity =
                            (int) $item["quantity"];


                        /*
                         * Ignore invalid order-item values.
                         */
                        if (
                            $product_id <= 0 ||
                            $quantity <= 0
                        ) {
                            continue;
                        }


                        $stock_stmt->bind_param(
                            "ii",
                            $quantity,
                            $product_id
                        );


                        if (!$stock_stmt->execute()) {

                            $stock_stmt->close();
                            $stmt->close();

                            throw new Exception(
                                "Unable to restore product stock."
                            );
                        }
                    }


                    $stock_stmt->close();
                    $stmt->close();
                }


                /* =================================================
                   REOPEN CANCELLED ORDER
                   Deduct stock again when changing a cancelled
                   order back to an active status.
                ================================================= */

                elseif (
                    $old_status === "Cancelled" &&
                    $new_status !== "Cancelled"
                ) {

                    $stmt = $conn->prepare("
                        SELECT
                            oi.product_id,
                            oi.product_name,
                            oi.quantity,
                            p.stock
                        FROM order_items oi
                        INNER JOIN products p
                            ON p.id = oi.product_id
                        WHERE oi.order_id = ?
                        FOR UPDATE
                    ");


                    if (!$stmt) {

                        throw new Exception(
                            "Unable to prepare the stock check."
                        );
                    }


                    $stmt->bind_param(
                        "i",
                        $order_id
                    );


                    if (!$stmt->execute()) {

                        $stmt->close();

                        throw new Exception(
                            "Unable to check product stock."
                        );
                    }


                    $items_result =
                        $stmt->get_result();


                    $items = [];


                    while (
                        $item =
                        $items_result->fetch_assoc()
                    ) {

                        $items[] = $item;
                    }


                    $stmt->close();


                    /*
                     * Check all products before deducting
                     * anything. This prevents a partial stock
                     * deduction if one product does not have
                     * enough stock.
                     */
                    foreach ($items as $item) {

                        $product_name =
                            $item["product_name"];

                        $quantity =
                            (int) $item["quantity"];

                        $current_stock =
                            (int) $item["stock"];


                        if (
                            $current_stock <
                            $quantity
                        ) {

                            throw new Exception(
                                "Not enough stock for " .
                                $product_name .
                                ". Available: " .
                                $current_stock .
                                ", required: " .
                                $quantity .
                                "."
                            );
                        }
                    }


                    /*
                     * Deduct stock.
                     */
                    $stock_stmt = $conn->prepare("
                        UPDATE products
                        SET stock = stock - ?
                        WHERE id = ?
                          AND stock >= ?
                    ");


                    if (!$stock_stmt) {

                        throw new Exception(
                            "Unable to prepare the stock update."
                        );
                    }


                    foreach ($items as $item) {

                        $product_id =
                            (int) $item["product_id"];

                        $quantity =
                            (int) $item["quantity"];


                        $stock_stmt->bind_param(
                            "iii",
                            $quantity,
                            $product_id,
                            $quantity
                        );


                        if (!$stock_stmt->execute()) {

                            $stock_stmt->close();

                            throw new Exception(
                                "Unable to update product stock."
                            );
                        }


                        /*
                         * If exactly one row was not changed,
                         * the stock may have changed unexpectedly.
                         */
                        if (
                            $stock_stmt->affected_rows !== 1
                        ) {

                            $stock_stmt->close();

                            throw new Exception(
                                "Stock changed while processing the order. Please try again."
                            );
                        }
                    }


                    $stock_stmt->close();
                }


                /* =================================================
                   UPDATE ORDER STATUS
                ================================================= */

                $stmt = $conn->prepare("
                    UPDATE orders
                    SET status = ?
                    WHERE id = ?
                    LIMIT 1
                ");


                if (!$stmt) {

                    throw new Exception(
                        "Unable to prepare the order status update."
                    );
                }


                $stmt->bind_param(
                    "si",
                    $new_status,
                    $order_id
                );


                if (!$stmt->execute()) {

                    $stmt->close();

                    throw new Exception(
                        "Unable to update the order status."
                    );
                }


                $stmt->close();


                /*
                 * Everything succeeded.
                 */
                $conn->commit();


                $message =
                    "Order #" .
                    $order_id .
                    " status updated from " .
                    $old_status .
                    " to " .
                    $new_status .
                    ".";


                if (
                    $new_status === "Cancelled"
                ) {

                    $message .=
                        " Product stock has been restored.";
                }


                if (
                    $old_status === "Cancelled" &&
                    $new_status !== "Cancelled"
                ) {

                    $message .=
                        " Product stock has been deducted again.";
                }


                $message_type = "success";
            }

        } catch (Throwable $e) {

            if ($transaction_started) {
                $conn->rollback();
            }


            $message =
                "Unable to update order #" .
                $order_id .
                ". " .
                $e->getMessage();

            $message_type = "error";
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

        $order_ids[] =
            (int) $order["id"];
    }


    /*
     * Build a safe integer-only IN() list.
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


    $items_result =
        $conn->query($items_sql);


    if ($items_result) {

        while (
            $item =
            $items_result->fetch_assoc()
        ) {

            $order_id =
                (int) $item["order_id"];


            if (
                !isset(
                    $order_items[$order_id]
                )
            ) {

                $order_items[$order_id] = [];
            }


            $order_items[$order_id][] =
                $item;
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

    $status =
        $order["status"];


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
        }


        .item-price {

            font-size: 13px;

            font-weight: 700;

            white-space: nowrap;
        }


        /* =================================================
           CUSTOMER INFORMATION
        ================================================= */

        .info-box {

            background: #faf8f5;

            border: 1px solid #eee7e0;

            border-radius: 10px;

            padding: 14px;
        }


        .info-row {

            display: flex;

            justify-content: space-between;

            gap: 15px;

            padding: 8px 0;

            border-bottom: 1px solid
                #eee7e0;

            font-size: 12px;
        }


        .info-row:last-child {

            border-bottom: 0;

            padding-bottom: 0;
        }


        .info-row:first-child {

            padding-top: 0;
        }


        .info-label {

            color: #91867e;

            flex-shrink: 0;
        }


        .info-value {

            color: #433c36;

            text-align: right;

            word-break: break-word;
        }


        /* =================================================
           TOTAL
        ================================================= */

        .order-total {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-top: 15px;

            padding-top: 15px;

            border-top: 1px solid #e8e0d8;
        }


        .total-label {

            color: #71675f;

            font-size: 13px;

            font-weight: 600;
        }


        .total-value {

            font-size: 20px;

            font-weight: 700;

            color: #2f2a25;
        }


        /* =================================================
           STATUS FORM
        ================================================= */

        .status-form {

            margin-top: 18px;

            padding-top: 18px;

            border-top: 1px solid #e8e0d8;
        }


        .status-form label {

            display: block;

            margin-bottom: 7px;

            color: #6e645c;

            font-size: 12px;

            font-weight: 600;
        }


        .status-form-row {

            display: flex;

            gap: 9px;
        }


        .status-select {

            flex: 1;

            min-width: 0;

            padding: 10px 11px;

            border: 1px solid #dcd3ca;

            border-radius: 8px;

            background: #ffffff;

            color: #413a34;

            font-size: 12px;

            outline: none;
        }


        .status-select:focus {

            border-color: #a99b90;

            box-shadow:
                0 0 0 3px
                rgba(169,155,144,0.12);
        }


        .update-button {

            border: 0;

            border-radius: 8px;

            padding: 10px 14px;

            background: #2f2a25;

            color: #ffffff;

            font-size: 12px;

            font-weight: 700;

            cursor: pointer;

            white-space: nowrap;
        }


        .update-button:hover {

            background: #4a423b;
        }


        /* =================================================
           NOTES
        ================================================= */

        .notes-box {

            margin-top: 14px;

            padding: 12px;

            border-radius: 9px;

            background: #fffdf9;

            border: 1px solid #eee7e0;
        }


        .notes-label {

            margin-bottom: 5px;

            color: #8c8178;

            font-size: 11px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.5px;
        }


        .notes-text {

            color: #5d544d;

            font-size: 12px;

            line-height: 1.5;

            white-space: pre-wrap;

            word-break: break-word;
        }


        /* =================================================
           EMPTY STATE
        ================================================= */

        .empty-state {

            background: #ffffff;

            border: 1px solid #e5ddd5;

            border-radius: 15px;

            padding: 55px 25px;

            text-align: center;

            box-shadow:
                0 5px 18px
                rgba(47,42,37,0.04);
        }


        .empty-icon {

            font-size: 40px;

            margin-bottom: 12px;

            opacity: 0.65;
        }


        .empty-state h2 {

            margin: 0 0 7px;

            font-size: 19px;
        }


        .empty-state p {

            margin: 0;

            color: #837971;

            font-size: 13px;
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 1250px) {

            .summary-grid {

                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }
        }


        @media (max-width: 950px) {

            .sidebar {

                width: 210px;
            }


            .main-content {

                padding: 22px;
            }


            .order-grid {

                grid-template-columns: 1fr;
            }
        }


        @media (max-width: 700px) {

            .admin-layout {

                display: block;
            }


            .sidebar {

                width: 100%;

                min-height: auto;

                padding: 18px;
            }


            .sidebar-logo {

                margin-bottom: 15px;
            }


            .admin-nav {

                display: grid;

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }


            .sidebar-bottom {

                margin-top: 18px;
            }


            .main-content {

                padding: 18px;
            }


            .topbar {

                flex-direction: column;
            }


            .summary-grid {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }


            .order-header {

                flex-direction: column;
            }
        }


        @media (max-width: 480px) {

            .summary-grid {

                grid-template-columns: 1fr;
            }


            .admin-nav {

                grid-template-columns: 1fr;
            }


            .item {

                grid-template-columns: 1fr;

                gap: 5px;
            }


            .item-price {

                text-align: left;
            }


            .status-form-row {

                flex-direction: column;
            }


            .update-button {

                width: 100%;
            }


            .info-row {

                flex-direction: column;

                gap: 4px;
            }


            .info-value {

                text-align: left;
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
                    Orders
                </h1>

                <p>
                    Manage customer orders and update their status.
                </p>

            </div>


            <a
                href="../index.php"
                class="view-site"
            >
                View Website
            </a>


        </div>


        <!-- =================================================
             SUMMARY
        ================================================== -->

        <div class="summary-grid">


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
                    Cancelled
                </div>

                <div class="summary-value">
                    <?= number_format($cancelled_orders) ?>
                </div>

            </div>


        </div>


        <?php if ($message !== ""): ?>

            <div
                class="message
                <?= $message_type === "success"
                    ? "success-message"
                    : "error-message" ?>"
            >

                <?= e($message) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             ORDERS
        ================================================== -->

        <div class="orders-list">


            <?php if (empty($orders)): ?>


                <div class="empty-state">

                    <div class="empty-icon">
                        🧾
                    </div>

                    <h2>
                        No orders yet
                    </h2>

                    <p>
                        Customer orders will appear here.
                    </p>

                </div>


            <?php else: ?>


                <?php foreach ($orders as $order): ?>

                    <?php

                    $order_id =
                        (int) $order["id"];

                    $status =
                        $order["status"];

                    $status_class =
                        strtolower(
                            str_replace(
                                " ",
                                "-",
                                $status
                            )
                        );

                    $items =
                        $order_items[$order_id]
                        ?? [];

                    ?>


                    <article class="order-card">


                        <div class="order-header">


                            <div>

                                <div class="order-number">

                                    Order #<?= $order_id ?>

                                </div>


                                <div class="order-date">

                                    <?= e(
                                        date(
                                            "M d, Y h:i A",
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

                                </div>

                            </div>


                            <span
                                class="status status-<?= e(
                                    $status_class
                                ) ?>"
                            >

                                <?= e($status) ?>

                            </span>


                        </div>


                        <div class="order-body">


                            <div class="order-grid">


                                <!-- =================================
                                     ORDER ITEMS
                                ================================== -->

                                <div>


                                    <h3 class="section-title">
                                        Order Items
                                    </h3>


                                    <?php if (!empty($items)): ?>


                                        <div class="item-list">


                                            <?php foreach ($items as $item): ?>

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
                                                        <?= (int) $item["quantity"] ?>

                                                    </div>


                                                    <div class="item-price">

                                                        ₱<?= number_format(
                                                            (float) $item["subtotal"],
                                                            2
                                                        ) ?>

                                                    </div>


                                                </div>

                                            <?php endforeach; ?>


                                        </div>


                                    <?php else: ?>


                                        <div class="info-box">

                                            <div class="info-value">
                                                No items found for this order.
                                            </div>

                                        </div>


                                    <?php endif; ?>


                                    <div class="order-total">


                                        <div class="total-label">
                                            Order Total
                                        </div>


                                        <div class="total-value">

                                            ₱<?= number_format(
                                                (float) $order["total_amount"],
                                                2
                                            ) ?>

                                        </div>


                                    </div>


                                </div>


                                <!-- =================================
                                     CUSTOMER / SHIPPING INFORMATION
                                ================================== -->

                                <div>


                                    <h3 class="section-title">
                                        Customer & Shipping
                                    </h3>


                                    <div class="info-box">


                                        <div class="info-row">

                                            <span class="info-label">
                                                Name
                                            </span>

                                            <span class="info-value">
                                                <?= e(
                                                    $order["shipping_name"]
                                                ) ?>
                                            </span>

                                        </div>


                                        <div class="info-row">

                                            <span class="info-label">
                                                Email
                                            </span>

                                            <span class="info-value">
                                                <?= e(
                                                    $order["shipping_email"]
                                                ) ?>
                                            </span>

                                        </div>


                                        <div class="info-row">

                                            <span class="info-label">
                                                Phone
                                            </span>

                                            <span class="info-value">
                                                <?= e(
                                                    $order["shipping_phone"]
                                                ) ?>
                                            </span>

                                        </div>


                                        <div class="info-row">

                                            <span class="info-label">
                                                Address
                                            </span>

                                            <span class="info-value">
                                                <?= e(
                                                    $order["shipping_address"]
                                                ) ?>
                                            </span>

                                        </div>


                                    </div>


                                    <?php if (
                                        trim(
                                            (string) $order["notes"]
                                        ) !== ""
                                    ): ?>


                                        <div class="notes-box">


                                            <div class="notes-label">
                                                Notes
                                            </div>


                                            <div class="notes-text">

                                                <?= e(
                                                    $order["notes"]
                                                ) ?>

                                            </div>


                                        </div>


                                    <?php endif; ?>


                                    <!-- =================================
                                         STATUS UPDATE
                                    ================================== -->

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


                                        <label
                                            for="status-<?= $order_id ?>"
                                        >
                                            Update Order Status
                                        </label>


                                        <div class="status-form-row">


                                            <select
                                                id="status-<?= $order_id ?>"
                                                name="status"
                                                class="status-select"
                                            >


                                                <?php foreach (
                                                    $allowed_statuses
                                                    as $allowed_status
                                                ): ?>

                                                    <option
                                                        value="<?= e(
                                                            $allowed_status
                                                        ) ?>"
                                                        <?= $status ===
                                                            $allowed_status
                                                            ? "selected"
                                                            : "" ?>
                                                    >

                                                        <?= e(
                                                            $allowed_status
                                                        ) ?>

                                                    </option>

                                                <?php endforeach; ?>


                                            </select>


                                            <button
                                                type="submit"
                                                class="update-button"
                                            >
                                                Update
                                            </button>


                                        </div>


                                    </form>


                                </div>


                            </div>


                        </div>


                    </article>


                <?php endforeach; ?>


            <?php endif; ?>


        </div>


    </main>


</div>


</body>

</html>