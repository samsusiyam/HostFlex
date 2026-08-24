<?php
$page_title = 'Roles & Permissions';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminRole(['admin']);

$msg = '';
$msg_type = 'success';
$perm_key = 'admin_permissions';

$roles = ['admin', 'manager', 'editor'];
$role_meta = [
    'admin' => [
        'title' => 'Administrator',
        'badge' => 'Super Admin',
        'badge_color' => 'bg-purple-100 text-purple-700 border-purple-200',
        'icon' => 'fa-shield-halved',
        'icon_color' => 'text-purple-600',
        'bg_color' => 'bg-purple-50/50',
        'desc' => 'Has unrestricted, full access to all system modules, settings, users, and backups.'
    ],
    'manager' => [
        'title' => 'Manager',
        'badge' => 'Operations',
        'badge_color' => 'bg-blue-100 text-blue-700 border-blue-200',
        'icon' => 'fa-user-tie',
        'icon_color' => 'text-blue-600',
        'bg_color' => 'bg-blue-50/50',
        'desc' => 'Manages hosting packages, promotions, customer inquiries, and operational workflows.'
    ],
    'editor' => [
        'title' => 'Editor',
        'badge' => 'Content & Media',
        'badge_color' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
        'icon' => 'fa-pen-nib',
        'icon_color' => 'text-emerald-600',
        'bg_color' => 'bg-emerald-50/50',
        'desc' => 'Creates, edits, and maintains blog posts, CMS pages, testimonials, and FAQs.'
    ]
];

$actions = ['view', 'create', 'edit', 'delete'];
$action_meta = [
    'view' => ['label' => 'View', 'icon' => 'fa-eye', 'color' => 'text-sky-600', 'bg' => 'hover:bg-sky-50'],
    'create' => ['label' => 'Create', 'icon' => 'fa-plus', 'color' => 'text-emerald-600', 'bg' => 'hover:bg-emerald-50'],
    'edit' => ['label' => 'Edit', 'icon' => 'fa-pen', 'color' => 'text-amber-600', 'bg' => 'hover:bg-amber-50'],
    'delete' => ['label' => 'Delete', 'icon' => 'fa-trash-can', 'color' => 'text-rose-600', 'bg' => 'hover:bg-rose-50']
];

$modules_by_group = [
    'Core & Administration' => [
        'icon' => 'fa-gear',
        'items' => [
            'dashboard' => ['label' => 'Dashboard', 'desc' => 'Overview statistics, charts, and system status', 'icon' => 'fa-chart-pie'],
            'settings' => ['label' => 'General Settings', 'desc' => 'Site branding, SEO, WHMCS, API keys & custom code', 'icon' => 'fa-sliders'],
            'users' => ['label' => 'Admin Users', 'desc' => 'Manage administrators, editors, and login credentials', 'icon' => 'fa-users-gear'],
            'roles' => ['label' => 'Roles & Permissions', 'desc' => 'Configure access matrices for user roles', 'icon' => 'fa-id-badge'],
            'logs' => ['label' => 'Activity Logs', 'desc' => 'Audit trails of logins and admin actions', 'icon' => 'fa-clock-rotate-left'],
            'backup' => ['label' => 'Database Backup', 'desc' => 'Export and download complete database SQL dumps', 'icon' => 'fa-database']
        ]
    ],
    'Content & Marketing' => [
        'icon' => 'fa-pen-to-square',
        'items' => [
            'blog' => ['label' => 'Blog Articles & Categories', 'desc' => 'Write, edit, and publish blog posts and categories', 'icon' => 'fa-newspaper'],
            'pages' => ['label' => 'CMS Custom Pages', 'desc' => 'Create rich landing and informational pages', 'icon' => 'fa-file-lines'],
            'menus' => ['label' => 'Navigation Menus', 'desc' => 'Configure header and footer menu structures', 'icon' => 'fa-bars-staggered'],
            'offers' => ['label' => 'Deals & Promo Offers', 'desc' => 'Manage limited-time special deals and badges', 'icon' => 'fa-tags'],
            'testimonials' => ['label' => 'Customer Reviews', 'desc' => 'Testimonials, ratings, and client feedback', 'icon' => 'fa-comments'],
            'faqs' => ['label' => 'FAQs Management', 'desc' => 'Frequently asked questions and answers', 'icon' => 'fa-circle-question'],
            'partners' => ['label' => 'Partners & Brands', 'desc' => 'Client logos and affiliated partner badges', 'icon' => 'fa-handshake']
        ]
    ],
    'Services & Inquiries' => [
        'icon' => 'fa-server',
        'items' => [
            'plans' => ['label' => 'Hosting Plans', 'desc' => 'Pricing packages, server specs, and purchase links', 'icon' => 'fa-server'],
            'categories' => ['label' => 'Service Categories', 'desc' => 'Hosting categories (Shared, VPS, Dedicated, etc.)', 'icon' => 'fa-folder-tree'],
            'contacts' => ['label' => 'Contact Inquiries', 'desc' => 'Messages sent from contact and support forms', 'icon' => 'fa-envelope'],
            'subscribers' => ['label' => 'Newsletter Subscribers', 'desc' => 'Email subscriber list and mass newsletter sender', 'icon' => 'fa-paper-plane']
        ]
    ]
];

