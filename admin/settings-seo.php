<?php
$page_title = 'SEO Settings';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();
checkPermission('settings', 'edit');

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
    header('Location: settings-seo.php?s=1');
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
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">SEO Settings</h1>
    <p class="text-gray-500 dark:text-gray-400">Meta tags for search engines</p>
</div>
<?php if (isset($success)): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 dark:bg-green-900/30 dark:border-green-700 dark:text-green-300"><?php echo $success; ?></div><?php endif; ?>
<form method="POST">
    <?= csrfField() ?>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4 flex items-center gap-2"><i class="fa fa-home text-blue-600 dark:text-blue-400"></i> Homepage SEO</h2>
        <div class="grid grid-cols-1 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Homepage Title</label><input type="text" name="homepage_title" value="<?php echo htmlspecialchars($s['homepage_title'] ?? ''); ?>" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" placeholder="Best Web Hosting in Bangladesh - HostNibo"></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Homepage Meta Description</label><textarea name="homepage_meta_description" rows="3" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" placeholder="HostNibo offers affordable, fast and reliable web hosting..."><?php echo htmlspecialchars($s['homepage_meta_description'] ?? ''); ?></textarea></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Homepage Meta Keywords</label><input type="text" name="homepage_meta_keywords" value="<?php echo htmlspecialchars($s['homepage_meta_keywords'] ?? ''); ?>" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" placeholder="web hosting, domain, VPS, WordPress"></div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4 flex items-center gap-2"><i class="fa fa-globe text-green-600 dark:text-green-400"></i> Global SEO</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Meta Keywords</label><input type="text" name="meta_keywords" value="<?php echo htmlspecialchars($s['meta_keywords'] ?? ''); ?>" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" placeholder="hosting, domain, web hosting"></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Meta Author</label><input type="text" name="meta_author" value="<?php echo htmlspecialchars($s['meta_author'] ?? ''); ?>" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" placeholder="<?php echo escSetting('site_name'); ?>"></div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4 flex items-center gap-2"><i class="fa fa-tags text-orange-600 dark:text-orange-400"></i> Offers Page SEO</h2>
        <div class="grid grid-cols-1 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Offers Page Title</label><input type="text" name="offers_page_title" value="<?php echo htmlspecialchars($s['offers_page_title'] ?? ''); ?>" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" placeholder="Hosting Offers & Deals - HostNibo Exclusive Discounts"></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Offers Meta Description</label><textarea name="offers_meta_description" rows="3" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" placeholder="Check out our latest hosting offers and deals..."><?php echo htmlspecialchars($s['offers_meta_description'] ?? ''); ?></textarea></div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4 flex items-center gap-2"><i class="fa fa-newspaper text-blue-600 dark:text-blue-400"></i> Blog SEO</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Individual blog post SEO can be set when creating/editing each post.</p>
        <div class="grid grid-cols-1 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Blog Listing Title</label><input type="text" name="blog_listing_title" value="<?php echo htmlspecialchars($s['blog_listing_title'] ?? ''); ?>" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" placeholder="Web Hosting Blog, Tips & Tutorials - HostNibo"></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Blog Listing Meta Description</label><textarea name="blog_listing_meta_description" rows="3" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" placeholder="Read our latest blog posts about web hosting, technology..."><?php echo htmlspecialchars($s['blog_listing_meta_description'] ?? ''); ?></textarea></div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4 flex items-center gap-2"><i class="fa fa-info-circle text-purple-600 dark:text-purple-400"></i> SEO Tips</h2>
        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
            <li><strong>Title:</strong> 30-60 characters ideal. Include main keyword.</li>
            <li><strong>Meta Description:</strong> 120-160 characters. Summarize the page.</li>
            <li><strong>H1 Tag:</strong> One main heading per page (e.g., "Contact Us"). Search engines use it to understand the page topic.</li>
            <li><strong>Keywords:</strong> Comma-separated. Less important for modern SEO but still used by some engines.</li>
        </ul>
    </div>

    <button type="submit" name="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 dark:hover:bg-blue-600"><i class="fa fa-save"></i> Save Settings</button>
</form>
<?php include 'footer.php'; ?>
