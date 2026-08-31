<?php
// ============================================================
// SESSION
// ============================================================
session_start();

$user_id = (int)($_SESSION['user_id'] ?? 0);

// Verification must use users.role_id -> roles.id.
// Do not trust the session role because old sessions may contain "admin".
$user_role = '';
$current_role_id = 0;
$current_role_name = '';
$is_surveyor = false;


// ============================================================
// DATABASE
// ============================================================
require_once "db.php";
require_once "assessment_audit_helper.php";

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}


// ============================================================
// CURRENT USER ROLE FROM DATABASE
// ============================================================
// Do not rely only on $_SESSION['role'] for verification routing.
// users.role_id -> roles.id is the source of truth.
if ($user_id > 0) {

    $user_role_stmt = $conn->prepare("
        SELECT
            u.role_id,
            u.role,
            u.status AS user_status,
            r.role_name,
            r.status AS role_status
        FROM users u
        LEFT JOIN roles r
            ON r.id = u.role_id
        WHERE u.id = ?
        LIMIT 1
    ");

    if (!$user_role_stmt) {
        die("❌ Unable to read user role: " . $conn->error);
    }

    $user_role_stmt->bind_param("i", $user_id);
    $user_role_stmt->execute();

    $user_role_result = $user_role_stmt->get_result();
    $current_user_data = $user_role_result->fetch_assoc();

    $user_role_stmt->close();

    if ($current_user_data) {
        $current_role_id = (int)($current_user_data['role_id'] ?? 0);
        $current_role_name = strtoupper(trim($current_user_data['role_name'] ?? ''));

        if ($current_role_name === '') {
            $current_role_name = strtoupper(trim($current_user_data['role'] ?? ''));
        }

        $user_role = $current_role_name;
        $is_surveyor = ($current_role_name === 'SURVEYOR');
    }
}


// ============================================================
// DYNAMIC VERIFICATION ROLE ORDER
// ============================================================
// Source of truth:
// users.role_id -> roles.id
//
// Workflow is built dynamically from active roles.
// ADMIN is always the FINAL role.
// If verification_order exists, it is used.
// Otherwise role IDs are used descending, which matches the
// current database: 8 LIPIK -> ... -> 1 ADMIN.

function normalizeVerificationRoleName($name)
{
    $name = strtoupper(trim((string)$name));
    $name = preg_replace('/\s+/', ' ', $name);

    if ($name === 'ADMINISTRATOR' || $name === 'SUPER ADMIN') {
        return 'ADMIN';
    }

    return $name;
}

$hasVerificationOrder = false;

$colCheck = $conn->query(
    "SHOW COLUMNS FROM roles LIKE 'verification_order'"
);

if ($colCheck && $colCheck->num_rows > 0) {
    $hasVerificationOrder = true;
}

if ($hasVerificationOrder) {
    $rolesSql = "
        SELECT id, role_name, verification_order
        FROM roles
        WHERE status = 1
        ORDER BY
            CASE
                WHEN UPPER(TRIM(role_name)) IN
                    ('ADMIN','ADMINISTRATOR','SUPER ADMIN')
                THEN 1 ELSE 0
            END ASC,
            CASE
                WHEN UPPER(TRIM(role_name)) IN
                    ('ADMIN','ADMINISTRATOR','SUPER ADMIN')
                THEN 999999
                ELSE verification_order
            END ASC,
            id ASC
    ";
} else {
    $rolesSql = "
        SELECT id, role_name, 0 AS verification_order
        FROM roles
        WHERE status = 1
        ORDER BY
            CASE
                WHEN UPPER(TRIM(role_name)) IN
                    ('ADMIN','ADMINISTRATOR','SUPER ADMIN')
                THEN 1 ELSE 0
            END ASC,
            id DESC
    ";
}

$rolesResult = $conn->query($rolesSql);

if (!$rolesResult) {
    die("❌ Unable to load verification roles: " . $conn->error);
}

$verification_roles = [];

while ($roleRow = $rolesResult->fetch_assoc()) {
    $verification_roles[] = [
        'id' => (int)$roleRow['id'],
        'name' => normalizeVerificationRoleName($roleRow['role_name']),
        'db_name' => $roleRow['role_name'],
        'order' => (int)$roleRow['verification_order']
    ];
}

// Keep ADMIN at the end regardless of its numeric order.
usort($verification_roles, function ($a, $b) {
    $aAdmin = ($a['name'] === 'ADMIN');
    $bAdmin = ($b['name'] === 'ADMIN');

    if ($aAdmin && !$bAdmin) return 1;
    if (!$aAdmin && $bAdmin) return -1;

    if ($a['order'] === $b['order']) {
        return $a['id'] <=> $b['id'];
    }

    return $a['order'] <=> $b['order'];
});

$first_verification_role_id = 0;

foreach ($verification_roles as $vr) {
    if ($vr['name'] !== 'ADMIN') {
        $first_verification_role_id = (int)$vr['id'];
        break;
    }
}

if ($first_verification_role_id <= 0) {
    die("❌ No active non-admin verification role found.");
}

// ============================================================
// CURRENT USER ROLE FROM DATABASE
// ============================================================
// Never use $_SESSION['role'] for approval decisions.
$current_role_id = 0;
$current_role_name = '';

if ($user_id > 0) {
    $uStmt = $conn->prepare("
        SELECT
            u.role_id,
            r.role_name
        FROM users u
        LEFT JOIN roles r ON r.id = u.role_id
        WHERE u.id = ?
          AND u.status = 'active'
        LIMIT 1
    ");

    if (!$uStmt) {
        die("❌ Unable to read current user role: " . $conn->error);
    }

    $uStmt->bind_param("i", $user_id);
    $uStmt->execute();
    $uData = $uStmt->get_result()->fetch_assoc();
    $uStmt->close();

    if ($uData) {
        $current_role_id = (int)($uData['role_id'] ?? 0);
        $current_role_name = normalizeVerificationRoleName(
            $uData['role_name'] ?? ''
        );
    }
}

$is_current_user_admin = ($current_role_name === 'ADMIN');

// ============================================================
// NEW ASSESSMENT VERIFICATION DECISION
// ============================================================
// ADMIN-created assessment => directly approved.
// Every other role => pending and starts at first verification role.
if ($is_current_user_admin) {
    $verification_status = 'approved';
    $current_verification_role_id = null;
    $verified_by = ($user_id > 0) ? $user_id : null;
    $verified_at = date('Y-m-d H:i:s');
} else {
    $verification_status = 'pending';
    $current_verification_role_id = $first_verification_role_id;
    $verified_by = null;
    $verified_at = null;
}

// ============================================================
// HOLDING NUMBER AUTO-GENERATE
// ============================================================
$ward_no = $_POST['ward_id'] ?? '';

if (!empty($ward_no)) {

    // Get last holding number for this ward
    $sql_last = "
        SELECT new_holding
        FROM assessments
        WHERE ward = ?
        ORDER BY id DESC
        LIMIT 1
    ";

    $stmt_last = $conn->prepare($sql_last);

    if (!$stmt_last) {
        die("❌ Prepare Error: " . $conn->error);
    }

    $stmt_last->bind_param("s", $ward_no);
    $stmt_last->execute();

    $result_last = $stmt_last->get_result();

    $nextNumber = 1;

    if ($result_last->num_rows > 0) {

        $row = $result_last->fetch_assoc();

        $last_holding = $row['new_holding'];

        // Example: KLB-W1-00005
        $parts = explode("-", $last_holding);

        $last_num = intval(end($parts));

        $nextNumber = $last_num + 1;
    }

    $stmt_last->close();


    // Generate new holding number
    $new_holding_number =
        "KLB-W" .
        $ward_no .
        "-" .
        str_pad($nextNumber, 5, "0", STR_PAD_LEFT);


    // Duplicate safety check
    $check = $conn->prepare("
        SELECT id
        FROM assessments
        WHERE new_holding = ?
        LIMIT 1
    ");

    if (!$check) {
        die("❌ Prepare Error: " . $conn->error);
    }

    $check->bind_param("s", $new_holding_number);
    $check->execute();

    $check_res = $check->get_result();

    if ($check_res->num_rows > 0) {
        $check->close();

        die("❌ Error: Generated Holding number already exists! Please try again.");
    }

    $check->close();
} else {

    die("❌ Ward number not provided.");
}


// ============================================================
// ZONE / WARD / MOHALLA
// ============================================================
$zone_id = 1;

$mohalla_id = isset($_POST['mohalla_id'])
    ? (int)$_POST['mohalla_id']
    : 0;

if ($mohalla_id <= 0) {
    die("❌ Mohalla not provided.");
}


// ============================================================
// CHECK MOHALLA BELONGS TO WARD
// ============================================================
$check_mohalla = $conn->prepare("
    SELECT mohalla_id
    FROM mohalla
    WHERE mohalla_id = ?
      AND ward_id = ?
    LIMIT 1
");

if (!$check_mohalla) {
    die("❌ Prepare Error: " . $conn->error);
}

$check_mohalla->bind_param(
    "ii",
    $mohalla_id,
    $ward_no
);

$check_mohalla->execute();

$check_mohalla_res = $check_mohalla->get_result();

if ($check_mohalla_res->num_rows === 0) {

    $check_mohalla->close();

    die("❌ Invalid Mohalla selected for this Ward.");
}

$check_mohalla->close();


// ============================================================
// PROPERTY TYPE
// ============================================================
$property_type_input = trim(
    $_POST['property_type'] ?? ''
);

$property_type_code = match (strtolower($property_type_input)) {

    'residential'     => 'R',
    'non residential' => 'N',
    'mix'             => 'M',
    'industrial'      => 'I',

    default           => ''
};


if ($property_type_code === '') {
    die("❌ Invalid property type for Property ID.");
}


// ============================================================
// PROPERTY ID SERIAL
// ============================================================
$sql_serial = "
    SELECT
        MAX(
            CAST(
                SUBSTRING(property_id, 11, 6)
                AS UNSIGNED
            )
        ) AS last_serial
    FROM assessments
    WHERE property_id IS NOT NULL
      AND property_id <> ''
";

$serial_result = $conn->query($sql_serial);

$last_serial = 0;

if (
    $serial_result &&
    ($serial_row = $serial_result->fetch_assoc())
) {

    $last_serial =
        (int)($serial_row['last_serial'] ?? 0);
}


$next_serial = $last_serial + 1;

if ($next_serial > 999999) {
    die("❌ Property ID serial limit reached.");
}


// ============================================================
// PROPERTY ID
// ============================================================
//
// 09   = UP State Code
// 526  = Khalilabad ULB Code
// 01   = Zone
// 001  = Ward
// 000001 = Running Serial
// R/N/M/I = Property Type
//
$property_id =
    '09' .
    '526' .
    str_pad(
        $zone_id,
        2,
        '0',
        STR_PAD_LEFT
    ) .
    str_pad(
        (int)$ward_no,
        3,
        '0',
        STR_PAD_LEFT
    ) .
    str_pad(
        $next_serial,
        6,
        '0',
        STR_PAD_LEFT
    ) .
    $property_type_code;


// ============================================================
// CREATE UNIQUE FOLDER
// ============================================================
$targetDir =
    "uploads/" .
    $new_holding_number .
    "/";

if (!is_dir($targetDir)) {

    if (!mkdir($targetDir, 0777, true)) {
        die("❌ Unable to create upload directory.");
    }
}


// ============================================================
// FILE UPLOAD FUNCTION
// ============================================================
function handleFileUpload($file, $targetDir)
{
    if (
        isset($file) &&
        isset($file['error']) &&
        $file['error'] == 0
    ) {

        $originalName =
            basename($file["name"]);

        $fileName =
            time() .
            "_" .
            uniqid() .
            "_" .
            $originalName;

        $targetFile =
            $targetDir .
            $fileName;

        $fileType =
            strtolower(
                pathinfo(
                    $targetFile,
                    PATHINFO_EXTENSION
                )
            );


        // Allowed file types
        $allowedTypes = [
            'jpg',
            'png',
            'jpeg',
            'pdf',
            'doc',
            'docx'
        ];


        if (
            !in_array(
                $fileType,
                $allowedTypes,
                true
            )
        ) {

            return NULL;
        }


        if (
            move_uploaded_file(
                $file["tmp_name"],
                $targetFile
            )
        ) {

            return $targetFile;
        }
    }

    return NULL;
}


// ============================================================
// FILE UPLOADS
// ============================================================
$document_path =
    handleFileUpload(
        $_FILES['doc_proof'] ?? null,
        $targetDir
    );

$center_image_path =
    handleFileUpload(
        $_FILES['center_image_gps'] ?? null,
        $targetDir
    );

$left_image_path =
    handleFileUpload(
        $_FILES['left_image_gps'] ?? null,
        $targetDir
    );

$right_image_path =
    handleFileUpload(
        $_FILES['right_image_gps'] ?? null,
        $targetDir
    );

$supporting_doc_1_path =
    handleFileUpload(
        $_FILES['supporting_doc_1'] ?? null,
        $targetDir
    );

$supporting_doc_2_path =
    handleFileUpload(
        $_FILES['supporting_doc_2'] ?? null,
        $targetDir
    );

$supporting_doc_3_path =
    handleFileUpload(
        $_FILES['supporting_doc_3'] ?? null,
        $targetDir
    );


// ============================================================
// WATER TAX
// ============================================================
$water_tax =
    isset($_POST['water_tax'])
    ? 1
    : 0;


// ============================================================
// OWNER + FLOOR JSON
// ============================================================
$owner_data =
    !empty($_POST['owners_json'])
    ? json_decode(
        $_POST['owners_json'],
        true
    )
    : [];

$floors_data =
    !empty($_POST['floors_json'])
    ? json_decode(
        $_POST['floors_json'],
        true
    )
    : [];


// ============================================================
// OWNER VALIDATION
// ============================================================
if (!is_array($owner_data)) {
    $owner_data = [];
}

if (count($owner_data) > 1) {

    die("❌ Error: Only one owner can be added to a property assessment.");
}


// ============================================================
// INSERT MAIN ASSESSMENT
// ============================================================

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
];


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
    $ward_no,
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


// ============================================================
// TYPES
// ============================================================
//
// Existing 32 fields
//
$type_string =
    "ssiiissssssdsssssssssissssssssss";


// ============================================================
// CREATED BY
// ============================================================
$fields[] = 'created_by';

$values[] =
    ($user_id > 0)
    ? $user_id
    : null;

$type_string .= 'i';


// ============================================================
// VERIFICATION STATUS
// ============================================================
$fields[] = 'verification_status';

$values[] =
    $verification_status;

$type_string .= 's';


// ============================================================
// CURRENT VERIFICATION ROLE
// ============================================================
$fields[] = 'current_verification_role_id';

$values[] =
    $current_verification_role_id;

$type_string .= 'i';


// ============================================================
// VERIFIED BY
// ============================================================
$fields[] = 'verified_by';

$values[] =
    $verified_by;

$type_string .= 'i';


// ============================================================
// VERIFIED AT
// ============================================================
$fields[] = 'verified_at';

$values[] =
    $verified_at;

$type_string .= 's';


// ============================================================
// SURVEYOR ID
// ============================================================
if ($is_surveyor) {

    $fields[] = 'surveyor_id';

    $values[] =
        $user_id;

    $type_string .= 'i';
}


// ============================================================
// PLACEHOLDERS
// ============================================================
$placeholders =
    array_fill(
        0,
        count($fields),
        '?'
    );


// ============================================================
// FINAL INSERT QUERY
// ============================================================
$sql =
    "INSERT INTO assessments (" .
    implode(', ', $fields) .
    ") VALUES (" .
    implode(', ', $placeholders) .
    ")";


$stmt =
    $conn->prepare($sql);

if (!$stmt) {

    die("❌ SQL Prepare Error: " .
        $conn->error);
}


// ============================================================
// BIND PARAMETERS
// ============================================================
if (
    !$stmt->bind_param(
        $type_string,
        ...$values
    )
) {

    die("❌ Bind Param Error: Check types and count! " .
        $stmt->error);
}


// ============================================================
// EXECUTE ASSESSMENT
// ============================================================
if ($stmt->execute()) {

    $assessment_id =
        $stmt->insert_id;


    // ========================================================
    // AUDIT LOG - CREATE ASSESSMENT
    // ========================================================
    $created_snapshot = [

        'id' =>
        $assessment_id,

        'municipality_name' =>
        $_POST['municipality'] ?? '',

        'year_of_assessment' =>
        $_POST['assesment_year'] ?? '',

        'zone_id' =>
        $zone_id,

        'ward_id' =>
        (int)$ward_no,

        'mohalla_id' =>
        $mohalla_id,

        'property_id' =>
        $property_id,

        'new_holding' =>
        $new_holding_number,

        'old_holding' =>
        $_POST['old_holding'] ?? '',

        'property_type' =>
        $_POST['property_type'] ?? '',

        'property_status' =>
        $_POST['property_status'] ?? '',

        'road' =>
        $_POST['road'] ?? '',

        'plot_area' =>
        (float)($_POST['plot_area'] ?? 0),

        'building_type' =>
        $_POST['building_type'] ?? '',

        'house_no' =>
        $_POST['house_no'] ?? '',

        'plot_no' =>
        $_POST['plot_no'] ?? '',

        'khata_no' =>
        $_POST['khata_no'] ?? '',

        'khasra_no' =>
        $_POST['khasra_no'] ?? '',

        'addr1' =>
        $_POST['addr1'] ?? '',

        'addr2' =>
        $_POST['addr2'] ?? '',

        'pincode' =>
        $_POST['pincode'] ?? '',

        'water_tax' =>
        $water_tax,

        'latitude' =>
        $_POST['latitude'] ?? '',

        'longitude' =>
        $_POST['longitude'] ?? '',

        'surveyor_id' =>
        $is_surveyor
            ? $user_id
            : null,

        'verification_status' =>
        $verification_status,

        'current_verification_role_id' =>
        $current_verification_role_id,

        'verified_by' =>
        $verified_by,

        'verified_at' =>
        $verified_at
    ];


    addAudit(
        $conn,
        [

            'assessment_id' =>
            $assessment_id,

            'property_id' =>
            $property_id,

            'entity_type' =>
            'ASSESSMENT',

            'entity_id' =>
            $assessment_id,

            'action' =>
            'CREATE',

            'old_data' =>
            [],

            'new_data' =>
            $created_snapshot,

            'changed_fields' =>
            array_keys(
                $created_snapshot
            ),

            'remark' => $is_current_user_admin
                ? 'Assessment created and directly approved by Admin'
                : 'Assessment created and sent to LIPIK for verification'
        ]
    );


    // ========================================================
    // SAVE OWNERS
    // ========================================================
    if (!empty($owner_data)) {

        $owner_sql = "
            INSERT INTO assessment_owners
            (
                assessment_id,
                owner_name,
                father_husband_pan,
                gender,
                mobile,
                email
            )
            VALUES (?,?,?,?,?,?)
        ";

        $owner_stmt =
            $conn->prepare($owner_sql);

        if ($owner_stmt) {

            foreach (
                $owner_data
                as $owner
            ) {

                $owner_name =
                    ($owner['title'] ?? '') .
                    ' ' .
                    ($owner['name'] ?? '');

                $father_name =
                    $owner['careof'] ?? '';

                $gender =
                    $owner['gender'] ?? '';

                $mobile =
                    $owner['mobile'] ?? '';

                $email =
                    $owner['email'] ?? '';


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

                    $owner_db_id =
                        $owner_stmt->insert_id;


                    addAudit(
                        $conn,
                        [

                            'assessment_id' =>
                            $assessment_id,

                            'property_id' =>
                            $property_id,

                            'entity_type' =>
                            'OWNER',

                            'entity_id' =>
                            $owner_db_id,

                            'action' =>
                            'CREATE',

                            'old_data' =>
                            [],

                            'new_data' =>
                            [

                                'id' =>
                                $owner_db_id,

                                'owner_name' =>
                                $owner_name,

                                'father_husband_pan' =>
                                $father_name,

                                'gender' =>
                                $gender,

                                'mobile' =>
                                $mobile,

                                'email' =>
                                $email
                            ],

                            'changed_fields' =>
                            [

                                'owner_name',
                                'father_husband_pan',
                                'gender',
                                'mobile',
                                'email'

                            ],

                            'remark' =>
                            'Owner created'
                        ]
                    );
                }
            }

            $owner_stmt->close();
        }
    }


    // ========================================================
    // SAVE FLOORS
    // ========================================================
    if (!empty($floors_data)) {

        $floor_sql = "
            INSERT INTO assessment_floors
            (
                assessment_id,
                floor_no,
                construction_type,
                date_from,
                date_to,
                occupancy_type,
                build_up_area,
                usage_type,
                non_residential_group,
                property_name
            )
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ";

        $floor_stmt =
            $conn->prepare($floor_sql);


        if ($floor_stmt) {

            foreach (
                $floors_data
                as $floor
            ) {

                $floor_no =
                    $floor['floor_no'] ?? '';

                $construction =
                    $floor['construction'] ?? '';

                $date_from =
                    $floor['date_from'] ?? null;

                $date_to =
                    $floor['date_to'] ?? null;

                $occupancy =
                    $floor['occupancy'] ?? '';

                $build_area =
                    (float)(
                        $floor['buildup'] ?? 0
                    );

                $usage =
                    $floor['usage'] ?? '';

                $non_res_group =
                    $floor['non_residential'] ?? '';

                $property_name =
                    $floor['properties'] ?? '';


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

                    $floor_db_id =
                        $floor_stmt->insert_id;


                    addAudit(
                        $conn,
                        [

                            'assessment_id' =>
                            $assessment_id,

                            'property_id' =>
                            $property_id,

                            'entity_type' =>
                            'FLOOR',

                            'entity_id' =>
                            $floor_db_id,

                            'action' =>
                            'CREATE',

                            'old_data' =>
                            [],

                            'new_data' =>
                            [

                                'id' =>
                                $floor_db_id,

                                'floor_no' =>
                                $floor_no,

                                'construction_type' =>
                                $construction,

                                'date_from' =>
                                $date_from,

                                'date_to' =>
                                $date_to,

                                'occupancy_type' =>
                                $occupancy,

                                'build_up_area' =>
                                $build_area,

                                'usage_type' =>
                                $usage,

                                'non_residential_group' =>
                                $non_res_group,

                                'property_name' =>
                                $property_name
                            ],

                            'changed_fields' =>
                            [

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

                            'remark' =>
                            'Floor created'
                        ]
                    );
                }
            }

            $floor_stmt->close();
        }
    }


    // ========================================================
    // REDIRECT
    // ========================================================
    $redirect_url =
        $is_surveyor
        ? 'surveyor-dashboard.php'
        : 'view-assesment.php';


    // ========================================================
    // SUCCESS MESSAGE
    // ========================================================
    if ($is_current_user_admin) {

        $message =
            "✅ Property Assessment Saved Successfully!\n" .
            "Status: APPROVED\n" .
            "Property ID: {$property_id}\n" .
            "Holding No: {$new_holding_number}";
    } else {

        $message =
            "✅ Property Assessment Saved Successfully!\n" .
            "Status: PENDING\n" .
            "Property ID: {$property_id}\n" .
            "Holding No: {$new_holding_number}";
    }


    echo "
    <script>
        alert(" .
        json_encode($message) .
        ");
        window.location.href = " .
        json_encode($redirect_url) .
        ";
    </script>
    ";

    exit();
} else {

    // ========================================================
    // INSERT FAILURE
    // ========================================================
    echo "
    <script>
        alert(" .
        json_encode(
            "❌ Insert Error: " .
                $stmt->error
        ) .
        ");
        window.location.href = 'view-assesment.php';
    </script>
    ";

    exit();
}


$stmt->close();
$conn->close();
