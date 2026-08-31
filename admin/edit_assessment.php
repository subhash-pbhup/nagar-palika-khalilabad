<?php
session_start();

include("db.php");

// ====================================================
// ⭐ NEW: DISPLAY ERROR/SUCCESS MESSAGES FROM SESSION ⭐
// यह कोड सुनिश्चित करेगा कि एरर पेज पर बड़े टेक्स्ट में दिखे।
// ====================================================
if (isset($_SESSION['update_error'])) {
  // Show the error message clearly on the page
  echo '<div style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; margin: 15px 0; border-radius: 5px; font-weight: bold; word-break: break-all;">';
  echo 'UPDATE FAILED: ' . htmlspecialchars($_SESSION['update_error']);
  echo '</div>';
  unset($_SESSION['update_error']);
}
if (isset($_SESSION['update_success'])) {
  // Show the success message
  echo '<div style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 15px; margin: 15px 0; border-radius: 5px; font-weight: bold;">';
  echo 'SUCCESS: ' . htmlspecialchars($_SESSION['update_success']);
  echo '</div>';
  unset($_SESSION['update_success']);
}

// ----------------------------------------------------
// 1. Fetch Assessment ID
// ----------------------------------------------------
$assessment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($assessment_id === 0) {
  header("Location: view-assesment.php");
  exit;
}

// ----------------------------------------------------
// 2. Fetch Main Assessment Data
// ----------------------------------------------------
$sql_assessment = "SELECT * FROM assessments WHERE id = ? AND is_deleted = 0";
$stmt_assessment = $conn->prepare($sql_assessment);
$stmt_assessment->bind_param("i", $assessment_id);
$stmt_assessment->execute();
$result_assessment = $stmt_assessment->get_result();
$assessment_data = $result_assessment->fetch_assoc();
$stmt_assessment->close();

if (!$assessment_data) {
  header("Location: view-assesment.php");
  exit;
}

// Current location / Property ID data
$current_ward_id = (int)($assessment_data['ward_id'] ?? 0);
$current_mohalla_id = (int)($assessment_data['mohalla_id'] ?? 0);
$current_zone_id = (int)($assessment_data['zone_id'] ?? 1);
if ($current_zone_id <= 0) {
  $current_zone_id = 1;
}

// Backward compatibility: old records may only have `ward`
if ($current_ward_id <= 0 && !empty($assessment_data['ward'])) {
  $current_ward_id = (int)$assessment_data['ward'];
}

$current_ward = (string)$current_ward_id;
$current_holding = $assessment_data['new_holding'] ?? '';
$previous_holding_db = $assessment_data['previous_holding'] ?? '';
$current_property_id = $assessment_data['property_id'] ?? '';

// Fetch all wards
$wards_data = [];
$wards_result = $conn->query("SELECT ward_id, ward_no FROM wards ORDER BY ward_id ASC");
if ($wards_result) {
  $wards_data = $wards_result->fetch_all(MYSQLI_ASSOC);
}

// Fetch all mohallas
$mohalla_data = [];
$mohalla_result = $conn->query("SELECT mohalla_id, ward_id, mohalla_name FROM mohalla ORDER BY ward_id ASC, mohalla_id ASC");
if ($mohalla_result) {
  $mohalla_data = $mohalla_result->fetch_all(MYSQLI_ASSOC);
}


// ----------------------------------------------------
// 3. Fetch Owner Details
// ----------------------------------------------------
$sql_owners = "SELECT * FROM assessment_owners WHERE assessment_id = ? AND is_deleted = 0";
$stmt_owners = $conn->prepare($sql_owners);
$stmt_owners->bind_param("i", $assessment_id);
$stmt_owners->execute();
$result_owners = $stmt_owners->get_result();
$owners_data = $result_owners->fetch_all(MYSQLI_ASSOC);
$stmt_owners->close();

$js_owners = [];
foreach ($owners_data as $index => $owner) {
  $js_owners[] = [
    'temp_id' => $owner['id'] ?? ($index + 1),
    'owner_name' => $owner['owner_name'],
    'father_husband_pan' => $owner['father_husband_pan'],
    'gender' => $owner['gender'],
    'mobile' => $owner['mobile'],
    'email' => $owner['email'],
    'db_id' => $owner['id'] ?? 0
  ];
}
$owners_json = json_encode($js_owners);


// ----------------------------------------------------
// 4. Fetch Floor Details
// ----------------------------------------------------
$sql_floors = "SELECT * FROM assessment_floors WHERE assessment_id = ? AND is_deleted = 0";
$stmt_floors = $conn->prepare($sql_floors);
$stmt_floors->bind_param("i", $assessment_id);
$stmt_floors->execute();
$result_floors = $stmt_floors->get_result();
$floors_data = $result_floors->fetch_all(MYSQLI_ASSOC);
$stmt_floors->close();

$js_floors = [];
foreach ($floors_data as $index => $floor) {
  $js_floors[] = [
    'temp_id' => $floor['id'] ?? ($index + 1),
    'floor_no' => $floor['floor_no'],
    'date_from' => $floor['date_from'] ?? '',
    'date_to' => $floor['date_to'] ?? '',
    'residential_type' => $floor['residential_type'],
    'construction_type' => $floor['construction_type'],
    'occupancy_type' => $floor['occupancy_type'],
    'build_up_area' => (float)($floor['build_up_area'] ?? 0.00),
    'usage_type' => $floor['usage_type'],
    'non_residential_group' => $floor['non_residential_group'],
    'property_name' => $floor['property_name']
  ];
}
$floors_json = json_encode($js_floors);

// ----------------------------------------------------
// 5. Image Paths & Display Logic
// ----------------------------------------------------
$image_base_url = '';
$holding_folder = !empty($current_holding) ? htmlspecialchars($current_holding, ENT_QUOTES, 'UTF-8') . '/' : '';

function get_assessment_file_path($base_url, $holding_folder, $filename)
{
  if (!empty($filename)) {
    return $base_url . htmlspecialchars($filename);
  }
  return '';
}

// Helper function to show clean name
function get_clean_display_name($db_filename, $title)
{
  if (empty($db_filename)) return 'Choose new file';
  $ext = pathinfo($db_filename, PATHINFO_EXTENSION);
  return $title . '.' . $ext;
}

$center_image_path = get_assessment_file_path($image_base_url, $holding_folder, $assessment_data['center_image_gps'] ?? '');
$left_image_path = get_assessment_file_path($image_base_url, $holding_folder, $assessment_data['left_image_gps'] ?? '');
$right_image_path = get_assessment_file_path($image_base_url, $holding_folder, $assessment_data['right_image_gps'] ?? '');

$document_path = get_assessment_file_path($image_base_url, $holding_folder, $assessment_data['doc_proof'] ?? '');
$sup_doc1_path = get_assessment_file_path($image_base_url, $holding_folder, $assessment_data['supporting_doc_1'] ?? '');
$sup_doc2_path = get_assessment_file_path($image_base_url, $holding_folder, $assessment_data['supporting_doc_2'] ?? '');
$sup_doc3_path = get_assessment_file_path($image_base_url, $holding_folder, $assessment_data['supporting_doc_3'] ?? '');

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />

  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
  <title>Edit Assessment - Dashboard</title>
  <link href="favicon.png" rel="icon" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link href='css/mystyle.css' rel='stylesheet'>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    :root {
      --primary-color: #4f46e5;
      --primary-hover: #4338ca;
      --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.06);
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8fafc;
      color: #1f2937;
    }

    .donezo-card-form {
      background-color: #ffffff;
      border-radius: 1rem;
      box-shadow: var(--card-shadow);
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      border: 1px solid #e5e7eb;
    }

    .donezo-input {
      background-color: #f9fafb;
      border: 1px solid #d1d5db;
      color: #1f2937;
      transition: all 0.2s;
      padding: 0.75rem;
      border-radius: 0.5rem;
    }

    .donezo-input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 1px var(--primary-color);
      background-color: #fff;
    }

    .readonly-input {
      background-color: #e5e7eb !important;
      color: #4b5563 !important;
      cursor: default;
    }

    .req:after {
      content: " *";
      color: #ef4444;
      font-weight: 600;
    }

    .modal {
      display: none
    }

    .modal.open {
      display: flex;
      justify-content: center;
      align-items: center;
      overflow-y: auto;
    }

    .file-name {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-weight: 500;
      color: #4b5563;
    }

    .file-name .icon {
      font-size: 1rem;
      color: var(--primary-color);
    }

    .success-msg {
      color: #10b981;
      font-weight: 500;
      margin-top: 0.5rem;
      display: block;
      font-size: 0.75rem;
    }

    .error-msg {
      color: #ef4444;
      font-weight: 500;
      margin-top: 0.5rem;
      display: block;
      font-size: 0.75rem;
    }
  </style>
