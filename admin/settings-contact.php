<?php
$page_title = 'Contact Page Settings';
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
    logActivity('Updated Contact Settings', 'Contact page content updated');
    header('Location: settings-contact.php?s=1');
    exit;
}

$success = isset($_GET['s']) ? 'Contact page settings saved successfully!' : '';
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
                <span class="p-2 bg-pink-50 text-pink-600 rounded-lg text-sm"><i class="fa-solid fa-headset"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Contact Page Settings</h1>
            </div>
            <p class="text-xs text-gray-500">Configure page banner headings, subtitle text, and form spam protection.</p>
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
            
            <!-- 1. Headings -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-4 text-xs">
                <div class="flex items-center gap-2 pb-3 border-b border-gray-100">
                    <span class="p-2 bg-pink-50 text-pink-600 rounded-lg text-xs"><i class="fa-solid fa-signature"></i></span>
                    <h2 class="text-sm font-bold text-gray-900">Banner Headings</h2>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Contact Page Heading</label>
                    <input type="text" name="contact_page_heading" value="<?php echo htmlspecialchars($s['contact_page_heading'] ?? 'Contact Us'); ?>" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-pink-500 focus:border-pink-500 outline-none">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Page Subtitle / Description</label>
                    <textarea name="contact_page_subheading" rows="3" class="w-full border border-gray-300 rounded-xl p-3 text-xs focus:ring-1 focus:ring-pink-500 focus:border-pink-500 outline-none leading-relaxed"><?php echo htmlspecialchars($s['contact_page_subheading'] ?? 'Have questions or need assistance? Our 24/7 technical team is here to help.'); ?></textarea>
                </div>
            </div>

            <!-- 2. Protection -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-4 text-xs">
                <div class="flex items-center gap-2 pb-3 border-b border-gray-100">
                    <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-xs"><i class="fa-solid fa-shield-halved"></i></span>
                    <h2 class="text-sm font-bold text-gray-900">Spam Prevention</h2>
                </div>

                <div class="p-4 rounded-xl bg-gray-50 border border-gray-200 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-gray-900">Enable Google reCAPTCHA</h3>
                        <p class="text-[11px] text-gray-500 mt-0.5">Protect contact inquiry forms from automated spam bots</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="recaptcha_enabled" value="0">
                        <input type="checkbox" name="recaptcha_enabled" value="1" <?php echo ($s['recaptcha_enabled'] ?? '0') == '1' ? 'checked' : ''; ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <p class="text-gray-500 text-[11px]">
                    Configure reCAPTCHA Site Key and Secret Key under <a href="settings-integrations.php" class="text-blue-600 font-bold hover:underline">Integrations</a>.
                </p>
            </div>

        </div>

        <div class="flex items-center gap-3">
            <button type="submit" name="submit" class="bg-pink-600 hover:bg-pink-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-xs transition flex items-center gap-2 text-xs cursor-pointer">
                <i class="fa-solid fa-floppy-disk"></i> Save Contact Settings
            </button>
        </div>

    </form>

</div>

<?php include 'footer.php'; ?>
