<?php
session_start();
include "include/header.php";

/*
|--------------------------------------------------------------------------
| DEMANDS PAGE
|--------------------------------------------------------------------------
| Uses the same Khalilabad admin theme as View Assessments.
| Main demand values are read from property_arv_details and linked with
| assessments + assessment_owners. Final Total Demand is pad.total_tax.
| Collections are read only from successful property payment orders.
|
| Columns:
| Sr No, Property No, Arrear Demand, Current Demand, Total Demand,
| Total Collection (Collected Demand + Penalty), Pending Demand,
| Zone, Ward, Owner Name, Mobile Number, Old Holding Number,
| Address, Applied Date, Type, Actions
|--------------------------------------------------------------------------
*/

if (!isset($conn) || !$conn) {
    die("Database connection not available.");
}

function e($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function money($value)
{
    return number_format((float)$value, 2);
}

/* ---------------------------------------------------------
   DATABASE COMPATIBILITY HELPERS
--------------------------------------------------------- */
function table_exists($conn, $table)
{
    $table = $conn->real_escape_string($table);
    $result = $conn->query("SHOW TABLES LIKE '{$table}'");

    return $result && $result->num_rows > 0;
}

function column_exists($conn, $table, $column)
{
    if (!table_exists($conn, $table)) {
        return false;
    }

    $table  = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);

    $result = $conn->query(
        "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'"
    );

    return $result && $result->num_rows > 0;
}

/* ---------------------------------------------------------
   FILTERS
--------------------------------------------------------- */
$zone_id          = trim($_GET['zone_id'] ?? '');
$ward             = trim($_GET['ward'] ?? '');
$property_type    = trim($_GET['property_type'] ?? '');
$property_no      = trim($_GET['property_no'] ?? '');
$application_no   = trim($_GET['application_no'] ?? '');
$owner_name       = trim($_GET['owner_name'] ?? '');
$mobile_no        = trim($_GET['mobile_no'] ?? '');
$old_holding      = trim($_GET['old_holding'] ?? '');
$date_from        = trim($_GET['date_from'] ?? '');
$date_to          = trim($_GET['date_to'] ?? '');
$non_zero         = trim($_GET['non_zero'] ?? '');
$tab              = strtolower(trim($_GET['tab'] ?? 'active'));

if (!in_array($tab, ['active', 'pending', 'rejected'], true)) {
    $tab = 'active';
}

/* ---------------------------------------------------------
   PAGINATION
--------------------------------------------------------- */
$records_per_page = max(1, min(100, (int)($_GET['per_page'] ?? 10)));
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $records_per_page;

/* ---------------------------------------------------------
   WHERE
--------------------------------------------------------- */
$where = ["a.is_deleted = 0"];
$params = [];
$types = '';

if ($zone_id !== '') {
    $where[] = "a.zone_id = ?";
    $params[] = $zone_id;
    $types .= 'i';
}

if ($ward !== '') {
    $where[] = "a.ward = ?";
    $params[] = $ward;
    $types .= 's';
}

if ($property_type !== '') {
    $where[] = "a.property_type = ?";
    $params[] = $property_type;
    $types .= 's';
}

if ($property_no !== '') {
    $where[] = "a.property_id LIKE ?";
    $params[] = "%{$property_no}%";
    $types .= 's';
}

if ($application_no !== '') {
    $where[] = "CAST(a.id AS CHAR) LIKE ?";
    $params[] = "%{$application_no}%";
    $types .= 's';
}

if ($owner_name !== '') {
    $where[] = "o.owner_name LIKE ?";
    $params[] = "%{$owner_name}%";
    $types .= 's';
}

if ($mobile_no !== '') {
    $where[] = "o.mobile LIKE ?";
    $params[] = "%{$mobile_no}%";
    $types .= 's';
}

if ($old_holding !== '') {
    $where[] = "a.old_holding LIKE ?";
    $params[] = "%{$old_holding}%";
    $types .= 's';
}

