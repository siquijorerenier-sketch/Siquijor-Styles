<?php

require_once "database.php";

session_start();

header("Content-Type: application/json; charset=UTF-8");


/* =========================================================
   RESPONSE HELPER
========================================================= */

function sendResponse(
    $success,
    $message,
    $data = []
) {

    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);

    exit;
}


/* =========================================================
   LOGIN CHECK
========================================================= */

if (
    empty($_SESSION["logged_in"]) ||
    empty($_SESSION["user_id"])
) {

    sendResponse(
        false,
        "Please log in first."
    );

}


$user_id =
    (int) $_SESSION["user_id"];


/* =========================================================
   ONLY POST
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] !== "POST"
) {

    sendResponse(
        false,
        "Invalid request."
    );

}


/* =========================================================
   CUSTOMER INFORMATION
========================================================= */

$full_name =
    trim(
        $_POST["full_name"] ??
        $_SESSION["full_name"] ??
        ""
    );


$email =
    trim(
        $_POST["email"] ??
        $_SESSION["email"] ??
        ""
    );


$phone =
    trim(
        $_POST["phone"] ??
        ""
    );


$address =
    trim(
        $_POST["address"] ??
        ""
    );


/* =========================================================
   VALIDATION
========================================================= */

if (
    $full_name === "" ||
    $email === "" ||
    $phone === "" ||
    $address === ""
) {

    sendResponse(
        false,
        "Please complete all checkout information."
    );

}


