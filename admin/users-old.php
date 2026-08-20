<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: index.php");
    exit;
}

require_once "db.php";

$message = "";
$message_type = "";

/* =========================================================
   UPLOAD DIRECTORY
========================================================= */

// $upload_dir = __DIR__ . "/admin-uploads/";
$upload_dir = __DIR__ . "/admin-uploads/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}


/* =========================================================
   DELETE USER
========================================================= */

if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    // Admin khud ko delete na kar sake
    if ($id == (int)$_SESSION['user_id']) {

        $message = "You cannot delete your own account.";
        $message_type = "danger";
    } else {

        // Profile image delete
        $stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && !empty($user['profile_pic'])) {

            $old_file = $upload_dir . $user['profile_pic'];

            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = "User deleted successfully.";
            $message_type = "success";
        } else {
            $message = "Unable to delete user.";
            $message_type = "danger";
        }
    }
}


/* =========================================================
   ADD / UPDATE USER
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action   = $_POST['action'] ?? '';
    $id       = (int)($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = trim($_POST['role'] ?? 'user');
    $status   = trim($_POST['status'] ?? 'active');


    /* ---------------------------------------------------------
       VALIDATION
    --------------------------------------------------------- */

    if ($username === '' || $name === '' || $email === '') {

        $message = "Username, Name and Email are required.";
        $message_type = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "danger";
    } else {

        /* -----------------------------------------------------
           DUPLICATE USERNAME
        ----------------------------------------------------- */

        if ($action === 'add') {

            $stmt = $conn->prepare("
                SELECT id 
                FROM users 
                WHERE username = ?
                LIMIT 1
            ");

            $stmt->bind_param("s", $username);
            $stmt->execute();

            if ($stmt->get_result()->num_rows > 0) {

                $message = "Username already exists.";
                $message_type = "danger";
            } else {

                /* ---------------------------------------------
                   DUPLICATE EMAIL
                --------------------------------------------- */

                $stmt = $conn->prepare("
                    SELECT id 
                    FROM users 
                    WHERE email = ?
                    LIMIT 1
                ");

                $stmt->bind_param("s", $email);
                $stmt->execute();

                if ($stmt->get_result()->num_rows > 0) {

                    $message = "Email already exists.";
                    $message_type = "danger";
                } elseif ($password === '') {

                    $message = "Password is required for new user.";
                    $message_type = "danger";
                } else {

                    /* -----------------------------------------
                       PASSWORD HASH
                    ----------------------------------------- */

                    $hashed_password = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    /* -----------------------------------------
                       INSERT USER
                    ----------------------------------------- */

                    $stmt = $conn->prepare("
                        INSERT INTO users
                        (
                            username,
                            name,
                            email,
                            password,
                            role,
                            status,
                            created_at
                        )
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");

                    $stmt->bind_param(
                        "ssssss",
                        $username,
                        $name,
                        $email,
                        $hashed_password,
                        $role,
                        $status
                    );

                    if ($stmt->execute()) {

                        $user_id = $conn->insert_id;

                        /* -------------------------------------
                           PROFILE PICTURE
                        ------------------------------------- */

                        if (
                            isset($_FILES['profile_pic']) &&
                            $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK
                        ) {

                            $allowed_ext = [
                                'jpg',
                                'jpeg',
                                'png',
                                'gif',
                                'webp'
                            ];

                            $extension = strtolower(
                                pathinfo(
                                    $_FILES['profile_pic']['name'],
                                    PATHINFO_EXTENSION
                                )
                            );

                            if (in_array($extension, $allowed_ext)) {

                                $file_name = "user_" . $user_id . "." . $extension;

                                $target = $upload_dir . $file_name;

                                if (
                                    move_uploaded_file(
                                        $_FILES['profile_pic']['tmp_name'],
                                        $target
                                    )
                                ) {

                                    $stmt_pic = $conn->prepare("
                                        UPDATE users
                                        SET profile_pic = ?
                                        WHERE id = ?
                                    ");

                                    $stmt_pic->bind_param(
                                        "si",
                                        $file_name,
                                        $user_id
                                    );

                                    $stmt_pic->execute();
                                }
                            }
                        }

                        $message = "User added successfully.";
                        $message_type = "success";
                    } else {

                        $message = "Error adding user.";
                        $message_type = "danger";
                    }
                }
            }


            /* =====================================================
           UPDATE USER
        ===================================================== */
        } elseif ($action === 'edit' && $id > 0) {

            /* -------------------------------------------------
               DUPLICATE USERNAME
            ------------------------------------------------- */

            $stmt = $conn->prepare("
                SELECT id
                FROM users
                WHERE username = ?
                AND id != ?
                LIMIT 1
            ");

            $stmt->bind_param(
                "si",
                $username,
                $id
            );

            $stmt->execute();

            if ($stmt->get_result()->num_rows > 0) {

                $message = "Username already exists.";
                $message_type = "danger";
            } else {

                /* ---------------------------------------------
                   DUPLICATE EMAIL
                --------------------------------------------- */

                $stmt = $conn->prepare("
                    SELECT id
                    FROM users
                    WHERE email = ?
                    AND id != ?
                    LIMIT 1
                ");

                $stmt->bind_param(
                    "si",
                    $email,
                    $id
                );

                $stmt->execute();

                if ($stmt->get_result()->num_rows > 0) {

                    $message = "Email already exists.";
                    $message_type = "danger";
                } else {

                    /* -----------------------------------------
                       PASSWORD CHANGE
                    ----------------------------------------- */

                    if ($password !== '') {

                        $hashed_password = password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                        $stmt = $conn->prepare("
                            UPDATE users
                            SET
                                username = ?,
                                name = ?,
                                email = ?,
                                password = ?,
                                role = ?,
                                status = ?
                            WHERE id = ?
                        ");

                        $stmt->bind_param(
                            "ssssssi",
                            $username,
                            $name,
                            $email,
                            $hashed_password,
                            $role,
                            $status,
                            $id
                        );
                    } else {

                        $stmt = $conn->prepare("
                            UPDATE users
                            SET
                                username = ?,
                                name = ?,
                                email = ?,
                                role = ?,
                                status = ?
                            WHERE id = ?
                        ");

                        $stmt->bind_param(
                            "sssssi",
                            $username,
                            $name,
                            $email,
                            $role,
                            $status,
                            $id
                        );
                    }


                    if ($stmt->execute()) {

                        /* -------------------------------------
                           NEW PROFILE PICTURE
                        ------------------------------------- */

                        if (
                            isset($_FILES['profile_pic']) &&
                            $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK
                        ) {

                            $allowed_ext = [
                                'jpg',
                                'jpeg',
                                'png',
                                'gif',
                                'webp'
                            ];

                            $extension = strtolower(
                                pathinfo(
                                    $_FILES['profile_pic']['name'],
                                    PATHINFO_EXTENSION
                                )
                            );

                            if (in_array($extension, $allowed_ext)) {

                                /* Old image */
                                $stmt_old = $conn->prepare("
                                    SELECT profile_pic
                                    FROM users
                                    WHERE id = ?
                                ");

                                $stmt_old->bind_param("i", $id);
                                $stmt_old->execute();

                                $old_user =
                                    $stmt_old->get_result()->fetch_assoc();

                                if (
                                    $old_user &&
                                    !empty($old_user['profile_pic'])
                                ) {

                                    $old_file =
                                        $upload_dir .
                                        $old_user['profile_pic'];

                                    if (file_exists($old_file)) {
                                        unlink($old_file);
                                    }
                                }


                                $file_name =
                                    "user_" .
                                    $id .
                                    "." .
                                    $extension;

                                $target =
                                    $upload_dir .
                                    $file_name;

                                if (
                                    move_uploaded_file(
                                        $_FILES['profile_pic']['tmp_name'],
                                        $target
                                    )
                                ) {

                                    $stmt_pic = $conn->prepare("
                                        UPDATE users
                                        SET profile_pic = ?
                                        WHERE id = ?
                                    ");

                                    $stmt_pic->bind_param(
                                        "si",
                                        $file_name,
                                        $id
                                    );

                                    $stmt_pic->execute();
                                }
                            }
                        }

                        $message = "User updated successfully.";
                        $message_type = "success";
                    } else {

                        $message = "Error updating user.";
                        $message_type = "danger";
                    }
                }
            }
        }
    }
}


/* =========================================================
   SEARCH
========================================================= */

$search = trim($_GET['search'] ?? '');

if ($search !== '') {

    $search_like = "%" . $search . "%";

    $stmt = $conn->prepare("
        SELECT
            id,
            username,
            name,
            email,
            profile_pic,
            role,
            status,
            created_at
        FROM users
        WHERE
            username LIKE ?
            OR name LIKE ?
            OR email LIKE ?
            OR role LIKE ?
        ORDER BY id DESC
    ");

    $stmt->bind_param(
        "ssss",
        $search_like,
        $search_like,
        $search_like,
        $search_like
    );

    $stmt->execute();

    $users = $stmt->get_result();
} else {

    $users = $conn->query("
        SELECT
            id,
            username,
            name,
            email,
            profile_pic,
            role,
            status,
            created_at
        FROM users
        ORDER BY id DESC
    ");
}


/* =========================================================
   USER COUNT
========================================================= */

$total_users = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
")->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Surveyor - Deoria Property Tax</title>
    <link href="favicon.png" rel="icon">
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

    <main class="flex-1 p-6">

        <!-- =====================================================
     HEADER
===================================================== -->

        <div class="glass rounded-2xl p-5 mb-6">

            <div class="flex flex-col md:flex-row
                md:items-center
                md:justify-between gap-4">

                <div>

                    <h1 class="text-2xl font-bold">
                        User Management
                    </h1>

                    <p class="text-gray-500 mt-1">
                        Manage system users and access.
                    </p>

                </div>


                <button
                    onclick="openAddModal()"
                    class="px-5 py-3
                   bg-blue-600
                   hover:bg-blue-700
                   text-white
                   rounded-lg">

                    <i class="fa fa-plus mr-2"></i>
                    Add User

                </button>

            </div>

        </div>


        <!-- =====================================================
     MESSAGE
===================================================== -->

        <?php if (!empty($message)): ?>

            <div class="mb-5 p-4 rounded-lg
    <?= $message_type === 'success'
                ? 'bg-green-100 text-green-700'
                : 'bg-red-100 text-red-700' ?>">

                <?= $message ?>

            </div>

        <?php endif; ?>


        <!-- =====================================================
     STAT
===================================================== -->

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">

            <div class="glass rounded-xl p-5">

                <p class="text-gray-500 text-sm">
                    Total Users
                </p>

                <h2 class="text-3xl font-bold mt-2">
                    <?= $total_users ?>
                </h2>

            </div>

        </div>


        <!-- =====================================================
     USER LIST
===================================================== -->

        <div class="glass rounded-2xl p-5">

            <div class="flex flex-col md:flex-row
                md:items-center
                md:justify-between
                gap-4 mb-5">

                <h2 class="text-xl font-semibold">
                    Users List
                </h2>


                <form method="get"
                    class="flex gap-2">

                    <input
                        type="text"
                        name="search"
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search user..."
                        class="px-4 py-2
                       border
                       rounded-lg
                       outline-none
                       focus:ring-2
                       focus:ring-blue-500">

                    <button
                        type="submit"
                        class="px-4 py-2
                       bg-gray-800
                       text-white
                       rounded-lg">

                        <i class="fa fa-search"></i>

                    </button>

                    <?php if ($search !== ''): ?>

                        <a href="users.php"
                            class="px-4 py-2
                      bg-gray-200
                      rounded-lg">

                            Reset

                        </a>

                    <?php endif; ?>

                </form>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full text-sm">

                    <thead>

                        <tr class="border-b">

                            <th class="text-left p-3">
                                #
                            </th>

                            <th class="text-left p-3">
                                User
                            </th>

                            <th class="text-left p-3">
                                Username
                            </th>

                            <th class="text-left p-3">
                                Email
                            </th>

                            <th class="text-left p-3">
                                Role
                            </th>

                            <th class="text-left p-3">
                                Status
                            </th>

                            <th class="text-left p-3">
                                Created
                            </th>

                            <th class="text-center p-3">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php if ($users && $users->num_rows > 0): ?>

                            <?php
                            $sr = 1;

                            while ($user = $users->fetch_assoc()):
                            ?>

                                <tr class="border-b hover:bg-white/50">

                                    <td class="p-3">
                                        <?= $sr++ ?>
                                    </td>


                                    <td class="p-3">

                                        <div class="flex items-center gap-3">

                                            <?php

                                            $profile =
                                                !empty($user['profile_pic'])
                                                ? "admin-uploads/" .
                                                $user['profile_pic']
                                                : "man.png";

                                            ?>

                                            <img
                                                src="<?= htmlspecialchars($profile) ?>"
                                                class="w-10 h-10
                                       rounded-full
                                       object-cover
                                       border">

                                            <div>

                                                <div class="font-semibold">
                                                    <?= htmlspecialchars($user['name']) ?>
                                                </div>

                                            </div>

                                        </div>

                                    </td>


                                    <td class="p-3">

                                        <?= htmlspecialchars(
                                            $user['username']
                                        ) ?>

                                    </td>


                                    <td class="p-3">

                                        <?= htmlspecialchars(
                                            $user['email']
                                        ) ?>

                                    </td>


                                    <td class="p-3">

                                        <span class="px-3 py-1
                                     rounded-full
                                     text-xs
                                     bg-blue-100
                                     text-blue-700">

                                            <?= htmlspecialchars(
                                                ucfirst($user['role'])
                                            ) ?>

                                        </span>

                                    </td>


                                    <td class="p-3">

                                        <?php if ($user['status'] === 'active'): ?>

                                            <span class="px-3 py-1
                                         rounded-full
                                         text-xs
                                         bg-green-100
                                         text-green-700">

                                                Active

                                            </span>

                                        <?php else: ?>

                                            <span class="px-3 py-1
                                         rounded-full
                                         text-xs
                                         bg-red-100
                                         text-red-700">

                                                Inactive

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td class="p-3">

                                        <?= date(
                                            'd M Y',
                                            strtotime($user['created_at'])
                                        ) ?>

                                    </td>


                                    <td class="p-3">

                                        <div class="flex justify-center gap-2">

                                            <button
                                                onclick='editUser(
                                    <?= json_encode($user) ?>
                                )'
                                                class="w-9 h-9
                                       rounded-lg
                                       bg-blue-100
                                       text-blue-600
                                       hover:bg-blue-200">

                                                <i class="fa fa-pencil"></i>

                                            </button>


                                            <?php if (
                                                $user['id'] !=
                                                $_SESSION['user_id']
                                            ): ?>

                                                <a
                                                    href="users.php?delete=<?= $user['id'] ?>"
                                                    onclick="return confirm(
                                    'Are you sure you want to delete this user?'
                                )"
                                                    class="w-9 h-9
                                       rounded-lg
                                       bg-red-100
                                       text-red-600
                                       hover:bg-red-200
                                       flex items-center
                                       justify-center">

                                                    <i class="fa fa-trash"></i>

                                                </a>

                                            <?php endif; ?>

                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="8"
                                    class="text-center
                               py-10
                               text-gray-500">

                                    No users found.

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>


    </main>


    <!-- =====================================================
     ADD / EDIT MODAL
===================================================== -->

    <div
        id="userModal"
        class="hidden fixed inset-0
           modal-bg
           z-50
           items-center
           justify-center
           p-4">

        <div
            class="bg-white
               rounded-2xl
               shadow-2xl
               w-full
               max-w-2xl
               max-h-[90vh]
               overflow-y-auto">


            <!-- MODAL HEADER -->

            <div class="flex items-center
                    justify-between
                    p-5
                    border-b">

                <h2
                    id="modalTitle"
                    class="text-xl font-bold">

                    Add User

                </h2>


                <button
                    type="button"
                    onclick="closeModal()"
                    class="text-gray-500
                       hover:text-red-500
                       text-2xl">

                    &times;

                </button>

            </div>


            <!-- FORM -->

            <form
                method="post"
                enctype="multipart/form-data"
                class="p-6">

                <input
                    type="hidden"
                    name="action"
                    id="formAction"
                    value="add">

                <input
                    type="hidden"
                    name="id"
                    id="userId"
                    value="0">


                <div class="grid grid-cols-1
                        md:grid-cols-2
                        gap-5">


                    <!-- NAME -->

                    <div>

                        <label class="block
                                  text-sm
                                  font-medium
                                  mb-2">

                            Full Name
                            <span class="text-red-500">*</span>

                        </label>

                        <input
                            type="text"
                            name="name"
                            id="userName"
                            required
                            class="w-full
                               border
                               rounded-lg
                               px-4
                               py-3
                               focus:ring-2
                               focus:ring-blue-500
                               outline-none">

                    </div>


                    <!-- USERNAME -->

                    <div>

                        <label class="block
                                  text-sm
                                  font-medium
                                  mb-2">

                            Username
                            <span class="text-red-500">*</span>

                        </label>

                        <input
                            type="text"
                            name="username"
                            id="userUsername"
                            required
                            class="w-full
                               border
                               rounded-lg
                               px-4
                               py-3
                               focus:ring-2
                               focus:ring-blue-500
                               outline-none">

                    </div>


                    <!-- EMAIL -->

                    <div>

                        <label class="block
                                  text-sm
                                  font-medium
                                  mb-2">

                            Email
                            <span class="text-red-500">*</span>

                        </label>

                        <input
                            type="email"
                            name="email"
                            id="userEmail"
                            required
                            class="w-full
                               border
                               rounded-lg
                               px-4
                               py-3
                               focus:ring-2
                               focus:ring-blue-500
                               outline-none">

                    </div>


                    <!-- PASSWORD -->

                    <div>

                        <label class="block
                                  text-sm
                                  font-medium
                                  mb-2">

                            Password

                        </label>

                        <input
                            type="password"
                            name="password"
                            id="userPassword"
                            class="w-full
                               border
                               rounded-lg
                               px-4
                               py-3
                               focus:ring-2
                               focus:ring-blue-500
                               outline-none">

                        <p class="text-xs
                              text-gray-500
                              mt-1">

                            Leave blank while editing
                            to keep old password.

                        </p>

                    </div>


                    <!-- ROLE -->

                    <div>

                        <label class="block
                                  text-sm
                                  font-medium
                                  mb-2">

                            Role

                        </label>

                        <select
                            name="role"
                            id="userRole"
                            class="w-full
                               border
                               rounded-lg
                               px-4
                               py-3
                               outline-none">

                            <option value="admin">
                                Admin
                            </option>

                            <option value="user"
                                selected>
                                User
                            </option>

                            <option value="staff">
                                Staff
                            </option> 
                            <option value="surveyor">Surveyor</option>

                        </select>

                    </div>


                    <!-- STATUS -->

                    <div>

                        <label class="block
                                  text-sm
                                  font-medium
                                  mb-2">

                            Status

                        </label>

                        <select
                            name="status"
                            id="userStatus"
                            class="w-full
                               border
                               rounded-lg
                               px-4
                               py-3
                               outline-none">

                            <option value="active">
                                Active
                            </option>

                            <option value="inactive">
                                Inactive
                            </option>

                        </select>

                    </div>


                    <!-- PROFILE -->

                    <div class="md:col-span-2">

                        <label class="block
                                  text-sm
                                  font-medium
                                  mb-2">

                            Profile Picture

                        </label>

                        <input
                            type="file"
                            name="profile_pic"
                            accept=".jpg,.jpeg,.png,.gif,.webp"
                            class="w-full
                               border
                               rounded-lg
                               px-4
                               py-3">

                        <div
                            id="currentProfile"
                            class="hidden mt-3">

                            <img
                                id="currentProfileImg"
                                src=""
                                class="w-16
                                   h-16
                                   rounded-full
                                   object-cover
                                   border">

                        </div>

                    </div>

                </div>


                <!-- BUTTONS -->

                <div class="flex
                        justify-end
                        gap-3
                        mt-6
                        pt-5
                        border-t">

                    <button
                        type="button"
                        onclick="closeModal()"
                        class="px-5
                           py-2.5
                           rounded-lg
                           bg-gray-200
                           hover:bg-gray-300">

                        Cancel

                    </button>


                    <button
                        type="submit"
                        id="saveButton"
                        class="px-5
                           py-2.5
                           rounded-lg
                           bg-blue-600
                           text-white
                           hover:bg-blue-700">

                        <i class="fa fa-save mr-2"></i>

                        Save User

                    </button>

                </div>

            </form>

        </div>

    </div>


    <script>
        /* =========================================================
   OPEN ADD MODAL
========================================================= */

        function openAddModal() {

            document.getElementById('modalTitle').innerText =
                'Add User';

            document.getElementById('formAction').value =
                'add';

            document.getElementById('userId').value =
                '0';

            document.getElementById('userName').value =
                '';

            document.getElementById('userUsername').value =
                '';

            document.getElementById('userEmail').value =
                '';

            document.getElementById('userPassword').value =
                '';

            document.getElementById('userRole').value =
                'user';

            document.getElementById('userStatus').value =
                'active';

            document.getElementById('currentProfile').classList.add(
                'hidden'
            );

            document.getElementById('saveButton').innerHTML =
                '<i class="fa fa-save mr-2"></i> Save User';

            showModal();
        }


        /* =========================================================
           OPEN EDIT MODAL
        ========================================================= */

        function editUser(user) {

            document.getElementById('modalTitle').innerText =
                'Edit User';

            document.getElementById('formAction').value =
                'edit';

            document.getElementById('userId').value =
                user.id;

            document.getElementById('userName').value =
                user.name || '';

            document.getElementById('userUsername').value =
                user.username || '';

            document.getElementById('userEmail').value =
                user.email || '';

            document.getElementById('userPassword').value =
                '';

            document.getElementById('userRole').value =
                user.role || 'user';

            document.getElementById('userStatus').value =
                user.status || 'active';


            if (user.profile_pic) {

                document.getElementById(
                        'currentProfileImg'
                    ).src =
                    'admin-uploads/' +
                    user.profile_pic;

                document.getElementById(
                    'currentProfile'
                ).classList.remove('hidden');

            } else {

                document.getElementById(
                    'currentProfile'
                ).classList.add('hidden');
            }


            document.getElementById('saveButton').innerHTML =
                '<i class="fa fa-save mr-2"></i> Update User';

            showModal();
        }


        /* =========================================================
           SHOW MODAL
        ========================================================= */

        function showModal() {

            const modal =
                document.getElementById('userModal');

            modal.classList.remove('hidden');

            modal.classList.add('flex');
        }


        /* =========================================================
           CLOSE MODAL
        ========================================================= */

        function closeModal() {

            const modal =
                document.getElementById('userModal');

            modal.classList.add('hidden');

            modal.classList.remove('flex');
        }


        /* =========================================================
           CLOSE ON BACKGROUND CLICK
        ========================================================= */

        document.getElementById('userModal')
            .addEventListener('click', function(e) {

                if (e.target === this) {
                    closeModal();
                }

            });


        /* =========================================================
           ESC KEY
        ========================================================= */

        document.addEventListener('keydown', function(e) {

            if (e.key === 'Escape') {
                closeModal();
            }

        });
    </script>

</body>

</html>