if ($date_from !== '') {
    $where[] = "DATE(a.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to !== '') {
    $where[] = "DATE(a.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

/*
 * Demand is calculated from current/arrear tax fields.
 * Non-Zero = demand greater than zero.
 */
if ($non_zero === 'yes') {
    /* Demand screen ka non-zero filter final saved demand (total_tax) par chalega. */
    $where[] = "COALESCE(pad.total_tax,0) > 0";
}

/*
 * These tabs are application/demand state tabs.
 * Active = generated demand and not rejected assessment.
 * Pending/Rejected refer to assessment verification state.
 */
if ($tab === 'pending') {
    $where[] = "LOWER(COALESCE(a.verification_status,'pending')) = 'pending'";
} elseif ($tab === 'rejected') {
    $where[] = "LOWER(COALESCE(a.verification_status,'')) IN ('reject','rejected')";
}

/* ---------------------------------------------------------
   SQL
--------------------------------------------------------- */
$whereSql = 'WHERE ' . implode(' AND ', $where);

$baseSelect = "
    FROM assessments a
    LEFT JOIN assessment_owners o
        ON o.assessment_id = a.id
        AND o.is_deleted = 0
    LEFT JOIN property_arv_details pad
        ON pad.id = (
            SELECT MAX(p2.id)
            FROM property_arv_details p2
            WHERE p2.assessment_id = a.id
        )
    {$whereSql}
";

/* COUNT */
$countSql = "SELECT COUNT(DISTINCT a.id) AS total_records {$baseSelect}";
$countStmt = $conn->prepare($countSql);

if (!$countStmt) {
    die("Count query error: " . e($conn->error));
}

if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();
$total_records = (int)($countRow['total_records'] ?? 0);
$total_pages = max(1, (int)ceil($total_records / $records_per_page));

/* ---------------------------------------------------------
   PAYMENT TABLE COMPATIBILITY
   ---------------------------------------------------------
   Some existing databases do not have property_payment_years yet.
   The Demand page must still load in that case.

   If payment_years exists, collection is read year-wise from it.
   Otherwise, successful payment orders are used as the collection
   source (tax_amount). Pending/failed orders are never counted.
--------------------------------------------------------- */
$hasPaymentOrders = table_exists($conn, 'property_payment_orders');
$hasPaymentYears  = table_exists($conn, 'property_payment_years');

$collectionDemandSql = "0";
$collectionPenaltySql = "0";

if ($hasPaymentOrders) {
    if ($hasPaymentYears) {
        $collectionDemandSql = "COALESCE((
            SELECT SUM(COALESCE(py.total_tax,0))
            FROM property_payment_years py
            INNER JOIN property_payment_orders po
                ON po.id = py.payment_order_id
            WHERE po.assessment_id = a.id
              AND LOWER(TRIM(po.status))
                  IN ('paid','success','successful','completed','complete')
        ),0)";

        $collectionPenaltySql = "COALESCE((
            SELECT SUM(COALESCE(py.penalty,0))
            FROM property_payment_years py
            INNER JOIN property_payment_orders po
                ON po.id = py.payment_order_id
            WHERE po.assessment_id = a.id
              AND LOWER(TRIM(po.status))
                  IN ('paid','success','successful','completed','complete')
        ),0)";
    } else {
        /* Fallback for the current DB where property_payment_years is absent. */
        $collectionDemandSql = "COALESCE((
            SELECT SUM(COALESCE(po.tax_amount,0))
            FROM property_payment_orders po
            WHERE po.assessment_id = a.id
              AND LOWER(TRIM(po.status))
                  IN ('paid','success','successful','completed','complete')
        ),0)";

        $collectionPenaltySql = "0";
    }
}

