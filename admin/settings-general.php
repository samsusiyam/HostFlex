<?php
$page_title = 'General Settings';
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
    logActivity('Updated General Settings', 'Site configuration modified');
    header('Location: settings-general.php?s=1');
    exit;
}

$success = isset($_GET['s']) ? 'General settings updated successfully!' : '';
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
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-globe"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">General Settings</h1>
            </div>
            <p class="text-xs text-gray-500">Configure global website identity, default currency symbol, and organizational contact points.</p>
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

    <form method="POST" class="space-y-6">
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- 1. Site Identity -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-4 text-xs">
                <div class="flex items-center gap-2 pb-3 border-b border-gray-100">
                    <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-xs"><i class="fa-solid fa-signature"></i></span>
                    <h2 class="text-sm font-bold text-gray-900">Website Identity</h2>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Website Name</label>
                    <input type="text" name="site_name" value="<?php echo htmlspecialchars($s['site_name'] ?? 'Host Nibo'); ?>" required class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Site Tagline</label>
                    <input type="text" name="site_tagline" value="<?php echo htmlspecialchars($s['site_tagline'] ?? ''); ?>" placeholder="e.g. Ultra Fast Cloud & NVMe Web Hosting" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Default Currency Symbol / Code</label>
                    <div class="flex gap-2">
                        <input type="text" name="currency_symbol" id="currInput" value="<?php echo htmlspecialchars($s['currency_symbol'] ?? '৳'); ?>" class="w-28 border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none font-bold text-center">
                        <div class="flex flex-wrap gap-1 items-center">
                            <button type="button" onclick="document.getElementById('currInput').value = '৳'" class="px-2 py-1 bg-gray-100 hover:bg-blue-50 hover:text-blue-600 rounded-lg border border-gray-200 font-bold transition">৳ BDT</button>
                            <button type="button" onclick="document.getElementById('currInput').value = '$'" class="px-2 py-1 bg-gray-100 hover:bg-blue-50 hover:text-blue-600 rounded-lg border border-gray-200 font-bold transition">$ USD</button>
                            <button type="button" onclick="document.getElementById('currInput').value = '€'" class="px-2 py-1 bg-gray-100 hover:bg-blue-50 hover:text-blue-600 rounded-lg border border-gray-200 font-bold transition">€ EUR</button>
                            <button type="button" onclick="document.getElementById('currInput').value = '£'" class="px-2 py-1 bg-gray-100 hover:bg-blue-50 hover:text-blue-600 rounded-lg border border-gray-200 font-bold transition">£ GBP</button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Site Short Description</label>
                    <textarea name="site_description" rows="3" class="w-full border border-gray-300 rounded-xl p-3 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none leading-relaxed"><?php echo htmlspecialchars($s['site_description'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- 2. Official Contact Information -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-4 text-xs">
                <div class="flex items-center gap-2 pb-3 border-b border-gray-100">
                    <span class="p-2 bg-emerald-50 text-emerald-600 rounded-lg text-xs"><i class="fa-solid fa-address-book"></i></span>
                    <h2 class="text-sm font-bold text-gray-900">Official Contact Information</h2>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Official Support Email</label>
                    <input type="email" name="site_email" value="<?php echo htmlspecialchars($s['site_email'] ?? ''); ?>" placeholder="support@hostnibo.com" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Contact Hotline / Phone</label>
                    <input type="text" name="site_phone" value="<?php echo htmlspecialchars($s['site_phone'] ?? ''); ?>" placeholder="+880 1700-000000" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Physical Address / Office</label>
                    <input type="text" name="site_address" value="<?php echo htmlspecialchars($s['site_address'] ?? ''); ?>" placeholder="Dhaka, Bangladesh" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Footer Copyright Notice</label>
                    <input type="text" name="footer_copyright" value="<?php echo htmlspecialchars($s['footer_copyright'] ?? ''); ?>" placeholder="© {year} Host Nibo. All rights reserved." class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>

        </div>

        <div class="flex items-center gap-3">
            <button type="submit" name="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-xs transition flex items-center gap-2 text-xs cursor-pointer">
                <i class="fa-solid fa-floppy-disk"></i> Save General Settings
            </button>
        </div>

    </form>

</div>

<?php include 'footer.php'; ?>
