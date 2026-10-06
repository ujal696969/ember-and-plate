<?php

$host = "sql309.infinityfree.com";
$username = "if0_43106109";
$password = "YOUR_MYSQL_PASSWORD";
$database = "if0_43106109_photofinal_dp";
$port = 3306;

$conn = mysqli_connect(
    $host,
    $username,
    $password,
    $database,
    $port
);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");