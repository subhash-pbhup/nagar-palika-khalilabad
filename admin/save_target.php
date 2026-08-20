<?php
// save_target.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// adjust role check as per your app
$user_role = $_SESSION['role'] ?? 'user';
if (!in_array($user_role, ['admin', 'superadmin', 'manager'])) {
    echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
    exit;
}

include 'db.php';

$daily_target = isset($_POST['daily_target']) ? intval($_POST['daily_target']) : 0;
if ($daily_target <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid target']);
    exit;
}

// Upsert into settings table
// Try update first
$stmt = mysqli_prepare($conn, "INSERT INTO settings (`key`, `value`) VALUES ('daily_target', ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
mysqli_stmt_bind_param($stmt, 's', $daily_target);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($ok) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'DB error']);
}
