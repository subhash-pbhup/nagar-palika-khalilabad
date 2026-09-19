<?php
/*
 * pay-online.php
 * Khalilabad Nagar Palika Parishad
 *
 * DESIGN: Same navy + orange Payment Gateway design.
 *
 * DATA:
 * - Selected years come from view-saf-calculations.php
 * - Demand is read from property_arv_details
 * - Successful paid years are excluded
 * - Amount is calculated on the server
 * - Browser supplied amount is NOT trusted
 *
 * process-payment.php will create the actual payment order.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "include/header.php";
include "db.php";

/* =========================================================
   HELPERS
========================================================= */

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function money($value): string
{
    return number_format((float)($value ?? 0), 2);
}

function table_exists($conn, string $table): bool
{
    $safe = $conn->real_escape_string($table);
    $r = $conn->query("SHOW TABLES LIKE '{$safe}'");
    return $r && $r->num_rows > 0;
}

function table_columns($conn, string $table): array
{
    $columns = [];

    if (!table_exists($conn, $table)) {
        return $columns;
    }

    $safe = $conn->real_escape_string($table);
    $r = $conn->query("SHOW COLUMNS FROM `{$safe}`");

    if ($r) {
        while ($row = $r->fetch_assoc()) {
            if (isset($row['Field'])) {
                $columns[] = $row['Field'];
            }
        }
    }

    return $columns;
}

function has_column(array $columns, string $column): bool
{
    return in_array($column, $columns, true);
}

