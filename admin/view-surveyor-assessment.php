<?php
session_start();

// ✅ 1. Session Check: केवल Surveyor को ही अनुमति दें
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'surveyor') {
    header("Location: index.php");
    exit;
}

// Database connection details
include 'db.php';

// Surveyor ID
$surveyor_id = $_SESSION['user_id'];

// Get the ID from the URL
$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$property = null;
$owners = [];
$floors = [];
$ward_display_value = ''; // Variable to store the fetched Ward Name for display

if ($property_id > 0) {
    // ✅ 2. SQL Filter: Assessment ID और Surveyor ID दोनों को चेक करें
    // केवल वही असेसमेंट दिखाएँ जो इस Surveyor ने किया है
    $stmt = $conn->prepare("SELECT * FROM assessments WHERE id = ? AND surveyor_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $property_id, $surveyor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $property = $result->fetch_assoc();
        $stmt->close();
    } else {
        // Error handling for main property statement
    }
    
    // Fetch Ward Name for display
    if ($property && !empty($property['ward'])) {
        $ward_lookup_key = $property['ward'];
        $ward_display_value = htmlspecialchars($ward_lookup_key);

        // Query: SELECT ward_no (which has the name) FROM wards WHERE ward_id (the ID) = ?
        $stmt_ward = $conn->prepare("SELECT ward_no FROM wards WHERE ward_id = ? LIMIT 1");
        if ($stmt_ward) {
            $stmt_ward->bind_param("i", $ward_lookup_key);
            $stmt_ward->execute();
            $ward_result = $stmt_ward->get_result();
            if ($ward_row = $ward_result->fetch_assoc()) {
                $ward_name = $ward_row['ward_no'];
                // Display format matching view_assesment_details.php
                $ward_display_value = htmlspecialchars($ward_name) . " (वार्ड ID: " . htmlspecialchars($ward_lookup_key) . ")"; 
            }
            $stmt_ward->close();
        }
    } else if ($property) {
        $ward_display_value = htmlspecialchars($property['ward'] ?? 'N/A');
    }

    // ✅ UPDATED: Fetch related owner details - सिर्फ नॉन-डिलीटेड (is_deleted = 0)
    $stmt_owners = $conn->prepare("SELECT * FROM assessment_owners WHERE assessment_id = ? AND is_deleted = 0");
    if ($stmt_owners) {
        $stmt_owners->bind_param("i", $property_id);
        $stmt_owners->execute();
        $owners_result = $stmt_owners->get_result();
        while ($row = $owners_result->fetch_assoc()) {
            $owners[] = $row;
        }
        $stmt_owners->close();
    }
    
    // ✅ UPDATED: Fetch related floor details - सिर्फ नॉन-डिलीटेड (is_deleted = 0)
    $stmt_floors = $conn->prepare("SELECT * FROM assessment_floors WHERE assessment_id = ? AND is_deleted = 0");
    if ($stmt_floors) {
        $stmt_floors->bind_param("i", $property_id);
        $stmt_floors->execute();
        $floors_result = $stmt_floors->get_result();
        while ($row = $floors_result->fetch_assoc()) {
            $floors[] = $row;
        }
        $stmt_floors->close();
    }
}
$conn->close();

// Define a default image path (matching view_assesment_details.php)
$default_image_url = 'uploads/default_property.png'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Assessment Details - Surveyor View</title>
  <link href="favicon.png" rel="icon" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    
    /* THEME CHANGE: Minimal & Modern Styling */
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f4f6f9; /* Lighter, subtle background */
        color: #1f2937;
    }
    
    /* Minimal Card Style */
    .minimal-card {
        background-color: #ffffff;
        border-radius: 0.75rem; /* Slightly reduced radius */
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.08), 0 1px 2px 0 rgba(0, 0, 0, 0.02);
        transition: all 0.2s;
        border: 1px solid #f3f4f6; /* Very light border */
    }

    /* Data Display: Minimal Description List (dl/dt/dd) */
    .data-item dt {
        /* Label: Small, gray, uppercase for minimal look */
        font-size: 0.75rem; /* text-xs */
        font-weight: 500;
        color: #6b7280; /* Gray 500 */
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.25rem;
    }
    .data-item dd {
        /* Value: Larger, darker, bolder */
        font-size: 1rem; /* text-base */
        font-weight: 600;
        color: #1f2937; /* Darker text */
        line-height: 1.4;
        word-wrap: break-word;
    }
    
    .image-container img { max-height: 250px; width: 100%; object-fit: cover; }
    
    /* Style for default/placeholder images to visually distinguish them (Matching view_assesment_details.php) */
    .placeholder-image {
        border-color: #d1d5db !important; /* Light gray border */
        opacity: 0.6; /* Slightly transparent */
        filter: grayscale(100%); /* Grayscale filter */
    }
  </style>
