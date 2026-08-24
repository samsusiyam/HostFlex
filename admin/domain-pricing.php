<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

ensureDomainPricingSchema();

$base_currency = strtoupper(getSetting('base_currency') ?: 'BDT');
$currencies = getCurrenciesList();
$msg = '';
$err = '';

// Helper to get exchange rate multiplier to Base Currency
function getRateToBase($from_currency) {
    $currencies = getCurrenciesList();
    $from = strtoupper(trim($from_currency ?: 'USD'));
    $base = strtoupper(getSetting('base_currency') ?: 'BDT');
    if ($from === $base) return 1.0;
    $rate = (float)($currencies[$from]['rate'] ?? 1.0);
    return $rate > 0 ? (1.0 / $rate) : 1.0;
}

// 1. Handle Bulk Profit Margin Application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_margin') {
    $target_registrar = trim($_POST['target_registrar'] ?? 'all');
    $margin_type = $_POST['margin_type'] === 'fixed' ? 'fixed' : 'percentage';
    $margin_value = (float)($_POST['margin_value'] ?? 15.0);
    $apply_to = $_POST['apply_to'] ?? ['register', 'renew', 'transfer'];

    $where = "WHERE 1=1";
    if ($target_registrar !== 'all') {
        $reg_esc = mysqli_real_escape_string($conn, $target_registrar);
        $where .= " AND registrar = '$reg_esc'";
    }

    $tlds_res = mysqli_query($conn, "SELECT * FROM domain_pricing $where");
    $updated_count = 0;

    while ($row = mysqli_fetch_assoc($tlds_res)) {
        $cost = (float)$row['cost_price'];
        $cost_curr = $row['cost_currency'] ?: 'USD';
        
        if ($cost > 0) {
            $new_selling = calculateSellingPriceFromCost($cost, $cost_curr, $margin_type, $margin_value);
            
            $updates = [];
            $updates[] = "margin_type = '$margin_type'";
            $updates[] = "margin_value = $margin_value";
            
            if (in_array('register', $apply_to)) {
                $updates[] = "register_price = $new_selling";
            }
            if (in_array('renew', $apply_to)) {
                $updates[] = "renew_price = $new_selling";
            }
            if (in_array('transfer', $apply_to)) {
                $updates[] = "transfer_price = $new_selling";
            }

            if (!empty($updates)) {
                mysqli_query($conn, "UPDATE domain_pricing SET " . implode(", ", $updates) . " WHERE id = {$row['id']}");
                $updated_count++;
            }
        }
    }

    $msg = "Bulk profit margin ($margin_value" . ($margin_type === 'percentage' ? '%' : ' ' . $base_currency) . ") applied successfully to $updated_count TLD(s)!";
}

