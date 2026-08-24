<?php
$page_title = 'Logo & Branding';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminRole(['admin']);

$upload_dir = '../uploads/branding/';
if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);

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

    $file_fields = [
        'header_logo_file' => 'header_logo',
        'footer_logo_file' => 'footer_logo',
        'favicon_file' => 'favicon'
    ];

    foreach ($file_fields as $input_name => $setting_key) {
        if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES[$input_name]['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg','ico'])) {
                $fname = $setting_key . '_' . time() . '.' . $ext;
                $target = $upload_dir . $fname;
                if (move_uploaded_file($_FILES[$input_name]['tmp_name'], $target)) {
                    if ($ext === 'svg') {
                        $svg_c = file_get_contents($target);
                        $svg_c = preg_replace('/<script[\s\S]*?<\/script>/i', '', $svg_c);
                        $svg_c = preg_replace('/(?:\bon\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+))/i', '', $svg_c);
                        $svg_c = preg_replace('/javascript:[^"\'\s>]+/i', '', $svg_c);
                        file_put_contents($target, $svg_c);
                    }
                    $path = 'uploads/branding/' . $fname;
                    mysqli_query($conn, "UPDATE settings SET setting_value = '$path' WHERE setting_key = '$setting_key'");
                }
            }
        }
    }

    logActivity('Updated Branding', 'Logo and brand assets updated');
    header('Location: settings-branding.php?s=1');
    exit;
}

$success = isset($_GET['s']) ? 'Branding settings updated successfully!' : '';
$settings_result = mysqli_query($conn, "SELECT * FROM settings ORDER BY setting_key");
$s = [];
while ($row = mysqli_fetch_assoc($settings_result)) {
    $s[$row['setting_key']] = $row['setting_value'];
}
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-emerald-50 text-emerald-600 rounded-lg text-sm"><i class="fa-solid fa-palette"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Logo & Branding</h1>
            </div>
            <p class="text-xs text-gray-500">Manage header logo, footer logo, website favicon, and brand descriptions.</p>
        </div>
    </div>

    <!-- Alert Notification -->
    <?php if ($success): ?>
    <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between bg-emerald-50 text-emerald-800 border border-emerald-200">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span><?php echo htmlspecialchars($success); ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 cursor-pointer"><i class="fa-solid fa-xmark text-sm"></i></button>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- 1. Header Logo -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-4 text-xs">
                <div class="flex items-center gap-2 pb-3 border-b border-gray-100">
                    <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-xs"><i class="fa-solid fa-heading"></i></span>
                    <h2 class="text-sm font-bold text-gray-900">Header Logo</h2>
                </div>

                <?php $header_logo = $s['header_logo'] ?? 'images/bg.png'; ?>
                <div class="p-4 rounded-xl bg-gray-900 border border-gray-800 flex items-center justify-center min-h-[90px] overflow-hidden">
                    <img id="headerLogoPreview" src="/<?php echo ltrim($header_logo, '/'); ?>" class="max-h-12 object-contain" alt="Header Logo">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Upload New Logo (PNG, SVG, WEBP)</label>
                    <input type="file" name="header_logo_file" accept="image/*" class="w-full border border-gray-300 rounded-xl p-1.5 text-[11px] bg-gray-50 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:bg-blue-50 file:text-blue-700 file:font-bold" onchange="previewFile(this, 'headerLogoPreview')">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Or File Path</label>
                    <input type="text" name="header_logo" value="<?php echo htmlspecialchars($header_logo); ?>" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none font-mono">
                </div>
            </div>

            <!-- 2. Footer Logo -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-4 text-xs">
                <div class="flex items-center gap-2 pb-3 border-b border-gray-100">
                    <span class="p-2 bg-purple-50 text-purple-600 rounded-lg text-xs"><i class="fa-solid fa-shoe-prints"></i></span>
                    <h2 class="text-sm font-bold text-gray-900">Footer Logo</h2>
                </div>

                <?php $footer_logo = $s['footer_logo'] ?? 'images/bg.png'; ?>
                <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-center min-h-[90px] overflow-hidden">
                    <img id="footerLogoPreview" src="/<?php echo ltrim($footer_logo, '/'); ?>" class="max-h-12 object-contain" alt="Footer Logo">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Upload New Footer Logo</label>
                    <input type="file" name="footer_logo_file" accept="image/*" class="w-full border border-gray-300 rounded-xl p-1.5 text-[11px] bg-gray-50 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:bg-purple-50 file:text-purple-700 file:font-bold" onchange="previewFile(this, 'footerLogoPreview')">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Or File Path</label>
                    <input type="text" name="footer_logo" value="<?php echo htmlspecialchars($footer_logo); ?>" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-purple-500 focus:border-purple-500 outline-none font-mono">
                </div>
            </div>

            <!-- 3. Favicon -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-4 text-xs">
                <div class="flex items-center gap-2 pb-3 border-b border-gray-100">
                    <span class="p-2 bg-amber-50 text-amber-600 rounded-lg text-xs"><i class="fa-solid fa-icons"></i></span>
                    <h2 class="text-sm font-bold text-gray-900">Browser Favicon</h2>
                </div>

                <?php $favicon = $s['favicon'] ?? 'images/favicon.ico'; ?>
                <div class="p-4 rounded-xl bg-gray-50 border border-gray-200 flex items-center justify-center min-h-[90px] overflow-hidden">
                    <img id="faviconPreview" src="/<?php echo ltrim($favicon, '/'); ?>" class="w-10 h-10 object-contain p-1 bg-white rounded-lg border border-gray-200 shadow-xs" alt="Favicon">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Upload New Favicon (.ico, .png)</label>
                    <input type="file" name="favicon_file" accept=".ico,image/png,image/x-icon" class="w-full border border-gray-300 rounded-xl p-1.5 text-[11px] bg-gray-50 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:bg-amber-50 file:text-amber-700 file:font-bold" onchange="previewFile(this, 'faviconPreview')">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Or File Path</label>
                    <input type="text" name="favicon" value="<?php echo htmlspecialchars($favicon); ?>" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-amber-500 focus:border-amber-500 outline-none font-mono">
                </div>
            </div>

        </div>

        <div class="flex items-center gap-3">
            <button type="submit" name="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-xs transition flex items-center gap-2 text-xs cursor-pointer">
                <i class="fa-solid fa-floppy-disk"></i> Save Branding Assets
            </button>
        </div>

    </form>

</div>

<script>
function previewFile(input, targetId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(targetId).src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include 'footer.php'; ?>
