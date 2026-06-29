<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$page_title = 'Blog Categories';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();
checkPermission('blog', 'view');

$msg = '';
$error = '';

$has_meta_title = @mysqli_fetch_assoc(mysqli_query($conn, "SHOW COLUMNS FROM blog_categories LIKE 'meta_title'"));
if (!$has_meta_title) {
    @mysqli_query($conn, "ALTER TABLE blog_categories ADD COLUMN meta_title VARCHAR(255) DEFAULT '' AFTER description");
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    checkPermission('blog', 'delete');
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "UPDATE blog_posts SET category_id = NULL WHERE category_id = $id");
    @mysqli_query($conn, "DELETE FROM blog_post_categories WHERE category_id = $id");
    mysqli_query($conn, "DELETE FROM blog_categories WHERE id = $id");
    header('Location: blog-categories.php?msg=deleted');
    exit;
}
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') $msg = 'Category deleted!';
    elseif ($_GET['msg'] == 'added') $msg = 'Category added!';
    elseif ($_GET['msg'] == 'updated') $msg = 'Category updated!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFToken($_POST['csrf_token'] ?? '');
    $name = sanitize($_POST['name'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $meta_title = sanitize($_POST['meta_title'] ?? '');
    $meta_description = sanitize($_POST['meta_description'] ?? '');
    $meta_keywords = sanitize($_POST['meta_keywords'] ?? '');
    $edit_id = (int)($_POST['edit_id'] ?? 0);

    if (!$name || !$slug) {
        $error = 'Name and slug are required!';
    } else {
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $slug)));
        $check = mysqli_query($conn, "SELECT id FROM blog_categories WHERE slug = '$slug'" . ($edit_id ? " AND id != $edit_id" : ""));
        if (mysqli_num_rows($check) > 0) {
            $error = 'Slug already exists!';
        } elseif ($edit_id) {
            checkPermission('blog', 'edit');
            mysqli_query($conn, "UPDATE blog_categories SET name='$name', slug='$slug', description='$description', meta_title='$meta_title', meta_description='$meta_description', meta_keywords='$meta_keywords' WHERE id=$edit_id");
            header('Location: blog-categories.php?msg=updated');
            exit;
        } else {
            checkPermission('blog', 'create');
            mysqli_query($conn, "INSERT INTO blog_categories (name, slug, description, meta_title, meta_description, meta_keywords) VALUES ('$name', '$slug', '$description', '$meta_title', '$meta_description', '$meta_keywords')");
            header('Location: blog-categories.php?msg=added');
            exit;
        }
    }
}

$categories = mysqli_query($conn, "SELECT * FROM blog_categories ORDER BY sort_order ASC, name ASC");
?>
<?php include 'header.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Blog Categories</h1>
    <p class="text-gray-500 dark:text-gray-400">Manage blog post categories</p>
</div>
<?php if ($msg): ?><div class="bg-green-100 border border-green-400 text-green-700 dark:bg-green-900/30 dark:border-green-700 dark:text-green-300 px-4 py-3 rounded mb-4"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 dark:bg-red-900/30 dark:border-red-700 dark:text-red-300 px-4 py-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Add Category</h2>
        <form method="POST" id="categoryForm">
            <?= csrfField() ?>
            <input type="hidden" name="edit_id" id="editId" value="0">
            <div class="space-y-3">
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Name</label><input type="text" name="name" id="catName" required class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Slug</label><input type="text" name="slug" id="catSlug" required class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" placeholder="my-category"></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Description</label><textarea name="description" id="catDesc" rows="2" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"></textarea></div>
                <hr class="dark:border-gray-600">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">SEO</p>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Meta Title (SEO)</label><input type="text" name="meta_title" id="catMetaTitle" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" placeholder="Leave empty to use Category Name + Site Name"></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Meta Description</label><textarea name="meta_description" id="catMetaDesc" rows="2" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" placeholder="SEO description for this category"></textarea></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Meta Keywords</label><input type="text" name="meta_keywords" id="catMetaKw" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" placeholder="keyword1, keyword2"></div>
                <button type="submit" id="submitBtn" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 dark:hover:bg-blue-600"><i class="fa fa-plus mr-1"></i> Add Category</button>
                <button type="button" onclick="resetForm()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 hidden" id="cancelBtn"><i class="fa fa-times mr-1"></i> Cancel</button>
            </div>
        </form>
    </div>
    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700 border-b dark:border-gray-600">
                <tr>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Name</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Slug</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">SEO</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Posts</th>
                    <th class="text-right px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-gray-600">
                <?php while ($cat = mysqli_fetch_assoc($categories)):
                    $post_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM blog_post_categories WHERE category_id = {$cat['id']}"));
                    $has_seo = !empty($cat['meta_description']);
                ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-3 text-sm font-medium"><?php echo htmlspecialchars($cat['name']); ?></td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($cat['slug']); ?></td>
                    <td class="px-4 py-3 text-sm">
                        <?php if ($has_seo): ?>
                        <span class="text-green-600 dark:text-green-400"><i class="fa fa-check-circle"></i></span>
                        <?php else: ?>
                        <span class="text-gray-400 dark:text-gray-500"><i class="fa fa-minus-circle"></i></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-sm"><?php echo $post_count['c']; ?></td>
                    <td class="px-4 py-3 text-right">
                        <a href="../blog-category.php?slug=<?php echo htmlspecialchars($cat['slug']); ?>" target="_blank" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 mr-2" title="View"><i class="fa fa-eye"></i></a>
                        <button onclick="editCat(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($cat['slug'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($cat['description'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($cat['meta_title'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($cat['meta_description'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($cat['meta_keywords'] ?? '', ENT_QUOTES); ?>')" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 mr-2"><i class="fa fa-edit"></i></button>
                        <a href="?delete=<?php echo $cat['id']; ?>" onclick="return confirm('Delete this category? Related posts will become uncategorized.')" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"><i class="fa fa-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
function editCat(id, name, slug, desc, metaTitle, metaDesc, metaKw) {
    document.getElementById('editId').value = id;
    document.getElementById('catName').value = name;
    document.getElementById('catSlug').value = slug;
    document.getElementById('catDesc').value = desc;
    document.getElementById('catMetaTitle').value = metaTitle;
    document.getElementById('catMetaDesc').value = metaDesc;
    document.getElementById('catMetaKw').value = metaKw;
    document.getElementById('submitBtn').innerHTML = '<i class="fa fa-save mr-1"></i> Update Category';
    document.getElementById('cancelBtn').classList.remove('hidden');
}
function resetForm() {
    document.getElementById('editId').value = 0;
    document.getElementById('catName').value = '';
    document.getElementById('catSlug').value = '';
    document.getElementById('catDesc').value = '';
    document.getElementById('catMetaTitle').value = '';
    document.getElementById('catMetaDesc').value = '';
    document.getElementById('catMetaKw').value = '';
    document.getElementById('submitBtn').innerHTML = '<i class="fa fa-plus mr-1"></i> Add Category';
    document.getElementById('cancelBtn').classList.add('hidden');
}
</script>
<?php include 'footer.php'; ?>
