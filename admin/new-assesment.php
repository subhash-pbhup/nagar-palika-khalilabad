<?php
session_start();
include("db.php");

// 1. Session
$user_role = $_SESSION['role'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;


// 2. Role के आधार पर SQL Query निर्धारित करें
if ($user_role === 'surveyor' && $user_id > 0) {
  // Surveyor के लिए: केवल वही वार्ड दिखाएं जो उसे assigned हैं और 'active' हैं।
  // tables: wards (w) JOIN surveyor_wards (sw)
  $sql_wards = "
        SELECT 
            w.ward_id, 
            w.ward_no 
        FROM 
            wards w
        INNER JOIN 
            surveyor_wards sw ON w.ward_id = sw.ward_id
        WHERE 
            sw.surveyor_id = ? AND sw.status = 'active'
        ORDER BY 
            w.ward_no ASC";

  // Prepared statement
  $stmt = $conn->prepare($sql_wards);
  $stmt->bind_param("i", $user_id);
} else {
  // Non-Surveyor (जैसे Admin, User) के लिए: सारे वार्ड दिखाएं।
  $sql_wards = "SELECT ward_id, ward_no FROM wards ORDER BY ward_no ASC";

  // Prepared statement
  $stmt = $conn->prepare($sql_wards);
}

// 3. Query को execute करें और डेटा fetch करें
$wards_data = [];
if ($stmt && $stmt->execute()) {
  $result = $stmt->get_result();
  // सभी वार्ड डेटा को associative array में fetch करें
  $wards_data = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
}

// 4. Fetch all Mohalla data for ward-wise dropdown
$mohalla_data = [];
$sql_mohalla = "SELECT mohalla_id, ward_id, mohalla_name FROM mohalla ORDER BY ward_id ASC, mohalla_id ASC";
$mohalla_result = $conn->query($sql_mohalla);

if ($mohalla_result) {
  $mohalla_data = $mohalla_result->fetch_all(MYSQLI_ASSOC);
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />

  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
  <title>Khalilabad Property Tax Dashboard</title>
  <link href="img/favicon.ico" rel="icon">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link href='css/mystyle.css' rel='stylesheet'>

  <style>
    /* Theme styles maintained from dashboard.php */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    :root {
      --primary-color: #4f46e5;
      /* Indigo-600 */
      --primary-hover: #4338ca;
      /* Indigo-700 */
      --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.06);
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8fafc;
      /* Light, clean background */
      color: #1f2937;
    }

    /* Donezo Card Style for Form Sections */
    .donezo-card-form {
      background-color: #ffffff;
      border-radius: 1rem;
      box-shadow: var(--card-shadow);
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      border: 1px solid #e5e7eb;
      /* Subtle border */
    }

    /* Clean Input Style for all form fields */
    .donezo-input {
      background-color: #f9fafb;
      /* Light gray background */
      border: 1px solid #d1d5db;
      /* Gray-300 border */
      color: #1f2937;
      transition: all 0.2s;
      padding: 0.75rem;
      /* p-3 equivalent */
      border-radius: 0.5rem;
      /* rounded-lg equivalent */
    }

    .donezo-input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 1px var(--primary-color);
      background-color: #fff;
      /* White background on focus */
    }

    .req:after {
      content: " *";
      color: #ef4444;
      font-weight: 600;
    }

    #addOwnerBtn:disabled {
      opacity: 0.55;
      cursor: not-allowed;
    }

    .modal {
      display: none
    }

    .modal.open {
      display: flex
    }

    /* Modern file input styling (using primary Indigo color) */
    input[type="file"].donezo-input {
      padding: 10px 12px;
    }

    input[type="file"]::file-selector-button {
      border: none;
      background: var(--primary-color);
      color: #fff;
      padding: 8px 16px;
      /* slightly larger buttons */
      border-radius: 8px;
      margin-right: 10px;
      cursor: pointer;
      font-weight: 500;
      transition: background 0.3s ease;
    }

    input[type="file"]::file-selector-button:hover {
      background: var(--primary-hover);
    }
  </style>
</head>

