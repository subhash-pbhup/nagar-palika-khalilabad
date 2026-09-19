<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once "db.php";

// Debug mode (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$base_image_path = 'admin-uploads/';
$default_image_file = 'man.png';
$default_image_src = $default_image_file;

$user_id = $_SESSION['user_id'];

// Fetch user data securely
$stmt = $conn->prepare("SELECT username, email, role, profile_pic FROM users WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$user_name = "User Not Found";
$user_email = "Email Not Found";
$user_role = "user";
$profile_image_src = $default_image_src;

if ($result->num_rows === 1) {
    $user_data = $result->fetch_assoc();
    $user_name = $user_data['username'];
    $user_email = $user_data['email'];
    $user_role = $user_data['role'];
    if (!empty($user_data['profile_pic'])) {
        $profile_image_src = $base_image_path . htmlspecialchars($user_data['profile_pic']);
    }
}
$stmt->close();

$theme_color_primary = 'emerald-600';

// Dummy Property List (sample)
$property_list = [
    [
        'sr' => 1,
        'code' => '01020001-T',
        'owner' => 'SEEMA BHARTI',
        'mobile' => '18956500',
        'zone' => 'Zone 5',
        'ward' => 4,
        'mohalla' => 'MANBELA PEER SHAHEED',
        'house_no' => '001',
        'old_arv' => '300 +',
        'curr_arv' => '350',
        'ht' => '40',
        'wt' => '25',
        'st' => '15',
        'arrear' => '15,000',
        'arrear_int' => '1,500',
        'current' => '1,200',
        'current_int' => '120',
        'total_demand' => '17,820'
    ],
    [
        'sr' => 2,
        'code' => '01020001AR',
        'owner' => 'SMT. HEMA SHAMSHLOTHERS',
        'mobile' => '0',
        'zone' => 'Zone 5',
        'ward' => 4,
        'mohalla' => 'MANBELA PEER SHAHEED',
        'house_no' => '001A',
        'old_arv' => '720 +',
        'curr_arv' => '800',
        'ht' => '50',
        'wt' => '30',
        'st' => '20',
        'arrear' => '25,000',
        'arrear_int' => '2,500',
        'current' => '2,400',
        'current_int' => '240',
        'total_demand' => '30,140'
    ],
    [
        'sr' => 3,
        'code' => '01020001BA',
        'owner' => 'SRI JABBAR',
        'mobile' => '0',
        'zone' => 'Zone 5',
        'ward' => 4,
        'mohalla' => 'MANBELA PEER SHAHEED',
        'house_no' => '001B',
        'old_arv' => '180X +',
        'curr_arv' => '200',
        'ht' => '20',
        'wt' => '10',
        'st' => '5',
        'arrear' => '5,000',
        'arrear_int' => '500',
        'current' => '600',
        'current_int' => '60',
        'total_demand' => '6,160'
    ],
    [
        'sr' => 4,
        'code' => '01020002BA',
        'owner' => 'NAUSAD ALI',
        'mobile' => '0',
        'zone' => 'Zone 5',
        'ward' => 4,
        'mohalla' => 'MANBELA PEER SHAHEED',
        'house_no' => '002',
        'old_arv' => '288 +',
        'curr_arv' => '300',
        'ht' => '25',
        'wt' => '12',
        'st' => '8',
        'arrear' => '7,000',
        'arrear_int' => '700',
        'current' => '900',
        'current_int' => '90',
        'total_demand' => '8,690'
    ],
    [
        'sr' => 5,
        'code' => '01020002AZ',
        'owner' => 'MOHAMMAD ABID ALI',
        'mobile' => '9876543210',
        'zone' => 'Zone 5',
        'ward' => 4,
        'mohalla' => 'MANBELA PEER SHAHEED',
        'house_no' => '002A',
        'old_arv' => '126X +',
        'curr_arv' => '150',
        'ht' => '15',
        'wt' => '8',
        'st' => '4',
        'arrear' => '3,500',
        'arrear_int' => '350',
        'current' => '450',
        'current_int' => '45',
        'total_demand' => '4,345'
    ],
];

$total_records = 196996;
$total_pages = 19700;
$current_page = 1;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Legacy Property List - khalilabad Property Tax</title>
    <link href="img/favicon.ico" rel="icon">
    <link href='css/mystyle.css' rel='stylesheet'>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

    <script>
        /* Tailwind custom config for primary color */
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            500: '#10B981',
                            600: '#059669',
                            700: '#047857',
                        },
                    }
                }
            }
        }
    </script>

    <!-- Sticky column styles (keeps header + sticky columns layering neat) -->
    <style>
        /* Container for horizontal scrolling */
        .table-scroll-container {
            position: relative;
            overflow-x: auto;
            width: 100%;
        }

        /* Make table cells not wrap so horizontal scroll triggers */
        th,
        td {
            white-space: nowrap;
        }

        /* Make header sticky to top */
        thead th {
            position: sticky;
            top: 0;
            background-color: #f8fafc;
            /* light bg similar to Tailwind gray-50 */
            z-index: 20;
        }

        /* Column widths (adjust as needed) */
        .col-sr {
            width: 70px;
            min-width: 70px;
            max-width: 70px;
        }

        .col-code {
            width: 160px;
            min-width: 160px;
            max-width: 160px;
        }

        .col-owner {
            width: 260px;
            min-width: 260px;
            max-width: 260px;
        }

        /* action column width */
        .col-action {
            width: 140px;
            min-width: 140px;
            max-width: 140px;
        }

        /* Sticky left columns */
        .sticky-left-sr {
            position: sticky;
            left: 0;
            z-index: 30;
            background: #fff;
        }

        .sticky-left-code {
            position: sticky;
            left: 70px;
            z-index: 30;
            background: #fff;
        }

        .sticky-left-owner {
            position: sticky;
            left: 230px;
            z-index: 30;
            background: #fff;
        }

        /* Sticky right action column */
        .sticky-right-action {
            position: sticky;
            right: 0;
            z-index: 30;
            background: #fff;
        }

        /* Ensure header sticky cells are above body sticky cells to avoid overlap */
        thead .sticky-left-sr,
        thead .sticky-left-code,
        thead .sticky-left-owner,
        thead .sticky-right-action {
            z-index: 40;
        }

        /* small visual fix when sticky overlaps borders */
        tbody tr td.sticky-left-sr,
        tbody tr td.sticky-left-code,
        tbody tr td.sticky-left-owner {
            box-shadow: 2px 0 0 rgba(0, 0, 0, 0.03);
        }

        tbody tr td.sticky-right-action {
            box-shadow: -2px 0 0 rgba(0, 0, 0, 0.03);
        }

        /* Make the table horizontally large (keeps many columns visible) */
        .min-w-table {
            min-width: 2200px;
        }
    </style>
