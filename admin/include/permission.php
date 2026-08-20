<?php

function hasPermission($permission)
{
    global $conn;

    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    $user_id = $_SESSION['user_id'];

    $sql = "
        SELECT 1
        FROM users u
        INNER JOIN role_permissions rp
            ON u.role_id = rp.role_id
        INNER JOIN permissions p
            ON rp.permission_id = p.id
        WHERE u.id = ?
        AND p.permission_key = ?
        AND u.status = 1
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $permission);
    $stmt->execute();

    return $stmt->get_result()->num_rows > 0;
}
