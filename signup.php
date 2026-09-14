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

    <title>Sign Up - Siquijor Styles</title>

    <link rel="stylesheet" href="css/style.css">

    <link
        href="https://fonts.googleapis.com/css2?family=Parisienne&family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet">

    <style>

        * {
            box-sizing: border-box;
        }

        body.signup-page {
            margin: 0;
            min-height: 100vh;
            background: #f8f5ee;
            color: #40565b;
            font-family: "Poppins", sans-serif;
        }


        /* ==================================================
           HEADER
        ================================================== */

        .signup-header {
            width: 100%;
            min-height: 78px;
            background: rgba(255, 255, 255, 0.96);
            border-bottom: 1px solid rgba(64, 86, 91, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 7%;
            position: relative;
            z-index: 20;
        }

        .signup-logo a {
            text-decoration: none;
            color: #285c60;
            font-family: "Playfair Display", serif;
            font-size: 23px;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .signup-navigation {
            display: flex;
            align-items: center;
            gap: 34px;
        }

        .signup-navigation a {
            text-decoration: none;
            color: #526a6e;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: 0.2s ease;
        }

        .signup-navigation a:hover {
            color: #285c60;
        }

        .signup-navigation a.active {
            color: #285c60;
            position: relative;
        }

        .signup-navigation a.active::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: -8px;
            width: 22px;
            height: 2px;
            margin: auto;
            background: #d48749;
        }


        /* ==================================================
           MAIN AREA
        ================================================== */

        .signup-main {
            min-height: calc(100vh - 78px);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 70px 7%;
        }

        .signup-shape-left {
            position: absolute;
            width: 470px;
            height: 430px;
            left: -200px;
            top: -120px;
            background: #dceceb;
            border-radius: 58% 42% 63% 37% / 42% 57% 43% 58%;
        }

        .signup-shape-right {
            position: absolute;
            width: 440px;
            height: 390px;
            right: -170px;
            bottom: -150px;
            background: #f1d9b3;
            border-radius: 43% 57% 38% 62% / 55% 40% 60% 45%;
        }

        .signup-card {
            width: 100%;
            max-width: 490px;
            position: relative;
            z-index: 5;
            background: rgba(255, 255, 255, 0.97);
            padding: 48px 48px 42px;
            border: 1px solid rgba(64, 86, 91, 0.07);
            box-shadow: 0 22px 65px rgba(51, 74, 79, 0.12);
        }


        /* ==================================================
           CARD HEADING
        ================================================== */

        .signup-eyebrow {
            text-align: center;
            color: #d48749;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 2.5px;
            margin-bottom: 12px;
        }

        .signup-card h1 {
            margin: 0;
            text-align: center;
            color: #285c60;
            font-family: "Playfair Display", serif;
            font-size: 37px;
            line-height: 1.1;
            font-weight: 600;
        }

        .signup-script {
            margin-top: 8px;
            margin-bottom: 10px;
            text-align: center;
            color: #d48749;
            font-family: "Parisienne", cursive;
            font-size: 29px;
        }

        .signup-description {
            margin: 0 auto 32px;
            max-width: 330px;
            text-align: center;
            color: #74868a;
            font-size: 10px;
            line-height: 1.8;
        }


        /* ==================================================
           FORM
        ================================================== */

        .signup-form {
            display: flex;
            flex-direction: column;
            gap: 17px;
        }

        .signup-field {
            display: flex;
            flex-direction: column;
        }

        .signup-field label {
            color: #285c60;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 1px;
            margin-bottom: 7px;
        }

        .signup-field input {
            width: 100%;
            height: 45px;
            padding: 0 14px;
            border: 1px solid #d9dfdd;
            background: #fbfaf7;
            color: #40565b;
            font-family: "Poppins", sans-serif;
            font-size: 10px;
            outline: none;
            transition: 0.2s ease;
        }

        .signup-field input::placeholder {
            color: #9aa8aa;
        }

        .signup-field input:focus {
            border-color: #75a3a4;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(117, 163, 164, 0.10);
        }


        /* ==================================================
           PASSWORD NOTE
        ================================================== */

        .signup-password-note {
            margin-top: -8px;
            color: #88989a;
            font-size: 8px;
        }


        /* ==================================================
           TERMS & PRIVACY
        ================================================== */

        .signup-terms {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            margin-top: 1px;
            color: #7b8b8e;
            font-size: 8.5px;
            line-height: 1.6;
        }

        .signup-terms input {
            width: 14px;
            height: 14px;
            margin: 1px 0 0;
            flex-shrink: 0;
            accent-color: #285c60;
            cursor: pointer;
        }

        .signup-terms label {
            cursor: pointer;
        }

        .signup-terms a {
            color: #285c60;
            font-weight: 600;
            text-decoration: none;
        }

        .signup-terms a:hover {
            color: #d48749;
            text-decoration: underline;
        }


        /* ==================================================
           SUBMIT BUTTON
        ================================================== */

        .signup-submit {
            width: 100%;
            min-height: 47px;
            margin-top: 5px;
            border: none;
            background: #285c60;
            color: #ffffff;
            font-family: "Poppins", sans-serif;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 1.6px;
            cursor: pointer;
            transition: 0.25s ease;
        }

        .signup-submit:hover {
            background: #214b4e;
            transform: translateY(-1px);
        }

        .signup-submit:active {
            transform: translateY(0);
        }

        .signup-submit:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            transform: none;
        }


        /* ==================================================
           LOGIN LINK
        ================================================== */

        .signup-login-text {
            margin: 25px 0 0;
            text-align: center;
            color: #7b8b8e;
            font-size: 9px;
        }

        .signup-login-text a {
            color: #285c60;
            font-weight: 600;
            text-decoration: none;
        }

        .signup-login-text a:hover {
            color: #d48749;
        }


        /* ==================================================
           DECORATIVE BOTTOM TEXT
        ================================================== */

        .signup-footer-note {
            margin-top: 26px;
            padding-top: 18px;
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

            .signup-header {
                padding: 18px 6%;
                flex-direction: column;
                gap: 18px;
            }

            .signup-navigation {
                gap: 20px;
                flex-wrap: wrap;
                justify-content: center;
            }

            .signup-main {
                min-height: calc(100vh - 130px);
                padding: 45px 6%;
            }

            .signup-card {
                padding: 38px 25px 32px;
            }

            .signup-card h1 {
                font-size: 32px;
            }

            .signup-shape-left {
                width: 320px;
                height: 300px;
                left: -180px;
            }

            .signup-shape-right {
                width: 300px;
                height: 280px;
                right: -170px;
            }

        }

    </style>

