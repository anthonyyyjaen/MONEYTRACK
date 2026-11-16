<?php

require_once __DIR__ . "/constants.php";

$conn = new mysqli(
    DB_HOST,
    DB_USER,
    DB_PASS,
    DB_NAME
);
global $conn;
