<?php
session_start();

require_once "db.php";

/*
|--------------------------------------------------------------------------
| UP Consumer List
|--------------------------------------------------------------------------
| New consumer/property search page for Nagar Palika Parishad Khalilabad.
| Logo theme:
|   Navy  : #061A3A
|   Orange: #F28C00
|--------------------------------------------------------------------------
*/

// Safe helper
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// ---------------------------------------------------------------------
// AJAX: payment history for Demand modal
// ---------------------------------------------------------------------
if (isset($_GET['ajax']) && $_GET['ajax'] === 'payments') {
    header('Content-Type: application/json; charset=utf-8');

    $assessment_id_ajax = (int)($_GET['assessment_id'] ?? 0);

    if ($assessment_id_ajax <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid assessment ID.'
        ]);
        exit;
    }

    $payments = [];

    $sql_payments = "
        SELECT
            po.id,
            po.order_number,
            po.assessment_id,
            po.property_no,
            po.payer_name,
            po.payment_made_at,
            po.payment_mode,
            po.tax_amount,
            po.form_fee,
            po.other_amount,
            po.boring_charge,
            po.advance_received,
            po.total_amount,
            po.status,
            po.payment_status,
            po.created_at,
            GROUP_CONCAT(DISTINCT py.financial_year ORDER BY py.financial_year SEPARATOR ', ') AS financial_years
        FROM property_payment_orders po
        LEFT JOIN property_payment_years py
            ON py.payment_order_id = po.id
        WHERE po.assessment_id = ?
        GROUP BY
            po.id,
            po.order_number,
            po.assessment_id,
            po.property_no,
            po.payer_name,
            po.payment_made_at,
            po.payment_mode,
            po.tax_amount,
            po.form_fee,
            po.other_amount,
            po.boring_charge,
            po.advance_received,
            po.total_amount,
            po.status,
            po.payment_status,
            po.created_at
        ORDER BY po.id DESC
    ";

    $stmt_payments = $conn->prepare($sql_payments);

    if (!$stmt_payments) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Unable to load payment list: ' . $conn->error
        ]);
        exit;
    }

    $stmt_payments->bind_param('i', $assessment_id_ajax);
    $stmt_payments->execute();
    $result_payments = $stmt_payments->get_result();

    while ($payment = $result_payments->fetch_assoc()) {
        $status_raw = trim((string)($payment['payment_status'] ?: $payment['status'] ?: 'Pending'));
        $status = strtolower($status_raw);
        $is_paid = in_array($status, ['paid', 'success', 'successful', 'completed', 'complete'], true);

        $date_source = $payment['payment_made_at'] ?: $payment['created_at'];
        $date_display = $date_source;
        if ($date_source && strtotime($date_source)) {
            $date_display = date('d/m/Y', strtotime($date_source));
        }

        $payments[] = [
            'id' => (int)$payment['id'],
            'order_number' => $payment['order_number'],
            'property_no' => $payment['property_no'],
            'payer_name' => $payment['payer_name'],
            'date' => $date_display,
            'payment_mode' => $payment['payment_mode'],
            'amount' => number_format((float)$payment['total_amount'], 2),
            'financial_years' => $payment['financial_years'] ?: '—',
            'status' => $status_raw,
            'is_paid' => $is_paid,
            'demand_url' => 'view-saf-calculations.php?id=' . $assessment_id_ajax . '&tab=demand',
            'receipt_url' => $is_paid
                ? 'payment-success.php?id=' . (int)$payment['id'] . '&autoprint=1'
                : ''
        ];
    }

    $stmt_payments->close();

    echo json_encode([
        'status' => 'success',
        'assessment_id' => $assessment_id_ajax,
        'payments' => $payments
    ]);
    exit;
}

// Search values
$mohalla      = trim($_GET['mohalla'] ?? '');
$ward         = trim($_GET['ward'] ?? '');
$house_no     = trim($_GET['house_no'] ?? '');
$address_code = trim($_GET['address_code'] ?? '');
$mobile       = trim($_GET['mobile'] ?? '');
$owner_name   = trim($_GET['owner_name'] ?? '');

