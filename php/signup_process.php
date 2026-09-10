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

$full_name = trim(
    $_POST["full_name"] ?? ""
);

$email = trim(
    $_POST["email"] ?? ""
);

$password =
    $_POST["password"] ?? "";

$confirm_password =
    $_POST["confirm_password"] ?? "";


/* ==================================================
   VALIDATION
================================================== */

if (
    $full_name === "" ||
    $email === "" ||
    $password === "" ||
    $confirm_password === ""
) {

    sendResponse(
        false,
        "Please complete all fields."
    );

}


if (!filter_var(
    $email,
    FILTER_VALIDATE_EMAIL
)) {

    sendResponse(
        false,
        "Please enter a valid email address."
    );

}


if (strlen($password) < 6) {

    sendResponse(
        false,
        "Password must be at least 6 characters."
    );

}


if ($password !== $confirm_password) {

    sendResponse(
        false,
        "Passwords do not match."
    );

}


/* ==================================================
   CHECK EMAIL
================================================== */

$check = $conn->prepare(
    "SELECT id
     FROM users
     WHERE email = ?
     LIMIT 1"
);


if (!$check) {

    sendResponse(
        false,
        "Unable to check account."
    );

}


$check->bind_param(
    "s",
    $email
);

$check->execute();

$result =
    $check->get_result();


if ($result->num_rows > 0) {

    $check->close();

    sendResponse(
        false,
        "An account with this email already exists."
    );

}

$check->close();


/* ==================================================
   HASH PASSWORD
================================================== */

$hashed_password =
    password_hash(
        $password,
        PASSWORD_DEFAULT
    );


/* ==================================================
   CREATE ACCOUNT
================================================== */

$stmt = $conn->prepare(
    "INSERT INTO users
    (
        full_name,
        email,
        password
    )
    VALUES
    (
        ?,
        ?,
        ?
    )"
);


if (!$stmt) {

    sendResponse(
        false,
        "Unable to create account."
    );

}


$stmt->bind_param(
    "sss",
    $full_name,
    $email,
    $hashed_password
);


if (!$stmt->execute()) {

    $stmt->close();

    sendResponse(
        false,
        "Unable to create account."
    );

}


$user_id =
    $conn->insert_id;

$stmt->close();


/* ==================================================
   AUTOMATIC LOGIN
================================================== */

session_regenerate_id(true);

$_SESSION["logged_in"] = true;

$_SESSION["user_id"] =
    (int)$user_id;

$_SESSION["full_name"] =
    $full_name;

$_SESSION["email"] =
    $email;


/* ==================================================
   RETURN USER
================================================== */

$user = [

    "id" =>
        (int)$user_id,

    "full_name" =>
        $full_name,

    "email" =>
        $email

];


sendResponse(
    true,
    "Account created successfully.",
    $user
);

?>