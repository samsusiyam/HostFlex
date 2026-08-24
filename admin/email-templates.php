<?php
$page_title = 'Email Templates';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/mail.php';
checkAdminRole(['admin']);

$msg = '';
$error = '';

// Ensure table exists and seed defaults
$check = mysqli_query($conn, "SHOW TABLES LIKE 'email_templates'");
if (mysqli_num_rows($check) == 0) {
    mysqli_query($conn, "CREATE TABLE email_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        variables VARCHAR(255) NOT NULL DEFAULT '',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $seeds = [
        [
            'Contact Auto-Reply',
            'Thank you for contacting {site_name}',
            "<p>Dear {name},</p>\n<p>Thank you for reaching out to us. We have received your message and will get back to you shortly.</p>\n<p><strong>Your message:</strong></p>\n<p>{message}</p>\n<p>Best regards,<br>{site_name} Team</p>",
            'name,email,message,site_name,site_url'
        ],
        [
            'Password Reset Notification',
            'Password Reset Request - {site_name}',
            "<p>Dear {name},</p>\n<p>We received a request to reset your password. Click the link below to set a new password:</p>\n<p><a href='{reset_link}' style='padding: 10px 20px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px;'>Reset My Password</a></p>\n<p>If you did not request this, please ignore this email.</p>",
            'name,email,reset_link,site_name,site_url'
        ]
    ];

    $stmt = mysqli_prepare($conn, "INSERT INTO email_templates (name, subject, body, variables) VALUES (?, ?, ?, ?)");
    foreach ($seeds as $s) {
        mysqli_stmt_bind_param($stmt, 'ssss', $s[0], $s[1], $s[2], $s[3]);
        mysqli_stmt_execute($stmt);
    }
    mysqli_stmt_close($stmt);
}

