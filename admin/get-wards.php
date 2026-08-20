<?php
require_once "db.php";

header('Content-Type: application/json');

$response = ["status" => "error", "message" => "An unknown error occurred."];

if ($conn) {
    // ✅ FIX: अब ward_id भी सेलेक्ट करें
    $sql = "SELECT ward_id, ward_no FROM wards ORDER BY ward_id ASC";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $wards = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $response = ["status" => "success", "wards" => $wards];
        mysqli_free_result($result);
    } else {
        $response["message"] = "Query failed: " . mysqli_error($conn);
    }
} else {
    $response["message"] = "Database connection failed.";
}

echo json_encode($response);
?>