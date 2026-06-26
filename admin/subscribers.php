<?php
$page_title = 'Newsletter Subscribers';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/mail.php';
checkAdminLogin();
checkPermission('subscribers', 'view');

$msg = '';
if (isset($_POST['send_newsletter'])) {
    checkPermission('subscribers', 'create');
    validateCSRFToken($_POST['csrf_token'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $selected = $_POST['selected_subs'] ?? [];
    if ($subject && $message) {
        require_once '../includes/mail.php';
        $where = "status = 'active'";
        if (!empty($selected)) {
            $ids = array_map('intval', $selected);
            $where = "id IN (" . implode(',', $ids) . ")";
        }
        $subs = mysqli_query($conn, "SELECT email, name FROM subscribers WHERE $where");
        $total = mysqli_num_rows($subs);
        $sent = 0;
        while ($sub = mysqli_fetch_assoc($subs)) {
            $personalized = "<p>Dear " . htmlspecialchars($sub['name'] ?: 'Subscriber') . ",</p>\n" . nl2br(htmlspecialchars($message));
            if (sendMail($sub['email'], $subject, $personalized)) $sent++;
        }
        $msg = "Newsletter sent to $sent of $total recipients.";
    } else {
        $msg = 'Please fill in both subject and message.';
    }
}
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    checkPermission('subscribers', 'delete');
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM subscribers WHERE id = $id");
    $msg = 'Subscriber deleted!';
}

$search = trim($_GET['search'] ?? '');
$where = '';
if ($search) {
    $search_esc = mysqli_real_escape_string($conn, $search);
    $where = "WHERE email LIKE '%$search_esc%' OR name LIKE '%$search_esc%'";
}
$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM subscribers $where"))['c'];
$page = max(1, (int)($_GET['p'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;
$pages = ceil($total / $per_page);

$result = mysqli_query($conn, "SELECT * FROM subscribers $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
?>
<?php include 'header.php'; ?>
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Newsletter Subscribers</h1>
        <p class="text-gray-500">View and manage email subscribers</p>
    </div>
    <div class="flex items-center gap-3">
        <span class="bg-blue-100 text-blue-800 text-sm font-semibold px-3 py-1 rounded-full">Total: <?php echo $total; ?></span>
        <button onclick="document.getElementById('newsletterModal').classList.remove('hidden')" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm"><i class="fa fa-envelope mr-1"></i> Send Email</button>
    </div>
</div>
<?php if ($msg): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $msg; ?></div><?php endif; ?>
<form method="GET" class="mb-4">
    <div class="flex gap-2">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by email or name..." class="border rounded px-3 py-2 flex-1 max-w-md">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"><i class="fa fa-search"></i></button>
        <?php if ($search): ?><a href="subscribers.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Clear</a><?php endif; ?>
    </div>
</form>
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 w-10"><input type="checkbox" id="selectAll" onchange="document.querySelectorAll('.sub-check').forEach(c=>c.checked=this.checked)"></th>
                <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Email</th>
                <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Name</th>
                <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Status</th>
                <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Subscribed</th>
                <th class="text-right px-4 py-3 text-sm font-semibold text-gray-600">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php if (mysqli_num_rows($result) > 0): while ($row = mysqli_fetch_assoc($result)): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3"><input type="checkbox" name="selected_subs[]" value="<?php echo $row['id']; ?>" class="sub-check h-4 w-4 text-blue-600 border-gray-300 rounded"></td>
                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($row['name'] ?: '-'); ?></td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-1 rounded-full <?php echo $row['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'; ?>">
                        <?php echo $row['status']; ?>
                    </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-500"><?php echo date('d M Y, g:i a', strtotime($row['created_at'])); ?></td>
                <td class="px-4 py-3 text-right">
                    <a href="?delete=<?php echo $row['id']; ?>&p=<?php echo $page; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" onclick="return confirm('Delete this subscriber?')" class="text-red-600 hover:text-red-800"><i class="fa fa-trash"></i></a>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No subscribers found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php if ($pages > 1): ?>
<div class="flex justify-center mt-4 gap-1">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a href="?p=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="px-3 py-1 rounded <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>"><?php echo $i; ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>
<div id="newsletterModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4">
        <form method="POST" id="newsletterForm">
            <?= csrfField() ?>
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="text-lg font-semibold text-gray-800"><i class="fa fa-envelope mr-2 text-green-600"></i>Send Newsletter</h3>
                <button type="button" onclick="document.getElementById('newsletterModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600"><i class="fa fa-times text-xl"></i></button>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <input type="text" name="subject" required class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="Email subject">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                    <textarea name="message" rows="8" required class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="Write your newsletter message here..."></textarea>
                    <p class="text-xs text-gray-400 mt-1">Leaving subscribers unchecked sends to all active. Check specific ones to send only to them.</p>
                </div>
                <div id="selectedSubsContainer"></div>
            </div>
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                <button type="button" onclick="document.getElementById('newsletterModal').classList.add('hidden')" class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300">Cancel</button>
                <button type="submit" name="send_newsletter" id="sendNewsletterBtn" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"><i class="fa fa-paper-plane mr-1"></i> Send Newsletter</button>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('newsletterForm').addEventListener('submit', function() {
    var container = document.getElementById('selectedSubsContainer');
    container.innerHTML = '';
    document.querySelectorAll('.sub-check:checked').forEach(function(cb) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_subs[]';
        input.value = cb.value;
        container.appendChild(input);
    });
});
</script>
<?php include 'footer.php'; ?>
