<?php

/* ==================================================
   SIQUIJOR STYLES SHARED SESSION
================================================== */

/*
 * IMPORTANT:
 *
 * Both:
 *
 * http://localhost:8000/
 *
 * and:
 *
 * http://localhost/Website/
 *
 * must use the EXACT SAME session storage.
 *
 * The session files are therefore stored inside:
 *
 * C:\xampp\htdocs\Website\php\sessions
 *
 * Because this file is inside /php, __DIR__ points to:
 *
 * C:\xampp\htdocs\Website\php
 *
 * ==================================================
 */

$session_path =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    "sessions";


/* ==================================================
   CREATE SESSION DIRECTORY IF NEEDED
================================================== */

if (!is_dir($session_path)) {

    @mkdir(
        $session_path,
        0777,
        true
    );

}


/* ==================================================
   VERIFY SESSION DIRECTORY
================================================== */

if (
    !is_dir($session_path) ||
    !is_writable($session_path)
) {

    die(
        "Session storage error. " .
        "The folder must exist and be writable: " .
        $session_path
    );

}


/* ==================================================
   START SHARED SESSION
================================================== */

if (
    session_status() !== PHP_SESSION_ACTIVE
) {

    session_name(
        "SIQUIJOR_STYLES_SESSION"
    );


    session_save_path(
        $session_path
    );


    session_set_cookie_params([

        "lifetime" => 0,

        "path" => "/",

        "secure" => false,

        "httponly" => true,

        "samesite" => "Lax"

    ]);


    session_start();

}

?>