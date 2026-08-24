<?php
$page_title = 'Contact Inquiries';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

$success = '';
$error = '';

// Handle Mark Read via POST / AJAX
if (isset($_POST['mark_read_id'])) {
    $id = (int)$_POST['mark_read_id'];
    mysqli_query($conn, "UPDATE contacts SET is_read = 1 WHERE id = $id");
    if (isset($_POST['ajax'])) {
        echo json_encode(['status' => 'success']);
        exit;
    }
}

// Handle Delete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_contact_id'])) {
    $id = (int)$_POST['delete_contact_id'];
    $del = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name, email, file FROM contacts WHERE id = $id"));
    if ($del && $del['file'] && file_exists('../' . $del['file'])) {
        unlink('../' . $del['file']);
    }
    if (mysqli_query($conn, "DELETE FROM contacts WHERE id = $id")) {
        logActivity('Deleted Contact Inquiry', ($del['name'] ?? 'Unknown') . ' <' . ($del['email'] ?? '') . '> (ID: ' . $id . ')');
        $success = 'Message inquiry deleted successfully.';
    } else {
        $error = 'Database error: ' . mysqli_error($conn);
    }
}

// Handle Reply via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $to_email = sanitize($_POST['reply_to'] ?? '');
    $to_name = sanitize($_POST['reply_name'] ?? '');
    $subject = sanitize($_POST['reply_subject'] ?? '');
    $message = $_POST['reply_message'] ?? '';

    if ($to_email && $subject && $message) {
        $res = sendMail($to_email, $subject, nl2br(htmlspecialchars($message)), $to_name);
        if ($res === true) {
            logActivity('Sent Contact Reply', "To: $to_name <$to_email> Subject: $subject");
            $success = "Reply email sent successfully to $to_name ($to_email)!";
        } else {
            $error = "Failed to send email: " . (is_string($res) ? $res : 'SMTP Error');
        }
    } else {
        $error = "All reply fields are required.";
    }
}

