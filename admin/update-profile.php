<?php

session_start();

// echo "<pre>";
// print_r($_SESSION);
// die;
// =========================================================
// SESSION CHECK
// =========================================================

if (!isset($_SESSION['user_id'])) {

    header("Location: index.php");
    exit;
}


// =========================================================
// DATABASE
// =========================================================

require_once "db.php";


if (!isset($conn) || $conn->connect_error) {

    die("Fatal Error: Database Connection failed. Please check your db.php file.");
}


// =========================================================
// CONFIGURATION
// =========================================================

$upload_dir = __DIR__ . "/admin-uploads/";

$upload_url = "admin-uploads/";

$allowed_extensions = [
    'jpg',
    'jpeg',
    'png',
    'gif',
    'webp'
];

$max_file_size = 5 * 1024 * 1024; // 5 MB


// Create upload directory if not exists

if (!is_dir($upload_dir)) {

    mkdir(
        $upload_dir,
        0755,
        true
    );
}


// =========================================================
// SESSION USER
// =========================================================

$user_id =
    (int)$_SESSION['user_id'];

$user_username =
    $_SESSION['username'] ?? '';

$user_role =
    $_SESSION['role'] ?? 'user';

$user_role_id =
    (int)($_SESSION['role_id'] ?? 0);


$profile_data = null;

$message = '';

$error = '';


// =========================================================
// FETCH PROFILE DATA
// =========================================================

function fetchProfileData(
    $conn,
    $user_id,
    &$error
) {

    $sql = "
        SELECT
            u.id,
            u.username,
            u.name,
            u.email,
            u.profile_pic,
            u.role_id,
            u.status,
            u.created_at,
            r.role_name
        FROM users u
        LEFT JOIN roles r
            ON u.role_id = r.id
        WHERE u.id = ?
        LIMIT 1
    ";


    $stmt =
        $conn->prepare($sql);


    if (!$stmt) {

        $error =
            "SQL Prepare Error: " .
            $conn->error;

        return null;
    }


    $stmt->bind_param(
        "i",
        $user_id
    );


    if (!$stmt->execute()) {

        $error =
            "Database Fetch Error: " .
            $stmt->error;

        $stmt->close();

        return null;
    }


    $result =
        $stmt->get_result();


    $data =
        $result->fetch_assoc();


    $stmt->close();


    if ($data) {

        // ---------------------------------------------
        // Update session
        // ---------------------------------------------

        $_SESSION['user_id'] =
            (int)$data['id'];

        $_SESSION['username'] =
            $data['username'];

        $_SESSION['name'] =
            $data['name'];

        $_SESSION['role_id'] =
            (int)$data['role_id'];

        /*
         * Keep role name in session.
         *
         * Example:
         * admin
         * chairman
         * eo
         * clerk
         */

        $_SESSION['role'] =
            $data['role_name'] ?? '';
    }


    return $data;
}


// =========================================================
// INITIAL PROFILE
// =========================================================

$profile_data =
    fetchProfileData(
        $conn,
        $user_id,
        $error
    );


if (!$profile_data && empty($error)) {

    $error =
        "Profile data could not be found.";
}


