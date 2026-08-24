<?php
$page_title = 'Hosting Plans';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

$error = '';
$success = '';

// Handle AJAX Quick Toggle Status & Popular
if (isset($_POST['ajax_toggle_field'])) {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    $field = $_POST['field'] ?? '';
    if ($id > 0 && in_array($field, ['status', 'is_popular'])) {
        $curr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT $field, name FROM hosting_plans WHERE id = $id"));
        if ($curr) {
            $new_val = (int)$curr[$field] === 1 ? 0 : 1;
            mysqli_query($conn, "UPDATE hosting_plans SET $field = $new_val WHERE id = $id");
            logActivity('Toggled Plan ' . ucfirst($field), ($curr['name'] ?? 'Plan') . " -> $new_val (ID: $id)");
            echo json_encode(['success' => true, 'new_val' => $new_val]);
            exit;
        }
    }
    echo json_encode(['success' => false]);
    exit;
}

// Handle Delete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_plan_id'])) {
    $id = (int)$_POST['delete_plan_id'];
    $del = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM hosting_plans WHERE id = $id"));
    if (mysqli_query($conn, "DELETE FROM hosting_plans WHERE id = $id")) {
        logActivity('Deleted Plan', ($del['name'] ?? 'Unknown') . ' (ID: ' . $id . ')');
        $success = 'Hosting plan deleted successfully.';
    } else {
        $error = 'Failed to delete plan: ' . mysqli_error($conn);
    }
}

// Handle Add / Edit via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_plan'])) {
    $category = sanitize(trim($_POST['category'] ?? ''));
    $name = sanitize(trim($_POST['name'] ?? ''));
    $subtitle = sanitize(trim($_POST['subtitle'] ?? ''));
    $badge = sanitize(trim($_POST['badge'] ?? ''));
    $monthly_price = (float)($_POST['monthly_price'] ?? 0);
    $yearly_price = (float)($_POST['yearly_price'] ?? 0);
    $features_lines = array_values(array_filter(array_map('trim', explode("\n", $_POST['features'] ?? ''))));
    $features = json_encode($features_lines, JSON_UNESCAPED_UNICODE);
    $order_url = trim($_POST['order_url'] ?? '');
    $is_popular = isset($_POST['is_popular']) ? 1 : 0;
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $status = isset($_POST['status']) ? 1 : 0;

    if (isset($_POST['plan_id']) && !empty($_POST['plan_id'])) {
        $id = (int)$_POST['plan_id'];
        $stmt = mysqli_prepare($conn, "UPDATE hosting_plans SET category=?, name=?, subtitle=?, badge=?, monthly_price=?, yearly_price=?, features=?, order_url=?, is_popular=?, sort_order=?, status=? WHERE id=?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssssddssiiii", $category, $name, $subtitle, $badge, $monthly_price, $yearly_price, $features, $order_url, $is_popular, $sort_order, $status, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            logActivity('Updated Plan', $name . ' (ID: ' . $id . ')');
            $success = 'Hosting plan "' . htmlspecialchars($name) . '" updated successfully!';
        } else {
            $error = 'Database error: ' . mysqli_error($conn);
        }
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO hosting_plans (category, name, subtitle, badge, monthly_price, yearly_price, features, order_url, is_popular, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssssddssiii", $category, $name, $subtitle, $badge, $monthly_price, $yearly_price, $features, $order_url, $is_popular, $sort_order, $status);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            logActivity('Created Plan', $name);
            $success = 'New hosting plan "' . htmlspecialchars($name) . '" added successfully!';
        } else {
            $error = 'Database error: ' . mysqli_error($conn);
        }
    }
}

$plans = mysqli_query($conn, "SELECT * FROM hosting_plans ORDER BY category, sort_order ASC");
$categories = getCategories(false);
$cat_lookup = [];
mysqli_data_seek($categories, 0);
while ($cat = mysqli_fetch_assoc($categories)) {
    $cat_lookup[$cat['slug']] = $cat['name'];
}

