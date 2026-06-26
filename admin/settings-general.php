<?php
$page_title = 'General Settings';
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
    header('Location: settings-general.php?s=1');
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
    <h1 class="text-2xl font-bold text-gray-800">General Settings</h1>
    <p class="text-gray-500">Site name, tagline, contact info, currency</p>
</div>
<?php if (isset($success)): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $success; ?></div><?php endif; ?>
<form method="POST" accept-charset="UTF-8">
    <?= csrfField() ?>
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Site Name</label><input type="text" name="site_name" value="<?php echo htmlspecialchars($s['site_name'] ?? ''); ?>" class="w-full border rounded px-3 py-2"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Currency Symbol</label><input type="text" name="currency_symbol" value="<?php echo htmlspecialchars($s['currency_symbol'] ?? ''); ?>" class="w-full border rounded px-3 py-2"></div>
            <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Site Tagline</label><input type="text" name="site_tagline" value="<?php echo htmlspecialchars($s['site_tagline'] ?? ''); ?>" class="w-full border rounded px-3 py-2"></div>
            <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Site Description</label><textarea name="site_description" rows="3" class="w-full border rounded px-3 py-2"><?php echo htmlspecialchars($s['site_description'] ?? ''); ?></textarea></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Site Email</label><input type="email" name="site_email" value="<?php echo htmlspecialchars($s['site_email'] ?? ''); ?>" class="w-full border rounded px-3 py-2"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Site Phone</label><input type="text" name="site_phone" value="<?php echo htmlspecialchars($s['site_phone'] ?? ''); ?>" class="w-full border rounded px-3 py-2"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Site Address</label><input type="text" name="site_address" value="<?php echo htmlspecialchars($s['site_address'] ?? ''); ?>" class="w-full border rounded px-3 py-2"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Footer Copyright</label><input type="text" name="footer_copyright" value="<?php echo htmlspecialchars($s['footer_copyright'] ?? ''); ?>" class="w-full border rounded px-3 py-2"></div>
        </div>
    </div>
    <button type="submit" name="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700"><i class="fa fa-save"></i> Save Settings</button>
</form>
<?php include 'footer.php'; ?>
