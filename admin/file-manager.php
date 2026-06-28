<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

function buildRefMap() {
    global $conn;
    $map = [];

    $addRef = function($path, $source) use (&$map) {
        $path = trim($path);
        if (!$path) return;
        $path = preg_replace('/^https?:\/\/[^\/]+/', '', $path);
        $path = ltrim($path, '/');
        if (!$path) return;
        if (!isset($map[$path])) $map[$path] = [];
        $map[$path][] = $source;
    };

    $setting_fields = [
        'header_logo' => 'Logo & Branding',
        'footer_logo' => 'Logo & Branding',
        'hero_image' => 'Homepage Editor',
        'bottom_cta_image' => 'Homepage Editor',
        'refund_image' => 'General Settings',
        'favicon' => 'Logo & Branding',
        'og_image' => 'SEO Settings',
        'popup_notice_message' => 'Popup & Social',
    ];
    foreach ($setting_fields as $key => $group) {
        $val = getSetting($key);
        if ($val) {
            $addRef($val, "Setting: $key ($group)");
            preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $val, $m);
            foreach ($m[1] as $src) $addRef($src, "Setting: $key ($group - inline)");
        }
    }

    $features_raw = getSetting('features_data');
    if ($features_raw) {
        foreach (explode("\n", $features_raw) as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 1) $addRef(trim($parts[0]), 'Setting: Features Data');
        }
    }

    $homepage_raw = getSetting('homepage_sections');
    if ($homepage_raw) {
        $decoded = json_decode($homepage_raw, true);
        if (is_array($decoded)) {
            array_walk_recursive($decoded, function($v) use (&$addRef) {
                $v = (string)$v;
                if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg|ico)(\?.*)?$/i', $v))
                    $addRef($v, 'Setting: Homepage Sections');
                preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $v, $m);
                foreach ($m[1] as $src) $addRef($src, 'Setting: Homepage Sections (inline)');
            });
        }
    }

    $direct_tables = [
        ['categories', 'image', 'name'],
        // ['hosting_plans', 'image', 'name'], // table has no image column
        ['offers', 'image', 'title'],
        ['blog_posts', 'image', 'title'],
        ['testimonials', 'photo', 'name'],
        ['partners', 'photo', 'name'],
        ['contacts', 'file', 'name'],
    ];
    foreach ($direct_tables as $t) {
        $label_col = $t[2];
        $r = mysqli_query($conn, "SELECT id, {$t[1]}, $label_col AS label FROM {$t[0]} WHERE {$t[1]} IS NOT NULL AND {$t[1]} != ''");
        if (!$r) continue;
        while ($row = mysqli_fetch_assoc($r)) {
            $addRef($row[$t[1]], "{$t[0]}: {$row['label']}");
        }
    }

    $content_tables = [
        ['pages', 'content', 'title'],
        ['blog_posts', 'content', 'title'],
    ];
    foreach ($content_tables as $t) {
        $label_col = $t[2];
        $r = mysqli_query($conn, "SELECT id, {$t[1]}, $label_col AS label FROM {$t[0]} WHERE {$t[1]} IS NOT NULL AND {$t[1]} != ''");
        if (!$r) continue;
        while ($row = mysqli_fetch_assoc($r)) {
            preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $row[$t[1]], $m);
            foreach ($m[1] as $src) $addRef($src, "{$t[0]}: {$row['label']}");
        }
    }

    return $map;
}

$ref_map = buildRefMap();
$all_refs = array_keys($ref_map);

$scan_dirs = ['../images'];
$all_files = [];
foreach ($scan_dirs as $d) {
    $all_files = array_merge($all_files, scanDirRecursive($d));
}

$upload_dir = '../uploads/content';
if (is_dir($upload_dir)) {
    $all_files = array_merge($all_files, scanDirRecursive($upload_dir));
}

