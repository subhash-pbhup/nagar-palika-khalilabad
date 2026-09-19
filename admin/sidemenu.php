<?php
require_once "./include/permission.php";
?>

<style>
    /* Spacing & Alignment Fixes */
    .sidebar-link-glass {
        padding: 0.65rem 1rem;
        border-radius: 0.85rem;
        color: #64748b;
        transition: all 0.2s ease;
    }

    .sidebar-link-glass:hover {
        background: rgba(16, 185, 129, 0.08);
        color: #10b981;
    }

    .sidebar-link-glass.active {
        background: white;
        color: #10b981;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
    }

    .submenu-item {
        display: flex;
        align-items: center;
        font-size: 0.8rem;
        font-weight: 600;
        color: #64748b;
        padding: 0.5rem 0.75rem;
        border-radius: 0.6rem;
        transition: all 0.2s ease;
        margin-left: 0.75rem;
    }

    .submenu-item:hover {
        background: white;
        color: #10b981;
        transform: translateX(3px);
    }

    .rotate-90 {
        transform: rotate(90deg);
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 3px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.05);
        border-radius: 10px;
    }
</style>


<div id="sidebar-overlay"
    class="fixed inset-0 bg-slate-900/20 backdrop-blur-sm hidden z-40 transition-opacity">
</div>


