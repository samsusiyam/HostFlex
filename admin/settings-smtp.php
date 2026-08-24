<?php
$page_title = 'SMTP Mail Server Settings';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminRole(['admin']);
require_once '../includes/mail.php';

$error = '';
$success = '';

// Handle AJAX Test Email
if (isset($_POST['ajax_send_test_email'])) {
    header('Content-Type: application/json');
    $to = sanitize(trim($_POST['target_email'] ?? ''));
    if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid recipient email.']);
        exit;
    }
    $site_name = getSetting('site_name') ?: 'Host Nibo';
    $body = "<h2>Host Nibo SMTP Test Verification</h2><p>Your SMTP mail configuration is operational and delivering messages correctly.</p><p>Server Time: " . date('Y-m-d H:i:s') . "</p>";
    if (sendMail($to, "[$site_name] Live SMTP Test Email", $body)) {
        echo json_encode(['success' => true, 'message' => "Test email successfully sent to $to!"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Mail delivery failed. Please verify host, port, credentials, or SSL/TLS settings.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_smtp'])) {
    $keys = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'smtp_from_email', 'smtp_from_name', 'smtp_reply_to'];
    foreach ($keys as $key) {
        if (!isset($_POST[$key])) continue;
        $s_key = mysqli_real_escape_string($conn, $key);
        $value = $key === 'smtp_password' && empty($_POST[$key]) ? null : $_POST[$key];
        if ($value === null) continue;
        $s_value = mysqli_real_escape_string($conn, $value);
        $check = mysqli_query($conn, "SELECT id FROM settings WHERE setting_key = '$s_key'");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "UPDATE settings SET setting_value = '$s_value' WHERE setting_key = '$s_key'");
        } else {
            mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('$s_key', '$s_value')");
        }
    }
    logActivity('Updated SMTP Settings', 'Email server config updated');
    header('Location: settings-smtp.php?s=1');
    exit;
}

if (isset($_GET['s'])) {
    $success = 'SMTP Settings updated successfully!';
}

