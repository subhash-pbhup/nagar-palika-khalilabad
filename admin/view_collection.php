<?php
session_start();
include "include/header.php"; // हेडर शामिल करें
?>

<style>
    /* Print Specific Styles */
    @media print {
        body {
            background-color: white !important;
        }

        .no-print {
            display: none !important;
        }

        .print-w-full {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
            border: none !important;
        }

        .print-text-black {
            color: black !important;
        }

        main {
            padding: 0 !important;
            min-height: auto !important;
        }

        .no-print {
            display: none !important;
        }
    }

    /* Responsive: receipt adapts to desktop, tablet and mobile */
    html,
    body {
        max-width: 100%;
        overflow-x: hidden;
    }

    .receipt-responsive {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }

    @media (max-width:1024px) {
        main {
            padding: 12px !important;
        }

        .receipt-responsive {
            padding: 18px !important;
        }

        .receipt-responsive .receipt-header-meta {
            padding-right: 0 !important;
        }
    }

    @media (max-width:640px) {
        main {
            padding: 8px !important;
        }

        .receipt-responsive {
            padding: 12px !important;
            border-radius: 10px !important;
        }

        .page-view-header {
            padding: 10px !important;
            gap: 10px !important;
        }

        .page-view-header h1 {
            font-size: 16px !important;
        }

        .page-view-header .back-btn {
            padding: 7px 11px !important;
            font-size: 12px !important;
        }

        .receipt-responsive .receipt-header {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 14px !important;
            margin-bottom: 18px !important;
            padding-bottom: 14px !important;
        }

        .receipt-responsive .receipt-header-logos {
            justify-content: center !important;
        }

        .receipt-responsive .receipt-header-logos img:first-child {
            height: 38px !important;
        }

        .receipt-responsive .receipt-header-logos img:last-child {
            height: 32px !important;
        }

        .receipt-responsive .receipt-header-meta {
            width: 100% !important;
            padding-right: 0 !important;
            text-align: center !important;
        }

        .receipt-responsive .receipt-header-meta h2 {
            font-size: 15px !important;
            line-height: 1.35 !important;
        }

        .receipt-responsive .receipt-meta-grid {
            grid-template-columns: 1fr !important;
            gap: 3px !important;
        }

        .receipt-responsive .property-info {
            grid-template-columns: 1fr !important;
            gap: 14px !important;
            padding: 10px !important;
            margin-bottom: 16px !important;
        }

        .receipt-responsive .owner-details-grid {
            grid-template-columns: 105px minmax(0, 1fr) !important;
            gap-y: 7px !important;
            font-size: 12px !important;
        }

        .receipt-responsive .floor-table-wrap,
        .receipt-responsive .responsive-table-wrap {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .receipt-responsive .floor-table {
            min-width: 500px;
        }

        .receipt-responsive .floor-table th,
        .receipt-responsive .floor-table td {
            font-size: 11px !important;
            padding: 6px !important;
        }

        .receipt-responsive .payment-declaration {
            padding: 10px !important;
            margin-bottom: 16px !important;
            font-size: 12px !important;
        }

        .receipt-responsive .tax-table {
            min-width: 620px;
        }

        .receipt-responsive .charges-table {
            min-width: 430px;
        }

        .receipt-responsive .tax-table th,
        .receipt-responsive .tax-table td,
        .receipt-responsive .charges-table th,
        .receipt-responsive .charges-table td {
            padding: 7px !important;
            font-size: 11px !important;
            white-space: nowrap;
        }

        .receipt-responsive .payment-info {
            grid-template-columns: 1fr !important;
            gap: 12px !important;
            margin-bottom: 16px !important;
        }

        .receipt-responsive .payment-info table td {
            padding: 7px !important;
            font-size: 11px !important;
        }

        .receipt-responsive .print-btn-wrap {
            margin-bottom: 10px !important;
        }

        .receipt-responsive .print-btn-wrap button {
            padding: 7px 12px !important;
            font-size: 12px !important;
        }
    }

    @media (max-width:380px) {
        .receipt-responsive {
            padding: 9px !important;
        }

        .receipt-responsive .owner-details-grid {
            grid-template-columns: 92px minmax(0, 1fr) !important;
            font-size: 11px !important;
        }

        .receipt-responsive .receipt-header-meta h2 {
            font-size: 14px !important;
        }
    }
</style>

<main class="flex-1 p-6 bg-slate-50 min-h-screen space-y-6">

    <!-- Top Header (No Print) -->
    <div class="w-full max-w-7xl mx-auto mb-4 flex justify-between items-center no-print page-view-header glass p-4 rounded-2xl">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600 border border-slate-200">
                <i class="fa fa-file-text-o text-lg"></i>
            </div>
            <h1 class="text-xl font-bold text-slate-800">View Payment Details</h1>
        </div>
        <a href="javascript:history.back()" class="px-5 py-2 rounded-full border border-emerald-500 back-btn text-emerald-600 font-semibold text-sm hover:bg-emerald-50 transition flex items-center gap-2">
            <i class="fa fa-arrow-left"></i> Back
        </a>
    </div>

    <!-- Receipt Container -->
    <div class="w-full max-w-7xl mx-auto glass rounded-2xl p-5 md:p-7 print-w-full relative receipt-responsive">
        <!-- Print Button -->
        <div class="no-print flex justify-end mb-4 print-btn-wrap">
            <button onclick="window.print()"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-full border border-rose-500 text-rose-500 text-sm font-semibold hover:bg-rose-50 transition"
                title="Print Receipt">
                <i class="fa fa-print"></i>
                <span>Print</span>
            </button>
        </div>

        <!-- Receipt Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start mb-6 gap-5 border-b receipt-header border-slate-200 pb-6">
            <!-- Logos (Placeholder for actual logos) -->
            <div class="flex items-center gap-3 receipt-header-logos">
                <!-- <img src="logo/logo-main.webp" alt="UP Govt Logo" class="h-12 w-auto grayscale contrast-125">
                <img src="logo/Swachh_Bharat_Mission_Logo.png" alt="Swachh Bharat" class="h-10 w-auto grayscale contrast-125"> -->

                <img src="logo/logo-main.webp" alt="UP Govt Logo" class="h-12 w-auto  contrast-125">
                <img src="logo/Swachh_Bharat_Mission_Logo.png" alt="Swachh Bharat" class="h-10 w-auto  contrast-125">
            </div>

            <!-- Receipt Meta Info -->
            <div class="text-right text-sm pr-14 receipt-header-meta">
                <h2 class="text-lg font-bold text-slate-900 uppercase mb-2">Khalilabad Nagar Palika Parishad</h2>
                <div class="grid grid-cols-[auto_auto] gap-x-2 text-slate-600 justify-end receipt-meta-grid">
                    <span class="font-semibold text-slate-800">Property Tax Receipt No -</span> <span>RECEIPT_1775389515</span>
                    <span class="font-semibold text-slate-800">Holding No -</span> <span>#BMC452153076391</span>
                    <span class="font-semibold text-slate-800">Receipt Date -</span> <span>31/03/2026</span>
                </div>
            </div>
        </div>

        <!-- Information Grid (Owner & Property Details) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 bg-slate-50/50 property-info p-5 rounded-lg border border-slate-100">

            <!-- Left: Owner Details -->
            <div class="owner-details">
                <div class="grid grid-cols-[140px_1fr] owner-details-grid gap-y-2.5 text-sm">
                    <div class="font-bold text-slate-700">Owner Name</div>
                    <div class="text-slate-800 uppercase">AJAY YADAV</div>

                    <div class="font-bold text-slate-700">Mobile Number</div>
                    <div class="text-slate-800">9031170531</div>

                    <div class="font-bold text-slate-700">Area of plot</div>
                    <div class="text-slate-800">1525</div>

                    <div class="font-bold text-slate-700">Address</div>
                    <div class="text-slate-800 uppercase">BOUNSI ROAD BHAGALPUR</div>

                    <div class="font-bold text-slate-700">Ward</div>
                    <div class="text-slate-800">45</div>

                    <div class="font-bold text-slate-700">Zone</div>
                    <div class="text-slate-800">Zone 1</div>

                    <div class="font-bold text-slate-700">City/Village</div>
                    <div class="text-slate-800 uppercase">Bhagalpur</div>

                    <div class="font-bold text-slate-700">Pincode</div>
                    <div class="text-slate-800">812005</div>
                </div>
            </div>

            <!-- Right: Property Floor Details -->
            <div class="floor-table-wrap">
                <table class="w-full text-sm text-center border border-slate-200 floor-table">
                    <thead class="bg-slate-100 font-bold text-slate-700">
                        <tr>
                            <th class="py-2 border-b border-slate-200">Floor Name</th>
                            <th class="py-2 border-b border-slate-200">Build Up Area (Sqft.)</th>
                            <th class="py-2 border-b border-slate-200">Area Of Plot</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-800 bg-white">
                        <tr>
                            <td class="py-2">Basement -1</td>
                            <td class="py-2">1300</td>
                            <td class="py-2">Residential</td>
                        </tr>
                        <tr class="bg-slate-50">
                            <td class="py-2">Ground Floor - 0</td>
                            <td class="py-2">1000</td>
                            <td class="py-2">Commercial</td>
                        </tr>
                        <tr>
                            <td class="py-2">Ground Floor - 0</td>
                            <td class="py-2">300</td>
                            <td class="py-2">Residential</td>
                        </tr>
                        <tr class="bg-slate-50">
                            <td class="py-2">First Floor - 1</td>
                            <td class="py-2">1300</td>
                            <td class="py-2">Residential</td>
                        </tr>
                        <tr>
                            <td class="py-2">Second Floor - 2</td>
                            <td class="py-2">1300</td>
                            <td class="py-2">Residential</td>
                        </tr>
                        <tr class="bg-slate-50">
                            <td class="py-2">Third Floor - 3</td>
                            <td class="py-2">1300</td>
                            <td class="py-2">Residential</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payment Declaration Text -->
        <div class="bg-slate-100/70 p-4 rounded-lg border border-slate-200 mb-6 payment-declaration text-sm text-slate-700 leading-relaxed text-center">
            A sum of <strong class="text-slate-900 text-base">25103.00 (Twenty Five Thousand One Hundred Three)</strong> has been received with thanks from Mr/Mrs <strong class="text-slate-900">AJAY YADAV</strong> towards the payment of tax as per the details given below.
        </div>

        <!-- Tax Breakup Table -->
        <div class="mb-6 overflow-x-auto responsive-table-wrap">
            <table class="w-full text-sm text-center border-collapse border border-slate-200 tax-table">
                <thead class="bg-slate-50 text-slate-800 font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-3 border border-slate-200">Sr. No</th>
                        <th class="p-3 border border-slate-200">Year</th>
                        <th class="p-3 border border-slate-200">VL. Tax</th>
                        <th class="p-3 border border-slate-200">Tax</th>
                        <th class="p-3 border border-slate-200">Rebate</th>
                        <th class="p-3 border border-slate-200">Penalty</th>
                        <th class="p-3 border border-slate-200">Total Tax</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700 bg-white">
                    <tr>
                        <td class="p-3 border border-slate-200 font-bold text-slate-900">1</td>
                        <td class="p-3 border border-slate-200">2025-2026</td>
                        <td class="p-3 border border-slate-200">0.00</td>
                        <td class="p-3 border border-slate-200">8366.00</td>
                        <td class="p-3 border border-slate-200">0.00</td>
                        <td class="p-3 border border-slate-200">0.00</td>
                        <td class="p-3 border border-slate-200">8366.00</td>
                    </tr>
                    <tr>
                        <td class="p-3 border border-slate-200 font-bold text-slate-900">2</td>
                        <td class="p-3 border border-slate-200">2024-2025</td>
                        <td class="p-3 border border-slate-200">0.00</td>
                        <td class="p-3 border border-slate-200">8366.00</td>
                        <td class="p-3 border border-slate-200">0.00</td>
                        <td class="p-3 border border-slate-200">0.00</td>
                        <td class="p-3 border border-slate-200">8366.00</td>
                    </tr>
                    <tr>
                        <td class="p-3 border border-slate-200 font-bold text-slate-900">3</td>
                        <td class="p-3 border border-slate-200">2023-2024</td>
                        <td class="p-3 border border-slate-200">0.00</td>
                        <td class="p-3 border border-slate-200">8366.00</td>
                        <td class="p-3 border border-slate-200">0.00</td>
                        <td class="p-3 border border-slate-200">0.00</td>
                        <td class="p-3 border border-slate-200">8366.00</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Additional Charges Table -->
        <div class="mb-6 overflow-x-auto responsive-table-wrap">
            <table class="w-full text-sm border-collapse border border-slate-200 charges-table">
                <tbody class="text-slate-700 bg-white">
                    <tr>
                        <td class="p-3 border border-slate-200 w-2/3 text-center">Solid Waste User Charge</td>
                        <td class="p-3 border border-slate-200 w-1/3 text-center">0</td>
                    </tr>
                    <tr>
                        <td class="p-3 border border-slate-200 text-center">Penalty Charge</td>
                        <td class="p-3 border border-slate-200 text-center">0.00</td>
                    </tr>
                    <tr>
                        <td class="p-3 border border-slate-200 text-center">Other Amount</td>
                        <td class="p-3 border border-slate-200 text-center">0.00</td>
                    </tr>
                    <tr>
                        <td class="p-3 border border-slate-200 text-center">Form Fee</td>
                        <td class="p-3 border border-slate-200 text-center">5</td>
                    </tr>
                    <tr>
                        <td class="p-3 border border-slate-200 text-center">Boring Charge</td>
                        <td class="p-3 border border-slate-200 text-center">0</td>
                    </tr>
                    <tr class="bg-slate-50 font-bold text-slate-900">
                        <td class="p-3 border border-slate-200 text-center">Total -Rs</td>
                        <td class="p-3 border border-slate-200 text-center">25103.00</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Payment Info & Grand Total -->
        <div class="grid grid-cols-1 md:grid-cols-[1fr_400px] gap-5 mb-6 payment-info">
            <!-- Left: Transaction Details -->
            <table class="w-full text-sm border-collapse border border-slate-200">
                <tbody class="text-slate-700 bg-white">
                    <tr>
                        <td class="p-3 border border-slate-200 text-center bg-slate-50">Payee Name</td>
                        <td class="p-3 border border-slate-200 text-center uppercase font-medium">AJAY YADAV</td>
                    </tr>
                    <tr>
                        <td class="p-3 border border-slate-200 text-center bg-slate-50 font-bold text-slate-900">Payment made at</td>
                        <td class="p-3 border border-slate-200 text-center font-bold text-slate-900 uppercase">JSK</td>
                    </tr>
                    <tr>
                        <td class="p-3 border border-slate-200 text-center bg-slate-50 font-bold text-slate-900">Mode Of Payment</td>
                        <td class="p-3 border border-slate-200 text-center font-bold text-slate-900 uppercase">NB</td>
                    </tr>
                    <tr>
                        <td class="p-3 border border-slate-200 text-center bg-slate-50">Transaction Id</td>
                        <td class="p-3 border border-slate-200 text-center">609092344503</td>
                    </tr>
                    <tr>
                        <td class="p-3 border border-slate-200 text-center bg-slate-50">Date of payment</td>
                        <td class="p-3 border border-slate-200 text-center">31/03/2026</td>
                    </tr>
                </tbody>
            </table>

            <!-- Right: Grand Total Box -->
            <div class="border border-slate-200 rounded-sm flex flex-col">
                <div class="flex-1 p-4 border-b border-slate-200 flex items-center justify-center font-bold text-slate-900 bg-slate-50 text-center">
                    Total -Rs- 25103.00/-
                </div>
                <div class="flex-1 p-4 flex flex-col items-center justify-center text-center font-bold text-slate-800 text-sm">
                    <span class="text-xs text-slate-500 font-normal mb-1">In Words</span>
                    Twenty Five Thousand One Hundred Three
                </div>
            </div>
        </div>

        <!-- Footer Note -->
        <div class="text-center text-sm text-slate-500 pt-4 border-t border-slate-200">
            Generated on 31/03/2026
        </div>

    </div>
</main>

<?php include "include/footer.php"; ?>