<?php

require_once "database.php";

session_start();

header("Content-Type: application/json; charset=UTF-8");


/* ==================================================
   RESPONSE FUNCTION
================================================== */

function sendResponse($success, $message, $user = null)
{
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "user" => $user
    ]);

    exit;
}


/* ==================================================
   ONLY POST
================================================== */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    sendResponse(
        false,
        "Invalid request."
    );

}


/* ==================================================
   GET FORM DATA
================================================== */

$email = trim(
    $_POST["email"] ?? ""
);

$password =
    $_POST["password"] ?? "";


/* ==================================================
   VALIDATION
================================================== */

if ($email === "" || $password === "") {

    sendResponse(
        false,
        "Please enter your email and password."
    );

}


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    sendResponse(
        false,
        "Please enter a valid email address."
    );

}


/* ==================================================
   FIND USER
================================================== */

$stmt = $conn->prepare(
    "SELECT
        id,
        full_name,
        email,
        password
     FROM users
     WHERE email = ?
     LIMIT 1"
);


if (!$stmt) {

    sendResponse(
        false,
        "Unable to process login."
    );

}


$stmt->bind_param(
    "s",
    $email
);

$stmt->execute();

$result =
    $stmt->get_result();


/* ==================================================
   USER NOT FOUND
================================================== */

if ($result->num_rows === 0) {

    $stmt->close();

    sendResponse(
        false,
        "No account was found with this email. Please sign up first."
    );

}


$user = $result->fetch_assoc();

$stmt->close();


/* ==================================================
   CHECK PASSWORD
================================================== */

if (!password_verify(
    $password,
    $user["password"]
)) {

    sendResponse(
        false,
        "Incorrect password. Please try again."
    );

}


/* ==================================================
   CREATE SESSION
================================================== */

session_regenerate_id(true);

$_SESSION["logged_in"] = true;

$_SESSION["user_id"] =
    (int)$user["id"];

$_SESSION["full_name"] =
    $user["full_name"];

$_SESSION["email"] =
    $user["email"];


/* ==================================================
   RETURN USER
================================================== */

$userData = [

    "id" =>
        (int)$user["id"],

    "full_name" =>
        $user["full_name"],

    "email" =>
        $user["email"]

];


sendResponse(
    true,
    "Login successful.",
    $userData
);

?>