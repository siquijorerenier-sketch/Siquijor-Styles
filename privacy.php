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
        content="width=device-width, initial-scale=1.0"
    >

    <title>Privacy Policy - Siquijor Styles</title>

    <link rel="stylesheet" href="css/style.css">

    <link
        href="https://fonts.googleapis.com/css2?family=Parisienne&family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet"
    >

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


        /* ==================================================
           HEADER
        ================================================== */

        .policy-header {
            width: 100%;
            min-height: 78px;
            background: rgba(255, 255, 255, 0.96);
            border-bottom: 1px solid rgba(64, 86, 91, 0.08);

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 7%;

            position: relative;
            z-index: 10;
        }

        .policy-logo a {
            text-decoration: none;
            color: #285c60;

            font-family: "Playfair Display", serif;
            font-size: 23px;
            font-weight: 600;
        }

        .policy-navigation {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .policy-navigation a {
            text-decoration: none;
            color: #526a6e;

            font-size: 11px;
            font-weight: 500;

            transition: 0.2s ease;
        }

        .policy-navigation a:hover {
            color: #285c60;
        }


        /* ==================================================
           MAIN
        ================================================== */

        .policy-main {
            position: relative;
            overflow: hidden;

            min-height: calc(100vh - 78px);

            padding: 70px 7%;
        }


        /* ==================================================
           DECORATIVE SHAPES
        ================================================== */

        .policy-shape-left {
            position: absolute;

            width: 380px;
            height: 340px;

            left: -190px;
            top: -130px;

            background: #dceceb;

            border-radius:
                58% 42% 63% 37% /
                42% 57% 43% 58%;
        }

        .policy-shape-right {
            position: absolute;

            width: 380px;
            height: 330px;

            right: -170px;
            bottom: -150px;

            background: #f1d9b3;

            border-radius:
                43% 57% 38% 62% /
                55% 40% 60% 45%;
        }


        /* ==================================================
           POLICY CARD
        ================================================== */

        .policy-card {
            position: relative;
            z-index: 2;

            width: 100%;
            max-width: 900px;

            margin: 0 auto;

            background: rgba(255, 255, 255, 0.97);

            padding: 55px 65px;

            border: 1px solid rgba(64, 86, 91, 0.07);

            box-shadow:
                0 22px 65px
                rgba(51, 74, 79, 0.12);
        }


        /* ==================================================
           TITLE
        ================================================== */

        .policy-eyebrow {
            text-align: center;

            color: #d48749;

            font-size: 9px;
            font-weight: 600;

            letter-spacing: 2.5px;

            margin-bottom: 12px;
        }

        .policy-card h1 {
            margin: 0;

            text-align: center;

            color: #285c60;

            font-family: "Playfair Display", serif;

            font-size: 42px;
            line-height: 1.15;

            font-weight: 600;
        }

        .policy-script {
            margin-top: 8px;
            margin-bottom: 18px;

            text-align: center;

            color: #d48749;

            font-family: "Parisienne", cursive;

            font-size: 28px;
        }

        .policy-updated {
            text-align: center;

            color: #8b999b;

            font-size: 9px;

            margin-bottom: 40px;
        }


        /* ==================================================
           CONTENT
        ================================================== */

        .policy-section {
            margin-bottom: 30px;
        }

        .policy-section h2 {
            margin: 0 0 10px;

            color: #285c60;

            font-family: "Playfair Display", serif;

            font-size: 22px;
            font-weight: 600;
        }

        .policy-section p {
            margin: 0 0 12px;

            color: #687b7f;

            font-size: 10px;

            line-height: 1.9;
        }

        .policy-section ul {
            margin: 8px 0 0 20px;
            padding: 0;
        }

        .policy-section li {
            margin-bottom: 8px;

            color: #687b7f;

            font-size: 10px;

            line-height: 1.8;
        }

        .policy-section strong {
            color: #40565b;
        }


        /* ==================================================
           BACK BUTTON
        ================================================== */

        .policy-back {
            display: inline-block;

            margin-top: 10px;

            padding: 12px 22px;

            background: #285c60;
            color: #ffffff;

            text-decoration: none;

            font-size: 9px;
            font-weight: 600;

            letter-spacing: 1px;

            transition: 0.2s ease;
        }

        .policy-back:hover {
            background: #214b4e;
            transform: translateY(-1px);
        }


        /* ==================================================
           FOOTER
        ================================================== */

        .policy-footer {
            margin-top: 45px;

            padding-top: 20px;

            border-top: 1px solid #edf0ee;

            text-align: center;

            color: #a0abad;

            font-size: 8px;

            line-height: 1.7;
        }


        /* ==================================================
           RESPONSIVE
        ================================================== */

        @media (max-width: 700px) {

            .policy-header {
                padding: 18px 6%;

                flex-direction: column;

                gap: 18px;
            }

            .policy-navigation {
                gap: 18px;

                flex-wrap: wrap;

                justify-content: center;
            }

            .policy-main {
                padding: 40px 6%;
            }

            .policy-card {
                padding: 38px 25px;
            }

            .policy-card h1 {
                font-size: 32px;
            }

            .policy-script {
                font-size: 24px;
            }

        }

    </style>

</head>


<body>


<!-- ==================================================
     HEADER
================================================== -->

<header class="policy-header">


    <div class="policy-logo">

        <a href="index.php">

            Siquijor Styles

        </a>

    </div>


    <nav class="policy-navigation">

        <a href="index.php">
            Home
        </a>

        <a href="shop.php">
            Shop
        </a>

        <a href="terms.php">
            Terms & Conditions
        </a>

    </nav>


</header>



<!-- ==================================================
     MAIN
================================================== -->

<main class="policy-main">


    <div class="policy-shape-left"></div>

    <div class="policy-shape-right"></div>


    <section class="policy-card">


        <div class="policy-eyebrow">

            YOUR PRIVACY MATTERS

        </div>


        <h1>

            Privacy Policy

        </h1>


        <div class="policy-script">

            Your trust is important to us

        </div>


        <div class="policy-updated">

            Last Updated: September 14, 2026

        </div>



        <!-- ==================================================
             1. INTRODUCTION
        ================================================== -->

        <div class="policy-section">

            <h2>
                1. Introduction
            </h2>

            <p>

                Siquijor Styles respects your privacy and is
                committed to protecting the personal information
                you provide when using our website.

            </p>

            <p>

                This Privacy Policy explains what information
                we collect, how we use it, and how we protect
                your information when you use Siquijor Styles.

            </p>

        </div>



        <!-- ==================================================
             2. INFORMATION WE COLLECT
        ================================================== -->

        <div class="policy-section">

            <h2>
                2. Information We Collect
            </h2>

            <p>

                When you create an account, place an order,
                or use certain features of our website, we may
                collect information such as:

            </p>

            <ul>

                <li>
                    Your full name
                </li>

                <li>
                    Your email address
                </li>

                <li>
                    Account login information
                </li>

                <li>
                    Information related to your orders
                </li>

                <li>
                    Shopping cart and product information
                </li>

                <li>
                    Information you voluntarily provide when
                    contacting us
                </li>

            </ul>

        </div>



        <!-- ==================================================
             3. HOW WE USE INFORMATION
        ================================================== -->

        <div class="policy-section">

            <h2>
                3. How We Use Your Information
            </h2>

            <p>

                We may use the information we collect to:

            </p>

            <ul>

                <li>
                    Create and manage your account
                </li>

                <li>
                    Process and manage your orders
                </li>

                <li>
                    Provide customer support
                </li>

                <li>
                    Maintain your shopping cart
                </li>

                <li>
                    Improve our website and services
                </li>

                <li>
                    Communicate with you about your account
                    or orders
                </li>

                <li>
                    Send updates or promotional information
                    when you choose to receive them
                </li>

            </ul>

        </div>



        <!-- ==================================================
             4. PASSWORD SECURITY
        ================================================== -->

        <div class="policy-section">

            <h2>
                4. Account and Password Security
            </h2>

            <p>

                Your account password should be kept
                confidential. You should not share your
                password with other people.

            </p>

            <p>

                Siquijor Styles takes reasonable steps to
                protect account information from unauthorized
                access. However, no online system can guarantee
                complete security.

            </p>

        </div>



        <!-- ==================================================
             5. INFORMATION SHARING
        ================================================== -->

        <div class="policy-section">

            <h2>
                5. Sharing of Information
            </h2>

            <p>

                We do not sell or rent your personal information
                to third parties.

            </p>

            <p>

                Information may only be shared when necessary
                to operate the website, provide requested
                services, process orders, comply with applicable
                legal requirements, or protect the security
                and rights of Siquijor Styles and its users.

            </p>

        </div>



        <!-- ==================================================
             6. COOKIES
        ================================================== -->

        <div class="policy-section">

            <h2>
                6. Cookies and Session Information
            </h2>

            <p>

                Siquijor Styles may use session information and
                similar technologies to keep users signed in,
                maintain shopping cart functionality, and
                provide a better website experience.

            </p>

            <p>

                You may configure your browser to restrict
                certain cookies or stored website information.
                Some website features may not work properly
                if these functions are disabled.

            </p>

        </div>



        <!-- ==================================================
             7. DATA RETENTION
        ================================================== -->

        <div class="policy-section">

            <h2>
                7. Data Retention
            </h2>

            <p>

                We retain account and order information only
                for as long as reasonably necessary for the
                purposes described in this Privacy Policy,
                including maintaining records and providing
                requested services.

            </p>

        </div>



        <!-- ==================================================
             8. YOUR RIGHTS
        ================================================== -->

        <div class="policy-section">

            <h2>
                8. Your Privacy Choices
            </h2>

            <p>

                Depending on applicable laws and circumstances,
                you may have rights regarding your personal
                information, including the ability to request
                access, correction, or deletion of information
                associated with your account.

            </p>

            <p>

                If you have questions about your personal
                information, you may contact Siquijor Styles
                through our contact page.

            </p>

        </div>



        <!-- ==================================================
             9. CHILDREN
        ================================================== -->

        <div class="policy-section">

            <h2>
                9. Children's Privacy
            </h2>

            <p>

                Siquijor Styles is not intended to knowingly
                collect personal information from children
                without appropriate consent.

            </p>

        </div>



        <!-- ==================================================
             10. POLICY CHANGES
        ================================================== -->

        <div class="policy-section">

            <h2>
                10. Changes to This Privacy Policy
            </h2>

            <p>

                We may update this Privacy Policy when
                necessary. Any changes will be reflected
                on this page by updating the "Last Updated"
                date.

            </p>

        </div>



        <!-- ==================================================
             11. CONTACT
        ================================================== -->

        <div class="policy-section">

            <h2>
                11. Contact Us
            </h2>

            <p>

                If you have questions or concerns about this
                Privacy Policy or how your information is
                handled, please contact Siquijor Styles
                through our Contact page.

            </p>

        </div>



        <a
            href="signup.php"
            class="policy-back"
        >

            ← BACK TO SIGN UP

        </a>


        <div class="policy-footer">

            Island-inspired fashion,
            rooted in Siquijor.

            <br>

            © <?= date("Y") ?>
            Siquijor Styles.
            All rights reserved.

        </div>


    </section>

</main>


</body>

</html>