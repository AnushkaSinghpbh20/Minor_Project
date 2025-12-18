<?php
// DB Connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "minorproject";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("DB Connection Failed: " . $conn->connect_error);
?>      