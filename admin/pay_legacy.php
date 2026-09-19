<?php
session_start();
// Check authentication and redirect if necessary
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once "db.php";

// Debug (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$base_image_path = 'admin-uploads/';
$default_image_file = 'man.png';
$default_image_src = $default_image_file;

$user_id = $_SESSION['user_id'];

// Fetch user data for header
$stmt = $conn->prepare("SELECT username, email, role, profile_pic FROM users WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$user_name = "User Not Found";
$user_email = "Email Not Found";
$user_role = "user";
$profile_image_src = $default_image_src;

if ($result && $result->num_rows === 1) {
    $user_data = $result->fetch_assoc();
    $user_name = $user_data['username'];
    $user_email = $user_data['email'];
    $user_role = $user_data['role'];
    if (!empty($user_data['profile_pic'])) {
        $profile_image_src = $base_image_path . htmlspecialchars($user_data['profile_pic']);
    }
}
if ($stmt) $stmt->close();

// Get parameters from URL (from view-legecy.php redirection)
$propertyId = htmlspecialchars($_GET['propertyId'] ?? 'N/A');
$paymentType = htmlspecialchars($_GET['type'] ?? 'full');
$paymentMode = htmlspecialchars($_GET['mode'] ?? 'cash');

// --- Mock Data for demonstration (Replace with actual database fetches) ---
$propertyDetails = [
    'PropertyNo' => '01020005CP',
    'TotalTax' => '717.00',
    'AddressCode' => '01020005CP',
    'OwnerName' => 'SMT ISHRAWATI DEVI',
];

$taxSummary = [
    ['label' => 'Arrear', 'ST' => '0.00', 'HT' => '476.00', 'WT' => '0.00', 'Rebate' => '0.00', 'Interest' => '0.00', 'Total' => '476.00'],
    ['label' => 'Current', 'ST' => '0.00', 'HT' => '241.00', 'WT' => '0.00', 'Rebate' => '0.00', 'Interest' => '0.00', 'Total' => '241.00'],
];
$totalTaxAmount = '717.00'; // Sum of all totals

// Using the fetched owner name as the default tax payer name
$ownerTaxPayerName = $propertyDetails['OwnerName'] ?? ''; 
// ---------------------------------------------------------------------

?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Payment Gateway - khalilabad Nagar Palika</title>
<link href="favicon.png" rel="icon">
<link href="css/mystyle.css" rel="stylesheet">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
<script src="https://cdn.tailwindcss.com"></script>

<script>
tailwind.config = {
    theme: {
        extend: {
            colors: {
                primary: { 500: '#10B981', 600: '#059669', 700: '#047857' }
            }
        }
    }
}
</script>

<style>
/* Base styles from view-legecy.php to maintain consistency */
.glass { background: rgba(255,255,255,0.85); backdrop-filter: blur(6px); }
.hover-glass:hover { transform: translateY(-2px); transition: .15s ease; }

/* Custom styles for Payment Gateway page */
.payment-card {
    background-color: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(16,24,40,0.06);
    padding: 24px;
    margin-bottom: 24px;
}
.payment-card-header {
    display: flex;
    align-items: center;
    margin-bottom: 24px;
}
.payment-card-icon {
    width: 48px;
    height: 48px;
    background-color: #f3f4f6; /* Light gray background for icon */
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 16px;
}
.payment-card-title {
    font-size: 1.5rem; /* 24px */
    font-weight: 700; /* bold */
    color: #1f2937; /* gray-900 */
}

/* Table styles (Minimal and clean) */
.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    font-size: 0.95rem;
    border-radius: 8px; /* Added rounded corners for tables */
    overflow: hidden; /* To apply border radius */
}
.data-table th, .data-table td {
    padding: 12px 16px; /* Increased padding for minimalism */
    border: 1px solid #e5e7eb; /* gray-200 */
    text-align: left;
}
.data-table th {
    background-color: #f3f4f6; /* Lighter background for headers */
    font-weight: 600;
    color: #4b5563; /* gray-600 */
}
.data-table .label {
    font-weight: 500;
    color: #374151; /* gray-700 */
    width: 35%; 
}
.data-table .value {
    color: #1f2937; /* gray-900 */
    width: 65%; 
}

