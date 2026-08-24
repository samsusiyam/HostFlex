<?php
$page_title = 'Admin Dashboard';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

$total_plans = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM hosting_plans"))['c'] ?? 0;
$active_plans = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM hosting_plans WHERE status=1"))['c'] ?? 0;
$total_offers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM offers"))['c'] ?? 0;
$total_contacts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM contacts"))['c'] ?? 0;
$unread_contacts = getUnreadContacts();
$recent_contacts = mysqli_query($conn, "SELECT * FROM contacts ORDER BY created_at DESC LIMIT 5");
$total_blog = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM blog_posts WHERE deleted_at IS NULL"))['c'] ?? 0;
$total_subscribers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM subscribers"))['c'] ?? 0;
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users"))['c'] ?? 0;
$total_pages = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM pages"))['c'] ?? 0;
$total_categories = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM categories"))['c'] ?? 0;
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Welcome Header -->
    <div class="bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-chart-pie"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Dashboard Overview</h1>
            </div>
            <p class="text-xs text-gray-500">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></strong>! Here is what's happening on your website.</p>
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <a href="/" target="_blank" class="w-full sm:w-auto text-center bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-700 text-xs font-bold px-4 py-2.5 rounded-xl shadow-xs transition">
                <i class="fa-solid fa-external-link mr-1"></i> Live Site
            </a>
            <a href="settings.php" class="w-full sm:w-auto text-center bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-xs transition">
                <i class="fa-solid fa-gear mr-1"></i> Settings
            </a>
        </div>
    </div>

    <!-- Stat Cards (Row 1) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Plans -->
        <a href="plans.php" class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md transition flex flex-col justify-between group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Hosting Plans</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg group-hover:scale-110 transition">
                    <i class="fa-solid fa-server"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-gray-900"><?php echo $total_plans; ?></span>
                <span class="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full"><?php echo $active_plans; ?> active</span>
            </div>
        </a>

        <!-- Offers -->
        <a href="offers.php" class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md transition flex flex-col justify-between group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Special Offers</span>
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg group-hover:scale-110 transition">
                    <i class="fa-solid fa-tags"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-gray-900"><?php echo $total_offers; ?></span>
                <span class="text-[11px] font-semibold text-purple-600">Promotions & Deals</span>
            </div>
        </a>

        <!-- Messages -->
        <a href="contacts.php" class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md transition flex flex-col justify-between group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Inquiries</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg group-hover:scale-110 transition">
                    <i class="fa-solid fa-envelope"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-gray-900"><?php echo $total_contacts; ?></span>
                <?php if ($unread_contacts > 0): ?>
                <span class="text-[11px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full"><?php echo $unread_contacts; ?> unread</span>
                <?php else: ?>
                <span class="text-[11px] text-gray-400">All answered</span>
                <?php endif; ?>
            </div>
        </a>

        <!-- Blog Posts -->
        <a href="blogs.php" class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md transition flex flex-col justify-between group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Blog Articles</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg group-hover:scale-110 transition">
                    <i class="fa-solid fa-newspaper"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-gray-900"><?php echo $total_blog; ?></span>
                <span class="text-[11px] font-semibold text-emerald-600">Published Posts</span>
            </div>
        </a>

    </div>

    <!-- Stat Cards (Row 2) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Subscribers -->
        <a href="subscribers.php" class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md transition flex flex-col justify-between group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Subscribers</span>
                <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg group-hover:scale-110 transition">
                    <i class="fa-solid fa-paper-plane"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-gray-900"><?php echo $total_subscribers; ?></span>
                <span class="text-[11px] font-semibold text-teal-600">Newsletter</span>
            </div>
        </a>

        <!-- Categories -->
        <a href="categories.php" class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md transition flex flex-col justify-between group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Categories</span>
                <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg group-hover:scale-110 transition">
                    <i class="fa-solid fa-folder-tree"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-gray-900"><?php echo $total_categories; ?></span>
                <span class="text-[11px] font-semibold text-sky-600">Hosting Types</span>
            </div>
        </a>

        <!-- CMS Pages -->
        <a href="pages.php" class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md transition flex flex-col justify-between group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">CMS Pages</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg group-hover:scale-110 transition">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-gray-900"><?php echo $total_pages; ?></span>
                <span class="text-[11px] font-semibold text-indigo-600">Custom Content</span>
            </div>
        </a>

        <!-- Users -->
        <a href="users.php" class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md transition flex flex-col justify-between group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Staff Users</span>
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg group-hover:scale-110 transition">
                    <i class="fa-solid fa-users-gear"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-gray-900"><?php echo $total_users; ?></span>
                <span class="text-[11px] font-semibold text-rose-600">Active Accounts</span>
            </div>
        </a>

    </div>

    <!-- Recent Inquiries & Quick Shortcuts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Recent Inquiries -->
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden flex flex-col justify-between">
            <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/70">
                <h3 class="font-bold text-xs text-gray-800 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-inbox text-blue-600"></i> Recent Inquiries
                </h3>
                <a href="contacts.php" class="text-[11px] font-bold text-blue-600 hover:text-blue-800">View All Inquiries →</a>
            </div>
            
            <div class="divide-y divide-gray-100 p-2 flex-1">
                <?php if (mysqli_num_rows($recent_contacts) > 0): ?>
                    <?php while ($msg = mysqli_fetch_assoc($recent_contacts)): ?>
                    <div class="p-3 hover:bg-gray-50 rounded-xl transition flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 font-bold flex items-center justify-center text-xs border border-blue-100 shrink-0 mt-0.5">
                            <?php echo strtoupper(substr($msg['name'], 0, 2)); ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <h4 class="font-bold text-xs text-gray-900 truncate"><?php echo htmlspecialchars($msg['name']); ?></h4>
                                <span class="text-[10px] text-gray-400 whitespace-nowrap"><?php echo timeAgo($msg['created_at']); ?></span>
                            </div>
                            <p class="text-xs text-gray-600 truncate mt-0.5"><?php echo htmlspecialchars($msg['subject']); ?></p>
                        </div>
                        <?php if (!$msg['is_read']): ?>
                        <span class="w-2 h-2 rounded-full bg-blue-600 shrink-0 mt-2"></span>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="py-12 text-center text-gray-400 text-xs">
                        <i class="fa-solid fa-inbox text-3xl text-gray-300 mb-2 block"></i>
                        No contact inquiries received yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Shortcuts -->
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 flex flex-col justify-between">
            <h3 class="font-bold text-xs text-gray-800 uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fa-solid fa-bolt text-amber-500"></i> Quick Navigation
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <a href="plans.php" class="p-3.5 rounded-xl border border-gray-200/80 hover:border-blue-300 hover:bg-blue-50/40 transition flex items-center gap-3 group">
                    <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm group-hover:scale-110 transition">
                        <i class="fa-solid fa-server"></i>
                    </div>
                    <div>
                        <div class="font-bold text-xs text-gray-900">Manage Plans</div>
                        <div class="text-[10px] text-gray-400">Hosting packages</div>
                    </div>
                </a>

                <a href="offers.php" class="p-3.5 rounded-xl border border-gray-200/80 hover:border-purple-300 hover:bg-purple-50/40 transition flex items-center gap-3 group">
                    <div class="w-9 h-9 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-sm group-hover:scale-110 transition">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                    <div>
                        <div class="font-bold text-xs text-gray-900">Promo Offers</div>
                        <div class="text-[10px] text-gray-400">Deals & badges</div>
                    </div>
                </a>

                <a href="blogs.php" class="p-3.5 rounded-xl border border-gray-200/80 hover:border-emerald-300 hover:bg-emerald-50/40 transition flex items-center gap-3 group">
                    <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm group-hover:scale-110 transition">
                        <i class="fa-solid fa-newspaper"></i>
                    </div>
                    <div>
                        <div class="font-bold text-xs text-gray-900">Blog Manager</div>
                        <div class="text-[10px] text-gray-400">Articles & guides</div>
                    </div>
                </a>

                <a href="settings-general.php" class="p-3.5 rounded-xl border border-gray-200/80 hover:border-amber-300 hover:bg-amber-50/40 transition flex items-center gap-3 group">
                    <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm group-hover:scale-110 transition">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <div>
                        <div class="font-bold text-xs text-gray-900">Site Settings</div>
                        <div class="text-[10px] text-gray-400">Branding, WHMCS, SEO</div>
                    </div>
                </a>
            </div>

            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                <span>System Status: <strong class="text-emerald-600">Operational</strong></span>
                <a href="activity-logs.php" class="text-blue-600 hover:underline">View Audit Trail →</a>
            </div>
    </div>

    <!-- Server & System Environment Diagnostics Widget -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-4 mb-4 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <span class="p-2 bg-slate-100 text-slate-700 rounded-lg text-xs"><i class="fa-solid fa-server"></i></span>
                <h3 class="font-bold text-xs text-gray-900 uppercase tracking-wider">System Environment & Server Health</h3>
            </div>
            <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200 inline-flex items-center gap-1.5 self-start sm:self-auto">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>PHP <?php echo PHP_VERSION; ?> Running Normal</span>
            </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
            <div class="p-3 bg-gray-50/70 rounded-xl border border-gray-100">
                <span class="text-[10px] text-gray-400 font-bold uppercase block">PHP Engine</span>
                <span class="font-bold text-gray-800 text-xs sm:text-sm mt-0.5 block truncate"><?php echo PHP_VERSION; ?></span>
            </div>
            <div class="p-3 bg-gray-50/70 rounded-xl border border-gray-100">
                <span class="text-[10px] text-gray-400 font-bold uppercase block">MySQL Server</span>
                <span class="font-bold text-gray-800 text-xs sm:text-sm mt-0.5 block truncate"><?php echo substr(mysqli_get_server_info($conn), 0, 10); ?></span>
            </div>
            <div class="p-3 bg-gray-50/70 rounded-xl border border-gray-100">
                <span class="text-[10px] text-gray-400 font-bold uppercase block">Memory Limit</span>
                <span class="font-bold text-gray-800 text-xs sm:text-sm mt-0.5 block truncate"><?php echo ini_get('memory_limit') ?: '256M'; ?></span>
            </div>
            <div class="p-3 bg-gray-50/70 rounded-xl border border-gray-100">
                <span class="text-[10px] text-gray-400 font-bold uppercase block">Max Upload</span>
                <span class="font-bold text-gray-800 text-xs sm:text-sm mt-0.5 block truncate"><?php echo ini_get('upload_max_filesize') ?: '64M'; ?></span>
            </div>
            <div class="p-3 bg-gray-50/70 rounded-xl border border-gray-100">
                <span class="text-[10px] text-gray-400 font-bold uppercase block">2FA Security</span>
                <span class="font-bold text-purple-700 text-xs sm:text-sm mt-0.5 block">TOTP Active</span>
            </div>
            <div class="p-3 bg-gray-50/70 rounded-xl border border-gray-100">
                <span class="text-[10px] text-gray-400 font-bold uppercase block">Admin Guard</span>
                <span class="font-bold text-blue-700 text-xs sm:text-sm mt-0.5 block">Protected</span>
            </div>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>
