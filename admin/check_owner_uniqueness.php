<?php
// check_owner_uniqueness.php - Owner Mobile/Email Duplication Check

// **Ensure the path to db.php file is correct**
require_once "db.php"; 

header('Content-Type: application/json');

// Get field and value from AJAX
$field = $_POST['field'] ?? ''; // e.g., 'mobile' or 'email'
$value = $_POST['value'] ?? '';
$response = ['is_unique' => true, 'message' => ''];

if ($conn->connect_error) {
    // Database connection error
    $response['is_unique'] = false;
    $response['message'] = 'Database connection failed.';
    echo json_encode($response);
    exit;
}

// Stop check for empty values
if (empty($field) || empty($value)) {
    $response['is_unique'] = true; 
    echo json_encode($response);
    exit;
}

$column_name = '';
$error_message = '';

// ----------------------------------------
// 1. Server-Side Validation & Setup
// ----------------------------------------
if ($field === 'mobile') {
    // Mobile Validation (10 digits, numeric)
    if (strlen($value) !== 10 || !ctype_digit($value)) {
        $response['is_unique'] = false;
        $response['message'] = 'Mobile Number must be exactly 10 digits.';
    } else {
        $column_name = 'mobile';
        $error_message = 'This mobile number is already associated with another owner.';
    }
} elseif ($field === 'email') {
    // Email Validation (Format)
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        $response['is_unique'] = false;
        $response['message'] = 'Please enter a valid email address format.';
    } else {
        $column_name = 'email';
        $error_message = 'This email address is already associated with another owner.';
    }
} 

// ----------------------------------------
// 2. SQL Duplication Check
// ----------------------------------------
if ($response['is_unique'] && !empty($column_name)) {
    // Query: SELECT COUNT(*) FROM assessment_owners WHERE mobile/email = ?
    $sql = "SELECT COUNT(*) FROM assessment_owners WHERE {$column_name} = ?";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // Bind the value (securely)
        $stmt->bind_param("s", $value);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        
        if ($count > 0) {
            $response['is_unique'] = false;
            $response['message'] = $error_message;
        }
    } else {
        $response['is_unique'] = false;
        $response['message'] = 'SQL error: ' . $conn->error;
    }
}

$conn->close();
echo json_encode($response);
?>