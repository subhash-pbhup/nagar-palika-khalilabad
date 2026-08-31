<?php

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=ARV_Tax_Template.csv');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');


// ============================================================
// 55 COLUMN ARV BULK UPLOAD TEMPLATE
// ============================================================

$headers = [

    'SR. NO.',
    'ULB ID',
    'Old Property ID',
    'Excel Property ID',
    'e-nagarsewa Generated Property ID',

    'Zone ID',
    'Ward ID',
    'Mohalla ID',
    'Property Status',

    'House No',
    'Owner Name',
    'Father Name',
    'Address',
    'Mobile',

    'No. of Floors',
    'Rebate Type',
    'Property Type',
    'Road Location',
    'Nature of House',

    'Total Area',
    'Property Use',

    'Bill No',
    'Bill Date',
    'Financial Year',

    'Total ARV',

    'House Tax (Current)',
    'House Tax (Arrear)',
    'House Tax (Interest)',
    'House Tax (Total)',

    'Water Tax (Current)',
    'Water Tax (Arrear)',
    'Water Tax (Interest)',
    'Water Tax (Total)',

    'Carpet Area',
    'Covered Area',

    'Sewerage Tax (Current)',
    'Sewerage Tax (Arrear)',
    'Sewerage Tax (Interest)',
    'Sewerage Tax (Total)',

    'Other Tax (Current)',
    'Other Tax (Arrear)',
    'Other Tax (Interest)',
    'Other Tax (Total)',

    'Water Charge (Current)',
    'Water Charge (Arrear)',
    'Water Charge (Interest)',
    'Water Charge (Total)',

    'Chuk Number',

    'Latitude',
    'Longitude',

    'Prev Adv House',
    'Prev Adv Water',
    'Prev Adv Sewerage',
    'Prev Adv Other',
    'Prev Adv Water Charge'
];


// ============================================================
// CHECK COLUMN COUNT
// ============================================================

if (count($headers) !== 55) {

    die('Template column configuration error. Expected 55 columns.');
}


// ============================================================
// WRITE HEADER
// ============================================================

fputcsv($output, $headers);


// ============================================================
// DUMMY DATA 1
// ============================================================

$sample1 = [

    '1',
    'ULB001',
    'OLD-000101',
    'EXCEL-000101',
    'UP054501010000101',

    '1',
    '12',
    '5',
    'Old',

    '14/B',
    'Rajesh Kumar',
    'Late Shri Ram Charan',
    'Civil Lines, Near Hanuman Mandir',
    '9876543210',

    '2',
    'General',
    'Residential',
    'Main Road',
    'Pucca',

    '1500',
    'Residential',

    'BILL-2025-001',
    '2025-04-15',
    '2025-2026',

    '83700',

    '8370',
    '14277',
    '1428',
    '24075',

    '8370',
    '14277',
    '1428',
    '24075',

    '1200',
    '1300',

    '0',
    '0',
    '0',
    '0',

    '0',
    '0',
    '0',
    '0',

    '0',
    '0',
    '0',
    '0',

    'CHUK-001',

    '26.7606',
    '83.3732',

    '500',
    '200',
    '0',
    '0',
    '100'
];


// ============================================================
// DUMMY DATA 2
// ============================================================

$sample2 = [

    '2',
    'ULB001',
    'OLD-000102',
    'EXCEL-000102',
    'UP054501300000102',

    '2',
    '30',
    '8',
    'New',

    'C-45',
    'Amit Srivastava',
    'Gyan Prakash Srivastava',
    'Malviya Road, Opposite Bank of Baroda',
    '8877665544',

    '3',
    'Senior Citizen',
    'Commercial',
    'Main Road',
    'RCC',

    '2500',
    'Commercial',

    'BILL-2025-002',
    '2025-04-20',
    '2025-2026',

    '120000',

    '12000',
    '5000',
    '500',
    '17500',

    '5000',
    '2000',
    '200',
    '7200',

    '2000',
    '2200',

    '3000',
    '1000',
    '100',
    '4100',

    '500',
    '200',
    '20',
    '720',

    '1000',
    '500',
    '50',
    '1550',

    'CHUK-002',

    '26.7640',
    '83.3745',

    '1000',
    '500',
    '250',
    '100',
    '200'
];


// ============================================================
// DUMMY DATA 3
// ============================================================

$sample3 = [

    '3',
    'ULB001',
    'OLD-000103',
    'EXCEL-000103',
    'UP054501150000103',

    '1',
    '15',
    '12',
    'Old',

    'A-125',
    'Sanjay Verma',
    'Ram Prasad Verma',
    'Station Road, Near Government School',
    '9123456789',

    '1',
    'General',
    'Residential',
    'Internal Road',
    'Pucca',

    '1100',
    'Residential',

    'BILL-2025-003',
    '2025-04-25',
    '2025-2026',

    '65000',

    '6500',
    '1200',
    '150',
    '7850',

    '1300',
    '200',
    '50',
    '1550',

    '900',
    '1000',

    '0',
    '0',
    '0',
    '0',

    '250',
    '100',
    '10',
    '360',

    '500',
    '100',
    '10',
    '610',

    'CHUK-003',

    '26.7588',
    '83.3701',

    '250',
    '100',
    '50',
    '0',
    '50'
];


// ============================================================
// CHECK DUMMY DATA COLUMN COUNT
// ============================================================

if (
    count($sample1) !== 55 ||
    count($sample2) !== 55 ||
    count($sample3) !== 55
) {

    die('Dummy data column configuration error. Expected 55 columns.');
}


// ============================================================
// WRITE DUMMY DATA
// ============================================================

fputcsv($output, $sample1);
fputcsv($output, $sample2);
fputcsv($output, $sample3);


// ============================================================
// CLOSE
// ============================================================

fclose($output);

exit;