$unused = [];
$file_data = [];
foreach ($all_files as $f) {
    $rel = str_replace('\\', '/', $f);
    $rel = ltrim(preg_replace('/^\.\.\//', '', $rel), '/');
    $fname = pathinfo($rel, PATHINFO_BASENAME);
    $sources = [];
    foreach ($all_refs as $ref_key) {
        if ($ref_key === $rel || $ref_key === $fname || strpos($ref_key, $fname) !== false || strpos($rel, $ref_key) !== false) {
            foreach ($ref_map[$ref_key] as $src) $sources[] = $src;
        }
    }
    $sources = array_unique($sources);
    $dim = @getimagesize($f);
    $file_data[] = [
        'path' => $f,
        'rel' => $rel,
        'name' => basename($f),
        'dir' => dirname($rel),
        'size' => filesize($f),
        'mtime' => filemtime($f),
        'width' => $dim ? $dim[0] : 0,
        'height' => $dim ? $dim[1] : 0,
        'ext' => strtolower(pathinfo($f, PATHINFO_EXTENSION)),
        'used' => !empty($sources),
        'sources' => $sources,
    ];
    if (empty($sources)) $unused[] = $f;
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

$sort_default = $_GET['sort'] ?? 'unused';
$file_json = json_encode($file_data);
?>
<?php include 'header.php'; ?>
<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">File Manager</h1>
        <p class="text-gray-500">Upload, browse, and manage images. Unused files are highlighted for cleanup.</p>
    </div>
    <button onclick="openUploadModal()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"><i class="fa fa-upload mr-1"></i> Upload Images</button>
</div>
<?php if ($msg): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>

<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4 text-center cursor-pointer border-2 <?php echo $sort_default === 'all' ? 'border-blue-500' : 'border-transparent'; ?> hover:border-blue-300 transition" onclick="setFilter('all')">
        <div class="text-2xl font-bold text-gray-700" id="countAll">0</div>
        <div class="text-sm text-gray-500">Total Files</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center cursor-pointer border-2 <?php echo $sort_default === 'used' ? 'border-green-500' : 'border-transparent'; ?> hover:border-green-300 transition" onclick="setFilter('used')">
        <div class="text-2xl font-bold text-green-600" id="countUsed">0</div>
        <div class="text-sm text-gray-500">In Use</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center cursor-pointer border-2 <?php echo $sort_default === 'unused' ? 'border-red-500' : 'border-transparent'; ?> hover:border-red-300 transition" onclick="setFilter('unused')">
        <div class="text-2xl font-bold text-red-600" id="countUnused">0</div>
        <div class="text-sm text-gray-500">Unused</div>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-4 mb-6">
    <div class="flex flex-col md:flex-row gap-4 items-start md:items-center md:justify-between">
        <div class="flex items-center gap-2 flex-1 max-w-md">
            <i class="fa fa-search text-gray-400"></i>
            <input type="text" id="searchInput" placeholder="Search filename..." oninput="renderTable()" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>
        <div class="flex items-center gap-4 flex-wrap">
            <label class="text-sm text-gray-500">Sort:</label>
            <select id="sortSelect" onchange="renderTable()" class="border rounded px-3 py-2 text-sm">
                <option value="size-desc">Size (largest)</option>
                <option value="size-asc">Size (smallest)</option>
                <option value="name-asc">Name (A-Z)</option>
                <option value="name-desc">Name (Z-A)</option>
                <option value="date-desc">Date (newest)</option>
                <option value="date-asc">Date (oldest)</option>
            </select>
            <label class="text-sm text-gray-500">Show:</label>
            <select id="perPageSelect" onchange="renderTable()" class="border rounded px-3 py-2 text-sm">
                <option value="25">25</option>
                <option value="50" selected>50</option>
                <option value="100">100</option>
                <option value="0">All</option>
            </select>
        </div>
    </div>
</div>

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
        <tbody class="divide-y" id="fileTableBody"></tbody>
    </table>
    <div id="emptyState" class="hidden p-12 text-center">
        <i class="fa fa-search text-5xl text-gray-300 mb-4"></i>
        <p class="text-gray-500 text-lg">No files match your filter.</p>
    </div>
</div>

<div class="mt-4 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
    <div class="flex items-center gap-4">
        <button type="submit" name="delete_files" id="deleteBtn" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-sm" onclick="return confirm('Delete the selected files? This cannot be undone.')" disabled>
            <i class="fa fa-trash mr-1"></i> Delete Selected
        </button>
        <span class="text-sm text-gray-500" id="selectedCount">0 selected</span>
    </div>
    <div class="flex items-center gap-1 text-sm" id="pagination"></div>
</div>
</form>

<div id="uploadModal" class="fixed inset-0 z-50 hidden bg-black/50 flex items-center justify-center" onclick="if(event.target===this)closeUploadModal()">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Upload Images</h3>
            <button onclick="closeUploadModal()" type="button" class="text-gray-400 hover:text-gray-600"><i class="fa fa-times text-xl"></i></button>
        </div>
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-400 transition cursor-pointer" id="dropZone" onclick="document.getElementById('fileInput').click()">
            <i class="fa fa-cloud-upload-alt text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 mb-2">Drop images here or click to browse</p>
            <p class="text-xs text-gray-400">JPG, PNG, GIF, WebP, SVG &mdash; Max 5MB each</p>
            <input type="file" id="fileInput" accept="image/*" multiple class="hidden" onchange="handleFiles(this.files)">
        </div>
        <div id="previewGrid" class="mt-4 grid grid-cols-4 gap-2"></div>
        <div id="uploadProgress" class="mt-4 hidden">
            <div class="bg-gray-200 rounded-full h-2"><div id="progressBar" class="bg-blue-600 h-2 rounded-full w-0 transition-all"></div></div>
            <p id="progressText" class="text-sm text-gray-500 mt-1"></p>
        </div>
        <div id="uploadResult" class="mt-4 hidden"></div>
        <div class="mt-4 flex gap-2" id="uploadActions">
            <button onclick="uploadFiles()" id="uploadBtn" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm disabled:opacity-50" disabled><i class="fa fa-upload mr-1"></i> Upload</button>
            <button onclick="closeUploadModal()" type="button" class="px-4 py-2 border rounded text-gray-600 hover:bg-gray-50 text-sm">Cancel</button>
        </div>
    </div>
</div>

<div id="detailModal" class="fixed inset-0 z-50 hidden bg-black/50 flex items-center justify-center" onclick="if(event.target===this)closeDetailModal()">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold" id="detailName">File Details</h3>
            <button onclick="closeDetailModal()" type="button" class="text-gray-400 hover:text-gray-600"><i class="fa fa-times text-xl"></i></button>
        </div>
        <div class="flex flex-col md:flex-row gap-6">
            <div class="flex-shrink-0">
                <img id="detailPreview" src="" alt="" class="w-48 h-48 object-cover rounded border">
            </div>
            <div class="flex-1 space-y-3 text-sm">
                <div><span class="font-medium text-gray-600">Path:</span> <code id="detailPath" class="text-blue-600 break-all"></code></div>
                <div><span class="font-medium text-gray-600">Directory:</span> <span id="detailDir" class="text-gray-700"></span></div>
                <div><span class="font-medium text-gray-600">Size:</span> <span id="detailSize"></span></div>
                <div><span class="font-medium text-gray-600">Dimensions:</span> <span id="detailDim"></span></div>
                <div><span class="font-medium text-gray-600">Modified:</span> <span id="detailModified"></span></div>
                <div><span class="font-medium text-gray-600">Type:</span> <span id="detailType"></span></div>
            </div>
        </div>
        <div class="mt-6" id="detailSourcesContainer">
            <h4 class="font-medium text-gray-700 mb-2">Referenced In:</h4>
            <ul id="detailSources" class="list-disc list-inside text-sm text-gray-600 space-y-1"></ul>
            <p id="detailNoSources" class="text-sm text-gray-400 hidden">This file is not referenced anywhere in the database.</p>
        </div>
        <div class="mt-6 flex gap-2">
            <a id="detailViewLink" href="#" target="_blank" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"><i class="fa fa-external-link mr-1"></i> View File</a>
            <button onclick="closeDetailModal()" type="button" class="px-4 py-2 border rounded text-gray-600 hover:bg-gray-50 text-sm">Close</button>
        </div>
    </div>
</div>

<script>
var fileData = <?php echo $file_json; ?>;
var currentFilter = '<?php echo $sort_default; ?>';
var currentPage = 1;
var selected = {};

function fmtSize(bytes) {
    if (bytes > 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
    return (bytes / 1024).toFixed(1) + ' KB';
}
function fmtDate(ts) {
    var d = new Date(ts * 1000);
    return d.toLocaleDateString('en-US', {month:'short',day:'numeric',year:'numeric',hour:'2-digit',minute:'2-digit'});
}
function esc(s) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(s));
    return d.innerHTML;
}