</head>

<body class="flex min-h-screen text-gray-800 bg-gray-50">

    <div id="sidebar-backdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

    <?php include "sidemenu.php" ?>

    <div class="content-wrapper flex flex-col flex-1 w-full lg:w-[calc(100%-280px)]">

        <!-- Header -->
        <header class="flex items-center justify-between glass p-4 rounded-2xl">
            <h1 class="text-2xl font-bold text-gray-900">View Assessments</h1>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <input type="text" placeholder="Search..." class="pl-10 pr-4 py-2 rounded-full glass text-sm focus:outline-none">
                    <span class="absolute left-3 top-2.5 text-gray-400"><i class="fa fa-search" aria-hidden="true"></i></span>
                </div>

                <button class="relative w-10 h-10 rounded-full glass flex items-center justify-center hover-glass">
                    <i class="fa fa-bell" aria-hidden="true"></i>
                    <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>

                <a href="new-assesment.php" class="relative w-10 h-10 rounded-full glass flex items-center justify-center hover-glass">
                    <i class="fa fa-plus" aria-hidden="true"></i>
                </a>

                <div class="relative inline-block text-left ml-4">
                    <button id="profileBtn" class="flex items-center space-x-2 focus:outline-none">
                        <div class="text-right hidden md:block">
                            <p class="text-base font-semibold text-gray-800"><?php echo htmlspecialchars($user_name); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user_email); ?></p>
                        </div>
                        <div class="w-10 h-10 rounded-full overflow-hidden border border-gray-200">
                            <img src="<?php echo $profile_image_src; ?>" alt="<?php echo htmlspecialchars($user_name); ?>'s Profile" class="w-full h-full object-cover">
                        </div>
                    </button>

                    <div id="profileMenu" class="hidden absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-xl z-50 py-1">
                        <a href="update-profile.php" class="flex items-center px-4 py-2 text-base text-gray-700 hover:bg-gray-100 rounded-lg">
                            <i class='bx bx-user-circle text-xl mr-2 text-blue-500'></i> Update Profile
                        </a>
                        <a href="reset-password.php" class="flex items-center px-4 py-2 text-base text-gray-700 hover:bg-gray-100 rounded-lg">
                            <i class='bx bx-lock-alt text-xl mr-2 text-yellow-600'></i> Reset Password
                        </a>
                        <hr class="my-1 border-gray-200">
                        <a href="logout.php" class="flex items-center px-4 py-2 text-base text-gray-700 hover:bg-red-50 rounded-lg">
                            <i class='bx bx-log-out text-xl mr-2 text-red-500'></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 p-6 space-y-8 overflow-y-auto">

            <!-- Filters + Add -->
            <div class="glass rounded-2xl p-6 mx-2 lg:mx-0">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-4">
                    <h3 class="text-xl font-bold mb-3 md:mb-0">Legacy Property Assessment Records</h3>
                    <div class="flex items-center space-x-3">
                        <a href="new-assesment.php" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-4 rounded-full shadow-lg transition duration-300 ease-in-out transform hover:-translate-y-1">
                            <i class="fa fa-plus" aria-hidden="true"></i> Add New
                        </a>
                    </div>
                </div>

                <div id="filter-panel" class="border-t border-gray-200 pt-6 mt-4">
                    <h4 class="text-lg font-semibold mb-4 text-gray-700">Search Filters</h4>
                    <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                        <div class="col-span-1">
                            <label for="ward" class="block text-sm font-medium text-gray-700 mb-1">Ward</label>
                            <select id="ward" class="mt-0.5 block w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 text-base py-2">
                                <option>Select Ward</option>
                            </select>
                        </div>
                        <div class="col-span-1">
                            <label for="mohalla" class="block text-sm font-medium text-gray-700 mb-1">Mohalla</label>
                            <select id="mohalla" class="mt-0.5 block w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 text-base py-2">
                                <option>Select Mohalla</option>
                            </select>
                        </div>
                        <div class="col-span-1">
                            <label for="house_no" class="block text-sm font-medium text-gray-700 mb-1">House No</label>
                            <input type="text" id="house_no" placeholder="House No" class="mt-0.5 block w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 text-base py-2">
                        </div>
                        <div class="col-span-1">
                            <label for="address_code" class="block text-sm font-medium text-gray-700 mb-1">Address Code</label>
                            <input type="text" id="address_code" placeholder="Address Code" class="mt-0.5 block w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 text-base py-2">
                        </div>
                        <div class="col-span-1">
                            <label for="owner_name" class="block text-sm font-medium text-gray-700 mb-1">Owner Name</label>
                            <input type="text" id="owner_name" placeholder="Owner Name" class="mt-0.5 block w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 text-base py-2">
                        </div>
                        <div class="col-span-1">
                            <label for="mobile_no" class="block text-sm font-medium text-gray-700 mb-1">Mobile No</label>
                            <input type="text" id="mobile_no" placeholder="Mobile No" class="mt-0.5 block w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 text-base py-2">
                        </div>
                    </div>

                    <div class="mt-6 flex space-x-3 justify-end md:justify-start">
                        <button class="px-6 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg shadow-md hover:bg-primary-700 transition">Search</button>
                        <button class="px-6 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition">Reset</button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="glass p-0 overflow-hidden rounded-2xl mx-2 lg:mx-0">
                <div class="table-scroll-container">
                    <table class="w-full min-w-table divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase col-sr sticky-left-sr">Sr. No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase col-code sticky-left-code">Address Code</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase col-owner sticky-left-owner">Owner</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Mobile No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Zone</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Ward</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Mohalla</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">House No</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Old ARV</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Current ARV</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">HT</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">WT</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">ST</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Arrear</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Arrear Interest</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Current</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Current Interest</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-900 uppercase">Total Demand</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase col-action sticky-right-action">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($property_list as $row): ?>
                                <tr class="hover:bg-emerald-50/50">
                                    <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-gray-900 col-sr sticky-left-sr"><?php echo $row['sr']; ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 col-code sticky-left-code"><?php echo $row['code']; ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 col-owner sticky-left-owner"><?php echo $row['owner']; ?></td>

                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?php echo $row['mobile']; ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?php echo $row['zone']; ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?php echo $row['ward']; ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?php echo $row['mohalla']; ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?php echo $row['house_no']; ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 text-right"><?php echo $row['old_arv']; ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 text-right"><?php echo $row['curr_arv']; ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 text-right"><?php echo $row['ht']; ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 text-right"><?php echo $row['wt']; ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 text-right"><?php echo $row['st']; ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 text-right"><?php echo $row['arrear']; ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 text-right"><?php echo $row['arrear_int']; ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 text-right"><?php echo $row['current']; ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 text-right"><?php echo $row['current_int']; ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900 text-right"><?php echo $row['total_demand']; ?></td>

                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-center col-action sticky-right-action">
                                        <div class="flex space-x-1 justify-center">
                                            <a href="view-legecy.php" title="View" class="inline-flex items-center justify-center w-6 h-6 rounded-full hover:bg-blue-200 transition"><i class='bx bx-show-alt text-sm text-blue-600'></i></a>
                                            <button title="Edit" class="inline-flex items-center justify-center w-6 h-6 rounded-full  hover:bg-green-200 transition"><i class='bx bx-edit text-sm text-green-600'></i></button>
                                            <button title="Add Demand" class="inline-flex items-center justify-center w-6 h-6 rounded-full  hover:bg-indigo-200 transition"><i class='bx bx-money text-sm text-indigo-600'></i></button>
                                            <button title="Print" class="inline-flex items-center justify-center w-6 h-6 rounded-full  hover:bg-gray-200 transition"><i class='bx bx-printer text-sm text-gray-600'></i></button>
                                            <button title="Delete" class="inline-flex items-center justify-center w-6 h-6 rounded-full hover:bg-red-200 transition"><i class='bx bx-trash text-sm text-red-600'></i></button>
                                            <button title="Update Legacy" class="inline-flex items-center justify-center w-6 h-6 rounded-full hover:bg-yellow-200 transition"><i class='bx bx-archive-out text-sm text-yellow-600'></i></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination / Footer of table -->
                <div class="px-4 py-3 flex flex-col md:flex-row items-center justify-between border-t border-gray-200 bg-white rounded-b-xl">
                    <div class="order-2 md:order-1 mt-3 md:mt-0">
                        <p class="text-sm text-gray-700">
                            Total Record: <span class="font-medium"><?php echo number_format($total_records); ?></span>, Total Pages: <span class="font-medium"><?php echo number_format($total_pages); ?></span>
                        </p>
                    </div>
                    <div class="flex items-center space-x-2 order-1 md:order-2">
                        <div class="flex space-x-1">
                            <button class="px-3 py-1 text-sm rounded-lg text-gray-700 bg-gray-100 hover:bg-gray-200 transition">First</button>
                            <button class="px-3 py-1 text-sm rounded-lg text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Previous</button>
                        </div>
                        <div class="flex space-x-1">
                            <span class="px-3 py-1 text-sm rounded-lg font-semibold bg-primary-600 text-white shadow-md"><?php echo $current_page; ?></span>
                            <?php for ($i = 2; $i <= 3; $i++): ?>
                                <button class="px-3 py-1 text-sm rounded-lg text-gray-700 bg-gray-100 hover:bg-gray-200 transition"><?php echo $i; ?></button>
                            <?php endfor; ?>
                        </div>
                        <div class="flex space-x-1">
                            <button class="px-3 py-1 text-sm rounded-lg text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Next</button>
                            <button class="px-3 py-1 text-sm rounded-lg text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Last</button>
                        </div>
                        <select class="ml-4 text-sm border-gray-300 rounded-lg focus:border-primary-500 focus:ring-primary-500 py-1.5">
                            <option>10</option>
                            <option>50</option>
                            <option>1000</option>
                        </select>
                    </div>
                </div>
            </div>

            <footer class="text-center text-gray-500 text-sm mt-6">
                © 2025 Khalilabad Nagar Parishad - All Rights Reserved.
            </footer>
        </main>
    </div>

    <!-- Scripts: sidebar toggle, profile menu, dropdowns, resize fixes -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const menuToggle = document.getElementById("menu-toggle");
            const sidebar = document.getElementById("sidebar");
            const backdrop = document.getElementById("sidebar-backdrop");
            const profileBtn = document.getElementById('profileBtn');
            const profileMenu = document.getElementById('profileMenu');
            const dropdownButtons = document.querySelectorAll('.sidebar-link[data-dropdown]'); // if you use them in sidemenu

            function toggleSidebar() {
                if (!sidebar) return;
                sidebar.classList.toggle("open");
                backdrop.classList.toggle("hidden");
            }

            if (menuToggle) menuToggle.addEventListener("click", toggleSidebar);
            if (backdrop) backdrop.addEventListener("click", toggleSidebar);

            window.addEventListener('resize', () => {
                if (window.innerWidth >= 1024 && sidebar) {
                    sidebar.classList.remove("open");
                    if (backdrop) backdrop.classList.add("hidden");
                }
            });

            // profile menu
            if (profileBtn && profileMenu) {
                profileBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    profileMenu.classList.toggle('hidden');
                });

                document.addEventListener('click', (e) => {
                    if (!profileMenu.contains(e.target) && !profileBtn.contains(e.target)) {
                        profileMenu.classList.add('hidden');
                    }
                });
            }

            // side menu dropdowns (if present)
            dropdownButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const submenuId = button.getAttribute('data-dropdown');
                    const submenu = document.getElementById(submenuId);
                    const icon = button.querySelector('.bx-chevron-right') || button.querySelector('.fa-chevron-right');
                    if (submenu) submenu.classList.toggle('open');
                    if (icon) icon.classList.toggle('rotate-90');
                    button.classList.toggle('active');
                });
            });
        });
    </script>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</body>

</html>