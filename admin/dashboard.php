<?php
// Start the session & check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

include 'db.php';

// --- Database queries hidden / replaced with placeholder values ---
$today = 12;
$week = 84;
$month = 340;
$total = 1250;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khalilabad Property Tax | Admin Dashboard</title>
    <link href="img/favicon.ico" rel="icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href='css/mystyle.css' rel='stylesheet'>
</head>

<body class="flex min-h-screen bg-slate-50 font-sans antialiased text-slate-800">

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'surveyor'): ?>
        <?php include 'sidemenu.php' ?>
    <?php endif; ?>

    <div id="sidebar-backdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 hidden lg:hidden transition-opacity"></div>

    <main class="flex-1 flex flex-col h-screen overflow-hidden">

        <!-- Header Included -->
        <div class="flex-shrink-0 z-10">
            <?php include 'header.php' ?>
        </div>

        <!-- Scrollable Main Content -->
        <div class="flex-1 overflow-y-auto p-4 md:p-8 lg:px-10 pb-20 scroll-smooth custom-scrollbar">

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-4">
                <div>
                    <p class="text-sm font-semibold tracking-wider text-emerald-600 uppercase mb-1">Overview</p>
                    <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight">Dashboard</h1>
                    <p class="text-base text-slate-500 mt-1">Monitor property tax assessments and surveyor activities.</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button class="flex items-center gap-2 px-5 py-2.5 bg-white text-slate-700 border border-slate-200 font-semibold rounded-xl shadow-sm hover:shadow-md hover:border-slate-300 hover:text-emerald-600 transition-all duration-300 hidden md:flex">
                        <i class='bx bx-cloud-download text-xl'></i> Export Report
                    </button>
                </div>
            </div>

            <!-- Stat Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

                <!-- Card 1 -->
                <div class="donezo-card relative p-6 bg-white rounded-2xl overflow-hidden group">
                    <div class="absolute -right-6 -top-6 text-yellow-50 opacity-50 group-hover:scale-110 transition-transform duration-500">
                        <i class='bx bx-target-lock text-9xl'></i>
                    </div>
                    <div class="relative z-10 flex justify-between items-start">
                        <div>
                            <p class="text-sm font-semibold text-slate-500">Today's Survey</p>
                            <h3 class="text-4xl font-extrabold text-slate-900 mt-2"><?php echo $today; ?></h3>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-yellow-100 flex items-center justify-center text-yellow-600 shadow-inner">
                            <i class='bx bx-sun text-2xl'></i>
                        </div>
                    </div>
                    <div class="relative z-10 mt-4 flex items-center text-sm font-medium text-yellow-600 bg-yellow-50 w-max px-3 py-1 rounded-full">
                        <i class='bx bx-check-circle mr-1 text-lg'></i> On target today
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="donezo-card relative p-6 bg-white rounded-2xl overflow-hidden group">
                    <div class="absolute -right-6 -top-6 text-emerald-50 opacity-50 group-hover:scale-110 transition-transform duration-500">
                        <i class='bx bx-line-chart text-9xl'></i>
                    </div>
                    <div class="relative z-10 flex justify-between items-start">
                        <div>
                            <p class="text-sm font-semibold text-slate-500">This Week</p>
                            <h3 class="text-4xl font-extrabold text-slate-900 mt-2"><?php echo $week; ?></h3>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-100 flex items-center justify-center text-emerald-600 shadow-inner">
                            <i class='bx bx-calendar-week text-2xl'></i>
                        </div>
                    </div>
                    <div class="relative z-10 mt-4 flex items-center text-sm font-medium text-emerald-600 bg-emerald-50 w-max px-3 py-1 rounded-full">
                        <i class='bx bx-trending-up mr-1 text-lg'></i> +12% from last week
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="donezo-card relative p-6 bg-white rounded-2xl overflow-hidden group">
                    <div class="absolute -right-6 -top-6 text-red-50 opacity-50 group-hover:scale-110 transition-transform duration-500">
                        <i class='bx bx-trending-down text-9xl'></i>
                    </div>
                    <div class="relative z-10 flex justify-between items-start">
                        <div>
                            <p class="text-sm font-semibold text-slate-500">This Month</p>
                            <h3 class="text-4xl font-extrabold text-slate-900 mt-2"><?php echo $month; ?></h3>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-red-100 flex items-center justify-center text-red-600 shadow-inner">
                            <i class='bx bx-calendar text-2xl'></i>
                        </div>
                    </div>
                    <div class="relative z-10 mt-4 flex items-center text-sm font-medium text-red-600 bg-red-50 w-max px-3 py-1 rounded-full">
                        <i class='bx bx-trending-down mr-1 text-lg'></i> -3% from last month
                    </div>
                </div>

                <!-- Card 4 -->
                <div class="donezo-card relative p-6 bg-white rounded-2xl overflow-hidden group">
                    <div class="absolute -right-6 -top-6 text-emerald-50 opacity-50 group-hover:scale-110 transition-transform duration-500">
                        <i class='bx bx-pie-chart-alt-2 text-9xl'></i>
                    </div>
                    <div class="relative z-10 flex justify-between items-start">
                        <div>
                            <p class="text-sm font-semibold text-slate-500">Total Surveys</p>
                            <h3 class="text-4xl font-extrabold text-slate-900 mt-2"><?php echo $total; ?></h3>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-600 flex items-center justify-center text-white shadow-lg shadow-emerald-600/30">
                            <i class='bx bx-layer text-2xl'></i>
                        </div>
                    </div>
                    <div class="relative z-10 mt-4 flex items-center text-sm font-medium text-emerald-600 bg-emerald-50 w-max px-3 py-1 rounded-full">
                        <i class='bx bx-data mr-1 text-lg'></i> Total records till date
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

                <!-- Chart Section -->
                <div class="lg:col-span-2 donezo-card p-6 bg-white rounded-2xl relative min-h-[400px]">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-bold text-slate-800">Survey Progress Analytics</h2>
                        <button class="text-slate-400 hover:text-emerald-600 transition"><i class='bx bx-dots-horizontal-rounded text-2xl'></i></button>
                    </div>

                    <!-- Blur Overlay for Development -->
                    <div class="absolute inset-0 bg-white/60 backdrop-blur-sm flex items-center justify-center z-10 rounded-2xl border border-white/50">
                        <div class="text-center p-6 bg-white rounded-2xl shadow-xl shadow-slate-200/50 max-w-sm">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class='bx bx-bar-chart-alt-2 text-4xl text-emerald-500'></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800">Analytics Coming Soon</h3>
                            <p class="mt-2 text-sm text-slate-500 leading-relaxed">We are currently integrating live data visualization for better insights.</p>
                        </div>
                    </div>

                    <!-- Mock Chart Elements -->
                    <div class="h-64 flex items-end justify-between gap-2 opacity-20 px-2 mt-8">
                        <div class="w-full bg-slate-200 rounded-t-md h-1/4"></div>
                        <div class="w-full bg-slate-200 rounded-t-md h-2/4"></div>
                        <div class="w-full bg-emerald-500 rounded-t-md h-3/4"></div>
                        <div class="w-full bg-slate-200 rounded-t-md h-1/3"></div>
                        <div class="w-full bg-emerald-500 rounded-t-md h-full"></div>
                        <div class="w-full bg-slate-200 rounded-t-md h-2/3"></div>
                        <div class="w-full bg-emerald-500 rounded-t-md h-4/5"></div>
                    </div>
                </div>

                <!-- Quick Actions & Reminders -->
                <div class="lg:col-span-1 donezo-card p-6 bg-white rounded-2xl relative min-h-[400px]">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-bold text-slate-800">Quick Reminders</h2>
                        <span class="bg-red-100 text-red-600 text-xs font-bold px-2 py-1 rounded-full">3 New</span>
                    </div>

                    <!-- Blur Overlay for Development -->
                    <div class="absolute inset-0 bg-white/60 backdrop-blur-sm flex items-center justify-center z-10 rounded-2xl">
                        <div class="text-center">
                            <i class='bx bx-time-five text-5xl text-slate-300 mb-2'></i>
                            <h3 class="text-lg font-bold text-slate-700">Under Development</h3>
                        </div>
                    </div>

                    <!-- Mock List Elements -->
                    <div class="space-y-4 opacity-30">
                        <div class="flex gap-4 items-start p-3 hover:bg-slate-50 rounded-xl transition cursor-pointer border border-slate-100">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 flex-shrink-0">
                                <i class='bx bx-file'></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-slate-800">Verify Ward 4 Data</h4>
                                <p class="text-xs text-slate-500 mt-1">Needs approval from head surveyor.</p>
                            </div>
                        </div>
                        <div class="flex gap-4 items-start p-3 hover:bg-slate-50 rounded-xl transition cursor-pointer border border-slate-100">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 flex-shrink-0">
                                <i class='bx bx-user-check'></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-slate-800">Assign New Areas</h4>
                                <p class="text-xs text-slate-500 mt-1">2 new surveyors added today.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Recent Survey Payments (Table) -->
                <div class="lg:col-span-2 donezo-card p-6 bg-white rounded-2xl relative">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-bold text-slate-800">Recent Assessments</h2>
                        <a href="#" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">View All</a>
                    </div>

                    <!-- Blur Overlay -->
                    <div class="absolute inset-0 bg-white/70 backdrop-blur-sm flex items-center justify-center z-10 rounded-2xl">
                        <div class="text-center bg-white p-4 rounded-xl shadow-lg border border-slate-100">
                            <i class='bx bx-credit-card-front text-4xl text-slate-400 mb-2'></i>
                            <h3 class="text-base font-bold text-slate-800">Payment Module Pending</h3>
                        </div>
                    </div>

                    <!-- Mock Table -->
                    <div class="overflow-x-auto opacity-30">
                        <table class="w-full text-left text-sm text-slate-500">
                            <thead class="text-xs text-slate-400 uppercase bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 rounded-l-lg">Property ID</th>
                                    <th class="px-4 py-3">Owner Name</th>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3 rounded-r-lg">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b border-slate-100">
                                    <td class="px-4 py-3 font-medium text-slate-900">#KHL-8942</td>
                                    <td class="px-4 py-3">Ramesh Kumar</td>
                                    <td class="px-4 py-3">Today, 10:23 AM</td>
                                    <td class="px-4 py-3"><span class="bg-emerald-100 text-emerald-700 px-2 py-1 rounded-md text-xs font-bold">Assessed</span></td>
                                </tr>
                                <tr class="border-b border-slate-100">
                                    <td class="px-4 py-3 font-medium text-slate-900">#KHL-8941</td>
                                    <td class="px-4 py-3">Suresh Singh</td>
                                    <td class="px-4 py-3">Yesterday</td>
                                    <td class="px-4 py-3"><span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded-md text-xs font-bold">Pending</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Circular Progress -->
                <div class="lg:col-span-1 donezo-card p-6 bg-white rounded-2xl flex flex-col justify-between items-center text-center">
                    <div class="w-full">
                        <h2 class="text-lg font-bold text-slate-800 mb-2">Annual Report</h2>
                        <p class="text-sm text-slate-500 mb-6">Overall municipality completion</p>

                        <div class="relative w-44 h-44 mx-auto mb-4">
                            <!-- Background Circle -->
                            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="42" fill="none" stroke="#f1f5f9" stroke-width="12"></circle>
                                <!-- Progress Circle -->
                                <circle cx="50" cy="50" r="42" fill="none" stroke="currentColor" stroke-width="12" stroke-linecap="round"
                                    class="text-emerald-500"
                                    stroke-dasharray="263.89"
                                    stroke-dashoffset="171.53"></circle> <!-- Adjust offset for percentage -->
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-4xl font-extrabold text-slate-800">35<span class="text-xl text-slate-400">%</span></span>
                            </div>
                        </div>
                    </div>

                    <button class="w-full mt-4 flex items-center justify-center gap-2 px-4 py-3 text-sm font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-lg shadow-emerald-600/30 hover:shadow-emerald-600/40 transition-all duration-300">
                        <i class='bx bx-cloud-download text-lg'></i> Download Draft
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <footer class="mt-8 pt-6 border-t border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-slate-500">
                <p>© 2026 Khalilabad Nagar Parishad. All Rights Reserved.</p>
                <p>Designed with <i class='bx bxs-heart text-red-500 mx-1'></i> for smart governance.</p>
            </footer>

        </div>
    </main>

    <script>
        // 1. Profile Dropdown Logic
        const profileBtn = document.getElementById("profileBtn");
        const profileMenu = document.getElementById("profileMenu");

        if (profileBtn && profileMenu) {
            profileBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                profileMenu.classList.toggle("hidden");
                // Add slight fade in animation
                if (!profileMenu.classList.contains("hidden")) {
                    profileMenu.classList.add("opacity-100", "scale-100");
                    profileMenu.classList.remove("opacity-0", "scale-95");
                }
            });

            window.addEventListener("click", (e) => {
                if (!profileBtn.contains(e.target) && !profileMenu.contains(e.target)) {
                    profileMenu.classList.add("hidden");
                }
            });
        }

        // 2. Mobile Sidebar Dropdown/Toggle Logic
        const menuToggle = document.getElementById("menu-toggle");
        const sidebar = document.getElementById("sidebar");
        const backdrop = document.getElementById("sidebar-backdrop");
        const dropdownButtons = document.querySelectorAll('.sidebar-link[data-dropdown]');

        function toggleSidebar() {
            if (sidebar) sidebar.classList.toggle("open");
            if (backdrop) backdrop.classList.toggle("hidden");
        }

        if (menuToggle) menuToggle.addEventListener("click", toggleSidebar);
        if (backdrop) backdrop.addEventListener("click", toggleSidebar);

        // 3. Submenu Dropdown Logic
        dropdownButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const submenuId = button.getAttribute('data-dropdown');
                const submenu = document.getElementById(submenuId);
                const icon = button.querySelector('.bx-chevron-right');

                // Close all other submenus
                document.querySelectorAll('.sidebar-submenu').forEach(otherSubmenu => {
                    if (otherSubmenu !== submenu) {
                        otherSubmenu.classList.remove('open');
                        const otherIcon = otherSubmenu.previousElementSibling?.querySelector('.bx-chevron-right');
                        if (otherIcon) otherIcon.classList.remove('rotate-90');
                    }
                });

                // Toggle current submenu
                if (submenu) submenu.classList.toggle('open');
                if (icon) icon.classList.toggle('rotate-90');
            });
        });
    </script>
</body>

</html>