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

// Prepare SQL statement to fetch user data
// Using prepared statements for security (prevents SQL Injection)
$stmt = $conn->prepare("SELECT username, email, role, profile_pic FROM users WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Initialize variables with default/fallback values
$user_name = "User Not Found";
$user_email = "Email Not Found";
$user_role = "user"; // Default role
$profile_image_src = $default_image_src;

if ($result->num_rows === 1) {
    // Fetch the data
    $user_data = $result->fetch_assoc();

    // Assign the actual data fetched from the database
    $user_name = $user_data['username'];
    $user_email = $user_data['email'];
    $user_role = $user_data['role'];

    if (!empty($user_data['profile_pic'])) {
        // If profile_image column is NOT empty, construct the dynamic path
        // $base_image_path (admin-uploads/) + $user_data['profile_image'] (filename)
        $profile_image_src = $base_image_path . htmlspecialchars($user_data['profile_pic']);
    }
}

$stmt->close();

$records_per_page = 20;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// ----------------------
// ✅ FILTER LOGIC
// ----------------------
$conditions = ["a.is_deleted = 0"]; // Base condition (alias a for assessments)
$params = [];
$types = "";

// OR filter group
$orConditions = [];
$joinOwners = false; // check if owner/mobile filter applied

// Holding Number (partial match)
if (!empty($_GET['new_holding'])) {
    $orConditions[] = "a.new_holding LIKE ?";
    $params[] = "%" . $_GET['new_holding'] . "%";
    $types .= "s";
}

// Ward Number (EXACT MATCH)
if (!empty($_GET['ward'])) {
    $orConditions[] = "a.ward = ?";
    $params[] = $_GET['ward'];
    $types .= "s";
}

// Owner Name (partial match from assessment_owners table)
if (!empty($_GET['owner_name'])) {
    // Filter by active owners only
    $orConditions[] = "o.owner_name LIKE ? AND o.is_deleted = 0";
    $params[] = "%" . $_GET['owner_name'] . "%";
    $types .= "s";
    $joinOwners = true; // need join
}

// Mobile Number (partial match from assessment_owners table)
if (!empty($_GET['mobile_number'])) {
    // Filter by active owners only
    $orConditions[] = "o.mobile LIKE ? AND o.is_deleted = 0";
    $params[] = "%" . $_GET['mobile_number'] . "%";
    $types .= "s";
    $joinOwners = true; // need join
}

// Combine OR filters
if (!empty($orConditions)) {
    $conditions[] = "(" . implode(" OR ", $orConditions) . ")";
}

// Final WHERE clause
$where_sql = "WHERE " . implode(" AND ", $conditions);

// ----------------------
// ✅ TOTAL RECORDS COUNT
// ----------------------
$sql_count = "SELECT COUNT(DISTINCT a.id) AS total_records 
              FROM assessments a 
              " . ($joinOwners ? "LEFT JOIN assessment_owners o ON a.id = o.assessment_id " : "") . "
              $where_sql";
$stmt_count = $conn->prepare($sql_count);

if ($types) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$row_count = $result_count->fetch_assoc();
$total_records = $row_count['total_records'] ?? 0;
$total_pages = $total_records > 0 ? ceil($total_records / $records_per_page) : 1;

// ----------------------
// ✅ FETCH DATA WITH FILTERS + PAGINATION
// ----------------------
$sql = "SELECT a.id, 
               a.municipality_name, 
               a.year_of_assessment, 
               a.ward, 
               a.new_holding, 
               a.property_type, 
               a.created_at,
               a.latitude,
               a.longitude,
               -- ⭐ UPDATED: Only show owner names if is_deleted = 0 ⭐
               GROUP_CONCAT(DISTINCT CASE WHEN o.is_deleted = 0 THEN o.owner_name END SEPARATOR ', ') AS owner_name,
               GROUP_CONCAT(DISTINCT CASE WHEN o.is_deleted = 0 THEN o.mobile END SEPARATOR ', ') AS mobile_numbers
               -- ⭐ END UPDATED ⭐
        FROM assessments a
        LEFT JOIN assessment_owners o ON a.id = o.assessment_id
        $where_sql
        GROUP BY a.id
        ORDER BY a.id DESC 
        LIMIT $offset, $records_per_page";

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params); // only filter params
}
$stmt->execute();
$result = $stmt->get_result();