// Handle Send Test Email via AJAX
if (isset($_POST['ajax_send_test_email'])) {
    header('Content-Type: application/json');
    $tpl_id = (int)($_POST['template_id'] ?? 0);
    $target_email = sanitize(trim($_POST['target_email'] ?? ''));
    if (!$target_email || !filter_var($target_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid destination email address.']);
        exit;
    }
    $tpl = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM email_templates WHERE id = $tpl_id"));
    if (!$tpl) {
        echo json_encode(['success' => false, 'message' => 'Template not found.']);
        exit;
    }
    $site_name = getSetting('site_name') ?: 'Host Nibo';
    $site_url = getSiteUrl();

    // Replace test variables
    $body = str_replace(
        ['{name}', '{email}', '{site_name}', '{site_url}', '{message}', '{reset_link}'],
        ['John Doe', $target_email, $site_name, $site_url, 'This is a sample inquiry message preview.', $site_url . 'admin/login.php'],
        $tpl['body']
    );
    $subject = str_replace(['{site_name}', '{name}'], [$site_name, 'John Doe'], $tpl['subject']);

    if (sendMail($target_email, '[TEST PREVIEW] ' . $subject, $body)) {
        echo json_encode(['success' => true, 'message' => "Test email successfully dispatched to $target_email!"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Mail sending failed. Please check SMTP settings.']);
    }
    exit;
}

// Handle Add / Edit via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template'])) {
    $name = sanitize($_POST['name'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $body = mysqli_real_escape_string($conn, $_POST['body'] ?? '');
    $variables = sanitize($_POST['variables'] ?? '');

    if (!$name || !$subject || !$body) {
        $error = 'Template name, subject, and HTML body are required!';
    } else {
        if (isset($_POST['template_id']) && !empty($_POST['template_id'])) {
            $id = (int)$_POST['template_id'];
            if (mysqli_query($conn, "UPDATE email_templates SET name='$name', subject='$subject', body='$body', variables='$variables' WHERE id=$id")) {
                logActivity('Updated Email Template', "$name (ID: $id)");
                $msg = 'Email template "' . htmlspecialchars($name) . '" updated successfully!';
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        } else {
            if (mysqli_query($conn, "INSERT INTO email_templates (name, subject, body, variables) VALUES ('$name', '$subject', '$body', '$variables')")) {
                logActivity('Created Email Template', $name);
                $msg = 'New email template "' . htmlspecialchars($name) . '" created successfully!';
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        }
    }
}

// Handle Delete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_template_id'])) {
    $id = (int)$_POST['delete_template_id'];
    $del = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM email_templates WHERE id = $id"));
    if (mysqli_query($conn, "DELETE FROM email_templates WHERE id = $id")) {
        logActivity('Deleted Email Template', ($del['name'] ?? 'Unknown') . " (ID: $id)");
        $msg = 'Email template deleted successfully.';
    } else {
        $error = 'Database error: ' . mysqli_error($conn);
    }
}

$templates = mysqli_query($conn, "SELECT * FROM email_templates ORDER BY name ASC");
$total_templates = mysqli_num_rows($templates);
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-envelope-open-text"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Email Templates</h1>
            </div>
            <p class="text-xs text-gray-500">Design and customize transactional email notifications and auto-responder templates.</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="openAddTemplateModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs cursor-pointer">
                <i class="fa-solid fa-plus"></i> Add New Template
            </button>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($msg): ?>
    <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between bg-emerald-50 text-emerald-800 border border-emerald-200">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span><?php echo htmlspecialchars($msg); ?></span>
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

    <!-- Templates Grid Table -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <div class="text-xs text-gray-500 font-semibold">
                Total Templates: <strong class="text-gray-900"><?php echo $total_templates; ?></strong>
            </div>
            <a href="settings-smtp.php" class="text-xs text-blue-600 hover:underline font-semibold flex items-center gap-1">
                <i class="fa-solid fa-server text-[11px]"></i> SMTP Settings
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/70 border-b border-gray-200 text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5">Template Name</th>
                        <th class="px-4 py-3.5">Email Subject</th>
                        <th class="px-4 py-3.5">Dynamic Variables</th>
                        <th class="px-4 py-3.5">Last Updated</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    <?php if ($total_templates > 0): while ($t = mysqli_fetch_assoc($templates)): 
                        $t_json = htmlspecialchars(json_encode($t), ENT_QUOTES, 'UTF-8');
                        $vars = array_filter(explode(',', $t['variables'] ?: ''));
                    ?>
                    <tr class="hover:bg-blue-50/20 transition">
                        <td class="px-4 py-3.5 font-bold text-gray-900">
                            <div class="flex items-center gap-2">
                                <span class="p-1.5 bg-blue-50 text-blue-600 rounded-lg text-xs"><i class="fa-solid fa-file-code"></i></span>
                                <span><?php echo htmlspecialchars($t['name']); ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 text-gray-700 font-medium">
                            <?php echo htmlspecialchars($t['subject']); ?>
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="flex flex-wrap gap-1">
                                <?php if (!empty($vars)): foreach ($vars as $v): ?>
                                <span class="px-2 py-0.5 rounded-md bg-gray-100 text-gray-700 font-mono text-[10px] font-bold border border-gray-200">
                                    {<?php echo trim($v); ?>}
                                </span>
                                <?php endforeach; else: ?>
                                <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 text-gray-500 text-[11px] whitespace-nowrap">
                            <?php echo date('M d, Y • h:i A', strtotime($t['updated_at'])); ?>
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" onclick='openTestEmailModal(<?php echo $t['id']; ?>, "<?php echo addslashes($t['name']); ?>")' class="p-1.5 bg-gray-50 hover:bg-purple-50 text-purple-600 rounded-lg border border-gray-200 hover:border-purple-200 transition cursor-pointer" title="Send Test Email">
                                    <i class="fa-solid fa-paper-plane text-xs"></i>
                                </button>
                                <button type="button" onclick='openEditTemplateModal(<?php echo $t_json; ?>)' class="p-1.5 bg-gray-50 hover:bg-blue-50 text-blue-600 rounded-lg border border-gray-200 hover:border-blue-200 transition cursor-pointer" title="Edit Template">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </button>
                                <button type="button" onclick='openDeleteTemplateModal(<?php echo $t['id']; ?>, "<?php echo addslashes($t['name']); ?>")' class="p-1.5 bg-gray-50 hover:bg-red-50 text-red-600 rounded-lg border border-gray-200 hover:border-red-200 transition cursor-pointer" title="Delete Template">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="5" class="px-4 py-16 text-center text-gray-400">
                            <i class="fa-solid fa-envelope-circle-check text-4xl text-gray-300 mb-2 block"></i>
                            <p class="font-bold text-gray-700">No email templates created</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Click "Add New Template" to create automated system notifications.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ==========================================
     POPUP MODAL: ADD / EDIT TEMPLATE
=============================================== -->
<div id="templateModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3 sm:p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden border border-gray-100 my-auto sm:my-8 animate-in fade-in duration-200 flex flex-col max-h-[90vh]">
        
        <!-- Modal Header -->
        <div class="shrink-0 flex items-center justify-between px-4 sm:px-6 py-3.5 sm:py-4 border-b bg-gray-50/70">
            <div class="flex items-center gap-2">
                <span class="p-2 bg-blue-100 text-blue-700 rounded-lg text-xs" id="tplModalIcon"><i class="fa-solid fa-plus"></i></span>
                <h3 class="text-sm font-bold text-gray-900" id="tplModalTitle">Add Email Template</h3>
            </div>
            <button type="button" onclick="closeTemplateModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg transition cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Modal Form Body -->
        <form method="POST" id="tplModalForm" class="flex flex-col flex-1 overflow-hidden">
            <input type="hidden" name="save_template" value="1">
            <input type="hidden" name="template_id" id="tpl_id" value="">

            <div class="p-4 sm:p-6 space-y-4 text-xs flex-1 overflow-y-auto">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Template Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="tpl_name" required placeholder="e.g. Contact Auto-Reply" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Variables List (Comma-separated)</label>
                        <input type="text" name="variables" id="tpl_variables" placeholder="name,email,site_name,message" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none font-mono">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Email Subject Line <span class="text-red-500">*</span></label>
                    <input type="text" name="subject" id="tpl_subject" required placeholder="e.g. Thank you for reaching out to {site_name}" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <!-- 1-Click Variable Chips -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="font-bold text-gray-700">Insert Dynamic Tag to Cursor:</label>
                        <span class="text-[11px] text-gray-400">Click to append to template</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" onclick="insertVariableTag('{name}')" class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 font-mono text-[11px] font-bold border border-blue-200 transition cursor-pointer">+{name}</button>
                        <button type="button" onclick="insertVariableTag('{email}')" class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 font-mono text-[11px] font-bold border border-blue-200 transition cursor-pointer">+{email}</button>
                        <button type="button" onclick="insertVariableTag('{site_name}')" class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 font-mono text-[11px] font-bold border border-blue-200 transition cursor-pointer">+{site_name}</button>
                        <button type="button" onclick="insertVariableTag('{site_url}')" class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 font-mono text-[11px] font-bold border border-blue-200 transition cursor-pointer">+{site_url}</button>
                        <button type="button" onclick="insertVariableTag('{message}')" class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 font-mono text-[11px] font-bold border border-blue-200 transition cursor-pointer">+{message}</button>
                        <button type="button" onclick="insertVariableTag('{reset_link}')" class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 font-mono text-[11px] font-bold border border-blue-200 transition cursor-pointer">+{reset_link}</button>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Template Body (HTML allowed) <span class="text-red-500">*</span></label>
                    <textarea name="body" id="tpl_body" rows="10" required class="w-full border border-gray-300 rounded-xl p-3 text-xs font-mono focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none leading-relaxed" placeholder="<p>Dear {name},</p>&#10;<p>Thank you for contacting {site_name}.</p>"></textarea>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="shrink-0 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2 px-4 sm:px-6 py-3.5 sm:py-4 border-t bg-gray-50">
                <button type="button" onclick="closeTemplateModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition text-xs flex items-center justify-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-floppy-disk"></i> Save Template
                </button>
            </div>
        </form>

    </div>
</div>

<!-- ==========================================
     POPUP MODAL: SEND TEST PREVIEW EMAIL
=============================================== -->
<div id="testEmailModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50/70">
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <i class="fa-solid fa-paper-plane text-purple-600"></i> Send Test Preview Email
            </h3>
            <button type="button" onclick="closeTestEmailModal()" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-base"></i></button>
        </div>
        <div class="p-6 space-y-4 text-xs">
            <p class="text-gray-500 leading-relaxed">
                Dispatch a live preview of <strong class="text-gray-900" id="testEmailTplName">Template Name</strong> with sample variable data to test formatting and delivery.
            </p>
            <input type="hidden" id="test_tpl_id" value="">
            <div>
                <label class="block font-bold text-gray-700 mb-1">Destination Email Address <span class="text-red-500">*</span></label>
                <input type="email" id="test_target_email" required value="<?php echo htmlspecialchars(getSetting('admin_email') ?: 'admin@hostnibo.com'); ?>" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-purple-500 focus:border-purple-500 outline-none">
            </div>
            <div id="testEmailResult" class="hidden p-3 rounded-xl text-xs font-semibold"></div>
        </div>
        <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
            <button type="button" onclick="closeTestEmailModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
            <button type="button" id="testSendSubmitBtn" onclick="submitTestEmail()" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                <i class="fa-solid fa-paper-plane"></i> Send Test
            </button>
        </div>
    </div>
</div>

<!-- ==========================================
     POPUP MODAL: DELETE CONFIRMATION
=============================================== -->
<div id="deleteTemplateModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST">
            <input type="hidden" name="delete_template_id" id="delete_tpl_id_input" value="">
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Delete Email Template?</h3>
                <p class="text-xs text-gray-500 mb-3">Are you sure you want to delete this transactional template?</p>
                <div class="bg-gray-50 p-2.5 rounded-xl border border-gray-200 font-bold text-xs text-gray-800" id="deleteTplName">
                    Template Name
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="closeDeleteTemplateModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-trash-can"></i> Delete Template
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddTemplateModal() {
    document.getElementById('tplModalTitle').innerText = 'Add Email Template';
    document.getElementById('tplModalIcon').innerHTML = '<i class="fa-solid fa-plus"></i>';
    document.getElementById('tpl_id').value = '';
    document.getElementById('tpl_name').value = '';
    document.getElementById('tpl_subject').value = '';
    document.getElementById('tpl_variables').value = 'name,email,site_name,message';
    document.getElementById('tpl_body').value = '';
    document.getElementById('templateModal').classList.remove('hidden');
}

function openEditTemplateModal(tpl) {
    document.getElementById('tplModalTitle').innerText = 'Edit Template: ' + tpl.name;
    document.getElementById('tplModalIcon').innerHTML = '<i class="fa-solid fa-pen"></i>';
    document.getElementById('tpl_id').value = tpl.id;
    document.getElementById('tpl_name').value = tpl.name;
    document.getElementById('tpl_subject').value = tpl.subject;
    document.getElementById('tpl_variables').value = tpl.variables || '';
    document.getElementById('tpl_body').value = tpl.body;
    document.getElementById('templateModal').classList.remove('hidden');
}

function closeTemplateModal() {
    document.getElementById('templateModal').classList.add('hidden');
}

function insertVariableTag(tag) {
    var ta = document.getElementById('tpl_body');
    var start = ta.selectionStart;
    var end = ta.selectionEnd;
    var text = ta.value;
    ta.value = text.substring(0, start) + tag + text.substring(end);
    ta.selectionStart = ta.selectionEnd = start + tag.length;
    ta.focus();
}

function openTestEmailModal(id, name) {
    document.getElementById('test_tpl_id').value = id;
    document.getElementById('testEmailTplName').innerText = name;
    document.getElementById('testEmailResult').className = 'hidden';
    document.getElementById('testEmailModal').classList.remove('hidden');
}

function closeTestEmailModal() {
    document.getElementById('testEmailModal').classList.add('hidden');
}

function submitTestEmail() {
    var id = document.getElementById('test_tpl_id').value;
    var email = document.getElementById('test_target_email').value.trim();
    var btn = document.getElementById('testSendSubmitBtn');
    var resBox = document.getElementById('testEmailResult');

    if (!email) {
        alert('Please enter a destination email address.');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Sending...';

    var fd = new FormData();
    fd.append('ajax_send_test_email', '1');
    fd.append('template_id', id);
    fd.append('target_email', email);

    fetch('email-templates.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Test';
        resBox.classList.remove('hidden');
        if (data.success) {
            resBox.className = 'p-3 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200';
            resBox.innerText = data.message;
        } else {
            resBox.className = 'p-3 rounded-xl text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200';
            resBox.innerText = data.message;
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Test';
        resBox.className = 'p-3 rounded-xl text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200';
        resBox.innerText = 'Network error occurred.';
    });
}

function openDeleteTemplateModal(id, name) {
    document.getElementById('delete_tpl_id_input').value = id;
    document.getElementById('deleteTplName').innerText = name;
    document.getElementById('deleteTemplateModal').classList.remove('hidden');
}

function closeDeleteTemplateModal() {
    document.getElementById('deleteTemplateModal').classList.add('hidden');
}

// Keyboard ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTemplateModal();
        closeTestEmailModal();
        closeDeleteTemplateModal();
    }
});
</script>

<?php include 'footer.php'; ?>
