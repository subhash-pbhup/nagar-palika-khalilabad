<?php
session_start();

require_once "db.php";

/*
|--------------------------------------------------------------------------
| Khalilabad Nagar Palika - Process Payment
|--------------------------------------------------------------------------
| IMPORTANT:
| - This file creates the payment order as PENDING.
| - A payment must become PAID only after a real payment gateway
|   success/callback is verified.
| - It never inserts a blank order_number.
| - It stores payer/property/payment details in property_payment_details.
|--------------------------------------------------------------------------
*/

/* =========================================================
   HELPERS
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function clean($value)
{
    return trim((string)($value ?? ''));
}

function tableExists($conn, $table)
{
    $table = $conn->real_escape_string($table);

    $q = $conn->query(
        "SHOW TABLES LIKE '{$table}'"
    );

    return $q && $q->num_rows > 0;
}

function columnExists($conn, $table, $column)
{
    if (!tableExists($conn, $table)) {
        return false;
    }

    $table  = str_replace('`', '', $table);
    $column = $conn->real_escape_string($column);

    $q = $conn->query(
        "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'"
    );

    return $q && $q->num_rows > 0;
}

function normalizeFinancialYear($value)
{
    $value = trim((string)($value ?? ''));

    if ($value === '') {
        return '';
    }

    $value = str_replace(
        ['/', '_', ' '],
        ['-', '-', ''],
        $value
    );

    if (
        preg_match(
            '/^(20\d{2})-(20\d{2})$/',
            $value,
            $m
        )
    ) {
        return $m[1] . '-' . $m[2];
    }

    if (
        preg_match(
            '/^(20\d{2})-(\d{2})$/',
            $value,
            $m
        )
    ) {
        return $m[1] . '-' . (2000 + (int)$m[2]);
    }

    return $value;
}

function rowValue($row, $keys, $default = '')
{
    if (!is_array($row)) {
        return $default;
    }

    foreach ($keys as $key) {

        if (
            array_key_exists($key, $row) &&
            $row[$key] !== null &&
            $row[$key] !== ''
        ) {
            return $row[$key];
        }
    }

    return $default;
}

function generateOrderNumber()
{
    /*
     * NEVER return an empty string.
     *
     * Example:
     * KLB-20260919-131025-7F4A92C1
     */
    return 'KLB-'
        . date('Ymd-His')
        . '-'
        . strtoupper(
            bin2hex(random_bytes(4))
        );
}

function redirectError(
    $message,
    $assessment_id = 0,
    $years = []
) {
    $params = [];

    if ((int)$assessment_id > 0) {
        $params['id'] = (int)$assessment_id;
        $params['assessment_id'] = (int)$assessment_id;
    }

    if (!empty($years)) {
        $params['years'] = array_values($years);
    }

    $params['error'] = $message;

    header(
        'Location: pay-online.php?' .
            http_build_query($params)
    );

    exit;
}

/*
 * Dynamic insert:
 * Only columns which actually exist in the current table are inserted.
 */
function dynamicInsert($conn, $table, $data)
{
    $columns = [];
    $values  = [];
    $types   = '';

    foreach ($data as $column => $item) {

        if (
            !isset($item['value']) ||
            !isset($item['type'])
        ) {
            continue;
        }

        if (
            !columnExists(
                $conn,
                $table,
                $column
            )
        ) {
            continue;
        }

        $columns[] = '`' . $column . '`';
        $values[]  = $item['value'];
        $types    .= $item['type'];
    }

    if (empty($columns)) {
        throw new Exception(
            "No matching columns found in {$table}."
        );
    }

    $placeholders =
        implode(
            ', ',
            array_fill(
                0,
                count($columns),
                '?'
            )
        );

    $sql = "
        INSERT INTO `{$table}`
        (" . implode(', ', $columns) . ")
        VALUES ({$placeholders})
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception(
            "Prepare failed for {$table}: " .
                $conn->error
        );
    }

    $bind = [$types];

    foreach ($values as $key => &$value) {
        $bind[] = &$value;
    }

    if (
        !call_user_func_array(
            [$stmt, 'bind_param'],
            $bind
        )
    ) {
        $error = $stmt->error ?: $conn->error;

        $stmt->close();

        throw new Exception(
            "Bind failed for {$table}: " . $error
        );
    }

    if (!$stmt->execute()) {

        $error =
            $stmt->error
            ?: $conn->error;

        $stmt->close();

        throw new Exception(
            $error
        );
    }

    $id = (int)$stmt->insert_id;

    $stmt->close();

    return $id;
}

