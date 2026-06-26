<?php
$page_title = 'Contact Messages';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();
checkPermission('contacts', 'view');

if (isset($_GET['read'])) {
    $id = (int)$_GET['read'];
    mysqli_query($conn, "UPDATE contacts SET is_read = 1 WHERE id = $id");
    header('Location: contacts.php');
    exit;
}

if (isset($_GET['delete'])) {
    checkPermission('contacts', 'delete');
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM contacts WHERE id = $id");
    header('Location: contacts.php');
    exit;
}

$view_message = null;
if (isset($_GET['view'])) {
    $id = (int)$_GET['view'];
    $result = mysqli_query($conn, "SELECT * FROM contacts WHERE id = $id");
    $view_message = mysqli_fetch_assoc($result);
    if ($view_message && !$view_message['is_read']) {
        mysqli_query($conn, "UPDATE contacts SET is_read = 1 WHERE id = $id");
    }
}

$contacts = mysqli_query($conn, "SELECT * FROM contacts ORDER BY created_at DESC");
?>
<?php include 'header.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Contact Messages</h1>
    <p class="text-gray-500">View messages from the contact form</p>
</div>

<?php if ($view_message): ?>
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex justify-between items-start mb-4">
        <h2 class="text-lg font-semibold">Message Details</h2>
        <a href="contacts.php" class="text-gray-500 hover:text-gray-700"><i class="fa fa-times"></i></a>
    </div>
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div><strong>Name:</strong> <?php echo htmlspecialchars($view_message['name']); ?></div>
        <div><strong>Email:</strong> <?php echo htmlspecialchars($view_message['email']); ?></div>
        <div><strong>Subject:</strong> <?php echo htmlspecialchars($view_message['subject']); ?></div>
        <div><strong>Date:</strong> <?php echo date('d M Y h:i A', strtotime($view_message['created_at'])); ?></div>
    </div>
    <div class="bg-gray-50 p-4 rounded">
        <strong>Message:</strong>
        <p class="mt-2 text-gray-700"><?php echo nl2br(htmlspecialchars($view_message['message'])); ?></p>
    </div>
    <?php if ($view_message['file']): ?>
    <div class="mt-4">
        <strong>Attachment:</strong> <a href="../<?php echo htmlspecialchars($view_message['file'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="text-blue-600"><?php echo htmlspecialchars(basename($view_message['file']), ENT_QUOTES, 'UTF-8'); ?></a>
    </div>
    <?php endif; ?>
    <div class="mt-4 space-x-2">
        <a href="mailto:<?php echo htmlspecialchars($view_message['email'], ENT_QUOTES, 'UTF-8'); ?>" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"><i class="fa fa-reply"></i> Reply</a>
        <a href="?delete=<?php echo $view_message['id']; ?>" onclick="return confirm('Delete this message?')" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700"><i class="fa fa-trash"></i> Delete</a>
    </div>
</div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <?php if (mysqli_num_rows($contacts) > 0): ?>
                <?php while ($msg = mysqli_fetch_assoc($contacts)): ?>
                <tr class="<?php echo !$msg['is_read'] ? 'bg-blue-50 font-medium' : ''; ?>">
                    <td class="px-6 py-4"><?php echo $msg['is_read'] ? '<span class="text-gray-400"><i class="fa fa-envelope-open"></i></span>' : '<span class="text-blue-600"><i class="fa fa-envelope"></i></span>'; ?></td>
                    <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($msg['name']); ?></td>
                    <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($msg['subject']); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo date('d M Y', strtotime($msg['created_at'])); ?></td>
                    <td class="px-6 py-4 text-sm space-x-2">
                        <a href="?view=<?php echo $msg['id']; ?>" class="text-blue-600 hover:text-blue-800"><i class="fa fa-eye"></i></a>
                        <a href="?delete=<?php echo $msg['id']; ?>" onclick="return confirm('Delete?')" class="text-red-600 hover:text-red-800"><i class="fa fa-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No messages yet</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include 'footer.php'; ?>
