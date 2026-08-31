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
    <title>Nagar Palika Parishad Khalilabad - Demand <?= $prop_id ?></title>
    <link href="img/favicon.ico" rel="icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Hind:wght@400;600;700&display=swap');

        body {
            font-family: 'Hind', sans-serif;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .page {
                margin: 0;
                box-shadow: none;
                width: 100%;
                height: 100%;
                padding: 5mm;
            }

            body {
                background: white;
            }
        }

        .setwidth {
            width: 170px !important;
            margin-bottom: 8px;
        }

        @media (max-width: 768px) {

            .main-wrapper,
            .container,
            .bill-container {
                width: 100% !important;
                padding: 10px !important;
            }

            table {
                width: 100% !important;
                font-size: 12px !important;
            }

            table th,
            table td {
                padding: 6px !important;
                word-break: break-word;
            }

            .header-section {
                flex-direction: column !important;
                text-align: center !important;
            }
        }
    </style>
</head>

<body class="bg-gray-100 antialiased p-4">

    <div class="max-w-4xl mx-auto mb-4 flex justify-center no-print">
        <button onclick="window.print()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg shadow-md transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
            </svg>
            Print Bill
        </button>
    </div>

    <div class="page max-w-[210mm] min-h-[297mm] mx-auto bg-white shadow-2xl p-8 border border-gray-200 overflow-hidden relative">

        <header class="flex justify-between items-center border-b-2 border-green-700 pb-4 mb-6">
            <img src="logo-main.png" class="h-16 w-auto" alt="Nagar Nigam Logo">
            <div class="text-center">
                <p class="text-yellow-600 font-bold text-xs uppercase tracking-widest"> <img src="sw-logo.png" class="setwidth w-auto" alt="Swachh Bharat Logo"></p>
                <h1 class="text-2xl font-bold text-green-800">नगर निगम देवरिया</h1>
                <p class="text-sm font-semibold text-gray-600">संपत्ति कर डिमांड बिल (2024-25)</p>
            </div>
            <img src="logo-1.png" class="h-16 w-auto" alt="UP Govt Logo">
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-sm text-gray-700">
            <div class="space-y-1">
                <p><span class="font-bold text-gray-900">संपत्ति आईडी:</span> <?= $prop_id ?></p
                    <p><span class="font-bold text-gray-900">बिल क्रमांक :</span> <?= $bill_no ?></p>
                <p><span class="font-bold text-gray-900">नाम:</span> <?= $owner ?></p>
                <p><span class="font-bold text-gray-900">पिता/पति:</span> <?= $details['father_husband_pan'] ?? '-' ?></p>
                <p><span class="font-bold text-gray-900">मोबाइल:</span> <?= $mobile ?></p>
                <p><span class="font-bold text-gray-900">वार्ड:</span> <?= $ward ?></p>
                <p><span class="font-bold text-gray-900">सड़क:</span> <?= $details['road'] ?? '-' ?></p>
                <p><span class="font-bold text-gray-900">वार्षिक मूल्यांकन:</span> ₹<?= $details['annual_value'] ?? '0' ?></p>
            </div>
            <div class="flex flex-col items-end">
                <div class="text-right mb-4">
                    <p class="font-bold text-green-700 uppercase">Status: <?= $details['property_status'] ?? 'New' ?></p>
                    <p class="text-xs text-gray-500">Date: <?= date('d-m-Y') ?></p>
                </div>
                <table class="w-full border text-[10px]">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-1">तल</th>
                            <th class="border p-1">क्षेत्रफल</th>
                            <th class="border p-1">प्रकार</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($floors as $floor): ?>
                            <tr class="text-center">
                                <td class="border p-1"><?= $floor['floor_no'] ?></td>
                                <td class="border p-1"><?= $floor['build_up_area'] ?> sqft</td>
                                <td class="border p-1"><?= $floor['construction_type'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="my-6 p-3 bg-gray-50 border-l-4 border-green-600 text-xs italic">
            A sum of Rs. <strong><?= $total_due ?></strong> is demand on the property with holding No. <strong><?= $details['new_holding'] ?? 'N/A' ?></strong> for FY 2025-26.
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-green-800 text-white">
                        <th class="border p-2 text-left">वित्तीय वर्ष</th>
                        <th class="border p-2">सामान्य कर</th>
                        <th class="border p-2">जल कर</th>
                        <th class="border p-2">सीवर कर</th>
                        <th class="border p-2">ब्याज</th>
                        <th class="border p-2">कुल योग</th>
                    </tr>
                </thead>
                <tbody class="text-center font-medium">
                    <tr class="bg-gray-50">
                        <td class="border p-2 text-left font-semibold">2024-25 (Arrear)</td>
                        <td class="border p-2">0.00</td>
                        <td class="border p-2">0.00</td>
                        <td class="border p-2">0.00</td>
                        <td class="border p-2">0.00</td>
                        <td class="border p-2">0.00</td>
                    </tr>
                    <tr class="bg-white">
                        <td class="border p-2 text-left font-semibold">2025-26 (Current)</td>
                        <td class="border p-2"><?= number_format($calc['property_tax'] ?? 0, 2) ?></td>
                        <td class="border p-2"><?= number_format($calc['water_tax'] ?? 0, 2) ?></td>
                        <td class="border p-2">0.00</td>
                        <td class="border p-2">0.00</td>
                        <td class="border p-2"><?= $total_due ?></td>
                    </tr>
                    <tr class="bg-green-50 font-bold text-lg">
                        <td colspan="5" class="border p-2 text-right">कुल देय राशि:</td>
                        <td class="border p-2 text-green-800">₹<?= $total_due ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <footer class="mt-10 flex justify-between items-start gap-6 pt-6 border-t border-gray-100">
            <div class="flex-1 text-[10px] leading-relaxed text-gray-600">
                <h3 class="font-bold text-gray-800 mb-1 underline">Discount Policy:</h3>
                <ul class="list-disc pl-4 space-y-1">
                    <li>31-07-2024 तक ऑनलाइन भुगतान पर चालू मांग पर 10% छूट प्रभावी होगी।</li>
                    <li>01-08-2024 से 31-08-2024 तक भुगतान पर 8% छूट प्रभावी होगी।</li>
                </ul>
                <div class="mt-4 flex gap-4 opacity-75">
                    <span>🌐 www.nagarnigamgkp.org.in</span>
                    <span>📞 05514056585</span>
                </div>
            </div>
            <div class="w-32 text-center">
                <div class="border p-1 rounded-lg">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=pay_gkp_<?= $id ?>" class="w-full" alt="QR Code">
                </div>
                <p class="text-[9px] mt-1 font-bold text-gray-500 uppercase">Scan to Pay Online</p>
            </div>
        </footer>

        <div class="page-break"></div>

        <div class="mt-12">
            <h2 class="text-xl font-bold text-center mb-6 border-b pb-2 text-gray-800 italic uppercase">Important Notice</h2>
            <ol class="text-[11px] space-y-3 text-gray-700 leading-snug">
                <li>जी०आई०एस० सर्वेक्षण के आधार पर पुनरीक्षण प्रक्रिया पूर्ण होने के उपरांत अंतर धनराशि नियमनुसार प्रभावी तिथि से अनिवार्य रूप से देय होगी।</li>
                <li>ऑनलाइन भुगतान हेतु वेबसाइट <strong>www.nagarnigamgkp.org</strong> का उपयोग करें।</li>
                <li>समय से भुगतान न करने पर नगर निगम अधिनियम 1959 के तहत कुर्की की कार्यवाही संभव है।</li>
                <li>यह कंप्यूटर जनित बिल है, इस पर हस्ताक्षर की आवश्यकता नहीं है।</li>
            </ol>
            <div class="mt-20 text-right">
                <p class="font-bold text-lg text-green-900 underline decoration-double">नगर निगम गोरखपुर</p>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto mt-6 text-center no-print">
        <button onclick="window.print()" class="bg-blue-600 text-white px-10 py-3 rounded-full font-bold shadow-lg hover:bg-blue-700 transition transform hover:scale-105">
            Click Here to Print Bill
        </button>
    </div>

</body>

</html>