function getFiltered() {
    var q = (document.getElementById('searchInput').value || '').toLowerCase();
    var arr = fileData.filter(function(f) {
        if (currentFilter === 'used' && !f.used) return false;
        if (currentFilter === 'unused' && f.used) return false;
        if (q && f.name.toLowerCase().indexOf(q) === -1) return false;
        return true;
    });
    var sort = document.getElementById('sortSelect').value;
    var parts = sort.split('-');
    var col = parts[0];
    var dir = parts[1];
    arr.sort(function(a, b) {
        var va, vb;
        if (col === 'name') { va = a.name.toLowerCase(); vb = b.name.toLowerCase(); return dir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va); }
        if (col === 'size') { return dir === 'asc' ? a.size - b.size : b.size - a.size; }
        if (col === 'date') { return dir === 'asc' ? a.mtime - b.mtime : b.mtime - a.mtime; }
        return 0;
    });
    return arr;
}

function renderTable() {
    var arr = getFiltered();
    var perPage = parseInt(document.getElementById('perPageSelect').value) || arr.length;
    var totalPages = Math.max(1, Math.ceil(arr.length / perPage));
    if (currentPage > totalPages) currentPage = totalPages;
    var start = (currentPage - 1) * perPage;
    var page = arr.slice(start, start + perPage);

    document.getElementById('countAll').textContent = fileData.length;
    document.getElementById('countUsed').textContent = fileData.filter(function(f){return f.used;}).length;
    document.getElementById('countUnused').textContent = fileData.filter(function(f){return !f.used;}).length;

    var tbody = document.getElementById('fileTableBody');
    if (page.length === 0) {
        tbody.innerHTML = '';
        document.getElementById('emptyState').classList.remove('hidden');
        document.getElementById('pagination').innerHTML = '';
        return;
    }
    document.getElementById('emptyState').classList.add('hidden');

    var html = '';
    page.forEach(function(f, i) {
        var idx = fileData.indexOf(f);
        var sizeLbl = fmtSize(f.size);
        var dimLbl = (f.width && f.height) ? f.width + 'x' + f.height : '-';
        var dateLbl = fmtDate(f.mtime);
        var preview = (f.ext === 'jpg' || f.ext === 'jpeg' || f.ext === 'png' || f.ext === 'gif' || f.ext === 'webp')
            ? '<img src="../' + esc(f.rel) + '" alt="" class="w-12 h-12 object-cover rounded border" loading="lazy">'
            : '<div class="w-12 h-12 flex items-center justify-center bg-gray-100 rounded border text-gray-400"><i class="fa fa-file-image"></i></div>';
        var statusBadge = f.used
            ? '<span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded font-medium">In Use</span>'
            : '<span class="bg-red-100 text-red-700 text-xs px-2 py-1 rounded font-medium">Unused</span>';
        var rowClass = f.used ? '' : 'bg-red-50';
        var checked = selected[f.path] ? 'checked' : '';
        var disabled = f.used ? 'disabled' : '';

        html += '<tr class="hover:bg-gray-50 ' + rowClass + ' cursor-pointer" onclick="openDetail(' + idx + ')">';
        html += '<td class="px-4 py-3" onclick="event.stopPropagation()"><input type="checkbox" name="delete[]" value="' + esc(f.path) + '" class="file-checkbox" ' + disabled + ' ' + checked + ' onchange="updateCount()"></td>';
        html += '<td class="px-4 py-3">' + preview + '</td>';
        html += '<td class="px-4 py-3"><a href="../' + esc(f.rel) + '" target="_blank" class="text-blue-600 hover:underline break-all" onclick="event.stopPropagation()">' + esc(f.name) + '</a><div class="text-xs text-gray-400">' + esc(f.dir) + '</div></td>';
        html += '<td class="px-4 py-3 whitespace-nowrap">' + sizeLbl + '</td>';
        html += '<td class="px-4 py-3 whitespace-nowrap">' + dimLbl + '</td>';
        html += '<td class="px-4 py-3 whitespace-nowrap">' + dateLbl + '</td>';
        html += '<td class="px-4 py-3 whitespace-nowrap">' + statusBadge + '</td>';
        html += '</tr>';
    });
    tbody.innerHTML = html;

    var pgHtml = '';
    if (totalPages > 1) {
        pgHtml += '<button onclick="gotoPage(' + (currentPage - 1) + ')" class="px-3 py-1 border rounded hover:bg-gray-50 ' + (currentPage <= 1 ? 'opacity-30 cursor-not-allowed' : '') + '" ' + (currentPage <= 1 ? 'disabled' : '') + '><i class="fa fa-chevron-left"></i></button>';
        for (var p = 1; p <= totalPages; p++) {
            if (p === 1 || p === totalPages || (p >= currentPage - 2 && p <= currentPage + 2)) {
                pgHtml += '<button onclick="gotoPage(' + p + ')" class="px-3 py-1 border rounded ' + (p === currentPage ? 'bg-blue-600 text-white border-blue-600' : 'hover:bg-gray-50') + '">' + p + '</button>';
            } else if (p === currentPage - 3 || p === currentPage + 3) {
                pgHtml += '<span class="px-2 text-gray-400">...</span>';
            }
        }
        pgHtml += '<button onclick="gotoPage(' + (currentPage + 1) + ')" class="px-3 py-1 border rounded hover:bg-gray-50 ' + (currentPage >= totalPages ? 'opacity-30 cursor-not-allowed' : '') + '" ' + (currentPage >= totalPages ? 'disabled' : '') + '><i class="fa fa-chevron-right"></i></button>';
    }
    document.getElementById('pagination').innerHTML = pgHtml;
    updateCount();
}

