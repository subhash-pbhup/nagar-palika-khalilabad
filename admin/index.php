<?php
// ============================================================
//  NAGAR PALIKA PARISHAD KHALILABAD — Admin Login
//  BUG FIX: Added $stmt->close() before second prepare()
//           Added null-checks on prepare() return values
//           Cleaned Step-2 condition to avoid false positives
// ============================================================
session_start();
include("db.php");

$error             = "";
$retained_username = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $login_id  = trim($_POST['username'] ?? '');
    $password  = trim($_POST['password'] ?? '');

    $retained_username = htmlspecialchars($login_id);

    $user_found     = false;
    $user_data      = null;
    $dashboard_path = "";


    $stmt = $conn->prepare(
        "SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1"
    );

    if ($stmt === false) {
        // prepare() returned false — log for debugging, show generic message
        error_log("[NPP Login] users prepare failed: " . $conn->error);
        $error = "A system error occurred. Please try again later.";
    } else {
        $stmt->bind_param("s", $login_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $user = $res->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $user_found     = true;
                $user_data      = $user;
                $dashboard_path = "dashboard.php";
            } else {
                $error = "Invalid username or password!";
            }
        }

        // ★ KEY FIX: close the first statement BEFORE the second prepare()
        $stmt->close();
    }

    // ─────────────────────────────────────────────────────────
    // STEP 2 — Check 'surveyors' table ONLY if:
    //   • username was NOT found/matched in Step 1, AND
    //   • no error was already set (avoids double-checking when
    //     user exists in 'users' but typed wrong password)
    // ─────────────────────────────────────────────────────────
    if (!$user_found && empty($error)) {

        $stmt2 = $conn->prepare(
            "SELECT id, username, password, role, status FROM surveyors WHERE username = ? LIMIT 1"
        );

        if ($stmt2 === false) {
            error_log("[NPP Login] surveyors prepare failed: " . $conn->error);
            $error = "A system error occurred. Please try again later.";
        } else {
            $stmt2->bind_param("s", $login_id);
            $stmt2->execute();
            $res2 = $stmt2->get_result();

            if ($res2 && $res2->num_rows === 1) {
                $surveyor = $res2->fetch_assoc();

                if ($surveyor['status'] === 'active') {
                    if (password_verify($password, $surveyor['password'])) {
                        $user_found     = true;
                        $user_data      = $surveyor;
                        $dashboard_path = "surveyor-dashboard.php";
                    } else {
                        $error = "Invalid username or password!";
                    }
                } else {
                    $error = "Your account is deactivated. Please contact the admin.";
                }
            } else {
                // Username not found in either table
                $error = "Invalid username or password!";
            }

            $stmt2->close(); // always close the second statement too
        }
    }

    // ─────────────────────────────────────────────────────────
    // SUCCESS — regenerate session and redirect
    // ─────────────────────────────────────────────────────────
    if ($user_found && !empty($dashboard_path)) {
        session_regenerate_id(true);

        $_SESSION['user_id']  = $user_data['id'];
        $_SESSION['username'] = $user_data['username'];
        $_SESSION['role']     = $user_data['role'];

        header("Location: " . $dashboard_path);
        exit;
    }
}

