<?php require_once '../config/database.php'; require_once '../includes/functions.php'; checkAdminLogin(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - <?php echo htmlspecialchars(getSetting('site_name') ?: 'Host Nibo'); ?> Admin</title>
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet" />
    <link rel="shortcut icon" href="/<?php echo ltrim(getSetting('favicon') ?: 'images/favicon.ico', '/'); ?>" type="image/x-icon" />
    <link rel="icon" href="/<?php echo ltrim(getSetting('favicon') ?: 'images/favicon.ico', '/'); ?>" type="image/x-icon" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        .sidebar-overlay { 
            display: none; 
            position: fixed; 
            inset: 0; 
            background: rgba(15, 23, 42, 0.6); 
            backdrop-filter: blur(2px);
            z-index: 998; 
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .sidebar-overlay.open { display: block; opacity: 1; }

        @media (max-width: 768px) {
            .admin-sidebar { 
                position: fixed; 
                left: -300px; 
                top: 0; 
                bottom: 0; 
                z-index: 999; 
                transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
                width: 280px; 
                box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            }
            .admin-sidebar.open { left: 0; }
        }
        
        .settings-sub { 
            overflow: hidden; 
            max-height: 0; 
            transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        .settings-sub.open { max-height: 1200px; }

        .menu-item-active {
            background-color: #eff6ff !important;
            color: #2563eb !important;
            font-weight: 700 !important;
            border-right: 3px solid #2563eb !important;
        }
        .sub-item-active {
            background-color: #eff6ff !important;
            color: #2563eb !important;
            font-weight: 700 !important;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 antialiased">
<?php if (isMaintenanceMode()): ?>
<div class="bg-amber-500 text-white text-center py-2 px-4 text-xs font-semibold flex items-center justify-center gap-2 sticky top-0 z-[999999] shadow-xs">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <span>Maintenance Mode is ACTIVE. Visitors see a maintenance screen.</span>
</div>
<?php endif; ?>

<!-- Backdrop Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Top Navbar -->
<nav class="bg-white shadow-xs border-b border-gray-200 sticky top-0 z-30">
    <div class="max-w-full mx-auto px-4 sm:px-6">
        <div class="flex justify-between items-center h-14 sm:h-16">
            
            <!-- Left: Toggle & Logo -->
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="md:hidden p-2 rounded-xl text-gray-600 hover:text-blue-600 hover:bg-gray-100 transition cursor-pointer" aria-label="Toggle navigation">
                    <i class="fa-solid fa-bars-staggered text-lg"></i>
                </button>
                <a href="dashboard.php" class="flex items-center gap-2.5">
                    <img src="/<?php echo ltrim(getSetting('header_logo') ?: 'images/bg.png', '/'); ?>" class="h-7 sm:h-8 object-contain" alt="<?php echo htmlspecialchars(getSetting('site_name') ?: 'Host Nibo'); ?>">
                    <span class="font-extrabold text-gray-900 text-sm sm:text-base hidden xs:inline tracking-tight">Admin Panel</span>
                </a>
            </div>

            <!-- Right: User Profile, View Site & Logout -->
            <div class="flex items-center gap-2 sm:gap-4">
                <a href="/" target="_blank" class="text-xs font-semibold text-gray-600 hover:text-blue-600 px-3 py-1.5 rounded-xl hover:bg-gray-100 transition hidden sm:inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-up-right-from-square text-[11px]"></i>
                    <span>View Site</span>
                </a>

                <!-- User Profile Pill -->
                <a href="profile.php" class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gray-50 border border-gray-200 hover:border-blue-300 transition">
                    <div class="w-6 h-6 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center">
                        <?php echo strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)); ?>
                    </div>
                    <div class="hidden sm:flex flex-col text-left">
                        <span class="text-xs font-bold text-gray-900 leading-tight"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                        <span class="text-[10px] text-blue-600 font-semibold uppercase tracking-wider"><?php echo htmlspecialchars($_SESSION['admin_role'] ?? 'Admin'); ?></span>
                    </div>
                </a>

                <a href="logout.php" class="text-xs font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-3 py-2 rounded-xl transition inline-flex items-center gap-1.5" title="Logout">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span class="hidden md:inline">Logout</span>
                </a>
            </div>

        </div>
    </div>
</nav>

<div class="flex min-h-[calc(100vh-3.5rem)] sm:min-h-[calc(100vh-4rem)]">
    
    <!-- Sidebar -->
    <aside class="admin-sidebar w-64 bg-white border-r border-gray-200 sticky top-14 sm:top-16 h-[calc(100vh-3.5rem)] sm:h-[calc(100vh-4rem)] overflow-y-auto custom-scrollbar flex flex-col justify-between shrink-0" id="adminSidebar">
        
        <div>
            <!-- Mobile Sidebar Top Header with Close Button -->
            <div class="md:hidden flex items-center justify-between p-4 border-b border-gray-100 bg-gray-50/50">
                <div class="flex items-center gap-2">
                    <img src="/<?php echo ltrim(getSetting('header_logo') ?: 'images/bg.png', '/'); ?>" class="h-6 object-contain" alt="Logo">
                    <span class="font-bold text-xs text-gray-800">Menu</span>
                </div>
                <button onclick="toggleSidebar()" class="p-1.5 text-gray-500 hover:text-gray-800 hover:bg-gray-200 rounded-lg transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="p-3 space-y-5 text-xs font-medium">

                <!-- 1. CORE SECTION -->
                <div class="space-y-1">
                    <div class="px-3 py-1 text-[10px] font-extrabold uppercase text-gray-400 tracking-wider">Core</div>
                    
                    <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'menu-item-active' : ''; ?>">
                        <i class="fa-solid fa-chart-pie w-4 text-center"></i>
                        <span>Dashboard</span>
                    </a>

                    <?php if (hasRole(['admin', 'manager'])): ?>
                    <a href="plans.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'plans') !== false ? 'menu-item-active' : ''; ?>">
                        <i class="fa-solid fa-server w-4 text-center"></i>
                        <span>Hosting Plans</span>
                    </a>
                    <a href="offers.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'offer') !== false ? 'menu-item-active' : ''; ?>">
                        <i class="fa-solid fa-tags w-4 text-center"></i>
                        <span>Offers & Deals</span>
                    </a>
                    <?php endif; ?>

                    <?php if (hasRole(['admin', 'manager', 'editor'])): ?>
                    <a href="categories.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'categor') !== false && strpos($_SERVER['PHP_SELF'], 'blog-categor') === false ? 'menu-item-active' : ''; ?>">
                        <i class="fa-solid fa-layer-group w-4 text-center"></i>
                        <span>Categories</span>
                    </a>
                    <?php endif; ?>
                </div>

                <!-- 2. CONTENT & CMS -->
                <?php if (hasRole(['admin', 'editor', 'manager'])): ?>
                <div class="space-y-1">
                    <div class="px-3 py-1 text-[10px] font-extrabold uppercase text-gray-400 tracking-wider">Content & CMS</div>
                    
                    <a href="pages.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'pages') !== false ? 'menu-item-active' : ''; ?>">
                        <i class="fa-solid fa-file-lines w-4 text-center"></i>
                        <span>CMS Pages</span>
                    </a>

                    <!-- Blog Dropdown -->
                    <div>
                        <button type="button" onclick="toggleBlog()" class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'blog') !== false ? 'bg-blue-50/50 text-blue-600 font-bold' : ''; ?>">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-newspaper w-4 text-center"></i>
                                <span>Blog Articles</span>
                            </div>
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" id="blogArrow"></i>
                        </button>
                        <div class="settings-sub ml-3 border-l-2 border-blue-100 pl-2 space-y-0.5 mt-1" id="blogSub">
                            <a href="blogs.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'blogs.php') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                <span>All Posts</span>
                            </a>
                            <a href="blog-edit.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'blog-edit.php') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                <span>Add New Post</span>
                            </a>
                            <a href="blog-categories.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'blog-categories') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                <span>Categories</span>
                            </a>
                        </div>
                    </div>

                    <a href="menus.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'menus') !== false ? 'menu-item-active' : ''; ?>">
                        <i class="fa-solid fa-bars w-4 text-center"></i>
                        <span>Menu Manager</span>
                    </a>
                    <a href="testimonials.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'testimonials') !== false ? 'menu-item-active' : ''; ?>">
                        <i class="fa-solid fa-quote-left w-4 text-center"></i>
                        <span>Testimonials</span>
                    </a>
                    <a href="faqs.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'faqs') !== false ? 'menu-item-active' : ''; ?>">
                        <i class="fa-solid fa-circle-question w-4 text-center"></i>
                        <span>FAQs</span>
                    </a>
                    <a href="partners.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'partners') !== false ? 'menu-item-active' : ''; ?>">
                        <i class="fa-solid fa-handshake w-4 text-center"></i>
                        <span>Partners</span>
                    </a>
                </div>
                <?php endif; ?>

                <!-- 3. COMMUNICATIONS & MARKETING -->
                <?php if (hasRole(['admin', 'manager'])): ?>
                <div class="space-y-1">
                    <div class="px-3 py-1 text-[10px] font-extrabold uppercase text-gray-400 tracking-wider">Communication</div>
                    
                    <a href="contacts.php" class="flex items-center justify-between px-3 py-2 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'contact') !== false && strpos($_SERVER['PHP_SELF'], 'settings-contact') === false ? 'menu-item-active' : ''; ?>">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-envelope w-4 text-center"></i>
                            <span>Contacts</span>
                        </div>
                        <?php $unread = getUnreadContacts(); if ($unread > 0): ?>
                        <span class="bg-rose-500 text-white font-extrabold text-[10px] px-2 py-0.5 rounded-full shadow-xs"><?php echo $unread; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="subscribers.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'subscribers') !== false ? 'menu-item-active' : ''; ?>">
                        <i class="fa-solid fa-paper-plane w-4 text-center"></i>
                        <span>Subscribers</span>
                    </a>
                    <a href="email-templates.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'email-templates') !== false ? 'menu-item-active' : ''; ?>">
                        <i class="fa-solid fa-envelope-open-text w-4 text-center"></i>
                        <span>Email Templates</span>
                    </a>
                </div>
                <?php endif; ?>

                <!-- 4. SYSTEM & SETTINGS -->
                <?php if (hasRole(['admin'])): ?>
                <div class="space-y-1">
                    <div class="px-3 py-1 text-[10px] font-extrabold uppercase text-gray-400 tracking-wider">System & Settings</div>

                    <!-- Settings Dropdown -->
                    <div>
                        <button type="button" onclick="toggleSettings()" class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition <?php echo (strpos($_SERVER['PHP_SELF'], 'setting') !== false || strpos($_SERVER['PHP_SELF'], 'smtp') !== false) ? 'bg-blue-50/50 text-blue-600 font-bold' : ''; ?>">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-gear w-4 text-center"></i>
                                <span>Settings Hub</span>
                            </div>
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" id="settingsArrow"></i>
                        </button>
                        <div class="settings-sub ml-3 border-l-2 border-blue-100 pl-2 space-y-0.5 mt-1" id="settingsSub">
                            <a href="settings.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                <span class="font-bold">Settings Overview</span>
                            </a>
                            <a href="settings-general.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'settings-general') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                <span>General</span>
                            </a>
                            <a href="settings-currency.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'settings-currency') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span class="font-semibold text-emerald-700">Multi-Currency & Rates</span>
                            </a>
                            <a href="settings-branding.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'settings-branding') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                <span>Logo & Branding</span>
                            </a>
                            <a href="settings-homepage.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'settings-homepage') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                <span>Homepage Sections</span>
                            </a>
                            <a href="settings-whmcs.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'settings-whmcs') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                <span>WHMCS Integration</span>
                            </a>
                            <a href="settings-smtp.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'settings-smtp') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                <span>SMTP Mail Server</span>
                            </a>
                            <a href="settings-popup.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'settings-popup') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                <span>Popup Notice & FAB</span>
                            </a>
                            <a href="settings-contact.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'settings-contact') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                <span>Contact Page</span>
                            </a>
                            <a href="settings-seo.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'settings-seo') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                <span>SEO & OpenGraph</span>
                            </a>
                            <a href="settings-footer.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'settings-footer') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                <span>Footer Settings</span>
                            </a>
                            <a href="settings-integrations.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'settings-integrations') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                <span>Integrations & Scripts</span>
                            </a>
                            <a href="settings-maintenance.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'settings-maintenance') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                <span>Maintenance Mode</span>
                            </a>
                            <a href="settings-security.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'settings-security') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                <span>Security & Admin URL</span>
                            </a>
                            <a href="settings-permalinks.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'settings-permalinks') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                <span>Permalinks</span>
                            </a>
                        </div>
                    </div>

                    <!-- Security & Administration Dropdown -->
                    <div>
                        <button type="button" onclick="toggleSecurity()" class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition <?php echo preg_match('/users|roles|activity-logs|login-logs|database-backup/', $_SERVER['PHP_SELF']) ? 'bg-blue-50/50 text-blue-600 font-bold' : ''; ?>">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-shield-halved w-4 text-center"></i>
                                <span>Admin & Security</span>
                            </div>
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" id="securityArrow"></i>
                        </button>
                        <div class="settings-sub ml-3 border-l-2 border-blue-100 pl-2 space-y-0.5 mt-1" id="securitySub">
                            <a href="users.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'users') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                <span>Admin Users</span>
                            </a>
                            <a href="roles.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'roles') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                <span>Roles & Permissions</span>
                            </a>
                            <a href="activity-logs.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'activity-logs') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                <span>Activity Logs</span>
                            </a>
                            <a href="login-logs.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'login-logs') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                <span>Login History</span>
                            </a>
                            <a href="database-backup.php" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition <?php echo strpos($_SERVER['PHP_SELF'], 'database-backup') !== false ? 'sub-item-active' : ''; ?>">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                <span>Database Backup</span>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </nav>
        </div>

        <!-- Sidebar Bottom Footer -->
        <div class="p-3 border-t border-gray-100 bg-gray-50/50 space-y-1">
            <a href="profile.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition text-xs font-semibold <?php echo strpos($_SERVER['PHP_SELF'], 'profile') !== false ? 'bg-blue-50 text-blue-600 font-bold' : ''; ?>">
                <i class="fa-solid fa-user-gear w-4 text-center"></i>
                <span>My Profile</span>
            </a>
            <a href="/" target="_blank" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-gray-500 hover:bg-gray-100 transition text-xs font-semibold">
                <i class="fa-solid fa-arrow-up-right-from-square w-4 text-center"></i>
                <span>Visit Live Website</span>
            </a>
        </div>

    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 p-3 sm:p-4 md:p-6 min-w-0 max-w-full overflow-x-hidden">

    <script>
        function toggleSidebar() {
            var sidebar = document.getElementById('adminSidebar');
            var overlay = document.getElementById('sidebarOverlay');
            if (sidebar) sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('open');
        }

        function toggleSettings() {
            var sub = document.getElementById('settingsSub');
            var arrow = document.getElementById('settingsArrow');
            if (!sub) return;
            sub.classList.toggle('open');
            if (arrow) arrow.style.transform = sub.classList.contains('open') ? 'rotate(180deg)' : '';
        }

        function toggleSecurity() {
            var sub = document.getElementById('securitySub');
            var arrow = document.getElementById('securityArrow');
            if (!sub) return;
            sub.classList.toggle('open');
            if (arrow) arrow.style.transform = sub.classList.contains('open') ? 'rotate(180deg)' : '';
        }

        function toggleBlog() {
            var sub = document.getElementById('blogSub');
            var arrow = document.getElementById('blogArrow');
            if (!sub) return;
            sub.classList.toggle('open');
            if (arrow) arrow.style.transform = sub.classList.contains('open') ? 'rotate(180deg)' : '';
        }

        <?php if (strpos($_SERVER['PHP_SELF'], 'setting') !== false || strpos($_SERVER['PHP_SELF'], 'smtp') !== false): ?>
        document.addEventListener('DOMContentLoaded', function() {
            var s = document.getElementById('settingsSub');
            var a = document.getElementById('settingsArrow');
            if (s) s.classList.add('open');
            if (a) a.style.transform = 'rotate(180deg)';
        });
        <?php endif; ?>

        <?php if (preg_match('/users|roles|activity-logs|login-logs|database-backup/', $_SERVER['PHP_SELF'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            var s = document.getElementById('securitySub');
            var a = document.getElementById('securityArrow');
            if (s) s.classList.add('open');
            if (a) a.style.transform = 'rotate(180deg)';
        });
        <?php endif; ?>

        <?php if (strpos($_SERVER['PHP_SELF'], 'blog') !== false): ?>
        document.addEventListener('DOMContentLoaded', function() {
            var s = document.getElementById('blogSub');
            var a = document.getElementById('blogArrow');
            if (s) s.classList.add('open');
            if (a) a.style.transform = 'rotate(180deg)';
        });
        <?php endif; ?>
    </script>
