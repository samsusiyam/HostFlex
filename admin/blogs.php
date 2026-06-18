<?php
$page_title = 'Blog Posts';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM users WHERE id = " . (int)$_SESSION['admin_id']));
$admin_username = $admin['username'] ?? 'Admin';

$msg = '';
$error = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $post = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image, title FROM blog_posts WHERE id = $id"));
    if ($post && $post['image'] && file_exists('../' . $post['image'])) {
        unlink('../' . $post['image']);
    }
    mysqli_query($conn, "DELETE FROM blog_posts WHERE id = $id");
    logActivity('Deleted Post', ($post['title'] ?? 'Unknown') . ' (ID: ' . $id . ')');
    header('Location: blogs.php?msg=deleted');
    exit;
}
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') $msg = 'Post deleted!';
    elseif ($_GET['msg'] == 'added') $msg = 'Post created!';
    elseif ($_GET['msg'] == 'updated') $msg = 'Post updated!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inline_cat'])) {
    validateCSRFToken($_POST['csrf_token'] ?? '');
    $action = $_POST['inline_cat'];
    if ($action === 'add' || $action === 'edit') {
        $name = sanitize($_POST['name'] ?? '');
        $edit_id = (int)($_POST['edit_id'] ?? 0);
        if ($name) {
            $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $name)));
            if (!mysqli_num_rows(mysqli_query($conn, "SELECT id FROM blog_categories WHERE slug='$slug'" . ($edit_id ? " AND id!=$edit_id" : "")))) {
                if ($edit_id) {
                    mysqli_query($conn, "UPDATE blog_categories SET name='$name', slug='$slug' WHERE id=$edit_id");
                } else {
                    mysqli_query($conn, "INSERT INTO blog_categories (name, slug) VALUES ('$name', '$slug')");
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            mysqli_query($conn, "UPDATE blog_posts SET category_id = NULL WHERE category_id = $id");
            mysqli_query($conn, "DELETE FROM blog_categories WHERE id = $id");
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_post'])) {
    validateCSRFToken($_POST['csrf_token'] ?? '');
    $title = sanitize($_POST['title'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    $content = $_POST['content'] ?? '';
    $excerpt = sanitize($_POST['excerpt'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $author = sanitize($_POST['author'] ?? $admin_username);
    $status = isset($_POST['status']) ? 1 : 0;
    $meta_description = sanitize($_POST['meta_description'] ?? '');
    $meta_keywords = sanitize($_POST['meta_keywords'] ?? '');
    $edit_id = (int)($_POST['edit_id'] ?? 0);

    if (!$title || !$slug) {
        $error = 'Title and slug are required!';
    } else {
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $slug)));
        $content_esc = mysqli_real_escape_string($conn, $content);
        $cat_sql = $category_id > 0 ? $category_id : 'NULL';

        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $v = validateImageUpload($_FILES['image'], ['jpg','jpeg','png','gif','webp']);
            if ($v === true) {
                $upload_dir = '../uploads/blog/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $fname = 'blog_' . time() . '_' . rand(100,999) . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $fname);
                $image = 'uploads/blog/' . $fname;
            }
        }

        if ($edit_id) {
            if (!$image) {
                $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM blog_posts WHERE id = $edit_id"));
                $image = $old['image'];
            }
            mysqli_query($conn, "UPDATE blog_posts SET title='$title', slug='$slug', content='$content_esc', excerpt='$excerpt', image='$image', category_id=$cat_sql, author='$author', status=$status, meta_description='$meta_description', meta_keywords='$meta_keywords' WHERE id=$edit_id");
            logActivity('Updated Post', $title . ' (ID: ' . $edit_id . ')');
            header('Location: blogs.php?msg=updated');
            exit;
        } else {
            mysqli_query($conn, "INSERT INTO blog_posts (title, slug, content, excerpt, image, category_id, author, status, meta_description, meta_keywords) VALUES ('$title', '$slug', '$content_esc', '$excerpt', '$image', $cat_sql, '$author', $status, '$meta_description', '$meta_keywords')");
            logActivity('Created Post', $title);
            header('Location: blogs.php?msg=added');
            exit;
        }
    }
}

$edit_post = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_post = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM blog_posts WHERE id = " . (int)$_GET['edit']));
}

