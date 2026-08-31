<?php
// ✅ 1. Session को शुरू करें और User ID/Role प्राप्त करें
session_start();
$user_id = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['role'] ?? 'user';
$is_surveyor = ($user_role === 'surveyor' && $user_id > 0);


// Ensure db.php file has the correct connection details
require_once "db.php";
require_once "assessment_audit_helper.php";

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
// ZONE / WARD / MOHALLA / PROPERTY ID
// -----------------------------
$zone_id = 1; // Khalilabad currently has Zone-1

$mohalla_id = isset($_POST['mohalla_id']) ? (int)$_POST['mohalla_id'] : 0;

if ($mohalla_id <= 0) {
    die("❌ Mohalla not provided.");
}

// Make sure selected Mohalla actually belongs to selected Ward
$check_mohalla = $conn->prepare("SELECT mohalla_id FROM mohalla WHERE mohalla_id = ? AND ward_id = ? LIMIT 1");
$check_mohalla->bind_param("ii", $mohalla_id, $ward_no);
$check_mohalla->execute();
$check_mohalla_res = $check_mohalla->get_result();

if ($check_mohalla_res->num_rows === 0) {
    $check_mohalla->close();
    die("❌ Invalid Mohalla selected for this Ward.");
}
$check_mohalla->close();

// Property type code as per the Property ID format
$property_type_input = trim($_POST['property_type'] ?? '');

$property_type_code = match (strtolower($property_type_input)) {
    'residential'     => 'R',
    'non residential' => 'N',
    'mix'             => 'M',
    'industrial'      => 'I',
    default           => ''
};
// echo "<pre>";
// print_r($property_type_code);  
// die;
if ($property_type_code === '') {
    die("❌ Invalid property type for Property ID.");
}

// 6-digit running serial. It does NOT reset with Ward/Zone.
$sql_serial = "
    SELECT MAX(CAST(SUBSTRING(property_id, 11, 6) AS UNSIGNED)) AS last_serial
    FROM assessments
    WHERE property_id IS NOT NULL
      AND property_id <> ''
";
$serial_result = $conn->query($sql_serial);

$last_serial = 0;
if ($serial_result && ($serial_row = $serial_result->fetch_assoc())) {
    $last_serial = (int)($serial_row['last_serial'] ?? 0);
}

$next_serial = $last_serial + 1;

if ($next_serial > 999999) {
    die("❌ Property ID serial limit reached.");
}

// Property ID:
// 09 = UP State Code
// 526 = Khalilabad ULB Code
// 01 = Zone
// 001 = Ward
// 000001 = Running Serial
// R/N/M = Property Type
$property_id =
    '09' .
    '526' .
    str_pad($zone_id, 2, '0', STR_PAD_LEFT) .
    str_pad((int)$ward_no, 3, '0', STR_PAD_LEFT) .
    str_pad($next_serial, 6, '0', STR_PAD_LEFT) .
    $property_type_code;

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

// Only ONE owner is allowed for one assessment.
if (!is_array($owner_data)) {
    $owner_data = [];
}
if (count($owner_data) > 1) {
    die("❌ Error: Only one owner can be added to a property assessment.");
}

// -----------------------------
// INSERT MAIN ASSESSMENT - FIX ArgumentCountError (Line 171)
// -----------------------------
// Total 28 Fields (Excluding the non-existent 'location' column)
$fields = [
    'municipality_name',
    'year_of_assessment',
    'zone_id',
    'ward_id',
    'mohalla_id',
    'property_id',
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
    'supporting_doc_1',
    'supporting_doc_2',
    'supporting_doc_3',
    'building_type',
    'latitude',
    'longitude'
]; // Total Count: 32

$values = [
    $_POST['municipality'] ?? '',
    $_POST['assesment_year'] ?? '',
    $zone_id,
    (int)$ward_no,
    $mohalla_id,
    $property_id,
    $new_holding_number,
    $_POST['old_holding'] ?? '',
    $_POST['property_type'] ?? '',
    $_POST['old_pid'] ?? '',
    $_POST['road'] ?? '',
    (float)($_POST['plot_area'] ?? 0),
    $_POST['property_status'] ?? '',
    $_POST['house_no'] ?? '',
    $_POST['plot_no'] ?? '',
    $_POST['khata_no'] ?? '',
    $_POST['khasra_no'] ?? '',
    $_POST['addr1'] ?? '',
    $_POST['addr2'] ?? '',
    $ward_no, // kept for existing code/backward compatibility
    $_POST['pincode'] ?? '',
    $water_tax,
    $center_image_path,
    $left_image_path,
    $right_image_path,
    $document_path,
    $supporting_doc_1_path,
    $supporting_doc_2_path,
    $supporting_doc_3_path,
    $_POST['building_type'] ?? '',
    $_POST['latitude'] ?? '',
    $_POST['longitude'] ?? ''
];