</head>
<body class="min-h-screen">
  
  <main class="container mx-auto px-4 py-8 max-w-7xl">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Assessment Details - <span class="text-emerald-600">#<?php echo htmlspecialchars($property['new_holding'] ?? $property_id); ?></span></h1>
        <a href="surveyor-dashboard.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
        </a>
    </div>

    <div class="space-y-8">
        <?php if ($property): ?>

            <section class="minimal-card p-6">
                <h2 class="text-xl font-bold mb-6 text-emerald-700 border-b border-gray-100 pb-3">Property Details</h2>
                
                <dl class="grid grid-cols-1 md:grid-cols-3 gap-y-6 gap-x-8 text-sm">
                    
                    <div class="data-item">
                        <dt>Name of Municipality</dt>
                        <dd><?php echo htmlspecialchars($property['municipality_name'] ?? 'N/A'); ?></dd>
                    </div>
                    <div class="data-item">
                        <dt>Assessment Year</dt>
                        <dd><?php echo htmlspecialchars($property['year_of_assessment'] ?? 'N/A'); ?></dd>
                    </div>
                    <div class="data-item">
                        <dt>Ward No</dt>
                        <dd><?php echo $ward_display_value; ?></dd>
                    </div>
                    
                    <div class="data-item">
                        <dt>New Holding Number</dt>
                        <dd><?php echo htmlspecialchars($property['new_holding'] ?? 'N/A'); ?></dd>
                    </div>
                    
                    <div class="data-item">
                        <dt>Previous Holding Number</dt>
                        <dd>
                            <?php 
                                if (isset($property['previous_holding']) && !empty($property['previous_holding'])) {
                                    echo htmlspecialchars($property['previous_holding']);
                                } else {
                                  echo '<span class="text-red-600 font-bold">' . 'N/A' . '</span>';
                                }
                            ?>
                        </dd>
                    </div>
                    <div class="data-item">
                        <dt>Property Status</dt>
                        <dd><?php echo htmlspecialchars($property['property_status'] ?? 'N/A'); ?></dd>
                    </div>
                    
                    <?php if (isset($property['property_status']) && $property['property_status'] === 'Old'): ?>
                        <div class="data-item">
                            <dt>Old Holding No</dt>
                            <dd><?php echo htmlspecialchars($property['old_holding'] ?? 'N/A'); ?></dd>
                        </div>
                        <div class="data-item">
                            <dt>Old ARV</dt>
                            <dd><?php echo htmlspecialchars($property['old_pid'] ?? 'N/A'); ?></dd>
                        </div>
                    <?php 
                        // Note: If property_status is 'New' (or anything else), 
                        // this block is skipped, and Old Holding No and Old ARV 
                        // are correctly hidden, as per your request.
                        endif; 
                    ?>

                    <div class="data-item">
                        <dt>Type of Property</dt>
                        <dd><?php echo htmlspecialchars($property['property_type'] ?? 'N/A'); ?></dd>
                    </div>
                    <div class="data-item">
                        <dt>Road on which located</dt>
                        <dd><?php echo htmlspecialchars($property['road'] ?? 'N/A'); ?></dd>
                    </div>
                    <div class="data-item">
                        <dt>Area of Plot</dt>
                        <dd><?php echo htmlspecialchars($property['plot_area'] ?? 'N/A'); ?> sq.ft</dd>
                    </div>
                    <div class="data-item">
                        <dt>Buildup Type</dt>
                        <dd><?php echo htmlspecialchars($property['building_type'] ?? 'N/A'); ?></dd>
                    </div>
                </dl>
            </section>
            
            <section class="minimal-card p-6">
                <h2 class="text-xl font-bold mb-4 text-emerald-700 border-b border-gray-100 pb-3">Owner Details (<?php echo count($owners); ?>)</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-emerald-50">
                            <tr class="text-left">
                                <th class="p-3">Sr. No</th>
                                <th class="p-3">Name/Organisation</th>
                                <th class="p-3">Father's/Husband Name / PAN</th>
                                <th class="p-3">Gender</th>
                                <th class="p-3">Mobile</th>
                                <th class="p-3">Email</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <?php if (!empty($owners)): ?>
                                <?php foreach ($owners as $index => $owner): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="p-3"><?php echo $index + 1; ?></td>
                                        <td class="p-3 font-semibold"><?php echo htmlspecialchars($owner['owner_name'] ?? ''); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($owner['father_husband_pan'] ?? ''); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($owner['gender'] ?? ''); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($owner['mobile'] ?? ''); ?></td>
                                        <td class="p-3 text-gray-600"><?php echo htmlspecialchars($owner['email'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="p-3 text-center text-gray-500">No active owners found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="minimal-card p-6">
                <h2 class="text-xl font-bold mb-6 text-emerald-700 border-b border-gray-100 pb-3">Address & Location Details</h2>
                <dl class="grid grid-cols-1 md:grid-cols-3 gap-y-6 gap-x-8 text-sm">
                    <div class="data-item">
                        <dt>Property/House No</dt>
                        <dd><?php echo htmlspecialchars($property['house_no'] ?? 'N/A'); ?></dd>
                    </div>
                    <div class="data-item">
                        <dt>Plot No</dt>
                        <dd><?php echo htmlspecialchars($property['plot_no'] ?? 'N/A'); ?></dd>
                    </div>
                    <div class="data-item">
                        <dt>Khata No</dt>
                        <dd><?php echo htmlspecialchars($property['khata_no'] ?? 'N/A'); ?></dd>
                    </div>
                    <div class="data-item">
                        <dt>Khasra No</dt>
                        <dd><?php echo htmlspecialchars($property['khasra_no'] ?? 'N/A'); ?></dd>
                    </div>
                    <div class="data-item">
                        <dt>Address Line 1</dt>
                        <dd class="font-normal text-gray-800"><?php echo htmlspecialchars($property['addr1'] ?? 'N/A'); ?></dd>
                    </div>
                    <div class="data-item">
                        <dt>Address Line 2</dt>
                        <dd class="font-normal text-gray-800"><?php echo htmlspecialchars($property['addr2'] ?? 'N/A'); ?></dd>
                    </div>
                    <div class="data-item">
                        <dt>Pincode</dt>
                        <dd><?php echo htmlspecialchars($property['pincode'] ?? 'N/A'); ?></dd>
                    </div>

                    <div class="md:col-span-3 mt-4 mb-4">
                        <hr class="border-t border-gray-200">
                    </div>

                    <div class="data-item">
                        <dt>Latitude</dt>
                        <dd><?php echo htmlspecialchars($property['latitude'] ?? 'N/A'); ?></dd>
                    </div>
                    <div class="data-item">
                        <dt>Longitude</dt>
                        <dd><?php echo htmlspecialchars($property['longitude'] ?? 'N/A'); ?></dd>
                    </div>
                </dl>
            </section>

            <section class="minimal-card p-6">
                <h2 class="text-xl font-bold mb-4 text-emerald-700 border-b border-gray-100 pb-3">Floor Details (<?php echo count($floors); ?>)</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-emerald-50">
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
                                <th class="p-3">Property Name</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <?php if (!empty($floors)): ?>
                                <?php foreach ($floors as $index => $floor): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="p-3"><?php echo $index + 1; ?></td>
                                        <td class="p-3 font-semibold"><?php echo htmlspecialchars($floor['floor_no'] ?? ''); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($floor['construction_type'] ?? ''); ?></td>
                                        <td class="p-3 font-medium text-blue-600"><?php echo htmlspecialchars($floor['date_from'] ?? 'N/A'); ?></td>
                                        <td class="p-3 font-medium text-blue-600"><?php echo htmlspecialchars($floor['date_to'] ?? 'N/A'); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($floor['occupancy_type'] ?? ''); ?></td>
                                        <td class="p-3 font-semibold"><?php echo htmlspecialchars($floor['build_up_area'] ?? ''); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($floor['usage_type'] ?? ''); ?></td>
                                        <td class="p-3 text-gray-600"><?php echo htmlspecialchars($floor['non_residential_group'] ?? ''); ?></td>
                                        <td class="p-3 text-gray-600"><?php echo htmlspecialchars($floor['property_name'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="p-3 text-center text-gray-500">No active floor details found.</td> 
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="minimal-card p-6">
                <h2 class="text-xl font-bold mb-4 text-emerald-700 border-b border-gray-100 pb-3">Other Taxes</h2>
                <ul class="space-y-2">
                    <li class="flex items-center gap-3">
                        <input
                            type="checkbox"
                            name="water_tax"
                            class="rounded text-emerald-600 focus:ring-emerald-500"
                            <?php echo (isset($property['water_tax']) && $property['water_tax'] == 1) ? 'checked' : ''; ?>
                            disabled
                        >
                        <span class="font-medium">Water Tax (Piped drinking within 250 meters)</span>
                    </li>
                </ul>
            </section>

            <section class="minimal-card p-6">
                <h2 class="text-xl font-bold mb-4 text-emerald-700 border-b border-gray-100 pb-3">Property Images (GPS)</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <div class="image-container">
                        <div class="data-item mb-2"><dt>Center Image of Property</dt></div>
                        <?php if (!empty($property['center_image_gps'])): ?>
                            <a href="<?php echo htmlspecialchars($property['center_image_gps']); ?>" target="_blank" class="block group">
                                <img src="<?php echo htmlspecialchars($property['center_image_gps']); ?>" alt="Center Image" class="rounded-lg shadow-md border-2 border-emerald-300 group-hover:border-emerald-500 transition">
                            </a>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars($default_image_url); ?>" target="_blank" class="block group" title="No uploaded image - Showing default">
                                <img src="<?php echo htmlspecialchars($default_image_url); ?>" alt="Default Image" class="rounded-lg shadow-md border-2 border-gray-300 transition placeholder-image">
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="image-container">
                        <div class="data-item mb-2"><dt>Left Image of Property</dt></div>
                        <?php if (!empty($property['left_image_gps'])): ?>
                            <a href="<?php echo htmlspecialchars($property['left_image_gps']); ?>" target="_blank" class="block group">
                                <img src="<?php echo htmlspecialchars($property['left_image_gps']); ?>" alt="Left Image" class="rounded-lg shadow-md border-2 border-emerald-300 group-hover:border-emerald-500 transition">
                            </a>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars($default_image_url); ?>" target="_blank" class="block group" title="No uploaded image - Showing default">
                                <img src="<?php echo htmlspecialchars($default_image_url); ?>" alt="Default Image" class="rounded-lg shadow-md border-2 border-gray-300 transition placeholder-image">
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="image-container">
                        <div class="data-item mb-2"><dt>Right Image of Property</dt></div>
                        <?php if (!empty($property['right_image_gps'])): ?>
                            <a href="<?php echo htmlspecialchars($property['right_image_gps']); ?>" target="_blank" class="block group">
                                <img src="<?php echo htmlspecialchars($property['right_image_gps']); ?>" alt="Right Image" class="rounded-lg shadow-md border-2 border-emerald-300 group-hover:border-emerald-500 transition">
                            </a>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars($default_image_url); ?>" target="_blank" class="block group" title="No uploaded image - Showing default">
                                <img src="<?php echo htmlspecialchars($default_image_url); ?>" alt="Default Image" class="rounded-lg shadow-md border-2 border-gray-300 transition placeholder-image">
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="minimal-card p-6">
                <h2 class="text-xl font-bold mb-4 text-emerald-700 border-b border-gray-100 pb-3">Supporting Documents & Proofs</h2>
                
                <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-y-6 gap-x-8 text-sm">
                    <?php 
                        // Array of documents to display, matching view_assesment_details.php
                        $documents_to_display = [
                            'Main Proof Document'   => $property['doc_proof'] ?? null,
                            'Supporting Document 1' => $property['supporting_doc_1'] ?? null,
                            'Supporting Document 2' => $property['supporting_doc_2'] ?? null,
                            'Supporting Document 3' => $property['supporting_doc_3'] ?? null,
                        ];
                        foreach ($documents_to_display as $label => $path):
                    ?>
                    <div class="data-item">
                        <dt><?php echo $label; ?></dt>
                        <dd>
                            <?php if (!empty($path)): ?>
                                <i class="fa fa-file-alt text-gray-500 mr-2"></i>
                                <a href="<?php echo htmlspecialchars($path); ?>" target="_blank" class="text-emerald-600 hover:text-emerald-700 hover:underline font-medium transition">View Document</a>
                            <?php else: ?>
                                <span class="text-gray-500 font-medium">No uploaded file.</span>
                            <?php endif; ?>
                        </dd>
                    </div>  
                    <?php endforeach; ?>
                </dl>
            </section>
            
        <?php else: ?>
            <div class="text-center p-10 minimal-card">
                <p class="text-lg font-semibold text-red-600">Assessment not found or you do not have permission to view it.</p>
                <p class="text-gray-600 mt-2">The assessment ID is invalid or does not belong to your assigned surveys.</p>
                <a href="surveyor-dashboard.php" class="mt-4 inline-block px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition">Go Back</a>
            </div>
        <?php endif; ?>
    </div>

    <footer class="text-center text-gray-500 text-sm mt-12 p-4">
        © 2025 Deoria Nagar Parishad - All Rights Reserved.
    </footer>

</main>
</body>
</html>