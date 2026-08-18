<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "khalilabad_property_tax";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("DB Connection Failed: " . $conn->connect_error);
}
?>
