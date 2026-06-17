<?php
$page_title = 'Logo & Branding';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

$upload_dir = '../uploads/branding/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    if (isset($_FILES['header_logo_file']) && $_FILES['header_logo_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['header_logo_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg'])) {
            $fname = 'header_logo_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['header_logo_file']['tmp_name'], $upload_dir . $fname);
            $path = 'uploads/branding/' . $fname;
            mysqli_query($conn, "UPDATE settings SET setting_value = '$path' WHERE setting_key = 'header_logo'");
        }
    }
    if (isset($_FILES['footer_logo_file']) && $_FILES['footer_logo_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['footer_logo_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg'])) {
            $fname = 'footer_logo_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['footer_logo_file']['tmp_name'], $upload_dir . $fname);
            $path = 'uploads/branding/' . $fname;
            mysqli_query($conn, "UPDATE settings SET setting_value = '$path' WHERE setting_key = 'footer_logo'");
        }
    }
    header('Location: settings-branding.php?s=1');
    exit;
}
if (isset($_GET['s'])) {
    $success = 'Settings updated!';
}
$settings_result = mysqli_query($conn, "SELECT * FROM settings ORDER BY setting_key");
$s = [];
while ($row = mysqli_fetch_assoc($settings_result)) {
    $s[$row['setting_key']] = $row['setting_value'];
}
include 'header.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Logo & Branding</h1>
    <p class="text-gray-500">Header logo, footer logo, description text</p>
</div>
<?php if (isset($success)): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $success; ?></div><?php endif; ?>
<form method="POST" enctype="multipart/form-data">
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Header Logo</label>
                <?php $header_logo = $s['header_logo'] ?? ''; ?>
                <?php if ($header_logo): ?>
                <div class="mb-2"><img src="../<?php echo htmlspecialchars($header_logo); ?>" class="max-h-16 rounded border" id="headerLogoPreview"></div>
                <?php else: ?>
                <div class="mb-2"><img class="max-h-16 hidden rounded border" id="headerLogoPreview"></div>
                <?php endif; ?>
                <input type="file" name="header_logo_file" accept="image/*" class="w-full border rounded px-3 py-2 text-sm" onchange="document.getElementById('headerLogoPreview').src=window.URL.createObjectURL(this.files[0]);document.getElementById('headerLogoPreview').classList.remove('hidden')">
                <input type="text" name="header_logo" value="<?php echo htmlspecialchars($header_logo); ?>" placeholder="Or enter path" class="w-full border rounded px-3 py-2 text-sm mt-2">
                <p class="text-xs text-gray-400 mt-1">Upload image or enter path manually</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Footer Logo</label>
                <?php $footer_logo = $s['footer_logo'] ?? ''; ?>
                <?php if ($footer_logo): ?>
                <div class="mb-2"><img src="../<?php echo htmlspecialchars($footer_logo); ?>" class="max-h-16 rounded border" id="footerLogoPreview"></div>
                <?php else: ?>
                <div class="mb-2"><img class="max-h-16 hidden rounded border" id="footerLogoPreview"></div>
                <?php endif; ?>
                <input type="file" name="footer_logo_file" accept="image/*" class="w-full border rounded px-3 py-2 text-sm" onchange="document.getElementById('footerLogoPreview').src=window.URL.createObjectURL(this.files[0]);document.getElementById('footerLogoPreview').classList.remove('hidden')">
                <input type="text" name="footer_logo" value="<?php echo htmlspecialchars($footer_logo); ?>" placeholder="Or enter path" class="w-full border rounded px-3 py-2 text-sm mt-2">
                <p class="text-xs text-gray-400 mt-1">Upload image or enter path manually</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Footer Description Text</label>
                <textarea name="footer_description" rows="3" class="w-full border rounded px-3 py-2"><?php echo htmlspecialchars($s['footer_description'] ?? ''); ?></textarea>
                <p class="text-xs text-gray-400 mt-1">Appears below the footer logo</p>
            </div>
        </div>
    </div>
    <button type="submit" name="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700"><i class="fa fa-save"></i> Save Settings</button>
</form>
<?php include 'footer.php'; ?>
