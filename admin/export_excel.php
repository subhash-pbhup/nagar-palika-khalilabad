<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once "db.php";

// Validate input
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : '';

if (!$start_date || !$end_date) {
    die("Please provide start_date and end_date in YYYY-MM-DD format.");
}

// Prepare SQL query: NOW JOINING WITH 'surveyors' TABLE (alias s)
$sql = "SELECT a.id,
                a.new_holding,
                a.created_at,
                a.municipality_name,
                a.ward,
                a.property_type,
                a.building_type,
                a.plot_area,
                a.house_no,
                a.plot_no,
                a.khata_no,
                a.khasra_no,
                a.addr1,
                a.addr2,
                a.surveyor_id, 
                s.name AS surveyor_name, /* <-- FETCHING NAME FROM SURVEYORS TABLE */
                GROUP_CONCAT(DISTINCT o.owner_name SEPARATOR ', ') AS owner_name,
                GROUP_CONCAT(DISTINCT o.mobile SEPARATOR ', ') AS owner_mobile,
                GROUP_CONCAT(DISTINCT af.usage_type SEPARATOR ', ') AS usage_type,
                GROUP_CONCAT(DISTINCT af.non_residential_group SEPARATOR ', ') AS non_residential_group,
                GROUP_CONCAT(DISTINCT af.property_name SEPARATOR ', ') AS property_name
        FROM assessments a
        LEFT JOIN assessment_owners o ON a.id = o.assessment_id AND o.is_deleted = 0
        LEFT JOIN assessment_floors af ON a.id = af.assessment_id
        LEFT JOIN surveyors s ON a.surveyor_id = s.id /* <-- CRITICAL CHANGE: JOINING 'surveyors' */
        WHERE a.is_deleted = 0
          AND DATE(a.created_at) BETWEEN ? AND ?
        GROUP BY a.id
        ORDER BY a.id ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL prepare failed: " . $conn->error);
}
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Prepare filename
$filename = "survey_report_{$start_date}_to_{$end_date}.xls";

// Send headers
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Print BOM for UTF-8
echo "\xEF\xBB\xBF";

// Start HTML table
echo "<table border='1' cellpadding='4' cellspacing='0' style='border-collapse:collapse'>";

// Title row (colspan is 15)
$title = "DATE RANGE - {$start_date} TO {$end_date} SURVEY REPORT";
echo "<tr><td colspan='15' style='font-weight:bold; text-align:left; padding:6px;'>{$title}</td></tr>";

// Header row
echo "<tr>";
echo "<td style='font-weight:bold'>SR NO.</td>";
echo "<td style='font-weight:bold'>MUNICIPALITY NAME</td>";
echo "<td style='font-weight:bold'>SURVEY NUMBER</td>";
echo "<td style='font-weight:bold'>DATE</td>";
echo "<td style='font-weight:bold'>OWNER NAME</td>";
echo "<td style='font-weight:bold'>MOBILE NUMBER</td>";
echo "<td style='font-weight:bold'>WARD</td>";
echo "<td style='font-weight:bold'>ADDRESS</td>";
echo "<td style='font-weight:bold'>PLOT AREA</td>";
echo "<td style='font-weight:bold'>BUILDING TYPE</td>";
echo "<td style='font-weight:bold'>BUILDUP TYPE</td>";
echo "<td style='font-weight:bold'>USAGE TYPE</td>";
echo "<td style='font-weight:bold'>NON RESIDENTIAL PROPERTIES</td>";
echo "<td style='font-weight:bold'>PROPERTY NAME</td>";
echo "<td style='font-weight:bold'>SURVEYED BY</td>"; 
echo "</tr>";

// Data rows
if ($result && $result->num_rows > 0) {
    $sr = 1;
    while ($row = $result->fetch_assoc()) {
        // Combine address parts
        $address_parts = array_filter([
            trim($row['house_no'] ?? ''),
            trim($row['plot_no'] ?? ''),
            trim($row['khata_no'] ?? ''),
            trim($row['khasra_no'] ?? ''),
            trim($row['addr1'] ?? ''),
            trim($row['addr2'] ?? '')
        ]);
        $full_address = $address_parts ? implode(', ', $address_parts) : $row['municipality_name'];

        // Owner mobile (force text format)
        $mobile = $row['owner_mobile'] ?? '';

        // Plot area (safe fallback)
        $plot_area = isset($row['plot_area']) && $row['plot_area'] !== null ? $row['plot_area'] : '';

        // Correct conditional logic for "N/A"
        $usage_type = htmlspecialchars($row['usage_type'] ?? '');
        $non_residential_group = htmlspecialchars($row['non_residential_group'] ?? '');
        $property_name = htmlspecialchars($row['property_name'] ?? '');

        if (strtolower($usage_type) === 'residential') {
            $usage_type = 'N/A';
            $non_residential_group = 'N/A';
            $property_name = 'N/A';
        }
        
        // --- START OF MODIFIED LOGIC (for Surveyor Name + ID) ---
        // Now using 'surveyor_name' from the new alias
        $surveyor_name = htmlspecialchars($row['surveyor_name'] ?? '');
        $surveyor_id   = htmlspecialchars($row['surveyor_id'] ?? ''); 
        
        if (!empty($surveyor_name)) {
            // Case 1: Name is available (JOIN succeeded) -> Display Name + ID
            $surveyed_by_display = $surveyor_name . " (ID: " . $surveyor_id . ")"; 
        } elseif (!empty($surveyor_id)) {
            // Case 2: ID is available but Name is NOT (e.g., surveyor deleted from 'surveyors' table)
            // Display only ID
            $surveyed_by_display = "ID: " . $surveyor_id; 
        } else {
            // Case 3: Neither ID nor Name is available (surveyed by admin or imported)
            $surveyed_by_display = "Admin"; 
        }
        // --- END OF MODIFIED LOGIC ---

        echo "<tr>";
        echo "<td>" . $sr . "</td>";
        echo "<td>" . htmlspecialchars($row['municipality_name'] ?? '') . "</td>";
        echo "<td style='mso-number-format:\\@'>" . htmlspecialchars($row['new_holding'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['owner_name'] ?? '') . "</td>";
        echo "<td style='mso-number-format:\\@'>" . htmlspecialchars($mobile) . "</td>";
        echo "<td>" . htmlspecialchars($row['ward'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($full_address) . "</td>";
        echo "<td style='mso-number-format:\\@'>" . htmlspecialchars($plot_area) . " sq. ft.</td>";
        echo "<td>" . htmlspecialchars($row['property_type'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['building_type'] ?? '') . "</td>";
        echo "<td>" . $usage_type . "</td>";
        echo "<td>" . $non_residential_group . "</td>";
        echo "<td>" . $property_name . "</td>";
        echo "<td>" . $surveyed_by_display . "</td>"; // Displaying the combined string
        echo "</tr>";

        $sr++;
    }
} else {
    echo "<tr><td colspan='15'>No records found for selected date range.</td></tr>";
}

echo "</table>";
exit;
?>