/* =========================================================
   REQUEST
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
) {
    redirectError(
        'Invalid payment request.'
    );
}

/* =========================================================
   ASSESSMENT ID
========================================================= */

$assessment_id = (int)(
    $_POST['assessment_id']
    ?? $_POST['property_id']
    ?? $_POST['id']
    ?? 0
);

if ($assessment_id <= 0) {
    redirectError(
        'Invalid assessment ID.'
    );
}

/* =========================================================
   SELECTED YEARS
========================================================= */

$input_years =
    $_POST['years']
    ?? $_POST['selected_years']
    ?? [];

if (!is_array($input_years)) {
    $input_years = [$input_years];
}

$selected_years = [];

foreach ($input_years as $year) {

    $year =
        normalizeFinancialYear($year);

    if (
        $year !== '' &&
        preg_match(
            '/^20\d{2}-20\d{2}$/',
            $year
        )
    ) {
        $selected_years[$year] = true;
    }
}

$selected_years =
    array_keys($selected_years);

if (empty($selected_years)) {
    redirectError(
        'Please select at least one financial year.',
        $assessment_id
    );
}

/* =========================================================
   PAYER
========================================================= */

$payer_name =
    clean($_POST['payer_name'] ?? '');

$payer_mobile =
    clean($_POST['payer_mobile'] ?? '');

$payment_made_at =
    clean($_POST['payment_made_at'] ?? '');

$payment_mode =
    clean($_POST['payment_mode'] ?? '');

if ($payer_name === '') {
    redirectError(
        'Payer name is required.',
        $assessment_id,
        $selected_years
    );
}

if (
    $payer_mobile !== '' &&
    !preg_match(
        '/^[0-9]{10}$/',
        $payer_mobile
    )
) {
    redirectError(
        'Please enter a valid 10 digit mobile number.',
        $assessment_id,
        $selected_years
    );
}

/* =========================================================
   TABLE CHECK
========================================================= */

if (
    !tableExists(
        $conn,
        'property_payment_orders'
    )
) {
    redirectError(
        'property_payment_orders table not found.',
        $assessment_id,
        $selected_years
    );
}

if (
    !tableExists(
        $conn,
        'property_payment_years'
    )
) {
    redirectError(
        'property_payment_years table not found.',
        $assessment_id,
        $selected_years
    );
}

if (
    !tableExists(
        $conn,
        'property_payment_details'
    )
) {
    redirectError(
        'property_payment_details table not found.',
        $assessment_id,
        $selected_years
    );
}

/* =========================================================
   ASSESSMENT
========================================================= */

$stmt = $conn->prepare("
    SELECT *
    FROM assessments
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    redirectError(
        'Assessment query error: ' .
            $conn->error,
        $assessment_id,
        $selected_years
    );
}

$stmt->bind_param(
    'i',
    $assessment_id
);

$stmt->execute();

$assessment =
    $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();

if (!$assessment) {
    redirectError(
        'Assessment/property not found.',
        $assessment_id,
        $selected_years
    );
}

/* =========================================================
   PROPERTY / OWNER
========================================================= */

$property_no = rowValue(
    $assessment,
    [
        'new_holding',
        'new_holding_number',
        'holding_no',
        'holding_number',
        'property_no'
    ],
    ''
);

$owner_name = rowValue(
    $assessment,
    [
        'owner_name',
        'name'
    ],
    $payer_name
);

$owner_mobile = rowValue(
    $assessment,
    [
        'mobile',
        'mobile_no',
        'mobile_number',
        'phone'
    ],
    $payer_mobile
);

$owner_email = rowValue(
    $assessment,
    [
        'email',
        'email_id'
    ],
    ''
);

