<?php
/*
 * view-saf-calculations.php
 * Khalilabad Nagar Palika - Property Demand / Collections
 *
 * Payment flow:
 *   Demand -> select unpaid financial year(s)
 *          -> pay-online.php
 *          -> process-payment.php
 *          -> pending order
 *
 * IMPORTANT:
 * Amounts are recalculated server-side in pay-online.php/process-payment.php.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "include/header.php";

$property_id = (int)($_GET['id'] ?? 0);
$active_tab  = strtolower(trim($_GET['tab'] ?? 'basic'));

$allowed_tabs = [
    'basic',
    'demand',
    'collections',
    'documents',
    'legacy',
    'other-demands',
    'fixed-arv',
    'reassessment'
];

if (!in_array($active_tab, $allowed_tabs, true)) {
    $active_tab = 'basic';
}

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function money($value): string
{
    return number_format((float)($value ?? 0), 2);
}

function safe_date($value): string
{
    if (
        $value === null || $value === '' ||
        $value === '0000-00-00' ||
        $value === '0000-00-00 00:00:00'
    ) {
        return 'N/A';
    }

    $ts = strtotime((string)$value);
    return $ts ? date('d/m/Y', $ts) : (string)$value;
}

function table_exists($conn, string $table): bool
{
    $table = $conn->real_escape_string($table);
    $r = $conn->query("SHOW TABLES LIKE '{$table}'");
    return $r && $r->num_rows > 0;
}

function column_exists($conn, string $table, string $column): bool
{
    if (!table_exists($conn, $table)) {
        return false;
    }

    $table  = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);

    $r = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return $r && $r->num_rows > 0;
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
        $start = (int)$m[1];
        return $start . '-' . ($start + 1);
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

function resolve_demand_year(array $row, int $index, int $total, string $assessment_year): string
{
    $stored = normalize_financial_year($row['financial_year'] ?? '');
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
    $start = ((int)date('n') >= 4) ? $year : $year - 1;

    return make_financial_year($start - ($total - 1 - $index));
}

function first_value(array $row, array $keys, $default = '')
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
            return $row[$key];
        }
    }
    return $default;
}

function demand_number(array $row, array $keys, $default = 0): float
{
    $v = first_value($row, $keys, null);
    return ($v === null || $v === '') ? (float)$default : (float)$v;
}

function tax_total(array $row, string $prefix): float
{
    $key = $prefix . '_total';

    if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
        return (float)$row[$key];
    }

    return
        (float)($row[$prefix . '_current'] ?? 0) +
        (float)($row[$prefix . '_arrear'] ?? 0) +
        (float)($row[$prefix . '_interest'] ?? 0);
}

function tab_url(int $id, string $tab): string
{
    return '?id=' . $id . '&tab=' . rawurlencode($tab);
}

/* ---------------------------------------------------------
   PROPERTY
--------------------------------------------------------- */
if ($property_id <= 0) {
    die('Invalid property ID.');
}

$stmt = $conn->prepare("
    SELECT *
    FROM assessments
    WHERE id = ?
      AND (is_deleted = 0 OR is_deleted IS NULL)
    LIMIT 1
");

if (!$stmt) {
    die('Unable to prepare property query: ' . e($conn->error));
}

$stmt->bind_param('i', $property_id);
$stmt->execute();
$property = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$property) {
    die('Property not found.');
}

/* ---------------------------------------------------------
   ZONE / WARD / MOHALLA DISPLAY
--------------------------------------------------------- */
$zone_display_value = !empty($property['zone_id'])
    ? 'Zone ' . (int)$property['zone_id']
    : (!empty($property['zone']) ? $property['zone'] : 'N/A');

$ward_display_value = $property['ward'] ?? 'N/A';

if (!empty($property['ward_id']) && table_exists($conn, 'wards')) {
    $stmt = $conn->prepare("
        SELECT ward_no FROM wards
        WHERE ward_id = ? LIMIT 1
    ");

    if ($stmt) {
        $ward_id = (int)$property['ward_id'];
        $stmt->bind_param('i', $ward_id);
        $stmt->execute();

        $ward_row = $stmt->get_result()->fetch_assoc();

        if ($ward_row && isset($ward_row['ward_no'])) {
            $ward_display_value = $ward_row['ward_no'];
        }

        $stmt->close();
    }
}

$mohalla_display_value = $property['mohalla'] ?? 'N/A';

if (!empty($property['mohalla_id']) && table_exists($conn, 'mohalla')) {
    $stmt = $conn->prepare("
        SELECT mohalla_name FROM mohalla
        WHERE mohalla_id = ? LIMIT 1
    ");

    if ($stmt) {
        $mohalla_id = (int)$property['mohalla_id'];
        $stmt->bind_param('i', $mohalla_id);
        $stmt->execute();

        $mohalla_row = $stmt->get_result()->fetch_assoc();

        if ($mohalla_row && isset($mohalla_row['mohalla_name'])) {
            $mohalla_display_value = $mohalla_row['mohalla_name'];
        }

        $stmt->close();
    }
}

/* ---------------------------------------------------------
   PROPERTY IMAGES
--------------------------------------------------------- */
$default_image_url = 'uploads/default_property.png';

$center_image = !empty($property['center_image_gps'])
    ? $property['center_image_gps']
    : $default_image_url;

$left_image = !empty($property['left_image_gps'])
    ? $property['left_image_gps']
    : $default_image_url;

$right_image = !empty($property['right_image_gps'])
    ? $property['right_image_gps']
    : $default_image_url;

/* ---------------------------------------------------------
   OWNER
--------------------------------------------------------- */
$owners = [];

if (table_exists($conn, 'assessment_owners')) {
    $stmt = $conn->prepare("
        SELECT *
        FROM assessment_owners
        WHERE assessment_id = ?
          AND (is_deleted = 0 OR is_deleted IS NULL)
        ORDER BY id ASC
    ");

    if ($stmt) {
        $stmt->bind_param('i', $property_id);
        $stmt->execute();
        $r = $stmt->get_result();

        while ($row = $r->fetch_assoc()) {
            $owners[] = $row;
        }

        $stmt->close();
    }
}

$primary_owner = $owners[0] ?? [];

$owner_name = (string)first_value(
    $primary_owner,
    ['owner_name', 'name'],
    'Property Owner'
);

$owner_mobile = (string)first_value(
    $primary_owner,
    ['mobile', 'mobile_no', 'phone'],
    ''
);

$owner_email = (string)first_value(
    $primary_owner,
    ['email', 'email_id'],
    ''
);

/* ---------------------------------------------------------
   FLOORS
--------------------------------------------------------- */
$floors = [];

if (table_exists($conn, 'assessment_floors')) {
    $stmt = $conn->prepare("
        SELECT *
        FROM assessment_floors
        WHERE assessment_id = ?
          AND (is_deleted = 0 OR is_deleted IS NULL)
        ORDER BY id ASC
    ");

    if ($stmt) {
        $stmt->bind_param('i', $property_id);
        $stmt->execute();
        $r = $stmt->get_result();

        while ($row = $r->fetch_assoc()) {
            $floors[] = $row;
        }

        $stmt->close();
    }
}

/* ---------------------------------------------------------
   DEMAND / ARV
--------------------------------------------------------- */
$arv_rows = [];

if (!table_exists($conn, 'property_arv_details')) {
    die('property_arv_details table not found.');
}

$stmt = $conn->prepare("
    SELECT *
    FROM property_arv_details
    WHERE assessment_id = ?
    ORDER BY
        CASE
            WHEN financial_year IS NULL OR financial_year = '' THEN 1
            ELSE 0
        END,
        financial_year ASC,
        id ASC
");

if (!$stmt) {
    die('Unable to prepare demand query: ' . e($conn->error));
}

$stmt->bind_param('i', $property_id);
$stmt->execute();
$r = $stmt->get_result();

while ($row = $r->fetch_assoc()) {
    $arv_rows[] = $row;
}

$stmt->close();

$demand_total_rows = count($arv_rows);

foreach ($arv_rows as $i => &$row) {
    $row['_display_financial_year'] = resolve_demand_year(
        $row,
        $i,
        $demand_total_rows,
        (string)($property['year_of_assessment'] ?? '')
    );
}
unset($row);

/* ---------------------------------------------------------
   SUCCESSFUL PAYMENTS = SOURCE OF TRUTH
--------------------------------------------------------- */
$paid_years = [];
$successful_payment_rows = [];
$form_fee_already_paid = 0.00;

$successful_statuses = [
    'paid',
    'success',
    'successful',
    'completed',
    'complete'
];

if (
    table_exists($conn, 'property_payment_orders') &&
    table_exists($conn, 'property_payment_years') &&
    column_exists($conn, 'property_payment_orders', 'assessment_id') &&
    column_exists($conn, 'property_payment_orders', 'status') &&
    column_exists($conn, 'property_payment_years', 'payment_order_id') &&
    column_exists($conn, 'property_payment_years', 'financial_year')
) {
    $sql = "
        SELECT
            po.id AS payment_order_id,
            " . (column_exists($conn, 'property_payment_orders', 'order_number')
        ? "po.order_number,"
        : "'' AS order_number,") . "
            po.assessment_id,
            " . (column_exists($conn, 'property_payment_orders', 'tax_amount')
        ? "po.tax_amount,"
        : "0 AS tax_amount,") . "
            " . (column_exists($conn, 'property_payment_orders', 'form_fee')
        ? "po.form_fee,"
        : "0 AS form_fee,") . "
            " . (column_exists($conn, 'property_payment_orders', 'other_amount')
        ? "po.other_amount,"
        : "0 AS other_amount,") . "
            " . (column_exists($conn, 'property_payment_orders', 'boring_charge')
        ? "po.boring_charge,"
        : "0 AS boring_charge,") . "
            " . (column_exists($conn, 'property_payment_orders', 'advance_received')
        ? "po.advance_received,"
        : "0 AS advance_received,") . "
            " . (column_exists($conn, 'property_payment_orders', 'total_amount')
        ? "po.total_amount,"
        : "0 AS total_amount,") . "
            po.status,
            " . (column_exists($conn, 'property_payment_orders', 'payment_status')
        ? "po.payment_status,"
        : "'' AS payment_status,") . "
            " . (column_exists($conn, 'property_payment_orders', 'payer_name')
        ? "po.payer_name,"
        : "'' AS payer_name,") . "
            " . (column_exists($conn, 'property_payment_orders', 'payer_mobile')
        ? "po.payer_mobile,"
        : "'' AS payer_mobile,") . "
            " . (column_exists($conn, 'property_payment_orders', 'payment_made_at')
        ? "po.payment_made_at,"
        : "'' AS payment_made_at,") . "
            " . (column_exists($conn, 'property_payment_orders', 'payment_mode')
        ? "po.payment_mode,"
        : "'' AS payment_mode,") . "
            " . (column_exists($conn, 'property_payment_orders', 'payment_gateway')
        ? "po.payment_gateway,"
        : "'' AS payment_gateway,") . "
            " . (column_exists($conn, 'property_payment_orders', 'gateway_order_id')
        ? "po.gateway_order_id,"
        : "'' AS gateway_order_id,") . "
            " . (column_exists($conn, 'property_payment_orders', 'gateway_payment_id')
        ? "po.gateway_payment_id,"
        : "'' AS gateway_payment_id,") . "
            " . (column_exists($conn, 'property_payment_orders', 'created_at')
        ? "po.created_at,"
        : "NULL AS created_at,") . "
            py.id AS payment_year_id,
            py.financial_year,
            " . (column_exists($conn, 'property_payment_years', 'demand_record_id')
        ? "py.demand_record_id,"
        : "NULL AS demand_record_id,") . "
            " . (column_exists($conn, 'property_payment_years', 'tax_amount')
        ? "py.tax_amount AS year_tax_amount"
        : "0 AS year_tax_amount") . "
        FROM property_payment_orders po
        INNER JOIN property_payment_years py
            ON py.payment_order_id = po.id
        WHERE po.assessment_id = ?
          AND (
                LOWER(TRIM(po.status))
                    IN ('paid','success','successful','completed','complete')
                OR
                LOWER(TRIM(po.payment_status))
                    IN ('paid','success','successful','completed','complete')
              )
        ORDER BY po.id DESC, py.id ASC
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param('i', $property_id);
        $stmt->execute();
        $r = $stmt->get_result();

        $seen_fee_orders = [];

        while ($row = $r->fetch_assoc()) {
            $fy = normalize_financial_year($row['financial_year'] ?? '');

            if ($fy !== '') {
                $paid_years[$fy] = true;
            }

            $successful_payment_rows[] = $row;

            $order_id = (int)($row['payment_order_id'] ?? 0);

            if ($order_id > 0 && !isset($seen_fee_orders[$order_id])) {
                $seen_fee_orders[$order_id] = true;
                $form_fee_already_paid += (float)($row['form_fee'] ?? 0);
            }
        }

        $stmt->close();
    }
}

