<?php
$page_title = 'File Manager';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();
checkPermission('file_manager', 'view');

$msg = '';
$error = '';

function scanDirRecursive($dir) {
    $files = [];
    if (!is_dir($dir)) return $files;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            $files = array_merge($files, scanDirRecursive($path));
        } else {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg','ico'])) {
                $files[] = $path;
            }
        }
    }
    return $files;
}

function getAllDbReferences() {
    global $conn;
    $refs = [];

    $settings_keys = ['header_logo','footer_logo','hero_image','bottom_cta_image','refund_image','favicon','og_image','popup_notice_message'];
    foreach ($settings_keys as $key) {
        $val = getSetting($key);
        if ($val) {
            $refs[] = $val;
            preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $val, $m);
            foreach ($m[1] as $src) $refs[] = $src;
        }
    }

    $features_raw = getSetting('features_data');
    if ($features_raw) {
        foreach (explode("\n", $features_raw) as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 1) $refs[] = trim($parts[0]);
        }
    }

    $homepage_raw = getSetting('homepage_sections');
    if ($homepage_raw) {
        $decoded = json_decode($homepage_raw, true);
        if (is_array($decoded)) {
            array_walk_recursive($decoded, function($v) use (&$refs) {
                $v = (string)$v;
                if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg|ico)(\?.*)?$/i', $v)) $refs[] = $v;
                preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $v, $m);
                foreach ($m[1] as $src) $refs[] = $src;
            });
        }
    }

    $tables = [
        ['categories', 'image'],
        ['hosting_plans', 'image'],
        ['offers', 'image'],
        ['blog_posts', 'image'],
        ['testimonials', 'photo'],
        ['partners', 'photo'],
        ['contacts', 'file'],
    ];
    foreach ($tables as $t) {
        $r = mysqli_query($conn, "SELECT {$t[1]} FROM {$t[0]} WHERE {$t[1]} IS NOT NULL AND {$t[1]} != ''");
        while ($row = mysqli_fetch_assoc($r)) $refs[] = $row[$t[1]];
    }

    $content_tables = [
        ['pages', 'content'],
        ['blog_posts', 'content'],
    ];
    foreach ($content_tables as $t) {
        $r = mysqli_query($conn, "SELECT {$t[1]} FROM {$t[0]} WHERE {$t[1]} IS NOT NULL AND {$t[1]} != ''");
        while ($row = mysqli_fetch_assoc($r)) {
            preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $row[$t[1]], $m);
            foreach ($m[1] as $src) $refs[] = $src;
        }
    }

    $normalized = [];
    foreach ($refs as $r) {
        $r = trim($r);
        $r = preg_replace('/^https?:\/\/[^\/]+/', '', $r);
        $r = ltrim($r, '/');
        if ($r) $normalized[] = $r;
    }

    return array_unique($normalized);
}

$scan_dirs = ['../images'];
$all_files = [];
foreach ($scan_dirs as $d) {
    $all_files = array_merge($all_files, scanDirRecursive($d));
}

$upload_dir = '../uploads/content';
if (is_dir($upload_dir)) {
    $all_files = array_merge($all_files, scanDirRecursive($upload_dir));
}

$db_refs = getAllDbReferences();

