<?php
$page_title = 'Security & Admin URL Settings';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminRole(['admin']);
ensure2FASchema();

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_access_slug = sanitize(trim($_POST['admin_access_slug'] ?? ''));
    $admin_access_slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $admin_access_slug);

    // Update admin_access_slug
    $check = mysqli_query($conn, "SELECT id FROM settings WHERE setting_key = 'admin_access_slug'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE settings SET setting_value = '$admin_access_slug' WHERE setting_key = 'admin_access_slug'");
    } else {
        mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('admin_access_slug', '$admin_access_slug')");
    }

    logActivity('Updated Security Settings', 'Admin Access Slug: ' . ($admin_access_slug ?: 'Disabled'));
    header('Location: settings-security.php?s=1');
    exit;
}

if (isset($_GET['s'])) {
    $msg = 'Security settings updated successfully!';
}

$settings_result = mysqli_query($conn, "SELECT * FROM settings ORDER BY setting_key");
$s = []; while ($row = mysqli_fetch_assoc($settings_result)) { $s[$row['setting_key']] = $row['setting_value']; }

$current_slug = $s['admin_access_slug'] ?? '';
$site_url = getSiteUrl();

// Count 2FA active users
$users_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total, SUM(CASE WHEN two_factor_enabled = 1 THEN 1 ELSE 0 END) as with_2fa FROM users"));
?>
<?php include 'header.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Security & Custom Admin URL</h1>
    <p class="text-gray-500">Configure custom admin login path, SEO privacy, and authentication security</p>
</div>

<?php if ($msg): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Custom Admin URL Card -->
    <div class="lg:col-span-2 space-y-6">
        <form method="POST" class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-2 flex items-center gap-2">
                <i class="fa fa-link text-blue-600"></i> Custom Admin URL / Secret Access Slug
            </h2>
            <p class="text-sm text-gray-600 mb-6">
                Protect your admin login from automated brute-force bots and scanners by requiring a secret access keyword. Anyone attempting to visit <code>/admin/</code> without this key will receive a <strong>404 Page Not Found</strong>.
            </p>

            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Secret Access Slug</label>
                    <div class="flex items-center">
                        <span class="bg-gray-100 border border-r-0 border-gray-300 rounded-l px-3 py-2 text-gray-500 text-sm font-mono">
                            <?php echo htmlspecialchars($site_url); ?>admin/?access=
                        </span>
                        <input type="text" name="admin_access_slug" value="<?php echo htmlspecialchars($current_slug); ?>" placeholder="e.g. my-secret-portal" class="flex-1 border border-gray-300 rounded-r px-3 py-2 text-sm font-mono focus:border-blue-600 focus:outline-none">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Leave empty to allow direct access to <code>/admin/</code> without secret key.</p>
                </div>

                <?php if ($current_slug): ?>
                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <span class="text-xs font-bold text-blue-900 uppercase block mb-1">Your Secret Admin Login URL:</span>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly id="adminLoginUrl" value="<?php echo htmlspecialchars($site_url . 'admin/?access=' . $current_slug); ?>" class="w-full bg-white border rounded px-3 py-1.5 text-xs font-mono text-blue-800 font-bold select-all">
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('adminLoginUrl').value); alert('Copied to clipboard!');" class="bg-blue-600 text-white px-3 py-1.5 rounded text-xs whitespace-nowrap hover:bg-blue-700">
                            <i class="fa fa-copy"></i> Copy
                        </button>
                    </div>
                    <p class="text-[11px] text-blue-700 mt-2">
                        <strong>Important:</strong> Bookmark this secret URL. If you log out, you must use this URL to access the admin login page again.
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2.5 rounded-lg shadow-sm transition">
                <i class="fa fa-save mr-1"></i> Save Security Settings
            </button>
        </form>

        <!-- SEO Protection Summary -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-2 flex items-center gap-2">
                <i class="fa fa-eye-slash text-emerald-600"></i> Search Engine Exclusion & Privacy
            </h2>
            <p class="text-sm text-gray-600 mb-4">
                The admin panel is completely shielded from Google, Bing, Yahoo, and search engine crawlers:
            </p>
            <ul class="space-y-2 text-sm text-gray-700">
                <li class="flex items-center gap-2">
                    <i class="fa fa-check text-green-500"></i>
                    <span><strong>Robots.txt</strong> explicitly blocks all bots from crawling <code>/admin/</code> and internal directories.</span>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa fa-check text-green-500"></i>
                    <span><strong>Robots Meta Tag</strong> (<code>noindex, nofollow, noarchive</code>) is active on every admin template.</span>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa fa-check text-green-500"></i>
                    <span><strong>HTTP X-Robots-Tag</strong> header ensures API and direct asset responses remain non-indexable.</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- 2FA & Auth Overview Sidebar -->
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-3 flex items-center gap-2">
                <i class="fa fa-shield-halved text-purple-600"></i> 2FA Authentication
            </h2>
            <div class="space-y-3 mb-6">
                <div class="flex justify-between items-center text-sm border-b pb-2">
                    <span class="text-gray-600">Total Admin Accounts:</span>
                    <span class="font-bold text-gray-900"><?php echo (int)($users_count['total'] ?? 0); ?></span>
                </div>
                <div class="flex justify-between items-center text-sm border-b pb-2">
                    <span class="text-gray-600">Accounts with 2FA Active:</span>
                    <span class="font-bold text-purple-600"><?php echo (int)($users_count['with_2fa'] ?? 0); ?></span>
                </div>
            </div>
            <a href="profile.php" class="block w-full text-center bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2.5 px-4 rounded-lg shadow-sm transition">
                <i class="fa fa-user-shield mr-1"></i> Manage My 2FA Settings
            </a>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
