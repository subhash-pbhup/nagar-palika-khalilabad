    
<?php 
$base_image_path = 'admin-uploads/';
$default_image_file = 'admin/man.png'; 
$default_image_src = $default_image_file; 

$user_id = $_SESSION['user_id'];

// Prepare SQL statement to fetch user data
// Using prepared statements for security (prevents SQL Injection)
$stmt = $conn->prepare("SELECT username, email, role, profile_pic FROM users WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Initialize variables with default/fallback values
$user_name = "User Not Found";
$user_email = "Email Not Found";
$user_role = "user"; // Default role
$profile_image_src = $default_image_src;

if ($result->num_rows === 1) {
    // Fetch the data
    $user_data = $result->fetch_assoc();
    
    // Assign the actual data fetched from the database
    $user_name = $user_data['username'];
    $user_email = $user_data['email'];
    $user_role = $user_data['role']; // 
	
	
	 if (!empty($user_data['profile_pic'])) {
        // If profile_image column is NOT empty, construct the dynamic path
        // $base_image_path (admin-uploads/) + $user_data['profile_image'] (filename)
        $profile_image_src = $base_image_path . htmlspecialchars($user_data['profile_pic']);
    } 
	
}

$stmt->close(); 
	?>
	<header class="flex items-center justify-between bg-white/70 backdrop-blur-md px-4 md:px-6 py-2.5 rounded-[1.5rem] border border-white/40 shadow-[0_4px_20px_rgba(0,0,0,0.03)] sticky top-4 z-30 mx-0 md:mx-2">
    
    <button id="menu-toggle" class="lg:hidden w-10 h-10 rounded-xl bg-white/50 text-slate-600 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-all duration-300 shadow-sm border border-white/60">
        <i class='bx bx-menu-alt-left text-2xl'></i>
    </button>

    <div class="relative flex-1 max-w-md mx-6 hidden sm:block group">
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-500 transition-colors">
            <i class='bx bx-search text-xl'></i>
        </span>
        <input type="text" placeholder="Quick search..." 
            class="pl-11 pr-4 py-2.5 w-full rounded-2xl bg-slate-100/50 border border-transparent focus:bg-white focus:border-emerald-200 focus:ring-4 focus:ring-emerald-500/10 transition-all outline-none text-sm font-medium text-slate-700">
    </div>
    
    <div class="flex items-center space-x-2 md:space-x-4 ml-auto">
        
        <button class="w-10 h-10 rounded-xl bg-white/50 text-slate-500 flex items-center justify-center border border-white/60 transition-all hover:bg-white hover:text-emerald-600 hover:shadow-md hidden sm:flex">
            <i class='bx bx-cog text-xl'></i>
        </button>
        
        <button class="relative w-10 h-10 rounded-xl bg-white/50 text-slate-500 flex items-center justify-center border border-white/60 transition-all hover:bg-white hover:text-emerald-600 hover:shadow-md">
            <i class='bx bx-bell text-xl'></i>
            <span class="absolute top-2.5 right-2.5 w-2 h-2 bg-rose-500 rounded-full border-2 border-white"></span>
        </button>

        <div class="relative inline-block text-left pl-2">
            <button id="profileBtn" class="flex items-center gap-3 group focus:outline-none bg-white/40 p-1.5 pr-3 rounded-2xl border border-white/60 hover:bg-white/80 transition-all">
                <div class="w-9 h-9 rounded-xl overflow-hidden shadow-sm border border-white">
                    <img src="<?php echo $profile_image_src; ?>" alt="Profile" class="w-full h-full object-cover">
                </div>
                <div class="text-left hidden md:block">
                    <p class="text-xs font-bold text-slate-800 leading-none"><?php echo htmlspecialchars($user_name); ?></p>
                    <p class="text-[10px] font-medium text-slate-500 mt-1 uppercase tracking-wider"><?php echo htmlspecialchars($user_email); ?></p>
                </div>
                <i class='bx bx-chevron-down text-slate-400 group-hover:text-slate-600 transition-transform'></i>
            </button>

            <div id="profileMenu" class="hidden absolute right-0 mt-3 w-56 bg-white/90 backdrop-blur-xl border border-white/50 rounded-[1.5rem] shadow-[0_10px_40px_rgba(0,0,0,0.1)] z-50 py-2 overflow-hidden">
                <div class="px-4 py-3 mb-1 border-b border-slate-100/50 md:hidden">
                    <p class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($user_name); ?></p>
                    <p class="text-xs text-slate-500 truncate"><?php echo htmlspecialchars($user_email); ?></p>
                </div>
                
                <a href="update-profile.php" class="flex items-center px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-emerald-50 hover:text-emerald-600 transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center mr-3">
                        <i class='bx bx-user-circle text-lg'></i>
                    </div>
                    My Profile
                </a>
                
                <a href="reset-password.php" class="flex items-center px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-emerald-50 hover:text-emerald-600 transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center mr-3">
                        <i class='bx bx-shield-quarter text-lg'></i>
                    </div>
                    Security
                </a>
                
                <div class="my-1 border-t border-slate-100/50"></div>
                
                <a href="logout.php" class="flex items-center px-4 py-2.5 text-sm font-semibold text-rose-500 hover:bg-rose-50 transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-500 flex items-center justify-center mr-3">
                        <i class='bx bx-log-out text-lg'></i>
                    </div>
                    Sign Out
                </a>
            </div>
        </div>
    </div>
</header>