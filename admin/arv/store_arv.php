<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

session_start();
require_once __DIR__ . '/../db.php';

if (empty($_SESSION['ARV_PREVIEW'])) {
    die("No ARV data found.");
}

$data = $_SESSION['ARV_PREVIEW'];
$assessment_id = $data['assessment_id'];

/* ================= FETCH ASSESSMENT DETAILS ================= */

$stmtA = $conn->prepare("SELECT ward, property_type, year_of_assessment, water_tax 
                         FROM assessments 
                         WHERE id=?");
$stmtA->bind_param("i", $assessment_id);
$stmtA->execute();
$resA = $stmtA->get_result();
$assessment = $resA->fetch_assoc();

if (!$assessment) {
    die("Assessment not found.");
}

/* ================= EXTRACT WARD NUMBER ================= */

preg_match('/^\d+/', $assessment['ward'], $match);
$ward_no = $match[0];

/* ================= PROPERTY TYPE CODE ================= */

$typeMap = [
    "Residential" => "R",
    "Commercial" => "C",
    "Mixed" => "M",
    "Open Plot" => "O"
];

$property_type = $typeMap[$assessment['property_type']] ?? "R";

/* ================= FINANCIAL YEAR SHORT ================= */

$year = trim($assessment['year_of_assessment']);

preg_match('/(\d{4}).*?(\d{4}|\d{2})/', $year, $matches);

if (count($matches) >= 3) {
    $start = $matches[1];
    $end   = $matches[2];

    if (strlen($end) == 4) {
        $end = substr($end, 2, 2);
    }

    $fy_short = substr($start, 2, 2) . $end;
} else {
    die("Invalid Financial Year Format");
}

/* ================= BASIC VALUES ================= */

$total_arv  = $data['total_arv'];
$house_tax  = $data['house_tax_arv'];
$water_tax  = $data['water_tax_arv'];
$total_tax  = $data['total_tax'];

$conn->begin_transaction();

try {

    $conn->query("LOCK TABLES property_arv_details WRITE");

    /* ================= PROPERTY ID ================= */

    $state = "09";
    $ulb   = "552";
    $zone  = "01";

    $prefix = $state . $ulb . $zone . $ward_no;

    $result = $conn->query("
        SELECT property_id 
        FROM property_arv_details 
        WHERE property_id LIKE '{$prefix}%'
        ORDER BY id DESC LIMIT 1
    ");

    if ($result->num_rows > 0) {
        $last = $result->fetch_assoc()['property_id'];
        $running = intval(substr($last, strlen($prefix), 6)) + 1;
    } else {
        $running = 1;
    }

    // ✅ 6 digit running
    $property_id = $prefix . str_pad($running, 6, "0", STR_PAD_LEFT) . $property_type;

    /* ================= BILL NO ================= */

    $bill_prefix = "B" . $ulb . $fy_short;

    $result2 = $conn->query("
        SELECT bill_no 
        FROM property_arv_details 
        WHERE bill_no LIKE '{$bill_prefix}%'
        ORDER BY id DESC LIMIT 1
    ");

    if ($result2->num_rows > 0) {
        $last_bill = $result2->fetch_assoc()['bill_no'];
        $running_bill = intval(substr($last_bill, strlen($bill_prefix), 6)) + 1;
    } else {
        $running_bill = 1;
    }

    $bill_no = $bill_prefix . str_pad($running_bill, 6, "0", STR_PAD_LEFT);

    /* ================= INSERT ================= */

    $house_rate = 10;
    $water_rate = ($assessment['water_tax'] > 0) ? 10 : 0;

    $stmt = $conn->prepare("
        INSERT INTO property_arv_details
        (assessment_id, property_id, bill_no, bill_date,
         house_tax_arv, house_tax_rate, house_tax_amount,
         water_tax_arv, water_tax_rate, water_tax_amount,
         total_tax, arv_status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $status = "GBW";

    $stmt->bind_param(
        "isssddddddds",
        $assessment_id,
        $property_id,
        $bill_no,
        date('Y-m-d'),
        $total_arv,
        $house_rate,
        $house_tax,
        $total_arv,
        $water_rate,
        $water_tax,
        $total_tax,
        $status
    );

    $stmt->execute();

    $conn->query("UNLOCK TABLES");

    $conn->commit();
    unset($_SESSION['ARV_PREVIEW']);

    // Prevent further output
    ob_clean();

?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <script>
            alert("ARV Generated Successfully");
            window.location.replace("../generate-demand.php?id=<?php echo $assessment_id; ?>");
        </script>
    </head>

    <body></body>

    </html>
<?php
    exit;
} catch (Exception $e) {
    $conn->rollback();
    $conn->query("UNLOCK TABLES");
    die("Error: " . $e->getMessage());
}
