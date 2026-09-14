<?php

/* ==================================================
   SHARED SESSION CONFIGURATION

   The storefront may run on PHP's built-in server
   (localhost:8000) while the admin area may run through
   Apache (localhost/Website). Both servers use this same
   physical project folder, so storing sessions here allows
   the same login to be recognized by both servers.
================================================== */

$session_path = __DIR__ . DIRECTORY_SEPARATOR . "sessions";

if (!is_dir($session_path)) {
    @mkdir($session_path, 0777, true);
}

if (session_status() !== PHP_SESSION_ACTIVE) {

    session_name("SIQUIJOR_STYLES_SESSION");

    session_save_path($session_path);

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
