<?php

require_once __DIR__ . "/../php/session.php";


/* =========================================================
   CLEAR ADMIN SESSION
========================================================= */

$_SESSION = [];


/* =========================================================
   DELETE SESSION COOKIE
========================================================= */

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}


/* =========================================================
   DESTROY SESSION
========================================================= */

session_destroy();


/* =========================================================
   REDIRECT TO ADMIN LOGIN
========================================================= */

header("Location: login.php");
exit;

?>