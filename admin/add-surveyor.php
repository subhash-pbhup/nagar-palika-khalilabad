<?php
session_start();
// ✅ Admin check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: index.php");
  exit;
}

require_once "db.php";

$message = "";
$message_type = "";

/**
 * Generate next unique surveyor username
 * Format: KLB-SUR-001, KLB-SUR-002 ...
 */
function generate_next_username($conn)
{
  $stmt = $conn->query("SELECT MAX(id) AS last_id FROM surveyors");
  $row = $stmt->fetch_assoc();
  $last_id = $row['last_id'] ?? 0;
  $next_id = $last_id + 1;
  return 'KLB-SUR-' . str_pad($next_id, 3, '0', STR_PAD_LEFT);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $name   = trim($_POST['name']);
  $email  = trim($_POST['email']);
  $mobile = trim($_POST['mobile']);

  // ✅ Mobile validation
  if (!preg_match('/^[0-9]{10}$/', $mobile)) {
    $message = "❌ Mobile number must be exactly 10 digits.";
    $message_type = "danger";
  } else {
    // ✅ Duplicate mobile check
    $stmt_check_mobile = $conn->prepare("SELECT id FROM surveyors WHERE mobile = ? LIMIT 1");
    $stmt_check_mobile->bind_param("s", $mobile);
    $stmt_check_mobile->execute();
    $result_check_mobile = $stmt_check_mobile->get_result();

    // ✅ Duplicate email check
    $stmt_check_email = $conn->prepare("SELECT id FROM surveyors WHERE email = ? LIMIT 1");
    $stmt_check_email->bind_param("s", $email);
    $stmt_check_email->execute();
    $result_check_email = $stmt_check_email->get_result();

    if ($result_check_mobile->num_rows > 0) {
      $message = "❌ Mobile number already exists. Please use a different number.";
      $message_type = "danger";
    } elseif ($result_check_email->num_rows > 0) {
      $message = "❌ Email already exists. Please use a different email.";
      $message_type = "danger";
    } else {
      // ✅ All validations passed
      $new_username = generate_next_username($conn);
      $default_password = 'surveyor@123';
      $hashed_password  = password_hash($default_password, PASSWORD_DEFAULT);
      $created_by       = $_SESSION['user_id'];

      mysqli_begin_transaction($conn);
      try {
        // Insert new surveyor
        $stmt_surveyor = $conn->prepare("
                    INSERT INTO surveyors (username, name, email, mobile, password, role, created_by)
                    VALUES (?, ?, ?, ?, ?, 'surveyor', ?)
                ");
        $stmt_surveyor->bind_param("sssssi", $new_username, $name, $email, $mobile, $hashed_password, $created_by);
        $stmt_surveyor->execute();
        $surveyor_id = $conn->insert_id;

        if ($surveyor_id) {
          $profile_pic_name = 'profile.png';
          $upload_dir = __DIR__ . "/surveyors_profile/{$new_username}/"; // ✅ username-based folder

          if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
          }

          if (!empty($_FILES['profile_pic']['name'])) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));

            if (in_array($file_ext, $allowed_ext)) {
              $target_file = $upload_dir . "profile." . $file_ext;
              if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
                $profile_pic_name = "profile." . $file_ext;
              } else {
                throw new Exception("Error uploading profile picture.");
              }
            } else {
              throw new Exception("Invalid file format. Only JPG, PNG, GIF, WEBP allowed.");
            }
          } else {
            // ✅ Copy default image if no file uploaded
            $default_pic_path = __DIR__ . "/../assets/img/man.png";  // adjust if needed
            $new_pic_path     = $upload_dir . "profile.png";
            if (file_exists($default_pic_path)) {
              copy($default_pic_path, $new_pic_path);
            }
          }


          // ✅ Update profile pic in DB
          $stmt_update_pic = $conn->prepare("UPDATE surveyors SET profile_pic = ? WHERE id = ?");
          $stmt_update_pic->bind_param("si", $profile_pic_name, $surveyor_id);
          $stmt_update_pic->execute();

          mysqli_commit($conn);
          $message = "✅ Surveyor <b>{$new_username}</b> added successfully!";
          $message_type = "success";

          echo "<script>alert('Surveyor added successfully with Username: {$new_username}'); window.location.href='add-surveyor.php';</script>";
          exit;
        } else {
          mysqli_rollback($conn);
          $message = "❌ Error adding surveyor. Please try again.";
          $message_type = "danger";
        }
      } catch (Exception $e) {
        mysqli_rollback($conn);
        $message = "❌ Error: " . $e->getMessage();
        $message_type = "danger";
      }
    }
  }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add New Surveyor - KLBria Property Tax</title>
  <link href="img/favicon.ico" rel="icon">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link href='css/mystyle.css' rel='stylesheet'>
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
      transition: all .3s ease;
      box-shadow: 0 8px 24px rgba(0, 0, 0, .1)
    }

    .req:after {
      content: " *";
      color: #ef4444;
      font-weight: bold
    }

    .file-name {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-weight: 500;
      color: #1f2937;
    }

    .file-name .icon {
      font-size: 1rem;
    }
  </style>