// Serial numbers
$start_sr_no = $offset;

// ----------------------
// ✅ PAGINATION LINKS (Preserve filters)
// ----------------------
$queryString = $_GET;
unset($queryString['page']); // page को छोड़कर बाकी सब preserve करो
$queryStr = http_build_query($queryString);

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Assessments - Deoria Property Tax</title>
    <link href="img/favicon.ico" rel="icon">
    <link href='css/mystyle.css' rel='stylesheet'>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />


    <style>
        /* This CSS ensures the 'Actions' column sticks to the right 
        when the table scrolls horizontally inside its parent (.overflow-x-auto).
        */
        .sticky-action-column {
            position: sticky;
            right: 0;
            background-color: white;
            /* Important: Set background color to cover the scrolling content */
            z-index: 10;
            /* Keep it above the scrolling content */
            box-shadow: -2px 0 5px rgba(0, 0, 0, 0.1);
            /* Optional: Shadow for separation */
        }

        /* Match the background of the table header for the sticky header cell */
        .table-header .sticky-action-column {
            background-color: #f9fafb;
            /* Assuming a light gray header background */
            /* You might need to adjust this color to match 'table-header' style */
            z-index: 11;
            /* Keep header above body cell */
        }

        /* Ensure table cells have padding/alignment */
        .table-cell {
            padding-top: 0.75rem;
            /* py-3 */
            padding-bottom: 0.75rem;
            /* py-3 */
            padding-left: 0.5rem;
            /* px-2, adjust as needed */
            padding-right: 0.5rem;
            /* px-2, adjust as needed */
        }
    </style>

</head>

