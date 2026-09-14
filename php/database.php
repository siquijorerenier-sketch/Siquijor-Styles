<?php

/*
 * SIQUIJOR STYLES
 * php/database.php
 *
 * Database connection.
 * IMPORTANT: there is intentionally NO closing PHP tag.
 * This prevents accidental whitespace from corrupting JSON responses.
 */

mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli(
    "localhost",
    "root",
    "",
    "rey"
);

if ($conn->connect_error) {
    throw new RuntimeException(
        "Database connection failed: " .
        $conn->connect_error
    );
}

$conn->set_charset("utf8mb4");