$plans_by_cat = [];
$total_plans = 0;
mysqli_data_seek($plans, 0);
while ($plan = mysqli_fetch_assoc($plans)) {
    $plans_by_cat[$plan['category']][] = $plan;
    $total_plans++;
}
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-server"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Hosting Plans</h1>
            </div>
            <p class="text-xs text-gray-500">Create, clone, edit, and organize web hosting packages and pricing tiers.</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="openAddPlanModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs cursor-pointer">
                <i class="fa-solid fa-plus"></i> Add New Plan
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($success): ?>
    <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between bg-emerald-50 text-emerald-800 border border-emerald-200">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span><?php echo htmlspecialchars($success); ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 cursor-pointer"><i class="fa-solid fa-xmark text-sm"></i></button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between bg-red-50 text-red-800 border border-red-200">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-red-600 text-sm"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 cursor-pointer"><i class="fa-solid fa-xmark text-sm"></i></button>
    </div>
    <?php endif; ?>

    <!-- Category Filter Bar & Live Search -->
    <div class="bg-white p-4 rounded-2xl border border-gray-200/80 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        
        <!-- Category Tab Buttons -->
        <div class="flex items-center gap-1.5 overflow-x-auto w-full md:w-auto pb-1 md:pb-0 text-xs font-semibold">
            <button type="button" onclick="filterCategoryTab('all', this)" class="cat-tab-btn px-3.5 py-1.5 rounded-xl transition cursor-pointer bg-blue-600 text-white shadow-xs">
                All Categories (<?php echo $total_plans; ?>)
            </button>
            <?php foreach ($cat_lookup as $c_slug => $c_name): 
                $count = count($plans_by_cat[$c_slug] ?? []);
            ?>
            <button type="button" onclick="filterCategoryTab('<?php echo $c_slug; ?>', this)" class="cat-tab-btn px-3.5 py-1.5 rounded-xl transition cursor-pointer text-gray-600 hover:bg-gray-100 whitespace-nowrap">
                <?php echo htmlspecialchars($c_name); ?> <span class="text-[10px] text-gray-400 font-normal">(<?php echo $count; ?>)</span>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Live Search Bar -->
        <div class="relative w-full md:w-64">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
            <input type="text" id="planSearchInput" onkeyup="filterPlanCards(this.value)" placeholder="Search plans..." class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-8 pr-3 py-1.5 text-xs text-gray-800 focus:bg-white focus:outline-none focus:border-blue-600 transition">
        </div>

    </div>

    <!-- Plans Grouped By Category -->
    <?php if (!empty($plans_by_cat)): ?>
    <?php foreach ($plans_by_cat as $cat_slug => $category_plans): 
        $cat_name = $cat_lookup[$cat_slug] ?? ucfirst(str_replace('-', ' ', $cat_slug));
    ?>
    <div class="category-block space-y-4" data-category="<?php echo $cat_slug; ?>">
        
        <div class="flex items-center justify-between border-b border-gray-200 pb-2">
            <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-folder-open text-blue-600"></i>
                <span><?php echo htmlspecialchars($cat_name); ?></span>
                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-normal"><?php echo count($category_plans); ?> plans</span>
            </h2>
            <a href="/category/<?php echo urlencode($cat_slug); ?>" target="_blank" class="text-xs font-semibold text-blue-600 hover:underline flex items-center gap-1">
                <span>View Public Page</span>
                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <?php foreach ($category_plans as $plan): 
                $feats = json_decode($plan['features'] ?? '[]', true) ?: [];
                $plan_json = htmlspecialchars(json_encode($plan), ENT_QUOTES, 'UTF-8');
            ?>
            <div class="plan-card bg-white rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md transition flex flex-col justify-between p-5 relative overflow-hidden" data-name="<?php echo strtolower($plan['name'] . ' ' . $cat_name . ' ' . $plan['subtitle']); ?>">
                
                <!-- Top Badge & Quick Actions -->
                <div>
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <?php if ($plan['badge']): ?>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-600 border border-rose-200">
                                <?php echo htmlspecialchars($plan['badge']); ?>
                            </span>
                            <?php endif; ?>
                            
                            <!-- Quick Popular Star Toggle Button -->
                            <button type="button" onclick="togglePlanField(<?php echo $plan['id']; ?>, 'is_popular', this)" class="p-1 rounded-lg text-xs cursor-pointer transition <?php echo $plan['is_popular'] ? 'text-amber-500 bg-amber-50' : 'text-gray-300 hover:text-amber-400'; ?>" title="Toggle Featured / Popular">
                                <i class="fa-solid fa-star"></i>
                            </button>
                        </div>

                        <!-- Quick Active/Inactive Toggle Badge -->
                        <button type="button" onclick="togglePlanField(<?php echo $plan['id']; ?>, 'status', this)" class="status-badge inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition <?php echo $plan['status'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'; ?>" title="Click to toggle Active/Inactive">
                            <span class="w-1.5 h-1.5 rounded-full <?php echo $plan['status'] ? 'bg-emerald-500' : 'bg-rose-500'; ?>"></span>
                            <span><?php echo $plan['status'] ? 'Active' : 'Inactive'; ?></span>
                        </button>
                    </div>

                    <h3 class="font-bold text-base text-gray-900 mb-0.5"><?php echo htmlspecialchars($plan['name']); ?></h3>
                    <?php if ($plan['subtitle']): ?>
                    <p class="text-xs text-gray-400 mb-3 line-clamp-2"><?php echo htmlspecialchars($plan['subtitle']); ?></p>
                    <?php else: ?>
                    <div class="h-4"></div>
                    <?php endif; ?>

                    <!-- Price Box -->
                    <div class="bg-gray-50/80 p-3 rounded-xl border border-gray-100 mb-4">
                        <div class="flex items-baseline gap-1">
                            <span class="text-xs text-gray-500 font-bold">BDT</span>
                            <span class="text-xl font-extrabold text-gray-900"><?php echo number_format($plan['monthly_price'], 0); ?></span>
                            <span class="text-xs text-gray-400 font-medium">/month</span>
                        </div>
                        <?php if ($plan['yearly_price'] > 0): ?>
                        <div class="text-[11px] text-gray-500 font-semibold mt-0.5">
                            Yearly: <strong>৳<?php echo number_format($plan['yearly_price'], 0); ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Features Snippet -->
                    <ul class="space-y-1.5 text-xs text-gray-600 mb-4">
                        <?php foreach (array_slice($feats, 0, 4) as $f): ?>
                        <li class="flex items-center gap-2 truncate">
                            <i class="fa-solid fa-check text-emerald-500 text-[10px]"></i>
                            <span class="truncate"><?php echo htmlspecialchars($f); ?></span>
                        </li>
                        <?php endforeach; ?>
                        <?php if (count($feats) > 4): ?>
                        <li class="text-[11px] text-blue-600 font-semibold pl-4">
                            +<?php echo count($feats) - 4; ?> more features
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Footer Action Buttons -->
                <div class="pt-3 border-t border-gray-100 flex items-center justify-between gap-2 text-xs">
                    <span class="text-[10px] text-gray-400">Order: <?php echo $plan['sort_order']; ?></span>
                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick='openDuplicatePlanModal(<?php echo $plan_json; ?>)' class="bg-white hover:bg-purple-50 text-purple-600 border border-gray-200 hover:border-purple-200 p-1.5 rounded-lg transition shadow-xs cursor-pointer" title="Duplicate / Clone Plan">
                            <i class="fa-solid fa-clone"></i>
                        </button>
                        <button type="button" onclick='openEditPlanModal(<?php echo $plan_json; ?>)' class="bg-white hover:bg-blue-50 text-blue-600 border border-gray-200 hover:border-blue-200 p-1.5 rounded-lg transition shadow-xs cursor-pointer" title="Edit Plan">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button type="button" onclick="openDeletePlanModal(<?php echo $plan['id']; ?>, '<?php echo addslashes($plan['name']); ?>', '<?php echo number_format($plan['monthly_price'], 0); ?>', '<?php echo addslashes($cat_name); ?>')" class="bg-white hover:bg-red-50 text-red-600 border border-gray-200 hover:border-red-200 p-1.5 rounded-lg transition shadow-xs cursor-pointer" title="Delete Plan">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </div>

            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <?php else: ?>
    <!-- Empty State -->
    <div class="bg-white rounded-2xl border border-gray-200/80 p-16 text-center shadow-xs">
        <div class="w-16 h-16 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl mx-auto mb-4">
            <i class="fa-solid fa-server"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800 mb-1">No Hosting Plans Yet</h3>
        <p class="text-xs text-gray-500 mb-6 max-w-sm mx-auto">Add your first hosting package and pricing tiers for your visitors.</p>
        <button type="button" onclick="openAddPlanModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold transition inline-flex items-center gap-2 shadow-xs cursor-pointer">
            <i class="fa-solid fa-plus"></i> Add First Plan
        </button>
    </div>
    <?php endif; ?>

