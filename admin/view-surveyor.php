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
$sql .= " LIMIT $offset,$records_per_page";
$result = mysqli_query($conn, $sql);


if ($result && mysqli_num_rows($result) > 0) {
    $surveyors = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    $surveyors = [];
}

// Serial numbers
$start_sr_no = $offset;
include "include/header.php";
?>

<style>
    /* Add this inside your existing styles if not present */
    .table-container {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 0.5rem;
        box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.02);
    }

    .table-cell {
        padding: 14px 16px;
        vertical-align: middle;
        white-space: nowrap;
    }

    /* Keeps actions column fixed on the right when scrolling */
    .sticky-actions {
        position: sticky;
        right: 0;
        background-color: #ffffff;
        box-shadow: -4px 0 10px rgba(0, 0, 0, 0.04);
        z-index: 10;
    }

    .sticky-actions-header {
        position: sticky;
        right: 0;
        background-color: #f8fafc;
        box-shadow: -4px 0 10px rgba(0, 0, 0, 0.04);
        z-index: 20;
    }

    .wrap-text {
        white-space: normal;
        min-width: 200px;
    }
</style>

<main class="flex-1 p-6 space-y-8 overflow-y-auto w-full max-w-full">

    <div class="glass rounded-2xl p-6 bg-white shadow-sm border border-gray-100">
        <div class="flex flex-col sm:flex-row items-center justify-between mb-6 gap-4">
            <h3 class="text-2xl font-extrabold text-gray-800">Surveyor Records</h3>
            <div class="flex items-center space-x-3">
                <a href="add-surveyor.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-5 rounded-full shadow-md transition duration-300 ease-in-out transform hover:-translate-y-1 flex items-center gap-2">
                    <i class="fa fa-plus" aria-hidden="true"></i> Add New Surveyor
                </a>
            </div>
        </div>

        <?php if (empty($surveyors)): ?>
            <div class="flex flex-col items-center justify-center h-64 text-center border-2 border-dashed border-gray-200 rounded-xl bg-gray-50">
                <p class="text-4xl mb-3 text-gray-300"><i class="fa fa-users-slash"></i></p>
                <p class="text-lg text-gray-500 font-bold">No surveyor records found!</p>
            </div>
        <?php else: ?>
            <!-- FIXED: Added responsive table container wrapper -->
            <div class="table-container border border-gray-200">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50">
                        <tr class="text-gray-500 text-xs uppercase border-b border-gray-200">
                            <th class="table-cell font-bold">Sr. No.</th>
                            <th class="table-cell font-bold">Profile</th>
                            <th class="table-cell font-bold">Username</th>
                            <th class="table-cell font-bold">Full Name</th>
                            <th class="table-cell font-bold">Email</th>
                            <th class="table-cell font-bold">Assigned Wards</th>
                            <th class="table-cell font-bold text-center">Status</th>
                            <!-- Sticky Action Header -->
                            <th class="table-cell font-bold text-center sticky-actions-header">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php $sr_no = $start_sr_no;
                        foreach ($surveyors as $surveyor): $sr_no++; ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="table-cell font-semibold text-gray-600"><?php echo $sr_no; ?></td>
                                <td class="table-cell">
                                    <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-gray-200 shadow-sm">
                                        <img src="surveyors_profile/<?php echo htmlspecialchars($surveyor['username']); ?>/<?php echo htmlspecialchars($surveyor['profile_pic'] ?? ''); ?>"
                                            onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($surveyor['name']); ?>&background=random';"
                                            class="w-full h-full object-cover"
                                            alt="Profile Picture">
                                    </div>
                                </td>
                                <td class="table-cell font-medium text-gray-800"><?php echo htmlspecialchars($surveyor['username']); ?></td>
                                <td class="table-cell font-bold text-gray-800"><?php echo htmlspecialchars($surveyor['name']); ?></td>
                                <td class="table-cell text-gray-500"><?php echo htmlspecialchars($surveyor['email']); ?></td>
                                <td class="table-cell wrap-text text-gray-600 leading-relaxed">
                                    <?php echo !empty($surveyor['assigned_wards']) ? htmlspecialchars($surveyor['assigned_wards']) : '<span class="text-red-500 font-semibold"><i class="fa fa-exclamation-circle"></i> N/A</span>'; ?>
                                </td>
                                <td class="table-cell text-center">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" data-id="<?php echo $surveyor['id']; ?>" class="sr-only peer toggle-status"
                                            <?php echo ($surveyor['status'] === 'active') ? 'checked' : ''; ?>>
                                        <div class="w-11 h-6 bg-gray-300 rounded-full peer-checked:bg-green-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full peer-checked:after:border-white shadow-sm"></div>
                                    </label>
                                </td>

                                <!-- FIXED: Action Column with flex-nowrap and min-width to prevent squishing -->
                                <td class="table-cell sticky-actions text-center border-l border-gray-100">
                                    <div class="flex items-center justify-center gap-2.5 flex-nowrap" style="min-width: max-content;">
                                        <a href="view-surveyor-details.php?id=<?php echo htmlspecialchars($surveyor['id']); ?>"
                                            class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-blue-50 hover:bg-blue-100 hover:shadow-sm transition"
                                            title="View Surveyor">
                                            <i class="fa fa-eye text-blue-600"></i>
                                        </a>
                                        <button class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-purple-50 hover:bg-purple-100 hover:shadow-sm transition assign-ward-btn"
                                            title="Assign Wards"
                                            data-id="<?php echo htmlspecialchars($surveyor['id']); ?>">
                                            <i class="fa fa-map text-purple-600"></i>
                                        </button>
                                        <a href="reset-password.php?id=<?php echo htmlspecialchars($surveyor['id']); ?>"
                                            class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-yellow-50 hover:bg-yellow-100 hover:shadow-sm transition"
                                            title="Reset Password">
                                            <i class="fa fa-key text-yellow-600"></i>
                                        </a>
                                        <a href="update-surveyor.php?id=<?php echo htmlspecialchars($surveyor['id']); ?>"
                                            class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-gray-50 hover:bg-gray-200 hover:shadow-sm transition"
                                            title="Update Records">
                                            <i class="fa fa-pencil text-gray-600"></i>
                                        </a>
                                        <a href="delete-surveyor.php?id=<?php echo htmlspecialchars($surveyor['id']); ?>"
                                            onclick="return confirm('Are you sure you want to delete this surveyor? This action cannot be undone.');"
                                            class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-red-50 hover:bg-red-100 hover:shadow-sm transition"
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

            <!-- Pagination -->
            <div class="flex flex-col md:flex-row justify-between items-center mt-6 pt-4 border-t border-gray-100 gap-4">
                <span class="text-sm text-gray-500 font-semibold">
                    Showing Page <span class="text-gray-900"><?php echo $current_page; ?></span> of <span class="text-gray-900"><?php echo $total_pages; ?></span>
                </span>

                <div class="flex items-center gap-2">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo $current_page - 1; ?>" class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold transition text-sm">Previous</a>
                    <?php endif; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?>" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold transition text-sm shadow-sm">Next</a>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>
    </div>

