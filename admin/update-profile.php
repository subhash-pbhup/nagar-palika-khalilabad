<?php
session_start();

// 1. Session Check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Database connection details
include 'db.php'; // This file should provide the database connection ($conn)

// CRITICAL CHECK: Ensure database connection is successful
if (!isset($conn) || $conn->connect_error) {
    die("Fatal Error: Database Connection failed. Please check your 'db.php' file.");
}

// Configuration for file uploads
// IMPORTANT: Ensure this directory exists and is writable by the web server
$upload_dir = 'admin-uploads/';
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
$max_file_size = 5 * 1024 * 1024; // 5 MB

// Get current user details from session
$user_id = $_SESSION['user_id'];
$user_username = $_SESSION['username'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'admin';
$profile_data = null;
$message = '';
$error = '';

// Determine which table to use based on the logged-in user's role
// NOTE: Assuming 'surveyors' and 'users' tables exist with the same structure
$table_name = ($user_role === 'surveyor') ? 'surveyors' : 'users';

// --- Function to fetch profile data (reusable after update) ---
function fetchProfileData($conn, $table_name, $user_id, &$error)
{
    $stmt_fetch = $conn->prepare("SELECT username, email, name, profile_pic, role, status FROM {$table_name} WHERE id = ? LIMIT 1");
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("i", $user_id);
        if (!$stmt_fetch->execute()) {
            $error = "Database Fetch Error: " . $stmt_fetch->error;
            return null;
        }
        $result = $stmt_fetch->get_result();
        $data = $result->fetch_assoc();
        $stmt_fetch->close();

        // Update session and return data
        if ($data) {
            $_SESSION['role'] = $data['role'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['name'] = $data['name'];
        }
        return $data;
    } else {
        $error = "SQL Prepare Error (Fetch): " . $conn->error;
        return null;
    }
}

// --- 2. Fetch Initial Profile Data ---
$profile_data = fetchProfileData($conn, $table_name, $user_id, $error);

if (!$profile_data) {
    $error = $error ?: "Profile data could not be fetched from table '{$table_name}'. Please ensure your user ID is valid and tables/columns exist.";
}

// --- 3. Handle Form Submission (Update Logic) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && $profile_data && !$error) {
    // Sanitize and get editable input (name)
    $new_name = trim($_POST['name'] ?? '');
    $update_fields = [];
    $update_types = '';
    $update_params = [];

    // --- Input Validation: Name ---
    if (empty($new_name)) {
        $error = "Name is required.";
    } else {
        // Add name to update fields
        $update_fields[] = "name = ?";
        $update_types .= 's';
        $update_params[] = $new_name;

        // --- File Upload Handling ---
        $new_profile_pic_filename = $profile_data['profile_pic']; // Default to current filename

        if (isset($_FILES['profile_pic_file']) && $_FILES['profile_pic_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_pic_file'];
            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            $file_size = $file['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Validate file size and extension
            if ($file_size > $max_file_size) {
                $error = "Error: File size must be less than 5MB.";
            } elseif (!in_array($file_ext, $allowed_extensions)) {
                $error = "Error: Only JPG, JPEG, PNG, and GIF files are allowed.";
            } else {
                // Generate unique filename to prevent overwriting and path traversal
                $unique_filename = uniqid('profile_', true) . '.' . $file_ext;
                $target_file = $upload_dir . $unique_filename;

                // Attempt to move the uploaded file
                if (move_uploaded_file($file_tmp, $target_file)) {
                    // Success: Update filename
                    $new_profile_pic_filename = $unique_filename;
                    $update_fields[] = "profile_pic = ?";
                    $update_types .= 's';
                    $update_params[] = $new_profile_pic_filename;

                    // OPTIONAL: Delete the old profile picture file if it's not the default
                    // if (!empty($profile_data['profile_pic']) && file_exists($upload_dir . $profile_data['profile_pic'])) {
                    //     unlink($upload_dir . $profile_data['profile_pic']);
                    // }

                } else {
                    $error = "Error: There was an issue uploading your file. Check directory permissions ('$upload_dir').";
                }
            }
        }

        // --- Execute UPDATE Query ---
        if (empty($error)) {
            // Add user_id to the parameters for the WHERE clause
            $update_types .= 'i';
            $update_params[] = $user_id;

            // Construct the SQL query
            $sql_update = "UPDATE {$table_name} SET " . implode(', ', $update_fields) . " WHERE id = ?";

            $stmt_update = $conn->prepare($sql_update);

            if ($stmt_update) {
                // Bind parameters dynamically
                $stmt_update->bind_param($update_types, ...$update_params);

                if ($stmt_update->execute()) {
                    $message = "Success! Your profile details have been updated.";

                    // Re-fetch data to refresh form and session with the latest data
                    $profile_data = fetchProfileData($conn, $table_name, $user_id, $error);
                } else {
                    $error = "Error updating profile in '{$table_name}' table: " . $stmt_update->error;
                }
                $stmt_update->close();
            } else {
                $error = "SQL Prepare Error (Update): " . $conn->error;
            }
        }
    }
}

// Close connection
if (isset($conn)) {
    $conn->close();
}

// Determine dashboard link for 'Back' button
$dashboard_link = ($user_role === 'surveyor') ? 'surveyor-dashboard.php' : 'dashboard.php';


// --- Image Path Configuration ---
$profile_pic_filename = $profile_data['profile_pic'] ?? '';
// If profile_pic field is empty, use 'placeholder.png', otherwise use 'admin-uploads/' path
// NOTE: I've removed the redundant 'admin-uploads/' prefix in the image_src construction below
$image_src = empty($profile_pic_filename) ? 'placeholder.png' : $upload_dir . $profile_pic_filename;

// Fallback text for placeholder
$avatar_fallback_text = htmlspecialchars(substr($profile_data['name'] ?? 'U', 0, 1));


// --- Status Color Configuration (NEW) ---
$status_value = htmlspecialchars(ucfirst($profile_data['status'] ?? 'N/A'));
$status_color_class = '';

if (strtolower($profile_data['status'] ?? '') === 'active') {
    // Green for Active
    $status_color_class = 'text-green-700 font-semibold border-green-400 bg-green-50';
} elseif (strtolower($profile_data['status'] ?? '') === 'inactive') {
    // Red for Inactive
    $status_color_class = 'text-red-700 font-semibold border-red-400 bg-red-50';
} else {
    // Default color for N/A or other statuses
    $status_color_class = 'text-gray-600';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile | <?php echo htmlspecialchars(ucfirst($user_role)); ?></title>
    <link href="favicon.png" rel="icon" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .profile-card {
            box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.1), 0 0 8px rgba(0, 0, 0, 0.05);
        }

        .input-field {
            transition: all 0.2s;
        }

        .input-field:focus {
            border-color: #059669;
            /* emerald-600 */
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.2);
        }

        .read-only-field {
            /* Keep background but allow dynamic text/border/bg color classes to override */
            background-color: #f3f4f6;
            /* Gray-100 */
            cursor: not-allowed;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Update Profile Details</h1>
            <p class="text-sm text-gray-500">
                Manage your personal information for your **<?php echo htmlspecialchars(ucfirst($user_role)); ?>** account.
            </p>
        </div>

        <div class="bg-white p-8 rounded-xl profile-card border-t-4 border-emerald-600">

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded-lg relative mb-6 text-sm font-medium" role="alert">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded-lg relative mb-6 text-sm font-medium" role="alert">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span class="block sm:inline"><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="update-profile.php" enctype="multipart/form-data" class="space-y-5">

                <div class="flex flex-col items-center justify-center mb-8">
                    <div class="relative w-32 h-32 mb-4">
                        <img
                            src="<?php echo htmlspecialchars($image_src); ?>"
                            alt="Profile Picture"
                            class="w-full h-full object-cover rounded-full border-4 border-emerald-500 shadow-lg"
                            onerror="this.onerror=null;this.src='https://placehold.co/128x128/9CA3AF/FFFFFF?text=<?php echo $avatar_fallback_text; ?>';">
                        <button type="button" onclick="document.getElementById('profile_pic_edit_section').classList.toggle('hidden');" class="absolute bottom-0 right-0 p-2 bg-emerald-600 rounded-full text-white shadow-xl hover:bg-emerald-700 transition duration-150 transform hover:scale-110" title="Change Profile Picture">
                            <i class="fas fa-camera text-sm" style="border-radius: 40px;width: 24px;"></i>
                        </button>
                    </div>
                    <p class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($profile_data['name'] ?? 'N/A'); ?></p>
                    <p class="text-sm text-gray-500">Role: <?php echo htmlspecialchars(ucfirst($profile_data['role'] ?? 'N/A')); ?></p>
                </div>

                <div id="profile_pic_edit_section" class="hidden mb-5 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <label for="profile_pic_file" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-upload mr-2"></i> Upload New Profile Picture (Max 5MB, JPG/PNG/GIF)
                    </label>
                    <input type="file" name="profile_pic_file" id="profile_pic_file" class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg bg-white file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    <p class="text-xs text-gray-500 mt-1">Leave blank if you don't want to change the image.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-user-circle mr-2"></i> Username
                        </label>
                        <input type="text" id="username" value="<?php echo htmlspecialchars($profile_data['username'] ?? 'N/A'); ?>" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg read-only-field" disabled>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-envelope mr-2"></i> Email
                        </label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($profile_data['email'] ?? 'N/A'); ?>" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg read-only-field" disabled>
                    </div>

                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-user mr-2"></i> Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($profile_data['name'] ?? ''); ?>" required class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg" autocomplete="name">
                    </div>

                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-shield-alt mr-2"></i> Role
                        </label>
                        <input type="text" id="role" value="<?php echo htmlspecialchars(ucfirst($profile_data['role'] ?? 'N/A')); ?>" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg read-only-field" disabled>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-info-circle mr-2"></i> Status
                        </label>
                        <input type="text" id="status" value="<?php echo $status_value; ?>" readonly class="w-full px-4 py-2 border rounded-lg read-only-field <?php echo $status_color_class; ?>" disabled>
                    </div>

                </div>

                <button type="submit" class="w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-lg shadow-md text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-offset-2 focus:ring-emerald-500/50 transition duration-200 mt-6">
                    <i class="fas fa-save mr-2"></i> SAVE PROFILE CHANGES
                </button>
            </form>

            <div class="mt-6 text-center border-t pt-4">
                <a href="<?php echo htmlspecialchars($dashboard_link); ?>" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium transition duration-150">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>

</html>