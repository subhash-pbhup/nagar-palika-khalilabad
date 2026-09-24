<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

    include "admin/db.php";


$assessment_id = (int)($_GET['id'] ?? $_GET['assessment_id'] ?? 0);

if ($assessment_id <= 0) {
    die("Invalid Assessment ID.");
}

if (!function_exists('e')) {
    function e($v)
    {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('money')) {
    function money($v)
    {
        return number_format((float)($v ?? 0), 2, '.', '');
    }
}

if (!function_exists('tableExists')) {
    function tableExists($conn, $table)
    {
        if (!$conn) return false;
        $table = $conn->real_escape_string($table);
        $q = $conn->query("SHOW TABLES LIKE '{$table}'");
        return $q and $q->num_rows > 0;
    }
}

if (!function_exists('rowValue')) {
    function rowValue($row, $keys, $default = '')
    {
        if (!is_array($row)) return $default;
        foreach ($keys as $key) {
            if (isset($row[$key]) and $row[$key] !== '' and $row[$key] !== null) {
                return $row[$key];
            }
        }
        return $default;
    }
}

function financialYear($value)
{
    $value = trim((string)$value);
    if ($value === '') return '';
    $value = str_replace(['/', '_'], '-', $value);
    $value = preg_replace('/\s+/', '', $value);
    if (preg_match('/^(20\d{2})-(20\d{2})$/', $value, $m)) return $m[1] . '-' . $m[2];
    if (preg_match('/^(20\d{2})-(\d{2})$/', $value, $m)) return $m[1] . '-' . (2000 + (int)$m[2]);
    return $value;
}

// ---------------------------------------------------------
// FETCH PROPERTY & OWNER
// ---------------------------------------------------------
$property = null;
if (tableExists($conn, 'assessments')) {
    $stmt = $conn->prepare("SELECT * FROM assessments WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $assessment_id);
        $stmt->execute();
        $property = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (!$property) {
    die("Property Record Not Found.");
}

$owners = [];
if (tableExists($conn, 'assessment_owners')) {
    $stmt = $conn->prepare("SELECT * FROM assessment_owners WHERE assessment_id = ? ORDER BY id ASC");
    if ($stmt) {
        $stmt->bind_param("i", $assessment_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $owners[] = $row;
        }
        $stmt->close();
    }
}
$primary_owner = $owners[0] ?? [];

// ---------------------------------------------------------
// FETCH DEMAND / ARV
// ---------------------------------------------------------
$arv_rows = [];
if (tableExists($conn, 'property_arv_details')) {
    $stmt = $conn->prepare("SELECT * FROM property_arv_details WHERE assessment_id = ? ORDER BY financial_year ASC, id ASC");
    if ($stmt) {
        $stmt->bind_param("i", $assessment_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $arv_rows[] = $row;
        }
        $stmt->close();
    }
}

// ---------------------------------------------------------
// FETCH PAID YEARS TO EXCLUDE THEM FROM UNPAID DEMAND
// ---------------------------------------------------------
$paid_years = [];
if (tableExists($conn, 'property_payment_orders') and tableExists($conn, 'property_payment_years')) {
    $sql = "SELECT py.financial_year FROM property_payment_orders po
            JOIN property_payment_years py ON py.payment_order_id = po.id
            WHERE po.assessment_id = ? AND LOWER(TRIM(po.status)) IN ('paid','success','completed')";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $assessment_id);
        $stmt->execute();
        $r = $stmt->get_result();
        while ($row = $r->fetch_assoc()) {
            $fy = financialYear($row['financial_year']);
            if ($fy) $paid_years[$fy] = true;
        }
        $stmt->close();
    }
}

// ---------------------------------------------------------
// CALCULATE CURRENT & ARREAR DEMAND
// ---------------------------------------------------------
$unpaid_rows = [];
foreach ($arv_rows as $row) {
    $fy = financialYear($row['financial_year'] ?? '');
    if ($fy !== '' and isset($paid_years[$fy])) continue;
    $unpaid_rows[] = $row;
}

$current_row = null;
$arrear_rows = [];

if (count($unpaid_rows) > 0) {
    $current_row = $unpaid_rows[count($unpaid_rows) - 1];
    if (count($unpaid_rows) > 1) {
        $arrear_rows = array_slice($unpaid_rows, 0, count($unpaid_rows) - 1);
    }
}

$c_house = 0;
$c_water = 0;
$c_sewer = 0;
$c_rebate = 0;
$c_penalty = 0;
$c_total = 0;
$a_house = 0;
$a_water = 0;
$a_sewer = 0;
$a_rebate = 0;
$a_penalty = 0;
$a_total = 0;

$current_fy = "N/A";

// Process Current
if ($current_row) {
    $current_fy = financialYear($current_row['financial_year'] ?? '2026-2027');
    $c_house = (float)rowValue($current_row, ['house_tax_current', 'house_tax', 'property_tax'], 0);
    $c_water = (float)rowValue($current_row, ['water_tax_current', 'water_tax'], 0);
    $c_sewer = (float)rowValue($current_row, ['sewer_tax_current', 'sewer_tax'], 0);
    $c_rebate = (float)rowValue($current_row, ['rebate'], 0);
    $c_penalty = (float)rowValue($current_row, ['house_tax_interest', 'penalty', 'interest'], 0);

    $calc_total = ($c_house + $c_water + $c_sewer) - $c_rebate + $c_penalty;
    $c_total = $calc_total > 0 ? $calc_total : (float)rowValue($current_row, ['total_tax', 'amount'], 0);
}

// Process Arrears
foreach ($arrear_rows as $ar) {
    $a_house += (float)rowValue($ar, ['total_tax', 'house_tax', 'property_tax'], 0);
    $a_water += (float)rowValue($ar, ['water_tax'], 0);
    $a_sewer += (float)rowValue($ar, ['sewer_tax'], 0);
    $a_rebate += (float)rowValue($ar, ['rebate'], 0);
    $a_penalty += (float)rowValue($ar, ['house_tax_interest', 'penalty', 'interest'], 0);
}
$calc_a_total = ($a_house + $a_water + $a_sewer) - $a_rebate + $a_penalty;
$a_total = $calc_a_total > 0 ? $calc_a_total : array_sum(array_column($arrear_rows, 'total_tax'));

$grand_total = $c_total + $a_total;

// Property Data Extraction
$property_no = rowValue($property, ['new_holding', 'new_holding_number', 'holding_no', 'house_no'], 'N/A');
$property_pid = rowValue($property, ['property_id', 'id'], 'N/A');
$zone = rowValue($property, ['zone', 'zone_id'], 'N/A');
$ward = rowValue($property, ['ward', 'ward_id'], 'N/A');
$mohalla = rowValue($property, ['mohalla', 'mohalla_id'], 'N/A');
$address = rowValue($property, ['address_line1', 'addr1'], 'N/A');
$owner_name = rowValue($primary_owner, ['owner_name', 'name'], 'N/A');
$father_name = rowValue($primary_owner, ['father_husband_pan', 'father_name', 'husband_name'], 'N/A');
$mobile = rowValue($primary_owner, ['mobile', 'mobile_no'], 'N/A');
$arv_val = rowValue($current_row, ['total_arv', 'arv', 'annual_rental_value'], 0);

$bill_no = "BILL" . date('Ymd') . str_pad($assessment_id, 4, "0", STR_PAD_LEFT);
$date_today = date('d-m-Y');
?>
<!DOCTYPE html>
<html lang="hi">

<head>
    <meta charset="UTF-8">
    <title>संपत्तिकर डिमांड बिल - खलीलाबाद</title>
    <link rel="icon" href="admin/img/favicon.ico">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;600;700&display=swap');

        :root {
            --brand-navy: #021842;
            --brand-orange: #f58220;
            --text-dark: #1e293b;
            --border-color: #cbd5e1;
        }

        body {
            margin: 0;
            padding: 20px;
            font-family: 'Noto Sans Devanagari', Arial, sans-serif;
            background: #eef2f6;
            color: var(--text-dark);
            font-size: 13px;
        }

        .bill-container {
            max-width: 950px;
            margin: 0 auto;
            background: #fff;
            padding: 30px 40px;
            border: 2px solid var(--brand-navy);
            border-top: 8px solid var(--brand-orange);
            box-shadow: 0 10px 30px rgba(2, 24, 66, 0.1);
        }

        /* HEADER */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--brand-navy);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .logo-box img {
            max-width: 90px;
        }

        .title-box {
            text-align: center;
            flex: 1;
            padding: 0 20px;
        }

        .title-box h1 {
            margin: 0;
            font-size: 26px;
            color: var(--brand-navy);
            font-weight: 700;
        }

        .title-box h3 {
            margin: 5px 0 0;
            font-size: 16px;
            color: var(--brand-orange);
            font-weight: 600;
        }

        /* NEW RIGHT BOX (from reference image) */
        .header-right-box {
            width: 80px;
            height: 80px;
            border: 3px solid #ef4444;
            /* Red border */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Optional placeholder text if you don't have an image right now */
        .header-right-box span {
            color: #ef4444;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
        }

        .header-right-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* DETAILS GRID */
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
            line-height: 1.8;
            color: #475569;
        }

        .details-grid .lbl {
            width: 150px;
            display: inline-block;
            color: var(--brand-navy);
            font-weight: 600;
        }

        .details-grid .val {
            font-weight: 700;
            color: #000;
            text-transform: uppercase;
        }

        /* DEMAND TEXT */
        .demand-text {
            background: #f8fafc;
            padding: 10px 15px;
            border-left: 4px solid var(--brand-orange);
            margin-bottom: 20px;
            font-weight: 600;
            color: var(--brand-navy);
            font-size: 14px;
        }

        /* TABLE */
        .r-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            text-align: center;
            border: 1px solid var(--border-color);
        }

        .r-table th,
        .r-table td {
            border: 1px solid var(--border-color);
            padding: 10px;
        }

        .r-table th {
            background: var(--brand-navy);
            color: #fff;
            font-weight: 600;
        }

        .r-table td {
            color: #000;
            font-weight: 600;
        }

        .r-table .text-left {
            text-align: left;
        }

        .r-table .bg-light {
            background: #f8fafc;
        }

        /* INFO SECTION */
        .info-section {
            margin-bottom: 30px;
            font-size: 12px;
            line-height: 1.6;
        }

        .info-section ul {
            padding-left: 20px;
            margin: 0;
            color: var(--brand-navy);
        }

        .info-section li {
            margin-bottom: 5px;
        }

        .social-qr {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--border-color);
            padding-top: 15px;
        }

        .qr-box {
            text-align: center;
            font-size: 10px;
            color: var(--brand-navy);
            font-weight: 600;
        }

        .qr-img {
            width: 70px;
            height: 70px;
            border: 1px dashed var(--brand-navy);
            display: inline-block;
            line-height: 70px;
            background: #f8fafc;
            margin-bottom: 5px;
        }

        /* RULES / INSTRUCTIONS */
        .rules-container {
            border-top: 2px dashed var(--border-color);
            padding-top: 20px;
            margin-top: 20px;
        }

        .rules-title {
            text-align: center;
            font-size: 18px;
            color: var(--brand-navy);
            font-weight: 700;
            margin-bottom: 15px;
        }

        .rules-list {
            font-size: 11px;
            line-height: 1.6;
            color: #334155;
            text-align: justify;
        }

        .rules-list p {
            margin: 0 0 8px 0;
        }

        .rules-list strong {
            color: var(--brand-navy);
        }

        /* ACTIONS */
        .action-buttons {
            text-align: center;
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-print {
            background: #64748b;
            color: #fff;
        }

        .btn-pay {
            background: var(--brand-orange);
            color: #fff;
            box-shadow: 0 4px 6px rgba(245, 130, 32, 0.2);
        }

        .btn-pay:hover {
            background: #d97015;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .bill-container {
                border: 2px solid var(--brand-navy);
                box-shadow: none;
                padding: 20px;
                max-width: 100%;
            }

            .action-buttons {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="bill-container">

        <!-- HEADER -->
        <div class="header">
            <div class="logo-box">
                <img src="assets/images/logo/logo.png" alt="Logo">
            </div>
            <div class="title-box">
                <h1>नगर पालिका परिषद खलीलाबाद</h1>
                <h3>संपत्तिकर डिमांड बिल (<?= e($current_fy) ?>)</h3>
            </div>
            <!-- RIGHT LOGO BOX -->
            <div class="logo-box">
                <!-- Placeholder text or your desired image -->
                <img src="assets/images/logo/up-logo.png" alt="Right Logo">
            </div>
        </div>

        <!-- DETAILS -->
        <div class="details-grid">
            <div>
                <div><span class="lbl">बिल संख्या:</span> <span class="val"><?= e($bill_no) ?></span></div>
                <div><span class="lbl">संपत्ति आईडी:</span> <span class="val"><?= e($property_pid) ?></span></div>
                <div><span class="lbl">मैप आईडी/GIS आईडी:</span> <span class="val">N/A</span></div>
                <div><span class="lbl">डिमांड संख्या/कोड:</span> <span class="val"><?= e($property_no) ?>-H</span></div>
                <div><span class="lbl">नाम:</span> <span class="val"><?= e($owner_name) ?></span></div>
                <div><span class="lbl">पुत्र/पुत्री/पति/द्वारा:</span> <span class="val">S/O <?= e($father_name) ?></span></div>
                <div><span class="lbl">मोबाइल न०:</span> <span class="val"><?= e($mobile) ?></span></div>
                <div><span class="lbl">ज़ोन:</span> <span class="val">Zone <?= e($zone) ?></span></div>
                <div><span class="lbl">वार्ड:</span> <span class="val"><?= e($ward) ?></span></div>
                <div><span class="lbl">मोहल्ला:</span> <span class="val"><?= e($mohalla) ?></span></div>
                <div><span class="lbl">पता:</span> <span class="val"><?= e($address) ?></span></div>
                <div><span class="lbl">वार्षिक मूल्यांकन:</span> <span class="val">₹ <?= money($arv_val) ?></span></div>
            </div>
            <div style="text-align: right;">
                <div><span class="lbl" style="width:auto; margin-right:10px;">भवन संख्या:</span> <span class="val"><?= e($property_no) ?></span></div>
                <div><span class="lbl" style="width:auto; margin-right:10px;">दिनांक:</span> <span class="val"><?= e($date_today) ?></span></div>
            </div>
        </div>

        <!-- DEMAND SUMMARY TEXT -->
        <div class="demand-text">
            A sum of Rs - <?= money($grand_total) ?> is demand on the property with holding No. <?= e($property_no) ?> on the financial year <?= e($current_fy) ?> as per the details given below:
        </div>

        <!-- TAX TABLE -->
        <table class="r-table">
            <thead>
                <tr>
                    <th class="text-left">वित्तीय-वर्ष</th>
                    <th>सामान्य कर</th>
                    <th>जल कर</th>
                    <th>सीवर कर</th>
                    <th>छूट</th>
                    <th>ब्याज</th>
                    <th>योग</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-left"><strong>वर्तमान कर (<?= e($current_fy) ?>)</strong></td>
                    <td><?= money($c_house) ?></td>
                    <td><?= money($c_water) ?></td>
                    <td><?= money($c_sewer) ?></td>
                    <td><?= money($c_rebate) ?></td>
                    <td><?= money($c_penalty) ?></td>
                    <td><?= money($c_total) ?></td>
                </tr>
                <tr>
                    <td class="text-left"><strong>बकाया कर (मार्च <?= substr($current_fy, 0, 4) ?> तक)</strong></td>
                    <td><?= money($a_house) ?></td>
                    <td><?= money($a_water) ?></td>
                    <td><?= money($a_sewer) ?></td>
                    <td><?= money($a_rebate) ?></td>
                    <td><?= money($a_penalty) ?></td>
                    <td><?= money($a_total) ?></td>
                </tr>
                <tr class="bg-light">
                    <td colspan="6" style="text-align: right; color: var(--brand-navy);"><strong>कुल</strong></td>
                    <td><strong><?= money($grand_total) ?></strong></td>
                </tr>
                <tr class="bg-light">
                    <td colspan="6" style="text-align: right; color: var(--brand-navy);"><strong>यूजर चार्जेज (एस.डब्लू.एम.)</strong></td>
                    <td><strong>0.00</strong></td>
                </tr>
                <tr class="bg-light">
                    <td colspan="6" style="text-align: right; color: var(--brand-navy); font-size: 15px;"><strong>कुल देय राशि</strong></td>
                    <td style="color: var(--brand-orange); font-size: 15px;"><strong><?= money($grand_total) ?></strong></td>
                </tr>
            </tbody>
        </table>

        <!-- INSTRUCTIONS & SOCIAL -->
        <div class="info-section">
            <ul>
                <li>नगर पालिका से प्राप्त बिल एवं जमा रसीद की प्रति अपने पास सुरक्षित रखें तथा कार्यालय द्वारा मांगे जाने पर पुनः दिखानी होगी।</li>
                <li>यह बिल किसी भी दशा में स्वामित्व/मालिकाना हक का साक्ष्य नहीं है।</li>
                <li>यह मात्र बिल है, रसीद नहीं है।</li>
                <li>आपके पंजीकृत मोबाइल नंबर पर कर राशि भुगतान उपरांत SMS प्राप्त होगा।</li>
            </ul>

            <div class="social-qr" style="margin-top: 20px;">
                <div>
                    <strong>Follow Us:</strong><br>
                    <i class="fa fa-twitter" style="color:#1DA1F2; margin-top:8px;"></i> twitter.com/Npkhalilabad<br>
                    <i class="fa fa-facebook-official" style="color:#4267B2; margin-top:5px;"></i> facebook.com/Npkhalilabad
                </div>
                <div class="qr-box">
                    <div class="qr-img">QR CODE</div><br>
                    ऑनलाइन बिल के लिए<br>QR कोड स्कैन करें
                </div>
            </div>
        </div>

        <!-- IMPORTANT RULES -->
        <div class="rules-container">
            <div class="rules-title">महत्वपूर्ण सूचना</div>
            <div class="rules-list">
                <p>1. जी०आई०एस० सर्वेक्षण के आधार पर पुनरीक्षण प्रक्रिया पूर्ण होने के उपरांत / अंतर धनराशि नियमानुसार प्रभावी तिथि से अनिवार्य रूप से देय होगी।</p>
                <p>2. वित्तीय वर्ष <?= e($current_fy) ?> के अवशेष मांग (ARREAR DEMAND) पर भुगतान किये जाने की तिथि तक 1% (एक फीसदी) प्रतिमाह की दर से साधारण ब्याज देय होगा।</p>
                <p>3. आप अपने संपत्ति कर बिल का ऑनलाइन भुगतान नगर पालिका की वेबसाइट के माध्यम से कर सकते हैं।</p>
                <p>4. संपत्ति कर की अवशेष धनराशि पर नियमानुसार 12 प्रतिशत साधारण ब्याज देय होगा।</p>
                <p>5. इस बिल के संबंध में कोई शिकायत है तो देय तिथि के अंदर संपत्ति कर विभाग, नगर पालिका खलीलाबाद को बताना आवश्यक है, बिल का भुगतान देय तिथि तक किया जाना अनिवार्य है।</p>
                <p>6. किसी भवन के संबंध में भुगतान संबंधी कोई विवाद न्यायालय में विचाराधीन होने के कर कारण से है तो उस परिस्थिति में विवरण सहित नगर पालिका, खलीलाबाद को लिखित सूचित करें।</p>
                <p>7. देय तिथि तक धनराशि भुगतान न करने पर उत्तर प्रदेश नगर निगम अधिनियम 1959 के प्रावधानों के तहत धारा - 506, 507, 509, 514 व 513 के तहत डिमान्ड नोटिस, कुर्की वारंट, किरायेदार अटैचमेंट, खाता कुर्क आदि की कार्यवाही की जाएगी।</p>
                <p>8. यह बिल या इसमें अंकित प्रविष्टि किसी भी दशा में स्वामित्व/मालिकाना हक का साक्ष्य नहीं है और इसे इस प्रकार प्रयोग /तौल होना और इसे शून्य माना जाएगा।</p>
                <p>9. यह मात्र बिल है, यह रसीद नहीं है।</p>
                <p>10. जिन भवनों के संबंधित करदाता जीवित नहीं है तो उनकी नई प्रविष्टि नगर पालिका अभिलेखों में नियमानुसार नामांतरण कर लेनी चाहिए। यह हितबद्ध पक्ष का दायित्व है।</p>
                <p>11. भवन स्वामी/अध्यासी का नाम सर्वेक्षण के आधार पर है। यदि इसमें कोई भिन्नता है तो हितबद्ध पक्ष द्वारा वैध पंजीकृत अभिलेख प्रस्तुत करने पर विहित प्रक्रिया के अंतर्गत कार्यवाही की जाएगी।</p>
                <p>12. संपत्ति की वास्तविक स्थिति यथा - निर्माण की प्रकृति/कवर्ड एरिया/संपत्ति का उपयोग में यदि परिवर्तन/परिवर्धन जांच में पाया गया है तो उत्तर प्रदेश नगर निगम अधिनियम - 1959 के अंतर्गत नगर पालिका का तथा संपत्ति के कर निर्धारण में परिवर्तन/परिवर्धन का पूर्ण अधिकार होगा और ऐसी स्थिति में यदि कर निर्धारण में वृद्धि होती है तो हितबद्ध पक्ष के द्वारा नियमानुसार अंतर धनराशि प्रभावी तिथि से देय होगी।</p>
                <p>13. यदि किसी संपत्ति पर भवन स्वामी का नाम दर्ज नहीं है तो रजिस्टर्ड अभिलेख प्रस्तुत कर उक्त संपत्ति पर अपना नाम दर्ज करा सकते हैं।</p>
                <p>14. कर नियम से प्राप्त बिल एवं जमा रसीद की प्रति अपने पास सुरक्षित रखनी होगी तथा कार्यालय द्वारा मांगे जाने पर पुनः दिखानी होगी।</p>
                <p>15. यह कंप्यूटर द्वारा बनाया गया बिल है, इस पर मोहर अथवा हस्ताक्षर की आवश्यकता नहीं है।</p>
                <p>16. संपत्ति करदाता का दायित्व है कि वह निर्धारित अवधि के अंदर अपने भवन के संपत्ति कर का भुगतान करें | वर्तमान मांग पर छूट नियमानुसार अनुमन्य होगी।</p>
                <p>17. ऑनलाइन भुगतान हेतु बिल पर प्रिंट QR कोड का प्रयोग करें | QR कोड स्कैन के उपरांत बिल पर दर्शाया गया मांग संख्या, भवन संख्या और नाम से संतुष्ट होने पर ही भुगतान करें।</p>
            </div>
        </div>

        <!-- ACTIONS -->
        <div class="action-buttons">
            <button class="btn btn-print" onclick="window.print()"><i class="fa fa-print"></i> Print Bill</button>
            <?php if ($grand_total > 0): ?>
                <a href="pay-online.php?id=<?= $assessment_id ?>&assessment_id=<?= $assessment_id ?>" class="btn btn-pay"><i class="fa fa-credit-card"></i> Pay Now</a>
            <?php endif; ?>
        </div>

    </div>

</body>

</html>