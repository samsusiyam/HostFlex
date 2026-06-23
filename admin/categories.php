<?php
$page_title = 'Categories';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM categories WHERE id = $id");
    header('Location: categories.php');
    exit;
}

if (isset($_POST['reorder'])) {
    $ids = $_POST['ids'] ?? '';
    $ids_arr = explode(',', $ids);
    foreach ($ids_arr as $i => $id) {
        $id = (int)$id;
        $sort = ($i + 1) * 10;
        mysqli_query($conn, "UPDATE categories SET sort_order = $sort WHERE id = $id");
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['reorder'])) {
    validateCSRFToken($_POST['csrf_token'] ?? '');
    $name = sanitize($_POST['name']);
    $slug = sanitize($_POST['slug']);
    $description = sanitize($_POST['description']);
    $image = sanitize($_POST['image']);
    $sort_order = (int)$_POST['sort_order'];
    $status = isset($_POST['status']) ? 1 : 0;

    $upload_dir = '../uploads/categories/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $v = validateImageUpload($_FILES['image_file']);
        if ($v === true) {
            $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            $fname = 'cat_' . time() . '_' . rand(100,999) . '.' . $ext;
            move_uploaded_file($_FILES['image_file']['tmp_name'], $upload_dir . $fname);
            $image = 'uploads/categories/' . $fname;
        }
    }

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        mysqli_query($conn, "UPDATE categories SET name='$name', slug='$slug', description='$description', image='$image', sort_order=$sort_order, status=$status WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO categories (name, slug, description, image, sort_order, status) VALUES ('$name', '$slug', '$description', '$image', $sort_order, $status)");
    }
    header('Location: categories.php');
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $r = mysqli_query($conn, "SELECT * FROM categories WHERE id = $id");
    $edit = mysqli_fetch_assoc($r);
}
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY sort_order ASC");
?>
<?php include 'header.php'; ?>
<div class="mb-6 flex justify-between items-center">
    <div><h1 class="text-2xl font-bold text-gray-800">Categories</h1><p class="text-gray-500">Drag & drop to reorder</p></div>
    <a href="?action=add" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"><i class="fa fa-plus"></i> Add Category</a>
</div>

<?php if (isset($_GET['action']) || $edit): ?>
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4"><?php echo $edit ? 'Edit Category' : 'Add Category'; ?></h2>
    <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <?php if ($edit): ?><input type="hidden" name="id" value="<?php echo $edit['id']; ?>"><?php endif; ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Name</label><input type="text" name="name" value="<?php echo $edit ? htmlspecialchars($edit['name']) : ''; ?>" required class="w-full border rounded px-3 py-2"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Slug</label><input type="text" name="slug" value="<?php echo $edit ? htmlspecialchars($edit['slug']) : ''; ?>" required class="w-full border rounded px-3 py-2" placeholder="e.g. basic-web"></div>
            <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea name="description" rows="3" class="w-full border rounded px-3 py-2"><?php echo $edit ? htmlspecialchars($edit['description']) : ''; ?></textarea></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                <input type="file" name="image_file" accept="image/*" class="w-full border rounded px-3 py-2 text-sm">
                <input type="text" name="image" value="<?php echo $edit ? htmlspecialchars($edit['image']) : 'images/s.png'; ?>" class="w-full border rounded px-3 py-2 text-sm mt-1" placeholder="Or enter path">
                <?php if ($edit && $edit['image']): ?><img src="../<?php echo htmlspecialchars($edit['image']); ?>" class="mt-2 max-h-12 rounded"><?php endif; ?>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label><input type="number" name="sort_order" value="<?php echo $edit ? $edit['sort_order'] : '0'; ?>" class="w-full border rounded px-3 py-2"></div>
        </div>
        <div class="mt-4 flex items-center space-x-4">
            <label class="flex items-center"><input type="checkbox" name="status" value="1" <?php echo (!$edit || $edit['status']) ? 'checked' : ''; ?> class="mr-2"> Active</label>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700"><?php echo $edit ? 'Update' : 'Add'; ?></button>
            <a href="categories.php" class="text-gray-600 px-4 py-2 border rounded hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full">
        <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-12">#</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th></tr></thead>
        <tbody id="sortableCategories" class="divide-y divide-gray-200">
            <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
            <tr data-id="<?php echo $cat['id']; ?>" class="hover:bg-gray-50 cursor-move">
                <td class="px-6 py-4 text-sm text-gray-400"><i class="fa fa-grip-vertical"></i></td>
                <td class="px-6 py-4"><?php if ($cat['image']): ?><img src="../<?php echo htmlspecialchars($cat['image']); ?>" class="h-8 w-8 object-contain rounded"><?php endif; ?></td>
                <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($cat['name']); ?></td>
                <td class="px-6 py-4 text-sm text-gray-500"><?php echo $cat['slug']; ?></td>
                <td class="px-6 py-4 text-sm"><?php echo $cat['status'] ? '<span class="text-green-600">Active</span>' : '<span class="text-red-600">Inactive</span>'; ?></td>
                <td class="px-6 py-4 text-sm space-x-2">
                    <a href="/category.php?slug=<?php echo $cat['slug']; ?>" target="_blank" title="View on site" class="text-green-600 hover:text-green-800"><i class="fa fa-eye"></i></a>
                    <a href="?edit=<?php echo $cat['id']; ?>" class="text-blue-600 hover:text-blue-800"><i class="fa fa-edit"></i></a>
                    <a href="?delete=<?php echo $cat['id']; ?>" onclick="return confirm('Delete?')" class="text-red-600 hover:text-red-800"><i class="fa fa-trash"></i></a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
new Sortable(document.getElementById('sortableCategories'), {
    handle: '.fa-grip-vertical',
    animation: 150,
    onEnd: function() {
        var ids = [];
        document.querySelectorAll('#sortableCategories tr').forEach(function(tr) {
            ids.push(tr.dataset.id);
        });
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'categories.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('reorder=1&ids=' + ids.join(','));
    }
});
</script>
<?php include 'footer.php'; ?>
