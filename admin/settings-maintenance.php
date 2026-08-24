<?php
$page_title = 'Maintenance Mode';
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
    $state = ($_POST['maintenance_mode'] ?? '0') === '1' ? 'Enabled' : 'Disabled';
    logActivity('Updated Maintenance Mode', "Status: $state");
    header('Location: settings-maintenance.php?s=1');
    exit;
}

$success = isset($_GET['s']) ? 'Maintenance mode settings saved!' : '';
$settings_result = mysqli_query($conn, "SELECT * FROM settings ORDER BY setting_key");
$s = []; 
while ($row = mysqli_fetch_assoc($settings_result)) { 
    $s[$row['setting_key']] = $row['setting_value']; 
}
$is_active = ($s['maintenance_mode'] ?? '0') === '1';
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-orange-50 text-orange-600 rounded-lg text-sm"><i class="fa-solid fa-screwdriver-wrench"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Maintenance Mode</h1>
            </div>
            <p class="text-xs text-gray-500">Temporarily redirect non-admin visitors to an under-maintenance landing page during system upgrades.</p>
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

    <?php if ($is_active): ?>
    <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-900 flex items-center gap-3 text-xs font-semibold shadow-xs">
        <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center text-sm shrink-0">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
            <strong class="block text-amber-950 font-bold">Maintenance mode is currently ACTIVE!</strong>
            <span class="text-amber-800/90">Public visitors see your maintenance message. Logged-in administrators retain full access.</span>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-6 text-xs max-w-2xl">
            
            <!-- Toggle Switch -->
            <div class="p-4 rounded-xl bg-gray-50 border border-gray-200 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900">Enable Maintenance Mode</h3>
                    <p class="text-[11px] text-gray-500 mt-0.5">Toggle site offline status for visitors</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="maintenance_mode" value="0">
                    <input type="checkbox" name="maintenance_mode" value="1" <?php echo $is_active ? 'checked' : ''; ?> class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                </label>
            </div>

            <div>
                <label class="block font-bold text-gray-700 mb-1">Browser Tab Title</label>
                <input type="text" name="maintenance_title" value="<?php echo htmlspecialchars($s['maintenance_title'] ?? 'Under Maintenance - Host Nibo'); ?>" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-amber-500 focus:border-amber-500 outline-none">
            </div>

            <div>
                <label class="block font-bold text-gray-700 mb-1">Page Heading</label>
                <input type="text" name="maintenance_heading" value="<?php echo htmlspecialchars($s['maintenance_heading'] ?? "We'll Be Back Shortly! 🚀"); ?>" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-amber-500 focus:border-amber-500 outline-none font-bold">
            </div>

            <div>
                <label class="block font-bold text-gray-700 mb-1">Visitor Notice Message</label>
                <textarea name="maintenance_message" rows="4" class="w-full border border-gray-300 rounded-xl p-3 text-xs focus:ring-1 focus:ring-amber-500 focus:border-amber-500 outline-none leading-relaxed"><?php echo htmlspecialchars($s['maintenance_message'] ?? 'Our platform is currently undergoing scheduled infrastructure upgrades. We apologize for any inconvenience.'); ?></textarea>
            </div>

            <div class="pt-2">
                <button type="submit" name="submit" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-xs transition flex items-center gap-2 text-xs cursor-pointer">
                    <i class="fa-solid fa-floppy-disk"></i> Save Maintenance Settings
                </button>
            </div>

        </div>

    </form>

</div>

<?php include 'footer.php'; ?>
