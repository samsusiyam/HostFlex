<?php require_once '../config/database.php'; require_once '../includes/functions.php'; checkAdminLogin(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - <?php echo escSetting('site_name'); ?> Admin</title>
    <link rel="shortcut icon" href="../<?php echo htmlspecialchars(escSetting('favicon') ?: 'images/favicon.ico'); ?>" type="image/x-icon" />
    <script>if(localStorage.getItem('adminDarkMode')==='true')document.documentElement.classList.add('dark')</script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
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
        .dark .settings-sub a { color: #9ca3af; }
        .dark .settings-sub a:hover { color: #60a5fa; background: rgba(55,65,81,0.5); }
        .dark .settings-sub a.font-medium { color: #60a5fa; background: rgba(30,58,138,0.3); }
        .dark .sidebar-overlay.open { background: rgba(0,0,0,0.7); }
        .dark input:not([type="file"]):not([type="checkbox"]):not([type="radio"]),
        .dark textarea, .dark select { background: #374151; color: #e5e7eb; border-color: #4b5563; }
        .dark input:focus, .dark textarea:focus, .dark select:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 0 2px rgba(59,130,246,0.3); }
        .dark table { color: #d1d5db; }
        .dark table thead { background: #1f2937; color: #9ca3af; }
        .dark table tbody tr { border-color: #374151; }
        .dark table tbody tr:hover { background: rgba(55,65,81,0.4); }
        .dark .bg-white { background-color: #1f2937; }
        .dark .bg-gray-50 { background-color: #111827; }
        .dark .bg-gray-100 { background-color: #1f2937; }
        .dark .bg-blue-50 { background-color: rgba(30,58,138,0.3); }
        .dark .bg-green-100 { background-color: rgba(6,78,59,0.3); }
        .dark .bg-red-100 { background-color: rgba(127,29,29,0.3); }
        .dark .bg-yellow-100 { background-color: rgba(113,63,18,0.3); }
        .dark .bg-purple-100 { background-color: rgba(73,27,105,0.3); }
        .dark .bg-indigo-100 { background-color: rgba(30,58,138,0.3); }
        .dark .bg-gray-200 { background-color: #374151; }
        .dark .bg-blue-500 { background-color: #2563eb; }
        .dark .bg-blue-600 { background-color: #1d4ed8; }
        .dark .bg-blue-700 { background-color: #1e40af; }
        .dark .bg-red-500 { background-color: #dc2626; }
        .dark .bg-red-600 { background-color: #b91c1c; }
        .dark .bg-green-500 { background-color: #16a34a; }
        .dark .bg-green-600 { background-color: #15803d; }
        .dark .bg-yellow-500 { background-color: #ca8a04; }
        .dark .bg-purple-500 { background-color: #8b5cf6; }
        .dark .bg-indigo-500 { background-color: #6366f1; }
        .dark .bg-indigo-600 { background-color: #4f46e5; }
        .dark .text-gray-500 { color: #9ca3af; }
        .dark .text-gray-600 { color: #9ca3af; }
        .dark .text-gray-700 { color: #d1d5db; }
        .dark .text-gray-800 { color: #e5e7eb; }
        .dark .text-gray-900 { color: #f3f4f6; }
        .dark .text-blue-600 { color: #60a5fa; }
        .dark .text-blue-500 { color: #3b82f6; }
        .dark .text-red-600 { color: #f87171; }
        .dark .text-red-500 { color: #ef4444; }
        .dark .text-green-600 { color: #4ade80; }
        .dark .text-green-700 { color: #22c55e; }
        .dark .text-yellow-600 { color: #eab308; }
        .dark .text-purple-600 { color: #a78bfa; }
        .dark .text-white { color: #e5e7eb; }
        .dark .border { border-color: #374151; }
        .dark .border-b { border-color: #374151; }
        .dark .border-t { border-color: #374151; }
        .dark .border-r { border-color: #374151; }
        .dark .border-l { border-color: #374151; }
        .dark .border-gray-200 { border-color: #374151; }
        .dark .border-gray-300 { border-color: #4b5563; }
        .dark .border-blue-200 { border-color: rgba(59,130,246,0.3); }
        .dark .border-blue-500 { border-color: #3b82f6; }
        .dark .border-l-4.border-blue-500 { border-left-color: #3b82f6; }
        .dark .border-l-4.border-green-500 { border-left-color: #22c55e; }
        .dark .border-l-4.border-yellow-500 { border-left-color: #ca8a04; }
        .dark .border-l-4.border-purple-500 { border-left-color: #8b5cf6; }
        .dark .border-l-4.border-red-500 { border-left-color: #ef4444; }
        .dark .border-l-4.border-indigo-500 { border-left-color: #6366f1; }
        .dark .shadow-sm { box-shadow: 0 1px 2px rgba(0,0,0,0.3); }
        .dark .shadow { box-shadow: 0 1px 3px rgba(0,0,0,0.4); }
        .dark .shadow-lg { box-shadow: 0 10px 15px rgba(0,0,0,0.4); }
        .dark .rounded { background-color: transparent; }
        .dark .divide-gray-200 > * { border-color: #374151; }
        .dark .ring-1 { box-shadow: 0 0 0 1px #4b5563; }
        .dark hr { border-color: #374151; }
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
                    <img src="../<?php echo htmlspecialchars(getSetting('header_logo') ?: 'images/bg.webp'); ?>" class="h-8" alt="<?php echo escSetting('site_name'); ?>">
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
