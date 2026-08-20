<html lang="en">

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Surveyor - Deoria Property Tax</title>
    <link href="favicon.png" rel="icon">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href='css/mystyle.css' rel='stylesheet'>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #e0f2fe, #f8fafc);
        }

        .glass {
            background: rgba(255, 255, 255, 0.25);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.05);
        }

        .hover-glass:hover {
            transform: translateY(-4px);
            transition: all .3s ease;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .1)
        }

        .req:after {
            content: " *";
            color: #ef4444;
            font-weight: bold
        }

        .file-name {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            color: #1f2937;
        }

        .file-name .icon {
            font-size: 1rem;
        }
    </style>
</head>

<body class="flex min-h-screen text-gray-800">
    <?php include 'sidemenu.php'; ?>

    <main class="flex-1 p-4 md:p-12 space-y-8 overflow-y-auto">



        <div class="max-w-7xl mx-auto">

            <div class="mb-12 text-center md:text-left">
                <h1 class="text-3xl md:text-4xl font-extrabold text-slate-800 tracking-tight">
                    Bulk Property <span class="text-emerald-600">Upload Engine</span>
                </h1>
                <p class="text-slate-500 mt-2 text-sm md:text-base">Quickly sync your external property records with the central database.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-16">

                <div class="glass-card p-6 md:p-10 flex flex-col justify-between">
                    <div>
                        <div class="w-20 h-20 bg-emerald-50 text-emerald-600 rounded-3xl flex items-center justify-center mb-8 shadow-sm">
                            <i class="fa fa-file-excel-o text-4xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-800 mb-4">1. Get Processing Template</h3>
                        <p class="text-slate-500 text-sm leading-relaxed mb-8">
                            Download the official Excel template. Use this file to organize your property data to ensure the system reads your information correctly.
                        </p>

                        <div class="bg-emerald-600 p-5 rounded-2xl shadow-lg shadow-emerald-100 flex items-start gap-4">
                            <div class="bg-white/20 p-2 rounded-lg">
                                <i class="bx bxs-bell-ring text-white text-xl"></i>
                            </div>
                            <div class="text-white">
                                <p class="text-[10px] font-black uppercase tracking-widest opacity-80 mb-1">Important Notice</p>
                                <p class="text-xs font-medium leading-relaxed">
                                    Do not modify or delete the top header row in the file. Changing column names will cause the upload to fail.
                                </p>
                            </div>
                        </div>
                    </div>

                    <a href="download_arv_excel_template.php" class="mt-8 w-full py-5 px-6 rounded-2xl border-2 border-emerald-100 text-emerald-700 font-bold hover:bg-emerald-50 transition text-center flex items-center justify-center gap-3">
                        <i class="bx bxs-cloud-download text-2xl"></i> Download Excel Template
                    </a>
                </div>

                <div class="glass-card p-6 md:p-10">
                    <div class="flex items-center justify-between mb-10">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-slate-900 text-white rounded-xl flex items-center justify-center mr-4">
                                <i class="bx bx-upload text-2xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-slate-800">2. Upload &amp; Sync</h3>
                        </div>
                        <span class="text-[10px] bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full font-bold uppercase tracking-wider">CSV Only</span>
                    </div>

                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="relative mb-8">
                            <label id="dropzone" class="upload-dropzone relative block w-full py-16 px-6 rounded-[32px] text-center cursor-pointer overflow-hidden">
                                <div id="uploadContent">
                                    <i class="bx bx-file-blank text-5xl text-slate-300 mb-4 transition-transform group-hover:scale-110"></i>
                                    <p class="text-sm font-bold text-slate-600">Drag or Select CSV File</p>
                                    <p class="text-[10px] text-slate-400 mt-1">UTF-8 encoded files work best</p>
                                </div>
                                <div id="fileSelectedArea" class="hidden animate-in fade-in zoom-in duration-300">
                                    <div class="inline-flex items-center justify-center bg-emerald-100 text-emerald-700 p-4 rounded-2xl">
                                        <i class="bx bxs-check-circle text-2xl mr-3"></i>
                                        <div class="text-left">
                                            <p class="text-[10px] font-black uppercase tracking-widest">Ready to upload</p>
                                            <p id="fileNameDisplay" class="text-sm font-bold truncate max-w-[200px]"></p>
                                        </div>
                                    </div>
                                    <p class="text-[10px] text-emerald-600 mt-4 font-bold underline cursor-pointer">Change file</p>
                                </div>
                                <input type="file" name="file" id="file" accept=".csv" required="" class="hidden" onchange="displayFileName(this)">
                            </label>
                        </div>

                        <div id="progressArea" class="hidden mb-8 p-6 bg-slate-50/50 rounded-2xl border border-slate-100">
                            <div class="flex justify-between items-center mb-3">
                                <span id="progressStatus" class="text-[10px] font-black text-slate-500 uppercase">Engine Syncing...</span>
                                <span id="progressPercent" class="text-xs font-bold text-emerald-600">0%</span>
                            </div>
                            <div class="w-full bg-white rounded-full h-1.5 overflow-hidden">
                                <div id="progressBar" class="bg-emerald-500 h-full transition-all duration-500" style="width: 0%"></div>
                            </div>
                            <div id="recordCount" class="text-[10px] text-slate-400 mt-4 text-center italic">Establishing database connection...</div>
                        </div>

                        <button type="submit" id="processBtn" class="btn-modern w-full py-5 rounded-2xl font-bold text-sm tracking-widest uppercase flex items-center justify-center gap-3">
                            Start Data Sync <i class="bx bx-right-arrow-alt text-xl"></i>
                        </button>
                    </form>
                </div>
            </div>

            <div class="glass-card p-6 md:p-12 border-none shadow-none relative overflow-hidden bg-white/30">
                <div class="absolute top-0 right-0 w-64 h-64 bg-emerald-100 rounded-full blur-3xl -mr-32 -mt-32 opacity-20"></div>

                <h4 class="font-black text-slate-800 mb-10 flex items-center gap-3 text-lg uppercase tracking-tight relative z-10">
                    <span class="w-8 h-8 bg-emerald-600 text-white rounded-lg flex items-center justify-center"><i class="bx bx-list-check"></i></span>
                    Pre-Upload Checklist
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 relative z-10">
                    <div class="footer-rule-card">
                        <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mb-4"><i class="bx bx-extension"></i></div>
                        <h5 class="text-sm font-bold text-slate-800 mb-2 uppercase tracking-wide">CSV Extension</h5>
                        <p class="text-xs text-slate-500 leading-relaxed">Save your Excel as <b>CSV (Comma Delimited)</b>. The system doesn't read standard .xlsx files.</p>
                    </div>
                    <div class="footer-rule-card">
                        <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center mb-4"><i class="bx bx-mobile-alt"></i></div>
                        <h5 class="text-sm font-bold text-slate-800 mb-2 uppercase tracking-wide">Phone Validation</h5>
                        <p class="text-xs text-slate-500 leading-relaxed">Owners must have a <b>unique 10-digit mobile</b>. Same mobile for two owners will cause rejection.</p>
                    </div>
                    <div class="footer-rule-card">
                        <div class="w-10 h-10 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center mb-4"><i class="bx bx-map-pin"></i></div>
                        <h5 class="text-sm font-bold text-slate-800 mb-2 uppercase tracking-wide">Ward Mapping</h5>
                        <p class="text-xs text-slate-500 leading-relaxed">Ensure the <b>Ward Number</b> in your file already exists in the system database settings.</p>
                    </div>
                    <div class="footer-rule-card">
                        <div class="w-10 h-10 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center mb-4"><i class="bx bx-calendar"></i></div>
                        <h5 class="text-sm font-bold text-slate-800 mb-2 uppercase tracking-wide">Date Logic</h5>
                        <p class="text-xs text-slate-500 leading-relaxed">Standardize all dates to <b>YYYY-MM-DD</b>. (Example: 2024-03-21) to prevent calendar errors.</p>
                    </div>
                    <div class="footer-rule-card">
                        <div class="w-10 h-10 bg-red-50 text-red-600 rounded-xl flex items-center justify-center mb-4"><i class="bx bx-toggle-right"></i></div>
                        <h5 class="text-sm font-bold text-slate-800 mb-2 uppercase tracking-wide">Status Keys</h5>
                        <p class="text-xs text-slate-500 leading-relaxed">The 'Status' column only accepts <b>'New'</b> or <b>'Old'</b>. Other text will stop the import.</p>
                    </div>
                    <div class="footer-rule-card">
                        <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center mb-4"><i class="bx bx-no-entry"></i></div>
                        <h5 class="text-sm font-bold text-slate-800 mb-2 uppercase tracking-wide">No Symbols</h5>
                        <p class="text-xs text-slate-500 leading-relaxed">Remove currency symbols like <b>₹, $ or commas</b>. Use plain numbers for all tax/money fields.</p>
                    </div>
                </div>
            </div>
        </div>

        <footer class="text-center">
            <p class="text-xs font-medium text-slate-400/80">
                © 2025 Deoria Nagar Parishad <span class="mx-2">•</span> <span class="text-slate-500">Property Tax System v1.0</span>
            </p>
        </footer>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/mystyle.js"></script>

    <script>
        function displayFileName(input) {
            const area = document.getElementById('fileSelectedArea');
            const content = document.getElementById('uploadContent');
            const nameText = document.getElementById('fileNameDisplay');
            const dropzone = document.getElementById('dropzone');

            if (input.files && input.files[0]) {
                content.classList.add('hidden');
                area.classList.remove('hidden');
                dropzone.classList.add('has-file');
                nameText.textContent = input.files[0].name;
            }
        }

        $('#uploadForm').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            formData.append('import', 'true');

            $('#processBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin mr-2"></i> Syncing Data...');
            $('#progressArea').removeClass('hidden');
            $('#progressBar').css('width', '50%');

            $.ajax({
                url: 'import_bulk.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    let res = (typeof response === 'object') ? response : JSON.parse(response);
                    if (res.status === 'success') {
                        $('#progressBar').css('width', '100%');
                        alert("Success! Synchronization complete.");
                        location.reload();
                    } else {
                        $('#progressBar').removeClass('bg-emerald-500').addClass('bg-red-500').css('width', '100%');
                        alert("Alert! Data errors found.");
                        let errorHtml = `
                    <div class="mt-6 p-6 bg-red-50 border border-red-100 rounded-3xl">
                        <p class="text-red-700 font-bold text-xs uppercase mb-3">Fix these issues:</p>
                        <ul class="text-[11px] text-red-600 space-y-2 list-disc ml-5">
                            ${res.details.slice(0, 5).map(err => `<li>${err}</li>`).join('')}
                        </ul>
                        <div class="mt-6 flex gap-3">
                            <a href="${res.log_file}" target="_blank" class="flex-1 text-center py-4 bg-slate-800 text-white rounded-xl text-xs font-bold uppercase tracking-widest">Error Log</a>
                            <button type="button" onclick="location.reload()" class="flex-1 text-center py-4 bg-emerald-600 text-white rounded-xl text-xs font-bold uppercase tracking-widest">Retry Sync</button>
                        </div>
                    </div>`;
                        $('#recordCount').html(errorHtml);
                        $('#processBtn').addClass('hidden');
                    }
                },
                error: function() {
                    alert("Connection error.");
                    $('#processBtn').prop('disabled', false).html('Restart Engine');
                }
            });
        });
    </script>

    <script>
        // Profile Dropdown Toggle Logic
        document.addEventListener('DOMContentLoaded', function() {
            const profileBtn = document.getElementById('profileBtn');
            const profileMenu = document.getElementById('profileMenu');

            if (profileBtn && profileMenu) {
                profileBtn.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevents the click from bubbling up to the document
                    profileMenu.classList.toggle('hidden');
                });

                // Close the menu when clicking anywhere else on the page
                document.addEventListener('click', function(e) {
                    if (!profileBtn.contains(e.target)) {
                        profileMenu.classList.add('hidden');
                    }
                });
            }

            // Sidebar Menu Toggle (for mobile)
            const menuToggle = document.getElementById('menu-toggle');
            // If you have a sidebar with an ID, you can add logic here too
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    // Logic to open/close sidebar for mobile if needed
                    console.log("Mobile menu toggled");
                });
            }
        });
    </script>
    <script type="module" src="https://static.cloudflareinsights.com/beacon.min.js/v4513226cdae34746b4dedf0b4dfa099e1781791509496" integrity="sha512-ZE9pZaUXND66v380QUtch/5sE9tPFh2zg45pR2PB0CVkCtOREv2AJKkSidISWkysEuQ0EH8faUU5du78bx87UQ==" data-cf-beacon="{&quot;version&quot;:&quot;2024.11.0&quot;,&quot;token&quot;:&quot;d65bb4551cea423997566eff7278a290&quot;,&quot;r&quot;:1}" crossorigin="anonymous"></script>

</body>

</html>