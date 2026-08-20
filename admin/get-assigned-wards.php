<?php
require_once "db.php";

header('Content-Type: application/json');

$response = [];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $surveyorId = $_GET['id'];
    
    // ✅ FIX: ward_id को सेलेक्ट करें और Prepared Statement का उपयोग करें
    $sql = "SELECT ward_id FROM surveyor_wards WHERE surveyor_id = ? AND status = 'active'";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $surveyorId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            // ward_id को fetch करें
            $assignedWards = mysqli_fetch_all($result, MYSQLI_ASSOC);
            $response = $assignedWards; 
            mysqli_free_result($result);
        }
        $stmt->close();
    } else {
        // Prepare statement fail होने पर भी खाली array रिटर्न करें
        $response = [];
    }
} else {
    // id न दिए जाने पर खाली array रिटर्न करें
    $response = [];
}

echo json_encode($response);
?>