// =========================================================
// UPDATE PROFILE
// =========================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $profile_data &&
    empty($error)
) {


    // =====================================================
    // NAME
    // =====================================================

    $new_name =
        trim(
            $_POST['name'] ?? ''
        );


    if ($new_name === '') {

        $error =
            "Name is required.";
    } elseif (mb_strlen($new_name) < 2) {

        $error =
            "Name must contain at least 2 characters.";
    } else {


        // =================================================
        // UPDATE FIELDS
        // =================================================

        $update_fields = [];

        $update_types = '';

        $update_params = [];


        // -------------------------------------------------
        // NAME
        // -------------------------------------------------

        $update_fields[] =
            "name = ?";

        $update_types .=
            "s";

        $update_params[] =
            $new_name;


        // =================================================
        // PROFILE IMAGE
        // =================================================

        $new_profile_pic =
            $profile_data['profile_pic'] ?? '';


        if (
            isset($_FILES['profile_pic_file']) &&
            $_FILES['profile_pic_file']['error']
            !== UPLOAD_ERR_NO_FILE
        ) {


            $file =
                $_FILES['profile_pic_file'];


            // ---------------------------------------------
            // Upload error
            // ---------------------------------------------

            if (
                $file['error']
                !== UPLOAD_ERR_OK
            ) {

                $error =
                    "There was an error uploading the profile picture.";
            } else {


                $file_size =
                    (int)$file['size'];


                $original_name =
                    $file['name'];


                $file_tmp =
                    $file['tmp_name'];


                $file_ext =
                    strtolower(
                        pathinfo(
                            $original_name,
                            PATHINFO_EXTENSION
                        )
                    );


                // -----------------------------------------
                // File size
                // -----------------------------------------

                if (
                    $file_size <= 0
                ) {

                    $error =
                        "Invalid profile picture.";
                } elseif (
                    $file_size > $max_file_size
                ) {

                    $error =
                        "Profile picture must be less than 5MB.";

                    // -----------------------------------------
                    // Extension
                    // -----------------------------------------

                } elseif (
                    !in_array(
                        $file_ext,
                        $allowed_extensions,
                        true
                    )
                ) {

                    $error =
                        "Only JPG, JPEG, PNG, GIF and WEBP files are allowed.";
                } else {


                    // -------------------------------------
                    // MIME validation
                    // -------------------------------------

                    $allowed_mimes = [
                        'image/jpeg',
                        'image/png',
                        'image/gif',
                        'image/webp'
                    ];


                    $mime_type = '';


                    if (
                        function_exists(
                            'finfo_open'
                        )
                    ) {

                        $finfo =
                            finfo_open(
                                FILEINFO_MIME_TYPE
                            );


                        if ($finfo) {

                            $mime_type =
                                finfo_file(
                                    $finfo,
                                    $file_tmp
                                );


                            finfo_close(
                                $finfo
                            );
                        }
                    }


                    if (
                        $mime_type !== '' &&
                        !in_array(
                            $mime_type,
                            $allowed_mimes,
                            true
                        )
                    ) {

                        $error =
                            "Invalid image file.";
                    } else {


                        // ---------------------------------
                        // Generate unique filename
                        // ---------------------------------

                        $unique_filename =
                            'profile_' .
                            $user_id .
                            '_' .
                            bin2hex(
                                random_bytes(8)
                            ) .
                            '.' .
                            $file_ext;


                        $target_file =
                            $upload_dir .
                            $unique_filename;


                        // ---------------------------------
                        // Move file
                        // ---------------------------------

                        if (
                            !move_uploaded_file(
                                $file_tmp,
                                $target_file
                            )
                        ) {

                            $error =
                                "Unable to upload profile picture. Please check folder permissions.";
                        } else {


                            $new_profile_pic =
                                $unique_filename;


                            $update_fields[] =
                                "profile_pic = ?";


                            $update_types .=
                                "s";


                            $update_params[] =
                                $new_profile_pic;
                        }
                    }
                }
            }
        }


        // =================================================
        // EXECUTE UPDATE
        // =================================================

        if (empty($error)) {


            // ---------------------------------------------
            // WHERE user ID
            // ---------------------------------------------

            $update_types .=
                "i";


            $update_params[] =
                $user_id;


            $sql_update =
                "UPDATE users SET " .
                implode(
                    ", ",
                    $update_fields
                ) .
                " WHERE id = ?";


            $stmt_update =
                $conn->prepare(
                    $sql_update
                );


            if (!$stmt_update) {

                $error =
                    "SQL Prepare Error: " .
                    $conn->error;
            } else {


                // -----------------------------------------
                // Dynamic bind
                // -----------------------------------------

                $stmt_update->bind_param(
                    $update_types,
                    ...$update_params
                );


                if (
                    $stmt_update->execute()
                ) {

                    $message =
                        "Success! Your profile details have been updated.";


                    // -------------------------------------
                    // Delete old profile picture
                    // -------------------------------------

                    if (
                        !empty($new_profile_pic) &&
                        !empty($profile_data['profile_pic']) &&
                        $profile_data['profile_pic']
                        !==
                        $new_profile_pic
                    ) {

                        $old_file =
                            $upload_dir .
                            basename(
                                $profile_data['profile_pic']
                            );


                        if (
                            is_file(
                                $old_file
                            )
                        ) {

                            @unlink(
                                $old_file
                            );
                        }
                    }


                    // -------------------------------------
                    // Refresh profile
                    // -------------------------------------

                    $profile_data =
                        fetchProfileData(
                            $conn,
                            $user_id,
                            $error
                        );


                    if (
                        !$profile_data &&
                        empty($error)
                    ) {

                        $error =
                            "Profile updated but could not refresh profile data.";
                    }
                } else {

                    $error =
                        "Error updating profile: " .
                        $stmt_update->error;


                    // -------------------------------------
                    // Remove newly uploaded image if
                    // database update failed
                    // -------------------------------------

                    if (
                        !empty($new_profile_pic) &&
                        $new_profile_pic
                        !==
                        ($profile_data['profile_pic'] ?? '')
                    ) {

                        $new_file =
                            $upload_dir .
                            basename(
                                $new_profile_pic
                            );


                        if (
                            is_file(
                                $new_file
                            )
                        ) {

                            @unlink(
                                $new_file
                            );
                        }
                    }
                }


                $stmt_update->close();
            }
        }
    }
}


