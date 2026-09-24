<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "admin/db.php";

$order_id = (int)($_GET['id'] ?? $_GET['order_id'] ?? 0);
$autoprint = isset($_GET['autoprint']) and (string)$_GET['autoprint'] === '1';

if (!function_exists('e')) {
    function e($v)
    {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('money')) {
    function money($v)
    {
        return number_format((float)($v ?? 0), 2);
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

if (!function_exists('columnExists')) {
    function columnExists($conn, $table, $column)
    {
        if (!tableExists($conn, $table)) return false;
        $table = str_replace('`', '', $table);
        $column = $conn->real_escape_string($column);
        $q = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
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

if (!function_exists('financialYear')) {
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
}

if (!function_exists('number_to_words_indian')) {
    function number_to_words_indian($amount)
    {
        $amount = round((float)$amount, 2);
        if ($amount <= 0) return 'Zero';
        $rupees = (int)floor($amount);
        $paise  = (int)round(($amount - $rupees) * 100);
        if ($paise === 100) {
            $rupees++;
            $paise = 0;
        }
        $words = [];

        // Multi-line arrays to prevent line-wrap parsing errors
        $ones = [
            0 => '',
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

        $tens = [0 => '', 1 => 'Ten', 2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty',             5 => 'Fifty', 6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'];
        $get_hundreds = function ($num) use ($ones, $tens) {
            $res = '';
            if ($num > 99) {
                $res .= $ones[(int)($num / 100)] . ' Hundred ';
                $num %= 100;
            }
            if ($num > 19) {
                $res .= $tens[(int)($num / 10)] . ' ' . $ones[$num % 10];
            } else {
                $res .= $ones[$num];
            }
            return trim($res);
        };

        $crore = (int)($rupees / 10000000);
        $rupees %= 10000000;
        $lakh = (int)($rupees / 100000);
        $rupees %= 100000;
        $thousand = (int)($rupees / 1000);
        $rupees %= 1000;
        $hundreds = $rupees;

        if ($crore > 0) $words[] = $get_hundreds($crore) . ' Crore';
        if ($lakh > 0) $words[] = $get_hundreds($lakh) . ' Lakh';
        if ($thousand > 0) $words[] = $get_hundreds($thousand) . ' Thousand';
        if ($hundreds > 0) $words[] = $get_hundreds($hundreds);

        $result = implode(' ', $words);
        if ($paise > 0) $result .= ' and ' . $get_hundreds($paise) . ' Paise';
        return trim($result);
    }
}

$order = null;
$years = [];
$assessment = null;
$payment_details = null;
$owners = null;
$arv_amount = 0;

/* =========================================================
   ORDER FETCH
   ========================================================= */
if ($order_id > 0 and tableExists($conn, 'property_payment_orders')) {
    $stmt = $conn->prepare("SELECT * FROM property_payment_orders WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

/* =========================================================
   PAYMENT YEARS
   ========================================================= */
if ($order and tableExists($conn, 'property_payment_years')) {
    $stmt = $conn->prepare("SELECT * FROM property_payment_years WHERE payment_order_id = ? ORDER BY id ASC");
    if ($stmt) {
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $years[] = $row;
        }
        $stmt->close();
    }
}

/* =========================================================
   EXTENDED DETAILS (ASSESSMENTS, OWNERS, ARV)
   ========================================================= */
$assessment_id = (int)rowValue($order, ['assessment_id', 'property_id'], 0);

if ($assessment_id > 0 and tableExists($conn, 'assessments')) {
    $astmt = $conn->prepare("SELECT * FROM assessments WHERE id = ? LIMIT 1");
    if ($astmt) {
        $astmt->bind_param("i", $assessment_id);
        $astmt->execute();
        $assessment = $astmt->get_result()->fetch_assoc();
        $astmt->close();
    }
}

if ($assessment_id > 0 and tableExists($conn, 'assessment_owners')) {
    $ostmt = $conn->prepare("SELECT * FROM assessment_owners WHERE assessment_id = ? ORDER BY id ASC LIMIT 1");
    if ($ostmt) {
        $ostmt->bind_param("i", $assessment_id);
        $ostmt->execute();
        $owners = $ostmt->get_result()->fetch_assoc();
        $ostmt->close();
    }
}

if ($assessment_id > 0 and tableExists($conn, 'property_arv_details')) {
    $arvstmt = $conn->prepare("SELECT total_arv, arv FROM property_arv_details WHERE assessment_id = ? ORDER BY id DESC LIMIT 1");
    if ($arvstmt) {
        $arvstmt->bind_param("i", $assessment_id);
        $arvstmt->execute();
        $arv_row = $arvstmt->get_result()->fetch_assoc();
        $arv_amount = (float)rowValue($arv_row, ['total_arv', 'arv'], 0);
        $arvstmt->close();
    }
}

if ($order_id > 0 and tableExists($conn, 'property_payment_details')) {
    $dstmt = $conn->prepare("SELECT * FROM property_payment_details WHERE payment_order_id = ? LIMIT 1");
    if ($dstmt) {
        $dstmt->bind_param("i", $order_id);
        $dstmt->execute();
        $payment_details = $dstmt->get_result()->fetch_assoc();
        $dstmt->close();
    }
}

$property_no = rowValue($order, ['property_no', 'holding_no', 'holding_number'], 'N/A');
$payer_name = rowValue($order, ['payer_name', 'owner_name', 'name'], 'N/A');
$payer_mobile = rowValue($order, ['payer_mobile', 'mobile', 'mobile_number'], 'N/A');
$payment_made_at = rowValue($order, ['payment_made_at', 'payment_at'], 'N/A');
$payment_mode = rowValue($order, ['payment_mode', 'mode_of_payment', 'mode'], 'N/A');
$order_number = rowValue($order, ['order_number', 'order_no'], 'PAY-' . $order_id);
$created_at = rowValue($order, ['created_at', 'payment_date'], date('Y-m-d H:i:s'));
$formatted_date = date('d-m-Y', strtotime($created_at));

$demand_amount = (float)rowValue($order, ['tax_amount', 'demand_amount', 'demand_tax'], 0);
$form_fee = (float)rowValue($order, ['form_fee', 'form_fees'], 0);
$other_amount = (float)rowValue($order, ['other_amount', 'other_charge'], 0);
$boring_charge = (float)rowValue($order, ['boring_charge', 'boring_fee'], 0);
$advance_received = (float)rowValue($order, ['advance_received', 'advance_amount', 'advance_deposit'], 0);
$total_amount = (float)rowValue($order, ['total_amount', 'grand_total', 'amount'], 0);

$father_name = rowValue($owners, ['father_husband_pan', 'father_name', 'husband_name'], 'N/A');
$zone = rowValue($assessment, ['zone', 'zone_id'], 'N/A');
$ward = rowValue($assessment, ['ward', 'ward_id'], 'N/A');
$mohalla = rowValue($assessment, ['mohalla', 'mohalla_id'], 'N/A');
$address = rowValue($assessment, ['address_line1', 'addr1', 'house_no'], 'N/A');

$transaction_id = rowValue($payment_details, ['transaction_id', 'gateway_payment_id', 'cash_reference', 'cheque_number', 'dd_number'], 'N/A');
if ($transaction_id === '' or $transaction_id === 'N/A') {
    $transaction_id = rowValue($order, ['gateway_payment_id', 'transaction_id'], 'N/A');
}

$receipt_no = "RECEIPT" . str_pad($order_id, 8, "0", STR_PAD_LEFT);
$property_id_display = $assessment_id > 0 ? str_pad($assessment_id, 7, "0", STR_PAD_LEFT) : 'N/A';

/* Calculate Amounts */
if ($demand_amount <= 0 and !empty($years)) {
    foreach ($years as $y) {
        $demand_amount += (float)rowValue($y, ['total_tax', 'tax_amount', 'amount'], 0);
    }
}

if ($total_amount <= 0) {
    $total_amount = $demand_amount + $form_fee + $other_amount + $boring_charge - $advance_received;
    if ($total_amount < 0) $total_amount = 0;
}

$amount_in_words = strtolower(number_to_words_indian($total_amount));
?>
<!DOCTYPE html>
<html lang="hi">

<head>
    <meta charset="UTF-8">
    <title>संपत्तिकर भुगतान रसीद - खलीलाबाद</title>
    <link rel="icon" href="admin/img/favicon.ico">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;600;700&display=swap');

        :root {
            --brand-navy: #021842;
            /* Deep Navy Blue from Logo */
            --brand-orange: #f58220;
            /* Bright Orange/Gold from Logo */
            --text-dark: #1e293b;
            --text-muted: #475569;
        }

        body {
            margin: 0;
            padding: 20px;
            font-family: 'Noto Sans Devanagari', Arial, sans-serif;
            background: #eef2f6;
            color: var(--text-dark);
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .receipt-container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            padding: 30px 40px;
            border: 2px solid var(--brand-navy);
            border-top: 8px solid var(--brand-orange);
            box-shadow: 0 10px 30px rgba(2, 24, 66, 0.1);
            box-sizing: border-box;
            position: relative;
            border-radius: 4px;
        }

        .r-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--brand-navy);
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .r-logo {
            width: 100px;
            text-align: left;
        }

        .r-logo img {
            width: 100%;
            max-width: 90px;
            height: auto;
        }

        .r-title-wrap {
            text-align: center;
            flex: 1;
            padding: 0 15px;
        }

        .r-title-wrap h1 {
            margin: 5px 0;
            font-size: 28px;
            color: var(--brand-navy);
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .r-title-wrap h3 {
            margin: 5px 0 0;
            font-size: 16px;
            color: var(--brand-orange);
            font-weight: 600;
        }

        /* NEW RIGHT SIDE WRAPPER (Details + Red Box) */
        .r-header-right-wrap {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .r-header-right {
            text-align: right;
            font-size: 13px;
            color: var(--brand-navy);
            font-weight: 600;
            line-height: 1.6;
        }

        /* RIGHT RED BOX (Matching user image) */
        .right-box {
            width: 80px;
            height: 80px;
            border: 3px solid #ef4444;
            /* Exact red border from image */
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            overflow: hidden;
            flex-shrink: 0;
        }

        .right-box span {
            color: #ef4444;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
        }

        .right-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .r-details-grid {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 25px;
            line-height: 1.8;
            color: var(--text-muted);
        }

        .r-col-left {
            flex: 1;
        }

        .r-col-right {
            text-align: left;
            min-width: 320px;
            background: #f8fafc;
            padding: 15px;
            border-radius: 6px;
            border: 1px dashed #cbd5e1;
            height: fit-content;
        }

        .r-details-grid span.val {
            color: var(--brand-navy);
            font-weight: 700;
            text-transform: uppercase;
        }

        .r-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 13px;
            border: 1px solid var(--brand-navy);
        }

        .r-table th,
        .r-table td {
            border: 1px solid var(--brand-navy);
            padding: 10px;
            text-align: center;
        }

        .r-table th {
            font-weight: 700;
            background: var(--brand-navy);
            color: #ffffff;
        }

        .r-table td {
            color: var(--text-dark);
            font-weight: 600;
        }

        .r-table .text-left {
            text-align: left;
        }

        .r-footer-info {
            font-size: 13px;
            line-height: 1.8;
            margin-bottom: 40px;
            background: #fff7ed;
            padding: 15px;
            border-left: 4px solid var(--brand-orange);
            color: var(--brand-navy);
        }

        .r-footer-info strong {
            color: var(--brand-orange);
        }

        .r-signatures {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: var(--brand-navy);
            margin-top: 50px;
            font-weight: 600;
        }

        .r-signatures .sig-box {
            text-align: left;
            line-height: 1.6;
        }

        .r-signatures .sig-box-right {
            text-align: center;
        }

        .note-text {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 20px;
            max-width: 600px;
            font-weight: 400;
        }

        .action-buttons {
            text-align: center;
            margin-top: 30px;
        }

        .btn-print {
            padding: 10px 25px;
            background: var(--brand-orange);
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
            box-shadow: 0 4px 6px rgba(245, 130, 32, 0.2);
            transition: background 0.3s;
        }

        .btn-print:hover {
            background: #d97015;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .receipt-container {
                border: 2px solid var(--brand-navy);
                border-top: 8px solid var(--brand-orange);
                box-shadow: none;
                padding: 20px;
                width: 100%;
                max-width: 100%;
            }

            .action-buttons {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="receipt-container">

        <!-- Header -->
        <div class="r-header">
            <div class="r-logo">
                <img src="assets/images/logo/logo.png" alt="Nagar Palika Khalilabad Logo">
            </div>

            <div class="r-title-wrap">
                <h1>नगर पालिका परिषद खलीलाबाद</h1>
                <h3>संपत्तिकर भुगतान रसीद (2026-2027)</h3>
            </div>

            <div class="r-header-right-wrap">
                <div class="r-header-right">
                    <div>रसीद/क्रम संख्या- <?= e($order_id) ?></div>
                    <div>दिनांक- <?= e($formatted_date) ?></div>
                </div>

                <!-- RED BORDERED LOGO BOX ON RIGHT -->
                <div class="r-logo">
                    <!-- Placeholder text or your desired image -->
                    <img src="assets/images/logo/up-logo.png" alt="Nagar Palika Khalilabad Logo">
                </div>
            </div>
        </div>

        <!-- Payer Details -->
        <div class="r-details-grid">
            <div class="r-col-left">
                <div>नाम- <span class="val"><?= e($payer_name) ?></span></div>
                <div>पुत्र/पत्नी/पति- <span class="val"><?= e($father_name) ?></span></div>
                <div>भवन संख्या- <span class="val"><?= e($property_no) ?></span></div>
                <div>मुहल्ला- <span class="val"><?= e($mohalla) ?></span></div>
                <div>वार्षिक मूल्यांकन- <span class="val">₹ <?= money($arv_amount) ?></span></div>
                <div>मोबाइल न०- <span class="val"><?= e($payer_mobile) ?></span></div>
                <div>ज़ोन- <span class="val"><?= e($zone) ?></span></div>
                <div>वार्ड- <span class="val"><?= e($ward) ?></span></div>
                <div>पता- <span class="val"><?= e($address) ?></span></div>
            </div>
            <div class="r-col-right">
                <div>रसीद संख्या - <span class="val"><?= e($receipt_no) ?></span></div>
                <div>बिल संख्या - <span class="val"><?= e($order_number) ?></span></div>
                <div>संपत्ति आईडी - <span class="val"><?= e($property_id_display) ?></span></div>
                <div>मांग संख्या/एड्रेस कोड - <span class="val">N/A</span></div>
            </div>
        </div>

        <!-- Table -->
        <table class="r-table">
            <thead>
                <tr>
                    <th class="text-left">वित्तीय-वर्ष</th>
                    <th>सामान्य कर</th>
                    <th>जल कर</th>
                    <th>सीवर कर</th>
                    <th>छूट</th>
                    <th>ब्याज/पेनल्टी</th>
                    <th>योग</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sum_house = 0;
                $sum_water = 0;
                $sum_sewer = 0;
                $sum_rebate = 0;
                $sum_penalty = 0;
                $sum_total = 0;

                if (!empty($years)):
                    foreach ($years as $year):
                        $fy = financialYear(rowValue($year, ['financial_year', 'year'], 'N/A'));
                        $h_tax = (float)rowValue($year, ['house_tax', 'property_tax', 'house_tax_current'], 0);
                        $w_tax = (float)rowValue($year, ['water_tax', 'water_tax_current'], 0);
                        $s_tax = (float)rowValue($year, ['sewer_tax', 'sewer_tax_current'], 0);
                        $rebate = (float)rowValue($year, ['rebate'], 0);
                        $penalty = (float)rowValue($year, ['penalty', 'interest'], 0);

                        $row_total = ($h_tax + $w_tax + $s_tax) - $rebate + $penalty;
                        if ($row_total <= 0) {
                            $row_total = (float)rowValue($year, ['total_tax', 'amount'], 0);
                            if ($h_tax == 0 and $w_tax == 0 and $s_tax == 0) $h_tax = $row_total;
                        }

                        $sum_house += $h_tax;
                        $sum_water += $w_tax;
                        $sum_sewer += $s_tax;
                        $sum_rebate += $rebate;
                        $sum_penalty += $penalty;
                        $sum_total += $row_total;
                ?>
                        <tr>
                            <td class="text-left" style="color: var(--brand-navy);"><strong><?= e($fy) ?></strong></td>
                            <td><?= money($h_tax) ?></td>
                            <td><?= money($w_tax) ?></td>
                            <td><?= money($s_tax) ?></td>
                            <td style="color: green;"><?= money($rebate) ?></td>
                            <td style="color: red;"><?= money($penalty) ?></td>
                            <td style="color: var(--brand-navy);"><strong><?= money($row_total) ?></strong></td>
                        </tr>
                    <?php
                    endforeach;
                else:
                    ?>
                    <tr>
                        <td class="text-left"><strong>कर विवरण (N/A)</strong></td>
                        <td><?= money($demand_amount) ?></td>
                        <td>0.00</td>
                        <td>0.00</td>
                        <td>0.00</td>
                        <td>0.00</td>
                        <td><?= money($demand_amount) ?></td>
                    </tr>
                <?php endif; ?>

                <tr>
                    <td colspan="6" class="text-left" style="color: var(--brand-navy);">अतिरिक्त शुल्क (फॉर्म/बोरिंग) / एडवांस्ड</td>
                    <td><?= money($form_fee + $other_amount + $boring_charge - $advance_received) ?></td>
                </tr>

                <tr>
                    <td colspan="6" class="text-left" style="color: var(--brand-navy); font-size: 15px;"><strong>कुल जमा राशि</strong></td>
                    <td style="color: var(--brand-orange); font-size: 15px;"><strong>₹ <?= money($total_amount) ?></strong></td>
                </tr>
            </tbody>
        </table>

        <!-- Footer Summary -->
        <div class="r-footer-info">
            <div><strong>योग शब्दों में - </strong>Rupees <?= e($amount_in_words) ?> only and zero</div>
            <div><strong>माध्यम: </strong><?= e(ucfirst($payment_mode)) ?> / NB Transaction No - <?= e($transaction_id) ?></div>
        </div>

        <!-- Signatures -->
        <div class="r-signatures">
            <div class="sig-box">
                <div>दिनांक: <?= e($formatted_date) ?></div>
                <div>रोकड़िया</div>
                <div>लेखा अधिकारी/राजस्व अधीक्षक</div>

                <div class="note-text">
                    टिप्पणी:- अनुज्ञप्ति (लाइसेंसों) की दशा में रसीद अनुज्ञप्ति के स्थान पर प्रयुक्त नहीं की जा सकती और यह
                    नगर पालिका के अनुज्ञप्ति अस्वीकार कर देने के अधिकार पर कोई प्रतिकूल प्रभाव नहीं डालती |<br>
                    अवैधानिक निर्माण के गिराये या हटाये जाने हेतु पालिका द्वारा की जाने वाली कार्यवाही पर इसका प्रतिकूल
                    प्रभाव नहीं पड़ेगा
                </div>
            </div>
            <div class="sig-box-right">
                <div>नगर आयुक्त</div>
                <div>जाँच और समाहरण</div>
                <div>प्रभारी लिपिक</div>
                <div style="margin-top: 15px; width: 65px; height: 65px; border: 1.5px dashed var(--brand-navy); display: inline-block; line-height: 65px; font-size: 10px; color: var(--brand-navy); font-weight: bold; border-radius: 4px; background: #f8fafc;">QR CODE</div>
                <div style="font-size: 10px; margin-top: 5px; font-weight: normal; color: var(--text-muted);">ऑनलाइन रसीद देखने<br>हेतु QR कोड स्कैन करें</div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn-print" onclick="window.print()">Print Receipt</button>
        </div>

    </div>

    <?php if ($autoprint and $order): ?>
        <script>
            window.addEventListener('load', function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            });
        </script>
    <?php endif; ?>
</body>

</html>