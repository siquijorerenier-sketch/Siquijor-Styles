<?php
session_start();

require_once __DIR__ . "/php/database.php";

/* =========================================================
   CHECK LOGIN
========================================================= */

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true ||
    !isset($_SESSION["user_id"])
) {
    header("Location: login.php?return=checkout.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];


/* =========================================================
   GET USER
========================================================= */

$user = [];

$stmt = $conn->prepare(
    "SELECT * FROM users WHERE id = ? LIMIT 1"
);

if ($stmt) {

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }

    $stmt->close();
}


$user_name = "";

if (isset($user["full_name"])) {
    $user_name = $user["full_name"];
} elseif (isset($user["name"])) {
    $user_name = $user["name"];
} elseif (isset($user["username"])) {
    $user_name = $user["username"];
}

$user_email = $user["email"] ?? "";


/* =========================================================
   GET CART
========================================================= */

$cart_items = [];
$cart_total = 0;
$cart_count = 0;

$sql = "
    SELECT
        ci.id AS cart_item_id,
        ci.product_id,
        ci.quantity,
        p.product_name,
        p.description,
        p.price,
        p.image,
        p.stock
    FROM cart_items ci
    INNER JOIN cart c
        ON ci.cart_id = c.id
    INNER JOIN products p
        ON ci.product_id = p.id
    WHERE c.user_id = ?
    ORDER BY ci.id DESC
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $row["quantity"] = (int) $row["quantity"];
        $row["price"] = (float) $row["price"];

        $row["item_total"] =
            $row["price"] * $row["quantity"];

        $cart_items[] = $row;

        $cart_count += $row["quantity"];
        $cart_total += $row["item_total"];
    }

    $stmt->close();
}