// 2. Handle 1-Click Preset Importer / Reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_preset') {
    $preset_type = $_POST['preset_type'] ?? 'namecheap';
    
    $presets = [
        'namecheap' => [
            ['.com', 'Popular', 'USD', 9.58, 1150.00, 1350.00, 1150.00, 999.00, 'Namecheap', 1, 1, 1],
            ['.net', 'Popular', 'USD', 11.48, 1450.00, 1650.00, 1450.00, null, 'Namecheap', 1, 1, 0],
            ['.org', 'Popular', 'USD', 10.98, 1390.00, 1590.00, 1390.00, null, 'Namecheap', 1, 0, 0],
            ['.io', 'Tech', 'USD', 34.98, 4650.00, 4850.00, 4650.00, null, 'Namecheap', 1, 1, 0],
            ['.me', 'General', 'USD', 5.98, 850.00, 1650.00, 850.00, null, 'Namecheap', 0, 0, 0],
            ['.co', 'Business', 'USD', 10.98, 1450.00, 1750.00, 1450.00, null, 'Namecheap', 0, 0, 0],
            ['.app', 'Tech', 'USD', 14.00, 1850.00, 1850.00, 1850.00, null, 'Namecheap', 0, 0, 0],
            ['.dev', 'Tech', 'USD', 14.00, 1850.00, 1850.00, 1850.00, null, 'Namecheap', 0, 0, 0],
        ],
        'dynadot' => [
            ['.xyz', 'Tech', 'USD', 1.85, 250.00, 1250.00, 1250.00, 199.00, 'Dynadot', 1, 1, 1],
            ['.ai', 'Tech', 'USD', 65.00, 8600.00, 8600.00, 8600.00, null, 'Dynadot', 1, 1, 0],
            ['.co', 'Business', 'USD', 10.50, 1390.00, 1650.00, 1390.00, 990.00, 'Dynadot', 0, 0, 1],
            ['.cc', 'Tech', 'USD', 8.50, 1150.00, 1250.00, 1150.00, null, 'Dynadot', 0, 0, 0],
            ['.tv', 'General', 'USD', 27.00, 3600.00, 3600.00, 3600.00, null, 'Dynadot', 0, 0, 0]
        ],
        'resellerclub' => [
            ['.com', 'Popular', 'USD', 9.89, 1180.00, 1380.00, 1180.00, 999.00, 'ResellerClub', 1, 1, 1],
            ['.net', 'Popular', 'USD', 11.99, 1490.00, 1690.00, 1490.00, null, 'ResellerClub', 1, 1, 0],
            ['.org', 'Popular', 'USD', 11.49, 1450.00, 1650.00, 1450.00, null, 'ResellerClub', 1, 0, 0],
            ['.in', 'Country', 'USD', 6.50, 890.00, 990.00, 890.00, null, 'ResellerClub', 0, 0, 0],
            ['.biz', 'Business', 'USD', 5.99, 790.00, 1690.00, 1690.00, null, 'ResellerClub', 0, 0, 0]
        ],
        'radix' => [
            ['.online', 'Business', 'USD', 2.50, 350.00, 1450.00, 1450.00, 299.00, 'Radix', 1, 0, 1],
            ['.site', 'General', 'USD', 2.20, 320.00, 1350.00, 1350.00, null, 'Radix', 0, 0, 0],
            ['.tech', 'Tech', 'USD', 3.99, 590.00, 1850.00, 1850.00, 499.00, 'Radix', 1, 1, 1],
            ['.store', 'Business', 'USD', 2.99, 399.00, 1850.00, 1850.00, null, 'Radix', 0, 0, 1],
            ['.space', 'Tech', 'USD', 1.99, 299.00, 1250.00, 1250.00, null, 'Radix', 0, 0, 0]
        ]
    ];

    if (isset($presets[$preset_type])) {
        $p_list = $presets[$preset_type];
        $count = 0;
        foreach ($p_list as $item) {
            $ext = mysqli_real_escape_string($conn, $item[0]);
            $cat = mysqli_real_escape_string($conn, $item[1]);
            $cost_c = mysqli_real_escape_string($conn, $item[2]);
            $cost_p = (float)$item[3];
            $reg_p = (float)$item[4];
            $ren_p = (float)$item[5];
            $tra_p = (float)$item[6];
            $pro_p = $item[7] !== null ? (float)$item[7] : 'NULL';
            $reg_name = mysqli_real_escape_string($conn, $item[8]);
            $is_feat = (int)$item[9];
            $is_pop = (int)$item[10];
            $is_pro = (int)$item[11];

            $check = mysqli_query($conn, "SELECT id FROM domain_pricing WHERE extension = '$ext'");
            if (mysqli_num_rows($check) > 0) {
                mysqli_query($conn, "UPDATE domain_pricing SET 
                    category='$cat', cost_currency='$cost_c', cost_price=$cost_p, 
                    register_price=$reg_p, renew_price=$ren_p, transfer_price=$tra_p, promo_price=$pro_p, 
                    registrar='$reg_name', is_featured=$is_feat, is_popular=$is_pop, is_promo=$is_pro 
                    WHERE extension='$ext'");
            } else {
                mysqli_query($conn, "INSERT INTO domain_pricing 
                    (extension, category, cost_currency, cost_price, margin_type, margin_value, register_price, renew_price, transfer_price, promo_price, registrar, is_featured, is_popular, is_promo) 
                    VALUES ('$ext', '$cat', '$cost_c', $cost_p, 'percentage', 15.0, $reg_p, $ren_p, $tra_p, $pro_p, '$reg_name', $is_feat, $is_pop, $is_pro)");
            }
            $count++;
        }
        $msg = "Successfully synced $count TLD(s) from " . ucfirst($preset_type) . " wholesale preset catalogue!";
    }
}

// 3. Handle Add / Edit TLD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['add', 'edit'])) {
    $extension = strtolower(trim($_POST['extension'] ?? ''));
    if ($extension && $extension[0] !== '.') $extension = '.' . $extension;

    $category = trim($_POST['category'] ?? 'Popular');
    $cost_currency = strtoupper(trim($_POST['cost_currency'] ?? 'USD'));
    $cost_price = (float)($_POST['cost_price'] ?? 0.00);
    $margin_type = $_POST['margin_type'] === 'fixed' ? 'fixed' : 'percentage';
    $margin_value = (float)($_POST['margin_value'] ?? 15.0);
    
    $register_price = (float)($_POST['register_price'] ?? 0.00);
    $renew_price = (float)($_POST['renew_price'] ?? 0.00);
    $transfer_price = (float)($_POST['transfer_price'] ?? 0.00);
    $promo_price = trim($_POST['promo_price'] ?? '') !== '' ? (float)$_POST['promo_price'] : null;
    $promo_sql = $promo_price !== null ? $promo_price : 'NULL';

    $registrar = trim($_POST['registrar'] ?? 'Namecheap');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_popular = isset($_POST['is_popular']) ? 1 : 0;
    $is_promo = isset($_POST['is_promo']) ? 1 : 0;
    $status = isset($_POST['status']) ? 1 : 0;
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    if (empty($extension)) {
        $err = "Domain extension (TLD) is required (e.g. .com).";
    } else {
        $ext_esc = mysqli_real_escape_string($conn, $extension);
        $cat_esc = mysqli_real_escape_string($conn, $category);
        $reg_esc = mysqli_real_escape_string($conn, $registrar);

        if ($_POST['action'] === 'add') {
            $chk = mysqli_query($conn, "SELECT id FROM domain_pricing WHERE extension = '$ext_esc'");
            if (mysqli_num_rows($chk) > 0) {
                $err = "TLD $extension already exists in database. You can edit it instead.";
            } else {
                $sql = "INSERT INTO domain_pricing 
                    (extension, category, cost_currency, cost_price, margin_type, margin_value, register_price, renew_price, transfer_price, promo_price, registrar, is_featured, is_popular, is_promo, status, sort_order) 
                    VALUES ('$ext_esc', '$cat_esc', '$cost_currency', $cost_price, '$margin_type', $margin_value, $register_price, $renew_price, $transfer_price, $promo_sql, '$reg_esc', $is_featured, $is_popular, $is_promo, $status, $sort_order)";
                if (mysqli_query($conn, $sql)) {
                    $msg = "New TLD $extension added successfully!";
                } else {
                    $err = "Database error: " . mysqli_error($conn);
                }
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = (int)$_POST['id'];
            $sql = "UPDATE domain_pricing SET 
                extension='$ext_esc', category='$cat_esc', cost_currency='$cost_currency', cost_price=$cost_price, 
                margin_type='$margin_type', margin_value=$margin_value, register_price=$register_price, renew_price=$renew_price, 
                transfer_price=$transfer_price, promo_price=$promo_sql, registrar='$reg_esc', is_featured=$is_featured, 
                is_popular=$is_popular, is_promo=$is_promo, status=$status, sort_order=$sort_order 
                WHERE id=$id";
            if (mysqli_query($conn, $sql)) {
                $msg = "TLD $extension updated successfully!";
            } else {
                $err = "Database error: " . mysqli_error($conn);
            }
        }
    }
}

// 4. Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    mysqli_query($conn, "DELETE FROM domain_pricing WHERE id = $id");
    $msg = "TLD deleted successfully!";
}

