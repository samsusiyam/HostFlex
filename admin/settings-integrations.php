<?php
$page_title = 'Integrations';
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
    header('Location: settings-integrations.php?s=1');
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
    <h1 class="text-2xl font-bold text-gray-800">Integrations</h1>
    <p class="text-gray-500">Third-party integrations, custom codes, and Google reCAPTCHA</p>
</div>
<?php if (isset($success)): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $success; ?></div><?php endif; ?>
<form method="POST">
    <?= csrfField() ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4 flex items-center gap-2"><i class="fa fa-code text-blue-600"></i> Custom Code</h2>
            <div class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Header Code</label>
                    <textarea name="header_code" rows="6" class="w-full border rounded px-3 py-2 font-mono text-sm"><?php echo htmlspecialchars($s['header_code'] ?? ''); ?></textarea>
                    <p class="text-xs text-gray-400 mt-1">Inserted before &lt;/head&gt;. Use for tracking codes, meta tags, etc.</p>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Footer Code</label>
                    <textarea name="footer_code" rows="6" class="w-full border rounded px-3 py-2 font-mono text-sm"><?php echo htmlspecialchars($s['footer_code'] ?? ''); ?></textarea>
                    <p class="text-xs text-gray-400 mt-1">Inserted before &lt;/body&gt;. Use for chat widgets, analytics, etc.</p>
                </div>
            </div>
        </div>
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4 flex items-center gap-2"><i class="fa fa-bell text-blue-600"></i> OneSignal</h2>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">App ID</label>
                    <input type="text" name="onesignal_app_id" value="<?php echo htmlspecialchars($s['onesignal_app_id'] ?? ''); ?>" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" class="w-full border rounded px-3 py-2">
                    <p class="text-xs text-gray-400 mt-1">Find in OneSignal Dashboard → Settings → Keys & IDs</p>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4 flex items-center gap-2"><i class="fa fa-comment text-blue-600"></i> Tawk.to</h2>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Widget ID</label>
                    <input type="text" name="tawkto_widget_id" value="<?php echo htmlspecialchars($s['tawkto_widget_id'] ?? ''); ?>" placeholder="123abc" class="w-full border rounded px-3 py-2">
                    <p class="text-xs text-gray-400 mt-1">Find in Tawk.to Dashboard → Widget Code (the alphanumeric ID after "https://embed.tawk.to/")</p>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4 flex items-center gap-2"><i class="fa fa-comments text-blue-600"></i> Crisp Chat</h2>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Website ID</label>
                    <input type="text" name="crisp_website_id" value="<?php echo htmlspecialchars($s['crisp_website_id'] ?? ''); ?>" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" class="w-full border rounded px-3 py-2">
                    <p class="text-xs text-gray-400 mt-1">Find in Crisp Dashboard → Website Settings → Website ID</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 lg:col-span-2">
            <h2 class="text-lg font-semibold mb-4 flex items-center gap-2"><i class="fa fa-shield-alt text-blue-600"></i> Google reCAPTCHA</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Site Key</label>
                    <input type="text" name="recaptcha_site_key" value="<?php echo htmlspecialchars($s['recaptcha_site_key'] ?? ''); ?>" class="w-full border rounded px-3 py-2">
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Secret Key</label>
                    <input type="text" name="recaptcha_secret_key" value="<?php echo htmlspecialchars($s['recaptcha_secret_key'] ?? ''); ?>" class="w-full border rounded px-3 py-2">
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Get keys from <a href="https://www.google.com/recaptcha/admin" target="_blank" class="text-blue-600 underline">Google reCAPTCHA Admin</a> (reCAPTCHA v2 Checkbox). Enable/disable captcha on the Contact Page settings.</p>
        </div>
    </div>
    <button type="submit" name="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700"><i class="fa fa-save"></i> Save Settings</button>
</form>
<?php include 'footer.php'; ?>
