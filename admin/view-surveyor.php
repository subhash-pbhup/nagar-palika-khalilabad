<?php
session_start();

// echo "<pre>";
// print_r($_SESSION);
// die;
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

require "db.php";

// Debug mode (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$records_per_page = 20;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// ----------------------
// ✅ FETCH DATA WITH FILTERS + PAGINATION
// ----------------------
$sql = "SELECT s.id, s.username, s.name, s.email, s.role, s.status, s.profile_pic, s.created_at,
               GROUP_CONCAT(w.ward_no ORDER BY w.ward_no ASC SEPARATOR ', ') AS assigned_wards
        FROM surveyors s
        LEFT JOIN surveyor_wards sw ON s.id = sw.surveyor_id
        LEFT JOIN wards w ON sw.ward_id = w.ward_id
        GROUP BY s.id
        ORDER BY s.id DESC";


// Get total count for pagination
$count_sql = "SELECT COUNT(*) AS total_records FROM surveyors";
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
    // Debugging: show SQL error (remove in production)
    // echo "Query Error: " . mysqli_error($conn);
    $surveyors = [];
}

// Serial numbers
$start_sr_no = $offset;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Surveyors - Deoria Property Tax</title>
    <link href="img/favicon.ico" rel="icon">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link href='css/mystyle.css' rel='stylesheet'>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #e0f2fe, #f8fafc);
        }

        .glass {
            background: rgba(255, 255, 255, 0.25);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.05);
        }

        .hover-glass:hover {
            transform: translateY(-4px);
            transition: all 0.3s ease;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .table-header {
            background-color: rgba(255, 255, 255, 0.5);
        }

        .table-row-hover:hover {
            background-color: rgba(255, 255, 255, 0.5);
        }

        .table-cell {
            padding: 12px 16px;
        }

        .modal {
            z-index: 99999 !important;
        }

        /* --- NEW STYLES FOR SCROLLING TABLE --- */
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table-container table {
            width: 100%;
            white-space: nowrap;
            /* Prevents text from wrapping */
        }

        .sticky-actions {
            position: sticky;
            right: 0;
            background-color: rgba(255, 255, 255, 0.85);
            /* Semi-transparent background */
            z-index: 10;
        }

        .sticky-actions-header {
            position: sticky;
            right: 0;
            background-color: rgba(255, 255, 255, 0.5);
            /* Same as table header */
            z-index: 20;
        }

        /* Added to wrap assigned wards */
        .wrap-text {
            white-space: normal;
        }
    </style>
</head>

<body class="flex min-h-screen text-gray-800">

    <?php include 'sidemenu.php' ?>

    <main class="flex-1 p-6 space-y-8 overflow-y-auto">

        <header class="flex items-center justify-between glass p-4 rounded-2xl">
            <h1 class="text-2xl font-bold text-gray-900">View Surveyors</h1>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <input type="text" placeholder="Search..." class="pl-10 pr-4 py-2 rounded-full glass text-sm focus:outline-none">
                    <span class="absolute left-3 top-2.5 text-gray-400"><i class="fa fa-search" aria-hidden="true"></i>&nbsp;</span>
                </div>
                <button class="relative w-10 h-10 rounded-full glass flex items-center justify-center hover-glass"><i class="fa fa-bell" aria-hidden="true"></i>&nbsp;
                    <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>
                <a href="new-assesment.php" class="relative w-10 h-10 rounded-full glass flex items-center justify-center hover-glass"><i class="fa fa-plus" aria-hidden="true"></i>&nbsp;</a>
                <div class="relative inline-block text-left">
                    <button id="profileBtn" class="flex items-center focus:outline-none">
                        <div class="w-10 h-10 rounded-full overflow-hidden border border-gray-300">
                            <img src="<?php echo $_SESSION['profile_img']; ?>" class="w-full h-full object-cover" alt="Profile Picture">
                        </div>
                    </button>
                    <div id="profileMenu" class="hidden absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
                        <a href="logout.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V5m0 14h.01M13 5h.01" />
                            </svg>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="glass rounded-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold">Surveyor Records</h3>
                <div class="flex items-center space-x-3">
                    <a href="add-surveyor.php" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-full shadow-lg transition duration-300 ease-in-out transform hover:-translate-y-1">
                        <i class="fa fa-plus" aria-hidden="true"></i> Add New Surveyor
                    </a>
                </div>
            </div>

            <?php if (empty($surveyors)): ?>
                <div class="flex flex-col items-center justify-center h-48 text-center">
                    <p class="text-3xl mb-2">😔</p>
                    <p class="text-lg text-gray-500 font-medium">No surveyor records found!</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-gray-500 text-xs uppercase border-b border-gray-200 table-header">
                                <th class="table-cell text-left">Sr. No.</th>
                                <th class="table-cell text-left">Profile</th>
                                <th class="table-cell text-left">Username</th>
                                <th class="table-cell text-left">Full Name</th>
                                <th class="table-cell text-left">Email</th>
                                <th class="table-cell text-left">Assigned Wards</th>
                                <th class="table-cell text-left">Status</th>
                                <th class="table-cell text-left sticky-actions-header">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $sr_no = $start_sr_no;
                            foreach ($surveyors as $surveyor): $sr_no++; ?>
                                <tr class="border-b border-gray-200 table-row-hover">
                                    <td class="table-cell"><?php echo $sr_no; ?></td>
                                    <td class="table-cell">
                                        <div class="w-10 h-10 rounded-full overflow-hidden border border-gray-300">
                                            <img src="surveyors_profile/<?php echo htmlspecialchars($surveyor['username']); ?>/<?php echo htmlspecialchars($surveyor['profile_pic'] ?? ''); ?>"
                                                onerror="this.onerror=null;this.src='man.png';"
                                                class="w-full h-full object-cover"
                                                alt="Profile Picture">
                                        </div>
                                    </td>
                                    <td class="table-cell"><?php echo htmlspecialchars($surveyor['username']); ?></td>
                                    <td class="table-cell"><?php echo htmlspecialchars($surveyor['name']); ?></td>
                                    <td class="table-cell"><?php echo htmlspecialchars($surveyor['email']); ?></td>
                                    <td class="table-cell wrap-text"><?php echo !empty($surveyor['assigned_wards']) ? htmlspecialchars($surveyor['assigned_wards']) : 'N/A'; ?></td>
                                    <td class="table-cell">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" data-id="<?php echo $surveyor['id']; ?>" class="sr-only peer toggle-status"
                                                <?php echo ($surveyor['status'] === 'active') ? 'checked' : ''; ?>>
                                            <div class="w-11 h-6 bg-gray-300 rounded-full peer-checked:bg-green-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                                        </label>
                                    </td>
                                    <td class="table-cell sticky-actions">
                                        <div class="flex items-center justify-center space-x-2">
                                            <a href="view-surveyor-details.php?id=<?php echo htmlspecialchars($surveyor['id']); ?>"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 hover:bg-blue-200 transition"
                                                title="View Surveyor">
                                                <i class="fa fa-eye text-blue-600"></i>
                                            </a>
                                            <button class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-purple-100 hover:bg-purple-200 transition assign-ward-btn"
                                                title="Assign Wards"
                                                data-id="<?php echo htmlspecialchars($surveyor['id']); ?>">
                                                <i class="fa fa-map text-purple-600"></i>
                                            </button>
                                            <a href="reset-password.php?id=<?php echo htmlspecialchars($surveyor['id']); ?>"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-yellow-100 hover:bg-yellow-200 transition"
                                                title="Reset Password">
                                                <i class="fa fa-key text-yellow-600"></i>
                                            </a>
                                            <a href="update-surveyor.php?id=<?php echo htmlspecialchars($surveyor['id']); ?>"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 transition"
                                                title="Update Records">
                                                <i class="fa fa-pencil text-gray-600"></i>
                                            </a>
                                            <a href="javascript:void(0);"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-100 hover:bg-red-200 transition btn-delete-assessment"
                                                data-id="<?php echo $surveyor['id']; ?>"
                                                title="Delete Surveyor">
                                                <i class="fa fa-trash text-red-600"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col md:flex-row justify-center items-center mt-6 space-y-3 md:space-y-0 md:space-x-4">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo $current_page - 1; ?>" class="px-4 py-2 rounded-lg glass hover-glass">Previous</a>
                    <?php endif; ?>

                    <span class="px-4 py-2 text-gray-700">
                        Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
                    </span>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?>" class="px-4 py-2 rounded-lg glass hover-glass">Next</a>
                    <?php endif; ?>

                    <form method="get" class="flex items-center space-x-2">
                        <input type="number" name="page" min="1" max="<?php echo $total_pages; ?>" class="w-20 px-2 py-1 border rounded-lg focus:outline-none focus:ring focus:border-blue-300" placeholder="Page" required>
                        <button type="submit" class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-lg shadow">Go</button>
                    </form>
                </div>

            <?php endif; ?>
        </div>

        <footer class="text-center text-gray-500 text-sm mt-6">
            © 2025 Deoria Nagar Parishad - All Rights Reserved.
        </footer>
    </main>

    <div id="assignWardModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full modal">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-2xl bg-white" style="width:500px">
            <div class="mt-3 text-center">
                <h3 class="text-xl leading-6 font-medium text-gray-900 mb-4">Assign Wards</h3>
                <div class="mt-2 px-7 py-3">
                    <form id="assignWardForm" method="POST">
                        <input type="hidden" id="surveyorId" name="surveyor_id">
                        <div class="mb-4 text-left">
                            <label for="wards" class="block text-gray-700 font-semibold mb-2">Select Wards</label>
                            <select id="wards" name="wards[]" multiple class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring focus:border-blue-300" style="min-height: 200px;">
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Hold `Ctrl` (Windows) or `Cmd` (Mac) to select multiple wards.</p>
                        </div>
                        <div class="flex items-center justify-end space-x-4 mt-6">
                            <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-lg text-gray-700 border border-gray-300 hover:bg-gray-100 transition">Cancel</button>
                            <button type="submit" class="px-4 py-2 rounded-lg text-white bg-blue-500 hover:bg-blue-600 transition">Save Wards</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.toggle-status').forEach(toggle => {
            toggle.addEventListener('change', function() {
                let id = this.dataset.id;
                let status = this.checked ? 'active' : 'inactive';
                const originalState = this.checked;

                let formData = new URLSearchParams();
                formData.append('id', id);
                formData.append('status', status);

                fetch('update-status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: formData
                    })
                    .then(res => {
                        if (!res.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return res.json();
                    })
                    .then(data => {
                        console.log('Response from server:', data);

                        if (data.status === 'success') {
                            console.log('✅ Status change was successful!');
                        } else {
                            console.error('❌ Status change failed on the server:', data.message);
                            this.checked = originalState; // Revert the toggle state on failure
                        }
                    })
                    .catch(error => {
                        console.error('There was a problem with the fetch operation:', error);
                        this.checked = originalState; // Revert the toggle state on network error
                    });
            });
        });
    </script>

    <script>
        const assignWardModal = document.getElementById('assignWardModal');
        const wardsSelect = document.getElementById('wards');
        const surveyorIdInput = document.getElementById('surveyorId');

        function openModal(surveyorId) {
            surveyorIdInput.value = surveyorId;
            assignWardModal.classList.remove('hidden');
            fetchWards(surveyorId);
        }

        function closeModal() {
            assignWardModal.classList.add('hidden');
            wardsSelect.innerHTML = '';
            document.getElementById('assignWardForm').reset();
        }

        // Add click event to the "Assign Wards" buttons
        document.querySelectorAll('.assign-ward-btn').forEach(button => {
            button.addEventListener('click', () => {
                const surveyorId = button.getAttribute('data-id');
                openModal(surveyorId);
            });
        });

        // Handle form submission
        document.getElementById('assignWardForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = e.target;
            const formData = new FormData(form);

            fetch('save-surveyor-wards.php', {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Wards assigned successfully!');
                        closeModal();
                        window.location.reload(); // Reload to show updated wards
                    } else {
                        alert('Error assigning wards: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An unexpected error occurred.');
                });
        });

        // Fetch wards and populate the select dropdown
        function fetchWards(surveyorId) {
            // Clear previous options
            wardsSelect.innerHTML = '';

            fetch('get-wards.php')
                .then(response => {
                    // Check if the response is successful
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(allWards => {
                    if (allWards.status !== 'success') {
                        throw new Error('Server-side error: ' + allWards.message);
                    }

                    fetch(`get-assigned-wards.php?id=${surveyorId}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(assignedWards => {
                            // Create a Set for faster lookup of assigned wards
                            const assignedWardNumbers = new Set(assignedWards.map(w => w.ward_no));

                            allWards.wards.forEach(ward => {
                                const option = document.createElement('option');
                                option.value = ward.ward_no;
                                option.textContent = `Ward No. ${ward.ward_no}`;
                                if (assignedWardNumbers.has(ward.ward_no)) {
                                    option.selected = true;
                                }
                                wardsSelect.appendChild(option);
                            });
                        })
                        .catch(error => {
                            console.error('Error fetching assigned wards:', error);
                            alert('Failed to load assigned wards. Please check the network and server.');
                        });
                })
                .catch(error => {
                    console.error('Error fetching all wards:', error);
                    alert('Failed to load all wards. Please check the network and server.');
                });
        }
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

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="js/mystyle.js"></script>
</body>

</html>