/* ---------------------------------------------------------
   UNPAID DEMAND
--------------------------------------------------------- */
$unpaid_arv_rows = [];

foreach ($arv_rows as $row) {
    $fy = normalize_financial_year($row['_display_financial_year'] ?? '');

    if ($fy !== '' && isset($paid_years[$fy])) {
        continue;
    }

    $unpaid_arv_rows[] = $row;
}

$total_demand_all_years = 0.00;
$total_unpaid_demand = 0.00;

foreach ($arv_rows as $row) {
    $total_demand_all_years += (float)($row['total_tax'] ?? 0);
}

foreach ($unpaid_arv_rows as $row) {
    $total_unpaid_demand += (float)($row['total_tax'] ?? 0);
}

/* ---------------------------------------------------------
   COMMON CHARGES
--------------------------------------------------------- */
$latest_demand = !empty($arv_rows)
    ? $arv_rows[count($arv_rows) - 1]
    : [];

$net_form_fee = (float)first_value(
    $latest_demand,
    ['form_fee'],
    5
);

$net_other_amount = (float)first_value(
    $latest_demand,
    ['other_amount', 'other_charge', 'other_tax_amount'],
    0
);

$net_boring_charge = (float)first_value(
    $latest_demand,
    ['boring_charge', 'boring_fee', 'boring_amount'],
    0
);

$net_advance_received = (float)first_value(
    $latest_demand,
    ['advance_deposit', 'advance_received', 'advance_amount'],
    0
);

if ($form_fee_already_paid > 0 || empty($unpaid_arv_rows)) {
    $net_form_fee = 0.00;
}

if (empty($unpaid_arv_rows)) {
    $net_other_amount = 0.00;
    $net_boring_charge = 0.00;
    $net_advance_received = 0.00;
}

$net_total_property_tax =
    $total_unpaid_demand +
    $net_form_fee +
    $net_other_amount +
    $net_boring_charge -
    $net_advance_received;

if ($net_total_property_tax < 0) {
    $net_total_property_tax = 0;
}

/* ---------------------------------------------------------
   COLLECTION GROUPING
--------------------------------------------------------- */
$collections_by_year = [];

foreach ($successful_payment_rows as $row) {
    $fy = normalize_financial_year($row['financial_year'] ?? '');
    if ($fy === '') {
        $fy = 'Other';
    }

    if (!isset($collections_by_year[$fy])) {
        $collections_by_year[$fy] = [];
    }

    $collections_by_year[$fy][] = $row;
}

uksort($collections_by_year, function ($a, $b) {
    if ($a === 'Other') return 1;
    if ($b === 'Other') return -1;

    preg_match('/^(20\d{2})-/', $a, $ma);
    preg_match('/^(20\d{2})-/', $b, $mb);

    return ((int)($ma[1] ?? 0)) <=> ((int)($mb[1] ?? 0));
});

$property_no = (string)first_value(
    $property,
    ['new_holding', 'new_holding_number', 'holding_no', 'property_no'],
    $property_id
);

$property_pid = (string)($property['property_id'] ?? 'N/A');
?>

