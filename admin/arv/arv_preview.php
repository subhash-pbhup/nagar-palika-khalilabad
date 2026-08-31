<?php
session_start();

if (empty($_SESSION['ARV_PREVIEW'])) {
    die("No ARV data found. Please calculate again.");
}

$data = $_SESSION['ARV_PREVIEW'];

$assessment_id = $data['assessment_id']; // Direct ID bina 0 ke
$ward_name     = $data['ward_name'];
$total_arv     = $data['total_arv'];
$house_tax     = $data['house_tax_arv'];
$water_tax     = $data['water_tax_arv'];
$total_tax     = $data['total_tax'];
$floors        = $data['floors'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Tax Assessment Preview</title>
    <link href="../img/favicon.ico" rel="icon">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #cbd5e1;
            min-height: 100vh;

            display: flex;
            justify-content: center;
            align-items: center;

            padding: 20px 0;
            overflow-y: auto;
        }

        .preview-card {
            background: white;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid #e2e8f0;
            width: 100%;
            max-width: 800px;
            border-radius: 12px;
        }

        .invoice-table th {
            background: #f8fafc;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.05em;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white;
            }

            .preview-card {
                box-shadow: none;
                border: none;
                max-width: 100%;
            }
        }
    </style>
</head>

<body>

    <div class="preview-card flex flex-col overflow-hidden">

        <div class="p-6 border-b border-dashed border-slate-200 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="h-14 w-14 bg-white rounded flex items-center justify-center overflow-hidden">
                    <img src="logo-main.png" alt="Logo" class="h-12 object-contain">
                </div>
                <div>
                    <h1 class="text-base font-black text-slate-800 uppercase leading-none tracking-tight">Nagar Palika Parishad Khalilabad</h1>
                    <p class="text-[10px] font-bold text-slate-400 mt-1 uppercase">Property Tax Assessment Preview (Digital Copy)</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-[10px] font-bold text-slate-400 uppercase">Assessment Date</p>
                <p class="text-xs font-bold text-slate-700"><?= date('d-M-Y') ?></p>
            </div>
        </div>

        <div class="px-8 py-4 bg-slate-50 flex justify-between items-center border-b border-slate-100">
            <div>
                <p class="text-[9px] font-black text-emerald-600 uppercase tracking-widest">Assessed Ward</p>
                <h2 class="text-base font-bold text-slate-800 uppercase"><?= htmlspecialchars($ward_name) ?></h2>
            </div>
            <div class="text-right">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Assessment ID</p>
                <p class="text-sm font-mono font-bold text-slate-700">#<?= $assessment_id ?></p>
            </div>
        </div>

        <div class="p-0 flex-1">
            <table class="w-full invoice-table">
                <thead>
                    <tr>
                        <th class="px-8 py-3 text-left">Floor Details</th>
                        <th class="px-8 py-3 text-center">Area (Sq.ft)</th>
                        <th class="px-8 py-3 text-center">Rate</th>
                        <th class="px-8 py-3 text-right">ARV Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($floors as $f): ?>
                        <tr class="text-xs sm:text-sm">
                            <td class="px-8 py-3 font-bold text-slate-700"><?= $f['floor'] ?></td>
                            <td class="px-8 py-3 text-center text-slate-500"><?= $f['area'] ?></td>
                            <td class="px-8 py-3 text-center text-slate-500">₹<?= $f['rate'] ?></td>
                            <td class="px-8 py-3 text-right font-bold text-slate-900">₹<?= number_format($f['arv'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="px-8 py-6 bg-slate-50 border-t border-slate-200">
            <div class="flex justify-between items-start gap-10">
                <div class="flex-1">
                    <p class="text-[10px] text-slate-400 leading-normal italic">
                        * Note: Nagar Palika Parishad, Khalilabad. This is a digital preview. Calculations include 10% House Tax and 10% Water Tax based on the Annual Rental Value (ARV).
                    </p>
                </div>
                <div class="w-56 space-y-1">
                    <div class="flex justify-between text-[11px] font-bold text-slate-500 uppercase">
                        <span>Total ARV</span>
                        <span>₹<?= number_format($total_arv, 2) ?></span>
                    </div>
                    <div class="flex justify-between text-[11px] font-bold text-slate-500 uppercase">
                        <span>House Tax (10%)</span>
                        <span>₹<?= number_format($house_tax, 2) ?></span>
                    </div>
                    <div class="flex justify-between text-[11px] font-bold text-slate-500 uppercase">
                        <span>Water Tax (10%)</span>
                        <span>₹<?= number_format($water_tax, 2) ?></span>
                    </div>
                    <div class="pt-2 mt-2 border-t border-slate-300 flex justify-between items-center">
                        <span class="text-xs font-black text-slate-800 uppercase">Grand Total</span>
                        <span class="text-2xl font-black text-emerald-600">₹<?= number_format($total_tax, 2) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="no-print p-4 bg-white border-t border-slate-100 flex justify-between gap-4">
            <button onclick="history.back()" class="px-6 py-2 text-xs font-bold text-slate-400 hover:text-slate-600 transition">
                ← Go Back
            </button>
            <div class="flex gap-2">
                <button onclick="window.print()" class="px-6 py-2 rounded-lg bg-slate-100 text-slate-600 font-bold text-xs hover:bg-slate-200 transition">
                    Print Preview
                </button>
                <a href="store_arv.php" target="_blank"
                    class="px-8 py-2 rounded-lg bg-emerald-600 text-white font-bold text-xs shadow-lg shadow-emerald-200 hover:bg-emerald-700 transition active:scale-95 inline-flex items-center justify-center">
                    Confirm & Save Record
                </a>
            </div>
        </div>
    </div>

</body>

</html>