function first_value(array $row, array $keys, $default = '')
{
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

function normalize_financial_year($value): string
{
    $value = trim((string)$value);

    if ($value === '') {
        return '';
    }

    $value = str_replace(['/', '_'], '-', $value);
    $value = preg_replace('/\s+/', '', $value);

    if (preg_match('/^(20\d{2})-(20\d{2})$/', $value, $m)) {
        return $m[1] . '-' . $m[2];
    }

    if (preg_match('/^(20\d{2})-(\d{2})$/', $value, $m)) {
        return $m[1] . '-' . ((int)$m[1] + 1);
    }

    if (preg_match('/^(20\d{2})$/', $value, $m)) {
        return $m[1] . '-' . ((int)$m[1] + 1);
    }

    return $value;
}

function assessment_year_start($value): int
{
    $value = normalize_financial_year($value);

    if (preg_match('/^(20\d{2})-/', $value, $m)) {
        return (int)$m[1];
    }

    return 0;
}

function make_financial_year(int $start): string
{
    return $start . '-' . ($start + 1);
}

function resolve_demand_year(
    array $row,
    int $index,
    int $total,
    string $assessment_year
): string {

    $stored = normalize_financial_year(
        $row['financial_year'] ?? ''
    );

    if ($stored !== '') {
        return $stored;
    }

    $assessment_start = assessment_year_start($assessment_year);

    if ($assessment_start > 0 && $total > 0) {

        $start = $assessment_start - ($total - 1 - $index);

        if ($start >= 1900 && $start <= 2100) {
            return make_financial_year($start);
        }
    }

    $year = (int)date('Y');
    $start = ((int)date('n') >= 4)
        ? $year
        : $year - 1;

    return make_financial_year(
        $start - ($total - 1 - $index)
    );
}

function demand_tax(array $row): float
{
    if (
        isset($row['total_tax']) &&
        $row['total_tax'] !== null &&
        $row['total_tax'] !== ''
    ) {
        return (float)$row['total_tax'];
    }

    $tax =
        (float)($row['tax'] ?? 0) +
        (float)($row['property_tax'] ?? 0) +
        (float)($row['house_tax'] ?? 0) +
        (float)($row['water_tax'] ?? 0) +
        (float)($row['water_fee'] ?? 0) +
        (float)($row['sewer_tax'] ?? 0) +
        (float)($row['other_tax'] ?? 0);

    return $tax;
}

function demand_rebate(array $row): float
{
    return (float)(
        $row['rebate'] ??
        $row['rebate_amount'] ??
        0
    );
}

function demand_penalty(array $row): float
{
    return (float)(
        $row['penalty'] ??
        $row['interest'] ??
        $row['interest_amount'] ??
        0
    );
}

function number_to_words_indian($amount): string
{
    $amount = round((float)$amount, 2);

    if ($amount < 0) {
        return 'Minus ' . number_to_words_indian(abs($amount));
    }

    $rupees = (int)floor($amount);
    $paise  = (int)round(($amount - $rupees) * 100);

    if ($paise === 100) {
        $rupees++;
        $paise = 0;
    }

    $ones = [
        0 => 'Zero',
        1 => 'One',
        2 => 'Two',
        3 => 'Three',
        4 => 'Four',
        5 => 'Five',
        6 => 'Six',
        7 => 'Seven',
        8 => 'Eight',
        9 => 'Nine',
        10 => 'Ten',
        11 => 'Eleven',
        12 => 'Twelve',
        13 => 'Thirteen',
        14 => 'Fourteen',
        15 => 'Fifteen',
        16 => 'Sixteen',
        17 => 'Seventeen',
        18 => 'Eighteen',
        19 => 'Nineteen'
    ];

    $tens = [
        2 => 'Twenty',
        3 => 'Thirty',
        4 => 'Forty',
        5 => 'Fifty',
        6 => 'Sixty',
        7 => 'Seventy',
        8 => 'Eighty',
        9 => 'Ninety'
    ];

    $twoDigits = function ($n) use ($ones, $tens) {
        $n = (int)$n;

        if ($n < 20) {
            return $ones[$n];
        }

        $t = intdiv($n, 10);
        $o = $n % 10;

        return $tens[$t] . ($o ? ' ' . $ones[$o] : '');
    };

    $underThousand = function ($n) use ($ones, $twoDigits) {
        $n = (int)$n;
        $parts = [];

        if ($n >= 100) {
            $parts[] =
                $ones[intdiv($n, 100)] . ' Hundred';

            $n %= 100;
        }

        if ($n > 0) {
            $parts[] = $twoDigits($n);
        }

        return implode(' ', $parts);
    };

    $parts = [];

    if ($rupees >= 10000000) {
        $parts[] =
            $underThousand(intdiv($rupees, 10000000))
            . ' Crore';

        $rupees %= 10000000;
    }

    if ($rupees >= 100000) {
        $parts[] =
            $twoDigits(intdiv($rupees, 100000))
            . ' Lakh';

        $rupees %= 100000;
    }

    if ($rupees >= 1000) {
        $parts[] =
            $underThousand(intdiv($rupees, 1000))
            . ' Thousand';

        $rupees %= 1000;
    }

    if ($rupees > 0) {
        $parts[] = $underThousand($rupees);
    }

    $result =
        ($parts ? implode(' ', $parts) : 'Zero')
        . ' Rupees';

    if ($paise > 0) {
        $result .=
            ' and ' .
            $twoDigits($paise) .
            ' Paise';
    }

    return $result . ' Only';
}

/* =========================================================
   INPUT
========================================================= */

$property_id = (int)(
    $_POST['assessment_id']
    ?? $_POST['property_id']
    ?? $_POST['id']
    ?? $_GET['assessment_id']
    ?? $_GET['property_id']
    ?? $_GET['id']
    ?? ($_SESSION['current_assessment_id'] ?? 0)
);

$years_input =
    $_GET['years']
    ?? $_POST['years']
    ?? $_GET['selected_years']
    ?? $_POST['selected_years']
    ?? [];

if (!is_array($years_input)) {
    $years_input = preg_split(
        '/\s*,\s*/',
        trim((string)$years_input)
    );
}

$selected_years = [];

foreach ($years_input as $year) {

    $year = normalize_financial_year($year);

    if ($year !== '') {
        $selected_years[$year] = true;
    }
}

$selected_years = array_keys($selected_years);

/* =========================================================
   PAYMENT ERROR / SUCCESS MESSAGE
   Show the REAL error returned by process-payment.php.
   Examples:
   ?error=Payment%20Database%20Error...
   ?success=Payment%20order%20created...
========================================================= */

$page_error = trim((string)(
    $_GET['error']
    ?? $_POST['error']
    ?? $_SESSION['payment_error']
    ?? ''
));

$page_success = trim((string)(
    $_GET['success']
    ?? $_POST['success']
    ?? $_SESSION['payment_success']
    ?? ''
));

/*
 * Do not keep the same message in session after displaying it.
 * This prevents the error from appearing again on a later refresh.
 */
if (isset($_SESSION['payment_error'])) {
    unset($_SESSION['payment_error']);
}

if (isset($_SESSION['payment_success'])) {
    unset($_SESSION['payment_success']);
}

/*
 * Decode URL encoded error text and convert escaped/newline text
 * into readable HTML.
 */
if ($page_error !== '') {
    $page_error = urldecode($page_error);
    $page_error = str_replace(
        ['\\r\\n', '\\n', '\\r'],
        "\\n",
        $page_error
    );
}

if ($page_success !== '') {
    $page_success = urldecode($page_success);
    $page_success = str_replace(
        ['\\r\\n', '\\n', '\\r'],
        "\\n",
        $page_success
    );
}

if ($property_id <= 0) {
?>
    <div class="pay-error-wrap">
        <div class="pay-error-card">
            <h2>Invalid Property</h2>
            <p>Property / assessment ID was not received. Please open the payment page from the Demand tab again.</p>
            <a href="javascript:history.back()" class="back-btn">
                <i class="fa fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
<?php
    include "include/footer.php";
    exit;
}

/* =========================================================
   ASSESSMENT
========================================================= */

$stmt = $conn->prepare("
    SELECT *
    FROM assessments
    WHERE id = ?
      AND (is_deleted = 0 OR is_deleted IS NULL)
    LIMIT 1
");

if (!$stmt) {
    die('Assessment query failed: ' . e($conn->error));
}

$stmt->bind_param('i', $property_id);
$stmt->execute();

$assessment = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$assessment) {
?>
    <div class="pay-error-wrap">
        <div class="pay-error-card">
            <h2>Property Not Found</h2>
            <p>No assessment record was found for this property.</p>
            <a href="javascript:history.back()" class="back-btn">
                <i class="fa fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
<?php
    include "include/footer.php";
    exit;
}

/* =========================================================
   PROPERTY DISPLAY DETAILS
========================================================= */

$property_no = (string)first_value(
    $assessment,
    [
        'new_holding',
        'new_holding_number',
        'holding_no',
        'property_no'
    ],
    ''
);

if ($property_no === '') {
    $property_no = (string)$property_id;
}

$property_pid = (string)first_value(
    $assessment,
    [
        'property_id',
        'pid',
        'old_pid'
    ],
    'N/A'
);

/* =========================================================
   OWNER
========================================================= */

$owner_name = (string)first_value(
    $assessment,
    ['owner_name', 'name'],
    ''
);

$mobile = (string)first_value(
    $assessment,
    [
        'mobile',
        'mobile_number',
        'mobile_no',
        'phone'
    ],
    ''
);

$owner_email = (string)first_value(
    $assessment,
    [
        'email',
        'email_id'
    ],
    ''
);

if (table_exists($conn, 'assessment_owners')) {

    $owner_stmt = $conn->prepare("
        SELECT *
        FROM assessment_owners
        WHERE assessment_id = ?
          AND (is_deleted = 0 OR is_deleted IS NULL)
        ORDER BY id ASC
        LIMIT 1
    ");

    if ($owner_stmt) {

        $owner_stmt->bind_param(
            'i',
            $property_id
        );

        $owner_stmt->execute();

        $owner = $owner_stmt
            ->get_result()
            ->fetch_assoc();

        $owner_stmt->close();

        if ($owner) {

            $owner_name = (string)first_value(
                $owner,
                [
                    'owner_name',
                    'name',
                    'organisation_name',
                    'organisation_company_name'
                ],
                $owner_name
            );

            $mobile = (string)first_value(
                $owner,
                [
                    'mobile',
                    'mobile_no',
                    'mobile_number',
                    'phone'
                ],
                $mobile
            );

            $owner_email = (string)first_value(
                $owner,
                [
                    'email',
                    'email_id'
                ],
                $owner_email
            );
        }
    }
}

if ($owner_name === '') {
    $owner_name = 'Property Owner';
}

/* =========================================================
   DEMAND RECORDS
========================================================= */

$arv_rows = [];

if (table_exists($conn, 'property_arv_details')) {

    $stmt = $conn->prepare("
        SELECT *
        FROM property_arv_details
        WHERE assessment_id = ?
        ORDER BY id ASC
    ");

    if (!$stmt) {
        die('Demand query failed: ' . e($conn->error));
    }

    $stmt->bind_param(
        'i',
        $property_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $arv_rows[] = $row;
    }

    $stmt->close();
}

$total_arv_rows = count($arv_rows);

foreach ($arv_rows as $index => &$row) {

    $row['_financial_year'] =
        resolve_demand_year(
            $row,
            $index,
            $total_arv_rows,
            (string)(
                $assessment['year_of_assessment']
                ?? ''
            )
        );

    $row['_tax'] = demand_tax($row);
    $row['_rebate'] = demand_rebate($row);
    $row['_penalty'] = demand_penalty($row);

    /*
     * Existing saved total_tax is respected.
     * Rebate/penalty are displayed separately because the
     * existing project stores these values in the demand record.
     */
}
unset($row);

/* =========================================================
   PAID YEARS
========================================================= */

$paid_years = [];
$form_fee_already_paid = 0.00;

$po_columns = table_columns(
    $conn,
    'property_payment_orders'
);

$py_columns = table_columns(
    $conn,
    'property_payment_years'
);

$payment_status_column = '';

if (has_column($po_columns, 'status')) {
    $payment_status_column = 'status';
} elseif (has_column($po_columns, 'payment_status')) {
    $payment_status_column = 'payment_status';
}

$payment_tables_ready =
    !empty($po_columns) &&
    !empty($py_columns) &&
    has_column($po_columns, 'assessment_id') &&
    $payment_status_column !== '' &&
    has_column($py_columns, 'payment_order_id') &&
    has_column($py_columns, 'financial_year');

if ($payment_tables_ready) {

    $po_select = [
        'po.id AS payment_order_id'
    ];

    if (has_column($po_columns, 'form_fee')) {
        $po_select[] = 'po.form_fee AS form_fee';
    } else {
        $po_select[] = '0 AS form_fee';
    }

    $success_sql = "
        SELECT
            " . implode(',', $po_select) . ",
            py.financial_year
        FROM property_payment_orders po
        INNER JOIN property_payment_years py
            ON py.payment_order_id = po.id
        WHERE po.assessment_id = ?
          AND LOWER(TRIM(po.`" . $payment_status_column . "`))
              IN (
                  'paid',
                  'success',
                  'successful',
                  'completed',
                  'complete'
              )
        ORDER BY po.id DESC, py.id ASC
    ";

    $stmt = $conn->prepare($success_sql);

    if ($stmt) {

        $stmt->bind_param(
            'i',
            $property_id
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $seen_orders = [];

        while ($row = $result->fetch_assoc()) {

            $fy = normalize_financial_year(
                $row['financial_year'] ?? ''
            );

            if ($fy !== '') {
                $paid_years[$fy] = true;
            }

            $order_id = (int)(
                $row['payment_order_id'] ?? 0
            );

            if (
                $order_id > 0 &&
                !isset($seen_orders[$order_id])
            ) {
                $seen_orders[$order_id] = true;

                $form_fee_already_paid +=
                    (float)($row['form_fee'] ?? 0);
            }
        }

        $stmt->close();
    }
}

/* =========================================================
   SELECTED DEMANDS
========================================================= */

$year_rows = [];

foreach ($arv_rows as $row) {

    $fy = normalize_financial_year(
        $row['_financial_year'] ?? ''
    );

    if ($fy === '') {
        continue;
    }

    /*
     * If no years were passed, show none instead of charging
     * the user for an arbitrary year.
     */
    if (
        !empty($selected_years) &&
        in_array($fy, $selected_years, true) &&
        !isset($paid_years[$fy])
    ) {
        $year_rows[] = [
            'id' => (int)($row['id'] ?? 0),
            'financial_year' => $fy,
            'tax' => (float)$row['_tax'],
            'rebate' => (float)$row['_rebate'],
            'penalty' => (float)$row['_penalty'],
            'total_tax' => (float)(
                $row['total_tax']
                ?? $row['_tax']
            )
        ];
    }
}

/*
 * Sort exactly in the same order as the selected demand records.
 */
$selected_year_string = implode(
    ',',
    array_column(
        $year_rows,
        'financial_year'
    )
);

/* =========================================================
   SAFETY: PAYMENT PAGE MUST HAVE AT LEAST ONE VALID YEAR
========================================================= */
if (empty($year_rows)) {
    $error_message = 'No valid unpaid financial year was selected. Please return to Demand and select the year again.';
} else {
    $error_message = '';
}

/* =========================================================
   AMOUNTS
========================================================= */

$base_demand = 0.00;

foreach ($year_rows as $row) {
    $base_demand += (float)$row['total_tax'];
}

/*
 * One-time form fee.
 * Existing successful orders have already paid it.
 */
$form_fee = 5.00;

if ($form_fee_already_paid > 0) {
    $form_fee = 0.00;
}

/*
 * These values can be stored in assessments or the latest ARV
 * record depending on the existing project version.
 */
$latest_row =
    !empty($arv_rows)
    ? $arv_rows[count($arv_rows) - 1]
    : [];

$other_amount = (float)first_value(
    $latest_row,
    [
        'other_amount',
        'other_charge',
        'other_tax_amount'
    ],
    first_value(
        $assessment,
        ['other_amount', 'other_charge'],
        0
    )
);

$boring_charge = (float)first_value(
    $latest_row,
    [
        'boring_charge',
        'boring_fee',
        'boring_amount'
    ],
    first_value(
        $assessment,
        ['boring_charge', 'boring_fee'],
        0
    )
);

$advance_received = (float)first_value(
    $latest_row,
    [
        'advance_received',
        'advance_deposit',
        'advance_amount'
    ],
    first_value(
        $assessment,
        ['advance_received', 'advance_deposit'],
        0
    )
);

/*
 * Do not charge common charges when no financial year is selected.
 */
if (empty($year_rows)) {
    $form_fee = 0.00;
    $other_amount = 0.00;
    $boring_charge = 0.00;
    $advance_received = 0.00;
}

$grand_total = round(
    max(
        0,
        $base_demand +
            $form_fee +
            $other_amount +
            $boring_charge -
            $advance_received
    ),
    2
);

$amount_words =
    number_to_words_indian($grand_total);

/* =========================================================
   CSS / UI
========================================================= */
?>

<style>
    :root {
        --klb-navy: #011341;
        --klb-navy-2: #0b234d;
        --klb-orange: #f58220;
        --klb-orange-dark: #dc6e0d;
        --klb-light: #f4f7fb;
        --klb-border: #dfe6ef;
        --klb-text: #26364f;
        --klb-muted: #6c7a90;
        --klb-green: #159447;
    }

    .pay-page {
        min-height: calc(100vh - 80px);
        background: linear-gradient(135deg, #f4f7fb 0%, #eef3f8 100%);
        padding: 28px;
        color: var(--klb-text);
    }

    .pay-container {
        max-width: 1250px;
        margin: 0 auto;
    }

    /* HEADER */
    .pay-hero {
        background: #fff;
        border: 1px solid var(--klb-border);
        border-radius: 18px;
        padding: 22px 26px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        box-shadow: 0 8px 30px rgba(1, 19, 65, .07);
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
    }

    .pay-hero:after {
        content: "";
        position: absolute;
        right: -90px;
        top: -100px;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        background: rgba(245, 130, 32, .08);
    }

    .brand-area {
        display: flex;
        align-items: center;
        gap: 16px;
        position: relative;
        z-index: 1;
    }

    .brand-logo {
        width: 62px;
        height: 62px;
        border-radius: 14px;
        object-fit: contain;
        background: #fff;
        border: 1px solid #edf0f4;
        padding: 5px;
    }

    .hero-title h1 {
        margin: 0;
        color: var(--klb-navy);
        font-size: 25px;
        font-weight: 800;
    }

    .hero-title p {
        margin: 4px 0 0;
        color: var(--klb-muted);
        font-size: 13px;
    }

    .secure-badge {
        margin-top: 7px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--klb-green);
        font-size: 11px;
        font-weight: 700;
    }

    .back-btn {
        position: relative;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        border: 1px solid var(--klb-orange);
        color: var(--klb-orange-dark);
        background: #fff;
        border-radius: 24px;
        padding: 10px 18px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        transition: .2s;
    }

    .back-btn:hover {
        background: var(--klb-orange);
        color: #fff;
    }

    /* GRID */
    .pay-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.65fr) minmax(300px, .75fr);
        gap: 20px;
        align-items: start;
    }

    .pay-card {
        background: #fff;
        border: 1px solid var(--klb-border);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 7px 25px rgba(1, 19, 65, .055);
        margin-bottom: 20px;
    }

    .card-title {
        padding: 16px 20px;
        border-bottom: 1px solid #e8edf3;
        background: linear-gradient(90deg, #fff, #fafcff);
        color: var(--klb-navy);
        font-size: 15px;
        font-weight: 800;
    }

    .card-title i {
        color: var(--klb-orange);
        margin-right: 7px;
    }

    .card-body {
        padding: 20px;
    }

    /* TABLES */
    .info-table,
    .year-table {
        width: 100%;
        border-collapse: collapse;
    }

    .info-table th,
    .info-table td,
    .year-table th,
    .year-table td {
        border: 1px solid var(--klb-border);
        padding: 12px 13px;
        font-size: 13px;
    }

    .info-table th {
        width: 35%;
        background: #f7f9fc;
        color: var(--klb-navy);
        font-weight: 800;
    }

    .info-table td {
        background: #fff;
    }

    .year-table th {
        background: var(--klb-navy);
        color: #fff;
        font-weight: 700;
        text-align: center;
    }

    .year-table td {
        text-align: center;
    }

    .year-table td:first-child {
        font-weight: 800;
        color: var(--klb-navy);
    }

    .amount {
        color: var(--klb-orange-dark);
        font-weight: 800;
    }

    .total-strip {
        margin-top: 16px;
        border: 1px solid #dbe6f3;
        background: linear-gradient(90deg, #f3f7ff, #fff8f1);
        border-radius: 12px;
        padding: 15px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }

    .total-strip span {
        font-weight: 700;
        color: var(--klb-navy);
    }

    .total-strip strong {
        color: var(--klb-orange-dark);
        font-size: 23px;
    }

    /* FIELDS */
    .fields {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 17px;
    }

    .field.full {
        grid-column: 1/-1;
    }

    .field label {
        display: block;
        margin-bottom: 7px;
        color: var(--klb-navy);
        font-size: 12px;
        font-weight: 800;
    }

    .field input,
    .field select {
        width: 100%;
        height: 44px;
        box-sizing: border-box;
        border: 1px solid #ccd6e3;
        border-radius: 8px;
        padding: 0 12px;
        background: #fff;
        color: #26364f;
        outline: none;
        font-size: 13px;
        transition: .2s;
    }

    .field input:focus,
    .field select:focus {
        border-color: var(--klb-orange);
        box-shadow: 0 0 0 3px rgba(245, 130, 32, .10);
    }

    .mode-select-wrap {
        max-width: 520px;
    }

    /* DYNAMIC PAYMENT MODE */
    .dynamic {
        display: none;
        margin-top: 20px;
        padding: 18px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-left: 4px solid var(--klb-orange);
        border-radius: 10px;
    }

    .dynamic.active {
        display: block;
    }

    .dynamic-title {
        color: var(--klb-navy);
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 15px;
        text-transform: uppercase;
    }

    /* SUMMARY */
    .summary-card {
        position: sticky;
        top: 18px;
    }

    .summary-head {
        background: var(--klb-navy);
        color: #fff;
        padding: 18px 20px;
        font-size: 15px;
        font-weight: 700;
    }

    .summary-head i {
        color: var(--klb-orange);
        margin-right: 7px;
    }

    .summary-body {
        padding: 20px;
    }

    .summary-label {
        color: var(--klb-muted);
        font-size: 12px;
        font-weight: 700;
    }

    .big-total {
        color: var(--klb-orange-dark);
        font-size: 34px;
        font-weight: 900;
        margin: 5px 0 20px;
    }

    .summary-line {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 0;
        border-bottom: 1px dashed #dce3eb;
        font-size: 13px;
    }

    .summary-line strong {
        color: var(--klb-navy);
        text-align: right;
    }

    .year-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        margin-top: 10px;
    }

    .year-chip {
        background: #fff4e9;
        color: var(--klb-orange-dark);
        border: 1px solid #ffd6ad;
        border-radius: 20px;
        padding: 6px 10px;
        font-size: 11px;
        font-weight: 800;
    }

    .summary-breakdown {
        margin-top: 17px;
        border-top: 1px solid #e5eaf0;
        padding-top: 10px;
    }

    .summary-breakdown .summary-line:last-child {
        border-bottom: 0;
    }

    .summary-grand {
        margin-top: 13px;
        background: #fff5eb;
        border: 1px solid #ffd8b1;
        border-radius: 10px;
        padding: 13px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: var(--klb-navy);
        font-weight: 800;
    }

    .summary-grand strong {
        color: var(--klb-orange-dark);
        font-size: 20px;
    }

    .pay-btn {
        width: 100%;
        min-height: 50px;
        margin-top: 18px;
        border: 0;
        border-radius: 9px;
        background: linear-gradient(135deg, var(--klb-orange), var(--klb-orange-dark));
        color: #fff;
        font-size: 14px;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 7px 18px rgba(245, 130, 32, .25);
        transition: .2s;
    }

    .pay-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(245, 130, 32, .30);
    }

    .pay-btn:disabled {
        background: #b9c1cc;
        box-shadow: none;
        cursor: not-allowed;
    }

    .pay-btn.processing {
        opacity: .85;
        cursor: wait;
    }

    .secure-note {
        text-align: center;
        margin-top: 12px;
        color: var(--klb-muted);
        font-size: 11px;
    }

    /* DECLARATION */
    .declaration {
        background: #fbfcfe;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 16px;
    }

    .check {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        color: #64748b;
        font-size: 12px;
        line-height: 1.55;
        margin: 8px 0;
    }

    .check input {
        margin-top: 3px;
        accent-color: var(--klb-orange);
    }

    /* PAYMENT ERROR / SUCCESS */
    .payment-message {
        margin-bottom: 18px;
        border-radius: 12px;
        padding: 16px 18px;
        box-shadow: 0 5px 18px rgba(1, 19, 65, .06);
    }

    .payment-message.error {
        border: 1px solid #f5a4a4;
        background: #fff5f5;
        color: #7f1d1d;
    }

    .payment-message.success {
        border: 1px solid #9bd7b0;
        background: #f0fdf4;
        color: #166534;
    }

    .payment-message-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
        font-weight: 900;
        margin-bottom: 7px;
    }

    .payment-message-body {
        background: #fff;
        border: 1px dashed currentColor;
        border-radius: 8px;
        padding: 11px 12px;
        font-family: Consolas, Monaco, monospace;
        font-size: 12px;
        line-height: 1.65;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .payment-message-actions {
        display: flex;
        gap: 9px;
        flex-wrap: wrap;
        margin-top: 12px;
    }

    .payment-message-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 7px;
        text-decoration: none;
        font-size: 11px;
        font-weight: 800;
        border: 1px solid #d1d5db;
        background: #fff;
        color: #334155;
    }

    .payment-message-btn:hover {
        background: #f8fafc;
    }

    .payment-message-btn.retry {
        border-color: #f58220;
        color: #dc6e0d;
    }

    /* ERROR */
    .pay-error-wrap {
        min-height: 60vh;
        background: var(--klb-light);
        padding: 40px 20px;
    }

    .pay-error-card {
        max-width: 650px;
        margin: 40px auto;
        padding: 25px;
        background: #fff;
        border: 1px solid #fecaca;
        border-radius: 14px;
        box-shadow: 0 8px 25px rgba(1, 19, 65, .07);
    }

    .pay-error-card h2 {
        color: #991b1b;
        margin-top: 0;
    }

    .pay-error-card p {
        color: #64748b;
    }

    /* MOBILE */
    @media(max-width:1000px) {
        .pay-grid {
            grid-template-columns: 1fr;
        }

        .summary-card {
            position: static;
        }
    }

    @media(max-width:650px) {
        .pay-page {
            padding: 12px;
        }

        .pay-hero {
            padding: 16px;
            align-items: flex-start;
        }

        .brand-logo {
            width: 50px;
            height: 50px;
        }

        .hero-title h1 {
            font-size: 19px;
        }

        .back-btn {
            padding: 8px 12px;
        }

        .fields {
            grid-template-columns: 1fr;
        }

        .field.full {
            grid-column: auto;
        }

        .card-body {
            padding: 13px;
        }

        .scroll {
            overflow-x: auto;
        }

        .year-table {
            min-width: 650px;
        }

        .total-strip {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div class="pay-page">

    <!--
        process-payment.php can return:
        pay-online.php?...&error=Payment%20Database%20Error%3A...
        or:
        pay-online.php?...&success=...
        The message is displayed above the payment form.
    -->

    <div class="pay-container">

        <?php if ($page_error !== '' || !empty($error_message)): ?>

            <div class="payment-message error">

                <div class="payment-message-title">
                    <i class="fa fa-exclamation-triangle"></i>
                    Payment Error
                </div>

                <div class="payment-message-body"><?= e(
                                                        $page_error !== ''
                                                            ? $page_error
                                                            : $error_message
                                                    ) ?></div>

                <div class="payment-message-actions">

                    <a
                        href="javascript:history.back()"
                        class="payment-message-btn">
                        <i class="fa fa-arrow-left"></i>
                        Go Back
                    </a>

                    <a
                        href="javascript:location.reload()"
                        class="payment-message-btn retry">
                        <i class="fa fa-refresh"></i>
                        Retry
                    </a>

                </div>

            </div>

        <?php endif; ?>

        <?php if ($page_success !== ''): ?>

            <div class="payment-message success">

                <div class="payment-message-title">
                    <i class="fa fa-check-circle"></i>
                    Payment Message
                </div>

                <div class="payment-message-body"><?= e($page_success) ?></div>

            </div>

        <?php endif; ?>

        <!-- HEADER -->
        <div class="pay-hero">

            <div class="brand-area">

                <img
                    src="logo-main.webp"
                    alt="Khalilabad Nagar Palika Parishad"
                    class="brand-logo"
                    onerror="this.style.display='none';">

                <div class="hero-title">

                    <h1>Payment Gateway</h1>

                    <p>
                        Property Tax Payment Portal
                    </p>

                    <div class="secure-badge">
                        <i class="fa fa-shield"></i>
                        Secure &amp; Verified Payment
                    </div>

                </div>

            </div>

            <a
                href="javascript:history.back()"
                class="back-btn">
                <i class="fa fa-arrow-left"></i>
                Back
            </a>

        </div>

        <form
            id="paymentForm"
            method="post"
            action="process-payment.php?id=<?= e($property_id) ?>&assessment_id=<?= e($property_id) ?>"
            enctype="multipart/form-data">

            <!-- Server-side verification will happen again in process-payment.php -->
            <input
                type="hidden"
                name="assessment_id"
                value="<?= e($property_id) ?>">

            <?php foreach ($year_rows as $row): ?>

                <input
                    type="hidden"
                    name="years[]"
                    value="<?= e($row['financial_year']) ?>">

            <?php endforeach; ?>

            <input
                type="hidden"
                name="create_payment_order"
                value="1">

            <div class="pay-grid">

                <!-- =================================================
                     LEFT
                ================================================== -->

                <div>

                    <!-- PROPERTY -->
                    <div class="pay-card">

                        <div class="card-title">
                            <i class="fa fa-home"></i>
                            Property Payment Details
                        </div>

                        <div class="card-body">

                            <table class="info-table">

                                <tr>
                                    <th>Property No</th>
                                    <td>
                                        <?= e(
                                            $property_no ?: 'N/A'
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Owner Name</th>
                                    <td>
                                        <?= e(
                                            $owner_name ?: 'N/A'
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Mobile Number</th>
                                    <td>
                                        <?= e(
                                            $mobile ?: 'N/A'
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Demand Amount</th>
                                    <td class="amount">
                                        ₹ <?= money($base_demand) ?>
                                    </td>
                                </tr>

                            </table>

                        </div>

                    </div>

                    <!-- SELECTED YEARS -->
                    <div class="pay-card">

                        <div class="card-title">
                            <i class="fa fa-list-alt"></i>
                            Selected Financial Years
                        </div>

                        <div class="card-body">

                            <?php if (!empty($year_rows)): ?>

                                <div class="scroll">

                                    <table class="year-table">

                                        <thead>

                                            <tr>
                                                <th>Sr. No</th>
                                                <th>Financial Year</th>
                                                <th>Tax</th>
                                                <th>Rebate</th>
                                                <th>Penalty</th>
                                                <th>Total Tax</th>
                                            </tr>

                                        </thead>

                                        <tbody>

                                            <?php foreach ($year_rows as $i => $row): ?>

                                                <tr>

                                                    <td>
                                                        <?= $i + 1 ?>
                                                    </td>

                                                    <td>
                                                        <?= e(
                                                            $row['financial_year']
                                                        ) ?>
                                                    </td>

                                                    <td>
                                                        ₹ <?= money(
                                                                $row['tax']
                                                            ) ?>
                                                    </td>

                                                    <td>
                                                        ₹ <?= money(
                                                                $row['rebate']
                                                            ) ?>
                                                    </td>

                                                    <td>
                                                        ₹ <?= money(
                                                                $row['penalty']
                                                            ) ?>
                                                    </td>

                                                    <td class="amount">
                                                        ₹ <?= money(
                                                                $row['total_tax']
                                                            ) ?>
                                                    </td>

                                                </tr>

                                            <?php endforeach; ?>

                                        </tbody>

                                    </table>

                                </div>

                            <?php else: ?>

                                <div class="empty-state">
                                    <i class="fa fa-info-circle"></i>
                                    No financial year selected.
                                </div>

                            <?php endif; ?>

                            <div class="total-strip">

                                <span>
                                    Total Demand Amount
                                </span>

                                <strong>
                                    ₹ <?= money($base_demand) ?>
                                </strong>

                            </div>

                        </div>

                    </div>

                    <!-- PAYER -->
                    <div class="pay-card">

                        <div class="card-title">
                            <i class="fa fa-user"></i>
                            Payer Details
                        </div>

                        <div class="card-body">

                            <div class="fields">

                                <div class="field full">

                                    <label>
                                        Name of the Owner / Tax Payer *
                                    </label>

                                    <input
                                        type="text"
                                        name="payer_name"
                                        value="<?= e($owner_name) ?>"
                                        placeholder="Enter Name of the Owner / Tax Payer"
                                        required>

                                </div>

                                <div class="field">

                                    <label>
                                        Mobile Number
                                    </label>

                                    <input
                                        type="text"
                                        name="payer_mobile"
                                        value="<?= e($mobile) ?>"
                                        maxlength="10"
                                        inputmode="numeric"
                                        placeholder="Enter Mobile Number">

                                </div>

                                <div class="field">

                                    <label>
                                        Payment Made At *
                                    </label>

                                    <select
                                        name="payment_made_at"
                                        required>

                                        <option value="">
                                            -- Select Payment Made At --
                                        </option>

                                        <option value="JSK">
                                            JSK
                                        </option>

                                        <option value="Nagar Palika">
                                            Nagar Palika
                                        </option>

                                        <option value="Online">
                                            Online
                                        </option>

                                    </select>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- PAYMENT DETAILS -->
                    <div class="pay-card">

                        <div class="card-title">

                            <i class="fa fa-credit-card"></i>
                            Payment Details

                        </div>

                        <div class="card-body">

                            <div class="mode-select-wrap">

                                <div class="field">

                                    <label>
                                        Mode Of Payment *
                                    </label>

                                    <select
                                        name="payment_mode"
                                        id="paymentMode"
                                        required>

                                        <option value="">
                                            -- Select Mode Of Payment --
                                        </option>

                                        <option value="Cash">
                                            Cash
                                        </option>

                                        <option value="Cheque">
                                            Cheque
                                        </option>

                                        <option value="DD">
                                            DD
                                        </option>

                                        <option value="Online">
                                            Cards / Net Banking / Online
                                        </option>

                                    </select>

                                </div>

                            </div>

                            <!-- CASH -->
                            <div id="cashBox" class="dynamic">

                                <div class="dynamic-title">
                                    Cash Payment
                                </div>

                                <div class="fields">

                                    <div class="field">

                                        <label>
                                            Receipt / Reference No.
                                        </label>

                                        <input
                                            type="text"
                                            name="cash_reference"
                                            placeholder="Enter receipt/reference number">

                                    </div>

                                    <div class="field">

                                        <label>
                                            Payment Date
                                        </label>

                                        <input
                                            type="date"
                                            name="cash_date">

                                    </div>

                                </div>

                            </div>

                            <!-- CHEQUE -->
                            <div id="chequeBox" class="dynamic">

                                <div class="dynamic-title">
                                    For Cheque Details
                                </div>

                                <div class="fields">

                                    <div class="field">

                                        <label>Cheque Number *</label>

                                        <input
                                            type="text"
                                            name="cheque_number">

                                    </div>

                                    <div class="field">

                                        <label>Cheque Date *</label>

                                        <input
                                            type="date"
                                            name="cheque_date">

                                    </div>

                                    <div class="field">

                                        <label>Bank *</label>

                                        <input
                                            type="text"
                                            name="cheque_bank">

                                    </div>

                                    <div class="field">

                                        <label>Branch *</label>

                                        <input
                                            type="text"
                                            name="cheque_branch">

                                    </div>

                                </div>

                            </div>

                            <!-- DD -->
                            <div id="ddBox" class="dynamic">

                                <div class="dynamic-title">
                                    For DD Details
                                </div>

                                <div class="fields">

                                    <div class="field">

                                        <label>DD Number *</label>

                                        <input
                                            type="text"
                                            name="dd_number">

                                    </div>

                                    <div class="field">

                                        <label>DD Date *</label>

                                        <input
                                            type="date"
                                            name="dd_date">

                                    </div>

                                    <div class="field">

                                        <label>Bank *</label>

                                        <input
                                            type="text"
                                            name="dd_bank">

                                    </div>

                                    <div class="field">

                                        <label>Branch *</label>

                                        <input
                                            type="text"
                                            name="dd_branch">

                                    </div>

                                </div>

                            </div>

                            <!-- ONLINE -->
                            <div id="onlineBox" class="dynamic">

                                <div class="dynamic-title">
                                    Card / Net Banking / Online Payment
                                </div>

                                <div class="fields">

                                    <div class="field">

                                        <label>
                                            Transaction ID
                                        </label>

                                        <input
                                            type="text"
                                            name="transaction_id"
                                            placeholder="Enter Transaction ID">

                                    </div>

                                    <div class="field">

                                        <label>
                                            Payment Date
                                        </label>

                                        <input
                                            type="date"
                                            name="online_payment_date">

                                    </div>

                                    <div class="field">

                                        <label>
                                            Payment From
                                        </label>

                                        <select name="payment_from">

                                            <option value="">
                                                Select
                                            </option>

                                            <option value="UPI">
                                                UPI
                                            </option>

                                            <option value="Net Banking">
                                                Net Banking
                                            </option>

                                            <option value="Debit Card">
                                                Debit Card
                                            </option>

                                            <option value="Credit Card">
                                                Credit Card
                                            </option>

                                        </select>

                                    </div>

                                    <div class="field">

                                        <label>
                                            Name of Vendor
                                        </label>

                                        <input
                                            type="text"
                                            name="vendor_name"
                                            placeholder="Name of Vendor">

                                    </div>

                                    <div class="field full">

                                        <label>
                                            Upload Payment Proof
                                        </label>

                                        <input
                                            type="file"
                                            name="payment_proof"
                                            accept=".jpg,.jpeg,.png,.pdf">

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- DECLARATION -->
                    <div class="pay-card">

                        <div class="card-body declaration">

                            <label class="check">

                                <input
                                    type="checkbox"
                                    name="declaration_1"
                                    value="1"
                                    required>

                                <span>
                                    I/We hereby declare that the above
                                    information and property tax assessment
                                    is correct to the best of my/our
                                    knowledge and belief and I/We undertake
                                    to abide by the relevant provisions of
                                    applicable Municipal law.
                                </span>

                            </label>

                            <label class="check">

                                <input
                                    type="checkbox"
                                    name="declaration_2"
                                    value="1"
                                    required>

                                <span>
                                    I/We fully understand that any
                                    information furnished above, if proved
                                    incorrect or false, may render me/us
                                    liable for penal action or other
                                    consequences under applicable Laws,
                                    Rules or Regulations.
                                </span>

                            </label>

                        </div>

                    </div>

                </div>

                <!-- =================================================
                     RIGHT SUMMARY
                ================================================== -->

                <div>

                    <div class="pay-card summary-card">

                        <div class="summary-head">

                            <i class="fa fa-money"></i>
                            Payment Summary

                        </div>

                        <div class="summary-body">

                            <div class="summary-label">
                                Total Payable Amount
                            </div>

                            <div class="big-total">
                                ₹ <?= money($grand_total) ?>
                            </div>

                            <div class="summary-line">

                                <span>
                                    Property No
                                </span>

                                <strong>
                                    <?= e(
                                        $property_no ?: 'N/A'
                                    ) ?>
                                </strong>

                            </div>

                            <div class="summary-line">

                                <span>
                                    Owner
                                </span>

                                <strong>
                                    <?= e(
                                        $owner_name ?: 'N/A'
                                    ) ?>
                                </strong>

                            </div>

                            <div class="summary-line">

                                <span>
                                    Financial Years
                                </span>

                                <strong>
                                    <?= count($year_rows) ?>
                                </strong>

                            </div>

                            <div
                                class="summary-label"
                                style="margin-top:18px;">
                                Selected Years
                            </div>

                            <div class="year-chips">

                                <?php if (!empty($year_rows)): ?>

                                    <?php foreach ($year_rows as $row): ?>

                                        <span class="year-chip">
                                            <?= e(
                                                $row['financial_year']
                                            ) ?>
                                        </span>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <span class="year-chip">
                                        None
                                    </span>

                                <?php endif; ?>

                            </div>

                            <div class="summary-breakdown">

                                <div class="summary-line">

                                    <span>
                                        Demand Amount
                                    </span>

                                    <strong>
                                        ₹ <?= money($base_demand) ?>
                                    </strong>

                                </div>

                                <div class="summary-line">

                                    <span>
                                        Form Fee
                                    </span>

                                    <strong>
                                        ₹ <?= money($form_fee) ?>
                                    </strong>

                                </div>

                                <div class="summary-line">

                                    <span>
                                        Other Amount
                                    </span>

                                    <strong>
                                        ₹ <?= money($other_amount) ?>
                                    </strong>

                                </div>

                                <div class="summary-line">

                                    <span>
                                        Boring Charge
                                    </span>

                                    <strong>
                                        ₹ <?= money($boring_charge) ?>
                                    </strong>

                                </div>

                                <div class="summary-line">

                                    <span>
                                        Advance Received
                                    </span>

                                    <strong>
                                        ₹ <?= money($advance_received) ?>
                                    </strong>

                                </div>

                            </div>

                            <div class="summary-grand">

                                <span>
                                    Grand Total
                                </span>

                                <strong>
                                    ₹ <?= money($grand_total) ?>
                                </strong>

                            </div>

                            <button
                                type="submit"
                                class="pay-btn"
                                id="payButton"
                                <?= ($grand_total <= 0 || empty($year_rows))
                                    ? 'disabled'
                                    : '' ?>>

                                <i class="fa fa-lock"></i>

                                <span id="payText">
                                    Proceed to Payment
                                </span>

                            </button>

                            <div class="secure-note">

                                <i class="fa fa-shield"></i>

                                Your payment information is
                                handled securely.

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </form>

    </div>

</div>

<script>
    (function() {

        const paymentForm =
            document.getElementById('paymentForm');

        const paymentMode =
            document.getElementById('paymentMode');

        const payButton =
            document.getElementById('payButton');

        const payText =
            document.getElementById('payText');

        const boxes = {
            Cash: document.getElementById('cashBox'),
            Cheque: document.getElementById('chequeBox'),
            DD: document.getElementById('ddBox'),
            Online: document.getElementById('onlineBox')
        };

        function resetBox(box) {

            if (!box) {
                return;
            }

            box.classList.remove('active');

            box
                .querySelectorAll('input, select')
                .forEach(function(input) {

                    input.removeAttribute('required');

                });
        }

        function setRequired(name) {

            const element =
                document.querySelector(
                    '[name="' + name + '"]'
                );

            if (element) {
                element.setAttribute(
                    'required',
                    'required'
                );
            }
        }

        function setMode() {

            Object.keys(boxes).forEach(function(key) {
                resetBox(boxes[key]);
            });

            const value =
                paymentMode ?
                paymentMode.value :
                '';

            if (boxes[value]) {
                boxes[value].classList.add('active');
            }

            if (value === 'Cheque') {

                [
                    'cheque_number',
                    'cheque_date',
                    'cheque_bank',
                    'cheque_branch'
                ].forEach(setRequired);

            }

            if (value === 'DD') {

                [
                    'dd_number',
                    'dd_date',
                    'dd_bank',
                    'dd_branch'
                ].forEach(setRequired);

            }

            if (!payText) {
                return;
            }

            if (value === 'Online') {

                payText.textContent =
                    'Proceed to Online Payment';

            } else if (value === 'Cash') {

                payText.textContent =
                    'Submit Cash Payment';

            } else if (value === 'Cheque') {

                payText.textContent =
                    'Submit Cheque Payment';

            } else if (value === 'DD') {

                payText.textContent =
                    'Submit DD Payment';

            } else {

                payText.textContent =
                    'Proceed to Payment';
            }
        }

        if (paymentMode) {
            paymentMode.addEventListener(
                'change',
                setMode
            );

            setMode();
        }

        if (paymentForm) {

            paymentForm.addEventListener(
                'submit',
                function() {

                    if (payButton) {

                        /*
                         * Do NOT disable the button here before submit
                         * completes. The browser must continue the normal
                         * POST to process-payment.php.
                         */
                        payButton.classList.add('processing');

                        payText.textContent =
                            'Processing...';
                    }

                }
            );
        }

    })();
</script>

<?php include "include/footer.php"; ?>