<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

set_time_limit(0);
ini_set('memory_limit', '512M');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {

    // ---------------------------------------------------------
    // AUTH
    // ---------------------------------------------------------
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access.');
    }

    // Store user_id for insertion
    $user_id = (int)$_SESSION['user_id'];

    // ---------------------------------------------------------
    // DATABASE
    // ---------------------------------------------------------
    require_once __DIR__ . '/db.php';

    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new Exception('Database connection is not available.');
    }

    // ---------------------------------------------------------
    // REQUEST
    // ---------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    if (!isset($_FILES['file'])) {
        throw new Exception('CSV file was not received.');
    }

    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception(
            'File upload failed. Error code: ' . $_FILES['file']['error']
        );
    }

    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileName = $_FILES['file']['name'];

    if (!is_uploaded_file($fileTmp)) {
        throw new Exception('Invalid uploaded file.');
    }

    if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) !== 'csv') {
        throw new Exception('Only CSV files are allowed.');
    }

    $handle = fopen($fileTmp, 'r');

    if (!$handle) {
        throw new Exception('Unable to open CSV file.');
    }

    // ---------------------------------------------------------
    // EXACT 54-COLUMN HEADER
    // ---------------------------------------------------------
    $expectedHeaders = [
        'SR. NO.',
        'ULB ID',
        'Old Property ID',
        'Excel Property ID',
        'e-nagarsewa Generated Property ID',
        'Zone ID',
        'Ward ID',
        'Mohalla ID',
        'Property Status',
        'House No',
        'Owner Name',
        'Father Name',
        'Address',
        'Mobile',
        'No. of Floors',
        'Rebate Type',
        'Property Type',
        'Road Location',
        'Nature of House',
        'Total Area',
        'Property Use',
        'Bill No',
        'Bill Date',
        'Financial Year',
        'Total ARV',
        'House Tax (Current)',
        'House Tax (Arrear)',
        'House Tax (Interest)',
        'House Tax (Total)',
        'Water Tax (Current)',
        'Water Tax (Arrear)',
        'Water Tax (Interest)',
        'Water Tax (Total)',
        'Carpet Area',
        'Covered Area',
        'Sewerage Tax (Current)',
        'Sewerage Tax (Arrear)',
        'Sewerage Tax (Interest)',
        'Sewerage Tax (Total)',
        'Other Tax (Current)',
        'Other Tax (Arrear)',
        'Other Tax (Interest)',
        'Other Tax (Total)',
        'Water Charge (Current)',
        'Water Charge (Arrear)',
        'Water Charge (Interest)',
        'Water Charge (Total)',
        'Chuk Number',
        'Latitude',
        'Longitude',
        'Prev Adv House',
        'Prev Adv Water',
        'Prev Adv Sewerage',
        'Prev Adv Other',
        'Prev Adv Water Charge'
    ];

    $header = fgetcsv($handle, 10000, ',');

    if ($header === false) {
        throw new Exception('CSV header row is missing.');
    }

    $header = array_map(function ($value) {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', (string)$value);
        return trim($value);
    }, $header);

    if (count($header) !== 55) {
        throw new Exception(
            'Invalid CSV format. Exactly 54 columns are required. Received: ' .
                count($header)
        );
    }

    $normalizedHeader = array_map(
        fn($v) => strtolower(trim($v)),
        $header
    );

    $normalizedExpected = array_map(
        fn($v) => strtolower(trim($v)),
        $expectedHeaders
    );

    if ($normalizedHeader !== $normalizedExpected) {
        $missing = array_values(array_diff(
            $normalizedExpected,
            $normalizedHeader
        ));

        $extra = array_values(array_diff(
            $normalizedHeader,
            $normalizedExpected
        ));

        throw new Exception(
            'CSV headers do not match the official 54-column template. ' .
                (!empty($missing) ? 'Missing: ' . implode(', ', $missing) . '. ' : '') .
                (!empty($extra) ? 'Extra: ' . implode(', ', $extra) . '.' : '')
        );
    }

    // Column map
    $col = [];
    foreach ($normalizedHeader as $i => $name) {
        $col[$name] = $i;
    }

    // ---------------------------------------------------------
    // HELPERS
    // ---------------------------------------------------------
    $csvValue = function (array $row, string $name) use ($col): string {
        $key = strtolower(trim($name));
        return trim((string)($row[$col[$key]] ?? ''));
    };

    $money = function (string $value): float {
        $value = trim($value);
        if ($value === '') {
            return 0.00;
        }

        $value = str_replace([',', '₹', ' '], '', $value);

        if (!is_numeric($value)) {
            throw new Exception("Invalid numeric value: {$value}");
        }

        return (float)$value;
    };

    $nullableInt = function (string $value): ?int {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/\.0+$/', '', $value);

        if (!preg_match('/^-?\d+$/', $value)) {
            throw new Exception("Invalid integer value: {$value}");
        }

        return (int)$value;
    };

    $nullableDecimal = function (string $value): ?float {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $value = str_replace([',', '₹', ' '], '', $value);

        if (!is_numeric($value)) {
            throw new Exception("Invalid decimal value: {$value}");
        }

        return (float)$value;
    };

    $parseDate = function (string $value): ?string {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // Excel serial date
        if (is_numeric($value) && (float)$value > 20000) {
            $serial = (int)$value;
            $unix = ($serial - 25569) * 86400;
            return gmdate('Y-m-d', $unix);
        }

        $formats = [
            'Y-m-d',
            'Y/m/d',
            'd-m-Y',
            'd/m/Y',
            'd.m.Y',
            'm-d-Y',
            'm/d/Y',
            'm.d.Y'
        ];

        foreach ($formats as $format) {
            $date = DateTime::createFromFormat('!' . $format, $value);
            $errors = DateTime::getLastErrors();

            $hasErrors =
                is_array($errors) &&
                ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

            if ($date && !$hasErrors && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        throw new Exception("Invalid Bill Date: {$value}");
    };

    // ---------------------------------------------------------
    // LOG
    // ---------------------------------------------------------
    $logDir = __DIR__ . '/logs';

    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }

    $logFileName = 'arv_bulk_' . date('Y-m-d_H-i-s') . '.txt';
    $logFilePath = $logDir . '/' . $logFileName;
    $logHandle = @fopen($logFilePath, 'a');

    $writeLog = function (string $message) use ($logHandle) {
        if ($logHandle) {
            fwrite(
                $logHandle,
                '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL
            );
        }
    };

    $writeLog('ARV BULK IMPORT STARTED: ' . $fileName);

    // ---------------------------------------------------------
    // PREPARED STATEMENTS
    // ---------------------------------------------------------

    // Duplicate property
    $duplicateStmt = $conn->prepare("
        SELECT id
        FROM assessments
        WHERE
            (
                property_id = ?
                AND property_id IS NOT NULL
                AND property_id <> ''
            )
            OR
            (
                ward_id = ?
                AND house_no = ?
                AND plot_no = ?
                AND khata_no = ?
                AND khasra_no = ?
                AND is_deleted = 0
            )
        LIMIT 1
    ");

    // Last holding in ward
    $holdingStmt = $conn->prepare("
        SELECT new_holding
        FROM assessments
        WHERE ward_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");

    // Holding duplicate
    $holdingCheckStmt = $conn->prepare("
        SELECT id
        FROM assessments
        WHERE new_holding = ?
        LIMIT 1
    ");

    // Assessment insert
    // Columns not present in assessments (ULB ID, floors, rebate,
    // property use, carpet/covered area, chuk number) are intentionally
    // not inserted because they do not exist in the supplied schema.
    $assessmentStmt = $conn->prepare("
        INSERT INTO assessments (
            municipality_name,
            year_of_assessment,
            zone_id,
            ward_id,
            mohalla_id,
            property_id,
            ward,
            new_holding,
            property_status,
            old_holding,
            old_pid,
            property_type,
            road,
            plot_area,
            building_type,
            latitude,
            longitude,
            house_no,
            plot_no,
            khata_no,
            khasra_no,
            addr1,
            addr2,
            water_tax,
            environment,
            verification_status,
            created_by
        )
        VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? ,?, ?
        )
    ");

    // Owner insert
    $ownerStmt = $conn->prepare("
        INSERT INTO assessment_owners (
            assessment_id,
            owner_name,
            father_husband_pan,
            mobile,
            is_deleted
        )
        VALUES (?, ?, ?, ?, 0)
    ");

    // ARV insert.
    // Use NULL for road_width and rate_master_id because ARV generation
    // will be done later from rate_master.
    $arvStmt = $conn->prepare("
        INSERT INTO property_arv_details (
            assessment_id,
            zone_id,
            ward_id,
            mohalla_id,
            financial_year,
            road_width,
            construction_type,
            rate_master_id,
            property_id,
            bill_no,
            bill_date,

            house_tax_arv,
            house_tax_rate,
            house_tax_amount,

            water_tax_arv,
            water_tax_rate,
            water_tax_amount,

            water_fee_arv,
            water_fee_rate,
            water_fee_amount,

            sewer_tax_arv,
            sewer_tax_rate,
            sewer_tax_amount,

            other_tax_arv,
            other_tax_rate,
            other_tax_amount,

            total_tax,
            status,

            house_tax_current,
            house_tax_arrear,
            house_tax_interest,
            house_tax_total,

            water_tax_current,
            water_tax_arrear,
            water_tax_interest,
            water_tax_total,

            water_fee_current,
            water_fee_arrear,
            water_fee_interest,
            water_fee_total,

            sewer_tax_current,
            sewer_tax_arrear,
            sewer_tax_interest,
            sewer_tax_total,

            other_tax_current,
            other_tax_arrear,
            other_tax_interest,
            other_tax_total,

            discount_amount,
            advance_deposit,
            arv_status
        )
        VALUES (
            ?,
            ?, ?, ?,
            ?,
            NULL,
            ?,
            NULL,
            ?,
            ?,
            ?,

            ?,
            0,
            ?,

            0,
            0,
            ?,

            0,
            0,
            ?,

            0,
            0,
            ?,

            0,
            0,
            ?,

            ?,
            1,

            ?, ?, ?, ?,

            ?, ?, ?, ?,

            ?, ?, ?, ?,

            ?, ?, ?, ?,

            ?, ?, ?, ?,

            ?,
            ?,
            ?
        )
    ");

    // ---------------------------------------------------------
    // COUNTERS
    // ---------------------------------------------------------
    $success = 0;
    $skipped = 0;
    $errors = 0;
    $summary = [];

    $rowNumber = 1;

    // ---------------------------------------------------------
    // PROCESS CSV
    // ---------------------------------------------------------
    while (($row = fgetcsv($handle, 10000, ',')) !== false) {

        $rowNumber++;

        // Skip completely empty rows
        $hasData = false;
        foreach ($row as $v) {
            if (trim((string)$v) !== '') {
                $hasData = true;
                break;
            }
        }

        if (!$hasData) {
            continue;
        }

        try {

            // =================================================
            // 54-COLUMN MAPPING
            // =================================================

            $srNo =
                $csvValue($row, 'SR. NO.');

            $ulbId =
                $csvValue($row, 'ULB ID');

            $oldPropertyId =
                $csvValue($row, 'Old Property ID');

            $excelPropertyId =
                $csvValue($row, 'Excel Property ID');

            $generatedPropertyId =
                $csvValue(
                    $row,
                    'e-nagarsewa Generated Property ID'
                );

            // Prefer e-nagarsewa generated ID.
            // If blank, use Excel Property ID.
            $propertyId =
                $generatedPropertyId !== ''
                ? $generatedPropertyId
                : $excelPropertyId;

            $zoneId =
                $nullableInt(
                    $csvValue($row, 'Zone ID')
                );

            $wardId =
                $nullableInt(
                    $csvValue($row, 'Ward ID')
                );

            $mohallaId =
                $nullableInt(
                    $csvValue($row, 'Mohalla ID')
                );

            $houseNo =
                $csvValue($row, 'House No');

            $ownerName =
                $csvValue($row, 'Owner Name');

            $fatherName =
                $csvValue($row, 'Father Name');

            $address =
                $csvValue($row, 'Address');

            $mobile =
                $csvValue($row, 'Mobile');

            $propertyStatus =
                strtolower(
                    $csvValue($row, 'Property Status')
                );

            $propertyType =
                $csvValue($row, 'Property Type');

            $roadLocation =
                $csvValue($row, 'Road Location');

            $natureOfHouse =
                $csvValue($row, 'Nature of House');

            $totalArea =
                $nullableDecimal(
                    $csvValue($row, 'Total Area')
                );

            $propertyUse =
                $csvValue($row, 'Property Use');

            $billNo =
                $csvValue($row, 'Bill No');

            $billDate =
                $parseDate(
                    $csvValue($row, 'Bill Date')
                );

            $financialYear =
                $csvValue($row, 'Financial Year');

            $totalArv =
                $money(
                    $csvValue($row, 'Total ARV')
                );

            // House
            $houseCurrent =
                $money(
                    $csvValue(
                        $row,
                        'House Tax (Current)'
                    )
                );

            $houseArrear =
                $money(
                    $csvValue(
                        $row,
                        'House Tax (Arrear)'
                    )
                );

            $houseInterest =
                $money(
                    $csvValue(
                        $row,
                        'House Tax (Interest)'
                    )
                );

            $houseTotal =
                $money(
                    $csvValue(
                        $row,
                        'House Tax (Total)'
                    )
                );

            // Water
            $waterCurrent =
                $money(
                    $csvValue(
                        $row,
                        'Water Tax (Current)'
                    )
                );

            $waterArrear =
                $money(
                    $csvValue(
                        $row,
                        'Water Tax (Arrear)'
                    )
                );

            $waterInterest =
                $money(
                    $csvValue(
                        $row,
                        'Water Tax (Interest)'
                    )
                );

            $waterTotal =
                $money(
                    $csvValue(
                        $row,
                        'Water Tax (Total)'
                    )
                );

            // Extra property fields
            $carpetArea =
                $csvValue($row, 'Carpet Area');

            $coveredArea =
                $csvValue($row, 'Covered Area');

            // Sewerage
            $sewerCurrent =
                $money(
                    $csvValue(
                        $row,
                        'Sewerage Tax (Current)'
                    )
                );

            $sewerArrear =
                $money(
                    $csvValue(
                        $row,
                        'Sewerage Tax (Arrear)'
                    )
                );

            $sewerInterest =
                $money(
                    $csvValue(
                        $row,
                        'Sewerage Tax (Interest)'
                    )
                );

            $sewerTotal =
                $money(
                    $csvValue(
                        $row,
                        'Sewerage Tax (Total)'
                    )
                );

            // Other
            $otherCurrent =
                $money(
                    $csvValue(
                        $row,
                        'Other Tax (Current)'
                    )
                );

            $otherArrear =
                $money(
                    $csvValue(
                        $row,
                        'Other Tax (Arrear)'
                    )
                );

            $otherInterest =
                $money(
                    $csvValue(
                        $row,
                        'Other Tax (Interest)'
                    )
                );

            $otherTotal =
                $money(
                    $csvValue(
                        $row,
                        'Other Tax (Total)'
                    )
                );

            // Water Charge
            $waterChargeCurrent =
                $money(
                    $csvValue(
                        $row,
                        'Water Charge (Current)'
                    )
                );

            $waterChargeArrear =
                $money(
                    $csvValue(
                        $row,
                        'Water Charge (Arrear)'
                    )
                );

            $waterChargeInterest =
                $money(
                    $csvValue(
                        $row,
                        'Water Charge (Interest)'
                    )
                );

            $waterChargeTotal =
                $money(
                    $csvValue(
                        $row,
                        'Water Charge (Total)'
                    )
                );

            // Other fields currently not represented in supplied DB
            $chukNumber =
                $csvValue($row, 'Chuk Number');

            $latitude =
                $csvValue($row, 'Latitude');

            $longitude =
                $csvValue($row, 'Longitude');

            // Previous advances
            $prevAdvHouse =
                $money(
                    $csvValue(
                        $row,
                        'Prev Adv House'
                    )
                );

            $prevAdvWater =
                $money(
                    $csvValue(
                        $row,
                        'Prev Adv Water'
                    )
                );

            $prevAdvSewerage =
                $money(
                    $csvValue(
                        $row,
                        'Prev Adv Sewerage'
                    )
                );

            $prevAdvOther =
                $money(
                    $csvValue(
                        $row,
                        'Prev Adv Other'
                    )
                );

            $prevAdvWaterCharge =
                $money(
                    $csvValue(
                        $row,
                        'Prev Adv Water Charge'
                    )
                );

            // Existing property_arv_details has one advance_deposit
            // field, so all five previous advance fields are summed.
            $advanceDeposit =
                $prevAdvHouse
                + $prevAdvWater
                + $prevAdvSewerage
                + $prevAdvOther
                + $prevAdvWaterCharge;

            // Total payable tax stored in property_arv_details.total_tax
            $totalTax =
                $houseTotal
                + $waterTotal
                + $waterChargeTotal
                + $sewerTotal
                + $otherTotal;

            // =================================================
            // VALIDATION
            // =================================================

            if ($financialYear === '') {
                throw new Exception(
                    'Financial Year is required.'
                );
            }

            if ($propertyId === '') {
                throw new Exception(
                    'e-nagarsewa Generated Property ID / Excel Property ID is required.'
                );
            }

            if ($wardId === null) {
                throw new Exception(
                    'Ward ID is required.'
                );
            }

            if ($ownerName === '') {
                throw new Exception(
                    'Owner Name is required.'
                );
            }

            if (
                $propertyStatus !== '' &&
                !in_array(
                    $propertyStatus,
                    ['new', 'old'],
                    true
                )
            ) {
                throw new Exception(
                    "Invalid Property Status '{$propertyStatus}'. Use New or Old."
                );
            }

            // =================================================
            // DUPLICATE PROPERTY CHECK
            // =================================================

            $duplicateStmt->bind_param(
                'sissss',
                $propertyId,
                $wardId,
                $houseNo,
                $plotNo,
                $khataNo,
                $khasraNo
            );

            /*
             * We need plot/khata/khasra for the duplicate query.
             * They are not separate columns in the 54-column Excel,
             * so use empty values here and rely on property_id.
             */
            $plotNo   = '';
            $khataNo  = '';
            $khasraNo = '';

            $duplicateStmt->execute();
            $duplicateStmt->store_result();

            if ($duplicateStmt->num_rows > 0) {

                $skipped++;

                $message =
                    "Row {$rowNumber}: Property ID {$propertyId} already exists.";

                $summary[] = $message;
                $writeLog('[SKIPPED] ' . $message);

                continue;
            }

            // =================================================
            // GENERATE NEW HOLDING
            // =================================================

            $holdingStmt->bind_param(
                'i',
                $wardId
            );

            $holdingStmt->execute();

            $holdingResult =
                $holdingStmt->get_result();

            $nextNumber = 1;

            if ($holdingRow = $holdingResult->fetch_assoc()) {

                $lastHolding =
                    trim(
                        (string)(
                            $holdingRow['new_holding'] ?? ''
                        )
                    );

                if ($lastHolding !== '') {

                    $parts =
                        explode(
                            '-',
                            $lastHolding
                        );

                    $lastPart =
                        end($parts);

                    if (is_numeric($lastPart)) {
                        $nextNumber =
                            ((int)$lastPart) + 1;
                    }
                }
            }

            $newHolding =
                'KLB-W' .
                $wardId .
                '-' .
                str_pad(
                    $nextNumber,
                    5,
                    '0',
                    STR_PAD_LEFT
                );

            // Make unique
            do {

                $holdingCheckStmt->bind_param(
                    's',
                    $newHolding
                );

                $holdingCheckStmt->execute();
                $holdingCheckStmt->store_result();

                if ($holdingCheckStmt->num_rows > 0) {

                    $nextNumber++;

                    $newHolding =
                        'KLB-W' .
                        $wardId .
                        '-' .
                        str_pad(
                            $nextNumber,
                            5,
                            '0',
                            STR_PAD_LEFT
                        );

                    $holdingCheckStmt->free_result();
                }
            } while ($holdingCheckStmt->num_rows > 0);

            // =================================================
            // BILL FALLBACK
            // =================================================

            if ($billNo === '') {

                $fyDigits =
                    preg_replace(
                        '/[^0-9]/',
                        '',
                        $financialYear
                    );

                $fyShort =
                    substr(
                        $fyDigits,
                        -4
                    );

                $billNo =
                    'KLB552' .
                    $fyShort .
                    $wardId .
                    str_pad(
                        random_int(1, 99999),
                        5,
                        '0',
                        STR_PAD_LEFT
                    );
            }

            if ($billDate === null) {
                $billDate = date('Y-m-d');
            }

            // =================================================
            // TRANSACTION
            // =================================================

            $conn->begin_transaction();

            try {

                // ---------------------------------------------
                // ASSESSMENT
                // ---------------------------------------------

                $municipalityName =
                    'Nagar Palika Parishad Khalilabad';

                $wardText =
                    (string)$wardId;

                $oldHolding =
                    null;

                $oldPid =
                    $oldPropertyId !== ''
                    ? $oldPropertyId
                    : null;

                $waterTaxFlag =
                    $waterTotal > 0
                    ? 1.00
                    : 0.00;

                $environment =
                    'web';

                $verification_status = "pending";

                /*
                 * assessments table has plot_area and building_type.
                 * Total Area -> plot_area
                 * Nature of House -> building_type
                 * Road Location -> road
                 */

                $assessmentStmt->bind_param(

                    'ssiiisssssssssdssssssssdssi',

                    $municipalityName,
                    $financialYear,

                    $zoneId,
                    $wardId,
                    $mohallaId,

                    $propertyId,
                    $wardText,
                    $newHolding,

                    $propertyStatus,
                    $oldHolding,
                    $oldPid,

                    $propertyType,
                    $roadLocation,

                    $totalArea,

                    $natureOfHouse,

                    $latitude,
                    $longitude,

                    $houseNo,

                    $plotNo,
                    $khataNo,
                    $khasraNo,

                    $address,
                    $address,

                    $waterTaxFlag,
                    $environment,
                    $verification_status,

                    $user_id
                );

                $assessmentStmt->execute();

                $assessmentId =
                    $conn->insert_id;

                if (!$assessmentId) {
                    throw new Exception(
                        'Assessment insert failed.'
                    );
                }

                // ---------------------------------------------
                // OWNER
                // ---------------------------------------------

                $ownerStmt->bind_param(
                    'isss',
                    $assessmentId,
                    $ownerName,
                    $fatherName,
                    $mobile
                );

                $ownerStmt->execute();

                // ---------------------------------------------
                // ARV DETAILS
                // ---------------------------------------------

                /*
                 * Current upload does NOT calculate rates.
                 *
                 * house_tax_arv = Total ARV from Excel.
                 * Other ARV fields = 0 because Excel has only Total ARV.
                 *
                 * Legacy *_amount fields store the corresponding total.
                 */

                $arStatus =
                    'bulk';

                $constructionType =
                    $natureOfHouse;

                $houseArv =
                    $totalArv;

                $houseAmount =
                    $houseTotal;

                $waterAmount =
                    $waterTotal;

                $waterFeeAmount =
                    $waterChargeTotal;

                $sewerAmount =
                    $sewerTotal;

                $otherAmount =
                    $otherTotal;

                /*
                 * 39 placeholders:
                 *
                 * 1 assessment_id
                 * 3 location IDs
                 * 1 financial year
                 * 1 construction type
                 * 3 property/bill fields
                 * 7 ARV/amount/total values
                 * 20 tax breakdown values
                 * 2 discount/advance
                 * 1 status
                 */

                // 39 placeholders in the ARV INSERT.
                $arvTypes =
                    'iiiisssss' .
                    str_repeat('d', 29) .
                    's';

                /*
                 * IMPORTANT:
                 * discount is not available as a separate Excel column,
                 * therefore 0.00 is used.
                 */
                $discountAmount = 0.00;

                $arvStmt->bind_param(

                    $arvTypes,

                    $assessmentId,

                    $zoneId,
                    $wardId,
                    $mohallaId,

                    $financialYear,
                    $constructionType,

                    $propertyId,
                    $billNo,
                    $billDate,

                    $houseArv,
                    $houseAmount,

                    $waterAmount,

                    $waterFeeAmount,

                    $sewerAmount,

                    $otherAmount,

                    $totalTax,

                    $houseCurrent,
                    $houseArrear,
                    $houseInterest,
                    $houseTotal,

                    $waterCurrent,
                    $waterArrear,
                    $waterInterest,
                    $waterTotal,

                    $waterChargeCurrent,
                    $waterChargeArrear,
                    $waterChargeInterest,
                    $waterChargeTotal,

                    $sewerCurrent,
                    $sewerArrear,
                    $sewerInterest,
                    $sewerTotal,

                    $otherCurrent,
                    $otherArrear,
                    $otherInterest,
                    $otherTotal,

                    $discountAmount,
                    $advanceDeposit,

                    $arStatus
                );

                $arvStmt->execute();

                // ---------------------------------------------
                // COMMIT
                // ---------------------------------------------

                $conn->commit();

                $success++;

                $message =
                    "Row {$rowNumber}: Uploaded successfully. " .
                    "Assessment ID: {$assessmentId}, " .
                    "Holding: {$newHolding}, " .
                    "Property ID: {$propertyId}";

                $summary[] =
                    $message;

                $writeLog(
                    '[SUCCESS] ' . $message
                );
            } catch (Throwable $e) {

                $conn->rollback();

                throw $e;
            }
        } catch (Throwable $e) {

            $errors++;

            $message =
                "Row {$rowNumber}: " .
                $e->getMessage();

            $summary[] =
                $message;

            $writeLog(
                '[ERROR] ' . $message
            );
        }
    }

    // ---------------------------------------------------------
    // CLOSE STATEMENTS
    // ---------------------------------------------------------
    fclose($handle);

    $assessmentStmt->close();
    $ownerStmt->close();
    $arvStmt->close();
    $duplicateStmt->close();
    $holdingStmt->close();
    $holdingCheckStmt->close();

    $writeLog(
        "FINISHED | Success: {$success} | Skipped: {$skipped} | Errors: {$errors}"
    );

    if ($logHandle) {
        fclose($logHandle);
    }

    // ---------------------------------------------------------
    // RESPONSE
    // ---------------------------------------------------------
    $status =
        $errors === 0
        ? 'success'
        : (
            $success > 0
            ? 'partial'
            : 'error'
        );

    echo json_encode([

        'status' =>
        $status,

        'message' =>
        "Process Finished. Success: {$success}, Skipped: {$skipped}, Failures: {$errors}",

        'inserted' =>
        $success,

        'skipped' =>
        $skipped,

        'errors' =>
        $errors,

        'details' =>
        array_slice(
            $summary,
            0,
            100
        ),

        'log_file' =>
        'logs/' . $logFileName

    ], JSON_UNESCAPED_UNICODE);

    exit;
} catch (Throwable $e) {

    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }

    http_response_code(500);

    echo json_encode([

        'status' =>
        'error',

        'message' =>
        $e->getMessage(),

        'line' =>
        $e->getLine()

    ], JSON_UNESCAPED_UNICODE);

    exit;
}