/* =========================================================
   OWNER TABLE
========================================================= */

if (
    tableExists(
        $conn,
        'assessment_owners'
    )
) {

    $owner_stmt = $conn->prepare("
        SELECT *
        FROM assessment_owners
        WHERE assessment_id = ?
        ORDER BY id ASC
        LIMIT 1
    ");

    if ($owner_stmt) {

        $owner_stmt->bind_param(
            'i',
            $assessment_id
        );

        if ($owner_stmt->execute()) {

            $owner =
                $owner_stmt
                ->get_result()
                ->fetch_assoc();

            if ($owner) {

                $owner_name =
                    rowValue(
                        $owner,
                        [
                            'owner_name',
                            'name'
                        ],
                        $owner_name
                    );

                $owner_mobile =
                    rowValue(
                        $owner,
                        [
                            'mobile',
                            'mobile_no',
                            'mobile_number',
                            'phone'
                        ],
                        $owner_mobile
                    );

                $owner_email =
                    rowValue(
                        $owner,
                        [
                            'email',
                            'email_id'
                        ],
                        $owner_email
                    );
            }
        }

        $owner_stmt->close();
    }
}

if ($payer_name === '') {
    $payer_name = $owner_name;
}

if ($payer_mobile === '') {
    $payer_mobile = $owner_mobile;
}

/* =========================================================
   DEMAND
========================================================= */

$stmt = $conn->prepare("
    SELECT *
    FROM property_arv_details
    WHERE assessment_id = ?
    ORDER BY id ASC
");

if (!$stmt) {
    redirectError(
        'Demand query error: ' .
            $conn->error,
        $assessment_id,
        $selected_years
    );
}

$stmt->bind_param(
    'i',
    $assessment_id
);

$stmt->execute();

$result =
    $stmt->get_result();

$demand_rows = [];

while (
    $row =
    $result->fetch_assoc()
) {
    $demand_rows[] = $row;
}

$stmt->close();

if (empty($demand_rows)) {
    redirectError(
        'No property demand found.',
        $assessment_id,
        $selected_years
    );
}

/* =========================================================
   MAP FINANCIAL YEAR
========================================================= */

$total_demand_rows =
    count($demand_rows);

foreach (
    $demand_rows as $index => &$row
) {

    $fy =
        normalizeFinancialYear(
            $row['financial_year']
                ?? ''
        );

    if (
        !preg_match(
            '/^20\d{2}-20\d{2}$/',
            $fy
        )
    ) {

        $current_start =
            ((int)date('n') >= 4)
            ? (int)date('Y')
            : (int)date('Y') - 1;

        $start =
            $current_start
            - (
                $total_demand_rows
                - 1
                - $index
            );

        $fy =
            $start .
            '-' .
            ($start + 1);
    }

    $row['_financial_year'] =
        $fy;
}

unset($row);

/* =========================================================
   ALREADY PAID YEARS
========================================================= */

$paid_years = [];

$status_condition = '';

if (
    columnExists(
        $conn,
        'property_payment_orders',
        'status'
    )
) {

    $status_condition =
        "LOWER(TRIM(po.status)) IN
        ('paid','success','successful','completed','complete')";
} elseif (
    columnExists(
        $conn,
        'property_payment_orders',
        'payment_status'
    )
) {

    $status_condition =
        "LOWER(TRIM(po.payment_status)) IN
        ('paid','success','successful','completed','complete')";
}

if (
    $status_condition !== '' &&
    columnExists(
        $conn,
        'property_payment_years',
        'financial_year'
    ) &&
    columnExists(
        $conn,
        'property_payment_years',
        'payment_order_id'
    ) &&
    columnExists(
        $conn,
        'property_payment_years',
        'assessment_id'
    )
) {

    $paid_sql = "
        SELECT py.financial_year
        FROM property_payment_years py
        INNER JOIN property_payment_orders po
            ON po.id = py.payment_order_id
        WHERE py.assessment_id = ?
          AND {$status_condition}
    ";

    $paid_stmt =
        $conn->prepare($paid_sql);

    if ($paid_stmt) {

        $paid_stmt->bind_param(
            'i',
            $assessment_id
        );

        if ($paid_stmt->execute()) {

            $paid_result =
                $paid_stmt
                ->get_result();

            while (
                $paid =
                $paid_result->fetch_assoc()
            ) {

                $fy =
                    normalizeFinancialYear(
                        $paid['financial_year']
                    );

                if ($fy !== '') {
                    $paid_years[$fy] = true;
                }
            }
        }

        $paid_stmt->close();
    }
}

/* =========================================================
   SELECT DEMANDS
========================================================= */

$selected_demands = [];

foreach ($demand_rows as $row) {

    $fy =
        $row['_financial_year'];

    if (
        !in_array(
            $fy,
            $selected_years,
            true
        )
    ) {
        continue;
    }

    if (
        isset($paid_years[$fy])
    ) {
        continue;
    }

    $selected_demands[$fy] =
        $row;
}

if (count($selected_demands) !== count($selected_years)) {

    $already_paid =
        array_keys(
            array_intersect_key(
                $paid_years,
                array_flip($selected_years)
            )
        );

    if (!empty($already_paid)) {

        redirectError(
            'These financial years are already paid: ' .
                implode(', ', $already_paid),
            $assessment_id,
            $selected_years
        );
    }

    redirectError(
        'One or more selected financial years do not have a valid demand record.',
        $assessment_id,
        $selected_years
    );
}

/* =========================================================
   AMOUNT
========================================================= */

$tax_amount = 0.00;

foreach (
    $selected_demands as $demand
) {
    $tax_amount +=
        (float)(
            $demand['total_tax']
            ?? 0
        );
}

$last_demand =
    end($selected_demands);

$other_amount =
    (float)rowValue(
        $last_demand,
        [
            'other_amount',
            'other_charge'
        ],
        0
    );

$form_fee =
    (float)rowValue(
        $last_demand,
        ['form_fee'],
        5
    );

$boring_charge =
    (float)rowValue(
        $last_demand,
        [
            'boring_charge',
            'boring_fee',
            'boring_amount'
        ],
        0
    );

$advance_received =
    (float)rowValue(
        $last_demand,
        [
            'advance_received',
            'advance_amount',
            'advance_deposit'
        ],
        0
    );

/*
 * If a previous successful payment has already paid the form fee,
 * don't charge the form fee again.
 */
$successful_status =
    "
    LOWER(TRIM(status))
    IN (
        'paid',
        'success',
        'successful',
        'completed',
        'complete'
    )
    ";

if (
    columnExists(
        $conn,
        'property_payment_orders',
        'status'
    ) &&
    columnExists(
        $conn,
        'property_payment_orders',
        'form_fee'
    )
) {

    $fee_stmt = $conn->prepare("
        SELECT COALESCE(
            SUM(form_fee),
            0
        ) AS paid_form_fee
        FROM property_payment_orders
        WHERE assessment_id = ?
          AND {$successful_status}
    ");

    if ($fee_stmt) {

        $fee_stmt->bind_param(
            'i',
            $assessment_id
        );

        if ($fee_stmt->execute()) {

            $fee =
                $fee_stmt
                ->get_result()
                ->fetch_assoc();

            if (
                (float)(
                    $fee['paid_form_fee']
                    ?? 0
                ) > 0
            ) {
                $form_fee = 0.00;
            }
        }

        $fee_stmt->close();
    }
}

$total_amount =
    $tax_amount +
    $form_fee +
    $other_amount +
    $boring_charge -
    $advance_received;

if ($total_amount < 0) {
    $total_amount = 0;
}

if ($total_amount <= 0) {
    redirectError(
        'Payment amount is zero.',
        $assessment_id,
        $selected_years
    );
}

/* =========================================================
   CREATE PENDING ORDER
========================================================= */

$conn->begin_transaction();

try {

    $payment_order_id = 0;
    $order_number = '';

    /*
     * Generate and insert a UNIQUE, NON-BLANK order number.
     */
    for (
        $attempt = 1;
        $attempt <= 10;
        $attempt++
    ) {

        $order_number =
            generateOrderNumber();

        /*
     * CASH / CHEQUE / DD are already received modes, so their order
     * can be marked PAID immediately.
     *
     * ONLINE must remain PENDING until the real gateway callback
     * verifies the transaction.
     */
        $normalized_payment_mode = strtolower(trim($payment_mode));

        $is_direct_received_payment = in_array(
            $normalized_payment_mode,
            ['cash', 'cheque', 'dd'],
            true
        );

        $initial_status = $is_direct_received_payment
            ? 'paid'
            : 'pending';

        $order_data = [

            'order_number' => [
                'value' => $order_number,
                'type'  => 's'
            ],

            'assessment_id' => [
                'value' => $assessment_id,
                'type'  => 'i'
            ],

            'property_no' => [
                'value' => $property_no,
                'type'  => 's'
            ],

            'payer_name' => [
                'value' => $payer_name,
                'type'  => 's'
            ],

            'payer_mobile' => [
                'value' => $payer_mobile,
                'type'  => 's'
            ],

            'payer_email' => [
                'value' => $owner_email,
                'type'  => 's'
            ],

            'payment_made_at' => [
                'value' => $payment_made_at,
                'type'  => 's'
            ],

            'payment_mode' => [
                'value' => $payment_mode,
                'type'  => 's'
            ],

            'selected_years' => [
                'value' => implode(
                    ',',
                    $selected_years
                ),
                'type' => 's'
            ],

            'tax_amount' => [
                'value' => $tax_amount,
                'type'  => 'd'
            ],

            'other_amount' => [
                'value' => $other_amount,
                'type'  => 'd'
            ],

            'form_fee' => [
                'value' => $form_fee,
                'type'  => 'd'
            ],

            'boring_charge' => [
                'value' => $boring_charge,
                'type'  => 'd'
            ],

            'advance_received' => [
                'value' => $advance_received,
                'type'  => 'd'
            ],

            'total_amount' => [
                'value' => $total_amount,
                'type'  => 'd'
            ],

            /*
             * IMPORTANT:
             * This order is only CREATED here.
             * It is NOT marked paid until a real gateway
             * verification succeeds.
             */
            'status' => [
                'value' => $initial_status,
                'type'  => 's'
            ],

            'payment_status' => [
                'value' => $initial_status,
                'type'  => 's'
            ],

            'created_at' => [
                'value' => date(
                    'Y-m-d H:i:s'
                ),
                'type' => 's'
            ],

            'updated_at' => [
                'value' => date(
                    'Y-m-d H:i:s'
                ),
                'type' => 's'
            ]
        ];

        try {

            $payment_order_id =
                dynamicInsert(
                    $conn,
                    'property_payment_orders',
                    $order_data
                );

            if ($payment_order_id > 0) {
                break;
            }
        } catch (Throwable $insertError) {

            /*
             * UNIQUE order_number collision:
             * generate another number and retry.
             */
            $isDuplicate =
                stripos(
                    $insertError->getMessage(),
                    'Duplicate entry'
                ) !== false &&
                stripos(
                    $insertError->getMessage(),
                    'order_number'
                ) !== false;

            if (
                !$isDuplicate ||
                $attempt >= 10
            ) {
                throw $insertError;
            }
        }
    }

    if ($payment_order_id <= 0) {
        throw new Exception(
            'Unable to create payment order.'
        );
    }

    /* =====================================================
       SAVE YEAR-WISE PAYMENT DATA
    ===================================================== */

    foreach (
        $selected_demands as $fy => $demand
    ) {

        $year_data = [

            'payment_order_id' => [
                'value' => $payment_order_id,
                'type'  => 'i'
            ],

            'assessment_id' => [
                'value' => $assessment_id,
                'type'  => 'i'
            ],

            'financial_year' => [
                'value' => $fy,
                'type'  => 's'
            ],

            /*
             * Current table may use arv_detail_id OR
             * demand_record_id.
             */
            'arv_detail_id' => [
                'value' => (int)(
                    $demand['id'] ?? 0
                ),
                'type' => 'i'
            ],

            'demand_record_id' => [
                'value' => (int)(
                    $demand['id'] ?? 0
                ),
                'type' => 'i'
            ],

            'tax_amount' => [
                'value' => (float)(
                    $demand['total_tax']
                    ?? 0
                ),
                'type' => 'd'
            ],

            'rebate' => [
                'value' => (float)(
                    $demand['rebate']
                    ?? 0
                ),
                'type' => 'd'
            ],

            'penalty' => [
                'value' => (float)(
                    $demand['penalty']
                    ?? $demand['interest']
                    ?? 0
                ),
                'type' => 'd'
            ],

            'total_tax' => [
                'value' => (float)(
                    $demand['total_tax']
                    ?? 0
                ),
                'type' => 'd'
            ],

            'created_at' => [
                'value' => date(
                    'Y-m-d H:i:s'
                ),
                'type' => 's'
            ]
        ];

        dynamicInsert(
            $conn,
            'property_payment_years',
            $year_data
        );
    }

    /* =====================================================
       SAVE PAYMENT DETAILS
    ===================================================== */

    $detail_data = [

        'payment_order_id' => [
            'value' => $payment_order_id,
            'type'  => 'i'
        ],

        'assessment_id' => [
            'value' => $assessment_id,
            'type'  => 'i'
        ],

        'property_no' => [
            'value' => $property_no,
            'type'  => 's'
        ],

        'payer_name' => [
            'value' => $payer_name,
            'type'  => 's'
        ],

        'payer_mobile' => [
            'value' => $payer_mobile,
            'type'  => 's'
        ],

        'payer_email' => [
            'value' => $owner_email,
            'type'  => 's'
        ],

        'payment_mode' => [
            'value' => $payment_mode,
            'type'  => 's'
        ],

        'payment_made_at' => [
            'value' => $payment_made_at,
            'type'  => 's'
        ],

        'amount' => [
            'value' => $total_amount,
            'type'  => 'd'
        ],

        /*
         * Existing table columns shown in phpMyAdmin.
         * Keep them populated where applicable.
         */
        'cash_reference' => [
            'value' => '',
            'type'  => 's'
        ],

        'cash_date' => [
            'value' => null,
            'type'  => 's'
        ],

        'transaction_id' => [
            'value' => '',
            'type'  => 's'
        ],

        'online_payment_date' => [
            'value' => null,
            'type'  => 's'
        ],

        'cash_reference' => [
            'value' => (
                $is_direct_received_payment && $normalized_payment_mode === 'cash'
                ? ('CASH-' . $order_number)
                : ''
            ),
            'type' => 's'
        ],

        'cash_date' => [
            'value' => (
                $is_direct_received_payment && $normalized_payment_mode === 'cash'
                ? date('Y-m-d')
                : null
            ),
            'type' => 's'
        ],

        'cheque_date' => [
            'value' => null,
            'type'  => 's'
        ],

        'dd_date' => [
            'value' => null,
            'type'  => 's'
        ],

        'payment_from' => [
            'value' => $payment_made_at,
            'type'  => 's'
        ],

        'created_at' => [
            'value' => date(
                'Y-m-d H:i:s'
            ),
            'type' => 's'
        ],

        'updated_at' => [
            'value' => date(
                'Y-m-d H:i:s'
            ),
            'type' => 's'
        ]
    ];

    dynamicInsert(
        $conn,
        'property_payment_details',
        $detail_data
    );

    /* =====================================================
       COMMIT
    ===================================================== */

    $conn->commit();

    $_SESSION['last_payment_order_id'] =
        $payment_order_id;

    $_SESSION['last_payment_order_number'] =
        $order_number;

    /*
     * Redirect to the order/details page.
     *
     * Cash/Cheque/DD:
     *     status = paid immediately because the payment is received.
     *
     * Online:
     *     status = pending until the real payment gateway callback
     *     verifies the transaction.
     */
    header(
        'Location: payment-success.php?id=' .
            $payment_order_id
    );

    exit;
} catch (Throwable $e) {

    @$conn->rollback();

    redirectError(
        'Payment Database Error: ' .
            $e->getMessage(),
        $assessment_id,
        $selected_years
    );
}
