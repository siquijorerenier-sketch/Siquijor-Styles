<?php

/* ==================================================
   SIQUIJOR STYLES SHARED SESSION

   The storefront may run on:
       http://localhost:8000

   while the admin area may run through XAMPP Apache on:
       http://localhost/Website

   Both addresses must use the SAME PHP session files.
   XAMPP provides C:\xampp\tmp, so use a dedicated
   shared folder there instead of PHP's default session
   directory. This makes the login survive when moving
   between the two servers/ports.
================================================== */

$shared_session_path = "C:\\xampp\\tmp\\siquijor_styles_sessions";

/*
 * Create the shared session directory if necessary.
 */
if (!is_dir($shared_session_path)) {
    @mkdir($shared_session_path, 0777, true);
}

/*
 * If XAMPP's shared temp directory is unavailable,
 * fall back to the project's own session folder.
 */
if (
    !is_dir($shared_session_path) ||
    !is_writable($shared_session_path)
) {

    $shared_session_path =
        __DIR__ .
        DIRECTORY_SEPARATOR .
        "sessions";

    if (!is_dir($shared_session_path)) {
        @mkdir(
            $shared_session_path,
            0777,
            true
        );
    }
}


/* ==================================================
   START SHARED SESSION
================================================== */

if (session_status() !== PHP_SESSION_ACTIVE) {

    session_name(
        "SIQUIJOR_STYLES_SESSION"
    );

    session_save_path(
        $shared_session_path
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