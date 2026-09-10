<?php

session_start();

require_once "database.php";

header("Content-Type: application/json; charset=UTF-8");


/* ==================================================
   RESPONSE HELPER
================================================== */

function sendResponse(
    bool $success,
    string $message,
    array $data = []
): void {

    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);

    exit;
}


/* ==================================================
   DATABASE CHECK
================================================== */

if (!isset($conn) || !$conn) {

    sendResponse(
        false,
        "Database connection failed."
    );

}


/* ==================================================
   LOGIN CHECK
================================================== */

if (
    empty($_SESSION["logged_in"]) ||
    empty($_SESSION["user_id"])
) {

    sendResponse(
        false,
        "Please log in first."
    );

}


$user_id = (int) $_SESSION["user_id"];


/* ==================================================
   REQUEST METHOD
================================================== */

if (
    $_SERVER["REQUEST_METHOD"] !== "POST"
) {

    sendResponse(
        false,
        "Invalid request."
    );

}


/* ==================================================
   REQUEST VALUES
================================================== */

$action =
    trim($_POST["action"] ?? "");

$product_id =
    (int) ($_POST["product_id"] ?? 0);

$quantity =
    (int) ($_POST["quantity"] ?? 1);


/* ==================================================
   GET CART
================================================== */

if ($action === "get") {

    /*
     * IMPORTANT:
     * products.id is the primary key.
     * cart_items.product_id points to products.id.
     */

    $stmt = $conn->prepare(
        "SELECT
            ci.id,
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
         ORDER BY ci.id DESC"
    );


    if (!$stmt) {

        sendResponse(
            false,
            "Unable to load cart: " . $conn->error
        );

    }


    $stmt->bind_param(
        "i",
        $user_id
    );


    if (!$stmt->execute()) {

        $error =
            $stmt->error;

        $stmt->close();

        sendResponse(
            false,
            "Unable to load cart: " . $error
        );

    }


    $result =
        $stmt->get_result();


    $items = [];


    if ($result) {

        while (
            $row =
            $result->fetch_assoc()
        ) {

            $items[] = [

                "id" =>
                    (int) $row["id"],

                "product_id" =>
                    (int) $row["product_id"],

                "productId" =>
                    (int) $row["product_id"],

                "name" =>
                    $row["product_name"],

                "product_name" =>
                    $row["product_name"],

                "price" =>
                    (float) $row["price"],

                "image" =>
                    $row["image"],

                "quantity" =>
                    (int) $row["quantity"],

                "stock" =>
                    (int) $row["stock"]

            ];

        }

    }


    $stmt->close();


    sendResponse(
        true,
        "Cart loaded.",
        [
            "cart" => $items
        ]
    );

}


/* ==================================================
   ADD TO CART
================================================== */