// =========================================================
// DISPLAY DATA
// =========================================================

$display_role =
    $profile_data['role_name']
    ?? $user_role
    ?? 'User';


$display_role =
    trim(
        $display_role
    );


$display_role =
    $display_role !== ''
    ? $display_role
    : 'User';


$display_name =
    $profile_data['name']
    ?? 'User';


$display_username =
    $profile_data['username']
    ?? '';


$display_email =
    $profile_data['email']
    ?? '';


$display_status =
    strtolower(
        $profile_data['status']
            ?? 'inactive'
    );


// =========================================================
// IMAGE
// =========================================================

$profile_pic_filename =
    $profile_data['profile_pic']
    ?? '';


if (
    !empty($profile_pic_filename)
) {

    $image_src =
        $upload_url .
        rawurlencode(
            basename(
                $profile_pic_filename
            )
        );
} else {

    $image_src =
        'placeholder.png';
}


// =========================================================
// AVATAR FALLBACK
// =========================================================

$avatar_letter =
    strtoupper(
        mb_substr(
            $display_name,
            0,
            1
        )
    );


$avatar_fallback_text =
    rawurlencode(
        $avatar_letter
    );


// =========================================================
// STATUS COLOR
// =========================================================

if (
    $display_status === 'active'
) {

    $status_label =
        'Active';

    $status_color_class =
        'text-green-700 font-semibold border-green-400 bg-green-50';
} elseif (
    $display_status === 'inactive'
) {

    $status_label =
        'Inactive';

    $status_color_class =
        'text-red-700 font-semibold border-red-400 bg-red-50';
} else {

    $status_label =
        ucfirst(
            $display_status
        );

    $status_color_class =
        'text-gray-600 font-semibold border-gray-300 bg-gray-50';
}


// =========================================================
// DASHBOARD LINK
// =========================================================

$role_key =
    strtolower(
        trim(
            $display_role
        )
    );


if (
    $role_key === 'surveyor'
) {

    $dashboard_link =
        'surveyor-dashboard.php';
} else {

    $dashboard_link =
        'dashboard.php';
}


// =========================================================
// CLOSE DB
// =========================================================