// 5. Handle AJAX toggle
if (isset($_GET['ajax_toggle']) && isset($_GET['id']) && isset($_GET['field'])) {
    header('Content-Type: application/json');
    $id = (int)$_GET['id'];
    $field = $_GET['field'];
    if (in_array($field, ['status', 'is_featured', 'is_popular', 'is_promo'])) {
        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT $field FROM domain_pricing WHERE id=$id"));
        if ($row) {
            $new_val = $row[$field] ? 0 : 1;
            mysqli_query($conn, "UPDATE domain_pricing SET $field=$new_val WHERE id=$id");
            echo json_encode(['success' => true, 'new_val' => $new_val]);
            exit;
        }
    }
    echo json_encode(['success' => false]);
    exit;
}

// Query TLD list
$cat_filter = $_GET['category'] ?? 'all';
$search_filter = trim($_GET['search'] ?? '');
$tlds_result = getDomainPricingList(false, $cat_filter, $search_filter);

// Stats
$total_tlds = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM domain_pricing"))['c'] ?? 0;
$active_tlds = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM domain_pricing WHERE status=1"))['c'] ?? 0;
$featured_tlds = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM domain_pricing WHERE is_featured=1 AND status=1"))['c'] ?? 0;
$registrars_list = mysqli_query($conn, "SELECT DISTINCT registrar FROM domain_pricing WHERE registrar != '' ORDER BY registrar ASC");
$all_regs = [];
while ($r = mysqli_fetch_assoc($registrars_list)) {
    $all_regs[] = $r['registrar'];
}

$page_title = "Domain Pricing & Profit Engine";
include 'header.php';
?>

