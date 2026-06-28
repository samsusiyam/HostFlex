<?php
$page_title = 'Partners';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();
checkPermission('partners', 'view');

$msg = '';
$error = '';

$upload_dir = '../uploads/partners/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    checkPermission('partners', 'delete');
    $id = (int)$_GET['delete'];
    $p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT photo FROM partners WHERE id = $id"));
    if ($p && $p['photo']) { $p_path = realpath(__DIR__ . '/../' . $p['photo']); $p_dir = realpath(__DIR__ . '/../uploads/partners/'); if ($p_path && $p_dir && strpos($p_path, $p_dir) === 0 && file_exists($p_path)) unlink($p_path); }
    mysqli_query($conn, "DELETE FROM partners WHERE id = $id");
    header('Location: partners.php?msg=deleted');
    exit;
}
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') $msg = 'Deleted!';
    elseif ($_GET['msg'] == 'added') $msg = 'Added!';
    elseif ($_GET['msg'] == 'updated') $msg = 'Updated!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFToken($_POST['csrf_token'] ?? '');
    $name = sanitize($_POST['name'] ?? '');
    $edit_id = (int)($_POST['edit_id'] ?? 0);
    if (!$name) { $error = 'Name required!'; }
    else {
        $photo = isset($_POST['existing_photo']) ? sanitize($_POST['existing_photo']) : '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $v = validateImageUpload($_FILES['photo'], ['jpg','jpeg','png','gif','webp','svg']);
            if ($v === true) {
                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                $fname = 'partner_' . time() . '_' . rand(100,999) . '.' . $ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $fname);
                $uploaded = $upload_dir . $fname;
                resizeImage($uploaded, $uploaded, 400, 128);
                $photo = 'uploads/partners/' . $fname;
                if ($edit_id) { $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT photo FROM partners WHERE id = $edit_id")); if ($old && $old['photo']) { $old_path = realpath(__DIR__ . '/../' . $old['photo']); $safe_dir = realpath(__DIR__ . '/../uploads/partners/'); if ($old_path && $safe_dir && strpos($old_path, $safe_dir) === 0 && file_exists($old_path)) unlink($old_path); } }
            }
        }
        if ($edit_id) {
            checkPermission('partners', 'edit');
            mysqli_query($conn, "UPDATE partners SET name='$name', photo='$photo' WHERE id=$edit_id");
            header('Location: partners.php?msg=updated');
            exit;
        } else {
            checkPermission('partners', 'create');
            $max = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(sort_order) as m FROM partners"));
            $sort = ($max['m'] ?? 0) + 1;
            mysqli_query($conn, "INSERT INTO partners (name, photo, sort_order) VALUES ('$name', '$photo', $sort)");
            header('Location: partners.php?msg=added');
            exit;
        }
    }
}

$items = mysqli_query($conn, "SELECT * FROM partners ORDER BY sort_order ASC");
?>
<?php include 'header.php'; ?>
<div class="mb-6"><h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Partners</h1><p class="text-gray-500 dark:text-gray-400">Partner/Client company logos</p></div>
<?php if ($msg): ?><div class="bg-green-100 border border-green-400 text-green-700 dark:bg-green-900/30 dark:border-green-700 dark:text-green-300 px-4 py-3 rounded mb-4"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 dark:bg-red-900/30 dark:border-red-700 dark:text-red-300 px-4 py-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4" id="formTitle">Add Partner</h2>
        <form method="POST" enctype="multipart/form-data" id="itemForm">
            <?= csrfField() ?>
            <input type="hidden" name="edit_id" id="editId" value="0">
            <input type="hidden" name="existing_photo" id="existingPhoto" value="">
            <div class="space-y-3">
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Company Name</label><input type="text" name="name" id="fName" required class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Logo</label>
                    <input type="file" name="photo" id="photoInput" accept="image/*" class="w-full border rounded px-3 py-2 text-sm dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                    <div id="logoPreview" class="mt-2 hidden">
                        <div class="relative inline-block">
                            <img class="max-h-16 rounded border dark:border-gray-600">
                            <button type="button" onclick="removeLogo()" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600" title="Remove logo">&times;</button>
                        </div>
                    </div>
                </div>
                <button type="submit" id="submitBtn" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 dark:hover:bg-blue-600 w-full"><i class="fa fa-plus mr-1"></i> Add</button>
                <button type="button" onclick="resetForm()" class="bg-gray-300 text-gray-700 dark:bg-gray-600 dark:text-gray-200 px-4 py-2 rounded hover:bg-gray-400 dark:hover:bg-gray-500 w-full hidden" id="cancelBtn"><i class="fa fa-times mr-1"></i> Cancel</button>
            </div>
        </form>
    </div>
    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700 border-b dark:border-gray-600">
                <tr><th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Logo</th><th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Company</th><th class="text-right px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Actions</th></tr>
            </thead>
            <tbody class="divide-y dark:divide-gray-600">
                <?php while ($row = mysqli_fetch_assoc($items)): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-3"><?php if ($row['photo']): ?><img src="../<?php echo htmlspecialchars($row['photo']); ?>" class="h-10 object-contain"><?php else: ?><span class="text-gray-400 dark:text-gray-500 text-sm">No logo</span><?php endif; ?></td>
                    <td class="px-4 py-3 text-sm font-medium"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td class="px-4 py-3 text-right">
                        <button onclick="editItem(<?php echo $row['id']; ?>,<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES); ?>)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 mr-2"><i class="fa fa-edit"></i></button>
                        <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete?')" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"><i class="fa fa-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
document.getElementById('photoInput').addEventListener('change', function() {
    var prev = document.getElementById('logoPreview');
    var img = prev.querySelector('img');
    if (this.files && this.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) { img.src = e.target.result; prev.classList.remove('hidden'); };
        reader.readAsDataURL(this.files[0]);
    }
});
function removeLogo() {
    document.getElementById('photoInput').value = '';
    document.getElementById('existingPhoto').value = '';
    document.getElementById('logoPreview').classList.add('hidden');
}
function editItem(id, data) {
    document.getElementById('editId').value = id;
    document.getElementById('fName').value = data.name || '';
    document.getElementById('existingPhoto').value = data.photo || '';
    document.getElementById('formTitle').textContent = 'Edit Partner';
    document.getElementById('submitBtn').innerHTML = '<i class="fa fa-save mr-1"></i> Update';
    document.getElementById('cancelBtn').classList.remove('hidden');
    var prev = document.getElementById('logoPreview');
    if (data.photo) { prev.querySelector('img').src = '../' + data.photo; prev.classList.remove('hidden'); }
    else { prev.classList.add('hidden'); }
}
function resetForm() {
    document.getElementById('editId').value = 0;
    document.getElementById('fName').value = ''; document.getElementById('existingPhoto').value = '';
    document.getElementById('photoInput').value = '';
    document.getElementById('formTitle').textContent = 'Add Partner';
    document.getElementById('submitBtn').innerHTML = '<i class="fa fa-plus mr-1"></i> Add';
    document.getElementById('cancelBtn').classList.add('hidden');
    document.getElementById('logoPreview').classList.add('hidden');
}
</script>
<?php include 'footer.php'; ?>