$conn->close();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Update Profile |
        <?= htmlspecialchars(
            ucfirst($display_role)
        ) ?>
    </title>


    <link
        href="favicon.png"
        rel="icon">


    <script
        src="https://cdn.tailwindcss.com">
    </script>


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap'
        );


        body {

            font-family:
                'Inter',
                sans-serif;
        }


        .profile-card {

            box-shadow:
                0 15px 30px -10px rgba(0, 0, 0, 0.10),
                0 0 8px rgba(0, 0, 0, 0.05);
        }


        .input-field {

            transition:
                all 0.2s;
        }


        .input-field:focus {

            border-color:
                #059669;

            box-shadow:
                0 0 0 3px rgba(5, 150, 105, 0.20);
        }


        .read-only-field {

            background-color:
                #f3f4f6;

            cursor:
                not-allowed;
        }
    </style>

</head>


<body
    class="bg-gray-100
           min-h-screen
           flex
           items-center
           justify-center
           p-4">


    <div
        class="w-full
           max-w-2xl">


        <!-- =====================================================
         PAGE TITLE
    ====================================================== -->

        <div
            class="text-center
               mb-8">

            <h1
                class="text-3xl
                   font-extrabold
                   text-gray-900
                   mb-2">

                Update Profile Details

            </h1>


            <p
                class="text-sm
                   text-gray-500">

                Manage your personal information for your
                <strong>
                    <?= htmlspecialchars(
                        ucfirst($display_role)
                    ) ?>
                </strong>
                account.

            </p>

        </div>


        <!-- =====================================================
         CARD
    ====================================================== -->

        <div
            class="bg-white
               p-8
               rounded-xl
               profile-card
               border-t-4
               border-emerald-600">


            <!-- =================================================
             ERROR
        ================================================== -->

            <?php if ($error): ?>

                <div
                    class="bg-red-50
                   border
                   border-red-300
                   text-red-700
                   px-4
                   py-3
                   rounded-lg
                   relative
                   mb-6
                   text-sm
                   font-medium"
                    role="alert">

                    <i
                        class="fas fa-exclamation-triangle mr-2"></i>


                    <span>

                        <?= htmlspecialchars(
                            $error
                        ) ?>

                    </span>

                </div>

            <?php endif; ?>


            <!-- =================================================
             SUCCESS
        ================================================== -->

            <?php if ($message): ?>

                <div
                    class="bg-green-50
                   border
                   border-green-300
                   text-green-700
                   px-4
                   py-3
                   rounded-lg
                   relative
                   mb-6
                   text-sm
                   font-medium"
                    role="alert">

                    <i
                        class="fas fa-check-circle mr-2"></i>


                    <span>

                        <?= htmlspecialchars(
                            $message
                        ) ?>

                    </span>

                </div>

            <?php endif; ?>


            <!-- =================================================
             FORM
        ================================================== -->

            <form
                method="POST"
                action="update-profile.php"
                enctype="multipart/form-data"
                class="space-y-5">


                <!-- =============================================
                 PROFILE IMAGE
            ============================================== -->

                <div
                    class="flex
                       flex-col
                       items-center
                       justify-center
                       mb-8">


                    <div
                        class="relative
                           w-32
                           h-32
                           mb-4">


                        <img
                            src="<?= htmlspecialchars(
                                        $image_src
                                    ) ?>"
                            alt="Profile Picture"
                            class="w-full
                               h-full
                               object-cover
                               rounded-full
                               border-4
                               border-emerald-500
                               shadow-lg"
                            onerror="this.onerror=null;this.src='https://placehold.co/128x128/9CA3AF/FFFFFF?text=<?= $avatar_fallback_text ?>';">


                        <button
                            type="button"
                            onclick="document.getElementById('profile_pic_edit_section').classList.toggle('hidden');"
                            class="absolute
                               bottom-0
                               right-0
                               p-2
                               bg-emerald-600
                               rounded-full
                               text-white
                               shadow-xl
                               hover:bg-emerald-700
                               transition
                               duration-150
                               transform
                               hover:scale-110"
                            title="Change Profile Picture">

                            <i
                                class="fas fa-camera text-sm"
                                style="
                                border-radius:40px;
                                width:24px;
                            "></i>

                        </button>

                    </div>


                    <p
                        class="text-xl
                           font-semibold
                           text-gray-800">

                        <?= htmlspecialchars(
                            $display_name
                        ) ?>

                    </p>


                    <p
                        class="text-sm
                           text-gray-500">

                        Role:
                        <?= htmlspecialchars(
                            ucfirst(
                                $display_role
                            )
                        ) ?>

                    </p>

                </div>


                <!-- =============================================
                 PROFILE IMAGE UPLOAD
            ============================================== -->

                <div
                    id="profile_pic_edit_section"
                    class="hidden
                       mb-5
                       p-4
                       bg-gray-50
                       rounded-lg
                       border
                       border-gray-200">


                    <label
                        for="profile_pic_file"
                        class="block
                           text-sm
                           font-medium
                           text-gray-700
                           mb-1">

                        <i
                            class="fas fa-upload mr-2"></i>

                        Upload New Profile Picture

                    </label>


                    <input
                        type="file"
                        name="profile_pic_file"
                        id="profile_pic_file"
                        accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp"
                        class="input-field
                           w-full
                           px-4
                           py-2
                           border
                           border-gray-300
                           rounded-lg
                           bg-white
                           file:mr-4
                           file:py-2
                           file:px-4
                           file:rounded-full
                           file:border-0
                           file:text-sm
                           file:font-semibold
                           file:bg-emerald-50
                           file:text-emerald-700
                           hover:file:bg-emerald-100">


                    <p
                        class="text-xs
                           text-gray-500
                           mt-1">

                        Maximum 5MB.
                        JPG, JPEG, PNG, GIF or WEBP.

                    </p>

                </div>


                <!-- =============================================
                 FIELDS
            ============================================== -->

                <div
                    class="grid
                       grid-cols-1
                       md:grid-cols-2
                       gap-5">


                    <!-- USERNAME -->

                    <div>

                        <label
                            for="username"
                            class="block
                               text-sm
                               font-medium
                               text-gray-700
                               mb-1">

                            <i
                                class="fas fa-user-circle mr-2"></i>

                            Username

                        </label>


                        <input
                            type="text"
                            id="username"
                            value="<?= htmlspecialchars(
                                        $display_username
                                    ) ?>"
                            readonly
                            disabled
                            class="w-full
                               px-4
                               py-2
                               border
                               border-gray-300
                               rounded-lg
                               read-only-field">

                    </div>


                    <!-- EMAIL -->

                    <div>

                        <label
                            for="email"
                            class="block
                               text-sm
                               font-medium
                               text-gray-700
                               mb-1">

                            <i
                                class="fas fa-envelope mr-2"></i>

                            Email

                        </label>


                        <input
                            type="email"
                            id="email"
                            value="<?= htmlspecialchars(
                                        $display_email
                                    ) ?>"
                            readonly
                            disabled
                            class="w-full
                               px-4
                               py-2
                               border
                               border-gray-300
                               rounded-lg
                               read-only-field">

                    </div>


                    <!-- NAME -->

                    <div
                        class="md:col-span-2">

                        <label
                            for="name"
                            class="block
                               text-sm
                               font-medium
                               text-gray-700
                               mb-1">

                            <i
                                class="fas fa-user mr-2"></i>

                            Name

                            <span
                                class="text-red-500">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            name="name"
                            id="name"
                            value="<?= htmlspecialchars(
                                        $display_name
                                    ) ?>"
                            required
                            minlength="2"
                            maxlength="100"
                            class="input-field
                               w-full
                               px-4
                               py-2
                               border
                               border-gray-300
                               rounded-lg"
                            autocomplete="name">

                    </div>


                    <!-- ROLE -->

                    <div>

                        <label
                            for="role"
                            class="block
                               text-sm
                               font-medium
                               text-gray-700
                               mb-1">

                            <i
                                class="fas fa-shield-alt mr-2"></i>

                            Role

                        </label>


                        <input
                            type="text"
                            id="role"
                            value="<?= htmlspecialchars(
                                        ucfirst(
                                            $display_role
                                        )
                                    ) ?>"
                            readonly
                            disabled
                            class="w-full
                               px-4
                               py-2
                               border
                               border-gray-300
                               rounded-lg
                               read-only-field">

                    </div>


                    <!-- STATUS -->

                    <div>

                        <label
                            for="status"
                            class="block
                               text-sm
                               font-medium
                               text-gray-700
                               mb-1">

                            <i
                                class="fas fa-info-circle mr-2"></i>

                            Status

                        </label>


                        <input
                            type="text"
                            id="status"
                            value="<?= htmlspecialchars(
                                        $status_label
                                    ) ?>"
                            readonly
                            disabled
                            class="w-full
                               px-4
                               py-2
                               border
                               rounded-lg
                               read-only-field
                               <?= htmlspecialchars(
                                    $status_color_class
                                ) ?>">

                    </div>


                </div>


                <!-- =============================================
                 SAVE
            ============================================== -->

                <button
                    type="submit"
                    class="w-full
                       flex
                       justify-center
                       items-center
                       py-2.5
                       px-4
                       border
                       border-transparent
                       rounded-lg
                       shadow-md
                       text-sm
                       font-bold
                       text-white
                       bg-emerald-600
                       hover:bg-emerald-700
                       focus:outline-none
                       focus:ring-4
                       focus:ring-offset-2
                       focus:ring-emerald-500/50
                       transition
                       duration-200
                       mt-6">

                    <i
                        class="fas fa-save mr-2"></i>

                    SAVE PROFILE CHANGES

                </button>


            </form>


            <!-- ================================================
             BACK
        ================================================= -->

            <div
                class="mt-6
                   text-center
                   border-t
                   pt-4">

                <a
                    href="<?= htmlspecialchars(
                                $dashboard_link
                            ) ?>"
                    class="text-sm
                       text-emerald-600
                       hover:text-emerald-800
                       font-medium
                       transition
                       duration-150">

                    <i
                        class="fas fa-arrow-left mr-1"></i>

                    Back to Dashboard

                </a>

            </div>


        </div>

    </div>


    <!-- =========================================================
     IMAGE PREVIEW
========================================================= -->

    <script>
        const profileInput =
            document.getElementById(
                'profile_pic_file'
            );


        if (profileInput) {

            profileInput.addEventListener(
                'change',
                function() {

                    const file =
                        this.files[0];


                    if (!file) {
                        return;
                    }


                    const maxSize =
                        5 * 1024 * 1024;


                    if (
                        file.size > maxSize
                    ) {

                        alert(
                            'Profile picture must be less than 5MB.'
                        );


                        this.value =
                            '';

                        return;
                    }


                    const allowedTypes = [
                        'image/jpeg',
                        'image/png',
                        'image/gif',
                        'image/webp'
                    ];


                    if (
                        !allowedTypes.includes(
                            file.type
                        )
                    ) {

                        alert(
                            'Only JPG, JPEG, PNG, GIF and WEBP files are allowed.'
                        );


                        this.value =
                            '';

                        return;
                    }


                    const reader =
                        new FileReader();


                    reader.onload =
                        function(e) {

                            const img =
                                document.querySelector(
                                    'img[alt="Profile Picture"]'
                                );


                            if (img) {

                                img.src =
                                    e.target.result;
                            }
                        };


                    reader.readAsDataURL(
                        file
                    );

                }
            );

        }
    </script>

</body>

</html>