<?php
$page_title = 'Blog Categories';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

$msg = '';
$error = '';

// Handle Delete Category
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $cat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM blog_categories WHERE id = $id"));
    mysqli_query($conn, "UPDATE blog_posts SET category_id = NULL WHERE category_id = $id");
    mysqli_query($conn, "DELETE FROM blog_categories WHERE id = $id");
    logActivity('Deleted Category', ($cat['name'] ?? 'Unknown') . " (ID: $id)");
    header('Location: blog-categories.php?msg=deleted');
    exit;
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') $msg = 'Category deleted successfully!';
    elseif ($_GET['msg'] === 'added') $msg = 'Category created successfully!';
    elseif ($_GET['msg'] === 'updated') $msg = 'Category updated successfully!';
}

// Handle Add / Edit Category Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize(trim($_POST['name'] ?? ''));
    $slug = sanitize(trim($_POST['slug'] ?? ''));
    $description = sanitize(trim($_POST['description'] ?? ''));
    $edit_id = (int)($_POST['edit_id'] ?? 0);

    if (empty($slug) && !empty($name)) {
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $name)));
    } else {
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $slug)));
    }

    if (!$name) {
        $error = 'Category name is required!';
    } elseif (!$slug) {
        $error = 'Category slug is required!';
    } else {
        $check = mysqli_query($conn, "SELECT id FROM blog_categories WHERE slug = '$slug'" . ($edit_id ? " AND id != $edit_id" : ""));
        if (mysqli_num_rows($check) > 0) {
            $error = 'Category slug already exists. Please choose a different slug.';
        } elseif ($edit_id) {
            mysqli_query($conn, "UPDATE blog_categories SET name='$name', slug='$slug', description='$description' WHERE id=$edit_id");
            logActivity('Updated Category', "$name (ID: $edit_id)");
            header('Location: blog-categories.php?msg=updated');
            exit;
        } else {
            mysqli_query($conn, "INSERT INTO blog_categories (name, slug, description, status) VALUES ('$name', '$slug', '$description', 1)");
            $new_id = mysqli_insert_id($conn);
            logActivity('Created Category', "$name (ID: $new_id)");
            header('Location: blog-categories.php?msg=added');
            exit;
        }
    }
}

// Search filter
$search = trim($_GET['search'] ?? '');
$where_sql = "";
if (!empty($search)) {
    $search_esc = mysqli_real_escape_string($conn, $search);
    $where_sql = "WHERE (name LIKE '%$search_esc%' OR slug LIKE '%$search_esc%' OR description LIKE '%$search_esc%')";
}

$categories_query = "SELECT * FROM blog_categories $where_sql ORDER BY name ASC";
$categories = mysqli_query($conn, $categories_query);
$total_cats = mysqli_num_rows($categories);
?>
<?php include 'header.php'; ?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900">Categories</h1>
            <span class="text-xs bg-gray-200 text-gray-700 font-semibold px-2.5 py-0.5 rounded-full"><?php echo $total_cats; ?> total</span>
        </div>
        <p class="text-gray-500 text-xs md:text-sm mt-1">Organize your blog articles into distinct subject categories</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="blog-edit.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3.5 py-1.5 rounded-lg text-xs md:text-sm shadow-sm transition inline-flex items-center gap-1.5">
            <i class="fa fa-plus text-xs"></i> Add New Post
        </a>
        <a href="settings-permalinks.php" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold px-3.5 py-1.5 rounded-lg text-xs md:text-sm shadow-sm transition inline-flex items-center gap-1.5">
            <i class="fa fa-link text-blue-600 text-xs"></i> Permalink Settings
        </a>
    </div>
</div>

