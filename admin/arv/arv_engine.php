<?php

function calculateARV(int $assessment_id, mysqli $conn): array {

    $stmt = $conn->prepare("SELECT * FROM assessments WHERE id = ? AND is_deleted = 0");
    $stmt->bind_param("i", $assessment_id);
    $stmt->execute();
    $assessment = $stmt->get_result()->fetch_assoc();

    if (!$assessment) {
        throw new Exception("Assessment not found.");
    }

    $ward_id = (int)$assessment['ward'];
    $road_category = trim($assessment['road']);

    $assessment_year_raw = trim($assessment['year_of_assessment']);
    $years = explode('-', $assessment_year_raw);

    if (count($years) !== 2) {
        throw new Exception("Invalid assessment year format.");
    }

    $start_year = trim($years[0]);
    $end_year_short = substr(trim($years[1]), -2);
    $financial_year = $start_year . '-' . $end_year_short;

    $stmt = $conn->prepare("
        SELECT * FROM rate_master
        WHERE ward_no = ?
        AND financial_year = ?
        AND road_width = ?
        AND status = 'active'
        LIMIT 1
    ");

    $stmt->bind_param("iss", $ward_id, $financial_year, $road_category);
    $stmt->execute();
    $rates = $stmt->get_result()->fetch_assoc();

    if (!$rates) {

        $stmt2 = $conn->prepare("
            SELECT * FROM rate_master
            WHERE ward_no = ?
            AND road_width = ?
            AND status = 'active'
            ORDER BY financial_year DESC
            LIMIT 1
        ");

        $stmt2->bind_param("is", $ward_id, $road_category);
        $stmt2->execute();
        $rates = $stmt2->get_result()->fetch_assoc();

        if (!$rates) {
            throw new Exception("Rate not found for selected ward/road.");
        }
    }

    $stmt = $conn->prepare("
        SELECT * FROM assessment_floors
        WHERE assessment_id = ?
        AND is_deleted = 0
    ");
    $stmt->bind_param("i", $assessment_id);
    $stmt->execute();
    $floors = $stmt->get_result();

    if ($floors->num_rows == 0) {
        throw new Exception("No floors found.");
    }

    $total_arv = 0;
    $floor_details = [];

    while ($row = $floors->fetch_assoc()) {

        $area = floatval($row['build_up_area']);
        if ($area <= 0) continue;

        $const = strtoupper(trim($row['construction_type']));
        $usage_raw = strtolower(trim($row['usage_type'] ?? ''));
        $occupancy_raw = strtolower(trim($row['occupancy_type'] ?? ''));

        $is_residential =
            $usage_raw === 'fully residential' ||
            $usage_raw === 'residential';

        $is_tenanted = strpos($occupancy_raw, 'tenant') !== false;

        // ==============================
        // ✅ FIXED RATE BY CONSTRUCTION
        // ==============================

        if ($const === 'RCC') {
            $rate = floatval($rates['rcc_rate']);

        } elseif ($const === 'ACC') {
            $rate = floatval($rates['acc_rate']);

        } 
        // ✅ Vacant Land / Open Plot Proper Handling
        elseif (
            $const === 'OPEN PLOT' ||
            $const === 'VACANT LAND' ||
            $const === 'VACANT' ||
            strpos($const, 'OPEN') !== false
        ) {
            $rate = floatval($rates['open_plot_rate']);
        } 
        else {
            $rate = floatval($rates['other_rate']);
        }

        // ==============================
        // RESIDENTIAL
        // ==============================
        if ($is_residential) {

            $gross = 12 * $rate * $area;
            $gross *= 0.80;

            $age_years = 0;
            if (!empty($row['date_from'])) {
                $built_year = date('Y', strtotime($row['date_from']));
                $age_years = date('Y') - $built_year;
            }

            if ($age_years <= 10) {
                $gross *= 0.75;
            } elseif ($age_years <= 20) {
                $gross *= 0.675;
            } else {
                $gross *= 0.60;
            }

            if ($is_tenanted) {
                $gross *= 1.25;
            }

            $floor_arv = round($gross, 2);

        } else {

            if ($usage_raw === 'industrial') {
                $factor = 4;
            } else {

                $group = strtolower($row['non_residential_group'] ?? '');

                switch ($group) {
                    case 'group1':
                        $factor = 1;
                        break;
                    case 'group2':
                        $factor = 3;
                        break;
                    default:
                        $factor = 4;
                        break;
                }
            }

            $floor_arv = round(12 * $rate * $area * $factor, 2);
        }

        $total_arv += $floor_arv;

        $floor_details[] = [
            'floor' => $row['floor_no'],
            'area'  => $area,
            'rate'  => $rate,
            'arv'   => $floor_arv
        ];
    }

    $total_arv = round($total_arv, 2);

    $house_tax_rate = 0.10;
    $house_tax = round($total_arv * $house_tax_rate, 2);

    $water_tax_rate = 0.00;
    $water_tax = 0.00;

    if (
        isset($assessment['water_tax']) &&
        floatval($assessment['water_tax']) == 1.00
    ) {
        $water_tax_rate = 0.10;
        $water_tax = round($total_arv * $water_tax_rate, 2);
    }

    $total_tax = round($house_tax + $water_tax, 2);

    return [
        'assessment_id'   => $assessment_id,
        'ward_name'       => $rates['ward_name'],
        'financial_year'  => $rates['financial_year'],
        'total_arv'       => $total_arv,
        'house_tax_rate'  => $house_tax_rate * 100,
        'house_tax_arv'   => $house_tax,
        'water_tax_rate'  => $water_tax_rate * 100,
        'water_tax_arv'   => $water_tax,
        'total_tax'       => $total_tax,
        'arv_status'      => 'Generated',
        'floors'          => $floor_details
    ];
}