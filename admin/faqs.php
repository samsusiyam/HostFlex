<?php
$page_title = 'FAQs';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();
checkPermission('faqs', 'view');

$msg = '';
$error = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    checkPermission('faqs', 'delete');
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM faqs WHERE id = $id");
    header('Location: faqs.php?msg=deleted');
    exit;
}
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') $msg = 'Deleted!';
    elseif ($_GET['msg'] == 'added') $msg = 'Added!';
    elseif ($_GET['msg'] == 'updated') $msg = 'Updated!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFToken($_POST['csrf_token'] ?? '');
    $question = sanitize($_POST['question'] ?? '');
    $answer = $_POST['answer'] ?? '';
    $edit_id = (int)($_POST['edit_id'] ?? 0);

    if (!$question || !$answer) { $error = 'Question and answer required!'; }
    else {
        $answer_esc = mysqli_real_escape_string($conn, $answer);
        if ($edit_id) {
            checkPermission('faqs', 'edit');
            mysqli_query($conn, "UPDATE faqs SET question='$question', answer='$answer_esc' WHERE id=$edit_id");
            header('Location: faqs.php?msg=updated');
            exit;
        } else {
            checkPermission('faqs', 'create');
            $max = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(sort_order) as m FROM faqs"));
            $sort = ($max['m'] ?? 0) + 1;
            mysqli_query($conn, "INSERT INTO faqs (question, answer, sort_order) VALUES ('$question', '$answer_esc', $sort)");
            header('Location: faqs.php?msg=added');
            exit;
        }
    }
}

$items = mysqli_query($conn, "SELECT * FROM faqs ORDER BY sort_order ASC");
?>
<?php include 'header.php'; ?>
<div class="mb-6"><h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">FAQs</h1><p class="text-gray-500 dark:text-gray-400">Frequently asked questions</p></div>
<?php if ($msg): ?><div class="bg-green-100 border border-green-400 text-green-700 dark:bg-green-900/30 dark:border-green-700 dark:text-green-300 px-4 py-3 rounded mb-4"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 dark:bg-red-900/30 dark:border-red-700 dark:text-red-300 px-4 py-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4" id="formTitle">Add FAQ</h2>
        <form method="POST" id="itemForm">
            <?= csrfField() ?>
            <input type="hidden" name="edit_id" id="editId" value="0">
            <div class="space-y-3">
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Question</label><input type="text" name="question" id="fQuestion" required class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Answer</label><textarea name="answer" id="fAnswer" rows="5" required class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"></textarea></div>
                <button type="submit" id="submitBtn" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 dark:hover:bg-blue-600 w-full"><i class="fa fa-plus mr-1"></i> Add</button>
                <button type="button" onclick="resetForm()" class="bg-gray-300 text-gray-700 dark:bg-gray-600 dark:text-gray-200 px-4 py-2 rounded hover:bg-gray-400 dark:hover:bg-gray-500 w-full hidden" id="cancelBtn"><i class="fa fa-times mr-1"></i> Cancel</button>
            </div>
        </form>
    </div>
    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700 border-b dark:border-gray-600">
                <tr><th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Question</th><th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Answer</th><th class="text-right px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Actions</th></tr>
            </thead>
            <tbody class="divide-y dark:divide-gray-600">
                <?php while ($row = mysqli_fetch_assoc($items)): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-3 text-sm font-medium max-w-xs truncate"><?php echo htmlspecialchars($row['question']); ?></td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 max-w-md truncate"><?php echo strip_tags(substr($row['answer'], 0, 100)); ?>...</td>
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
    document.getElementById('fQuestion').value = data.question || '';
    document.getElementById('fAnswer').value = data.answer || '';
    document.getElementById('formTitle').textContent = 'Edit FAQ';
    document.getElementById('submitBtn').innerHTML = '<i class="fa fa-save mr-1"></i> Update';
    document.getElementById('cancelBtn').classList.remove('hidden');
}
function resetForm() {
    document.getElementById('editId').value = 0;
    document.getElementById('fQuestion').value = ''; document.getElementById('fAnswer').value = '';
    document.getElementById('formTitle').textContent = 'Add FAQ';
    document.getElementById('submitBtn').innerHTML = '<i class="fa fa-plus mr-1"></i> Add';
    document.getElementById('cancelBtn').classList.add('hidden');
}
</script>
<?php include 'footer.php'; ?>
