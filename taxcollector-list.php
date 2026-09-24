<?php
session_start();

require "admin/db.php";

// Debug mode (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$records_per_page = 20;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Search Query
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = "";
if (!empty($search_query)) {
    $where_clause = " WHERE s.name LIKE '%$search_query%' OR s.username LIKE '%$search_query%' ";
}

// ----------------------
// FETCH DATA WITH FILTERS + PAGINATION
// ----------------------
$sql = "SELECT s.id, s.username, s.name, s.email, s.role, s.status, s.profile_pic, s.created_at";

// Check if mobile exists in table
$check_mobile = mysqli_query($conn, "SHOW COLUMNS FROM surveyors LIKE 'mobile'");
if (mysqli_num_rows($check_mobile) > 0) {
    $sql .= ", s.mobile";
}

// Using ~~ as separator to prevent long ward names with commas from breaking
$sql .= ", GROUP_CONCAT(w.ward_no ORDER BY CAST(w.ward_no AS UNSIGNED) ASC SEPARATOR '~~') AS assigned_wards
        FROM surveyors s
        LEFT JOIN surveyor_wards sw ON s.id = sw.surveyor_id
        LEFT JOIN wards w ON sw.ward_id = w.ward_id
        $where_clause
        GROUP BY s.id
        ORDER BY s.id DESC";


// Get total count for pagination
$count_sql = "SELECT COUNT(*) AS total_records FROM surveyors s $where_clause";
$result_count = mysqli_query($conn, $count_sql);
$row_count = mysqli_fetch_assoc($result_count);
$total_records = $row_count['total_records'] ?? 0;
$total_pages = $total_records > 0 ? ceil($total_records / $records_per_page) : 1;