<body class="flex min-h-screen text-gray-800">

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'surveyor'): ?>
        <?php include "sidemenu.php" ?>
    <?php endif; ?>

    <div id="sidebar-backdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>
    <main class="flex-1 p-6 space-y-8 overflow-y-auto">
        <header class="flex items-center justify-between glass p-4 rounded-2xl">
            <h1 class="text-2xl font-bold text-gray-900">View Assessments</h1>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <input type="text" placeholder="Search..." class="pl-10 pr-4 py-2 rounded-full glass text-sm focus:outline-none">
                    <span class="absolute left-3 top-2.5 text-gray-400"><i class="fa fa-search" aria-hidden="true"></i>&nbsp;</span>
                </div>
                <button class="relative w-10 h-10 rounded-full glass flex items-center justify-center hover-glass"><i class="fa fa-bell" aria-hidden="true"></i>&nbsp;
                    <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>
                <a href="new-assesment.php" class="relative w-10 h-10 rounded-full glass flex items-center justify-center hover-glass"><i class="fa fa-plus" aria-hidden="true"></i>&nbsp;</a>
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

        <div class="glass rounded-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold">Property Assessment Records</h3>
                <div class="flex items-center space-x-3">
                    <button id="dateRangeBtn" class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-200 hover:bg-gray-300 transition" title="Export by date range">
                        <i class="fa fa-calendar text-gray-700" aria-hidden="true"></i>
                    </button>

                    <button id="filterBtn" class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-200 hover:bg-gray-300 transition">
                        <i class="fa fa-filter text-gray-700"></i>
                    </button>
                    <a href="new-assesment.php" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-full shadow-lg transition duration-300 ease-in-out transform hover:-translate-y-1">
                        <i class="fa fa-plus" aria-hidden="true"></i> Add New
                    </a>
                </div>
            </div>


            <?php if ($result->num_rows > 0): ?>

                <div class="overflow-x-auto w-full">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-gray-500 text-xs uppercase border-b border-gray-200 table-header">
                                <th class="table-cell text-left">Sr. No.</th>
                                <th class="table-cell text-left">Owner Name</th>
                                <th class="table-cell text-left">Year</th>
                                <th class="table-cell text-left">Ward</th>
                                <th class="table-cell text-left">New Holding No.</th>
                                <th class="table-cell text-left">Property Type</th>
                                <th class="table-cell text-left">Created At</th>
                                <th class="table-cell text-center sticky-action-column">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sr_no = $start_sr_no + 1;
                            while ($row = $result->fetch_assoc()): ?>
                                <tr class="border-b border-gray-200 table-row-hover" id="row-<?php echo $row['id']; ?>">
                                    <td class="table-cell"><?php echo $sr_no++; ?></td>
                                    <td class="table-cell"><?php echo htmlspecialchars($row['owner_name']); ?></td>
                                    <td class="table-cell"><?php echo htmlspecialchars($row['year_of_assessment']); ?></td>
                                    <td class="table-cell"><?php echo htmlspecialchars($row['ward']); ?></td>
                                    <td class="table-cell"><?php echo htmlspecialchars($row['new_holding']); ?></td>
                                    <td class="table-cell"><?php echo htmlspecialchars($row['property_type']); ?></td>
                                    <td class="table-cell"><?php echo date('d M, Y', strtotime(htmlspecialchars($row['created_at']))); ?></td>

                                    <td class="table-cell text-center sticky-action-column">
                                        <div class="flex justify-center items-center space-x-1">
                                            <a href="view_assesment_details.php?id=<?php echo htmlspecialchars($row['id']); ?>"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-yellow-100 hover:bg-yellow-200 transition"
                                                title="View Details">
                                                <i class="fa fa-eye text-yellow-600 text-xs"></i>
                                            </a>

                                            <a href="assessment_history.php?assessment_id=<?= (int)$row['id'] ?>"
                                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-yallow-800 text-white hover:bg-yallow-900 transition">
                                                <i class="fa fa-history text-yellow-600 text-xs"></i>
                                            </a>

                                            <a href="edit_assessment.php?id=<?php echo $row['id']; ?>"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-100 hover:bg-green-200 transition"
                                                title="Edit Assesment Record.">
                                                <i class="fa fa-pencil text-green-600 text-xs"></i>
                                            </a>

                                            <a href="property_map.php?lat=<?php echo urlencode($row['latitude'] ?? ''); ?>&lng=<?php echo urlencode($row['longitude'] ?? ''); ?>"
                                                target="_blank"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 transition"
                                                title="View Property Location">
                                                <i class="fa fa-map-marker text-gray-600 text-xs"></i>
                                            </a>

                                            <a href="javascript:void(0);"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-100 hover:bg-red-200 transition btn-delete-assessment"
                                                data-id="<?php echo $row['id']; ?>"
                                                title="Delete Assessment Record.">
                                                <i class="fa fa-trash text-red-600 text-xs"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col md:flex-row justify-center items-center mt-6 space-y-3 md:space-y-0 md:space-x-4">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo $current_page - 1;
                                        echo $queryStr ? '&' . $queryStr : ''; ?>"
                            class="px-4 py-2 rounded-lg glass hover-glass">Previous</a>
                    <?php endif; ?>

                    <span class="px-4 py-2 text-gray-700">
                        Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
                    </span>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1;
                                        echo $queryStr ? '&' . $queryStr : ''; ?>"
                            class="px-4 py-2 rounded-lg glass hover-glass">Next</a>
                    <?php endif; ?>

                    <form method="get" class="flex items-center space-x-2">
                        <?php foreach ($queryString as $key => $value): ?>
                            <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                        <?php endforeach; ?>

                        <input type="number"
                            name="page"
                            min="1"
                            max="<?php echo $total_pages; ?>"
                            class="w-20 px-2 py-1 border rounded-lg focus:outline-none focus:ring focus:border-blue-300"
                            placeholder="Page"
                            required>
                        <button type="submit"
                            class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-lg shadow">
                            Go
                        </button>
                    </form>
                </div>

            <?php else: ?>
                <div class="flex flex-col items-center justify-center h-48 text-center">
                    <p class="text-3xl mb-2">😔</p>
                    <p class="text-lg text-gray-500 font-medium">No records found!</p>
                </div>
            <?php endif; ?>
        </div>



        <div id="filterModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 relative">
                <h2 class="text-lg font-bold mb-4">Filter Records</h2>
                <form method="get" class="space-y-4">

                    <div>
                        <label class="block text-sm font-medium mb-1">Holding Number</label>
                        <input type="text" name="new_holding"
                            value="<?php echo isset($_GET['new_holding']) ? htmlspecialchars($_GET['new_holding']) : ''; ?>"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:border-blue-300">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Ward Number</label>
                        <input type="text" name="ward"
                            value="<?php echo isset($_GET['ward']) ? htmlspecialchars($_GET['ward']) : ''; ?>"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:border-blue-300">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Name of Owner</label>
                        <input type="text" name="owner_name"
                            value="<?php echo isset($_GET['owner_name']) ? htmlspecialchars($_GET['owner_name']) : ''; ?>"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:border-blue-300">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Mobile Number</label>
                        <input type="text" name="mobile_number"
                            value="<?php echo isset($_GET['mobile_number']) ? htmlspecialchars($_GET['mobile_number']) : ''; ?>"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:border-blue-300">
                    </div>

                    <div class="flex justify-between items-center pt-4">
                        <?php if (!empty($_GET['new_holding']) || !empty($_GET['ward']) || !empty($_GET['owner_name']) || !empty($_GET['mobile_number'])): ?>
                            <a href="view-assesment.php"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-red-500 text-white text-sm font-semibold rounded-lg shadow hover:bg-red-600 transition">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor"
                                    class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>

                                Clear Filter
                            </a>
                        <?php else: ?>
                            <div></div> <?php endif; ?>

                        <div class="flex justify-end space-x-2">
                            <button type="button" id="closeFilter" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Apply</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>


        <div id="deleteRemarkModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
                <h2 class="text-xl font-semibold mb-4">Delete Assessment</h2>
                <form id="deleteRemarkForm">
                    <input type="hidden" name="id" id="delete_record_id" value="">
                    <div class="mb-4">
                        <label for="delete_remark" class="block text-sm font-medium text-gray-700">Enter Remark</label>
                        <textarea id="delete_remark" name="remark" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm"
                            placeholder="Reason for deleting this record..."></textarea>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" id="cancelDelete"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Confirm Delete</button>
                    </div>
                </form>
            </div>
        </div>


        <div id="dateRangeModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 relative">
                <h2 class="text-lg font-bold mb-3">Export: Select Date Range</h2>
                <p class="text-sm text-gray-500 mb-4">Choose start and end date. Then click <strong>Download Excel</strong>.</p>

                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Start Date</label>
                        <input id="startDate" type="date" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:border-blue-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">End Date</label>
                        <input id="endDate" type="date" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:border-blue-300">
                    </div>
                </div>

                <div class="flex justify-end items-center space-x-2 pt-6">
                    <button id="closeDateModal" type="button" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">Cancel</button>

                    <a id="downloadExcelBtn" href="#" disabled class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 opacity-50 cursor-not-allowed">
                        Download Excel
                    </a>
                </div>
            </div>
        </div>




        <script>
            const filterBtn = document.getElementById("filterBtn");
            const filterModal = document.getElementById("filterModal");
            const closeFilter = document.getElementById("closeFilter");

            filterBtn.addEventListener("click", () => {
                filterModal.classList.remove("hidden");
            });

            closeFilter.addEventListener("click", () => {
                filterModal.classList.add("hidden");
            });

            // Close on outside click
            window.addEventListener("click", (e) => {
                if (e.target === filterModal) {
                    filterModal.classList.add("hidden");
                }
            });
        </script>

        <script>
            // profile menu
            const profileBtn = document.getElementById("profileBtn");
            const profileMenu = document.getElementById("profileMenu");
            profileBtn.addEventListener("click", () => {
                profileMenu.classList.toggle("hidden");
            });
            document.addEventListener("click", (event) => {
                if (!profileBtn.contains(event.target) && !profileMenu.contains(event.target)) {
                    profileMenu.classList.add("hidden");
                }
            });
        </script>

        <script>
            const dateRangeBtn = document.getElementById('dateRangeBtn');
            const dateRangeModal = document.getElementById('dateRangeModal');
            const closeDateModal = document.getElementById('closeDateModal');
            const startDateInput = document.getElementById('startDate');
            const endDateInput = document.getElementById('endDate');
            const downloadBtn = document.getElementById('downloadExcelBtn');

            function openDateModal() {
                dateRangeModal.classList.remove('hidden');
            }

            function closeDateModalFn() {
                dateRangeModal.classList.add('hidden');
            }

            dateRangeBtn.addEventListener('click', openDateModal);
            closeDateModal.addEventListener('click', closeDateModalFn);

            // close on outside click
            window.addEventListener('click', (e) => {
                if (e.target === dateRangeModal) {
                    closeDateModalFn();
                }
            });

            function validateAndToggle() {
                const s = startDateInput.value;
                const e = endDateInput.value;
                if (s && e && s <= e) {
                    downloadBtn.removeAttribute('disabled');
                    downloadBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    // Build URL — we'll call export_excel.php with GET params
                    const url = `export_excel.php?start_date=${encodeURIComponent(s)}&end_date=${encodeURIComponent(e)}`;
                    downloadBtn.setAttribute('href', url);
                } else {
                    downloadBtn.setAttribute('disabled', '');
                    downloadBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    downloadBtn.setAttribute('href', '#');
                }
            }

            startDateInput.addEventListener('change', validateAndToggle);
            endDateInput.addEventListener('change', validateAndToggle);

            downloadBtn.addEventListener('click', function(e) {
                if (this.hasAttribute('disabled')) {
                    e.preventDefault();
                    alert('Please select a valid start and end date (start <= end).');
                } else {
                    // close modal once clicked so UX is tidy (download will start)
                    closeDateModalFn();
                }
            });
        </script>

        <script>
            // Open modal
            document.addEventListener('click', function(e) {
                if (e.target.closest('.btn-delete-assessment')) {
                    let id = e.target.closest('.btn-delete-assessment').dataset.id;
                    document.getElementById('delete_record_id').value = id;
                    document.getElementById('delete_remark').value = '';
                    document.getElementById('deleteRemarkModal').classList.remove('hidden');
                }
            });

            // Cancel button -> close modal
            document.getElementById('cancelDelete').addEventListener('click', function() {
                document.getElementById('deleteRemarkModal').classList.add('hidden');
            });

            // Form submit
            document.getElementById('deleteRemarkForm').addEventListener('submit', function(e) {
                e.preventDefault();
                let id = document.getElementById('delete_record_id').value;
                let remark = document.getElementById('delete_remark').value.trim();

                if (remark === '') {
                    alert('Please enter a remark before deleting.');
                    return;
                }

                fetch('delete_assessment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            id: id,
                            remark: remark
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'ok') {
                            document.getElementById('deleteRemarkModal').classList.add('hidden');
                            // Remove the row from the table (assuming you add a row ID)
                            let row = document.getElementById('row-' + id);
                            if (row) row.remove();

                            // Reload the page to refresh pagination and counts after deletion
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.msg);
                        }
                    })
                    .catch(() => alert('Request failed. Please try again.'));
            });
        </script>


        <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
        <script src="js/mystyle.js"></script>


        <footer class="text-center text-gray-500 text-sm mt-6">
            © 2025 Deoria Nagar Parishad - All Rights Reserved.
        </footer>
    </main>
</body>

</html>