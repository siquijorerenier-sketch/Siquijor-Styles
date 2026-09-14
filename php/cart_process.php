<?php

/*
 * SIQUIJOR STYLES
 * php/cart_process.php
 *
 * SINGLE DATABASE CART API
 *
 * Supported actions:
 *   get
 *   add
 *   update
 *   remove
 *   clear
 */


/* =========================================================
   OUTPUT BUFFER
========================================================= */

ob_start();


/* =========================================================
   ERROR SETTINGS
========================================================= */

ini_set(
    "display_errors",
    "0"
);

ini_set(
    "display_startup_errors",
    "0"
);

error_reporting(
    E_ALL
);


/* =========================================================
   LOAD SHARED SESSION + DATABASE
========================================================= */

try {

    require_once __DIR__ . "/session.php";

    require_once __DIR__ . "/database.php";

} catch (Throwable $e) {

    while (
        ob_get_level() > 0
    ) {

        ob_end_clean();

    }

    header(
        "Content-Type: application/json; charset=UTF-8"
    );

    echo json_encode(
        [
            "success" => false,
            "message" => "Unable to load the cart system.",
            "data" => []
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;

}


/* =========================================================
   RESPONSE HEADERS
========================================================= */

header(
    "Content-Type: application/json; charset=UTF-8"
);

header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);

header(
    "Pragma: no-cache"
);


/* =========================================================
   JSON RESPONSE FUNCTION
========================================================= */

function cart_json(
    $success,
    $message = "",
    $data = []
) {

    while (
        ob_get_level() > 0
    ) {

        ob_end_clean();

    }

    header(
        "Content-Type: application/json; charset=UTF-8"
    );

    echo json_encode(
        [
            "success" =>
                (bool) $success,

            "message" =>
                (string) $message,

            "data" =>
                is_array($data)
                    ? $data
                    : []
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;

}


/* =========================================================
   CONVERT PHP ERRORS INTO EXCEPTIONS
========================================================= */

set_error_handler(
    function (
        $severity,
        $message,
        $file,
        $line
    ) {

        throw new ErrorException(
            $message,
            0,
            $severity,
            $file,
            $line
        );

    }
);


/* =========================================================
   MAIN CART LOGIC
========================================================= */

try {


    /* =====================================================
       REQUEST METHOD
    ===================================================== */

    if (
        ($_SERVER["REQUEST_METHOD"] ?? "") !== "POST"
    ) {

        cart_json(
            false,
            "Invalid request method."
        );

    }


    /* =====================================================
       LOGIN CHECK
    ===================================================== */

    if (
        empty($_SESSION["logged_in"]) ||
        empty($_SESSION["user_id"])
    ) {

        cart_json(
            false,
            "Please log in first."
        );

    }


    $user_id =
        (int) $_SESSION["user_id"];


    if (
        $user_id <= 0
    ) {

        cart_json(
            false,
            "Invalid user session."
        );

    }


    /* =====================================================
       DATABASE CHECK
    ===================================================== */

    if (
        !isset($conn) ||
        !($conn instanceof mysqli)
    ) {

        cart_json(
            false,
            "Database connection is unavailable."
        );

    }


    if (
        $conn->connect_errno
    ) {

        cart_json(
            false,
            "Database connection failed."
        );

    }


    $conn->set_charset(
        "utf8mb4"
    );


    /* =====================================================
       ACTION
    ===================================================== */

    $action =
        trim(
            (string)
            (
                $_POST["action"]
                ?? ""
            )
        );


    if (
        $action === ""
    ) {

        cart_json(
            false,
            "No cart action was provided."
        );

    }


    /* =====================================================
       FIND USER CART
    ===================================================== */

    $cart_id =
        0;


    $cart_stmt =
        $conn->prepare(
            "
            SELECT
                id

            FROM cart

            WHERE user_id = ?

            ORDER BY id ASC

            LIMIT 1
            "
        );


    if (
        !$cart_stmt
    ) {

        throw new RuntimeException(
            "Unable to prepare cart lookup: " .
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

        throw new RuntimeException(
            "Unable to load cart: " .
            $error
        );

    }


    $cart_result =
        $cart_stmt->get_result();


    if (
        $cart_result &&
        $cart_result->num_rows > 0
    ) {

        $cart_row =
            $cart_result->fetch_assoc();

        $cart_id =
            (int) $cart_row["id"];

    }


    $cart_stmt->close();


    /* =====================================================
       CREATE CART IF USER DOES NOT HAVE ONE
    ===================================================== */

    if (
        $cart_id <= 0
    ) {

        $create_cart_stmt =
            $conn->prepare(
                "
                INSERT INTO cart
                (
                    user_id
                )

                VALUES
                (
                    ?
                )
                "
            );


        if (
            !$create_cart_stmt
        ) {

            throw new RuntimeException(
                "Unable to prepare cart creation: " .
                $conn->error
            );

        }


        $create_cart_stmt->bind_param(
            "i",
            $user_id
        );


        if (
            !$create_cart_stmt->execute()
        ) {

            $error =
                $create_cart_stmt->error;

            $create_cart_stmt->close();

            throw new RuntimeException(
                "Unable to create cart: " .
                $error
            );

        }


        $cart_id =
            (int)
            $create_cart_stmt->insert_id;


        $create_cart_stmt->close();

    }


    /* =====================================================
       GET CART
    ===================================================== */

    if (
        $action === "get"
    ) {

        $items =
            [];


        $items_stmt =
            $conn->prepare(
                "
                SELECT

                    ci.id,

                    ci.product_id,

                    ci.quantity,

                    p.product_name,

                    p.description,

                    p.price,

                    p.image,

                    p.stock

                FROM cart_items ci

                INNER JOIN products p

                    ON p.id = ci.product_id

                WHERE ci.cart_id = ?

                ORDER BY ci.id ASC
                "
            );


        if (
            !$items_stmt
        ) {

            throw new RuntimeException(
                "Unable to prepare cart items query: " .
                $conn->error
            );

        }


        $items_stmt->bind_param(
            "i",
            $cart_id
        );


        if (
            !$items_stmt->execute()
        ) {

            $error =
                $items_stmt->error;

            $items_stmt->close();

            throw new RuntimeException(
                "Unable to load cart items: " .
                $error
            );

        }


        $items_result =
            $items_stmt->get_result();


        if (
            $items_result
        ) {

            while (
                $row =
                $items_result->fetch_assoc()
            ) {

                $items[] = [

                    "id" =>
                        (int)
                        $row["id"],

                    "product_id" =>
                        (int)
                        $row["product_id"],

                    "quantity" =>
                        (int)
                        $row["quantity"],

                    "product_name" =>
                        (string)
                        $row["product_name"],

                    "description" =>
                        (string)
                        (
                            $row["description"]
                            ?? ""
                        ),

                    "price" =>
                        (float)
                        $row["price"],

                    "image" =>
                        (string)
                        (
                            $row["image"]
                            ?? ""
                        ),

                    "stock" =>
                        (int)
                        $row["stock"]

                ];

            }

        }


        $items_stmt->close();


        $total_items =
            0;


        $total_amount =
            0.00;


        foreach (
            $items as $item
        ) {

            $quantity =
                (int)
                $item["quantity"];


            $price =
                (float)
                $item["price"];


            $total_items +=
                $quantity;


            $total_amount +=
                $price *
                $quantity;

        }


        cart_json(
            true,
            "Cart loaded successfully.",
            [

                "cart_id" =>
                    $cart_id,

                "cart" =>
                    $items,

                "items" =>
                    $items,

                "total_items" =>
                    $total_items,

                "total_amount" =>
                    round(
                        $total_amount,
                        2
                    )

            ]
        );

    }


    /* =====================================================
       ADD TO CART
    ===================================================== */

    if (
        $action === "add"
    ) {

        $product_id =
            (int)
            (
                $_POST["product_id"]
                ?? 0
            );


        $quantity =
            (int)
            (
                $_POST["quantity"]
                ?? 1
            );


        if (
            $product_id <= 0
        ) {

            cart_json(
                false,
                "Invalid product."
            );

        }


        if (
            $quantity <= 0
        ) {

            cart_json(
                false,
                "Invalid quantity."
            );

        }


        /* -------------------------------------------------
           PRODUCT
        ------------------------------------------------- */

        $product_stmt =
            $conn->prepare(
                "
                SELECT

                    id,

                    product_name,

                    price,

                    stock

                FROM products

                WHERE id = ?

                LIMIT 1
                "
            );


        if (
            !$product_stmt
        ) {

            throw new RuntimeException(
                "Unable to prepare product lookup: " .
                $conn->error
            );

        }


        $product_stmt->bind_param(
            "i",
            $product_id
        );


        if (
            !$product_stmt->execute()
        ) {

            $error =
                $product_stmt->error;

            $product_stmt->close();

            throw new RuntimeException(
                "Unable to check product: " .
                $error
            );

        }


        $product_result =
            $product_stmt->get_result();


        if (
            !$product_result ||
            $product_result->num_rows === 0
        ) {

            $product_stmt->close();

            cart_json(
                false,
                "Product not found."
            );

        }


        $product =
            $product_result->fetch_assoc();


        $product_stmt->close();


        $stock =
            (int)
            $product["stock"];


        if (
            $stock <= 0
        ) {

            cart_json(
                false,
                "This product is out of stock."
            );

        }


        /* -------------------------------------------------
           EXISTING CART ITEM
        ------------------------------------------------- */

        $existing_stmt =
            $conn->prepare(
                "
                SELECT

                    id,

                    quantity

                FROM cart_items

                WHERE cart_id = ?

                  AND product_id = ?

                LIMIT 1
                "
            );


        if (
            !$existing_stmt
        ) {

            throw new RuntimeException(
                "Unable to prepare cart item lookup: " .
                $conn->error
            );

        }


        $existing_stmt->bind_param(
            "ii",
            $cart_id,
            $product_id
        );


        if (
            !$existing_stmt->execute()
        ) {

            $error =
                $existing_stmt->error;

            $existing_stmt->close();

            throw new RuntimeException(
                "Unable to check cart item: " .
                $error
            );

        }


        $existing_result =
            $existing_stmt->get_result();


        $existing_id =
            0;


        $existing_quantity =
            0;


        if (
            $existing_result &&
            $existing_result->num_rows > 0
        ) {

            $existing =
                $existing_result->fetch_assoc();


            $existing_id =
                (int)
                $existing["id"];


            $existing_quantity =
                (int)
                $existing["quantity"];

        }


        $existing_stmt->close();


        $new_quantity =
            $existing_quantity +
            $quantity;


        if (
            $new_quantity > $stock
        ) {

            cart_json(
                false,
                "Only " .
                $stock .
                " unit(s) are available."
            );

        }


        /* -------------------------------------------------
           UPDATE EXISTING ITEM
        ------------------------------------------------- */

        if (
            $existing_id > 0
        ) {

            $update_stmt =
                $conn->prepare(
                    "
                    UPDATE cart_items

                    SET quantity = ?

                    WHERE id = ?

                      AND cart_id = ?
                    "
                );


            if (
                !$update_stmt
            ) {

                throw new RuntimeException(
                    "Unable to prepare cart update: " .
                    $conn->error
                );

            }


            $update_stmt->bind_param(
                "iii",
                $new_quantity,
                $existing_id,
                $cart_id
            );


            if (
                !$update_stmt->execute()
            ) {

                $error =
                    $update_stmt->error;

                $update_stmt->close();

                throw new RuntimeException(
                    "Unable to update cart: " .
                    $error
                );

            }


            $update_stmt->close();


        }

        /* -------------------------------------------------
           INSERT NEW ITEM
        ------------------------------------------------- */

        else {

            $insert_stmt =
                $conn->prepare(
                    "
                    INSERT INTO cart_items
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
                    )
                    "
                );


            if (
                !$insert_stmt
            ) {

                throw new RuntimeException(
                    "Unable to prepare cart insertion: " .
                    $conn->error
                );

            }


            $insert_stmt->bind_param(
                "iii",
                $cart_id,
                $product_id,
                $quantity
            );


            if (
                !$insert_stmt->execute()
            ) {

                $error =
                    $insert_stmt->error;

                $insert_stmt->close();

                throw new RuntimeException(
                    "Unable to add item to cart: " .
                    $error
                );

            }


            $insert_stmt->close();

        }


        cart_json(
            true,
            $product["product_name"] .
            " has been added to your cart.",
            [

                "cart_id" =>
                    $cart_id,

                "product_id" =>
                    $product_id

            ]
        );

    }


    /* =====================================================
       UPDATE CART QUANTITY
    ===================================================== */

    if (
        $action === "update"
    ) {

        $product_id =
            (int)
            (
                $_POST["product_id"]
                ?? 0
            );


        $quantity =
            (int)
            (
                $_POST["quantity"]
                ?? 0
            );


        if (
            $product_id <= 0
        ) {

            cart_json(
                false,
                "Invalid product."
            );

        }


        /* -------------------------------------------------
           QUANTITY ZERO = REMOVE
        ------------------------------------------------- */

        if (
            $quantity <= 0
        ) {

            $remove_stmt =
                $conn->prepare(
                    "
                    DELETE FROM cart_items

                    WHERE cart_id = ?

                      AND product_id = ?
                    "
                );


            if (
                !$remove_stmt
            ) {

                throw new RuntimeException(
                    "Unable to prepare item removal: " .
                    $conn->error
                );

            }


            $remove_stmt->bind_param(
                "ii",
                $cart_id,
                $product_id
            );


            if (
                !$remove_stmt->execute()
            ) {

                $error =
                    $remove_stmt->error;

                $remove_stmt->close();

                throw new RuntimeException(
                    "Unable to remove item: " .
                    $error
                );

            }


            $remove_stmt->close();


            cart_json(
                true,
                "Item removed from your cart."
            );

        }


        /* -------------------------------------------------
           STOCK CHECK
        ------------------------------------------------- */

        $stock_stmt =
            $conn->prepare(
                "
                SELECT
                    stock

                FROM products

                WHERE id = ?

                LIMIT 1
                "
            );


        if (
            !$stock_stmt
        ) {

            throw new RuntimeException(
                "Unable to prepare stock lookup: " .
                $conn->error
            );

        }


        $stock_stmt->bind_param(
            "i",
            $product_id
        );


        if (
            !$stock_stmt->execute()
        ) {

            $error =
                $stock_stmt->error;

            $stock_stmt->close();

            throw new RuntimeException(
                "Unable to check stock: " .
                $error
            );

        }


        $stock_result =
            $stock_stmt->get_result();


        if (
            !$stock_result ||
            $stock_result->num_rows === 0
        ) {

            $stock_stmt->close();

            cart_json(
                false,
                "Product not found."
            );

        }


        $stock_row =
            $stock_result->fetch_assoc();


        $stock_stmt->close();


        $stock =
            (int)
            $stock_row["stock"];


        if (
            $quantity > $stock
        ) {

            cart_json(
                false,
                "Only " .
                $stock .
                " unit(s) are available."
            );

        }


        /* -------------------------------------------------
           UPDATE
        ------------------------------------------------- */

        $update_stmt =
            $conn->prepare(
                "
                UPDATE cart_items

                SET quantity = ?

                WHERE cart_id = ?

                  AND product_id = ?
                "
            );


        if (
            !$update_stmt
        ) {

            throw new RuntimeException(
                "Unable to prepare quantity update: " .
                $conn->error
            );

        }


        $update_stmt->bind_param(
            "iii",
            $quantity,
            $cart_id,
            $product_id
        );


        if (
            !$update_stmt->execute()
        ) {

            $error =
                $update_stmt->error;

            $update_stmt->close();

            throw new RuntimeException(
                "Unable to update quantity: " .
                $error
            );

        }


        if (
            $update_stmt->affected_rows === 0
        ) {

            $update_stmt->close();

            cart_json(
                false,
                "Cart item not found."
            );

        }


        $update_stmt->close();


        cart_json(
            true,
            "Cart quantity updated."
        );

    }


    /* =====================================================
       REMOVE FROM CART
    ===================================================== */

    if (
        $action === "remove"
    ) {

        $product_id =
            (int)
            (
                $_POST["product_id"]
                ?? 0
            );


        if (
            $product_id <= 0
        ) {

            cart_json(
                false,
                "Invalid product."
            );

        }


        $remove_stmt =
            $conn->prepare(
                "
                DELETE FROM cart_items

                WHERE cart_id = ?

                  AND product_id = ?
                "
            );


        if (
            !$remove_stmt
        ) {

            throw new RuntimeException(
                "Unable to prepare item removal: " .
                $conn->error
            );

        }


        $remove_stmt->bind_param(
            "ii",
            $cart_id,
            $product_id
        );


        if (
            !$remove_stmt->execute()
        ) {

            $error =
                $remove_stmt->error;

            $remove_stmt->close();

            throw new RuntimeException(
                "Unable to remove item: " .
                $error
            );

        }


        if (
            $remove_stmt->affected_rows === 0
        ) {

            $remove_stmt->close();

            cart_json(
                false,
                "Cart item not found."
            );

        }


        $remove_stmt->close();


        cart_json(
            true,
            "Item removed from your cart."
        );

    }


    /* =====================================================
       CLEAR CART
    ===================================================== */

    if (
        $action === "clear"
    ) {

        $clear_stmt =
            $conn->prepare(
                "
                DELETE FROM cart_items

                WHERE cart_id = ?
                "
            );


        if (
            !$clear_stmt
        ) {

            throw new RuntimeException(
                "Unable to prepare cart clear: " .
                $conn->error
            );

        }


        $clear_stmt->bind_param(
            "i",
            $cart_id
        );


        if (
            !$clear_stmt->execute()
        ) {

            $error =
                $clear_stmt->error;

            $clear_stmt->close();

            throw new RuntimeException(
                "Unable to clear cart: " .
                $error
            );

        }


        $clear_stmt->close();


        cart_json(
            true,
            "Your cart has been cleared."
        );

    }


    /* =====================================================
       UNKNOWN ACTION
    ===================================================== */

    cart_json(
        false,
        "Unknown cart action."
    );


} catch (Throwable $e) {


    error_log(
        "SIQUIJOR STYLES cart_process.php ERROR: " .
        $e->getMessage() .
        " in " .
        $e->getFile() .
        ":" .
        $e->getLine()
    );


    cart_json(
        false,
        "Cart server error: " .
        $e->getMessage()
    );

}