// Flatten sections list
$all_sections = [];
foreach ($modules_by_group as $grp => $info) {
    foreach ($info['items'] as $sec => $meta) {
        $all_sections[$sec] = $meta;
    }
}

// Handle Save Permissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_perms'])) {
    $perm_data = [];

    foreach ($roles as $role) {
        $perm_data[$role] = [];
        foreach (array_keys($all_sections) as $sec) {
            foreach ($actions as $act) {
                if ($role === 'admin') {
                    $perm_data[$role][$sec][$act] = 1;
                } else {
                    $key = "perm_{$role}_{$sec}_{$act}";
                    $perm_data[$role][$sec][$act] = isset($_POST[$key]) ? 1 : 0;
                }
            }
        }
    }

    $json = mysqli_real_escape_string($conn, json_encode($perm_data));
    $check = mysqli_query($conn, "SELECT id FROM settings WHERE setting_key='$perm_key'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE settings SET setting_value='$json' WHERE setting_key='$perm_key'");
    } else {
        mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('$perm_key', '$json')");
    }
    logActivity('Updated Roles & Permissions', 'Modified role access controls');
    $msg = 'Roles and permissions saved successfully!';
}

$raw = getSetting($perm_key);
$permissions = $raw ? json_decode($raw, true) : [];

// Ensure default structure
foreach ($roles as $role) {
    if (!isset($permissions[$role])) $permissions[$role] = [];
    foreach (array_keys($all_sections) as $sec) {
        if (!isset($permissions[$role][$sec])) $permissions[$role][$sec] = [];
        foreach ($actions as $act) {
            if ($role === 'admin') {
                $permissions[$role][$sec][$act] = 1;
            } elseif (!isset($permissions[$role][$sec][$act])) {
                $permissions[$role][$sec][$act] = 0;
            }
        }
    }
}

