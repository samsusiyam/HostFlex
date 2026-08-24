<?php
$page_title = 'Newsletter Subscribers';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/mail.php';
checkAdminLogin();

$msg = '';
$msg_type = 'success';

// Handle AJAX Quick Toggle Status
if (isset($_POST['ajax_toggle_status'])) {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $curr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status, email FROM subscribers WHERE id = $id"));
        if ($curr) {
            $new_val = $curr['status'] === 'active' ? 'unsubscribed' : 'active';
            mysqli_query($conn, "UPDATE subscribers SET status = '$new_val' WHERE id = $id");
            logActivity('Toggled Subscriber Status', ($curr['email'] ?? 'Subscriber') . " -> $new_val (ID: $id)");
            echo json_encode(['success' => true, 'new_val' => $new_val]);
            exit;
        }
    }
    echo json_encode(['success' => false]);
    exit;
}

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

// Handle Add / Edit Subscriber via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_subscriber'])) {
    $email = sanitize(trim($_POST['email'] ?? ''));
    $name = sanitize(trim($_POST['name'] ?? ''));
    $status = sanitize(trim($_POST['status'] ?? 'active'));
    $sub_id = (int)($_POST['subscriber_id'] ?? 0);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Please enter a valid email address.';
        $msg_type = 'danger';
    } else {
        if ($sub_id > 0) {
            $check = mysqli_query($conn, "SELECT id FROM subscribers WHERE email = '$email' AND id != $sub_id");
            if (mysqli_num_rows($check) > 0) {
                $msg = 'Subscriber with this email already exists.';
                $msg_type = 'danger';
            } else {
                mysqli_query($conn, "UPDATE subscribers SET email = '$email', name = '$name', status = '$status' WHERE id = $sub_id");
                logActivity('Updated Subscriber', "$email (ID: $sub_id)");
                $msg = "Subscriber $email updated successfully.";
            }
        } else {
            $check = mysqli_query($conn, "SELECT id FROM subscribers WHERE email = '$email'");
            if (mysqli_num_rows($check) > 0) {
                $msg = 'Subscriber with this email already exists.';
                $msg_type = 'danger';
            } else {
                mysqli_query($conn, "INSERT INTO subscribers (email, name, status) VALUES ('$email', '$name', '$status')");
                logActivity('Added Subscriber', $email);
                $msg = "New subscriber $email added successfully.";
            }
        }
    }
}

// Handle Delete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_single_id'])) {
    $id = (int)$_POST['delete_single_id'];
    $sub = mysqli_fetch_assoc(mysqli_query($conn, "SELECT email FROM subscribers WHERE id = $id"));
    if (mysqli_query($conn, "DELETE FROM subscribers WHERE id = $id")) {
        logActivity('Deleted Subscriber', ($sub['email'] ?? 'Unknown') . " (ID: $id)");
        $msg = 'Subscriber deleted successfully.';
    } else {
        $msg = 'Database error: ' . mysqli_error($conn);
        $msg_type = 'danger';
    }
}

