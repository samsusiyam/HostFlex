<?php
$page_title = 'Newsletter Subscribers';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/mail.php';
checkAdminLogin();

$msg = '';
$msg_type = 'success';

// Handle Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=subscribers_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Email', 'Name', 'Status', 'Subscribed At']);
    $res = mysqli_query($conn, "SELECT id, email, name, status, created_at FROM subscribers ORDER BY id DESC");
    while ($r = mysqli_fetch_assoc($res)) {
        fputcsv($output, $r);
    }
    fclose($output);
    exit;
}

// Handle Delete All
if (isset($_POST['delete_all_subscribers'])) {
    if (mysqli_query($conn, "DELETE FROM subscribers")) {
        logActivity('Deleted All Subscribers', 'All records cleared');
        $msg = 'All subscribers have been deleted successfully.';
    } else {
        $msg = 'Database error: ' . mysqli_error($conn);
        $msg_type = 'danger';
    }
}

// Handle Bulk Delete Selected
if (isset($_POST['bulk_delete_subscribers'])) {
    $selected_ids = $_POST['selected_subs'] ?? [];
    if (!empty($selected_ids) && is_array($selected_ids)) {
        $ids = array_map('intval', $selected_ids);
        $ids_str = implode(',', $ids);
        if (mysqli_query($conn, "DELETE FROM subscribers WHERE id IN ($ids_str)")) {
            $count = count($ids);
            logActivity('Bulk Deleted Subscribers', "$count subscribers deleted");
            $msg = "$count selected subscriber(s) deleted successfully.";
        } else {
            $msg = 'Database error: ' . mysqli_error($conn);
            $msg_type = 'danger';
        }
    } else {
        $msg = 'No subscribers selected for deletion.';
        $msg_type = 'warning';
    }
}

// Handle Send Newsletter
if (isset($_POST['send_newsletter'])) {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $selected = $_POST['selected_subs'] ?? [];
    if ($subject && $message) {
        $where = "status = 'active'";
        if (!empty($selected)) {
            $ids = array_map('intval', $selected);
            $where = "id IN (" . implode(',', $ids) . ")";
        }
        $subs = mysqli_query($conn, "SELECT email, name FROM subscribers WHERE $where");
        $total_subs = mysqli_num_rows($subs);
        $sent = 0;
        while ($sub = mysqli_fetch_assoc($subs)) {
            $personalized = "<p>Dear " . htmlspecialchars($sub['name'] ?: 'Subscriber') . ",</p>\n" . nl2br(htmlspecialchars($message));
            if (sendMail($sub['email'], $subject, $personalized)) $sent++;
        }
        $msg = "Newsletter sent to $sent of $total_subs recipients.";
    } else {
        $msg = 'Please fill in both subject and message.';
        $msg_type = 'danger';
    }
}

// Handle Individual Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if (mysqli_query($conn, "DELETE FROM subscribers WHERE id = $id")) {
        logActivity('Deleted Subscriber', "ID: $id");
        $msg = 'Subscriber deleted successfully.';
    }
}

