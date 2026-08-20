<?php
require_once "db.php";

if (isset($_POST['ward'])) {
    $ward_no = intval($_POST['ward']);

    // Get last holding number
    $sql_last = "SELECT new_holding FROM assessments WHERE ward = ? ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sql_last);
    $stmt->bind_param("s", $ward_no);
    $stmt->execute();
    $result = $stmt->get_result();

    $nextNumber = 1;
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last_holding = $row['new_holding']; // e.g. DEO-W1-00005

        $parts = explode("-", $last_holding);
        $last_num = intval(end($parts));
        $nextNumber = $last_num + 1;
    }
    $new_holding_number = "KLB-W" . $ward_no . "-" . str_pad($nextNumber, 5, "0", STR_PAD_LEFT);
    echo $new_holding_number;
}