// 32 types:
// municipality(s), year(s), zone(i), ward(i), mohalla(i), property_id(s),
// new_holding(s), old_holding(s), property_type(s), old_pid(s), road(s),
// plot_area(d), property_status(s), house_no(s), plot_no(s), khata_no(s),
// khasra_no(s), addr1(s), addr2(s), ward(s), pincode(s), water_tax(i),
// remaining fields are strings.
$type_string = "ssiiissssssdsssssssssissssssssss";

// Audit: store the user who created this assessment.
$fields[] = 'created_by';
$values[] = ($user_id > 0 ? $user_id : null);
$type_string .= 'i';

// Existing surveyor relation is kept as-is.
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

    // ============================================================
    // AUDIT LOG: CREATE ASSESSMENT
    // ============================================================
    $created_snapshot = [
        'id' => $assessment_id,
        'municipality_name' => $_POST['municipality'] ?? '',
        'year_of_assessment' => $_POST['assesment_year'] ?? '',
        'zone_id' => $zone_id,
        'ward_id' => (int)$ward_no,
        'mohalla_id' => $mohalla_id,
        'property_id' => $property_id,
        'new_holding' => $new_holding_number,
        'old_holding' => $_POST['old_holding'] ?? '',
        'property_type' => $_POST['property_type'] ?? '',
        'property_status' => $_POST['property_status'] ?? '',
        'road' => $_POST['road'] ?? '',
        'plot_area' => (float)($_POST['plot_area'] ?? 0),
        'building_type' => $_POST['building_type'] ?? '',
        'house_no' => $_POST['house_no'] ?? '',
        'plot_no' => $_POST['plot_no'] ?? '',
        'khata_no' => $_POST['khata_no'] ?? '',
        'khasra_no' => $_POST['khasra_no'] ?? '',
        'addr1' => $_POST['addr1'] ?? '',
        'addr2' => $_POST['addr2'] ?? '',
        'pincode' => $_POST['pincode'] ?? '',
        'water_tax' => $water_tax,
        'latitude' => $_POST['latitude'] ?? '',
        'longitude' => $_POST['longitude'] ?? '',
        'surveyor_id' => $is_surveyor ? $user_id : null
    ];

    addAudit($conn, [
        'assessment_id' => $assessment_id,
        'property_id' => $property_id,
        'entity_type' => 'ASSESSMENT',
        'entity_id' => $assessment_id,
        'action' => 'CREATE',
        'old_data' => [],
        'new_data' => $created_snapshot,
        'changed_fields' => array_keys($created_snapshot),
        'remark' => 'Assessment created'
    ]);

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
                if ($owner_stmt->execute()) {
                    $owner_db_id = $owner_stmt->insert_id;

                    addAudit($conn, [
                        'assessment_id' => $assessment_id,
                        'property_id' => $property_id,
                        'entity_type' => 'OWNER',
                        'entity_id' => $owner_db_id,
                        'action' => 'CREATE',
                        'old_data' => [],
                        'new_data' => [
                            'id' => $owner_db_id,
                            'owner_name' => $owner_name,
                            'father_husband_pan' => $father_name,
                            'gender' => $gender,
                            'mobile' => $mobile,
                            'email' => $email
                        ],
                        'changed_fields' => ['owner_name', 'father_husband_pan', 'gender', 'mobile', 'email'],
                        'remark' => 'Owner created'
                    ]);
                }
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

                if ($floor_stmt->execute()) {
                    $floor_db_id = $floor_stmt->insert_id;

                    addAudit($conn, [
                        'assessment_id' => $assessment_id,
                        'property_id' => $property_id,
                        'entity_type' => 'FLOOR',
                        'entity_id' => $floor_db_id,
                        'action' => 'CREATE',
                        'old_data' => [],
                        'new_data' => [
                            'id' => $floor_db_id,
                            'floor_no' => $floor_no,
                            'construction_type' => $construction,
                            'date_from' => $date_from,
                            'date_to' => $date_to,
                            'occupancy_type' => $occupancy,
                            'build_up_area' => $build_area,
                            'usage_type' => $usage,
                            'non_residential_group' => $non_res_group,
                            'property_name' => $property_name
                        ],
                        'changed_fields' => [
                            'floor_no',
                            'construction_type',
                            'date_from',
                            'date_to',
                            'occupancy_type',
                            'build_up_area',
                            'usage_type',
                            'non_residential_group',
                            'property_name'
                        ],
                        'remark' => 'Floor created'
                    ]);
                }
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
        alert('✅ Property Assessment Saved Successfully!\\nProperty ID: {$property_id}\\nHolding No: {$new_holding_number}');
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
