<?php
// =======================================================
// ⭐ ERROR REPORTING ADDED HERE ⭐
// This will display all PHP errors, warnings, and notices
// =======================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("db.php");
require_once "assessment_audit_helper.php";
header('Content-Type: text/html; charset=utf-8');

// Helper function to handle values, JSON encode arrays, and trim strings
function val($v)
{
    return is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : trim((string)$v);
}

// Helper function to log changes only if old and new values differ
function addLog(&$log, $f, $old, $new, $aid, $uid)
{
    if (trim((string)$old) !== trim((string)$new)) $log[] = [$aid, $f, (string)$old, (string)$new, $uid];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Invalid Request");

$aid = intval($_POST['assessment_id'] ?? 0);
if ($aid <= 0) {
    $_SESSION['error_message'] = "Invalid Assessment ID";
    header("Location: view-assesment.php");
    exit;
}

$user_id = $_SESSION['user_id'] ?? 1;
$log = [];

$delete_remark = "deleted by edit assement record";

try {
    // Start Transaction
    $conn->begin_transaction();

    // ===== 1. Fetch existing assessment data =====
    $result = $conn->query("SELECT * FROM assessments WHERE id=$aid");
    $old = $result->fetch_assoc();
    if (!$old) throw new Exception("Assessment ID: $aid not found in database.");

    // ===== 2. Handle Holding Number Change and Folder Rename =====
    $new_holding = trim($_POST['new_holding'] ?? $old['new_holding']);
    $previous_holding = ($old['new_holding'] != $new_holding) ? $old['new_holding'] : $old['previous_holding'];

    $old_folder_path = "uploads/" . $old['new_holding'];
    $new_folder_path = "uploads/" . $new_holding;

    // Folder Rename Logic
    if ($old['new_holding'] != $new_holding) {
        if (is_dir($old_folder_path)) {
            if (@rename($old_folder_path, $new_folder_path)) {
                addLog($log, "folder_rename", $old_folder_path, $new_folder_path, $aid, $user_id);
            }
        }
    }

    // Prepare fields for main assessment table update
    $fields = [
        'zone_id',
        'ward_id',
        'mohalla_id',
        'property_id',
        'property_status',
        'old_holding',
        'old_pid',
        'property_type',
        'road',
        'plot_area',
        'building_type',
        'latitude',
        'longitude',
        'house_no',
        'plot_no',
        'khata_no',
        'khasra_no',
        'addr1',
        'addr2',
        'pincode'
    ];

    // Logic to update $old array for logging current file paths
    $file_fields_for_log = ['center_image_gps', 'left_image_gps', 'right_image_gps', 'doc_proof', 'supporting_doc_1', 'supporting_doc_2', 'supporting_doc_3'];
    foreach ($file_fields_for_log as $f) {
        if (isset($_POST["current_$f"])) {
            $old[$f] = val($_POST["current_$f"] ?? $old[$f]);
        }
    }

    // Build the main UPDATE SQL
    $update_sql = "UPDATE assessments SET previous_holding=?, new_holding=?, updated_by=?, updated_at=NOW()";
    $ref_params = [$previous_holding, $new_holding, (int)$user_id];
    $types = "ssi";
    // --- SMART FIX: Check if 'update_counter' column exists and increment it ---
    $check_col = $conn->query("SHOW COLUMNS FROM assessments LIKE 'update_counter'");
    if ($check_col && $check_col->num_rows > 0) {
        $update_sql .= ", update_counter = COALESCE(update_counter, 0) + 1";
    }

    // Location/property identifiers
    $posted_zone_id = 1;
    $posted_ward_id = (int)($_POST['ward_id'] ?? $old['ward_id'] ?? $old['ward'] ?? 0);
    $posted_mohalla_id = (int)($_POST['mohalla_id'] ?? $old['mohalla_id'] ?? 0);
    $posted_property_id = trim($_POST['property_id'] ?? ($old['property_id'] ?? ''));

    // Validate ward/mohalla relation before update
    if ($posted_ward_id <= 0 || $posted_mohalla_id <= 0) {
        throw new Exception("Ward/Mohalla is required.");
    }
    $check = $conn->prepare("SELECT mohalla_id FROM mohalla WHERE mohalla_id=? AND ward_id=? LIMIT 1");
    if (!$check) throw new Exception("Mohalla validation failed: " . $conn->error);
    $check->bind_param("ii", $posted_mohalla_id, $posted_ward_id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        $check->close();
        throw new Exception("Invalid Mohalla selected for this Ward.");
    }
    $check->close();

    $_POST['zone_id'] = $posted_zone_id;
    $_POST['ward_id'] = $posted_ward_id;
    $_POST['mohalla_id'] = $posted_mohalla_id;
    $_POST['property_id'] = $posted_property_id;

    $posted_status = $_POST['property_status'] ?? '';
    if ($posted_status === 'New') {
        $_POST['old_holding'] = '';
        $_POST['old_pid'] = '';
    }

    foreach ($fields as $f) {
        $new = val($_POST[$f] ?? $old[$f]);
        addLog($log, $f, $old[$f], $new, $aid, $user_id);
        $update_sql .= ", `$f`=?";
        $ref_params[] = $new;
        $types .= "s";
    }

    // Handle water_tax
    // Normalize numeric values so 1, 1.0 and 1.00 are treated as the same value.
    $water_tax_new = isset($_POST['water_tax'])
        ? (float)$_POST['water_tax']
        : (float)($old['water_tax'] ?? 0);

    $old_water_tax = (float)($old['water_tax'] ?? 0);

    if (abs($old_water_tax - $water_tax_new) > 0.000001) {
        addLog(
            $log,
            'water_tax',
            (string)$old_water_tax,
            (string)$water_tax_new,
            $aid,
            $user_id
        );
    }

    $update_sql .= ", `water_tax`=?";
    $ref_params[] = $water_tax_new;
    $types .= "d";

    $update_sql .= " WHERE id=?";
    $ref_params[] = $aid;
    $types .= "i";

    // Execute Main Assessment Update
    $stmt = $conn->prepare($update_sql);
    if (!$stmt) throw new Exception("Main Assessment Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$ref_params);
    if (!$stmt->execute()) throw new Exception("Main Assessment Execute failed: " . $stmt->error);
    $stmt->close();

    // ========================================================
    // 3. OWNERS LOGIC
    // IMPORTANT:
    // - Do nothing if owners_data is empty/invalid.
    // - Never soft-delete all owners because db_id was omitted.
    // - Ignore blank owner UI rows.
    // - Changes are collected into the single $log entry.
    // ========================================================
    if (isset($_POST['owners_data']) && trim((string)$_POST['owners_data']) !== '') {

        $owners = json_decode($_POST['owners_data'], true);

        if (is_array($owners)) {

            // Ignore completely blank owner rows.
            $owners = array_values(array_filter($owners, function ($o) {
                if (!is_array($o)) return false;

                $db_id = (int)($o['db_id'] ?? 0);

                if ($db_id > 0) return true;

                foreach (
                    [
                        'owner_name',
                        'father_husband_pan',
                        'gender',
                        'mobile',
                        'email'
                    ] as $k
                ) {
                    if (trim((string)($o[$k] ?? '')) !== '') {
                        return true;
                    }
                }

                return false;
            }));

            $submitted_owner_db_ids = [];

            foreach ($owners as $o) {
                $db_id = (int)($o['db_id'] ?? 0);

                if ($db_id > 0) {
                    $submitted_owner_db_ids[] = $db_id;
                }
            }

            $submitted_owner_db_ids = array_values(
                array_unique($submitted_owner_db_ids)
            );

            /*
             * Only perform soft-delete comparison when the form
             * actually sent existing owner IDs.
             */
            if (!empty($submitted_owner_db_ids)) {

                $ids_str = implode(
                    ',',
                    array_map('intval', $submitted_owner_db_ids)
                );

                $owner_delete_sql = "
                    UPDATE assessment_owners
                    SET
                        is_deleted = 1,
                        deleted_remark = ?,
                        deleted_at = NOW()
                    WHERE
                        assessment_id = ?
                        AND is_deleted = 0
                        AND id NOT IN ($ids_str)
                ";

                $stmt_owner_delete =
                    $conn->prepare($owner_delete_sql);

                if (!$stmt_owner_delete) {
                    throw new Exception(
                        "Owner Soft Delete Prepare failed: " .
                            $conn->error
                    );
                }

                $stmt_owner_delete->bind_param(
                    "si",
                    $delete_remark,
                    $aid
                );

                if (!$stmt_owner_delete->execute()) {
                    throw new Exception(
                        "Owner Soft Delete Execute failed: " .
                            $stmt_owner_delete->error
                    );
                }

                $stmt_owner_delete->close();
            }

            foreach ($owners as $o) {

                $db_id = (int)($o['db_id'] ?? 0);

                $owner_data = [
                    'owner_name' =>
                    val($o['owner_name'] ?? ''),

                    'father_husband_pan' =>
                    val($o['father_husband_pan'] ?? ''),

                    'gender' =>
                    val($o['gender'] ?? ''),

                    'mobile' =>
                    val($o['mobile'] ?? ''),

                    'email' =>
                    val($o['email'] ?? '')
                ];

                /*
                 * Existing owner
                 */
                if ($db_id > 0) {

                    $stmt_old_owner = $conn->prepare("
                        SELECT *
                        FROM assessment_owners
                        WHERE id = ?
                        AND assessment_id = ?
                        LIMIT 1
                    ");

                    if (!$stmt_old_owner) {
                        throw new Exception(
                            "Owner Select Error: " .
                                $conn->error
                        );
                    }

                    $stmt_old_owner->bind_param(
                        "ii",
                        $db_id,
                        $aid
                    );

                    $stmt_old_owner->execute();

                    $old_owner =
                        $stmt_old_owner
                        ->get_result()
                        ->fetch_assoc();

                    $stmt_old_owner->close();

                    if ($old_owner) {

                        foreach ($owner_data as $k => $v) {

                            $old_v =
                                $old_owner[$k] ?? '';

                            if (
                                (string)$old_v !==
                                (string)$v
                            ) {
                                addLog(
                                    $log,
                                    "owner.$db_id.$k",
                                    $old_v,
                                    $v,
                                    $aid,
                                    $user_id
                                );
                            }
                        }

                        $o_name =
                            $owner_data['owner_name'];

                        $o_pan =
                            $owner_data['father_husband_pan'];

                        $o_gender =
                            $owner_data['gender'];

                        $o_mobile =
                            $owner_data['mobile'];

                        $o_email =
                            $owner_data['email'];

                        $stmt_owner = $conn->prepare("
                            UPDATE assessment_owners
                            SET
                                owner_name = ?,
                                father_husband_pan = ?,
                                gender = ?,
                                mobile = ?,
                                email = ?,
                                is_deleted = 0,
                                deleted_remark = NULL,
                                deleted_at = NULL
                            WHERE
                                id = ?
                                AND assessment_id = ?
                        ");

                        if (!$stmt_owner) {
                            throw new Exception(
                                "Owner Update Prepare failed: " .
                                    $conn->error
                            );
                        }

                        $stmt_owner->bind_param(
                            "sssssii",
                            $o_name,
                            $o_pan,
                            $o_gender,
                            $o_mobile,
                            $o_email,
                            $db_id,
                            $aid
                        );

                        if (!$stmt_owner->execute()) {
                            throw new Exception(
                                "Owner Update Execute failed: " .
                                    $stmt_owner->error
                            );
                        }

                        $stmt_owner->close();
                    }

                    /*
                 * New owner:
                 * Only insert when actual owner data exists.
                 */
                } else {

                    $has_owner_data = false;

                    foreach ($owner_data as $v) {
                        if (trim((string)$v) !== '') {
                            $has_owner_data = true;
                            break;
                        }
                    }

                    if (!$has_owner_data) {
                        continue;
                    }

                    $o_name =
                        $owner_data['owner_name'];

                    $o_pan =
                        $owner_data['father_husband_pan'];

                    $o_gender =
                        $owner_data['gender'];

                    $o_mobile =
                        $owner_data['mobile'];

                    $o_email =
                        $owner_data['email'];

                    $stmt_owner = $conn->prepare("
                        INSERT INTO assessment_owners
                        (
                            assessment_id,
                            owner_name,
                            father_husband_pan,
                            gender,
                            mobile,
                            email,
                            is_deleted
                        )
                        VALUES
                        (?, ?, ?, ?, ?, ?, 0)
                    ");

                    if (!$stmt_owner) {
                        throw new Exception(
                            "Owner Insert Prepare failed: " .
                                $conn->error
                        );
                    }

                    $stmt_owner->bind_param(
                        "isssss",
                        $aid,
                        $o_name,
                        $o_pan,
                        $o_gender,
                        $o_mobile,
                        $o_email
                    );

                    if (!$stmt_owner->execute()) {
                        throw new Exception(
                            "Owner Insert Execute failed: " .
                                $stmt_owner->error
                        );
                    }

                    $stmt_owner->close();

                    addLog(
                        $log,
                        "owner.new",
                        "",
                        "Owner Added: {$o_name}",
                        $aid,
                        $user_id
                    );
                }
            }
        }
    }


    // ========================================================
    // 4. FLOORS LOGIC - SAFE / NO DUPLICATE VERSION
    // ========================================================
    if (isset($_POST['floors_data']) && trim((string)$_POST['floors_data']) !== '') {

        $floors = json_decode($_POST['floors_data'], true);
        if (!is_array($floors)) $floors = [];

        // Delete ONLY when user explicitly clicked Delete.
        $deleted_floor_ids = [];
        if (isset($_POST['deleted_floor_ids'])) {
            $tmp_deleted = json_decode($_POST['deleted_floor_ids'], true);
            if (is_array($tmp_deleted)) {
                foreach ($tmp_deleted as $did) {
                    $did = (int)$did;
                    if ($did > 0) $deleted_floor_ids[] = $did;
                }
            }
        }
        $deleted_floor_ids = array_values(array_unique($deleted_floor_ids));

        foreach ($deleted_floor_ids as $deleted_id) {
            $stmt = $conn->prepare("SELECT id FROM assessment_floors WHERE id=? AND assessment_id=? LIMIT 1");
            if (!$stmt) throw new Exception("Floor delete select failed: " . $conn->error);
            $stmt->bind_param("ii", $deleted_id, $aid);
            $stmt->execute();
            $exists = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$exists) continue;

            $stmt = $conn->prepare("UPDATE assessment_floors SET is_deleted=1, deleted_remark=?, deleted_at=NOW() WHERE id=? AND assessment_id=?");
            if (!$stmt) throw new Exception("Floor delete prepare failed: " . $conn->error);
            $stmt->bind_param("sii", $delete_remark, $deleted_id, $aid);
            if (!$stmt->execute()) throw new Exception("Floor delete failed: " . $stmt->error);
            $stmt->close();
            addLog($log, "floor.$deleted_id.deleted", "Active", "Deleted", $aid, $user_id);
        }

        foreach ($floors as $f) {
            if (!is_array($f)) continue;

            $db_id = (int)($f['db_id'] ?? 0);
            $floor_data = [
                'floor_no' => val($f['floor_no'] ?? ''),
                'date_from' => val($f['date_from'] ?? ''),
                'date_to' => val($f['date_to'] ?? ''),
                'residential_type' => val($f['residential_type'] ?? ''),
                'construction_type' => val($f['construction_type'] ?? ''),
                'occupancy_type' => val($f['occupancy_type'] ?? ''),
                'build_up_area' => (float)($f['build_up_area'] ?? 0),
                'usage_type' => val($f['usage_type'] ?? ''),
                'non_residential_group' => val($f['non_residential_group'] ?? ''),
                'property_name' => val($f['property_name'] ?? '')
            ];

            // Ignore completely blank UI rows.
            $has_data = false;
            foreach ($floor_data as $k => $v) {
                if ($k === 'build_up_area') {
                    if ((float)$v > 0) {
                        $has_data = true;
                        break;
                    }
                } elseif (trim((string)$v) !== '') {
                    $has_data = true;
                    break;
                }
            }
            if (!$has_data) continue;

            // A deleted floor must never be re-inserted by the same request.
            if ($db_id > 0 && in_array($db_id, $deleted_floor_ids, true)) continue;

            // If db_id is missing, ALWAYS try to reuse an existing floor by floor_no.
            if ($db_id <= 0 && $floor_data['floor_no'] !== '') {
                $match_no = $floor_data['floor_no'];
                $stmt = $conn->prepare("SELECT id FROM assessment_floors WHERE assessment_id=? AND TRIM(floor_no)=TRIM(?) ORDER BY is_deleted ASC, id ASC LIMIT 1");
                if (!$stmt) throw new Exception("Floor match failed: " . $conn->error);
                $stmt->bind_param("is", $aid, $match_no);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($row) $db_id = (int)$row['id'];
            }

            // Existing floor => UPDATE only.
            if ($db_id > 0) {
                $stmt = $conn->prepare("SELECT * FROM assessment_floors WHERE id=? AND assessment_id=? LIMIT 1");
                if (!$stmt) throw new Exception("Floor select failed: " . $conn->error);
                $stmt->bind_param("ii", $db_id, $aid);
                $stmt->execute();
                $old_floor = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($old_floor) {
                    foreach ($floor_data as $k => $new_v) {
                        $old_v = $old_floor[$k] ?? '';
                        $changed = ($k === 'build_up_area')
                            ? abs((float)$old_v - (float)$new_v) > 0.000001
                            : (string)$old_v !== (string)$new_v;
                        if ($changed) {
                            addLog($log, "floor.$db_id.$k", $old_v, $new_v, $aid, $user_id);
                        }
                    }

                    $stmt = $conn->prepare("UPDATE assessment_floors SET floor_no=?, date_from=?, date_to=?, residential_type=?, construction_type=?, occupancy_type=?, build_up_area=?, usage_type=?, non_residential_group=?, property_name=?, is_deleted=0, deleted_remark=NULL, deleted_at=NULL WHERE id=? AND assessment_id=?");
                    if (!$stmt) throw new Exception("Floor update prepare failed: " . $conn->error);
                    $stmt->bind_param(
                        "ssssssdsssii",
                        $floor_data['floor_no'],
                        $floor_data['date_from'],
                        $floor_data['date_to'],
                        $floor_data['residential_type'],
                        $floor_data['construction_type'],
                        $floor_data['occupancy_type'],
                        $floor_data['build_up_area'],
                        $floor_data['usage_type'],
                        $floor_data['non_residential_group'],
                        $floor_data['property_name'],
                        $db_id,
                        $aid
                    );
                    if (!$stmt->execute()) throw new Exception("Floor update failed: " . $stmt->error);
                    $stmt->close();
                    continue;
                }
            }

            // New floor only if there is NO existing floor with the same floor_no.
            $stmt = $conn->prepare("SELECT id FROM assessment_floors WHERE assessment_id=? AND TRIM(floor_no)=TRIM(?) ORDER BY is_deleted ASC, id ASC LIMIT 1");
            if (!$stmt) throw new Exception("Final floor duplicate check failed: " . $conn->error);
            $stmt->bind_param("is", $aid, $floor_data['floor_no']);
            $stmt->execute();
            $duplicate = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($duplicate) {
                // Reuse existing row; NEVER INSERT a duplicate.
                $existing_id = (int)$duplicate['id'];
                $stmt = $conn->prepare("UPDATE assessment_floors SET date_from=?, date_to=?, residential_type=?, construction_type=?, occupancy_type=?, build_up_area=?, usage_type=?, non_residential_group=?, property_name=?, is_deleted=0, deleted_remark=NULL, deleted_at=NULL WHERE id=? AND assessment_id=?");
                if (!$stmt) throw new Exception("Existing floor restore/update failed: " . $conn->error);
                $stmt->bind_param(
                    "sssssdsssii",
                    $floor_data['date_from'],
                    $floor_data['date_to'],
                    $floor_data['residential_type'],
                    $floor_data['construction_type'],
                    $floor_data['occupancy_type'],
                    $floor_data['build_up_area'],
                    $floor_data['usage_type'],
                    $floor_data['non_residential_group'],
                    $floor_data['property_name'],
                    $existing_id,
                    $aid
                );
                if (!$stmt->execute()) throw new Exception("Existing floor update failed: " . $stmt->error);
                $stmt->close();
                continue;
            }

            // Genuine new floor.
            $stmt = $conn->prepare("INSERT INTO assessment_floors (assessment_id,floor_no,date_from,date_to,residential_type,construction_type,occupancy_type,build_up_area,usage_type,non_residential_group,property_name,is_deleted) VALUES (?,?,?,?,?,?,?,?,?,?,?,0)");
            if (!$stmt) throw new Exception("Floor insert prepare failed: " . $conn->error);
            $stmt->bind_param(
                "issssssdsss",
                $aid,
                $floor_data['floor_no'],
                $floor_data['date_from'],
                $floor_data['date_to'],
                $floor_data['residential_type'],
                $floor_data['construction_type'],
                $floor_data['occupancy_type'],
                $floor_data['build_up_area'],
                $floor_data['usage_type'],
                $floor_data['non_residential_group'],
                $floor_data['property_name']
            );
            if (!$stmt->execute()) throw new Exception("Floor insert failed: " . $stmt->error);
            $new_id = $stmt->insert_id;
            $stmt->close();
            addLog($log, "floor.new.$new_id", '', 'Floor Added: ' . $floor_data['floor_no'], $aid, $user_id);
        }
    }

    // ===== 5. Upload All Files =====
    $upload_fields = [
        'center_image_gps',
        'left_image_gps',
        'right_image_gps',
        'doc_proof',
        'supporting_doc_1',
        'supporting_doc_2',
        'supporting_doc_3'
    ];

    $folder = "uploads/" . $new_holding . "/";
    if (!is_dir($folder)) @mkdir($folder, 0777, true);

    foreach ($upload_fields as $upload_field) {
        if (isset($_FILES[$upload_field]) && $_FILES[$upload_field]['error'] == 0) {
            $tmp_name = $_FILES[$upload_field]['tmp_name'];
            $name = $_FILES[$upload_field]['name'];
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $newname = uniqid($upload_field . '_', true) . ".$ext";

            $file_path_var = "uploads/" . $new_holding . "/" . $newname;

            if (move_uploaded_file($tmp_name, $folder . $newname)) {

                $old_path = $old[$upload_field] ?? '';
                if (!empty($old_path) && file_exists($old_path) && is_file($old_path)) {
                    @unlink($old_path);
                }

                addLog($log, $upload_field, $old[$upload_field], $file_path_var, $aid, $user_id);

                $stmt = $conn->prepare("UPDATE assessments SET `$upload_field`=? WHERE id=?");
                if (!$stmt) throw new Exception("File Update Prepare failed: " . $conn->error);

                $stmt->bind_param("si", $file_path_var, $aid);
                if (!$stmt->execute()) throw new Exception("File Update Execute failed: " . $stmt->error);
                $stmt->close();

                // Insert into assessment_media for Doc Proofs only
                if (strpos($upload_field, 'doc') !== false) {
                    $type = "Document";
                    $stmt = $conn->prepare("INSERT INTO assessment_media(assessment_id,file_path,media_type,is_deleted) VALUES(?,?,?,0)");
                    if (!$stmt) throw new Exception("Media Insert Prepare failed: " . $conn->error);

                    $stmt->bind_param("iss", $aid, $file_path_var, $type);
                    if (!$stmt->execute()) throw new Exception("Media Insert Execute failed: " . $stmt->error);
                    $stmt->close();
                }
            } else {
                throw new Exception("File upload failed for field: $upload_field. Could not move uploaded file.");
            }
        }
    }

    // ===== SINGLE AUDIT ENTRY =====
    // Main assessment + owner + floor changes are collected in $log.
    // We write exactly ONE history row for this request.
    // CREATE is never written to the audit table.
    $history_old = [];
    $history_new = [];
    $history_fields = [];

    foreach ($log as $entry) {
        if (!is_array($entry) || count($entry) < 4) {
            continue;
        }

        $field = (string)$entry[1];
        $oldValue = (string)$entry[2];
        $newValue = (string)$entry[3];

        if ($oldValue === $newValue) {
            continue;
        }

        $history_fields[] = $field;
        $history_old[$field] = $oldValue;
        $history_new[$field] = $newValue;
    }

    if (!empty($history_fields)) {
        addAudit($conn, [
            'assessment_id' => $aid,
            'property_id' => $posted_property_id,
            'entity_type' => 'ASSESSMENT',
            'entity_id' => $aid,
            'action' => 'UPDATE',
            'old_data' => $history_old,
            'new_data' => $history_new,
            'changed_fields' => array_values(array_unique($history_fields)),
            'remark' => $_POST['audit_remark'] ?? 'Assessment updated'
        ]);
    }

    // Commit Transaction
    $conn->commit();

    // ⭐ SUCCESS ALERT AND REDIRECT ⭐
    $success_message = "Assessment Holding: $new_holding Updated Successfully!";
    echo "<script>alert('$success_message'); window.location.href='view-assesment.php?id=$aid';</script>";
    exit;
} catch (Exception $e) {
    // Rollback Transaction 
    $conn->rollback();

    // ⭐ ERROR ALERT AND REDIRECT (to edit page) ⭐
    // This will now capture and display the highly detailed database or PHP errors.
    $error_message = "UPDATE ERROR: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    echo "<script>alert('$error_message'); window.location.href='edit_assessment.php?id=$aid';</script>";
    exit;
}
$conn->close();
