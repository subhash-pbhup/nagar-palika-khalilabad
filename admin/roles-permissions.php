<?php

session_start();

include "./include/header.php";
include "./include/sidebar.php";
include "./include/db.php";


// =========================================================
// LOGIN CHECK
// =========================================================

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


// =========================================================
// CREATE ROLE
// =========================================================

if (isset($_POST['create_role'])) {

    $role_name  = trim($_POST['role_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($role_name === '') {

        $_SESSION['error'] = "Role name is required.";
    } else {

        $check = $conn->prepare("
            SELECT id
            FROM roles
            WHERE role_name = ?
            LIMIT 1
        ");

        $check->bind_param("s", $role_name);
        $check->execute();

        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {

            $_SESSION['error'] = "This role already exists.";
        } else {

            $stmt = $conn->prepare("
                INSERT INTO roles
                (role_name, description, status)
                VALUES (?, ?, 1)
            ");

            $stmt->bind_param(
                "ss",
                $role_name,
                $description
            );

            if ($stmt->execute()) {

                $_SESSION['success'] = "Role created successfully.";
            } else {

                $_SESSION['error'] = "Unable to create role.";
            }
        }
    }

    // header("Location: roles-permissions.php");

    echo "<script>
    window.location.href = 'roles-permissions.php';
</script>";
}


// =========================================================
// SAVE PERMISSIONS
// =========================================================

if (isset($_POST['save_permissions'])) {

    $role_id = (int)($_POST['role_id'] ?? 0);

    $permissions = $_POST['permissions'] ?? [];


    if ($role_id <= 0) {

        $_SESSION['error'] = "Please select a role.";
    } else {

        // Get role name
        $role_stmt = $conn->prepare("
            SELECT role_name
            FROM roles
            WHERE id = ?
            LIMIT 1
        ");

        $role_stmt->bind_param("i", $role_id);
        $role_stmt->execute();

        $role_result = $role_stmt->get_result();

        $role_data = $role_result->fetch_assoc();


        // ADMIN permissions are automatically full access
        if (
            $role_data &&
            strtoupper($role_data['role_name']) === 'ADMIN'
        ) {

            $_SESSION['success'] =
                "ADMIN automatically has full access.";
        } else {

            $conn->begin_transaction();

            try {

                // Remove old permissions
                $delete = $conn->prepare("
                    DELETE FROM role_permissions
                    WHERE role_id = ?
                ");

                $delete->bind_param(
                    "i",
                    $role_id
                );

                $delete->execute();


                // Insert selected permissions
                if (!empty($permissions)) {

                    $insert = $conn->prepare("
                        INSERT INTO role_permissions
                        (role_id, permission_id)
                        VALUES (?, ?)
                    ");

                    foreach ($permissions as $permission_id) {

                        $permission_id = (int)$permission_id;

                        if ($permission_id > 0) {

                            $insert->bind_param(
                                "ii",
                                $role_id,
                                $permission_id
                            );

                            $insert->execute();
                        }
                    }
                }


                $conn->commit();

                $_SESSION['success'] =
                    "Permissions updated successfully.";
            } catch (Exception $e) {

                $conn->rollback();

                $_SESSION['error'] =
                    "Unable to update permissions.";
            }
        }
    }


    header(
        "Location: roles-permissions.php?role_id=" . $role_id
    );

    exit;
}


// =========================================================
// ALERTS
// =========================================================

$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';

unset($_SESSION['success']);
unset($_SESSION['error']);


// =========================================================
// FETCH ROLES
// =========================================================

$roles = [];

$roles_result = $conn->query("
    SELECT
        id,
        role_name,
        description,
        status
    FROM roles
    ORDER BY
        CASE
            WHEN role_name = 'ADMIN' THEN 1
            ELSE 2
        END,
        role_name ASC
");

if ($roles_result) {

    while ($row = $roles_result->fetch_assoc()) {

        $roles[] = $row;
    }
}


// =========================================================
// SELECTED ROLE
// =========================================================

$selected_role = (int)($_GET['role_id'] ?? 0);


if ($selected_role <= 0 && !empty($roles)) {

    $selected_role = (int)$roles[0]['id'];
}


// =========================================================
// SELECTED ROLE INFORMATION
// =========================================================

$selected_role_data = null;

if ($selected_role > 0) {

    $stmt = $conn->prepare("
        SELECT
            id,
            role_name,
            description,
            status
        FROM roles
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param(
        "i",
        $selected_role
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $selected_role_data = $result->fetch_assoc();
}


// =========================================================
// FETCH PERMISSIONS
// =========================================================

$permissions = [];

$permission_result = $conn->query("
    SELECT
        id,
        module_name,
        permission_name,
        permission_key
    FROM permissions
    ORDER BY
        module_name ASC,
        id ASC
");

if ($permission_result) {

    while ($row = $permission_result->fetch_assoc()) {

        $permissions[] = $row;
    }
}


// =========================================================
// GROUP PERMISSIONS BY MODULE
// =========================================================

$grouped_permissions = [];

foreach ($permissions as $permission) {

    $module = $permission['module_name'];

    if (!isset($grouped_permissions[$module])) {

        $grouped_permissions[$module] = [];
    }

    $grouped_permissions[$module][] = $permission;
}


// =========================================================
// GET SELECTED ROLE PERMISSIONS
// =========================================================

$role_permissions = [];

if ($selected_role > 0) {

    $stmt = $conn->prepare("
        SELECT permission_id
        FROM role_permissions
        WHERE role_id = ?
    ");

    $stmt->bind_param(
        "i",
        $selected_role
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $role_permissions[] =
            (int)$row['permission_id'];
    }
}


// =========================================================
// ADMIN CHECK
// =========================================================

$is_admin_role = false;

if (
    $selected_role_data &&
    strtoupper($selected_role_data['role_name']) === 'ADMIN'
) {

    $is_admin_role = true;
}

?>

<style>
    /* =====================================================
       KHALILABAD THEME
    ===================================================== */

    :root {

        --orange: #f47c00;
        --orange-dark: #e66f00;
        --orange-light: #fff3e6;

        --navy: #102452;
        --navy-dark: #0b193c;

        --cream: #fbf8f4;

        --border: #f3dfc8;

        --muted: #71809a;

        --green: #10a968;

    }


    body {
        background: var(--cream);
    }


    /* =====================================================
       PAGE
    ===================================================== */

    .roles-page {

        min-height: 100vh;

        background:
            radial-gradient(circle at top right,
                rgba(244, 124, 0, 0.045),
                transparent 30%),
            #fbf8f4;
    }


    /* =====================================================
       HEADER
    ===================================================== */

    .roles-header {

        background: #ffffff;

        border: 1px solid var(--border);

        border-radius: 22px;

        padding: 25px;

        box-shadow:
            0 8px 25px rgba(16, 36, 82, 0.035);
    }


    .roles-title {

        color: var(--navy);

        font-size: 25px;

        font-weight: 800;
    }


    .roles-description {

        color: var(--muted);

        font-size: 14px;

        margin-top: 6px;
    }


    /* =====================================================
       ADD ROLE BUTTON
    ===================================================== */

    .add-role-btn {

        display: inline-flex;

        align-items: center;

        gap: 8px;

        background: var(--orange);

        color: #ffffff;

        border: none;

        border-radius: 13px;

        padding: 12px 19px;

        font-size: 13px;

        font-weight: 700;

        cursor: pointer;

        box-shadow:
            0 7px 16px rgba(244, 124, 0, 0.18);

        transition: 0.2s ease;
    }


    .add-role-btn:hover {

        background: var(--orange-dark);

        transform: translateY(-1px);
    }


    /* =====================================================
       MAIN GRID
    ===================================================== */

    .roles-grid {

        display: grid;

        grid-template-columns: 285px 1fr;

        gap: 20px;
    }


    /* =====================================================
       COMMON CARD
    ===================================================== */

    .role-card,
    .permission-card {

        background: #ffffff;

        border: 1px solid var(--border);

        border-radius: 22px;

        box-shadow:
            0 8px 25px rgba(16, 36, 82, 0.035);
    }


    /* =====================================================
       ROLE LIST
    ===================================================== */

    .role-card-header {

        padding: 21px;

        border-bottom: 1px solid #f2e5d7;
    }


    .role-card-title {

        color: var(--navy);

        font-size: 17px;

        font-weight: 800;
    }


    .role-card-description {

        color: var(--muted);

        font-size: 11px;

        margin-top: 5px;
    }


    .roles-list {

        padding: 10px;
    }


    .role-item {

        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 10px;

        padding: 11px;

        border-radius: 13px;

        margin-bottom: 3px;

        text-decoration: none;

        color: var(--muted);

        transition: 0.2s ease;
    }


    .role-item:hover {

        background: #fff8f0;

        color: var(--orange);
    }


    .role-item.active {

        background: var(--navy);

        color: #ffffff;

        box-shadow:
            0 5px 14px rgba(16, 36, 82, 0.12);
    }


    .role-left {

        display: flex;

        align-items: center;

        gap: 10px;

        min-width: 0;
    }


    .role-icon {

        width: 37px;

        height: 37px;

        border-radius: 10px;

        display: flex;

        align-items: center;

        justify-content: center;

        background: #fff3e6;

        color: var(--orange);

        flex-shrink: 0;
    }


    .role-item.active .role-icon {

        background: rgba(255, 255, 255, 0.1);

        color: #ffffff;
    }


    .role-name {

        font-size: 12px;

        font-weight: 800;

        line-height: 1.3;
    }


    .role-description {

        font-size: 10px;

        opacity: 0.65;

        margin-top: 2px;

        white-space: nowrap;

        overflow: hidden;

        text-overflow: ellipsis;

        max-width: 170px;
    }


    .role-arrow {

        font-size: 17px;

        flex-shrink: 0;
    }


    /* =====================================================
       PERMISSION HEADER
    ===================================================== */

    .permission-header {

        padding: 21px;

        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 15px;

        border-bottom: 1px solid #f2e5d7;
    }


    .selected-role-name {

        color: var(--navy);

        font-size: 19px;

        font-weight: 800;
    }


    .selected-role-description {

        color: var(--muted);

        font-size: 11px;

        margin-top: 4px;
    }


    .full-access-badge {

        background: #effdf6;

        border: 1px solid #c9f3df;

        color: #10a968;

        padding: 7px 12px;

        border-radius: 20px;

        font-size: 10px;

        font-weight: 800;

        white-space: nowrap;
    }


    /* =====================================================
       SELECT ALL BAR
    ===================================================== */

    .select-all-bar {

        background: #fffaf5;

        border-bottom: 1px solid #f2e5d7;

        padding: 13px 21px;

        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 10px;
    }


    .select-all-label {

        display: flex;

        align-items: center;

        gap: 8px;

        color: var(--navy);

        font-size: 12px;

        font-weight: 800;

        cursor: pointer;
    }


    /* =====================================================
       PERMISSION CONTENT
    ===================================================== */

    .permission-content {

        padding: 18px 21px 21px;
    }


    .permission-module {

        border: 1px solid #edf0f4;

        border-radius: 15px;

        overflow: hidden;

        margin-bottom: 13px;

        background: #ffffff;
    }


    .permission-module:last-child {

        margin-bottom: 0;
    }


    .module-header {

        background: #fffaf5;

        border-bottom: 1px solid #f2e5d7;

        padding: 12px 15px;

        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 10px;
    }


    .module-name {

        display: flex;

        align-items: center;

        gap: 8px;

        color: var(--navy);

        font-size: 13px;

        font-weight: 800;
    }


    .module-name i {

        color: var(--orange);

        font-size: 18px;
    }


    .module-select-label {

        display: flex;

        align-items: center;

        gap: 5px;

        color: var(--muted);

        font-size: 10px;

        font-weight: 700;

        cursor: pointer;
    }


    .permission-list {

        padding: 12px;

        display: grid;

        grid-template-columns:
            repeat(3, minmax(0, 1fr));

        gap: 8px;
    }


    .permission-item {

        display: flex;

        align-items: center;

        gap: 9px;

        padding: 10px;

        border: 1px solid #edf0f4;

        border-radius: 10px;

        cursor: pointer;

        transition: 0.15s ease;
    }


    .permission-item:hover {

        background: #fffaf5;

        border-color: #f3d7b9;
    }


    .permission-item input {

        width: 16px;

        height: 16px;

        accent-color: var(--orange);

        cursor: pointer;

        flex-shrink: 0;
    }


    .permission-name {

        color: #4f607a;

        font-size: 11px;

        font-weight: 700;
    }


    .permission-key {

        color: #a0aabd;

        font-size: 9px;

        margin-top: 2px;

        word-break: break-all;
    }


    /* =====================================================
       SAVE BAR
    ===================================================== */

    .save-bar {

        padding: 16px 21px;

        border-top: 1px solid #f2e5d7;

        display: flex;

        justify-content: flex-end;

        align-items: center;

        gap: 12px;
    }


    .save-btn {

        background: var(--navy);

        color: #ffffff;

        border: none;

        border-radius: 12px;

        padding: 11px 20px;

        font-size: 12px;

        font-weight: 800;

        cursor: pointer;

        transition: 0.2s ease;
    }


    .save-btn:hover {

        background: var(--navy-dark);

        transform: translateY(-1px);
    }


    .admin-info {

        color: var(--muted);

        font-size: 11px;
    }


    /* =====================================================
       ALERT
    ===================================================== */

    .alert-success {

        background: #effdf6;

        border: 1px solid #c9f3df;

        color: #15915d;

        border-radius: 13px;

        padding: 12px 15px;

        font-size: 12px;

        font-weight: 700;

        margin-bottom: 18px;
    }


    .alert-error {

        background: #fff2f2;

        border: 1px solid #ffd0d0;

        color: #d64e58;

        border-radius: 13px;

        padding: 12px 15px;

        font-size: 12px;

        font-weight: 700;

        margin-bottom: 18px;
    }


    /* =====================================================
       MODAL
    ===================================================== */

    .role-modal {

        position: fixed;

        inset: 0;

        background: rgba(16, 36, 82, 0.35);

        backdrop-filter: blur(5px);

        display: none;

        align-items: center;

        justify-content: center;

        padding: 20px;

        z-index: 9999;
    }


    .role-modal.show {

        display: flex;
    }


    .modal-box {

        width: 100%;

        max-width: 440px;

        background: #ffffff;

        border-radius: 20px;

        border: 1px solid var(--border);

        box-shadow:
            0 25px 70px rgba(16, 36, 82, 0.18);

        overflow: hidden;
    }


    .modal-header {

        padding: 19px 20px;

        border-bottom: 1px solid #f2e5d7;

        display: flex;

        align-items: center;

        justify-content: space-between;
    }


    .modal-title {

        color: var(--navy);

        font-size: 17px;

        font-weight: 800;
    }


    .modal-close {

        width: 32px;

        height: 32px;

        border: none;

        background: #fff3e6;

        color: var(--orange);

        border-radius: 9px;

        cursor: pointer;

        font-size: 18px;
    }


    .modal-body {

        padding: 20px;
    }


    .form-label {

        display: block;

        color: var(--navy);

        font-size: 12px;

        font-weight: 800;

        margin-bottom: 6px;
    }


    .form-input {

        width: 100%;

        border: 1px solid #dce3ee;

        border-radius: 11px;

        padding: 11px 13px;

        font-size: 12px;

        outline: none;

        color: var(--navy);

        transition: 0.2s ease;
    }


    .form-input:focus {

        border-color: var(--orange);

        box-shadow:
            0 0 0 4px rgba(244, 124, 0, 0.08);
    }


    .modal-footer {

        padding: 15px 20px;

        border-top: 1px solid #f2e5d7;

        display: flex;

        justify-content: flex-end;

        gap: 8px;
    }


    .cancel-btn {

        border: 1px solid #dce3ee;

        background: #ffffff;

        color: var(--muted);

        border-radius: 11px;

        padding: 10px 17px;

        font-size: 12px;

        font-weight: 700;

        cursor: pointer;
    }


    .create-btn {

        border: none;

        background: var(--orange);

        color: #ffffff;

        border-radius: 11px;

        padding: 10px 18px;

        font-size: 12px;

        font-weight: 800;

        cursor: pointer;
    }


    /* =====================================================
       RESPONSIVE
    ===================================================== */

    @media (max-width: 1100px) {

        .roles-grid {

            grid-template-columns: 240px 1fr;
        }

        .permission-list {

            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }
    }


    @media (max-width: 800px) {

        .roles-grid {

            grid-template-columns: 1fr;
        }

        .permission-list {

            grid-template-columns: 1fr;
        }

        .permission-header {

            align-items: flex-start;

            flex-direction: column;
        }

    }


    @media (max-width: 600px) {

        .roles-header {

            padding: 19px;
        }

        .roles-header-content {

            flex-direction: column;

            align-items: stretch;
        }

        .add-role-btn {

            justify-content: center;
        }

        .permission-content {

            padding: 13px;
        }

    }
</style>


<div class="roles-page p-4 md:p-6 lg:p-8">


    <!-- =========================================================
         PAGE HEADER
    ========================================================== -->

    <div class="roles-header mb-5">

        <div class="roles-header-content flex items-center justify-between gap-5">

            <div>

                <h1 class="roles-title">
                    Roles & Permissions
                </h1>

                <p class="roles-description">
                    Manage roles and control system access for Khalilabad Nagar Palika.
                </p>

            </div>


            <?php if (hasPermission('roles.add') || $is_admin_role): ?>

                <button
                    type="button"
                    onclick="openRoleModal()"
                    class="add-role-btn">

                    <i class='bx bx-plus text-lg'></i>

                    Add New Role

                </button>

            <?php endif; ?>

        </div>

    </div>


    <!-- =========================================================
         ALERTS
    ========================================================== -->

    <?php if ($success): ?>

        <div class="alert-success">

            <i class='bx bx-check-circle mr-1'></i>

            <?= htmlspecialchars($success) ?>

        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="alert-error">

            <i class='bx bx-error-circle mr-1'></i>

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <!-- =========================================================
         MAIN GRID
    ========================================================== -->

    <div class="roles-grid">


        <!-- =====================================================
             LEFT : ROLES
        ====================================================== -->

        <div class="role-card">


            <div class="role-card-header">

                <div class="role-card-title">
                    Roles
                </div>

                <div class="role-card-description">
                    Select a role to manage permissions
                </div>

            </div>


            <div class="roles-list">


                <?php if (!empty($roles)): ?>


                    <?php foreach ($roles as $role): ?>

                        <?php

                        $is_selected =
                            ((int)$role['id'] === $selected_role);

                        ?>


                        <a
                            href="?role_id=<?= (int)$role['id'] ?>"
                            class="role-item <?= $is_selected ? 'active' : '' ?>">


                            <div class="role-left">


                                <div class="role-icon">

                                    <i class='bx bx-shield'></i>

                                </div>


                                <div>

                                    <div class="role-name">

                                        <?= htmlspecialchars(
                                            $role['role_name']
                                        ) ?>

                                    </div>


                                    <?php if (!empty($role['description'])): ?>

                                        <div class="role-description">

                                            <?= htmlspecialchars(
                                                $role['description']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                </div>


                            </div>


                            <i class='bx bx-chevron-right role-arrow'></i>


                        </a>


                    <?php endforeach; ?>


                <?php else: ?>


                    <div class="text-center py-8 text-slate-400 text-xs">

                        No roles found.

                    </div>


                <?php endif; ?>


            </div>

        </div>


        <!-- =====================================================
             RIGHT : PERMISSIONS
        ====================================================== -->

        <div class="permission-card">


            <?php if ($selected_role_data): ?>


                <!-- HEADER -->

                <div class="permission-header">


                    <div>

                        <div class="selected-role-name">

                            <?= htmlspecialchars(
                                $selected_role_data['role_name']
                            ) ?>

                        </div>


                        <div class="selected-role-description">

                            <?=
                            !empty($selected_role_data['description'])
                                ? htmlspecialchars(
                                    $selected_role_data['description']
                                )
                                : 'Configure access permissions for this role.'
                            ?>

                        </div>

                    </div>


                    <?php if ($is_admin_role): ?>

                        <span class="full-access-badge">

                            <i class='bx bx-check-circle mr-1'></i>

                            FULL ACCESS

                        </span>

                    <?php endif; ?>


                </div>


                <!-- SELECT ALL -->

                <div class="select-all-bar">


                    <label class="select-all-label">

                        <input
                            type="checkbox"
                            id="selectAll"
                            onchange="toggleAllPermissions(this)">

                        Select All Permissions

                    </label>


                    <span class="text-[10px] text-slate-400">

                        <?= count($permissions) ?> permissions

                    </span>


                </div>


                <!-- FORM -->

                <form method="POST">


                    <input
                        type="hidden"
                        name="role_id"
                        value="<?= $selected_role ?>">


                    <!-- PERMISSION CONTENT -->

                    <div class="permission-content">


                        <?php if (!empty($grouped_permissions)): ?>


                            <?php foreach (
                                $grouped_permissions as $module => $module_permissions
                            ): ?>


                                <?php

                                $module_id =
                                    'module_' .
                                    md5($module);

                                ?>


                                <div class="permission-module">


                                    <!-- MODULE HEADER -->

                                    <div class="module-header">


                                        <div class="module-name">

                                            <i class='bx bx-folder'></i>

                                            <?= htmlspecialchars($module) ?>

                                        </div>


                                        <label class="module-select-label">

                                            <input
                                                type="checkbox"
                                                class="module-master"
                                                data-module="<?= $module_id ?>"
                                                onchange="toggleModule(this)">

                                            Select All

                                        </label>


                                    </div>


                                    <!-- PERMISSIONS -->

                                    <div class="permission-list">


                                        <?php foreach (
                                            $module_permissions as $permission
                                        ): ?>


                                            <label class="permission-item">


                                                <input
                                                    type="checkbox"
                                                    name="permissions[]"
                                                    value="<?= (int)$permission['id'] ?>"
                                                    class="permission-checkbox <?= $module_id ?>"
                                                    <?= (
                                                        $is_admin_role ||
                                                        in_array(
                                                            (int)$permission['id'],
                                                            $role_permissions,
                                                            true
                                                        )
                                                    )
                                                        ? 'checked'
                                                        : ''
                                                    ?>>


                                                <div>

                                                    <div class="permission-name">

                                                        <?= htmlspecialchars(
                                                            $permission['permission_name']
                                                        ) ?>

                                                    </div>


                                                    <div class="permission-key">

                                                        <?= htmlspecialchars(
                                                            $permission['permission_key']
                                                        ) ?>

                                                    </div>

                                                </div>


                                            </label>


                                        <?php endforeach; ?>


                                    </div>


                                </div>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <div class="text-center py-12 text-slate-400">

                                <i class='bx bx-lock-alt text-4xl mb-2'></i>

                                <div class="text-sm font-bold text-slate-700">
                                    No permissions found
                                </div>

                                <div class="text-xs mt-1">
                                    Add permissions to the permissions table first.
                                </div>

                            </div>


                        <?php endif; ?>


                    </div>


                    <!-- SAVE -->

                    <div class="save-bar">


                        <?php if ($is_admin_role): ?>

                            <span class="admin-info">

                                <i class='bx bx-info-circle mr-1'></i>

                                ADMIN automatically has full access.

                            </span>


                        <?php else: ?>


                            <?php if (
                                hasPermission('roles.manage_permissions') ||
                                hasPermission('roles.edit')
                            ): ?>

                                <button
                                    type="submit"
                                    name="save_permissions"
                                    class="save-btn">

                                    <i class='bx bx-save mr-1'></i>

                                    Save Permissions

                                </button>

                            <?php endif; ?>


                        <?php endif; ?>


                    </div>


                </form>


            <?php else: ?>


                <div class="text-center py-20 text-slate-400">

                    <i class='bx bx-shield-x text-5xl mb-3'></i>

                    <div class="text-sm font-bold text-slate-700">
                        No Role Selected
                    </div>

                    <div class="text-xs mt-1">
                        Select a role from the left side.
                    </div>

                </div>


            <?php endif; ?>


        </div>


    </div>


</div>


<!-- =========================================================
     ADD ROLE MODAL
========================================================== -->

<div
    id="roleModal"
    class="role-modal">


    <div class="modal-box">


        <!-- HEADER -->

        <div class="modal-header">


            <div class="modal-title">

                Add New Role

            </div>


            <button
                type="button"
                onclick="closeRoleModal()"
                class="modal-close">

                <i class='bx bx-x'></i>

            </button>


        </div>


        <!-- FORM -->

        <form method="POST">


            <div class="modal-body">


                <div class="mb-4">


                    <label class="form-label">
                        Role Name
                    </label>


                    <input
                        type="text"
                        name="role_name"
                        class="form-input"
                        placeholder="Example: TAX COLLECTOR"
                        required>


                </div>


                <div>


                    <label class="form-label">
                        Description
                    </label>


                    <textarea
                        name="description"
                        rows="3"
                        class="form-input"
                        placeholder="Enter role description"></textarea>


                </div>


            </div>


            <!-- FOOTER -->

            <div class="modal-footer">


                <button
                    type="button"
                    onclick="closeRoleModal()"
                    class="cancel-btn">

                    Cancel

                </button>


                <button
                    type="submit"
                    name="create_role"
                    class="create-btn">

                    <i class='bx bx-plus mr-1'></i>

                    Create Role

                </button>


            </div>


        </form>


    </div>

</div>


<script>
    // =========================================================
    // OPEN MODAL
    // =========================================================

    function openRoleModal() {

        const modal =
            document.getElementById("roleModal");

        if (modal) {

            modal.classList.add("show");
        }
    }


    // =========================================================
    // CLOSE MODAL
    // =========================================================

    function closeRoleModal() {

        const modal =
            document.getElementById("roleModal");

        if (modal) {

            modal.classList.remove("show");
        }
    }


    // =========================================================
    // CLOSE MODAL ON BACKDROP CLICK
    // =========================================================

    document.addEventListener(
        "click",
        function(event) {

            const modal =
                document.getElementById("roleModal");

            if (
                modal &&
                event.target === modal
            ) {

                closeRoleModal();
            }

        }
    );


    // =========================================================
    // SELECT ALL PERMISSIONS
    // =========================================================

    function toggleAllPermissions(master) {

        const checkboxes =
            document.querySelectorAll(
                ".permission-checkbox"
            );


        checkboxes.forEach(function(checkbox) {

            checkbox.checked =
                master.checked;

        });


        document
            .querySelectorAll(".module-master")
            .forEach(function(checkbox) {

                checkbox.checked =
                    master.checked;

            });

    }


    // =========================================================
    // MODULE SELECT ALL
    // =========================================================

    function toggleModule(master) {

        const moduleClass =
            master.getAttribute(
                "data-module"
            );


        const checkboxes =
            document.querySelectorAll(
                "." + moduleClass
            );


        checkboxes.forEach(function(checkbox) {

            checkbox.checked =
                master.checked;

        });


        updateMainSelectAll();
    }


    // =========================================================
    // UPDATE CHECKBOX STATES
    // =========================================================

    function updateCheckboxStates() {


        document
            .querySelectorAll(".module-master")
            .forEach(function(master) {


                const moduleClass =
                    master.getAttribute(
                        "data-module"
                    );


                const checkboxes =
                    document.querySelectorAll(
                        "." + moduleClass
                    );


                if (!checkboxes.length) {
                    return;
                }


                const checked =
                    document.querySelectorAll(
                        "." + moduleClass + ":checked"
                    ).length;


                master.checked =
                    checked === checkboxes.length;

            });


        updateMainSelectAll();
    }


    // =========================================================
    // MAIN SELECT ALL STATE
    // =========================================================

    function updateMainSelectAll() {


        const all =
            document.querySelectorAll(
                ".permission-checkbox"
            );


        const checked =
            document.querySelectorAll(
                ".permission-checkbox:checked"
            );


        const selectAll =
            document.getElementById(
                "selectAll"
            );


        if (!selectAll) {
            return;
        }


        selectAll.checked =
            all.length > 0 &&
            all.length === checked.length;
    }


    // =========================================================
    // INITIAL CHECK
    // =========================================================

    document.addEventListener(
        "DOMContentLoaded",
        function() {

            updateCheckboxStates();

        }
    );
</script>


<?php include "./include/footer.php"; ?>