<?php
$page_title = 'Menus';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM menu_items WHERE id = $id OR parent_id = $id");
    header('Location: menus.php');
    exit;
}

if (isset($_POST['reorder'])) {
    $ids = $_POST['ids'] ?? '';
    foreach (explode(',', $ids) as $i => $id) {
        $id = (int)$id;
        $sort = ($i + 1) * 10;
        mysqli_query($conn, "UPDATE menu_items SET sort_order = $sort WHERE id = $id");
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['reorder'])) {
    $label = sanitize($_POST['label']);
    $url = sanitize($_POST['url']);
    $parent_id = (int)$_POST['parent_id'];
    $location = sanitize($_POST['location']);
    $sort_order = (int)$_POST['sort_order'];
    $status = isset($_POST['status']) ? 1 : 0;

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        mysqli_query($conn, "UPDATE menu_items SET label='$label', url='$url', parent_id=$parent_id, location='$location', sort_order=$sort_order, status=$status WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO menu_items (label, url, parent_id, location, sort_order, status) VALUES ('$label', '$url', $parent_id, '$location', $sort_order, $status)");
    }
    header('Location: menus.php');
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $r = mysqli_query($conn, "SELECT * FROM menu_items WHERE id = $id");
    $edit = mysqli_fetch_assoc($r);
}

