<?php
$page_title = 'Blog Categories';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

$msg = '';
$error = '';

// Handle Delete Category via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category_id'])) {
    $id = (int)$_POST['delete_category_id'];
    $cat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM blog_categories WHERE id = $id"));
    mysqli_query($conn, "UPDATE blog_posts SET category_id = NULL WHERE category_id = $id");
    if (mysqli_query($conn, "DELETE FROM blog_categories WHERE id = $id")) {
        logActivity('Deleted Category', ($cat['name'] ?? 'Unknown') . " (ID: $id)");
        $msg = 'Blog category deleted successfully! Associated posts set to Uncategorized.';
    } else {
        $error = 'Database error: ' . mysqli_error($conn);
    }
}

// Handle Add / Edit Category via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $name = sanitize(trim($_POST['name'] ?? ''));
    $slug = sanitize(trim($_POST['slug'] ?? ''));
    $description = sanitize(trim($_POST['description'] ?? ''));
    $edit_id = (int)($_POST['category_id'] ?? 0);

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
            if (mysqli_query($conn, "UPDATE blog_categories SET name='$name', slug='$slug', description='$description' WHERE id=$edit_id")) {
                logActivity('Updated Category', "$name (ID: $edit_id)");
                $msg = 'Category "' . htmlspecialchars($name) . '" updated successfully!';
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        } else {
            if (mysqli_query($conn, "INSERT INTO blog_categories (name, slug, description, status) VALUES ('$name', '$slug', '$description', 1)")) {
                $new_id = mysqli_insert_id($conn);
                logActivity('Created Category', "$name (ID: $new_id)");
                $msg = 'New blog category "' . htmlspecialchars($name) . '" created successfully!';
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        }
    }
}