/* DATA */
$dataSql = "
    SELECT
        a.id,
        a.property_id,
        a.new_holding,
        a.old_holding,
        a.ward,
        a.zone_id,
        a.property_type,
        a.addr1,
        a.addr2,
        a.created_at,
        a.verification_status,

        GROUP_CONCAT(
            DISTINCT o.owner_name
            ORDER BY o.id
            SEPARATOR ', '
        ) AS owner_name,

        GROUP_CONCAT(
            DISTINCT o.mobile
            ORDER BY o.id
            SEPARATOR ', '
        ) AS mobile_number,

        COALESCE(pad.house_tax_arrear,0)
          + COALESCE(pad.water_tax_arrear,0)
          + COALESCE(pad.water_fee_arrear,0)
          + COALESCE(pad.sewer_tax_arrear,0)
          + COALESCE(pad.other_tax_arrear,0) AS arrear_demand,

        COALESCE(pad.house_tax_current,0)
          + COALESCE(pad.water_tax_current,0)
          + COALESCE(pad.water_fee_current,0)
          + COALESCE(pad.sewer_tax_current,0)
          + COALESCE(pad.other_tax_current,0) AS current_demand,

        COALESCE(pad.house_tax_interest,0)
          + COALESCE(pad.water_tax_interest,0)
          + COALESCE(pad.water_fee_interest,0)
          + COALESCE(pad.sewer_tax_interest,0)
          + COALESCE(pad.other_tax_interest,0) AS penalty,

        /* Final demand amount saved in property_arv_details. */
        COALESCE(pad.total_tax,0) AS arv_total_tax,

        /* Only SUCCESSFUL payment orders count as collection. */
        {$collectionDemandSql} AS collected_demand,

        {$collectionPenaltySql} AS collected_penalty

    {$baseSelect}
    GROUP BY a.id
    ORDER BY a.id DESC
    LIMIT ?, ?
";

/* LIMIT values are integers appended to binding */
$dataParams = $params;
$dataTypes = $types . 'ii';
$dataParams[] = $offset;
$dataParams[] = $records_per_page;

$dataStmt = $conn->prepare($dataSql);

if (!$dataStmt) {
    die("Data query error: " . e($conn->error));
}

$dataStmt->bind_param($dataTypes, ...$dataParams);
$dataStmt->execute();
$result = $dataStmt->get_result();

/* ---------------------------------------------------------
   DROPDOWN DATA
--------------------------------------------------------- */
$zones = [];
$wards = [];
$propertyTypes = [];

