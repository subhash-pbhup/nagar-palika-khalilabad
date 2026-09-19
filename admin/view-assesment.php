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

$user_id = (int)$_SESSION['user_id'];

// Prepare SQL statement to fetch user data
$stmt = $conn->prepare("SELECT username, email, role, role_id, profile_pic FROM users WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Initialize variables with default/fallback values
$user_name = "User Not Found";
$user_email = "Email Not Found";
$user_role = "user"; // Default role
$user_role_id = 0;
$profile_image_src = $default_image_src;

if ($result->num_rows === 1) {
    // Fetch the data
    $user_data = $result->fetch_assoc();

    // Assign the actual data fetched from the database
    $user_name = $user_data['username'];
    $user_email = $user_data['email'];
    $user_role = $user_data['role'];
    $user_role_id = (int)($user_data['role_id'] ?? 0);

    if (!empty($user_data['profile_pic'])) {
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
$conditions = ["a.is_deleted = 0"];
$params = [];
$types = "";

// ⭐ ROLE-BASED VISIBILITY RESTRICTION ⭐
$is_admin = in_array(strtoupper(trim($user_role)), ['ADMIN', 'SUPER ADMIN', 'ADMINISTRATOR']);

if (!$is_admin) {
    // Non-admins see records if:
    // 1. It is currently assigned to their role for verification.
    // OR
    // 2. They are the original creator of the record (they can ALWAYS see it).
    $conditions[] = "(a.current_verification_role_id = ? OR a.created_by = ?)";
    $params[] = $user_role_id;
    $params[] = $user_id;
    $types .= "ii";
}

// VERIFICATION STATUS FILTER: pending / reject / approved
$status_filter = strtolower(trim($_GET['status'] ?? ''));

if ($status_filter === 'rejected') {
    $status_filter = 'reject';
}

if (in_array($status_filter, ['pending', 'reject', 'approved'], true)) {
    $conditions[] = "LOWER(TRIM(COALESCE(NULLIF(a.verification_status, ''), 'pending'))) = ?";
    $params[] = $status_filter;
    $types .= "s";
} else {
    $status_filter = '';
}

// OR filter group
$orConditions = [];
$joinOwners = false;

// Holding Number
if (!empty($_GET['new_holding'])) {
    $orConditions[] = "a.new_holding LIKE ?";
    $params[] = "%" . $_GET['new_holding'] . "%";
    $types .= "s";
}

// Ward Number
if (!empty($_GET['ward'])) {
    $orConditions[] = "a.ward = ?";
    $params[] = $_GET['ward'];
    $types .= "s";
}

// Owner Name
if (!empty($_GET['owner_name'])) {
    $orConditions[] = "o.owner_name LIKE ? AND o.is_deleted = 0";
    $params[] = "%" . $_GET['owner_name'] . "%";
    $types .= "s";
    $joinOwners = true;
}

// Mobile Number
if (!empty($_GET['mobile_number'])) {
    $orConditions[] = "o.mobile LIKE ? AND o.is_deleted = 0";
    $params[] = "%" . $_GET['mobile_number'] . "%";
    $types .= "s";
    $joinOwners = true;
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
// ✅ FETCH DATA WITH FILTERS + ROLE JOINS
// ----------------------
$sql = "SELECT a.id, 
               a.municipality_name, 
               a.year_of_assessment, 
               a.ward, 
               a.new_holding, 
               a.property_type,
               a.verification_status,
               a.current_verification_role_id,
               a.created_at,
               a.latitude,
               a.longitude,
               GROUP_CONCAT(DISTINCT CASE WHEN o.is_deleted = 0 THEN o.owner_name END SEPARATOR ', ') AS owner_name,
               GROUP_CONCAT(DISTINCT CASE WHEN o.is_deleted = 0 THEN o.mobile END SEPARATOR ', ') AS mobile_numbers,
               
               -- ⭐ ARV details
               MAX(pad.id) AS arv_record_id,
       		   MAX(pad.arv_status) AS arv_status,
               
               -- ⭐ Added By Role Details
               cu.name AS creator_name,
               cr.role_name AS creator_role_name
               
        FROM assessments a
        LEFT JOIN assessment_owners o ON a.id = o.assessment_id
        LEFT JOIN property_arv_details pad ON a.id = pad.assessment_id
        LEFT JOIN users cu ON a.created_by = cu.id
        LEFT JOIN roles cr ON cu.role_id = cr.id
        $where_sql
        GROUP BY a.id
        ORDER BY a.id DESC 
        LIMIT $offset, $records_per_page";

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$start_sr_no = $offset;

$queryString = $_GET;
unset($queryString['page']);
$queryStr = http_build_query($queryString);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Assessments - Khalilabad Property Tax</title>
    <link href="img/favicon.ico" rel="icon">
    <link href='css/mystyle.css' rel='stylesheet'>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        /* Khalilabad logo theme: navy + orange */
        :root {
            --kp-navy: #061A3A;
            --kp-navy-light: #102B5C;
            --kp-orange: #F28C00;
            --kp-orange-dark: #D97700;
            --kp-orange-soft: #FFF4E5;
        }

        .sticky-action-column {
            position: sticky;
            right: 0;
            background-color: white;
            z-index: 10;
            box-shadow: -2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .table-header .sticky-action-column {
            background-color: #f9fafb;
            z-index: 11;
        }

        .table-cell {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            padding-left: 0.5rem;
            padding-right: 0.5rem;
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
            <div class="flex items-center justify-between mb-4 gap-4 flex-wrap">
                <h3 class="text-xl font-bold">Property Assessment Records </h3>

                <!-- STATUS FILTER BUTTONS -->
                <div class="flex items-center gap-2 flex-wrap">
                    <?php
                    $status_buttons = [
                        ''         => ['label' => 'All',      'class' => 'gray'],
                        'pending'  => ['label' => 'Pending', 'class' => 'yellow'],
                        'reject'   => ['label' => 'Rejected', 'class' => 'red'],
                        'approved' => ['label' => 'Approved', 'class' => 'green'],
                    ];

                    foreach ($status_buttons as $status_key => $status_btn):
                        $is_active = ($status_filter === $status_key);

                        $button_classes = [
                            'gray'   => $is_active ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                            'yellow' => $is_active ? 'bg-yellow-500 text-white' : 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100',
                            'red'    => $is_active ? 'bg-red-500 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100',
                            'green'  => $is_active ? 'bg-green-500 text-white' : 'bg-green-50 text-green-700 hover:bg-green-100',
                        ];
                    ?>
                        <?php
                        $status_query = $_GET;
                        unset($status_query['page']);
                        if ($status_key === '') {
                            unset($status_query['status']);
                        } else {
                            $status_query['status'] = $status_key;
                        }
                        $status_href = '?' . http_build_query($status_query);
                        ?>
                        <a href="<?= htmlspecialchars($status_href) ?>"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-xs font-semibold transition <?= $button_classes[$status_btn['class']] ?>">
                            <?php if ($status_key === 'pending'): ?>
                                <i class="fa fa-clock-o"></i>
                            <?php elseif ($status_key === 'reject'): ?>
                                <i class="fa fa-times-circle"></i>
                            <?php elseif ($status_key === 'approved'): ?>
                                <i class="fa fa-check-circle"></i>
                            <?php else: ?>
                                <i class="fa fa-list"></i>
                            <?php endif; ?>
                            <?= htmlspecialchars($status_btn['label']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

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
                                <th class="table-cell text-left">Status</th>
                                <!-- <th class="table-cell text-left">Added By</th> -->
                                <th class="table-cell text-left">Created At</th>
                                <th class="table-cell text-center sticky-action-column">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sr_no = $start_sr_no + 1;
                            while ($row = $result->fetch_assoc()):

                                // ⭐ B, G, N LOGIC START ⭐
                                $demand_icon = "";
                                $demand_class = "";
                                $demand_title = "";
                                $click_action = "";

                                if (!empty($row['arv_record_id'])) {
                                    if ($row['arv_status'] === 'bulk') {
                                        $demand_icon  = "B";
                                        $demand_class = "bg-blue-100 text-blue-600 font-bold";
                                        $demand_title = "Bulk Demand Uploaded";
                                        $click_action = "onclick=\"confirmDemand({$row['id']})\"";
                                    } else {
                                        $demand_icon  = "G";
                                        $demand_class = "bg-green-100 text-green-600 font-bold";
                                        $demand_title = "Demand Generated";
                                        $click_action = "onclick=\"confirmDemand({$row['id']})\"";
                                    }
                                } else {
                                    $demand_icon  = "N";
                                    $demand_class = "bg-red-100 text-red-600 font-bold";
                                    $demand_title = "ARV Not Generated";
                                    $click_action = "onclick=\"generateRealArv({$row['id']})\"";
                                }
                                // ⭐ B, G, N LOGIC END ⭐

                            ?>
                                <tr class="border-b border-gray-200 table-row-hover" id="row-<?php echo $row['id']; ?>">
                                    <td class="table-cell"><?php echo $sr_no++; ?></td>
                                    <td class="table-cell"><?php echo htmlspecialchars($row['owner_name'] ?? ''); ?></td>
                                    <td class="table-cell"><?php echo htmlspecialchars($row['year_of_assessment'] ?? ''); ?></td>
                                    <td class="table-cell"><?php echo htmlspecialchars($row['ward'] ?? ''); ?></td>
                                    <td class="table-cell"><?php echo htmlspecialchars($row['new_holding'] ?? ''); ?></td>
                                    <td class="table-cell"><?php echo htmlspecialchars($row['property_type'] ?? ''); ?></td>
                                    <td class="table-cell">
                                        <?php
                                        $row_status = strtolower(trim($row['verification_status'] ?? 'pending'));

                                        if ($row_status === 'approved') {
                                            $status_label = 'Approved';
                                            $status_class = 'bg-green-100 text-green-700';
                                            $status_icon = 'fa-check-circle';
                                        } elseif ($row_status === 'reject') {
                                            $status_label = 'Rejected';
                                            $status_class = 'bg-red-100 text-red-700';
                                            $status_icon = 'fa-times-circle';
                                        } else {
                                            $status_label = 'Pending';
                                            $status_class = 'bg-yellow-100 text-yellow-700';
                                            $status_icon = 'fa-clock-o';
                                        }
                                        ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold <?= $status_class ?>">
                                            <i class="fa <?= $status_icon ?>"></i>
                                            <?= $status_label ?>
                                        </span>
                                    </td>

                                    <!-- ⭐ ADDED BY COLUMN (Commented out per original file) ⭐ -->
                                    <!-- <td class="table-cell">
                                        <div class="font-medium text-slate-800"><?= htmlspecialchars($row['creator_name'] ?: 'Unknown User') ?></div>
                                        <?php if (!empty($row['creator_role_name'])): ?>
                                            <div class="text-[10px] inline-block bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded text-slate-600 mt-0.5">
                                                <?= htmlspecialchars($row['creator_role_name']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td> -->

                                    <td class="table-cell"><?php echo date('d M, Y', strtotime(htmlspecialchars($row['created_at']))); ?></td>

                                    <td class="table-cell text-center sticky-action-column">
                                        <?php
                                        $row_status = strtolower(trim($row['verification_status'] ?? 'pending'));
                                        if ($row_status === 'rejected') {
                                            $row_status = 'reject';
                                        }
                                        ?>
                                        <div class="flex justify-center items-center space-x-1">

                                            <!-- VIEW ASSESSMENT: ALWAYS AVAILABLE -->
                                            <a href="view_assesment_details.php?id=<?= (int)$row['id']; ?>"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-yellow-100 hover:bg-yellow-200 transition"
                                                title="View Assessment Details">
                                                <i class="fa fa-eye text-yellow-600 text-xs"></i>
                                            </a>

                                            <!-- ⭐ B, G, N ACTION BUTTON ⭐ -->
                                            <a href="javascript:void(0);"
                                                <?php echo $click_action; ?>
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full <?php echo $demand_class; ?> hover:opacity-80 transition font-bold text-xs"
                                                title="<?php echo $demand_title; ?>">
                                                <?php echo $demand_icon; ?>
                                            </a>

                                            <!-- ASSESSMENT UPDATE HISTORY: ALWAYS AVAILABLE -->
                                            <a href="assessment_history.php?assessment_id=<?= (int)$row['id']; ?>"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-purple-100 hover:bg-purple-200 transition"
                                                title="Assessment Update History">
                                                <i class="fa fa-history text-purple-600 text-xs"></i>
                                            </a>

                                            <!-- VERIFICATION HISTORY / CURRENT WORKFLOW: ALWAYS AVAILABLE -->
                                            <a href="verify-assessment.php?id=<?= (int)$row['id']; ?>"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 hover:bg-indigo-200 transition"
                                                title="Verification History">
                                                <i class="fa fa-check-circle text-indigo-600 text-xs"></i>
                                            </a>

                                            <!-- VERIFY: ONLY PENDING AND CURRENT ASSIGNED ROLE -->
                                            <?php
                                            $can_verify = ($row_status === 'pending' && $row['current_verification_role_id'] == $user_role_id && !$is_admin);
                                            ?>
                                            <?php if ($can_verify): ?>
                                                <a href="verify-assessment.php?id=<?= (int)$row['id']; ?>"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 hover:bg-blue-200 transition"
                                                    title="Verify Assessment">
                                                    <i class="fa fa-check text-blue-600 text-xs"></i>
                                                </a>
                                            <?php endif; ?>

                                            <!-- EDIT: PENDING / REJECTED. APPROVED CAN BE EDITED BY ADMIN ONLY -->
                                            <?php
                                            $is_admin_user = (strtoupper(trim((string)$user_role)) === 'ADMIN');
                                            $can_edit_row = (
                                                $row_status === 'pending' ||
                                                $row_status === 'reject' ||
                                                ($row_status === 'approved' && $is_admin_user)
                                            );
                                            ?>
                                            <?php if ($can_edit_row): ?>
                                                <a href="edit_assessment.php?id=<?= (int)$row['id']; ?>"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-100 hover:bg-green-200 transition"
                                                    title="Edit Assessment">
                                                    <i class="fa fa-pencil text-green-600 text-xs"></i>
                                                </a>
                                            <?php endif; ?>

                                            <!-- PROPERTY LOCATION -->
                                            <a href="property_map.php?lat=<?= urlencode($row['latitude'] ?? ''); ?>&lng=<?= urlencode($row['longitude'] ?? ''); ?>"
                                                target="_blank"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 transition"
                                                title="View Property Location">
                                                <i class="fa fa-map-marker text-gray-600 text-xs"></i>
                                            </a>

                                            <!-- DELETE -->
                                            <a href="javascript:void(0);"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-100 hover:bg-red-200 transition btn-delete-assessment"
                                                data-id="<?= (int)$row['id']; ?>"
                                                title="Delete Assessment Record">
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

        <div id="arvModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
            <!-- Logo-theme overlay -->
            <div class="fixed inset-0 bg-[#061A3A]/55 backdrop-blur-sm transition-opacity"
                onclick="closeArvModal()"></div>

            <!-- Confirmation modal -->
            <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl border border-[#E5E7EB]">

                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#F28C00]"></span>
                        <span class="text-xs font-bold uppercase tracking-[0.12em] text-[#6B7280]">
                            Official Confirmation
                        </span>
                    </div>

                    <button type="button"
                        onclick="closeArvModal()"
                        class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:text-[#061A3A] hover:bg-[#FFF4E5] transition"
                        aria-label="Close">
                        <i class="fa fa-times text-sm"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="px-6 py-7">
                    <div class="flex items-start gap-4">

                        <!-- Logo-inspired orange icon -->
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-[#FFF4E5] flex items-center justify-center">
                            <i class="fa fa-file-text-o text-[#F28C00] text-xl"></i>
                        </div>

                        <div class="min-w-0">
                            <h2 class="text-xl font-bold text-[#061A3A] mb-2">
                                Finalize Tax Details?
                            </h2>

                            <p class="text-sm leading-6 text-gray-500">
                                You are about to calculate and lock the property tax for this year.
                                Once confirmed, the tax details will be saved in the government records
                                and a permanent bill ID will be generated.
                            </p>
                        </div>
                    </div>

                    <!-- Important notice -->
                    <div class="mt-5 flex items-start gap-3 rounded-xl bg-[#FFF8ED] border border-[#F9D9AA] px-4 py-3">
                        <i class="fa fa-info-circle text-[#F28C00] mt-0.5"></i>
                        <p class="text-xs leading-5 text-[#7A4A00]">
                            Please verify the assessment details before generating the tax record.
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 bg-[#F8FAFC] border-t border-gray-100">
                    <button type="button"
                        onclick="closeArvModal()"
                        class="px-5 py-2.5 rounded-lg bg-white border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">
                        Go Back
                    </button>

                    <button type="button"
                        onclick="confirmGenerateArv()"
                        class="px-5 py-2.5 rounded-lg bg-[#F28C00] hover:bg-[#D97700] text-white text-sm font-semibold shadow-sm transition active:scale-[0.98]">
                        <i class="fa fa-check mr-1.5"></i>
                        Yes, Generate Now
                    </button>
                </div>
            </div>
        </div>

        <script>
            function confirmDemand(id) {
                if (confirm("Do you want to go to demand page?")) {
                    window.open('generate-demand.php?id=' + id, '_blank');
                }
            }

            let selectedAssessmentId = null;

            function generateRealArv(id) {
                selectedAssessmentId = id;
                document.getElementById('arvModal').classList.remove('hidden');
            }

            function closeArvModal() {
                selectedAssessmentId = null;
                document.getElementById('arvModal').classList.add('hidden');
            }

            function confirmGenerateArv() {
                if (!selectedAssessmentId) return;
                window.location.href = 'arv/calculate_arv.php?id=' + selectedAssessmentId;
            }
        </script>

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

            window.addEventListener("click", (e) => {
                if (e.target === filterModal) {
                    filterModal.classList.add("hidden");
                }
            });
        </script>

        <script>
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
                    closeDateModalFn();
                }
            });
        </script>

        <script>
            document.addEventListener('click', function(e) {
                if (e.target.closest('.btn-delete-assessment')) {
                    let id = e.target.closest('.btn-delete-assessment').dataset.id;
                    document.getElementById('delete_record_id').value = id;
                    document.getElementById('delete_remark').value = '';
                    document.getElementById('deleteRemarkModal').classList.remove('hidden');
                }
            });

            document.getElementById('cancelDelete').addEventListener('click', function() {
                document.getElementById('deleteRemarkModal').classList.add('hidden');
            });

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
                            let row = document.getElementById('row-' + id);
                            if (row) row.remove();
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.msg);
                        }
                    })
                    .catch(() => alert('Request failed. Please try again.'));
            });
        </script>

        <?php include 'include/footer.php' ?>