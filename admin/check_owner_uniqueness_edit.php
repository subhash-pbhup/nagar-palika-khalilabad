<?php
// check_owner_uniqueness_edit.php
// यह फाइल Edit Assessment के लिए है, यह खुद की ID को ignore करती है।

require_once "db.php"; 

header('Content-Type: application/json');

$field = $_POST['field'] ?? ''; // mobile या email
$value = trim($_POST['value'] ?? '');
$exclude_id = intval($_POST['exclude_id'] ?? 0); // जिस Owner को edit कर रहे हैं उसकी ID

$response = ['is_unique' => true, 'message' => ''];

// DB Connection Check
if ($conn->connect_error) {
    echo json_encode(['is_unique' => false, 'message' => 'Database error']);
    exit;
}

if (empty($field) || empty($value)) {
    echo json_encode($response);
    exit;
}

$column_name = '';
$error_message = '';

// 1. Validation Logic
if ($field === 'mobile') {
    if (strlen($value) !== 10 || !ctype_digit($value)) {
        $response['is_unique'] = false;
        $response['message'] = 'Mobile Number must be 10 digits.';
    } else {
        $column_name = 'mobile';
        $error_message = 'Mobile number already exists.';
    }
} elseif ($field === 'email') {
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        $response['is_unique'] = false;
        $response['message'] = 'Invalid email format.';
    } else {
        $column_name = 'email';
        $error_message = 'Email address already exists.';
    }
} 

// 2. SQL Check (Exclude Current ID)
if ($response['is_unique'] && !empty($column_name)) {
    // यहाँ हम check करेंगे कि ID != exclude_id हो
    $sql = "SELECT COUNT(*) FROM assessment_owners WHERE {$column_name} = ? AND id != ? AND is_deleted = 0";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("si", $value, $exclude_id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        
        if ($count > 0) {
            $response['is_unique'] = false;
            $response['message'] = $error_message;
        }
    }
}

$conn->close();
echo json_encode($response);
?>