</div>

<!-- ==========================================
     POPUP MODAL: ADD / EDIT / DUPLICATE PLAN
=============================================== -->
<div id="planModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3 sm:p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl overflow-hidden border border-gray-100 my-auto sm:my-8 animate-in fade-in duration-200 flex flex-col max-h-[90vh]">
        
        <!-- Modal Header -->
        <div class="shrink-0 flex items-center justify-between px-4 sm:px-6 py-3.5 sm:py-4 border-b bg-gray-50/70">
            <div class="flex items-center gap-2">
                <span class="p-2 bg-blue-100 text-blue-700 rounded-lg text-xs" id="planModalIcon"><i class="fa-solid fa-plus"></i></span>
                <h3 class="text-sm font-bold text-gray-900" id="planModalTitle">Add New Hosting Plan</h3>
            </div>
            <button type="button" onclick="closePlanModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg transition cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Modal Form Body -->
        <form method="POST" id="planModalForm" class="flex flex-col flex-1 overflow-hidden">
            <input type="hidden" name="save_plan" value="1">
            <input type="hidden" name="plan_id" id="plan_id" value="">

            <div class="p-4 sm:p-6 space-y-4 text-xs flex-1 overflow-y-auto">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1"><i class="fa-solid fa-folder text-blue-600 mr-1"></i> Category <span class="text-red-500">*</span></label>
                        <select name="category" id="plan_category" required class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                            <?php if ($categories): mysqli_data_seek($categories, 0); while ($cat = mysqli_fetch_assoc($categories)): ?>
                            <option value="<?php echo $cat['slug']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1"><i class="fa-solid fa-tag text-blue-600 mr-1"></i> Plan Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="plan_name" required placeholder="e.g. Starter NVMe" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1"><i class="fa-solid fa-subscript text-gray-400 mr-1"></i> Subtitle / Tagline</label>
                        <input type="text" name="subtitle" id="plan_subtitle" placeholder="e.g. Perfect for personal blogs & portfolios" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1"><i class="fa-solid fa-certificate text-purple-600 mr-1"></i> Badge Text</label>
                        <input type="text" name="badge" id="plan_badge" placeholder="e.g. 50% OFF, BEST VALUE" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-gray-50/60 p-4 rounded-xl border border-gray-200">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1"><i class="fa-solid fa-bangladeshi-taka-sign text-emerald-600 mr-1"></i> Monthly Price (BDT) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" name="monthly_price" id="plan_monthly_price" required placeholder="99.00" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white font-bold">
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1"><i class="fa-solid fa-calendar-days text-emerald-600 mr-1"></i> Yearly Price (BDT)</label>
                        <input type="number" step="0.01" name="yearly_price" id="plan_yearly_price" placeholder="999.00" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1"><i class="fa-solid fa-link text-blue-600 mr-1"></i> WHMCS Order / Purchase URL</label>
                        <input type="url" name="order_url" id="plan_order_url" placeholder="https://my.hostnibo.com/cart.php?a=add&pid=1" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1"><i class="fa-solid fa-arrow-down-1-9 text-gray-400 mr-1"></i> Sort Order</label>
                        <input type="number" name="sort_order" id="plan_sort_order" value="0" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                </div>

                <!-- Features Textarea + Instant Helper Chips -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="font-bold text-gray-700"><i class="fa-solid fa-list-check text-blue-600 mr-1"></i> Package Features (One per line)</label>
                        <span class="text-[11px] text-gray-400">Click chips below to insert</span>
                    </div>
                    <textarea name="features" id="plan_features" rows="5" placeholder="10 GB NVMe Storage&#10;Unlimited Bandwidth&#10;Free SSL Certificate&#10;cPanel Control Panel&#10;24/7 Support" class="w-full border border-gray-300 rounded-xl p-3 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none font-mono"></textarea>
                    
                    <!-- Quick Helper Chips -->
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        <button type="button" onclick="insertFeatureChip('10 GB NVMe SSD Storage')" class="text-[10px] bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-700 px-2 py-0.5 rounded-lg border border-gray-200 transition cursor-pointer">+ 10 GB NVMe</button>
                        <button type="button" onclick="insertFeatureChip('Unlimited Bandwidth')" class="text-[10px] bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-700 px-2 py-0.5 rounded-lg border border-gray-200 transition cursor-pointer">+ Unmetered Bandwidth</button>
                        <button type="button" onclick="insertFeatureChip('Free Let\'s Encrypt SSL')" class="text-[10px] bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-700 px-2 py-0.5 rounded-lg border border-gray-200 transition cursor-pointer">+ Free SSL</button>
                        <button type="button" onclick="insertFeatureChip('cPanel Control Panel')" class="text-[10px] bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-700 px-2 py-0.5 rounded-lg border border-gray-200 transition cursor-pointer">+ cPanel Access</button>
                        <button type="button" onclick="insertFeatureChip('LiteSpeed Web Server + LSCache')" class="text-[10px] bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-700 px-2 py-0.5 rounded-lg border border-gray-200 transition cursor-pointer">+ LiteSpeed</button>
                        <button type="button" onclick="insertFeatureChip('Daily Automated Backups')" class="text-[10px] bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-700 px-2 py-0.5 rounded-lg border border-gray-200 transition cursor-pointer">+ Daily Backups</button>
                        <button type="button" onclick="insertFeatureChip('24/7 Priority Ticket Support')" class="text-[10px] bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-700 px-2 py-0.5 rounded-lg border border-gray-200 transition cursor-pointer">+ 24/7 Support</button>
                        <button type="button" onclick="insertFeatureChip('99.9% Uptime SLA')" class="text-[10px] bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-700 px-2 py-0.5 rounded-lg border border-gray-200 transition cursor-pointer">+ 99.9% SLA</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100">
                    <label class="flex items-center gap-2 cursor-pointer select-none font-semibold text-gray-700">
                        <input type="checkbox" name="is_popular" id="plan_is_popular" value="1" class="rounded text-amber-500 focus:ring-amber-400 w-4 h-4">
                        <span><i class="fa-solid fa-star text-amber-500 mr-1"></i> Mark as Popular / Highlighted</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer select-none font-semibold text-gray-700">
                        <input type="checkbox" name="status" id="plan_status" value="1" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span><i class="fa-solid fa-circle-check text-emerald-500 mr-1"></i> Active (Visible to visitors)</span>
                    </label>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="shrink-0 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2 px-4 sm:px-6 py-3.5 sm:py-4 border-t bg-gray-50">
                <button type="button" onclick="closePlanModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition text-xs flex items-center justify-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-floppy-disk"></i> Save Plan
                </button>
            </div>
        </form>

    </div>
