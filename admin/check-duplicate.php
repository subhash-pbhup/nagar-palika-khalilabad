<?php
require_once "db.php";

$field = $_GET['field'] ?? '';
$value = $_GET['value'] ?? '';

$response = ['exists' => false];

if (in_array($field, ['email','mobile']) && !empty($value)) {
    $stmt = $conn->prepare("SELECT id FROM surveyors WHERE $field = ? LIMIT 1");
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $response['exists'] = true;
    }
}

header("Content-Type: application/json");
echo json_encode($response);
?>