$retained_username = $retained_username ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-brand-navy">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>नगर पालिका परिषद - Khalilabad, Sant Kabir Nagar</title>

    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="../assets/images/logo/logo.png">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Tailwind Config for Theme Colors -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            navy: '#051124',
                            card: '#0f172a',
                            orange: '#f97316',
                            orangeHover: '#ea580c'
                        }
                    }
                }
            }
        }
    </script>

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tiro+Devanagari+Hindi&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .font-hindi {
            font-family: 'Tiro Devanagari Hindi', serif;
        }

        /* Subtle dot-grid background */
        .bg-grid-pattern {
            background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 0);
            background-size: 28px 28px;
        }

        /* Icon turns orange when its sibling input is focused */
        .input-group:focus-within .input-icon {
            color: #f97316;
        }

        .service-chip {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .service-chip:hover {
            transform: translateY(-2px);
        }
    </style>
</head>

<body class="min-h-full bg-brand-navy text-slate-100 bg-grid-pattern flex items-center justify-center p-4 sm:p-6 lg:p-8">

    <!-- ── Top Nav Bar ── -->
    <div class="fixed top-0 left-0 right-0 p-4 sm:p-6 flex justify-between items-center z-20 pointer-events-none">
        <a href="../"
            class="pointer-events-auto group inline-flex items-center gap-2 px-4 py-2 rounded-full
                  bg-slate-900/80 border border-slate-800 text-xs font-semibold text-slate-300
                  hover:text-white hover:border-brand-orange hover:bg-slate-800/80 backdrop-blur-md transition-all shadow-lg">
            <i class="fas fa-arrow-left text-brand-orange group-hover:-translate-x-1 transition-transform"></i>
            <span>Main Portal</span>
        </a>

        <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full
                    bg-slate-900/60 border border-slate-700/50 text-[11px] font-medium
                    text-slate-300 backdrop-blur-md">
            <span class="w-2 h-2 rounded-full bg-brand-orange animate-pulse"></span>
            <span>Official Admin Portal &bull; Sant Kabir Nagar</span>
        </div>
    </div>

    <!-- ── Main Grid ── -->
    <div class="w-full max-w-5xl my-auto grid grid-cols-1 lg:grid-cols-12 gap-8 items-center relative z-10 pt-16 sm:pt-12">

        <!-- ═══════════ LEFT — Branding ═══════════ -->
        <div class="lg:col-span-7 space-y-6 text-center lg:text-left pr-0 lg:pr-4">

            <!-- Logo + Hindi heading -->
            <div class="flex flex-col sm:flex-row items-center lg:items-start justify-center lg:justify-start gap-4">
                <div class="p-2.5 rounded-2xl bg-brand-card/90 border border-slate-800 shadow-xl backdrop-blur-md shrink-0">
                    <img src="../assets/images/logo/logo.png"
                        alt="Municipal Logo"
                        class="h-16 sm:h-20 w-auto object-contain"
                        onerror="this.style.display='none'">
                </div>

                <div class="space-y-1">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full
                                bg-slate-900/90 border border-slate-800 text-[11px] text-slate-300 font-medium">
                        <span class="text-brand-orange font-bold">
                            <i class="fas fa-shield-halved mr-1"></i> Govt. of UP
                        </span>
                        <span class="text-slate-600">&bull;</span>
                        <span class="text-slate-400">Digital India</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-white font-hindi tracking-wide pt-1">
                        नगर पालिका परिषद
                    </h2>
                </div>
            </div>

            <!-- City / District name -->
            <div>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight leading-none uppercase">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 via-orange-300 to-orange-500">
                        KHALILABAD
                    </span>
                </h1>
                <p class="text-sm sm:text-base font-semibold text-slate-400 tracking-wider uppercase mt-1">
                    Sant Kabir Nagar, Uttar Pradesh
                </p>
                <p class="mt-3 text-xs sm:text-sm text-slate-400 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                    Integrated Property Tax, ARV Assessment &amp; GIS Survey Management Information System.
                </p>
            </div>

            <!-- Service Chips -->
            <div class="pt-2">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-3">
                    Key Services &amp; Modules
                </p>
                <div class="flex flex-wrap justify-center lg:justify-start gap-2.5">
                    <div class="service-chip flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900/70
                                border border-slate-800 text-slate-300 text-xs font-medium
                                hover:border-brand-orange/50 hover:bg-slate-900 cursor-default">
                        <i class="fas fa-calculator text-brand-orange opacity-80"></i>
                        <span>ARV Calculation</span>
                    </div>
                    <div class="service-chip flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900/70
                                border border-slate-800 text-slate-300 text-xs font-medium
                                hover:border-brand-orange/50 hover:bg-slate-900 cursor-default">
                        <i class="fas fa-house-user text-brand-orange opacity-80"></i>
                        <span>Property Assessment</span>
                    </div>
                    <div class="service-chip flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900/70
                                border border-slate-800 text-slate-300 text-xs font-medium
                                hover:border-brand-orange/50 hover:bg-slate-900 cursor-default">
                        <i class="fas fa-map-location-dot text-brand-orange opacity-80"></i>
                        <span>GIS Field Survey</span>
                    </div>
                    <div class="service-chip flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900/70
                                border border-slate-800 text-slate-300 text-xs font-medium
                                hover:border-brand-orange/50 hover:bg-slate-900 cursor-default">
                        <i class="fas fa-file-invoice-dollar text-brand-orange opacity-80"></i>
                        <span>Tax Management</span>
                    </div>
                </div>
            </div>

            <!-- Security note (desktop only) -->
            <div class="hidden lg:flex items-center gap-4 pt-4 border-t border-slate-800/80 text-xs text-slate-400">
                <div class="flex items-center gap-1.5">
                    <i class="fas fa-lock text-brand-orange"></i>
                    <span>256-Bit SSL Encrypted</span>
                </div>
                <span>&bull;</span>
                <div class="flex items-center gap-1.5">
                    <i class="fas fa-user-shield text-brand-orange"></i>
                    <span>Authorized Official Access Only</span>
                </div>
            </div>
        </div>

        <!-- ═══════════ RIGHT — Login Card ═══════════ -->
        <div class="lg:col-span-5">
            <div class="bg-brand-card border border-slate-800/80 rounded-3xl p-6 sm:p-8
                        shadow-2xl backdrop-blur-xl relative overflow-hidden">

                <!-- Top accent gradient line -->
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-orange-400 via-brand-orange to-orange-600"></div>

                <!-- Card header -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-brand-navy border border-slate-800
                                    flex items-center justify-center p-1 shadow-inner shrink-0">
                            <img src="../assets/images/logo/logo.png"
                                alt="Logo"
                                class="w-full h-full object-contain"
                                onerror="this.onerror=null; this.style.display='none'">
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">Official Login</h2>
                            <p class="text-xs text-slate-400">Enter your credentials below</p>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-md bg-slate-800 border border-brand-orange/30
                                 text-[10px] font-bold tracking-wider uppercase text-brand-orange">
                        Secure v1.0
                    </span>
                </div>

                <!-- ── Error Alert ── -->
                <?php if ($error): ?>
                    <div class="mb-5 p-3.5 rounded-xl bg-red-500/10 border border-red-500/30
                            text-red-300 text-xs flex items-center gap-3">
                        <i class="fas fa-circle-exclamation text-red-400 text-sm shrink-0"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <!-- ── Caps Lock Warning (JS) ── -->
                <div id="capsWarning"
                    class="hidden mb-4 p-2.5 rounded-lg bg-amber-500/10 border border-amber-500/30
                            text-amber-300 text-xs flex items-center gap-2">
                    <i class="fas fa-triangle-exclamation text-amber-400"></i>
                    <span><strong>Caps Lock</strong> is active</span>
                </div>

                <!-- ── Login Form ── -->
                <form method="POST" class="space-y-4" autocomplete="off">

                    <!-- Username -->
                    <div class="input-group">
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Username / Employee ID
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i class="fas fa-user input-icon text-slate-500 transition-colors text-sm"></i>
                            </div>
                            <input type="text"
                                name="username"
                                placeholder="e.g. admin or surveyor_01"
                                value="<?= $retained_username ?>"
                                required
                                autocomplete="username"
                                class="w-full bg-slate-900/60 border border-slate-700 rounded-xl
                                          py-3 pl-10 pr-4 text-sm text-white placeholder-slate-500
                                          focus:outline-none focus:border-brand-orange focus:ring-1
                                          focus:ring-brand-orange transition-all">
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="input-group">
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i class="fas fa-key input-icon text-slate-500 transition-colors text-sm"></i>
                            </div>
                            <input type="password"
                                name="password"
                                id="passwordInput"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                                class="w-full bg-slate-900/60 border border-slate-700 rounded-xl
                                          py-3 pl-10 pr-10 text-sm text-white placeholder-slate-500
                                          focus:outline-none focus:border-brand-orange focus:ring-1
                                          focus:ring-brand-orange transition-all">
                            <button type="button"
                                id="passwordToggle"
                                aria-label="Toggle password visibility"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center
                                           text-slate-500 hover:text-slate-300 transition-colors">
                                <i class="far fa-eye-slash text-sm" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember + SSL note -->
                    <div class="flex items-center justify-between text-xs pt-1">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox"
                                name="remember_me"
                                class="w-4 h-4 rounded border-slate-700 bg-slate-900
                                          text-brand-orange focus:ring-brand-orange focus:ring-offset-brand-card">
                            <span class="text-slate-400">Remember session</span>
                        </label>
                        <span class="text-slate-500 text-[11px]">
                            <i class="fas fa-lock text-xs mr-1 text-brand-orange opacity-70"></i> SSL Protected
                        </span>
                    </div>

                    <!-- Submit -->
                    <button type="submit"
                        class="w-full mt-2 py-3 px-4 bg-gradient-to-r from-brand-orange to-orange-600
                                   hover:from-brand-orangeHover hover:to-orange-700 text-white font-bold rounded-xl
                                   shadow-lg shadow-orange-900/30 hover:shadow-orange-900/50
                                   active:scale-[0.99] transition-all flex items-center justify-center gap-2 group">
                        <span>LOGIN SECURELY</span>
                        <i class="fas fa-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </form>

                <!-- Footer -->
                <div class="mt-6 pt-4 border-t border-slate-800/80 text-center">
                    <p class="text-[11px] text-slate-500">
                        Nagar Palika Parishad Khalilabad &copy; <?= date("Y") ?>.<br>
                        Sant Kabir Nagar — All Rights Reserved.
                    </p>
                </div>

            </div><!-- /card -->
        </div>

    </div><!-- /grid -->

    <!-- ── JavaScript ── -->
    <script>
        /* Password show/hide toggle */
        const passwordInput = document.getElementById('passwordInput');
        const passwordToggle = document.getElementById('passwordToggle');
        const toggleIcon = document.getElementById('toggleIcon');

        passwordToggle.addEventListener('click', function() {
            const isHidden = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isHidden ? 'text' : 'password');
            toggleIcon.classList.toggle('fa-eye-slash', !isHidden);
            toggleIcon.classList.toggle('fa-eye', isHidden);
        });

        /* Caps Lock warning */
        const capsWarning = document.getElementById('capsWarning');
        passwordInput.addEventListener('keyup', function(e) {
            capsWarning.classList.toggle('hidden', !e.getModifierState('CapsLock'));
        });
    </script>
</body>

</html>