// Handle Delete All
if (isset($_POST['delete_all_subscribers'])) {
    if (mysqli_query($conn, "DELETE FROM subscribers")) {
        logActivity('Deleted All Subscribers', 'All records cleared');
        $msg = 'All subscribers have been cleared successfully.';
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
        $msg_type = 'danger';
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

<div class="space-y-6">
    
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-users"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Newsletter Subscribers</h1>
            </div>
            <p class="text-xs text-gray-500">Manage newsletter email lists, export CSV data, and broadcast announcements.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" onclick="openAddSubscriberModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                <i class="fa-solid fa-user-plus"></i> Add Subscriber
            </button>
            <button type="button" onclick="openNewsletterModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                <i class="fa-solid fa-paper-plane"></i> Broadcast Email
            </button>
            <a href="?export=csv" class="bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200 px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </a>
            <?php if ($total > 0): ?>
            <button type="button" onclick="openDeleteAllModal()" class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 px-3 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-trash-can"></i> Clear All
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alert Notification -->
    <?php if ($msg): ?>
    <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between <?php echo $msg_type === 'danger' ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200'; ?>">
        <div class="flex items-center gap-2">
            <i class="fa-solid <?php echo $msg_type === 'danger' ? 'fa-triangle-exclamation text-rose-600' : 'fa-circle-check text-emerald-600'; ?> text-sm"></i>
            <span><?php echo htmlspecialchars($msg); ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-600 cursor-pointer"><i class="fa-solid fa-xmark text-sm"></i></button>
    </div>
    <?php endif; ?>

    <!-- Search & Bulk Selection Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-gray-200/80 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        
        <div class="text-xs text-gray-500 font-semibold">
            Total Active Subscribers: <strong class="text-gray-900"><?php echo number_format($total); ?></strong>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
            <!-- Search Bar -->
            <form method="GET" class="relative flex-1 md:w-64">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search email or name..." class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-8 pr-3 py-1.5 text-xs text-gray-800 focus:bg-white focus:outline-none focus:border-blue-600 transition">
            </form>

            <!-- Bulk Action Trigger -->
            <div id="bulkActionBar" class="hidden flex items-center gap-2">
                <span id="selectedCountBadge" class="text-xs text-gray-500 font-bold">0 selected</span>
                <button type="button" onclick="openBulkDeleteModal()" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-3 py-1.5 rounded-xl text-xs transition flex items-center gap-1 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-trash-can"></i> Delete Selected
                </button>
            </div>
        </div>

    </div>

    <!-- Subscribers Table -->
    <form id="bulkForm" method="POST">
        <input type="hidden" name="bulk_delete_subscribers" value="1">
        
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50/70 border-b border-gray-200 text-xs font-bold text-gray-700 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3.5 w-10 text-center">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" class="rounded text-blue-600 focus:ring-blue-500 cursor-pointer">
                            </th>
                            <th class="px-4 py-3.5">Subscriber Email</th>
                            <th class="px-4 py-3.5">Name</th>
                            <th class="px-4 py-3.5">Status</th>
                            <th class="px-4 py-3.5">Subscribed Date</th>
                            <th class="px-4 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-xs">
                        <?php if (mysqli_num_rows($result) > 0): while ($row = mysqli_fetch_assoc($result)): 
                            $row_json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr class="hover:bg-blue-50/20 transition">
                            <td class="px-4 py-3.5 text-center">
                                <input type="checkbox" name="selected_subs[]" value="<?php echo $row['id']; ?>" class="sub-check rounded text-blue-600 focus:ring-blue-500 cursor-pointer" onchange="updateBulkBar()">
                            </td>
                            <td class="px-4 py-3.5 font-bold text-gray-900">
                                <div class="flex items-center gap-2">
                                    <i class="fa-regular fa-envelope text-gray-400"></i>
                                    <span class="select-all font-mono"><?php echo htmlspecialchars($row['email']); ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-gray-600 font-medium">
                                <?php echo htmlspecialchars($row['name'] ?: '—'); ?>
                            </td>
                            <td class="px-4 py-3.5">
                                <button type="button" onclick="toggleSubscriberStatus(<?php echo $row['id']; ?>, this)" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition <?php echo $row['status'] === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'; ?>" title="Click to toggle Active/Unsubscribed">
                                    <span class="w-1.5 h-1.5 rounded-full <?php echo $row['status'] === 'active' ? 'bg-emerald-500' : 'bg-rose-500'; ?>"></span>
                                    <span><?php echo ucfirst($row['status']); ?></span>
                                </button>
                            </td>
                            <td class="px-4 py-3.5 text-gray-500 text-[11px] whitespace-nowrap">
                                <?php echo date('M d, Y • h:i A', strtotime($row['created_at'])); ?>
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" onclick='openEditSubscriberModal(<?php echo $row_json; ?>)' class="p-1.5 bg-gray-50 hover:bg-blue-50 text-blue-600 rounded-lg border border-gray-200 hover:border-blue-200 transition cursor-pointer" title="Edit Subscriber">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </button>
                                    <button type="button" onclick="openDeleteSubscriberModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['email']); ?>')" class="p-1.5 bg-gray-50 hover:bg-red-50 text-red-600 rounded-lg border border-gray-200 hover:border-red-200 transition cursor-pointer" title="Delete Subscriber">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center text-gray-400">
                                <i class="fa-regular fa-envelope-open text-4xl text-gray-300 mb-2 block"></i>
                                <p class="font-bold text-gray-700">No subscribers found</p>
                                <p class="text-[11px] text-gray-400 mt-0.5">Subscribers will appear here when visitors join your newsletter on the website.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($pages > 1): ?>
            <div class="p-4 border-t border-gray-200 flex items-center justify-between bg-gray-50/50">
                <span class="text-xs text-gray-500 font-semibold">
                    Page <?php echo $page; ?> of <?php echo $pages; ?>
                </span>
                <div class="flex items-center gap-1">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <a href="?p=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?php echo $i == $page ? 'bg-blue-600 text-white shadow-xs' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-100'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </form>

</div>

<!-- ==========================================
     POPUP MODAL: ADD / EDIT SUBSCRIBER
=============================================== -->
<div id="subscriberModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST">
            <input type="hidden" name="save_subscriber" value="1">
            <input type="hidden" name="subscriber_id" id="sub_id" value="">

            <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50/70">
                <h3 class="text-sm font-bold text-gray-900" id="subModalTitle">Add New Subscriber</h3>
                <button type="button" onclick="closeSubscriberModal()" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-base"></i></button>
            </div>

            <div class="p-6 space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="sub_email" required placeholder="user@example.com" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Subscriber Name</label>
                    <input type="text" name="name" id="sub_name" placeholder="Optional full name" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Status</label>
                    <select name="status" id="sub_status" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                        <option value="active">Active</option>
                        <option value="unsubscribed">Unsubscribed</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="closeSubscriberModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-floppy-disk"></i> Save Subscriber
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     POPUP MODAL: BROADCAST NEWSLETTER
=============================================== -->
<div id="newsletterModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST" id="newsletterForm">
            <input type="hidden" name="send_newsletter" value="1">
            <div id="selectedSubsContainer"></div>

            <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50/70">
                <div class="flex items-center gap-2">
                    <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg text-xs"><i class="fa-solid fa-paper-plane"></i></span>
                    <h3 class="text-sm font-bold text-gray-900">Broadcast Newsletter Email</h3>
                </div>
                <button type="button" onclick="closeNewsletterModal()" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-base"></i></button>
            </div>

            <div class="p-6 space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Email Subject <span class="text-red-500">*</span></label>
                    <input type="text" name="subject" required class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none" placeholder="e.g. Special Weekend Discount from Host Nibo">
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Message Content <span class="text-red-500">*</span></label>
                    <textarea name="message" rows="6" required class="w-full border border-gray-300 rounded-xl p-3 text-xs focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none" placeholder="Write your announcement or newsletter content here..."></textarea>
                    <p class="text-[11px] text-gray-400 mt-1">If subscribers are checked in the table, the email will be sent only to those selected. Otherwise, it broadcasts to all active subscribers.</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="closeNewsletterModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" id="sendMailBtn" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-paper-plane"></i> Send Broadcast
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     POPUP MODAL: DELETE SINGLE SUBSCRIBER
=============================================== -->
<div id="deleteSingleModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST">
            <input type="hidden" name="delete_single_id" id="delete_single_id_input" value="">
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Delete Subscriber?</h3>
                <p class="text-xs text-gray-500 mb-3">Are you sure you want to delete this subscriber?</p>
                <div class="bg-gray-50 p-2.5 rounded-xl border border-gray-200 font-mono text-xs font-bold text-gray-800 truncate" id="deleteSingleEmail">
                    email@example.com
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="closeDeleteSubscriberModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-trash-can"></i> Delete
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     POPUP MODAL: DELETE ALL SUBSCRIBERS
=============================================== -->
<div id="deleteAllModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST">
            <input type="hidden" name="delete_all_subscribers" value="1">
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Delete ALL Subscribers?</h3>
                <p class="text-xs text-rose-600 font-semibold mb-3">⚠️ Warning: This will permanently wipe all subscriber records. This cannot be undone!</p>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="closeDeleteAllModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-trash-can"></i> Wipe All Data
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

function toggleSubscriberStatus(id, btn) {
    var fd = new FormData();
    fd.append('ajax_toggle_status', '1');
    fd.append('id', id);

    fetch('subscribers.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (data.new_val === 'active') {
                btn.className = 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition bg-emerald-50 text-emerald-700 border border-emerald-200';
                btn.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span>Active</span>';
            } else {
                btn.className = 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition bg-rose-50 text-rose-700 border border-rose-200';
                btn.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span><span>Unsubscribed</span>';
            }
        }
    });
}