</div>

<!-- ==========================================
     POPUP MODAL: DELETE CONFIRMATION
=============================================== -->
<div id="deletePlanModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST">
            <input type="hidden" name="delete_plan_id" id="delete_plan_id_input" value="">
            
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Delete Hosting Plan?</h3>
                <p class="text-xs text-gray-500 mb-4">Are you sure you want to delete this plan? This action cannot be undone.</p>
                
                <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 text-xs text-left mb-2">
                    <div class="font-bold text-gray-900" id="deletePlanName">Starter NVMe</div>
                    <div class="text-gray-500 mt-0.5 flex justify-between">
                        <span id="deletePlanCat">Shared Hosting</span>
                        <span class="font-bold text-gray-700" id="deletePlanPrice">৳99/mo</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="closeDeletePlanModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-trash-can"></i> Yes, Delete Plan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddPlanModal() {
    document.getElementById('planModalTitle').innerText = 'Add New Hosting Plan';
    document.getElementById('planModalIcon').innerHTML = '<i class="fa-solid fa-plus"></i>';
    document.getElementById('plan_id').value = '';
    document.getElementById('plan_name').value = '';
    document.getElementById('plan_subtitle').value = '';
    document.getElementById('plan_badge').value = '';
    document.getElementById('plan_monthly_price').value = '';
    document.getElementById('plan_yearly_price').value = '';
    document.getElementById('plan_features').value = '';
    document.getElementById('plan_order_url').value = '';
    document.getElementById('plan_sort_order').value = '0';
    document.getElementById('plan_is_popular').checked = false;
    document.getElementById('plan_status').checked = true;

    document.getElementById('planModal').classList.remove('hidden');
}