function gotoPage(n) {
    var arr = getFiltered();
    var perPage = parseInt(document.getElementById('perPageSelect').value) || arr.length;
    var totalPages = Math.max(1, Math.ceil(arr.length / perPage));
    if (n < 1 || n > totalPages) return;
    currentPage = n;
    renderTable();
}

function setFilter(f) {
    currentFilter = f;
    currentPage = 1;
    renderTable();
    document.querySelectorAll('.grid-cols-3 .bg-white').forEach(function(el, i) {
        el.classList.remove('border-blue-500', 'border-green-500', 'border-red-500');
        if (i === 0 && f === 'all') el.classList.add('border-blue-500');
        else if (i === 1 && f === 'used') el.classList.add('border-green-500');
        else if (i === 2 && f === 'unused') el.classList.add('border-red-500');
    });
}

function toggleAll() {
    var checked = document.getElementById('selectAll').checked;
    document.querySelectorAll('.file-checkbox:not(:disabled)').forEach(function(cb) {
        cb.checked = checked;
        selected[cb.value] = checked;
    });
    updateCount();
}

function updateCount() {
    document.querySelectorAll('.file-checkbox').forEach(function(cb) {
        selected[cb.value] = cb.checked;
    });
    var n = Object.keys(selected).filter(function(k) { return selected[k]; }).length;
    document.getElementById('selectedCount').textContent = n + ' selected';
    document.getElementById('deleteBtn').disabled = (n === 0);
}