<body class="flex min-h-screen text-gray-800 bg-slate-50">
  <?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'surveyor'): ?>
    <?php include 'sidemenu.php' ?>
  <?php endif; ?>

  <div id="sidebar-backdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>
  <main class="flex-1 p-6 space-y-8 overflow-y-auto">
    <?php include 'header.php' ?>

    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">New Assessment</h1>
        <p class="text-base text-gray-500">Assessment is for registering a new property in the municipal system.</p>
      </div>
    </div>


    <form id="assessmentForm" action="save_assessment.php" method="post" enctype="multipart/form-data" class="space-y-8">

      <section class="donezo-card-form p-6">
        <h2 class="text-xl font-bold mb-4 border-b pb-2 text-gray-900">Property Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium mb-2 req">Name of Municipality</label>
            <input name="municipality" type="text" value="Nagar Palika Parishad Khalilabad" class="w-full donezo-input" style="background-color: #e5e7eb; color: #4b5563;" readonly>
            <input type="hidden" name="municipality" value="Nagar Palika Parishad Khalilabad">
          </div>

          <div>
            <label class="block text-sm font-medium mb-2 req">Year of Assessment</label>
            <input name="assesment_year" type="text" value="2025-2026" class="w-full donezo-input" style="background-color: #e5e7eb; color: #4b5563;" readonly>
            <input type="hidden" name="assesment_year" value="2025-2026">

          </div>

          <div>
            <label class="block text-sm font-medium mb-2 req">Ward No</label>
            <select id="ward_no" name="ward_id" required class="w-full donezo-input">
              <option value="">--Select Ward--</option>
              <?php foreach ($wards_data as $ward): ?>
                <option value="<?= htmlspecialchars($ward['ward_id']) ?>">
                  <?= htmlspecialchars($ward['ward_no']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2 req">Mohalla Name</label>
            <select id="mohalla_id" name="mohalla_id" required class="w-full donezo-input" disabled>
              <!-- <option value="">--Select Mohalla--</option> -->
            </select>
          </div>

          <div class="md:col-span-1">
            <label class="block text-sm font-medium mb-2">New Holding Number(Survey Number)</label>
            <div class="flex items-center gap-3">
              <input id="holding_no" name="new_holding" type="text" readonly class="w-full donezo-input" style="background-color: #e5e7eb; color: #4b5563;">

            </div>
            <label class="inline-flex items-center gap-2 mt-2 text-sm">
              <input id="autoHold" type="checkbox" class="rounded text-indigo-600 focus:ring-indigo-500" checked disabled>
              <span>Will Be Auto Generate...</span>
            </label>
          </div>


          <div>
            <label class="block text-sm font-medium mb-2 req">Status of Property</label>
            <select name="property_status" id="property_status" class="w-full donezo-input" required>
              <option value="">--Select Property Status--</option>
              <option value="Old">Old</option>
              <option value="New" selected>New</option>
            </select>
          </div>

          <div id="oldFields" hidden>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium mb-2 req">Old Holding Number</label>
                <input name="old_holding" type="text" placeholder="Old Holding Number" id="old_holding_no" class="w-full donezo-input">
              </div>

              <div>
                <label class="block text-sm font-medium mb-2 req">Old ARV</label>
                <input name="old_pid" type="text" placeholder="Old ARV" id="old_arv" class="w-full donezo-input">
              </div>
            </div>
          </div>


          <script>
            // यह JavaScript ब्लॉक सुनिश्चित करता है कि 'Old' चुनने पर Old Holding Number और Old ARV अनिवार्य हो जाएं।
            document.addEventListener('DOMContentLoaded', function() {
              const statusSelect = document.querySelector('select[name="property_status"]');
              const oldFields = document.getElementById('oldFields');
              const oldHolding = document.getElementById('old_holding_no');
              const oldArv = document.getElementById('old_arv');

              function showOldFields(show) {
                if (show) {
                  // 'Old' selected: Show fields and make them required
                  oldFields.removeAttribute('hidden');
                  oldHolding.setAttribute('required', '');
                  oldArv.setAttribute('required', '');
                } else {
                  // 'New' selected: Hide fields, remove required, and clear values
                  oldFields.setAttribute('hidden', '');
                  oldFields.querySelectorAll('input').forEach(i => i.value = '');
                  oldHolding.removeAttribute('required');
                  oldArv.removeAttribute('required');
                }
              }

              function handleChange() {
                // Trim and convert to lower case for reliable comparison
                const val = (statusSelect.value || '').trim().toLowerCase();
                showOldFields(val === 'old');
              }

              // Initialize state based on default selection ('New' is selected by default in your HTML)
              handleChange();

              // Listen for changes
              statusSelect.addEventListener('change', handleChange);
            });
          </script>


          <div>
            <label class="block text-sm font-medium mb-2 req">Property Type</label>
            <select name="property_type" class="w-full donezo-input" required>
              <option value="">--Select Property Type--</option>
              <option>Non Residential</option>
              <option>Residential</option>
              <option>Mix</option>
              <option>Industrial</option>
            </select>
          </div>


          <div>
            <label class="block text-sm font-medium mb-2 req">Road on which located</label>

            <select name="road" class="w-full donezo-input" required>
              <option selected disabled value="">--Select Road on which Located--</option>
              <option>Upto 9 Meters</option>
              <option>9 to 12 Meters</option>
              <option>12 to 24 Meters</option>
              <option>Above 24 Meters</option>
            </select>

            <!-- <input name="road" list="roads" placeholder="Select Road on which Located" class="w-full donezo-input" required>
            <datalist id="roads">
              <option>Upto 9 Meters</option>
              <option>9 to 12 Meters</option>
              <option>12 to 24 Meters</option>
              <option>Above 24 Meters</option>
            </datalist> -->
          </div>

          <div>
            <label class="block text-sm font-medium mb-2 req">Area of Plot</label>
            <input name="plot_area" type="number" step="0.01" placeholder="Area of Plot (sq.ft)" class="w-full donezo-input" required>
          </div>


          <div>
            <label class="block text-sm font-medium mb-2 req">Buildup Type</label>
            <select name="building_type" class="w-full donezo-input" required>
              <option value="">--Select Buildup Type--</option>
              <option>RCC</option>
              <option>ACC</option>
              <option>Other</option>
              <option>Vaccant Land</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Latitude</label>
            <input id="latitude" name="latitude" type="text" placeholder="Latitude"
              class="w-full donezo-input" readonly>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Longitude</label>
            <input id="longitude" name="longitude" type="text" placeholder="Longitude"
              class="w-full donezo-input" readonly>
          </div>

          <input id="location" type="hidden" name="location">

          <div class="mt-4">
            <button type="button" onclick="getLocation()"
              class="px-6 py-3 rounded-full bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition">
              <i class="fa fa-map-pin" aria-hidden="true"></i>&nbsp; Get Location
            </button>
          </div>

          <script>
            // ** Functionality preserved **
            function getLocation() {
              if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                  function(position) {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;

                    // Show latitude and longitude in visible inputs
                    document.getElementById("latitude").value = lat;
                    document.getElementById("longitude").value = lon;

                    // Reverse geocoding (hidden, not displayed)
                    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`)
                      .then(response => response.json())
                      .then(data => {
                        // Save full location in hidden input
                        document.getElementById("location").value = data.display_name;
                      })
                      .catch(err => console.error(err));
                  },
                  function(error) {
                    switch (error.code) {
                      case error.PERMISSION_DENIED:
                        alert("User denied the request for Geolocation.");
                        break;
                      case error.POSITION_UNAVAILABLE:
                        alert("Location information is unavailable.");
                        break;
                      case error.TIMEOUT:
                        alert("The request to get user location timed out.");
                        break;
                      case error.UNKNOWN_ERROR:
                        alert("An unknown error occurred.");
                        break;
                    }
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

            // Auto-run on page load
            window.onload = getLocation;
          </script>



      </section>

      <section class="donezo-card-form p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-bold">Owner Details</h2>
          <button type="button" id="addOwnerBtn" onclick="openOwnerModal()" class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition">
            <i class="fa fa-plus" aria-hidden="true"></i>&nbsp; Add Owner
          </button>
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
            <tbody id="ownersBody" class="divide-y divide-gray-100 bg-white"></tbody>
          </table>
        </div>
        <input type="hidden" name="owners_json" id="owners_json">
      </section>

      <section class="donezo-card-form p-6">
        <h2 class="text-xl font-bold mb-4 border-b pb-2 text-gray-900">Address Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium mb-2 req">Property/House No</label>
            <input name="house_no" type="text" placeholder="Enter Property/House No" class="w-full donezo-input" required>
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Plot No</label>
            <input name="plot_no" type="text" placeholder="Plot No" class="w-full donezo-input">
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Khata No</label>
            <input name="khata_no" type="text" placeholder="Khata No" class="w-full donezo-input">
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Khasra No</label>
            <input name="khasra_no" type="text" placeholder="Khasra No" class="w-full donezo-input">
          </div>
          <div>
            <label class="block text-sm font-medium mb-2 req">Address Line 1</label>
            <input name="addr1" type="text" placeholder="Address Line 1" class="w-full donezo-input" required>
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Address Line 2</label>
            <input name="addr2" type="text" placeholder="Address Line 2" class="w-full donezo-input">
          </div>

          <div>
            <label class="block text-sm font-medium mb-2 req">Pincode</label>
            <input name="pincode" type="text" pattern="[0-9]{6}" placeholder="Enter Pincode" class="w-full donezo-input" required>
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
                <th class="p-3">Construction Type</th>
                <th class="p-3">Date From</th>
                <th class="p-3">Date To</th>
                <th class="p-3">Occupancy Type</th>
                <th class="p-3">Build Up Area</th>
                <th class="p-3">Usage Type</th>
                <th class="p-3">Non Residential Properties</th>
                <th class="p-3">Property</th>
                <th class="p-3">Edit</th>
                <th class="p-3">Delete</th>
              </tr>
            </thead>
            <tbody id="floorsBody" class="divide-y divide-gray-100 bg-white"></tbody>
          </table>
        </div>
        <input type="hidden" name="floors_json" id="floors_json">
      </section>

      <section class="donezo-card-form p-6">
        <h2 class="text-xl font-bold mb-4 border-b pb-2 text-gray-900">Other Taxes</h2>
        <label class="flex items-center gap-3 mb-2">
          <input type="checkbox" name="water_tax" class="rounded text-indigo-600 focus:ring-indigo-500">
          <span>Water Tax </span>
        </label>

      </section>


      <section class="donezo-card-form p-6">
        <h2 class="text-xl font-bold mb-4 border-b pb-2 text-gray-900">Property Images</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <label class="block text-sm font-medium mb-2 req">Center Image of Property</label>
            <input type="file" name="center_image_gps" class="w-full donezo-input file-input" required>
            <p class="text-sm mt-2 text-gray-800 file-name">
              <span class="icon"><i class="fa fa-image" aria-hidden="true"></i>&nbsp; </span>
              <span class="name">No file selected</span>
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2 req">Left Image of Property</label>
            <input type="file" name="left_image_gps" class="w-full donezo-input file-input" required>
            <p class="text-sm mt-2 text-gray-800 file-name">
              <span class="icon"><i class="fa fa-image" aria-hidden="true"></i>&nbsp;</span>
              <span class="name">No file selected</span>
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2 req">Right Image of Property</label>
            <input type="file" name="right_image_gps" class="w-full donezo-input file-input" required>
            <p class="text-sm mt-2 text-gray-800 file-name">
              <span class="icon"><i class="fa fa-image" aria-hidden="true"></i>&nbsp;</span>
              <span class="name">No file selected</span>
            </p>
          </div>
        </div>
      </section>

      <section class="donezo-card-form p-6">
        <h2 class="text-xl font-bold mb-4 border-b pb-2 text-gray-900">Document</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div>
            <label class="block text-sm font-medium mb-2 req">Doc Proof</label>
            <input type="file" name="doc_proof" class="w-full donezo-input file-input" required>
            <p class="text-sm mt-2 text-gray-800 file-name">
              <span class="icon"><i class="fa fa-file" aria-hidden="true"></i>&nbsp;</span>
              <span class="name">No file selected</span>
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Supporting Document 1</label>
            <input type="file" name="supporting_doc_1" class="w-full donezo-input file-input">
            <p class="text-sm mt-2 text-gray-800 file-name">
              <span class="icon"><i class="fa fa-file" aria-hidden="true"></i>&nbsp;</span>
              <span class="name">No file selected</span>
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Supporting Document 2</label>
            <input type="file" name="supporting_doc_2" class="w-full donezo-input file-input">
            <p class="text-sm mt-2 text-gray-800 file-name">
              <span class="icon"><i class="fa fa-file" aria-hidden="true"></i>&nbsp;</span>
              <span class="name">No file selected</span>
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Supporting Document 3</label>
            <input type="file" name="supporting_doc_3" class="w-full donezo-input file-input">
            <p class="text-sm mt-2 text-gray-800 file-name">
              <span class="icon"><i class="fa fa-file" aria-hidden="true"></i>&nbsp;</span>
              <span class="name">No file selected</span>
            </p>
          </div>
        </div>
      </section>

      <script>
        // ** Functionality preserved **
        document.querySelectorAll('.file-input').forEach(input => {
          input.addEventListener('change', function() {
            const fileName = this.files.length > 0 ? this.files[0].name : "No file selected";
            const fileDisplay = this.nextElementSibling.querySelector('.name');
            fileDisplay.textContent = fileName;
          });
        });
      </script>

      <style>
        .file-name {
          display: flex;
          align-items: center;
          gap: 0.5rem;
          font-weight: 500;
          color: #4b5563;
          /* Medium text */
        }

        .file-name .icon {
          font-size: 1rem;
          color: var(--primary-color);
        }
      </style>



      <div class="text-right">
        <?php
        $cancel_url = '';
        if (isset($_SESSION['role'])) {
          if ($_SESSION['role'] === 'admin') {
            $cancel_url = 'dashboard.php';
          } elseif ($_SESSION['role'] === 'surveyor') {
            $cancel_url = 'surveyor-dashboard.php';
          }
          // Note: If the role is set but is neither 'admin' nor 'surveyor', 
          // $cancel_url will remain empty or will need a specific fallback added here.
        }

        ?>
        <a href="<?php echo $cancel_url; ?>" class="px-6 py-3 rounded-full bg-red-600 text-white font-medium hover:bg-red-700 transition">Cancel</a>

        <button type="submit" class="px-6 py-3 rounded-full bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition">Save</button>
      </div>

      <script>
        // ** Functionality preserved **
        // --- Owners hidden inputs  ---
        function prepareOwnerData() {
          const form = document.querySelector("form");
          form.querySelectorAll("input[name^='owner_list']").forEach(el => el.remove());

          owners.forEach((owner, index) => {
            form.insertAdjacentHTML("beforeend", `
                <input type="hidden" name="owner_list[${index}][owner_name]" value="${owner.title + ' ' + owner.name}">
                <input type="hidden" name="owner_list[${index}][father_husband_pan]" value="${owner.careof}">
                <input type="hidden" name="owner_list[${index}][gender]" value="${owner.gender}">
                <input type="hidden" name="owner_list[${index}][mobile]" value="${owner.mobile}">
                <input type="hidden" name="owner_list[${index}][email]" value="${owner.email}">
            `);
          });
        }

        // --- Floors hidden inputs  ---
        function prepareFloorData() {
          const form = document.querySelector("form");
          form.querySelectorAll("input[name^='floor_list']").forEach(el => el.remove());

          floors.forEach((floor, index) => {
            form.insertAdjacentHTML("beforeend", `
                <input type="hidden" name="floor_list[${index}][floor_no]" value="${floor.floor_no}">
                <input type="hidden" name="floor_list[${index}][residential_type]" value="${floor.usage}">
                <input type="hidden" name="floor_list[${index}][construction_type]" value="${floor.construction}">
                <input type="hidden" name="floor_list[${index}][date_from]" value="${floor.date_from}">
                <input type="hidden" name="floor_list[${index}][date_to]" value="${floor.date_to}">
                <input type="hidden" name="floor_list[${index}][occupancy_type]" value="${floor.occupancy}">
                <input type="hidden" name="floor_list[${index}][build_up_area]" value="${floor.buildup}">
            `);
          });
        }

        // --- Form submit  hidden inputs create  ---
        document.querySelector("form").addEventListener("submit", function(e) {
          prepareOwnerData();
          prepareFloorData();
        });
      </script>

    </form>

    <footer class="text-center text-gray-500 text-sm mt-6"> 2025 Khalilabad Nagar Parishad - All Rights Reserved.</footer>
  </main>

  <div id="ownerModal" class="modal fixed inset-0 bg-black/50 items-center justify-center z-50">
    <div class="bg-white donezo-card-form p-6 w-full max-w-3xl mx-4">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">Add Owner</h3>
        <button type="button" class="text-gray-500" onclick="closeOwnerModal()">✖</button>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-2 req">Title</label>
          <select id="o_title" class="donezo-input w-full" required>
            <option value="">Select</option>
            <option>Mr.</option>
            <option>Mrs.</option>
            <option>Ms.</option>
            <option>Dr.</option>
            <option>Company</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-2 req">Name/Name of Organisation/Company</label>
          <input id="o_name" type="text" class="donezo-input w-full" placeholder="Name or Company" required>
        </div>
        <div class="md:col-span-1">
          <label class="block text-sm font-medium mb-2 req">C/O-S/O-D/O-W/O</label>
          <input id="o_careof" type="text" class="donezo-input w-full" placeholder="C/O-S/O-D/O-W/O" required>
        </div>
        <div class="md:col-span-1">
          <label class="block text-sm font-medium mb-2 req">Gender</label>
          <select id="o_gender" class="donezo-input w-full" required>
            <option value="">Select Gender</option>
            <option>Male</option>
            <option>Female</option>
            <option>Other</option>
          </select>
        </div>



        <div>
          <label class="block text-sm font-medium mb-2 req">Mobile</label>
          <input id="o_mobile"
            type="text"
            name="owner_mobile"
            class="donezo-input w-full"
            placeholder="Mobile"
            required
            maxlength="10"
            oninput="this.value = this.value.replace(/\D/g, '').slice(0, 10);">
          <small id="mobileError" style="color:red; display:none;">
          </small>
        </div>

        <div>
          <label class="block text-sm font-medium mb-2">Email</label>
          <input id="o_email"
            type="email"
            name="owner_email"
            class="donezo-input w-full"
            placeholder="Email">
          <small id="emailError" style="color:red; display:none;">
          </small>
        </div>









      </div>
      <div class="mt-5 flex justify-end gap-3">
        <button type="button" class="px-4 py-2 rounded-lg bg-gray-400 text-white hover:bg-gray-500 transition" onclick="closeOwnerModal()">Close</button>
        <button id="ownerAddBtn" type="button" class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition" onclick="saveOwner()">Add</button>
      </div>
    </div>
  </div>

  <div id="floorModal" class="modal fixed inset-0 bg-black/50 items-center justify-center z-50">
    <div class="bg-white donezo-card-form p-6 w-full max-w-4xl mx-4">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">Add Floor</h3>
        <button type="button" class="text-gray-500" onclick="closeFloorModal()">✖</button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-2 req">Floor No</label>
          <select id="f_floor_no" class="donezo-input w-full" required>
            <option value="">---Select Floor No---</option>
            <option>Basement -1</option>
            <option>Ground Floor - 0</option>
            <option>First Floor - 2</option>
            <option>Second Floor - 3</option>
            <option>Third Floor - 4</option>
            <option>Fourth Floor - 5</option>
            <option>Fifth Floor - 6 </option>
            <option>Sixth Floor - 7</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-2 req">Construction Type</label>
          <select id="f_construction" class="donezo-input w-full" required>
            <option value="">---Select Construction Type---</option>
            <option>RCC</option>
            <option>ACC</option>
            <option>Others</option>
            <option>Vacant Land</option>
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
          <label class="block text-sm font-medium mb-2 req">Occupancy Type</label>
          <select id="f_occupancy" class="donezo-input w-full" required>
            <option value="">---Select Occupancy Type---</option>

            <option>Tenanted (T)</option>
            <option>Self-Occupied (S)</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-2 req">Build Up Area</label>
          <input id="f_buildup" type="number" step="0.01" class="donezo-input w-full" placeholder="Enter Build Up Area" required>
        </div>


        <div>
          <label class="block text-sm font-medium mb-2 req">Usage Type</label>
          <select id="f_usage" class="donezo-input w-full" required>
            <option value="">---Select Residential Type---</option>
            <option value="Non-Residential">Non-Residential</option>
            <option value="Fully Residential">Fully Residential</option>
            <option value="Industrial">Industrial</option>
          </select>
        </div>

        <div id="nonResSection" style="display:none;">



          <div class="mt-4">
            <label class="block text-sm font-medium mb-2 req">Non Residential Properties</label>
            <select id="non_residential" class="donezo-input w-full">
              <option value="">---Non Residential Properties---</option>
              <option value="group1">Govt. Hostel, Govt. And Non Govt. Educational Centers & Schools, Swimming Pool, Play Ground & Gym...</option>
              <option value="group2">Medical Store, Each Type Of Commercial Complexes...</option>
              <option value="group3">Govt. /Semi Govt./Private Offices, Public Undertakings...</option>
              <option value="group4">All Other Commercial Buildings Which Are Not Included In Above Mentioned.</option>
            </select>
          </div>

          <div class="mt-4">
            <label class="block text-sm font-medium mb-2 req">Select Properties</label>
            <select id="properties" class="donezo-input w-full">
              <option value="">---Select Properties---</option>
            </select>
          </div>
        </div>

        <script>
          // ** Functionality preserved **
          const propertyGroups = {
            group1: ["Govt. Hostel", "Govt. And Non Govt. Educational Centers & Schools", "Swimming Pool", "Play Ground & Gym", "Physical Fitness Center", "Theatre (Being Used Only For Cultural Events, Don't Organise Marriage/Party Functions)", "Music And Dance Academy", "Micro And Small Industries", "Single Screen Cinema Halls (Not In Malls)", "Tea & Milk Shop (120 Sq. Ft./11.14 Sq.Meter)", "Egg Shop", "Laundry/Washerman Shops", "Barber/Hairdresser Shop (Don't Have More Than Two Chairs, Ac & Cooler)", "Tailor Shop"],
            group2: ["Medical Store", "Each Type Of Commercial Complexes", "Shops In Market Area", "Building Material Store", "Non Govt. Coaching Center"],
            group3: ["Govt. /Semi Govt./Private Offices", "Public Undertakings", "State Corporation & Boards Etc.", "Clinics", "Polyclinics", "Dental Clinics", "Diagnostic Center", "Pathology Labs", "Nursing Homes", "Technical University", "Medical College", "Dental College", "Engineering College", "Management Institute", "Law Institute", "Other Commercial Education Centers", "Petrol Pump", "Gas Agency", "Depots & Godowns Etc.", "Community Halls", "Welfare Pavilion", "Marriage Clubs", "Auditorium", "Community Centers", "Medium And Large Industries", "Restaurant", "All Type Of Hotels", "Tourist Places", "Buildings Having Tower & Advertisement Boards", "T.V. Tower", "Mobile Tower", "All Other Buildings Having Tower On Roof Or In Vacant Area", "Banks", "Bank ATM", "Finance Companies", "Private Offices And Buildings", "Malls", "Pubs", "Bars", "Beer Shops", "Hotels Having Beer And Food Serving Facility"],
            group4: ["All Other Commercial Buildings Which Are Not Included In Above Mentioned"]
          };

          const fUsage = document.getElementById("f_usage");
          const nonResSection = document.getElementById("nonResSection");
          const nonResidential = document.getElementById("non_residential");
          const properties = document.getElementById("properties");

          // Usage Type toggle
          function toggleUsage() {
            if (fUsage.value === "Non-Residential" || fUsage.value === "Industrial") {
              nonResSection.style.display = "block";
            } else {
              nonResSection.style.display = "none";
              nonResidential.value = "";
              properties.innerHTML = '<option value="">---Select Properties---</option>'; // reset
            }
          }

          fUsage.addEventListener("change", toggleUsage);

          // Properties loading logic
          nonResidential.addEventListener("change", function() {
            const group = this.value;
            properties.innerHTML = '<option value="">---Select Properties---</option>'; // reset

            if (propertyGroups[group]) {
              propertyGroups[group].forEach(item => {
                const opt = document.createElement("option");
                opt.textContent = item;
                opt.value = item;
                properties.appendChild(opt);
              });
            }
          });
        </script>




      </div>

      <div class="mt-5 flex justify-end gap-3">
        <button type="button" class="px-4 py-2 rounded-lg bg-gray-400 text-white hover:bg-gray-500 transition" onclick="closeFloorModal()">Close</button>
        <button id="floorAddBtn" type="button" class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700 transition" onclick="saveFloor()">Add</button>
      </div>
    </div>
  </div>




  <script>
    // ** All JavaScript functionality preserved **
    // --- Auto-generate toggle ---
    const autoHold = document.getElementById('autoHold');
    const newHolding = document.getElementById('holding_no');
    autoHold.addEventListener('change', () => {
      newHolding.disabled = autoHold.checked;
      if (autoHold.checked) newHolding.value = '';
    });

    // --- Modal helpers ---
    const ownerModal = document.getElementById('ownerModal');
    const floorModal = document.getElementById('floorModal');

    function openOwnerModal() {
      if (owners.length >= 1 && editingOwner === -1) {
        alert('Only one owner can be added to a property.');
        return;
      }
      ownerModal.classList.add('open');
    }

    function closeOwnerModal() {
      ownerModal.classList.remove('open');
      resetOwnerModal();
      editingOwner = -1;
      document.getElementById('ownerAddBtn').textContent = 'Add';
    }

    function openFloorModal() {
      floorModal.classList.add('open');
    }

    function closeFloorModal() {
      floorModal.classList.remove('open');
      resetFloorModal();
      editingFloor = -1;
      document.getElementById('floorAddBtn').textContent = 'Add';
    }

    // Close on ESC
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeOwnerModal();
        closeFloorModal();
      }
    });

    // --- Owners state ---
    let owners = [];
    let editingOwner = -1;

    function renderOwners() {
      const tbody = document.getElementById('ownersBody');
      const addOwnerBtn = document.getElementById('addOwnerBtn');

      tbody.innerHTML = owners.map((o, i) => `
        <tr>
          <td class="p-3">${i+1}</td>
          <td class="p-3">${o.title} ${o.name}</td>
          <td class="p-3">${o.careof || ''}</td>
          <td class="p-3">${o.gender || ''}</td>
          <td class="p-3">${o.mobile || ''}</td>
          <td class="p-3">${o.email || ''}</td>
          <td class="p-3"><button type="button" class="px-2 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700 transition" onclick="editOwner(${i})">Edit</button></td>
          <td class="p-3"><button type="button" class="px-2 py-1 rounded bg-red-600 text-white hover:bg-red-700 transition" onclick="deleteOwner(${i})">Delete</button></td>
        </tr>
      `).join('');

      document.getElementById('owners_json').value = JSON.stringify(owners);

      // Only one owner is allowed.
      if (owners.length >= 1) {
        addOwnerBtn.disabled = true;
        addOwnerBtn.innerHTML = '<i class="fa fa-check" aria-hidden="true"></i>&nbsp; Owner Added';
        addOwnerBtn.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
        addOwnerBtn.classList.add('bg-gray-500');
      } else {
        addOwnerBtn.disabled = false;
        addOwnerBtn.innerHTML = '<i class="fa fa-plus" aria-hidden="true"></i>&nbsp; Add Owner';
        addOwnerBtn.classList.remove('bg-gray-500');
        addOwnerBtn.classList.add('bg-indigo-600', 'hover:bg-indigo-700');
      }
    }

    function resetOwnerModal() {
      ['o_title', 'o_name', 'o_careof', 'o_gender', 'o_mobile', 'o_email'].forEach(id => {
        const el = document.getElementById(id);
        if (el.tagName === 'SELECT') el.selectedIndex = 0;
        else el.value = '';
      });
      document.getElementById("mobileError").style.display = "none";
    }

    function saveOwner() {
      const o = {
        title: document.getElementById('o_title').value.trim(),
        name: document.getElementById('o_name').value.trim(),
        careof: document.getElementById('o_careof').value.trim(),
        gender: document.getElementById('o_gender').value,
        mobile: document.getElementById('o_mobile').value.trim(),
        email: document.getElementById('o_email').value.trim()
      };

      // Validation check preserved
      if (!o.title || !o.name || !o.careof || !o.gender || o.mobile.length !== 10) {
        alert('Please fill all required Owner fields and ensure the mobile number is 10 digits.');
        return;
      }

      if (editingOwner > -1) {
        owners[editingOwner] = o;
      } else {
        if (owners.length >= 1) {
          alert('Only one owner can be added to a property.');
          return;
        }
        owners.push(o);
      }

      renderOwners();
      closeOwnerModal();
    }

    function editOwner(i) {
      editingOwner = i;
      const o = owners[i];
      document.getElementById('o_title').value = o.title;
      document.getElementById('o_name').value = o.name;
      document.getElementById('o_careof').value = o.careof;
      document.getElementById('o_gender').value = o.gender;
      document.getElementById('o_mobile').value = o.mobile;
      document.getElementById('o_email').value = o.email;
      document.getElementById('ownerAddBtn').textContent = 'Update';
      openOwnerModal();
    }

    function deleteOwner(i) {
      if (confirm('Delete this owner?')) {
        owners.splice(i, 1);
        renderOwners();
      }
    }

    // --- Floors state ---
    let floors = [];
    let editingFloor = -1;

    function renderFloors() {
      const tbody = document.getElementById('floorsBody');
      tbody.innerHTML = floors.map((f, i) => `
        <tr>
          <td class="p-3">${i+1}</td>
          <td class="p-3">${f.floor_no}</td>
          <td class="p-3">${f.construction}</td>
          <td class="p-3">${f.date_from || 'N/A'}</td>
          <td class="p-3">${f.date_to || 'N/A'}</td>
          <td class="p-3">${f.occupancy}</td>
          <td class="p-3">${f.buildup}</td>
          <td class="p-3">${f.usage}</td>
          <td class="p-3">${f.non_residential || 'N/A'}</td>
          <td class="p-3">${f.properties || 'N/A'}</td>
          <td class="p-3"><button type="button" class="px-2 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700 transition" onclick="editFloor(${i})">Edit</button></td>
          <td class="p-3"><button type="button" class="px-2 py-1 rounded bg-red-600 text-white hover:bg-red-700 transition" onclick="deleteFloor(${i})">Delete</button></td>
        </tr>
      `).join('');
      document.getElementById('floors_json').value = JSON.stringify(floors);
    }

    function resetFloorModal() {
      ['f_floor_no', 'f_construction', 'f_date_from', 'f_date_to', 'f_occupancy', 'f_buildup', 'f_usage', 'non_residential', 'properties'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        if (el.tagName === 'SELECT') el.selectedIndex = 0;
        else el.value = '';
      });
      document.getElementById("nonResSection").style.display = "none";
    }

    function saveFloor() {
      const f = {
        floor_no: document.getElementById('f_floor_no').value,
        construction: document.getElementById('f_construction').value,
        date_from: document.getElementById('f_date_from').value, // New
        date_to: document.getElementById('f_date_to').value, // New
        occupancy: document.getElementById('f_occupancy').value,
        buildup: document.getElementById('f_buildup').value,
        usage: document.getElementById('f_usage').value,
        non_residential: document.getElementById('non_residential').value,
        properties: document.getElementById('properties').value
      };

      // Validation check preserved
      if (!f.floor_no || !f.construction || !f.date_from || !f.date_to || !f.occupancy || !f.buildup || !f.usage) {
        alert('Please fill all required Floor fields.');
        return;
      }

      // Conditional validation check preserved
      if ((f.usage === "Non-Residential" || f.usage === "Industrial") && (!f.non_residential || !f.properties)) {
        alert('Please select Non Residential Properties and a specific property type.');
        return;
      }

      // Add new floor or update existing one
      if (editingFloor > -1) {
        floors[editingFloor] = f;
      } else {
        floors.push(f);
      }

      renderFloors();
      closeFloorModal();
    }

    function editFloor(i) {
      editingFloor = i;
      const f = floors[i];
      document.getElementById('f_floor_no').value = f.floor_no;
      document.getElementById('f_construction').value = f.construction;
      document.getElementById('f_date_from').value = f.date_from; // New
      document.getElementById('f_date_to').value = f.date_to; // New
      document.getElementById('f_occupancy').value = f.occupancy;
      document.getElementById('f_buildup').value = f.buildup;
      document.getElementById('f_usage').value = f.usage;

      // Handling the non-residential fields
      if (f.usage === "Non-Residential" || f.usage === "Industrial") {
        document.getElementById("nonResSection").style.display = "block";
        document.getElementById('non_residential').value = f.non_residential;
        const group = f.non_residential;
        document.getElementById('properties').innerHTML = '<option value="">---Select Properties---</option>'; // reset
        if (propertyGroups[group]) {
          propertyGroups[group].forEach(item => {
            const opt = document.createElement("option");
            opt.textContent = item;
            opt.value = item;
            document.getElementById('properties').appendChild(opt);
          });
        }
        document.getElementById('properties').value = f.properties;
      } else {
        document.getElementById("nonResSection").style.display = "none";
      }

      document.getElementById('floorAddBtn').textContent = 'Update';
      openFloorModal();
    }

    function deleteFloor(i) {
      if (confirm('Delete this floor?')) {
        floors.splice(i, 1);
        renderFloors();
      }
    }

    // initial render
    renderOwners();
    renderFloors();

    // Prevent form submit if no owner or floor (Functionality preserved)
    document.getElementById('assessmentForm').addEventListener('submit', function(e) {
      // e.preventDefault(); 
      let messages = [];

      // Check Owners
      if (owners.length === 0) {
        messages.push("⚠️ Please add at least one Owner before submitting.");
      } else if (owners.length > 1) {
        messages.push("⚠️ Only one Owner can be added to a property.");
      }

      // Check Floors
      if (floors.length === 0) {
        messages.push("⚠️ Please add at least one Floor before submitting.");
      }

      // Stop submission if invalid
      if (messages.length > 0) {
        e.preventDefault();
        alert(messages.join("\n"));
        return false;
      }
    });
  </script>


  <script>
    // Profile Dropdown Logic (Functionality preserved)
    const profileBtn = document.getElementById("profileBtn");
    const profileMenu = document.getElementById("profileMenu");

    profileBtn.addEventListener("click", () => {
      profileMenu.classList.toggle("hidden");
    });

    // Close dropdown if clicked outside
    window.addEventListener("click", (e) => {
      if (!profileBtn.contains(e.target) && !profileMenu.contains(e.target)) {
        profileMenu.classList.add("hidden");
      }
    });
  </script>


  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <script>
    // This function handles both client-side validation and server-side duplication check
    function checkDuplication(field, value, errorElement) {
      // 1. Clear the previous error message
      $(errorElement).hide().text('');

      // If the value is empty, don't proceed with the check
      if (value.trim() === '') {
        return;
      }

      // 2. Client-side Validation (Mobile 10 digits, Email format)
      if (field === 'mobile') {
        if (value.length !== 10) {
          $(errorElement).text('⚠️ Mobile Number must be exactly 10 digits.').show();
          return; // Stop further checks
        }
      } else if (field === 'email') {
        // Basic email format check
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
          $(errorElement).text('⚠️ Please enter a valid email address format (user@example.com).').show();
          return; // Stop further checks
        }
      }

      // 3. Server-side Duplication Check (AJAX)
      // Call check_owner_uniqueness.php for duplication check
      $.ajax({
        url: 'check_owner_uniqueness.php',
        type: 'POST',
        data: {
          field: field, // 'mobile' or 'email'
          value: value
        },
        dataType: 'json',
        success: function(response) {
          if (!response.is_unique) {
            // Duplication found: Display error
            $(errorElement).text('❌ ' + response.message).show();
          } else {
            // Unique: Do not show any error
            $(errorElement).hide();
          }
        },
        error: function() {
          // Network/Server error
          $(errorElement).text('❌ Network Error: Failed to check uniqueness.').show();
        }
      });
    }

    // Focus Out (blur) Events attachment
    $(document).ready(function() {
      // Check when focus leaves the mobile input field
      $("#o_mobile").on('blur', function() {
        const mobile = $(this).val().trim();
        checkDuplication('mobile', mobile, '#mobileError');
      });

      // Check when focus leaves the email input field
      $("#o_email").on('blur', function() {
        const email = $(this).val().trim();
        checkDuplication('email', email, '#emailError');
      });

      // Your 'Add Owner' button click handler (for example)
      /*
      $('#addOwnerBtn').on('click', function(e) {
          // Re-run checks to ensure no errors are visible before submission
          checkDuplication('mobile', $('#o_mobile').val().trim(), '#mobileError');
          checkDuplication('email', $('#o_email').val().trim(), '#emailError');
          
          // Wait briefly for AJAX to complete, or use promises for better handling
          setTimeout(function() {
              if ($('#mobileError').is(':visible') || $('#emailError').is(':visible')) {
                  e.preventDefault();
                  alert('Please fix the mobile/email duplication error before adding the owner.');
                  return;
              }
              // ... continue with adding owner logic here
          }, 300); // 300ms delay to allow AJAX check to return
      });
      */
    });
  </script>

  <script>
    // Ward select karne par usi Ward ke Mohalla load honge.
    const mohallaData = <?= json_encode($mohalla_data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    $(document).ready(function() {

      $("#ward_no").change(function() {
        const ward_id = $(this).val();
        const mohallaSelect = $("#mohalla_id");

        // Mohalla dropdown reset aur clear 
        // mohallaSelect.html('<option value="">--Select Mohalla--</option>');
        mohallaSelect.html('');
        mohallaSelect.prop("disabled", true);

        if (ward_id !== "") {

          // Selected Ward ke Mohalla filter karo
          const wardMohallas = mohallaData.filter(function(mohalla) {
            return String(mohalla.ward_id) === String(ward_id);
          });

          // Mohalla dropdown populate karo
          wardMohallas.forEach(function(mohalla) {
            const option = $("<option></option>")
              .val(mohalla.mohalla_id)
              .text(mohalla.mohalla_name);

            mohallaSelect.append(option);
          });

          if (wardMohallas.length > 0) {
            mohallaSelect.prop("disabled", false);
          }

          // Existing holding number generation
          $.ajax({
            url: "generate_holding_no.php",
            type: "POST",
            data: {
              ward: ward_id
            },
            success: function(response) {
              $("#holding_no").val(response);
            },
            error: function() {
              $("#holding_no").val("");
            }
          });

        } else {
          $("#holding_no").val("");
        }
      });

    });
  </script>

  <script src="js/mystyle.js"></script>
</body>

</html>