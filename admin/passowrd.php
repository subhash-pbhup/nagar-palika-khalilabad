<?php
require 'db.php';

$hash = password_hash("admin", PASSWORD_DEFAULT);

$sql = "INSERT INTO users (username, password, role) VALUES ('admin', '$hash', 'admin')";
$conn->query($sql);

echo "Admin user created successfully";
?>