function openDetail(idx) {
    var f = fileData[idx];
    if (!f) return;
    document.getElementById('detailName').textContent = f.name;
    document.getElementById('detailPreview').src = '../' + f.rel;
    document.getElementById('detailPath').textContent = f.rel;
    document.getElementById('detailDir').textContent = f.dir;
    document.getElementById('detailSize').textContent = fmtSize(f.size);
    document.getElementById('detailDim').textContent = (f.width && f.height) ? f.width + 'x' + f.height : '-';
    document.getElementById('detailModified').textContent = fmtDate(f.mtime);
    document.getElementById('detailType').textContent = f.ext.toUpperCase();
    document.getElementById('detailViewLink').href = '../' + f.rel;

    var list = document.getElementById('detailSources');
    var container = document.getElementById('detailSourcesContainer');
    var noSrc = document.getElementById('detailNoSources');
    if (f.sources && f.sources.length > 0) {
        list.innerHTML = f.sources.map(function(s) { return '<li>' + esc(s) + '</li>'; }).join('');
        list.classList.remove('hidden');
        noSrc.classList.add('hidden');
    } else {
        list.classList.add('hidden');
        noSrc.classList.remove('hidden');
    }
    document.getElementById('detailModal').classList.remove('hidden');
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.add('hidden');
}