if (
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {

    sendResponse(
        false,
        "Please enter a valid email address."
    );

}


/* =========================================================
   VERIFY USER
========================================================= */

$user_stmt =
    $conn->prepare(
        "SELECT
            id,
            full_name,
            email
         FROM users
         WHERE id = ?
         LIMIT 1"
    );


if (!$user_stmt) {

    sendResponse(
        false,
        "Unable to verify your account: " .
        $conn->error
    );

}


$user_stmt->bind_param(
    "i",
    $user_id
);


if (
    !$user_stmt->execute()
) {

    $error =
        $user_stmt->error;

    $user_stmt->close();

    sendResponse(
        false,
        "Unable to verify your account: " .
        $error
    );

}


$user_result =
    $user_stmt->get_result();


if (
    !$user_result ||
    $user_result->num_rows === 0
) {

    $user_stmt->close();

    sendResponse(
        false,
        "User account not found."
    );

}


$user =
    $user_result->fetch_assoc();


$user_stmt->close();


/* =========================================================
   GET CART
========================================================= */

$cart_stmt =
    $conn->prepare(
        "SELECT
            ci.id,
            ci.product_id,
            ci.quantity,
            p.product_name,
            p.price,
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

    sendResponse(
        false,
        "Unable to load your cart: " .
        $conn->error
    );

}


$cart_stmt->bind_param(
    "i",
    $user_id
);


if (
    !$cart_stmt->execute()
) {

    $error =
        $cart_stmt->error;

    $cart_stmt->close();

    sendResponse(
        false,
        "Unable to load your cart: " .
        $error
    );

}


$cart_result =
    $cart_stmt->get_result();


$cart_items = [];

$total_amount = 0;


/* =========================================================
   CHECK EVERY CART ITEM
========================================================= */

while (
    $item =
    $cart_result->fetch_assoc()
) {

    $product_id =
        (int) $item["product_id"];


    $quantity =
        (int) $item["quantity"];


    $price =
        (float) $item["price"];


    $stock =
        (int) $item["stock"];


    if (
        $quantity <= 0
    ) {

        continue;

    }


    /* -----------------------------------------------------
       CHECK STOCK
    ----------------------------------------------------- */

    if (
        $stock <= 0
    ) {

        $cart_stmt->close();

        sendResponse(
            false,
            $item["product_name"] .
            " is out of stock."
        );

    }


    if (
        $quantity > $stock
    ) {

        $cart_stmt->close();

        sendResponse(
            false,
            "Only " .
            $stock .
            " unit(s) of " .
            $item["product_name"] .
            " are available."
        );

    }


    /* -----------------------------------------------------
       CALCULATE SUBTOTAL
    ----------------------------------------------------- */

    $subtotal =
        $price *
        $quantity;


    $total_amount +=
        $subtotal;


    $cart_items[] = [

        "product_id" =>
            $product_id,

        "product_name" =>
            $item["product_name"],

        "quantity" =>
            $quantity,

        "price" =>
            $price

    ];

}


$cart_stmt->close();


/* =========================================================
   EMPTY CART
========================================================= */

if (
    count($cart_items) === 0
) {

    sendResponse(
        false,
        "Your cart is empty."
    );

}


/* =========================================================
   START TRANSACTION
========================================================= */

$conn->begin_transaction();


try {


    /* =====================================================
       CREATE ORDER
    ===================================================== */

    $status =
        "Pending";


    $order_stmt =
        $conn->prepare(
            "INSERT INTO orders
            (
                user_id,
                full_name,
                email,
                phone,
                address,
                total_amount,
                status
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )"
        );


    if (!$order_stmt) {

        throw new Exception(
            "Unable to create order: " .
            $conn->error
        );

    }


    $order_stmt->bind_param(
        "issssds",
        $user_id,
        $full_name,
        $email,
        $phone,
        $address,
        $total_amount,
        $status
    );


    if (
        !$order_stmt->execute()
    ) {

        $error =
            $order_stmt->error;

        $order_stmt->close();

        throw new Exception(
            "Unable to save order: " .
            $error
        );

    }


    $order_id =
        (int) $conn->insert_id;


    $order_stmt->close();


    /* =====================================================
       PREPARE ORDER ITEMS
    ===================================================== */

    $item_stmt =
        $conn->prepare(
            "INSERT INTO order_items
            (
                order_id,
                product_id,
                quantity,
                price
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?
            )"
        );


    if (!$item_stmt) {

        throw new Exception(
            "Unable to create order items: " .
            $conn->error
        );

    }


    /* =====================================================
       PREPARE STOCK UPDATE
    ===================================================== */

    $stock_stmt =
        $conn->prepare(
            "UPDATE products
             SET stock = stock - ?
             WHERE id = ?
               AND stock >= ?"
        );


    if (!$stock_stmt) {

        throw new Exception(
            "Unable to prepare stock update: " .
            $conn->error
        );

    }


    /* =====================================================
       PROCESS CART ITEMS
    ===================================================== */

    foreach (
        $cart_items as $item
    ) {

        $product_id =
            (int) $item["product_id"];


        $quantity =
            (int) $item["quantity"];


        $price =
            (float) $item["price"];


        /* -------------------------------------------------
           INSERT ORDER ITEM
        ------------------------------------------------- */

        $item_stmt->bind_param(
            "iiid",
            $order_id,
            $product_id,
            $quantity,
            $price
        );


        if (
            !$item_stmt->execute()
        ) {

            throw new Exception(
                "Unable to save order item."
            );

        }


        /* -------------------------------------------------
           REDUCE PRODUCT STOCK
        ------------------------------------------------- */

        $stock_stmt->bind_param(
            "iii",
            $quantity,
            $product_id,
            $quantity
        );


        if (
            !$stock_stmt->execute()
        ) {

            throw new Exception(
                "Unable to update product stock."
            );

        }


        /*
         * Exactly one product row must be updated.
         */

        if (
            $stock_stmt->affected_rows !== 1
        ) {

            throw new Exception(
                "Product stock changed while placing the order. Please review your cart and try again."
            );

        }

    }


    $item_stmt->close();

    $stock_stmt->close();


    /* =====================================================
       CLEAR USER CART
    ===================================================== */

    $clear_stmt =
        $conn->prepare(
            "DELETE ci
             FROM cart_items ci
             INNER JOIN cart c
                ON ci.cart_id = c.id
             WHERE c.user_id = ?"
        );


    if (!$clear_stmt) {

        throw new Exception(
            "Unable to clear your cart: " .
            $conn->error
        );

    }


    $clear_stmt->bind_param(
        "i",
        $user_id
    );


    if (
        !$clear_stmt->execute()
    ) {

        $error =
            $clear_stmt->error;

        $clear_stmt->close();

        throw new Exception(
            "Unable to clear your cart: " .
            $error
        );

    }


    $clear_stmt->close();


    /* =====================================================
       COMMIT
    ===================================================== */

    $conn->commit();


    /* =====================================================
       SUCCESS
    ===================================================== */

    sendResponse(
        true,
        "Order placed successfully!",
        [
            "order_id" =>
                $order_id,

            "total" =>
                (float) $total_amount
        ]
    );


} catch (
    Exception $e
) {


    /* =====================================================
       ROLLBACK
    ===================================================== */

    $conn->rollback();


    sendResponse(
        false,
        $e->getMessage()
    );

}

?>