<?php if ($msg): ?>
<div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg text-sm mb-6 flex items-center gap-2 shadow-sm">
    <i class="fa fa-check-circle text-green-500"></i>
    <span><?php echo $msg; ?></span>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg text-sm mb-6 flex items-center gap-2 shadow-sm">
    <i class="fa fa-circle-exclamation text-red-500"></i>
    <span><?php echo $error; ?></span>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Left Column: WordPress-Style Add / Edit Category Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 h-fit">
        <h2 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2" id="formHeader">
            <i class="fa fa-folder-plus text-blue-600"></i> Add New Category
        </h2>
        
        <form method="POST" id="categoryForm" class="space-y-4">
            <input type="hidden" name="edit_id" id="editId" value="0">
            
            <!-- Category Name -->
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Name</label>
                <input type="text" name="name" id="catName" required placeholder="e.g. Web Hosting"
                       class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:border-blue-600 focus:outline-none transition">
                <p class="text-[11px] text-gray-400 mt-1">The name is how it appears on your site.</p>
            </div>

            <!-- Category Slug -->
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Slug</label>
                <input type="text" name="slug" id="catSlug" required placeholder="web-hosting"
                       class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm font-mono focus:border-blue-600 focus:outline-none transition">
                <p class="text-[11px] text-gray-400 mt-1">The "slug" is the URL-friendly version of the name (lowercase, numbers, and hyphens).</p>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Description</label>
                <textarea name="description" id="catDesc" rows="3" placeholder="Optional category overview for SEO and theme archives..."
                          class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:border-blue-600 focus:outline-none transition"></textarea>
                <p class="text-[11px] text-gray-400 mt-1">The description is displayed on category archive pages.</p>
            </div>

            <!-- Buttons -->
            <div class="pt-2 flex items-center gap-2">
                <button type="submit" id="submitBtn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-5 rounded-lg text-sm shadow-sm transition flex items-center gap-1.5">
                    <i class="fa fa-plus"></i> Add New Category
                </button>
                <button type="button" onclick="resetCatForm()" id="cancelBtn" class="hidden bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 px-4 rounded-lg text-sm transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>

    <!-- Right Column: WordPress-Style Categories Table -->
    <div class="lg:col-span-2 space-y-4">
        
        <!-- Search Bar -->
        <div class="bg-white p-3.5 rounded-xl shadow-sm border border-gray-200 flex items-center justify-between gap-4">
            <form method="GET" class="flex items-center gap-2 w-full sm:w-72">
                <div class="relative flex-1">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search categories..."
                           class="w-full pl-8 pr-3 py-1.5 border border-gray-300 rounded-lg text-xs focus:border-blue-600 focus:outline-none">
                    <i class="fa fa-search absolute left-2.5 top-2 text-gray-400 text-xs"></i>
                </div>
                <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-3 py-1.5 rounded-lg text-xs transition">Search</button>
                <?php if ($search): ?><a href="blog-categories.php" class="text-xs text-red-600 hover:underline">Clear</a><?php endif; ?>
            </form>
            <div class="text-xs text-gray-500 font-medium">
                <?php echo $total_cats; ?> items
            </div>
        </div>

        <!-- Categories Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50/75 border-b border-gray-200 text-xs uppercase font-bold text-gray-500 tracking-wider">
                        <tr>
                            <th scope="col" class="py-3.5 px-4">Name</th>
                            <th scope="col" class="py-3.5 px-4">Description</th>
                            <th scope="col" class="py-3.5 px-4">Slug</th>
                            <th scope="col" class="py-3.5 px-4 text-center">Count</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (mysqli_num_rows($categories) > 0): ?>
                            <?php while ($cat = mysqli_fetch_assoc($categories)):
                                $count_query = mysqli_query($conn, "SELECT COUNT(*) as c FROM blog_posts WHERE category_id = " . (int)$cat['id']);
                                $post_count = mysqli_fetch_assoc($count_query)['c'] ?? 0;
                            ?>
                            <tr class="hover:bg-blue-50/30 transition group">
                                <!-- Name + WordPress Hover Action Links -->
                                <td class="py-3 px-4">
                                    <span class="font-bold text-gray-900 text-sm block">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </span>
                                    
                                    <!-- Hover Quick Action Row (WordPress Style) -->
                                    <div class="flex items-center gap-2 mt-1 text-xs opacity-80 group-hover:opacity-100 transition">
                                        <button type="button" onclick="editCat(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($cat['slug'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($cat['description'] ?? '', ENT_QUOTES); ?>')" class="text-blue-600 hover:text-blue-800 font-medium">Edit</button>
                                        <span class="text-gray-300">|</span>
                                        <a href="/blog/category/<?php echo urlencode($cat['slug']); ?>" target="_blank" class="text-emerald-600 hover:text-emerald-800 font-medium">View</a>
                                        <span class="text-gray-300">|</span>
                                        <a href="?delete=<?php echo $cat['id']; ?>" onclick="return confirm('Delete this category? Related posts will become Uncategorized.')" class="text-red-600 hover:text-red-800 font-medium">Delete</a>
                                    </div>
                                </td>

                                <!-- Description -->
                                <td class="py-3 px-4 text-xs text-gray-500 max-w-xs">
                                    <?php echo htmlspecialchars($cat['description'] ?: '—'); ?>
                                </td>

                                <!-- Slug -->
                                <td class="py-3 px-4 text-xs font-mono text-gray-600">
                                    <?php echo htmlspecialchars($cat['slug']); ?>
                                </td>

                                <!-- Count -->
                                <td class="py-3 px-4 text-center">
                                    <a href="blogs.php?cat=<?php echo $cat['id']; ?>" class="inline-block font-bold text-xs bg-blue-50 hover:bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full border border-blue-200 transition" title="View posts in this category">
                                        <?php echo $post_count; ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="py-10 text-center text-gray-400">
                                    <i class="fa fa-folder-open text-3xl mb-2 block text-gray-300"></i>
                                    <p class="font-semibold text-gray-700">No categories found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<script>
$(document).ready(function() {
    // Auto-generate slug from name in Add mode
    $('#catName').on('input', function() {
        if ($('#editId').val() === '0') {
            var val = $(this).val();
            var slug = val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            $('#catSlug').val(slug);
        }
    });
});

function editCat(id, name, slug, desc) {
    $('#editId').val(id);
    $('#catName').val(name).focus();
    $('#catSlug').val(slug);
    $('#catDesc').val(desc);
    $('#formHeader').html('<i class="fa fa-pencil-alt text-blue-600"></i> Edit Category');
    $('#submitBtn').html('<i class="fa fa-check"></i> Update Category');
    $('#cancelBtn').removeClass('hidden');
    $('html, body').animate({ scrollTop: $('#categoryForm').offset().top - 100 }, 200);
}

function resetCatForm() {
    $('#editId').val('0');
    $('#catName').val('');
    $('#catSlug').val('');
    $('#catDesc').val('');
    $('#formHeader').html('<i class="fa fa-folder-plus text-blue-600"></i> Add New Category');
    $('#submitBtn').html('<i class="fa fa-plus"></i> Add New Category');
    $('#cancelBtn').addClass('hidden');
}
</script>

<?php include 'footer.php'; ?>
