<?php

function calculatePropertyTax($conn, $assessment_id)
{
    $result = array(
        'total_area'   => 0,
        'annual_arv'   => 0,
        'property_tax' => 0,
        'water_tax'    => 0,
        'total_demand' => 0,
        'applied_factor' => 1, // Default factor
        'ward'         => '',
        'road'         => '',
        'holding'      => ''
    );

    // 1. Get Assessment Details
    $stmt = $conn->prepare("SELECT ward, road, property_type, new_holding FROM assessments WHERE id = ?");
    $stmt->bind_param("i", $assessment_id);
    $stmt->execute();
    $asmt_res = $stmt->get_result();
    $asmt = $asmt_res->fetch_assoc();

    if (!$asmt) return false;

    $result['ward'] = $asmt['ward'];
    $result['road'] = $asmt['road'];
    $result['holding'] = $asmt['new_holding'];

    // 2. Get Rates (Fuzzy Match for Road)
    $road_search = "%" . trim(str_replace('Meters', '', $asmt['road'])) . "%";
    $rateStmt = $conn->prepare("SELECT rcc_rate, acc_rate, other_rate FROM rate_master WHERE ward_no = ? AND road_width LIKE ? AND status = 'active' LIMIT 1");
    $rateStmt->bind_param("ss", $asmt['ward'], $road_search);
    $rateStmt->execute();
    $rate = $rateStmt->get_result()->fetch_assoc();

    if (!$rate) {
        // Fallback to ward default
        $fbStmt = $conn->prepare("SELECT rcc_rate, acc_rate, other_rate FROM rate_master WHERE ward_no = ? LIMIT 1");
        $fbStmt->bind_param("s", $asmt['ward']);
        $fbStmt->execute();
        $rate = $fbStmt->get_result()->fetch_assoc();
    }

    if (!$rate) return false;

    // 3. Get Floors & Calculate
    $floorStmt = $conn->prepare("SELECT build_up_area, construction_type, usage_type FROM assessment_floors WHERE assessment_id = ? AND is_deleted = 0");
    $floorStmt->bind_param("i", $assessment_id);
    $floorStmt->execute();
    $floors = $floorStmt->get_result();

    $monthly_total = 0;
    while ($f = $floors->fetch_assoc()) {
        $area = (float)$f['build_up_area'];
        $result['total_area'] += $area;

        // Base Rate Selection
        $ctype = strtoupper(trim($f['construction_type']));
        $base = ($ctype == 'RCC') ? $rate['rcc_rate'] : (($ctype == 'ACC') ? $rate['acc_rate'] : $rate['other_rate']);

        $row_arv = $area * $base;

        // Logic for Commercial Factor
        if (strtoupper(trim($asmt['property_type'])) != 'RESIDENTIAL') {
            $usage = strtolower(trim($f['usage_type']));
            $factor = 4; // Default for Company/Office
            if (strpos($usage, 'medical') !== false || strpos($usage, 'shop') !== false) $factor = 3;
            if (strpos($usage, 'school') !== false || strpos($usage, 'industry') !== false) $factor = 1;
            
            $row_arv *= $factor;
            $result['applied_factor'] = $factor; // Store for summary
        } else {
            $row_arv *= 0.75; // Residential Age Discount
            if ($ctype == 'RCC') $row_arv *= 0.80; // RCC Rebate
        }

        $monthly_total += $row_arv;
    }

    $result['annual_arv'] = $monthly_total * 12;
    $result['property_tax'] = $result['annual_arv'] * 0.10;
    $result['water_tax'] = $result['annual_arv'] * 0.10;
    $result['total_demand'] = $result['property_tax'] + $result['water_tax'];

    return $result;
}