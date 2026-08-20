<?php
require_once "db.php";
session_start();

// --- Helper function for dynamic bind_param (मल्टीपल इंसर्ट के लिए आवश्यक) ---
if (!function_exists('refValues')) {
// ... (refValues function definition) ...
    function refValues($arr){
        if (strnatcmp(phpversion(),'5.3') >= 0) // PHP 5.3+
        {
            $refs = array();
            foreach($arr as $key => $value)
                $refs[$key] = &$arr[$key];
            return $refs;
        }
        return $arr;
    }
}
// ----------------------------------------------------------------------------

header('Content-Type: application/json');

$response = ["status" => "error", "message" => "An unknown error occurred."];

// ... (Authorization check) ...

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Surveyor ID को सुरक्षित रूप से validate करें
    $surveyorId = filter_input(INPUT_POST, 'surveyor_id', FILTER_VALIDATE_INT);
    $wards = $_POST['wards'] ?? []; // यह array अब Ward IDs को hold करेगा

    if ($surveyorId === false || !is_array($wards)) {
        $response["message"] = "Invalid surveyor ID or wards data.";
        echo json_encode($response);
        exit;
    }

    // Start a transaction for atomicity
    mysqli_begin_transaction($conn);

    try {
        // 1. Delete existing assignments for the surveyor (Secure Prepared Statement)
        $sql_delete = "DELETE FROM surveyor_wards WHERE surveyor_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        
        if (!$stmt_delete) {
             throw new Exception("Delete Prepare Failed: " . $conn->error);
        }
        
        $stmt_delete->bind_param("i", $surveyorId);
        if (!$stmt_delete->execute()) {
            throw new Exception($stmt_delete->error);
        }
        $stmt_delete->close();

        // 2. Insert the new Ward IDs (Secure Prepared Statement)
        if (count($wards) > 0) {
            $values = [];
            $types = "";
            $bind_params = [];
            
            // Build the dynamic query parts (surveyor_id, ward_id, status)
            foreach ($wards as $ward_id) {
                $ward_id_int = (int)$ward_id; 
                
                $values[] = "(?, ?, 'active')";
                $types .= "ii"; // 'i' for surveyorId, 'i' for ward_id
                
                $bind_params[] = $surveyorId;
                $bind_params[] = $ward_id_int;
            }
            
            // ✅ NOTE: Insert into the correct column name 'ward_id'
            $sql_insert = "INSERT INTO surveyor_wards (surveyor_id, ward_id, status) VALUES " . implode(", ", $values);
            
            $stmt_insert = $conn->prepare($sql_insert);
            
            if (!$stmt_insert) {
                 throw new Exception("Insert Prepare Failed: " . $conn->error);
            }
            
            $bind_arr = array_merge([$types], $bind_params);
            call_user_func_array([$stmt_insert, 'bind_param'], refValues($bind_arr));
             
            if (!$stmt_insert->execute()) {
                 throw new Exception($stmt_insert->error);
            }
            $stmt_insert->close();
        }
        
        // 3. Commit the transaction
        mysqli_commit($conn);
        $response = ["status" => "success", "message" => "Wards assigned successfully."];

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $response["message"] = "Database error: Failed to assign wards. " . $e->getMessage();
        http_response_code(500); 
    }
} else {
    $response["message"] = "Invalid request method.";
    http_response_code(405);
}

echo json_encode($response);
mysqli_close($conn);
?>