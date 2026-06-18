<?php
$page_title = 'Pages';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM pages WHERE id = $id");
    header('Location: pages.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFToken($_POST['csrf_token'] ?? '');
    $title = sanitize($_POST['title']);
    $slug = sanitize($_POST['slug']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $meta_description = sanitize($_POST['meta_description']);
    $meta_keywords = sanitize($_POST['meta_keywords']);
    $status = isset($_POST['status']) ? 1 : 0;

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        mysqli_query($conn, "UPDATE pages SET title='$title', slug='$slug', content='$content', meta_description='$meta_description', meta_keywords='$meta_keywords', status=$status WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO pages (title, slug, content, meta_description, meta_keywords, status) VALUES ('$title', '$slug', '$content', '$meta_description', '$meta_keywords', $status)");
    }
    header('Location: pages.php');
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $r = mysqli_query($conn, "SELECT * FROM pages WHERE id = $id");
    $edit = mysqli_fetch_assoc($r);
}
$pages = mysqli_query($conn, "SELECT * FROM pages ORDER BY title ASC");
?>
<?php include 'header.php'; ?>
<div class="mb-6 flex justify-between items-center">
    <div><h1 class="text-2xl font-bold text-gray-800">CMS Pages</h1><p class="text-gray-500">Manage About, Terms, Policy & custom pages</p></div>
    <a href="?action=add" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"><i class="fa fa-plus"></i> Add Page</a>
</div>

<?php if (isset($_GET['action']) || $edit): ?>
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4"><?php echo $edit ? 'Edit Page' : 'Add Page'; ?></h2>
    <form method="POST">
        <?= csrfField() ?>
        <?php if ($edit): ?><input type="hidden" name="id" value="<?php echo $edit['id']; ?>"><?php endif; ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Title</label><input type="text" name="title" value="<?php echo $edit ? htmlspecialchars($edit['title']) : ''; ?>" required class="w-full border rounded px-3 py-2"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Slug</label><input type="text" name="slug" value="<?php echo $edit ? htmlspecialchars($edit['slug']) : ''; ?>" required class="w-full border rounded px-3 py-2" placeholder="e.g. about-us"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label><input type="text" name="meta_description" value="<?php echo $edit ? htmlspecialchars($edit['meta_description']) : ''; ?>" class="w-full border rounded px-3 py-2"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label><input type="text" name="meta_keywords" value="<?php echo $edit ? htmlspecialchars($edit['meta_keywords']) : ''; ?>" class="w-full border rounded px-3 py-2"></div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Content (HTML)</label>
                <textarea name="content" id="pageContent" rows="15" class="w-full border rounded px-3 py-2"><?php echo $edit ? htmlspecialchars($edit['content']) : ''; ?></textarea>
            </div>
        </div>
        <div class="mt-4 flex items-center space-x-4">
            <label class="flex items-center"><input type="checkbox" name="status" value="1" <?php echo (!$edit || $edit['status']) ? 'checked' : ''; ?> class="mr-2"> Active</label>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700"><?php echo $edit ? 'Update' : 'Add'; ?></button>
            <a href="pages.php" class="text-gray-600 px-4 py-2 border rounded hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.10.9/tinymce.min.js"></script>
<script>
tinymce.init({
    selector: '#pageContent',
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
</script>
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full">
        <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th></tr></thead>
        <tbody class="divide-y divide-gray-200">
            <?php while ($p = mysqli_fetch_assoc($pages)): ?>
            <tr><td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($p['title']); ?></td><td class="px-6 py-4 text-sm"><?php echo $p['slug']; ?></td><td class="px-6 py-4 text-sm"><?php echo $p['status'] ? '<span class="text-green-600">Active</span>' : '<span class="text-red-600">Inactive</span>'; ?></td><td class="px-6 py-4 text-sm space-x-2"><a href="?edit=<?php echo $p['id']; ?>" class="text-blue-600"><i class="fa fa-edit"></i></a> <a href="/page/<?php echo $p['slug']; ?>" target="_blank" class="text-green-600"><i class="fa fa-eye"></i></a> <a href="?delete=<?php echo $p['id']; ?>" onclick="return confirm('Delete?')" class="text-red-600"><i class="fa fa-trash"></i></a></td></tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include 'footer.php'; ?>
