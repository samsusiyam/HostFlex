<?php
$page_title = 'System Update';
require_once '../config/database.php';
require_once '../config/version.php';
require_once '../includes/functions.php';
checkAdminLogin();

$msg = '';
$error = '';

// Check for update
$latest = null;
$repo = GITHUB_REPO;
if ($repo) {
    $url = "https://api.github.com/repos/$repo/releases/latest";
    $ctx = stream_context_create(['http' => ['header' => "User-Agent: " . APP_NAME . "\r\n", 'timeout' => 10]]);
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp) {
        $data = json_decode($resp, true);
        if ($data && isset($data['tag_name'])) {
            $latest = [
                'version' => ltrim($data['tag_name'], 'v'),
                'zip_url' => $data['zipball_url'],
                'published' => $data['published_at'] ?? '',
                'notes' => $data['body'] ?? ''
            ];
        }
    }
}

$update_available = $latest && version_compare($latest['version'], APP_VERSION, '>');

// Perform update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_update']) && $latest) {
    ignore_user_abort(true);
    set_time_limit(300);

    $tmp_zip = sys_get_temp_dir() . '/' . APP_NAME . '_update.zip';
    $tmp_dir = sys_get_temp_dir() . '/' . APP_NAME . '_extract';

    // Download
    $zip_data = @file_get_contents($latest['zip_url'], false, $ctx);
    if (!$zip_data) {
        $error = 'Failed to download update package.';
    } else {
        file_put_contents($tmp_zip, $zip_data);

        // Extract
        $zip = new ZipArchive;
        if ($zip->open($tmp_zip) !== TRUE) {
            $error = 'Failed to open update package.';
        } else {
            // Clean tmp dir
            if (is_dir($tmp_dir)) {
                array_map('unlink', glob("$tmp_dir/*.*"));
                rmdir($tmp_dir);
            }
            $zip->extractTo($tmp_dir);
            $zip->close();

            // Find the root (first subdir inside zip)
            $items = scandir($tmp_dir);
            $root = $tmp_dir;
            foreach ($items as $item) {
                if ($item !== '.' && $item !== '..' && is_dir("$tmp_dir/$item")) {
                    $root = "$tmp_dir/$item";
                    break;
                }
            }

            // Copy files, preserving config/database.php and uploads/
            $protected = ['config/database.php', 'uploads'];
            $copied = 0;
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                $rel = substr($file->getPathname(), strlen($root) + 1);
                $rel = str_replace('\\', '/', $rel);

                // Skip protected
                $skip = false;
                foreach ($protected as $p) {
                    if (strpos($rel, $p) === 0) { $skip = true; break; }
                }
                if ($skip) continue;

                $target = dirname(__DIR__) . '/' . $rel;
                $target_dir = dirname($target);
                if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
                copy($file, $target);
                $copied++;
            }

            // Run pending migrations
            $migrate_file = dirname(__DIR__) . '/config/migrate-system.php';
            if (file_exists($migrate_file)) {
                ob_start();
                include $migrate_file;
                ob_end_clean();
            }

            // Update version file
            $new_ver = $latest['version'];
            $ver_content = "<?php\ndefine('APP_VERSION', '$new_ver');\ndefine('APP_VERSION_DATE', '" . date('Y-m-d') . "');\ndefine('APP_NAME', '" . APP_NAME . "');\ndefine('GITHUB_REPO', '$repo');\n";
            file_put_contents(dirname(__DIR__) . '/config/version.php', $ver_content);

            // Cleanup
            unlink($tmp_zip);
            array_map('unlink', glob("$tmp_dir/*.*"));
            rmdir($tmp_dir);

            $msg = "Update complete! Updated to v{$new_ver} ($copied files updated).";
            // Refresh
            $update_available = false;
            $latest = null;
        }
    }
}
?>
<?php include 'header.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800"><i class="fa fa-sync-alt mr-2"></i> System Update</h1>
    <p class="text-gray-500">Check for updates and update your system</p>
</div>

<?php if ($msg): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Current Installation</h2>
        <div class="space-y-3">
            <div class="flex justify-between py-2 border-b"><span class="text-gray-600">Application</span><span class="font-medium"><?php echo APP_NAME; ?></span></div>
            <div class="flex justify-between py-2 border-b"><span class="text-gray-600">Current Version</span><span class="font-medium text-blue-600">v<?php echo APP_VERSION; ?></span></div>
            <div class="flex justify-between py-2 border-b"><span class="text-gray-600">Release Date</span><span class="font-medium"><?php echo APP_VERSION_DATE; ?></span></div>
            <div class="flex justify-between py-2 border-b"><span class="text-gray-600">GitHub Repo</span><span class="font-medium"><?php echo $repo ?: '<span class="text-red-500">Not configured</span>'; ?></span></div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Update Status</h2>
        <?php if (!$repo): ?>
            <div class="text-center py-8">
                <i class="fa fa-exclamation-triangle text-4xl text-yellow-500 mb-3"></i>
                <p class="text-gray-600">GitHub repository not configured.</p>
                <p class="text-sm text-gray-400 mt-1">Set <code>GITHUB_REPO</code> in <code>config/version.php</code></p>
            </div>
        <?php elseif ($latest === null): ?>
            <div class="text-center py-8">
                <i class="fa fa-times-circle text-4xl text-red-400 mb-3"></i>
                <p class="text-gray-600">Could not check for updates.</p>
                <p class="text-sm text-gray-400 mt-1">Make sure your GitHub repo has releases.</p>
            </div>
        <?php elseif ($update_available): ?>
            <div class="text-center py-4">
                <i class="fa fa-arrow-circle-up text-5xl text-green-500 mb-3"></i>
                <h3 class="text-xl font-bold text-green-600">v<?php echo $latest['version']; ?> Available!</h3>
                <p class="text-sm text-gray-500 mt-1">Released: <?php echo date('d M Y', strtotime($latest['published'])); ?></p>
                <?php if ($latest['notes']): ?>
                <div class="mt-4 text-left bg-gray-50 rounded p-3 text-sm max-h-32 overflow-y-auto"><?php echo nl2br(htmlspecialchars($latest['notes'])); ?></div>
                <?php endif; ?>
                <form method="POST" class="mt-4" onsubmit="return confirm('Update to v<?php echo $latest['version']; ?>? This will overwrite system files. Your database and uploads will be preserved.')">
                    <button type="submit" name="do_update" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 shadow"><i class="fa fa-download mr-2"></i> Update to v<?php echo $latest['version']; ?></button>
                </form>
            </div>
        <?php else: ?>
            <div class="text-center py-8">
                <i class="fa fa-check-circle text-5xl text-green-500 mb-3"></i>
                <h3 class="text-xl font-bold text-gray-800">You're up to date!</h3>
                <p class="text-gray-500 mt-1">v<?php echo APP_VERSION; ?> is the latest version.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