/* =========================================================
   ESCAPE
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

    <title>Checkout | Siquijor Styles</title>


    <!-- FONTS -->

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


    <!-- ICONS -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >


    <!-- MAIN CSS -->

    <link
        rel="stylesheet"
        href="css/style.css"
    >

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

    </nav>


    <div class="header-tools">

        <button
            type="button"
            class="header-icon"
            onclick="openSearch()"
            aria-label="Search"
        >
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>


        <a
            href="cart.php"
            class="header-icon cart-button"
            aria-label="Cart"
        >

            <i class="fa-solid fa-bag-shopping"></i>

            <span id="cartCount">
                <?= $cart_count ?>
            </span>

        </a>


        <a
            href="orders.php"
            class="header-icon"
            aria-label="Orders"
        >
            <i class="fa-solid fa-receipt"></i>
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
     CHECKOUT
========================================================= -->

<main>

<section class="checkout-page">


    <!-- PAGE HEADING -->

    <div class="checkout-heading">

        <p class="eyebrow">
            SIQUIJOR STYLES
        </p>

        <h1>
            Complete Your Order
        </h1>

        <p>
            Review your items and enter your delivery details below.
        </p>

    </div>


<?php if (empty($cart_items)): ?>


    <!-- =====================================================
         EMPTY CART
    ====================================================== -->

    <div class="checkout-empty">

        <div class="checkout-empty-icon">
            <i class="fa-solid fa-bag-shopping"></i>
        </div>

        <h2>
            Your Cart Is Empty
        </h2>

        <p>
            Add some island-inspired pieces before checking out.
        </p>

        <a
            href="shop.php"
            class="primary-button"
        >
            CONTINUE SHOPPING
        </a>

    </div>


<?php else: ?>


    <!-- =====================================================
         CHECKOUT GRID
    ====================================================== -->

    <div class="checkout-container">


        <!-- =================================================
             ORDER SUMMARY
        ================================================== -->

        <section class="checkout-summary">

            <div class="checkout-box-heading">

                <div>

                    <p class="checkout-label">
                        ORDER SUMMARY
                    </p>

                    <h2>
                        Your Order
                    </h2>

                </div>

                <span class="checkout-count">
                    <?= $cart_count ?>
                    <?= $cart_count === 1 ? "item" : "items" ?>
                </span>

            </div>


            <div
                id="checkoutItems"
                class="checkout-items"
            >

                <?php foreach ($cart_items as $item): ?>

                    <div class="checkout-item">


                        <!-- PRODUCT IMAGE -->

                        <div class="checkout-product-image">

                            <?php if (!empty($item["image"])): ?>

                                <img
                                    src="images/<?= e($item["image"]) ?>"
                                    alt="<?= e($item["product_name"]) ?>"
                                    onerror="this.style.display='none';"
                                >

                            <?php endif; ?>

                        </div>


                        <!-- PRODUCT DETAILS -->

                        <div class="checkout-product-info">

                            <h3>
                                <?= e($item["product_name"]) ?>
                            </h3>

                            <p class="checkout-price">
                                ₱<?= number_format($item["price"], 2) ?>
                            </p>

                            <p class="checkout-quantity">
                                Quantity:
                                <strong>
                                    <?= $item["quantity"] ?>
                                </strong>
                            </p>

                        </div>


                        <!-- ITEM TOTAL -->

                        <div class="checkout-item-total">

                            ₱<?= number_format($item["item_total"], 2) ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>


            <!-- TOTAL -->

            <div class="checkout-total">

                <span>
                    Total
                </span>

                <strong>
                    ₱<?= number_format($cart_total, 2) ?>
                </strong>

            </div>

        </section>



        <!-- =================================================
             DELIVERY DETAILS
        ================================================== -->

        <section class="checkout-form-box">

            <div class="checkout-box-heading">

                <div>

                    <p class="checkout-label">
                        DELIVERY
                    </p>

                    <h2>
                        Delivery Details
                    </h2>

                </div>

                <i class="fa-solid fa-location-dot checkout-heading-icon"></i>

            </div>


            <form
                id="checkoutForm"
                onsubmit="submitOrder(event)"
            >


                <!-- FULL NAME -->

                <div class="checkout-field">

                    <label for="full_name">
                        Full Name
                    </label>

                    <div class="checkout-input-wrap">

                        <i class="fa-regular fa-user"></i>

                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            value="<?= e($user_name) ?>"
                            placeholder="Your full name"
                            required
                        >

                    </div>

                </div>


                <!-- EMAIL -->

                <div class="checkout-field">

                    <label for="email">
                        Email Address
                    </label>

                    <div class="checkout-input-wrap">

                        <i class="fa-regular fa-envelope"></i>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= e($user_email) ?>"
                            placeholder="you@example.com"
                            required
                        >

                    </div>

                </div>


                <!-- PHONE -->

                <div class="checkout-field">

                    <label for="phone">
                        Phone Number
                    </label>

                    <div class="checkout-input-wrap">

                        <i class="fa-solid fa-phone"></i>

                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            placeholder="09XXXXXXXXX"
                            required
                        >

                    </div>

                </div>


                <!-- ADDRESS -->

                <div class="checkout-field">

                    <label for="address">
                        Delivery Address
                    </label>

                    <div class="checkout-input-wrap textarea-wrap">

                        <i class="fa-solid fa-location-dot"></i>

                        <textarea
                            id="address"
                            name="address"
                            placeholder="House number, street, barangay, municipality, province..."
                            required
                        ></textarea>

                    </div>

                </div>


                <!-- NOTE -->

                <div class="checkout-note">

                    <i class="fa-solid fa-shield-heart"></i>

                    <span>
                        Please make sure your delivery information is correct before placing your order.
                    </span>

                </div>


                <!-- BUTTON -->

                <button
                    type="submit"
                    class="checkout-place-button"
                    id="placeOrderButton"
                >

                    <span>
                        PLACE ORDER
                    </span>

                    <i class="fa-solid fa-arrow-right"></i>

                </button>


                <a
                    href="cart.php"
                    class="back-to-cart"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to Cart
                </a>

            </form>

        </section>

    </div>

<?php endif; ?>

</section>

</main>



<!-- =========================================================
     SEARCH MODAL
========================================================= -->

<div
    class="modal"
    id="searchModal"
>

    <div class="modal-box search-box">

        <button
            type="button"
            class="close-modal"
            onclick="closeModal('searchModal')"
        >
            ×
        </button>

        <h2>
            Search
        </h2>

        <p>
            Find something you love.
        </p>

        <input
            type="text"
            id="searchInput"
            placeholder="Search products..."
            oninput="searchProducts()"
        >

        <div id="searchResults"></div>

    </div>

</div>



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

        <a href="shop.php">
            Tops
        </a>

        <a href="shop.php">
            Dresses
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

        <a href="cart.php">
            Cart
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



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

async function submitOrder(event) {

    event.preventDefault();

    const form =
        document.getElementById("checkoutForm");

    const button =
        document.getElementById("placeOrderButton");


    if (!form || !button) {
        return;
    }


    const fullName =
        document.getElementById("full_name")
        .value
        .trim();

    const email =
        document.getElementById("email")
        .value
        .trim();

    const phone =
        document.getElementById("phone")
        .value
        .trim();

    const address =
        document.getElementById("address")
        .value
        .trim();


    if (
        !fullName ||
        !email ||
        !phone ||
        !address
    ) {

        alert(
            "Please complete all delivery details."
        );

        return;
    }


    button.disabled = true;

    button.innerHTML = `
        <span>PROCESSING...</span>
        <i class="fa-solid fa-spinner fa-spin"></i>
    `;


    try {

        const formData =
            new FormData();

        formData.append(
            "full_name",
            fullName
        );

        formData.append(
            "email",
            email
        );

        formData.append(
            "phone",
            phone
        );

        formData.append(
            "address",
            address
        );


        const response =
            await fetch(
                "php/order_process.php",
                {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                }
            );


        const text =
            await response.text();


        let data;

        try {

            data = JSON.parse(text);

        } catch (error) {

            console.error(
                "Server response:",
                text
            );

            throw new Error(
                "The server returned an invalid response."
            );
        }


        if (!data.success) {

            alert(
                data.message ||
                "Unable to place your order."
            );

            button.disabled = false;

            button.innerHTML = `
                <span>PLACE ORDER</span>
                <i class="fa-solid fa-arrow-right"></i>
            `;

            return;
        }


        alert(
            "Your order has been placed successfully!"
        );


        window.location.href =
            "orders.php";

    } catch (error) {

        console.error(error);

        alert(
            error.message ||
            "Something went wrong while placing your order."
        );


        button.disabled = false;

        button.innerHTML = `
            <span>PLACE ORDER</span>
            <i class="fa-solid fa-arrow-right"></i>
        `;
    }

}


/* =========================================================
   SEARCH
========================================================= */

function openSearch() {

    const modal =
        document.getElementById("searchModal");

    if (!modal) {
        return;
    }

    modal.classList.add("show");

    const input =
        document.getElementById("searchInput");

    if (input) {

        setTimeout(() => {
            input.focus();
        }, 100);

    }

}


function closeModal(id) {

    const modal =
        document.getElementById(id);

    if (modal) {
        modal.classList.remove("show");
    }

}


function searchProducts() {

    const input =
        document.getElementById("searchInput");

    const results =
        document.getElementById("searchResults");


    if (!input || !results) {
        return;
    }


    const keyword =
        input.value
        .trim()
        .toLowerCase();


    if (!keyword) {

        results.innerHTML = "";

        return;
    }


    results.innerHTML = `
        <div class="search-result">
            <a href="shop.php">
                Search "${keyword}" in the shop
            </a>
        </div>
    `;
}


/* =========================================================
   CLOSE SEARCH
========================================================= */

document.addEventListener(
    "click",
    function(event) {

        const modal =
            document.getElementById("searchModal");

        if (!modal) {
            return;
        }


        if (event.target === modal) {

            modal.classList.remove("show");

        }

    }
);

</script>


</body>
</html> 