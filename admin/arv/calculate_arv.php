<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/arv_engine.php';

try {

    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception("Assessment ID missing in URL.");
    }

    $id = (int) $_GET['id'];

    if ($id <= 0) {
        throw new Exception("Invalid Assessment ID.");
    }

    $data = calculateARV($id, $conn);

    if (empty($data)) {
        throw new Exception("ARV calculation failed.");
    }

    $_SESSION['ARV_PREVIEW'] = $data;

    header("Location: arv_preview.php");
    exit;
} catch (Exception $e) {

    echo "<div style='color:red; border:1px solid red; padding:20px; font-family:sans-serif;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "<br><br><a href='../view-assessment.php?id=" . ($id ?? '') . "'>Go Back</a>";
    echo "</div>";
}
