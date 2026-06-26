<?php
$page_title = 'Manage Plans';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

$error = '';
$success = '';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $del = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM hosting_plans WHERE id = $id"));
    mysqli_query($conn, "DELETE FROM hosting_plans WHERE id = $id");
    logActivity('Deleted Plan', ($del['name'] ?? 'Unknown') . ' (ID: ' . $id . ')');
    header('Location: plans.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFToken($_POST['csrf_token'] ?? '');
    $category = sanitize($_POST['category']);
    $name = sanitize($_POST['name']);
    $subtitle = sanitize($_POST['subtitle']);
    $badge = sanitize($_POST['badge']);
    $monthly_price = (float)$_POST['monthly_price'];
    $yearly_price = (float)$_POST['yearly_price'];
    if (!isset($_POST['enable_monthly'])) $monthly_price = 0;
    if (!isset($_POST['enable_yearly'])) $yearly_price = 0;
    $features = json_encode(array_filter(array_map('trim', explode("\n", $_POST['features']))));
    $order_url = sanitize($_POST['order_url']);
    $is_popular = isset($_POST['is_popular']) ? 1 : 0;
    $sort_order = (int)$_POST['sort_order'];
    $status = isset($_POST['status']) ? 1 : 0;

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $query = "UPDATE hosting_plans SET category='$category', name='$name', subtitle='$subtitle', badge='$badge', monthly_price=$monthly_price, yearly_price=$yearly_price, features='$features', order_url='$order_url', is_popular=$is_popular, sort_order=$sort_order, status=$status WHERE id=$id";
        mysqli_query($conn, $query);
        logActivity('Updated Plan', $name . ' (ID: ' . $id . ')');
        header('Location: plans.php?msg=updated');
        exit;
    } else {
        $query = "INSERT INTO hosting_plans (category, name, subtitle, badge, monthly_price, yearly_price, features, order_url, is_popular, sort_order, status) VALUES ('$category', '$name', '$subtitle', '$badge', $monthly_price, $yearly_price, '$features', '$order_url', $is_popular, $sort_order, $status)";
        mysqli_query($conn, $query);
        logActivity('Created Plan', $name);
        header('Location: plans.php?msg=added');
        exit;
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
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Hosting Plans</h1>
        <p class="text-gray-500">Manage your hosting plans and pricing</p>
    </div>
    <button onclick="openAddModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition shadow"><i class="fa fa-plus mr-1"></i> Add Plan</button>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
    <i class="fa fa-check-circle mr-1"></i>
    <?php echo $_GET['msg'] === 'updated' ? 'Plan updated successfully!' : 'Plan added successfully!'; ?>
</div>
<?php endif; ?>

<div id="planModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 id="planModalTitle" class="text-lg font-semibold flex items-center gap-2">
                <i class="fa fa-plus-circle text-blue-600"></i> Add New Plan
            </h3>
            <button onclick="closePlanModal()" class="text-gray-400 hover:text-gray-600 transition"><i class="fa fa-times text-xl"></i></button>
        </div>
        <form method="POST" id="planForm">
            <?= csrfField() ?>
            <input type="hidden" name="id" id="planFormId" value="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fa fa-folder text-gray-400 mr-1"></i> Category</label>
                    <select name="category" id="planFormCategory" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <?php if ($categories): mysqli_data_seek($categories, 0); while ($cat = mysqli_fetch_assoc($categories)): ?>
                        <option value="<?php echo $cat['slug']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fa fa-tag text-gray-400 mr-1"></i> Plan Name</label>
                    <input type="text" name="name" id="planFormName" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fa fa-subscript text-gray-400 mr-1"></i> Subtitle</label>
                    <input type="text" name="subtitle" id="planFormSubtitle" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fa fa-certificate text-gray-400 mr-1"></i> Badge Text</label>
                    <input type="text" name="badge" id="planFormBadge" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fa fa-money text-gray-400 mr-1"></i> Monthly Price</label>
                    <input type="number" step="0.01" name="monthly_price" id="planFormMonthlyPrice" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <label class="mt-1 flex items-center text-xs text-gray-500"><input type="checkbox" name="enable_monthly" id="planFormEnableMonthly" value="1" checked onchange="if(!this.checked) document.getElementById('planFormMonthlyPrice').value='0'"> <span class="ml-1">Enable monthly billing</span></label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fa fa-calendar text-gray-400 mr-1"></i> Yearly Price</label>
                    <input type="number" step="0.01" name="yearly_price" id="planFormYearlyPrice" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <label class="mt-1 flex items-center text-xs text-gray-500"><input type="checkbox" name="enable_yearly" id="planFormEnableYearly" value="1" checked onchange="if(!this.checked) document.getElementById('planFormYearlyPrice').value='0'"> <span class="ml-1">Enable yearly billing</span></label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fa fa-link text-gray-400 mr-1"></i> Order URL</label>
                    <input type="url" name="order_url" id="planFormOrderUrl" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fa fa-sort-numeric-asc text-gray-400 mr-1"></i> Sort Order</label>
                    <input type="number" name="sort_order" id="planFormSortOrder" value="0" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fa fa-list text-gray-400 mr-1"></i> Features</label>
                <textarea name="features" id="planFormFeatures" rows="6" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                <p class="text-xs text-gray-400 mt-1"><i class="fa fa-info-circle"></i> One feature per line</p>
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-6">
                <label class="flex items-center cursor-pointer select-none">
                    <input type="checkbox" name="is_popular" id="planFormPopular" value="1" class="mr-2 rounded">
                    <span class="text-sm font-medium text-gray-700"><i class="fa fa-star text-yellow-500 mr-1"></i> Popular / Featured</span>
                </label>
                <label class="flex items-center cursor-pointer select-none">
                    <input type="checkbox" name="status" id="planFormStatus" value="1" class="mr-2 rounded">
                    <span class="text-sm font-medium text-gray-700"><i class="fa fa-check-circle text-green-500 mr-1"></i> Active</span>
                </label>
            </div>
            <div class="mt-6 flex items-center gap-3">
                <button type="submit" id="planFormSubmitBtn" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition shadow"><i class="fa fa-plus-circle mr-1"></i> Add Plan</button>
                <button type="button" onclick="closePlanModal()" class="text-gray-600 px-4 py-2 border rounded-lg hover:bg-gray-50 transition"><i class="fa fa-times mr-1"></i> Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php if ($total_plans > 0): ?>
<?php foreach ($plans_by_cat as $cat_slug => $cat_plans): ?>
<?php $cat_name = isset($cat_lookup[$cat_slug]) ? $cat_lookup[$cat_slug] : ucfirst($cat_slug); ?>
<?php $plan_count = count($cat_plans); ?>
<div class="mb-8">
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-5 py-3 rounded-lg mb-4 flex justify-between items-center">
        <div class="flex items-center gap-2">
            <h2 class="text-lg font-semibold"><i class="fa fa-folder mr-2"></i> <?php echo htmlspecialchars($cat_name); ?></h2>
            <a href="/category.php?slug=<?php echo $cat_slug; ?>" target="_blank" title="View on site" class="text-white/70 hover:text-white text-sm transition-colors"><i class="fa fa-external-link-alt"></i></a>
        </div>
        <span class="bg-white text-blue-600 text-xs font-bold px-3 py-1 rounded-full"><?php echo $plan_count; ?> plan<?php echo $plan_count > 1 ? 's' : ''; ?></span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php foreach ($cat_plans as $plan): ?>
        <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-shadow duration-300 border border-gray-100 flex flex-col relative group">
            <?php if ($plan['is_popular']): ?>
            <div class="absolute -top-3 left-4 z-10">
                <span class="bg-yellow-400 text-yellow-900 text-xs font-bold px-3 py-1 rounded-full shadow"><i class="fa fa-star mr-1"></i>POPULAR</span>
            </div>
            <?php endif; ?>
            <?php if ($plan['sort_order']): ?>
            <div class="absolute top-3 right-3 z-10">
                <span class="bg-gray-200 text-gray-600 text-xs font-bold w-6 h-6 flex items-center justify-center rounded-full"><?php echo $plan['sort_order']; ?></span>
            </div>
            <?php endif; ?>

            <div class="p-5 flex-1">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-bold text-gray-800 truncate"><?php echo htmlspecialchars($plan['name']); ?></h3>
                        <?php if ($plan['subtitle']): ?>
                        <p class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars($plan['subtitle']); ?></p>
                        <?php endif; ?>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $plan['status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> shrink-0 ml-2">
                        <span class="w-2 h-2 rounded-full <?php echo $plan['status'] ? 'bg-green-500' : 'bg-red-500'; ?> mr-1"></span>
                        <?php echo $plan['status'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <span class="bg-blue-100 text-blue-700 text-xs font-medium px-2.5 py-0.5 rounded-full"><?php echo htmlspecialchars($plan['category']); ?></span>
                    <?php if ($plan['badge']): ?>
                    <span class="bg-purple-100 text-purple-700 text-xs font-medium px-2.5 py-0.5 rounded-full"><?php echo htmlspecialchars($plan['badge']); ?></span>
                    <?php endif; ?>
                </div>

                <?php $sym = escSetting('currency_symbol') ?: 'TK.'; ?>
                <div class="space-y-2">
                    <?php if ($plan['monthly_price'] > 0): ?>
                    <div class="flex items-baseline gap-1">
                        <span class="text-2xl font-bold text-gray-900"><?php echo $sym; ?><?php echo number_format($plan['monthly_price'], 0); ?></span>
                        <span class="text-sm text-gray-500">/month</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($plan['yearly_price'] > 0): ?>
                    <div class="flex items-baseline gap-1">
                        <span class="text-lg font-semibold text-gray-700"><?php echo $sym; ?><?php echo number_format($plan['yearly_price'], 0); ?></span>
                        <span class="text-sm text-gray-500">/year</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="px-5 py-3 border-t border-gray-100 bg-gray-50 rounded-b-xl flex items-center justify-between">
                <span class="text-xs text-gray-400"><i class="fa fa-database mr-1"></i> #<?php echo $plan['id']; ?></span>
                <div class="flex items-center gap-3">
                    <a href="javascript:void(0)" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($plan)); ?>)" class="text-blue-600 hover:text-blue-800 hover:scale-110 transition-transform" title="Edit"><i class="fa fa-pencil-alt"></i></a>
                    <a href="?delete=<?php echo $plan['id']; ?>" onclick="return confirm('Delete this plan?')" class="text-red-600 hover:text-red-800 hover:scale-110 transition-transform" title="Delete"><i class="fa fa-trash-alt"></i></a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if ($total_plans === 0): ?>
<div class="text-center py-16">
    <i class="fa fa-server text-5xl text-gray-300 mb-4"></i>
    <h3 class="text-xl font-semibold text-gray-500 mb-2">No Plans Yet</h3>
    <p class="text-gray-400 mb-4">Get started by adding your first hosting plan.</p>
    <button onclick="openAddModal()" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition shadow"><i class="fa fa-plus mr-1"></i> Add First Plan</button>
</div>
<?php endif; ?>
<script>
function openAddModal() {
    document.getElementById('planModalTitle').innerHTML = '<i class="fa fa-plus-circle text-blue-600"></i> Add New Plan';
    document.getElementById('planForm').reset();
    document.getElementById('planFormId').value = '';
    document.getElementById('planFormSubmitBtn').innerHTML = '<i class="fa fa-plus-circle mr-1"></i> Add Plan';
    document.getElementById('planFormEnableMonthly').checked = true;
    document.getElementById('planFormEnableYearly').checked = true;
    document.getElementById('planFormStatus').checked = true;
    document.getElementById('planModal').classList.remove('hidden');
}

function openEditModal(plan) {
    document.getElementById('planModalTitle').innerHTML = '<i class="fa fa-pencil text-blue-600"></i> Edit Plan';
    document.getElementById('planFormId').value = plan.id;
    document.getElementById('planFormCategory').value = plan.category;
    document.getElementById('planFormName').value = plan.name;
    document.getElementById('planFormSubtitle').value = plan.subtitle || '';
    document.getElementById('planFormBadge').value = plan.badge || '';
    document.getElementById('planFormMonthlyPrice').value = plan.monthly_price;
    document.getElementById('planFormYearlyPrice').value = plan.yearly_price;
    document.getElementById('planFormOrderUrl').value = plan.order_url || '';
    document.getElementById('planFormSortOrder').value = plan.sort_order;
    document.getElementById('planFormPopular').checked = plan.is_popular == 1;
    document.getElementById('planFormStatus').checked = plan.status == 1;
    document.getElementById('planFormEnableMonthly').checked = plan.monthly_price > 0;
    document.getElementById('planFormEnableYearly').checked = plan.yearly_price > 0;
    var feats = [];
    try { feats = JSON.parse(plan.features); } catch(e) {}
    document.getElementById('planFormFeatures').value = feats.join('\n');
    document.getElementById('planFormSubmitBtn').innerHTML = '<i class="fa fa-save mr-1"></i> Update Plan';
    document.getElementById('planModal').classList.remove('hidden');
}

function closePlanModal() {
    document.getElementById('planModal').classList.add('hidden');
}

document.getElementById('planModal').addEventListener('click', function(e) {
    if (e.target === this) closePlanModal();
});
</script>
<?php include 'footer.php'; ?>