<aside id="sidebar"
    class="fixed lg:sticky top-0 h-screen w-[280px] -translate-x-full lg:translate-x-0 transition-all duration-500 z-50 p-4">

    <div class="h-full w-full flex flex-col bg-white/75 backdrop-blur-xl border border-white/40 shadow-[0_8px_32px_0_rgba(31,38,135,0.05)] rounded-[2rem] overflow-hidden">


        <!-- =========================================================
             LOGO
        ========================================================== -->

        <div class="p-6 flex items-center justify-center border-b border-white/50">

            <a href="dashboard.php"
                class="transition-transform hover:scale-105 duration-300">

                <img src="img/admin-logo.png"
                    alt="Khalilabad Logo"
                    class="h-10 w-auto">

            </a>

        </div>


        <!-- =========================================================
             NAVIGATION
        ========================================================== -->

        <nav class="flex-1 overflow-y-auto py-4 px-4 space-y-4 custom-scrollbar">


            <!-- =====================================================
                 MAIN MENU
            ====================================================== -->

            <div>

                <h3 class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400 mb-2 px-4">
                    Main Menu
                </h3>


                <div class="space-y-1">


                    <!-- =================================================
                         DASHBOARD
                    ================================================== -->

                    <?php if (hasPermission('dashboard.view')): ?>

                        <a href="dashboard.php"
                            class="sidebar-link-glass active flex justify-between items-center group">

                            <span class="flex items-center">

                                <i class='bx bxs-dashboard text-xl mr-3'></i>

                                <span class="font-semibold text-sm">
                                    Dashboard
                                </span>

                            </span>

                            <div class="w-4"></div>

                        </a>

                    <?php endif; ?>


                    <!-- =================================================
                         ASSESSMENTS
                    ================================================== -->

                    <?php

                    $assessmentAccess =
                        hasPermission('assessments.view') ||
                        hasPermission('assessments.add') ||
                        hasPermission('assessments.edit') ||
                        hasPermission('assessments.delete') ||
                        hasPermission('assessments.export') ||
                        hasPermission('assessments.bulk_upload');

                    ?>

                    <?php if ($assessmentAccess): ?>

                        <div class="dropdown-container">


                            <button
                                class="sidebar-link-glass w-full flex justify-between items-center group"
                                data-dropdown="assessments">

                                <span class="flex items-center">

                                    <i class='bx bx-list-check text-xl mr-3'></i>

                                    <span class="font-semibold text-sm">
                                        Assessments
                                    </span>

                                </span>

                                <i class='bx bx-chevron-right text-base transition-transform arrow-icon'></i>

                            </button>


                            <div
                                class="sidebar-submenu-glass overflow-hidden transition-all duration-300 max-h-0"
                                id="assessments">

                                <div class="pl-4 pr-2 py-1 space-y-1 mt-1">


                                    <!-- New Entry -->

                                    <?php if (hasPermission('assessments.add')): ?>

                                        <a href="new-assesment.php"
                                            class="submenu-item group">

                                            <i class='bx bx-plus-circle mr-2 opacity-70 group-hover:opacity-100'></i>

                                            New Entry

                                        </a>

                                    <?php endif; ?>


                                    <!-- View All -->

                                    <?php if (hasPermission('assessments.view')): ?>

                                        <a href="view-assesment.php"
                                            class="submenu-item group">

                                            <i class='bx bx-show-alt mr-2 opacity-70 group-hover:opacity-100'></i>

                                            View All

                                        </a>

                                    <?php endif; ?>


                                    <!-- Property List -->

                                    <?php if (hasPermission('assessments.view')): ?>

                                        <a href="legecy-property.php"
                                            class="submenu-item group">

                                            <i class='bx bx-table mr-2 opacity-70 group-hover:opacity-100'></i>

                                            Property List

                                        </a>

                                    <?php endif; ?>


                                    <!-- Bulk Upload -->

                                    <?php if (hasPermission('assessments.bulk_upload')): ?>

                                        <a href="bulk-upload-arv.php"
                                            class="submenu-item group">

                                            <i class='bx bx-cloud-upload mr-2 opacity-70 group-hover:opacity-100'></i>

                                            Bulk Upload

                                        </a>

                                    <?php endif; ?>


                                </div>

                            </div>

                        </div>

                    <?php endif; ?>

                    <!-- =================================================
                         SURVEYOR MANAGEMENT
                    ================================================== -->

                    <?php

                    $safapplication =
                        hasPermission('safapplication.collection') ||
                        hasPermission('safapplication.demands');

                    ?>

                    <?php if ($safapplication): ?>

                        <div class="dropdown-container">


                            <button
                                class="sidebar-link-glass w-full flex justify-between items-center group"
                                data-dropdown="safapplication">

                                <span class="flex items-center">

                                    <i class='bx bx-user-check text-xl mr-3'></i>

                                    <span class="font-semibold text-sm">
                                        SAF Appliaction
                                    </span>

                                </span>

                                <i class='bx bx-chevron-right text-base transition-transform arrow-icon'></i>

                            </button>


                            <div
                                class="sidebar-submenu-glass overflow-hidden transition-all duration-300 max-h-0"
                                id="safapplication">

                                <div class="pl-4 pr-2 py-1 space-y-1 mt-1">


                                    <!-- Add safapplication -->

                                    <?php if (hasPermission('safapplication.collection')): ?>

                                        <a href="collections.php"
                                            class="submenu-item group">

                                            <i class='bx bx-user-plus mr-2 opacity-70 group-hover:opacity-100'></i>

                                            Collections

                                        </a>

                                    <?php endif; ?>

                                    <?php if (hasPermission('safapplication.demands')): ?>

                                        <a href="demands.php"
                                            class="submenu-item group">

                                            <i class='bx bx-user-plus mr-2 opacity-70 group-hover:opacity-100'></i>

                                            Demands

                                        </a>

                                    <?php endif; ?>



                                </div>

                            </div>

                        </div>

                    <?php endif; ?>


                    <!-- =================================================
                         SURVEYOR MANAGEMENT
                    ================================================== -->

                    <?php

                    $surveyorAccess =
                        hasPermission('surveyor.view') ||
                        hasPermission('surveyor.add') ||
                        hasPermission('surveyor.edit') ||
                        hasPermission('surveyor.delete');

                    ?>

                    <?php if ($surveyorAccess): ?>

                        <div class="dropdown-container">


                            <button
                                class="sidebar-link-glass w-full flex justify-between items-center group"
                                data-dropdown="surveyor">

                                <span class="flex items-center">

                                    <i class='bx bx-user-check text-xl mr-3'></i>

                                    <span class="font-semibold text-sm">
                                        Surveyor Mgmt
                                    </span>

                                </span>

                                <i class='bx bx-chevron-right text-base transition-transform arrow-icon'></i>

                            </button>


                            <div
                                class="sidebar-submenu-glass overflow-hidden transition-all duration-300 max-h-0"
                                id="surveyor">

                                <div class="pl-4 pr-2 py-1 space-y-1 mt-1">


                                    <!-- Add Surveyor -->

                                    <?php if (hasPermission('surveyor.add')): ?>

                                        <a href="add-surveyor.php"
                                            class="submenu-item group">

                                            <i class='bx bx-user-plus mr-2 opacity-70 group-hover:opacity-100'></i>

                                            Add New

                                        </a>

                                    <?php endif; ?>


                                    <!-- View Surveyors -->

                                    <?php if (hasPermission('surveyor.view')): ?>

                                        <a href="view-surveyor.php"
                                            class="submenu-item group">

                                            <i class='bx bx-group mr-2 opacity-70 group-hover:opacity-100'></i>

                                            All Surveyors

                                        </a>

                                    <?php endif; ?>


                                </div>

                            </div>

                        </div>

                    <?php endif; ?>


                    <!-- =================================================
                         PAYMENTS
                    ================================================== -->

                    <?php if (hasPermission('payments.view')): ?>

                        <a href="payments.php"
                            class="sidebar-link-glass flex justify-between items-center group">

                            <span class="flex items-center">

                                <i class='bx bx-calendar text-xl mr-3'></i>

                                <span class="font-semibold text-sm">
                                    Payments
                                </span>

                            </span>

                            <div class="w-4"></div>

                        </a>

                    <?php endif; ?>


                    <!-- =================================================
                         ANALYTICS
                    ================================================== -->

                    <?php if (hasPermission('analytics.view')): ?>

                        <a href="analytics.php"
                            class="sidebar-link-glass flex justify-between items-center group">

                            <span class="flex items-center">

                                <i class='bx bx-stats text-xl mr-3'></i>

                                <span class="font-semibold text-sm">
                                    Analytics
                                </span>

                            </span>

                            <div class="w-4"></div>

                        </a>

                    <?php endif; ?>


                </div>

            </div>


            <!-- =====================================================
                 MANAGEMENT
            ====================================================== -->

            <div>


                <h3 class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400 mb-2 px-4">
                    Management
                </h3>


                <div class="space-y-1">


                    <!-- =================================================
                         USERS
                    ================================================== -->

                    <?php if (hasPermission('users.view')): ?>

                        <a href="users.php"
                            class="sidebar-link-glass flex justify-between items-center group">

                            <span class="flex items-center">

                                <i class='bx bx-group text-xl mr-3'></i>

                                <span class="font-semibold text-sm">
                                    Users
                                </span>

                            </span>

                            <div class="w-4"></div>

                        </a>

                        <!-- <a href="assessment_history.php"
                            class="sidebar-link-glass flex justify-between items-center group">

                            <span class="flex items-center">

                                <i class='bx bx-group text-xl mr-3'></i>

                                <span class="font-semibold text-sm">
                                    Assessment Update History
                                </span>

                            </span>

                            <div class="w-4"></div>

                        </a> -->

                    <?php endif; ?>


                    <!-- =================================================
                         ROLES & PERMISSIONS
                    ================================================== -->

                    <?php

                    $rolesAccess =
                        hasPermission('roles.view') ||
                        hasPermission('roles.manage_permissions');

                    ?>

                    <?php if ($rolesAccess): ?>

                        <a href="roles-permissions.php"
                            class="sidebar-link-glass flex justify-between items-center group">

                            <span class="flex items-center">

                                <i class='bx bx-shield-quarter text-xl mr-3'></i>

                                <span class="font-semibold text-sm">
                                    Roles Permission
                                </span>

                            </span>

                            <div class="w-4"></div>

                        </a>

                    <?php endif; ?>


                    <!-- =================================================
                         SETTINGS
                    ================================================== -->

                    <?php if (hasPermission('settings.view')): ?>

                        <a href="settings.php"
                            class="sidebar-link-glass flex justify-between items-center group">

                            <span class="flex items-center">

                                <i class='bx bx-cog text-xl mr-3'></i>

                                <span class="font-semibold text-sm">
                                    Settings
                                </span>

                            </span>

                            <div class="w-4"></div>

                        </a>

                    <?php endif; ?>


                    <!-- =================================================
                         LOGOUT
                    ================================================== -->

                    <a href="logout.php"
                        class="sidebar-link-glass flex justify-between items-center group text-rose-500 hover:bg-rose-50/50">

                        <span class="flex items-center">

                            <i class='bx bx-log-out-circle text-xl mr-3'></i>

                            <span class="font-semibold text-sm">
                                Logout
                            </span>

                        </span>

                        <div class="w-4"></div>

                    </a>


                </div>

            </div>


        </nav>


        <!-- =========================================================
             MOBILE APP
        ========================================================== -->

        <div class="p-4 mt-auto border-t border-white/50 bg-white/30">


            <div class="relative overflow-hidden bg-slate-900 p-4 rounded-2xl text-white">


                <h4 class="font-bold text-xs flex items-center">

                    <i class='bx bxl-play-store mr-2 text-emerald-400 text-lg'></i>

                    Mobile App

                </h4>


                <p class="text-[10px] opacity-60 mt-1 mb-3">
                    Access assessment data on the go.
                </p>


                <a
                    href="https://play.google.com/store/apps/details?id=com.lgf.Khalilabadupmuncipal"
                    target="_blank"
                    class="block w-full text-center py-2 rounded-xl bg-emerald-500 text-white text-[10px] font-bold hover:bg-emerald-600 transition-all">

                    DOWNLOAD NOW

                </a>


            </div>

        </div>


    </div>

