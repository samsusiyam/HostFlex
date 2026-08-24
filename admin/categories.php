<?php
$page_title = 'Hosting Categories';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

$success = '';
$error = '';

// Handle Reorder via AJAX
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

// Handle Delete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category_id'])) {
    $id = (int)$_POST['delete_category_id'];
    $del = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name, slug FROM categories WHERE id = $id"));
    if (mysqli_query($conn, "DELETE FROM categories WHERE id = $id")) {
        logActivity('Deleted Category', ($del['name'] ?? 'Unknown') . ' (ID: ' . $id . ')');
        $success = 'Category deleted successfully.';
    } else {
        $error = 'Database error: ' . mysqli_error($conn);
    }
}

// Handle Add / Edit via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $name = sanitize($_POST['name'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $image = sanitize($_POST['image'] ?? 'images/s.png');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $status = isset($_POST['status']) ? 1 : 0;

    $upload_dir = '../uploads/categories/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg'])) {
            $fname = 'cat_' . time() . '_' . rand(100,999) . '.' . $ext;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $upload_dir . $fname)) {
                $image = 'uploads/categories/' . $fname;
            }
        }
    }

    if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
        $id = (int)$_POST['category_id'];
        if (mysqli_query($conn, "UPDATE categories SET name='$name', slug='$slug', description='$description', image='$image', sort_order=$sort_order, status=$status WHERE id=$id")) {
            logActivity('Updated Category', $name . ' (ID: ' . $id . ')');
            $success = 'Category "' . htmlspecialchars($name) . '" updated successfully!';
        } else {
            $error = 'Database error: ' . mysqli_error($conn);
        }
    } else {
        if (mysqli_query($conn, "INSERT INTO categories (name, slug, description, image, sort_order, status) VALUES ('$name', '$slug', '$description', '$image', $sort_order, $status)")) {
            logActivity('Created Category', $name);
            $success = 'New category "' . htmlspecialchars($name) . '" created successfully!';
        } else {
            $error = 'Database error: ' . mysqli_error($conn);
        }
    }
}