// Final query with LIMIT and OFFSET
$sql .= " LIMIT $offset, $records_per_page";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $surveyors = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    $surveyors = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Collector List - Khalilabad Nagar Palika</title>
    <link rel="icon" href="admin/img/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Tailwind Config for Theme Colors based on Homepage -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            navy: '#051124',
                            /* Deep Dark Navy from Homepage */
                            card: '#0f172a',
                            /* Officer Directory Card Bg */
                            orange: '#f97316',
                            /* Bright Orange */
                            orangeHover: '#ea580c',
                            light: '#f8fafc'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
        }

        /* Top Header Styling */
        .top-header {
            background: linear-gradient(90deg, #051124 0%, #0a192f 100%);
            border-bottom: 4px solid #f97316;
        }

        /* Officer Directory Card Style */
        .tc-card {
            background: #0f172a;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border: 1px solid #1e293b;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .tc-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(249, 115, 22, 0.15);
            border-color: #334155;
        }

        .tc-card-top-accent {
            height: 4px;
            width: 100%;
            background: linear-gradient(90deg, #f97316 0%, #fb923c 100%);
        }

        /* Profile Image */
        .profile-img-wrap {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            padding: 4px;
            background: #f97316;
            margin: 0 auto;
            position: relative;
            z-index: 10;
            margin-top: 24px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .profile-img-wrap img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #0f172a;
            background-color: #ffffff;
        }

        /* Ward Blocks (Replaces the small pills to show full names) */
        .ward-list-container {
            max-height: 140px;
            overflow-y: auto;
        }

        /* Custom Scrollbar for Wards */
        .ward-list-container::-webkit-scrollbar {
            width: 4px;
        }

        .ward-list-container::-webkit-scrollbar-track {
            background: #1e293b;
            border-radius: 4px;
        }

        .ward-list-container::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 4px;
        }

        .ward-block {
            background-color: #1e293b;
            color: #e2e8f0;
            padding: 8px 12px;
            margin-bottom: 8px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            border: 1px solid #334155;
            word-break: break-word;
            transition: all 0.2s ease;
        }

        .ward-block:hover {
            background-color: #f97316;
            color: #ffffff;
            border-color: #f97316;
        }

        /* No Wards Message */
        .no-ward-msg {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px dashed #ef4444;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            width: 100%;
        }
    </style>
</head>

<body class="flex flex-col min-h-screen">

    <!-- HOMEPAGE STYLE HEADER -->
    <header class="top-header w-full py-4 px-6 md:px-12 flex flex-col md:flex-row items-center justify-between shadow-lg sticky top-0 z-50">
        <div class="flex items-center gap-4 mb-4 md:mb-0">
            <!-- Logo Setup -->
            <img src="assets/images/logo/logo.png" alt="Khalilabad Logo" class="h-14 w-auto" onerror="this.src='admin/img/logo.png'">
            <div class="text-white">
                <p class="text-[10px] md:text-xs text-brand-orange font-bold tracking-widest uppercase mb-0.5">Nagar Palika Parishad</p>
                <h1 class="text-xl md:text-2xl font-extrabold tracking-wider uppercase text-white">Khalilabad</h1>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <a href="index.php" class="bg-brand-orange hover:bg-brand-orangeHover text-white px-5 py-2.5 rounded-md font-bold text-sm transition shadow-[0_0_15px_rgba(249,115,22,0.4)] flex items-center gap-2">
                <i class="fa-solid fa-house"></i> Home
            </a>
        </div>
    </header>

    <!-- MAIN CONTENT CONTAINER (No Sidebar) -->
    <main class="w-full max-w-7xl mx-auto p-4 md:p-8 flex-1">

        <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4">
            <div>
                <h2 class="text-3xl font-extrabold text-brand-navy">Tax Collector Directory</h2>
                <p class="text-gray-500 font-medium mt-1">Manage all Surveyors and their Assigned Wards</p>
            </div>

            <!-- SEARCH BAR -->
            <form method="GET" class="flex w-full md:w-auto gap-2 bg-white p-2 rounded-lg shadow-sm border border-gray-200">
                <div class="relative w-full md:w-64">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search by name..." class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-orange focus:bg-white text-sm font-medium">
                </div>
                <button type="submit" class="bg-brand-navy hover:bg-gray-800 text-white px-4 py-2 rounded-md font-bold text-sm transition">
                    Search
                </button>
                <a href="taxcollector-list.php" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded-md font-bold text-sm transition flex items-center">
                    <i class="fa-solid fa-rotate-right"></i>
                </a>
            </form>
        </div>

        <!-- GRID CARDS -->
        <?php if (empty($surveyors)): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col items-center justify-center py-20 text-center">
                <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center text-gray-300 text-5xl mb-4">
                    <i class="fa-solid fa-users-slash"></i>
                </div>
                <h2 class="text-2xl font-bold text-brand-navy mb-2">No Records Found</h2>
                <p class="text-gray-500">We couldn't find any surveyor matching your search criteria.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">

                <?php foreach ($surveyors as $surveyor): ?>
                    <div class="tc-card">

                        <!-- Top Orange Accent -->
                        <div class="tc-card-top-accent"></div>

                        <!-- Status Indicator -->
                        <div class="absolute top-4 right-4 z-20">
                            <?php if ($surveyor['status'] === 'active'): ?>
                                <span class="flex h-3.5 w-3.5" title="Active">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-green-500 border-2 border-brand-card"></span>
                                </span>
                            <?php else: ?>
                                <span class="w-3.5 h-3.5 bg-red-500 border-2 border-brand-card rounded-full block" title="Inactive"></span>
                            <?php endif; ?>
                        </div>

                        <!-- Profile Image -->
                        <div class="profile-img-wrap">
                            <img src="admin/surveyors_profile/<?php echo htmlspecialchars($surveyor['username']); ?>/<?php echo htmlspecialchars($surveyor['profile_pic'] ?? ''); ?>"
                                onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($surveyor['name']); ?>&background=0f172a&color=fff&bold=true';"
                                alt="Profile">
                        </div>

                        <!-- Name & Contact -->
                        <div class="text-center px-4 pt-4 pb-5 border-b border-slate-800">
                            <h2 class="text-white font-bold text-lg uppercase tracking-wide truncate" title="<?php echo htmlspecialchars($surveyor['name']); ?>">
                                <?php echo htmlspecialchars($surveyor['name']); ?>
                            </h2>
                            <p class="text-brand-orange text-sm font-semibold mt-1">
                                <i class="fa-solid fa-phone-alt text-xs mr-1"></i>
                                <?php echo !empty($surveyor['mobile']) ? htmlspecialchars($surveyor['mobile']) : htmlspecialchars($surveyor['email']); ?>
                            </p>
                        </div>

                        <!-- Wards Section (Scrollable Full Width Blocks) -->
                        <div class="p-5 flex-1 bg-[#0b1120]">
                            <h3 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3 text-center">Assigned Wards</h3>

                            <div class="ward-list-container">
                                <?php if (!empty($surveyor['assigned_wards'])): ?>
                                    <!-- Split by the safe separator ~~ -->
                                    <?php
                                    $wards = explode('~~', $surveyor['assigned_wards']);
                                    foreach ($wards as $ward):
                                    ?>
                                        <div class="ward-block">
                                            <?php echo trim(htmlspecialchars($ward)); ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-ward-msg">
                                        <i class="fa-solid fa-circle-exclamation mr-1"></i> No Wards Assigned
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>

            </div>

            <!-- PAGINATION -->
            <div class="flex flex-col md:flex-row justify-between items-center mt-10 bg-white p-4 rounded-xl shadow-sm border border-gray-200">
                <div class="text-sm text-gray-500 font-semibold mb-4 md:mb-0">
                    Showing Page <span class="text-brand-navy text-base"><?php echo $current_page; ?></span> of <span class="text-brand-navy text-base"><?php echo $total_pages; ?></span>
                </div>

                <div class="flex items-center gap-2">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search_query); ?>" class="px-5 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold transition">Prev</a>
                    <?php endif; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search_query); ?>" class="px-5 py-2 rounded-md bg-brand-navy hover:bg-gray-800 text-white text-sm font-bold transition">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <!-- FOOTER -->
    <footer class="bg-brand-navy text-slate-400 py-6 text-center text-sm font-medium mt-auto">
        &copy; <?php echo date('Y'); ?> Nagar Palika Parishad, Khalilabad. All Rights Reserved.
    </footer>

</body>

</html>