// Calculate permission counts
$role_counts = [];
foreach ($roles as $role) {
    $count = 0;
    $total_possible = count($all_sections) * count($actions);
    foreach (array_keys($all_sections) as $sec) {
        foreach ($actions as $act) {
            if (!empty($permissions[$role][$sec][$act])) $count++;
        }
    }
    $role_counts[$role] = [
        'count' => $count,
        'total' => $total_possible,
        'pct' => round(($count / $total_possible) * 100)
    ];
}
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-shield-halved"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Roles & Permissions</h1>
            </div>
            <p class="text-xs text-gray-500">Fine-tune system capabilities, access restrictions, and administrative privileges for each user role.</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="submitPermissionsForm()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs cursor-pointer">
                <i class="fa-solid fa-floppy-disk"></i> Save Permissions
            </button>
        </div>
    </div>

    <!-- Alert Message -->
    <?php if ($msg): ?>
    <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between bg-emerald-50 text-emerald-800 border border-emerald-200 animate-in fade-in">
        <div class="flex items-center gap-2.5">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span><?php echo htmlspecialchars($msg); ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 cursor-pointer"><i class="fa-solid fa-xmark text-sm"></i></button>
    </div>
    <?php endif; ?>

    <!-- Role Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php foreach ($roles as $role): 
            $meta = $role_meta[$role];
            $stat = $role_counts[$role];
        ?>
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-xs relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl <?php echo $meta['bg_color']; ?> <?php echo $meta['icon_color']; ?> flex items-center justify-center text-lg border border-gray-100">
                        <i class="fa-solid <?php echo $meta['icon']; ?>"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-sm"><?php echo $meta['title']; ?></h3>
                        <span class="inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-full border <?php echo $meta['badge_color']; ?> mt-0.5">
                            <?php echo $meta['badge']; ?>
                        </span>
                    </div>
                </div>
            </div>
            <p class="text-xs text-gray-500 leading-relaxed mb-4"><?php echo $meta['desc']; ?></p>
            <div class="pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                <span class="text-gray-500 font-medium">Access Level:</span>
                <?php if ($role === 'admin'): ?>
                <span class="font-bold text-purple-700 flex items-center gap-1"><i class="fa-solid fa-infinity text-[10px]"></i> 100% (Unrestricted)</span>
                <?php else: ?>
                <span class="font-bold text-gray-800"><?php echo $stat['count']; ?> / <?php echo $stat['total']; ?> (<?php echo $stat['pct']; ?>%)</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Main Configuration Form -->
    <form method="POST" id="permissionsForm">
        <input type="hidden" name="save_perms" value="1">

        <!-- Tabs and Filter Toolbar -->
        <div class="bg-white p-4 rounded-2xl border border-gray-200/80 shadow-xs mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
            
            <!-- View Selector Tabs -->
            <div class="flex items-center bg-gray-100 p-1 rounded-xl w-full md:w-auto">
                <button type="button" onclick="switchView('manager')" id="tabBtn_manager" class="tab-btn active px-4 py-2 rounded-lg text-xs font-bold transition cursor-pointer flex items-center gap-2 bg-white text-blue-700 shadow-xs">
                    <i class="fa-solid fa-user-tie text-blue-600"></i> Manager Role
                </button>
                <button type="button" onclick="switchView('editor')" id="tabBtn_editor" class="tab-btn px-4 py-2 rounded-lg text-xs font-bold transition cursor-pointer flex items-center gap-2 text-gray-600 hover:text-gray-900">
                    <i class="fa-solid fa-pen-nib text-emerald-600"></i> Editor Role
                </button>
                <button type="button" onclick="switchView('matrix')" id="tabBtn_matrix" class="tab-btn px-4 py-2 rounded-lg text-xs font-bold transition cursor-pointer flex items-center gap-2 text-gray-600 hover:text-gray-900">
                    <i class="fa-solid fa-table-cells text-purple-600"></i> Full Matrix Table
                </button>
            </div>

            <!-- Real-Time Search & Quick Preset Filters -->
            <div class="flex items-center gap-2 w-full md:w-auto">
                <div class="relative flex-1 md:w-64">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" id="moduleSearchInput" onkeyup="filterModules(this.value)" placeholder="Search modules..." class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-8 pr-3 py-1.5 text-xs text-gray-800 focus:bg-white focus:outline-none focus:border-blue-600 transition">
                </div>
            </div>
        </div>

        <!-- 1. MANAGER TAB VIEW -->
        <div id="view_manager" class="view-panel space-y-6">
            <!-- Manager Preset Bar -->
            <div class="bg-blue-50/60 border border-blue-100 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2 text-blue-900 font-semibold">
                    <i class="fa-solid fa-wand-magic-sparkles text-blue-600"></i>
                    <span>Quick Presets for <strong>Manager</strong>:</span>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" onclick="applyPreset('manager', 'all')" class="bg-white hover:bg-blue-600 hover:text-white text-blue-700 border border-blue-200 px-3 py-1.5 rounded-lg font-bold transition shadow-xs cursor-pointer">Grant All</button>
                    <button type="button" onclick="applyPreset('manager', 'view_only')" class="bg-white hover:bg-sky-600 hover:text-white text-sky-700 border border-sky-200 px-3 py-1.5 rounded-lg font-bold transition shadow-xs cursor-pointer">View Only</button>
                    <button type="button" onclick="applyPreset('manager', 'operations')" class="bg-white hover:bg-emerald-600 hover:text-white text-emerald-700 border border-emerald-200 px-3 py-1.5 rounded-lg font-bold transition shadow-xs cursor-pointer">Operations Preset</button>
                    <button type="button" onclick="applyPreset('manager', 'none')" class="bg-white hover:bg-rose-600 hover:text-white text-rose-700 border border-rose-200 px-3 py-1.5 rounded-lg font-bold transition shadow-xs cursor-pointer">Revoke All</button>
                </div>
            </div>

            <!-- Grouped Modules for Manager -->
            <?php foreach ($modules_by_group as $group_name => $grp): ?>
            <div class="module-group-card bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                <div class="bg-gray-50/80 px-6 py-3.5 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-bold text-xs text-gray-800 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid <?php echo $grp['icon']; ?> text-blue-600"></i> <?php echo $group_name; ?>
                    </h3>
                    <button type="button" onclick="toggleGroupRow('manager', this)" class="text-[11px] font-bold text-blue-600 hover:text-blue-800 cursor-pointer">
                        Toggle All in Section
                    </button>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php foreach ($grp['items'] as $sec => $meta): ?>
                    <div class="module-row p-4 sm:px-6 hover:bg-blue-50/20 transition flex flex-col md:flex-row md:items-center justify-between gap-4" data-name="<?php echo strtolower($meta['label'] . ' ' . $meta['desc']); ?>">
                        <div class="flex items-start gap-3.5 max-w-md">
                            <div class="w-9 h-9 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center text-sm shrink-0 mt-0.5">
                                <i class="fa-solid <?php echo $meta['icon']; ?>"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-gray-900"><?php echo $meta['label']; ?></h4>
                                <p class="text-xs text-gray-400 mt-0.5"><?php echo $meta['desc']; ?></p>
                            </div>
                        </div>

                        <!-- 4 Action Toggles -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <?php foreach ($actions as $act): 
                                $checked = !empty($permissions['manager'][$sec][$act]);
                                $act_info = $action_meta[$act];
                            ?>
                            <label class="perm-pill-label inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold cursor-pointer select-none transition <?php echo $checked ? 'bg-blue-50 border-blue-300 text-blue-700 shadow-xs' : 'bg-gray-50 border-gray-200 text-gray-500 hover:bg-gray-100'; ?>">
                                <input type="checkbox" name="perm_manager_<?php echo $sec; ?>_<?php echo $act; ?>" value="1" <?php echo $checked ? 'checked' : ''; ?> class="sr-only perm-checkbox" data-role="manager" data-section="<?php echo $sec; ?>" data-action="<?php echo $act; ?>" onchange="updatePillState(this)">
                                <i class="fa-solid <?php echo $act_info['icon']; ?> text-[11px] <?php echo $checked ? $act_info['color'] : 'text-gray-400'; ?>"></i>
                                <span><?php echo $act_info['label']; ?></span>
                            </label>
                            <?php endforeach; ?>
                            <button type="button" onclick="toggleRowActions('manager', '<?php echo $sec; ?>')" class="text-gray-400 hover:text-gray-600 p-1.5 rounded text-xs ml-1 cursor-pointer" title="Toggle all actions for this module">
                                <i class="fa-solid fa-check-double"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- 2. EDITOR TAB VIEW -->
        <div id="view_editor" class="view-panel space-y-6 hidden">
            <!-- Editor Preset Bar -->
            <div class="bg-emerald-50/60 border border-emerald-100 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2 text-emerald-900 font-semibold">
                    <i class="fa-solid fa-wand-magic-sparkles text-emerald-600"></i>
                    <span>Quick Presets for <strong>Editor</strong>:</span>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" onclick="applyPreset('editor', 'all')" class="bg-white hover:bg-emerald-600 hover:text-white text-emerald-700 border border-emerald-200 px-3 py-1.5 rounded-lg font-bold transition shadow-xs cursor-pointer">Grant All</button>
                    <button type="button" onclick="applyPreset('editor', 'content_creator')" class="bg-white hover:bg-emerald-600 hover:text-white text-emerald-700 border border-emerald-200 px-3 py-1.5 rounded-lg font-bold transition shadow-xs cursor-pointer">Content Creator (Recommended)</button>
                    <button type="button" onclick="applyPreset('editor', 'view_only')" class="bg-white hover:bg-sky-600 hover:text-white text-sky-700 border border-sky-200 px-3 py-1.5 rounded-lg font-bold transition shadow-xs cursor-pointer">View Only</button>
                    <button type="button" onclick="applyPreset('editor', 'none')" class="bg-white hover:bg-rose-600 hover:text-white text-rose-700 border border-rose-200 px-3 py-1.5 rounded-lg font-bold transition shadow-xs cursor-pointer">Revoke All</button>
                </div>
            </div>

            <!-- Grouped Modules for Editor -->
            <?php foreach ($modules_by_group as $group_name => $grp): ?>
            <div class="module-group-card bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                <div class="bg-gray-50/80 px-6 py-3.5 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-bold text-xs text-gray-800 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid <?php echo $grp['icon']; ?> text-emerald-600"></i> <?php echo $group_name; ?>
                    </h3>
                    <button type="button" onclick="toggleGroupRow('editor', this)" class="text-[11px] font-bold text-emerald-600 hover:text-emerald-800 cursor-pointer">
                        Toggle All in Section
                    </button>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php foreach ($grp['items'] as $sec => $meta): ?>
                    <div class="module-row p-4 sm:px-6 hover:bg-emerald-50/20 transition flex flex-col md:flex-row md:items-center justify-between gap-4" data-name="<?php echo strtolower($meta['label'] . ' ' . $meta['desc']); ?>">
                        <div class="flex items-start gap-3.5 max-w-md">
                            <div class="w-9 h-9 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center text-sm shrink-0 mt-0.5">
                                <i class="fa-solid <?php echo $meta['icon']; ?>"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-gray-900"><?php echo $meta['label']; ?></h4>
                                <p class="text-xs text-gray-400 mt-0.5"><?php echo $meta['desc']; ?></p>
                            </div>
                        </div>

                        <!-- 4 Action Toggles -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <?php foreach ($actions as $act): 
                                $checked = !empty($permissions['editor'][$sec][$act]);
                                $act_info = $action_meta[$act];
                            ?>
                            <label class="perm-pill-label inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold cursor-pointer select-none transition <?php echo $checked ? 'bg-emerald-50 border-emerald-300 text-emerald-700 shadow-xs' : 'bg-gray-50 border-gray-200 text-gray-500 hover:bg-gray-100'; ?>">
                                <input type="checkbox" name="perm_editor_<?php echo $sec; ?>_<?php echo $act; ?>" value="1" <?php echo $checked ? 'checked' : ''; ?> class="sr-only perm-checkbox" data-role="editor" data-section="<?php echo $sec; ?>" data-action="<?php echo $act; ?>" onchange="updatePillState(this)">
                                <i class="fa-solid <?php echo $act_info['icon']; ?> text-[11px] <?php echo $checked ? $act_info['color'] : 'text-gray-400'; ?>"></i>
                                <span><?php echo $act_info['label']; ?></span>
                            </label>
                            <?php endforeach; ?>
                            <button type="button" onclick="toggleRowActions('editor', '<?php echo $sec; ?>')" class="text-gray-400 hover:text-gray-600 p-1.5 rounded text-xs ml-1 cursor-pointer" title="Toggle all actions for this module">
                                <i class="fa-solid fa-check-double"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- 3. FULL MATRIX TABLE VIEW -->
        <div id="view_matrix" class="view-panel space-y-6 hidden">
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50/80 border-b border-gray-200 text-xs font-bold text-gray-700">
                            <tr>
                                <th class="px-6 py-4 w-72 uppercase tracking-wider">Module / Section</th>
                                <th class="px-6 py-4 text-center bg-purple-50/50 text-purple-900 border-l border-r border-gray-200 w-44">
                                    <div class="flex items-center justify-center gap-1.5 font-extrabold">
                                        <i class="fa-solid fa-shield-halved text-purple-600"></i> Administrator
                                    </div>
                                    <span class="text-[10px] font-medium text-purple-600 block mt-0.5">Full System Access</span>
                                </th>
                                <th class="px-6 py-4 text-center bg-blue-50/50 text-blue-900 border-r border-gray-200">
                                    <div class="flex items-center justify-center gap-1.5 font-extrabold">
                                        <i class="fa-solid fa-user-tie text-blue-600"></i> Manager
                                    </div>
                                    <div class="flex items-center justify-center gap-2 mt-1 text-[10px] font-bold text-gray-400">
                                        <span class="w-6">V</span>
                                        <span class="w-6">C</span>
                                        <span class="w-6">E</span>
                                        <span class="w-6">D</span>
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-center bg-emerald-50/50 text-emerald-900">
                                    <div class="flex items-center justify-center gap-1.5 font-extrabold">
                                        <i class="fa-solid fa-pen-nib text-emerald-600"></i> Editor
                                    </div>
                                    <div class="flex items-center justify-center gap-2 mt-1 text-[10px] font-bold text-gray-400">
                                        <span class="w-6">V</span>
                                        <span class="w-6">C</span>
                                        <span class="w-6">E</span>
                                        <span class="w-6">D</span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            <?php foreach ($all_sections as $sec => $meta): ?>
                            <tr class="hover:bg-gray-50/50 transition matrix-row" data-name="<?php echo strtolower($meta['label']); ?>">
                                <td class="px-6 py-3.5 font-semibold text-gray-900">
                                    <div class="flex items-center gap-2.5">
                                        <i class="fa-solid <?php echo $meta['icon']; ?> text-gray-400 text-xs w-4"></i>
                                        <span><?php echo $meta['label']; ?></span>
                                    </div>
                                </td>
                                
                                <!-- Administrator (Locked) -->
                                <td class="px-6 py-3.5 text-center bg-purple-50/20 border-l border-r border-gray-100">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-100 text-purple-700">
                                        <i class="fa-solid fa-check text-[9px]"></i> Full
                                    </span>
                                </td>

                                <!-- Manager Actions -->
                                <td class="px-6 py-3.5 text-center bg-blue-50/10 border-r border-gray-100">
                                    <div class="flex items-center justify-center gap-2">
                                        <?php foreach ($actions as $act): 
                                            $checked = !empty($permissions['manager'][$sec][$act]);
                                        ?>
                                        <input type="checkbox" name="perm_manager_<?php echo $sec; ?>_<?php echo $act; ?>" value="1" <?php echo $checked ? 'checked' : ''; ?> class="rounded text-blue-600 focus:ring-blue-500 cursor-pointer w-4 h-4 perm-checkbox" data-role="manager" data-section="<?php echo $sec; ?>" data-action="<?php echo $act; ?>" title="Manager: <?php echo ucfirst($act); ?> <?php echo $meta['label']; ?>" onchange="syncFromMatrix('manager', '<?php echo $sec; ?>', '<?php echo $act; ?>', this.checked)">
                                        <?php endforeach; ?>
                                    </div>
                                </td>

                                <!-- Editor Actions -->
                                <td class="px-6 py-3.5 text-center bg-emerald-50/10">
                                    <div class="flex items-center justify-center gap-2">
                                        <?php foreach ($actions as $act): 
                                            $checked = !empty($permissions['editor'][$sec][$act]);
                                        ?>
                                        <input type="checkbox" name="perm_editor_<?php echo $sec; ?>_<?php echo $act; ?>" value="1" <?php echo $checked ? 'checked' : ''; ?> class="rounded text-emerald-600 focus:ring-emerald-500 cursor-pointer w-4 h-4 perm-checkbox" data-role="editor" data-section="<?php echo $sec; ?>" data-action="<?php echo $act; ?>" title="Editor: <?php echo ucfirst($act); ?> <?php echo $meta['label']; ?>" onchange="syncFromMatrix('editor', '<?php echo $sec; ?>', '<?php echo $act; ?>', this.checked)">
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Floating / Bottom Save Bar -->
        <div class="sticky bottom-6 mt-8 bg-gray-900/90 backdrop-blur-md text-white p-4 rounded-2xl shadow-xl flex items-center justify-between gap-4 border border-gray-700/50 z-40">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-shield"></i>
                </div>
                <div class="hidden sm:block">
                    <h4 class="text-xs font-bold text-white">Unsaved Changes Protection</h4>
                    <p class="text-[11px] text-gray-400">Make sure to click save after updating role permissions.</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button type="submit" name="save_perms" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-blue-500/20 cursor-pointer">
                    <i class="fa-solid fa-floppy-disk"></i> Save Permissions
                </button>
            </div>
        </div>

    </form>