if ($action === "add") {


    if ($product_id <= 0) {

        sendResponse(
            false,
            "Invalid product."
        );

    }


    if ($quantity <= 0) {

        sendResponse(
            false,
            "Invalid quantity."
        );

    }


    /* ----------------------------------------------
       FIND PRODUCT
    ---------------------------------------------- */

    $stmt = $conn->prepare(
        "SELECT
            id,
            product_name,
            price,
            stock,
            image
         FROM products
         WHERE id = ?
         LIMIT 1"
    );


    if (!$stmt) {

        sendResponse(
            false,
            "Unable to find product: " . $conn->error
        );

    }


    $stmt->bind_param(
        "i",
        $product_id
    );


    if (!$stmt->execute()) {

        $error =
            $stmt->error;

        $stmt->close();

        sendResponse(
            false,
            "Unable to find product: " . $error
        );

    }


    $result =
        $stmt->get_result();


    if (
        !$result ||
        $result->num_rows === 0
    ) {

        $stmt->close();

        sendResponse(
            false,
            "Product not found."
        );

    }


    $product =
        $result->fetch_assoc();


    $stmt->close();


    $stock =
        (int) $product["stock"];


    if ($stock <= 0) {

        sendResponse(
            false,
            $product["product_name"] .
            " is out of stock."
        );

    }


    if ($quantity > $stock) {

        sendResponse(
            false,
            "Only " .
            $stock .
            " unit(s) available."
        );

    }


    /* ----------------------------------------------
       FIND USER CART
    ---------------------------------------------- */

    $stmt = $conn->prepare(
        "SELECT
            id
         FROM cart
         WHERE user_id = ?
         LIMIT 1"
    );


    if (!$stmt) {

        sendResponse(
            false,
            "Unable to find cart: " . $conn->error
        );

    }


    $stmt->bind_param(
        "i",
        $user_id
    );


    if (!$stmt->execute()) {

        $error =
            $stmt->error;

        $stmt->close();

        sendResponse(
            false,
            "Unable to find cart: " . $error
        );

    }


    $result =
        $stmt->get_result();


    if (
        $result &&
        $result->num_rows > 0
    ) {

        $cart =
            $result->fetch_assoc();

        $cart_id =
            (int) $cart["id"];

        $stmt->close();

    }

    else {

        $stmt->close();


        $create =
            $conn->prepare(
                "INSERT INTO cart
                 (user_id)
                 VALUES (?)"
            );


        if (!$create) {

            sendResponse(
                false,
                "Unable to create cart: " . $conn->error
            );

        }


        $create->bind_param(
            "i",
            $user_id
        );


        if (!$create->execute()) {

            $error =
                $create->error;

            $create->close();

            sendResponse(
                false,
                "Unable to create cart: " . $error
            );

        }


        $cart_id =
            (int) $conn->insert_id;


        $create->close();

    }


    /* ----------------------------------------------
       CHECK EXISTING CART ITEM
    ---------------------------------------------- */

    $stmt = $conn->prepare(
        "SELECT
            id,
            quantity
         FROM cart_items
         WHERE cart_id = ?
           AND product_id = ?
         LIMIT 1"
    );


    if (!$stmt) {

        sendResponse(
            false,
            "Unable to check cart item: " .
            $conn->error
        );

    }


    $stmt->bind_param(
        "ii",
        $cart_id,
        $product_id
    );


    if (!$stmt->execute()) {

        $error =
            $stmt->error;

        $stmt->close();

        sendResponse(
            false,
            "Unable to check cart item: " .
            $error
        );

    }


    $result =
        $stmt->get_result();


    if (
        $result &&
        $result->num_rows > 0
    ) {

        $existing =
            $result->fetch_assoc();


        $item_id =
            (int) $existing["id"];


        $old_quantity =
            (int) $existing["quantity"];


        $new_quantity =
            $old_quantity +
            $quantity;


        $stmt->close();


        if (
            $new_quantity >
            $stock
        ) {

            sendResponse(
                false,
                "Only " .
                $stock .
                " unit(s) available."
            );

        }


        $update =
            $conn->prepare(
                "UPDATE cart_items
                 SET quantity = ?
                 WHERE id = ?"
            );


        if (!$update) {

            sendResponse(
                false,
                "Unable to update cart: " .
                $conn->error
            );

        }


        $update->bind_param(
            "ii",
            $new_quantity,
            $item_id
        );


        if (!$update->execute()) {

            $error =
                $update->error;

            $update->close();

            sendResponse(
                false,
                "Unable to update cart: " .
                $error
            );

        }


        $update->close();

    }

    else {

        $stmt->close();


        $insert =
            $conn->prepare(
                "INSERT INTO cart_items
                 (
                    cart_id,
                    product_id,
                    quantity
                 )
                 VALUES
                 (
                    ?,
                    ?,
                    ?
                 )"
            );


        if (!$insert) {

            sendResponse(
                false,
                "Unable to add item to cart: " .
                $conn->error
            );

        }


        $insert->bind_param(
            "iii",
            $cart_id,
            $product_id,
            $quantity
        );


        if (!$insert->execute()) {

            $error =
                $insert->error;

            $insert->close();

            sendResponse(
                false,
                "Unable to add item to cart: " .
                $error
            );

        }


        $insert->close();

    }


    sendResponse(
        true,
        $product["product_name"] .
        " has been added to your cart."
    );

}


