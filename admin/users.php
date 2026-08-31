<?php

session_start();


// =========================================================
// ADMIN ACCESS ONLY
// =========================================================

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'ADMIN'
) {
    header("Location: index.php");
    exit;
}


require_once "./db.php";


// =========================================================
// VARIABLES
// =========================================================

$message = "";
$message_type = "";

$upload_dir = __DIR__ . "/admin-uploads/";


if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}


// =========================================================
// DELETE USER
// =========================================================

if (isset($_GET['delete'])) {

    $delete_id = (int) $_GET['delete'];


    // Prevent self delete
    if ($delete_id === (int) $_SESSION['user_id']) {

        $message = "You cannot delete your own account.";
        $message_type = "danger";
    } else {

        // Get old profile picture
        $stmt = $conn->prepare("
            SELECT profile_pic
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        if ($stmt) {

            $stmt->bind_param(
                "i",
                $delete_id
            );

            $stmt->execute();

            $result =
                $stmt->get_result();

            $user =
                $result->fetch_assoc();

            $stmt->close();


            // Delete profile image
            if (
                $user &&
                !empty($user['profile_pic'])
            ) {

                $old_file =
                    $upload_dir .
                    $user['profile_pic'];

                if (
                    file_exists($old_file)
                ) {

                    unlink($old_file);
                }
            }
        }


        // Delete user
        $stmt = $conn->prepare("
            DELETE FROM users
            WHERE id = ?
        ");

        if ($stmt) {

            $stmt->bind_param(
                "i",
                $delete_id
            );


            if ($stmt->execute()) {

                $message =
                    "User deleted successfully.";

                $message_type =
                    "success";
            } else {

                $message =
                    "Unable to delete user.";

                $message_type =
                    "danger";
            }

            $stmt->close();
        } else {

            $message =
                "Unable to prepare delete query.";

            $message_type =
                "danger";
        }
    }
}


// =========================================================
// ADD / UPDATE USER
// =========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action =
        $_POST['action'] ?? '';

    $id =
        (int)($_POST['id'] ?? 0);

    $username =
        trim($_POST['username'] ?? '');

    $name =
        trim($_POST['name'] ?? '');

    $email =
        trim($_POST['email'] ?? '');

    $password =
        $_POST['password'] ?? '';

    $role_id =
        (int)($_POST['role'] ?? 0);

    $status =
        trim($_POST['status'] ?? 'active');


    // =====================================================
    // VALIDATION
    // =====================================================

    if (
        $username === '' ||
        $name === '' ||
        $email === ''
    ) {

        $message =
            "Username, Name and Email are required.";

        $message_type =
            "danger";
    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $message =
            "Please enter a valid email address.";

        $message_type =
            "danger";
    } elseif (
        $role_id <= 0
    ) {

        $message =
            "Please select a role.";

        $message_type =
            "danger";
    } elseif (
        !in_array(
            $status,
            ['active', 'inactive'],
            true
        )
    ) {

        $message =
            "Invalid user status.";

        $message_type =
            "danger";
    } else {


        // =================================================
        // CHECK ROLE
        // =================================================

        $role_check =
            $conn->prepare("
                SELECT id, role_name
                FROM roles
                WHERE id = ?
                AND status = 1
                LIMIT 1
            ");

        if (!$role_check) {

            $message =
                "Role query failed.";

            $message_type =
                "danger";
        } else {

            $role_check->bind_param(
                "i",
                $role_id
            );

            $role_check->execute();

            $role_result =
                $role_check->get_result();

            $role_row = $role_result->fetch_assoc();

            $role_exists = !empty($role_row);

            $selected_role_name =
                trim($role_row['role_name'] ?? '');

            $role_check->close();


            if (!$role_exists) {

                $message =
                    "Selected role does not exist.";

                $message_type =
                    "danger";
            } else {


                // =================================================
                // ADD USER
                // =================================================

                if ($action === 'add') {


                    // ---------------------------------------------
                    // Password required
                    // ---------------------------------------------

                    if ($password === '') {

                        $message =
                            "Password is required for new user.";

                        $message_type =
                            "danger";
                    } else {


                        // -----------------------------------------
                        // Check duplicate username/email
                        // -----------------------------------------

                        $duplicate =
                            $conn->prepare("
                                SELECT id
                                FROM users
                                WHERE username = ?
                                OR email = ?
                                LIMIT 1
                            ");

                        if (!$duplicate) {

                            $message =
                                "Unable to check duplicate user.";

                            $message_type =
                                "danger";
                        } else {

                            $duplicate->bind_param(
                                "ss",
                                $username,
                                $email
                            );

                            $duplicate->execute();

                            $duplicate_result =
                                $duplicate->get_result();

                            $exists =
                                $duplicate_result->num_rows > 0;

                            $duplicate->close();


                            if ($exists) {

                                $message =
                                    "Username or email already exists.";

                                $message_type =
                                    "danger";
                            } else {


                                // ---------------------------------
                                // Password hash
                                // ---------------------------------

                                $hashed_password =
                                    password_hash(
                                        $password,
                                        PASSWORD_DEFAULT
                                    );


                                // ---------------------------------
                                // INSERT USER
                                // ---------------------------------

                                $stmt =
                                    $conn->prepare("
                                        INSERT INTO users
                                        (
                                            username,
                                            name,
                                            email,
                                            password,
                                            role,
                                            role_id,
                                            status,
                                            created_at
                                        )
                                        VALUES
                                        (
                                            ?,
                                            ?,
                                            ?,
                                            ?,
                                            ?,
                                            ?,
                                            ?,
                                            NOW()
                                        )
                                    ");


                                if (!$stmt) {

                                    $message =
                                        "Unable to prepare user insert.";

                                    $message_type =
                                        "danger";
                                } else {


                                    /*
                                     * username       = s
                                     * name           = s
                                     * email          = s
                                     * password       = s
                                     * role_id        = i
                                     * status         = s
                                     *
                                     * Correct:
                                     * ssssis
                                     */

                                    $stmt->bind_param(
                                        "sssssis",
                                        $username,
                                        $name,
                                        $email,
                                        $hashed_password,
                                        $selected_role_name,
                                        $role_id,
                                        $status
                                    );


                                    if ($stmt->execute()) {

                                        $new_user_id =
                                            $stmt->insert_id;


                                        // =================================
                                        // PROFILE IMAGE
                                        // =================================

                                        if (
                                            isset(
                                                $_FILES['profile_pic']
                                            ) &&
                                            $_FILES['profile_pic']['error']
                                            === UPLOAD_ERR_OK
                                        ) {

                                            $allowed_ext = [
                                                'jpg',
                                                'jpeg',
                                                'png',
                                                'gif',
                                                'webp'
                                            ];


                                            $extension =
                                                strtolower(
                                                    pathinfo(
                                                        $_FILES['profile_pic']['name'],
                                                        PATHINFO_EXTENSION
                                                    )
                                                );


                                            if (
                                                in_array(
                                                    $extension,
                                                    $allowed_ext,
                                                    true
                                                )
                                            ) {

                                                $file_name =
                                                    "user_" .
                                                    $new_user_id .
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

                                                    $stmt_pic =
                                                        $conn->prepare("
                                                            UPDATE users
                                                            SET profile_pic = ?
                                                            WHERE id = ?
                                                        ");

                                                    if ($stmt_pic) {

                                                        $stmt_pic->bind_param(
                                                            "si",
                                                            $file_name,
                                                            $new_user_id
                                                        );

                                                        $stmt_pic->execute();

                                                        $stmt_pic->close();
                                                    }
                                                }
                                            }
                                        }


                                        $message =
                                            "User added successfully.";

                                        $message_type =
                                            "success";
                                    } else {

                                        $message =
                                            "Error adding user: " .
                                            $stmt->error;

                                        $message_type =
                                            "danger";
                                    }


                                    $stmt->close();
                                }
                            }
                        }
                    }


                    // =================================================
                    // EDIT USER
                    // =================================================

                } elseif (
                    $action === 'edit' &&
                    $id > 0
                ) {


                    // ---------------------------------------------
                    // Check duplicate username
                    // ---------------------------------------------

                    $duplicate_username =
                        $conn->prepare("
                            SELECT id
                            FROM users
                            WHERE username = ?
                            AND id != ?
                            LIMIT 1
                        ");

                    if (!$duplicate_username) {

                        $message =
                            "Unable to check username.";

                        $message_type =
                            "danger";
                    } else {

                        $duplicate_username->bind_param(
                            "si",
                            $username,
                            $id
                        );

                        $duplicate_username->execute();

                        $username_result =
                            $duplicate_username->get_result();

                        $username_exists =
                            $username_result->num_rows > 0;

                        $duplicate_username->close();


                        if ($username_exists) {

                            $message =
                                "Username already exists.";

                            $message_type =
                                "danger";
                        } else {


                            // -------------------------------------
                            // Check duplicate email
                            // -------------------------------------

                            $duplicate_email =
                                $conn->prepare("
                                    SELECT id
                                    FROM users
                                    WHERE email = ?
                                    AND id != ?
                                    LIMIT 1
                                ");

                            if (!$duplicate_email) {

                                $message =
                                    "Unable to check email.";

                                $message_type =
                                    "danger";
                            } else {

                                $duplicate_email->bind_param(
                                    "si",
                                    $email,
                                    $id
                                );

                                $duplicate_email->execute();

                                $email_result =
                                    $duplicate_email->get_result();

                                $email_exists =
                                    $email_result->num_rows > 0;

                                $duplicate_email->close();


                                if ($email_exists) {

                                    $message =
                                        "Email already exists.";

                                    $message_type =
                                        "danger";
                                } else {


                                    // =================================
                                    // UPDATE WITH PASSWORD
                                    // =================================

                                    if ($password !== '') {

                                        $hashed_password =
                                            password_hash(
                                                $password,
                                                PASSWORD_DEFAULT
                                            );


                                        $stmt =
                                            $conn->prepare("
                                                UPDATE users
                                                SET
                                                    username = ?,
                                                    name = ?,
                                                    email = ?,
                                                    password = ?,
                                                    role = ?,
                                                    role_id = ?,
                                                    status = ?
                                                WHERE id = ?
                                            ");


                                        if (!$stmt) {

                                            $message =
                                                "Unable to prepare user update.";

                                            $message_type =
                                                "danger";
                                        } else {


                                            /*
                                             * username   = s
                                             * name       = s
                                             * email      = s
                                             * password   = s
                                             * role_id    = i
                                             * status     = s
                                             * id         = i
                                             *
                                             * Correct:
                                             * ssssisi
                                             */

                                            $stmt->bind_param(
                                                "sssssisi",
                                                $username,
                                                $name,
                                                $email,
                                                $hashed_password,
                                                $selected_role_name,
                                                $role_id,
                                                $status,
                                                $id
                                            );


                                            $update_success =
                                                $stmt->execute();

                                            $stmt->close();
                                        }


                                        // =================================
                                        // UPDATE WITHOUT PASSWORD
                                        // =================================

                                    } else {

                                        $stmt =
                                            $conn->prepare("
                                                UPDATE users
                                                SET
                                                    username = ?,
                                                    name = ?,
                                                    email = ?,
                                                    role = ?,
                                                    role_id = ?,
                                                    status = ?
                                                WHERE id = ?
                                            ");


                                        if (!$stmt) {

                                            $message =
                                                "Unable to prepare user update.";

                                            $message_type =
                                                "danger";
                                        } else {


                                            /*
                                             * username = s
                                             * name     = s
                                             * email    = s
                                             * role_id  = i
                                             * status   = s
                                             * id       = i
                                             *
                                             * Correct:
                                             * sssisi
                                             */

                                            $stmt->bind_param(
                                                "ssssisi",
                                                $username,
                                                $name,
                                                $email,
                                                $selected_role_name,
                                                $role_id,
                                                $status,
                                                $id
                                            );


                                            $update_success =
                                                $stmt->execute();

                                            $stmt->close();
                                        }
                                    }


                                    // =================================
                                    // AFTER UPDATE
                                    // =================================

                                    if (
                                        isset($update_success) &&
                                        $update_success
                                    ) {


                                        // =================================
                                        // NEW PROFILE IMAGE
                                        // =================================

                                        if (
                                            isset(
                                                $_FILES['profile_pic']
                                            ) &&
                                            $_FILES['profile_pic']['error']
                                            === UPLOAD_ERR_OK
                                        ) {

                                            $allowed_ext = [
                                                'jpg',
                                                'jpeg',
                                                'png',
                                                'gif',
                                                'webp'
                                            ];


                                            $extension =
                                                strtolower(
                                                    pathinfo(
                                                        $_FILES['profile_pic']['name'],
                                                        PATHINFO_EXTENSION
                                                    )
                                                );


                                            if (
                                                in_array(
                                                    $extension,
                                                    $allowed_ext,
                                                    true
                                                )
                                            ) {


                                                // -------------------------
                                                // Get old image
                                                // -------------------------

                                                $stmt_old =
                                                    $conn->prepare("
                                                        SELECT profile_pic
                                                        FROM users
                                                        WHERE id = ?
                                                        LIMIT 1
                                                    ");


                                                if ($stmt_old) {

                                                    $stmt_old->bind_param(
                                                        "i",
                                                        $id
                                                    );

                                                    $stmt_old->execute();

                                                    $old_user =
                                                        $stmt_old
                                                        ->get_result()
                                                        ->fetch_assoc();

                                                    $stmt_old->close();


                                                    if (
                                                        $old_user &&
                                                        !empty($old_user['profile_pic'])
                                                    ) {

                                                        $old_file =
                                                            $upload_dir .
                                                            $old_user['profile_pic'];


                                                        if (
                                                            file_exists(
                                                                $old_file
                                                            )
                                                        ) {

                                                            unlink(
                                                                $old_file
                                                            );
                                                        }
                                                    }
                                                }


                                                // -------------------------
                                                // Save new image
                                                // -------------------------

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

                                                    $stmt_pic =
                                                        $conn->prepare("
                                                            UPDATE users
                                                            SET profile_pic = ?
                                                            WHERE id = ?
                                                        ");


                                                    if ($stmt_pic) {

                                                        $stmt_pic->bind_param(
                                                            "si",
                                                            $file_name,
                                                            $id
                                                        );

                                                        $stmt_pic->execute();

                                                        $stmt_pic->close();
                                                    }
                                                }
                                            }
                                        }


                                        $message =
                                            "User updated successfully.";

                                        $message_type =
                                            "success";
                                    } else {

                                        $message =
                                            "Error updating user.";

                                        $message_type =
                                            "danger";
                                    }
                                }
                            }
                        }
                    }
                } else {

                    $message =
                        "Invalid action.";

                    $message_type =
                        "danger";
                }
            }
        }
    }
}


// =========================================================
// SEARCH USERS
// =========================================================

$search =
    trim($_GET['search'] ?? '');


if ($search !== '') {

    $search_like =
        "%" . $search . "%";


    $stmt =
        $conn->prepare("
            SELECT
                users.id,
                users.username,
                users.name,
                users.email,
                users.profile_pic,
                users.role_id,
                users.status,
                roles.role_name,
                users.created_at
            FROM users
            LEFT JOIN roles
                ON users.role_id = roles.id
            WHERE
                users.username LIKE ?
                OR users.name LIKE ?
                OR users.email LIKE ?
                OR roles.role_name LIKE ?
            ORDER BY users.id DESC
        ");


    if ($stmt) {

        $stmt->bind_param(
            "ssss",
            $search_like,
            $search_like,
            $search_like,
            $search_like
        );

        $stmt->execute();

        $users =
            $stmt->get_result();
    } else {

        $users = false;
    }
} else {

    $users =
        $conn->query("
            SELECT
                users.id,
                users.username,
                users.name,
                users.email,
                users.profile_pic,
                users.role_id,
                users.status,
                roles.role_name,
                users.created_at
            FROM users
            LEFT JOIN roles
                ON users.role_id = roles.id
            ORDER BY users.id DESC
        ");
}


// =========================================================
// USER COUNT
// =========================================================

$count_result =
    $conn->query("
        SELECT COUNT(*) AS total
        FROM users
    ");

$total_users = 0;

if ($count_result) {

    $count_row =
        $count_result->fetch_assoc();

    $total_users =
        (int)($count_row['total'] ?? 0);
}


// =========================================================
// FETCH ROLES
// =========================================================

$roles_result =
    $conn->query("
        SELECT
            id,
            role_name
        FROM roles
        WHERE status = 1
        ORDER BY role_name ASC
    ");


include "./include/header.php";

?>


<!-- =========================================================
     TAILWIND CONFIG
========================================================= -->

<script>
    tailwind.config = {

        theme: {

            extend: {

                colors: {

                    themeOrange: '#ef7d00',

                    themeOrangeHover: '#d97100',

                    themeOrangeLight: '#fff7ed',

                    themeNavy: '#0a1945',

                    themeNavyLight: '#182b5e'

                }

            }

        }

    };
</script>


<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap'
    );


    body {

        font-family: 'Inter', sans-serif;

        background-color: #fcfaf8;

        background-image:
            radial-gradient(at 100% 0%,
                rgba(239, 125, 0, 0.05) 0px,
                transparent 50%),
            radial-gradient(at 0% 100%,
                rgba(10, 25, 69, 0.03) 0px,
                transparent 50%);

        background-attachment: fixed;
    }


    .glass-panel {

        background:
            rgba(255, 255, 255, 0.85);

        border-radius:
            1.25rem;

        border:
            1px solid rgba(239, 125, 0, 0.15);

        backdrop-filter:
            blur(16px);

        -webkit-backdrop-filter:
            blur(16px);

        box-shadow:
            0 4px 20px -2px rgba(239, 125, 0, 0.05);
    }


    .req:after {

        content: " *";

        color: #ef4444;

        font-weight: bold;
    }


    .custom-scrollbar::-webkit-scrollbar {

        height: 6px;
    }


    .custom-scrollbar::-webkit-scrollbar-track {

        background: transparent;
    }


    .custom-scrollbar::-webkit-scrollbar-thumb {

        background-color: #fed7aa;

        border-radius: 20px;
    }
</style>


<!-- =========================================================
     PAGE HEADER
========================================================= -->

<div class="glass-panel p-6 mb-6">

    <div
        class="flex flex-col md:flex-row
               md:items-center
               md:justify-between
               gap-4">

        <div>

            <h1
                class="text-2xl
                       font-extrabold
                       tracking-tight
                       text-themeNavy">

                User Management

            </h1>


            <p
                class="text-slate-500
                       text-sm
                       mt-1
                       font-medium">

                Manage system users, access levels, and surveyors for Khalilabad.

            </p>

        </div>


        <button
            onclick="openAddModal()"
            type="button"
            class="px-6 py-2.5
                   bg-themeOrange
                   hover:bg-themeOrangeHover
                   text-white
                   text-sm
                   font-semibold
                   rounded-xl
                   shadow-lg
                   shadow-orange-500/20
                   transition-all
                   duration-300
                   flex items-center
                   gap-2">

            <i class="bx bx-plus text-xl"></i>

            Add New User

        </button>

    </div>

</div>


<!-- =========================================================
     MESSAGE
========================================================= -->

<?php if (!empty($message)): ?>

    <div
        class="mb-6
           p-4
           rounded-xl
           border
           flex
           items-center
           gap-3
           backdrop-blur-md
           font-medium
           <?= $message_type === 'success'
                ? 'bg-green-50/80 border-green-200 text-green-700'
                : 'bg-red-50/80 border-red-200 text-red-700'
            ?>">

        <i
            class="bx
               <?= $message_type === 'success'
                    ? 'bx-check-circle'
                    : 'bx-error-circle'
                ?>
               text-xl">
        </i>


        <span class="text-sm">

            <?= htmlspecialchars($message) ?>

        </span>

    </div>

<?php endif; ?>


<!-- =========================================================
     STATS
========================================================= -->

<div
    class="grid
           grid-cols-1
           sm:grid-cols-2
           lg:grid-cols-4
           gap-6
           mb-6">


    <div
        class="glass-panel
               p-6
               relative
               overflow-hidden
               group">

        <div
            class="absolute
                   top-0
                   right-0
                   p-4
                   opacity-10
                   group-hover:opacity-20
                   transition-opacity">

            <i
                class="bx bx-group
                       text-6xl
                       text-themeOrange">
            </i>

        </div>


        <div class="relative z-10">

            <p
                class="text-slate-500
                       text-sm
                       font-semibold
                       uppercase
                       tracking-wider">

                Total Users

            </p>


            <h2
                class="text-4xl
                       font-extrabold
                       mt-2
                       text-themeNavy">

                <?= $total_users ?>

            </h2>

        </div>

    </div>

</div>


<!-- =========================================================
     USER LIST
========================================================= -->

<div class="glass-panel p-6">


    <div
        class="flex
               flex-col
               sm:flex-row
               sm:items-center
               sm:justify-between
               gap-4
               mb-6">


        <h2
            class="text-lg
                   font-bold
                   text-themeNavy">

            Active Directory

        </h2>


        <form
            method="get"
            class="flex
                   flex-1
                   sm:max-w-md
                   gap-2">


            <div class="relative w-full">

                <i
                    class="bx bx-search
                           absolute
                           left-3
                           top-1/2
                           -translate-y-1/2
                           text-slate-400">
                </i>


                <input
                    type="text"
                    name="search"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search users by name or email..."
                    class="w-full
                           pl-10
                           pr-4
                           py-2.5
                           bg-white
                           border
                           border-slate-200
                           rounded-xl
                           text-sm
                           outline-none
                           focus:border-themeOrange
                           focus:ring-4
                           focus:ring-themeOrange/10
                           transition-all
                           font-medium">

            </div>


            <button
                type="submit"
                class="px-5
                       py-2.5
                       bg-themeNavy
                       hover:bg-themeNavyLight
                       text-white
                       font-medium
                       rounded-xl
                       transition-colors
                       shadow-md
                       text-sm">

                Search

            </button>


            <?php if ($search !== ''): ?>

                <a
                    href="users.php"
                    class="px-4
                       py-2.5
                       bg-slate-100
                       hover:bg-slate-200
                       text-slate-700
                       rounded-xl
                       transition-colors
                       text-sm
                       font-medium
                       flex
                       items-center
                       justify-center">

                    Reset

                </a>

            <?php endif; ?>


        </form>

    </div>


    <!-- =====================================================
         TABLE
    ====================================================== -->

    <div
        class="overflow-x-auto
               custom-scrollbar
               pb-2">


        <table
            class="w-full
                   text-sm
                   text-left
                   whitespace-nowrap">


            <thead>

                <tr
                    class="border-b
                           border-orange-100
                           text-slate-500
                           bg-themeOrangeLight/50">


                    <th class="p-4 font-semibold">
                        #
                    </th>


                    <th class="p-4 font-semibold">
                        User Details
                    </th>


                    <th class="p-4 font-semibold">
                        Username
                    </th>


                    <th class="p-4 font-semibold">
                        Role
                    </th>


                    <th class="p-4 font-semibold">
                        Status
                    </th>


                    <th class="p-4 font-semibold">
                        Created
                    </th>


                    <th class="p-4 font-semibold text-center">
                        Action
                    </th>


                </tr>

            </thead>


            <tbody>


                <?php if (
                    $users &&
                    $users->num_rows > 0
                ): ?>


                    <?php

                    $sr = 1;

                    while (
                        $user =
                        $users->fetch_assoc()
                    ):

                    ?>


                        <tr
                            class="border-b
                           border-slate-100
                           hover:bg-themeOrangeLight/30
                           transition-colors">


                            <!-- SERIAL -->

                            <td
                                class="p-4
                               text-slate-400
                               font-medium">

                                <?= $sr++ ?>

                            </td>


                            <!-- USER -->

                            <td class="p-4">

                                <div
                                    class="flex
                                   items-center
                                   gap-3">


                                    <?php

                                    $profile =
                                        !empty($user['profile_pic'])
                                        ? "admin-uploads/" .
                                        $user['profile_pic']
                                        : "admin/man.png";

                                    ?>


                                    <img
                                        src="<?= htmlspecialchars($profile) ?>"
                                        class="w-10
                                       h-10
                                       rounded-full
                                       object-cover
                                       border-2
                                       border-white
                                       shadow-sm
                                       ring-1
                                       ring-slate-100"
                                        onerror="this.onerror=null;this.src='admin/man.png';">


                                    <div>

                                        <div
                                            class="font-bold
                                           text-themeNavy">

                                            <?= htmlspecialchars(
                                                $user['name']
                                            ) ?>

                                        </div>


                                        <div
                                            class="text-xs
                                           text-slate-500
                                           font-medium">

                                            <?= htmlspecialchars(
                                                $user['email']
                                            ) ?>

                                        </div>

                                    </div>

                                </div>

                            </td>


                            <!-- USERNAME -->

                            <td
                                class="p-4
                               font-semibold
                               text-slate-600">

                                @<?= htmlspecialchars(
                                        $user['username']
                                    ) ?>

                            </td>


                            <!-- ROLE -->

                            <td class="p-4">

                                <span
                                    class="px-3
                                   py-1
                                   rounded-full
                                   text-[11px]
                                   font-bold
                                   tracking-wider
                                   uppercase
                                   bg-orange-100
                                   text-themeOrange
                                   border
                                   border-orange-200">

                                    <?= htmlspecialchars(
                                        $user['role_name']
                                            ?? 'No Role'
                                    ) ?>

                                </span>

                            </td>


                            <!-- STATUS -->

                            <td class="p-4">


                                <?php if (
                                    $user['status'] === 'active'
                                ): ?>


                                    <span
                                        class="px-3
                                   py-1.5
                                   rounded-full
                                   text-[11px]
                                   font-bold
                                   tracking-wide
                                   uppercase
                                   bg-emerald-50
                                   text-emerald-600
                                   border
                                   border-emerald-100
                                   flex
                                   items-center
                                   w-fit
                                   gap-1.5">


                                        <div
                                            class="w-1.5
                                       h-1.5
                                       rounded-full
                                       bg-emerald-500">
                                        </div>


                                        Active


                                    </span>


                                <?php else: ?>


                                    <span
                                        class="px-3
                                   py-1.5
                                   rounded-full
                                   text-[11px]
                                   font-bold
                                   tracking-wide
                                   uppercase
                                   bg-rose-50
                                   text-rose-600
                                   border
                                   border-rose-100
                                   flex
                                   items-center
                                   w-fit
                                   gap-1.5">


                                        <div
                                            class="w-1.5
                                       h-1.5
                                       rounded-full
                                       bg-rose-500">
                                        </div>


                                        Inactive


                                    </span>


                                <?php endif; ?>


                            </td>


                            <!-- CREATED -->

                            <td
                                class="p-4
                               text-slate-500
                               font-medium">

                                <?= !empty($user['created_at'])
                                    ? date(
                                        'd M Y',
                                        strtotime(
                                            $user['created_at']
                                        )
                                    )
                                    : '-'
                                ?>

                            </td>


                            <!-- ACTION -->

                            <td class="p-4">

                                <div
                                    class="flex
                                   justify-center
                                   gap-2">


                                    <!-- EDIT -->

                                    <button
                                        type="button"
                                        onclick='editUser(<?= json_encode(
                                                                $user,
                                                                JSON_HEX_TAG |
                                                                    JSON_HEX_APOS |
                                                                    JSON_HEX_QUOT |
                                                                    JSON_HEX_AMP
                                                            ) ?>)'
                                        class="w-8
                                       h-8
                                       rounded-lg
                                       bg-orange-50
                                       text-themeOrange
                                       hover:bg-themeOrange
                                       hover:text-white
                                       flex
                                       items-center
                                       justify-center
                                       transition-colors
                                       shadow-sm"
                                        title="Edit">


                                        <i
                                            class="bx bx-edit text-lg">
                                        </i>


                                    </button>


                                    <!-- DELETE -->

                                    <?php if (
                                        (int)$user['id']
                                        !==
                                        (int)$_SESSION['user_id']
                                    ): ?>


                                        <a
                                            href="users.php?delete=<?= (int)$user['id'] ?>"
                                            onclick="return confirm('Are you sure you want to delete this user?')"
                                            class="w-8
                                       h-8
                                       rounded-lg
                                       bg-rose-50
                                       text-rose-600
                                       hover:bg-rose-500
                                       hover:text-white
                                       flex
                                       items-center
                                       justify-center
                                       transition-colors
                                       shadow-sm"
                                            title="Delete">


                                            <i
                                                class="bx bx-trash text-lg">
                                            </i>


                                        </a>


                                    <?php endif; ?>


                                </div>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="7"
                            class="text-center py-12">


                            <div
                                class="flex
                                   flex-col
                                   items-center
                                   justify-center
                                   text-slate-400">


                                <i
                                    class="bx bx-folder-open
                                       text-5xl
                                       mb-3
                                       text-orange-200">
                                </i>


                                <p class="font-medium">

                                    No users found matching your criteria.

                                </p>


                            </div>

                        </td>

                    </tr>


                <?php endif; ?>


            </tbody>

        </table>

    </div>

</div>


<!-- =========================================================
     ADD / EDIT MODAL
========================================================= -->

<div
    id="userModal"
    class="hidden
           fixed
           inset-0
           z-50
           items-center
           justify-center
           p-4">


    <!-- BACKDROP -->

    <div
        class="absolute
               inset-0
               bg-themeNavy/40
               backdrop-blur-sm"
        onclick="closeModal()">
    </div>


    <!-- MODAL -->

    <div
        class="relative
               bg-white
               rounded-2xl
               shadow-2xl
               w-full
               max-w-2xl
               max-h-[90vh]
               overflow-y-auto
               border
               border-orange-100">


        <!-- HEADER -->

        <div
            class="sticky
                   top-0
                   bg-white/90
                   backdrop-blur-md
                   flex
                   items-center
                   justify-between
                   p-5
                   border-b
                   border-orange-50
                   z-10">


            <h2
                id="modalTitle"
                class="text-xl
                       font-extrabold
                       text-themeNavy">

                Add User

            </h2>


            <button
                type="button"
                onclick="closeModal()"
                class="w-8
                       h-8
                       rounded-full
                       bg-slate-50
                       hover:bg-rose-50
                       text-slate-500
                       hover:text-rose-500
                       flex
                       items-center
                       justify-center">

                <i class="bx bx-x text-xl"></i>

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


            <div
                class="grid
                       grid-cols-1
                       md:grid-cols-2
                       gap-5">


                <!-- NAME -->

                <div>

                    <label
                        class="block
                               text-sm
                               font-bold
                               text-slate-700
                               mb-1.5
                               req">

                        Full Name

                    </label>


                    <input
                        type="text"
                        name="name"
                        id="userName"
                        required
                        class="w-full
                               bg-slate-50
                               border
                               border-slate-200
                               rounded-xl
                               px-4
                               py-2.5
                               text-sm
                               outline-none
                               focus:bg-white
                               focus:border-themeOrange
                               focus:ring-4
                               focus:ring-themeOrange/10
                               transition-all
                               font-medium">

                </div>


                <!-- USERNAME -->

                <div>

                    <label
                        class="block
                               text-sm
                               font-bold
                               text-slate-700
                               mb-1.5
                               req">

                        Username

                    </label>


                    <input
                        type="text"
                        name="username"
                        id="userUsername"
                        required
                        class="w-full
                               bg-slate-50
                               border
                               border-slate-200
                               rounded-xl
                               px-4
                               py-2.5
                               text-sm
                               outline-none
                               focus:bg-white
                               focus:border-themeOrange
                               focus:ring-4
                               focus:ring-themeOrange/10
                               transition-all
                               font-medium">

                </div>


                <!-- EMAIL -->

                <div>

                    <label
                        class="block
                               text-sm
                               font-bold
                               text-slate-700
                               mb-1.5
                               req">

                        Email Address

                    </label>


                    <input
                        type="email"
                        name="email"
                        id="userEmail"
                        required
                        class="w-full
                               bg-slate-50
                               border
                               border-slate-200
                               rounded-xl
                               px-4
                               py-2.5
                               text-sm
                               outline-none
                               focus:bg-white
                               focus:border-themeOrange
                               focus:ring-4
                               focus:ring-themeOrange/10
                               transition-all
                               font-medium">

                </div>


                <!-- PASSWORD -->

                <div>

                    <label
                        class="block
                               text-sm
                               font-bold
                               text-slate-700
                               mb-1.5">

                        Password

                    </label>


                    <input
                        type="password"
                        name="password"
                        id="userPassword"
                        class="w-full
                               bg-slate-50
                               border
                               border-slate-200
                               rounded-xl
                               px-4
                               py-2.5
                               text-sm
                               outline-none
                               focus:bg-white
                               focus:border-themeOrange
                               focus:ring-4
                               focus:ring-themeOrange/10
                               transition-all
                               font-medium">


                    <p
                        class="text-[11px]
                               text-themeOrange
                               mt-1
                               font-semibold">

                        Leave blank while editing to keep old password.

                    </p>

                </div>


                <!-- ROLE -->

                <div>

                    <label
                        class="block
                               text-sm
                               font-bold
                               text-slate-700
                               mb-1.5">

                        Role

                    </label>


                    <select
                        name="role"
                        id="userRole"
                        required
                        class="w-full
                               bg-slate-50
                               border
                               border-slate-200
                               rounded-xl
                               px-4
                               py-2.5
                               text-sm
                               outline-none
                               focus:bg-white
                               focus:border-themeOrange
                               focus:ring-4
                               focus:ring-themeOrange/10
                               transition-all
                               font-medium
                               cursor-pointer">


                        <option value="">
                            Select Role
                        </option>


                        <?php if (
                            $roles_result &&
                            $roles_result->num_rows > 0
                        ): ?>


                            <?php while (
                                $role =
                                $roles_result->fetch_assoc()
                            ): ?>


                                <option
                                    value="<?= (int)$role['id'] ?>">

                                    <?= htmlspecialchars(
                                        $role['role_name']
                                    ) ?>

                                </option>


                            <?php endwhile; ?>


                        <?php else: ?>


                            <option value="">
                                No roles available
                            </option>


                        <?php endif; ?>


                    </select>

                </div>


                <!-- STATUS -->

                <div>

                    <label
                        class="block
                               text-sm
                               font-bold
                               text-slate-700
                               mb-1.5">

                        Status

                    </label>


                    <select
                        name="status"
                        id="userStatus"
                        class="w-full
                               bg-slate-50
                               border
                               border-slate-200
                               rounded-xl
                               px-4
                               py-2.5
                               text-sm
                               outline-none
                               focus:bg-white
                               focus:border-themeOrange
                               focus:ring-4
                               focus:ring-themeOrange/10
                               transition-all
                               font-medium
                               cursor-pointer">


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


                    <label
                        class="block
                               text-sm
                               font-bold
                               text-slate-700
                               mb-1.5">

                        Profile Picture

                    </label>


                    <div
                        class="flex
                               items-center
                               gap-4">


                        <div class="flex-1">


                            <input
                                type="file"
                                name="profile_pic"
                                accept=".jpg,.jpeg,.png,.gif,.webp"
                                class="w-full
                                       text-sm
                                       text-slate-500
                                       file:mr-4
                                       file:py-2.5
                                       file:px-4
                                       file:rounded-xl
                                       file:border-0
                                       file:text-sm
                                       file:font-bold
                                       file:bg-orange-50
                                       file:text-themeOrange
                                       hover:file:bg-orange-100
                                       border
                                       border-slate-200
                                       rounded-xl
                                       bg-slate-50">

                        </div>


                        <div
                            id="currentProfile"
                            class="hidden
                                   shrink-0">


                            <img
                                id="currentProfileImg"
                                src=""
                                class="w-12
                                       h-12
                                       rounded-xl
                                       object-cover
                                       border-2
                                       border-orange-100
                                       shadow-sm">

                        </div>


                    </div>

                </div>


            </div>


            <!-- FOOTER -->

            <div
                class="flex
                       justify-end
                       gap-3
                       mt-8
                       pt-5
                       border-t
                       border-slate-100">


                <button
                    type="button"
                    onclick="closeModal()"
                    class="px-5
                           py-2.5
                           rounded-xl
                           bg-slate-100
                           hover:bg-slate-200
                           text-slate-700
                           text-sm
                           font-bold">

                    Cancel

                </button>


                <button
                    type="submit"
                    id="saveButton"
                    class="px-6
                           py-2.5
                           rounded-xl
                           bg-themeOrange
                           hover:bg-themeOrangeHover
                           text-white
                           text-sm
                           font-bold
                           shadow-lg
                           shadow-orange-500/20
                           flex
                           items-center
                           gap-2">


                    <i class="bx bx-save text-lg"></i>


                    <span>
                        Save User
                    </span>


                </button>


            </div>


        </form>

    </div>

</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>
    // =========================================================
    // OPEN ADD MODAL
    // =========================================================

    function openAddModal() {

        document.getElementById(
                'modalTitle'
            ).innerText =
            'Add New User';


        document.getElementById(
                'formAction'
            ).value =
            'add';


        document.getElementById(
                'userId'
            ).value =
            '0';


        document.getElementById(
                'userName'
            ).value =
            '';


        document.getElementById(
                'userUsername'
            ).value =
            '';


        document.getElementById(
                'userEmail'
            ).value =
            '';


        document.getElementById(
                'userPassword'
            ).value =
            '';


        document.getElementById(
                'userRole'
            ).value =
            '';


        document.getElementById(
                'userStatus'
            ).value =
            'active';


        document
            .getElementById(
                'currentProfile'
            )
            .classList
            .add('hidden');


        document.getElementById(
                'saveButton'
            ).innerHTML =
            '<i class="bx bx-save text-lg"></i>' +
            '<span>Save User</span>';


        showModal();
    }


    // =========================================================
    // EDIT USER
    // =========================================================

    function editUser(user) {


        document.getElementById(
                'modalTitle'
            ).innerText =
            'Edit User Profile';


        document.getElementById(
                'formAction'
            ).value =
            'edit';


        document.getElementById(
                'userId'
            ).value =
            user.id;


        document.getElementById(
                'userName'
            ).value =
            user.name || '';


        document.getElementById(
                'userUsername'
            ).value =
            user.username || '';


        document.getElementById(
                'userEmail'
            ).value =
            user.email || '';


        document.getElementById(
                'userPassword'
            ).value =
            '';


        // =============================================
        // ROLE ID
        // =============================================

        const roleSelect =
            document.getElementById(
                'userRole'
            );


        roleSelect.value =
            String(
                user.role_id || ''
            );


        // =============================================
        // STATUS
        // =============================================

        document.getElementById(
                'userStatus'
            ).value =
            user.status || 'active';


        // =============================================
        // PROFILE IMAGE
        // =============================================

        if (
            user.profile_pic &&
            user.profile_pic !== ''
        ) {

            document.getElementById(
                    'currentProfileImg'
                ).src =
                'admin-uploads/' +
                user.profile_pic;


            document
                .getElementById(
                    'currentProfile'
                )
                .classList
                .remove('hidden');

        } else {

            document
                .getElementById(
                    'currentProfile'
                )
                .classList
                .add('hidden');
        }


        // =============================================
        // BUTTON
        // =============================================

        document.getElementById(
                'saveButton'
            ).innerHTML =
            '<i class="bx bx-check-circle text-lg"></i>' +
            '<span>Update User</span>';


        showModal();
    }


    // =========================================================
    // SHOW MODAL
    // =========================================================

    function showModal() {

        const modal =
            document.getElementById(
                'userModal'
            );


        modal.classList.remove(
            'hidden'
        );


        modal.classList.add(
            'flex'
        );
    }


    // =========================================================
    // CLOSE MODAL
    // =========================================================

    function closeModal() {

        const modal =
            document.getElementById(
                'userModal'
            );


        modal.classList.add(
            'hidden'
        );


        modal.classList.remove(
            'flex'
        );
    }


    // =========================================================
    // ESC KEY
    // =========================================================

    document.addEventListener(
        'keydown',
        function(e) {

            if (
                e.key === 'Escape'
            ) {

                closeModal();

            }

        }
    );
</script>


<?php

include "./include/footer.php";

?>