</main>

<!-- Ward Assignment Modal -->
<div id="assignWardModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 hidden items-center justify-center z-[9999] backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 transform transition-all p-6">
        <div class="flex justify-between items-center mb-5 border-b border-gray-100 pb-3">
            <h3 class="text-xl font-extrabold text-gray-800">Assign Wards</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-red-500 transition">
                <i class="fa fa-times text-xl"></i>
            </button>
        </div>

        <form id="assignWardForm" method="POST">
            <input type="hidden" id="surveyorId" name="surveyor_id">
            <div class="mb-5">
                <label for="wards" class="block text-sm font-bold text-gray-700 mb-2">Select Target Wards</label>
                <select id="wards" name="wards[]" multiple class="w-full p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm" style="min-height: 180px;">
                    <!-- Options populated via JS -->
                </select>
                <p class="text-xs text-gray-500 mt-2 bg-gray-50 p-2 rounded-lg border border-gray-100">
                    <i class="fa fa-info-circle text-blue-500 mr-1"></i> Hold <kbd class="bg-gray-200 px-1 rounded">Ctrl</kbd> (Windows) or <kbd class="bg-gray-200 px-1 rounded">Cmd</kbd> (Mac) to select multiple wards.
                </p>
            </div>

            <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeModal()" class="px-5 py-2.5 rounded-xl text-gray-700 bg-gray-100 hover:bg-gray-200 font-bold transition">Cancel</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl text-white bg-blue-600 hover:bg-blue-700 font-bold shadow-md transition">Save Wards</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Status Toggle
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
                    if (!res.ok) throw new Error('Network response was not ok');
                    return res.json();
                })
                .then(data => {
                    if (data.status !== 'success') {
                        console.error('❌ Status change failed:', data.message);
                        this.checked = originalState;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.checked = originalState;
                });
        });
    });

    // Ward Assignment Modal Logic
    const assignWardModal = document.getElementById('assignWardModal');
    const wardsSelect = document.getElementById('wards');
    const surveyorIdInput = document.getElementById('surveyorId');

    function openModal(surveyorId) {
        surveyorIdInput.value = surveyorId;
        assignWardModal.classList.remove('hidden');
        assignWardModal.classList.add('flex');
        fetchWards(surveyorId);
    }

    function closeModal() {
        assignWardModal.classList.add('hidden');
        assignWardModal.classList.remove('flex');
        wardsSelect.innerHTML = '';
        document.getElementById('assignWardForm').reset();
    }

    document.querySelectorAll('.assign-ward-btn').forEach(button => {
        button.addEventListener('click', () => {
            openModal(button.getAttribute('data-id'));
        });
    });

    document.getElementById('assignWardForm').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('save-surveyor-wards.php', {
                method: 'POST',
                body: new URLSearchParams(new FormData(this))
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    closeModal();
                    window.location.reload();
                } else {
                    alert('Error assigning wards: ' + data.message);
                }
            })
            .catch(error => alert('An unexpected error occurred.'));
    });

    function fetchWards(surveyorId) {
        wardsSelect.innerHTML = '<option disabled>Loading wards...</option>';
        fetch('get-wards.php')
            .then(response => response.json())
            .then(allWards => {
                if (allWards.status !== 'success') throw new Error(allWards.message);

                fetch(`get-assigned-wards.php?id=${surveyorId}`)
                    .then(response => response.json())
                    .then(assignedWards => {
                        wardsSelect.innerHTML = '';
                        const assignedWardNumbers = new Set(assignedWards.map(w => w.ward_no));
                        allWards.wards.forEach(ward => {
                            const option = document.createElement('option');
                            option.value = ward.ward_no;
                            option.textContent = `Ward No. ${ward.ward_no}`;
                            if (assignedWardNumbers.has(ward.ward_no)) option.selected = true;
                            wardsSelect.appendChild(option);
                        });
                    });
            })
            .catch(error => {
                console.error(error);
                wardsSelect.innerHTML = '<option disabled>Failed to load wards.</option>';
            });
    }
</script>

<?php include "include/footer.php" ?>