function renderMenuTable($location_label) {
    global $conn;
    $items = mysqli_query($conn, "SELECT * FROM menu_items WHERE location = '$location_label' OR location = 'both' ORDER BY parent_id, sort_order ASC");
    $parents = [];
    while ($m = mysqli_fetch_assoc($items)) {
        if ($m['parent_id'] == 0) $parents[] = $m;
    }
    ?>
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
            <h3 class="font-semibold text-gray-700"><i class="fa fa-list mr-1"></i> <?php echo ucfirst($location_label); ?> Menu</h3>
            <span class="text-xs text-gray-400">Drag to reorder</span>
        </div>
        <table class="min-w-full">
            <thead class="bg-gray-100"><tr><th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-10"></th><th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Label</th><th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">URL</th><th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th><th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th></tr></thead>
            <tbody id="sortableMenu<?php echo ucfirst($location_label); ?>" class="divide-y divide-gray-200">
                <?php foreach ($parents as $m):
                    $children = mysqli_query($conn, "SELECT * FROM menu_items WHERE parent_id = {$m['id']} ORDER BY sort_order ASC");
                    $has_children = mysqli_num_rows($children) > 0;
                ?>
                <tr data-id="<?php echo $m['id']; ?>" class="bg-blue-50/50 hover:bg-blue-50 cursor-move">
                    <td class="px-4 py-3 text-gray-400"><i class="fa fa-grip-vertical"></i></td>
                    <td class="px-4 py-3 font-semibold text-gray-800"><i class="fa fa-folder-open text-yellow-500 mr-1"></i> <?php echo htmlspecialchars($m['label']); ?></td>
                    <td class="px-4 py-3 text-sm text-gray-500"><?php echo htmlspecialchars($m['url']); ?></td>
                    <td class="px-4 py-3"><?php echo $m['status'] ? '<span class="text-green-600 text-sm"><i class="fa fa-check-circle"></i> Active</span>' : '<span class="text-red-500 text-sm"><i class="fa fa-times-circle"></i> Inactive</span>'; ?></td>
                    <td class="px-4 py-3"><a href="?edit=<?php echo $m['id']; ?>" class="text-blue-600 hover:text-blue-800 mr-2"><i class="fa fa-edit"></i></a><a href="?delete=<?php echo $m['id']; ?>" onclick="return confirm('Delete &quot;<?php echo htmlspecialchars($m['label']); ?>&quot; and its children?')" class="text-red-500 hover:text-red-700"><i class="fa fa-trash"></i></a></td>
                </tr>
                <?php while ($child = mysqli_fetch_assoc($children)): ?>
                <tr data-id="<?php echo $child['id']; ?>" class="hover:bg-gray-50 cursor-move">
                    <td class="px-4 py-2.5 text-gray-400"><i class="fa fa-grip-vertical text-xs"></i></td>
                    <td class="px-4 py-2.5 text-sm pl-10"><i class="fa fa-level-down-alt text-gray-300 mr-1 rotate-90"></i> <i class="fa fa-file text-gray-400 mr-1"></i> <?php echo htmlspecialchars($child['label']); ?></td>
                    <td class="px-4 py-2.5 text-sm text-gray-400"><?php echo htmlspecialchars($child['url']); ?></td>
                    <td class="px-4 py-2.5"><?php echo $child['status'] ? '<span class="text-green-600 text-sm"><i class="fa fa-check-circle"></i></span>' : '<span class="text-red-400 text-sm"><i class="fa fa-times-circle"></i></span>'; ?></td>
                    <td class="px-4 py-2.5"><a href="?edit=<?php echo $child['id']; ?>" class="text-blue-600 hover:text-blue-800 mr-2"><i class="fa fa-edit"></i></a><a href="?delete=<?php echo $child['id']; ?>" onclick="return confirm('Delete?')" class="text-red-500 hover:text-red-700"><i class="fa fa-trash"></i></a></td>
                </tr>
                <?php endwhile; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
?>
<?php include 'header.php'; ?>
<div class="mb-6 flex justify-between items-center flex-wrap gap-3">
    <div><h1 class="text-2xl font-bold text-gray-800"><i class="fa fa-bars text-blue-600 mr-2"></i> Menu Manager</h1><p class="text-gray-500">Drag items to reorder, nest under parents for dropdown menus</p></div>
    <a href="?action=add" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition"><i class="fa fa-plus"></i> Add Menu Item</a>
</div>

<?php if (isset($_GET['action']) || $edit): ?>
<div class="bg-white rounded-lg shadow p-6 mb-6 border-t-4 border-blue-500">
    <h2 class="text-lg font-semibold mb-4 flex items-center"><i class="fa fa-<?php echo $edit ? 'edit' : 'plus-circle'; ?> text-blue-600 mr-2"></i> <?php echo $edit ? 'Edit Menu Item' : 'Add New Menu Item'; ?></h2>
    <form method="POST">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?php echo $edit['id']; ?>"><?php endif; ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Label <span class="text-red-500">*</span></label><input type="text" name="label" value="<?php echo $edit ? htmlspecialchars($edit['label']) : ''; ?>" required class="w-full border rounded px-3 py-2"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">URL <span class="text-red-500">*</span></label><input type="text" name="url" value="<?php echo $edit ? htmlspecialchars($edit['url']) : ''; ?>" required class="w-full border rounded px-3 py-2" placeholder="e.g. index.php or #"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label><input type="number" name="sort_order" value="<?php echo $edit ? $edit['sort_order'] : '0'; ?>" class="w-full border rounded px-3 py-2"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Parent Item</label>
                <select name="parent_id" class="w-full border rounded px-3 py-2">
                    <option value="0">— Top Level (No Parent) —</option>
                    <?php $parent_items = mysqli_query($conn, "SELECT * FROM menu_items WHERE parent_id = 0 ORDER BY sort_order ASC"); ?>
                    <?php mysqli_data_seek($parent_items, 0); while ($p = mysqli_fetch_assoc($parent_items)): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo ($edit && $edit['parent_id'] == $p['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['label']); ?></option>
                    <?php endwhile; ?>
                </select>
                <p class="text-xs text-gray-400 mt-1">Select a parent to make this a dropdown child</p>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                <select name="location" class="w-full border rounded px-3 py-2">
                    <option value="header" <?php echo ($edit && $edit['location'] == 'header') ? 'selected' : ''; ?>>Header Menu</option>
                    <option value="footer" <?php echo ($edit && $edit['location'] == 'footer') ? 'selected' : ''; ?>>Footer Menu</option>
                    <option value="both" <?php echo ($edit && $edit['location'] == 'both') ? 'selected' : ''; ?>>Both</option>
                </select>
            </div>
            <div class="flex items-end space-x-4 pb-1">
                <label class="flex items-center bg-gray-50 px-3 py-2 rounded border cursor-pointer hover:bg-gray-100">
                    <input type="checkbox" name="status" value="1" <?php echo (!$edit || $edit['status']) ? 'checked' : ''; ?> class="mr-2"> Active
                </label>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition shadow"><i class="fa fa-save mr-1"></i> <?php echo $edit ? 'Update' : 'Save'; ?></button>
                <a href="menus.php" class="text-gray-600 px-4 py-2 border rounded hover:bg-gray-50 transition"><i class="fa fa-times"></i> Cancel</a>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>

<?php renderMenuTable('header'); ?>
<?php renderMenuTable('footer'); ?>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
['Header', 'Footer'].forEach(function(loc) {
    var el = document.getElementById('sortableMenu' + loc);
    if (el) {
        new Sortable(el, {
            handle: '.fa-grip-vertical',
            animation: 150,
            onEnd: function() {
                var ids = [];
                el.querySelectorAll('tr[data-id]').forEach(function(tr) {
                    ids.push(tr.dataset.id);
                });
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'menus.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.send('reorder=1&ids=' + ids.join(','));
            }
        });
    }
});
</script>
<?php include 'footer.php'; ?>
