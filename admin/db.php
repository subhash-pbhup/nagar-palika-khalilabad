<?php
// $host = "localhost";
// $user = "root";
// $dbname = "khalilabad";
// $pass = "";


$host = "localhost";
$user = "u561121976_khalilabad";
$dbname = "u561121976_khalilabad";
$pass = "Su#@1700";


$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("DB Connection Failed: " . $conn->connect_error);
}