</div>

<script>
function switchView(tab) {
    document.querySelectorAll('.view-panel').forEach(p => p.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('active', 'bg-white', 'text-blue-700', 'shadow-xs');
        b.classList.add('text-gray-600');
    });

    var targetView = document.getElementById('view_' + tab);
    var targetBtn = document.getElementById('tabBtn_' + tab);

    if (targetView && targetBtn) {
        targetView.classList.remove('hidden');
        targetBtn.classList.add('active', 'bg-white', 'text-blue-700', 'shadow-xs');
        targetBtn.classList.remove('text-gray-600');
    }
}

function updatePillState(checkbox) {
    var label = checkbox.closest('.perm-pill-label');
    var icon = label.querySelector('i');
    var role = checkbox.dataset.role;
    var act = checkbox.dataset.action;
    var isChecked = checkbox.checked;

    if (isChecked) {
        label.classList.remove('bg-gray-50', 'border-gray-200', 'text-gray-500');
        if (role === 'editor') {
            label.classList.add('bg-emerald-50', 'border-emerald-300', 'text-emerald-700', 'shadow-xs');
        } else {
            label.classList.add('bg-blue-50', 'border-blue-300', 'text-blue-700', 'shadow-xs');
        }
    } else {
        label.classList.remove('bg-blue-50', 'border-blue-300', 'text-blue-700', 'bg-emerald-50', 'border-emerald-300', 'text-emerald-700', 'shadow-xs');
        label.classList.add('bg-gray-50', 'border-gray-200', 'text-gray-500');
    }

    // Sync with corresponding matrix input
    var matrixInput = document.querySelector(`.matrix-row input[name="${checkbox.name}"]`);
    if (matrixInput) {
        matrixInput.checked = isChecked;
    }
}

