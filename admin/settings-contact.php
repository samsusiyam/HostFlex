<?php
$page_title = 'Contact Page';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();
checkPermission('settings', 'edit');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFToken($_POST['csrf_token'] ?? '');
    foreach ($_POST as $key => $value) {
        if ($key === 'submit') continue;
        $s_key = sanitize($key);
        $s_value = mysqli_real_escape_string($conn, $value);
        $check = mysqli_query($conn, "SELECT id FROM settings WHERE setting_key = '$s_key'");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "UPDATE settings SET setting_value = '$s_value' WHERE setting_key = '$s_key'");
        } else {
            mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('$s_key', '$s_value')");
        }
    }
    header('Location: settings-contact.php?s=1');
    exit;
}
if (isset($_GET['s'])) {
    $success = 'Settings updated!';
}
$settings_result = mysqli_query($conn, "SELECT * FROM settings ORDER BY setting_key");
$s = []; while ($row = mysqli_fetch_assoc($settings_result)) { $s[$row['setting_key']] = $row['setting_value']; }
?>
<?php include 'header.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Contact Page</h1>
    <p class="text-gray-500 dark:text-gray-400">Contact page heading and subheading text</p>
</div>
<?php if (isset($success)): ?><div class="bg-green-100 border border-green-400 text-green-700 dark:bg-green-900/30 dark:border-green-700 dark:text-green-300 px-4 py-3 rounded mb-4"><?php echo $success; ?></div><?php endif; ?>
<form method="POST">
    <?= csrfField() ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Page Heading</label><input type="text" name="contact_page_heading" value="<?php echo htmlspecialchars($s['contact_page_heading'] ?? 'Contact Us'); ?>" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Page Subheading</label><textarea name="contact_page_subheading" rows="2" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"><?php echo htmlspecialchars($s['contact_page_subheading'] ?? 'We would love to hear from you.'); ?></textarea></div>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4 flex items-center gap-2"><i class="fa fa-envelope text-green-600 dark:text-green-400"></i> Forward Email</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Contact form submissions will be forwarded to these email addresses. Add one per line.</p>
        <div><textarea name="contact_forward_emails" rows="4" class="w-full border rounded px-3 py-2 font-mono text-sm dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" placeholder="admin@example.com&#10;support@example.com"><?php echo htmlspecialchars($s['contact_forward_emails'] ?? ''); ?></textarea></div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4 flex items-center gap-2"><i class="fa fa-shield-alt text-blue-600 dark:text-blue-400"></i> reCAPTCHA Settings</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Configure reCAPTCHA keys in <a href="settings-integrations.php" class="text-blue-600 underline dark:text-blue-400">Integrations</a> page.</p>
        <div class="flex items-center gap-3">
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="hidden" name="recaptcha_enabled" value="0">
                <input type="checkbox" name="recaptcha_enabled" value="1" <?php echo ($s['recaptcha_enabled'] ?? '0') == '1' ? 'checked' : ''; ?> class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                <span class="ms-3 text-sm font-medium text-gray-700 dark:text-gray-200">Enable reCAPTCHA on Contact Form</span>
            </label>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4 flex items-center gap-2"><i class="fa fa-search text-purple-600 dark:text-purple-400"></i> Contact Page SEO</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Meta tags for the contact page (for search engines).</p>
        <div class="grid grid-cols-1 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Contact Page Title</label><input type="text" name="contact_page_title" value="<?php echo htmlspecialchars($s['contact_page_title'] ?? ''); ?>" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" placeholder="Contact Us - HostNibo"></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Meta Description</label><textarea name="contact_meta_description" rows="2" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" placeholder="Get in touch with HostNibo support team..."><?php echo htmlspecialchars($s['contact_meta_description'] ?? ''); ?></textarea></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Meta Keywords</label><input type="text" name="contact_meta_keywords" value="<?php echo htmlspecialchars($s['contact_meta_keywords'] ?? ''); ?>" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" placeholder="contact, support, web hosting help"></div>
        </div>
    </div>
    <button type="submit" name="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 dark:hover:bg-blue-600"><i class="fa fa-save"></i> Save Settings</button>
</form>
<?php include 'footer.php'; ?>
