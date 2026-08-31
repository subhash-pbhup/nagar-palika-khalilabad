<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
error_reporting(E_ALL);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


/*
|--------------------------------------------------------------------------
| ADMIN AUTH
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    http_response_code(403);

    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

include "db.php";


/*
|--------------------------------------------------------------------------
| REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| FILE
|--------------------------------------------------------------------------
*/

if (
    !isset($_FILES['file']) ||
    $_FILES['file']['error'] !== UPLOAD_ERR_OK
) {

    echo json_encode([
        'status' => 'error',
        'message' => 'CSV file was not received.'
    ]);

    exit;
}


$file = $_FILES['file'];


/*
|--------------------------------------------------------------------------
| EXTENSION
|--------------------------------------------------------------------------
*/

$extension = strtolower(
    pathinfo($file['name'], PATHINFO_EXTENSION)
);


if ($extension !== 'csv') {

    echo json_encode([
        'status' => 'error',
        'message' => 'Only CSV files are allowed.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| OPEN CSV
|--------------------------------------------------------------------------
*/

$handle = fopen($file['tmp_name'], 'r');


if (!$handle) {

    echo json_encode([
        'status' => 'error',
        'message' => 'Unable to open CSV file.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| READ HEADER
|--------------------------------------------------------------------------
*/

$header = fgetcsv($handle);


if (!$header) {

    fclose($handle);

    echo json_encode([
        'status' => 'error',
        'message' => 'CSV header not found.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| CLEAN HEADER
|--------------------------------------------------------------------------
*/

$header = array_map(function ($value) {

    $value = preg_replace(
        '/^\xEF\xBB\xBF/',
        '',
        $value
    );

    return strtolower(trim($value));
}, $header);


/*
|--------------------------------------------------------------------------
| EXPECTED 44 COLUMNS
|--------------------------------------------------------------------------
*/

$expectedColumns = [

    'assessment year',
    'zone id',
    'ward id',
    'mohalla id',

    'property status',
    'property type',

    'house no',
    'plot no',
    'khata no',
    'khasra no',

    'address1',
    'address2',

    'owner name',
    'father name',
    'mobile no',

    'property id',

    'house arv',
    'house tax current',
    'house arrear',
    'house interest',
    'total house tax',

    'water arv',
    'water tax current',
    'water arrear',
    'water interest',
    'total water tax',

    'water fee arv',
    'water fee current',
    'water fee arrear',
    'water fee interest',
    'total water fee',

    'sewer arv',
    'sewer current',
    'sewer arrear',
    'sewer interest',
    'total sewer tax',

    'other arv',
    'other current',
    'other arrear',
    'other interest',
    'total other tax',

    'discount amount',
    'advance deposit',

    'grand total (net payable)'
];


/*
|--------------------------------------------------------------------------
| HEADER COUNT
|--------------------------------------------------------------------------
*/

if (count($header) !== 44) {

    fclose($handle);

    echo json_encode([

        'status' => 'error',

        'message' =>
        'CSV must contain exactly 44 columns.',

        'received_columns' =>
        count($header),

        'expected_columns' =>
        44

    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| HEADER MATCH
|--------------------------------------------------------------------------
*/

if ($header !== $expectedColumns) {

    $missing = array_values(
        array_diff(
            $expectedColumns,
            $header
        )
    );

    $extra = array_values(
        array_diff(
            $header,
            $expectedColumns
        )
    );

    fclose($handle);

    echo json_encode([

        'status' => 'error',

        'message' =>
        'CSV header format is incorrect.',

        'missing_columns' =>
        $missing,

        'extra_columns' =>
        $extra

    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| COLUMN INDEX
|--------------------------------------------------------------------------
*/

$index = [];

foreach ($header as $key => $column) {

    $index[$column] = $key;
}


/*
|--------------------------------------------------------------------------
| BATCH ID
|--------------------------------------------------------------------------
*/

$batchId =
    'ARV-' .
    date('YmdHis') .
    '-' .
    strtoupper(
        substr(
            bin2hex(random_bytes(5)),
            0,
            10
        )
    );


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function csvValue(
    array $row,
    array $index,
    string $column
): string {

    return trim(
        $row[$index[$column]] ?? ''
    );
}


function numericValue(
    string $value
): float {

    if ($value === '') {
        return 0;
    }

    $value = str_replace(
        ',',
        '',
        $value
    );

    if (!is_numeric($value)) {

        throw new Exception(
            'Invalid numeric value: ' . $value
        );
    }

    return (float)$value;
}


/*
|--------------------------------------------------------------------------
| NUMERIC COLUMNS
|--------------------------------------------------------------------------
*/

$numericColumns = [

    'house arv',
    'house tax current',
    'house arrear',
    'house interest',
    'total house tax',

    'water arv',
    'water tax current',
    'water arrear',
    'water interest',
    'total water tax',

    'water fee arv',
    'water fee current',
    'water fee arrear',
    'water fee interest',
    'total water fee',

    'sewer arv',
    'sewer current',
    'sewer arrear',
    'sewer interest',
    'total sewer tax',

    'other arv',
    'other current',
    'other arrear',
    'other interest',
    'total other tax',

    'discount amount',
    'advance deposit',

    'grand total (net payable)'
];


/*
|--------------------------------------------------------------------------
| INSERT QUERY
|--------------------------------------------------------------------------
*/

$insertSQL = "

INSERT INTO arv_bulk_upload (

    assessment_year,

    zone_id,
    ward_id,
    mohalla_id,

    property_status,
    property_type,

    house_no,
    plot_no,
    khata_no,
    khasra_no,

    address1,
    address2,

    owner_name,
    father_name,
    mobile_no,

    property_id,

    house_arv,
    house_tax_current,
    house_arrear,
    house_interest,
    total_house_tax,

    water_arv,
    water_tax_current,
    water_arrear,
    water_interest,
    total_water_tax,

    water_fee_arv,
    water_fee_current,
    water_fee_arrear,
    water_fee_interest,
    total_water_fee,

    sewer_arv,
    sewer_current,
    sewer_arrear,
    sewer_interest,
    total_sewer_tax,

    other_arv,
    other_current,
    other_arrear,
    other_interest,
    total_other_tax,

    discount_amount,
    advance_deposit,

    grand_total_net_payable,

    upload_batch_id,
    upload_status

)

VALUES (

    ?,

    ?,
    ?,
    ?,

    ?,
    ?,

    ?,
    ?,
    ?,
    ?,

    ?,
    ?,

    ?,
    ?,
    ?,

    ?,

    ?,
    ?,
    ?,
    ?,
    ?,

    ?,
    ?,
    ?,
    ?,
    ?,

    ?,
    ?,
    ?,
    ?,
    ?,

    ?,
    ?,
    ?,
    ?,
    ?,

    ?,
    ?,
    ?,
    ?,
    ?,

    ?,
    ?,

    ?,

    ?,
    'pending'

)

";


$insertStmt = $conn->prepare($insertSQL);


/*
|--------------------------------------------------------------------------
| DUPLICATE CHECK
|--------------------------------------------------------------------------
*/

$duplicateStmt = $conn->prepare("

    SELECT id

    FROM arv_bulk_upload

    WHERE property_id = ?

      AND assessment_year = ?

    LIMIT 1

");


/*
|--------------------------------------------------------------------------
| TRANSACTION
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();


$inserted = 0;
$skipped = 0;
$errorCount = 0;

$errors = [];

$rowNumber = 1;


try {

    /*
    |--------------------------------------------------------------------------
    | READ ROWS
    |--------------------------------------------------------------------------
    */

    while (($row = fgetcsv($handle)) !== false) {

        $rowNumber++;


        /*
        | Empty row
        */

        $hasData = false;

        foreach ($row as $value) {

            if (trim($value) !== '') {

                $hasData = true;

                break;
            }
        }


        if (!$hasData) {
            continue;
        }


        /*
        |--------------------------------------------------------------------------
        | BASIC DATA
        |--------------------------------------------------------------------------
        */

        $assessmentYear =
            csvValue(
                $row,
                $index,
                'assessment year'
            );


        $zoneIdValue =
            csvValue(
                $row,
                $index,
                'zone id'
            );


        $wardIdValue =
            csvValue(
                $row,
                $index,
                'ward id'
            );


        $mohallaIdValue =
            csvValue(
                $row,
                $index,
                'mohalla id'
            );


        $propertyStatus =
            csvValue(
                $row,
                $index,
                'property status'
            );


        $propertyType =
            csvValue(
                $row,
                $index,
                'property type'
            );


        $houseNo =
            csvValue(
                $row,
                $index,
                'house no'
            );


        $plotNo =
            csvValue(
                $row,
                $index,
                'plot no'
            );


        $khataNo =
            csvValue(
                $row,
                $index,
                'khata no'
            );


        $khasraNo =
            csvValue(
                $row,
                $index,
                'khasra no'
            );


        $address1 =
            csvValue(
                $row,
                $index,
                'address1'
            );


        $address2 =
            csvValue(
                $row,
                $index,
                'address2'
            );


        $ownerName =
            csvValue(
                $row,
                $index,
                'owner name'
            );


        $fatherName =
            csvValue(
                $row,
                $index,
                'father name'
            );


        $mobileNo =
            csvValue(
                $row,
                $index,
                'mobile no'
            );


        $propertyId =
            csvValue(
                $row,
                $index,
                'property id'
            );


        /*
        |--------------------------------------------------------------------------
        | REQUIRED
        |--------------------------------------------------------------------------
        */

        if ($assessmentYear === '') {

            $errorCount++;

            $errors[] =
                "Row {$rowNumber}: Assessment Year required.";

            continue;
        }


        if ($propertyId === '') {

            $errorCount++;

            $errors[] =
                "Row {$rowNumber}: Property ID required.";

            continue;
        }


        /*
        |--------------------------------------------------------------------------
        | ZONE / WARD / MOHALLA
        |--------------------------------------------------------------------------
        */

        if (
            $zoneIdValue !== '' &&
            !ctype_digit($zoneIdValue)
        ) {

            $errorCount++;

            $errors[] =
                "Row {$rowNumber}: Zone ID must be numeric.";

            continue;
        }


        if (
            $wardIdValue !== '' &&
            !ctype_digit($wardIdValue)
        ) {

            $errorCount++;

            $errors[] =
                "Row {$rowNumber}: Ward ID must be numeric.";

            continue;
        }


        if (
            $mohallaIdValue !== '' &&
            !ctype_digit($mohallaIdValue)
        ) {

            $errorCount++;

            $errors[] =
                "Row {$rowNumber}: Mohalla ID must be numeric.";

            continue;
        }


        $zoneId =
            $zoneIdValue !== ''
            ? (int)$zoneIdValue
            : null;


        $wardId =
            $wardIdValue !== ''
            ? (int)$wardIdValue
            : null;


        $mohallaId =
            $mohallaIdValue !== ''
            ? (int)$mohallaIdValue
            : null;


        /*
        |--------------------------------------------------------------------------
        | NUMERIC VALUES
        |--------------------------------------------------------------------------
        */

        $num = [];

        $numericError = false;


        foreach ($numericColumns as $column) {

            $value =
                csvValue(
                    $row,
                    $index,
                    $column
                );


            try {

                $num[$column] =
                    numericValue($value);
            } catch (Throwable $e) {

                $numericError = true;

                $errorCount++;

                $errors[] =
                    "Row {$rowNumber}: " .
                    $column .
                    " must be numeric.";

                break;
            }
        }


        if ($numericError) {
            continue;
        }


        /*
        |--------------------------------------------------------------------------
        | DUPLICATE CHECK
        |--------------------------------------------------------------------------
        */

        $duplicateStmt->bind_param(
            "ss",
            $propertyId,
            $assessmentYear
        );


        $duplicateStmt->execute();

        $duplicateStmt->store_result();


        if ($duplicateStmt->num_rows > 0) {

            $skipped++;

            continue;
        }


        /*
        |--------------------------------------------------------------------------
        | VALUES
        |--------------------------------------------------------------------------
        */

        $houseArv =
            $num['house arv'];

        $houseTaxCurrent =
            $num['house tax current'];

        $houseArrear =
            $num['house arrear'];

        $houseInterest =
            $num['house interest'];

        $totalHouseTax =
            $num['total house tax'];


        $waterArv =
            $num['water arv'];

        $waterTaxCurrent =
            $num['water tax current'];

        $waterArrear =
            $num['water arrear'];

        $waterInterest =
            $num['water interest'];

        $totalWaterTax =
            $num['total water tax'];


        $waterFeeArv =
            $num['water fee arv'];

        $waterFeeCurrent =
            $num['water fee current'];

        $waterFeeArrear =
            $num['water fee arrear'];

        $waterFeeInterest =
            $num['water fee interest'];

        $totalWaterFee =
            $num['total water fee'];


        $sewerArv =
            $num['sewer arv'];

        $sewerCurrent =
            $num['sewer current'];

        $sewerArrear =
            $num['sewer arrear'];

        $sewerInterest =
            $num['sewer interest'];

        $totalSewerTax =
            $num['total sewer tax'];


        $otherArv =
            $num['other arv'];

        $otherCurrent =
            $num['other current'];

        $otherArrear =
            $num['other arrear'];

        $otherInterest =
            $num['other interest'];

        $totalOtherTax =
            $num['total other tax'];


        $discountAmount =
            $num['discount amount'];

        $advanceDeposit =
            $num['advance deposit'];

        $grandTotal =
            $num['grand total (net payable)'];


        /*
        |--------------------------------------------------------------------------
        | BIND
        |--------------------------------------------------------------------------
        |
        | 45 parameters:
        |
        | 4 initial:
        | assessment_year + zone + ward + mohalla
        |
        | 12 strings:
        | property status through property id
        |
        | 28 decimals
        |
        | 1 batch id
        |
        */

        $types =
            'siii' .
            str_repeat('s', 12) .
            str_repeat('d', 28) .
            's';


        if (strlen($types) !== 45) {

            throw new Exception(
                'Bind parameter configuration error.'
            );
        }


        $insertStmt->bind_param(

            $types,

            $assessmentYear,

            $zoneId,
            $wardId,
            $mohallaId,

            $propertyStatus,
            $propertyType,

            $houseNo,
            $plotNo,
            $khataNo,
            $khasraNo,

            $address1,
            $address2,

            $ownerName,
            $fatherName,
            $mobileNo,

            $propertyId,

            $houseArv,
            $houseTaxCurrent,
            $houseArrear,
            $houseInterest,
            $totalHouseTax,

            $waterArv,
            $waterTaxCurrent,
            $waterArrear,
            $waterInterest,
            $totalWaterTax,

            $waterFeeArv,
            $waterFeeCurrent,
            $waterFeeArrear,
            $waterFeeInterest,
            $totalWaterFee,

            $sewerArv,
            $sewerCurrent,
            $sewerArrear,
            $sewerInterest,
            $totalSewerTax,

            $otherArv,
            $otherCurrent,
            $otherArrear,
            $otherInterest,
            $totalOtherTax,

            $discountAmount,
            $advanceDeposit,

            $grandTotal,

            $batchId

        );


        /*
        |--------------------------------------------------------------------------
        | INSERT
        |--------------------------------------------------------------------------
        */

        if (!$insertStmt->execute()) {

            $errorCount++;

            $errors[] =
                "Row {$rowNumber}: " .
                $insertStmt->error;

            continue;
        }


        $inserted++;
    }


    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    $conn->commit();


    fclose($handle);

    $insertStmt->close();
    $duplicateStmt->close();


    /*
    |--------------------------------------------------------------------------
    | RESPONSE
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        'status' => 'success',

        'message' =>
        'Bulk property data uploaded successfully.',

        'batch_id' =>
        $batchId,

        'inserted' =>
        $inserted,

        'skipped' =>
        $skipped,

        'errors' =>
        $errorCount,

        'details' =>
        array_slice(
            $errors,
            0,
            20
        )

    ]);

    exit;
} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    $conn->rollback();


    if (is_resource($handle)) {
        fclose($handle);
    }


    http_response_code(500);


    error_log(
        'ARV BULK UPLOAD ERROR: ' .
            $e->getMessage() .
            ' | Line: ' .
            $e->getLine()
    );


    echo json_encode([

        'status' =>
        'error',

        'message' =>
        $e->getMessage(),

        'row' =>
        $rowNumber

    ]);

    exit;
}