</aside>


<!-- =============================================================
     JAVASCRIPT
============================================================= -->

<script>
    document.addEventListener("DOMContentLoaded", function() {


        const sidebar = document.getElementById("sidebar");

        const overlay = document.getElementById("sidebar-overlay");

        const dropdownButtons =
            document.querySelectorAll('[data-dropdown]');


        /* =========================================================
           MOBILE MENU
        ========================================================= */

        const mobileBtn =
            document.getElementById("menu-toggle");


        function toggleMenu() {

            sidebar.classList.toggle("-translate-x-full");

            sidebar.classList.toggle("translate-x-0");

            overlay.classList.toggle("hidden");

        }


        if (mobileBtn) {

            mobileBtn.addEventListener(
                "click",
                toggleMenu
            );

        }


        if (overlay) {

            overlay.addEventListener(
                "click",
                toggleMenu
            );

        }


        /* =========================================================
           DROPDOWN
        ========================================================= */

        dropdownButtons.forEach(function(btn) {


            btn.addEventListener("click", function() {


                const targetId =
                    btn.getAttribute("data-dropdown");


                const targetMenu =
                    document.getElementById(targetId);


                const icon =
                    btn.querySelector(".arrow-icon");


                /* Close other dropdowns */

                document
                    .querySelectorAll(".sidebar-submenu-glass")
                    .forEach(function(menu) {

                        if (menu.id !== targetId) {

                            menu.style.maxHeight = null;

                            const previous =
                                menu.previousElementSibling;

                            if (previous) {

                                const previousIcon =
                                    previous.querySelector(".arrow-icon");

                                if (previousIcon) {

                                    previousIcon.classList.remove(
                                        "rotate-90"
                                    );

                                }

                            }

                        }

                    });


                /* Toggle current */

                if (targetMenu.style.maxHeight) {

                    targetMenu.style.maxHeight = null;

                    if (icon) {
                        icon.classList.remove("rotate-90");
                    }

                } else {

                    targetMenu.style.maxHeight =
                        targetMenu.scrollHeight + "px";

                    if (icon) {
                        icon.classList.add("rotate-90");
                    }

                }


            });


        });


    });
</script>