<div class="p-4 sm:p-6 space-y-6 max-w-[1400px] mx-auto">
    
    <!-- Top Header & Action Buttons -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-5 rounded-2xl border border-gray-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-xl"><i class="fa-solid fa-globe text-xl"></i></span>
                <h1 class="text-xl font-extrabold text-gray-900">Domain Pricing & Profit Engine</h1>
            </div>
            <p class="text-xs text-gray-500 mt-1">Manage wholesale registrar costs, auto-calculate profit margins, and sync selling prices seamlessly across multi-currencies.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <!-- 1-Click Sync Presets Dropdown -->
            <div class="relative" id="presetDropdownWrapper">
                <button type="button" onclick="toggleDropdown('presetDropdownMenu')" class="px-3.5 py-2 text-xs font-bold bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-xl transition flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-cloud-arrow-down text-blue-600"></i>
                    <span>Sync Registrar Presets</span>
                    <i class="fa-solid fa-chevron-down text-[9px] text-gray-400"></i>
                </button>
                <div id="presetDropdownMenu" class="hidden absolute right-0 top-full mt-2 w-52 bg-white border border-gray-200 rounded-xl shadow-xl p-1.5 z-50 animate-fadeIn">
                    <div class="px-3 py-1.5 text-[10px] font-extrabold uppercase text-gray-400">Import Wholesale Rates</div>
                    <form method="post">
                        <input type="hidden" name="action" value="import_preset">
                        <button type="submit" name="preset_type" value="namecheap" class="w-full text-left px-3 py-2 text-xs font-bold text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition flex items-center justify-between cursor-pointer">
                            <span>Namecheap Wholesale</span>
                            <span class="text-[10px] text-blue-600 font-mono">USD $</span>
                        </button>
                        <button type="submit" name="preset_type" value="dynadot" class="w-full text-left px-3 py-2 text-xs font-bold text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition flex items-center justify-between cursor-pointer">
                            <span>Dynadot / Radix Deals</span>
                            <span class="text-[10px] text-blue-600 font-mono">USD $</span>
                        </button>
                        <button type="submit" name="preset_type" value="resellerclub" class="w-full text-left px-3 py-2 text-xs font-bold text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition flex items-center justify-between cursor-pointer">
                            <span>ResellerClub Standard</span>
                            <span class="text-[10px] text-blue-600 font-mono">USD $</span>
                        </button>
                        <button type="submit" name="preset_type" value="radix" class="w-full text-left px-3 py-2 text-xs font-bold text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition flex items-center justify-between cursor-pointer">
                            <span>Radix New TLDs (.tech/.site)</span>
                            <span class="text-[10px] text-blue-600 font-mono">USD $</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Bulk Profit Margin Modal Trigger -->
            <button type="button" onclick="openBulkMarginModal()" class="px-3.5 py-2 text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl transition flex items-center gap-2 cursor-pointer shadow-2xs">
                <i class="fa-solid fa-calculator text-indigo-600"></i>
                <span>Bulk Margin Engine</span>
            </button>

            <!-- Add TLD Button -->
            <button type="button" onclick="openAddTldModal()" class="px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition flex items-center gap-2 cursor-pointer shadow-xs">
                <i class="fa-solid fa-plus"></i>
                <span>Add New TLD</span>
            </button>
        </div>
    </div>

    <?php if ($msg): ?>
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold flex items-center justify-between animate-fadeIn">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
            <span><?php echo htmlspecialchars($msg); ?></span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <?php endif; ?>

    <?php if ($err): ?>
    <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold flex items-center justify-between animate-fadeIn">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation text-rose-600 text-base"></i>
            <span><?php echo htmlspecialchars($err); ?></span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <?php endif; ?>

    <!-- Summary Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-2xs">
            <span class="text-xs font-bold text-gray-500">Total TLD Extensions</span>
            <div class="flex items-center justify-between mt-1">
                <span class="text-2xl font-extrabold text-gray-900"><?php echo $total_tlds; ?></span>
                <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm"><i class="fa-solid fa-list"></i></span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-2xs">
            <span class="text-xs font-bold text-gray-500">Active Public TLDs</span>
            <div class="flex items-center justify-between mt-1">
                <span class="text-2xl font-extrabold text-emerald-600"><?php echo $active_tlds; ?></span>
                <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm"><i class="fa-solid fa-check"></i></span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-2xs">
            <span class="text-xs font-bold text-gray-500">Featured on Homepage</span>
            <div class="flex items-center justify-between mt-1">
                <span class="text-2xl font-extrabold text-indigo-600"><?php echo $featured_tlds; ?></span>
                <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm"><i class="fa-solid fa-star"></i></span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-2xs">
            <span class="text-xs font-bold text-gray-500">Base Currency & USD Rate</span>
            <div class="flex items-center justify-between mt-1">
                <span class="text-sm font-extrabold text-gray-800">1 USD ≈ <?php echo round(getRateToBase('USD'), 2); ?> <?php echo $base_currency; ?></span>
                <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm"><i class="fa-solid fa-coins"></i></span>
            </div>
        </div>
    </div>

    <!-- Filters and Search Bar -->
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
            <a href="?category=all<?php echo $search_filter ? '&search=' . urlencode($search_filter) : ''; ?>" class="px-3 py-1.5 text-xs font-bold rounded-lg transition <?php echo $cat_filter === 'all' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'; ?>">All Categories</a>
            <a href="?category=Popular<?php echo $search_filter ? '&search=' . urlencode($search_filter) : ''; ?>" class="px-3 py-1.5 text-xs font-bold rounded-lg transition <?php echo $cat_filter === 'Popular' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'; ?>">🔥 Popular</a>
            <a href="?category=Tech<?php echo $search_filter ? '&search=' . urlencode($search_filter) : ''; ?>" class="px-3 py-1.5 text-xs font-bold rounded-lg transition <?php echo $cat_filter === 'Tech' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'; ?>">💻 Tech & Dev</a>
            <a href="?category=Business<?php echo $search_filter ? '&search=' . urlencode($search_filter) : ''; ?>" class="px-3 py-1.5 text-xs font-bold rounded-lg transition <?php echo $cat_filter === 'Business' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'; ?>">💼 Business</a>
            <a href="?category=Country<?php echo $search_filter ? '&search=' . urlencode($search_filter) : ''; ?>" class="px-3 py-1.5 text-xs font-bold rounded-lg transition <?php echo $cat_filter === 'Country' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'; ?>">🌐 Country (ccTLD)</a>
            <a href="?category=General<?php echo $search_filter ? '&search=' . urlencode($search_filter) : ''; ?>" class="px-3 py-1.5 text-xs font-bold rounded-lg transition <?php echo $cat_filter === 'General' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'; ?>">🏷️ General</a>
        </div>

        <form method="get" class="flex items-center gap-2 w-full md:w-72">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($cat_filter); ?>">
            <div class="relative w-full">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search_filter); ?>" placeholder="Search TLD, registrar..." class="w-full pl-8 pr-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:bg-white focus:border-blue-500 focus:outline-none">
            </div>
            <?php if ($search_filter): ?>
            <a href="?category=<?php echo htmlspecialchars($cat_filter); ?>" class="p-1.5 text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- TLDs Pricing Table -->
    <div class="bg-white border border-gray-200 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-500 font-extrabold uppercase text-[10px] tracking-wider">
                        <th class="py-3.5 px-4">TLD Extension</th>
                        <th class="py-3.5 px-4">Category</th>
                        <th class="py-3.5 px-4">Registrar & Cost</th>
                        <th class="py-3.5 px-4">Profit Margin</th>
                        <th class="py-3.5 px-4">Register Price</th>
                        <th class="py-3.5 px-4">Renew / Transfer</th>
                        <th class="py-3.5 px-3 text-center">Featured</th>
                        <th class="py-3.5 px-3 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700 font-medium">
                    <?php if (mysqli_num_rows($tlds_result) > 0): ?>
                    <?php while ($tld = mysqli_fetch_assoc($tlds_result)): ?>
                    <tr class="hover:bg-blue-50/40 transition">
                        <!-- TLD Extension + Badges -->
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-sm font-extrabold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-lg border border-blue-100"><?php echo htmlspecialchars($tld['extension']); ?></span>
                                <?php if ($tld['is_popular']): ?>
                                <span class="px-1.5 py-0.5 text-[9px] font-extrabold bg-amber-100 text-amber-700 rounded-md">🔥 Hot</span>
                                <?php endif; ?>
                                <?php if ($tld['is_promo']): ?>
                                <span class="px-1.5 py-0.5 text-[9px] font-extrabold bg-rose-100 text-rose-700 rounded-md">Sale</span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- Category -->
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-700 text-[11px] font-bold rounded-md"><?php echo htmlspecialchars($tld['category']); ?></span>
                        </td>

                        <!-- Registrar & Cost -->
                        <td class="py-3 px-4">
                            <div class="font-bold text-gray-800"><?php echo htmlspecialchars($tld['registrar']); ?></div>
                            <div class="text-[11px] text-gray-500 font-mono">
                                Cost: <?php echo htmlspecialchars($tld['cost_currency'] . ' ' . number_format($tld['cost_price'], 2)); ?>
                            </div>
                        </td>

                        <!-- Profit Margin -->
                        <td class="py-3 px-4">
                            <span class="inline-flex items-center gap-1 font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100">
                                <i class="fa-solid fa-arrow-trend-up text-[10px]"></i>
                                <?php echo htmlspecialchars($tld['margin_value'] . ($tld['margin_type'] === 'percentage' ? '%' : ' ' . $base_currency)); ?>
                            </span>
                        </td>

                        <!-- Register Price (+ Promo Price if active) -->
                        <td class="py-3 px-4">
                            <?php if ($tld['promo_price'] !== null && $tld['promo_price'] > 0): ?>
                            <div>
                                <span class="text-xs font-bold text-gray-400 line-through mr-1"><?php echo $base_currency . ' ' . number_format($tld['register_price']); ?></span>
                                <span class="text-sm font-extrabold text-rose-600"><?php echo $base_currency . ' ' . number_format($tld['promo_price']); ?></span>
                            </div>
                            <?php else: ?>
                            <span class="text-sm font-extrabold text-gray-900"><?php echo $base_currency . ' ' . number_format($tld['register_price']); ?></span>
                            <?php endif; ?>
                        </td>

                        <!-- Renew & Transfer -->
                        <td class="py-3 px-4">
                            <div class="text-[11px] text-gray-600">Renew: <strong class="text-gray-800"><?php echo $base_currency . ' ' . number_format($tld['renew_price']); ?></strong></div>
                            <div class="text-[11px] text-gray-500">Transfer: <strong class="text-gray-800"><?php echo $base_currency . ' ' . number_format($tld['transfer_price']); ?></strong></div>
                        </td>

                        <!-- Featured Toggle -->
                        <td class="py-3 px-3 text-center">
                            <button type="button" onclick="toggleAjaxField(<?php echo $tld['id']; ?>, 'is_featured', this)" class="p-1 rounded-md transition cursor-pointer <?php echo $tld['is_featured'] ? 'text-amber-500 hover:text-amber-600' : 'text-gray-300 hover:text-gray-400'; ?>" title="Toggle Homepage Featured">
                                <i class="fa-solid fa-star text-base"></i>
                            </button>
                        </td>

                        <!-- Status Toggle -->
                        <td class="py-3 px-3 text-center">
                            <button type="button" onclick="toggleAjaxField(<?php echo $tld['id']; ?>, 'status', this)" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold transition cursor-pointer <?php echo $tld['status'] ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'; ?>">
                                <?php echo $tld['status'] ? 'Active' : 'Disabled'; ?>
                            </button>
                        </td>

                        <!-- Action Buttons -->
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" onclick='openEditTldModal(<?php echo json_encode($tld); ?>)' class="p-1.5 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition cursor-pointer" title="Edit TLD">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button type="button" onclick="openDeleteTldModal(<?php echo $tld['id']; ?>, '<?php echo htmlspecialchars($tld['extension']); ?>')" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer" title="Delete TLD">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="9" class="py-12 text-center text-gray-400 font-medium">
                            <i class="fa-solid fa-globe text-3xl mb-2 text-gray-300 block"></i>
                            No domain pricing records found matching your filters.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 1: ADD NEW TLD MODAL WITH LIVE CALCULATOR          -->
