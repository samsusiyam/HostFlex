<?php
$page_title = 'Footer Settings';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminRole(['admin']);

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
    logActivity('Updated Footer Settings', 'Footer text and copyright updated');
    header('Location: settings-footer.php?s=1');
    exit;
}

$success = isset($_GET['s']) ? 'Footer settings updated successfully!' : '';
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
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-shoe-prints"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Footer Content Settings</h1>
            </div>
            <p class="text-xs text-gray-500">Manage copyright statement, footer brand bio description, and navigation quick-links.</p>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left 2 Cols: Form -->
        <div class="lg:col-span-2">
            <form method="POST" class="space-y-6">
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-4 text-xs">
                    <div class="flex items-center gap-2 pb-3 border-b border-gray-100">
                        <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-xs"><i class="fa-solid fa-pen-to-square"></i></span>
                        <h2 class="text-sm font-bold text-gray-900">Footer Text & Notices</h2>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Copyright Notice Text</label>
                        <input type="text" name="footer_copyright" value="<?php echo htmlspecialchars($s['footer_copyright'] ?? '© {year} Host Nibo. All rights reserved.'); ?>" placeholder="© {year} Host Nibo. All rights reserved." class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <span class="text-[11px] text-gray-400 mt-1 block">Tip: Use <code class="bg-gray-100 px-1 rounded font-bold">{year}</code> to automatically display the current year.</span>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Footer Brand Bio / Description</label>
                        <textarea name="footer_description" rows="4" class="w-full border border-gray-300 rounded-xl p-3 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none leading-relaxed" placeholder="Host Nibo provides industry leading NVMe SSD web hosting, BDIX connectivity, domain registration, and cloud server solutions with 24/7 dedicated support."><?php echo htmlspecialchars($s['footer_description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" name="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-xs transition flex items-center gap-2 text-xs cursor-pointer">
                        <i class="fa-solid fa-floppy-disk"></i> Save Footer Settings
                    </button>
                </div>
            </form>
        </div>

        <!-- Right 1 Col: Related Quick Links -->
        <div class="space-y-4 text-xs">
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-4">
                <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-arrow-up-right-from-square text-blue-600"></i> Related Footer Customizers
                </h3>
                <div class="space-y-2">
                    <a href="settings-branding.php" class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-blue-50 hover:text-blue-600 transition font-bold text-gray-700">
                        <span class="flex items-center gap-2"><i class="fa-solid fa-palette text-emerald-500"></i> Footer Logo</span>
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </a>
                    <a href="menus.php" class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-blue-50 hover:text-blue-600 transition font-bold text-gray-700">
                        <span class="flex items-center gap-2"><i class="fa-solid fa-bars text-blue-500"></i> Footer Navigation Menu</span>
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </a>
                    <a href="settings-popup.php" class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-blue-50 hover:text-blue-600 transition font-bold text-gray-700">
                        <span class="flex items-center gap-2"><i class="fa-solid fa-share-nodes text-purple-500"></i> Social Media Icons</span>
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </a>
                </div>
            </div>
        </div>

    </div>

</div>

<?php include 'footer.php'; ?>
