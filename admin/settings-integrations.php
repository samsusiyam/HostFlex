<?php
$page_title = 'Integrations & Custom Scripts';
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
    logActivity('Updated Integrations', 'Third-party scripts and API keys updated');
    header('Location: settings-integrations.php?s=1');
    exit;
}

$success = isset($_GET['s']) ? 'Integrations updated successfully!' : '';
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
                <span class="p-2 bg-teal-50 text-teal-600 rounded-lg text-sm"><i class="fa-solid fa-puzzle-piece"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Integrations & Tracking Scripts</h1>
            </div>
            <p class="text-xs text-gray-500">Inject analytics tags, live chat widgets (Crisp, Tawk.to), web push alerts, and reCAPTCHA protection.</p>
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
            
            <!-- 1. Custom Header & Footer Code -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-4 text-xs">
                <div class="flex items-center gap-2 pb-3 border-b border-gray-100">
                    <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-xs"><i class="fa-solid fa-code"></i></span>
                    <h2 class="text-sm font-bold text-gray-900">Custom HTML / JavaScript Injection</h2>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Header Scripts (&lt;head&gt; Injection)</label>
                    <textarea name="header_code" rows="5" placeholder="<!-- Google Analytics, Facebook Pixel, or Meta Tags -->" class="w-full border border-gray-300 rounded-xl p-3 font-mono text-[11px] focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none"><?php echo htmlspecialchars($s['header_code'] ?? ''); ?></textarea>
                    <span class="text-[11px] text-gray-400 mt-1 block">Rendered automatically right before &lt;/head&gt; tag on all public frontend pages.</span>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Footer Scripts (&lt;/body&gt; Injection)</label>
                    <textarea name="footer_code" rows="5" placeholder="<!-- Custom Tracking, Pixel Event or External JS -->" class="w-full border border-gray-300 rounded-xl p-3 font-mono text-[11px] focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none"><?php echo htmlspecialchars($s['footer_code'] ?? ''); ?></textarea>
                    <span class="text-[11px] text-gray-400 mt-1 block">Rendered right before closing &lt;/body&gt; tag.</span>
                </div>
            </div>

            <!-- 2. Live Chat & Push Providers -->
            <div class="space-y-6">
                
                <!-- Crisp Chat -->
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-3 text-xs">
                    <div class="flex items-center gap-2 pb-2 border-b border-gray-100">
                        <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-xs"><i class="fa-solid fa-comments"></i></span>
                        <h2 class="text-sm font-bold text-gray-900">Crisp Live Chat</h2>
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Crisp Website ID</label>
                        <input type="text" name="crisp_website_id" value="<?php echo htmlspecialchars($s['crisp_website_id'] ?? ''); ?>" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs font-mono focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <span class="text-[11px] text-gray-400 mt-1 block">From Crisp Dashboard &rarr; Website Settings &rarr; Setup Instructions</span>
                    </div>
                </div>

                <!-- Tawk.to -->
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-3 text-xs">
                    <div class="flex items-center gap-2 pb-2 border-b border-gray-100">
                        <span class="p-2 bg-emerald-50 text-emerald-600 rounded-lg text-xs"><i class="fa-solid fa-headset"></i></span>
                        <h2 class="text-sm font-bold text-gray-900">Tawk.to Live Chat</h2>
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Tawk.to Property / Direct Widget ID</label>
                        <input type="text" name="tawkto_widget_id" value="<?php echo htmlspecialchars($s['tawkto_widget_id'] ?? ''); ?>" placeholder="e.g. 60xxxxxxxxxx/1xxxxxxx" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs font-mono focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                    </div>
                </div>

                <!-- OneSignal -->
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-3 text-xs">
                    <div class="flex items-center gap-2 pb-2 border-b border-gray-100">
                        <span class="p-2 bg-red-50 text-red-600 rounded-lg text-xs"><i class="fa-solid fa-bell"></i></span>
                        <h2 class="text-sm font-bold text-gray-900">OneSignal Web Push Notifications</h2>
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">OneSignal App ID</label>
                        <input type="text" name="onesignal_app_id" value="<?php echo htmlspecialchars($s['onesignal_app_id'] ?? ''); ?>" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs font-mono focus:ring-1 focus:ring-red-500 focus:border-red-500 outline-none">
                    </div>
                </div>

            </div>

        </div>

        <!-- Google reCAPTCHA v2 -->
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-4 text-xs">
            <div class="flex items-center gap-2 pb-3 border-b border-gray-100">
                <span class="p-2 bg-purple-50 text-purple-600 rounded-lg text-xs"><i class="fa-solid fa-shield-halved"></i></span>
                <h2 class="text-sm font-bold text-gray-900">Google reCAPTCHA v2 (Checkbox Protection)</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-gray-700 mb-1">reCAPTCHA Site Key</label>
                    <input type="text" name="recaptcha_site_key" value="<?php echo htmlspecialchars($s['recaptcha_site_key'] ?? ''); ?>" placeholder="6LeIx0aTAAAAA..." class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs font-mono focus:ring-1 focus:ring-purple-500 focus:border-purple-500 outline-none">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">reCAPTCHA Secret Key</label>
                    <input type="password" name="recaptcha_secret_key" value="<?php echo htmlspecialchars($s['recaptcha_secret_key'] ?? ''); ?>" placeholder="6LeIx0aTAAAA..." class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs font-mono focus:ring-1 focus:ring-purple-500 focus:border-purple-500 outline-none">
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" name="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-xs transition flex items-center gap-2 text-xs cursor-pointer">
                <i class="fa-solid fa-floppy-disk"></i> Save Integrations
            </button>
        </div>

    </form>

</div>

<?php include 'footer.php'; ?>
