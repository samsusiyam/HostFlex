<?php
$page_title = 'Maintenance Mode';
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
    header('Location: settings-maintenance.php?s=1');
    exit;
}
if (isset($_GET['s'])) {
    $success = 'Maintenance settings saved!';
}
$settings_result = mysqli_query($conn, "SELECT * FROM settings ORDER BY setting_key");
$s = []; while ($row = mysqli_fetch_assoc($settings_result)) { $s[$row['setting_key']] = $row['setting_value']; }
$is_active = ($s['maintenance_mode'] ?? '0') === '1';
?>
<?php include 'header.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Maintenance Mode</h1>
    <p class="text-gray-500 dark:text-gray-400">Enable maintenance mode to temporarily hide your website from visitors</p>
</div>
<?php if (isset($success)): ?><div class="bg-green-100 border border-green-400 text-green-700 dark:bg-green-900/30 dark:border-green-700 dark:text-green-300 px-4 py-3 rounded mb-4"><?php echo $success; ?></div><?php endif; ?>

<?php if ($is_active): ?>
<div class="bg-yellow-100 border border-yellow-400 text-yellow-800 dark:bg-yellow-900/30 dark:border-yellow-700 dark:text-yellow-300 px-4 py-3 rounded mb-4 flex items-center gap-2">
    <i class="fa fa-exclamation-triangle"></i> <strong>Maintenance mode is currently ACTIVE.</strong> Visitors will see the maintenance page. Admin users can still access the site.
</div>
<?php endif; ?>

<form method="POST">
    <?= csrfField() ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <div class="flex items-center gap-3 mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="hidden" name="maintenance_mode" value="0">
                <input type="checkbox" name="maintenance_mode" value="1" <?php echo $is_active ? 'checked' : ''; ?> class="sr-only peer" id="maintenanceToggle">
                <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-500"></div>
                <span class="ms-3 text-sm font-medium text-gray-700 dark:text-gray-200">Enable Maintenance Mode</span>
            </label>
        </div>
        <div class="grid grid-cols-1 gap-4 max-w-2xl">
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Page Title (Browser Tab)</label>
                <input type="text" name="maintenance_title" value="<?php echo htmlspecialchars($s['maintenance_title'] ?? 'Under Maintenance'); ?>" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
            </div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Heading</label>
                <input type="text" name="maintenance_heading" value="<?php echo htmlspecialchars($s['maintenance_heading'] ?? "We'll be back soon!"); ?>" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
            </div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Message</label>
                <textarea name="maintenance_message" rows="4" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"><?php echo htmlspecialchars($s['maintenance_message'] ?? 'Our website is currently undergoing scheduled maintenance. Please check back later.'); ?></textarea>
            </div>
        </div>
    </div>
    <button type="submit" name="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 dark:hover:bg-blue-600"><i class="fa fa-save"></i> Save Settings</button>
</form>
<?php include 'footer.php'; ?>