function openEditPlanModal(plan) {
    document.getElementById('planModalTitle').innerText = 'Edit Plan: ' + plan.name;
    document.getElementById('planModalIcon').innerHTML = '<i class="fa-solid fa-pen"></i>';
    document.getElementById('plan_id').value = plan.id;
    document.getElementById('plan_category').value = plan.category;
    document.getElementById('plan_name').value = plan.name;
    document.getElementById('plan_subtitle').value = plan.subtitle || '';
    document.getElementById('plan_badge').value = plan.badge || '';
    document.getElementById('plan_monthly_price').value = plan.monthly_price;
    document.getElementById('plan_yearly_price').value = plan.yearly_price || '';
    
    var feats = [];
    try { feats = JSON.parse(plan.features) || []; } catch(e) { feats = []; }
    document.getElementById('plan_features').value = feats.join('\n');

    document.getElementById('plan_order_url').value = plan.order_url || '';
    document.getElementById('plan_sort_order').value = plan.sort_order || 0;
    document.getElementById('plan_is_popular').checked = (parseInt(plan.is_popular) === 1);
    document.getElementById('plan_status').checked = (parseInt(plan.status) === 1);

    document.getElementById('planModal').classList.remove('hidden');
}

function openDuplicatePlanModal(plan) {
    document.getElementById('planModalTitle').innerText = 'Clone Plan: ' + plan.name;
    document.getElementById('planModalIcon').innerHTML = '<i class="fa-solid fa-clone"></i>';
    document.getElementById('plan_id').value = ''; // Empty ID triggers new INSERT
    document.getElementById('plan_category').value = plan.category;
    document.getElementById('plan_name').value = plan.name + ' (Copy)';
    document.getElementById('plan_subtitle').value = plan.subtitle || '';
    document.getElementById('plan_badge').value = plan.badge || '';
    document.getElementById('plan_monthly_price').value = plan.monthly_price;
    document.getElementById('plan_yearly_price').value = plan.yearly_price || '';
    
    var feats = [];
    try { feats = JSON.parse(plan.features) || []; } catch(e) { feats = []; }
    document.getElementById('plan_features').value = feats.join('\n');

    document.getElementById('plan_order_url').value = plan.order_url || '';
    document.getElementById('plan_sort_order').value = (parseInt(plan.sort_order) || 0) + 1;
    document.getElementById('plan_is_popular').checked = false;
    document.getElementById('plan_status').checked = true;

    document.getElementById('planModal').classList.remove('hidden');
}