<!-- ======================================================== -->
<div id="addTldModal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl border border-gray-200 overflow-hidden animate-fadeIn">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <div class="flex items-center gap-2">
                <span class="p-1.5 bg-blue-50 text-blue-600 rounded-lg"><i class="fa-solid fa-plus"></i></span>
                <h3 class="text-sm font-extrabold text-gray-900">Add New TLD Extension</h3>
            </div>
            <button type="button" onclick="closeModal('addTldModal')" class="text-gray-400 hover:text-gray-600 cursor-pointer"><i class="fa-solid fa-xmark text-base"></i></button>
        </div>

        <form method="post" id="addTldForm" class="p-5 space-y-4">
            <input type="hidden" name="action" value="add">
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">TLD Extension *</label>
                    <input type="text" name="extension" placeholder=".store" required class="w-full px-3 py-2 text-xs font-mono font-bold bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Category</label>
                    <select name="category" class="w-full px-3 py-2 text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 focus:outline-none">
                        <option value="Popular">Popular</option>
                        <option value="Tech">Tech & Dev</option>
                        <option value="Business">Business</option>
                        <option value="Country">Country (ccTLD)</option>
                        <option value="General">General</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Registrar</label>
                    <input type="text" name="registrar" placeholder="Namecheap" value="Namecheap" class="w-full px-3 py-2 text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 focus:outline-none">
                </div>
            </div>

            <!-- Cost & Margin Calculator Card -->
            <div class="p-3.5 bg-blue-50/50 border border-blue-100 rounded-xl space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold text-blue-900 flex items-center gap-1.5">
                        <i class="fa-solid fa-calculator text-blue-600"></i> Smart Cost & Margin Auto-Calculator
                    </span>
                    <span class="text-[10px] font-bold text-blue-600">Base: <?php echo $base_currency; ?></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Cost Currency</label>
                        <select name="cost_currency" id="add_cost_currency" onchange="calcAddTldPrices()" class="w-full px-2.5 py-1.5 text-xs font-bold bg-white border border-gray-200 rounded-lg">
                            <?php foreach ($currencies as $c_code => $c_val): ?>
                            <option value="<?php echo $c_code; ?>" <?php echo $c_code === 'USD' ? 'selected' : ''; ?>><?php echo $c_code; ?> (<?php echo $c_val['symbol']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Registrar Cost Price</label>
                        <input type="number" step="0.01" name="cost_price" id="add_cost_price" value="9.50" oninput="calcAddTldPrices()" placeholder="9.50" class="w-full px-2.5 py-1.5 text-xs font-bold bg-white border border-gray-200 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Profit Margin (%)</label>
                        <input type="number" step="0.5" name="margin_value" id="add_margin_val" value="15.0" oninput="calcAddTldPrices()" placeholder="15" class="w-full px-2.5 py-1.5 text-xs font-bold bg-white border border-gray-200 rounded-lg">
                        <input type="hidden" name="margin_type" value="percentage">
                    </div>
                </div>

                <div class="flex items-center justify-between text-[11px] text-blue-800 font-bold pt-1 border-t border-blue-100">
                    <span>Suggested Selling Base:</span>
                    <span id="add_calc_result" class="font-mono text-xs text-blue-700 font-extrabold">-</span>
                </div>
            </div>

            <!-- Final Selling Prices -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Register Price (<?php echo $base_currency; ?>) *</label>
                    <input type="number" step="0.01" name="register_price" id="add_reg_price" required class="w-full px-3 py-2 text-xs font-bold font-mono bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Renew Price (<?php echo $base_currency; ?>) *</label>
                    <input type="number" step="0.01" name="renew_price" id="add_ren_price" required class="w-full px-3 py-2 text-xs font-bold font-mono bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Transfer Price (<?php echo $base_currency; ?>) *</label>
                    <input type="number" step="0.01" name="transfer_price" id="add_tra_price" required class="w-full px-3 py-2 text-xs font-bold font-mono bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-rose-600 uppercase mb-1">Sale / Promo Price</label>
                    <input type="number" step="0.01" name="promo_price" placeholder="Optional" class="w-full px-3 py-2 text-xs font-bold font-mono bg-rose-50/40 border border-rose-200 text-rose-700 rounded-xl focus:bg-white focus:border-rose-500 focus:outline-none">
                </div>
            </div>

            <!-- Toggles & Order -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-gray-100">
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-gray-700">
                        <input type="checkbox" name="is_popular" class="rounded text-blue-600">
                        <span>🔥 Popular</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-gray-700">
                        <input type="checkbox" name="is_promo" class="rounded text-blue-600">
                        <span>🎉 Promo</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-gray-700">
                        <input type="checkbox" name="is_featured" checked class="rounded text-blue-600">
                        <span>⭐ Featured</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-gray-700">
                        <input type="checkbox" name="status" checked class="rounded text-blue-600">
                        <span>Active</span>
                    </label>
                </div>
                <div class="w-24">
                    <input type="number" name="sort_order" value="0" placeholder="Sort order" class="w-full px-2.5 py-1.5 text-xs text-center font-bold bg-gray-50 border border-gray-200 rounded-lg">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3">
                <button type="button" onclick="closeModal('addTldModal')" class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-xs cursor-pointer">Save & Publish TLD</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 2: EDIT TLD MODAL WITH LIVE CALCULATOR             -->
<!-- ======================================================== -->
<div id="editTldModal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl border border-gray-200 overflow-hidden animate-fadeIn">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <div class="flex items-center gap-2">
                <span class="p-1.5 bg-blue-50 text-blue-600 rounded-lg"><i class="fa-solid fa-pen-to-square"></i></span>
                <h3 class="text-sm font-extrabold text-gray-900">Edit TLD Extension</h3>
            </div>
            <button type="button" onclick="closeModal('editTldModal')" class="text-gray-400 hover:text-gray-600 cursor-pointer"><i class="fa-solid fa-xmark text-base"></i></button>
        </div>

        <form method="post" id="editTldForm" class="p-5 space-y-4">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">TLD Extension *</label>
                    <input type="text" name="extension" id="edit_extension" required class="w-full px-3 py-2 text-xs font-mono font-bold bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Category</label>
                    <select name="category" id="edit_category" class="w-full px-3 py-2 text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 focus:outline-none">
                        <option value="Popular">Popular</option>
                        <option value="Tech">Tech & Dev</option>
                        <option value="Business">Business</option>
                        <option value="Country">Country (ccTLD)</option>
                        <option value="General">General</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Registrar</label>
                    <input type="text" name="registrar" id="edit_registrar" class="w-full px-3 py-2 text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 focus:outline-none">
                </div>
            </div>

            <!-- Cost & Margin Calculator Card -->
            <div class="p-3.5 bg-blue-50/50 border border-blue-100 rounded-xl space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold text-blue-900 flex items-center gap-1.5">
                        <i class="fa-solid fa-calculator text-blue-600"></i> Smart Cost & Margin Auto-Calculator
                    </span>
                    <span class="text-[10px] font-bold text-blue-600">Base: <?php echo $base_currency; ?></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Cost Currency</label>
                        <select name="cost_currency" id="edit_cost_currency" onchange="calcEditTldPrices()" class="w-full px-2.5 py-1.5 text-xs font-bold bg-white border border-gray-200 rounded-lg">
                            <?php foreach ($currencies as $c_code => $c_val): ?>
                            <option value="<?php echo $c_code; ?>"><?php echo $c_code; ?> (<?php echo $c_val['symbol']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Registrar Cost Price</label>
                        <input type="number" step="0.01" name="cost_price" id="edit_cost_price" oninput="calcEditTldPrices()" class="w-full px-2.5 py-1.5 text-xs font-bold bg-white border border-gray-200 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Profit Margin (%)</label>
                        <input type="number" step="0.5" name="margin_value" id="edit_margin_val" oninput="calcEditTldPrices()" class="w-full px-2.5 py-1.5 text-xs font-bold bg-white border border-gray-200 rounded-lg">
                        <input type="hidden" name="margin_type" id="edit_margin_type" value="percentage">
                    </div>
                </div>

                <div class="flex items-center justify-between text-[11px] text-blue-800 font-bold pt-1 border-t border-blue-100">
                    <span>Suggested Selling Base:</span>
                    <span id="edit_calc_result" class="font-mono text-xs text-blue-700 font-extrabold">-</span>
                </div>
            </div>

            <!-- Final Selling Prices -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Register (<?php echo $base_currency; ?>) *</label>
                    <input type="number" step="0.01" name="register_price" id="edit_reg_price" required class="w-full px-3 py-2 text-xs font-bold font-mono bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Renew (<?php echo $base_currency; ?>) *</label>
                    <input type="number" step="0.01" name="renew_price" id="edit_ren_price" required class="w-full px-3 py-2 text-xs font-bold font-mono bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Transfer (<?php echo $base_currency; ?>) *</label>
                    <input type="number" step="0.01" name="transfer_price" id="edit_tra_price" required class="w-full px-3 py-2 text-xs font-bold font-mono bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-rose-600 uppercase mb-1">Sale / Promo Price</label>
                    <input type="number" step="0.01" name="promo_price" id="edit_promo_price" placeholder="Optional" class="w-full px-3 py-2 text-xs font-bold font-mono bg-rose-50/40 border border-rose-200 text-rose-700 rounded-xl focus:bg-white focus:border-rose-500 focus:outline-none">
                </div>
            </div>

            <!-- Toggles & Order -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-gray-100">
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-gray-700">
                        <input type="checkbox" name="is_popular" id="edit_is_popular" class="rounded text-blue-600">
                        <span>🔥 Popular</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-gray-700">
                        <input type="checkbox" name="is_promo" id="edit_is_promo" class="rounded text-blue-600">
                        <span>🎉 Promo</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-gray-700">
                        <input type="checkbox" name="is_featured" id="edit_is_featured" class="rounded text-blue-600">
                        <span>⭐ Featured</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-gray-700">
                        <input type="checkbox" name="status" id="edit_status" class="rounded text-blue-600">
                        <span>Active</span>
                    </label>
                </div>
                <div class="w-24">
                    <input type="number" name="sort_order" id="edit_sort_order" placeholder="Sort" class="w-full px-2.5 py-1.5 text-xs text-center font-bold bg-gray-50 border border-gray-200 rounded-lg">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3">
                <button type="button" onclick="closeModal('editTldModal')" class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-xs cursor-pointer">Update TLD</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 3: BULK PROFIT MARGIN APPLICATOR MODAL             -->
<!-- ======================================================== -->
<div id="bulkMarginModal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl border border-gray-200 overflow-hidden animate-fadeIn">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-indigo-50/50">
            <div class="flex items-center gap-2">
                <span class="p-1.5 bg-indigo-100 text-indigo-700 rounded-lg"><i class="fa-solid fa-calculator"></i></span>
                <h3 class="text-sm font-extrabold text-indigo-950">Bulk Profit Margin Engine</h3>
            </div>
            <button type="button" onclick="closeModal('bulkMarginModal')" class="text-gray-400 hover:text-gray-600 cursor-pointer"><i class="fa-solid fa-xmark text-base"></i></button>
        </div>

        <form method="post" class="p-5 space-y-4">
            <input type="hidden" name="action" value="bulk_margin">

            <div>
                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Target Registrar</label>
                <select name="target_registrar" class="w-full px-3 py-2 text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:outline-none">
                    <option value="all">All Registrars & TLDs (Bulk Update All)</option>
                    <?php foreach ($all_regs as $reg): ?>
                    <option value="<?php echo htmlspecialchars($reg); ?>">Only <?php echo htmlspecialchars($reg); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Margin Mode</label>
                    <select name="margin_type" class="w-full px-3 py-2 text-xs font-bold bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:outline-none">
                        <option value="percentage">Percentage Markup (+%)</option>
                        <option value="fixed">Fixed Amount (+<?php echo $base_currency; ?>)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Margin Value</label>
                    <input type="number" step="0.5" name="margin_value" value="15.0" required class="w-full px-3 py-2 text-xs font-bold font-mono bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:outline-none">
                </div>
            </div>

            <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 space-y-1">
                <div class="font-bold flex items-center gap-1.5">
                    <i class="fa-solid fa-info-circle text-amber-600"></i> How Bulk Margin Calculation Works:
                </div>
                <p class="text-[11px] text-amber-900 leading-relaxed">
                    It takes the registrar wholesale cost (e.g. $9.50 USD), converts it into <strong><?php echo $base_currency; ?></strong> using active exchange rates, applies the profit margin, and recalculates the final selling price across registration, renewal, and transfer.
                </p>
            </div>

            <div class="space-y-1.5">
                <span class="block text-[11px] font-bold text-gray-700 uppercase">Apply to Price Fields</span>
                <div class="flex items-center gap-4 text-xs font-bold text-gray-700">
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="apply_to[]" value="register" checked class="rounded text-indigo-600">
                        <span>Registration</span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="apply_to[]" value="renew" checked class="rounded text-indigo-600">
                        <span>Renewal</span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="apply_to[]" value="transfer" checked class="rounded text-indigo-600">
                        <span>Transfer</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
                <button type="button" onclick="closeModal('bulkMarginModal')" class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-xs cursor-pointer">Apply & Recalculate Now</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 4: DELETE CONFIRMATION MODAL                       -->
<!-- ======================================================== -->
<div id="deleteTldModal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl border border-gray-200 p-5 space-y-4 animate-fadeIn">
        <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto text-xl font-bold">
            <i class="fa-solid fa-trash-can"></i>
        </div>
        <div class="text-center space-y-1">
            <h3 class="text-sm font-extrabold text-gray-900">Delete Domain TLD?</h3>
            <p class="text-xs text-gray-500">Are you sure you want to delete <span id="del_tld_name" class="font-bold text-gray-800"></span>? This action cannot be undone.</p>
        </div>
        <form method="post" class="flex items-center gap-2">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="del_tld_id">
            <button type="button" onclick="closeModal('deleteTldModal')" class="w-1/2 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl cursor-pointer">Cancel</button>
            <button type="submit" class="w-1/2 py-2 text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white rounded-xl shadow-xs cursor-pointer">Delete</button>
        </form>
    </div>
</div>

<script>
// Currency Exchange Rate Map for JS Calculator
var exchangeRatesToBase = {
    <?php foreach ($currencies as $c_code => $c_data): ?>
    '<?php echo $c_code; ?>': <?php echo getRateToBase($c_code); ?>,
    <?php endforeach; ?>
};
var baseCurrencyCode = '<?php echo $base_currency; ?>';

function calcSellingPrice(cost, curr, margin) {
    var rate = exchangeRatesToBase[curr] || 1.0;
    var baseCost = parseFloat(cost || 0) * rate;
    var marginVal = parseFloat(margin || 0);
    var selling = baseCost * (1 + (marginVal / 100));
    return Math.round(selling);
}

function calcAddTldPrices() {
    var cost = document.getElementById('add_cost_price').value;
    var curr = document.getElementById('add_cost_currency').value;
    var margin = document.getElementById('add_margin_val').value;
    var selling = calcSellingPrice(cost, curr, margin);
    
    document.getElementById('add_reg_price').value = selling;
    document.getElementById('add_ren_price').value = Math.round(selling * 1.1); // default renew margin
    document.getElementById('add_tra_price').value = selling;
    document.getElementById('add_calc_result').innerText = baseCurrencyCode + ' ' + selling;
}

function calcEditTldPrices() {
    var cost = document.getElementById('edit_cost_price').value;
    var curr = document.getElementById('edit_cost_currency').value;
    var margin = document.getElementById('edit_margin_val').value;
    var selling = calcSellingPrice(cost, curr, margin);
    
    document.getElementById('edit_reg_price').value = selling;
    document.getElementById('edit_ren_price').value = Math.round(selling * 1.1);
    document.getElementById('edit_tra_price').value = selling;
    document.getElementById('edit_calc_result').innerText = baseCurrencyCode + ' ' + selling;
}

function openAddTldModal() {
    document.getElementById('addTldForm').reset();
    calcAddTldPrices();
    document.getElementById('addTldModal').classList.remove('hidden');
}

function openEditTldModal(tld) {
    document.getElementById('edit_id').value = tld.id;
    document.getElementById('edit_extension').value = tld.extension;
    document.getElementById('edit_category').value = tld.category;
    document.getElementById('edit_registrar').value = tld.registrar;
    document.getElementById('edit_cost_currency').value = tld.cost_currency || 'USD';
    document.getElementById('edit_cost_price').value = tld.cost_price;
    document.getElementById('edit_margin_val').value = tld.margin_value;
    document.getElementById('edit_reg_price').value = tld.register_price;
    document.getElementById('edit_ren_price').value = tld.renew_price;
    document.getElementById('edit_tra_price').value = tld.transfer_price;
    document.getElementById('edit_promo_price').value = tld.promo_price || '';
    document.getElementById('edit_sort_order').value = tld.sort_order;
    document.getElementById('edit_is_popular').checked = tld.is_popular == 1;
    document.getElementById('edit_is_promo').checked = tld.is_promo == 1;
    document.getElementById('edit_is_featured').checked = tld.is_featured == 1;
    document.getElementById('edit_status').checked = tld.status == 1;
    
    var selling = calcSellingPrice(tld.cost_price, tld.cost_currency || 'USD', tld.margin_value);
    document.getElementById('edit_calc_result').innerText = baseCurrencyCode + ' ' + selling;
    
    document.getElementById('editTldModal').classList.remove('hidden');
}

function openBulkMarginModal() {
    document.getElementById('bulkMarginModal').classList.remove('hidden');
}

function openDeleteTldModal(id, name) {
    document.getElementById('del_tld_id').value = id;
    document.getElementById('del_tld_name').innerText = name;
    document.getElementById('deleteTldModal').classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function toggleDropdown(id) {
    var el = document.getElementById(id);
    if (el) el.classList.toggle('hidden');
}

document.addEventListener('click', function(e) {
    var wrapper = document.getElementById('presetDropdownWrapper');
    var menu = document.getElementById('presetDropdownMenu');
    if (wrapper && menu && !wrapper.contains(e.target)) {
        menu.classList.add('hidden');
    }
});

function toggleAjaxField(id, field, btn) {
    fetch('domain-pricing.php?ajax_toggle=1&id=' + id + '&field=' + field)
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            if (field === 'status') {
                if (data.new_val == 1) {
                    btn.className = 'px-2 py-0.5 rounded-full text-[10px] font-extrabold transition cursor-pointer bg-emerald-100 text-emerald-700';
                    btn.innerText = 'Active';
                } else {
                    btn.className = 'px-2 py-0.5 rounded-full text-[10px] font-extrabold transition cursor-pointer bg-gray-100 text-gray-500';
                    btn.innerText = 'Disabled';
                }
            } else if (field === 'is_featured') {
                if (data.new_val == 1) {
                    btn.className = 'p-1 rounded-md transition cursor-pointer text-amber-500 hover:text-amber-600';
                } else {
                    btn.className = 'p-1 rounded-md transition cursor-pointer text-gray-300 hover:text-gray-400';
                }
            }
        }
    });
}
</script>

<?php include 'footer.php'; ?>