$zResult = $conn->query("
    SELECT DISTINCT zone_id
    FROM assessments
    WHERE is_deleted = 0
      AND zone_id IS NOT NULL
      AND zone_id <> ''
    ORDER BY zone_id
");
if ($zResult) {
    while ($r = $zResult->fetch_assoc()) {
        $zones[] = $r['zone_id'];
    }
}

$wResult = $conn->query("
    SELECT DISTINCT ward
    FROM assessments
    WHERE is_deleted = 0
      AND ward IS NOT NULL
      AND ward <> ''
    ORDER BY ward
");
if ($wResult) {
    while ($r = $wResult->fetch_assoc()) {
        $wards[] = $r['ward'];
    }
}

$tResult = $conn->query("
    SELECT DISTINCT property_type
    FROM assessments
    WHERE is_deleted = 0
      AND property_type IS NOT NULL
      AND property_type <> ''
    ORDER BY property_type
");
if ($tResult) {
    while ($r = $tResult->fetch_assoc()) {
        $propertyTypes[] = $r['property_type'];
    }
}

/* Pagination query */
$query = $_GET;
unset($query['page']);
$queryString = http_build_query($query);
?>

<style>
    .demand-card {
        background: rgba(255, 255, 255, .86);
        border: 1px solid rgba(226, 232, 240, .95);
        border-radius: 18px;
        box-shadow: 0 8px 28px rgba(15, 23, 42, .05);
    }

    .demand-filter-input,
    .demand-filter-select {
        width: 100%;
        height: 42px;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        background: #fff;
        padding: 0 12px;
        color: #334155;
        font-size: 13px;
        outline: none;
    }

    .demand-filter-input:focus,
    .demand-filter-select:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 2px rgba(99, 102, 241, .10);
    }

    .demand-table-wrap {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .demand-table {
        width: max-content;
        min-width: 1700px;
        border-collapse: separate;
        border-spacing: 0;
    }

    .demand-table th {
        background: #f8fafc;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .02em;
        white-space: nowrap;
        padding: 13px 12px;
        border-bottom: 1px solid #e2e8f0;
    }

    .demand-table td {
        color: #334155;
        font-size: 13px;
        white-space: nowrap;
        padding: 13px 12px;
        border-bottom: 1px solid #eef2f7;
        background: #fff;
    }

    .demand-table tbody tr:hover td {
        background: #f8fafc;
    }

    .demand-table th:first-child,
    .demand-table td:first-child {
        width: 60px;
        text-align: center;
    }

    .demand-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
    }

    .demand-action {
        width: 32px;
        height: 32px;
        min-width: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        transition: .18s ease;
    }

    .demand-action:hover {
        transform: translateY(-1px);
    }

    .demand-tabs a {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 15px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        transition: .18s ease;
    }

    .demand-tabs a.active {
        background: #1e293b;
        color: #fff;
    }

    .demand-tabs a.pending {
        background: #fef3c7;
        color: #b45309;
    }

    .demand-tabs a.rejected {
        background: #fee2e2;
        color: #b91c1c;
    }

    .demand-tabs a:not(.active):hover {
        filter: brightness(.97);
    }

    @media(max-width:900px) {
        main {
            padding: 12px !important;
        }

        .demand-card {
            border-radius: 14px;
        }

        .demand-table {
            min-width: 1650px;
        }
    }

    @media(max-width:640px) {
        main {
            padding: 8px !important;
        }

        .demand-filter-grid {
            grid-template-columns: 1fr !important;
        }

        .demand-header {
            flex-direction: column !important;
            align-items: flex-start !important;
        }

        .demand-tabs {
            width: 100%;
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 2px;
        }

        .demand-table {
            min-width: 1650px;
        }

        .demand-table th,
        .demand-table td {
            padding: 10px 9px;
            font-size: 11px;
        }
    }

    /* Demand action icons - matched to supplied reference */
    .demand-action {
        width: 32px !important;
        height: 32px !important;
        min-width: 32px !important;
        max-width: 32px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 9999px !important;
        border: 0 !important;
        font-size: 14px !important;
        line-height: 1 !important;
        text-decoration: none !important;
        transition: transform .18s ease, opacity .18s ease !important;
    }

    .demand-action:hover {
        transform: translateY(-1px);
    }

    .demand-eye {
        background: #fff4b8 !important;
        color: #f2ad00 !important;
    }

    .demand-download {
        background: #e7f0ff !important;
        color: #3478c5 !important;
    }

    .demand-transfer {
        background: #ffe9e9 !important;
        color: #ef3434 !important;
    }

    .demand-whatsapp {
        background: #e4f7e9 !important;
        color: #1fa64a !important;
    }

    .demand-action i {
        font-size: 14px !important;
        line-height: 1 !important;
    }
</style>

<main class="flex-1 p-6 bg-slate-50 min-h-screen overflow-y-auto">

    <div class="w-full max-w-[1500px] mx-auto space-y-5">

        <!-- PAGE HEADER -->
        <header class="glass p-4 rounded-2xl flex items-center justify-between demand-header">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="fa fa-pie-chart"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Demand List</h1>
                    <p class="text-sm text-gray-500">View and manage property tax demands</p>
                </div>
            </div>

            <div class="relative w-full md:w-64">
                <i class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input id="quickDemandSearch"
                    type="text"
                    placeholder="Quick search..."
                    class="w-full pl-10 pr-4 py-2 rounded-full glass text-sm focus:outline-none">
            </div>
        </header>

        <!-- FILTERS -->
        <section class="demand-card p-5">
            <form method="GET" class="space-y-4">

                <input type="hidden" name="tab" value="<?= e($tab) ?>">

                <div class="grid demand-filter-grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">State</label>
                        <input class="demand-filter-input bg-slate-50" value="Uttar Pradesh" readonly>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">District</label>
                        <input class="demand-filter-input bg-slate-50" value="Sant Kabir Nagar" readonly>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">City/Village</label>
                        <input class="demand-filter-input bg-slate-50" value="Khalilabad" readonly>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Zone</label>
                        <select name="zone_id" class="demand-filter-select">
                            <option value="">Select Zone</option>
                            <?php foreach ($zones as $z): ?>
                                <option value="<?= e($z) ?>" <?= ((string)$zone_id === (string)$z) ? 'selected' : '' ?>>
                                    <?= e(is_numeric($z) ? 'Zone ' . $z : $z) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Ward</label>
                        <select name="ward" class="demand-filter-select">
                            <option value="">Select Ward</option>
                            <?php foreach ($wards as $w): ?>
                                <option value="<?= e($w) ?>" <?= ((string)$ward === (string)$w) ? 'selected' : '' ?>>
                                    <?= e('Ward ' . $w) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Property Type</label>
                        <select name="property_type" class="demand-filter-select">
                            <option value="">Select Property Type</option>
                            <?php foreach ($propertyTypes as $pt): ?>
                                <option value="<?= e($pt) ?>" <?= $property_type === $pt ? 'selected' : '' ?>>
                                    <?= e($pt) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Property No</label>
                        <input name="property_no" value="<?= e($property_no) ?>" class="demand-filter-input" placeholder="Property No">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Application No</label>
                        <input name="application_no" value="<?= e($application_no) ?>" class="demand-filter-input" placeholder="Application No">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Application Date From</label>
                        <input type="date" name="date_from" value="<?= e($date_from) ?>" class="demand-filter-input">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Application Date To</label>
                        <input type="date" name="date_to" value="<?= e($date_to) ?>" class="demand-filter-input">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Owner Name</label>
                        <input name="owner_name" value="<?= e($owner_name) ?>" class="demand-filter-input" placeholder="Owner Name">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Mobile No</label>
                        <input name="mobile_no" value="<?= e($mobile_no) ?>" class="demand-filter-input" placeholder="Mobile">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Old Holding No</label>
                        <input name="old_holding" value="<?= e($old_holding) ?>" class="demand-filter-input" placeholder="Old Holding Number">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Non-Zero</label>
                        <select name="non_zero" class="demand-filter-select">
                            <option value="">Select Non-zero</option>
                            <option value="yes" <?= $non_zero === 'yes' ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>

                </div>

                <div class="flex items-center gap-2 pt-1">
                    <button type="submit"
                        class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition">
                        <i class="fa fa-search mr-1"></i> Search
                    </button>

                    <a href="demands.php"
                        class="px-5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold transition">
                        Reset
                    </a>
                </div>

            </form>
        </section>

        <!-- STATUS TABS -->
        <section class="demand-card p-4">
            <div class="demand-tabs flex items-center gap-2 overflow-x-auto">

                <?php
                $tabLinks = [
                    'active' => ['label' => 'Active', 'icon' => 'fa-check-circle'],
                    'pending' => ['label' => 'Pending', 'icon' => 'fa-clock-o'],
                    'rejected' => ['label' => 'Rejected', 'icon' => 'fa-times-circle'],
                ];

                foreach ($tabLinks as $tabKey => $tabItem):
                    $tabQuery = $_GET;
                    $tabQuery['tab'] = $tabKey;
                    unset($tabQuery['page']);
                    $tabHref = '?' . http_build_query($tabQuery);
                ?>
                    <a href="<?= e($tabHref) ?>"
                        class="<?= $tab === $tabKey ? 'active' : ($tabKey === 'pending' ? 'pending' : ($tabKey === 'rejected' ? 'rejected' : '')) ?>">
                        <i class="fa <?= $tabItem['icon'] ?>"></i>
                        <?= e($tabItem['label']) ?>
                    </a>
                <?php endforeach; ?>

            </div>
        </section>

        <!-- TABLE -->
        <section class="demand-card overflow-hidden">

            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Property Demand Records</h2>
                    <p class="text-xs text-slate-500 mt-1">
                        <?= number_format($total_records) ?> records found
                    </p>
                </div>

                <div class="flex items-center gap-2 text-xs text-slate-500">
                    Show
                    <select onchange="changePerPage(this.value)"
                        class="border border-slate-300 rounded-lg px-2 py-1 bg-white">
                        <?php foreach ([10, 25, 50, 100] as $n): ?>
                            <option value="<?= $n ?>" <?= $records_per_page === $n ? 'selected' : '' ?>><?= $n ?></option>
                        <?php endforeach; ?>
                    </select>
                    entries
                </div>
            </div>

            <div class="demand-table-wrap">
                <table class="demand-table">
                    <thead>
                        <tr>
                            <th>Sr No</th>
                            <th>Property No</th>
                            <th>Arrear Demand</th>
                            <th>Current Demand</th>
                            <th>Total Demand</th>
                            <th>Total Collection<br>(Collected Demand + Penalty)</th>
                            <th>Pending Demand</th>
                            <th>Zone</th>
                            <th>Ward</th>
                            <th>Owners(s)</th>
                            <th>Mobile Number</th>
                            <th>Old Holding Number</th>
                            <th>Address</th>
                            <th>Applied Date</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody id="demandTableBody">

                        <?php
                        $sr = $offset + 1;

                        if ($result->num_rows > 0):
                            while ($row = $result->fetch_assoc()):

                                $arrear = (float)$row['arrear_demand'];
                                $current = (float)$row['current_demand'];

                                /*
                                 * IMPORTANT:
                                 * Demand amount must be the same final amount shown
                                 * inside view-saf-calculations.php for the financial year.
                                 * Therefore we DO NOT calculate Total Demand as
                                 * arrear + current (that was producing 1184 instead of
                                 * the saved demand 1951 in the supplied screenshot).
                                 */
                                $totalDemand = (float)$row['arv_total_tax'];

                                /* Only successful payment orders are collections. */
                                $collection = min(
                                    $totalDemand,
                                    (float)$row['collected_demand']
                                );

                                $penalty = min(
                                    max(0, $totalDemand - $collection),
                                    (float)$row['collected_penalty']
                                );

                                $totalCollection = $collection + $penalty;
                                $pending = max(0, $totalDemand - $totalCollection);

                                $address = trim(
                                    ($row['addr1'] ?? '') .
                                        (!empty($row['addr2']) ? ', ' . $row['addr2'] : '')
                                );

                                $status = strtolower(trim($row['verification_status'] ?? 'pending'));
                        ?>

                                <tr class="demand-row">

                                    <td><?= $sr++ ?></td>

                                    <td class="font-semibold text-slate-800">
                                        <?= e($row['property_id'] ?: $row['new_holding']) ?>
                                    </td>

                                    <td><?= money($arrear) ?></td>

                                    <td><?= money($current) ?></td>

                                    <td class="font-semibold"><?= money($totalDemand) ?></td>

                                    <td class="font-semibold text-emerald-700">
                                        (<?= money($collection) ?> + <?= money($penalty) ?>)
                                        = <?= money($totalCollection) ?>
                                    </td>

                                    <td>
                                        <span class="demand-badge <?= $pending > 0 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' ?>">
                                            <?= money($pending) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= e(is_numeric($row['zone_id']) ? 'Zone ' . $row['zone_id'] : $row['zone_id']) ?>
                                    </td>

                                    <td><?= e($row['ward']) ?></td>

                                    <td class="font-medium">
                                        <?= e($row['owner_name'] ?: '-') ?>
                                    </td>

                                    <td><?= e($row['mobile_number'] ?: '-') ?></td>

                                    <td><?= e($row['old_holding'] ?: '-') ?></td>

                                    <td title="<?= e($address) ?>">
                                        <?= e($address ?: '-') ?>
                                    </td>

                                    <td><?= !empty($row['created_at']) ? e(date('d/m/Y', strtotime($row['created_at']))) : '-' ?></td>

                                    <td><?= e($row['property_type'] ?: '-') ?></td>

                                    <td>
                                        <div class="flex items-center gap-1.5 whitespace-nowrap">

                                            <!-- View -->
                                            <a href="view-saf-calculations.php?id=<?= (int)$row['id'] ?>"
                                                class="demand-action demand-eye"
                                                title="View Assessment">
                                                <i class="fa fa-eye"></i>
                                            </a>

                                            <!-- Download -->
                                            <a href="view-assesment.php?id=<?= (int)$row['id'] ?>"
                                                class="demand-action demand-download"
                                                title="Download Demand">
                                                <i class="fa fa-download"></i>
                                            </a>

                                            <!-- Transfer / Collection -->
                                            <a href="view-assesment.php?id=<?= (int)$row['id'] ?>"
                                                class="demand-action demand-transfer"
                                                title="Collection / Transfer">
                                                <i class="fa fa-retweet"></i>
                                            </a>

                                            <!-- WhatsApp -->
                                            <?php
                                            $mobile = preg_replace('/\D+/', '', (string)($row['mobile_number'] ?? ''));
                                            if (strlen($mobile) === 10) {
                                                $whatsappNumber = '91' . $mobile;
                                            } else {
                                                $whatsappNumber = $mobile;
                                            }

                                            $whatsappText = rawurlencode(
                                                'Property No: ' . ($row['property_id'] ?? '') .
                                                    ' | Demand: ₹' . money($totalDemand) .
                                                    ' | Pending: ₹' . money($pending)
                                            );
                                            ?>
                                            <?php if ($whatsappNumber !== ''): ?>
                                                <a href="https://wa.me/<?= e($whatsappNumber) ?>?text=<?= $whatsappText ?>"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="demand-action demand-whatsapp"
                                                    title="Send on WhatsApp">
                                                    <i class="fa fa-whatsapp"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="demand-action demand-whatsapp opacity-40"
                                                    title="Mobile number not available">
                                                    <i class="fa fa-whatsapp"></i>
                                                </span>
                                            <?php endif; ?>

                                        </div>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="16" class="text-center py-12 text-slate-500">
                                    <i class="fa fa-folder-open-o text-2xl mb-2"></i>
                                    <div class="font-semibold">No demand records found</div>
                                    <div class="text-xs mt-1">Try changing the filters.</div>
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="px-5 py-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">

                <div class="text-sm text-slate-500">
                    <?php
                    $from = $total_records > 0 ? $offset + 1 : 0;
                    $to = min($offset + $records_per_page, $total_records);
                    ?>
                    Showing <?= number_format($from) ?> to <?= number_format($to) ?>
                    of <?= number_format($total_records) ?> entries
                </div>

                <div class="flex items-center gap-1">

                    <?php if ($page > 1): ?>
                        <a class="px-3 py-2 rounded-lg border border-slate-200 text-sm hover:bg-slate-50"
                            href="?<?= e($queryString) ?>&page=<?= $page - 1 ?>">
                            Previous
                        </a>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($total_pages, $page + 2);

                    for ($p = $startPage; $p <= $endPage; $p++):
                        $pageHref = '?' . ($queryString ? $queryString . '&' : '') . 'page=' . $p;
                    ?>
                        <a href="<?= e($pageHref) ?>"
                            class="px-3 py-2 rounded-lg text-sm <?= $p === $page ? 'bg-indigo-600 text-white' : 'border border-slate-200 text-slate-700 hover:bg-slate-50' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a class="px-3 py-2 rounded-lg border border-slate-200 text-sm hover:bg-slate-50"
                            href="?<?= e($queryString) ?>&page=<?= $page + 1 ?>">
                            Next
                        </a>
                    <?php endif; ?>

                </div>
            </div>

        </section>

    </div>
</main>

<script>
    function changePerPage(value) {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', value);
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }

    /* Quick table search - does not change database filters */
    document.getElementById('quickDemandSearch')?.addEventListener('input', function() {
        const term = this.value.toLowerCase().trim();

        document.querySelectorAll('.demand-row').forEach(function(row) {
            row.style.display = row.innerText.toLowerCase().includes(term) ? '' : 'none';
        });
    });
</script>

<?php include "include/footer.php"; ?>