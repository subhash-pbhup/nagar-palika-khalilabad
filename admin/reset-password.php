<?php
// Start the session
session_start();

// 1. Session Check: सुनिश्चित करें कि कोई लॉग इन है
if (!isset($_SESSION['user_id'])) {
    // अगर लॉग इन नहीं है, तो वापस लॉगिन पेज पर भेजें
    header("Location: index.php");
    exit;
}

// Database connection details
include 'db.php';

// Get current user details from session
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user'; // Default to 'user' if role is not set
$message = '';
$error = '';

// Determine which table to use
$table_name = ($user_role === 'surveyor') ? 'surveyors' : 'users';
$id_column = 'id'; // Assuming both tables use 'id' as the primary key

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and get inputs
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // --- 2. Input Validation ---
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all the fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New Password and Confirm Password do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "New Password must be at least 6 characters long.";
    } else {
        // --- 3. Old Password Verification ---
        
        // Fetch current password hash from the determined table
        $stmt = $conn->prepare("SELECT password FROM {$table_name} WHERE {$id_column} = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user_data = $result->fetch_assoc();
                $stored_hash = $user_data['password'];

                // Verify the old password
                if (password_verify($old_password, $stored_hash)) {
                    // --- 4. Password Update ---
                    
                    // Hash the new password securely
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

                    // Update the password in the database
                    $update_stmt = $conn->prepare("UPDATE {$table_name} SET password = ? WHERE {$id_column} = ?");
                    if ($update_stmt) {
                        $update_stmt->bind_param("si", $new_password_hash, $user_id);
                        if ($update_stmt->execute()) {
                            $message = "✅ Success! Your password has been updated. Please use the new password next time you log in.";
                        } else {
                            $error = "Error updating password: " . $update_stmt->error;
                        }
                        $update_stmt->close();
                    } else {
                        $error = "Database error during update preparation.";
                    }
                } else {
                    $error = "Incorrect Old Password. Please try again.";
                }
            } else {
                $error = "User not found in the system.";
            }
            $stmt->close();
        } else {
            $error = "Database error during fetch preparation.";
        }
    }
}

// Close connection if it was opened
if (isset($conn)) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
	
  <link href="favicon.png" rel="icon" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        .form-card {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 0 10px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Change Your Password</h1>
            <p class="text-gray-500">Security for user: **<?php echo htmlspecialchars($_SESSION['username'] ?? 'N/A'); ?>** (Role: <?php echo htmlspecialchars(ucfirst($user_role)); ?>)</p>
        </div>

        <div class="bg-white p-8 rounded-xl form-card border-t-4 border-emerald-600">
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <i class="fas fa-times-circle mr-2"></i>
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span class="block sm:inline"><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="reset-password.php" class="space-y-6">
                
                <div>
                    <label for="old_password" class="block text-sm font-medium text-gray-700 mb-1">Old Password</label>
                    <input type="password" name="old_password" id="old_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 transition duration-150" autocomplete="current-password">
                </div>

                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password (Minimum. 6 characters)</label>
                    <input type="password" name="new_password" id="new_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 transition duration-150" autocomplete="new-password">
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 transition duration-150" autocomplete="new-password">
                </div>

                <button type="submit" class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition duration-200">
                    <i class="fas fa-key mr-2"></i> Reset Password
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <a href="<?php echo ($user_role === 'surveyor' ? 'surveyor-dashboard.php' : 'dashboard.php'); ?>" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>