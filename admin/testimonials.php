<?php
$page_title = 'Testimonials';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();
checkPermission('testimonials', 'view');

$msg = '';
$error = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    checkPermission('testimonials', 'delete');
    $id = (int)$_GET['delete'];
    $t = mysqli_fetch_assoc(mysqli_query($conn, "SELECT photo FROM testimonials WHERE id = $id"));
    if ($t && $t['photo'] && file_exists('../' . $t['photo'])) unlink('../' . $t['photo']);
    mysqli_query($conn, "DELETE FROM testimonials WHERE id = $id");
    header('Location: testimonials.php?msg=deleted');
    exit;
}
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') $msg = 'Deleted!';
    elseif ($_GET['msg'] == 'added') $msg = 'Added!';
    elseif ($_GET['msg'] == 'updated') $msg = 'Updated!';
}

$upload_dir = '../uploads/testimonials/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFToken($_POST['csrf_token'] ?? '');
    $name = sanitize($_POST['name'] ?? '');
    $company = sanitize($_POST['company'] ?? '');
    $review = sanitize($_POST['review'] ?? '');
    $rating = min(5, max(0, (float)($_POST['rating'] ?? 5)));
    $edit_id = (int)($_POST['edit_id'] ?? 0);

    if (!$name || !$review) { $error = 'Name and review required!'; }
    else {
        $photo = isset($_POST['existing_photo']) ? sanitize($_POST['existing_photo']) : '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $v = validateImageUpload($_FILES['photo'], ['jpg','jpeg','png','gif','webp']);
            if ($v === true) {
                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                $fname = 'testimonial_' . time() . '_' . rand(100,999) . '.' . $ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $fname);
                $photo = 'uploads/testimonials/' . $fname;
                if ($edit_id) { $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT photo FROM testimonials WHERE id = $edit_id")); if ($old['photo'] && file_exists('../' . $old['photo'])) unlink('../' . $old['photo']); }
            }
        }
        if ($edit_id) {
            checkPermission('testimonials', 'edit');
            mysqli_query($conn, "UPDATE testimonials SET name='$name', company='$company', photo='$photo', rating=$rating, review='$review' WHERE id=$edit_id");
            header('Location: testimonials.php?msg=updated');
            exit;
        } else {
            checkPermission('testimonials', 'create');
            $max = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(sort_order) as m FROM testimonials"));
            $sort = ($max['m'] ?? 0) + 1;
            mysqli_query($conn, "INSERT INTO testimonials (name, company, photo, rating, review, sort_order) VALUES ('$name', '$company', '$photo', $rating, '$review', $sort)");
            header('Location: testimonials.php?msg=added');
            exit;
        }
    }
}

$items = mysqli_query($conn, "SELECT * FROM testimonials ORDER BY sort_order ASC");
?>
<?php include 'header.php'; ?>
<div class="mb-6"><h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Testimonials</h1><p class="text-gray-500 dark:text-gray-400">Client reviews and ratings</p></div>
<?php if ($msg): ?><div class="bg-green-100 border border-green-400 text-green-700 dark:bg-green-900/30 dark:border-green-700 dark:text-green-300 px-4 py-3 rounded mb-4" id="msgBox"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 dark:bg-red-900/30 dark:border-red-700 dark:text-red-300 px-4 py-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4" id="formTitle">Add Testimonial</h2>
        <form method="POST" enctype="multipart/form-data" id="itemForm">
            <?= csrfField() ?>
            <input type="hidden" name="edit_id" id="editId" value="0">
            <input type="hidden" name="existing_photo" id="existingPhoto" value="">
            <div class="space-y-3">
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Name</label><input type="text" name="name" id="fName" required class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Company</label><input type="text" name="company" id="fCompany" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Photo</label>
                    <input type="file" name="photo" accept="image/*" class="w-full border rounded px-3 py-2 text-sm dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                    <div id="photoPreview" class="mt-1 hidden"><img class="max-h-12 rounded dark:border-gray-600"></div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Rating</label>
                    <select name="rating" id="fRating" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                        <option value="<?php echo $i; ?>"><?php echo str_repeat('★', $i) . str_repeat('☆', 5-$i); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Review</label><textarea name="review" id="fReview" rows="4" required class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"></textarea></div>
                <button type="submit" id="submitBtn" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 dark:hover:bg-blue-600 w-full"><i class="fa fa-plus mr-1"></i> Add</button>
                <button type="button" onclick="resetForm()" class="bg-gray-300 text-gray-700 dark:bg-gray-600 dark:text-gray-200 px-4 py-2 rounded hover:bg-gray-400 dark:hover:bg-gray-500 w-full hidden" id="cancelBtn"><i class="fa fa-times mr-1"></i> Cancel</button>
            </div>
        </form>
    </div>
    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700 border-b dark:border-gray-600">
                <tr><th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Photo</th><th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Name</th><th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Company</th><th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Rating</th><th class="text-right px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Actions</th></tr>
            </thead>
            <tbody class="divide-y dark:divide-gray-600">
                <?php while ($row = mysqli_fetch_assoc($items)): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-3"><?php if ($row['photo']): ?><img src="../<?php echo htmlspecialchars($row['photo']); ?>" class="w-10 h-10 rounded-full object-cover"><?php else: ?><span class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 inline-block"></span><?php endif; ?></td>
                    <td class="px-4 py-3 text-sm font-medium"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($row['company'] ?: '-'); ?></td>
                    <td class="px-4 py-3 text-sm text-yellow-500"><?php echo str_repeat('★', (int)$row['rating']) . str_repeat('☆', 5-(int)$row['rating']); ?></td>
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
function editItem(id, data) {
    document.getElementById('editId').value = id;
    document.getElementById('fName').value = data.name;
    document.getElementById('fCompany').value = data.company || '';
    document.getElementById('fReview').value = data.review || '';
    document.getElementById('fRating').value = data.rating || 5;
    document.getElementById('existingPhoto').value = data.photo || '';
    document.getElementById('formTitle').textContent = 'Edit Testimonial';
    document.getElementById('submitBtn').innerHTML = '<i class="fa fa-save mr-1"></i> Update';
    document.getElementById('cancelBtn').classList.remove('hidden');
    var prev = document.getElementById('photoPreview');
    if (data.photo) { prev.querySelector('img').src = '../' + data.photo; prev.classList.remove('hidden'); }
    else { prev.classList.add('hidden'); }
}
function resetForm() {
    document.getElementById('editId').value = 0;
    document.getElementById('fName').value = ''; document.getElementById('fCompany').value = '';
    document.getElementById('fReview').value = ''; document.getElementById('fRating').value = 5;
    document.getElementById('existingPhoto').value = '';
    document.getElementById('formTitle').textContent = 'Add Testimonial';
    document.getElementById('submitBtn').innerHTML = '<i class="fa fa-plus mr-1"></i> Add';
    document.getElementById('cancelBtn').classList.add('hidden');
    document.getElementById('photoPreview').classList.add('hidden');
}
</script>
<?php include 'footer.php'; ?>
