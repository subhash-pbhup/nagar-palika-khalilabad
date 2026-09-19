<?php
// Error Handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "db.php";
require_once "calculation_helper.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. Fetch Dynamic Data from DB
$query = "SELECT a.*, o.owner_name, o.father_husband_pan, o.mobile as owner_mobile 
          FROM assessments a 
          LEFT JOIN assessment_owners o ON a.id = o.assessment_id 
          WHERE a.id = $id";


/* ================= FETCH PROPERTY ARV DETAILS ================= */

$arvStmt = $conn->prepare("
    SELECT property_id, bill_no, total_tax, house_tax_amount, water_tax_amount
    FROM property_arv_details
    WHERE assessment_id = ?
    ORDER BY id DESC LIMIT 1
");

$arvStmt->bind_param("i", $id);
$arvStmt->execute();
$arvData = $arvStmt->get_result()->fetch_assoc();

$prop_id = $arvData['property_id'] ?? 'N/A';
$bill_no = $arvData['bill_no'] ?? 'N/A';



$res = $conn->query($query);
$details = $res->fetch_assoc() ?: [];

// 2. Fetch Floor Data
$floor_res = $conn->query("SELECT * FROM assessment_floors WHERE assessment_id = $id");
$floors = [];
while ($f = $floor_res->fetch_assoc()) {
    $floors[] = $f;
}

// 3. Calculation Logic from Helper
$calc = calculatePropertyTax($conn, $id);

// Dynamic Variables Setup

$owner = $details['owner_name'] ?? 'N/A';
$mobile = $details['owner_mobile'] ?? 'N/A';
$ward = $details['ward'] ?? 'N/A';
$total_due = number_format($calc['total_demand'] ?? 0, 2);
?>

<!DOCTYPE html>
<html lang="hi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>नगर पालिका परिषद खलीलाबाद - Demand <?= htmlspecialchars($prop_id) ?></title>
    <link href="img/favicon.ico" rel="icon">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600;700&display=swap');

        :root {
            --navy: #0b1f4d;
            --navy-2: #132e63;
            --orange: #f28c00;
            --orange-light: #fff3df;
            --cream: #fffaf2;
            --border: #d9c9ad;
            --text: #263238;
            --muted: #64748b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            background: #eef1f5;
            color: var(--text);
            font-family: 'Hind', Arial, sans-serif;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #fff;
            position: relative;
            overflow: hidden;
            border: 1px solid #d7dce4;
            box-shadow: 0 12px 35px rgba(11, 31, 77, .14);
        }

        .top-strip {
            height: 8px;
            background: linear-gradient(90deg, var(--navy) 0 72%, var(--orange) 72% 100%);
        }

        .watermark {
            position: absolute;
            right: -70px;
            top: 150px;
            width: 330px;
            height: 330px;
            opacity: .035;
            pointer-events: none;
        }

        .content {
            padding: 18mm 15mm 14mm;
            position: relative;
            z-index: 1;
        }

        .header {
            display: grid;
            grid-template-columns: 105px 1fr 105px;
            align-items: center;
            gap: 14px;
            padding-bottom: 14px;
            border-bottom: 3px solid var(--orange);
        }

        .logo {
            width: 100px;
            height: 100px;
            object-fit: contain;
        }

        .header-center {
            text-align: center;
        }

        .govt-line {
            font-size: 12px;
            color: var(--orange);
            font-weight: 700;
            letter-spacing: 1.2px;
        }

        .title {
            margin: 2px 0;
            color: var(--navy);
            font-size: 27px;
            line-height: 1.15;
            font-weight: 700;
        }

        .subtitle {
            color: #475569;
            font-size: 14px;
            font-weight: 600;
        }

        .year-badge {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 13px;
            color: #fff;
            background: var(--navy);
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .side-mark {
            width: 100px;
            height: 100px;
            border: 2px solid var(--orange);
            border-radius: 50%;
            padding: 5px;
            object-fit: contain;
            justify-self: end;
        }

        .bill-ribbon {
            margin: 16px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 14px;
            color: #fff;
            background: var(--navy);
            border-radius: 6px;
            border-left: 7px solid var(--orange);
        }

        .bill-ribbon strong {
            font-size: 17px;
        }

        .bill-ribbon span {
            font-size: 11px;
            opacity: .9;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 16px 0 8px;
            color: var(--navy);
            font-size: 15px;
            font-weight: 700;
        }

        .section-title::before {
            content: "";
            width: 5px;
            height: 20px;
            background: var(--orange);
            border-radius: 4px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 22px;
            padding: 13px 15px;
            background: var(--cream);
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .info-item {
            display: grid;
            grid-template-columns: 125px 1fr;
            gap: 7px;
            min-height: 25px;
            font-size: 12px;
            border-bottom: 1px dashed #e1d5c2;
            padding-bottom: 3px;
        }

        .info-item:last-child,
        .info-item:nth-last-child(2) {
            border-bottom: 0;
        }

        .label {
            color: var(--navy);
            font-weight: 700;
        }

        .value {
            color: #334155;
            font-weight: 500;
        }

        .status {
            color: #166534;
            font-weight: 700;
        }

        .floor-table,
        .tax-table {
            width: 100%;
            border-collapse: collapse;
        }

        .floor-table th {
            background: var(--orange);
            color: #fff;
            border: 1px solid #d77b00;
            padding: 6px;
            font-size: 10px;
        }

        .floor-table td {
            border: 1px solid #e2e8f0;
            padding: 6px;
            text-align: center;
            font-size: 10px;
        }

        .floor-table tr:nth-child(even) td {
            background: #fffaf2;
        }

        .demand-note {
            margin: 15px 0;
            padding: 11px 14px;
            background: var(--orange-light);
            border: 1px solid #f6c56d;
            border-left: 6px solid var(--orange);
            border-radius: 6px;
            font-size: 11px;
            line-height: 1.6;
        }

        .tax-table th {
            background: var(--navy);
            color: #fff;
            border: 1px solid #0a193c;
            padding: 8px 6px;
            font-size: 11px;
        }

        .tax-table td {
            border: 1px solid #d9dee7;
            padding: 8px 6px;
            text-align: center;
            font-size: 11px;
        }

        .tax-table td:first-child {
            text-align: left;
        }

        .tax-table .current td {
            background: #fffaf2;
        }

        .tax-table .total td {
            background: var(--orange-light);
            color: var(--navy);
            font-size: 14px;
            font-weight: 700;
        }

        .total-amount {
            color: #c55f00 !important;
            font-size: 16px !important;
        }

        .footer {
            display: grid;
            grid-template-columns: 1fr 125px;
            gap: 18px;
            margin-top: 20px;
            padding-top: 13px;
            border-top: 2px solid var(--navy);
        }

        .policy-title {
            color: var(--navy);
            font-weight: 700;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .policy {
            margin: 0;
            padding-left: 17px;
            color: #475569;
            font-size: 9.5px;
            line-height: 1.6;
        }

        .contact {
            margin-top: 8px;
            color: var(--navy);
            font-size: 9px;
            font-weight: 600;
        }

        .qr-box {
            text-align: center;
        }

        .qr {
            width: 92px;
            height: 92px;
            padding: 4px;
            border: 2px solid var(--navy);
            border-radius: 7px;
            background: #fff;
        }

        .qr-label {
            margin-top: 3px;
            color: var(--navy);
            font-size: 8px;
            font-weight: 700;
        }

        .notice-page {
            page-break-before: always;
            margin-top: 30px;
            padding-top: 12px;
        }

        .notice-heading {
            color: var(--navy);
            text-align: center;
            font-size: 19px;
            font-weight: 700;
            border-bottom: 2px solid var(--orange);
            padding-bottom: 7px;
            margin-bottom: 18px;
        }

        .notice-list {
            margin: 0;
            padding-left: 20px;
            color: #475569;
            font-size: 11px;
            line-height: 1.8;
        }

        .signature {
            margin-top: 55px;
            text-align: right;
            color: var(--navy);
            font-size: 15px;
            font-weight: 700;
        }

        .print-bar {
            width: 210mm;
            margin: 0 auto 15px;
            display: flex;
            justify-content: center;
        }

        .print-btn {
            border: 0;
            padding: 10px 22px;
            border-radius: 6px;
            background: var(--navy);
            color: #fff;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(11, 31, 77, .18);
        }

        .print-btn:hover {
            background: var(--navy-2);
        }

        @media (max-width: 850px) {
            body {
                padding: 10px;
            }

            .page,
            .print-bar {
                width: 100%;
            }

            .content {
                padding: 20px;
            }
        }

        @media (max-width: 650px) {
            .header {
                grid-template-columns: 80px 1fr;
            }

            .side-mark {
                display: none;
            }

            .logo {
                width: 78px;
                height: 78px;
            }

            .title {
                font-size: 21px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .footer {
                grid-template-columns: 1fr;
            }

            .qr-box {
                text-align: left;
            }

            .info-item {
                grid-template-columns: 110px 1fr;
            }
        }

        @media print {
            @page {
                size: A4;
                margin: 0;
            }

            body {
                padding: 0;
                background: #fff;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print {
                display: none !important;
            }

            .page {
                width: 210mm;
                min-height: 297mm;
                margin: 0;
                border: 0;
                box-shadow: none;
            }

            .content {
                padding: 14mm 13mm 11mm;
            }

            .watermark {
                display: block;
            }
        }
    </style>
</head>

<body>

    <div class="print-bar no-print">
        <button onclick="window.print()" class="print-btn">🖨 Print Demand Bill</button>
    </div>

    <div class="page">
        <div class="top-strip"></div>

        <div class="content">

            <!-- <img src="logo-main.png" class="watermark" alt=""> -->

            <header class="header">
                <img src="logo-main.png" class="logo" alt="नगर पालिका परिषद खलीलाबाद">

                <div class="header-center">
                    <div class="govt-line">उत्तर प्रदेश • स्थानीय निकाय</div>
                    <h1 class="title">नगर पालिका परिषद खलीलाबाद</h1>
                    <div class="subtitle">संपत्ति कर मांग पत्र / PROPERTY TAX DEMAND NOTICE</div>
                    <span class="year-badge">वित्तीय वर्ष 2025-26</span>
                </div>

                <img src="logo-1.png" class="side-mark" alt="">
            </header>

            <div class="bill-ribbon">
                <strong>संपत्ति कर मांग विवरण</strong>
                <span>Demand No.: <?= htmlspecialchars($bill_no) ?></span>
            </div>

            <div class="section-title">करदाता एवं संपत्ति विवरण</div>

            <div class="info-grid">
                <div class="info-item">
                    <span class="label">संपत्ति आईडी</span>
                    <span class="value"><?= htmlspecialchars($prop_id) ?></span>
                </div>
                <div class="info-item">
                    <span class="label">बिल क्रमांक</span>
                    <span class="value"><?= htmlspecialchars($bill_no) ?></span>
                </div>
                <div class="info-item">
                    <span class="label">नाम</span>
                    <span class="value"><?= htmlspecialchars($owner) ?></span>
                </div>
                <div class="info-item">
                    <span class="label">पिता/पति</span>
                    <span class="value"><?= htmlspecialchars($details['father_husband_pan'] ?? '-') ?></span>
                </div>
                <div class="info-item">
                    <span class="label">मोबाइल</span>
                    <span class="value"><?= htmlspecialchars($mobile) ?></span>
                </div>
                <div class="info-item">
                    <span class="label">वार्ड</span>
                    <span class="value"><?= htmlspecialchars($ward) ?></span>
                </div>
                <div class="info-item">
                    <span class="label">सड़क</span>
                    <span class="value"><?= htmlspecialchars($details['road'] ?? '-') ?></span>
                </div>
                <div class="info-item">
                    <span class="label">वार्षिक मूल्यांकन</span>
                    <span class="value">₹<?= htmlspecialchars($details['annual_value'] ?? '0') ?></span>
                </div>
                <div class="info-item">
                    <span class="label">स्थिति</span>
                    <span class="value status"><?= htmlspecialchars($details['property_status'] ?? 'New') ?></span>
                </div>
                <div class="info-item">
                    <span class="label">जारी दिनांक</span>
                    <span class="value"><?= date('d-m-Y') ?></span>
                </div>
            </div>

            <div class="section-title">भवन / तल विवरण</div>

            <table class="floor-table">
                <thead>
                    <tr>
                        <th>तल</th>
                        <th>क्षेत्रफल</th>
                        <th>निर्माण प्रकार</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($floors)): ?>
                        <?php foreach ($floors as $floor): ?>
                            <tr>
                                <td><?= htmlspecialchars($floor['floor_no']) ?></td>
                                <td><?= htmlspecialchars($floor['build_up_area']) ?> sqft</td>
                                <td><?= htmlspecialchars($floor['construction_type']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">कोई तल विवरण उपलब्ध नहीं है।</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="demand-note">
                <strong>मांग सूचना:</strong>
                Holding No. <strong><?= htmlspecialchars($details['new_holding'] ?? 'N/A') ?></strong>
                वाली संपत्ति पर वित्तीय वर्ष 2025-26 के लिए कुल देय राशि
                <strong>₹<?= $total_due ?></strong> है।
            </div>

            <div class="section-title">कर मांग का विवरण</div>

            <table class="tax-table">
                <thead>
                    <tr>
                        <th>वित्तीय वर्ष</th>
                        <th>सामान्य कर</th>
                        <th>जल कर</th>
                        <th>सीवर कर</th>
                        <th>ब्याज</th>
                        <th>कुल योग</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>2024-25 (Arrear)</strong></td>
                        <td>0.00</td>
                        <td>0.00</td>
                        <td>0.00</td>
                        <td>0.00</td>
                        <td>0.00</td>
                    </tr>

                    <tr class="current">
                        <td><strong>2025-26 (Current)</strong></td>
                        <td><?= number_format($calc['property_tax'] ?? 0, 2) ?></td>
                        <td><?= number_format($calc['water_tax'] ?? 0, 2) ?></td>
                        <td>0.00</td>
                        <td>0.00</td>
                        <td><strong><?= $total_due ?></strong></td>
                    </tr>

                    <tr class="total">
                        <td colspan="5" style="text-align:right;">कुल देय राशि:</td>
                        <td class="total-amount">₹<?= $total_due ?></td>
                    </tr>
                </tbody>
            </table>

            <footer class="footer">
                <div>
                    <div class="policy-title">भुगतान / छूट संबंधी सूचना</div>
                    <ul class="policy">
                        <li>31-07-2024 तक ऑनलाइन भुगतान पर चालू मांग पर 10% छूट प्रभावी होगी।</li>
                        <li>01-08-2024 से 31-08-2024 तक भुगतान पर 8% छूट प्रभावी होगी।</li>
                    </ul>

                    <div class="contact">
                        🌐 www.nagarnigankhalilabad.org.in &nbsp;&nbsp; | &nbsp;&nbsp; ☎ 05514056585
                    </div>
                </div>

                <div class="qr-box">
                    <img
                        src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=pay_gkp_<?= urlencode($id) ?>"
                        class="qr"
                        alt="QR Code">
                    <div class="qr-label">SCAN TO PAY ONLINE</div>
                </div>
            </footer>

            <div class="notice-page">
                <div class="notice-heading">महत्वपूर्ण सूचना</div>

                <ol class="notice-list">
                    <li>जी०आई०एस० सर्वेक्षण के आधार पर पुनरीक्षण प्रक्रिया पूर्ण होने के उपरांत अंतर धनराशि नियमनुसार प्रभावी तिथि से अनिवार्य रूप से देय होगी।</li>
                    <li>ऑनलाइन भुगतान हेतु वेबसाइट <strong>www.nagarnigankhalilabad.org</strong> का उपयोग करें।</li>
                    <li>समय से भुगतान न करने पर नगर निगम अधिनियम 1959 के तहत कुर्की की कार्यवाही संभव है।</li>
                    <li>यह कंप्यूटर जनित बिल है, इस पर हस्ताक्षर की आवश्यकता नहीं है।</li>
                </ol>

                <div class="signature">नगर पालिका परिषद खलीलाबाद</div>
            </div>

        </div>
    </div>

</body>

</html>