$search = trim($_GET['search'] ?? '');
$where = '';
if ($search) {
    $search_esc = mysqli_real_escape_string($conn, $search);
    $where = "WHERE email LIKE '%$search_esc%' OR name LIKE '%$search_esc%'";
}
$total = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM subscribers $where"))['c'] ?? 0);
$page = max(1, (int)($_GET['p'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;
$pages = ceil($total / $per_page);

$result = mysqli_query($conn, "SELECT * FROM subscribers $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
?>
<?php include 'header.php'; ?>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Newsletter Subscribers</h1>
        <p class="text-xs text-gray-500 mt-1">View, email, and manage newsletter subscribers</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <span class="bg-blue-50 border border-blue-200 text-blue-800 text-xs font-bold px-3 py-1.5 rounded-lg">
            Total: <?php echo number_format($total); ?>
        </span>
        <a href="?export=csv" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3.5 py-1.5 rounded-lg text-xs font-semibold border transition flex items-center gap-1.5 shadow-xs">
            <i class="fa fa-download text-[11px]"></i> Export CSV
        </a>
        <button type="button" onclick="document.getElementById('newsletterModal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 shadow-xs">
            <i class="fa fa-paper-plane text-[11px]"></i> Send Email
        </button>
        <?php if ($total > 0): ?>
        <form method="POST" onsubmit="return confirm('⚠️ ARE YOU SURE?\n\nThis will permanently delete ALL subscribers from the database.\nThis action CANNOT be undone!')" class="inline">
            <button type="submit" name="delete_all_subscribers" class="bg-red-600 hover:bg-red-700 text-white px-3.5 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 shadow-xs">
                <i class="fa fa-trash-can text-[11px]"></i> Delete All
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Alert Notice -->
<?php if ($msg): ?>
<div class="mb-4 p-3.5 rounded-xl text-xs font-semibold flex items-center justify-between <?php echo $msg_type === 'danger' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'; ?>">
    <div class="flex items-center gap-2">
        <i class="fa <?php echo $msg_type === 'danger' ? 'fa-circle-exclamation' : 'fa-circle-check'; ?> text-sm"></i>
        <span><?php echo htmlspecialchars($msg); ?></span>
    </div>
    <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-600"><i class="fa fa-xmark"></i></button>
</div>
<?php endif; ?>

<!-- Search and Bulk Action Toolbar -->
<div class="bg-white p-4 rounded-xl border border-gray-200 shadow-xs mb-4">
    <div class="flex flex-col md:flex-row items-center justify-between gap-3">
        
        <!-- Search Form -->
        <form method="GET" class="flex gap-2 w-full md:w-auto flex-1 max-w-md">
            <div class="relative flex-1">
                <i class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by email or name..." class="w-full border border-gray-300 rounded-lg pl-8 pr-3 py-1.5 text-xs text-gray-800 focus:outline-none focus:border-blue-600">
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-1.5 rounded-lg text-xs font-semibold transition">Search</button>
            <?php if ($search): ?>
            <a href="subscribers.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center">Clear</a>
            <?php endif; ?>
        </form>

        <!-- Bulk Action Bar -->
        <div id="bulkActionBar" class="hidden flex items-center gap-2 w-full md:w-auto justify-end">
            <span id="selectedCountBadge" class="text-xs text-gray-500 font-semibold">0 selected</span>
            <button type="button" onclick="submitBulkDelete()" class="bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1">
                <i class="fa fa-trash-can"></i> Delete Selected
            </button>
        </div>

    </div>
</div>

<!-- Subscribers Table Form (For Bulk Delete & Select) -->
<form id="bulkForm" method="POST">
    <input type="hidden" name="bulk_delete_subscribers" value="1">
    
    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider w-10 text-center">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" class="rounded text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider">Email Address</th>
                        <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider">Subscribed Date</th>
                        <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    <?php if (mysqli_num_rows($result) > 0): while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr class="hover:bg-blue-50/30 transition">
                        <td class="px-4 py-3 text-center">
                            <input type="checkbox" name="selected_subs[]" value="<?php echo $row['id']; ?>" class="sub-check rounded text-blue-600 focus:ring-blue-500" onchange="updateBulkBar()">
                        </td>
                        <td class="px-4 py-3 font-semibold text-gray-900">
                            <div class="flex items-center gap-2">
                                <i class="fa-regular fa-envelope text-gray-400 text-xs"></i>
                                <span class="select-all"><?php echo htmlspecialchars($row['email']); ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            <?php echo htmlspecialchars($row['name'] ?: '—'); ?>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider <?php echo $row['status'] === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-600'; ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?php echo $row['status'] === 'active' ? 'bg-emerald-500' : 'bg-gray-400'; ?>"></span>
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">
                            <?php echo date('M d, Y • g:i A', strtotime($row['created_at'])); ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="?delete=<?php echo $row['id']; ?>&p=<?php echo $page; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" onclick="return confirm('Delete subscriber <?php echo addslashes($row['email']); ?>?')" class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50 transition" title="Delete Subscriber">
                                <i class="fa fa-trash-can"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                            <i class="fa-regular fa-envelope-open text-4xl text-gray-300 mb-2 block"></i>
                            <p class="font-semibold text-gray-600">No subscribers found</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Subscribers will appear here when visitors sign up via the footer newsletter form.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div class="flex justify-center items-center mt-6 gap-1.5 flex-wrap">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a href="?p=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold transition <?php echo $i == $page ? 'bg-blue-600 text-white shadow-xs' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50'; ?>">
        <?php echo $i; ?>
    </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<!-- Send Newsletter Modal -->
<div id="newsletterModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST" id="newsletterForm">
            <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50/50">
                <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa fa-envelope text-blue-600"></i> Send Newsletter
                </h3>
                <button type="button" onclick="document.getElementById('newsletterModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fa fa-xmark text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Subject</label>
                    <input type="text" name="subject" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="Newsletter Subject...">
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Message</label>
                    <textarea name="message" rows="7" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="Write your email content here..."></textarea>
                    <p class="text-[11px] text-gray-400 mt-1">If subscribers are checked in the table, the email will be sent only to selected ones. Otherwise, it sends to all active subscribers.</p>
                </div>
                <div id="selectedSubsContainer"></div>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="document.getElementById('newsletterModal').classList.add('hidden')" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg font-semibold hover:bg-gray-300 transition text-xs">Cancel</button>
                <button type="submit" name="send_newsletter" id="sendNewsletterBtn" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold transition text-xs flex items-center gap-1.5 shadow-xs">
                    <i class="fa fa-paper-plane"></i> Send Newsletter
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSelectAll(master) {
    document.querySelectorAll('.sub-check').forEach(function(cb) {
        cb.checked = master.checked;
    });
    updateBulkBar();
}

function updateBulkBar() {
    var checked = document.querySelectorAll('.sub-check:checked');
    var bar = document.getElementById('bulkActionBar');
    var badge = document.getElementById('selectedCountBadge');
    if (checked.length > 0) {
        bar.classList.remove('hidden');
        badge.innerText = checked.length + ' selected';
    } else {
        bar.classList.add('hidden');
    }
}

function submitBulkDelete() {
    var checked = document.querySelectorAll('.sub-check:checked');
    if (checked.length === 0) {
        alert('Please select at least one subscriber.');
        return;
    }
    if (confirm('Are you sure you want to delete ' + checked.length + ' selected subscriber(s)?')) {
        document.getElementById('bulkForm').submit();
    }
}

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
