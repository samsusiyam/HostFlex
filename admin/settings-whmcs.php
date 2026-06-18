<?php
$page_title = 'WHMCS Integration';
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
    header('Location: settings-whmcs.php?s=1');
    exit;
}
if (isset($_GET['s'])) {
    $success = 'Settings updated!';
}
$settings_result = mysqli_query($conn, "SELECT * FROM settings ORDER BY setting_key");
$s = []; while ($row = mysqli_fetch_assoc($settings_result)) { $s[$row['setting_key']] = $row['setting_value']; }
?>
<?php include 'header.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">WHMCS Integration</h1>
    <p class="text-gray-500">WHMCS URL configuration for domain & client areas</p>
</div>
<?php if (isset($success)): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $success; ?></div><?php endif; ?>
<form method="POST">
    <?= csrfField() ?>
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Domain Search URL</label><input type="text" name="whmcs_domain_search_url" value="<?php echo htmlspecialchars($s['whmcs_domain_search_url'] ?? ''); ?>" class="w-full border rounded px-3 py-2" placeholder="https://billing.yourdomain.com/domainchecker.php"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Client Area URL</label><input type="text" name="whmcs_client_area_url" value="<?php echo htmlspecialchars($s['whmcs_client_area_url'] ?? ''); ?>" class="w-full border rounded px-3 py-2" placeholder="https://billing.yourdomain.com"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Domain Pricing URL</label><input type="text" name="whmcs_domain_pricing_url" value="<?php echo htmlspecialchars($s['whmcs_domain_pricing_url'] ?? ''); ?>" class="w-full border rounded px-3 py-2" placeholder="https://billing.yourdomain.com/domain/pricing"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Domain Register URL</label><input type="text" name="whmcs_domain_register_url" value="<?php echo htmlspecialchars($s['whmcs_domain_register_url'] ?? ''); ?>" class="w-full border rounded px-3 py-2" placeholder="https://billing.yourdomain.com/cart.php?a=add&domain=register"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Domain Transfer URL</label><input type="text" name="whmcs_domain_transfer_url" value="<?php echo htmlspecialchars($s['whmcs_domain_transfer_url'] ?? ''); ?>" class="w-full border rounded px-3 py-2" placeholder="https://billing.yourdomain.com/cart.php?a=add&domain=transfer"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Affiliate URL</label><input type="text" name="whmcs_affiliate_url" value="<?php echo htmlspecialchars($s['whmcs_affiliate_url'] ?? ''); ?>" class="w-full border rounded px-3 py-2" placeholder="https://billing.yourdomain.com/affiliates.php"></div>
        </div>
    </div>
    <button type="submit" name="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700"><i class="fa fa-save"></i> Save Settings</button>
</form>
<?php include 'footer.php'; ?>