$settings_result = mysqli_query($conn, "SELECT * FROM settings ORDER BY setting_key");
$s = []; 
while ($row = mysqli_fetch_assoc($settings_result)) { 
    $s[$row['setting_key']] = $row['setting_value']; 
}
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-amber-50 text-amber-600 rounded-lg text-sm"><i class="fa-solid fa-envelope-circle-check"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">SMTP Mail Server</h1>
            </div>
            <p class="text-xs text-gray-500">Configure outgoing email delivery servers, credentials, and test inbox delivery.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="openTestModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                <i class="fa-solid fa-paper-plane"></i> Send Test Email
            </button>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($success): ?>
    <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between bg-emerald-50 text-emerald-800 border border-emerald-200">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span><?php echo htmlspecialchars($success); ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 cursor-pointer"><i class="fa-solid fa-xmark text-sm"></i></button>
    </div>
    <?php endif; ?>

    <!-- Preset Helpers -->
    <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs">
        <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider mb-2 flex items-center gap-2">
            <i class="fa-solid fa-wand-magic-sparkles text-blue-600"></i> Quick Provider 1-Click Presets:
        </h3>
        <div class="flex flex-wrap gap-2 text-xs">
            <button type="button" onclick="applySmtpPreset('gmail')" class="px-3 py-1.5 rounded-xl bg-gray-50 hover:bg-blue-50 text-gray-700 hover:text-blue-700 border border-gray-200 font-semibold transition cursor-pointer">
                <i class="fa-brands fa-google mr-1 text-red-500"></i> Gmail / Google Workspace
            </button>
            <button type="button" onclick="applySmtpPreset('cpanel')" class="px-3 py-1.5 rounded-xl bg-gray-50 hover:bg-blue-50 text-gray-700 hover:text-blue-700 border border-gray-200 font-semibold transition cursor-pointer">
                <i class="fa-solid fa-server mr-1 text-orange-500"></i> cPanel Webmail / Host Nibo
            </button>
            <button type="button" onclick="applySmtpPreset('zoho')" class="px-3 py-1.5 rounded-xl bg-gray-50 hover:bg-blue-50 text-gray-700 hover:text-blue-700 border border-gray-200 font-semibold transition cursor-pointer">
                <i class="fa-solid fa-envelope mr-1 text-emerald-500"></i> Zoho Mail
            </button>
            <button type="button" onclick="applySmtpPreset('mailgun')" class="px-3 py-1.5 rounded-xl bg-gray-50 hover:bg-blue-50 text-gray-700 hover:text-blue-700 border border-gray-200 font-semibold transition cursor-pointer">
                <i class="fa-solid fa-paper-plane mr-1 text-rose-500"></i> Mailgun
            </button>
            <button type="button" onclick="applySmtpPreset('sendgrid')" class="px-3 py-1.5 rounded-xl bg-gray-50 hover:bg-blue-50 text-gray-700 hover:text-blue-700 border border-gray-200 font-semibold transition cursor-pointer">
                <i class="fa-solid fa-paperclip mr-1 text-blue-500"></i> SendGrid
            </button>
        </div>
    </div>

    <!-- SMTP Settings Form -->
    <form method="POST">
        <input type="hidden" name="save_smtp" value="1">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            
            <!-- 1. Server Configuration -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-4 text-xs">
                <div class="flex items-center gap-2 pb-3 border-b border-gray-100">
                    <span class="p-2 bg-purple-50 text-purple-600 rounded-lg text-xs"><i class="fa-solid fa-server"></i></span>
                    <h2 class="text-sm font-bold text-gray-900">Server & Credentials</h2>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">SMTP Host Server</label>
                    <input type="text" name="smtp_host" id="smtpHostInput" value="<?php echo htmlspecialchars($s['smtp_host'] ?? ''); ?>" placeholder="e.g. mail.hostnibo.com or smtp.gmail.com" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Port</label>
                        <input type="number" name="smtp_port" id="smtpPortInput" value="<?php echo htmlspecialchars($s['smtp_port'] ?? '587'); ?>" placeholder="587" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Encryption</label>
                        <select name="smtp_encryption" id="smtpEncSelect" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                            <option value="">None (Insecure)</option>
                            <option value="tls" <?php echo ($s['smtp_encryption'] ?? '') === 'tls' ? 'selected' : ''; ?>>TLS (Port 587)</option>
                            <option value="ssl" <?php echo ($s['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL (Port 465)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">SMTP Username</label>
                    <input type="text" name="smtp_username" value="<?php echo htmlspecialchars($s['smtp_username'] ?? ''); ?>" placeholder="e.g. support@hostnibo.com" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none" autocomplete="off">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">SMTP Password</label>
                    <div class="relative">
                        <input type="password" name="smtp_password" id="smtpPassInput" placeholder="Leave blank to preserve current" class="w-full border border-gray-300 rounded-xl px-3 pr-8 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none" autocomplete="new-password">
                        <button type="button" onclick="togglePassField('smtpPassInput', this)" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs cursor-pointer" tabindex="-1">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                    <span class="text-[11px] text-gray-400 mt-1 block">Leave empty to retain existing stored password</span>
                </div>
            </div>

            <!-- 2. Sender Identity -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-4 text-xs">
                <div class="flex items-center gap-2 pb-3 border-b border-gray-100">
                    <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-xs"><i class="fa-solid fa-address-card"></i></span>
                    <h2 class="text-sm font-bold text-gray-900">Sender Identity & Routing</h2>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">From Email Address</label>
                    <input type="email" name="smtp_from_email" value="<?php echo htmlspecialchars($s['smtp_from_email'] ?? ''); ?>" placeholder="noreply@hostnibo.com" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">From Sender Name</label>
                    <input type="text" name="smtp_from_name" value="<?php echo htmlspecialchars($s['smtp_from_name'] ?? ''); ?>" placeholder="Host Nibo Notifications" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Reply-To Email Address</label>
                    <input type="email" name="smtp_reply_to" value="<?php echo htmlspecialchars($s['smtp_reply_to'] ?? ''); ?>" placeholder="support@hostnibo.com" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>

        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-xs transition flex items-center gap-2 text-xs cursor-pointer">
                <i class="fa-solid fa-floppy-disk"></i> Save SMTP Settings
            </button>
        </div>
    </form>

</div>

<!-- ==========================================
     POPUP MODAL: LIVE SMTP TEST EMAIL
=============================================== -->
<div id="smtpTestModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50/70">
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <i class="fa-solid fa-paper-plane text-emerald-600"></i> Live SMTP Mail Test
            </h3>
            <button type="button" onclick="closeTestModal()" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-base"></i></button>
        </div>
        <div class="p-6 space-y-4 text-xs">
            <p class="text-gray-500 leading-relaxed">
                Send a real test email through your configured SMTP server to verify inbox delivery.
            </p>
            <div>
                <label class="block font-bold text-gray-700 mb-1">Recipient Email Address</label>
                <input type="email" id="testEmailTarget" required value="<?php echo htmlspecialchars(getSetting('admin_email') ?: 'admin@hostnibo.com'); ?>" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
            </div>
            <div id="testResultBox" class="hidden p-3 rounded-xl text-xs font-semibold"></div>
        </div>
        <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
            <button type="button" onclick="closeTestModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
            <button type="button" id="smtpTestSendBtn" onclick="runSmtpTest()" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                <i class="fa-solid fa-paper-plane"></i> Send Test Email
            </button>
        </div>
    </div>
</div>

<script>
function applySmtpPreset(type) {
    var host = document.getElementById('smtpHostInput');
    var port = document.getElementById('smtpPortInput');
    var enc = document.getElementById('smtpEncSelect');

    if (type === 'gmail') {
        host.value = 'smtp.gmail.com';
        port.value = '587';
        enc.value = 'tls';
    } else if (type === 'cpanel') {
        host.value = 'mail.' + window.location.hostname.replace('www.', '');
        port.value = '465';
        enc.value = 'ssl';
    } else if (type === 'zoho') {
        host.value = 'smtppro.zoho.com';
        port.value = '465';
        enc.value = 'ssl';
    } else if (type === 'mailgun') {
        host.value = 'smtp.mailgun.org';
        port.value = '587';
        enc.value = 'tls';
    } else if (type === 'sendgrid') {
        host.value = 'smtp.sendgrid.net';
        port.value = '587';
        enc.value = 'tls';
    }
}

function togglePassField(inputId, btn) {
    var input = document.getElementById(inputId);
    var icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function openTestModal() {
    document.getElementById('testResultBox').className = 'hidden';
    document.getElementById('smtpTestModal').classList.remove('hidden');
}

function closeTestModal() {
    document.getElementById('smtpTestModal').classList.add('hidden');
}

function runSmtpTest() {
    var email = document.getElementById('testEmailTarget').value.trim();
    var btn = document.getElementById('smtpTestSendBtn');
    var resBox = document.getElementById('testResultBox');

    if (!email) {
        alert('Please enter an email address.');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Testing Connection...';

    var fd = new FormData();
    fd.append('ajax_send_test_email', '1');
    fd.append('target_email', email);

    fetch('settings-smtp.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Test Email';
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
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Test Email';
        resBox.className = 'p-3 rounded-xl text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200';
        resBox.innerText = 'Network communication error.';
    });
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTestModal();
    }
});
</script>

<?php include 'footer.php'; ?>
