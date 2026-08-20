<?php
// Start the session & check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// --- Database queries hidden / replaced with placeholder values ---
$today = 0;
$week = 0;
$month = 0;
$total = 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khalilabad Property Tax Dashboard | Admin Dashboard</title>
    <link href="img/favicon.ico" rel="icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href='css/mystyle.css' rel='stylesheet'>
</head>

<body class="flex min-h-screen">

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'surveyor'): ?>
        <?php include 'sidemenu.php' ?>
    <?php endif; ?>

    <div id="sidebar-backdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>
    <main class="flex-1 p-4 md:p-8 space-y-8 overflow-y-auto">

        <?php include 'header.php' ?>

        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-base text-gray-500">View and manage the Khalilabad Property Tax Assessments.</p>
            </div>
            <div class="flex space-x-3">
                <button class="flex items-center px-4 py-2 bg-white text-gray-700 border border-gray-300 font-medium rounded-lg shadow-sm hover:bg-gray-50 transition hidden md:flex text-base">
                    <i class='bx bx-download text-xl mr-1'></i> Export Data
                </button>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 lg:gap-6">

            <div class="donezo-card p-4 lg:p-6 flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-medium text-gray-700">Today's Survey</h3>
                        <p class="text-3xl lg:text-4xl font-extrabold text-gray-900 mt-1"><?php echo $today; ?></p>
                    </div>
                    <div class="w-9 h-9 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600">
                        <i class='bx bx-right-arrow-alt text-2xl'></i>
                    </div>
                </div>
                <p class="text-sm font-medium text-yellow-600 mt-2">
                    On target today
                </p>
            </div>

            <div class="donezo-card p-4 lg:p-6 flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-medium text-gray-700">This Week</h3>
                        <p class="text-3xl lg:text-4xl font-extrabold text-gray-900 mt-1"><?php echo $week; ?></p>
                    </div>
                    <div class="w-9 h-9 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                        <i class='bx bx-up-arrow-alt text-2xl'></i>
                    </div>
                </div>
                <p class="text-sm font-medium text-emerald-600 mt-2">
                    <i class='bx bx-trending-up text-lg mr-1'></i> Increased from last week
                </p>
            </div>

            <div class="donezo-card p-4 lg:p-6 flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-medium text-gray-700">This Month</h3>
                        <p class="text-3xl lg:text-4xl font-extrabold text-gray-900 mt-1"><?php echo $month; ?></p>
                    </div>
                    <div class="w-9 h-9 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                        <i class='bx bx-down-arrow-alt text-2xl'></i>
                    </div>
                </div>
                <p class="text-sm font-medium text-red-600 mt-2">
                    Decreased from last month
                </p>
            </div>

            <div class="donezo-card p-4 lg:p-6 flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-medium text-gray-700">Total Survey</h3>
                        <p class="text-3xl lg:text-4xl font-extrabold text-gray-900 mt-1"><?php echo $total; ?></p>
                    </div>
                    <div class="w-9 h-9 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                        <i class='bx bx-up-arrow-alt text-2xl'></i>
                    </div>
                </div>
                <p class="text-sm font-medium text-emerald-600 mt-2">
                    <i class='bx bx-trending-up text-lg mr-1'></i> Total till date
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="lg:col-span-2 donezo-card p-6 min-h-[350px] relative">
                <h2 class="text-xl font-bold mb-4">Survey Progress Analytics</h2>
                <div class="under-dev-overlay">
                    <div class="text-center">
                        <i class='bx bx-bar-chart-alt-2 text-7xl text-gray-400 mb-3'></i>
                        <h3 class="text-2xl font-bold text-gray-800">Analytics Under Development</h3>
                        <p class="mt-1 text-base text-gray-600">Feature integration coming soon!</p>
                    </div>
                </div>
                <div class="h-64 bg-gray-100 rounded opacity-10"></div>
            </div>

            <div class="lg:col-span-1 donezo-card p-6 relative">
                <h2 class="text-xl font-bold mb-4">Reminders / Quick Actions</h2>
                <div class="under-dev-overlay">
                    <div class="text-center">
                        <i class='bx bx-cogs text-7xl text-gray-400 mb-3'></i>
                        <h3 class="text-2xl font-bold text-gray-800">Under Development</h3>
                        <p class="mt-1 text-base text-gray-600">This feature is coming soon!</p>
                    </div>
                </div>
                <div class="space-y-4 opacity-10">
                    <div class="p-3 bg-gray-50 rounded-lg border-l-4 border-emerald-500">...</div>
                    <div class="p-3 bg-gray-50 rounded-lg border-l-4 border-blue-500">...</div>
                    <div class="p-3 bg-gray-50 rounded-lg border-l-4 border-red-500">...</div>
                </div>
            </div>

            <div class="lg:col-span-2 donezo-card p-6 relative">
                <h2 class="text-xl font-bold mb-4 flex justify-between items-center">
                    Recent Survey Payments
                </h2>
                <div class="under-dev-overlay">
                    <div class="text-center">
                        <i class='bx bx-credit-card-front text-7xl text-gray-400 mb-3'></i>
                        <h3 class="text-2xl font-bold text-gray-800">Under Development</h3>
                        <p class="mt-1 text-base text-gray-600">Payment integration coming soon!</p>
                    </div>
                </div>
                <div class="space-y-4 opacity-10">
                    <div class="flex items-center justify-between p-2 border-b">...</div>
                    <div class="flex items-center justify-between p-2 border-b">...</div>
                    <div class="flex items-center justify-between p-2 border-b">...</div>
                </div>
            </div>

            <div class="lg:col-span-1 donezo-card p-6 min-h-[350px] flex flex-col justify-between">
                <div>
                    <h2 class="text-xl font-bold mb-4">Annual Report Progress</h2>
                    <div class="flex flex-col items-center">
                        <div class="relative w-40 h-40">
                            <svg class="w-full h-full" viewBox="0 0 100 100">
                                <circle class="text-gray-200 stroke-current" stroke-width="10" cx="50" cy="50" r="45" fill="transparent"></circle>
                                <circle class="text-emerald-500 stroke-current" stroke-width="10" stroke-linecap="round" cx="50" cy="50" r="45" fill="transparent"
                                    stroke-dasharray="282.7" stroke-dashoffset="183.7" transform="rotate(-90 50 50)"></circle>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-4xl font-bold text-gray-800">35%</span>
                            </div>
                        </div>
                        <p class="mt-3 text-base text-gray-500">Report 35% Completed</p>
                    </div>
                </div>
                <button class="mt-4 w-full px-4 py-2 text-base font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition">
                    Download Latest Draft
                </button>
            </div>
        </div>

        <footer class="text-center text-gray-500 text-base mt-12">
            © 2025 Khalilabad Nagar Parishad - All Rights Reserved.
        </footer>
    </main>

    <script>
        // 1. Profile Dropdown Logic
        const profileBtn = document.getElementById("profileBtn");
        const profileMenu = document.getElementById("profileMenu");

        if (profileBtn && profileMenu) {
            profileBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                profileMenu.classList.toggle("hidden");
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