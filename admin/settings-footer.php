<?php
$page_title = 'Footer Settings';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

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
    header('Location: settings-footer.php?s=1');
    exit;
}
if (isset($_GET['s'])) {
    $success = 'Footer settings updated!';
}
$settings_result = mysqli_query($conn, "SELECT * FROM settings ORDER BY setting_key");
$s = [];
while ($row = mysqli_fetch_assoc($settings_result)) {
    $s[$row['setting_key']] = $row['setting_value'];
}
?>
<?php include 'header.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Footer Settings</h1>
    <p class="text-gray-500">Edit footer content, links, and copyright</p>
</div>
<?php if (isset($success)): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $success; ?></div><?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <form method="POST">
            <?= csrfField() ?>
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">Footer Content</h2>
                <div class="space-y-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Copyright Text</label><input type="text" name="footer_copyright" value="<?php echo htmlspecialchars($s['footer_copyright'] ?? ''); ?>" class="w-full border rounded px-3 py-2"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea name="footer_description" rows="4" class="w-full border rounded px-3 py-2"><?php echo htmlspecialchars($s['footer_description'] ?? ''); ?></textarea></div>
                </div>
            </div>
            <button type="submit" name="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700"><i class="fa fa-save"></i> Save</button>
        </form>
    </div>
    <div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Quick Links</h2>
            <div class="space-y-3 text-sm">
                <a href="settings-branding.php" class="flex items-center gap-2 text-blue-600 hover:underline"><i class="fa fa-image"></i> Logo & Branding</a>
                <a href="menus.php" class="flex items-center gap-2 text-blue-600 hover:underline"><i class="fa fa-bars"></i> Menu Manager</a>
                <a href="settings-popup.php" class="flex items-center gap-2 text-blue-600 hover:underline"><i class="fa fa-share-alt"></i> Social Links</a>
                <hr>
                <p class="text-gray-500">Footer menu items, logos, and social links are managed from their respective pages.</p>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