/* Tax summary specific styling */
.tax-summary-table thead th {
    text-align: center;
}
.tax-summary-table tbody td {
    text-align: center;
}
.tax-summary-table .first-col {
    text-align: left;
    font-weight: 500;
    color: #374151;
}
.tax-summary-table tfoot td {
    font-weight: 700; /* Slightly bolder for totals */
    color: #1f2937;
    background-color: #e5e7eb; /* Light gray total footer */
}
.tax-summary-table tfoot .total-label {
    text-align: right;
}

/* Input group with icon */
.input-group-icon {
    position: relative;
    margin-top: 8px;
}
.input-group-icon input,
.input-group-icon select {
    width: 100%;
    padding: 10px 12px 10px 40px; 
    border: 1px solid #d1d5db;
    border-radius: 8px; /* Slightly more rounded inputs */
    background-color: #fff; /* White background for clean look */
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); /* subtle inset shadow */
    font-size: 0.95rem;
    color: #374151;
    appearance: none; 
}
.input-group-icon .input-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #10B981; /* Green icon color */
    pointer-events: none;
}
.input-group-icon select.dropdown-arrow {
    background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"%3e%3cpath fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"%3e%3c/path%3e%3c/svg%3e');
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem; 
}

/* Checkbox styling */
.checkbox-group {
    display: flex;
    align-items: flex-start;
    margin-bottom: 16px; 
}
.checkbox-group input[type="checkbox"] {
    margin-right: 10px;
    margin-top: 4px; 
    flex-shrink: 0;
    width: 18px;
    height: 18px;
    /* Updated to Green Accent Color */
    accent-color: #10B981; 
}
.checkbox-group label {
    font-size: 0.9rem; 
    color: #4b5563; 
    line-height: 1.5;
}

/* Pay Now button */
.pay-now-btn {
    /* Updated to Green Primary Color */
    background-color: #10B981; /* primary-500 */ 
    color: #fff;
    padding: 12px 30px; 
    border-radius: 8px;
    font-weight: 600;
    font-size: 1.05rem;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4); 
    transition: all 0.2s ease;
}
.pay-now-btn:hover {
    /* Updated hover color to darker green */
    background-color: #059669; /* primary-600 */
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.6);
    transform: translateY(-1px);
}