$categories_query = "SELECT c.*, (SELECT COUNT(*) FROM blog_posts p WHERE p.category_id = c.id AND p.deleted_at IS NULL) as post_count FROM blog_categories c ORDER BY c.name ASC";
$categories = mysqli_query($conn, $categories_query);
$total_cats = mysqli_num_rows($categories);
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-newspaper"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Blog Categories</h1>
            </div>
            <p class="text-xs text-gray-500">Organize articles and tutorials into distinct topics and permalinks.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="openAddBlogCatModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs cursor-pointer">
                <i class="fa-solid fa-plus"></i> Add Category
            </button>
            <a href="blog-edit.php" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold px-4 py-2.5 rounded-xl text-xs shadow-xs transition flex items-center gap-1.5">
                <i class="fa-solid fa-pen-to-square text-blue-600"></i> New Post
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($msg): ?>
    <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between bg-emerald-50 text-emerald-800 border border-emerald-200">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span><?php echo htmlspecialchars($msg); ?></span>
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

    <!-- Categories Table -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <div class="text-xs text-gray-500 font-semibold">
                Total Topics: <strong class="text-gray-900"><?php echo $total_cats; ?></strong>
            </div>
            <div class="relative w-64">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="blogCatSearchInput" onkeyup="filterBlogCatRows(this.value)" placeholder="Search category..." class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-8 pr-3 py-1.5 text-xs text-gray-800 focus:bg-white focus:outline-none focus:border-blue-600 transition">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/70 border-b border-gray-200 text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5">Category Name</th>
                        <th class="px-4 py-3.5">URL Permalink</th>
                        <th class="px-4 py-3.5">Description</th>
                        <th class="px-4 py-3.5 text-center">Articles</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    <?php if ($total_cats > 0): while ($cat = mysqli_fetch_assoc($categories)): 
                        $cat_json = htmlspecialchars(json_encode($cat), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr class="blog-cat-row hover:bg-blue-50/20 transition" data-name="<?php echo strtolower($cat['name'] . ' ' . $cat['slug'] . ' ' . $cat['description']); ?>">
                        <td class="px-4 py-3.5 font-bold text-gray-900">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-folder text-blue-500"></i>
                                <span><?php echo htmlspecialchars($cat['name']); ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 font-mono text-[11px] text-gray-500">
                            /blog/category/<?php echo htmlspecialchars($cat['slug']); ?>
                        </td>
                        <td class="px-4 py-3.5 text-gray-500 max-w-sm truncate">
                            <?php echo htmlspecialchars($cat['description'] ?: '—'); ?>
                        </td>
                        <td class="px-4 py-3.5 text-center">
                            <a href="blogs.php?cat=<?php echo $cat['id']; ?>" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 transition" title="View posts in this category">
                                <?php echo $cat['post_count']; ?> posts
                            </a>
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="/blog/category/<?php echo urlencode($cat['slug']); ?>" target="_blank" class="p-1.5 bg-gray-50 hover:bg-emerald-50 text-emerald-600 rounded-lg border border-gray-200 hover:border-emerald-200 transition cursor-pointer" title="View Public Page">
                                    <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                                </a>
                                <button type="button" onclick='openEditBlogCatModal(<?php echo $cat_json; ?>)' class="p-1.5 bg-gray-50 hover:bg-blue-50 text-blue-600 rounded-lg border border-gray-200 hover:border-blue-200 transition cursor-pointer" title="Edit Category">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </button>
                                <button type="button" onclick="openDeleteBlogCatModal(<?php echo $cat['id']; ?>, '<?php echo addslashes($cat['name']); ?>', <?php echo $cat['post_count']; ?>)" class="p-1.5 bg-gray-50 hover:bg-red-50 text-red-600 rounded-lg border border-gray-200 hover:border-red-200 transition cursor-pointer" title="Delete Category">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="5" class="px-4 py-16 text-center text-gray-400">
                            <i class="fa-solid fa-newspaper text-4xl text-gray-300 mb-2 block"></i>
                            <p class="font-bold text-gray-700">No blog categories found</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Click "Add Category" to organize your blog posts.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ==========================================
     POPUP MODAL: ADD / EDIT BLOG CATEGORY
=============================================== -->
<div id="blogCatModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-100 my-8 animate-in fade-in duration-200">
        
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50/70">
            <div class="flex items-center gap-2">
                <span class="p-2 bg-blue-100 text-blue-700 rounded-lg text-xs" id="blogCatModalIcon"><i class="fa-solid fa-plus"></i></span>
                <h3 class="text-sm font-bold text-gray-900" id="blogCatModalTitle">Add Blog Category</h3>
            </div>
            <button type="button" onclick="closeBlogCatModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg transition cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Modal Form Body -->
        <form method="POST" id="blogCatModalForm">
            <input type="hidden" name="save_category" value="1">
            <input type="hidden" name="category_id" id="blog_cat_id" value="">

            <div class="p-6 space-y-4 text-xs max-h-[75vh] overflow-y-auto">
                
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Category Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="blog_cat_name" required placeholder="e.g. WordPress, Server Tips" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none" onkeyup="autoGenBlogSlug(this.value)">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Slug <span class="text-red-500">*</span></label>
                    <input type="text" name="slug" id="blog_cat_slug" required placeholder="e.g. server-tips" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none font-mono">
                    <p class="text-[11px] text-gray-400 mt-1">Used in URL: /blog/category/your-slug</p>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="blog_cat_description" rows="3" placeholder="Brief summary of what articles belong to this topic..." class="w-full border border-gray-300 rounded-xl p-3 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t bg-gray-50">
                <button type="button" onclick="closeBlogCatModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-floppy-disk"></i> Save Category
                </button>
            </div>
        </form>

    </div>
</div>

<!-- ==========================================
     POPUP MODAL: DELETE CONFIRMATION
=============================================== -->
<div id="deleteBlogCatModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST">
            <input type="hidden" name="delete_category_id" id="delete_blog_cat_id_input" value="">
            
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Delete Blog Category?</h3>
                <p class="text-xs text-gray-500 mb-4">Are you sure you want to delete this category? Associated posts will become Uncategorized.</p>
                
                <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 text-xs text-left mb-2">
                    <div class="font-bold text-gray-900" id="deleteBlogCatName">Category Name</div>
                    <div class="text-gray-500 mt-0.5" id="deleteBlogCatPosts">0 posts attached</div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="closeDeleteBlogCatModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-trash-can"></i> Yes, Delete Category
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddBlogCatModal() {
    document.getElementById('blogCatModalTitle').innerText = 'Add Blog Category';
    document.getElementById('blogCatModalIcon').innerHTML = '<i class="fa-solid fa-plus"></i>';
    document.getElementById('blog_cat_id').value = '';
    document.getElementById('blog_cat_name').value = '';
    document.getElementById('blog_cat_slug').value = '';
    document.getElementById('blog_cat_description').value = '';

    document.getElementById('blogCatModal').classList.remove('hidden');
}

function openEditBlogCatModal(cat) {
    document.getElementById('blogCatModalTitle').innerText = 'Edit Category: ' + cat.name;
    document.getElementById('blogCatModalIcon').innerHTML = '<i class="fa-solid fa-pen"></i>';
    document.getElementById('blog_cat_id').value = cat.id;
    document.getElementById('blog_cat_name').value = cat.name;
    document.getElementById('blog_cat_slug').value = cat.slug;
    document.getElementById('blog_cat_description').value = cat.description || '';

    document.getElementById('blogCatModal').classList.remove('hidden');
}

function closeBlogCatModal() {
    document.getElementById('blogCatModal').classList.add('hidden');
}

function autoGenBlogSlug(val) {
    if (document.getElementById('blog_cat_id').value === '') {
        document.getElementById('blog_cat_slug').value = val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    }
}

function openDeleteBlogCatModal(id, name, postCount) {
    document.getElementById('delete_blog_cat_id_input').value = id;
    document.getElementById('deleteBlogCatName').innerText = name;
    document.getElementById('deleteBlogCatPosts').innerText = postCount + ' article(s) currently assigned';
    document.getElementById('deleteBlogCatModal').classList.remove('hidden');
}

function closeDeleteBlogCatModal() {
    document.getElementById('deleteBlogCatModal').classList.add('hidden');
}

function filterBlogCatRows(q) {
    q = q.trim().toLowerCase();
    document.querySelectorAll('.blog-cat-row').forEach(row => {
        var text = row.dataset.name || '';
        if (!q || text.includes(q)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Keyboard ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeBlogCatModal();
        closeDeleteBlogCatModal();
    }
});
</script>

<?php include 'footer.php'; ?>
