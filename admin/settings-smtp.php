<?php
$page_title = 'SMTP Settings';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();
checkPermission('settings', 'edit');
require_once '../includes/mail.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFToken($_POST['csrf_token'] ?? '');
    if (isset($_POST['test_email'])) {
        $admin_id = (int)$_SESSION['admin_id'];
        $admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT email FROM users WHERE id = $admin_id"));
        $test_to = $admin['email'] ?? getSetting('site_email');
        $mail_error = '';
        $sent = sendMail($test_to, 'Test Email from ' . getSetting('site_name'), '<h2>Test Email</h2><p>This is a test email from your SMTP configuration.</p>', '', $mail_error);
        if ($sent) {
            $success = 'Test email sent successfully to ' . htmlspecialchars($test_to) . '! Check your inbox (and spam folder).';
        } else {
            $error = 'Failed to send test email to ' . htmlspecialchars($test_to) . '. Error: ' . htmlspecialchars($mail_error);
        }
    } else {
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
        header('Location: settings-smtp.php?s=1');
        exit;
    }
}

if (isset($_GET['s']) && !$success && !$error) {
    $success = 'Settings updated successfully!';
}

$settings_result = mysqli_query($conn, "SELECT * FROM settings ORDER BY setting_key");
$s = []; while ($row = mysqli_fetch_assoc($settings_result)) { $s[$row['setting_key']] = $row['setting_value']; }
?>
<?php include 'header.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">SMTP Settings</h1>
    <p class="text-gray-500">Configure outgoing email server settings</p>
</div>

<?php if ($success): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4"><?php echo $error; ?></div><?php endif; ?>

<form method="POST">
    <?= csrfField() ?>
    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4 flex items-center"><i class="fa fa-server text-purple-600 mr-2"></i> SMTP Configuration</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">SMTP Host</label><input type="text" name="smtp_host" value="<?php echo htmlspecialchars($s['smtp_host'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" placeholder="smtp.gmail.com"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Port</label><input type="number" name="smtp_port" value="<?php echo htmlspecialchars($s['smtp_port'] ?? '587'); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" placeholder="587"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Encryption</label><select name="smtp_encryption" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                <option value="">NONE</option>
                <option value="tls" <?php echo ($s['smtp_encryption'] ?? '') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                <option value="ssl" <?php echo ($s['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
            </select></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Username</label><input type="text" name="smtp_username" value="<?php echo htmlspecialchars($s['smtp_username'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" autocomplete="off"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Password</label><input type="password" name="smtp_password" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" autocomplete="new-password" placeholder="Leave blank to keep current"><p class="text-xs text-gray-400 mt-1">Leave blank to keep existing password</p></div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4 flex items-center"><i class="fa fa-envelope text-blue-600 mr-2"></i> Sender Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">From Email</label><input type="email" name="smtp_from_email" value="<?php echo htmlspecialchars($s['smtp_from_email'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" placeholder="noreply@example.com"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">From Name</label><input type="text" name="smtp_from_name" value="<?php echo htmlspecialchars($s['smtp_from_name'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" placeholder="Your Site Name"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Reply-To Email</label><input type="email" name="smtp_reply_to" value="<?php echo htmlspecialchars($s['smtp_reply_to'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" placeholder="reply@example.com"></div>
        </div>
    </div>

    <div class="flex items-center gap-4">
        <button type="submit" name="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition shadow font-medium"><i class="fa fa-save mr-1"></i> Save Settings</button>
        <button type="submit" name="test_email" class="bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 transition shadow font-medium"><i class="fa fa-paper-plane mr-1"></i> Send Test Email</button>
    </div>
</form>

<?php include 'footer.php'; ?>