// ---------------------------------------------------------------------
// Load Mohalla list
// ---------------------------------------------------------------------
$mohalla_list = [];
$ward_list = [];

$mohalla_sql = "
    SELECT
        mohalla_id,
        ward_id,
        mohalla_name
    FROM mohalla
    ORDER BY mohalla_name ASC
";

if ($mohalla_result = $conn->query($mohalla_sql)) {
    while ($m = $mohalla_result->fetch_assoc()) {
        $mohalla_list[] = $m;
    }
    $mohalla_result->free();
}

/* Load Ward list */
$ward_sql = "
    SELECT
        ward_id,
        ward_no
    FROM wards
    ORDER BY ward_no ASC
";

if ($ward_result = $conn->query($ward_sql)) {
    while ($w = $ward_result->fetch_assoc()) {
        $ward_list[] = $w;
    }
    $ward_result->free();
}

// ---------------------------------------------------------------------
// Consumer/property search
// ---------------------------------------------------------------------
// The query uses the assessment and mohalla columns supplied for this project.
// If no search is supplied, no records are loaded.
$rows = [];

$has_search = ($ward !== '' || $mohalla !== '' || $house_no !== '' || $address_code !== '' || $mobile !== '' || $owner_name !== '');

if ($has_search) {

    $conditions = ["COALESCE(a.is_deleted, 0) = 0"];
    $params = [];
    $types = "";

    // Owner/mobile search
    if ($owner_name !== '') {
        $conditions[] = "o.owner_name LIKE ?";
        $params[] = "%{$owner_name}%";
        $types .= "s";
    }

    if ($mobile !== '') {
        $conditions[] = "o.mobile LIKE ?";
        $params[] = "%{$mobile}%";
        $types .= "s";
    }

    // House / address code
    if ($house_no !== '') {
        $conditions[] = "(
            a.house_no LIKE ?
            OR a.new_holding LIKE ?
            OR a.property_id LIKE ?
        )";
        $params[] = "%{$house_no}%";
        $params[] = "%{$house_no}%";
        $params[] = "%{$house_no}%";
        $types .= "sss";
    }

    if ($address_code !== '') {
        $conditions[] = "(
            a.property_id LIKE ?
            OR a.new_holding LIKE ?
            OR a.old_pid LIKE ?
            OR a.old_holding LIKE ?
        )";
        $params[] = "%{$address_code}%";
        $params[] = "%{$address_code}%";
        $params[] = "%{$address_code}%";
        $params[] = "%{$address_code}%";
        $types .= "ssss";
    }

    if ($ward !== '') {
        $conditions[] = "a.ward_id = ?";
        $params[] = (int)$ward;
        $types .= "i";
    }

    if ($mohalla !== '') {
        $conditions[] = "a.mohalla_id = ?";
        $params[] = (int)$mohalla;
        $types .= "i";
    }

    $where = implode(" AND ", $conditions);

    $sql = "
        SELECT
            a.id,
            a.municipality_name,
            a.year_of_assessment,
            a.zone_id,
            a.ward_id,
            a.mohalla_id,
            a.property_id,
            a.ward,
            a.new_holding,
            a.previous_holding,
            a.property_status,
            a.old_holding,
            a.old_pid,
            a.property_type,
            a.road,
            a.plot_area,
            a.building_type,
            a.house_no,
            a.plot_no,
            a.khata_no,
            a.khasra_no,
            a.addr1,
            a.addr2,
            a.pincode,
            a.water_tax,
            a.verification_status,

            w.ward_no,
            m.mohalla_name,

            COALESCE(
                GROUP_CONCAT(
                    DISTINCT CASE
                        WHEN o.owner_name IS NOT NULL
                        THEN o.owner_name
                    END
                    SEPARATOR ', '
                ), ''
            ) AS owner_name,

            COALESCE(
                GROUP_CONCAT(
                    DISTINCT CASE
                        WHEN o.mobile IS NOT NULL
                        THEN o.mobile
                    END
                    SEPARATOR ', '
                ), ''
            ) AS mobile

        FROM assessments a

        LEFT JOIN wards w
            ON w.ward_id = a.ward_id

        LEFT JOIN mohalla m
            ON m.mohalla_id = a.mohalla_id

        LEFT JOIN assessment_owners o
            ON o.assessment_id = a.id

        WHERE {$where}

        GROUP BY
            a.id,
            a.municipality_name,
            a.year_of_assessment,
            a.zone_id,
            a.ward_id,
            a.mohalla_id,
            a.property_id,
            a.ward,
            a.new_holding,
            a.previous_holding,
            a.property_status,
            a.old_holding,
            a.old_pid,
            a.property_type,
            a.road,
            a.plot_area,
            a.building_type,
            a.house_no,
            a.plot_no,
            a.khata_no,
            a.khasra_no,
            a.addr1,
            a.addr2,
            a.pincode,
            a.water_tax,
            a.verification_status,
            w.ward_no,
            m.mohalla_name

        ORDER BY a.id DESC
        LIMIT 100
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        if ($stmt->execute()) {
            $result = $stmt->get_result();

            while ($r = $result->fetch_assoc()) {
                $rows[] = $r;
            }

            $result->free();
        }

        $stmt->close();
    }
}

