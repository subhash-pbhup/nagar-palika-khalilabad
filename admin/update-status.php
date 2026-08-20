<?php
require_once "db.php";

// Check if both 'id' and 'status' POST variables are set
if (isset($_POST['id'], $_POST['status'])) {
    $id = intval($_POST['id']);
    $status = ($_POST['status'] === 'active') ? 'active' : 'inactive';

    // Start a transaction for data integrity
    $conn->begin_transaction();

    try {
        // Query 1: Update the status in the 'surveyors' table
        $sql1 = "UPDATE surveyors SET status=? WHERE id=?";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->bind_param("si", $status, $id);
        $stmt1->execute();

        // Query 2: Update the status in the 'surveyor_wards' table
        $sql2 = "UPDATE surveyor_wards SET status=? WHERE surveyor_id=?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("si", $status, $id);
        $stmt2->execute();

        // If both queries were successful, commit the transaction
        $conn->commit();

        // Return a JSON success response
        echo json_encode(['status' => 'success', 'message' => 'Status updated successfully for both tables.']);

    } catch (Exception $e) {
        // If an error occurs, roll back the transaction
        $conn->rollback();

        // Return a JSON error response with the specific error message
        echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage()]);
    }
    
    // Stop script execution after sending the response
    die();
}

// If required POST data is missing, return a specific error
echo json_encode(['status' => 'error', 'message' => 'Missing ID or Status.']);
?>