$used = [];
$unused = [];
foreach ($all_files as $f) {
    $rel = str_replace('\\', '/', $f);
    $rel = ltrim(preg_replace('/^\.\.\//', '', $rel), '/');
    $fname = pathinfo($rel, PATHINFO_BASENAME);
    $found = false;
    foreach ($db_refs as $ref) {
        if ($ref === $rel || $ref === $fname || strpos($ref, $fname) !== false || strpos($rel, $ref) !== false) {
            $found = true;
            break;
        }
    }
    if ($found) {
        $used[] = $f;
    } else {
        $unused[] = $f;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_files'])) {
    checkPermission('file_manager', 'delete');
    validateCSRFToken($_POST['csrf_token'] ?? '');
    $to_delete = $_POST['delete'] ?? [];
    $deleted = 0;
    $images_real = realpath('../images');
    $upload_real = is_dir($upload_dir) ? realpath($upload_dir) : null;
    foreach ($to_delete as $fp) {
        $fp = realpath($fp);
        if (!$fp) continue;
        $allowed = ($images_real && str_starts_with($fp, $images_real)) || ($upload_real && str_starts_with($fp, $upload_real));
        if ($allowed && unlink($fp)) {
            $deleted++;
            logActivity('File Deleted', basename($fp));
        }
    }
    $msg = "$deleted file(s) deleted successfully!";
    $redirect = 'file-manager.php?s=1';
    header("Location: $redirect");
    exit;
}

if (isset($_GET['s'])) $msg = 'File(s) deleted successfully!';

$sort = $_GET['sort'] ?? 'unused';
?>
<?php include 'header.php'; ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">File Manager</h1>
        <p class="text-gray-500">Scan and manage image files. Unused files are highlighted.</p>
    </div>
    <div class="flex gap-2">
        <a href="?sort=all" class="px-4 py-2 rounded <?php echo $sort === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border'; ?>">All (<?php echo count($all_files); ?>)</a>
        <a href="?sort=unused" class="px-4 py-2 rounded <?php echo $sort === 'unused' ? 'bg-red-600 text-white' : 'bg-white text-gray-700 border'; ?>">Unused (<?php echo count($unused); ?>)</a>
        <a href="?sort=used" class="px-4 py-2 rounded <?php echo $sort === 'used' ? 'bg-green-600 text-white' : 'bg-white text-gray-700 border'; ?>">Used (<?php echo count($used); ?>)</a>
    </div>
</div>
<?php if ($msg): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>

<?php if ($sort === 'unused' && empty($unused)): ?>
<div class="bg-white rounded-lg shadow p-12 text-center">
    <i class="fa fa-check-circle text-6xl text-green-500 mb-4"></i>
    <h2 class="text-xl font-semibold text-gray-700">No Unused Files</h2>
    <p class="text-gray-500 mt-2">All image files in your project are being used.</p>
</div>
<?php elseif ($sort === 'used' && empty($used)): ?>
<div class="bg-white rounded-lg shadow p-12 text-center">
    <p class="text-gray-500">No referenced files found.</p>
</div>
<?php else: ?>

<?php
$display_files = [];
if ($sort === 'unused') $display_files = $unused;
elseif ($sort === 'used') $display_files = $used;
else $display_files = $all_files;

usort($display_files, function($a, $b) {
    return filesize($b) - filesize($a);
});
?>

<form method="post" id="fileForm">
<?= csrfField() ?>
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gray-50 border-b text-left">
                <th class="px-4 py-3 w-10"><input type="checkbox" id="selectAll" onchange="toggleAll()"></th>
                <th class="px-4 py-3 w-16">Preview</th>
                <th class="px-4 py-3">File</th>
                <th class="px-4 py-3">Size</th>
                <th class="px-4 py-3">Dimensions</th>
                <th class="px-4 py-3">Modified</th>
                <th class="px-4 py-3">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php foreach ($display_files as $f):
                $rel = str_replace('\\', '/', $f);
                $rel = ltrim(preg_replace('/^\.\.\//', '', $rel), '/');
                $size = filesize($f);
                $size_label = $size > 1048576 ? round($size/1048576, 1).' MB' : round($size/1024, 1).' KB';
                $mtime = date('M d, Y H:i', filemtime($f));
                $dim = @getimagesize($f);
                $dim_label = $dim ? $dim[0].'x'.$dim[1] : '-';
                $is_unused = in_array($f, $unused);
                $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            ?>
            <tr class="hover:bg-gray-50 <?php echo $is_unused ? 'bg-red-50' : ''; ?>">
                <td class="px-4 py-3"><input type="checkbox" name="delete[]" value="<?php echo htmlspecialchars(realpath($f)); ?>" class="file-checkbox" <?php echo !$is_unused ? 'disabled' : ''; ?>></td>
                <td class="px-4 py-3">
                    <?php if (in_array($ext, ['jpg','jpeg','png','gif','webp'])): ?>
                    <img src="../<?php echo htmlspecialchars($rel); ?>" alt="" class="w-12 h-12 object-cover rounded border" loading="lazy">
                    <?php else: ?>
                    <div class="w-12 h-12 flex items-center justify-center bg-gray-100 rounded border text-gray-400"><i class="fa fa-file-image"></i></div>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3">
                    <a href="../<?php echo htmlspecialchars($rel); ?>" target="_blank" class="text-blue-600 hover:underline break-all"><?php echo htmlspecialchars(basename($f)); ?></a>
                    <div class="text-xs text-gray-400"><?php echo htmlspecialchars(dirname($rel)); ?></div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap"><?php echo $size_label; ?></td>
                <td class="px-4 py-3 whitespace-nowrap"><?php echo $dim_label; ?></td>
                <td class="px-4 py-3 whitespace-nowrap"><?php echo $mtime; ?></td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <?php if ($is_unused): ?>
                    <span class="bg-red-100 text-red-700 text-xs px-2 py-1 rounded font-medium">Unused</span>
                    <?php else: ?>
                    <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded font-medium">In Use</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (!empty($unused)): ?>
<div class="mt-4 flex items-center gap-4">
    <button type="submit" name="delete_files" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700" onclick="return confirm('Are you sure you want to delete the selected unused files? This cannot be undone.')">
        <i class="fa fa-trash mr-1"></i> Delete Selected
    </button>
    <span class="text-sm text-gray-500" id="selectedCount">0 selected</span>
</div>
<?php endif; ?>
</form>

<script>
function toggleAll() {
    const checked = document.getElementById('selectAll').checked;
    document.querySelectorAll('.file-checkbox:not(:disabled)').forEach(cb => cb.checked = checked);
    updateCount();
}
document.querySelectorAll('.file-checkbox').forEach(cb => cb.addEventListener('change', updateCount));
function updateCount() {
    const n = document.querySelectorAll('.file-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = n + ' selected';
}
</script>

<?php endif; ?>
<?php include 'footer.php'; ?>
