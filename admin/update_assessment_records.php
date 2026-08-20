<?php
// =======================================================
// ⭐ ERROR REPORTING ADDED HERE ⭐
// This will display all PHP errors, warnings, and notices
// =======================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("db.php"); 
header('Content-Type: text/html; charset=utf-8');

// Helper function to handle values, JSON encode arrays, and trim strings
function val($v){ return is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : trim((string)$v); }

// Helper function to log changes only if old and new values differ
function addLog(&$log, $f, $old, $new, $aid, $uid){
    if (trim((string)$old) !== trim((string)$new)) $log[] = [$aid, $f, (string)$old, (string)$new, $uid];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Invalid Request");

$aid = intval($_POST['assessment_id'] ?? 0);
if ($aid <= 0){ $_SESSION['error_message']="Invalid Assessment ID"; header("Location: view-assesment.php"); exit; }

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

    $old_folder_path = "uploads/".$old['new_holding'];
    $new_folder_path = "uploads/".$new_holding;

    // Folder Rename Logic
    if ($old['new_holding'] != $new_holding){ 
        if (is_dir($old_folder_path)){
            if (@rename($old_folder_path, $new_folder_path)){
                addLog($log, "folder_rename", $old_folder_path, $new_folder_path, $aid, $user_id);
            }
        }
    }

    // Prepare fields for main assessment table update
    $fields = [
        'property_status','old_holding','old_pid','property_type','road','plot_area','building_type',
        'latitude','longitude','house_no','plot_no','khata_no','khasra_no','addr1','addr2','pincode'
    ];
    
    // Logic to update $old array for logging current file paths
    $file_fields_for_log = ['center_image_gps', 'left_image_gps', 'right_image_gps', 'doc_proof', 'supporting_doc_1', 'supporting_doc_2', 'supporting_doc_3'];
    foreach($file_fields_for_log as $f) {
        if(isset($_POST["current_$f"])) {
             $old[$f] = val($_POST["current_$f"] ?? $old[$f]); 
        }
    }

    // Build the main UPDATE SQL
    $update_sql = "UPDATE assessments SET previous_holding=?, new_holding=?";
    $ref_params = [$previous_holding, $new_holding];
    $types = "ss";

    // --- SMART FIX: Check if 'update_counter' column exists and increment it ---
    $check_col = $conn->query("SHOW COLUMNS FROM assessments LIKE 'update_counter'");
    if($check_col && $check_col->num_rows > 0) {
        $update_sql .= ", update_counter = COALESCE(update_counter, 0) + 1";
    }
    
    $posted_status = $_POST['property_status'] ?? '';
    if ($posted_status === 'New') {
        $_POST['old_holding'] = '';
        $_POST['old_pid'] = '';
    }

    foreach ($fields as $f){
        $new = val($_POST[$f] ?? $old[$f]);
        addLog($log, $f, $old[$f], $new, $aid, $user_id);
        $update_sql .= ", `$f`=?";
        $ref_params[] = $new;
        $types .= "s";
    }
    
    // Handle water_tax
    $water_tax_new = isset($_POST['water_tax']) ? val($_POST['water_tax']) : '0'; 
    addLog($log, 'water_tax', $old['water_tax'], $water_tax_new, $aid, $user_id);
    $update_sql .= ", `water_tax`=?";
    $ref_params[] = $water_tax_new;
    $types .= "s";

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
    // ⭐ 3. OWNERS LOGIC (FIXED) ⭐
    // ========================================================
    if(isset($_POST['owners_data'])){ 
        $owners = json_decode($_POST['owners_data'], true);
        if(!is_array($owners)) $owners = [];

        // Identify existing IDs for Soft Delete logic
        $submitted_owner_db_ids = array_column($owners, 'db_id'); // Changed from temp_id to db_id
        $submitted_owner_db_ids = array_filter($submitted_owner_db_ids, fn($id) => intval($id) > 0); 
        
        // 1. SOFT DELETE (Only delete IDs that are NOT in the submitted list)
        $where_clause = !empty($submitted_owner_db_ids) ? "AND id NOT IN (".implode(',', array_map('intval', $submitted_owner_db_ids)).")" : "";
        
        $stmt_soft_del_owners = $conn->prepare("UPDATE assessment_owners SET is_deleted=1, deleted_remark=?, deleted_at=NOW() WHERE assessment_id=? AND is_deleted=0 $where_clause");
        if (!$stmt_soft_del_owners) throw new Exception("Owner Soft Delete Prepare failed: " . $conn->error);
        
        $stmt_soft_del_owners->bind_param("si", $delete_remark, $aid);
        $stmt_soft_del_owners->execute();
        $deleted_owner_count = $stmt_soft_del_owners->affected_rows;
        $stmt_soft_del_owners->close();
        
        if ($deleted_owner_count > 0) {
            addLog($log, 'Soft Deleted Owners', 'Active', "Soft Deleted $deleted_owner_count records (Remark: $delete_remark)", $aid, $user_id);
        }

        // 2. INSERT/UPDATE Owners
        foreach($owners as $o){
            // ⭐ FIX: Use db_id to check if record exists, temp_id is just for UI
            $db_id = intval($o['db_id'] ?? 0); 
            
            $owner_data = [
                'owner_name' => val($o['owner_name'] ?? ''), 
                'father_husband_pan' => val($o['father_husband_pan'] ?? ''),
                'gender' => val($o['gender'] ?? ''),
                'mobile' => val($o['mobile'] ?? ''),
                'email' => val($o['email'] ?? '')
            ];
            
            $o_name = $owner_data['owner_name'];
            $o_pan = $owner_data['father_husband_pan'];
            $o_gender = $owner_data['gender'];
            $o_mobile = $owner_data['mobile'];
            $o_email = $owner_data['email'];

            if($db_id > 0){
                // Update existing (Logic: We have a valid DB ID)
                $old_o = $conn->query("SELECT * FROM assessment_owners WHERE id=$db_id AND assessment_id=$aid")->fetch_assoc();
                if($old_o){
                    foreach($owner_data as $k=>$v) {
                        $old_val = $old_o[$k] ?? ''; 
                        addLog($log,"owner.$db_id.$k",$old_val,$v,$aid,$user_id); 
                    }
                    
                    $stmt = $conn->prepare("UPDATE assessment_owners SET owner_name=?, father_husband_pan=?, gender=?, mobile=?, email=?, is_deleted=0, deleted_remark=NULL, deleted_at=NULL WHERE id=? AND assessment_id=?");
                    if (!$stmt) throw new Exception("Owner Update Prepare failed: " . $conn->error);
                    
                    $stmt->bind_param("sssssii", $o_name, $o_pan, $o_gender, $o_mobile, $o_email, $db_id, $aid);
                    if (!$stmt->execute()) throw new Exception("Owner Update Execute failed: " . $stmt->error);
                    $stmt->close();
                }
            } else {
                // Insert new (Logic: No valid DB ID found)
                addLog($log,"owner.new","","Owner Added: {$o_name}",$aid,$user_id);
                $stmt = $conn->prepare("INSERT INTO assessment_owners(assessment_id,owner_name,father_husband_pan,gender,mobile,email,is_deleted) VALUES(?,?,?,?,?,?,0)");
                if (!$stmt) throw new Exception("Owner Insert Prepare failed: " . $conn->error);
                
                $stmt->bind_param("isssss", $aid, $o_name, $o_pan, $o_gender, $o_mobile, $o_email);
                if (!$stmt->execute()) throw new Exception("Owner Insert Execute failed: " . $stmt->error);
                $stmt->close();
            }
        }
    }

    // ========================================================
    // ⭐ 4. FLOORS LOGIC (FIXED) ⭐
    // ========================================================
    if(isset($_POST['floors_data'])){ 
        $floors = json_decode($_POST['floors_data'], true);
        if(!is_array($floors)) $floors = [];
        
        // Identify existing IDs for Soft Delete logic
        $submitted_floor_db_ids = array_column($floors, 'db_id'); // Changed from temp_id to db_id
        $submitted_floor_db_ids = array_filter($submitted_floor_db_ids, fn($id) => intval($id) > 0);
        
        // 1. SOFT DELETE
        $soft_delete_floor_sql = "UPDATE assessment_floors SET is_deleted=1, deleted_remark=?, deleted_at=NOW() WHERE assessment_id=? AND is_deleted=0";
        if (!empty($submitted_floor_db_ids)) {
             $ids_str = implode(',', array_map('intval', $submitted_floor_db_ids));
             $soft_delete_floor_sql .= " AND id NOT IN ($ids_str)";
        }

        $stmt_del_floors = $conn->prepare($soft_delete_floor_sql);
        if (!$stmt_del_floors) throw new Exception("Floor Soft Delete Prepare failed: " . $conn->error);

        $stmt_del_floors->bind_param("si", $delete_remark, $aid);
        if (!$stmt_del_floors->execute()) throw new Exception("Floor Soft Delete Execute failed: " . $stmt_del_floors->error);
        $deleted_floor_count = $stmt_del_floors->affected_rows;
        $stmt_del_floors->close();

        if ($deleted_floor_count > 0) {
            addLog($log, 'Soft Deleted Floors', 'Active', "Soft Deleted $deleted_floor_count records (Remark: $delete_remark)", $aid, $user_id);
        }

        // 2. INSERT/UPDATE Floors
        foreach($floors as $f){
            // ⭐ FIX: Use db_id to check if record exists
            $db_id = intval($f['db_id'] ?? 0); 
            
            $floor_data = [
                'floor_no'=>val($f['floor_no'] ?? ''),
                'date_from'=>val($f['date_from'] ?? ''),
                'date_to'=>val($f['date_to'] ?? ''),
                'residential_type'=>val($f['residential_type'] ?? ''),
                'construction_type'=>val($f['construction_type'] ?? ''),
                'occupancy_type'=>val($f['occupancy_type'] ?? ''),
                'build_up_area'=>floatval($f['build_up_area'] ?? 0.00), 
                'usage_type'=>val($f['usage_type'] ?? ''),
                'non_residential_group'=>val($f['non_residential_group'] ?? ''),
                'property_name'=>val($f['property_name'] ?? '')
            ];
            
            $f_no = $floor_data['floor_no'];
            $f_from = $floor_data['date_from'];
            $f_to = $floor_data['date_to'];
            $f_res = $floor_data['residential_type'];
            $f_con = $floor_data['construction_type'];
            $f_occ = $floor_data['occupancy_type'];
            $f_area = $floor_data['build_up_area'];
            $f_usage = $floor_data['usage_type'];
            $f_nonres = $floor_data['non_residential_group'];
            $f_prop = $floor_data['property_name'];


            if($db_id > 0){
                // Update existing
                $old_f = $conn->query("SELECT * FROM assessment_floors WHERE id=$db_id AND assessment_id=$aid")->fetch_assoc();
                if($old_f){
                    foreach($floor_data as $k=>$v) addLog($log,"floor.$db_id.$k",$old_f[$k],$v,$aid,$user_id);
                    
                    $stmt = $conn->prepare("UPDATE assessment_floors SET floor_no=?, date_from=?, date_to=?, residential_type=?, construction_type=?, occupancy_type=?, build_up_area=?, usage_type=?, non_residential_group=?, property_name=?, is_deleted=0, deleted_remark=NULL, deleted_at=NULL WHERE id=? AND assessment_id=?");
                    if (!$stmt) throw new Exception("Floor Update Prepare failed: " . $conn->error);

                    $stmt->bind_param(
                        "ssssssdsssii",
                        $f_no, $f_from, $f_to, $f_res, $f_con, $f_occ, $f_area, $f_usage, $f_nonres, $f_prop, $db_id, $aid
                    );
                    
                    if (!$stmt->execute()) throw new Exception("Floor Update Execute failed: " . $stmt->error);
                    $stmt->close();
                }
            } else {
                // Insert new
                addLog($log,"floor.new","","Floor Added: {$f_no}",$aid,$user_id);
                $stmt = $conn->prepare("INSERT INTO assessment_floors(assessment_id,floor_no,date_from,date_to,residential_type,construction_type,occupancy_type,build_up_area,usage_type,non_residential_group,property_name,is_deleted) VALUES(?,?,?,?,?,?,?,?,?,?,?,0)");
                if (!$stmt) throw new Exception("Floor Insert Prepare failed: " . $conn->error);
                
                $stmt->bind_param(
                    "issssssdsss",
                    $aid, $f_no, $f_from, $f_to, $f_res, $f_con, $f_occ, $f_area, $f_usage, $f_nonres, $f_prop
                );
                
                if (!$stmt->execute()) throw new Exception("Floor Insert Execute failed: " . $stmt->error);
                $stmt->close();
            }
        }
    }


    // ===== 5. Upload All Files =====
    $upload_fields = [
        'center_image_gps', 'left_image_gps', 'right_image_gps', 
        'doc_proof', 'supporting_doc_1', 'supporting_doc_2', 'supporting_doc_3'
    ];
    
    $folder = "uploads/".$new_holding."/"; 
    if(!is_dir($folder)) @mkdir($folder,0777,true);

    foreach($upload_fields as $upload_field){
        if(isset($_FILES[$upload_field]) && $_FILES[$upload_field]['error'] == 0){
            $tmp_name = $_FILES[$upload_field]['tmp_name'];
            $name = $_FILES[$upload_field]['name'];
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $newname = uniqid($upload_field.'_', true) . ".$ext";
            
            $file_path_var = "uploads/".$new_holding."/".$newname; 

            if(move_uploaded_file($tmp_name,$folder.$newname)){
                
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
                if(strpos($upload_field, 'doc') !== false) {
                    $type = "Document"; 
                    $stmt=$conn->prepare("INSERT INTO assessment_media(assessment_id,file_path,media_type,is_deleted) VALUES(?,?,?,0)");
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

    // ===== 6. Log Changes to History Table =====
    if(count($log)>0){
        $stmt=$conn->prepare("INSERT INTO assessment_updates_history(assessment_id,field_name,old_value,new_value,updated_by,updated_at) VALUES(?,?,?,?,?,NOW())");
        if (!$stmt) throw new Exception("History Insert Prepare failed: " . $conn->error);
        
        foreach($log as $r){ 
            $aid_log = $r[0];
            $field_log = $r[1];
            $old_log = $r[2];
            $new_log = $r[3];
            $user_log = $r[4];

            $stmt->bind_param("issss", $aid_log, $field_log, $old_log, $new_log, $user_log); 
            if (!$stmt->execute()) throw new Exception("History Insert Execute failed: " . $stmt->error);
        }
        $stmt->close();
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
?>