</head>


<body class="signup-page">


<!-- ==================================================
     HEADER
================================================== -->

<header class="signup-header">

    <div class="signup-logo">

        <a href="index.php">
            Siquijor Styles
        </a>

    </div>


    <nav class="signup-navigation">

        <a href="index.php">
            Home
        </a>

        <a href="shop.php">
            Shop
        </a>

        <a href="login.php">
            Login
        </a>

        <a
            href="signup.php"
            class="active">

            Sign Up

        </a>

    </nav>

</header>


<!-- ==================================================
     MAIN
================================================== -->

<main class="signup-main">

    <div class="signup-shape-left"></div>

    <div class="signup-shape-right"></div>


    <section class="signup-card">


        <div class="signup-eyebrow">

            JOIN THE ISLAND STYLE

        </div>


        <h1>

            Create Account

        </h1>


        <div class="signup-script">

            Welcome to Siquijor Styles

        </div>


        <p class="signup-description">

            Create your account and discover
            island-inspired fashion made for
            everyday life.

        </p>


        <!-- ==================================================
             SIGN UP FORM
        ================================================== -->

        <form
            id="signupForm"
            class="signup-form"
            onsubmit="signup(event)"
        >


            <!-- FULL NAME -->

            <div class="signup-field">

                <label for="signupName">

                    FULL NAME

                </label>

                <input
                    type="text"
                    id="signupName"
                    name="full_name"
                    placeholder="Enter your full name"
                    autocomplete="name"
                    required
                >

            </div>


            <!-- EMAIL -->

            <div class="signup-field">

                <label for="signupEmail">

                    EMAIL ADDRESS

                </label>

                <input
                    type="email"
                    id="signupEmail"
                    name="email"
                    placeholder="Enter your email"
                    autocomplete="email"
                    required
                >

            </div>


            <!-- PASSWORD -->

            <div class="signup-field">

                <label for="signupPassword">

                    PASSWORD

                </label>

                <input
                    type="password"
                    id="signupPassword"
                    name="password"
                    placeholder="Create a password"
                    autocomplete="new-password"
                    minlength="6"
                    required
                >

            </div>


            <div class="signup-password-note">

                Password must contain at least 6 characters.

            </div>


            <!-- CONFIRM PASSWORD -->

            <div class="signup-field">

                <label for="signupConfirm">

                    CONFIRM PASSWORD

                </label>

                <input
                    type="password"
                    id="signupConfirm"
                    name="confirm_password"
                    placeholder="Confirm your password"
                    autocomplete="new-password"
                    minlength="6"
                    required
                >

            </div>


            <!-- ==================================================
                 TERMS & PRIVACY AGREEMENT
            ================================================== -->

            <div class="signup-terms">

                <input
                    type="checkbox"
                    id="agreeTerms"
                    name="agree_terms"
                    value="1"
                    required
                >

                <label for="agreeTerms">

                    I agree to the

                    <a
                        href="terms.php"
                        target="_blank"
                        rel="noopener noreferrer"
                    >

                        Terms &amp; Conditions

                    </a>

                    and

                    <a
                        href="privacy.php"
                        target="_blank"
                        rel="noopener noreferrer"
                    >

                        Privacy Policy

                    </a>.

                </label>

            </div>


            <!-- SUBMIT -->

            <button
                type="submit"
                class="signup-submit"
            >

                CREATE ACCOUNT

            </button>


        </form>


        <!-- LOGIN LINK -->

        <p class="signup-login-text">

            Already have an account?

            <a href="login.php">

                Log in here

            </a>

        </p>


        <!-- FOOTER NOTE -->

        <div class="signup-footer-note">

            Island-inspired fashion,
            rooted in Siquijor.

        </div>


    </section>

</main>


<!-- JAVASCRIPT -->

<script src="js/script.js"></script>


</body>

</html>