$categories = mysqli_query($conn, "SELECT c.*, (SELECT COUNT(*) FROM hosting_plans p WHERE p.category = c.slug) as plan_count FROM categories c ORDER BY c.sort_order ASC");
$total_categories = mysqli_num_rows($categories);
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-folder-tree"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Hosting Categories</h1>
            </div>
            <p class="text-xs text-gray-500">Organize hosting service categories. Drag and drop rows to reorder.</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="openAddCategoryModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs cursor-pointer">
                <i class="fa-solid fa-plus"></i> Add Category
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

    <!-- Categories Table -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <div class="text-xs text-gray-500 font-semibold">
                Total Categories: <strong class="text-gray-900"><?php echo $total_categories; ?></strong>
            </div>
            <div class="text-[11px] text-gray-400">
                <i class="fa-solid fa-arrows-up-down mr-1"></i> Drag rows to reorder order on frontend
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/70 border-b border-gray-200 text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5 w-10 text-center">#</th>
                        <th class="px-4 py-3.5 w-16 text-center">Icon</th>
                        <th class="px-4 py-3.5">Category Name</th>
                        <th class="px-4 py-3.5">URL Slug</th>
                        <th class="px-4 py-3.5 text-center">Plans</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="sortableCategories" class="divide-y divide-gray-100 text-xs">
                    <?php if ($total_categories > 0): while ($cat = mysqli_fetch_assoc($categories)): 
                        $cat_json = htmlspecialchars(json_encode($cat), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr data-id="<?php echo $cat['id']; ?>" class="hover:bg-blue-50/20 transition cursor-move">
                        <td class="px-4 py-3.5 text-center text-gray-300 hover:text-gray-600">
                            <i class="fa-solid fa-grip-vertical"></i>
                        </td>
                        <td class="px-4 py-3.5 text-center">
                            <?php if (!empty($cat['image'])): ?>
                            <img src="/<?php echo ltrim($cat['image'], '/'); ?>" class="h-9 w-9 object-contain rounded-lg p-1 bg-gray-50 border border-gray-100 mx-auto" alt="">
                            <?php else: ?>
                            <div class="h-9 w-9 rounded-lg bg-gray-100 text-gray-400 flex items-center justify-center mx-auto text-sm">
                                <i class="fa-solid fa-folder"></i>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3.5 font-bold text-gray-900">
                            <div><?php echo htmlspecialchars($cat['name']); ?></div>
                            <?php if ($cat['description']): ?>
                            <div class="text-[11px] text-gray-400 font-normal truncate max-w-xs"><?php echo htmlspecialchars($cat['description']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3.5 text-gray-500 font-mono text-[11px]">
                            /category/<?php echo htmlspecialchars($cat['slug']); ?>
                        </td>
                        <td class="px-4 py-3.5 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100">
                                <?php echo $cat['plan_count']; ?> plans
                            </span>
                        </td>
                        <td class="px-4 py-3.5">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold <?php echo $cat['status'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'; ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?php echo $cat['status'] ? 'bg-emerald-500' : 'bg-rose-500'; ?>"></span>
                                <?php echo $cat['status'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" onclick='openEditCategoryModal(<?php echo $cat_json; ?>)' class="p-1.5 bg-gray-50 hover:bg-blue-50 text-blue-600 rounded-lg border border-gray-200 hover:border-blue-200 transition cursor-pointer" title="Edit Category">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </button>
                                <button type="button" onclick="openDeleteCategoryModal(<?php echo $cat['id']; ?>, '<?php echo addslashes($cat['name']); ?>', <?php echo $cat['plan_count']; ?>)" class="p-1.5 bg-gray-50 hover:bg-red-50 text-red-600 rounded-lg border border-gray-200 hover:border-red-200 transition cursor-pointer" title="Delete Category">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="7" class="px-4 py-16 text-center text-gray-400">
                            <i class="fa-solid fa-folder-open text-4xl text-gray-300 mb-2 block"></i>
                            <p class="font-bold text-gray-700">No categories found</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Click "Add Category" to create your first service category.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ==========================================
     POPUP MODAL: ADD / EDIT CATEGORY
=============================================== -->
<div id="categoryModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-100 my-8 animate-in fade-in duration-200">
        
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50/70">
            <div class="flex items-center gap-2">
                <span class="p-2 bg-blue-100 text-blue-700 rounded-lg text-xs" id="catModalIcon"><i class="fa-solid fa-plus"></i></span>
                <h3 class="text-sm font-bold text-gray-900" id="catModalTitle">Add New Category</h3>
            </div>
            <button type="button" onclick="closeCategoryModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg transition cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Modal Form Body -->
        <form method="POST" id="catModalForm" enctype="multipart/form-data">
            <input type="hidden" name="save_category" value="1">
            <input type="hidden" name="category_id" id="cat_id" value="">

            <div class="p-6 space-y-4 text-xs max-h-[75vh] overflow-y-auto">
                
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Category Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="cat_name" required placeholder="e.g. Shared Hosting" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none" onkeyup="autoGenerateSlug(this.value)">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">URL Slug <span class="text-red-500">*</span></label>
                    <input type="text" name="slug" id="cat_slug" required placeholder="e.g. shared-hosting" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none font-mono">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Description / Tagline</label>
                    <textarea name="description" id="cat_description" rows="3" placeholder="Short description of this hosting category..." class="w-full border border-gray-300 rounded-xl p-3 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea>
                </div>

                <!-- Image Icon & Upload -->
                <div class="bg-gray-50/60 p-4 rounded-xl border border-gray-200 space-y-3">
                    <label class="block font-bold text-gray-700">Category Icon / Image</label>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-white border border-gray-200 flex items-center justify-center shrink-0 overflow-hidden" id="catImagePreviewBox">
                            <img id="catImagePreview" src="/images/s.png" class="max-h-10 max-w-10 object-contain" alt="">
                        </div>
                        <div class="flex-1">
                            <input type="file" name="image_file" id="cat_image_file" accept="image/*" class="w-full border border-gray-300 rounded-lg p-1 text-[11px] bg-white file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[11px] file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" onchange="previewCategoryFile(this)">
                            <input type="text" name="image" id="cat_image_path" value="images/s.png" placeholder="Or enter image path..." class="w-full border border-gray-300 rounded-lg px-2.5 py-1 text-[11px] mt-1.5 focus:outline-none focus:border-blue-500" oninput="previewCategoryUrl(this.value)">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" id="cat_sort_order" value="0" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div class="pt-2 border-t border-gray-100">
                    <label class="flex items-center gap-2 cursor-pointer select-none font-semibold text-gray-700">
                        <input type="checkbox" name="status" id="cat_status" value="1" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span><i class="fa-solid fa-circle-check text-emerald-500 mr-1"></i> Active (Visible in Menus & Category list)</span>
                    </label>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t bg-gray-50">
                <button type="button" onclick="closeCategoryModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
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
<div id="deleteCategoryModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST">
            <input type="hidden" name="delete_category_id" id="delete_cat_id" value="">
            
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Delete Category?</h3>
                <p class="text-xs text-gray-500 mb-4">Are you sure you want to delete this category? This action cannot be undone.</p>
                
                <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 text-xs text-left mb-2">
                    <div class="font-bold text-gray-900" id="deleteCatName">Category Name</div>
                    <div class="text-gray-500 mt-0.5" id="deleteCatDetails">0 plans attached</div>
                </div>
                <p class="text-[11px] text-amber-600 font-semibold" id="deleteCatWarning"></p>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="closeDeleteCategoryModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-trash-can"></i> Yes, Delete Category
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
function openAddCategoryModal() {
    document.getElementById('catModalTitle').innerText = 'Add New Category';
    document.getElementById('catModalIcon').innerHTML = '<i class="fa-solid fa-plus"></i>';
    document.getElementById('cat_id').value = '';
    document.getElementById('cat_name').value = '';
    document.getElementById('cat_slug').value = '';
    document.getElementById('cat_description').value = '';
    document.getElementById('cat_image_path').value = 'images/s.png';
    document.getElementById('cat_image_file').value = '';
    document.getElementById('catImagePreview').src = '/images/s.png';
    document.getElementById('cat_sort_order').value = '0';
    document.getElementById('cat_status').checked = true;

    document.getElementById('categoryModal').classList.remove('hidden');
}

function openEditCategoryModal(cat) {
    document.getElementById('catModalTitle').innerText = 'Edit Category: ' + cat.name;
    document.getElementById('catModalIcon').innerHTML = '<i class="fa-solid fa-pen"></i>';
    document.getElementById('cat_id').value = cat.id;
    document.getElementById('cat_name').value = cat.name;
    document.getElementById('cat_slug').value = cat.slug;
    document.getElementById('cat_description').value = cat.description || '';
    document.getElementById('cat_image_path').value = cat.image || 'images/s.png';
    document.getElementById('cat_image_file').value = '';
    document.getElementById('catImagePreview').src = '/' + (cat.image || 'images/s.png').replace(/^\/+/, '');
    document.getElementById('cat_sort_order').value = cat.sort_order || 0;
    document.getElementById('cat_status').checked = (parseInt(cat.status) === 1);

    document.getElementById('categoryModal').classList.remove('hidden');
}

function closeCategoryModal() {
    document.getElementById('categoryModal').classList.add('hidden');
}

function autoGenerateSlug(val) {
    if (document.getElementById('cat_id').value === '') {
        document.getElementById('cat_slug').value = val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    }
}

function previewCategoryFile(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('catImagePreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewCategoryUrl(url) {
    if (url) {
        document.getElementById('catImagePreview').src = '/' + url.replace(/^\/+/, '');
    }
}

function openDeleteCategoryModal(id, name, planCount) {
    document.getElementById('delete_cat_id').value = id;
    document.getElementById('deleteCatName').innerText = name;
    document.getElementById('deleteCatDetails').innerText = planCount + ' hosting plan(s) assigned';
    if (planCount > 0) {
        document.getElementById('deleteCatWarning').innerText = '⚠️ Note: ' + planCount + ' plan(s) are attached to this category. Delete or reassign them first if needed.';
    } else {
        document.getElementById('deleteCatWarning').innerText = '';
    }
    document.getElementById('deleteCategoryModal').classList.remove('hidden');
}

function closeDeleteCategoryModal() {
    document.getElementById('deleteCategoryModal').classList.add('hidden');
}

// Drag & Drop Sorting with SortableJS
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

// Keyboard ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCategoryModal();
        closeDeleteCategoryModal();
    }
});
</script>

<?php include 'footer.php'; ?>
