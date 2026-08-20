<?php
// ✅ 1. Session को शुरू करें और User ID/Role प्राप्त करें
session_start();
$user_id = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['role'] ?? 'user';
$is_surveyor = ($user_role === 'surveyor' && $user_id > 0);


// Ensure db.php file has the correct connection details
require_once "db.php";

// Check if the database connection is successful
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// -----------------------------
// HOLDING NUMBER AUTO-GENERATE
// -----------------------------
$ward_no = $_POST['ward_id'] ?? '';

if (!empty($ward_no)) {
    // Get last holding number for this ward
    $sql_last = "SELECT new_holding FROM assessments WHERE ward = ? ORDER BY id DESC LIMIT 1";
    $stmt_last = $conn->prepare($sql_last);
    $stmt_last->bind_param("s", $ward_no);
    $stmt_last->execute();
    $result_last = $stmt_last->get_result();

    $nextNumber = 1;
    if ($result_last->num_rows > 0) {
        $row = $result_last->fetch_assoc();
        $last_holding = $row['new_holding']; // e.g. DEO-W1-00005

        $parts = explode("-", $last_holding);
        $last_num = intval(end($parts));
        $nextNumber = $last_num + 1;
    }

    $new_holding_number = "KLB-W" . $ward_no . "-" . str_pad($nextNumber, 5, "0", STR_PAD_LEFT);

    // Duplicate safety check
    $check = $conn->prepare("SELECT id FROM assessments WHERE new_holding = ? LIMIT 1");
    $check->bind_param("s", $new_holding_number);
    $check->execute();
    $check_res = $check->get_result();
    if ($check_res->num_rows > 0) {
        die("❌ Error: Generated Holding number already exists! Please try again.");
    }
} else {
    die("❌ Ward number not provided.");
}

// -----------------------------
// CREATE UNIQUE FOLDER FOR FILES
// -----------------------------
$targetDir = "uploads/" . $new_holding_number . "/";
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// File upload handler (Modified to accept common file types)
function handleFileUpload($file, $targetDir)
{
    if (isset($file) && $file['error'] == 0) {
        $fileName = time() . "_" . uniqid() . "_" . basename($file["name"]);
        $targetFile = $targetDir . $fileName;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        // Allow common image and document files
        if (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'pdf', 'doc', 'docx'])) {
            return NULL;
        }

        if (move_uploaded_file($file["tmp_name"], $targetFile)) {
            // Return relative path to be saved in DB
            return $targetFile;
        }
    }
    return NULL;
}

// Existing and New File Uploads
$document_path          = handleFileUpload($_FILES['doc_proof'] ?? null, $targetDir);
$center_image_path      = handleFileUpload($_FILES['center_image_gps'] ?? null, $targetDir);
$left_image_path        = handleFileUpload($_FILES['left_image_gps'] ?? null, $targetDir);
$right_image_path       = handleFileUpload($_FILES['right_image_gps'] ?? null, $targetDir);
// New Document Fields
$supporting_doc_1_path  = handleFileUpload($_FILES['supporting_doc_1'] ?? null, $targetDir);
$supporting_doc_2_path  = handleFileUpload($_FILES['supporting_doc_2'] ?? null, $targetDir);
$supporting_doc_3_path  = handleFileUpload($_FILES['supporting_doc_3'] ?? null, $targetDir);


// Handle 'water_tax' checkbox
$water_tax = isset($_POST['water_tax']) ? 1 : 0;

// Get owner + floor JSON
$owner_data  = !empty($_POST['owners_json']) ? json_decode($_POST['owners_json'], true) : [];
$floors_data = !empty($_POST['floors_json']) ? json_decode($_POST['floors_json'], true) : [];

// -----------------------------
// INSERT MAIN ASSESSMENT - FIX ArgumentCountError (Line 171)
// -----------------------------
// Total 28 Fields (Excluding the non-existent 'location' column)
$fields = [
    'municipality_name',
    'year_of_assessment',
    'new_holding',
    'old_holding',
    'property_type',
    'old_pid',
    'road',
    'plot_area',
    'property_status',
    'house_no',
    'plot_no',
    'khata_no',
    'khasra_no',
    'addr1',
    'addr2',
    'ward',
    'pincode',
    'water_tax',
    'center_image_gps',
    'left_image_gps',
    'right_image_gps',
    'doc_proof',
    // New Document Fields
    'supporting_doc_1',
    'supporting_doc_2',
    'supporting_doc_3',
    'building_type',
    'latitude',
    'longitude'
]; // Total Count: 28

