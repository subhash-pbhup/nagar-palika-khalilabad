<?php
// Include db.php.
include("db.php");
header('Content-Type: application/json');

// Check 1: Database connection check
if (!isset($conn) || $conn->connect_error) {
    echo json_encode([
        'exists' => true,
        'message' => 'Error (DB): Database connection failed. Please check db.php.',
        'error_code' => 500
    ]);
    exit;
}

// Check 2: POST data check
if (!isset($_POST['new_holding']) || !isset($_POST['ward']) || !isset($_POST['assessment_id'])) {
    echo json_encode([
        'exists' => true,
        'message' => 'Error (Data): Invalid request data received by server.',
        'error_code' => 400
    ]);
    exit;
}

$new_holding = trim($_POST['new_holding']);
$ward = trim($_POST['ward']);
$assessment_id = (int)$_POST['assessment_id'];

if (empty($new_holding) || empty($ward)) {
    echo json_encode([
        'exists' => true,
        'message' => 'Error: Holding number and Ward cannot be empty.',
        'error_code' => 400
    ]);
    exit;
}

// --- NEW SERVER-SIDE FORMAT CHECK ---
$expected_prefix = "KLB-W" . $ward . "-";
$expected_length = strlen($expected_prefix) + 5;

// Check if: 1. It starts with the correct prefix, 2. The total length is correct, 3. The last 5 characters are numeric.
if (strpos($new_holding, $expected_prefix) !== 0 || strlen($new_holding) !== $expected_length || !ctype_digit(substr($new_holding, -5))) {
    echo json_encode([
        'exists' => true,
        'message' => 'Error: Holding Number format is incorrect. It should be in the format ' . $expected_prefix . 'XXXXX (5 digits) for Ward ' . htmlspecialchars($ward) . '.',
        'error_code' => 400
    ]);
    exit;
}
// --- END NEW SERVER-SIDE FORMAT CHECK ---


// Check 3: Database Uniqueness Check
// SQL statement: Check for duplicate new_holding in the same ward, 
// EXCLUDING the current assessment ID being edited.
$sql = "SELECT id FROM assessments WHERE new_holding = ? AND ward = ? AND id != ? AND is_deleted = 0 LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'exists' => true,
        'message' => 'Error: Database statement preparation failed. ' . $conn->error,
        'error_code' => 500
    ]);
    $conn->close();
    exit;
}

$stmt->bind_param("ssi", $new_holding, $ward, $assessment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Duplicate found
    echo json_encode([
        'exists' => true,
        'message' => 'Error: This New Holding Number already exists in Ward ' . htmlspecialchars($ward) . '.'
    ]);
} else {
    // No duplicate found
    echo json_encode([
        'exists' => false,
        'message' => 'Success: New Holding Number is available and ready for update.'
    ]);
}

$stmt->close();
$conn->close();