function openAddSubscriberModal() {
    document.getElementById('subModalTitle').innerText = 'Add New Subscriber';
    document.getElementById('sub_id').value = '';
    document.getElementById('sub_email').value = '';
    document.getElementById('sub_name').value = '';
    document.getElementById('sub_status').value = 'active';
    document.getElementById('subscriberModal').classList.remove('hidden');
}

function openEditSubscriberModal(sub) {
    document.getElementById('subModalTitle').innerText = 'Edit Subscriber: ' + sub.email;
    document.getElementById('sub_id').value = sub.id;
    document.getElementById('sub_email').value = sub.email;
    document.getElementById('sub_name').value = sub.name || '';
    document.getElementById('sub_status').value = sub.status || 'active';
    document.getElementById('subscriberModal').classList.remove('hidden');
}

function closeSubscriberModal() {
    document.getElementById('subscriberModal').classList.add('hidden');
}

function openNewsletterModal() {
    document.getElementById('newsletterModal').classList.remove('hidden');
}

function closeNewsletterModal() {
    document.getElementById('newsletterModal').classList.add('hidden');
}

function openDeleteSubscriberModal(id, email) {
    document.getElementById('delete_single_id_input').value = id;
    document.getElementById('deleteSingleEmail').innerText = email;
    document.getElementById('deleteSingleModal').classList.remove('hidden');
}

function closeDeleteSubscriberModal() {
    document.getElementById('deleteSingleModal').classList.add('hidden');
}

function openDeleteAllModal() {
    document.getElementById('deleteAllModal').classList.remove('hidden');
}

function closeDeleteAllModal() {
    document.getElementById('deleteAllModal').classList.add('hidden');
}

function openBulkDeleteModal() {
    if (confirm('Delete all selected subscribers?')) {
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
    document.getElementById('sendMailBtn').disabled = true;
    document.getElementById('sendMailBtn').innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Sending...';
});

// Keyboard ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSubscriberModal();
        closeNewsletterModal();
        closeDeleteSubscriberModal();
        closeDeleteAllModal();
    }
});
</script>

<?php include 'footer.php'; ?>
