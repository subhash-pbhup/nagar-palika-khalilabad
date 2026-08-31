<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function auditUser(mysqli $conn): array
{
    $id = isset($_SESSION['user_id'])
        ? (int)$_SESSION['user_id']
        : 0;

    $name = null;
    $username = null;

    if ($id > 0) {

        $stmt = $conn->prepare(
            "SELECT name, username FROM users WHERE id = ? LIMIT 1"
        );

        if ($stmt) {

            $stmt->bind_param("i", $id);
            $stmt->execute();

            $row = $stmt
                ->get_result()
                ->fetch_assoc();

            if ($row) {
                $name = $row['name'] ?? null;
                $username = $row['username'] ?? null;
            }

            $stmt->close();
        }
    }

    return [
        $id > 0 ? $id : null,
        $name,
        $username
    ];
}

function auditJson($value): ?string
{
    if ($value === null) {
        return null;
    }

    $json = json_encode(
        $value,
        JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
    );

    return $json === false ? null : $json;
}

function addAudit(mysqli $conn, array $data): bool
{
    /*
     * CREATE history intentionally disabled.
     * We only want UPDATE history.
     */
    $action = strtoupper(
        $data['action'] ?? 'UPDATE'
    );

    if ($action === 'CREATE') {
        return true;
    }

    $assessmentId =
        (int)($data['assessment_id'] ?? 0);

    if ($assessmentId <= 0) {
        return false;
    }

    $propertyId =
        $data['property_id'] ?? null;

    $entityType =
        strtoupper(
            $data['entity_type'] ?? 'ASSESSMENT'
        );

    if (
        !in_array(
            $entityType,
            ['ASSESSMENT', 'OWNER', 'FLOOR'],
            true
        )
    ) {
        $entityType = 'ASSESSMENT';
    }

    $entityId =
        (int)(
            $data['entity_id']
            ?? $assessmentId
        );

    if (
        !in_array(
            $action,
            ['UPDATE', 'DELETE', 'RESTORE'],
            true
        )
    ) {
        $action = 'UPDATE';
    }

    $oldData =
        $data['old_data'] ?? [];

    $newData =
        $data['new_data'] ?? [];

    $changedFields =
        $data['changed_fields']
        ?? [];

    /*
     * Do not insert an empty history row.
     */
    if (
        $action === 'UPDATE' &&
        empty($changedFields)
    ) {
        return true;
    }

    [$userId, $userName, $username] =
        auditUser($conn);

    $changedJson =
        auditJson($changedFields);

    $oldJson =
        auditJson($oldData);

    $newJson =
        auditJson($newData);

    $remark =
        $data['remark'] ?? null;

    $ipAddress =
        $_SERVER['REMOTE_ADDR'] ?? null;

    $userAgent =
        $_SERVER['HTTP_USER_AGENT'] ?? null;

    $sql = "
        INSERT INTO assessment_updates_history
        (
            assessment_id,
            property_id,
            entity_type,
            entity_id,
            action,
            user_id,
            user_name,
            username,
            changed_fields,
            old_data,
            new_data,
            remark,
            ip_address,
            user_agent
        )
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt =
        $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception(
            "Audit Prepare Error: " .
                $conn->error
        );
    }

    /*
     * 14 variables = 14 type characters.
     */
    $stmt->bind_param(
        "issisissssssss",
        $assessmentId,
        $propertyId,
        $entityType,
        $entityId,
        $action,
        $userId,
        $userName,
        $username,
        $changedJson,
        $oldJson,
        $newJson,
        $remark,
        $ipAddress,
        $userAgent
    );

    if (!$stmt->execute()) {

        $error =
            $stmt->error;

        $stmt->close();

        throw new Exception(
            "Audit Insert Error: " .
                $error
        );
    }

    $stmt->close();

    return true;
}