/* ==================================================
   UPDATE CART
================================================== */

if ($action === "update") {


    if (
        $product_id <= 0 ||
        $quantity <= 0
    ) {

        sendResponse(
            false,
            "Invalid cart information."
        );

    }


    $stmt = $conn->prepare(
        "SELECT
            ci.id,
            p.product_name,
            p.stock
         FROM cart_items ci
         INNER JOIN cart c
            ON ci.cart_id = c.id
         INNER JOIN products p
            ON ci.product_id = p.id
         WHERE c.user_id = ?
           AND ci.product_id = ?
         LIMIT 1"
    );


    if (!$stmt) {

        sendResponse(
            false,
            "Unable to find cart item: " .
            $conn->error
        );

    }


    $stmt->bind_param(
        "ii",
        $user_id,
        $product_id
    );


    if (!$stmt->execute()) {

        $error =
            $stmt->error;

        $stmt->close();

        sendResponse(
            false,
            "Unable to find cart item: " .
            $error
        );

    }


    $result =
        $stmt->get_result();


    if (
        !$result ||
        $result->num_rows === 0
    ) {

        $stmt->close();

        sendResponse(
            false,
            "Cart item not found."
        );

    }


    $item =
        $result->fetch_assoc();


    $stmt->close();


    $stock =
        (int) $item["stock"];


    if (
        $quantity >
        $stock
    ) {

        sendResponse(
            false,
            "Only " .
            $stock .
            " unit(s) available."
        );

    }


    $update =
        $conn->prepare(
            "UPDATE cart_items ci
             INNER JOIN cart c
                ON ci.cart_id = c.id
             SET ci.quantity = ?
             WHERE c.user_id = ?
               AND ci.product_id = ?"
        );


    if (!$update) {

        sendResponse(
            false,
            "Unable to update cart: " .
            $conn->error
        );

    }


    $update->bind_param(
        "iii",
        $quantity,
        $user_id,
        $product_id
    );


    if (!$update->execute()) {

        $error =
            $update->error;

        $update->close();

        sendResponse(
            false,
            "Unable to update cart: " .
            $error
        );

    }


    $update->close();


    sendResponse(
        true,
        "Cart updated."
    );

}


/* ==================================================
   REMOVE ITEM
================================================== */

if ($action === "remove") {


    if ($product_id <= 0) {

        sendResponse(
            false,
            "Invalid product."
        );

    }


    $stmt = $conn->prepare(
        "DELETE ci
         FROM cart_items ci
         INNER JOIN cart c
            ON ci.cart_id = c.id
         WHERE c.user_id = ?
           AND ci.product_id = ?"
    );


    if (!$stmt) {

        sendResponse(
            false,
            "Unable to remove item: " .
            $conn->error
        );

    }


    $stmt->bind_param(
        "ii",
        $user_id,
        $product_id
    );


    if (!$stmt->execute()) {

        $error =
            $stmt->error;

        $stmt->close();

        sendResponse(
            false,
            "Unable to remove item: " .
            $error
        );

    }


    $stmt->close();


    sendResponse(
        true,
        "Item removed from cart."
    );

}


/* ==================================================
   CLEAR CART
================================================== */

if ($action === "clear") {


    $stmt = $conn->prepare(
        "DELETE ci
         FROM cart_items ci
         INNER JOIN cart c
            ON ci.cart_id = c.id
         WHERE c.user_id = ?"
    );


    if (!$stmt) {

        sendResponse(
            false,
            "Unable to clear cart: " .
            $conn->error
        );

    }


    $stmt->bind_param(
        "i",
        $user_id
    );


    if (!$stmt->execute()) {

        $error =
            $stmt->error;

        $stmt->close();

        sendResponse(
            false,
            "Unable to clear cart: " .
            $error
        );

    }


    $stmt->close();


    sendResponse(
        true,
        "Cart cleared."
    );

}


/* ==================================================
   INVALID ACTION
================================================== */

sendResponse(
    false,
    "Invalid cart action."
);

?>