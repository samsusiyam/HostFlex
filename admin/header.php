<?php require_once '../config/database.php'; require_once '../includes/functions.php'; checkAdminLogin(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - <?php echo escSetting('site_name'); ?> Admin</title>
    <link rel="shortcut icon" href="../<?php echo htmlspecialchars(escSetting('favicon') ?: 'images/favicon.ico'); ?>" type="image/x-icon" />
    <script>if(localStorage.getItem('adminDarkMode')==='true')document.documentElement.classList.add('dark')</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={darkMode:'class'}</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        .sidebar-overlay { display: none; }
        @media (max-width: 768px) {
            .admin-sidebar { position: fixed; left: -280px; top: 0; bottom: 0; z-index: 1000; transition: left 0.3s ease; width: 260px; }
            .admin-sidebar.open { left: 0; }
            .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; }
            .sidebar-overlay.open { display: block; }
        }
        .settings-sub { overflow: hidden; max-height: 0; transition: max-height 0.3s ease; }
        .settings-sub.open { max-height: 500px; }
        html.dark .settings-sub a { color: #9ca3af; }
        html.dark .settings-sub a:hover { color: #60a5fa; background: rgba(55,65,81,0.5); }
        html.dark .settings-sub a.font-medium { color: #60a5fa; background: rgba(30,58,138,0.3); }
        html.dark .sidebar-overlay.open { background: rgba(0,0,0,0.7); }
        html.dark input:not([type="file"]):not([type="checkbox"]):not([type="radio"]),
        html.dark textarea, html.dark select { background: #374151; color: #e5e7eb; border-color: #4b5563; }
        html.dark input:focus, html.dark textarea:focus, html.dark select:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 0 2px rgba(59,130,246,0.3); }
        html.dark table { color: #d1d5db; }
        html.dark table thead { background: #1f2937; color: #9ca3af; }
        html.dark table tbody tr { border-color: #374151; }
        html.dark table tbody tr:hover { background: rgba(55,65,81,0.4); }
        html.dark .bg-white { background-color: #1f2937; }
        html.dark .bg-gray-50 { background-color: #111827; }
        html.dark .bg-gray-100 { background-color: #1f2937; }
        html.dark .bg-blue-50 { background-color: rgba(30,58,138,0.3); }
        html.dark .bg-green-100 { background-color: rgba(6,78,59,0.3); }
        html.dark .bg-red-100 { background-color: rgba(127,29,29,0.3); }
        html.dark .bg-yellow-100 { background-color: rgba(113,63,18,0.3); }
        html.dark .bg-purple-100 { background-color: rgba(73,27,105,0.3); }
        html.dark .bg-indigo-100 { background-color: rgba(30,58,138,0.3); }
        html.dark .bg-gray-200 { background-color: #374151; }
        html.dark .text-gray-500 { color: #9ca3af; }
        html.dark .text-gray-600 { color: #9ca3af; }
        html.dark .text-gray-700 { color: #d1d5db; }
        html.dark .text-gray-800 { color: #e5e7eb; }
        html.dark .text-gray-900 { color: #f3f4f6; }
        html.dark .text-blue-600 { color: #60a5fa; }
        html.dark .text-blue-500 { color: #3b82f6; }
        html.dark .text-red-600 { color: #f87171; }
        html.dark .text-red-500 { color: #ef4444; }
        html.dark .text-green-600 { color: #4ade80; }
        html.dark .text-green-700 { color: #22c55e; }
        html.dark .text-yellow-600 { color: #eab308; }
        html.dark .text-purple-600 { color: #a78bfa; }
        html.dark .border { border-color: #374151; }
        html.dark .border-b { border-color: #374151; }
        html.dark .border-t { border-color: #374151; }
        html.dark .border-r { border-color: #374151; }
        html.dark .border-l { border-color: #374151; }
        html.dark .border-gray-200 { border-color: #374151; }
        html.dark .border-gray-300 { border-color: #4b5563; }
        html.dark .border-blue-200 { border-color: rgba(59,130,246,0.3); }
        html.dark .shadow-sm { box-shadow: 0 1px 2px rgba(0,0,0,0.3); }
        html.dark .shadow { box-shadow: 0 1px 3px rgba(0,0,0,0.4); }
        html.dark .shadow-lg { box-shadow: 0 10px 15px rgba(0,0,0,0.4); }
        html.dark .divide-gray-200 > * { border-color: #374151; }
        html.dark hr { border-color: #374151; }
        html.dark .text-gray-400 { color: #9ca3af; }
        html.dark .text-gray-300 { color: #d1d5db; }
        html.dark .bg-red-50 { background-color: rgba(127,29,29,0.2); }
        html.dark .bg-green-50 { background-color: rgba(6,78,59,0.2); }
        html.dark .bg-yellow-50 { background-color: rgba(113,63,18,0.2); }
        html.dark .bg-purple-50 { background-color: rgba(73,27,105,0.2); }
        html.dark .bg-teal-100 { background-color: rgba(17,94,89,0.3); }
        html.dark .bg-pink-100 { background-color: rgba(131,24,67,0.3); }
        html.dark .bg-orange-100 { background-color: rgba(120,53,15,0.3); }
        html.dark .bg-cyan-100 { background-color: rgba(21,94,117,0.3); }
        html.dark .text-teal-600 { color: #2dd4bf; }
        html.dark .text-cyan-600 { color: #22d3ee; }
        html.dark .text-indigo-600 { color: #818cf8; }
        html.dark .text-pink-600 { color: #f472b6; }
        html.dark .text-orange-600 { color: #fb923c; }
        html.dark .bg-blue-50\/50 { background-color: rgba(30,58,138,0.3); }
        html.dark .hover\:bg-blue-100:hover { background-color: rgba(30,58,138,0.4); }
        html.dark .hover\:bg-green-100:hover { background-color: rgba(6,78,59,0.4); }
        html.dark .hover\:bg-yellow-100:hover { background-color: rgba(113,63,18,0.4); }
        html.dark .hover\:bg-purple-100:hover { background-color: rgba(73,27,105,0.4); }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
<?php if (isMaintenanceMode()): ?>
<div class="bg-yellow-500 dark:bg-yellow-600 text-white text-center py-2 px-4 text-sm font-medium">
    <i class="fa fa-tools mr-1"></i> Maintenance Mode is ACTIVE. Visitors see a maintenance page.
</div>
<?php endif; ?>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
<nav class="bg-white dark:bg-gray-800 shadow-sm dark:shadow-none border-b dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <button onclick="toggleSidebar()" class="md:hidden mr-3 text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"><i class="fa fa-bars text-xl"></i></button>
                <a href="dashboard.php" class="flex items-center space-x-3">
                    <img src="../<?php echo htmlspecialchars(getSetting('header_logo') ?: 'images/bg.webp'); ?>" class="h-8 dark:invert dark:brightness-200" alt="<?php echo escSetting('site_name'); ?>">
                    <span class="font-semibold text-gray-700 dark:text-gray-200 hidden sm:inline">Admin Panel</span>
                </a>
            </div>
            <div class="flex items-center space-x-3">
                <button onclick="toggleDarkMode()" id="darkToggle" class="text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-yellow-400 text-lg" title="Toggle Dark Mode">
                    <i class="fa fa-moon" id="darkIcon"></i>
                </button>
                <a href="../index.php" target="_blank" class="text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hidden sm:inline"><i class="fa fa-external-link-alt"></i> View Site</a>
                <a href="logout.php" class="text-gray-500 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400"><i class="fa fa-sign-out-alt"></i> <span class="hidden sm:inline">Logout</span></a>
            </div>
        </div>
    </div>
</nav>
<div class="flex">
    <aside class="admin-sidebar w-64 bg-white dark:bg-gray-800 shadow-sm dark:shadow-none min-h-screen border-r dark:border-gray-700 md:relative" id="adminSidebar">
        <nav class="p-4 space-y-1">
            <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-2.5 rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : ''; ?>">
                <i class="fa fa-chart-pie w-5"></i><span>Dashboard</span>
            </a>
            <a href="plans.php" class="flex items-center space-x-3 px-4 py-2.5 rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'plans') !== false && strpos($_SERVER['PHP_SELF'], 'categories') === false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : ''; ?>">
                <i class="fa fa-server w-5"></i><span>Hosting Plans</span>
            </a>
            <a href="offers.php" class="flex items-center space-x-3 px-4 py-2.5 rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'offer') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : ''; ?>">
                <i class="fa fa-tags w-5"></i><span>Offers</span>
            </a>
            <a href="categories.php" class="flex items-center space-x-3 px-4 py-2.5 rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'categor') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : ''; ?>">
                <i class="fa fa-th-large w-5"></i><span>Categories</span>
            </a>
            <a href="pages.php" class="flex items-center space-x-3 px-4 py-2.5 rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'pages') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : ''; ?>">
                <i class="fa fa-file w-5"></i><span>CMS Pages</span>
            </a>
            <a href="menus.php" class="flex items-center space-x-3 px-4 py-2.5 rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'menus') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : ''; ?>">
                <i class="fa fa-bars w-5"></i><span>Menu Manager</span>
            </a>
            <a href="contacts.php" class="flex items-center space-x-3 px-4 py-2.5 rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'contact') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : ''; ?>">
                <i class="fa fa-envelope w-5"></i><span>Contacts</span>
                <?php $unread = getUnreadContacts(); if ($unread > 0): ?>
                    <span class="ml-auto bg-red-500 dark:bg-red-600 text-white text-xs rounded-full px-2 py-0.5"><?php echo $unread; ?></span>
                <?php endif; ?>
            </a>
            <a href="subscribers.php" class="flex items-center space-x-3 px-4 py-2.5 rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'subscribers') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : ''; ?>">
                <i class="fa fa-envelope-open w-5"></i><span>Subscribers</span>
            </a>
            <a href="testimonials.php" class="flex items-center space-x-3 px-4 py-2.5 rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'testimonials') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : ''; ?>">
                <i class="fa fa-star w-5"></i><span>Testimonials</span>
            </a>
            <a href="faqs.php" class="flex items-center space-x-3 px-4 py-2.5 rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'faqs') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : ''; ?>">
                <i class="fa fa-question-circle w-5"></i><span>FAQs</span>
            </a>
            <a href="partners.php" class="flex items-center space-x-3 px-4 py-2.5 rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'partners') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : ''; ?>">
                <i class="fa fa-handshake w-5"></i><span>Partners</span>
            </a>
            <div>
                <a href="javascript:void(0)" onclick="toggleSettings()" class="flex items-center space-x-3 px-4 py-2.5 rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'setting') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : ''; ?>">
                    <i class="fa fa-cog w-5"></i><span>Settings</span>
                    <i class="fa fa-chevron-down ml-auto text-xs transition-transform" id="settingsArrow"></i>
                </a>
                <div class="settings-sub ml-2 border-l-2 border-blue-200 dark:border-blue-800 pl-3 space-y-0.5" id="settingsSub">
                    <a href="settings-general.php" class="flex items-center space-x-2 px-4 py-2 text-sm rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'settings-general') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : ''; ?>"><i class="fa fa-globe w-4"></i><span>General</span></a>
                    <a href="settings-branding.php" class="flex items-center space-x-2 px-4 py-2 text-sm rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'settings-branding') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : ''; ?>"><i class="fa fa-image w-4"></i><span>Logo & Branding</span></a>
                    <a href="settings-homepage.php" class="flex items-center space-x-2 px-4 py-2 text-sm rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'settings-homepage') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : ''; ?>"><i class="fa fa-home w-4"></i><span>Homepage Editor</span></a>
                    <a href="settings-whmcs.php" class="flex items-center space-x-2 px-4 py-2 text-sm rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'settings-whmcs') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : ''; ?>"><i class="fa fa-link w-4"></i><span>WHMCS</span></a>
                    <a href="settings-popup.php" class="flex items-center space-x-2 px-4 py-2 text-sm rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'settings-popup') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : ''; ?>"><i class="fa fa-bell w-4"></i><span>Popup & Social</span></a>
                    <a href="settings-contact.php" class="flex items-center space-x-2 px-4 py-2 text-sm rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'settings-contact') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : ''; ?>"><i class="fa fa-envelope w-4"></i><span>Contact Page</span></a>
                    <a href="settings-seo.php" class="flex items-center space-x-2 px-4 py-2 text-sm rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'settings-seo') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : ''; ?>"><i class="fa fa-search w-4"></i><span>SEO</span></a>
                    <a href="settings-footer.php" class="flex items-center space-x-2 px-4 py-2 text-sm rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'settings-footer') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : ''; ?>"><i class="fa fa-shoe-prints w-4"></i><span>Footer</span></a>
                    <a href="settings-integrations.php" class="flex items-center space-x-2 px-4 py-2 text-sm rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'settings-integrations') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : ''; ?>"><i class="fa fa-puzzle-piece w-4"></i><span>Integrations</span></a>
                    <a href="settings-maintenance.php" class="flex items-center space-x-2 px-4 py-2 text-sm rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'settings-maintenance') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : ''; ?>"><i class="fa fa-shield-alt w-4"></i><span>Maintenance</span></a>
                </div>
            </div>
            <div>
                <a href="javascript:void(0)" onclick="toggleEmail()" class="flex items-center space-x-3 px-4 py-2.5 rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'smtp') !== false || strpos($_SERVER['PHP_SELF'], 'email-templates') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : ''; ?>">
                    <i class="fa fa-envelope w-5"></i><span>Email</span>
                    <i class="fa fa-chevron-down ml-auto text-xs transition-transform" id="emailArrow"></i>
                </a>
                <div class="settings-sub ml-2 border-l-2 border-blue-200 dark:border-blue-800 pl-3 space-y-0.5" id="emailSub">
                    <a href="settings-smtp.php" class="flex items-center space-x-2 px-4 py-2 text-sm rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'settings-smtp') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : ''; ?>"><i class="fa fa-cog w-4"></i><span>SMTP Settings</span></a>
                    <a href="email-templates.php" class="flex items-center space-x-2 px-4 py-2 text-sm rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'email-templates') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : ''; ?>"><i class="fa fa-envelope-open-text w-4"></i><span>Email Templates</span></a>
                </div>
            </div>
            <div>
                <a href="javascript:void(0)" onclick="toggleSecurity()" class="flex items-center space-x-3 px-4 py-2.5 rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo preg_match('/users|roles|activity-logs|login-logs|database-backup|update/', $_SERVER['PHP_SELF']) ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : ''; ?>">
                    <i class="fa fa-lock w-5"></i><span>Security</span>
                    <i class="fa fa-chevron-down ml-auto text-xs transition-transform" id="securityArrow"></i>
                </a>
                <div class="settings-sub ml-2 border-l-2 border-blue-200 dark:border-blue-800 pl-3 space-y-0.5" id="securitySub">
                    <a href="users.php" class="flex items-center space-x-2 px-4 py-2 text-sm rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'users') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : ''; ?>"><i class="fa fa-users-cog w-4"></i><span>Admin Users</span></a>
                    <a href="roles.php" class="flex items-center space-x-2 px-4 py-2 text-sm rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'roles') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : ''; ?>"><i class="fa fa-user-tag w-4"></i><span>Roles & Permissions</span></a>
                    <a href="activity-logs.php" class="flex items-center space-x-2 px-4 py-2 text-sm rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'activity-logs') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : ''; ?>"><i class="fa fa-history w-4"></i><span>Activity Logs</span></a>
                    <a href="login-logs.php" class="flex items-center space-x-2 px-4 py-2 text-sm rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'login-logs') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : ''; ?>"><i class="fa fa-sign-in-alt w-4"></i><span>Login Logs</span></a>
                    <a href="database-backup.php" class="flex items-center space-x-2 px-4 py-2 text-sm rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'database-backup') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : ''; ?>"><i class="fa fa-database w-4"></i><span>Database Backup</span></a>
                    <a href="file-manager.php" class="flex items-center space-x-2 px-4 py-2 text-sm rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'file-manager') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : ''; ?>"><i class="fa fa-folder-open w-4"></i><span>File Manager</span></a>
                    <a href="update.php" class="flex items-center space-x-2 px-4 py-2 text-sm rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'update') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : ''; ?>"><i class="fa fa-sync-alt w-4"></i><span>System Update</span></a>
                </div>
            </div>
            <hr class="my-4 border-gray-200 dark:border-gray-700">
            <div>
                <a href="javascript:void(0)" onclick="toggleBlog()" class="flex items-center space-x-3 px-4 py-2.5 rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'blog') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : ''; ?>">
                    <i class="fa fa-blog w-5"></i><span>Blog</span>
                    <i class="fa fa-chevron-down ml-auto text-xs transition-transform" id="blogArrow"></i>
                </a>
                <div class="settings-sub ml-2 border-l-2 border-blue-200 dark:border-blue-800 pl-3 space-y-0.5" id="blogSub">
                    <a href="blog-categories.php" class="flex items-center space-x-2 px-4 py-2 text-sm rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'blog-categories') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : ''; ?>"><i class="fa fa-tags w-4"></i><span>Blog Categories</span></a>
                    <a href="blogs.php" class="flex items-center space-x-2 px-4 py-2 text-sm rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'blogs.php') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : ''; ?>"><i class="fa fa-list w-4"></i><span>All Blogs</span></a>
                </div>
            </div>
            <a href="profile.php" class="flex items-center space-x-3 px-4 py-2.5 rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 <?php echo strpos($_SERVER['PHP_SELF'], 'profile') !== false ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : ''; ?>">
                <i class="fa fa-user-cog w-5"></i><span>Admin Profile</span>
            </a>
            <hr class="my-4 border-gray-200 dark:border-gray-700">
            <a href="../index.php" target="_blank" class="flex items-center space-x-3 px-4 py-2.5 rounded hover:bg-blue-50 dark:hover:bg-gray-700/50 text-gray-500 dark:text-gray-400">
                <i class="fa fa-external-link-alt w-5"></i><span>View Website</span>
            </a>
        </nav>
    </aside>
    <main class="flex-1 p-4 md:p-6 dark:text-gray-200">
    <script>
        function toggleSidebar() {
            document.getElementById('adminSidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('open');
        }
        function toggleSettings() {
            const sub = document.getElementById('settingsSub');
            const arrow = document.getElementById('settingsArrow');
            sub.classList.toggle('open');
            arrow.style.transform = sub.classList.contains('open') ? 'rotate(180deg)' : '';
        }
        function toggleSecurity() {
            const sub = document.getElementById('securitySub');
            const arrow = document.getElementById('securityArrow');
            sub.classList.toggle('open');
            arrow.style.transform = sub.classList.contains('open') ? 'rotate(180deg)' : '';
        }
        function toggleBlog() {
            const sub = document.getElementById('blogSub');
            const arrow = document.getElementById('blogArrow');
            sub.classList.toggle('open');
            arrow.style.transform = sub.classList.contains('open') ? 'rotate(180deg)' : '';
        }
        function toggleEmail() {
            const sub = document.getElementById('emailSub');
            const arrow = document.getElementById('emailArrow');
            sub.classList.toggle('open');
            arrow.style.transform = sub.classList.contains('open') ? 'rotate(180deg)' : '';
        }
        function toggleDarkMode() {
            const html = document.documentElement;
            const icon = document.getElementById('darkIcon');
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('adminDarkMode', 'false');
                icon.className = 'fa fa-moon';
            } else {
                html.classList.add('dark');
                localStorage.setItem('adminDarkMode', 'true');
                icon.className = 'fa fa-sun';
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            const icon = document.getElementById('darkIcon');
            if (document.documentElement.classList.contains('dark')) {
                icon.className = 'fa fa-sun';
            } else {
                icon.className = 'fa fa-moon';
            }
        });
        <?php if (strpos($_SERVER['PHP_SELF'], 'setting') !== false): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('settingsSub').classList.add('open');
            document.getElementById('settingsArrow').style.transform = 'rotate(180deg)';
        });
        <?php endif; ?>
        <?php if (preg_match('/users|roles|activity-logs|login-logs|database-backup|file-manager|update/', $_SERVER['PHP_SELF'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('securitySub').classList.add('open');
            document.getElementById('securityArrow').style.transform = 'rotate(180deg)';
        });
        <?php endif; ?>
        <?php if (strpos($_SERVER['PHP_SELF'], 'blog') !== false): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('blogSub').classList.add('open');
            document.getElementById('blogArrow').style.transform = 'rotate(180deg)';
        });
        <?php endif; ?>
        <?php if (strpos($_SERVER['PHP_SELF'], 'smtp') !== false || strpos($_SERVER['PHP_SELF'], 'email-templates') !== false): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('emailSub').classList.add('open');
            document.getElementById('emailArrow').style.transform = 'rotate(180deg)';
        });
        <?php endif; ?>
    </script>
