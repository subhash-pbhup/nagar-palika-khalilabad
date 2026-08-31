<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: index.php");
    exit;
}

// Database connection details
include 'db.php';

// Get the surveyor ID from the URL
$surveyor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$surveyor = null;
$assigned_wards = [];

if ($surveyor_id > 0) {
    // Fetch main surveyor details from the 'surveyors' table
    $stmt = $conn->prepare("SELECT * FROM surveyors WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $surveyor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $surveyor = $result->fetch_assoc();
        $stmt->close();
    }

    // Fetch assigned ward numbers from 'surveyor_wards' table
   $stmt_wards = $conn->prepare("
    SELECT w.ward_no 
    FROM surveyor_wards sw
    INNER JOIN wards w ON sw.ward_id = w.ward_id
    WHERE sw.surveyor_id = ?
");

    if ($stmt_wards) {
        $stmt_wards->bind_param("i", $surveyor_id);
        $stmt_wards->execute();
        $wards_result = $stmt_wards->get_result();
        while ($row = $wards_result->fetch_assoc()) {
            $assigned_wards[] = $row['ward_no'];
        }
        $stmt_wards->close();
    }
}

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <title>Surveyor Details - Deoria Property Tax</title>
    <link href="favicon.png" rel="icon" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #e0f2fe, #f8fafc); }
        .glass { background: rgba(255, 255, 255, .25); border-radius: 1rem; border: 1px solid rgb(210 207 207 / 30%); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .req:after { content: " *"; color: #ef4444; font-weight: 600; }
        .read-only-input { background-color: #f1f5f9; cursor: not-allowed; }
    </style>
</head>
<body class="flex min-h-screen text-gray-800">
    <?php include 'sidemenu.php' ?>

    <main class="flex-1 p-6 space-y-8 overflow-y-auto">
        <header class="flex items-center justify-between glass p-4 rounded-2xl">
            <h1 class="text-2xl font-bold text-gray-900">Surveyor Details</h1>
            <div class="flex items-center space-x-4">
                <a href="view-surveyor.php" class="px-4 py-2 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back to List</a>
            </div>
        </header>

        <?php if ($surveyor): ?>
            <section class="space-y-8">
                <div class="glass p-6 rounded-2xl">
                    <h2 class="text-lg font-semibold mb-4">Surveyor Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium mb-2">Profile Picture</label>
                            <div class="w-24 h-24 rounded-full overflow-hidden border border-gray-300">
                                <img src="surveyors_profile/<?php echo htmlspecialchars($surveyor['username']); ?>/<?php echo htmlspecialchars($surveyor['profile_pic'] ?? ''); ?>" 
                                     onerror="this.onerror=null;this.src='man.png';" 
                                     class="w-full h-full object-cover" 
                                     alt="Profile Picture">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Full Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($surveyor['name'] ?? ''); ?>" class="w-full p-3 rounded-lg glass read-only-input" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Username</label>
                            <input type="text" value="<?php echo htmlspecialchars($surveyor['username'] ?? ''); ?>" class="w-full p-3 rounded-lg glass read-only-input" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Email</label>
                            <input type="text" value="<?php echo htmlspecialchars($surveyor['email'] ?? ''); ?>" class="w-full p-3 rounded-lg glass read-only-input" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Mobile</label>
                            <input type="text" value="<?php echo htmlspecialchars($surveyor['mobile'] ?? ''); ?>" class="w-full p-3 rounded-lg glass read-only-input" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Role</label>
                            <input type="text" value="<?php echo htmlspecialchars($surveyor['role'] ?? ''); ?>" class="w-full p-3 rounded-lg glass read-only-input" readonly>
                        </div>
                       <div>
							<label class="block text-sm font-medium mb-2">Status</label>
							<?php if (!empty($surveyor['status']) && $surveyor['status'] === 'active'): ?>
								<span class="inline-flex items-center px-3 py-2 rounded-lg bg-green-100 text-green-700 font-semibold">
									<i class="fa fa-check-circle mr-2"></i> Active
								</span>
							<?php elseif (!empty($surveyor['status']) && $surveyor['status'] === 'inactive'): ?>
								<span class="inline-flex items-center px-3 py-2 rounded-lg bg-red-100 text-red-700 font-semibold">
									<i class="fa fa-times-circle mr-2"></i> Inactive
								</span>
							<?php else: ?>
								<span class="inline-flex items-center px-3 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold">
									<i class="fa fa-question-circle mr-2"></i> Unknown
								</span>
							<?php endif; ?>
						</div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Created At</label>
                            <input type="text" value="<?php echo htmlspecialchars(date('d M, Y', strtotime($surveyor['created_at'] ?? ''))); ?>" class="w-full p-3 rounded-lg glass read-only-input" readonly>
                        </div>
                    </div>
                </div>

               <div class="glass p-6 rounded-2xl">
					<h2 class="text-lg font-semibold mb-4">Assigned Wards</h2>
					<div class="flex flex-wrap gap-2">
						<?php if (!empty($assigned_wards)): ?>
							<?php foreach ($assigned_wards as $ward): ?>
    <button class="flex items-center px-3 py-2 rounded-lg bg-blue-100 text-blue-700 font-semibold shadow-sm hover:bg-blue-200 transition">
        <i class="fa fa-map-marker mr-2"></i> Ward <?php echo htmlspecialchars($ward); ?>
    </button>
<?php endforeach; ?>

						<?php else: ?>
							<p class="text-center text-gray-500 w-full">No wards have been assigned to this surveyor.</p>
						<?php endif; ?>
					</div>
				</div>

            </section>
        <?php else: ?>
            <div class="text-center p-10 bg-white/50 rounded-lg shadow-md">
                <p class="text-lg font-semibold text-red-600">Surveyor not found.</p>
                <p class="text-gray-600 mt-2">Please go back to the list and select a valid surveyor.</p>
                <a href="view-surveyor.php" class="mt-4 inline-block px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Go Back</a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>