var uploadFilesList = [];

function openUploadModal() {
    uploadFilesList = [];
    document.getElementById('previewGrid').innerHTML = '';
    document.getElementById('uploadResult').innerHTML = '';
    document.getElementById('uploadResult').classList.add('hidden');
    document.getElementById('uploadProgress').classList.add('hidden');
    document.getElementById('uploadActions').classList.remove('hidden');
    document.getElementById('uploadBtn').disabled = true;
    document.getElementById('fileInput').value = '';
    document.getElementById('uploadModal').classList.remove('hidden');
}

function closeUploadModal() {
    document.getElementById('uploadModal').classList.add('hidden');
}

function handleFiles(files) {
    uploadFilesList = Array.from(files);
    var grid = document.getElementById('previewGrid');
    grid.innerHTML = '';
    uploadFilesList.forEach(function(f) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var div = document.createElement('div');
            div.className = 'relative';
            div.innerHTML = '<img src="' + e.target.result + '" class="w-full h-16 object-cover rounded border"><div class="text-xs text-gray-500 truncate mt-1">' + f.name + '</div>';
            grid.appendChild(div);
        };
        reader.readAsDataURL(f);
    });
    document.getElementById('uploadBtn').disabled = uploadFilesList.length === 0;
}

function uploadFiles() {
    if (uploadFilesList.length === 0) return;
    var btn = document.getElementById('uploadBtn');
    btn.disabled = true;
    var progress = document.getElementById('uploadProgress');
    progress.classList.remove('hidden');
    var bar = document.getElementById('progressBar');
    var text = document.getElementById('progressText');
    var result = document.getElementById('uploadResult');
    result.classList.add('hidden');

    var total = uploadFilesList.length;
    var done = 0;
    var errors = [];

    function uploadNext(i) {
        if (i >= total) {
            var msg = done + ' file(s) uploaded successfully.';
            if (errors.length) msg += ' ' + errors.length + ' error(s).';
            result.innerHTML = '<div class="bg-green-100 text-green-700 px-4 py-3 rounded text-sm"><i class="fa fa-check-circle mr-1"></i> ' + msg + '</div>';
            result.classList.remove('hidden');
            progress.classList.add('hidden');
            document.getElementById('uploadActions').classList.add('hidden');
            var reloadBtn = document.createElement('button');
            reloadBtn.className = 'mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm';
            reloadBtn.innerHTML = '<i class="fa fa-refresh mr-1"></i> Refresh Page';
            reloadBtn.onclick = function() { location.reload(); };
            result.appendChild(reloadBtn);
            return;
        }

        var fd = new FormData();
        fd.append('file', uploadFilesList[i]);
        text.textContent = 'Uploading ' + (i + 1) + ' of ' + total + ': ' + uploadFilesList[i].name;

        fetch('upload.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.location) done++; else errors.push(uploadFilesList[i].name);
            })
            .catch(function() { errors.push(uploadFilesList[i].name); })
            .then(function() {
                bar.style.width = ((i + 1) / total * 100) + '%';
                uploadNext(i + 1);
            });
    }
    uploadNext(0);
}

renderTable();
</script>
<?php include 'footer.php'; ?>
