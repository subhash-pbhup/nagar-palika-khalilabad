<?php
require_once "db.php"; // your DB connection file

header('Content-Type: application/json; charset=utf-8');

$ward_raw = $_GET['ward'] ?? '';
// extract numeric ward (defensive)
$ward_num = intval(preg_replace('/\D+/', '', $ward_raw));

if ($ward_num <= 0) {
    echo json_encode(['success'=>false, 'message'=>'Invalid ward']);
    exit;
}

$sql = "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(new_holding, '-', -1) AS UNSIGNED)), 0) AS maxseq
        FROM assessments
        WHERE new_holding LIKE CONCAT('DEO-W', ?, '-%')";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success'=>false, 'message' => 'DB prepare error: ' . $conn->error]);
    exit;
}

$ward_str = (string)$ward_num;
$stmt->bind_param('s', $ward_str);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$maxseq = intval($res['maxseq'] ?? 0);
$next = $maxseq + 1;
$holding = 'DEO-W' . $ward_str . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);

echo json_encode(['success'=>true, 'holding'=> $holding]);