$search = trim($_GET['search'] ?? '');
$where = '';
if ($search) {
    $search_esc = mysqli_real_escape_string($conn, $search);
    $where = "WHERE p.title LIKE '%$search_esc%'";
}
$page = max(1, (int)($_GET['p'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;
$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM blog_posts p $where"))['c'];
$pages = ceil($total / $per_page);

$posts = mysqli_query($conn, "SELECT p.*, c.name as category_name FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id $where ORDER BY p.created_at DESC LIMIT $per_page OFFSET $offset");
$categories = mysqli_query($conn, "SELECT * FROM blog_categories WHERE status = 1 ORDER BY name");
$all_cats = [];
while ($c = mysqli_fetch_assoc($categories)) $all_cats[] = $c;
?>
<?php include 'header.php'; ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Blog Posts</h1>
        <p class="text-gray-500">Create and manage blog posts</p>
    </div>
    <a href="?edit=0" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm <?php echo $edit_post !== null ? 'hidden' : ''; ?>"><i class="fa fa-plus mr-1"></i> New Post</a>
</div>
<?php if ($msg): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>

<?php if ($edit_post !== null || isset($_GET['edit'])): ?>
<?php $ep = $edit_post; $is_new = !$ep && isset($_GET['edit']) && $_GET['edit'] == 0; $ep = $ep ?: ['id'=>0,'title'=>'','slug'=>'','content'=>'','excerpt'=>'','image'=>'','category_id'=>0,'author'=>$admin_username,'status'=>1,'meta_description'=>'','meta_keywords'=>'']; ?>
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4"><?php echo $is_new ? 'New Post' : 'Edit Post'; ?></h2>
    <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="edit_id" value="<?php echo $ep['id']; ?>">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <div class="space-y-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Title</label><input type="text" name="title" value="<?php echo htmlspecialchars($ep['title']); ?>" required class="w-full border rounded px-3 py-2"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Slug</label><input type="text" name="slug" value="<?php echo htmlspecialchars($ep['slug']); ?>" required class="w-full border rounded px-3 py-2" placeholder="my-post-title"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                        <textarea name="content" id="blogContent" rows="16" class="w-full border rounded px-3 py-2"><?php echo htmlspecialchars($ep['content']); ?></textarea>
                    </div>
                </div>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <div class="flex gap-2">
                        <select name="category_id" id="catSelect" class="flex-1 border rounded px-3 py-2">
                            <option value="">None</option>
                            <?php foreach ($all_cats as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $ep['category_id'] == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" onclick="showCatForm()" class="bg-green-600 text-white px-3 py-2 rounded text-sm" title="Add Category"><i class="fa fa-plus"></i></button>
                        <button type="button" onclick="editCat()" class="bg-blue-600 text-white px-3 py-2 rounded text-sm" title="Edit Category"><i class="fa fa-pencil-alt"></i></button>
                        <button type="button" onclick="deleteCat()" class="bg-red-600 text-white px-3 py-2 rounded text-sm" title="Delete Category"><i class="fa fa-trash"></i></button>
                    </div>
                    <div id="catForm" class="hidden mt-2 p-2 bg-gray-50 rounded border flex gap-2 items-center">
                        <input type="text" id="catName" placeholder="Category name" class="flex-1 border rounded px-3 py-2 text-sm">
                        <input type="hidden" id="catEditId" value="">
                        <button type="button" onclick="saveCat()" class="bg-blue-600 text-white px-3 py-2 rounded text-sm">Save</button>
                        <button type="button" onclick="hideCatForm()" class="bg-gray-300 text-gray-700 px-3 py-2 rounded text-sm">Cancel</button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Manage categories inline</p>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Author</label>
                    <input type="text" name="author" value="<?php echo htmlspecialchars($ep['author']); ?>" class="w-full border rounded px-3 py-2">
                    <p class="text-xs text-gray-400 mt-1">Auto-filled with your username</p>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Featured Image</label>
                    <input type="file" name="image" accept="image/*" class="w-full border rounded px-3 py-2 text-sm" onchange="document.getElementById('blogImgPreview').src=window.URL.createObjectURL(this.files[0]);document.getElementById('blogImgPreview').classList.remove('hidden')">
                    <?php if ($ep['image']): ?>
                    <img id="blogImgPreview" src="../<?php echo htmlspecialchars($ep['image']); ?>" class="mt-2 max-h-32 rounded border">
                    <?php else: ?>
                    <img id="blogImgPreview" class="mt-2 max-h-32 rounded border hidden">
                    <?php endif; ?>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Excerpt</label><textarea name="excerpt" rows="2" class="w-full border rounded px-3 py-2 text-sm"><?php echo htmlspecialchars($ep['excerpt']); ?></textarea></div>
                <hr>
                <p class="text-sm font-semibold text-gray-700">SEO</p>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label><textarea name="meta_description" rows="2" class="w-full border rounded px-3 py-2 text-sm"><?php echo htmlspecialchars($ep['meta_description']); ?></textarea></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label><input type="text" name="meta_keywords" value="<?php echo htmlspecialchars($ep['meta_keywords']); ?>" class="w-full border rounded px-3 py-2 text-sm"></div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="status" value="1" id="postStatus" <?php echo $ep['status'] ? 'checked' : ''; ?> class="h-5 w-5 text-blue-600 border rounded">
                    <label for="postStatus" class="text-sm font-medium text-gray-700">Published</label>
                </div>
                <button type="submit" name="save_post" class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 w-full"><i class="fa fa-save mr-1"></i> <?php echo $is_new ? 'Create Post' : 'Update Post'; ?></button>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-4 border-b flex items-center justify-between">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search posts..." class="border rounded px-3 py-2 text-sm">
            <button type="submit" class="bg-blue-600 text-white px-3 py-2 rounded text-sm"><i class="fa fa-search"></i></button>
            <?php if ($search): ?><a href="blogs.php" class="bg-gray-300 text-gray-700 px-3 py-2 rounded text-sm">Clear</a><?php endif; ?>
        </form>
        <span class="text-sm text-gray-500"><?php echo $total; ?> total</span>
    </div>
    <table class="w-full">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Title</th>
                <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Category</th>
                <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Author</th>
                <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Status</th>
                <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Date</th>
                <th class="text-right px-4 py-3 text-sm font-semibold text-gray-600">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php if (mysqli_num_rows($posts) > 0): while ($post = mysqli_fetch_assoc($posts)): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm font-medium">
                    <?php if ($post['image']): ?><img src="../<?php echo htmlspecialchars($post['image']); ?>" class="w-8 h-8 object-cover rounded inline mr-2"><?php endif; ?>
                    <?php echo htmlspecialchars($post['title']); ?>
                </td>
                <td class="px-4 py-3 text-sm text-gray-500"><?php echo htmlspecialchars($post['category_name'] ?? 'Uncategorized'); ?></td>
                <td class="px-4 py-3 text-sm text-gray-500"><?php echo htmlspecialchars($post['author'] ?: '-'); ?></td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-1 rounded-full <?php echo $post['status'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'; ?>"><?php echo $post['status'] ? 'Published' : 'Draft'; ?></span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-500"><?php echo date('d M Y', strtotime($post['created_at'])); ?></td>
                <td class="px-4 py-3 text-right">
                    <a href="../blog.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" target="_blank" class="text-green-600 hover:text-green-800 mr-2" title="View"><i class="fa fa-eye"></i></a>
                    <a href="?edit=<?php echo $post['id']; ?>" class="text-blue-600 hover:text-blue-800 mr-2" title="Edit"><i class="fa fa-edit"></i></a>
                    <a href="?delete=<?php echo $post['id']; ?>" onclick="return confirm('Delete this post?')" class="text-red-600 hover:text-red-800" title="Delete"><i class="fa fa-trash"></i></a>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No posts yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php if ($pages > 1): ?>
<div class="flex justify-center mt-4 gap-1">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a href="?p=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="px-3 py-1 rounded text-sm <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>"><?php echo $i; ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.10.9/tinymce.min.js"></script>
<script>
tinymce.init({
    selector: '#blogContent',
    height: 500,
    menubar: true,
    plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen media table code help wordcount',
    toolbar: 'undo redo | formatselect | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | image link media table | code fullscreen help',
    images_upload_handler: function (blobInfo, success, failure) {
        var formData = new FormData();
        formData.append('file', blobInfo.blob(), blobInfo.filename());
        formData.append('upload', 'tinymce');
        fetch('upload.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => { if (d.location) success(d.location); else failure('Upload failed'); })
            .catch(() => failure('Upload error'));
    },
    setup: function (editor) {
        editor.on('submit', function (e) {
            tinymce.triggerSave();
        });
    }
});
$('form').on('submit', function() {
    tinymce.triggerSave();
});
function showCatForm() {
    $('#catForm').removeClass('hidden');
    $('#catEditId').val('');
    $('#catName').val('').focus();
}
function hideCatForm() {
    $('#catForm').addClass('hidden');
    $('#catName').val('');
    $('#catEditId').val('');
}
function saveCat() {
    var name = $('#catName').val().trim();
    if (!name) return alert('Enter a category name');
    var id = $('#catEditId').val();
    var data = 'inline_cat=' + (id ? 'edit' : 'add') + '&name=' + encodeURIComponent(name);
    if (id) data += '&edit_id=' + id;
    $.post('', data, function() { location.reload(); });
}
function editCat() {
    var sel = document.getElementById('catSelect');
    if (!sel.value) return alert('Select a category first');
    var text = sel.options[sel.selectedIndex].text;
    $('#catForm').removeClass('hidden');
    $('#catEditId').val(sel.value);
    $('#catName').val(text).focus();
}
function deleteCat() {
    var sel = document.getElementById('catSelect');
    if (!sel.value) return alert('Select a category first');
    if (!confirm('Delete category "' + sel.options[sel.selectedIndex].text + '"?')) return;
    $.post('', 'inline_cat=delete&id=' + sel.value, function() { location.reload(); });
}
</script>
<?php include 'footer.php'; ?>