function closePlanModal() {
    document.getElementById('planModal').classList.add('hidden');
}

function insertFeatureChip(text) {
    var ta = document.getElementById('plan_features');
    var val = ta.value.trim();
    if (val.length > 0) {
        ta.value = val + '\n' + text;
    } else {
        ta.value = text;
    }
    ta.focus();
}

function togglePlanField(id, field, btn) {
    var fd = new FormData();
    fd.append('ajax_toggle_field', '1');
    fd.append('id', id);
    fd.append('field', field);

    fetch('plans.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (field === 'status') {
                if (data.new_val === 1) {
                    btn.className = 'status-badge inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition bg-emerald-50 text-emerald-700 border border-emerald-200';
                    btn.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span>Active</span>';
                } else {
                    btn.className = 'status-badge inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition bg-rose-50 text-rose-700 border border-rose-200';
                    btn.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span><span>Inactive</span>';
                }
            } else if (field === 'is_popular') {
                if (data.new_val === 1) {
                    btn.className = 'p-1 rounded-lg text-xs cursor-pointer transition text-amber-500 bg-amber-50';
                } else {
                    btn.className = 'p-1 rounded-lg text-xs cursor-pointer transition text-gray-300 hover:text-amber-400';
                }
            }
        }
    });
}

function openDeletePlanModal(id, name, price, category) {
    document.getElementById('delete_plan_id_input').value = id;
    document.getElementById('deletePlanName').innerText = name;
    document.getElementById('deletePlanCat').innerText = category;
    document.getElementById('deletePlanPrice').innerText = '৳' + price + '/mo';
    document.getElementById('deletePlanModal').classList.remove('hidden');
}

function closeDeletePlanModal() {
    document.getElementById('deletePlanModal').classList.add('hidden');
}

function filterCategoryTab(slug, btn) {
    document.querySelectorAll('.cat-tab-btn').forEach(b => {
        b.classList.remove('bg-blue-600', 'text-white', 'shadow-xs');
        b.classList.add('text-gray-600');
    });
    btn.classList.add('bg-blue-600', 'text-white', 'shadow-xs');
    btn.classList.remove('text-gray-600');

    document.querySelectorAll('.category-block').forEach(block => {
        if (slug === 'all' || block.dataset.category === slug) {
            block.style.display = '';
        } else {
            block.style.display = 'none';
        }
    });
}

function filterPlanCards(q) {
    q = q.trim().toLowerCase();
    document.querySelectorAll('.plan-card').forEach(card => {
        var text = card.dataset.name || '';
        if (!q || text.includes(q)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// Keyboard ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePlanModal();
        closeDeletePlanModal();
    }
});
</script>

<?php include 'footer.php'; ?>
