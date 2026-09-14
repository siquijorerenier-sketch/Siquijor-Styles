```php
<?php

session_start();

require_once __DIR__ . "/database.php";

header("Content-Type: application/json; charset=UTF-8");

/* =========================================================
   JSON RESPONSE
========================================================= */

function json_response(
    bool $success,
    string $message,
    array $extra = []
): void {

    http_response_code(
        $success ? 200 : 400
    );

    echo json_encode(
        array_merge(
            [
                "success" => $success,
                "message" => $message
            ],
            $extra
        ),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

/* =========================================================
   REQUEST CHECK
========================================================= */

if (
    ($_SERVER["REQUEST_METHOD"] ?? "") !== "POST"
) {

    json_response(
        false,
        "Invalid request."
    );
}

/* =========================================================
   LOGIN CHECK
========================================================= */

if (
    empty($_SESSION["logged_in"]) ||
    empty($_SESSION["user_id"])
) {

    json_response(
        false,
        "You must be logged in to place an order."
    );
}

$user_id = (int) $_SESSION["user_id"];

/* =========================================================
   DATABASE CHECK
========================================================= */

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {

    json_response(
        false,
        "Database connection is not available."
    );
}

/* =========================================================
   GET FORM DATA
========================================================= */

$full_name = trim(
    $_POST["full_name"] ?? ""
);

$email = trim(
    $_POST["email"] ?? ""
);

$phone = trim(
    $_POST["phone"] ?? ""
);

$address = trim(
    $_POST["address"] ?? ""
);

$payment_method = trim(
    $_POST["payment_method"] ?? "Cash on Delivery"
);

$payment_platform = trim(
    $_POST["payment_platform"] ?? ""
);

/* =========================================================
   BASIC VALIDATION
========================================================= */

if (
    $full_name === "" ||
    $email === "" ||
    $phone === "" ||
    $address === ""
) {

    json_response(
        false,
        "Please complete all delivery information."
    );
}

if (
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {

    json_response(
        false,
        "Please enter a valid email address."
    );
}

if (
    strlen($full_name) > 100
) {

    json_response(
        false,
        "Your name is too long."
    );
}

if (
    strlen($email) > 150
) {

    json_response(
        false,
        "Your email address is too long."
    );
}

if (
    strlen($phone) > 30
) {

    json_response(
        false,
        "Your phone number is too long."
    );
}

if (
    strlen($address) > 500
) {

    json_response(
        false,
        "Your delivery address is too long."
    );
}

/* =========================================================
   PAYMENT METHOD VALIDATION
========================================================= */

$allowed_payment_methods = [
    "Cash on Delivery",
    "Online Payment"
];

if (
    !in_array(
        $payment_method,
        $allowed_payment_methods,
        true
    )
) {

    json_response(
        false,
        "Invalid payment method."
    );
}

/* =========================================================
   PAYMENT PLATFORM
========================================================= */

$allowed_platforms = [
    "GCash",
    "Maya",
    "BDO",
    "BPI",
    "UnionBank"
];

/*
 * Only Online Payment needs a platform.
 */

if (
    $payment_method === "Online Payment"
) {

    if (
        !in_array(
            $payment_platform,
            $allowed_platforms,
            true
        )
    ) {

        json_response(
            false,
            "Please select a valid online payment platform."
        );
    }
}

/*
 * COD does not need a payment platform.
 */

if (
    $payment_method === "Cash on Delivery"
) {

    $payment_platform = "";
}

/* =========================================================
   PAYMENT STATUS
========================================================= */

$payment_status = "Pending";

/*
 * Both COD and Online Payment start as Pending.
 *
 * COD:
 *     Payment is collected when delivered.
 *
 * Online:
 *     Receipt is waiting for admin verification.
 */

/* =========================================================
   RECEIPT
========================================================= */

$receipt_relative_path = null;

if (
    $payment_method === "Online Payment"
) {

    if (
        !isset($_FILES["payment_receipt"])
    ) {

        json_response(
            false,
            "Please upload your payment receipt."
        );
    }

    $receipt = $_FILES["payment_receipt"];

    if (
        !isset($receipt["error"]) ||
        $receipt["error"] !== UPLOAD_ERR_OK
    ) {

        json_response(
            false,
            "There was a problem uploading your payment receipt."
        );
    }

    /* -----------------------------------------------------
       CHECK FILE SIZE
    ----------------------------------------------------- */

    if (
        $receipt["size"] > 5 * 1024 * 1024
    ) {

        json_response(
            false,
            "Payment receipt must not exceed 5 MB."
        );
    }

    /* -----------------------------------------------------
       CHECK TEMPORARY FILE
    ----------------------------------------------------- */

    if (
        !is_uploaded_file(
            $receipt["tmp_name"]
        )
    ) {

        json_response(
            false,
            "Invalid payment receipt upload."
        );
    }

    /* -----------------------------------------------------
       MIME TYPE
    ----------------------------------------------------- */

    $allowed_mime_types = [

        "image/jpeg" => "jpg",

        "image/png" => "png",

        "image/webp" => "webp",

        "application/pdf" => "pdf"
    ];

    /*
     * Check whether finfo is available.
     */

    if (
        !class_exists("finfo")
    ) {

        json_response(
            false,
            "File upload validation is not available on this server."
        );
    }

    $finfo = new finfo(
        FILEINFO_MIME_TYPE
    );

    $mime_type = $finfo->file(
        $receipt["tmp_name"]
    );

    if (
        !isset(
            $allowed_mime_types[$mime_type]
        )
    ) {

        json_response(
            false,
            "Invalid receipt format. Please upload JPG, PNG, WEBP, or PDF."
        );
    }

    /* -----------------------------------------------------
       UPLOAD DIRECTORY
    ----------------------------------------------------- */

    $upload_directory =
        __DIR__ .
        "/uploads/receipts/";

    if (
        !is_dir(
            $upload_directory
        )
    ) {

        if (
            !mkdir(
                $upload_directory,
                0755,
                true
            )
        ) {

            json_response(
                false,
                "Unable to create the receipt upload directory."
            );
        }
    }

    /* -----------------------------------------------------
       CHECK DIRECTORY WRITABLE
    ----------------------------------------------------- */

    if (
        !is_writable(
            $upload_directory
        )
    ) {

        json_response(
            false,
            "The receipt upload directory is not writable."
        );
    }

    /* -----------------------------------------------------
       FILE NAME
    ----------------------------------------------------- */

    $extension =
        $allowed_mime_types[$mime_type];

    try {

        $random_part =
            bin2hex(
                random_bytes(5)
            );

    } catch (Throwable $e) {

        $random_part =
            uniqid();
    }

    $file_name =
        "receipt_" .
        $user_id .
        "_" .
        date("YmdHis") .
        "_" .
        $random_part .
        "." .
        $extension;

    $destination =
        $upload_directory .
        $file_name;

    /* -----------------------------------------------------
       MOVE FILE
    ----------------------------------------------------- */

    if (
        !move_uploaded_file(
            $receipt["tmp_name"],
            $destination
        )
    ) {

        json_response(
            false,
            "Unable to save the payment receipt."
        );
    }

    /*
     * This path is stored in the database notes.
     */

    $receipt_relative_path =
        "php/uploads/receipts/" .
        $file_name;
}

/* =========================================================
   VERIFY USER
========================================================= */

$user_stmt = $conn->prepare(
    "
    SELECT
        id
    FROM users
    WHERE id = ?
    LIMIT 1
    "
);

if (!$user_stmt) {

    if ($receipt_relative_path) {

        @unlink(
            __DIR__ .
            "/uploads/receipts/" .
            basename(
                $receipt_relative_path
            )
        );
    }

    json_response(
        false,
        "Unable to verify your account."
    );
}

$user_stmt->bind_param(
    "i",
    $user_id
);

if (
    !$user_stmt->execute()
) {

    $user_stmt->close();

    if ($receipt_relative_path) {

        @unlink(
            __DIR__ .
            "/uploads/receipts/" .
            basename(
                $receipt_relative_path
            )
        );
    }

    json_response(
        false,
        "Unable to verify your account."
    );
}

$user_result =
    $user_stmt->get_result();

if (
    !$user_result ||
    $user_result->num_rows === 0
) {

    $user_stmt->close();

    if ($receipt_relative_path) {

        @unlink(
            __DIR__ .
            "/uploads/receipts/" .
            basename(
                $receipt_relative_path
            )
        );
    }

    json_response(
        false,
        "Your account could not be found."
    );
}

$user_stmt->close();

/* =========================================================
   LOAD CART
========================================================= */

$cart_stmt = $conn->prepare(
    "
    SELECT
        ci.id AS cart_item_id,
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

    ORDER BY ci.id ASC
    "
);

if (!$cart_stmt) {

    if ($receipt_relative_path) {

        @unlink(
            __DIR__ .
            "/uploads/receipts/" .
            basename(
                $receipt_relative_path
            )
        );
    }

    json_response(
        false,
        "Unable to load your cart."
    );
}

$cart_stmt->bind_param(
    "i",
    $user_id
);

if (
    !$cart_stmt->execute()
) {

    $cart_stmt->close();

    if ($receipt_relative_path) {

        @unlink(
            __DIR__ .
            "/uploads/receipts/" .
            basename(
                $receipt_relative_path
            )
        );
    }

    json_response(
        false,
        "Unable to load your cart."
    );
}

$cart_result =
    $cart_stmt->get_result();

$cart_items = [];

$total_amount = 0.00;

/* =========================================================
   READ CART
========================================================= */

while (
    $row =
    $cart_result->fetch_assoc()
) {

    $product_id =
        (int) $row["product_id"];

    $quantity =
        (int) $row["quantity"];

    $price =
        (float) $row["price"];

    $stock =
        (int) $row["stock"];

    /* -----------------------------------------------------
       INVALID QUANTITY
    ----------------------------------------------------- */

    if (
        $quantity <= 0
    ) {

        $cart_stmt->close();

        if ($receipt_relative_path) {

            @unlink(
                __DIR__ .
                "/uploads/receipts/" .
                basename(
                    $receipt_relative_path
                )
            );
        }

        json_response(
            false,
            "Your cart contains an invalid quantity."
        );
    }

    /* -----------------------------------------------------
       INVALID STOCK
    ----------------------------------------------------- */

    if (
        $stock < 0
    ) {

        $cart_stmt->close();

        if ($receipt_relative_path) {

            @unlink(
                __DIR__ .
                "/uploads/receipts/" .
                basename(
                    $receipt_relative_path
                )
            );
        }

        json_response(
            false,
            "Invalid stock information for one of your products."
        );
    }

    /* -----------------------------------------------------
       CALCULATE SUBTOTAL
    ----------------------------------------------------- */

    $subtotal =
        round(
            $price * $quantity,
            2
        );

    $total_amount +=
        $subtotal;

    $cart_items[] = [

        "cart_item_id" =>
            (int) $row["cart_item_id"],

        "product_id" =>
            $product_id,

        "quantity" =>
            $quantity,

        "product_name" =>
            $row["product_name"],

        "price" =>
            $price,

        "stock" =>
            $stock,

        "subtotal" =>
            $subtotal
    ];
}

$cart_stmt->close();

$total_amount =
    round(
        $total_amount,
        2
    );

/* =========================================================
   EMPTY CART
========================================================= */

if (
    empty($cart_items)
) {

    if ($receipt_relative_path) {

        @unlink(
            __DIR__ .
            "/uploads/receipts/" .
            basename(
                $receipt_relative_path
            )
        );
    }

    json_response(
        false,
        "Your cart is empty."
    );
}

/* =========================================================
   BUILD ORDER NOTES
========================================================= */

$notes_parts = [];

/*
 * Always save payment method.
 */

$notes_parts[] =
    "Payment Method: " .
    $payment_method;

/*
 * Online payment information.
 */

if (
    $payment_method === "Online Payment"
) {

    $notes_parts[] =
        "Payment Platform: " .
        $payment_platform;

    if (
        $receipt_relative_path
    ) {

        $notes_parts[] =
            "Payment Receipt: " .
            $receipt_relative_path;
    }
}

/*
 * COD information.
 */

if (
    $payment_method === "Cash on Delivery"
) {

    $notes_parts[] =
        "Payment Platform: None";

    $notes_parts[] =
        "Payment will be collected upon delivery.";
}

$order_notes =
    implode(
        "\n",
        $notes_parts
    );

/* =========================================================
   TRANSACTION
========================================================= */

$transaction_started = false;

try {

    $conn->begin_transaction();

    $transaction_started = true;

    /* =====================================================
       LOCK AND VERIFY PRODUCTS
    ===================================================== */

    foreach (
        $cart_items as $item
    ) {

        $product_id =
            (int) $item["product_id"];

        $quantity =
            (int) $item["quantity"];

        $stock_stmt =
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

                FOR UPDATE
                "
            );

        if (!$stock_stmt) {

            throw new Exception(
                "Unable to check product stock."
            );
        }

        $stock_stmt->bind_param(
            "i",
            $product_id
        );

        if (
            !$stock_stmt->execute()
        ) {

            $stock_stmt->close();

            throw new Exception(
                "Unable to check product stock."
            );
        }

        $stock_result =
            $stock_stmt->get_result();

        if (
            !$stock_result ||
            $stock_result->num_rows === 0
        ) {

            $stock_stmt->close();

            throw new Exception(
                "One of the products in your cart is no longer available."
            );
        }

        $product =
            $stock_result->fetch_assoc();

        $stock_stmt->close();

        $current_stock =
            (int) $product["stock"];

        if (
            $current_stock < $quantity
        ) {

            throw new Exception(
                "Not enough stock for " .
                $product["product_name"] .
                ". Only " .
                $current_stock .
                " item(s) available."
            );
        }
    }

    /* =====================================================
       CREATE ORDER
    ===================================================== */

    $status = "Pending";

    /*
     * IMPORTANT:
     *
     * This version explicitly saves:
     *
     * payment_method
     * payment_status
     *
     * This is important because your orders.php
     * already reads these columns.
     */

    $order_stmt =
        $conn->prepare(
            "
            INSERT INTO orders
            (
                user_id,
                total_amount,
                status,
                shipping_name,
                shipping_email,
                shipping_phone,
                shipping_address,
                notes,
                payment_method,
                payment_status,
                created_at
            )

            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                NOW()
            )
            "
        );

    if (!$order_stmt) {

        throw new Exception(
            "Unable to create your order: " .
            $conn->error
        );
    }

    /*
     * 11 values:
     *
     * i = user_id
     * d = total_amount
     * s = status
     * s = shipping_name
     * s = shipping_email
     * s = shipping_phone
     * s = shipping_address
     * s = notes
     * s = payment_method
     * s = payment_status
     */

    $order_stmt->bind_param(
        "idssssssss",
        $user_id,
        $total_amount,
        $status,
        $full_name,
        $email,
        $phone,
        $address,
        $order_notes,
        $payment_method,
        $payment_status
    );

    if (
        !$order_stmt->execute()
    ) {

        $error =
            $order_stmt->error;

        $order_stmt->close();

        throw new Exception(
            "Unable to create your order: " .
            $error
        );
    }

    $order_id =
        (int) $conn->insert_id;

    $order_stmt->close();

    if (
        $order_id <= 0
    ) {

        throw new Exception(
            "The order could not be created."
        );
    }

    /* =====================================================
       CREATE ORDER ITEMS
    ===================================================== */

    foreach (
        $cart_items as $item
    ) {

        $product_id =
            (int) $item["product_id"];

        $product_name =
            $item["product_name"];

        $price =
            (float) $item["price"];

        $quantity =
            (int) $item["quantity"];

        $subtotal =
            (float) $item["subtotal"];

        /* -------------------------------------------------
           INSERT ORDER ITEM
        ------------------------------------------------- */

        $item_stmt =
            $conn->prepare(
                "
                INSERT INTO order_items
                (
                    order_id,
                    product_id,
                    product_name,
                    price,
                    quantity,
                    subtotal
                )

                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
                "
            );

        if (!$item_stmt) {

            throw new Exception(
                "Unable to create order items: " .
                $conn->error
            );
        }

        $item_stmt->bind_param(
            "iisdid",
            $order_id,
            $product_id,
            $product_name,
            $price,
            $quantity,
            $subtotal
        );

        if (
            !$item_stmt->execute()
        ) {

            $error =
                $item_stmt->error;

            $item_stmt->close();

            throw new Exception(
                "Unable to save an order item: " .
                $error
            );
        }

        $item_stmt->close();

        /* -------------------------------------------------
           REDUCE STOCK
        ------------------------------------------------- */

        $update_stock_stmt =
            $conn->prepare(
                "
                UPDATE products

                SET stock = stock - ?

                WHERE id = ?

                AND stock >= ?
                "
            );

        if (!$update_stock_stmt) {

            throw new Exception(
                "Unable to update product stock."
            );
        }

        $update_stock_stmt->bind_param(
            "iii",
            $quantity,
            $product_id,
            $quantity
        );

        if (
            !$update_stock_stmt->execute()
        ) {

            $error =
                $update_stock_stmt->error;

            $update_stock_stmt->close();

            throw new Exception(
                "Unable to update product stock: " .
                $error
            );
        }

        if (
            $update_stock_stmt->affected_rows !== 1
        ) {

            $update_stock_stmt->close();

            throw new Exception(
                "Unable to update stock for " .
                $product_name .
                "."
            );
        }

        $update_stock_stmt->close();
    }

    /* =====================================================
       FIND CART
    ===================================================== */

    $cart_id_stmt =
        $conn->prepare(
            "
            SELECT
                id

            FROM cart

            WHERE user_id = ?

            LIMIT 1

            FOR UPDATE
            "
        );

    if (!$cart_id_stmt) {

        throw new Exception(
            "Unable to access your cart."
        );
    }

    $cart_id_stmt->bind_param(
        "i",
        $user_id
    );

    if (
        !$cart_id_stmt->execute()
    ) {

        $cart_id_stmt->close();

        throw new Exception(
            "Unable to access your cart."
        );
    }

    $cart_id_result =
        $cart_id_stmt->get_result();

    $cart_id = null;

    if (
        $cart_id_result &&
        $cart_id_result->num_rows > 0
    ) {

        $cart_row =
            $cart_id_result->fetch_assoc();

        $cart_id =
            (int) $cart_row["id"];
    }

    $cart_id_stmt->close();

    /* =====================================================
       CLEAR CART
    ===================================================== */

    if (
        $cart_id !== null
    ) {

        $clear_cart_stmt =
            $conn->prepare(
                "
                DELETE FROM cart_items

                WHERE cart_id = ?
                "
            );

        if (!$clear_cart_stmt) {

            throw new Exception(
                "Unable to clear your cart."
            );
        }

        $clear_cart_stmt->bind_param(
            "i",
            $cart_id
        );

        if (
            !$clear_cart_stmt->execute()
        ) {

            $error =
                $clear_cart_stmt->error;

            $clear_cart_stmt->close();

            throw new Exception(
                "Unable to clear your cart: " .
                $error
            );
        }

        $clear_cart_stmt->close();
    }

    /* =====================================================
       COMMIT
    ===================================================== */

    $conn->commit();

    $transaction_started = false;

    /* =====================================================
       SUCCESS
    ===================================================== */

    json_response(
        true,
        "Your order has been placed successfully!",
        [
            "order_id" =>
                $order_id,

            "total_amount" =>
                $total_amount,

            "status" =>
                $status,

            "payment_method" =>
                $payment_method,

            "payment_status" =>
                $payment_status
        ]
    );

} catch (Throwable $e) {

    /* =====================================================
       ROLLBACK
    ===================================================== */

    if (
        $transaction_started
    ) {

        try {

            $conn->rollback();

        } catch (Throwable $rollback_error) {

            error_log(
                "Rollback error: " .
                $rollback_error->getMessage()
            );
        }
    }

    /* =====================================================
       REMOVE RECEIPT IF ORDER FAILED
    ===================================================== */

    if (
        $receipt_relative_path
    ) {

        $receipt_file =
            __DIR__ .
            "/uploads/receipts/" .
            basename(
                $receipt_relative_path
            );

        if (
            is_file(
                $receipt_file
            )
        ) {

            @unlink(
                $receipt_file
            );
        }
    }

    /* =====================================================
       LOG ERROR
    ===================================================== */

    error_log(
        "Checkout error for user " .
        $user_id .
        ": " .
        $e->getMessage()
    );

    /* =====================================================
       SEND ERROR
    ===================================================== */

    json_response(
        false,
        $e->getMessage()
    );
}

?>
```