function syncFromMatrix(role, sec, act, isChecked) {
    var tabCheckbox = document.querySelector(`#view_${role} input[name="perm_${role}_${sec}_${act}"]`);
    if (tabCheckbox) {
        tabCheckbox.checked = isChecked;
        updatePillState(tabCheckbox);
    }
}

function toggleRowActions(role, sec) {
    var cbs = document.querySelectorAll(`#view_${role} input[data-section="${sec}"]`);
    var allChecked = Array.from(cbs).every(c => c.checked);
    cbs.forEach(cb => {
        cb.checked = !allChecked;
        updatePillState(cb);
    });
}

function toggleGroupRow(role, btn) {
    var groupCard = btn.closest('.module-group-card');
    var cbs = groupCard.querySelectorAll(`input[data-role="${role}"]`);
    var allChecked = Array.from(cbs).every(c => c.checked);
    cbs.forEach(cb => {
        cb.checked = !allChecked;
        updatePillState(cb);
    });
}

function applyPreset(role, preset) {
    var cbs = document.querySelectorAll(`#view_${role} input[data-role="${role}"]`);
    
    if (preset === 'all') {
        cbs.forEach(cb => { cb.checked = true; updatePillState(cb); });
    } else if (preset === 'none') {
        cbs.forEach(cb => { cb.checked = false; updatePillState(cb); });
    } else if (preset === 'view_only') {
        cbs.forEach(cb => {
            cb.checked = (cb.dataset.action === 'view');
            updatePillState(cb);
        });
    } else if (preset === 'content_creator') {
        var contentSections = ['blog', 'pages', 'testimonials', 'faqs', 'partners', 'menus', 'offers'];
        cbs.forEach(cb => {
            var sec = cb.dataset.section;
            if (contentSections.includes(sec)) {
                cb.checked = true;
            } else if (cb.dataset.action === 'view') {
                cb.checked = true;
            } else {
                cb.checked = false;
            }
            updatePillState(cb);
        });
    } else if (preset === 'operations') {
        var opSections = ['dashboard', 'plans', 'categories', 'offers', 'contacts', 'subscribers', 'blog', 'pages', 'testimonials', 'faqs'];
        cbs.forEach(cb => {
            var sec = cb.dataset.section;
            if (opSections.includes(sec)) {
                cb.checked = true;
            } else if (cb.dataset.action === 'view') {
                cb.checked = true;
            } else {
                cb.checked = false;
            }
            updatePillState(cb);
        });
    }
}

function filterModules(q) {
    q = q.trim().toLowerCase();
    
    // Filter in Tab Views
    document.querySelectorAll('.module-row').forEach(row => {
        var name = row.getAttribute('data-name') || '';
        if (!q || name.includes(q)) {
            row.style.display = 'flex';
        } else {
            row.style.display = 'none';
        }
    });

    // Filter in Matrix View
    document.querySelectorAll('.matrix-row').forEach(row => {
        var name = row.getAttribute('data-name') || '';
        if (!q || name.includes(q)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function submitPermissionsForm() {
    document.getElementById('permissionsForm').submit();
}
</script>

<?php include 'footer.php'; ?>
