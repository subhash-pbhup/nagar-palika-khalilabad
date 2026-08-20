<?php
require_once "db.php";

// Handle file upload
$document_path = NULL;
if (isset($_FILES['doc_proof']) && $_FILES['doc_proof']['error'] == 0) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $fileName = time() . "_" . basename($_FILES["doc_proof"]["name"]);
    $targetFile = $targetDir . $fileName;
    if (move_uploaded_file($_FILES["doc_proof"]["tmp_name"], $targetFile)) {
        $document_path = $targetFile;
    }
}

// ✅ Prepare boolean values
$water_tax            = isset($_POST['water_tax']) ? 1 : 0;
$sewer_tax            = isset($_POST['sewer_tax']) ? 1 : 0;
$rainwater_harvesting = isset($_POST['rebate_rwh']) ? 1 : 0;
$greenery_covered     = isset($_POST['rebate_green']) ? 1 : 0;
$parking_available    = isset($_POST['rebate_parking']) ? 1 : 0;
$anti_pollution       = isset($_POST['rebate_pollution']) ? 1 : 0;

// Insert query (44 placeholders)
$sql = "INSERT INTO newassessment (
    municipality_name, year_of_assessment, new_holding_number, old_holding_number,
    property_type, old_pid_number, road_name, area_of_plot, start_year_of_property, acquisition_date,
    gis_map_id, nagar_nigam_id, demand_no, address_code, status_of_property,
    owner_name, father_husband_pan, gender, mobile, email,
    property_house_no, plot_no, khata_no, khasra_no, address_line1, address_line2,
    zone, ward, mohalla, pincode,
    floor_no, residential_type, construction_type, occupancy_type, build_up_area,
    date_from, date_to,
    water_tax, sewer_tax,
    rainwater_harvesting, greenery_covered, parking_available, anti_pollution_measures,
    document_path
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

// ✅ Debug: Count columns & placeholders
preg_match("/\((.*?)\)/s", $sql, $matches);
$columns = array_map('trim', explode(",", $matches[1]));
$columnCount = count($columns);
$placeholders = substr_count($sql, "?");

echo "<pre>";
echo "🟢 Columns in query: $columnCount\n";
echo "🟢 Placeholders in query: $placeholders\n";
echo "🟢 Parameters to bind: 44\n";
if ($columnCount === $placeholders && $placeholders === 44) {
    echo "✅ Perfect! Columns, Placeholders & Bind Params all MATCH\n";
} else {
    echo "❌ Mismatch! Please re-check\n";
}
echo "</pre>";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Prepare Error: " . $conn->error);
}

// ✅ Bind parameters (44 total)
$stmt->bind_param(
    "ssssssssssssssssssssssssssssssssssddiiiiiiis",
    $_POST['municipality'],         // municipality_name
    $_POST['assesment_year'],       // year_of_assessment
    $_POST['new_holding'],          // new_holding_number
    $_POST['old_holding'],          // old_holding_number
    $_POST['property_type'],        // property_type
    $_POST['old_pid'],              // old_pid_number
    $_POST['road'],                 // road_name
    $_POST['plot_area'],            // area_of_plot (decimal)
    $_POST['start_year'],           // start_year_of_property (date)
    $_POST['acq_date'],             // acquisition_date (date)
    $_POST['gis_id'],               // gis_map_id
    $_POST['nagar_id'],             // nagar_nigam_id
    $_POST['demand_no'],            // demand_no
    $_POST['address_code'],         // address_code
    $_POST['property_status'],      // status_of_property
    $_POST['owner_name'],           // owner_name
    $_POST['father_name'],          // father_husband_pan
    $_POST['gender'],               // gender
    $_POST['mobile'],               // mobile
    $_POST['email'],                // email
    $_POST['house_no'],             // property_house_no
    $_POST['plot_no'],              // plot_no
    $_POST['khata_no'],             // khata_no
    $_POST['khasra_no'],            // khasra_no
    $_POST['addr1'],                // address_line1
    $_POST['addr2'],                // address_line2
    $_POST['zone'],                 // zone
    $_POST['ward'],                 // ward
    $_POST['mohalla'],              // mohalla
    $_POST['pincode'],              // pincode
    $_POST['floor_no'],             // floor_no
    $_POST['residential_type'],     // residential_type
    $_POST['construction_type'],    // construction_type
    $_POST['occupancy_type'],       // occupancy_type
    $_POST['build_up_area'],        // build_up_area (decimal)
    $_POST['date_from'],            // date_from (date)
    $_POST['date_to'],              // date_to (date)
    $water_tax,                     // water_tax (bool)
    $sewer_tax,                     // sewer_tax (bool)
    $rainwater_harvesting,          // rainwater_harvesting
    $greenery_covered,              // greenery_covered
    $parking_available,             // parking_available
    $anti_pollution,                // anti_pollution_measures
    $document_path                  // document_path
);

// Execute query
if ($stmt->execute()) {
    echo "<h2 style='color:green'>✅ Property Assessment Saved Successfully!</h2>";
    echo "<a href='dashboard.php'>Go Back to Dashboard</a>";
} else {
    echo "<h2 style='color:red'>❌ Insert Error: " . $stmt->error . "</h2>";
}

$stmt->close();
$conn->close();
