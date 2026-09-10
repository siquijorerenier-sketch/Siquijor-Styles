<?php

session_start();

require_once "../php/database.php";


/* =========================================================
   CHECK IF ALREADY LOGGED IN AS ADMIN
========================================================= */

if (
    !empty($_SESSION["logged_in"]) &&
    !empty($_SESSION["user_id"]) &&
    !empty($_SESSION["role"]) &&
    $_SESSION["role"] === "admin"
) {
    header("Location: index.php");
    exit;
}


/* =========================================================
   VARIABLES
========================================================= */

$error = "";

$email = "";


/* =========================================================
   PROCESS LOGIN
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";


    /* -----------------------------------------------------
       VALIDATION
    ----------------------------------------------------- */

    if ($email === "" || $password === "") {

        $error = "Please enter your email and password.";

    } else {

        /* -------------------------------------------------
           FIND USER
        ------------------------------------------------- */

        $stmt = $conn->prepare("
            SELECT
                id,
                full_name,
                email,
                password,
                role
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        if ($stmt) {

            $stmt->bind_param("s", $email);

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {

                $user = $result->fetch_assoc();


                /* -----------------------------------------
                   CHECK ADMIN ROLE
                ----------------------------------------- */

                if ($user["role"] !== "admin") {

                    $error = "You do not have permission to access the admin area.";

                }

                /* -----------------------------------------
                   CHECK PASSWORD
                ----------------------------------------- */

                elseif (!password_verify($password, $user["password"])) {

                    $error = "Invalid email or password.";

                }

                /* -----------------------------------------
                   LOGIN SUCCESS
                ----------------------------------------- */

                else {

                    session_regenerate_id(true);

                    $_SESSION["logged_in"] = true;

                    $_SESSION["user_id"] = (int) $user["id"];

                    $_SESSION["full_name"] = $user["full_name"];

                    $_SESSION["email"] = $user["email"];

                    $_SESSION["role"] = $user["role"];


                    header("Location: index.php");

                    exit;
                }

            } else {

                $error = "Invalid email or password.";

            }

            $stmt->close();

        } else {

            $error = "Unable to process the login right now.";

        }
    }
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

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Login | Siquijor Styles</title>


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

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 30px;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #f4efe7,
                    #e7ddd0
                );

            color: #2f2a25;
        }


        .login-wrapper {

            width: 100%;

            max-width: 430px;
        }


        .login-card {

            background: #ffffff;

            border-radius: 18px;

            padding: 38px;

            box-shadow:
                0 18px 45px
                rgba(0, 0, 0, 0.12);
        }


        .brand {

            text-align: center;

            margin-bottom: 28px;
        }


        .brand img {

            width: auto;

            max-width: 220px;

            height: 65px;

            object-fit: contain;
        }


        .brand h1 {

            margin: 18px 0 6px;

            font-size: 28px;

            font-weight: 700;

            letter-spacing: 0.5px;
        }


        .brand p {

            margin: 0;

            font-size: 14px;

            color: #7a7067;
        }


        .error-message {

            margin-bottom: 20px;

            padding: 13px 14px;

            border-radius: 10px;

            background: #fff0f0;

            border: 1px solid #e2a6a6;

            color: #a32121;

            font-size: 14px;

            line-height: 1.5;
        }


        .form-group {

            margin-bottom: 18px;
        }


        .form-group label {

            display: block;

            margin-bottom: 8px;

            font-size: 14px;

            font-weight: 600;

            color: #40382f;
        }


        .form-group input {

            width: 100%;

            height: 48px;

            border: 1px solid #d7cec4;

            border-radius: 10px;

            padding: 0 14px;

            font-size: 15px;

            outline: none;

            background: #fff;

            color: #2f2a25;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }


        .form-group input:focus {

            border-color: #9c7854;

            box-shadow:
                0 0 0 3px
                rgba(156, 120, 84, 0.12);
        }


        .login-button {

            width: 100%;

            height: 50px;

            margin-top: 5px;

            border: none;

            border-radius: 10px;

            background: #2f2a25;

            color: #ffffff;

            font-size: 15px;

            font-weight: 700;

            cursor: pointer;

            transition:
                background 0.2s ease,
                transform 0.2s ease;
        }


        .login-button:hover {

            background: #4a4036;

            transform: translateY(-1px);
        }


        .back-link {

            display: block;

            margin-top: 22px;

            text-align: center;

            color: #6c5a49;

            font-size: 14px;

            text-decoration: none;
        }


        .back-link:hover {

            text-decoration: underline;
        }


        .admin-label {

            display: inline-block;

            margin-bottom: 8px;

            padding: 5px 10px;

            border-radius: 20px;

            background: #f0e8df;

            color: #6c5a49;

            font-size: 12px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.8px;
        }


        @media (max-width: 500px) {

            body {
                padding: 18px;
            }

            .login-card {
                padding: 28px 22px;
            }

            .brand img {
                max-width: 180px;
                height: 55px;
            }

            .brand h1 {
                font-size: 24px;
            }
        }

    </style>

</head>


<body>

    <div class="login-wrapper">

        <div class="login-card">

            <div class="brand">

                <img
                    src="../images/logo.png"
                    alt="Siquijor Styles"
                >

                <div class="admin-label">
                    Administrator
                </div>

                <h1>
                    Admin Login
                </h1>

                <p>
                    Sign in to manage Siquijor Styles.
                </p>

            </div>


            <?php if ($error !== ""): ?>

                <div class="error-message">
                    <?= e($error) ?>
                </div>

            <?php endif; ?>


            <form
                method="POST"
                action=""
                autocomplete="off"
            >

                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= e($email) ?>"
                        placeholder="Enter admin email"
                        required
                        autocomplete="email"
                    >

                </div>


                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter admin password"
                        required
                        autocomplete="current-password"
                    >

                </div>


                <button
                    type="submit"
                    class="login-button"
                >
                    Sign In
                </button>

            </form>


            <a
                href="../index.php"
                class="back-link"
            >
                ← Back to Siquijor Styles
            </a>

        </div>

    </div>

</body>

</html>