/* Dropdown specific styles */
.dropdown-label { display: block; font-weight: 500; color: #374151; margin-bottom: 8px; }

</style>
</head>
<body class="flex min-h-screen text-gray-800 bg-gray-50">

<div id="sidebar-backdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

<?php include "sidemenu.php"; ?>

<div class="content-wrapper flex flex-col flex-1 w-full lg:w-[calc(100%-280px)]">

    <header class="flex items-center justify-between glass p-4 rounded-2xl">
        <h1 class="text-2xl font-bold text-gray-900">Payment Gateway</h1>

        <div class="flex items-center space-x-4">
            <div class="relative">
                <input type="text" placeholder="Search..." class="pl-10 pr-4 py-2 rounded-full glass text-sm focus:outline-none">
                <span class="absolute left-3 top-2.5 text-gray-400"><i class="fa fa-search"></i></span>
            </div>

            <button class="relative w-10 h-10 rounded-full glass flex items-center justify-center hover-glass">
                <i class="fa fa-bell"></i>
                <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>

            <a href="new-assesment.php" class="relative w-10 h-10 rounded-full glass flex items-center justify-center hover-glass">
                <i class="fa fa-plus"></i>
            </a>

            <div class="relative inline-block text-left ml-4">
                <button id="profileBtn" class="flex items-center space-x-2 focus:outline-none">
                    <div class="text-right hidden md:block">
                        <p class="text-base font-semibold text-gray-800"><?php echo htmlspecialchars($user_name); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user_email); ?></p>
                    </div>
                    <div class="w-10 h-10 rounded-full overflow-hidden border border-gray-200">
                        <img src="<?php echo $profile_image_src; ?>" alt="<?php echo htmlspecialchars($user_name); ?>'s Profile" class="w-full h-full object-cover">
                    </div>
                </button>

                <div id="profileMenu" class="hidden absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-xl z-50 py-1">
                    <a href="update-profile.php" class="flex items-center px-4 py-2 text-base text-gray-700 hover:bg-gray-100 rounded-lg">
                        <i class='bx bx-user-circle text-xl mr-2 text-blue-500'></i> Update Profile
                    </a>
                    <a href="reset-password.php" class="flex items-center px-4 py-2 text-base text-gray-700 hover:bg-gray-100 rounded-lg">
                        <i class='bx bx-lock-alt text-xl mr-2 text-primary-500'></i> Reset Password
                    </a>
                    <hr class="my-1 border-gray-200">
                    <a href="logout.php" class="flex items-center px-4 py-2 text-base text-gray-700 hover:bg-red-50 rounded-lg">
                        <i class='bx bx-log-out text-xl mr-2 text-red-500'></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 p-6 space-y-8 overflow-y-auto">

        <div class="payment-card mx-2 lg:mx-0">
            <div class="payment-card-header">
                <div class="payment-card-icon">
                    <i class='bx bxs-credit-card text-2xl text-primary-600'></i>
                </div>
                <h3 class="payment-card-title">Payment Gateway</h3>
                <div class="ml-auto">
                    <a href="view-legecy.php" class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-lg text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors duration-150">
                        <i class='bx bx-arrow-back mr-1'></i> Back
                    </a>
                </div>
            </div>

            <table class="data-table">
                <tbody>
                    <tr>
                        <td class="label">Property No</td>
                        <td class="value"><?php echo $propertyDetails['PropertyNo']; ?></td>
                    </tr>
                    <tr>
                        <td class="label">Total Tax</td>
                        <td class="value"><?php echo $propertyDetails['TotalTax']; ?></td>
                    </tr>
                    <tr>
                        <td class="label">Address Code</td>
                        <td class="value"><?php echo $propertyDetails['AddressCode']; ?></td>
                    </tr>
                    <tr>
                        <td class="label">Owner Name</td>
                        <td class="value"><?php echo $propertyDetails['OwnerName']; ?></td>
                    </tr>
                </tbody>
            </table>

            <table class="data-table tax-summary-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="first-col"></th>
                        <th colspan="3">Tax</th>
                        <th rowspan="2">Rebate</th>
                        <th rowspan="2">Interest</th>
                        <th rowspan="2">Total</th>
                    </tr>
                    <tr>
                        <th>ST</th>
                        <th>HT</th>
                        <th>WT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($taxSummary as $row): ?>
                    <tr>
                        <td class="first-col"><?php echo $row['label']; ?></td>
                        <td><?php echo $row['ST']; ?></td>
                        <td><?php echo $row['HT']; ?></td>
                        <td><?php echo $row['WT']; ?></td>
                        <td><?php echo $row['Rebate']; ?></td>
                        <td><?php echo $row['Interest']; ?></td>
                        <td><?php echo $row['Total']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6" class="total-label">Total</td>
                        <td><?php echo $totalTaxAmount; ?></td>
                    </tr>
                </tfoot>
            </table>

            <p class="text-sm text-gray-600 mt-4 mb-8">
                *Discount of 0 percentage Rs.0.00 on Current Year for Cash Payment is added as Rebate.
            </p>

            <div class="mb-6">
                <label for="ownerTaxPayerName" class="dropdown-label">Name of the Owner/Tax Payer*</label>
                <div class="input-group-icon">
                    <span class="input-icon"><i class='bx bx-user'></i></span>
                    <input type="text" id="ownerTaxPayerName" name="ownerTaxPayerName" class="oc-input !w-full" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                
                <div>
                    <label for="paymentMadeAt" class="dropdown-label">Payment Made At*</label>
                    <div class="input-group-icon">
                        <span class="input-icon"><i class='bx bx-building'></i></span>
                        <select id="paymentMadeAt" name="paymentMadeAt" class="oc-input !w-full dropdown-arrow">
                            <option value="tax_collector" selected>Tax Collector</option>
                            <option value="bank">Bank</option>
                            <option value="online_portal">Online Portal</option>
                        </select>
                    </div>
                </div>

                
                <div>
                    <label for="modeOfPayment" class="dropdown-label">Mode Of Payment*</label>
                    <div class="input-group-icon">
                        <span class="input-icon"><i class='bx bx-wallet'></i></span>
                        <select id="modeOfPayment" name="modeOfPayment" class="oc-input !w-full dropdown-arrow">
                            <option value="cash" <?php echo ($paymentMode == 'cash') ? 'selected' : ''; ?>>Cash</option>
                            <option value="online" <?php echo ($paymentMode == 'online') ? 'selected' : ''; ?>>Online</option>
                            <option value="cheque">Cheque</option>
                            <option value="dd">DD</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mb-8 p-4 bg-primary-50/50 border border-primary-100 rounded-lg">
                <p class="text-primary-700 font-semibold mb-2">Declaration</p>
                <div class="checkbox-group">
                    <input type="checkbox" id="declaration1" name="declaration1" checked>
                    <label for="declaration1">I/We hereby declare that the above information and property tax assessment based there one is correct to the best of my/our knowledge and belief and I/We undertake to abide by the relevant provisions of the Nagar Palika Parishad khalilabad.</label>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="declaration2" name="declaration2" checked>
                    <label for="declaration2">I/We fully understand that any information furnished above, if proved incorrect or false will render me/us liable for penal action or other consequences as may be prescribed in Laws, Rules or Regulations framed by Govt. of Nagar Palika Parishad khalilabad.</label>
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <button type="submit" class="pay-now-btn">
                    <i class='bx bxs-check-circle mr-2'></i> Pay Now (₹<?php echo $totalTaxAmount; ?>)
                </button>
            </div>

        </div> 

        <footer class="text-center text-gray-500 text-sm mt-6">
            © 2025 khalilabad Nagar Palika Parishad - All Rights Reserved.
        </footer>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Profile menu toggle (same as earlier pages)
    const profileBtn = document.getElementById('profileBtn');
    const profileMenu = document.getElementById('profileMenu');
    if (profileBtn && profileMenu) {
      profileBtn.addEventListener('click', function(e){
        e.stopPropagation();
        profileMenu.classList.toggle('hidden');
      });
      document.addEventListener('click', function(e){
        if (!profileMenu.contains(e.target) && !profileBtn.contains(e.target)) profileMenu.classList.add('hidden');
      });
    }

    // Sidebar toggle (if sidemenu.php uses #menu-toggle and #sidebar)
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    function toggleSidebar(){ if(!sidebar) return; sidebar.classList.toggle('open'); backdrop.classList.toggle('hidden'); }
    if (menuToggle) menuToggle.addEventListener('click', toggleSidebar);
    if (backdrop) backdrop.addEventListener('click', toggleSidebar);
    window.addEventListener('resize', () => { if(window.innerWidth >= 1024 && sidebar){ sidebar.classList.remove('open'); if (backdrop) backdrop.classList.add('hidden'); } });

    // Set initial dropdown values based on URL parameters (if any)
    const modeOfPaymentSelect = document.getElementById('modeOfPayment');
    const urlParams = new URLSearchParams(window.location.search);
    const modeParam = urlParams.get('mode');
    if (modeParam && modeOfPaymentSelect) {
        modeOfPaymentSelect.value = modeParam;
    }

    // Add submit logic here if needed (e.g., handling form submission/redirection to payment)
});
</script>

</body>
</html>