<style>
    /* =========================================================
       BASIC TAB - SAME LOOK AS THE SHARED SCREENSHOT
    ========================================================= */

    .property-value-box {
        display: flex;
        align-items: center;
        min-height: 36px;
        padding: 7px 10px;
        background: #e8ecef;
        border: 1px solid #d4dbe1;
        border-radius: 4px;
        color: #5b6570;
        font-size: 13px;
        font-weight: 600;
        line-height: 1.35;
        word-break: break-word;
    }

    .basic-owner-table,
    .floor-basic-table {
        min-width: 820px;
    }

    .basic-owner-table th,
    .basic-owner-table td,
    .floor-basic-table th,
    .floor-basic-table td {
        white-space: nowrap;
        text-align: center;
    }

    .basic-owner-table th:nth-child(2),
    .basic-owner-table td:nth-child(2) {
        min-width: 220px;
    }

    .basic-owner-table th:nth-child(3),
    .basic-owner-table td:nth-child(3) {
        min-width: 230px;
    }

    .other-tax-row {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #374151;
        font-size: 13px;
        font-weight: 600;
    }

    .other-tax-row input {
        accent-color: #10b981;
    }

    .image-grid-basic {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 22px;
    }

    .property-image-box {
        min-width: 0;
    }

    .property-image-box img {
        width: 180px;
        height: 220px;
        max-width: 100%;
        object-fit: cover;
        display: block;
        border: 2px solid #86efac;
        border-radius: 3px;
        background: #f3f4f6;
    }

    .property-image-box img.default-property-image {
        border-color: #d1d5db;
        opacity: .55;
        filter: grayscale(1);
    }

    @media (max-width: 900px) {
        .image-grid-basic {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 600px) {
        .image-grid-basic {
            grid-template-columns: 1fr;
        }
    }


    body {
        background: #f4f6f9;
        font-family: Inter, Arial, sans-serif;
    }

    .minimal-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 3px 12px rgba(15, 23, 42, .05);
    }

    .property-head {
        display: flex;
        justify-content: space-between;
        gap: 15px;
        align-items: center;
        margin-bottom: 18px;
    }

    .property-head h1 {
        margin: 0;
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
    }

    .property-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding: 6px;
        margin-bottom: 18px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
    }

    .property-tab {
        padding: 10px 14px;
        border-radius: 8px;
        color: #475569;
        text-decoration: none;
        font-size: 12px;
        font-weight: 800;
    }

    .property-tab:hover,
    .property-tab.active {
        background: #0f172a;
        color: #fff;
    }

    .section-title {
        font-size: 17px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 16px;
    }

    .data-item dt {
        color: #64748b;
        font-size: 11px;
        text-transform: uppercase;
        margin-bottom: 3px;
    }

    .data-item dd {
        color: #1e293b;
        font-size: 14px;
        font-weight: 700;
        word-break: break-word;
    }

    .owner-card,
    .floor-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px;
        background: #f8fafc;
    }

    .demand-year-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
    }

    .demand-year-tab {
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #334155;
        padding: 9px 13px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 800;
        font-size: 12px;
    }

    .demand-year-tab.active {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }

    .demand-year-panel {
        display: none;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 12px;
    }

    .demand-year-panel.active {
        display: block;
    }

    .year-bar {
        padding: 12px 14px;
        background: #f8fafc;
        display: flex;
        justify-content: space-between;
        gap: 15px;
        align-items: center;
        flex-wrap: wrap;
        border-bottom: 1px solid #e2e8f0;
    }

    .year-summary {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        font-size: 11px;
        color: #64748b;
    }

    .year-summary strong {
        color: #1d4ed8;
    }

    .table-wrap {
        width: 100%;
        overflow: auto;
    }

    .detail-table {
        width: 100%;
        min-width: 650px;
        border-collapse: collapse;
    }

    .detail-table th,
    .detail-table td {
        border: 1px solid #e5e7eb;
        padding: 9px 10px;
        font-size: 12px;
    }

    .detail-table th {
        background: #f8fafc;
        text-align: left;
        color: #475569;
    }

    .demand-pay-footer,
    .net-payable-row {
        display: flex;
        justify-content: space-between;
        gap: 15px;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 16px;
        padding: 15px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }

    .demand-total-box {
        font-size: 14px;
        font-weight: 700;
    }

    .demand-total-box strong {
        color: #1d4ed8;
        font-size: 18px;
    }

    .pay-now-btn {
        border: 0;
        background: #ea580c;
        color: #fff;
        padding: 11px 18px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 800;
    }

    .pay-now-btn:hover {
        background: #c2410c;
    }

    .empty-box {
        text-align: center;
        padding: 35px 15px;
        color: #64748b;
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        background: #f8fafc;
    }

    .pay-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: rgba(15, 23, 42, .65);
        padding: 20px;
        align-items: center;
        justify-content: center;
    }

    .pay-modal-overlay.show {
        display: flex;
    }

    .pay-modal {
        width: min(560px, 100%);
        max-height: 90vh;
        overflow: auto;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .25);
    }

    .pay-modal-header {
        padding: 18px;
        display: flex;
        justify-content: space-between;
        gap: 15px;
        border-bottom: 1px solid #e5e7eb;
    }

    .pay-modal-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 800;
    }

    .pay-modal-header p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 12px;
    }

    .pay-close-btn {
        width: 34px;
        height: 34px;
        border: 0;
        border-radius: 8px;
        background: #f1f5f9;
        font-size: 24px;
        cursor: pointer;
    }

    .pay-year-list {
        padding: 14px 18px;
    }

    .pay-year-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px;
        margin-bottom: 7px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        cursor: pointer;
        font-size: 13px;
    }

    .pay-year-item:hover {
        background: #f8fafc;
    }

    .pay-select-all {
        background: #fff7ed;
        border-color: #fed7aa;
        font-weight: 800;
    }


    .pay-modal-summary {
        padding: 12px 18px;
        border-top: 1px solid #e5e7eb;
        border-bottom: 1px solid #e5e7eb;
        background: #f8fafc;
    }

    .pay-summary-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 4px 0;
        color: #64748b;
        font-size: 12px;
    }

    .pay-summary-row strong {
        color: #334155;
    }

    .pay-summary-row.payable {
        margin-top: 5px;
        padding-top: 9px;
        border-top: 1px solid #cbd5e1;
        color: #0f172a;
        font-size: 14px;
        font-weight: 800;
    }

    .pay-summary-row.payable strong {
        color: #2563eb;
        font-size: 17px;
    }

    .pay-modal-footer {
        padding: 16px 18px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
    }

    .pay-modal-btn {
        border: 0;
        background: #2563eb;
        color: #fff;
        padding: 11px 20px;
        border-radius: 8px;
        font-weight: 800;
        cursor: pointer;
    }

    .pay-modal-btn:disabled {
        opacity: .45;
        cursor: not-allowed;
    }

    /* =========================================================
       COLLECTIONS - COMPLETE RECEIPT VIEW
    ========================================================= */

    .collection-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .collection-record {
        border: 1px solid #dbe3ec;
        background: #fff;
        overflow: hidden;
    }

    .collection-record-head {
        width: 100%;
        min-height: 58px;
        border: 0;
        background: #120005;
        color: #fff;
        padding: 12px 16px;
        display: grid;
        grid-template-columns: 180px 1fr auto;
        align-items: center;
        gap: 18px;
        cursor: pointer;
        text-align: left;
    }

    .collection-record-head:hover {
        background: #220008;
    }

    .collection-record-amount {
        font-size: 17px;
        font-weight: 700;
        white-space: nowrap;
    }

    .collection-record-middle {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 28px;
        font-size: 13px;
        font-weight: 600;
        flex-wrap: wrap;
    }

    .collection-record-right {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .collection-record-chevron {
        transition: transform .2s ease;
    }

    .collection-record.open .collection-record-chevron {
        transform: rotate(180deg);
    }

    .collection-record-body {
        display: none;
        background: #fff;
        border-top: 1px solid #dbe3ec;
    }

    .collection-record.open .collection-record-body {
        display: block;
    }

    .collection-receipt-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 25px;
        padding: 22px 20px 16px;
        border-bottom: 1px solid #e5e7eb;
    }

    .collection-logo-area {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .collection-municipality-name {
        font-size: 21px;
        font-weight: 900;
        color: #111827;
    }

    .collection-receipt-caption {
        margin-top: 3px;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
    }

    .collection-receipt-number {
        min-width: 310px;
        text-align: right;
        color: #4b5563;
        font-size: 12px;
        line-height: 1.8;
    }

    .collection-receipt-number strong {
        color: #374151;
        margin-right: 5px;
    }

    .collection-property-box {
        display: grid;
        grid-template-columns: 1.15fr .85fr;
        gap: 18px;
        margin: 16px 18px;
        padding: 16px;
        background: #f1f1f1;
        border: 1px solid #e5e7eb;
    }

    .collection-property-info {
        display: grid;
        grid-template-columns: 1fr;
        gap: 5px;
        color: #5b6570;
        font-size: 12px;
        line-height: 1.45;
    }

    .collection-property-info div {
        display: grid;
        grid-template-columns: 155px 1fr;
        gap: 8px;
    }

    .collection-property-info strong {
        color: #4b5563;
    }

    .collection-floor-box {
        border: 1px solid #d1d5db;
        background: #fff;
        overflow: hidden;
    }

    .collection-mini-title {
        padding: 9px 11px;
        background: #f8fafc;
        color: #374151;
        font-size: 11px;
        font-weight: 800;
        border-bottom: 1px solid #d1d5db;
    }

    .collection-floor-table,
    .collection-tax-table,
    .collection-full-amount-table {
        width: 100%;
        border-collapse: collapse;
    }

    .collection-floor-table th,
    .collection-floor-table td,
    .collection-tax-table th,
    .collection-tax-table td,
    .collection-full-amount-table th,
    .collection-full-amount-table td {
        border: 1px solid #e5e7eb;
        padding: 8px 9px;
        font-size: 11px;
    }

    .collection-floor-table th,
    .collection-tax-table th,
    .collection-full-amount-table th {
        background: #f8fafc;
        color: #374151;
        font-weight: 800;
        text-align: center;
    }

    .collection-floor-table td,
    .collection-tax-table td {
        text-align: center;
    }

    .collection-received-line {
        margin: 0 18px 16px;
        color: #5b6570;
        font-size: 12px;
        line-height: 1.6;
    }

    .collection-received-line strong {
        color: #374151;
    }

    .collection-section-heading {
        margin: 15px 18px 8px;
        padding: 9px 11px;
        background: #f8fafc;
        border-left: 3px solid #2563eb;
        color: #475569;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .collection-tax-table {
        min-width: 650px;
    }

    .collection-full-amount-table {
        max-width: 720px;
        margin: 0 18px;
    }

    .collection-full-amount-table th {
        width: 65%;
        text-align: left;
    }

    .collection-full-amount-table td {
        text-align: right;
        font-weight: 700;
    }

    .collection-payment-info-grid {
        margin: 18px;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        border: 1px solid #e5e7eb;
    }

    .collection-payment-info-grid>div {
        padding: 12px;
        border-right: 1px solid #e5e7eb;
    }

    .collection-payment-info-grid>div:last-child {
        border-right: 0;
    }

    .collection-payment-info-grid span,
    .collection-gateway-grid span {
        display: block;
        color: #64748b;
        font-size: 9px;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .collection-payment-info-grid strong,
    .collection-gateway-grid strong {
        color: #1f2937;
        font-size: 12px;
        word-break: break-word;
    }

    .collection-gateway-grid {
        margin: 0 18px 18px;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
    }

    .collection-gateway-grid>div {
        padding: 10px;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
    }

    .collection-receipt-footer {
        margin-top: 18px;
        padding: 13px 18px 18px;
        display: flex;
        justify-content: center;
        gap: 30px;
        flex-wrap: wrap;
        color: #64748b;
        font-size: 11px;
        border-top: 1px solid #e5e7eb;
    }

    .collection-all-total {
        margin-top: 14px;
        padding: 13px 16px;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: #9a3412;
        font-size: 13px;
        font-weight: 800;
    }

    .collection-all-total strong {
        font-size: 18px;
    }

    @media(max-width: 900px) {
        .collection-record-head {
            grid-template-columns: 1fr auto;
        }

        .collection-record-middle {
            grid-column: 1 / -1;
            justify-content: flex-start;
            gap: 15px;
        }

        .collection-receipt-header {
            flex-direction: column;
        }

        .collection-receipt-number {
            min-width: 0;
            width: 100%;
            text-align: left;
        }

        .collection-property-box {
            display: block;
        }

        .collection-floor-box {
            margin-top: 15px;
        }

        .collection-payment-info-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .collection-payment-info-grid>div:nth-child(2) {
            border-right: 0;
        }

        .collection-gateway-grid {
            grid-template-columns: 1fr;
        }
    }

    @media(max-width: 600px) {
        .collection-record-head {
            grid-template-columns: 1fr auto;
            padding: 11px;
        }

        .collection-record-amount {
            font-size: 15px;
        }

        .collection-record-middle {
            font-size: 11px;
        }

        .collection-property-info div {
            grid-template-columns: 1fr;
            gap: 1px;
            margin-bottom: 4px;
        }

        .collection-payment-info-grid {
            grid-template-columns: 1fr;
        }

        .collection-payment-info-grid>div {
            border-right: 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .collection-payment-info-grid>div:last-child {
            border-bottom: 0;
        }

        .collection-full-amount-table {
            margin: 0 10px;
            width: calc(100% - 20px);
        }

        .collection-section-heading,
        .collection-received-line {
            margin-left: 10px;
            margin-right: 10px;
        }

        .collection-receipt-footer {
            padding-left: 10px;
            padding-right: 10px;
            gap: 10px;
        }
    }

    /* =========================================================
       COLLECTIONS - YEAR WISE ACCORDION
    ========================================================= */
    .collection-year {
        border: 1px solid #dbe3ec;
        border-radius: 12px;
        margin-bottom: 12px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
    }

    .collection-year-head {
        width: 100%;
        border: 0;
        padding: 14px 16px;
        background: #f8fafc;
        color: #0f172a;
        font-weight: 800;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        cursor: pointer;
        text-align: left;
    }

    .collection-year-head:hover {
        background: #f1f5f9;
    }

    .collection-year-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .collection-year-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #dcfce7;
        color: #15803d;
        font-size: 13px;
    }

    .collection-year-title {
        font-size: 13px;
        color: #0f172a;
    }

    .collection-year-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        font-size: 11px;
    }

    .collection-paid-badge {
        padding: 0px 9px;
        border-radius: 20px;
        background: #dcfce7;
        color: #166534;
        font-weight: 800;
    }

    .collection-year-total {
        color: #1d4ed8;
        font-weight: 900;
    }

    .collection-chevron {
        transition: transform .2s ease;
        color: #64748b;
    }

    .collection-year.open .collection-chevron {
        transform: rotate(180deg);
    }

    .collection-year-body {
        display: none;
        padding: 14px;
        border-top: 1px solid #e5e7eb;
        background: #fff;
    }

    .collection-year.open .collection-year-body {
        display: block;
    }

    .collection-detail-card {
        border: 1px solid #e5e7eb;
        border-radius: 9px;
        overflow: hidden;
        margin-bottom: 12px;
    }

    .collection-detail-card:last-child {
        margin-bottom: 0;
    }

    .collection-detail-title {
        padding: 9px 12px;
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        font-size: 11px;
        font-weight: 800;
        color: #475569;
    }

    .collection-detail-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 8px;
        padding: 12px;
    }

    .collection-detail-item {
        padding: 9px 10px;
        background: #f8fafc;
        border: 1px solid #edf0f4;
        border-radius: 7px;
    }

    .collection-detail-item span {
        display: block;
        color: #64748b;
        font-size: 9px;
        text-transform: uppercase;
        margin-bottom: 3px;
    }

    .collection-detail-item strong {
        color: #1e293b;
        font-size: 12px;
    }

    .collection-amount-table {
        width: 100%;
        border-collapse: collapse;
    }

    .collection-amount-table th,
    .collection-amount-table td {
        border: 1px solid #e5e7eb;
        padding: 8px 10px;
        font-size: 11px;
    }

    .collection-amount-table th {
        background: #f8fafc;
        text-align: left;
        color: #475569;
    }

    .collection-amount-table td:last-child {
        text-align: right;
        font-weight: 700;
    }

    .collection-grand-total td {
        background: #fff7ed;
        color: #c2410c;
        font-weight: 900 !important;
    }

    @media(max-width:900px) {
        .collection-detail-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media(max-width:600px) {
        .collection-detail-grid {
            grid-template-columns: 1fr;
        }
    }

    .collection-year {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        margin-bottom: 12px;
        overflow: hidden;
    }

    .collection-year-head {
        padding: 12px 14px;
        background: #f8fafc;
        font-weight: 800;
        display: flex;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }

    @media(max-width:700px) {
        .property-head {
            align-items: flex-start;
            flex-direction: column;
        }
    }

    /* =========================================================
       DEMAND TAB - SCREENSHOT STYLE
    ========================================================= */
    .demand-screen {
        border-radius: 0;
        box-shadow: none;
        border: 1px solid #e5e7eb;
    }

    .demand-years-list {
        width: 100%;
        border: 1px solid #d9e0e8;
        border-radius: 4px;
        overflow: hidden;
        background: #fff;
    }

    .demand-year-item {
        border-bottom: 1px solid #d9e0e8;
    }

    .demand-year-item:last-child {
        border-bottom: 0;
    }

    .demand-year-row {
        width: 100%;
        border: 0;
        background: #fff;
        padding: 14px 20px;
        display: grid;
        grid-template-columns: 180px 1fr;
        align-items: center;
        gap: 20px;
        cursor: pointer;
        text-align: left;
        color: #4775e5;
    }

    .demand-year-row:hover {
        background: #f8fafc;
    }

    .demand-year-row.active {
        background: #f8fafc;
    }

    .demand-year-name {
        font-size: 16px;
        font-weight: 500;
        color: #4775e5;
        white-space: nowrap;
    }

    .demand-year-summary {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 22px;
        flex-wrap: wrap;
        font-size: 13px;
        color: #4775e5;
    }

    .demand-year-summary span {
        white-space: nowrap;
    }

    .demand-year-summary .total-tax {
        font-weight: 700;
    }

    .demand-year-status {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 800;
    }

    .demand-year-status.paid {
        background: #dcfce7;
        color: #166534;
    }

    .demand-year-status.unpaid {
        background: #fff7ed;
        color: #c2410c;
    }

    .demand-year-arrow {
        margin-left: 2px;
        font-size: 11px;
        transition: transform .2s ease;
    }

    .demand-year-row.active .demand-year-arrow {
        transform: rotate(180deg);
    }

    .demand-year-details {
        display: none;
        padding: 14px;
        background: #fff;
        border-top: 1px solid #e5e7eb;
    }

    .demand-year-details.active {
        display: block;
    }

    .demand-year-panel {
        border: 1px solid #e5e7eb;
        border-radius: 0;
        margin-bottom: 14px;
        overflow: hidden;
        background: #fff;
    }

    .year-bar {
        padding: 10px 12px;
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
    }

    .demand-year-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        margin-right: 5px;
        border-radius: 50%;
        background: #eef4ff;
        color: #4775e5;
        font-size: 10px;
        font-weight: 800;
    }

    .year-summary {
        gap: 10px;
        font-size: 10px;
    }

    .year-summary strong {
        color: #0f5fd7;
    }

    .demand-year-body {
        padding: 10px;
        background: #fff;
    }

    .demand-info-line {
        margin-bottom: 8px;
        padding: 7px 9px;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        color: #52627a;
        font-size: 10px;
        font-weight: 700;
    }

    .demand-subtitle {
        margin: 12px 0 7px;
        padding: 7px 9px;
        border-left: 3px solid #4775e5;
        background: #f8fafc;
        color: #475569;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .02em;
    }

    .demand-note {
        margin: 7px 0;
        padding: 7px 2px;
        color: #52627a;
        font-size: 10px;
        font-weight: 700;
    }

    .demand-calc-table,
    .demand-summary-table {
        min-width: 650px;
    }

    .demand-calc-table th,
    .demand-summary-table th {
        background: #f8fafc;
        text-align: center;
    }

    .demand-calc-table td,
    .demand-summary-table td {
        text-align: center;
    }

    .floor-demand-table {
        min-width: 1250px;
    }

    .floor-demand-table th,
    .floor-demand-table td {
        text-align: center;
        vertical-align: middle;
        white-space: nowrap;
    }

    .floor-demand-table .floor-code-row th {
        background: #fff;
        color: #334155;
        font-size: 10px;
        padding: 6px 5px;
    }

    .floor-demand-table thead tr:nth-child(2) th {
        background: #fff;
        font-size: 10px;
        line-height: 1.25;
    }

    .floor-total-row th,
    .floor-total-row td {
        background: #f8fafc;
        font-weight: 700;
    }

    .net-payable-section {
        margin-top: 18px;
        padding: 0;
        background: #fff;
        border-top: 1px solid #e5e7eb;
    }

    .net-payable-title {
        margin: 0;
        padding: 14px 2px 9px;
        color: #52627a;
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .net-payable-subtitle {
        margin: 0 0 9px;
        padding: 0 2px;
        color: #52627a;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .net-payable-table {
        width: min(850px, 100%);
        border-collapse: collapse;
    }

    .net-payable-table td {
        border: 1px solid #e5e7eb;
        padding: 8px 12px;
        font-size: 11px;
        color: #4b5563;
    }

    .net-payable-table td:first-child {
        width: 240px;
        text-align: center;
        font-weight: 500;
    }

    .net-payable-table td:last-child {
        text-align: center;
    }

    .net-payable-total td {
        font-weight: 700;
    }

    .net-payable-pay-row {
        display: flex;
        justify-content: flex-end;
        margin-top: 12px;
    }

    .demand-tax-breakup {
        margin-top: 12px;
    }

    @media (max-width: 900px) {
        .year-summary {
            width: 100%;
        }

        .demand-year-tab {
            border-left: 1px solid #d1d5db !important;
            margin-bottom: 4px;
        }
    }
</style>

<main class="flex-1 p-4 md:p-6 space-y-5 overflow-y-auto">

    <div class="property-head">
        <div>
            <h1>
                Property Details
                <span style="color:#2563eb;">#<?= e($property_no) ?></span>
            </h1>
            <div class="text-xs text-slate-500 mt-1">
                Assessment ID: <?= $property_id ?> &nbsp; | &nbsp; PID: <?= e($property_pid) ?>
            </div>
        </div>

        <div class="flex gap-2">
            <a href="edit_assessment.php?id=<?= $property_id ?>"
                class="px-4 py-2 rounded-lg bg-white border border-slate-300 text-sm font-bold text-slate-700">
                <i class="fa fa-edit mr-1"></i> Edit
            </a>

            <a href="view-assesment.php"
                class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm font-bold">
                <i class="fa fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>

    <nav class="property-tabs">
        <?php foreach ($allowed_tabs as $tab): ?>
            <a href="<?= e(tab_url($property_id, $tab)) ?>"
                class="property-tab <?= $active_tab === $tab ? 'active' : '' ?>">
                <?= e(strtoupper(str_replace('-', ' ', $tab))) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if ($active_tab === 'basic'): ?>

        <!-- BASIC -->
        <section class="minimal-card p-5 md:p-6">

            <div class="section-title">Property Details</div>

            <?php
            $property_fields = [
                'Name of Municipality*' => first_value($property, ['municipality_name', 'municipality'], 'N/A'),
                'Year Of Assessment*' => first_value($property, ['year_of_assessment', 'assessment_year'], 'N/A'),
                'New Holding Number' => first_value($property, ['new_holding', 'new_holding_number', 'holding_no'], 'N/A'),
                'Old Holding Number' => first_value($property, ['old_holding', 'old_holding_number'], 'N/A'),
                'Property Status*' => first_value($property, ['property_type', 'property_status'], 'N/A'),
                'Old PID Number' => first_value($property, ['old_pid', 'old_pid_number'], 'N/A'),
                'Road On Which Located*' => first_value($property, ['road_location', 'road_on_which_located', 'road'], 'N/A'),
                'Date of Acquisition / Construction of Property *' => first_value($property, ['date_of_acquisition', 'construction_date'], 'N/A'),
                'Area Of Plot*' => first_value($property, ['area_of_plot', 'total_area', 'plot_area'], 'N/A'),
                'Build Up Area*' => first_value($property, ['build_up_area', 'covered_area'], 'N/A'),
                'Water Connection*' => first_value($property, ['water_connection'], 'N/A'),
                'Rain Water Harvesting*' => first_value($property, ['rain_water_harvesting'], 'N/A'),
            ];
            ?>

            <dl class="grid grid-cols-1 md:grid-cols-2 gap-y-5 gap-x-8">

                <?php foreach ($property_fields as $label => $value): ?>
                    <div class="data-item">
                        <dt><?= e($label) ?></dt>
                        <dd class="property-value-box">
                            <?= e($value ?: 'N/A') ?>
                        </dd>
                    </div>
                <?php endforeach; ?>

            </dl>

        </section>

        <!-- OWNER DETAILS -->
        <section class="minimal-card p-5 md:p-6">

            <div class="section-title">Owner Details</div>

            <div class="table-wrap">
                <table class="detail-table basic-owner-table">
                    <thead>
                        <tr>
                            <th>Sr. No</th>
                            <th>Name/Name of Organisation/Company</th>
                            <th>C/O-S/O-D/O-W/O</th>
                            <th>Gender</th>
                            <th>Mobile</th>
                            <th>Email</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($owners): ?>

                            <?php foreach ($owners as $index => $owner): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>

                                    <td class="font-semibold">
                                        <?= e(first_value(
                                            $owner,
                                            ['owner_name', 'name', 'organisation_name', 'organisation_company_name'],
                                            'N/A'
                                        )) ?>
                                    </td>

                                    <td>
                                        <?= e(first_value(
                                            $owner,
                                            ['father_husband_name', 'father_husband', 'relation'],
                                            'N/A'
                                        )) ?>
                                    </td>

                                    <td>
                                        <?= e(first_value(
                                            $owner,
                                            ['gender'],
                                            'N/A'
                                        )) ?>
                                    </td>

                                    <td>
                                        <?= e(first_value(
                                            $owner,
                                            ['mobile', 'mobile_no', 'phone'],
                                            'N/A'
                                        )) ?>
                                    </td>

                                    <td>
                                        <?= e(first_value(
                                            $owner,
                                            ['email', 'email_id'],
                                            'N/A'
                                        )) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="6" class="text-center">
                                    No owner details found.
                                </td>
                            </tr>

                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </section>

        <!-- ADDRESS -->
        <section class="minimal-card p-5 md:p-6">

            <div class="section-title">Address Details</div>

            <?php
            $address_fields = [
                'Property/House No*' => first_value($property, ['house_no'], 'N/A'),
                'Plot No' => first_value($property, ['plot_no'], 'N/A'),
                'Khata No' => first_value($property, ['khata_no'], 'N/A'),
                'Khasra No' => first_value($property, ['khasra_no'], 'N/A'),
                'Address Line 1*' => first_value($property, ['addr1', 'address_line1'], 'N/A'),
                'Address Line 2' => first_value($property, ['addr2', 'address_line2'], 'N/A'),
                'State*' => first_value($property, ['state'], 'N/A'),
                'District*' => first_value($property, ['district'], 'N/A'),
                'City/Village *' => first_value($property, ['city_village', 'city', 'village'], 'N/A'),
                'Zone*' => $zone_display_value,
                'Ward*' => $ward_display_value,
                'Pincode*' => first_value($property, ['pincode'], 'N/A'),
            ];
            ?>

            <dl class="grid grid-cols-1 md:grid-cols-2 gap-y-5 gap-x-8">

                <?php foreach ($address_fields as $label => $value): ?>
                    <div class="data-item">
                        <dt><?= e($label) ?></dt>
                        <dd class="property-value-box">
                            <?= e($value ?: 'N/A') ?>
                        </dd>
                    </div>
                <?php endforeach; ?>

            </dl>

        </section>

        <!-- FLOOR DETAILS -->
        <section class="minimal-card p-5 md:p-6">

            <div class="section-title">Floor Details</div>

            <div class="table-wrap">
                <table class="detail-table floor-basic-table">
                    <thead>
                        <tr>
                            <th>Sr. No</th>
                            <th>Floor No</th>
                            <th>Residential Type</th>
                            <th>Construction Type</th>
                            <th>Occupancy Type</th>
                            <th>Build Up Area</th>
                            <th>Date From</th>
                            <th>Date To</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($floors): ?>

                            <?php foreach ($floors as $index => $floor): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>

                                    <td class="font-semibold">
                                        <?= e(first_value($floor, ['floor_no', 'floor_name'], 'N/A')) ?>
                                    </td>

                                    <td>
                                        <?= e(first_value($floor, ['usage_type', 'residential_type'], 'N/A')) ?>
                                    </td>

                                    <td>
                                        <?= e(first_value($floor, ['construction_type'], 'N/A')) ?>
                                    </td>

                                    <td>
                                        <?= e(first_value($floor, ['occupancy_type'], 'N/A')) ?>
                                    </td>

                                    <td>
                                        <?= e(first_value($floor, ['build_up_area', 'built_up_area', 'area'], 'N/A')) ?>
                                    </td>

                                    <td>
                                        <?= e(safe_date(first_value($floor, ['date_from'], ''))) ?>
                                    </td>

                                    <td>
                                        <?= e(safe_date(first_value($floor, ['date_to'], ''))) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="8" class="text-center">
                                    No floor details found.
                                </td>
                            </tr>

                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </section>

        <!-- OTHER TAX -->
        <section class="minimal-card p-5 md:p-6">

            <div class="section-title">Other Taxes</div>

            <label class="other-tax-row">
                <input
                    type="checkbox"
                    disabled
                    <?= !empty($property['water_tax']) && (string)$property['water_tax'] === '1'
                        ? 'checked'
                        : '' ?>>
                <span>
                    Water Tax (Piped drinking within 250 meters)
                </span>
            </label>

        </section>

        <!-- IMAGE UPLOAD -->
        <section class="minimal-card p-5 md:p-6">

            <div class="section-title">Image Upload</div>

            <div class="image-grid-basic">

                <?php
                $images = [
                    'Center Image of Property' => [
                        $center_image,
                        empty($property['center_image_gps'])
                    ],
                    'Left Image of Property' => [
                        $left_image,
                        empty($property['left_image_gps'])
                    ],
                    'Right Image of Property' => [
                        $right_image,
                        empty($property['right_image_gps'])
                    ]
                ];
                ?>

                <?php foreach ($images as $label => [$src, $is_default]): ?>

                    <div class="property-image-box">

                        <div class="data-item mb-2">
                            <dt><?= e($label) ?></dt>
                        </div>

                        <a
                            href="<?= e($src) ?>"
                            target="_blank"
                            rel="noopener">

                            <img
                                src="<?= e($src) ?>"
                                alt="<?= e($label) ?>"
                                class="<?= $is_default ? 'default-property-image' : '' ?>">

                        </a>

                    </div>

                <?php endforeach; ?>

            </div>

        </section>

    <?php elseif ($active_tab === 'demand'): ?>

        <section class="minimal-card p-5 demand-screen">

            <div class="section-title">Demand / ARV</div>

            <?php if ($arv_rows): ?>

                <div class="demand-years-list">

                    <?php foreach ($unpaid_arv_rows as $arv): ?>
                        <?php
                        $house_current  = (float)($arv['house_tax_current'] ?? 0);
                        $house_arrear   = (float)($arv['house_tax_arrear'] ?? 0);
                        $house_interest = (float)($arv['house_tax_interest'] ?? 0);
                        $house_total    = tax_total($arv, 'house_tax');

                        $water_current  = (float)($arv['water_tax_current'] ?? 0);
                        $water_arrear   = (float)($arv['water_tax_arrear'] ?? 0);
                        $water_interest = (float)($arv['water_tax_interest'] ?? 0);
                        $water_total    = tax_total($arv, 'water_tax');

                        $water_fee_current  = (float)($arv['water_fee_current'] ?? 0);
                        $water_fee_arrear   = (float)($arv['water_fee_arrear'] ?? 0);
                        $water_fee_interest = (float)($arv['water_fee_interest'] ?? 0);
                        $water_fee_total    = tax_total($arv, 'water_fee');

                        $sewer_current  = (float)($arv['sewer_tax_current'] ?? 0);
                        $sewer_arrear   = (float)($arv['sewer_tax_arrear'] ?? 0);
                        $sewer_interest = (float)($arv['sewer_tax_interest'] ?? 0);
                        $sewer_total    = tax_total($arv, 'sewer_tax');

                        $other_current  = (float)($arv['other_tax_current'] ?? 0);
                        $other_arrear   = (float)($arv['other_tax_arrear'] ?? 0);
                        $other_interest = (float)($arv['other_tax_interest'] ?? 0);
                        $other_total    = tax_total($arv, 'other_tax');

                        $year_total = (float)($arv['total_tax'] ?? (
                            $house_total + $water_total + $water_fee_total + $sewer_total + $other_total
                        ));

                        $plot_area = demand_number(
                            $arv,
                            ['area_of_plot', 'plot_area', 'land_area', 'area_plot'],
                            first_value($property, ['area_of_plot', 'plot_area', 'total_area'], 0)
                        );

                        $ground_area = demand_number(
                            $arv,
                            ['ground_floor_built_up_area', 'built_up_ground_area', 'built_up_area_ground', 'ground_built_up_area'],
                            0
                        );

                        if ($ground_area <= 0) {
                            foreach ($floors as $floor_check) {
                                $floor_name_check = strtolower((string)first_value($floor_check, ['floor_no', 'floor_name'], ''));
                                if (strpos($floor_name_check, 'ground') !== false || $floor_name_check === '0') {
                                    $ground_area = demand_number(
                                        $floor_check,
                                        ['build_up_area', 'built_up_area', 'area'],
                                        0
                                    );
                                    break;
                                }
                            }
                        }

                        $built_percentage = first_value(
                            $arv,
                            ['percentage_area_built', 'built_up_percentage', 'constructed_percentage'],
                            null
                        );

                        if ($built_percentage === null || $built_percentage === '') {
                            $built_percentage = $plot_area > 0
                                ? ($ground_area / $plot_area) * 100
                                : 0;
                        }

                        $built_percentage = (float)$built_percentage;

                        $vacant_land = demand_number(
                            $arv,
                            ['taxable_vacant_land', 'vacant_land_area'],
                            $plot_area - ($ground_area * 1.43)
                        );

                        $vacant_rate = demand_number(
                            $arv,
                            ['vacant_land_tax_rate', 'vacant_land_rate'],
                            0
                        );

                        $vacant_annual_tax = demand_number(
                            $arv,
                            ['vacant_land_annual_tax', 'vacant_land_tax'],
                            $vacant_land * $vacant_rate
                        );

                        $floor_total_arv = 0.00;
                        $floor_total_tax = 0.00;

                        foreach ($floors as $floor_calc) {
                            $floor_total_arv += demand_number(
                                $floor_calc,
                                ['annual_rental_value', 'arv', 'annual_rent'],
                                0
                            );

                            $floor_total_tax += demand_number(
                                $floor_calc,
                                ['annual_property_tax', 'property_tax', 'tax_amount'],
                                0
                            );
                        }

                        $summary_arv = demand_number(
                            $arv,
                            ['annual_rental_value', 'arv', 'total_arv', 'house_tax_arv'],
                            $floor_total_arv
                        );

                        $summary_property_tax = demand_number(
                            $arv,
                            ['property_tax', 'total_property_tax', 'house_tax_amount'],
                            $floor_total_tax
                        );

                        $summary_annual_tax = demand_number(
                            $arv,
                            ['annual_tax', 'total_annual_tax', 'total_tax'],
                            $year_total
                        );

                        $summary_rebate = demand_number($arv, ['rebate'], 0);

                        $summary_penalty = demand_number(
                            $arv,
                            ['penalty', 'interest', 'interest_penalty'],
                            $house_interest
                        );

                        $previous_payment = first_value(
                            $arv,
                            ['previous_year_payment', 'previous_payment'],
                            'N/A'
                        );
                        ?>

                        <?php
                        $display_year = normalize_financial_year($arv['_display_financial_year'] ?? '');
                        $is_paid_year = $display_year !== '' && isset($paid_years[$display_year]);
                        ?>

                        <div class="demand-year-item">

                            <button type="button"
                                class="demand-year-row"
                                data-demand-toggle="demand-details-<?= (int)$arv['id'] ?>"
                                aria-expanded="false">

                                <span class="demand-year-name">
                                    <?= e($arv['_display_financial_year']) ?>
                                </span>

                                <span class="demand-year-summary">
                                    <span>VL.Tax: <?= money($water_total) ?></span>
                                    <span>Fl.Tax: <?= money($house_total) ?></span>
                                    <span>Pr.Tax: <?= money($sewer_total) ?></span>
                                    <span>Rebate: <?= money($summary_rebate) ?></span>
                                    <span>Penalty: <?= money($summary_penalty) ?></span>
                                    <span class="total-tax">Total Tax: <?= money($year_total) ?></span>
                                    <span class="demand-year-status <?= $is_paid_year ? 'paid' : 'unpaid' ?>">
                                        <?= $is_paid_year ? 'PAID' : 'UNPAID' ?>
                                    </span>
                                    <i class="fa fa-chevron-down demand-year-arrow"></i>
                                </span>

                            </button>

                            <div id="demand-details-<?= (int)$arv['id'] ?>"
                                class="demand-year-details">

                                <div class="demand-year-body">

                                    <div class="demand-info-line">
                                        <i class="fa fa-info-circle mr-1 text-blue-500"></i>
                                        Demand Details for this Financial Year
                                    </div>

                                    <!-- 1A - 1F -->
                                    <div class="demand-subtitle">
                                        <i class="fa fa-calculator mr-1"></i>
                                        Property / Vacant Land Calculation
                                    </div>

                                    <div class="table-wrap mb-4">
                                        <table class="detail-table demand-calc-table">
                                            <tbody>

                                                <tr>
                                                    <th style="width:90px;">1A</th>
                                                    <td>Area of Plot / Land (in sq. ft.)</td>
                                                    <td class="text-right font-semibold">
                                                        <?= money($plot_area) ?>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th>1B</th>
                                                    <td>Built-up / Constructed area on the ground floor (in sq.ft.)</td>
                                                    <td class="text-right font-semibold">
                                                        <?= money($ground_area) ?>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th>1C</th>
                                                    <td>Percentage Area built-up / Constructed = (1B/1A) × 100</td>
                                                    <td class="text-right font-semibold">
                                                        <?= money($built_percentage) ?>
                                                    </td>
                                                </tr>

                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="demand-note">
                                        NOTE: IF 1C IS GREATER THEN OR EQUAL TO 70%, THEN GO TO 2 OTHERWISE CONTINUE TO 1D
                                    </div>

                                    <div class="table-wrap mb-4">
                                        <table class="detail-table demand-calc-table">
                                            <tbody>

                                                <tr>
                                                    <th style="width:90px;">1D</th>
                                                    <td>Taxable Vacant Land (in sq. ft.) = 1A - (1B × 1.43)</td>
                                                    <td class="text-right font-semibold">
                                                        <?= money($vacant_land) ?>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th>1E</th>
                                                    <td>Vacant Land Tax Rate (Refer to A2 of annex 1) (Rs.)</td>
                                                    <td class="text-right font-semibold">
                                                        <?= money($vacant_rate) ?>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th>1F</th>
                                                    <td class="font-bold">Vacant Land Annual Tax (1D × 1E) (Rs.)</td>
                                                    <td class="text-right font-bold">
                                                        <?= money($vacant_annual_tax) ?>
                                                    </td>
                                                </tr>

                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- FLOOR-WISE ARV -->
                                    <div class="demand-subtitle">
                                        <i class="fa fa-building mr-1"></i>
                                        Floor-wise Property Tax / ARV Calculation
                                    </div>

                                    <div class="table-wrap mb-4">
                                        <table class="detail-table floor-demand-table">

                                            <thead>

                                                <tr class="floor-code-row">
                                                    <th>2A</th>
                                                    <th>2B</th>
                                                    <th>2C</th>
                                                    <th>2D</th>
                                                    <th>2E</th>
                                                    <th>2F</th>
                                                    <th>2G</th>
                                                    <th>2H</th>
                                                    <th>2I</th>
                                                    <th>2J</th>
                                                    <th>2K</th>
                                                </tr>

                                                <tr>
                                                    <th>Floor No</th>
                                                    <th>Build Up Area</th>
                                                    <th>Residential or Non-Residential</th>
                                                    <th>Construction Type</th>
                                                    <th>Rateable Area (in %)</th>
                                                    <th>Unit Area Rate (Rs/sq. feet)</th>
                                                    <th>Non-Residential Use / Multiplying Factor</th>
                                                    <th>Occupancy Factor</th>
                                                    <th>Annual Rental Value (2D × 2E × 2F × 2G × 2H)</th>
                                                    <th>Property Tax Rate (in %)</th>
                                                    <th>Annual Property Tax (in Rs 2I × 2J)</th>
                                                </tr>

                                            </thead>

                                            <tbody>

                                                <?php if ($floors): ?>

                                                    <?php foreach ($floors as $floor): ?>

                                                        <?php
                                                        $f_area = demand_number(
                                                            $floor,
                                                            ['build_up_area', 'built_up_area', 'area'],
                                                            0
                                                        );

                                                        $f_rateable = first_value(
                                                            $floor,
                                                            ['rateable_area', 'rateable_area_percentage', 'rateable_percentage'],
                                                            'N/A'
                                                        );

                                                        $f_unit = demand_number(
                                                            $floor,
                                                            ['unit_area_rate', 'rate', 'unit_rate'],
                                                            0
                                                        );

                                                        $f_multiplier = first_value(
                                                            $floor,
                                                            ['non_residential_use', 'non_residential_group', 'multiplying_factor'],
                                                            'N/A'
                                                        );

                                                        $f_occupancy = first_value(
                                                            $floor,
                                                            ['occupancy_factor', 'occupancy_type'],
                                                            'N/A'
                                                        );

                                                        $f_arv = demand_number(
                                                            $floor,
                                                            ['annual_rental_value', 'arv', 'annual_rent'],
                                                            0
                                                        );

                                                        $f_tax_rate = first_value(
                                                            $floor,
                                                            ['property_tax_rate', 'tax_rate'],
                                                            'N/A'
                                                        );

                                                        $f_tax = demand_number(
                                                            $floor,
                                                            ['annual_property_tax', 'property_tax', 'tax_amount'],
                                                            0
                                                        );
                                                        ?>

                                                        <tr>
                                                            <td>
                                                                <?= e(first_value($floor, ['floor_no', 'floor_name'], 'N/A')) ?>
                                                            </td>

                                                            <td><?= money($f_area) ?></td>

                                                            <td>
                                                                <?= e(first_value(
                                                                    $floor,
                                                                    ['residential_type', 'usage_type', 'property_type'],
                                                                    'N/A'
                                                                )) ?>
                                                            </td>

                                                            <td>
                                                                <?= e(first_value(
                                                                    $floor,
                                                                    ['construction_type'],
                                                                    'N/A'
                                                                )) ?>
                                                            </td>

                                                            <td><?= e((string)$f_rateable) ?></td>

                                                            <td><?= money($f_unit) ?></td>

                                                            <td><?= e((string)$f_multiplier) ?></td>

                                                            <td><?= e((string)$f_occupancy) ?></td>

                                                            <td><?= money($f_arv) ?></td>

                                                            <td><?= e((string)$f_tax_rate) ?></td>

                                                            <td><?= money($f_tax) ?></td>
                                                        </tr>

                                                    <?php endforeach; ?>

                                                    <tr class="floor-total-row">
                                                        <th colspan="8" class="text-right">Total</th>
                                                        <th><?= money($floor_total_arv) ?></th>
                                                        <th></th>
                                                        <th><?= money($floor_total_tax) ?></th>
                                                    </tr>

                                                <?php else: ?>

                                                    <tr>
                                                        <td colspan="11" class="text-center text-gray-400">
                                                            No floor-wise calculation records found.
                                                        </td>
                                                    </tr>

                                                <?php endif; ?>

                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- 3A - 3G -->
                                    <div class="demand-subtitle">
                                        <i class="fa fa-list-alt mr-1"></i>
                                        Annual Tax Summary
                                    </div>

                                    <div class="table-wrap mb-4">
                                        <table class="detail-table demand-summary-table">
                                            <tbody>

                                                <tr>
                                                    <th style="width:90px;">3A</th>
                                                    <td>Annual Rental Value (ARV) sum</td>
                                                    <td class="text-right">
                                                        ₹ <?= money($summary_arv) ?>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th>3B</th>
                                                    <td>Total Property Tax</td>
                                                    <td class="text-right">
                                                        ₹ <?= money($summary_property_tax) ?>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th>3C</th>
                                                    <td>Total Annual Tax</td>
                                                    <td class="text-right">
                                                        ₹ <?= money($summary_annual_tax) ?>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th>3D</th>
                                                    <td>Rebate</td>
                                                    <td class="text-right">
                                                        ₹ <?= money($summary_rebate) ?>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th>3E</th>
                                                    <td>Interest / Penalty</td>
                                                    <td class="text-right">
                                                        ₹ <?= money($summary_penalty) ?>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th>3F</th>
                                                    <td>Previous Year Payment</td>
                                                    <td class="text-right">
                                                        <?= e((string)$previous_payment) ?>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th>3G</th>
                                                    <td class="font-bold">Total Annual Property Tax</td>
                                                    <td class="text-right font-bold">
                                                        ₹ <?= money($year_total) ?>
                                                    </td>
                                                </tr>

                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Tax breakup kept visible as part of "all details" -->
                                    <div class="demand-tax-breakup">

                                        <div class="demand-subtitle">
                                            <i class="fa fa-money mr-1"></i>
                                            Tax Component Breakup
                                        </div>

                                        <div class="table-wrap">
                                            <table class="detail-table">

                                                <thead>
                                                    <tr>
                                                        <th>Particular</th>
                                                        <th>Current</th>
                                                        <th>Arrear</th>
                                                        <th>Interest / Penalty</th>
                                                        <th>Total</th>
                                                    </tr>
                                                </thead>

                                                <tbody>

                                                    <tr>
                                                        <td>House / Property Tax</td>
                                                        <td><?= money($house_current) ?></td>
                                                        <td><?= money($house_arrear) ?></td>
                                                        <td><?= money($house_interest) ?></td>
                                                        <td><strong><?= money($house_total) ?></strong></td>
                                                    </tr>

                                                    <tr>
                                                        <td>Water Tax</td>
                                                        <td><?= money($water_current) ?></td>
                                                        <td><?= money($water_arrear) ?></td>
                                                        <td><?= money($water_interest) ?></td>
                                                        <td><strong><?= money($water_total) ?></strong></td>
                                                    </tr>

                                                    <tr>
                                                        <td>Water Fee / Charge</td>
                                                        <td><?= money($water_fee_current) ?></td>
                                                        <td><?= money($water_fee_arrear) ?></td>
                                                        <td><?= money($water_fee_interest) ?></td>
                                                        <td><strong><?= money($water_fee_total) ?></strong></td>
                                                    </tr>

                                                    <tr>
                                                        <td>Sewerage Tax</td>
                                                        <td><?= money($sewer_current) ?></td>
                                                        <td><?= money($sewer_arrear) ?></td>
                                                        <td><?= money($sewer_interest) ?></td>
                                                        <td><strong><?= money($sewer_total) ?></strong></td>
                                                    </tr>

                                                    <tr>
                                                        <td>Other Tax</td>
                                                        <td><?= money($other_current) ?></td>
                                                        <td><?= money($other_arrear) ?></td>
                                                        <td><?= money($other_interest) ?></td>
                                                        <td><strong><?= money($other_total) ?></strong></td>
                                                    </tr>

                                                </tbody>

                                            </table>
                                        </div>

                                    </div>

                                </div>
                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty-box mb-4">
                    <i class="fa fa-check-circle text-2xl text-emerald-500"></i>
                    <div class="font-bold mt-2">No unpaid demand.</div>
                    <div class="text-xs mt-1">
                        All available financial years have already been paid.
                    </div>
                </div>

            <?php endif; ?>

            <!-- 4 - NET PAYABLE AMOUNT -->
            <div class="net-payable-section">

                <h3 class="net-payable-title">4 - NET PAYABLE AMOUNT*</h3>

                <h4 class="net-payable-subtitle">
                    4.2 PROPERTY TAX (APPLICABLE ONLY FOR ALL PROPERTIES FALLING UNDER CATEGORY OTHER 4.1)
                </h4>

                <div class="table-wrap">
                    <table class="net-payable-table">
                        <tbody>

                            <tr>
                                <td>Other Amount</td>
                                <td><?= money($net_other_amount) ?></td>
                            </tr>

                            <tr>
                                <td>Form Fee</td>
                                <td><?= money($net_form_fee) ?></td>
                            </tr>

                            <tr>
                                <td>Boring Charge</td>
                                <td><?= money($net_boring_charge) ?></td>
                            </tr>

                            <tr>
                                <td>Advance Received</td>
                                <td><?= money($net_advance_received) ?></td>
                            </tr>

                            <tr class="net-payable-total">
                                <td>Total Property Tax</td>
                                <td><?= money($net_total_property_tax) ?></td>
                            </tr>

                        </tbody>
                    </table>
                </div>

                <?php if ($unpaid_arv_rows): ?>
                    <div class="net-payable-pay-row">
                        <button type="button"
                            id="openPayModalBottom"
                            class="pay-now-btn">
                            <i class="fa fa-credit-card mr-1"></i> Pay Now
                        </button>
                    </div>
                <?php endif; ?>

            </div>

        </section>

        <?php if ($unpaid_arv_rows): ?>

            <div id="payYearsModal"
                class="pay-modal-overlay"
                aria-hidden="true">

                <div class="pay-modal">

                    <div class="pay-modal-header">

                        <div>
                            <h3>Pay Now - Select Financial Year</h3>
                            <p>Select one or more unpaid financial years.</p>
                        </div>

                        <button type="button"
                            id="closePayModal"
                            class="pay-close-btn">&times;</button>

                    </div>

                    <form
                        action="pay-online.php?id=<?= $property_id ?>&assessment_id=<?= $property_id ?>"
                        method="POST"
                        id="payYearsForm">

                        <input type="hidden"
                            name="assessment_id"
                            value="<?= $property_id ?>">

                        <input type="hidden"
                            name="id"
                            value="<?= $property_id ?>">

                        <!--
                            These values are only for the UI/payment request.
                            The payment page must recalculate the final amount
                            server-side from the selected financial years.
                        -->
                        <input type="hidden"
                            name="display_form_fee"
                            id="displayFormFee"
                            value="<?= e($net_form_fee) ?>">

                        <input type="hidden"
                            name="display_other_amount"
                            id="displayOtherAmount"
                            value="<?= e($net_other_amount) ?>">

                        <input type="hidden"
                            name="display_boring_charge"
                            id="displayBoringCharge"
                            value="<?= e($net_boring_charge) ?>">

                        <input type="hidden"
                            name="display_advance_received"
                            id="displayAdvanceReceived"
                            value="<?= e($net_advance_received) ?>">

                        <div class="pay-year-list">

                            <label class="pay-year-item pay-select-all">
                                <input type="checkbox" id="selectAllPayYears">
                                <span>Select All</span>
                            </label>

                            <?php foreach ($unpaid_arv_rows as $arv): ?>

                                <label class="pay-year-item">

                                    <input type="checkbox"
                                        name="years[]"
                                        value="<?= e($arv['_display_financial_year']) ?>"
                                        data-amount="<?= e((float)($arv['total_tax'] ?? 0)) ?>"
                                        class="pay-year-checkbox">

                                    <span>
                                        <strong><?= e($arv['_display_financial_year']) ?></strong>
                                        -
                                        Tax:
                                        ₹<?= money($arv['total_tax'] ?? 0) ?>
                                    </span>

                                </label>

                            <?php endforeach; ?>

                        </div>

                        <!-- IMPORTANT:
                             Previously the modal showed only tax amount.
                             Net payable also includes Form Fee/Other/Boring
                             and subtracts Advance Received.
                        -->
                        <div class="pay-modal-summary">

                            <div class="pay-summary-row">
                                <span>Selected Tax</span>
                                <strong id="selectedPayTax">₹0.00</strong>
                            </div>

                            <div class="pay-summary-row">
                                <span>Form Fee</span>
                                <strong id="selectedPayFormFee">₹0.00</strong>
                            </div>

                            <div class="pay-summary-row">
                                <span>Other Amount</span>
                                <strong id="selectedPayOther">₹0.00</strong>
                            </div>

                            <div class="pay-summary-row">
                                <span>Boring Charge</span>
                                <strong id="selectedPayBoring">₹0.00</strong>
                            </div>

                            <div class="pay-summary-row">
                                <span>Advance Received</span>
                                <strong id="selectedPayAdvance">- ₹0.00</strong>
                            </div>

                            <div class="pay-summary-row payable">
                                <span>Payable Amount</span>
                                <strong id="selectedPayTotal">₹0.00</strong>
                            </div>

                        </div>

                        <div class="pay-modal-footer">

                            <div class="text-xs text-slate-500">
                                Final amount will be verified on server.
                            </div>

                            <button type="submit"
                                id="modalPayNow"
                                class="pay-modal-btn"
                                disabled>
                                Continue
                            </button>

                        </div>

                    </form>

                </div>
            </div>
        <?php endif; ?>

    <?php elseif ($active_tab === 'collections'): ?>

        <?php
        /*
         * COLLECTIONS
         * One visible record = one successful payment order.
         * Payment-year rows belonging to the same order are merged.
         */

        $collection_orders = [];

        foreach ($successful_payment_rows as $row) {

            $order_id = (int)($row['payment_order_id'] ?? 0);

            if ($order_id <= 0) {
                $order_id = 'row-' . md5(json_encode($row));
            }

            if (!isset($collection_orders[$order_id])) {
                $collection_orders[$order_id] = [
                    'payment_order_id'     => (int)($row['payment_order_id'] ?? 0),
                    'order_number'        => (string)($row['order_number'] ?? ''),
                    'assessment_id'      => (int)($row['assessment_id'] ?? $property_id),
                    'tax_amount'          => (float)($row['tax_amount'] ?? 0),
                    'form_fee'            => (float)($row['form_fee'] ?? 0),
                    'other_amount'        => (float)($row['other_amount'] ?? 0),
                    'boring_charge'       => (float)($row['boring_charge'] ?? 0),
                    'advance_received'    => (float)($row['advance_received'] ?? 0),
                    'total_amount'        => (float)($row['total_amount'] ?? 0),
                    'status'              => (string)($row['status'] ?? ''),
                    'payment_status'      => (string)($row['payment_status'] ?? ''),
                    'payer_name'          => (string)($row['payer_name'] ?? ''),
                    'payer_mobile'        => (string)($row['payer_mobile'] ?? ''),
                    'payment_made_at'     => (string)($row['payment_made_at'] ?? ''),
                    'payment_mode'        => (string)($row['payment_mode'] ?? ''),
                    'payment_gateway'     => (string)($row['payment_gateway'] ?? ''),
                    'gateway_order_id'    => (string)($row['gateway_order_id'] ?? ''),
                    'gateway_payment_id' => (string)($row['gateway_payment_id'] ?? ''),
                    'created_at'         => $row['created_at'] ?? '',
                    'years'              => [],
                    'year_tax_total'     => 0.00,
                ];
            }

            $fy = normalize_financial_year($row['financial_year'] ?? '');

            if ($fy !== '' && !in_array($fy, $collection_orders[$order_id]['years'], true)) {
                $collection_orders[$order_id]['years'][] = $fy;
            }

            $collection_orders[$order_id]['year_tax_total'] +=
                (float)($row['year_tax_amount'] ?? 0);

            if ($collection_orders[$order_id]['total_amount'] <= 0) {
                $collection_orders[$order_id]['total_amount'] =
                    $collection_orders[$order_id]['year_tax_total'] +
                    $collection_orders[$order_id]['form_fee'] +
                    $collection_orders[$order_id]['other_amount'] +
                    $collection_orders[$order_id]['boring_charge'] -
                    $collection_orders[$order_id]['advance_received'];
            }
        }

        uasort($collection_orders, function ($a, $b) {
            $ta = strtotime((string)($a['payment_made_at'] ?: $a['created_at'])) ?: 0;
            $tb = strtotime((string)($b['payment_made_at'] ?: $b['created_at'])) ?: 0;
            return $tb <=> $ta;
        });

        $collection_grand_total = 0.00;

        foreach ($collection_orders as $collection_order) {
            $collection_grand_total += (float)$collection_order['total_amount'];
        }

        $receipt_number = function (array $order): string {
            $number = trim((string)($order['order_number'] ?? ''));

            if ($number !== '') {
                return $number;
            }

            $id = (int)($order['payment_order_id'] ?? 0);
            return $id > 0 ? 'RECEIPT_' . $id : 'N/A';
        };

        $display_payment_date = function (array $order): string {
            $value = $order['payment_made_at'] ?: ($order['created_at'] ?? '');

            if (!$value) {
                return 'N/A';
            }

            $ts = strtotime((string)$value);
            return $ts ? date('d/m/Y', $ts) : (string)$value;
        };

        $display_payment_datetime = function (array $order): string {
            $value = $order['payment_made_at'] ?: ($order['created_at'] ?? '');

            if (!$value) {
                return 'N/A';
            }

            $ts = strtotime((string)$value);
            return $ts ? date('d/m/Y h:i A', $ts) : (string)$value;
        };

        $display_payment_mode = function (array $order): string {
            $mode = trim((string)($order['payment_mode'] ?? ''));

            if ($mode === '') {
                $mode = trim((string)($order['payment_gateway'] ?? ''));
            }

            return $mode !== '' ? $mode : 'N/A';
        };
        ?>

        <section class="minimal-card p-5">

            <div class="section-title">
                <i class="fa fa-list-alt mr-1"></i>
                Collections / Payment History
            </div>

            <div class="text-xs text-slate-500 mb-4">
                All successful collections are shown below. Click any collection
                to view its complete receipt, owner, property, floor, tax and payment details.
            </div>

            <?php if ($collection_orders): ?>

                <div class="collection-list">

                    <?php foreach ($collection_orders as $order_index => $order): ?>

                        <?php
                        $collection_id = 'collection-order-' . (int)$order['payment_order_id'];

                        if ((int)$order['payment_order_id'] <= 0) {
                            $collection_id = 'collection-order-' . $order_index;
                        }

                        $order_receipt = $receipt_number($order);
                        $order_date = $display_payment_date($order);
                        $order_mode = $display_payment_mode($order);
                        $order_total = (float)$order['total_amount'];

                        $status = trim(
                            (string)($order['payment_status'] ?: $order['status'])
                        );

                        if ($status === '') {
                            $status = 'PAID';
                        }

                        $year_text = $order['years']
                            ? implode(', ', $order['years'])
                            : 'N/A';
                        ?>

                        <div class="collection-record" data-collection-record>

                            <button type="button"
                                class="collection-record-head"
                                data-collection-record-toggle="<?= e($collection_id) ?>"
                                aria-expanded="false">

                                <span class="collection-record-amount">
                                    ₹ <?= money($order_total) ?>
                                </span>

                                <span class="collection-record-middle">
                                    <span>Mode - <?= e($order_mode) ?></span>
                                    <span>On - <?= e($order_date) ?></span>
                                </span>

                                <span class="collection-record-right">
                                    <span class="collection-paid-badge">
                                        <?= e(strtoupper($status)) ?>
                                    </span>
                                    <i class="fa fa-chevron-down collection-record-chevron"></i>
                                </span>

                            </button>

                            <div id="<?= e($collection_id) ?>"
                                class="collection-record-body">

                                <!-- RECEIPT HEADER -->
                                <div class="collection-receipt-header">

                                    <div class="collection-logo-area">

                                        <div>
                                            <div class="collection-municipality-name">
                                                <?= e(first_value(
                                                    $property,
                                                    ['municipality_name', 'municipality'],
                                                    'Khalilabad Nagar Palika'
                                                )) ?>
                                            </div>

                                            <div class="collection-receipt-caption">
                                                Property Tax Collection Receipt
                                            </div>
                                        </div>

                                    </div>

                                    <div class="collection-receipt-number">

                                        <div>
                                            <strong>Property Tax Receipt No :</strong>
                                            <?= e($order_receipt) ?>
                                        </div>

                                        <div>
                                            <strong>Holding No :</strong>
                                            <?= e($property_no) ?>
                                        </div>

                                        <div>
                                            <strong>Receipt Date :</strong>
                                            <?= e($order_date) ?>
                                        </div>

                                    </div>

                                </div>

                                <!-- OWNER / PROPERTY + FLOOR -->
                                <div class="collection-property-box">

                                    <div class="collection-property-info">

                                        <div>
                                            <strong>Owner Name :</strong>
                                            <span><?= e($owner_name) ?></span>
                                        </div>

                                        <div>
                                            <strong>Mobile Number :</strong>
                                            <span><?= e($owner_mobile ?: 'N/A') ?></span>
                                        </div>

                                        <div>
                                            <strong>Area of plot :</strong>
                                            <span><?= e(first_value(
                                                        $property,
                                                        ['area_of_plot', 'plot_area', 'total_area'],
                                                        'N/A'
                                                    )) ?></span>
                                        </div>

                                        <div>
                                            <strong>Address :</strong>
                                            <span><?= e(trim(
                                                        (string)first_value($property, ['addr1', 'address_line1'], '') .
                                                            ' ' .
                                                            (string)first_value($property, ['addr2', 'address_line2'], '')
                                                    ) ?: 'N/A') ?></span>
                                        </div>

                                        <div>
                                            <strong>Ward :</strong>
                                            <span><?= e($ward_display_value) ?></span>
                                        </div>

                                        <div>
                                            <strong>Zone :</strong>
                                            <span><?= e($zone_display_value) ?></span>
                                        </div>

                                        <div>
                                            <strong>City/Village :</strong>
                                            <span><?= e(first_value(
                                                        $property,
                                                        ['city_village', 'city', 'village'],
                                                        'N/A'
                                                    )) ?></span>
                                        </div>

                                        <div>
                                            <strong>Pincode :</strong>
                                            <span><?= e(first_value($property, ['pincode'], 'N/A')) ?></span>
                                        </div>

                                    </div>

                                    <div class="collection-floor-box">

                                        <div class="collection-mini-title">
                                            Floor Details
                                        </div>

                                        <div class="table-wrap">
                                            <table class="collection-floor-table">

                                                <thead>
                                                    <tr>
                                                        <th>Floor Name</th>
                                                        <th>Build Up Area (Sqft.)</th>
                                                        <th>Area Of Plot / Use</th>
                                                    </tr>
                                                </thead>

                                                <tbody>

                                                    <?php if ($floors): ?>

                                                        <?php foreach ($floors as $floor): ?>

                                                            <tr>
                                                                <td>
                                                                    <?= e(first_value(
                                                                        $floor,
                                                                        ['floor_no', 'floor_name'],
                                                                        'N/A'
                                                                    )) ?>
                                                                </td>

                                                                <td>
                                                                    <?= e(first_value(
                                                                        $floor,
                                                                        ['build_up_area', 'built_up_area', 'area'],
                                                                        'N/A'
                                                                    )) ?>
                                                                </td>

                                                                <td>
                                                                    <?= e(first_value(
                                                                        $floor,
                                                                        ['usage_type', 'residential_type', 'property_type'],
                                                                        'N/A'
                                                                    )) ?>
                                                                </td>
                                                            </tr>

                                                        <?php endforeach; ?>

                                                    <?php else: ?>

                                                        <tr>
                                                            <td colspan="3">N/A</td>
                                                        </tr>

                                                    <?php endif; ?>

                                                </tbody>

                                            </table>
                                        </div>

                                    </div>

                                </div>

                                <div class="collection-received-line">
                                    A sum of
                                    <strong>Rs- <?= money($order_total) ?></strong>
                                    has been received with thanks from
                                    <strong><?= e($owner_name) ?></strong>
                                    towards the payment of tax.
                                </div>

                                <!-- TAX DETAILS -->
                                <div class="collection-section-heading">
                                    Tax / Demand Details
                                </div>

                                <div class="table-wrap">
                                    <table class="collection-tax-table">

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

                                            <?php
                                            $order_year_no = 0;

                                            foreach ($successful_payment_rows as $payment_year_row):

                                                if (
                                                    (int)($payment_year_row['payment_order_id'] ?? 0)
                                                    !== (int)$order['payment_order_id']
                                                ) {
                                                    continue;
                                                }

                                                $order_year_no++;

                                                $year_tax = (float)($payment_year_row['year_tax_amount'] ?? 0);

                                                $payment_fy = normalize_financial_year(
                                                    $payment_year_row['financial_year'] ?? ''
                                                );

                                                $demand_row = null;

                                                foreach ($arv_rows as $demand_row_candidate) {
                                                    $demand_fy = normalize_financial_year(
                                                        $demand_row_candidate['_display_financial_year'] ?? ''
                                                    );

                                                    if (
                                                        $payment_fy !== '' &&
                                                        $demand_fy === $payment_fy
                                                    ) {
                                                        $demand_row = $demand_row_candidate;
                                                        break;
                                                    }
                                                }

                                                $rebate = $demand_row
                                                    ? demand_number($demand_row, ['rebate'], 0)
                                                    : 0;

                                                $penalty = $demand_row
                                                    ? demand_number(
                                                        $demand_row,
                                                        ['penalty', 'interest', 'interest_penalty'],
                                                        0
                                                    )
                                                    : 0;
                                            ?>

                                                <tr>
                                                    <td><?= $order_year_no ?></td>
                                                    <td><?= e($payment_fy ?: 'N/A') ?></td>
                                                    <td><?= money($year_tax) ?></td>
                                                    <td><?= money($rebate) ?></td>
                                                    <td><?= money($penalty) ?></td>
                                                    <td><?= money($year_tax) ?></td>
                                                </tr>

                                            <?php endforeach; ?>

                                            <?php if ($order_year_no === 0): ?>

                                                <tr>
                                                    <td>1</td>
                                                    <td><?= e($year_text) ?></td>
                                                    <td><?= money($order['year_tax_total']) ?></td>
                                                    <td>0.00</td>
                                                    <td>0.00</td>
                                                    <td><?= money($order['year_tax_total']) ?></td>
                                                </tr>

                                            <?php endif; ?>

                                        </tbody>

                                    </table>
                                </div>

                                <!-- PAYMENT BREAKUP -->
                                <div class="collection-section-heading">
                                    Payment Amount Details
                                </div>

                                <div class="table-wrap">

                                    <table class="collection-full-amount-table">

                                        <tbody>

                                            <tr>
                                                <th>Property Tax / Selected Year Tax</th>
                                                <td>₹ <?= money($order['year_tax_total']) ?></td>
                                            </tr>

                                            <tr>
                                                <th>Form Fee</th>
                                                <td>₹ <?= money($order['form_fee']) ?></td>
                                            </tr>

                                            <tr>
                                                <th>Other Amount</th>
                                                <td>₹ <?= money($order['other_amount']) ?></td>
                                            </tr>

                                            <tr>
                                                <th>Boring Charge</th>
                                                <td>₹ <?= money($order['boring_charge']) ?></td>
                                            </tr>

                                            <tr>
                                                <th>Advance Received</th>
                                                <td>- ₹ <?= money($order['advance_received']) ?></td>
                                            </tr>

                                            <tr class="collection-grand-total">
                                                <th>Total Paid</th>
                                                <td>₹ <?= money($order_total) ?></td>
                                            </tr>

                                        </tbody>

                                    </table>

                                </div>

                                <!-- PAYEE / PAYMENT -->
                                <div class="collection-payment-info-grid">

                                    <div>
                                        <span>Payee Name</span>
                                        <strong><?= e($order['payer_name'] ?: $owner_name) ?></strong>
                                    </div>

                                    <div>
                                        <span>Payment Made At</span>
                                        <strong><?= e($order_mode) ?></strong>
                                    </div>

                                    <div>
                                        <span>Payment Date</span>
                                        <strong><?= e($display_payment_datetime($order)) ?></strong>
                                    </div>

                                    <div>
                                        <span>Mode Of Payment</span>
                                        <strong><?= e($order_mode) ?></strong>
                                    </div>

                                </div>

                                <?php if (
                                    !empty($order['gateway_order_id']) ||
                                    !empty($order['gateway_payment_id']) ||
                                    !empty($order['payment_gateway'])
                                ): ?>

                                    <div class="collection-section-heading">
                                        Payment Gateway Details
                                    </div>

                                    <div class="collection-gateway-grid">

                                        <?php if (!empty($order['payment_gateway'])): ?>
                                            <div>
                                                <span>Gateway</span>
                                                <strong><?= e($order['payment_gateway']) ?></strong>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($order['gateway_order_id'])): ?>
                                            <div>
                                                <span>Gateway Order ID</span>
                                                <strong><?= e($order['gateway_order_id']) ?></strong>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($order['gateway_payment_id'])): ?>
                                            <div>
                                                <span>Gateway Payment ID</span>
                                                <strong><?= e($order['gateway_payment_id']) ?></strong>
                                            </div>
                                        <?php endif; ?>

                                    </div>

                                <?php endif; ?>

                                <div class="collection-receipt-footer">
                                    <div>Generated on <?= e($order_date) ?></div>
                                    <div><strong>Assessment ID:</strong> <?= $property_id ?></div>
                                    <div><strong>Receipt No:</strong> <?= e($order_receipt) ?></div>
                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

                <div class="collection-all-total">
                    <span>Total Collection</span>
                    <strong>₹ <?= money($collection_grand_total) ?></strong>
                </div>

            <?php else: ?>

                <div class="empty-box">

                    <i class="fa fa-inr text-2xl"></i>

                    <div class="font-bold mt-2">
                        No completed collection found.
                    </div>

                    <div class="text-xs mt-1">
                        A collection appears here only after its payment order
                        is marked paid / success / successful / completed.
                    </div>

                </div>

            <?php endif; ?>

        </section>
    <?php else: ?>

        <section class="minimal-card p-8 text-center">
            <div class="text-slate-400 text-3xl">
                <i class="fa fa-info-circle"></i>
            </div>

            <h2 class="text-lg font-bold text-slate-800 mt-2">
                <?= e(strtoupper(str_replace('-', ' ', $active_tab))) ?>
            </h2>

            <p class="text-sm text-slate-500 mt-2">
                This section is available in the property view.
            </p>

            <div class="mt-5 flex justify-center gap-2 flex-wrap">
                <a href="<?= e(tab_url($property_id, 'basic')) ?>"
                    class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-bold">
                    Basic
                </a>

                <a href="<?= e(tab_url($property_id, 'demand')) ?>"
                    class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-bold">
                    Demand
                </a>

                <a href="<?= e(tab_url($property_id, 'collections')) ?>"
                    class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm font-bold">
                    Collections
                </a>
            </div>
        </section>

    <?php endif; ?>