</head>

<body class="flex min-h-screen text-gray-800 bg-slate-50">
  <?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'surveyor'): ?>
    <?php include 'sidemenu.php' ?>
  <?php endif; ?>

  <div id="sidebar-backdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>
  <main class="flex-1 p-6 space-y-8 overflow-y-auto">

    <form id="assessmentForm" action="update_assessment_records.php" onsubmit="return prepareFormSubmission()" method="post" enctype="multipart/form-data" class="space-y-8">


      <input type="hidden" name="deleted_owner_ids" id="deleted_owner_ids" value="[]">
      <input type="hidden" name="deleted_floor_ids" id="deleted_floor_ids" value="[]">
      <input type="hidden" name="owners_data" id="owners_data">
      <input type="hidden" name="floors_data" id="floors_data">


      <input type="hidden" name="assessment_id" value="<?= htmlspecialchars($assessment_id) ?>">

      <input type="hidden" id="zone_id" name="zone_id" value="1">
      <input type="hidden" id="ward_id" name="ward_id" value="<?= (int)$current_ward_id ?>">
      <input type="hidden" id="mohalla_id" name="mohalla_id" value="<?= (int)$current_mohalla_id ?>">
      <input type="hidden" id="property_id" name="property_id" value="<?= htmlspecialchars($current_property_id, ENT_QUOTES, 'UTF-8') ?>">

      <!-- Backward compatibility with existing update code -->
      <input type="hidden" id="current_ward" name="ward" value="<?= htmlspecialchars($current_ward, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" id="initial_holding_no" name="initial_holding" value="<?= htmlspecialchars($current_holding, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="previous_holding_db" value="<?= htmlspecialchars($previous_holding_db, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" id="final_new_holding" name="new_holding" value="<?= htmlspecialchars($current_holding, ENT_QUOTES, 'UTF-8') ?>">

      <section class="donezo-card-form p-6">
        <h2 class="text-xl font-bold mb-4 border-b pb-2 text-gray-900">Property Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

          <div>
            <label class="block text-sm font-medium mb-2 req">Name of Municipality</label>
            <input name="municipality_name" type="text" value="<?= htmlspecialchars($assessment_data['municipality_name'] ?? 'Nagar Palika Parishad') ?>" class="w-full donezo-input readonly-input" readonly>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2 req">Year of Assessment</label>
            <input name="year_of_assessment" type="text" value="<?= htmlspecialchars($assessment_data['year_of_assessment'] ?? '2025-2026') ?>" class="w-full donezo-input readonly-input" readonly>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2 req">Ward No</label>
            <select id="ward_select" class="w-full donezo-input" required>
              <option value="">--Select Ward--</option>
              <?php foreach ($wards_data as $ward): ?>
                <option value="<?= (int)$ward['ward_id'] ?>"
                  <?= ((int)$ward['ward_id'] === $current_ward_id) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($ward['ward_no'], ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2 req">Mohalla Name</label>
            <select id="mohalla_select" class="w-full donezo-input" required disabled>
              <option value="">--Select Mohalla--</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Property ID</label>
            <input type="text"
              value="<?= htmlspecialchars($current_property_id, ENT_QUOTES, 'UTF-8') ?>"
              class="w-full donezo-input readonly-input"
              readonly
              placeholder="Property ID">
          </div>

          <div class="md:col-span-1">
            <label class="block text-sm font-medium mb-2 req">New Holding Number (Survey No)</label>

            <div class="flex items-center gap-2 mb-2">
              <input type="text" value="<?= $current_holding ?? '' ?>"
                class="w-full donezo-input readonly-input" id="new_holding_display"
                readonly placeholder="Current Holding Number">
              <button type="button" id="update_holding_btn" class="px-3 py-2 text-sm rounded-lg bg-red-600 text-white font-medium hover:bg-red-700 transition flex-shrink-0">
                Update Holding No
              </button>
            </div>

            <div id="new_holding_update_container" class="hidden mt-2 p-3 border border-dashed border-indigo-300 rounded-lg bg-indigo-50">
              <label class="block text-xs font-semibold mb-2 text-indigo-700">Enter New Holding Number:</label>
              <input name="new_holding_temp" type="text"
                placeholder="Enter new Holding Number (KLB-W<?= $current_ward ?? 'XX' ?>-YYYYY)"
                class="w-full donezo-input" id="new_holding_input_field"
                value="<?= $current_holding ?? '' ?>">
              <span id="holding_error_msg"></span>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2 req">Status of Property</label>
            <select name="property_status" id="property_status" class="w-full donezo-input" required>
              <option value="">--Select Status--</option>
              <option value="Old" <?= ($assessment_data['property_status'] ?? '') === 'Old' ? 'selected' : '' ?>>Old</option>
              <option value="New" <?= ($assessment_data['property_status'] ?? '') === 'New' ? 'selected' : '' ?>>New</option>
            </select>
          </div>

          <div id="oldFields" class="md:col-span-1" <?= ($assessment_data['property_status'] ?? '') !== 'Old' ? 'hidden' : '' ?>>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium mb-2 old-req-label <?= ($assessment_data['property_status'] ?? '') === 'Old' ? 'req' : '' ?>">Old Holding Number</label>
                <input name="old_holding" type="text" placeholder="Old Holding Number" id="old_holding_no"
                  value="<?= htmlspecialchars($assessment_data['old_holding'] ?? '') ?>" class="w-full donezo-input"
                  <?= ($assessment_data['property_status'] ?? '') === 'Old' ? 'required' : '' ?>>
              </div>

              <div>
                <label class="block text-sm font-medium mb-2 old-req-label <?= ($assessment_data['property_status'] ?? '') === 'Old' ? 'req' : '' ?>">Old ARV</label>
                <input name="old_pid" type="text" placeholder="Old PID" id="old_pid_no"
                  value="<?= htmlspecialchars($assessment_data['old_pid'] ?? '') ?>" class="w-full donezo-input"
                  <?= ($assessment_data['property_status'] ?? '') === 'Old' ? 'required' : '' ?>>
              </div>
            </div>
          </div>

          <script>
            document.addEventListener('DOMContentLoaded', function() {
              const statusSelect = document.querySelector('select[name="property_status"]');
              const oldFields = document.getElementById('oldFields');
              const oldHoldingInput = document.getElementById('old_holding_no');
              const oldPidInput = document.getElementById('old_pid_no');

              // Find the labels using their association with inputs (previous sibling)
              const oldHoldingLabel = oldHoldingInput.previousElementSibling;
              const oldPidLabel = oldPidInput.previousElementSibling;

              function handleChange() {
                const val = (statusSelect.value || '').trim().toLowerCase();
                const isOld = val === 'old';

                if (isOld) {
                  oldFields.removeAttribute('hidden');

                  // Set fields as REQUIRED and add visual asterisk (req class)
                  oldHoldingInput.setAttribute('required', 'required');
                  oldPidInput.setAttribute('required', 'required');
                  oldHoldingLabel.classList.add('req');
                  oldPidLabel.classList.add('req');
                } else {
                  oldFields.setAttribute('hidden', '');

                  // Remove REQUIRED and visual asterisk
                  oldHoldingInput.removeAttribute('required');
                  oldPidInput.removeAttribute('required');
                  oldHoldingLabel.classList.remove('req');
                  oldPidLabel.classList.remove('req');

                  // Logic: If changed to New or empty, clear the values immediately
                  if (val === 'new' || val === '') {
                    oldHoldingInput.value = '';
                    oldPidInput.value = '';
                  }
                }
              }

              // Initialize on load to ensure labels are correct if page loaded with 'Old' selected
              handleChange();

              // Attach event listener
              statusSelect.addEventListener('change', handleChange);
            });
          </script>
          <div>
            <label class="block text-sm font-medium mb-2 req">Property Type</label>
            <select name="property_type" class="w-full donezo-input" required>
              <option value="">--Select Property Type--</option>
              <?php $prop_type = $assessment_data['property_type'] ?? ''; ?>
              <option value="Non Residential" <?= $prop_type === 'Non Residential' ? 'selected' : '' ?>>Non Residential</option>
              <option value="Residential" <?= $prop_type === 'Residential' ? 'selected' : '' ?>>Residential</option>
              <option value="Mix" <?= $prop_type === 'Mix' ? 'selected' : '' ?>>Mix</option>
              <option value="Industrial" <?= $prop_type === 'Industrial' ? 'selected' : '' ?>>Industrial</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2 req">Road on which located</label>
            <select name="road" class="w-full donezo-input" required>
              <option value="">--Select Road--</option>
              <?php $road = $assessment_data['road'] ?? ''; ?>
              <option value="Upto 9 Meters" <?= $road === 'Upto 9 Meters' ? 'selected' : '' ?>>Upto 9 Meters</option>
              <option value="9 to 12 Meters" <?= $road === '9 to 12 Meters' ? 'selected' : '' ?>>9 to 12 Meters</option>
              <option value="12 to 24 Meters" <?= $road === '12 to 24 Meters' ? 'selected' : '' ?>>12 to 24 Meters</option>
              <option value="Above 24 Meters" <?= $road === 'Above 24 Meters' ? 'selected' : '' ?>>Above 24 Meters</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2 req">Area of Plot</label>
            <input name="plot_area" type="number" step="0.01" placeholder="Area of Plot (sq.ft)"
              value="<?= htmlspecialchars($assessment_data['plot_area'] ?? '') ?>" class="w-full donezo-input" required>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2 req">Buildup Type</label>
            <select name="building_type" class="w-full donezo-input" required>
              <option value="">--Select Buildup Type--</option>
              <?php $build_type = $assessment_data['building_type'] ?? ''; ?>
              <option value="RCC" <?= $build_type === 'RCC' ? 'selected' : '' ?>>RCC</option>
              <option value="ACC" <?= $build_type === 'ACC' ? 'selected' : '' ?>>ACC</option>
              <option value="Other" <?= $build_type === 'Other' ? 'selected' : '' ?>>Other</option>
              <option value="Vaccant Land" <?= $build_type === 'Vaccant Land' ? 'selected' : '' ?>>Vaccant Land</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Latitude</label>
            <input id="latitude" name="latitude" type="text" placeholder="Latitude"
              value="<?= htmlspecialchars($assessment_data['latitude'] ?? '') ?>" class="w-full donezo-input readonly-input" readonly>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Longitude</label>
            <input id="longitude" name="longitude" type="text" placeholder="Longitude"
              value="<?= htmlspecialchars($assessment_data['longitude'] ?? '') ?>" class="w-full donezo-input readonly-input" readonly>
          </div>

          <input id="location" type="hidden" name="location" value="<?= htmlspecialchars($assessment_data['location'] ?? '') ?>">

          <div class="mt-4 md:col-span-1 lg:col-span-3">
            <button type="button" onclick="getLocation()"
              class="px-6 py-3 rounded-full bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition">
              <i class="fa fa-map-pin" aria-hidden="true"></i>&nbsp; Update GPS Location
            </button>
          </div>

          <script>
            function getLocation() {
              if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                  function(position) {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    document.getElementById("latitude").value = lat;
                    document.getElementById("longitude").value = lon;
                    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`)
                      .then(response => response.json())
                      .then(data => {
                        document.getElementById("location").value = data.display_name;
                      })
                      .catch(err => console.error("Reverse Geocoding Error:", err));
                  },
                  function(error) {
                    alert("Location error: " + error.message);
                  }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                  }
                );
              } else {
                alert("Geolocation is not supported by this browser.");
              }
            }
          </script>

      </section>

      <section class="donezo-card-form p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-bold">Owner Details</h2>
          <button type="button" id="addOwnerBtn" onclick="openOwnerModal()" class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition"><i class="fa fa-plus" aria-hidden="true"></i>&nbsp; Add Owner</button>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr class="text-left">
                <th class="p-3">Sr. No</th>
                <th class="p-3">Name/Organisation</th>
                <th class="p-3">Father's/Husband Name / PAN</th>
                <th class="p-3">Gender</th>
                <th class="p-3">Mobile</th>
                <th class="p-3">Email</th>
                <th class="p-3">Edit</th>
                <th class="p-3">Delete</th>
              </tr>
            </thead>
            <tbody id="ownersBody" class="divide-y divide-gray-100 bg-white">
            </tbody>
          </table>
        </div>
        <input type="hidden" name="owners_json" id="owners_json">
      </section>

      <section class="donezo-card-form p-6">
        <h2 class="text-xl font-bold mb-4 border-b pb-2 text-gray-900">Address Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div>
            <label class="block text-sm font-medium mb-2 req">Property/House No</label>
            <input name="house_no" type="text" placeholder="Enter Property/House No"
              value="<?= htmlspecialchars($assessment_data['house_no'] ?? '') ?>" class="w-full donezo-input" required>
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Plot No</label>
            <input name="plot_no" type="text" placeholder="Plot No"
              value="<?= htmlspecialchars($assessment_data['plot_no'] ?? '') ?>" class="w-full donezo-input">
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Khata No</label>
            <input name="khata_no" type="text" placeholder="Khata No"
              value="<?= htmlspecialchars($assessment_data['khata_no'] ?? '') ?>" class="w-full donezo-input">
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Khasra No</label>
            <input name="khasra_no" type="text" placeholder="Khasra No"
              value="<?= htmlspecialchars($assessment_data['khasra_no'] ?? '') ?>" class="w-full donezo-input">
          </div>
          <div>
            <label class="block text-sm font-medium mb-2 req">Address Line 1</label>
            <input name="addr1" type="text" placeholder="Address Line 1"
              value="<?= htmlspecialchars($assessment_data['addr1'] ?? '') ?>" class="w-full donezo-input" required>
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Address Line 2</label>
            <input name="addr2" type="text" placeholder="Address Line 2"
              value="<?= htmlspecialchars($assessment_data['addr2'] ?? '') ?>" class="w-full donezo-input">
          </div>

          <div class="md:col-span-1 lg:col-span-1">
            <label class="block text-sm font-medium mb-2 req">Pincode</label>
            <input name="pincode" type="text" pattern="[0-9]{6}" maxlength="6" placeholder="Enter Pincode"
              value="<?= htmlspecialchars($assessment_data['pincode'] ?? '') ?>" class="w-full donezo-input" required>
          </div>
        </div>
      </section>

      <section class="donezo-card-form p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-bold">Floor Details</h2>
          <button type="button" onclick="openFloorModal()" class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700 transition"><i class="fa fa-plus" aria-hidden="true"></i>&nbsp; Add Floor</button>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr class="text-left">
                <th class="p-3">Sr. No</th>
                <th class="p-3">Floor No</th>
                <th class="p-3">Date From</th>
                <th class="p-3">Date To</th>
                <th class="p-3">Const. Type</th>
                <th class="p-3">Occupancy Type</th>
                <th class="p-3">Area (sq.ft)</th>
                <th class="p-3">Usage Type</th>
                <th class="p-3">Non-Res Group</th>
                <th class="p-3">Property Name</th>
                <th class="p-3">Edit</th>
                <th class="p-3">Delete</th>
              </tr>
            </thead>
            <tbody id="floorsBody" class="divide-y divide-gray-100 bg-white">
            </tbody>
          </table>
        </div>
        <input type="hidden" name="floors_json" id="floors_json">
      </section>

      <section class="donezo-card-form p-6">
        <h2 class="text-xl font-bold mb-4 border-b pb-2 text-gray-900">Other Taxes</h2>
        <label class="flex items-center gap-3 mb-2">
          <input type="checkbox" name="water_tax" class="rounded text-indigo-600 focus:ring-indigo-500"
            value="1" <?= (float)($assessment_data['water_tax'] ?? 0) > 0 ? 'checked' : '' ?>>
          <span>Water Tax (Check this box to apply Water Tax)</span>
        </label>
      </section>

      <section class="donezo-card-form p-6">
        <h2 class="text-xl font-bold mb-4 border-b pb-2 text-gray-900">Property Images</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

          <div>
            <label class="block text-sm font-medium mb-2">Current Center Image</label>
            <?php if ($center_image_path): ?>
              <a href="<?= $center_image_path ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 flex items-center mb-2"><i class="fa fa-eye"></i>&nbsp; View Current Image</a>
              <img id="preview_center" src="<?= $center_image_path ?>" alt="Center Image" class="w-full h-32 object-cover rounded-lg mb-2 border border-gray-200">
            <?php else: ?>
              <div class="w-full h-32 flex items-center justify-center rounded-lg mb-2 border-2 border-dashed border-gray-300 bg-gray-50">
                <p class="text-gray-500 font-medium text-center"><i class="fa fa-times-circle text-red-500 mr-2"></i> Image Not Uploaded.</p>
              </div>
              <img id="preview_center" src="#" alt="Center Image Preview" class="hidden w-full h-32 object-cover rounded-lg mb-2 border border-gray-200">
            <?php endif; ?>
            <label class="block text-sm font-medium mb-2 mt-4">Change Image (Optional)</label>
            <input type="file" name="center_image_gps" id="center_image_gps" accept="image/*" class="w-full donezo-input file-input-with-preview">
            <p class="text-sm mt-2 text-gray-800 file-name" data-current-file="<?= basename($center_image_path) ?>">
              <span class="icon"><i class="fa fa-image" aria-hidden="true"></i>&nbsp; </span>
              <span class="name"><?= get_clean_display_name($assessment_data['center_image_gps'] ?? '', 'Center Image') ?></span>
            </p>
            <input type="hidden" name="current_center_image" value="<?= htmlspecialchars($assessment_data['center_image_gps'] ?? '') ?>">
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Current Left Image</label>
            <?php if ($left_image_path): ?>
              <a href="<?= $left_image_path ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 flex items-center mb-2"><i class="fa fa-eye"></i>&nbsp; View Current Image</a>
              <img id="preview_left" src="<?= $left_image_path ?>" alt="Left Image" class="w-full h-32 object-cover rounded-lg mb-2 border border-gray-200">
            <?php else: ?>
              <div class="w-full h-32 flex items-center justify-center rounded-lg mb-2 border-2 border-dashed border-gray-300 bg-gray-50">
                <p class="text-gray-500 font-medium text-center"><i class="fa fa-times-circle text-red-500 mr-2"></i> Image Not Uploaded.</p>
              </div>
              <img id="preview_left" src="#" alt="Left Image Preview" class="hidden w-full h-32 object-cover rounded-lg mb-2 border border-gray-200">
            <?php endif; ?>
            <label class="block text-sm font-medium mb-2 mt-4">Change Image</label>
            <input type="file" name="left_image_gps" id="left_image_gps" accept="image/*" class="w-full donezo-input file-input-with-preview">
            <p class="text-sm mt-2 text-gray-800 file-name" data-current-file="<?= basename($left_image_path) ?>">
              <span class="icon"><i class="fa fa-image" aria-hidden="true"></i>&nbsp; </span>
              <span class="name"><?= get_clean_display_name($assessment_data['left_image_gps'] ?? '', 'Left Image') ?></span>
            </p>
            <input type="hidden" name="current_left_image" value="<?= htmlspecialchars($assessment_data['left_image_gps'] ?? '') ?>">
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Current Right Image</label>
            <?php if ($right_image_path): ?>
              <a href="<?= $right_image_path ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 flex items-center mb-2"><i class="fa fa-eye"></i>&nbsp; View Current Image</a>
              <img id="preview_right" src="<?= $right_image_path ?>" alt="Right Image" class="w-full h-32 object-cover rounded-lg mb-2 border border-gray-200">
            <?php else: ?>
              <div class="w-full h-32 flex items-center justify-center rounded-lg mb-2 border-2 border-dashed border-gray-300 bg-gray-50">
                <p class="text-gray-500 font-medium text-center"><i class="fa fa-times-circle text-red-500 mr-2"></i> Image Not Uploaded.</p>
              </div>
              <img id="preview_right" src="#" alt="Right Image Preview" class="hidden w-full h-32 object-cover rounded-lg mb-2 border border-gray-200">
            <?php endif; ?>
            <label class="block text-sm font-medium mb-2 mt-4">Change Image (Optional)</label>
            <input type="file" name="right_image_gps" id="right_image_gps" accept="image/*" class="w-full donezo-input file-input-with-preview">
            <p class="text-sm mt-2 text-gray-800 file-name" data-current-file="<?= basename($right_image_path) ?>">
              <span class="icon"><i class="fa fa-image" aria-hidden="true"></i>&nbsp; </span>
              <span class="name"><?= get_clean_display_name($assessment_data['right_image_gps'] ?? '', 'Right Image') ?></span>
            </p>
            <input type="hidden" name="current_right_image" value="<?= htmlspecialchars($assessment_data['right_image_gps'] ?? '') ?>">
          </div>
        </div>
      </section>

      <section class="donezo-card-form p-6">
        <h2 class="text-xl font-bold mb-4 border-b pb-2 text-gray-900">Documents</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

          <div>
            <label class="block text-sm font-medium mb-2 req">Doc Proof</label>
            <?php if ($document_path): ?>
              <a href="<?= $document_path ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 flex items-center mb-2"><i class="fa fa-file"></i>&nbsp; View Current</a>
            <?php else: ?>
              <p class="text-gray-500 mb-2 font-medium text-xs">No Document.</p>
            <?php endif; ?>

            <label class="block text-xs font-medium mb-1 text-gray-600">Update File:</label>
            <input type="file" name="doc_proof" class="w-full donezo-input file-input">
            <p class="text-sm mt-2 text-gray-800 file-name" data-current-file="<?= basename($document_path) ?>">
              <span class="icon"><i class="fa fa-file" aria-hidden="true"></i>&nbsp;</span>
              <span class="name"><?= get_clean_display_name($assessment_data['doc_proof'] ?? '', 'Doc Proof') ?></span>
            </p>
            <input type="hidden" name="current_doc_proof" value="<?= htmlspecialchars($assessment_data['doc_proof'] ?? '') ?>">
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Supporting Document 1</label>
            <?php if ($sup_doc1_path): ?>
              <a href="<?= $sup_doc1_path ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 flex items-center mb-2"><i class="fa fa-file"></i>&nbsp; View Current</a>
            <?php else: ?>
              <p class="text-gray-500 mb-2 font-medium text-xs">No Document.</p>
            <?php endif; ?>

            <label class="block text-xs font-medium mb-1 text-gray-600">Update File:</label>
            <input type="file" name="supporting_doc_1" class="w-full donezo-input file-input">
            <p class="text-sm mt-2 text-gray-800 file-name" data-current-file="<?= basename($sup_doc1_path) ?>">
              <span class="icon"><i class="fa fa-file" aria-hidden="true"></i>&nbsp;</span>
              <span class="name"><?= get_clean_display_name($assessment_data['supporting_doc_1'] ?? '', 'Supporting Document 1') ?></span>
            </p>
            <input type="hidden" name="current_supporting_doc_1" value="<?= htmlspecialchars($assessment_data['supporting_doc_1'] ?? '') ?>">
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Supporting Document 2</label>
            <?php if ($sup_doc2_path): ?>
              <a href="<?= $sup_doc2_path ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 flex items-center mb-2"><i class="fa fa-file"></i>&nbsp; View Current</a>
            <?php else: ?>
              <p class="text-gray-500 mb-2 font-medium text-xs">No Document.</p>
            <?php endif; ?>

            <label class="block text-xs font-medium mb-1 text-gray-600">Update File:</label>
            <input type="file" name="supporting_doc_2" class="w-full donezo-input file-input">
            <p class="text-sm mt-2 text-gray-800 file-name" data-current-file="<?= basename($sup_doc2_path) ?>">
              <span class="icon"><i class="fa fa-file" aria-hidden="true"></i>&nbsp;</span>
              <span class="name"><?= get_clean_display_name($assessment_data['supporting_doc_2'] ?? '', 'Supporting Document 2') ?></span>
            </p>
            <input type="hidden" name="current_supporting_doc_2" value="<?= htmlspecialchars($assessment_data['supporting_doc_2'] ?? '') ?>">
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Supporting Document 3</label>
            <?php if ($sup_doc3_path): ?>
              <a href="<?= $sup_doc3_path ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 flex items-center mb-2"><i class="fa fa-file"></i>&nbsp; View Current</a>
            <?php else: ?>
              <p class="text-gray-500 mb-2 font-medium text-xs">No Document.</p>
            <?php endif; ?>

            <label class="block text-xs font-medium mb-1 text-gray-600">Update File:</label>
            <input type="file" name="supporting_doc_3" class="w-full donezo-input file-input">
            <p class="text-sm mt-2 text-gray-800 file-name" data-current-file="<?= basename($sup_doc3_path) ?>">
              <span class="icon"><i class="fa fa-file" aria-hidden="true"></i>&nbsp;</span>
              <span class="name"><?= get_clean_display_name($assessment_data['supporting_doc_3'] ?? '', 'Supporting Document 3') ?></span>
            </p>
            <input type="hidden" name="current_supporting_doc_3" value="<?= htmlspecialchars($assessment_data['supporting_doc_3'] ?? '') ?>">
          </div>

        </div>
      </section>

      <div class="text-right flex justify-end gap-4">
        <a href="view-assesment.php" class="px-6 py-3 rounded-full bg-red-600 text-white font-medium hover:bg-red-700 transition">Cancel</a>
        <button type="submit" id="update_record_btn" class="px-6 py-3 rounded-full bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition">Update Record</button>
      </div>

    </form>

    <footer class="text-center text-gray-500 text-sm mt-6"> 2025 Nagar Parishad - All Rights Reserved.</footer>
  </main>

  <div id="ownerModal" class="modal fixed inset-0 bg-black/50 items-center justify-center z-50">
    <div class="bg-white donezo-card-form p-6 w-full max-w-3xl mx-4">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold" id="ownerModalTitle">Add Owner</h3>
        <button type="button" class="text-gray-500" onclick="closeOwnerModal()">✖</button>
      </div>
      <input type="hidden" id="o_edit_temp_id">
      <input type="hidden" id="o_edit_db_id" value="0">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-2 req">Title</label>
          <select id="o_title" class="donezo-input w-full" required>
            <option value="">Select</option>
            <option>Mr.</option>
            <option>Mrs.</option>
            <option>Ms.</option>
            <option>M/S</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-2 req">Owner Name / Organisation Name</label>
          <input id="o_name" type="text" placeholder="Owner Name" class="donezo-input w-full" required>
        </div>
        <div>
          <label class="block text-sm font-medium mb-2 req">Father's/Husband Name / PAN</label>
          <input id="o_careof" type="text" placeholder="Father's/Husband Name / PAN" class="donezo-input w-full" required>
        </div>
        <div>
          <label class="block text-sm font-medium mb-2 req">Gender</label>
          <select id="o_gender" class="donezo-input w-full" required>
            <option value="">Select</option>
            <option>Male</option>
            <option>Female</option>
            <option>Other</option>
            <option>N/A</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-2 req">Mobile Number</label>
          <input id="o_mobile" type="text" pattern="[0-9]{10}" maxlength="10" placeholder="10 Digit Mobile No" class="donezo-input w-full" required onblur="checkOwnerUniqueness('mobile')">
          <span id="o_mobile_error" class="error-msg"></span>
        </div>
        <div>
          <label class="block text-sm font-medium mb-2">Email ID</label>
          <input id="o_email" type="email" placeholder="Email ID" class="donezo-input w-full" onblur="checkOwnerUniqueness('email')">
          <span id="o_email_error" class="error-msg"></span>
        </div>
      </div>
      <div class="text-right mt-6">
        <button type="button" onclick="saveOwner()" class="px-6 py-3 rounded-full bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition" id="saveOwnerBtn">Save Owner</button>
      </div>
    </div>
  </div>

  <div id="floorModal" class="modal fixed inset-0 bg-black/50 items-center justify-center z-50">
    <div class="bg-white donezo-card-form p-6 w-full max-w-5xl mx-4">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold" id="floorModalTitle">Add Floor</h3>
        <button type="button" class="text-gray-500" onclick="closeFloorModal()">✖</button>
      </div>
      <input type="hidden" id="f_edit_id">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

        <div>
          <label class="block text-sm font-medium mb-2 req">Floor No</label>
          <select id="f_floor_no" class="donezo-input w-full" required>
            <option value="">---Select Floor No---</option>
            <option value="Basement -1">Basement -1</option>
            <option value="Ground Floor - 0">Ground Floor - 0</option>
            <option value="First Floor - 2">First Floor - 2</option>
            <option value="Second Floor - 3">Second Floor - 3</option>
            <option value="Third Floor - 4">Third Floor - 4</option>
            <option value="Fourth Floor - 5">Fourth Floor - 5</option>
            <option value="Fifth Floor - 6">Fifth Floor - 6</option>
            <option value="Sixth Floor - 7">Sixth Floor - 7</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-2 req">Date From</label>
          <input id="f_date_from" type="date" class="donezo-input w-full" required>
        </div>
        <div>
          <label class="block text-sm font-medium mb-2 req">Date To</label>
          <input id="f_date_to" type="date" class="donezo-input w-full" required>
        </div>

        <div>
          <label class="block text-sm font-medium mb-2 req">Construction Type</label>
          <select id="f_construction" class="donezo-input w-full" required>
            <option value="">---Select Construction Type---</option>
            <option value="RCC">RCC</option>
            <option value="ACC">ACC</option>
            <option value="Others">Others</option>
            <option value="Vacant Land">Vacant Land</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-2 req">Occupancy Type</label>
          <select id="f_occupancy" class="donezo-input w-full" required>
            <option value="">---Select Occupancy Type---</option>
            <option value="Tenanted (T)">Tenanted (T)</option>
            <option value="Self-Occupied (S)">Self-Occupied (S)</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-2 req">Build Up Area (sq.ft)</label>
          <input id="f_buildup" type="number" step="0.01" placeholder="Enter Build Up Area" class="donezo-input w-full" required>
        </div>

        <div>
          <label class="block text-sm font-medium mb-2 req">Usage Type</label>
          <select id="f_usage" class="donezo-input w-full" required>
            <option value="">---Select Usage Type---</option>
            <option value="Fully Residential">Fully Residential</option>
            <option value="Non-Residential">Non-Residential</option>
            <option value="Industrial">Industrial</option>
          </select>
        </div>

        <div id="non_res_group_div" class="hidden">
          <label class="block text-sm font-medium mb-2 req">Non Residential Properties</label>
          <select id="f_non_residential_group" class="donezo-input w-full" required>
            <option value="">---Non Residential Properties---</option>
          </select>
        </div>

        <div id="property_name_div" class="hidden">
          <label class="block text-sm font-medium mb-2 req">Select Properties</label>
          <select id="f_property_name" class="donezo-input w-full" required>
            <option value="">---Select Properties---</option>
          </select>
        </div>

        <input type="hidden" id="f_residential_type" value="" />

      </div>
      <div class="text-right mt-6">
        <button type="button" onclick="saveFloor()" class="px-6 py-3 rounded-full bg-green-600 text-white font-medium hover:bg-green-700 transition">Save Floor</button>
      </div>
    </div>
  </div>

  <script>
    // -----------------------------------------------------------------
    // DATA AND CONSTANTS
    // -----------------------------------------------------------------
    const PROPERTY_DATA = {
      group1: ["Govt. Hostel", "Govt. And Non Govt. Educational Centers & Schools", "Swimming Pool", "Play Ground & Gym", "Physical Fitness Center", "Theatre (Being Used Only For Cultural Events, Don't Organise Marriage/Party Functions)", "Music And Dance Academy", "Micro And Small Industries", "Single Screen Cinema Halls (Not In Malls)", "Tea & Milk Shop (120 Sq. Ft./11.14 Sq.Meter)", "Egg Shop", "Laundry/Washerman Shops", "Barber/Hairdresser Shop (Don't Have More Than Two Chairs, Ac & Cooler)", "Tailor Shop"],
      group2: ["Medical Store", "Each Type Of Commercial Complexes", "Shops In Market Area", "Building Material Store", "Non Govt. Coaching Center"],
      group3: ["Govt. /Semi Govt./Private Offices", "Public Undertakings", "State Corporation & Boards Etc.", "Clinics", "Polyclinics", "Dental Clinics", "Diagnostic Center", "Pathology Labs", "Nursing Homes", "Technical University", "Medical College", "Dental College", "Engineering College", "Management Institute", "Law Institute", "Other Commercial Education Centers", "Petrol Pump", "Gas Agency", "Depots & Godowns Etc.", "Community Halls", "Welfare Pavilion", "Marriage Clubs", "Auditorium", "Community Centers", "Medium And Large Industries", "Restaurant", "All Type Of Hotels", "Tourist Places", "Buildings Having Tower & Advertisement Boards", "T.V. Tower", "Mobile Tower", "All Other Buildings Having Tower On Roof Or In Vacant Area", "Banks", "Bank ATM", "Finance Companies", "Private Offices And Buildings", "Malls", "Pubs", "Bars", "Beer Shops", "Hotels Having Beer And Food Serving Facility"],
      group4: ["All Other Commercial Buildings Which Are Not Included In Above Mentioned"]
    };

    const NON_RES_GROUPS = [{
        value: 'group1',
        label: '1- Govt. Hostel, Govt. And Non Govt. Educational Centers & Schools, Swimming Pool, Play Ground & Gym...'
      },
      {
        value: 'group2',
        label: '2- Medical Store, Each Type Of Commercial Complexes...'
      },
      {
        value: 'group3',
        label: '3- Govt. /Semi Govt./Private Offices, Public Undertakings...'
      },
      {
        value: 'group4',
        label: '4- All Other Commercial Buildings Which Are Not Included In Above Mentioned.'
      }
    ];

    // Initial Data from PHP
    let floors = <?= $floors_json ?>;
    let floorTempIdCounter = floors.length > 0 ? Math.max(...floors.map(f => f.temp_id)) + 1 : 1;

    let owners = <?= $owners_json ?>;
    let ownerTempIdCounter = owners.length > 0 ? Math.max(...owners.map(o => o.temp_id)) + 1 : 1;

    // -----------------------------------------------------------------
    // WARD -> MOHALLA LOGIC
    // -----------------------------------------------------------------
    const MOHALLA_DATA = <?= json_encode(
                            $mohalla_data,
                            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                          ) ?>;

    const INITIAL_WARD_ID = <?= (int)$current_ward_id ?>;
    const INITIAL_MOHALLA_ID = <?= (int)$current_mohalla_id ?>;

    function loadMohallas(wardId, selectedMohallaId = '') {
      const mohallaSelect = document.getElementById('mohalla_select');
      const wardIdInput = document.getElementById('ward_id');
      const mohallaIdInput = document.getElementById('mohalla_id');
      const currentWardInput = document.getElementById('current_ward');

      mohallaSelect.innerHTML = '<option value="">--Select Mohalla--</option>';
      mohallaSelect.disabled = true;
      wardIdInput.value = wardId || '';
      currentWardInput.value = wardId || '';
      mohallaIdInput.value = '';

      if (!wardId) return;

      const filtered = MOHALLA_DATA.filter(function(item) {
        return String(item.ward_id) === String(wardId);
      });

      filtered.forEach(function(item) {
        const option = document.createElement('option');
        option.value = item.mohalla_id;
        option.textContent = item.mohalla_name;

        if (String(item.mohalla_id) === String(selectedMohallaId)) {
          option.selected = true;
        }

        mohallaSelect.appendChild(option);
      });

      mohallaSelect.disabled = filtered.length === 0;

      if (selectedMohallaId && filtered.some(
          item => String(item.mohalla_id) === String(selectedMohallaId)
        )) {
        mohallaIdInput.value = selectedMohallaId;
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
      const wardSelect = document.getElementById('ward_select');
      const mohallaSelect = document.getElementById('mohalla_select');

      loadMohallas(INITIAL_WARD_ID, INITIAL_MOHALLA_ID);

      wardSelect.addEventListener('change', function() {
        loadMohallas(this.value, '');
      });

      mohallaSelect.addEventListener('change', function() {
        document.getElementById('mohalla_id').value = this.value;
      });
    });

    // -----------------------------------------------------------------
    // HOLDING NUMBER VALIDATION LOGIC
    // -----------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', function() {
      const holdingUpdateBtn = document.getElementById('update_holding_btn');
      const updateContainer = document.getElementById('new_holding_update_container');
      const newHoldingInput = document.getElementById('new_holding_input_field');
      const finalNewHolding = document.getElementById('final_new_holding');
      const wardInput = document.getElementById('current_ward');
      const initialHoldingNo = document.getElementById('initial_holding_no').value;
      const errorMsgSpan = document.getElementById('holding_error_msg');
      const updateButton = document.getElementById('update_record_btn');
      const assessmentId = <?= $assessment_id ?>;

      holdingUpdateBtn.addEventListener('click', function() {
        const isHidden = updateContainer.classList.toggle('hidden');
        if (!isHidden) {
          newHoldingInput.value = finalNewHolding.value;
          holdingUpdateBtn.textContent = 'Cancel Update';
        } else {
          newHoldingInput.value = initialHoldingNo;
          finalNewHolding.value = initialHoldingNo;
          errorMsgSpan.textContent = '';
          holdingUpdateBtn.textContent = 'Update Holding No';
          updateButton.disabled = false;
        }
      });

      function runUniquenessCheck() {
        const newHolding = newHoldingInput.value.trim();
        const ward = wardInput.value.trim();
        const expectedFormat = new RegExp(`^KLB-W${ward}-\\d{5}$`);

        errorMsgSpan.textContent = '';
        updateButton.disabled = true;

        if (newHolding === '') {
          errorMsgSpan.textContent = 'New Holding Number is required.';
          errorMsgSpan.className = 'error-msg';
          return;
        }

        if (!expectedFormat.test(newHolding)) {
          errorMsgSpan.textContent = `Error: Invalid Holding Number format. Must be KLB-W${ward}-XXXXX (5 digits).`;
          errorMsgSpan.className = 'error-msg';
          return;
        }

        if (newHolding === initialHoldingNo) {
          errorMsgSpan.textContent = 'Holding number is original. No change required.';
          errorMsgSpan.className = 'success-msg';
          updateButton.disabled = false;
          finalNewHolding.value = newHolding;
          return;
        }

        errorMsgSpan.textContent = 'Checking Holding Number availability...';
        errorMsgSpan.className = 'text-blue-500 font-medium mt-1 text-xs';

        fetch('check_holding_exists.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `new_holding=${encodeURIComponent(newHolding)}&ward=${encodeURIComponent(ward)}&assessment_id=${assessmentId}`
          })
          .then(response => response.json())
          .then(data => {
            if (data.exists) {
              errorMsgSpan.textContent = data.message;
              errorMsgSpan.className = 'error-msg';
              updateButton.disabled = true;
              finalNewHolding.value = initialHoldingNo;
            } else {
              errorMsgSpan.textContent = 'Success: New Holding Number is available.';
              errorMsgSpan.className = 'success-msg';
              updateButton.disabled = false;
              finalNewHolding.value = newHolding;
            }
          })
          .catch(error => {
            console.error('Error during AJAX check:', error);
            errorMsgSpan.textContent = 'Error: Could not check Holding Number.';
            errorMsgSpan.className = 'error-msg';
            updateButton.disabled = true;
            finalNewHolding.value = initialHoldingNo;
          });
      }

      newHoldingInput.addEventListener('change', runUniquenessCheck);
      newHoldingInput.addEventListener('blur', runUniquenessCheck);
    });

    // -----------------------------------------------------------------
    // FLOOR LOGIC
    // -----------------------------------------------------------------

    const floorModal = document.getElementById('floorModal');
    const f_floor_no = document.getElementById('f_floor_no');
    const f_construction = document.getElementById('f_construction');
    const f_occupancy = document.getElementById('f_occupancy');
    const f_buildup = document.getElementById('f_buildup');
    const f_usage = document.getElementById('f_usage');
    const f_non_residential_group = document.getElementById('f_non_residential_group');
    const f_property_name = document.getElementById('f_property_name');
    // Date Fields
    const f_date_from = document.getElementById('f_date_from');
    const f_date_to = document.getElementById('f_date_to');

    const nonResGroupDiv = document.getElementById('non_res_group_div');
    const propertyNameDiv = document.getElementById('property_name_div');

    function populateNonResGroupDropdown() {
      f_non_residential_group.innerHTML = '<option value="">---Non Residential Properties---</option>';
      NON_RES_GROUPS.forEach(group => {
        const option = document.createElement('option');
        option.value = group.value;
        option.textContent = group.label;
        f_non_residential_group.appendChild(option);
      });
    }

    function handleNonResidentialGroupChange(selectedValue = null) {
      const groupKey = f_non_residential_group.value;
      f_property_name.innerHTML = '<option value="">---Select Properties---</option>';
      if (groupKey && PROPERTY_DATA[groupKey]) {
        const properties = PROPERTY_DATA[groupKey];
        properties.forEach(prop => {
          const option = document.createElement('option');
          option.value = prop;
          option.textContent = prop;
          f_property_name.appendChild(option);
        });
      }
      if (selectedValue) {
        f_property_name.value = selectedValue;
      }
    }
    f_non_residential_group.addEventListener('change', () => handleNonResidentialGroupChange());

    function handleUsageTypeChange() {
      const usage = f_usage.value;
      f_non_residential_group.removeAttribute('required');
      f_property_name.removeAttribute('required');
      nonResGroupDiv.classList.add('hidden');
      propertyNameDiv.classList.add('hidden');
      f_non_residential_group.value = '';
      f_property_name.value = '';
      handleNonResidentialGroupChange();

      if (usage === 'Non-Residential' || usage === 'Industrial') {
        nonResGroupDiv.classList.remove('hidden');
        propertyNameDiv.classList.remove('hidden');
        f_non_residential_group.setAttribute('required', 'required');
        f_property_name.setAttribute('required', 'required');
      }
    }
    f_usage.addEventListener('change', handleUsageTypeChange);

    function setSelectValueByAnyMatch(selectElem, val) {
      if (!val && val !== 0) {
        selectElem.value = '';
        return;
      }
      val = String(val).trim();
      let exactByValue = Array.from(selectElem.options).find(opt => String(opt.value).trim() === val);
      if (exactByValue) {
        selectElem.value = exactByValue.value;
        return;
      }
      const exactByText = Array.from(selectElem.options).find(opt =>
        String(opt.textContent)
        .replace(/\s*\(S\)\s*$/, '')
        .replace(/\s*\(T\)\s*$/, '')
        .trim() === val
      );
      if (exactByText) {
        selectElem.value = exactByText.value;
        return;
      }
      selectElem.value = '';
    }

    function openFloorModal(tempId = null) {
      floorModal.classList.add('open');

      // Reset form
      f_floor_no.value = '';
      f_construction.value = '';
      f_occupancy.value = '';
      f_buildup.value = '';
      f_usage.value = '';
      f_non_residential_group.value = '';
      f_property_name.value = '';
      f_date_from.value = '';
      f_date_to.value = '';

      handleUsageTypeChange();
      document.getElementById('floorModalTitle').textContent = 'Add Floor';
      document.getElementById('f_edit_id').value = '';

      if (tempId !== null) {
        const floor = floors.find(f => f.temp_id === tempId);
        if (floor) {
          document.getElementById('floorModalTitle').textContent = 'Edit Floor';
          document.getElementById('f_edit_id').value = tempId;

          setSelectValueByAnyMatch(f_floor_no, floor.floor_no);
          setSelectValueByAnyMatch(f_construction, floor.construction_type);
          setSelectValueByAnyMatch(f_occupancy, floor.occupancy_type);
          f_buildup.value = floor.build_up_area || '';
          f_usage.value = floor.usage_type || '';

          f_date_from.value = floor.date_from || '';
          f_date_to.value = floor.date_to || '';

          handleUsageTypeChange();

          if (floor.usage_type === 'Non-Residential' || floor.usage_type === 'Industrial') {
            setSelectValueByAnyMatch(f_non_residential_group, floor.non_residential_group || '');
            f_non_residential_group.dispatchEvent(new Event('change'));
            setSelectValueByAnyMatch(f_property_name, floor.property_name);
          }
        }
      }
    }

    function closeFloorModal() {
      floorModal.classList.remove('open');
    }

    function saveFloor() {
      if (!f_floor_no.value || !f_construction.value || !f_occupancy.value || !f_buildup.value || !f_usage.value || !f_date_from.value || !f_date_to.value) {
        alert('Please fill all mandatory fields including Date From and Date To.');
        return;
      }

      const isNonResOrIndustrial = f_usage.value === 'Non-Residential' || f_usage.value === 'Industrial';
      if (isNonResOrIndustrial && (!f_non_residential_group.value || !f_property_name.value)) {
        alert('Non Residential Properties and Select Properties are required.');
        return;
      }

      // Check whether this is an existing floor or a new floor.
      const editId = document.getElementById('f_edit_id').value;
      const editTempId = editId ? parseInt(editId, 10) : 0;
      const existingFloor = editTempId ?
        floors.find(f => Number(f.temp_id) === editTempId) :
        null;

      const newFloor = {
        // IMPORTANT:
        // Existing floor keeps its real DB ID.
        // New floor gets db_id = 0.
        db_id: existingFloor ? Number(existingFloor.db_id || 0) : 0,

        floor_no: f_floor_no.value.trim(),
        date_from: f_date_from.value,
        date_to: f_date_to.value,
        residential_type: f_usage.value === 'Fully Residential' ? 'Residential' : '',
        construction_type: f_construction.value,
        occupancy_type: f_occupancy.value,
        build_up_area: parseFloat(f_buildup.value),
        usage_type: f_usage.value,
        non_residential_group: isNonResOrIndustrial ? f_non_residential_group.value : '',
        property_name: isNonResOrIndustrial ? f_property_name.value : ''
      };

      if (existingFloor) {

        // Preserve temp_id and DB id while updating the frontend object.
        floors = floors.map(function(floor) {

          if (Number(floor.temp_id) === editTempId) {
            return {
              ...floor,
              ...newFloor,
              temp_id: floor.temp_id,
              db_id: Number(floor.db_id || 0)
            };
          }

          return floor;
        });

      } else {

        // New floor: frontend temp ID + DB ID 0.
        newFloor.temp_id = floorTempIdCounter++;
        newFloor.db_id = 0;

        floors.push(newFloor);
      }

      closeFloorModal();
      renderFloorsTable();
    }

    function deleteFloor(tempId) {
      if (confirm('Are you sure you want to delete this floor record?')) {
        floors = floors.filter(f => f.temp_id !== tempId);
        renderFloorsTable();
      }
    }

    function renderFloorsTable() {
      const floorsBody = document.getElementById('floorsBody');
      floorsBody.innerHTML = '';

      if (floors.length === 0) {
        floorsBody.innerHTML = '<tr><td colspan="12" class="p-3 text-center text-gray-500">No floor details added.</td></tr>';
      } else {
        floors.forEach((floor, index) => {
          const row = floorsBody.insertRow();
          row.className = 'hover:bg-gray-50';

          row.insertCell().textContent = index + 1;
          row.insertCell().textContent = floor.floor_no;
          row.insertCell().textContent = floor.date_from || '-';
          row.insertCell().textContent = floor.date_to || '-';
          row.insertCell().textContent = floor.construction_type;
          row.insertCell().textContent = floor.occupancy_type;
          row.insertCell().textContent = floor.build_up_area.toFixed(2);
          row.insertCell().textContent = floor.usage_type;

          const groupItem = NON_RES_GROUPS.find(g => g.value === floor.non_residential_group);
          const groupDisplay = groupItem ? groupItem.label.split('-')[0].trim() : 'N/A';
          row.insertCell().textContent = floor.non_residential_group ? groupDisplay : 'N/A';
          row.insertCell().textContent = floor.property_name || 'N/A';

          const editCell = row.insertCell();
          editCell.innerHTML = `<button type="button" onclick="openFloorModal(${floor.temp_id})" class="text-indigo-600 hover:text-indigo-800 p-1"><i class="fa fa-pencil"></i></button>`;

          const deleteCell = row.insertCell();
          deleteCell.innerHTML = `<button type="button" onclick="deleteFloor(${floor.temp_id})" class="text-red-600 hover:text-red-800 p-1"><i class="fa fa-trash"></i></button>`;
        });
      }
      // Keep the real DB ID in the JSON sent to PHP.
      document.getElementById('floors_json').value = JSON.stringify(
        floors.map(function(floor) {
          return {
            temp_id: Number(floor.temp_id || 0),
            db_id: Number(floor.db_id || 0),
            floor_no: floor.floor_no || '',
            date_from: floor.date_from || '',
            date_to: floor.date_to || '',
            residential_type: floor.residential_type || '',
            construction_type: floor.construction_type || '',
            occupancy_type: floor.occupancy_type || '',
            build_up_area: Number(floor.build_up_area || 0),
            usage_type: floor.usage_type || '',
            non_residential_group: floor.non_residential_group || '',
            property_name: floor.property_name || ''
          };
        })
      );
    }

    // -----------------------------------------------------------------
    // OWNER LOGIC (UPDATED FOR UNIQUENESS CHECK WITH DB)
    // -----------------------------------------------------------------

    function renderOwnersTable() {
      const ownersBody = document.getElementById('ownersBody');
      ownersBody.innerHTML = '';

      if (owners.length === 0) {
        ownersBody.innerHTML = '<tr><td colspan="8" class="p-3 text-center text-gray-500">No owner details added.</td></tr>';
      } else {
        owners.forEach((owner, index) => {
          const row = ownersBody.insertRow();
          row.className = 'hover:bg-gray-50';

          row.insertCell().textContent = index + 1;
          row.insertCell().textContent = owner.owner_name;
          row.insertCell().textContent = owner.father_husband_pan;
          row.insertCell().textContent = owner.gender;
          row.insertCell().textContent = owner.mobile;
          row.insertCell().textContent = owner.email;

          const editCell = row.insertCell();
          editCell.innerHTML = `<button type="button" onclick="openOwnerModal(${owner.temp_id})" class="text-indigo-600 hover:text-indigo-800 p-1"><i class="fa fa-pencil"></i></button>`;

          const deleteCell = row.insertCell();
          deleteCell.innerHTML = `<button type="button" onclick="deleteOwner(${owner.temp_id})" class="text-red-600 hover:text-red-800 p-1"><i class="fa fa-trash"></i></button>`;
        });
      }
      document.getElementById('owners_json').value = JSON.stringify(owners);

      const addOwnerBtn = document.getElementById('addOwnerBtn');
      if (addOwnerBtn) {
        addOwnerBtn.disabled = owners.length >= 1;
        addOwnerBtn.classList.toggle('opacity-50', owners.length >= 1);
        addOwnerBtn.classList.toggle('cursor-not-allowed', owners.length >= 1);
        addOwnerBtn.innerHTML = owners.length >= 1 ?
          '<i class="fa fa-check"></i>&nbsp; Owner Added' :
          '<i class="fa fa-plus"></i>&nbsp; Add Owner';
      }
    }

    function openOwnerModal(tempId = null) {
      if (tempId === null && owners.length >= 1) {
        alert('Only one owner can be added to a property.');
        return;
      }

      const o_title = document.getElementById('o_title');
      const o_name = document.getElementById('o_name');
      const o_careof = document.getElementById('o_careof');
      const o_gender = document.getElementById('o_gender');
      const o_mobile = document.getElementById('o_mobile');
      const o_email = document.getElementById('o_email');

      document.getElementById('ownerModal').classList.add('open');

      o_title.value = '';
      o_name.value = '';
      o_careof.value = '';
      o_gender.value = '';
      o_mobile.value = '';
      o_email.value = '';

      document.getElementById('o_edit_temp_id').value = '';
      document.getElementById('o_edit_db_id').value = '0';

      // Clear previous errors
      document.getElementById('o_mobile_error').textContent = '';
      document.getElementById('o_email_error').textContent = '';
      document.getElementById('saveOwnerBtn').disabled = false;

      document.getElementById('ownerModalTitle').textContent = 'Add Owner';

      if (tempId !== null) {
        const owner = owners.find(o => o.temp_id === tempId);
        if (owner) {
          document.getElementById('ownerModalTitle').textContent = 'Edit Owner';
          document.getElementById('o_edit_temp_id').value = tempId;
          document.getElementById('o_edit_db_id').value = owner.db_id || 0;

          const fullName = owner.owner_name || '';
          let title = '';
          let nameOnly = fullName;
          if (fullName.startsWith('Mr.') || fullName.startsWith('Mrs.') || fullName.startsWith('Ms.') || fullName.startsWith('M/S')) {
            const parts = fullName.split(' ');
            title = parts[0];
            nameOnly = parts.slice(1).join(' ');
          }
          o_title.value = title;
          o_name.value = nameOnly;
          o_careof.value = owner.father_husband_pan || '';
          o_gender.value = owner.gender || '';
          o_mobile.value = owner.mobile || '';
          o_email.value = owner.email || '';
        }
      }
    }

    function closeOwnerModal() {
      document.getElementById('ownerModal').classList.remove('open');
    }

    // --- CHECK OWNER UNIQUENESS (Updated Function using check_owner_uniqueness_edit.php) ---
    async function checkOwnerUniqueness(field) {
      const value = document.getElementById('o_' + field).value.trim();
      const errorSpan = document.getElementById('o_' + field + '_error');
      const excludeId = document.getElementById('o_edit_db_id').value; // Pass DB ID to exclude self
      const saveBtn = document.getElementById('saveOwnerBtn');

      // Reset Error
      errorSpan.textContent = '';

      // Allow empty email if optional, but Mobile is mandatory
      if (value === '') return true;

      // Disable button while checking
      saveBtn.disabled = true;
      errorSpan.textContent = 'Checking...';
      errorSpan.className = 'text-blue-500 text-xs';

      try {
        const formData = new FormData();
        formData.append('field', field);
        formData.append('value', value);
        formData.append('exclude_id', excludeId); // Key Logic Change Here

        // Call the NEW edit-specific file
        const response = await fetch('check_owner_uniqueness_edit.php', {
          method: 'POST',
          body: formData
        });

        if (!response.ok) throw new Error('Network error');
        const data = await response.json();

        if (!data.is_unique) {
          errorSpan.textContent = data.message;
          errorSpan.className = 'error-msg';
          saveBtn.disabled = true;
          return false;
        } else {
          errorSpan.textContent = '';
          saveBtn.disabled = false;
          return true;
        }
      } catch (error) {
        console.error(error);
        errorSpan.textContent = 'Server Error.';
        saveBtn.disabled = true;
        return false;
      }
    }

    async function saveOwner() {
      const o_title = document.getElementById('o_title').value;
      const o_name = document.getElementById('o_name').value;
      const o_careof = document.getElementById('o_careof').value;
      const o_gender = document.getElementById('o_gender').value;
      const o_mobile = document.getElementById('o_mobile').value.trim();
      const o_email = document.getElementById('o_email').value.trim();

      const editTempId = document.getElementById('o_edit_temp_id').value;
      const ownerDbId = document.getElementById('o_edit_db_id').value;

      if (!o_title || !o_name || !o_careof || !o_gender || !o_mobile) {
        alert('Please fill all mandatory owner fields (*).');
        return;
      }

      // Final Uniqueness Check before Save
      const isMobileUnique = await checkOwnerUniqueness('mobile');
      if (!isMobileUnique) {
        alert('Mobile number already exists.');
        return;
      }

      if (o_email) {
        const isEmailUnique = await checkOwnerUniqueness('email');
        if (!isEmailUnique) {
          alert('Email already exists.');
          return;
        }
      }

      const newOwner = {
        owner_name: `${o_title} ${o_name}`.trim(),
        father_husband_pan: o_careof,
        gender: o_gender,
        mobile: o_mobile,
        email: o_email,
        db_id: parseInt(ownerDbId)
      };

      if (editTempId) {
        const index = owners.findIndex(o => o.temp_id === parseInt(editTempId));
        if (index !== -1) {
          owners[index] = {
            ...owners[index],
            ...newOwner
          };
        }
      } else {
        newOwner.temp_id = ownerTempIdCounter++;
        newOwner.db_id = 0;
        owners.push(newOwner);
      }
      closeOwnerModal();
      renderOwnersTable();
    }

    function deleteOwner(tempId) {
      if (confirm('Are you sure you want to delete this owner record?')) {
        owners = owners.filter(o => o.temp_id !== tempId);
        renderOwnersTable();
      }
    }

    // -----------------------------------------------------------------
    // INITIALIZATION
    // -----------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', () => {
      populateNonResGroupDropdown();
      renderOwnersTable();
      renderFloorsTable();

      // Preview Images
      document.querySelectorAll('.file-input-with-preview').forEach(input => {
        input.addEventListener('change', function(e) {
          const file = e.target.files[0];
          if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
              // Find the image element in the same container
              const container = input.closest('div');
              const img = container.querySelector('img');
              if (img) {
                img.src = e.target.result;
                img.classList.remove('hidden');
              }
            }
            reader.readAsDataURL(file);
          }
        });
      });
    });



    // =================================================================
    // ⭐ NEW GLOBAL ARRAYS TO TRACK DELETED DB RECORDS (Soft Delete के लिए आवश्यक) ⭐
    // =================================================================

    // इन Arrays में उन owners/floors की DB IDs आएंगी जिन्हें फ्रंटएंड से डिलीट किया गया है 
    // और जो पहले से Database में मौजूद थे (db_id > 0 वाले)
    let deletedOwnerIds = [];
    let deletedFloorIds = [];


    // =================================================================
    // ⭐ UPDATED DELETION FUNCTIONS ⭐
    // =================================================================

    function deleteOwner(tempId) {
      if (confirm('Are you sure you want to delete this owner record?')) {
        const deletedOwner = owners.find(o => o.temp_id === tempId);

        // अगर रिकॉर्ड पहले से DB में मौजूद था (db_id > 0), तो उसकी ID को डिलीट लिस्ट में जोड़ें
        // यह ID ही Backend में Soft Delete के लिए उपयोग होगी।
        if (deletedOwner && deletedOwner.db_id > 0) {
          deletedOwnerIds.push(deletedOwner.db_id);
        }

        // लोकल array से हटाएँ (Frontend से गायब करें)
        owners = owners.filter(o => o.temp_id !== tempId);
        renderOwnersTable();
      }
    }

    function deleteFloor(tempId) {
      if (confirm('Are you sure you want to delete this floor record?')) {
        const deletedFloor = floors.find(f => f.temp_id === tempId);

        // अगर रिकॉर्ड पहले से DB में मौजूद था (db_id > 0), तो उसकी ID को डिलीट लिस्ट में जोड़ें
        if (deletedFloor && deletedFloor.db_id > 0) {
          deletedFloorIds.push(deletedFloor.db_id);
        }

        // लोकल array से हटाएँ (Frontend से गायब करें)
        floors = floors.filter(f => f.temp_id !== tempId);
        renderFloorsTable();
      }
    }


    // =================================================================
    // ⭐ FORM SUBMISSION PREPARATION FUNCTION (Must be called before form submission) ⭐
    // =================================================================

    // सुनिश्चित करें कि यह फंक्शन आपके फॉर्म सबमिशन हैंडलर (जैसे onsubmit="return prepareFormSubmission()") में कॉल हो रहा है।
    function prepareFormSubmission() {
      const wardId = document.getElementById('ward_id').value;
      const mohallaId = document.getElementById('mohalla_id').value;

      if (!wardId) {
        alert('Please select Ward.');
        return false;
      }

      if (!mohallaId) {
        alert('Please select Mohalla.');
        return false;
      }

      // Keep legacy ward field synchronized.
      document.getElementById('current_ward').value = wardId;

      document.getElementById('owners_data').value = JSON.stringify(owners);
      document.getElementById('floors_data').value = JSON.stringify(floors);
      document.getElementById('deleted_owner_ids').value = JSON.stringify(deletedOwnerIds);
      document.getElementById('deleted_floor_ids').value = JSON.stringify(deletedFloorIds);

      return true;
    }
  </script>
  <script src="js/mystyle.js"></script>
</body>

</html>