</head>

<body class="flex min-h-screen text-gray-800">
  <?php include 'sidemenu.php'; ?>

  <main class="flex-1 p-6 space-y-8 overflow-y-auto">
    <header class="flex items-center justify-between glass p-4 rounded-2xl">
      <h1 class="text-2xl font-bold text-gray-900">Add New Surveyor</h1>
      <div class="flex items-center space-x-4">
        <div class="relative">
          <input type="text" placeholder="Search..." class="pl-10 pr-4 py-2 rounded-full glass text-sm focus:outline-none">
          <span class="absolute left-3 top-2.5 text-gray-400"><i class="fa fa-search"></i></span>
        </div>
        <button class="relative w-10 h-10 rounded-full glass flex items-center justify-center hover-glass">
          <i class="fa fa-bell"></i>
          <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
        </button>
        <a href="new-assesment.php" class="relative w-10 h-10 rounded-full glass flex items-center justify-center hover-glass"><i class="fa fa-plus"></i></a>
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

    <?php if (!empty($message)): ?>
      <div class="p-4 mb-4 text-sm rounded-lg <?= $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
        <?= $message ?>
      </div>
    <?php endif; ?>

    <form id="addSurveyorForm" action="" method="post" enctype="multipart/form-data" class="space-y-8">
      <section class="glass p-6 rounded-2xl hover-glass">
        <h2 class="text-lg font-semibold mb-4">Surveyor Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium mb-2 req">Full Name</label>
            <input name="name" type="text" placeholder="Full Name" class="w-full p-3 rounded-lg glass" required>
          </div>
          <div>
            <label class="block text-sm font-medium mb-2 req">Email Address</label>
            <input name="email" type="email" placeholder="Email Address" class="w-full p-3 rounded-lg glass" required>
            <span id="email-error" class="text-sm"></span>
          </div>
          <div>
            <label class="block text-sm font-medium mb-2 req">Mobile Number</label>
            <input name="mobile" type="tel" placeholder="Mobile Number" class="w-full p-3 rounded-lg glass" required>
            <span id="mobile-error" class="text-sm"></span>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2 req">Auto-Generated Username</label>
            <input name="username" type="text" value="<?php echo generate_next_username($conn); ?>" readonly class="w-full p-3 rounded-lg glass" style="background: darkgray;color: #e8f6ff;">
          </div>
          <div>
            <label class="block text-sm font-medium mb-2 req">Default Password</label>
            <input name="password" type="text" value="surveyor@123" readonly class="w-full p-3 rounded-lg glass" style="background: darkgray;color: #e8f6ff;">
          </div>
        </div>
      </section>

      <section class="glass p-6 rounded-2xl hover-glass">
        <h2 class="text-lg font-semibold mb-4">Profile Picture</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium mb-2">Profile Picture (Optional)</label>
            <input type="file" name="profile_pic" class="w-full p-3 rounded-lg glass file-input">
            <p class="text-sm mt-2 text-gray-800 file-name">
              <span class="icon"><i class="fa fa-image"></i></span>
              <span class="name">No file selected</span>
            </p>
          </div>
        </div>
      </section>

      <div class="text-right">
        <button type="submit" class="px-6 py-3 rounded-full bg-blue-600 text-white font-medium hover:bg-blue-700">Save</button>
      </div>
    </form>

    <footer class="text-center text-gray-500 text-sm mt-6">
      © 2025 KLBria Nagar Parishad - All Rights Reserved.
    </footer>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // File input display
      document.querySelectorAll('.file-input').forEach(input => {
        input.addEventListener('change', function() {
          const fileName = this.files.length > 0 ? this.files[0].name : "No file selected";
          const fileDisplay = this.nextElementSibling.querySelector('.name');
          fileDisplay.textContent = fileName;
        });
      });

      // ✅ Duplicate check via AJAX
      function checkDuplicate(field, value) {
        if (value.trim() === "") return;
        fetch("check-duplicate.php?field=" + field + "&value=" + encodeURIComponent(value))
          .then(res => res.json())
          .then(data => {
            const span = document.getElementById(field + "-error");
            if (data.exists) {
              span.textContent = field.charAt(0).toUpperCase() + field.slice(1) + " already registered!";
              span.style.color = "red";
            } else {
              span.textContent = "";
            }
          });
      }

      document.querySelector("input[name='email']").addEventListener("blur", function() {
        checkDuplicate("email", this.value);
      });

      document.querySelector("input[name='mobile']").addEventListener("blur", function() {
        if (!/^[0-9]{10}$/.test(this.value)) {
          document.getElementById("mobile-error").textContent = "Mobile must be exactly 10 digits.";
        } else {
          document.getElementById("mobile-error").textContent = "";
          checkDuplicate("mobile", this.value);
        }
      });
    });
  </script>
  <script src="js/mystyle.js"></script>

</body>

</html>