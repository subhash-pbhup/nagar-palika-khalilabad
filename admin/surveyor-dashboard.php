<?php
session_start();

// Enable full error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// echo "<pre>";
// print_r($_SESSION);
// die;

require_once 'db.php';


// Check for valid login session and role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'surveyor') {
    header("Location: index.php");
    exit;
}

$surveyor_id = $_SESSION['user_id'];

// ✅ Fetch surveyor info from DB to ensure email and name are available
try {
    $info_sql = "SELECT username, email, profile_pic, name FROM surveyors WHERE id = ?";
    $stmt_info = $conn->prepare($info_sql);
    $stmt_info->bind_param("i", $surveyor_id);
    $stmt_info->execute();

    $info_res = $stmt_info->get_result();

    if ($info_row = $info_res->fetch_assoc()) {

        $display_username = htmlspecialchars(
            $info_row['name'] ?: $info_row['username']
        );

        $display_email = htmlspecialchars($info_row['email']);

        $display_user_name = htmlspecialchars($info_row['username']);

        $display_img = htmlspecialchars($info_row['profile_pic']);
    } else {

        $display_username = "Surveyor User";
        $display_email = "surveyor@nagarpalika.com";
        $display_img = "default.png";
    }

    $stmt_info->close();
} catch (mysqli_sql_exception $e) {
    echo "<h1>Database Error (Surveyor Info):</h1>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// --- PHP Functionality (UPDATED FOR MULTIPLE OWNERS) ---
$surveyor_id = $_SESSION['user_id'];
$records_per_page = 20;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

try {
    // Today's Survey
    $today_sql = "SELECT COUNT(*) AS today_count FROM assessments WHERE DATE(created_at) = CURDATE() AND surveyor_id = ? AND is_deleted = 0";
    $stmt_today = mysqli_prepare($conn, $today_sql);
    mysqli_stmt_bind_param($stmt_today, "i", $surveyor_id);
    mysqli_stmt_execute($stmt_today);
    $today_res = mysqli_stmt_get_result($stmt_today);
    $today = mysqli_fetch_assoc($today_res)['today_count'];
    mysqli_stmt_close($stmt_today);

    // This Week's Survey
    $week_sql = "SELECT COUNT(*) AS week_count FROM assessments WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1) AND surveyor_id = ? AND is_deleted = 0";
    $stmt_week = mysqli_prepare($conn, $week_sql);
    mysqli_stmt_bind_param($stmt_week, "i", $surveyor_id);
    mysqli_stmt_execute($stmt_week);
    $week_res = mysqli_stmt_get_result($stmt_week);
    $week = mysqli_fetch_assoc($week_res)['week_count'];
    mysqli_stmt_close($stmt_week);

    // This Month's Survey
    $month_sql = "SELECT COUNT(*) AS month_count FROM assessments WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) AND surveyor_id = ? AND is_deleted = 0";
    $stmt_month = mysqli_prepare($conn, $month_sql);
    mysqli_stmt_bind_param($stmt_month, "i", $surveyor_id);
    mysqli_stmt_execute($stmt_month);
    $month_res = mysqli_stmt_get_result($stmt_month);
    $month = mysqli_fetch_assoc($month_res)['month_count'];
    mysqli_stmt_close($stmt_month);

    // Total Survey
    $total_sql = "SELECT COUNT(*) AS total_count FROM assessments WHERE surveyor_id = ? AND is_deleted = 0";
    $stmt_total = mysqli_prepare($conn, $total_sql);
    mysqli_stmt_bind_param($stmt_total, "i", $surveyor_id);
    mysqli_stmt_execute($stmt_total);
    $total_res = mysqli_stmt_get_result($stmt_total);
    $total = mysqli_fetch_assoc($total_res)['total_count'];
    mysqli_stmt_close($stmt_total);

    // Get total number of records for pagination (UNCHANGED)
    $total_records_sql = "SELECT COUNT(*) AS total FROM assessments WHERE surveyor_id = ? AND is_deleted = 0";
    $stmt_total_records = mysqli_prepare($conn, $total_records_sql);
    mysqli_stmt_bind_param($stmt_total_records, "i", $surveyor_id);
    mysqli_stmt_execute($stmt_total_records);
    $total_records_res = mysqli_stmt_get_result($stmt_total_records);
    $total_records = mysqli_fetch_assoc($total_records_res)['total'];
    $total_pages = ceil($total_records / $records_per_page);
    mysqli_stmt_close($stmt_total_records);

    // Fetch assessments for the current page - **UPDATED QUERY**
    // Using GROUP_CONCAT to display ALL non-deleted owners for an assessment
    $assessment_sql = "
        SELECT 
            a.id, 
            GROUP_CONCAT(ao.owner_name SEPARATOR ', ') as owner_name, 
            a.year_of_assessment, 
            a.ward, 
            a.new_holding, 
            a.property_type, 
            a.created_at 
        FROM 
            assessments a 
        LEFT JOIN 
            assessment_owners ao ON a.id = ao.assessment_id AND ao.is_deleted = 0
        WHERE 
            a.surveyor_id = ? AND a.is_deleted = 0 
        GROUP BY 
            a.id, a.year_of_assessment, a.ward, a.new_holding, a.property_type, a.created_at
        ORDER BY 
            a.created_at DESC 
        LIMIT ? OFFSET ?";

    $stmt_assessment = mysqli_prepare($conn, $assessment_sql);
    mysqli_stmt_bind_param($stmt_assessment, "iii", $surveyor_id, $records_per_page, $offset);
    mysqli_stmt_execute($stmt_assessment);
    $assessment_res = mysqli_stmt_get_result($stmt_assessment);
    mysqli_stmt_close($stmt_assessment);
} catch (mysqli_sql_exception $e) {
    // Production-level error handling should be more discreet
    echo "<h1>Database Error:</h1>";
    echo "<p>Please contact support or check your database configuration. Error details: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// --- End PHP Functionality ---
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khalilabad Property Tax Surveyor Dashboard</title>
    <link href="img/favicon.ico" rel="icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        :root {
            --primary-color: #4f46e5;
            /* Indigo-600 */
            --primary-hover: #4338ca;
            /* Indigo-700 */
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.06);
        }

        body {
            font-family: 'Inter', sans-serif;
            /* Light, clean background */
            background-color: #f8fafc;
            color: #1f2937;
        }

        /* Modern Card Style */
        .donezo-card {
            background-color: #ffffff;
            border-radius: 1rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .donezo-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
        }

        /* Header Background and Shadow */
        .header-bg {
            background-color: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
        }
    </style>
