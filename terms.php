<?php

session_start();

header("Content-Type: text/html; charset=UTF-8");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Terms & Conditions - Siquijor Styles</title>

    <link
        rel="stylesheet"
        href="css/style.css">

    <link
        href="https://fonts.googleapis.com/css2?family=Parisienne&family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f8f5ee;
            color: #40565b;
            font-family: "Poppins", sans-serif;
        }

        .legal-header {
            width: 100%;
            min-height: 78px;
            background: rgba(255, 255, 255, 0.96);
            border-bottom: 1px solid rgba(64, 86, 91, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 7%;
        }

        .legal-logo a {
            text-decoration: none;
            color: #285c60;
            font-family: "Playfair Display", serif;
            font-size: 23px;
            font-weight: 600;
        }

        .legal-navigation {
            display: flex;
            gap: 30px;
        }

        .legal-navigation a {
            text-decoration: none;
            color: #526a6e;
            font-size: 11px;
            font-weight: 500;
        }

        .legal-navigation a:hover {
            color: #285c60;
        }

        .legal-main {
            max-width: 900px;
            margin: 0 auto;
            padding: 70px 25px 90px;
        }

        .legal-card {
            background: #ffffff;
            padding: 50px;
            border: 1px solid rgba(64, 86, 91, 0.07);
            box-shadow: 0 20px 55px rgba(51, 74, 79, 0.10);
        }

        .legal-eyebrow {
            text-align: center;
            color: #d48749;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 2.5px;
            margin-bottom: 12px;
        }

        .legal-card h1 {
            margin: 0;
            text-align: center;
            color: #285c60;
            font-family: "Playfair Display", serif;
            font-size: 40px;
        }

        .legal-date {
            text-align: center;
            color: #8a999b;
            font-size: 9px;
            margin: 12px 0 40px;
        }

        .legal-card h2 {
            color: #285c60;
            font-family: "Playfair Display", serif;
            font-size: 22px;
            margin-top: 35px;
            margin-bottom: 12px;
        }

        .legal-card p,
        .legal-card li {
            color: #66777a;
            font-size: 11px;
            line-height: 1.9;
        }

        .legal-card ul {
            padding-left: 22px;
        }

        .legal-card a {
            color: #285c60;
            font-weight: 500;
        }

        .legal-footer {
            text-align: center;
            padding: 25px;
            color: #8a999b;
            font-size: 9px;
        }

        .back-signup {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 22px;
            background: #285c60;
            color: #ffffff !important;
            text-decoration: none;
            font-size: 10px;
            letter-spacing: 0.8px;
        }

        .back-signup:hover {
            background: #214b4e;
        }

        @media (max-width: 700px) {

            .legal-header {
                padding: 18px 6%;
                flex-direction: column;
                gap: 18px;
            }

            .legal-navigation {
                gap: 18px;
                flex-wrap: wrap;
                justify-content: center;
            }

            .legal-main {
                padding: 40px 6%;
            }

            .legal-card {
                padding: 30px 22px;
            }

            .legal-card h1 {
                font-size: 32px;
            }

        }

    </style>

</head>


<body>


<header class="legal-header">

    <div class="legal-logo">

        <a href="index.php">
            Siquijor Styles
        </a>

    </div>


    <nav class="legal-navigation">

        <a href="index.php">
            Home
        </a>

        <a href="shop.php">
            Shop
        </a>

        <a href="login.php">
            Login
        </a>

        <a href="signup.php">
            Sign Up
        </a>

    </nav>

</header>



<main class="legal-main">

    <section class="legal-card">

        <div class="legal-eyebrow">
            SIQUIJOR STYLES
        </div>


        <h1>
            Terms & Conditions
        </h1>


        <p class="legal-date">
            Last updated: September 14, 2026
        </p>


        <p>
            Welcome to Siquijor Styles. By creating an account,
            accessing our website, or purchasing products from
            Siquijor Styles, you agree to these Terms & Conditions.
        </p>


        <h2>
            1. Acceptance of Terms
        </h2>

        <p>
            By using the Siquijor Styles website, you confirm that
            you have read, understood, and agree to follow these
            Terms & Conditions. If you do not agree with these
            terms, please do not use the website or create an account.
        </p>


        <h2>
            2. User Accounts
        </h2>

        <p>
            When creating an account, you are responsible for
            providing accurate information and keeping your account
            information secure. You are responsible for activities
            performed through your account.
        </p>


        <h2>
            3. Products and Prices
        </h2>

        <p>
            Siquijor Styles makes reasonable efforts to display
            accurate product descriptions, images, prices, and
            availability. Product information may be updated when
            necessary.
        </p>

        <p>
            Prices and product availability may change without
            prior notice. The price displayed at the time of
            checkout will apply to your order.
        </p>


        <h2>
            4. Orders
        </h2>

        <p>
            Adding a product to your cart does not guarantee that
            the product will remain available. An order is subject
            to product availability and successful processing.
        </p>


        <h2>
            5. Payments
        </h2>

        <p>
            Customers are responsible for providing accurate
            information required for order processing and payment.
            Orders may not be completed if the required information
            is incomplete or incorrect.
        </p>


        <h2>
            6. Shipping and Delivery
        </h2>

        <p>
            Delivery times may vary depending on the customer's
            location, delivery conditions, and other circumstances.
            Customers should provide accurate delivery information
            when placing an order.
        </p>


        <h2>
            7. Returns and Exchanges
        </h2>

        <p>
            Customers may request a return or exchange subject to
            the return conditions provided by Siquijor Styles.
            Products must meet the applicable return requirements.
        </p>


        <h2>
            8. Prohibited Activities</h2>

        <p>
            Users must not use the website for unlawful activities,
            attempt to gain unauthorized access to the system,
            interfere with website operations, or misuse another
            user's account.
        </p>


        <h2>
            9. Website Content
        </h2>

        <p>
            Website content, including text, images, logos, designs,
            and other materials, is intended for use within the
            Siquijor Styles website and may not be copied,
            reproduced, or redistributed without permission.
        </p>


        <h2>
            10. Changes to These Terms
        </h2>

        <p>
            Siquijor Styles may update these Terms & Conditions
            when necessary. Updated terms will be posted on this
            page. Continued use of the website after changes means
            that you accept the updated terms.
        </p>


        <h2>
            11. Contact Us
        </h2>

        <p>
            If you have questions about these Terms & Conditions,
            please contact Siquijor Styles through the contact
            information provided on our website.
        </p>


        <a
            href="signup.php"
            class="back-signup">

            ← Back to Sign Up

        </a>

    </section>

</main>



<footer class="legal-footer">

    © <?= date("Y") ?>
    Siquijor Styles.
    All rights reserved.

</footer>


</body>

</html>s