</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        /* ---------------------------------------------------------
           COLLECTIONS: COMPLETE RECEIPT SHOW / HIDE
        --------------------------------------------------------- */
        const collectionButtons = Array.from(
            document.querySelectorAll('[data-collection-record-toggle]')
        );

        collectionButtons.forEach(function(button) {

            button.addEventListener('click', function() {

                const targetId = this.getAttribute(
                    'data-collection-record-toggle'
                );

                const target = document.getElementById(targetId);

                if (!target) return;

                const wrapper = this.closest('[data-collection-record]');
                if (!wrapper) return;

                const wasOpen = wrapper.classList.contains('open');

                document.querySelectorAll('[data-collection-record]').forEach(function(item) {
                    item.classList.remove('open');

                    const head = item.querySelector(
                        '[data-collection-record-toggle]'
                    );

                    if (head) {
                        head.setAttribute('aria-expanded', 'false');
                    }
                });

                if (!wasOpen) {
                    wrapper.classList.add('open');
                    this.setAttribute('aria-expanded', 'true');
                }
            });
        });

        /* Demand year rows: one detail row open at a time */
        const demandYearRows = Array.from(
            document.querySelectorAll('.demand-year-row')
        );

        demandYearRows.forEach(function(row) {

            row.addEventListener('click', function() {

                const targetId = this.getAttribute('data-demand-toggle');
                const target = document.getElementById(targetId);

                if (!target) return;

                const wasOpen = this.classList.contains('active');

                document.querySelectorAll('.demand-year-row').forEach(function(item) {
                    item.classList.remove('active');
                    item.setAttribute('aria-expanded', 'false');
                });

                document.querySelectorAll('.demand-year-details').forEach(function(item) {
                    item.classList.remove('active');
                });

                if (!wasOpen) {
                    this.classList.add('active');
                    this.setAttribute('aria-expanded', 'true');
                    target.classList.add('active');

                    if (window.innerWidth <= 768) {
                        setTimeout(function() {
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }, 80);
                    }
                }
            });
        });

        /* Pay Now modal */
        const openBtn = document.getElementById('openPayModalBottom') || document.getElementById('openPayModal');
        const modal = document.getElementById('payYearsModal');
        const closeBtn = document.getElementById('closePayModal');
        const selectAll = document.getElementById('selectAllPayYears');
        const checks = Array.from(document.querySelectorAll('.pay-year-checkbox'));
        const totalEl = document.getElementById('selectedPayTotal');
        const payBtn = document.getElementById('modalPayNow');

        function updatePayTotal() {

            let selectedTax = 0;
            let selected = 0;

            checks.forEach(function(check) {
                if (check.checked) {
                    selected++;
                    selectedTax += parseFloat(
                        check.getAttribute('data-amount') || '0'
                    ) || 0;
                }
            });

            /*
             * Common charges are applied ONCE when at least one
             * financial year is selected.
             *
             * Example:
             * Tax = 955.48
             * Form Fee = 5.00
             * Final Payable = 960.48
             */
            const formFee = parseFloat(
                document.getElementById('displayFormFee')?.value || '0'
            ) || 0;

            const otherAmount = parseFloat(
                document.getElementById('displayOtherAmount')?.value || '0'
            ) || 0;

            const boringCharge = parseFloat(
                document.getElementById('displayBoringCharge')?.value || '0'
            ) || 0;

            const advanceReceived = parseFloat(
                document.getElementById('displayAdvanceReceived')?.value || '0'
            ) || 0;

            const payableFormFee = selected > 0 ? formFee : 0;
            const payableOther = selected > 0 ? otherAmount : 0;
            const payableBoring = selected > 0 ? boringCharge : 0;
            const payableAdvance = selected > 0 ? advanceReceived : 0;

            let payable =
                selectedTax +
                payableFormFee +
                payableOther +
                payableBoring -
                payableAdvance;

            if (payable < 0) {
                payable = 0;
            }

            const taxEl = document.getElementById('selectedPayTax');
            const feeEl = document.getElementById('selectedPayFormFee');
            const otherEl = document.getElementById('selectedPayOther');
            const boringEl = document.getElementById('selectedPayBoring');
            const advanceEl = document.getElementById('selectedPayAdvance');

            if (taxEl) {
                taxEl.textContent = '₹' + selectedTax.toFixed(2);
            }

            if (feeEl) {
                feeEl.textContent = '₹' + payableFormFee.toFixed(2);
            }

            if (otherEl) {
                otherEl.textContent = '₹' + payableOther.toFixed(2);
            }

            if (boringEl) {
                boringEl.textContent = '₹' + payableBoring.toFixed(2);
            }

            if (advanceEl) {
                advanceEl.textContent = '- ₹' + payableAdvance.toFixed(2);
            }

            if (totalEl) {
                totalEl.textContent = '₹' + payable.toFixed(2);
            }

            if (payBtn) {
                payBtn.disabled = selected === 0;
            }

            if (selectAll) {
                selectAll.checked =
                    checks.length > 0 &&
                    selected === checks.length;
            }
        }

        function openModal() {
            if (!modal) return;
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            updatePayTotal();
        }

        function closeModal() {
            if (!modal) return;
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
        }

        if (openBtn) openBtn.addEventListener('click', openModal);
        if (closeBtn) closeBtn.addEventListener('click', closeModal);

        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeModal();
            });
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checks.forEach(function(check) {
                    check.checked = selectAll.checked;
                });
                updatePayTotal();
            });
        }

        checks.forEach(function(check) {
            check.addEventListener('change', updatePayTotal);
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    });
</script>