</head>

<body class="flex min-h-screen text-gray-800">

    <main class="flex-1 p-4 md:p-8 space-y-8 overflow-y-auto">

        <header class="flex items-center justify-between header-bg px-4 md:px-6 py-3 rounded-xl sticky top-0 z-30">
            <div class="flex items-center space-x-4">
                <img src="img/admin-logo.png" class="h-10 w-auto" alt="Admin Logo">
            </div>

            <div class="flex items-center space-x-4">
                <div class="relative hidden lg:block">
                    <input type="text" placeholder="Search property/task..." class="pl-10 pr-4 py-2 w-64 rounded-full bg-gray-100 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <span class="absolute left-3 top-2.5 text-gray-400"><i class='bx bx-search'></i></span>
                </div>

                <button class="relative w-10 h-10 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center hover:bg-gray-200 transition">
                    <i class='bx bx-bell text-xl'></i>
                    <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>

                <a href="new-assesment.php" class="hidden sm:flex items-center px-3 py-2 bg-indigo-600 text-white font-medium rounded-full hover:bg-indigo-700 transition text-sm">
                    <i class='bx bx-plus-circle text-lg mr-1'></i> New Assessment
                </a>

                <div class="relative inline-block text-left">
                    <button id="profileBtn" class="flex items-center focus:outline-none bg-gray-50 p-1.5 rounded-full hover:bg-gray-100 transition">
                        <span class="hidden md:flex flex-col items-end mr-3">
                            <span class="text-sm font-semibold text-gray-800"><?php echo $display_username; ?></span>
                            <span class="text-xs text-gray-500"><?php echo $display_email; ?></span>
                        </span>
                        <div class="w-10 h-10 rounded-full overflow-hidden border-2 border-indigo-500 hover:border-indigo-700 transition">
                            <img src="surveyors_profile/<?php echo $display_user_name; ?>/<?php echo $display_img; ?>" class="w-full h-full object-cover" alt="User Avatar">
                        </div>
                        <i class='bx bx-chevron-down text-lg ml-1 text-gray-500 hidden md:block'></i>
                    </button>

                    <div id="profileMenu" class="hidden absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-lg shadow-xl z-50 py-1">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-medium text-gray-800 truncate"><?php echo $display_username; ?></p>
                            <p class="text-xs text-gray-500 truncate"><?php echo $display_email; ?></p>
                        </div>

                        <a href="reset-password.php" class="flex items-center px-4 py-2 text-base text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition">
                            <i class='bx bx-key text-xl mr-2'></i>
                            Reset Password
                        </a>

                        <hr class="my-1 border-gray-100">

                        <a href="logout.php" class="flex items-center px-4 py-2 text-base text-gray-700 hover:bg-red-50 hover:text-red-600 transition">
                            <i class='bx bx-log-out text-xl mr-2 text-red-500'></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="flex items-center justify-between mt-6 p-4 bg-white rounded-xl shadow-sm border border-gray-100">
            <h1 class="text-2xl font-bold text-gray-900">Surveyor Dashboard</h1>
            <a href="new-assesment.php" class="sm:hidden bg-indigo-600 text-white px-3 py-2 rounded-full font-semibold hover:bg-indigo-700 transition-colors duration-300 flex items-center text-sm">
                <i class='bx bx-plus-circle mr-1'></i> New
            </a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 lg:gap-6">

            <div class="donezo-card p-4 lg:p-6 flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-sm md:text-lg font-medium text-gray-700">Today's Survey</h3>
                        <p class="text-3xl lg:text-4xl font-extrabold text-gray-900 mt-1"><?php echo $today; ?></p>
                    </div>
                    <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                        <i class='bx bx-calendar-check text-xl md:text-2xl'></i>
                    </div>
                </div>
                <p class="text-xs md:text-sm font-medium text-gray-500 mt-2">Surveys completed today</p>
            </div>

            <div class="donezo-card p-4 lg:p-6 flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-sm md:text-lg font-medium text-gray-700">This Week</h3>
                        <p class="text-3xl lg:text-4xl font-extrabold text-green-600 mt-1"><?php echo $week; ?></p>
                    </div>
                    <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                        <i class='bx bx-line-chart text-xl md:text-2xl'></i>
                    </div>
                </div>
                <p class="text-xs md:text-sm font-medium text-gray-500 mt-2">Number of surveys conducted this week</p>
            </div>

            <div class="donezo-card p-4 lg:p-6 flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-sm md:text-lg font-medium text-gray-700">This Month</h3>
                        <p class="text-3xl lg:text-4xl font-extrabold text-yellow-600 mt-1"><?php echo $month; ?></p>
                    </div>
                    <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600">
                        <i class='bx bx-trending-up text-xl md:text-2xl'></i>
                    </div>
                </div>
                <p class="text-xs md:text-sm font-medium text-gray-500 mt-2">Surveys completed this month</p>
            </div>

            <div class="donezo-card p-4 lg:p-6 flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-sm md:text-lg font-medium text-gray-700">Total Survey</h3>
                        <p class="text-3xl lg:text-4xl font-extrabold text-gray-600 mt-1"><?php echo $total; ?></p>
                    </div>
                    <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-600">
                        <i class='bx bx-list-ol text-xl md:text-2xl'></i>
                    </div>
                </div>
                <p class="text-xs md:text-sm font-medium text-gray-500 mt-2">Total Surveys till now</p>
            </div>
        </div>

        <div class="donezo-card p-4 md:p-6">
            <h2 class="text-xl font-bold mb-4 border-b pb-2 text-gray-900">Recent Assessments</h2>
            <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Sr. No.</th>
                            <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Owner Name(s)</th>
                            <th class="hidden sm:table-cell px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Year</th>
                            <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Ward</th>
                            <th class="hidden md:table-cell px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">New Holding No.</th>
                            <th class="hidden sm:table-cell px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Property Type</th>
                            <th class="hidden lg:table-cell px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Created At</th>
                            <th class="px-3 md:px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <?php
                        $i = $offset + 1;
                        if ($assessment_res && mysqli_num_rows($assessment_res) > 0) {
                            while ($row = mysqli_fetch_assoc($assessment_res)):
                                $date = new DateTime($row['created_at']);
                                $formatted_date = $date->format('d-m-Y');
                                // Display all concatenated non-deleted owner names, or 'N/A' if none exist
                                $owner_names = $row['owner_name'] ? htmlspecialchars($row['owner_name']) : 'N/A';
                        ?>
                                <tr>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm font-medium text-gray-500"><?php echo $i++; ?></td>
                                    <td class="px-3 md:px-6 py-4 text-xs md:text-sm text-gray-700 font-medium"><?php echo $owner_names; ?></td>
                                    <td class="hidden sm:table-cell px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-500"><?php echo htmlspecialchars($row['year_of_assessment'] ?? 'N/A'); ?></td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-500"><?php echo htmlspecialchars($row['ward'] ?? 'N/A'); ?></td>
                                    <td class="hidden md:table-cell px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-500"><?php echo htmlspecialchars($row['new_holding'] ?? 'N/A'); ?></td>
                                    <td class="hidden sm:table-cell px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <?php echo htmlspecialchars($row['property_type'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td class="hidden lg:table-cell px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-500"><?php echo htmlspecialchars($formatted_date); ?></td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-center">
                                        <a href="view-surveyor-assessment.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="text-indigo-600 hover:text-indigo-900 transition" title="View Details">
                                            <i class='bx bx-show text-lg'></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php
                            endwhile;
                        } else {
                            ?>
                            <tr>
                                <td colspan="8" class="px-6 py-10 text-center text-lg text-gray-500">
                                    <i class='bx bx-data text-4xl mb-2 block'></i>
                                    No assessments found yet. Let's create one!
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-center mt-6">
                <nav class="relative z-0 inline-flex rounded-lg shadow-md" aria-label="Pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo $current_page - 1; ?>" class="relative inline-flex items-center px-3 py-2 md:px-4 md:py-2 rounded-l-lg border border-gray-300 bg-white text-xs md:text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            <i class='bx bx-chevron-left text-base md:text-lg'></i> <span class="hidden sm:inline">Previous</span>
                        </a>
                    <?php endif; ?>

                    <?php
                    // Simple pagination display (can be optimized for large number of pages)
                    // Added logic to only show a few pages around the current page for better mobile view
                    $start_page = max(1, $current_page - 1);
                    $end_page = min($total_pages, $current_page + 1);

                    if ($current_page > 2 && $total_pages > 3) {
                        echo '<span class="relative inline-flex items-center px-3 py-2 md:px-4 md:py-2 border border-gray-300 bg-white text-xs md:text-sm text-gray-700">...</span>';
                    }

                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <a href="?page=<?php echo $i; ?>" class="relative inline-flex items-center px-3 py-2 md:px-4 md:py-2 border border-gray-300 text-xs md:text-sm font-medium transition
                            <?php echo $i == $current_page ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages - 1 && $total_pages > 3) {
                        echo '<span class="relative inline-flex items-center px-3 py-2 md:px-4 md:py-2 border border-gray-300 bg-white text-xs md:text-sm text-gray-700">...</span>';
                    } ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?>" class="relative inline-flex items-center px-3 py-2 md:px-4 md:py-2 rounded-r-lg border border-gray-300 bg-white text-xs md:text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            <span class="hidden sm:inline">Next</span> <i class='bx bx-chevron-right text-base md:text-lg'></i>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>

        <footer class="text-center text-gray-500 text-sm mt-6">
            © 2025 Khalilabad Nagar Parishad - All Rights Reserved.
        </footer>
    </main>

    <script>
        // Profile Dropdown Logic
        const profileBtn = document.getElementById("profileBtn");
        const profileMenu = document.getElementById("profileMenu");

        profileBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            profileMenu.classList.toggle("hidden");
        });

        // Close dropdown if clicked outside
        window.addEventListener("click", (e) => {
            if (!profileBtn.contains(e.target) && !profileMenu.contains(e.target)) {
                profileMenu.classList.add("hidden");
            }
        });
    </script>
</body>

</html>