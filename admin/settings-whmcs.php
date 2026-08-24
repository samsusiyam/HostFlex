<?php
$page_title = 'WHMCS Integration';
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
    logActivity('Updated WHMCS Settings', 'Billing and portal URLs updated');
    header('Location: settings-whmcs.php?s=1');
    exit;
}

$success = isset($_GET['s']) ? 'WHMCS endpoints updated successfully!' : '';
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
                <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg text-sm"><i class="fa-solid fa-link"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">WHMCS & Billing Integration</h1>
            </div>
            <p class="text-xs text-gray-500">Configure WHMCS client area, domain cart registration, transfer, and affiliate URLs.</p>
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

    <!-- Auto Populate Helper -->
    <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs">
        <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider mb-2 flex items-center gap-2">
            <i class="fa-solid fa-bolt text-indigo-600"></i> Auto-Generate URLs From Base Portal:
        </h3>
        <div class="flex flex-col sm:flex-row gap-2 max-w-xl">
            <input type="url" id="whmcsBaseUrl" placeholder="https://my.hostnibo.com" class="flex-1 border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
            <button type="button" onclick="autoFillWhmcsUrls()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-4 py-2 rounded-xl text-xs transition shadow-xs cursor-pointer">
                Auto Fill All
            </button>
        </div>
    </div>

    <form method="POST" class="space-y-6">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Domain Search URL -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-2 text-xs">
                <label class="block font-bold text-gray-700">Domain Search / Lookup URL</label>
                <div class="flex gap-2">
                    <input type="url" name="whmcs_domain_search_url" id="u_domainsearch" value="<?php echo htmlspecialchars($s['whmcs_domain_search_url'] ?? ''); ?>" placeholder="https://my.hostnibo.com/domainsearch.php" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    <button type="button" onclick="testUrl('u_domainsearch')" class="px-3 py-2 bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 text-gray-700 rounded-xl border border-gray-200 font-bold transition cursor-pointer" title="Test Link in new tab">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </button>
                </div>
            </div>

            <!-- Client Area URL -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-2 text-xs">
                <label class="block font-bold text-gray-700">Client Portal / Dashboard URL</label>
                <div class="flex gap-2">
                    <input type="url" name="whmcs_client_area_url" id="u_clientarea" value="<?php echo htmlspecialchars($s['whmcs_client_area_url'] ?? ''); ?>" placeholder="https://my.hostnibo.com/clientarea.php" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    <button type="button" onclick="testUrl('u_clientarea')" class="px-3 py-2 bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 text-gray-700 rounded-xl border border-gray-200 font-bold transition cursor-pointer" title="Test Link in new tab">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </button>
                </div>
            </div>

            <!-- Domain Pricing URL -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-2 text-xs">
                <label class="block font-bold text-gray-700">TLD Pricing Table URL</label>
                <div class="flex gap-2">
                    <input type="url" name="whmcs_domain_pricing_url" id="u_pricing" value="<?php echo htmlspecialchars($s['whmcs_domain_pricing_url'] ?? ''); ?>" placeholder="https://my.hostnibo.com/domainpricing.php" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    <button type="button" onclick="testUrl('u_pricing')" class="px-3 py-2 bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 text-gray-700 rounded-xl border border-gray-200 font-bold transition cursor-pointer" title="Test Link in new tab">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </button>
                </div>
            </div>

            <!-- Domain Register Cart URL -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-2 text-xs">
                <label class="block font-bold text-gray-700">Domain Register Cart Action URL</label>
                <div class="flex gap-2">
                    <input type="url" name="whmcs_domain_register_url" id="u_register" value="<?php echo htmlspecialchars($s['whmcs_domain_register_url'] ?? ''); ?>" placeholder="https://my.hostnibo.com/cart.php?a=add&domain=register" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    <button type="button" onclick="testUrl('u_register')" class="px-3 py-2 bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 text-gray-700 rounded-xl border border-gray-200 font-bold transition cursor-pointer" title="Test Link in new tab">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </button>
                </div>
            </div>

            <!-- Domain Transfer Cart URL -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-2 text-xs">
                <label class="block font-bold text-gray-700">Domain Transfer Cart Action URL</label>
                <div class="flex gap-2">
                    <input type="url" name="whmcs_domain_transfer_url" id="u_transfer" value="<?php echo htmlspecialchars($s['whmcs_domain_transfer_url'] ?? ''); ?>" placeholder="https://my.hostnibo.com/cart.php?a=add&domain=transfer" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    <button type="button" onclick="testUrl('u_transfer')" class="px-3 py-2 bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 text-gray-700 rounded-xl border border-gray-200 font-bold transition cursor-pointer" title="Test Link in new tab">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </button>
                </div>
            </div>

            <!-- Affiliate URL -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-2 text-xs">
                <label class="block font-bold text-gray-700">Affiliate Portal URL</label>
                <div class="flex gap-2">
                    <input type="url" name="whmcs_affiliate_url" id="u_affiliates" value="<?php echo htmlspecialchars($s['whmcs_affiliate_url'] ?? ''); ?>" placeholder="https://my.hostnibo.com/affiliates.php" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    <button type="button" onclick="testUrl('u_affiliates')" class="px-3 py-2 bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 text-gray-700 rounded-xl border border-gray-200 font-bold transition cursor-pointer" title="Test Link in new tab">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </button>
                </div>
            </div>

        </div>

        <div class="flex items-center gap-3">
            <button type="submit" name="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-xs transition flex items-center gap-2 text-xs cursor-pointer">
                <i class="fa-solid fa-floppy-disk"></i> Save WHMCS Endpoints
            </button>
        </div>

    </form>

</div>

<script>
function autoFillWhmcsUrls() {
    var base = document.getElementById('whmcsBaseUrl').value.trim().replace(/\/+$/, '');
    if (!base) {
        alert('Please enter your WHMCS base domain URL.');
        return;
    }
    document.getElementById('u_domainsearch').value = base + '/domainsearch.php';
    document.getElementById('u_clientarea').value = base + '/clientarea.php';
    document.getElementById('u_pricing').value = base + '/domainpricing.php';
    document.getElementById('u_register').value = base + '/cart.php?a=add&domain=register';
    document.getElementById('u_transfer').value = base + '/cart.php?a=add&domain=transfer';
    document.getElementById('u_affiliates').value = base + '/affiliates.php';
}

function testUrl(inputId) {
    var val = document.getElementById(inputId).value.trim();
    if (val) {
        window.open(val, '_blank');
    } else {
        alert('Please enter a URL first.');
    }
}
</script>

<?php include 'footer.php'; ?>