$contacts = mysqli_query($conn, "SELECT * FROM contacts ORDER BY is_read ASC, created_at DESC");
$total_messages = mysqli_num_rows($contacts);
$unread_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM contacts WHERE is_read = 0"))['c'] ?? 0;
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-envelope"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Contact Messages</h1>
            </div>
            <p class="text-xs text-gray-500">Inquiries and messages submitted through your website's contact forms.</p>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($unread_count > 0): ?>
            <span class="bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1.5 rounded-xl border border-blue-200 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                <?php echo $unread_count; ?> Unread Message<?php echo $unread_count > 1 ? 's' : ''; ?>
            </span>
            <?php endif; ?>
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

    <!-- Contacts Table -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <div class="text-xs text-gray-500 font-semibold">
                Total Inquiries: <strong class="text-gray-900"><?php echo $total_messages; ?></strong>
            </div>
            <div class="relative w-64">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="contactSearchInput" onkeyup="filterContactRows(this.value)" placeholder="Search inquiries..." class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-8 pr-3 py-1.5 text-xs text-gray-800 focus:bg-white focus:outline-none focus:border-blue-600 transition">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/70 border-b border-gray-200 text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5 w-10 text-center">Status</th>
                        <th class="px-4 py-3.5">Sender</th>
                        <th class="px-4 py-3.5">Subject</th>
                        <th class="px-4 py-3.5">Received Date</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    <?php if ($total_messages > 0): while ($msg = mysqli_fetch_assoc($contacts)): 
                        $msg_json = htmlspecialchars(json_encode($msg), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr class="contact-row hover:bg-blue-50/20 transition <?php echo !$msg['is_read'] ? 'bg-blue-50/30 font-semibold' : ''; ?>" data-name="<?php echo strtolower($msg['name'] . ' ' . $msg['email'] . ' ' . $msg['subject'] . ' ' . $msg['message']); ?>" id="contactRow_<?php echo $msg['id']; ?>">
                        <td class="px-4 py-3.5 text-center">
                            <?php if (!$msg['is_read']): ?>
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-600 text-[11px]" title="Unread Message">
                                <i class="fa-solid fa-envelope"></i>
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 text-gray-400 text-[11px]" title="Read">
                                <i class="fa-solid fa-envelope-open"></i>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="text-gray-900 font-bold"><?php echo htmlspecialchars($msg['name']); ?></div>
                            <div class="text-[11px] text-gray-500 font-normal"><?php echo htmlspecialchars($msg['email']); ?></div>
                        </td>
                        <td class="px-4 py-3.5 text-gray-800 max-w-sm truncate">
                            <span class="font-medium"><?php echo htmlspecialchars($msg['subject']); ?></span>
                            <span class="text-[11px] text-gray-400 block truncate font-normal"><?php echo htmlspecialchars(substr($msg['message'], 0, 80)); ?>...</span>
                        </td>
                        <td class="px-4 py-3.5 text-gray-500 whitespace-nowrap">
                            <div><?php echo date('d M Y', strtotime($msg['created_at'])); ?></div>
                            <div class="text-[10px] text-gray-400"><?php echo date('h:i A', strtotime($msg['created_at'])); ?></div>
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" onclick='openViewContactModal(<?php echo $msg_json; ?>)' class="bg-blue-50 hover:bg-blue-100 text-blue-700 px-3 py-1.5 rounded-lg border border-blue-200 transition text-xs font-bold flex items-center gap-1 cursor-pointer">
                                    <i class="fa-solid fa-eye"></i> View & Reply
                                </button>
                                <button type="button" onclick="openDeleteContactModal(<?php echo $msg['id']; ?>, '<?php echo addslashes($msg['name']); ?>', '<?php echo addslashes($msg['subject']); ?>')" class="p-1.5 bg-gray-50 hover:bg-red-50 text-red-600 rounded-lg border border-gray-200 hover:border-red-200 transition cursor-pointer" title="Delete Message">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="5" class="px-4 py-16 text-center text-gray-400">
                            <i class="fa-solid fa-inbox text-4xl text-gray-300 mb-2 block"></i>
                            <p class="font-bold text-gray-700">No contact messages received yet</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">When visitors submit messages via the contact form, they will appear here.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ==========================================
     POPUP MODAL: VIEW MESSAGE & QUICK REPLY
=============================================== -->
<div id="contactModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden border border-gray-100 my-8 animate-in fade-in duration-200">
        
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50/70">
            <div class="flex items-center gap-2">
                <span class="p-2 bg-blue-100 text-blue-700 rounded-lg text-xs"><i class="fa-solid fa-envelope-open-text"></i></span>
                <h3 class="text-sm font-bold text-gray-900">Message Details & Quick Reply</h3>
            </div>
            <button type="button" onclick="closeContactModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg transition cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <div class="p-6 space-y-5 text-xs max-h-[78vh] overflow-y-auto">
            
            <!-- Sender Card -->
            <div class="bg-blue-50/40 p-4 rounded-xl border border-blue-100 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="font-bold text-sm text-gray-900" id="msgModalSenderName">Sender Name</div>
                    <div class="text-blue-600 text-xs mt-0.5 flex items-center gap-1.5">
                        <i class="fa-solid fa-envelope text-[11px]"></i>
                        <a id="msgModalSenderEmailLink" href="#" class="hover:underline font-mono">sender@example.com</a>
                    </div>
                </div>
                <div class="text-right text-[11px] text-gray-500">
                    <span id="msgModalDate">Date</span>
                </div>
            </div>

            <!-- Subject & Content -->
            <div>
                <span class="text-[11px] text-gray-400 font-bold uppercase tracking-wider block mb-1">Subject</span>
                <h4 class="text-sm font-bold text-gray-900" id="msgModalSubject">Message Subject</h4>
            </div>

            <div>
                <span class="text-[11px] text-gray-400 font-bold uppercase tracking-wider block mb-1">Message Body</span>
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 text-gray-800 leading-relaxed whitespace-pre-wrap font-sans text-xs" id="msgModalBody">
                    Message content...
                </div>
            </div>

            <div id="msgModalAttachmentBox" class="hidden">
                <span class="text-[11px] text-gray-400 font-bold uppercase tracking-wider block mb-1">Attachment</span>
                <a id="msgModalAttachmentLink" href="#" target="_blank" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium transition">
                    <i class="fa-solid fa-paperclip text-blue-600"></i>
                    <span id="msgModalAttachmentName">file.pdf</span>
                </a>
            </div>

            <!-- Quick Reply Section -->
            <div class="pt-4 border-t border-gray-200">
                <div class="flex items-center gap-2 mb-3">
                    <span class="p-1.5 bg-emerald-50 text-emerald-700 rounded-md text-xs"><i class="fa-solid fa-reply"></i></span>
                    <h4 class="font-bold text-gray-900">Send Direct Email Reply</h4>
                </div>

                <form method="POST" id="replyForm" class="space-y-3">
                    <input type="hidden" name="send_reply" value="1">
                    <input type="hidden" name="reply_to" id="reply_to_email" value="">
                    <input type="hidden" name="reply_name" id="reply_to_name" value="">

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Reply Subject</label>
                        <input type="text" name="reply_subject" id="reply_subject" required class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Reply Message</label>
                        <textarea name="reply_message" id="reply_message" rows="4" required placeholder="Type your response to the customer..." class="w-full border border-gray-300 rounded-xl p-3 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-1">
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                            <i class="fa-solid fa-paper-plane"></i> Send Reply Email
                        </button>
                    </div>
                </form>
            </div>

        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-between px-6 py-3.5 border-t bg-gray-50 text-xs">
            <button type="button" onclick="closeContactModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition cursor-pointer">Close</button>
        </div>

    </div>
</div>

<!-- ==========================================
     POPUP MODAL: DELETE CONFIRMATION
=============================================== -->
<div id="deleteContactModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST">
            <input type="hidden" name="delete_contact_id" id="delete_contact_id_input" value="">
            
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Delete Message Inquiry?</h3>
                <p class="text-xs text-gray-500 mb-4">Are you sure you want to permanently delete this contact inquiry?</p>
                
                <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 text-xs text-left mb-2">
                    <div class="font-bold text-gray-900" id="deleteContactSender">Sender Name</div>
                    <div class="text-gray-500 mt-0.5 truncate" id="deleteContactSubject">Subject</div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="closeDeleteContactModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-trash-can"></i> Yes, Delete Message
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openViewContactModal(msg) {
    document.getElementById('msgModalSenderName').innerText = msg.name;
    document.getElementById('msgModalSenderEmailLink').innerText = msg.email;
    document.getElementById('msgModalSenderEmailLink').href = 'mailto:' + msg.email;
    document.getElementById('msgModalDate').innerText = msg.created_at;
    document.getElementById('msgModalSubject').innerText = msg.subject;
    document.getElementById('msgModalBody').innerText = msg.message;

    // Reply Form Fields
    document.getElementById('reply_to_email').value = msg.email;
    document.getElementById('reply_to_name').value = msg.name;
    document.getElementById('reply_subject').value = 'Re: ' + msg.subject;
    document.getElementById('reply_message').value = "Dear " + msg.name + ",\n\nThank you for reaching out to us.\n\n\n\nBest regards,\nSupport Team\n" + window.location.hostname;

    // Attachment
    var attachBox = document.getElementById('msgModalAttachmentBox');
    if (msg.file) {
        attachBox.classList.remove('hidden');
        document.getElementById('msgModalAttachmentLink').href = '/' + msg.file.replace(/^\/+/, '');
        document.getElementById('msgModalAttachmentName').innerText = msg.file.split('/').pop();
    } else {
        attachBox.classList.add('hidden');
    }

    // Mark Read via AJAX
    if (parseInt(msg.is_read) === 0) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'contacts.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('mark_read_id=' + msg.id + '&ajax=1');

        var row = document.getElementById('contactRow_' + msg.id);
        if (row) {
            row.classList.remove('bg-blue-50/30', 'font-semibold');
        }
    }

    document.getElementById('contactModal').classList.remove('hidden');
}

function closeContactModal() {
    document.getElementById('contactModal').classList.add('hidden');
}

function openDeleteContactModal(id, name, subject) {
    document.getElementById('delete_contact_id_input').value = id;
    document.getElementById('deleteContactSender').innerText = name;
    document.getElementById('deleteContactSubject').innerText = 'Subject: ' + subject;
    document.getElementById('deleteContactModal').classList.remove('hidden');
}

function closeDeleteContactModal() {
    document.getElementById('deleteContactModal').classList.add('hidden');
}

function filterContactRows(q) {
    q = q.trim().toLowerCase();
    document.querySelectorAll('.contact-row').forEach(row => {
        var text = row.dataset.name || '';
        if (!q || text.includes(q)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Keyboard ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeContactModal();
        closeDeleteContactModal();
    }
});
</script>

<?php include 'footer.php'; ?>
