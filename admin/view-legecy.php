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

// Fetch user data
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
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>View Legacy Property - Deoria Nagar Palika</title>
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
/* Basic glass card style (matches your UI) */
.glass { background: rgba(255,255,255,0.85); backdrop-filter: blur(6px); }
.hover-glass:hover { transform: translateY(-2px); transition: .15s ease; }

/* Tabs */
.tab-btn { padding: .5rem .9rem; border-radius: 8px; font-weight:600; }
.tab-btn.active { background: #111827; color: #fff; }

/* Frame area (iframe container) */
.frame-wrap { border:1px solid #e6e6e6; border-radius:12px; padding:12px; background:#fff; }
#a4frame { width:100%; height:760px; border:0; border-radius:8px; box-shadow: 0 6px 20px rgba(16,24,40,0.06); background:#fff; }

/* Other charges form styles */
.oc-input { border:1px solid #e5e7eb; border-radius:6px; padding:.6rem .8rem; width:260px; }
.oc-submit { background:#f59e0b; color:#fff; padding:.45rem .9rem; border-radius:6px; font-weight:600; }

/* Table card */
.table-card { box-shadow: 0 8px 30px rgba(16,24,40,0.06); border-radius:12px; background:#fff; padding:18px; }

/* Payment Modal Styles */
.modal-backdrop {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background-color: rgba(0, 0, 0, 0.5); z-index: 50; display: none;
    align-items: center; justify-content: center;
}
.modal-content {
    background-color: white; padding: 20px; border-radius: 12px;
    width: 90%; max-width: 450px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    position: relative;
}
.modal-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 20px;
}
.modal-close-btn {
    background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280;
}
.dropdown-label {
    display: block; font-weight: 500; color: #374151; margin-bottom: 8px;
}
.dropdown-select {
    width: 100%; padding: 10px 12px; border: 1px solid #d1d5db;
    border-radius: 6px; appearance: none;
    background-color: #f9fafb;
}
.dropdown-icon {
    position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
    pointer-events: none; color: #6b7280;
}


/* Responsive tweaks */
@media (max-width: 768px) {
    #a4frame { height:520px; }
    .oc-input { width:100%; }
}
</style>
</head>
<body class="flex min-h-screen text-gray-800 bg-gray-50">

<div id="sidebar-backdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

<?php include "sidemenu.php"; ?>

<div class="content-wrapper flex flex-col flex-1 w-full lg:w-[calc(100%-280px)]">

    <header class="flex items-center justify-between glass p-4 rounded-2xl">
        <h1 class="text-2xl font-bold text-gray-900">View Legacy Property</h1>

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
                        <i class='bx bx-lock-alt text-xl mr-2 text-yellow-600'></i> Reset Password
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

        <div class="glass rounded-2xl p-6 mx-2 lg:mx-0">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-6">
                    <h3 class="text-xl font-bold">View Legacy Property</h3>

                    <div class="flex items-center space-x-2">
                        <button id="tab-basic" class="tab-btn bg-gray-800 text-white rounded-md">BASIC</button>
                        <button id="tab-other" class="tab-btn bg-gray-200 text-gray-800 rounded-md">OTHER CHARGES</button>
                    </div>
                </div>
                <div></div>
            </div>

            </div>

        <div class="glass rounded-2xl p-6 mx-2 lg:mx-0">

            <div id="tab-content-basic" class="">
                <div class="frame-wrap">
                    <iframe id="a4frame" name="a4frame" title="Deoria A4 Preview" sandbox="allow-same-origin allow-scripts"></iframe>

                    <div class="mt-4 flex justify-end items-center space-x-3">
                        <button id="payNowBtn" class="oc-submit">Pay Now</button>
                        <button id="printBtn" class="px-4 py-2 border rounded-md bg-gray-100 text-gray-800">Print</button>
                    </div>
                </div>
            </div>

            <div id="tab-content-other" class="hidden">
                <div class="p-4">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-16 h-16 rounded-lg bg-white shadow flex items-center justify-center">
                            <i class="fa fa-pie-chart text-orange-500 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-semibold">Deoria Other Charges</h3>
                            <p class="text-sm text-gray-500">Add miscellaneous charges to the property demand.</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-end gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                            <input id="oc_amount" class="oc-input" placeholder="Amount">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Remark</label>
                            <input id="oc_remark" class="oc-input" placeholder="Enter Remark">
                        </div>
                        <div class="flex items-center gap-2">
                            <button id="oc_submit" class="oc-submit">Submit</button>
                            <button id="oc_reset" class="px-4 py-2 border rounded-md bg-orange-200 text-white" style="background:#f5952f;">Reset</button>
                        </div>
                    </div>

                    <div class="table-card">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-sm font-semibold text-gray-700">
                                    <th class="py-3 px-4 border-b">Sr. No</th>
                                    <th class="py-3 px-4 border-b">Amount</th>
                                    <th class="py-3 px-4 border-b">Remark</th>
                                    <th class="py-3 px-4 border-b">Action</th>
                                </tr>
                            </thead>
                            <tbody id="oc_table_body">
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-gray-500">No records yet</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

        </div>

        <footer class="text-center text-gray-500 text-sm mt-6">
            © 2025 Deoria Nagar Palika Parishad - All Rights Reserved.
        </footer>
    </main>
</div>

<div id="paymentModal" class="modal-backdrop">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="text-xl font-bold text-gray-900">Pay Now</h2>
            <button id="closeModalBtn" class="modal-close-btn">&times;</button>
        </div>

        <div class="space-y-6">
            <div>
                <label for="typesOfPayment" class="dropdown-label">Types of Payment*</label>
                <div class="relative">
                    <select id="typesOfPayment" class="dropdown-select">
                        <option value="full" selected>Full Payment</option>
                        </select>
                    <i class='bx bx-credit-card dropdown-icon'></i>
                </div>
            </div>

            <div>
                <label for="modeOfPayment" class="dropdown-label">Mode of Payment*</label>
                <div class="relative">
                    <select id="modeOfPayment" class="dropdown-select">
                        <option value="cash" selected>Cash</option>
                        <option value="online">Online</option>
                        </select>
                    <i class='bx bx-key dropdown-icon'></i>
                </div>
            </div>

            <div class="flex justify-end pt-4">
                <button id="confirmPayNow" class="oc-submit px-6 py-2">Pay Now</button>
            </div>
        </div>
    </div>
</div>
<script id="deoria-a4-template" type="text/template">
<!doctype html>
<html lang="hi">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Deoria Demand Bill (A4)</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
<style>
/* Reset and A4 specific styles */
body { margin:0; padding:0; background:#f3f4f6; font-family: 'Noto Sans', Arial, sans-serif; }
.a4-wrap { width: 1000px; max-width:100%; margin:18px auto; background:#fff; border:2px solid #111; padding:18px; box-sizing:border-box; }
.center { text-align:center; }
.deoria-title { color:#0b6b53; font-weight:700; font-size:28px; }
.sub-title { color:#0b6b53; font-weight:600; font-size:18px; margin-top:4px; }
.muted { color:#4b5563; }
.info-block { display:flex; gap:20px; align-items:flex-start; margin-top:18px; }
.left-col { width:45%; }
.right-col { width:45%; text-align:left; }
.small-label { color:#0b6b53; font-weight:600; display:block; margin-bottom:6px; }
.data-val { font-weight:600; }
.summary-table { margin-top:14px; width:100%; border-collapse:collapse; }
.summary-table th, .summary-table td { border:1px solid #111; padding:8px; text-align:center; }
.notice { margin-top:14px; color:#b91c1c; font-weight:700; }
.bottom { margin-top:18px; display:flex; justify-content:space-between; gap:10px; align-items:flex-end; }
.qr { width:120px; height:120px; border:1px dashed #e5e7eb; display:flex; align-items:center; justify-content:center; }
.action-row { text-align:center; margin-top:14px; }
.pay-btn { background:#059669; color:#fff; padding:8px 14px; border-radius:8px; border:none; font-weight:600; cursor:pointer; }
.print-btn { background:#f3f4f6; color:#111; padding:8px 14px; border-radius:8px; border:1px solid #d1d5db; cursor:pointer; }

/* Responsive adjustments for the iframe view */
@media (max-width: 768px) {
    .a4-wrap { padding: 10px; }
    .deoria-title { font-size: 20px; }
    .sub-title { font-size: 14px; }
    .info-block { flex-direction: column; gap: 10px; }
    .left-col, .right-col { width: 100%; }
    .summary-table th, .summary-table td { font-size: 10px; padding: 4px; }
}

</style>
</head>
<body>
  <div class="a4-wrap" role="document">
    <div class="center">
      <div style="display:flex; justify-content:center; align-items:center; gap:20px;">
        <img src="logo.png" alt="Deoria Logo" style="height:70px; width:auto;" onerror="this.style.display='none'">
        <div>
          <div class="deoria-title">नगर पालिका परिषद देवरिया</div>
          <div class="sub-title">संपत्तिकर डिमांड बिल (2025-26)</div>
        </div>
        <img src="up-logo.png" alt="Govt Emblem" style="height:70px; width:auto;" onerror="this.style.display='none'">
      </div>
    </div>

    <div class="info-block">
      <div class="left-col">
        <div><span class="small-label">बिल संख्या</span><div class="data-val">DEO-2025-0001</div></div>
        <div style="margin-top:8px"><span class="small-label">संपत्ति आईडी</span><div class="data-val">01020001-T</div></div>
        <div style="margin-top:8px"><span class="small-label">नाम</span><div class="data-val">SEEMA BHARTI</div></div>
        <div style="margin-top:8px"><span class="small-label">पुत्र/पति का नाम</span><div class="data-val">RAM KEDAR</div></div>
        <div style="margin-top:8px"><span class="small-label">मोबाइल नंबर</span><div class="data-val">9198XXXXXX</div></div>
        <div style="margin-top:8px"><span class="small-label">जोन / वार्ड / मोहल्ला</span><div class="data-val">Zone 5 / 4 / MANBELA PEER SHAHEED</div></div>
      </div>

      <div class="right-col">
        <div><span class="small-label">संपत्ति की स्थिति</span><div class="data-val">NA</div></div>
        <div style="margin-top:8px"><span class="small-label">भवन संख्या</span><div class="data-val">001</div></div>
        <div style="margin-top:8px"><span class="small-label">दिनांक</span><div class="data-val">07-11-2025</div></div>
        <div style="margin-top:8px"><span class="small-label">सर्वे नं</span><div class="data-val">-</div></div>
      </div>
    </div>

    <div class="action-row">
      <table class="summary-table">
        <thead>
          <tr>
            <th>वित्तीय-वर्ष</th>
            <th>सामान्य कर</th>
            <th>जल कर</th>
            <th>सीवर कर</th>
            <th>छूट</th>
            <th>ब्याज</th>
            <th>योग</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Till March 2025 (Arrear)*</td>
            <td>0.00</td><td>0.00</td><td>1010.11</td><td>0.00</td><td>60.00</td><td>1070.11</td>
          </tr>
          <tr>
            <td>2025-2026 (Current)</td>
            <td>0.00</td><td>0.00</td><td>506.00</td><td>0.00</td><td>0.00</td><td>506.00</td>
          </tr>
          <tr>
            <td colspan="6" style="text-align:right; font-weight:700; color:#0b6b53;">कुल</td>
            <td style="font-weight:700;">1576.00</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="notice">A sum of Rs <b>1576.00</b> is demand on the property with holding No. <b>01020001-T</b> for FY 2025-2026.</div>

    <div class="bottom">
      <div style="flex:1">
        <ul style="padding-left:16px; font-size:12px;">
          <li style="margin-bottom:6px;">*इस बिल पर अंकित अवशेष मांग की धनराशि मा० कार्यकारिणी समिति की बैठक दिनांक 05/12/2023 में स्वीकृत प्रस्ताव के क्रम में शासन से प्राप्त होने वाले आदेश की प्रत्याशा में है.</li>
          <li style="margin-bottom:6px;">1.जी०आई०एस० सर्वेक्षण के आधार पर पुनरीक्षण प्रक्रिया पूर्ण होने के उपरांत अंतर धनराशि नियमानुसार प्रभावी तिथि से अनिवार्य रूप से देय होगी.</li>
          <li style="margin-bottom:6px;">2.वित्तीय वर्ष 2024-25 के अवशेष मांग (ARREAR DEMAND) पर भुगतान किये जाने की तिथि तक 1% (एक फीसदी) प्रतिमाह की दर से साधारण ब्याज देय होगा </li>
          <li style="margin-bottom:6px;">3.आप अपने संपत्ति कर बिल का आंनलाइन भुगतान deoria.upmunicipal.com वेबसाइट के माध्यम से कर सकते हैं |</li>
          <li style="margin-bottom:6px;">4.संपत्ति कर की अवशेष बकाया धनराशि पर नियमानुसार 12 प्रतिशत साधारण ब्याज देय होगा |</li>
          <li style="margin-bottom:6px;">5.इस बिल के संबंध मे कोई शिकायत है तो देय तिथि के अंदर संपत्ति कर विभाग,नगर निगम,गोरखपुर को भेजना आवश्यक है,बिल का भुगतान देय तिथि तक किया जाना अनिवार्य है |</li>
          <li style="margin-bottom:6px;">6.किसी भवन के संबंध मे भुगतान संबंधी कोई विवाद न्यायालय मे विचाराधीन होने के कर कारण स्टे है तो उस परिस्थिति मे विवरण सहित नगर आयुक्त नगर निगम, गोरखपुर को लिखित सूचित करें</li>
          <li style="margin-bottom:6px;">7.देय तिथि तक धनराशि भुगतान न करने पर उत्तर प्रदेश नगर निगम अधिनियम 1959 के प्रावधानों के तहत धारा – 506,507,509,514 व 516 के तहत डिमाण्ड नोटिस, कुर्की वारंट, किरायेदार अटैचमेंट,खाता कुर्क आदि की कार्यवाही की जाएगी|</li>
          <li style="margin-bottom:6px;">8.यह बिल या इसमें अंकित प्रविष्टि किसी भी दशा मे स्वामित्व/धारणाधिकार का साक्ष्य नहीं है और इस हेतु इसका प्रयोग अवैध होगा और इसे शून्य माना जाएगा |</li>
          <li style="margin-bottom:6px;">9.यह मात्र बिल है, यह रसीद नहीं है |</li>
          <li style="margin-bottom:6px;">10.जिन भवनों के संबंधित करदाता जीवित नहीं है तो उसकी नई प्रविष्टि नगर निगम अभिलेखों मे नियमानुसार नामांतरण कर लेनी चाहिए | यह हितबद्ध पक्ष का दायित्व है |</li>
        </ul>
      </div>
      <div style="width:160px; text-align:center;">
        <div class="qr" id="qrcode">QR</div>
        <div style="margin-top:6px; font-size:12px;">Scan for online payment</div>
        <div style="margin-top:10px; font-size:14px; color:#1f2937; font-weight:600;">(Legacy Property)</div>
      </div>
    </div>

    <div class="action-row">
      <button class="pay-btn" onclick="navigateToPayment()">Pay Now</button>
      <button class="print-btn" onclick="openPrintView()">  <i class='bx bx-printer mr-2'></i>  Print</button>
    </div>

    <div style="text-align:center; margin-top:10px; font-size:12px; color:#6b7280;">
      Nagar Palika Parishad Deoria
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  
  <script>
  // JavaScript function to trigger the print dialog
  function openPrintView() {
   
    window.print();
  }
</script>

  
  
  <script>
    // QR code generation for the A4
    try {
      var qrc = new QRCode(document.getElementById("qrcode"), {
        text: "https://deoria.example.com/pay?prop=01020001-T",
        width: 120, height: 120
      });
    } catch(e){ console.warn(e); }

    // Function to trigger the main page's pay button from inside the iframe
    function navigateToPayment() {
      if (window.parent) {
        window.parent.document.getElementById('payNowBtn').click();
      }
    }

    // Hide print button during printing (optional)
    var printBtn = document.querySelector('.print-btn');
    window.onbeforeprint = function(){ if(printBtn) printBtn.style.display='none'; }
    window.onafterprint = function(){ if(printBtn) printBtn.style.display='inline-block'; }
  </script>
</body>
</html>
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab elements
    const tabBasicBtn = document.getElementById('tab-basic');
    const tabOtherBtn = document.getElementById('tab-other');
    const tabBasicPane = document.getElementById('tab-content-basic');
    const tabOtherPane = document.getElementById('tab-content-other');

    function showBasic() {
      tabBasicBtn.classList.add('bg-gray-800','text-white'); tabBasicBtn.classList.remove('bg-gray-200','text-gray-800');
      tabOtherBtn.classList.remove('bg-gray-800','text-white'); tabOtherBtn.classList.add('bg-gray-200','text-gray-800');
      tabBasicPane.classList.remove('hidden'); tabOtherPane.classList.add('hidden');
    }
    function showOther() {
      tabOtherBtn.classList.add('bg-gray-800','text-white'); tabOtherBtn.classList.remove('bg-gray-200','text-gray-800');
      tabBasicBtn.classList.remove('bg-gray-800','text-white'); tabBasicBtn.classList.add('bg-gray-200','text-gray-800');
      tabOtherPane.classList.remove('hidden'); tabBasicPane.classList.add('hidden');
    }
    tabBasicBtn.addEventListener('click', showBasic);
    tabOtherBtn.addEventListener('click', showOther);

    // Default to BASIC on load
    showBasic();

    // Inject A4 template into iframe via srcdoc
    const iframe = document.getElementById('a4frame');
    const template = document.getElementById('deoria-a4-template');
    if (iframe && template) {
        // set srcdoc to the template's innerHTML
        iframe.srcdoc = template.innerHTML;
    }

    // Pay & Print buttons
    const payNowBtn = document.getElementById('payNowBtn');
    const printBtn = document.getElementById('printBtn');

    // Payment Modal Elements
    const paymentModal = document.getElementById('paymentModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const confirmPayNowBtn = document.getElementById('confirmPayNow');
    const typesOfPaymentSelect = document.getElementById('typesOfPayment');
    const modeOfPaymentSelect = document.getElementById('modeOfPayment');

    // Function to show the modal
    function showPaymentModal() {
      paymentModal.style.display = 'flex';
    }

    // Function to hide the modal
    function hidePaymentModal() {
      paymentModal.style.display = 'none';
    }

    // Handle Pay Now button click (Opens Modal)
    payNowBtn.addEventListener('click', showPaymentModal);

    // Handle modal close button and backdrop click
    closeModalBtn.addEventListener('click', hidePaymentModal);
    paymentModal.addEventListener('click', function(e) {
      if (e.target === paymentModal) { // Check if click is on the backdrop
        hidePaymentModal();
      }
    });

    // Handle 'Pay Now' button click inside the modal (Redirects)
    confirmPayNowBtn.addEventListener('click', function() {
      const type = typesOfPaymentSelect.value;
      const mode = modeOfPaymentSelect.value;
      
      // Property ID hardcoded for demo, replace with actual PHP variable if available later
      const propertyId = '01020001-T'; 
      
      // Logic to redirect to pay_legacy.php with selected options
      const redirectUrl = `pay_legacy.php?propertyId=${propertyId}&type=${type}&mode=${mode}`;
      console.log(`Redirecting to: ${redirectUrl}`);
      
      // Use window.location.href to navigate to the payment page
      window.location.href = redirectUrl;
      
      hidePaymentModal(); // Hide modal before redirect
    });
    
    printBtn.addEventListener('click', function() {
      try {
        if (iframe && iframe.contentWindow) {
          iframe.contentWindow.focus();
          iframe.contentWindow.print();
        } else {
          window.print();
        }
      } catch (e) {
        console.error(e);
        window.print();
      }
    });

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

    // ================= Other Charges JS (simple client-side demo) =================
    const oc_submit = document.getElementById('oc_submit');
    const oc_reset = document.getElementById('oc_reset');
    const oc_amount = document.getElementById('oc_amount');
    const oc_remark = document.getElementById('oc_remark');
    const oc_table_body = document.getElementById('oc_table_body');

    // helper to re-render table rows
    function addOtherCharge(amount, remark){
      // if first row says "No records yet", remove it
      if (oc_table_body.children.length === 1 && oc_table_body.children[0].children.length === 1) {
        oc_table_body.innerHTML = '';
      }
      const idx = oc_table_body.children.length + 1;
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="py-3 px-4 border-b">${idx}</td>
        <td class="py-3 px-4 border-b">${amount}</td>
        <td class="py-3 px-4 border-b">${remark}</td>
        <td class="py-3 px-4 border-b">
          <button class="px-3 py-1 text-sm rounded bg-red-100 text-red-600" onclick="this.closest('tr').remove(); reindexOC()">Delete</button>
        </td>
      `;
      oc_table_body.appendChild(tr);
    }

    // re-index after deletion
    window.reindexOC = function() {
      const rows = Array.from(oc_table_body.querySelectorAll('tr'));
      if (rows.length === 0) {
        oc_table_body.innerHTML = '<tr><td colspan="4" class="py-6 text-center text-gray-500">No records yet</td></tr>';
        return;
      }
      rows.forEach((r, i) => {
        const firstCell = r.querySelector('td');
        if (firstCell) firstCell.textContent = i + 1;
      });
    };

    oc_submit.addEventListener('click', function(e){
      e.preventDefault();
      const amt = oc_amount.value.trim();
      const rem = oc_remark.value.trim();
      if (!amt || isNaN(amt)) {
        alert('Please enter a valid amount');
        return;
      }
      addOtherCharge(parseFloat(amt).toFixed(2), rem || '-');
      oc_amount.value = ''; oc_remark.value = '';
    }); 

    oc_reset.addEventListener('click', function(e){
      e.preventDefault();  
      oc_amount.value = ''; oc_remark.value = '';
    });

});
</script>
 <script src="js/mystyle.js"></script>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>


</body>
</html>