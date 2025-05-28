<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "auto_avenue_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>