<?php
require_once __DIR__ . '/db.php'; // db.php is included

// Check for POST request with 'id' and 'remark'
if (isset($_POST['id']) && isset($_POST['remark'])) {
    $id = (int) $_POST['id'];
    $remark = $_POST['remark']; // Get the remark from the POST request

    // Transaction start
    $conn->begin_transaction();

    try {
        // Floors soft delete - now includes remark
        $stmt = $conn->prepare("UPDATE assessment_floors SET is_deleted = 1, deleted_at = NOW(), deleted_remark = ? WHERE assessment_id = ?");
        $stmt->bind_param("si", $remark, $id);
        $stmt->execute();

        // Owners soft delete - now includes remark
        $stmt = $conn->prepare("UPDATE assessment_owners SET is_deleted = 1, deleted_at = NOW(), deleted_remark = ? WHERE assessment_id = ?");
        $stmt->bind_param("si", $remark, $id);
        $stmt->execute();

        // Main assessment soft delete - now includes remark
        $stmt = $conn->prepare("UPDATE assessments SET is_deleted = 1, deleted_at = NOW(), deleted_remark = ? WHERE id = ?");
        $stmt->bind_param("si", $remark, $id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        // Send a JSON response for the JavaScript fetch request to handle
        echo json_encode(['status' => 'ok', 'msg' => 'Record deleted successfully.']);
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        // Send a JSON error response
        echo json_encode(['status' => 'error', 'msg' => "Error deleting record: " . $e->getMessage()]);
        exit;
    }
} else {
    // Send a JSON response for invalid request
    echo json_encode(['status' => 'error', 'msg' => 'Invalid request. Missing ID or remark.']);
    exit;
}
?>