// ---------------------------------------------------------------------
// Reset link
// ---------------------------------------------------------------------
$reset_url = strtok($_SERVER['REQUEST_URI'], '?');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>UP Consumer List | Nagar Palika Parishad Khalilabad</title>

    <link rel="icon" href="admin/img/favicon.ico">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <style>
        :root {
            --kp-navy: #061A3A;
            --kp-navy-2: #0B2857;
            --kp-orange: #F28C00;
            --kp-orange-dark: #D97700;
            --kp-orange-soft: #FFF4E5;
            --kp-gold: #F7B731;
            --kp-border: #DDE4EE;
            --kp-bg: #F4F7FB;
            --kp-text: #172033;
            --kp-muted: #68758A;
            --white: #FFFFFF;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background:
                radial-gradient(circle at 90% 0%, rgba(242, 140, 0, .08), transparent 28%),
                linear-gradient(180deg, #F7F9FC 0%, var(--kp-bg) 100%);
            color: var(--kp-text);
            font-family: Arial, Helvetica, sans-serif;
        }

        a {
            text-decoration: none;
        }

        .page {
            width: 100%;
            min-height: 100vh;
        }

        /* ------------------------------------------------------------
           Top Header
        ------------------------------------------------------------ */
        .top-header {
            background: var(--white);
            border-bottom: 1px solid var(--kp-border);
            box-shadow: 0 4px 18px rgba(6, 26, 58, .07);
        }

        .header-inner {
            max-width: 1500px;
            margin: auto;
            min-height: 86px;
            padding: 12px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 13px;
            min-width: 0;
        }

        .brand-logo {
            width: 62px;
            height: 62px;
            object-fit: contain;
            flex: 0 0 62px;
        }

        .brand-title {
            color: var(--kp-navy);
            font-size: 20px;
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1.15;
        }

        .brand-subtitle {
            margin-top: 5px;
            color: var(--kp-muted);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-btn {
            min-height: 42px;
            padding: 0 17px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 700;
            border: 1px solid transparent;
            transition: .2s ease;
            white-space: nowrap;
        }

        .btn-tax {
            color: var(--kp-orange-dark);
            background: var(--kp-orange-soft);
            border-color: #FFD39A;
        }

        .btn-tax:hover {
            background: #FFE9C7;
            transform: translateY(-1px);
        }

        .btn-citizen {
            color: #fff;
            background: var(--kp-navy);
            border-color: var(--kp-navy);
            box-shadow: 0 5px 12px rgba(6, 26, 58, .18);
        }

        .btn-citizen:hover {
            background: var(--kp-navy-2);
            transform: translateY(-1px);
        }

        /* ------------------------------------------------------------
           Main
        ------------------------------------------------------------ */
        .container {
            max-width: 1500px;
            margin: auto;
            padding: 30px;
        }

        .page-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
        }

        .heading-left h1 {
            margin: 0;
            color: var(--kp-navy);
            font-size: 26px;
            font-weight: 800;
        }

        .heading-left p {
            margin: 7px 0 0;
            color: var(--kp-muted);
            font-size: 13px;
        }

        .heading-line {
            width: 55px;
            height: 4px;
            margin-top: 10px;
            border-radius: 50px;
            background: linear-gradient(90deg, var(--kp-orange), var(--kp-gold));
        }

        /* ------------------------------------------------------------
           Notice
        ------------------------------------------------------------ */
        .notice-card {
            background: #fff;
            border: 1px solid var(--kp-border);
            border-left: 4px solid var(--kp-orange);
            border-radius: 12px;
            padding: 17px 20px;
            margin-bottom: 18px;
            box-shadow: 0 5px 18px rgba(6, 26, 58, .05);
        }

        .notice-title {
            color: var(--kp-navy);
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 9px;
        }

        .notice-list {
            margin: 0;
            padding-left: 20px;
            color: #D73535;
            font-size: 12px;
            line-height: 1.9;
        }

        /* ------------------------------------------------------------
           Search Card
        ------------------------------------------------------------ */
        .search-card {
            background: #fff;
            border: 1px solid var(--kp-border);
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 8px 25px rgba(6, 26, 58, .06);
        }

        .search-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 15px;
        }

        .field label {
            display: block;
            margin-bottom: 7px;
            color: var(--kp-navy);
            font-size: 12px;
            font-weight: 700;
        }

        .input,
        .select {
            width: 100%;
            height: 45px;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            padding: 0 13px;
            outline: none;
            color: #374151;
            background: #fff;
            font-size: 13px;
            transition: .2s ease;
        }

        .input:focus,
        .select:focus {
            border-color: var(--kp-orange);
            box-shadow: 0 0 0 3px rgba(242, 140, 0, .12);
        }

        .search-actions {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-top: 18px;
        }

        .search-btn,
        .reset-btn {
            height: 43px;
            border: 0;
            border-radius: 8px;
            padding: 0 23px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: .2s ease;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .search-btn {
            background: var(--kp-orange);
            color: #fff;
            box-shadow: 0 5px 12px rgba(242, 140, 0, .2);
        }

        .search-btn:hover {
            background: var(--kp-orange-dark);
            transform: translateY(-1px);
        }

        .reset-btn {
            background: var(--kp-navy);
            color: #fff;
        }

        .reset-btn:hover {
            background: var(--kp-navy-2);
        }

        /* ------------------------------------------------------------
           Table
        ------------------------------------------------------------ */
        .table-card {
            margin-top: 22px;
            background: #fff;
            border: 1px solid var(--kp-border);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(6, 26, 58, .06);
        }

        .table-toolbar {
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--kp-border);
        }

        .table-title {
            color: var(--kp-navy);
            font-size: 15px;
            font-weight: 800;
        }

        .record-count {
            color: var(--kp-muted);
            font-size: 12px;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 1250px;
            border-collapse: collapse;
        }

        th {
            padding: 14px 12px;
            background: var(--kp-navy);
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            white-space: nowrap;
            border-right: 1px solid rgba(255, 255, 255, .12);
        }

        th:first-child {
            border-left: 4px solid var(--kp-orange);
        }

        td {
            padding: 13px 12px;
            color: #374151;
            font-size: 12px;
            border-bottom: 1px solid #E9EEF5;
            white-space: nowrap;
        }

        tbody tr:hover {
            background: #FFF9F0;
        }

        .empty {
            padding: 55px 20px;
            text-align: center;
            color: var(--kp-muted);
        }

        .empty i {
            display: block;
            color: #B9C3D0;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .status {
            display: inline-flex;
            align-items: center;
            padding: 5px 9px;
            border-radius: 50px;
            font-size: 10px;
            font-weight: 800;
            background: var(--kp-orange-soft);
            color: var(--kp-orange-dark);
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 7px;
            border: 0;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--kp-navy);
            color: #fff;
        }

        .action-btn:hover {
            background: var(--kp-orange);
        }

        .row-actions {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .action-demand {
            background: var(--kp-orange);
        }

        .action-demand:hover {
            background: var(--kp-orange-dark);
        }

        /* ------------------------------------------------------------
           Demand / Payments Modal
        ------------------------------------------------------------ */
        .demand-modal {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(6, 26, 58, .62);
            backdrop-filter: blur(3px);
        }

        .demand-modal.show {
            display: flex;
        }

        .demand-modal-card {
            width: min(1180px, 96vw);
            max-height: 88vh;
            overflow: hidden;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, .28);
            border: 1px solid #DDE4EE;
        }

        .demand-modal-head {
            min-height: 72px;
            padding: 0 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #E5EAF1;
        }

        .demand-modal-title {
            color: #253248;
            font-size: 20px;
            font-weight: 500;
        }

        .demand-modal-close {
            width: 38px;
            height: 38px;
            border: 0;
            background: transparent;
            color: #777;
            font-size: 25px;
            cursor: pointer;
            border-radius: 7px;
        }

        .demand-modal-close:hover {
            background: #F3F5F8;
            color: var(--kp-navy);
        }

        .demand-modal-body {
            padding: 18px;
            overflow: auto;
            max-height: calc(88vh - 72px);
        }

        .payment-table-wrap {
            overflow-x: auto;
            border: 1px solid #E2E7ED;
        }

        .payment-table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
        }

        .payment-table th {
            background: #fff;
            color: #222;
            border-bottom: 1px solid #DDE3EA;
            border-right: 1px solid #E4E8ED;
            padding: 13px 12px;
            font-size: 12px;
            text-transform: none;
        }

        .payment-table th:first-child {
            border-left: 0;
        }

        .payment-table td {
            border-right: 1px solid #E4E8ED;
            padding: 12px;
            text-align: center;
            font-size: 12px;
            white-space: nowrap;
        }

        .payment-table tbody tr:hover {
            background: #F8FAFC;
        }

        .payment-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 9px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 800;
        }

        .payment-status.paid {
            color: #087443;
            background: #DCFCE7;
        }

        .payment-status.pending {
            color: #9A5B00;
            background: #FFF3D6;
        }

        .modal-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            margin: 0 2px;
            border-radius: 5px;
            border: 0;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-action-demand {
            color: #222;
            background: #F5F6F8;
        }

        .modal-action-demand:hover {
            background: #E8ECF2;
        }

        .modal-action-download {
            color: #17568D;
            background: #EDF5FC;
        }

        .modal-action-download:hover {
            background: #DDEDFB;
        }

        .modal-action.disabled {
            color: #A7AFBA;
            background: #F1F3F5;
            cursor: not-allowed;
        }

        .modal-loading,
        .modal-empty,
        .modal-error {
            padding: 35px 20px;
            text-align: center;
            color: #6B7280;
        }

        .modal-error {
            color: #B42318;
        }

        /* ------------------------------------------------------------
           Responsive
        ------------------------------------------------------------ */
        @media (max-width: 1050px) {
            .search-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {

            .header-inner,
            .container {
                padding-left: 16px;
                padding-right: 16px;
            }

            .header-inner {
                align-items: flex-start;
                flex-direction: column;
            }

            .header-actions {
                width: 100%;
            }

            .header-btn {
                flex: 1;
            }

            .brand-title {
                font-size: 16px;
            }

            .search-grid {
                grid-template-columns: 1fr;
            }

            .page-heading {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <div class="page">

        <!-- ============================================================
         HEADER
    ============================================================= -->
        <header class="top-header">
            <div class="header-inner">

                <div class="brand">
                    <img
                        src="admin/logo-main.png"
                        alt="Nagar Palika Parishad Khalilabad"
                        class="brand-logo">

                    <div>
                        <div class="brand-title">
                            Nagar Palika Parishad Khalilabad
                        </div>
                        <div class="brand-subtitle">
                            Property Tax &amp; Citizen Services
                        </div>
                    </div>
                </div>

                <!-- RIGHT SIDE: 2 BUTTONS -->
                <div class="header-actions">

                    <!-- Tax Calculator -->
                    <a
                        href="tax-calculator.php"
                        class="header-btn btn-tax"
                        title="Tax Calculator">
                        <i class="fa fa-calculator"></i>
                        Tax Calculator
                    </a>

                    <!-- Citizen Login -->
                    <a
                        href="citizen-login.php"
                        class="header-btn btn-citizen"
                        title="Citizen Login">
                        <i class="fa fa-user"></i>
                        Citizen Login
                    </a>

                </div>
            </div>
        </header>


        <!-- ============================================================
         MAIN CONTENT
    ============================================================= -->
        <main class="container">

            <div class="page-heading">
                <div class="heading-left">
                    <h1>UP Consumer List</h1>
                    <p>Search property and consumer records by available details.</p>
                    <div class="heading-line"></div>
                </div>
            </div>


            <!-- ========================================================
             SEARCH INSTRUCTIONS
        ========================================================= -->
            <section class="notice-card">

                <div class="notice-title">
                    <i class="fa fa-info-circle" style="color:#F28C00;"></i>
                    Note:
                </div>

                <ol class="notice-list">
                    <li>If you know <strong>ADDRESS CODE</strong>, please enter your address code alone and search.</li>
                    <li>If you know your <strong>MOBILE NO.</strong>, please enter your mobile no alone and search.</li>
                    <li>If you know <strong>OWNER NAME</strong>, please enter owner name &amp; select Mohalla and search.</li>
                    <li>If you know <strong>HOUSE NO.</strong>, please select Mohalla &amp; enter house no and search.</li>
                </ol>

            </section>


            <!-- ========================================================
             SEARCH FORM
        ========================================================= -->
            <section class="search-card">

                <form method="get" action="up-consumerlist.php">

                    <div class="search-grid">

                        <div class="field">
                            <label for="ward">Ward</label>

                            <select
                                name="ward"
                                id="ward"
                                class="select">
                                <option value="">Select Ward</option>

                                <?php foreach ($ward_list as $w): ?>
                                    <option
                                        value="<?= e($w['ward_id']) ?>"
                                        <?= ((string)$ward === (string)$w['ward_id']) ? 'selected' : '' ?>>
                                        Ward <?= e($w['ward_no']) ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>


                        <div class="field">
                            <label for="mohalla">Mohalla</label>

                            <select
                                name="mohalla"
                                id="mohalla"
                                class="select">
                                <option value="">Select Mohalla</option>

                                <?php foreach ($mohalla_list as $m): ?>
                                    <option
                                        value="<?= e($m['mohalla_id']) ?>"
                                        <?= ((string)$mohalla === (string)$m['mohalla_id']) ? 'selected' : '' ?>>
                                        <?= e($m['mohalla_name']) ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>


                        <div class="field">
                            <label for="house_no">House No</label>

                            <input
                                type="text"
                                id="house_no"
                                name="house_no"
                                value="<?= e($house_no) ?>"
                                class="input"
                                placeholder="House No">
                        </div>


                        <div class="field">
                            <label for="address_code">Address Code</label>

                            <input
                                type="text"
                                id="address_code"
                                name="address_code"
                                value="<?= e($address_code) ?>"
                                class="input"
                                placeholder="Address Code">
                        </div>


                        <div class="field">
                            <label for="mobile">Mobile No</label>

                            <input
                                type="text"
                                id="mobile"
                                name="mobile"
                                value="<?= e($mobile) ?>"
                                class="input"
                                placeholder="Mobile No"
                                maxlength="15">
                        </div>


                        <div class="field">
                            <label for="owner_name">Owner Name</label>

                            <input
                                type="text"
                                id="owner_name"
                                name="owner_name"
                                value="<?= e($owner_name) ?>"
                                class="input"
                                placeholder="Owner Name">
                        </div>

                    </div>


                    <div class="search-actions">

                        <button type="submit" class="search-btn">
                            <i class="fa fa-search"></i>
                            Search
                        </button>

                        <a href="<?= e($reset_url) ?>" class="reset-btn">
                            <i class="fa fa-refresh"></i>
                            Reset
                        </a>

                    </div>

                </form>

            </section>


            <!-- ========================================================
             RESULTS
        ========================================================= -->
            <section class="table-card">

                <div class="table-toolbar">
                    <div class="table-title">
                        <i class="fa fa-list-alt" style="color:#F28C00;"></i>
                        Consumer / Property Records
                    </div>

                    <div class="record-count">
                        <?= $has_search ? count($rows) . ' record(s) found' : 'Search to view records' ?>
                    </div>
                </div>


                <div class="table-wrap">

                    <table>

                        <thead>
                            <tr>
                                <th>Sr. No</th>
                                <th>Address Code</th>
                                <th>Property ID</th>
                                <th>Owner Name</th>
                                <th>Mobile No</th>
                                <th>Zone</th>
                                <th>Ward</th>
                                <th>Mohalla</th>
                                <th>House No</th>
                                <th>ARV</th>
                                <th>HT</th>
                                <th>WT</th>
                                <th>ST</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (!$has_search): ?>

                                <tr>
                                    <td colspan="14">
                                        <div class="empty">
                                            <i class="fa fa-search"></i>
                                            Please enter search details to find consumer records.
                                        </div>
                                    </td>
                                </tr>

                            <?php elseif (empty($rows)): ?>

                                <tr>
                                    <td colspan="14">
                                        <div class="empty">
                                            <i class="fa fa-folder-open-o"></i>
                                            No Data Found
                                        </div>
                                    </td>
                                </tr>

                            <?php else: ?>

                                <?php foreach ($rows as $index => $row): ?>

                                    <tr>

                                        <td><?= $index + 1 ?></td>

                                        <td>
                                            <?= e($row['property_id'] ?: $row['new_holding']) ?>
                                        </td>

                                        <td>
                                            <?= e($row['property_id'] ?: $row['id']) ?>
                                        </td>

                                        <td>
                                            <strong><?= e($row['owner_name']) ?></strong>
                                        </td>

                                        <td><?= e($row['mobile']) ?></td>

                                        <td><?= e($row['zone_id']) ?></td>

                                        <td><?= e($row['ward_no'] !== null ? $row['ward_no'] : $row['ward']) ?></td>

                                        <td><?= e($row['mohalla_name']) ?></td>

                                        <td><?= e($row['house_no']) ?></td>

                                        <td>
                                            <span style="color:#9CA3AF;">—</span>
                                        </td>

                                        <td>—</td>
                                        <td>—</td>
                                        <td>—</td>

                                        <td>
                                            <div class="row-actions">
                                                <a
                                                    href="view_assesment_details.php?id=<?= (int)$row['id'] ?>"
                                                    class="action-btn action-view"
                                                    title="View Property">
                                                    <i class="fa fa-eye"></i>
                                                </a>

                                                <button
                                                    type="button"
                                                    class="action-btn action-demand"
                                                    title="Demand / Payments"
                                                    data-assessment-id="<?= (int)$row['id'] ?>"
                                                    data-property-no="<?= e($row['property_id'] ?: $row['new_holding']) ?>"
                                                    data-owner="<?= e($row['owner_name']) ?>">
                                                    <i class="fa fa-file-text"></i>
                                                </button>
                                            </div>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        </main>

    </div>

    <!-- ================================================================
     DEMAND / PAYMENTS MODAL
================================================================= -->
    <div class="demand-modal" id="demandModal" aria-hidden="true">
        <div class="demand-modal-card" role="dialog" aria-modal="true" aria-labelledby="demandModalTitle">
            <div class="demand-modal-head">
                <div class="demand-modal-title" id="demandModalTitle">Payments List</div>
                <button type="button" class="demand-modal-close" id="demandModalClose" aria-label="Close">&times;</button>
            </div>

            <div class="demand-modal-body">
                <div id="demandModalMeta" style="margin-bottom:12px;color:#68758A;font-size:12px;"></div>
                <div id="demandModalContent">
                    <div class="modal-loading">
                        <i class="fa fa-spinner fa-spin"></i> Loading payments...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const modal = document.getElementById('demandModal');
            const closeBtn = document.getElementById('demandModalClose');
            const content = document.getElementById('demandModalContent');
            const meta = document.getElementById('demandModalMeta');
            const title = document.getElementById('demandModalTitle');

            function closeModal() {
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            function esc(value) {
                const div = document.createElement('div');
                div.textContent = value == null ? '' : String(value);
                return div.innerHTML;
            }

            function openModal(button) {
                const assessmentId = button.getAttribute('data-assessment-id');
                const propertyNo = button.getAttribute('data-property-no') || '';
                const owner = button.getAttribute('data-owner') || '';

                if (!assessmentId) return;

                title.textContent = 'Payments List';
                meta.innerHTML = '<strong>Property:</strong> ' + esc(propertyNo) +
                    (owner ? ' &nbsp; | &nbsp; <strong>Owner:</strong> ' + esc(owner) : '');

                content.innerHTML = '<div class="modal-loading"><i class="fa fa-spinner fa-spin"></i> Loading payments...</div>';
                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';

                const url = 'up-consumerlist.php?ajax=payments&assessment_id=' + encodeURIComponent(assessmentId);

                fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        if (!data || data.status !== 'success') {
                            throw new Error(data && data.message ? data.message : 'Unable to load payment list.');
                        }

                        if (!Array.isArray(data.payments) || data.payments.length === 0) {
                            content.innerHTML = '<div class="modal-empty"><i class="fa fa-folder-open-o" style="font-size:30px;display:block;margin-bottom:10px;"></i>No payment record found.</div>';
                            return;
                        }

                        let html = '<div class="payment-table-wrap"><table class="payment-table">';
                        html += '<thead><tr>' +
                            '<th>Sr No</th>' +
                            '<th>Address Code</th>' +
                            '<th>Date</th>' +
                            '<th>Amount</th>' +
                            '<th>MOP</th>' +
                            '<th>Receipt No</th>' +
                            '<th>Action</th>' +
                            '</tr></thead><tbody>';

                        data.payments.forEach(function(payment, index) {
                            const paid = !!payment.is_paid;
                            const receipt = paid ?
                                '<a class="modal-action modal-action-download" href="' + esc(payment.receipt_url) + '" target="_blank" title="Download / Print Receipt"><i class="fa fa-download"></i></a>' :
                                '<span class="modal-action disabled" title="Receipt available after successful payment"><i class="fa fa-download"></i></span>';

                            const demand = '<a class="modal-action modal-action-demand" href="' + esc(payment.demand_url) + '" target="_blank" title="Open Demand"><i class="fa fa-file"></i></a>';

                            html += '<tr>' +
                                '<td>' + (index + 1) + '</td>' +
                                '<td>' + esc(payment.property_no || '—') + '</td>' +
                                '<td>' + esc(payment.date || '—') + '</td>' +
                                '<td><strong>' + esc(payment.amount || '0.00') + '</strong></td>' +
                                '<td>' + esc(payment.payment_mode || '—') + '</td>' +
                                '<td>' + esc(payment.order_number || '—') + '</td>' +
                                '<td>' + demand + receipt + '</td>' +
                                '</tr>';
                        });

                        html += '</tbody></table></div>';
                        html += '<div style="margin-top:12px;color:#68758A;font-size:12px;">Total Record - ' + data.payments.length + '</div>';
                        content.innerHTML = html;
                    })
                    .catch(function(error) {
                        content.innerHTML = '<div class="modal-error"><i class="fa fa-exclamation-triangle"></i> ' + esc(error.message) + '</div>';
                    });
            }

            document.querySelectorAll('.action-demand').forEach(function(button) {
                button.addEventListener('click', function() {
                    openModal(this);
                });
            });

            closeBtn.addEventListener('click', closeModal);

            modal.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && modal.classList.contains('show')) {
                    closeModal();
                }
            });
        })();
    </script>

</body>

</html>