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

    <title>Login - Siquijor Styles</title>

    <link rel="stylesheet" href="css/style.css">

    <link
        href="https://fonts.googleapis.com/css2?family=Parisienne&family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet">

    <style>

        /* ==================================================
           LOGIN PAGE
        ================================================== */

        * {
            box-sizing: border-box;
        }

        body.login-page {
            margin: 0;
            min-height: 100vh;
            background: #f8f5ee;
            color: #40565b;
            font-family: "Poppins", sans-serif;
        }


        /* ==================================================
           HEADER
        ================================================== */

        .login-header {
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

        .login-logo a {
            text-decoration: none;
            color: #285c60;
            font-family: "Playfair Display", serif;
            font-size: 23px;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .login-navigation {
            display: flex;
            align-items: center;
            gap: 34px;
        }

        .login-navigation a {
            text-decoration: none;
            color: #526a6e;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: 0.2s ease;
        }

        .login-navigation a:hover {
            color: #285c60;
        }

        .login-navigation a.active {
            color: #285c60;
            position: relative;
        }

        .login-navigation a.active::after {
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

        .login-main {
            min-height: calc(100vh - 78px);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 70px 7%;
        }

        .login-shape-left {
            position: absolute;
            width: 470px;
            height: 430px;
            left: -200px;
            top: -120px;
            background: #dceceb;
            border-radius: 58% 42% 63% 37% / 42% 57% 43% 58%;
        }

        .login-shape-right {
            position: absolute;
            width: 440px;
            height: 390px;
            right: -170px;
            bottom: -150px;
            background: #f1d9b3;
            border-radius: 43% 57% 38% 62% / 55% 40% 60% 45%;
        }

        .login-card {
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 5;
            background: rgba(255, 255, 255, 0.97);
            padding: 50px 48px 43px;
            border: 1px solid rgba(64, 86, 91, 0.07);
            box-shadow: 0 22px 65px rgba(51, 74, 79, 0.12);
        }


        /* ==================================================
           CARD HEADING
        ================================================== */

        .login-eyebrow {
            text-align: center;
            color: #d48749;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 2.5px;
            margin-bottom: 12px;
        }

        .login-card h1 {
            margin: 0;
            text-align: center;
            color: #285c60;
            font-family: "Playfair Display", serif;
            font-size: 39px;
            line-height: 1.1;
            font-weight: 600;
        }

        .login-script {
            margin-top: 8px;
            margin-bottom: 11px;
            text-align: center;
            color: #d48749;
            font-family: "Parisienne", cursive;
            font-size: 30px;
        }

        .login-description {
            margin: 0 auto 34px;
            max-width: 315px;
            text-align: center;
            color: #74868a;
            font-size: 10px;
            line-height: 1.8;
        }


        /* ==================================================
           FORM
        ================================================== */

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 19px;
        }

        .login-field {
            display: flex;
            flex-direction: column;
        }

        .login-field label {
            color: #285c60;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 1px;
            margin-bottom: 7px;
        }

        .login-field input {
            width: 100%;
            height: 47px;
            padding: 0 14px;
            border: 1px solid #d9dfdd;
            background: #fbfaf7;
            color: #40565b;
            font-family: "Poppins", sans-serif;
            font-size: 10px;
            outline: none;
            transition: 0.2s ease;
        }

        .login-field input::placeholder {
            color: #9aa8aa;
        }

        .login-field input:focus {
            border-color: #75a3a4;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(117, 163, 164, 0.10);
        }


        /* ==================================================
           SUBMIT BUTTON
        ================================================== */

        .login-submit {
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

        .login-submit:hover {
            background: #214b4e;
            transform: translateY(-1px);
        }

        .login-submit:active {
            transform: translateY(0);
        }


        /* ==================================================
           SIGN UP LINK
        ================================================== */

        .login-signup-text {
            margin: 26px 0 0;
            text-align: center;
            color: #7b8b8e;
            font-size: 9px;
        }

        .login-signup-text a {
            color: #285c60;
            font-weight: 600;
            text-decoration: none;
        }

        .login-signup-text a:hover {
            color: #d48749;
        }


        /* ==================================================
           BOTTOM NOTE
        ================================================== */

        .login-footer-note {
            margin-top: 25px;
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

            .login-header {
                padding: 18px 6%;
                flex-direction: column;
                gap: 18px;
            }

            .login-navigation {
                gap: 20px;
                flex-wrap: wrap;
                justify-content: center;
            }

            .login-main {
                min-height: calc(100vh - 130px);
                padding: 45px 6%;
            }

            .login-card {
                padding: 40px 25px 33px;
            }

            .login-card h1 {
                font-size: 34px;
            }

            .login-shape-left {
                width: 320px;
                height: 300px;
                left: -180px;
            }

            .login-shape-right {
                width: 300px;
                height: 280px;
                right: -170px;
            }
        }

    </style>

</head>


<body class="login-page">


<header class="login-header">

    <div class="login-logo">

        <a href="index.php">
            Siquijor Styles
        </a>

    </div>


    <nav class="login-navigation">

        <a href="index.php">
            Home
        </a>

        <a href="shop.php">
            Shop
        </a>

        <a
            href="login.php"
            class="active">

            Login

        </a>

        <a href="signup.php">
            Sign Up
        </a>

    </nav>

</header>


<main class="login-main">

    <div class="login-shape-left"></div>

    <div class="login-shape-right"></div>


    <section class="login-card">

        <div class="login-eyebrow">
            WELCOME BACK
        </div>


        <h1>
            Log In
        </h1>


        <div class="login-script">
            Back to Island Style
        </div>


        <p class="login-description">
            Welcome back to Siquijor Styles.
            Log in to continue shopping
            your favorite island-inspired pieces.
        </p>


        <form
            id="loginForm"
            class="login-form"
            onsubmit="login(event)">


            <div class="login-field">

                <label for="loginEmail">
                    EMAIL ADDRESS
                </label>

                <input
                    type="email"
                    id="loginEmail"
                    name="email"
                    placeholder="Enter your email"
                    autocomplete="email"
                    required>

            </div>


            <div class="login-field">

                <label for="loginPassword">
                    PASSWORD
                </label>

                <input
                    type="password"
                    id="loginPassword"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required>

            </div>


            <button
                type="submit"
                class="login-submit">

                LOG IN

            </button>


        </form>


        <p class="login-signup-text">

            Don't have an account?

            <a href="signup.php">
                Create an account
            </a>

        </p>


        <div class="login-footer-note">

            Island-inspired fashion,
            rooted in Siquijor.

        </div>

    </section>

</main>

<script src="js/script.js"></script>

</body>

</html>