// Total 28 Values
$values = [
    $_POST['municipality'] ?? '',
    $_POST['assesment_year'] ?? '',
    $new_holding_number,
    $_POST['old_holding'] ?? '',
    $_POST['property_type'] ?? '',
    $_POST['old_pid'] ?? '',
    $_POST['road'] ?? '',
    $_POST['plot_area'] ?? '', // Bound as double (d)
    $_POST['property_status'] ?? '',
    $_POST['house_no'] ?? '',
    $_POST['plot_no'] ?? '',
    $_POST['khata_no'] ?? '',
    $_POST['khasra_no'] ?? '',
    $_POST['addr1'] ?? '',
    $_POST['addr2'] ?? '',
    $ward_no,
    $_POST['pincode'] ?? '',
    $water_tax, // Bound as integer (i)
    $center_image_path,
    $left_image_path,
    $right_image_path,
    $document_path,
    // New Document Values
    $supporting_doc_1_path,
    $supporting_doc_2_path,
    $supporting_doc_3_path,
    $_POST['building_type'] ?? '',
    $_POST['latitude'] ?? '',
    $_POST['longitude'] ?? ''
]; // Total Count: 28

// FIX: Type string must be 28 characters long.
// Breakdown: 7s + 1d (plot_area) + 9s + 1i (water_tax) + 10s (files/build/lat/lon) = 28 chars
$type_string = "sssssssdssssssssisssssssssss";

// Conditionally add surveyor_id
if ($is_surveyor) {
    $fields[] = 'surveyor_id';
    $values[] = $user_id;
    $type_string .= 'i';
}

$placeholders = array_fill(0, count($fields), '?');
$sql = "INSERT INTO assessments (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("❌ SQL Prepare Error: " . $conn->error);
}

// Line 171 (Original error line) - Bind is now guaranteed to match count
if (!$stmt->bind_param($type_string, ...$values)) {
    // Added debug info to error message for future tracing if needed
    die("❌ Bind Param Error: Check types and count! " . $stmt->error);
}

if ($stmt->execute()) {
    $assessment_id = $stmt->insert_id;

    // -----------------------------
    // SAVE OWNERS (Working)
    // -----------------------------
    if (!empty($owner_data)) {
        $owner_sql = "INSERT INTO assessment_owners 
            (assessment_id, owner_name, father_husband_pan, gender, mobile, email) 
            VALUES (?,?,?,?,?,?)";
        $owner_stmt = $conn->prepare($owner_sql);
        if ($owner_stmt) {
            foreach ($owner_data as $owner) {
                $owner_name  = ($owner['title'] ?? '') . ' ' . ($owner['name'] ?? '');
                $father_name = $owner['careof'] ?? '';
                $gender      = $owner['gender'] ?? '';
                $mobile      = $owner['mobile'] ?? '';
                $email       = $owner['email'] ?? '';

                $owner_stmt->bind_param(
                    "isssss",
                    $assessment_id,
                    $owner_name,
                    $father_name,
                    $gender,
                    $mobile,
                    $email
                );
                $owner_stmt->execute();
            }
            $owner_stmt->close();
        }
    }

    // -----------------------------
    // SAVE FLOORS (Includes new date_from, date_to fields)
    // -----------------------------
    if (!empty($floors_data)) {
        $floor_sql = "INSERT INTO assessment_floors 
            (assessment_id, floor_no, construction_type, date_from, date_to, occupancy_type, build_up_area, usage_type, non_residential_group, property_name) 
            VALUES (?,?,?,?,?,?,?,?,?,?)";

        $floor_stmt = $conn->prepare($floor_sql);
        if ($floor_stmt) {
            foreach ($floors_data as $floor) {
                $floor_no      = $floor['floor_no'] ?? '';
                $construction  = $floor['construction'] ?? '';
                $date_from     = $floor['date_from'] ?? NULL; // New Field
                $date_to       = $floor['date_to'] ?? NULL;   // New Field
                $occupancy     = $floor['occupancy'] ?? '';
                $build_area    = (float)($floor['buildup'] ?? 0);
                $usage         = $floor['usage'] ?? '';
                $non_res_group = $floor['non_residential'] ?? '';
                $property_name = $floor['properties'] ?? '';

                // i: assessment_id, s: floor_no, s: construction, s: date_from, s: date_to, s: occupancy, d: build_area, sss: usage/groups
                $floor_stmt->bind_param(
                    "isssssdsss",
                    $assessment_id,
                    $floor_no,
                    $construction,
                    $date_from,
                    $date_to,
                    $occupancy,
                    $build_area,
                    $usage,
                    $non_res_group,
                    $property_name
                );
                $floor_stmt->execute();
            }
            $floor_stmt->close();
        }
    }

    // -----------------------------
    // Redirection
    // -----------------------------
    $redirect_url = ($user_role === 'surveyor') ? 'surveyor-dashboard.php' : 'view-assesment.php';

    // Success
    echo "<script>
        alert('✅ Property Assessment Saved Successfully! Holding No: {$new_holding_number}');
        window.location.href = '{$redirect_url}';
    </script>";
    exit();
} else {
    // Failure
    echo "<script>
        alert('❌ Insert Error: " . addslashes($stmt->error) . "');
        window.location.href = 'view-assesment.php';
    </script>";
    exit();
}

$stmt->close();
$conn->close();
