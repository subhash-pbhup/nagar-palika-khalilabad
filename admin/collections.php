<?php
session_start();
include "include/header.php";

// ==========================================
// 1. FILTER & SEARCH LOGIC
// ==========================================
$zone_id = isset($_GET['zone_id']) ? $_GET['zone_id'] : '';
$ward_id = isset($_GET['ward_id']) ? $_GET['ward_id'] : '';
$payment_made_at = isset($_GET['payment_made_at']) ? $_GET['payment_made_at'] : '';
$collector = isset($_GET['collector']) ? $_GET['collector'] : '';
$financial_year = isset($_GET['financial_year']) ? $_GET['financial_year'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$holding_no = isset($_GET['holding_no']) ? trim($_GET['holding_no']) : '';
$receipt_no = isset($_GET['receipt_no']) ? trim($_GET['receipt_no']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : 'Completed'; // Default Tab

// Pagination variables
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Total records dummy variable for UI
$total_records = 150;
$total_pages = ceil($total_records / $limit);

// ==========================================
// DUMMY DATA FOR UI DEMONSTRATION
// ==========================================
$collections = [
    [
        'id' => 1,
        'new_holding' => 'BMC381143005089',
        'zone_id' => 'Zone 1',
        'ward' => '19',
        'bill_no' => 'RECEIPT_1777959003',
        'payee' => 'SYED SHAHZAD HUSSAIN ZAIDI',
        'total_tax' => '383635.00',
        'bill_date' => '2026-03-31',
        'mode' => 'Cash',
        'payment_made_at' => 'TC',
        'collected_by' => 'Amit Kumar Srivastava'
    ],
    [
        'id' => 2,
        'new_holding' => 'BMC223301075795',
        'zone_id' => 'Zone 1',
        'ward' => '25',
        'bill_no' => 'RECEIPT_1775389837',
        'payee' => 'PRIYANKA KUMARI',
        'total_tax' => '4765.00',
        'bill_date' => '2026-03-31',
        'mode' => 'Cash',
        'payment_made_at' => 'TC',
        'collected_by' => 'ANURAG KUMAR SINGH'
    ],
    [
        'id' => 3,
        'new_holding' => 'BMC223311075794',
        'zone_id' => 'Zone 1',
        'ward' => '20',
        'bill_no' => 'RECEIPT_1775389778',
        'payee' => 'PRIYANKA KUMARI',
        'total_tax' => '13437.00',
        'bill_date' => '2026-03-31',
        'mode' => 'Cash',
        'payment_made_at' => 'TC',
        'collected_by' => 'RAJIV KUMAR'
    ],
    [
        'id' => 4,
        'new_holding' => 'BMC293111038230',
        'zone_id' => 'Zone 1',
        'ward' => '18',
        'bill_no' => 'RECEIPT_1775389596',
        'payee' => 'ARCHANA JHA',
        'total_tax' => '8465.00',
        'bill_date' => '2026-03-31',
        'mode' => 'Online',
        'payment_made_at' => 'Portal',
        'collected_by' => 'RAJIV KUMAR'
    ]
];

// ==========================================
// DYNAMIC TOTALS
// ==========================================
$displayed_count = is_array($collections) ? count($collections) : 0;

$displayed_total = 0.0;
foreach ($collections as $collection) {
    $displayed_total += (float)($collection['total_tax'] ?? 0);
}

// Keep dummy pagination consistent with the rows currently displayed.
$total_records = $displayed_count;
$total_pages = max(1, (int)ceil($total_records / $limit));
if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}
?>


<!-- Custom CSS for Sticky Columns Data Table -->
<style>
    .sticky-table-wrapper {
        overflow-x: auto;
        overflow-y: hidden;
        position: relative;
        width: 100%;
        -webkit-overflow-scrolling: touch;
    }

    .sticky-table-wrapper table {
        min-width: 1180px;
        border-collapse: separate;
        border-spacing: 0;
    }

    .sticky-col {
        position: sticky;
        background: #ffffff;
    }

    tbody tr:hover .sticky-col {
        background-color: #f8fafc;
    }

    thead .sticky-col {
        background-color: #f8fafc;
    }

    .col-sr-no {
        left: 0;
        width: 70px;
        min-width: 70px;
        z-index: 20;
        border-right: 1px solid #e2e8f0;
    }

    .col-holding {
        left: 70px;
        width: 170px;
        min-width: 170px;
        z-index: 20;
        border-right: 1px solid #e2e8f0;
    }

    .col-receipt {
        left: 240px;
        width: 190px;
        min-width: 190px;
        z-index: 20;
        border-right: 1px solid #cbd5e1;
        box-shadow: 3px 0 6px -3px rgba(0, 0, 0, .10);
    }

    .col-actions {
        right: 0;
        width: 380px;
        min-width: 380px;
        background-color: #ffffff;
        z-index: 20;
        border-left: 1px solid #cbd5e1;
        box-shadow: -3px 0 6px -3px rgba(0, 0, 0, .10);
    }

    thead .col-sr-no,
    thead .col-holding,
    thead .col-receipt,
    thead .col-actions {
        z-index: 40;
    }

    tfoot .sticky-col {
        z-index: 25;
    }


    .sticky-table-wrapper::-webkit-scrollbar {
        height: 8px;
    }

    .sticky-table-wrapper::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 9999px;
    }

    .sticky-table-wrapper::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    .action-btn {
        width: 32px;
        height: 32px;
        min-width: 32px;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: .2s ease;
        font-size: 13px;
    }

    @media (max-width: 900px) {
        .col-actions {
            width: 350px;
            min-width: 350px;
        }
    }

    /* Responsive: keep page inside viewport and make table touch-friendly */
    html,
    body {
        max-width: 100%;
        overflow-x: hidden;
    }

    .sticky-table-wrapper {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
    }

    .sticky-table-wrapper table {
        width: max-content;
        min-width: 1180px;
    }

    .col-actions {
        width: 300px;
        min-width: 300px;
        max-width: 300px;
    }

    .action-btn {
        width: 32px;
        height: 32px;
        min-width: 32px;
        max-width: 32px;
        flex: 0 0 32px;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
    }

    @media (max-width:1024px) {
        main {
            padding-left: 12px !important;
            padding-right: 12px !important;
        }

        .sticky-table-wrapper table {
            min-width: 1120px;
        }

        .col-sr-no {
            width: 60px;
            min-width: 60px;
        }

        .col-holding {
            left: 60px;
            width: 155px;
            min-width: 155px;
        }

        .col-receipt {
            left: 215px;
            width: 175px;
            min-width: 175px;
        }

        .col-actions {
            width: 285px;
            min-width: 285px;
            max-width: 285px;
        }
    }

    @media (max-width:640px) {
        main {
            padding: 8px !important;
        }

        form .grid {
            grid-template-columns: 1fr !important;
            gap: 10px !important;
        }

        form .flex.items-center.gap-2 {
            flex-wrap: wrap;
        }

        .sticky-table-wrapper table {
            min-width: 1080px;
        }

        .sticky-table-wrapper th,
        .sticky-table-wrapper td {
            font-size: 12px;
        }

        .col-sr-no {
            width: 52px;
            min-width: 52px;
        }

        .col-holding {
            left: 52px;
            width: 145px;
            min-width: 145px;
        }

        .col-receipt {
            left: 197px;
            width: 165px;
            min-width: 165px;
        }

        .col-actions {
            width: 275px;
            min-width: 275px;
            max-width: 275px;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            min-width: 32px;
            max-width: 32px;
            font-size: 12px;
        }

        .p-4.border-t.border-slate-200.flex {
            flex-wrap: wrap;
        }
    }

    @media (max-width:380px) {
        main {
            padding: 5px !important;
        }

        .sticky-table-wrapper table {
            min-width: 1040px;
        }

        .col-sr-no {
            width: 48px;
            min-width: 48px;
        }

        .col-holding {
            left: 48px;
            width: 135px;
            min-width: 135px;
        }

        .col-receipt {
            left: 183px;
            width: 155px;
            min-width: 155px;
        }

        .col-actions {
            width: 265px;
            min-width: 265px;
            max-width: 265px;
        }
    }


    /* =========================================================
   MOBILE TABLE FIX
   On phones, do NOT keep left/right columns sticky.
   This prevents Zone/Ward/Payee/Amount columns from being
   squeezed between the fixed columns.
   The complete table remains available by horizontal swipe.
   ========================================================= */
    @media (max-width: 640px) {
        .sticky-table-wrapper {
            overflow-x: auto !important;
            overflow-y: hidden !important;
            width: 100% !important;
            display: block !important;
        }

        .sticky-table-wrapper table {
            position: static !important;
            width: max-content !important;
            min-width: 1180px !important;
            table-layout: auto !important;
        }

        .sticky-table-wrapper .sticky-col,
        .sticky-table-wrapper thead .sticky-col,
        .sticky-table-wrapper tfoot .sticky-col {
            position: static !important;
            left: auto !important;
            right: auto !important;
            z-index: auto !important;
            box-shadow: none !important;
            border-left: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
        }

        .sticky-table-wrapper .col-sr-no {
            width: 55px !important;
            min-width: 55px !important;
        }

        .sticky-table-wrapper .col-holding {
            width: 150px !important;
            min-width: 150px !important;
        }

        .sticky-table-wrapper .col-receipt {
            width: 175px !important;
            min-width: 175px !important;
        }

        .sticky-table-wrapper .col-actions {
            width: 285px !important;
            min-width: 285px !important;
            max-width: 285px !important;
        }

        .sticky-table-wrapper th,
        .sticky-table-wrapper td {
            white-space: nowrap !important;
        }

        .sticky-table-wrapper .action-btn {
            width: 32px !important;
            height: 32px !important;
            min-width: 32px !important;
            max-width: 32px !important;
            flex: 0 0 32px !important;
        }
    }

    /* Very small phones */
    @media (max-width: 380px) {
        .sticky-table-wrapper table {
            min-width: 1140px !important;
        }

        .sticky-table-wrapper .col-actions {
            width: 275px !important;
            min-width: 275px !important;
            max-width: 275px !important;
        }
    }


    /* =========================================================
   TABLET FIX (iPad / Android tablets)
   At tablet widths, sticky left/right columns squeeze the
   middle columns. Keep the complete table horizontally
   scrollable, exactly like mobile.
   Desktop sticky columns remain unchanged above 1024px.
   ========================================================= */
    @media (min-width: 641px) and (max-width: 1024px) {
        .sticky-table-wrapper {
            overflow-x: auto !important;
            overflow-y: hidden !important;
            width: 100% !important;
            display: block !important;
        }

        .sticky-table-wrapper table {
            position: static !important;
            width: max-content !important;
            min-width: 1180px !important;
            table-layout: auto !important;
        }

        .sticky-table-wrapper .sticky-col,
        .sticky-table-wrapper thead .sticky-col,
        .sticky-table-wrapper tfoot .sticky-col {
            position: static !important;
            left: auto !important;
            right: auto !important;
            z-index: auto !important;
            box-shadow: none !important;
            border-left: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
        }

        .sticky-table-wrapper .col-sr-no {
            width: 60px !important;
            min-width: 60px !important;
        }

        .sticky-table-wrapper .col-holding {
            width: 155px !important;
            min-width: 155px !important;
        }

        .sticky-table-wrapper .col-receipt {
            width: 175px !important;
            min-width: 175px !important;
        }

        .sticky-table-wrapper .col-actions {
            width: 300px !important;
            min-width: 300px !important;
            max-width: 300px !important;
        }

        .sticky-table-wrapper th,
        .sticky-table-wrapper td {
            white-space: nowrap !important;
        }
    }


    /* =========================================================
   COLLECTION PAGE - SAME VISUAL LANGUAGE AS ASSESSMENTS
   ========================================================= */
    .collection-page {
        width: 100%;
        max-width: 100%;
    }

    .collection-page .collection-card {
        border-radius: 1rem;
    }

    .collection-page .table-card {
        border-radius: 1rem;
    }

    .collection-page .table-cell-compact {
        padding-top: .75rem;
        padding-bottom: .75rem;
        padding-left: .75rem;
        padding-right: .75rem;
    }

    .collection-page .collection-table th,
    .collection-page .collection-table td {
        white-space: nowrap;
    }

    .collection-page .collection-tabs a {
        text-decoration: none;
    }

    @media (max-width: 640px) {
        .collection-page {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .collection-page .collection-card,
        .collection-page .table-card {
            border-radius: .9rem;
        }

        .collection-page .collection-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .collection-page .collection-table {
            min-width: 1180px;
        }

        .collection-page .filter-actions {
            flex-wrap: wrap;
        }

        .collection-page .filter-actions>* {
            flex: 1 1 auto;
        }
    }
</style>

<main class="flex-1 p-6 space-y-8 overflow-y-auto collection-page">
    <div class="w-full max-w-[1400px] mx-auto space-y-6">

        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 glass p-4 rounded-2xl">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Payments List</h1>
                <p class="text-sm text-slate-500 mt-1">Manage and view property tax payments and receipts</p>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative">
                    <i class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" placeholder="Quick search..." class="pl-10 pr-4 py-2 bg-slate-100 border-none rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 w-64">
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="glass rounded-2xl p-6 text-slate-600 collection-card">
            <form method="GET" action="" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">

                    <!-- Row 1 -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Zone</label>
                        <select name="zone_id" class="w-full bg-white/70 border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition">
                            <option value="">Select Zone</option>
                            <option value="1" <?= $zone_id == '1' ? 'selected' : '' ?>>Zone 1</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Ward</label>
                        <select name="ward_id" class="w-full bg-white/70 border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition">
                            <option value="">Select Ward</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Payment Made At</label>
                        <select name="payment_made_at" class="w-full bg-white/70 border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition">
                            <option value="">--Select Payment M...</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Collector</label>
                        <select name="collector" class="w-full bg-white/70 border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition">
                            <option value="">--Select Collector-</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Financial Year</label>
                        <select name="financial_year" class="w-full bg-white/70 border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition">
                            <option value="">--Select Year--</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Date From</label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" class="w-full bg-white/70 border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition text-slate-500">
                    </div>

                    <!-- Row 2 -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Date To</label>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" class="w-full bg-white/70 border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition text-slate-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Holding No.</label>
                        <input type="text" name="holding_no" value="<?= htmlspecialchars($holding_no) ?>" class="w-full bg-white/70 border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Receipt No.</label>
                        <input type="text" name="receipt_no" value="<?= htmlspecialchars($receipt_no) ?>" class="w-full bg-white/70 border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Status</label>
                        <select name="status" class="w-full bg-white/70 border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition">
                            <option value=""></option>
                        </select>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2 pt-2">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-full shadow-lg transition">Search</button>
                    <a href="collections.php" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-full shadow-lg transition">Reset</a>
                    <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-full shadow-lg transition">Download Excel</button>
                    <button type="button" class="ml-1 text-slate-600 hover:text-slate-800 transition"><i class="fa fa-file text-xl"></i></button>
                </div>
            </form>
        </div>

        <!-- Data Table Card -->
        <div class="glass rounded-2xl overflow-hidden table-card">

            <!-- Tabs & Header -->
            <div class="p-5 border-b border-gray-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-2 flex-wrap collection-tabs">
                    <a href="?payment_status=Completed" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-xs font-semibold transition <?= $payment_status == 'Completed' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Completed</a>
                    <a href="?payment_status=Cheque_Rejected" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-xs font-semibold transition <?= $payment_status == 'Cheque_Rejected' ? 'bg-red-500 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100' ?>">Cheque Rejected</a>
                    <a href="?payment_status=Rejected" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-xs font-semibold transition <?= $payment_status == 'Rejected' ? 'bg-red-500 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100' ?>">Rejected</a>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-slate-500 font-medium">Show <select class="border rounded px-2 py-1">
                            <option>10</option>
                        </select> entries</span>
                </div>
            </div>

            <!-- Scrollable Table Wrapper -->
            <div class="sticky-table-wrapper bg-white collection-table-wrap">
                <table class="w-full text-left text-sm whitespace-nowrap min-w-max collection-table">
                    <thead class="bg-gray-50/80 text-gray-500 text-xs uppercase font-semibold border-b border-gray-200">
                        <tr>
                            <!-- Fixed Left Columns -->
                            <th class="px-3 py-3 sticky-col col-sr-no">Sr. No <i class="fa fa-sort ml-1 text-slate-300"></i></th>
                            <th class="px-3 py-3 sticky-col col-holding">Holding No <i class="fa fa-sort ml-1 text-slate-300"></i></th>
                            <th class="px-3 py-3 sticky-col col-receipt">Receipt No <i class="fa fa-sort ml-1 text-slate-300"></i></th>

                            <!-- Scrollable Middle Columns -->
                            <th class="px-4 py-3">Zone</th>
                            <th class="px-4 py-3 text-left">Ward</th>
                            <th class="px-4 py-3">Payee <i class="fa fa-sort ml-1 text-slate-300"></i></th>
                            <th class="px-4 py-3">Amount <i class="fa fa-sort ml-1 text-slate-300"></i></th>
                            <th class="px-4 py-3">Date <i class="fa fa-sort ml-1 text-slate-800"></i></th>
                            <th class="px-4 py-3">Mode <i class="fa fa-sort ml-1 text-slate-300"></i></th>
                            <th class="px-4 py-3">Payment Made At <i class="fa fa-sort ml-1 text-slate-300"></i></th>
                            <th class="px-4 py-3">Collected By <i class="fa fa-sort ml-1 text-slate-300"></i></th>

                            <!-- Fixed Right Column -->
                            <th class="px-4 py-4 text-center sticky-col col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-gray-700">
                        <?php foreach ($collections as $index => $row): ?>
                            <tr class="hover:bg-gray-50 transition-colors group">
                                <!-- Fixed Left Data -->
                                <td class="px-3 py-3 sticky-col col-sr-no font-medium"><?= $offset + $index + 1 ?></td>
                                <td class="px-3 py-3 sticky-col col-holding font-medium text-slate-800"><?= htmlspecialchars($row['new_holding']) ?></td>
                                <td class="px-3 py-3 sticky-col col-receipt text-indigo-600 font-medium"><?= htmlspecialchars($row['bill_no']) ?></td>

                                <!-- Scrollable Middle Data -->
                                <td class="px-4 py-3"><?= htmlspecialchars($row['zone_id']) ?></td>
                                <td class="px-4 py-3">
                                    <?= htmlspecialchars($row['ward'] ?? ''); ?>
                                </td>
                                <td class="px-5 py-2 uppercase"><?= htmlspecialchars($row['payee']) ?></td>
                                <td class="px-5 py-2 font-bold text-slate-800"><?= number_format($row['total_tax'], 2) ?></td>
                                <td class="px-4 py-3"><?= date('d/m/Y', strtotime($row['bill_date'])) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['mode']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['payment_made_at']) ?></td>
                                <td class="px-5 py-2 uppercase text-slate-600"><?= htmlspecialchars($row['collected_by']) ?></td>

                                <!-- Fixed Right Data -->
                                <td class="px-3 py-3 sticky-col col-actions">
                                    <div class="flex items-center justify-center gap-1.5 whitespace-nowrap">

                                        <a href="view_collection.php?id=<?= (int)$row['id'] ?>"
                                            title="View"
                                            class="action-btn bg-yellow-100 hover:bg-yellow-200 text-yellow-600">
                                            <i class="fa fa-eye"></i>
                                        </a>

                                        <button type="button" title="Edit"
                                            class="action-btn bg-green-100 hover:bg-green-200 text-green-600">
                                            <i class="fa fa-pencil-square-o"></i>
                                        </button>

                                        <button type="button" title="Receipt"
                                            class="action-btn bg-blue-100 hover:bg-blue-200 text-blue-600">
                                            <i class="fa fa-file-text"></i>
                                        </button>

                                        <button type="button" title="Reject"
                                            class="action-btn bg-red-100 hover:bg-red-200 text-red-600">
                                            <i class="fa fa-times-circle"></i>
                                        </button>

                                        <button type="button" title="Profile"
                                            class="action-btn bg-stone-100 hover:bg-stone-200 text-stone-600">
                                            <i class="fa fa-user-circle"></i>
                                        </button>

                                        <button type="button" title="WhatsApp"
                                            class="action-btn bg-emerald-100 hover:bg-emerald-200 text-emerald-600">
                                            <i class="fa fa-whatsapp"></i>
                                        </button>

                                        <button type="button" title="SMS"
                                            class="action-btn bg-slate-100 hover:bg-slate-200 text-slate-700">
                                            <i class="fa fa-comment"></i>
                                        </button>

                                        <button type="button" title="Email"
                                            class="action-btn bg-indigo-100 hover:bg-indigo-200 text-indigo-600">
                                            <i class="fa fa-envelope-o"></i>
                                        </button>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <!-- Totals Row -->
                    <tfoot class="bg-sky-100/50 border-t border-slate-200 font-bold text-slate-800">
                        <tr>
                            <td class="px-3 py-3 sticky-col col-sr-no bg-sky-100/50"></td>
                            <td class="px-3 py-3 sticky-col col-holding bg-sky-100/50"></td>
                            <td class="px-3 py-3 sticky-col col-receipt bg-sky-100/50 text-right pr-5 uppercase">Total</td>

                            <td class="px-5 py-3 bg-sky-100/50"></td>
                            <td class="px-5 py-3 bg-sky-100/50"></td>
                            <td class="px-5 py-3 bg-sky-100/50 text-slate-900 font-extrabold">
                                <?= number_format($displayed_total, 2) ?>
                            </td>
                            <td class="px-5 py-3 bg-sky-100/50"></td>
                            <td class="px-5 py-3 bg-sky-100/50"></td>
                            <td class="px-5 py-3 bg-sky-100/50"></td>
                            <td class="px-5 py-3 bg-sky-100/50"></td>

                            <td class="sticky-col col-actions bg-sky-100/50"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-5 border-t border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-sm text-slate-500 font-medium">Showing <?= $displayed_count > 0 ? 1 : 0 ?> to <?= $displayed_count ?> of <?= $total_records ?> entries</div>
                <div class="flex items-center gap-1">
                    <a href="#" class="px-3 py-1.5 border border-slate-300 rounded text-sm text-slate-600">Previous</a>
                    <a href="#" class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm font-semibold">1</a>
                    <a href="#" class="px-3 py-1.5 border border-slate-300 rounded text-sm text-slate-600">2</a>
                    <a href="#" class="px-3 py-1.5 border border-slate-300 rounded text-sm text-slate-600">Next</a>
                </div>
            </div>
        </div>

    </div>
</main>

<?php include "include/footer.php"; ?>