<?php
ob_start();
session_start();
require_once "include/header.php";

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

/*
|--------------------------------------------------------------------------
| VERIFICATION HISTORY TABLE
|--------------------------------------------------------------------------
*/
$historyTableSql = "
    CREATE TABLE IF NOT EXISTS assessment_verifications (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        assessment_id INT NOT NULL,
        user_id INT NOT NULL,
        role_id INT NOT NULL,
        verification_step INT NOT NULL DEFAULT 0,
        action VARCHAR(30) NOT NULL,
        remark TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_assessment_id (assessment_id),
        KEY idx_user_id (user_id),
        KEY idx_role_id (role_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

if (!$conn->query($historyTableSql)) {
    die("Unable to create verification history table: " . $conn->error);
}

$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($user_id <= 0) {
    header("Location: index.php");
    exit;
}

function normalizeRoleName($role)
{
    $role = strtoupper(trim((string)$role));
    $role = preg_replace('/\s+/', ' ', $role);

    $aliases = [
        'ADMINISTRATOR' => 'ADMIN',
        'SUPER ADMIN' => 'ADMIN',
        'TAX SUPRINTENDENT / KNA' => 'TAX SUPERINTENDENT / KNA',
        'TAX SUPRINTENDENT/KNA' => 'TAX SUPERINTENDENT / KNA',
        'TAX SUPERINTENDENT/KNA' => 'TAX SUPERINTENDENT / KNA'
    ];

    return $aliases[$role] ?? $role;
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function statusBadge($status)
{
    $status = strtolower(trim($status));

    if ($status === 'approved') {
        return '<span class="badge approved">✓ Approved</span>';
    }

    if ($status === 'reject') {
        return '<span class="badge rejected">✕ Rejected</span>';
    }

    return '<span class="badge pending">● Pending</span>';
}

/*
|--------------------------------------------------------------------------
| CURRENT USER - DATABASE IS SOURCE OF TRUTH
|--------------------------------------------------------------------------
*/
$userStmt = $conn->prepare("
    SELECT
        u.id,
        u.username,
        u.name,
        u.email,
        u.role_id,
        r.role_name,
        r.status AS role_status
    FROM users u
    LEFT JOIN roles r ON r.id = u.role_id
    WHERE u.id = ?
      AND u.status = 'active'
    LIMIT 1
");

if (!$userStmt) {
    die("Unable to load user: " . $conn->error);
}

$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$currentUser = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$currentUser) {
    die("Active user not found.");
}

$currentRoleId = (int)($currentUser['role_id'] ?? 0);
$currentRole = normalizeRoleName($currentUser['role_name'] ?? '');
$isAdmin = ($currentRole === 'ADMIN');

/*
|--------------------------------------------------------------------------
| DYNAMIC VERIFICATION WORKFLOW
|--------------------------------------------------------------------------
*/
$hasVerificationOrder = false;

$columnCheck = $conn->query("SHOW COLUMNS FROM roles LIKE 'verification_order'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasVerificationOrder = true;
}

if ($hasVerificationOrder) {
    $rolesResult = $conn->query("
        SELECT id, role_name, verification_order
        FROM roles
        WHERE (status = 1 OR status = '1' OR LOWER(TRIM(status)) = 'active')
        ORDER BY id ASC
    ");
} else {
    $rolesResult = $conn->query("
        SELECT id, role_name, 0 AS verification_order
        FROM roles
        WHERE (status = 1 OR status = '1' OR LOWER(TRIM(status)) = 'active')
        ORDER BY id ASC
    ");
}

if (!$rolesResult) {
    die("Unable to load verification roles: " . $conn->error);
}

$verificationRoles = [];
while ($r = $rolesResult->fetch_assoc()) {
    $verificationRoles[] = [
        'id' => (int)$r['id'],
        'name' => normalizeRoleName($r['role_name']),
        'db_name' => $r['role_name'],
        'order' => (int)($r['verification_order'] ?? 0)
    ];
}

$hasPositiveOrder = false;
foreach ($verificationRoles as $role) {
    if ($role['name'] !== 'ADMIN' && $role['order'] > 0) {
        $hasPositiveOrder = true;
        break;
    }
}

usort($verificationRoles, function ($a, $b) use ($hasPositiveOrder) {
    $aAdmin = ($a['name'] === 'ADMIN');
    $bAdmin = ($b['name'] === 'ADMIN');

    if ($aAdmin && !$bAdmin) return 1;
    if (!$aAdmin && $bAdmin) return -1;

    if (!$hasPositiveOrder) {
        return $b['id'] <=> $a['id'];
    }

    if ($a['order'] !== $b['order']) {
        return $a['order'] <=> $b['order'];
    }

    return $a['id'] <=> $b['id'];
});

$rolePosition = [];
$roleIdMap = [];

foreach ($verificationRoles as $i => $role) {
    $rolePosition[$role['id']] = $i;
    $roleIdMap[$role['name']] = $role['id'];
}

if (empty($verificationRoles)) {
    die("No active verification roles configured.");
}

$firstVerificationRoleId = 0;
foreach ($verificationRoles as $role) {
    if ($role['name'] !== 'ADMIN') {
        $firstVerificationRoleId = (int)$role['id'];
        break;
    }
}

if ($firstVerificationRoleId <= 0) {
    die("No active non-admin verification role configured.");
}

/*
|--------------------------------------------------------------------------
| ASSESSMENT
|--------------------------------------------------------------------------
*/
$assessment_id = (int)($_GET['id'] ?? $_POST['assessment_id'] ?? 0);

if ($assessment_id <= 0) {
    die("Invalid assessment ID.");
}

$stmt = $conn->prepare("
    SELECT
        a.*,
        u.name AS creator_name,
        cr.role_name AS creator_role_name,
        vr.role_name AS current_role_name
    FROM assessments a
    LEFT JOIN users u
        ON u.id = a.created_by
    LEFT JOIN roles cr 
        ON cr.id = u.role_id
    LEFT JOIN roles vr
        ON vr.id = a.current_verification_role_id
    WHERE a.id = ?
      AND a.is_deleted = 0
    LIMIT 1
");

if (!$stmt) {
    die("Prepare Error: " . $conn->error);
}

$stmt->bind_param("i", $assessment_id);
$stmt->execute();
$assessment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$assessment) {
    die("Assessment not found.");
}

$status = strtolower(trim($assessment['verification_status'] ?? 'pending'));

if (!in_array($status, ['pending', 'reject', 'approved'], true)) {
    $status = 'pending';
}

$currentVerificationRoleId = (int)($assessment['current_verification_role_id'] ?? 0);
$currentRequiredRole = normalizeRoleName($assessment['current_role_name'] ?? '');
$currentIndex = $rolePosition[$currentVerificationRoleId] ?? -1;
$currentUserRoleId = $currentRoleId;

/*
|--------------------------------------------------------------------------
| REPAIR OLD PENDING RECORDS
|--------------------------------------------------------------------------
*/
if ($status === 'pending') {
    $histCountStmt = $conn->prepare("SELECT COUNT(*) AS total FROM assessment_verifications WHERE assessment_id = ?");
    if ($histCountStmt) {
        $histCountStmt->bind_param("i", $assessment_id);
        $histCountStmt->execute();
        $histCountRow = $histCountStmt->get_result()->fetch_assoc();
        $histCountStmt->close();

        $historyCount = (int)($histCountRow['total'] ?? 0);

        if ($historyCount === 0 && $currentVerificationRoleId <= 0) {
            $repairStmt = $conn->prepare("UPDATE assessments SET current_verification_role_id = ?, updated_at = NOW() WHERE id = ? AND verification_status = 'pending'");
            if ($repairStmt) {
                $repairStmt->bind_param("ii", $firstVerificationRoleId, $assessment_id);
                $repairStmt->execute();
                $repairStmt->close();

                $currentVerificationRoleId = $firstVerificationRoleId;
                $currentIndex = $rolePosition[$firstVerificationRoleId] ?? -1;
                $currentRequiredRole = normalizeRoleName($verificationRoles[$currentIndex]['name'] ?? '');
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| PERMISSION & BUTTON LOGIC FIX
|--------------------------------------------------------------------------
*/
$roleAlreadyActed = false;

if ($currentRoleId > 0) {
    $actedStmt = $conn->prepare("
        SELECT id FROM assessment_verifications
        WHERE assessment_id = ? AND role_id = ? AND action IN ('verified', 'approved')
        ORDER BY id DESC LIMIT 1
    ");
    if ($actedStmt) {
        $actedStmt->bind_param("ii", $assessment_id, $currentRoleId);
        $actedStmt->execute();
        $roleAlreadyActed = !empty($actedStmt->get_result()->fetch_assoc());
        $actedStmt->close();
    }
}

$isAssignedToCurrentUser = ($currentRoleId > 0 && $currentVerificationRoleId === $currentRoleId);

$canReject = ($status === 'pending' && ($isAssignedToCurrentUser || $isAdmin) && !$roleAlreadyActed);
$canForward = ($status === 'pending' && $isAssignedToCurrentUser && !$isAdmin && !$roleAlreadyActed);
$canApprove = ($status === 'pending' && $isAdmin && $currentVerificationRoleId === ($roleIdMap['ADMIN'] ?? 0) && !$roleAlreadyActed);

$nextRoleId = 0;
if ($currentIndex >= 0) {
    $nextIndex = $currentIndex + 1;
    if (isset($verificationRoles[$nextIndex])) {
        $nextRoleId = (int)$verificationRoles[$nextIndex]['id'];
    }
}

function addVerificationHistory($conn, $assessmentId, $userId, $roleId, $step, $action, $remark = '')
{
    $historyStmt = $conn->prepare("INSERT INTO assessment_verifications (assessment_id, user_id, role_id, verification_step, action, remark) VALUES (?, ?, ?, ?, ?, ?)");
    $historyStmt->bind_param("iiiiss", $assessmentId, $userId, $roleId, $step, $action, $remark);
    $historyStmt->execute();
    $historyStmt->close();
}

/*
|--------------------------------------------------------------------------
| ACTION
|--------------------------------------------------------------------------
*/
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = strtolower(trim($_POST['action'] ?? ''));
    $remark = trim($_POST['remark'] ?? '');

    $lockStmt = $conn->prepare("SELECT verification_status, current_verification_role_id FROM assessments WHERE id = ? AND is_deleted = 0 LIMIT 1");

    if (!$lockStmt) {
        $error = "Unable to validate assessment.";
    } else {
        $lockStmt->bind_param("i", $assessment_id);
        $lockStmt->execute();
        $latest = $lockStmt->get_result()->fetch_assoc();
        $lockStmt->close();

        if (!$latest) {
            $error = "Assessment not found.";
        } else {
            $latestStatus = strtolower(trim($latest['verification_status'] ?? 'pending'));

            if ($latestStatus !== 'pending') {
                $error = "This assessment has already been processed.";
            } elseif (!in_array($action, ['verify', 'reject', 'approve'], true)) {
                $error = "Invalid verification action.";
            } elseif ($remark === '') {
                $error = "A remark is strictly required before you can perform this action.";
            } elseif ($action === 'reject' && !$canReject) {
                $error = $roleAlreadyActed ? "Your role has already verified/forwarded this assessment and cannot reject it." : "You are not authorized to reject this assessment.";
            } elseif ($action === 'approve' && !$canApprove) {
                $error = "Only ADMIN can give final approval when the assessment reaches ADMIN.";
            } elseif ($action === 'verify' && !$canForward) {
                $error = "Only the currently assigned verification role can forward this assessment.";
            } elseif ($action === 'verify' && $nextRoleId <= 0) {
                $error = "Next verification role was not found.";
            }
        }
    }

    if ($error === '') {
        $conn->begin_transaction();
        try {
            if ($action === 'reject') {
                $newStatus = 'reject';
                // ✅ BUG FIX: Changed 'siiisi' to 'siisii' so the remark is properly treated as a string!
                $update = $conn->prepare("UPDATE assessments SET verification_status = ?, current_verification_role_id = ?, verified_by = ?, verified_at = NOW(), rejection_remark = ?, updated_by = ?, updated_at = NOW() WHERE id = ? AND verification_status = 'pending'");
                $update->bind_param("siisii", $newStatus, $currentRoleId, $user_id, $remark, $user_id, $assessment_id);
                if (!$update->execute() || $update->affected_rows !== 1) throw new Exception("Assessment could not be rejected.");
                $update->close();

                addVerificationHistory($conn, $assessment_id, $user_id, $currentRoleId, max(1, $currentIndex + 1), 'rejected', $remark);
                $conn->commit();
                header("Location: view-assesment.php?status=reject");
                exit;
            }

            if ($action === 'approve') {
                $newStatus = 'approved';
                $update = $conn->prepare("UPDATE assessments SET verification_status = ?, current_verification_role_id = NULL, verified_by = ?, verified_at = NOW(), rejection_remark = NULL, updated_by = ?, updated_at = NOW() WHERE id = ? AND verification_status = 'pending' AND current_verification_role_id = ?");
                $update->bind_param("siiii", $newStatus, $user_id, $user_id, $assessment_id, $currentVerificationRoleId);
                if (!$update->execute() || $update->affected_rows !== 1) throw new Exception("Assessment could not be approved.");
                $update->close();

                addVerificationHistory($conn, $assessment_id, $user_id, $currentRoleId, max(1, $currentIndex + 1), 'approved', $remark);
                $conn->commit();
                header("Location: view-assesment.php?status=approved");
                exit;
            }

            if ($action === 'verify') {
                $newStatus = 'pending';
                $update = $conn->prepare("UPDATE assessments SET verification_status = ?, current_verification_role_id = ?, verified_by = ?, verified_at = NOW(), rejection_remark = NULL, updated_by = ?, updated_at = NOW() WHERE id = ? AND verification_status = 'pending' AND current_verification_role_id = ?");
                $update->bind_param("siiiii", $newStatus, $nextRoleId, $user_id, $user_id, $assessment_id, $currentVerificationRoleId);
                if (!$update->execute() || $update->affected_rows !== 1) throw new Exception("Assessment could not be forwarded.");
                $update->close();

                addVerificationHistory($conn, $assessment_id, $user_id, $currentRoleId, max(1, $currentIndex + 1), 'verified', $remark);
                $conn->commit();
                header("Location: view-assesment.php?status=pending");
                exit;
            }
            throw new Exception("Unknown action.");
        } catch (Throwable $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| FETCH HISTORY
|--------------------------------------------------------------------------
*/
$historyRows = [];
$historyStmt = $conn->prepare("
    SELECT av.*, u.name AS user_name, u.username, r.role_name
    FROM assessment_verifications av
    LEFT JOIN users u ON u.id = av.user_id
    LEFT JOIN roles r ON r.id = av.role_id
    WHERE av.assessment_id = ?
    ORDER BY av.id ASC
");
if ($historyStmt) {
    $historyStmt->bind_param("i", $assessment_id);
    $historyStmt->execute();
    $historyRows = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $historyStmt->close();
}

$actualRejectorRoleId = null;
$verifiedRoleIds = [];
foreach ($historyRows as $h) {
    if (strtolower($h['action']) === 'rejected') {
        $actualRejectorRoleId = (int)$h['role_id'];
    }
    if (strtolower($h['action']) === 'verified') {
        $verifiedRoleIds[] = (int)$h['role_id'];
    }
}
?>

<style>
    body {
        background: #f8fafc;
        font-family: Inter, Arial, sans-serif;
    }

    .card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .06);
    }

    .badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
    }

    .badge.pending {
        background: #fef3c7;
        color: #92400e;
    }

    .badge.approved {
        background: #ffedd5;
        color: #ea580c;
    }

    .badge.rejected {
        background: #fee2e2;
        color: #991b1b;
    }

    .detail-label {
        font-size: 12px;
        color: #64748b;
        margin-bottom: 3px;
    }

    .detail-value {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        word-break: break-word;
    }

    .timeline-line {
        border-left: 2px solid #e2e8f0;
        margin-left: 10px;
        padding-left: 22px;
    }
</style>

<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Verify Assessment</h1>
            <p class="text-slate-500 mt-1">Property verification workflow</p>
        </div>
        <a href="view-assesment.php" class="px-5 py-2.5 rounded-lg bg-slate-700 text-white hover:bg-slate-800">
            <i class="fa fa-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($error): ?>
        <div class="mb-5 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700">
            <i class="fa fa-exclamation-circle"></i> <?= e($error) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="mb-5 p-4 rounded-lg bg-green-50 border border-green-200 text-green-700">
            <?= e($success) ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- MAIN -->
        <div class="lg:col-span-2 space-y-6">
            <div class="card p-6">
                <div class="flex justify-between items-start gap-4 mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Assessment #<?= e($assessment['id']) ?></h2>
                        <p class="text-sm text-slate-500 mt-1">Property ID: <strong><?= e($assessment['property_id']) ?></strong></p>
                    </div>
                    <?= statusBadge($status) ?>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <div class="detail-label">Holding Number</div>
                        <div class="detail-value"><?= e($assessment['new_holding']) ?></div>
                    </div>
                    <div>
                        <div class="detail-label">Property Type</div>
                        <div class="detail-value"><?= e($assessment['property_type']) ?></div>
                    </div>
                    <div>
                        <div class="detail-label">Property Status</div>
                        <div class="detail-value"><?= e($assessment['property_status']) ?></div>
                    </div>
                    <div>
                        <div class="detail-label">Ward</div>
                        <div class="detail-value"><?= e($assessment['ward']) ?></div>
                    </div>
                    <div>
                        <div class="detail-label">House No</div>
                        <div class="detail-value"><?= e($assessment['house_no']) ?></div>
                    </div>
                    <div>
                        <div class="detail-label">Plot Area</div>
                        <div class="detail-value"><?= e($assessment['plot_area']) ?></div>
                    </div>
                    <div>
                        <div class="detail-label">Created By</div>
                        <div class="detail-value flex items-center gap-2">
                            <?= e($assessment['creator_name'] ?: 'N/A') ?>
                            <?php if (!empty($assessment['creator_role_name'])): ?>
                                <span class="text-xs text-slate-500 font-normal border border-slate-200 px-2 py-0.5 rounded">
                                    <?= e($assessment['creator_role_name']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <div class="detail-label">Created At</div>
                        <div class="detail-value"><?= e($assessment['created_at']) ?></div>
                    </div>
                    <div class="md:col-span-2">
                        <div class="detail-label">Address</div>
                        <div class="detail-value"><?= e(trim(($assessment['addr1'] ?? '') . ' ' . ($assessment['addr2'] ?? ''))) ?></div>
                    </div>
                </div>
            </div>

            <!-- CURRENT VERIFICATION -->
            <div class="card p-6">
                <h2 class="text-xl font-bold text-slate-900 mb-5">Verification Status</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 rounded-xl bg-slate-50">
                        <div class="detail-label">Current Status</div>
                        <div class="mt-2"><?= statusBadge($status) ?></div>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50">
                        <div class="detail-label">Current Verification Role</div>
                        <div class="detail-value mt-2">
                            <?= $status === 'reject' ? '<span class="text-red-600">✕ Rejected</span>' : e($currentRequiredRole ?: 'Completed') ?>
                        </div>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50">
                        <div class="detail-label">Current User</div>
                        <div class="detail-value mt-2"><?= e($currentUser['name'] ?: $currentUser['username'] ?: 'User') ?></div>
                        <div class="text-xs text-slate-500 mt-1">Role: <?= e($currentRole) ?></div>
                    </div>
                </div>

                <?php if ($status === 'reject'): ?>
                    <div class="mt-5 p-4 rounded-xl bg-red-50 border border-red-200">
                        <div class="font-bold text-red-800">Rejection Remark</div>
                        <div class="text-red-700 mt-1"><?= nl2br(e($assessment['rejection_remark'])) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($status === 'pending' && ($canReject || $canForward || $canApprove)): ?>
                    <form method="post" class="mt-6">
                        <input type="hidden" name="assessment_id" value="<?= e($assessment_id) ?>">

                        <label class="block text-sm font-semibold text-slate-700 mb-2">Remark</label>
                        <textarea
                            id="verificationRemark"
                            name="remark"
                            rows="3"
                            required
                            class="w-full border border-slate-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            placeholder="Enter remark (Required for any action)"></textarea>

                        <div class="flex justify-end gap-3 mt-5 flex-wrap">
                            <?php if ($canReject): ?>
                                <button
                                    type="submit"
                                    name="action"
                                    value="reject"
                                    onclick="return handleActionValidation('reject', 'Are you sure you want to reject this assessment?');"
                                    class="px-5 py-2.5 rounded-lg bg-red-600 text-white hover:bg-red-700">
                                    <i class="fa fa-times-circle"></i> Reject
                                </button>
                            <?php endif; ?>

                            <?php if ($canForward): ?>
                                <button
                                    type="submit"
                                    name="action"
                                    value="verify"
                                    onclick="return handleActionValidation('verify', 'Are you sure you want to verify and forward this assessment?');"
                                    class="px-5 py-2.5 rounded-lg bg-slate-900 text-white hover:bg-slate-800">
                                    <i class="fa fa-check"></i> Verify & Forward
                                </button>
                            <?php endif; ?>

                            <?php if ($canApprove): ?>
                                <button
                                    type="submit"
                                    name="action"
                                    value="approve"
                                    onclick="return handleActionValidation('approve', 'Are you sure you want to FINAL APPROVE this assessment?');"
                                    class="px-5 py-2.5 rounded-lg bg-orange-600 text-white hover:bg-orange-700">
                                    <i class="fa fa-check-circle"></i> Final Approve
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php elseif ($status === 'pending'): ?>
                    <div class="mt-5 p-4 rounded-xl bg-yellow-50 border border-yellow-200 text-yellow-800">
                        <i class="fa fa-lock"></i> This assessment is currently waiting for: <strong><?= e($currentRequiredRole ?: 'next verifier') ?></strong>.
                        <?php if (!$isAssignedToCurrentUser && $currentRoleId > 0): ?>
                            <div class="text-xs mt-2 text-yellow-700">
                                You are logged in as <strong><?= e($currentRole) ?></strong>. The action buttons will appear when it reaches your role.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT -->
        <div class="space-y-6">
            <div class="card p-6">
                <h2 class="text-lg font-bold text-slate-900 mb-2">Verification Workflow</h2>
                <p class="text-xs text-slate-500 mb-5">Each role verifies and forwards to the next role. ADMIN gives final approval.</p>
                <div class="space-y-5">
                    <?php
                    $currentRoleIndex = -1;
                    foreach ($verificationRoles as $idx => $role) {
                        if ($role['id'] == $currentVerificationRoleId) {
                            $currentRoleIndex = $idx;
                            break;
                        }
                    }

                    foreach ($verificationRoles as $index => $workflowRole):
                        $roleId = (int)$workflowRole['id'];
                        $role = $workflowRole['name'];
                        $isCurrent = ($roleId > 0 && $roleId === $currentVerificationRoleId);
                        $isLast = ($role === 'ADMIN');

                        if ($status === 'approved') {
                            $state = 'done';
                        } elseif ($status === 'reject') {
                            if ($actualRejectorRoleId !== null) {
                                if ($roleId === $actualRejectorRoleId) {
                                    $state = 'rejected';
                                } elseif (in_array($roleId, $verifiedRoleIds)) {
                                    $state = 'done';
                                } else {
                                    $state = 'normal'; // Waiting
                                }
                            } else {
                                if ($isCurrent) {
                                    $state = 'rejected';
                                } elseif ($currentRoleIndex >= 0 && $index < $currentRoleIndex) {
                                    $state = 'done';
                                } else {
                                    $state = 'normal';
                                }
                            }
                        } elseif ($isCurrent) {
                            $state = 'current';
                        } elseif ($currentRoleIndex >= 0 && $index < $currentRoleIndex) {
                            $state = 'done';
                        } else {
                            $state = 'normal';
                        }
                    ?>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center
                                <?php echo $state === 'done' ? 'bg-orange-100 text-orange-600' : ($state === 'current' ? 'bg-slate-900 text-white' : ($state === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-500')); ?>">
                                <?php if ($state === 'done'): ?>✓<?php elseif ($state === 'rejected'): ?>✕<?php else: ?><?= $index + 1 ?><?php endif; ?>
                            </div>
                            <div>
                                <div class="font-semibold text-slate-800"><?= e($role) ?></div>
                                <div class="text-xs text-slate-500">
                                    <?php if ($state === 'current'): ?>Current Verification<?php elseif ($state === 'done'): ?>Completed<?php elseif ($state === 'rejected'): ?>Rejected<?php elseif ($isLast): ?>Final Approval<?php else: ?>Waiting<?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- HISTORY -->
            <div class="card p-6">
                <h2 class="text-lg font-bold text-slate-900 mb-5">Verification History</h2>
                <?php if (empty($historyRows)): ?>
                    <p class="text-sm text-slate-500">No verification history available.</p>
                <?php else: ?>
                    <div class="timeline-line space-y-6">
                        <?php foreach ($historyRows as $history): ?>
                            <div>
                                <div class="font-semibold text-slate-800"><?= e($history['role_name'] ?? 'Unknown Role') ?></div>
                                <div class="text-sm text-slate-600"><?= e($history['user_name'] ?? 'Unknown User') ?></div>
                                <div class="text-xs text-slate-400 mt-1"><?= e($history['action']) ?> · <?= e($history['created_at']) ?></div>
                                <?php if (!empty($history['remark'])): ?>
                                    <div class="text-xs text-slate-500 mt-2"><?= nl2br(e($history['remark'])) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function handleActionValidation(actionType, confirmMessage) {
        const remarkField = document.getElementById('verificationRemark');
        if (remarkField.value.trim() === '') {
            let actionName = 'submitting';
            if (actionType === 'reject') actionName = 'rejecting';
            else if (actionType === 'approve') actionName = 'approving';
            else if (actionType === 'verify') actionName = 'verifying/forwarding';

            alert(`Please enter a remark before ${actionName} the assessment.`);
            remarkField.focus();
            return false;
        }
        return confirm(confirmMessage);
    }
</script>

<?php include "include/footer.php" ?>