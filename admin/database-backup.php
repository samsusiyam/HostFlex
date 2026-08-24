<?php
$page_title = 'Database Backup & Restore';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminRole(['admin']);

$msg = '';
$error = '';

$backup_dir = '../backups/';
if (!is_dir($backup_dir)) @mkdir($backup_dir, 0755, true);

// Handle Direct Download of Live Database
if (isset($_GET['download']) && $_GET['download'] == '1') {
    $tables = [];
    $result = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_array($result)) {
        $tables[] = $row[0];
    }

    $output = "-- Host Nibo Database SQL Dump\n";
    $output .= "-- Host: " . $_SERVER['HTTP_HOST'] . "\n";
    $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- PHP Version: " . PHP_VERSION . "\n\n";
    $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        $create = mysqli_fetch_assoc(mysqli_query($conn, "SHOW CREATE TABLE `$table`"));
        $output .= "-- Table structure for `$table`\n";
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $output .= $create['Create Table'] . ";\n\n";

        $rows = mysqli_query($conn, "SELECT * FROM `$table`");
        if (mysqli_num_rows($rows) > 0) {
            $output .= "-- Dumping data for `$table`\n";
            while ($row = mysqli_fetch_assoc($rows)) {
                $vals = [];
                foreach ($row as $v) {
                    $vals[] = $v === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, $v) . "'";
                }
                $output .= "INSERT INTO `$table` VALUES (" . implode(',', $vals) . ");\n";
            }
            $output .= "\n";
        }
    }
    $output .= "SET FOREIGN_KEY_CHECKS=1;\n";

    $filename = 'hostnibo_db_' . date('Y-m-d_H-i-s') . '.sql';
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=$filename");
    echo $output;
    exit;
}

// Handle Create & Save Backup to Server
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_server_backup'])) {
    $tables = [];
    $result = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_array($result)) {
        $tables[] = $row[0];
    }

    $output = "-- Host Nibo Database SQL Backup\n";
    $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        $create = mysqli_fetch_assoc(mysqli_query($conn, "SHOW CREATE TABLE `$table`"));
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $output .= $create['Create Table'] . ";\n\n";

        $rows = mysqli_query($conn, "SELECT * FROM `$table`");
        while ($row = mysqli_fetch_assoc($rows)) {
            $vals = [];
            foreach ($row as $v) {
                $vals[] = $v === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, $v) . "'";
            }
            $output .= "INSERT INTO `$table` VALUES (" . implode(',', $vals) . ");\n";
        }
        $output .= "\n";
    }
    $output .= "SET FOREIGN_KEY_CHECKS=1;\n";

    $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    if (file_put_contents($backup_dir . $filename, $output)) {
        logActivity('Created DB Backup', $filename);
        $msg = "Backup $filename saved successfully to server storage.";
    } else {
        $error = 'Failed to write backup file to server disk.';
    }
}

// Handle Delete Stored Backup File
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_backup_file'])) {
    $fname = basename($_POST['delete_backup_file']);
    $target = $backup_dir . $fname;
    if (file_exists($target)) {
        unlink($target);
        logActivity('Deleted DB Backup File', $fname);
        $msg = "Backup file $fname deleted.";
    }
}

// Handle Download Stored Backup File
if (isset($_GET['get_file'])) {
    $fname = basename($_GET['get_file']);
    $target = $backup_dir . $fname;
    if (file_exists($target)) {
        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=$fname");
        readfile($target);
        exit;
    }
}

// Handle Restore Backup File (Uploaded or Stored)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_backup'])) {
    $sql = '';
    if (!empty($_POST['stored_file_restore'])) {
        $fname = basename($_POST['stored_file_restore']);
        $target = $backup_dir . $fname;
        if (file_exists($target)) {
            $sql = file_get_contents($target);
        } else {
            $error = 'Selected backup file not found.';
        }
    } elseif (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'sql') {
            $error = 'Only .sql database files are allowed.';
        } else {
            $sql = file_get_contents($_FILES['backup_file']['tmp_name']);
        }
    } else {
        $error = 'Please provide a valid .sql backup file to restore.';
    }

    if ($sql && empty($error)) {
        try {
            mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0");
            if (mysqli_multi_query($conn, $sql)) {
                do {
                    if ($result = mysqli_store_result($conn)) {
                        mysqli_free_result($result);
                    }
                } while (mysqli_next_result($conn));
            }
            mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1");
            logActivity('Restored Database Backup', 'Database restore executed');
            $msg = 'Database restored successfully!';
        } catch (Exception $e) {
            $error = 'Database restore failed: ' . $e->getMessage();
        }
    }
}

// Compute Metrics
$db_tables_count = 0;
$db_rows_count = 0;
$db_size_bytes = 0;
$table_list = [];

$status_res = mysqli_query($conn, "SHOW TABLE STATUS");
if ($status_res) {
    while ($row = mysqli_fetch_assoc($status_res)) {
        $db_tables_count++;
        $db_rows_count += (int)$row['Rows'];
        $db_size_bytes += ((int)$row['Data_length'] + (int)$row['Index_length']);
        $table_list[] = $row['Name'];
    }
}
$db_size_mb = round($db_size_bytes / (1024 * 1024), 2);

// List Stored Server Backups
$stored_backups = [];
if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $f) {
        if (str_ends_with($f, '.sql')) {
            $stored_backups[] = [
                'filename' => $f,
                'size' => round(filesize($backup_dir . $f) / 1024, 1) . ' KB',
                'created_at' => date('M d, Y • h:i A', filemtime($backup_dir . $f))
            ];
        }
    }
}
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-database"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Database Backup & Restore</h1>
            </div>
            <p class="text-xs text-gray-500">Generate complete SQL snapshots, manage server backups, and restore data safely.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="?download=1" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs cursor-pointer">
                <i class="fa-solid fa-download"></i> Download Live SQL
            </a>
            <form method="POST" class="inline">
                <button type="submit" name="save_server_backup" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-hard-drive"></i> Backup to Server
                </button>
            </form>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($msg): ?>
    <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between bg-emerald-50 text-emerald-800 border border-emerald-200">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span><?php echo htmlspecialchars($msg); ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 cursor-pointer"><i class="fa-solid fa-xmark text-sm"></i></button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between bg-red-50 text-red-800 border border-red-200">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-red-600 text-sm"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 cursor-pointer"><i class="fa-solid fa-xmark text-sm"></i></button>
    </div>
    <?php endif; ?>

    <!-- Database Metrics Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        
        <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-weight-hanging"></i>
            </div>
            <div>
                <span class="text-xs text-gray-500 font-semibold block">Database Size</span>
                <span class="text-xl font-bold text-gray-900"><?php echo $db_size_mb; ?> MB</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-table-cells"></i>
            </div>
            <div>
                <span class="text-xs text-gray-500 font-semibold block">Total Tables</span>
                <span class="text-xl font-bold text-gray-900"><?php echo $db_tables_count; ?> Tables</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-list-ol"></i>
            </div>
            <div>
                <span class="text-xs text-gray-500 font-semibold block">Total Data Rows</span>
                <span class="text-xl font-bold text-gray-900"><?php echo number_format($db_rows_count); ?> Records</span>
            </div>
        </div>

    </div>

    <!-- Main Content: Server Backups List & Restore Card -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left 2 Cols: Stored Server Backups -->
        <div class="lg:col-span-2 space-y-6">
            
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                    <div class="text-xs font-bold text-gray-800 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-folder-open text-blue-600"></i> Stored Backups on Server
                    </div>
                    <span class="text-xs text-gray-400"><?php echo count($stored_backups); ?> files</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50/70 border-b border-gray-200 text-xs font-bold text-gray-700 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3.5">Backup File</th>
                                <th class="px-4 py-3.5">File Size</th>
                                <th class="px-4 py-3.5">Created Date</th>
                                <th class="px-4 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            <?php if (!empty($stored_backups)): foreach ($stored_backups as $bk): ?>
                            <tr class="hover:bg-blue-50/20 transition">
                                <td class="px-4 py-3.5 font-mono font-bold text-gray-900">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-file-code text-blue-500"></i>
                                        <span><?php echo htmlspecialchars($bk['filename']); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-gray-600 font-semibold">
                                    <?php echo $bk['size']; ?>
                                </td>
                                <td class="px-4 py-3.5 text-gray-500 text-[11px]">
                                    <?php echo $bk['created_at']; ?>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="?get_file=<?php echo urlencode($bk['filename']); ?>" class="p-1.5 bg-gray-50 hover:bg-blue-50 text-blue-600 rounded-lg border border-gray-200 hover:border-blue-200 transition cursor-pointer" title="Download Backup">
                                            <i class="fa-solid fa-download text-xs"></i>
                                        </a>
                                        <button type="button" onclick="openRestoreConfirmModal('<?php echo addslashes($bk['filename']); ?>')" class="p-1.5 bg-gray-50 hover:bg-amber-50 text-amber-600 rounded-lg border border-gray-200 hover:border-amber-200 transition cursor-pointer" title="Restore this backup">
                                            <i class="fa-solid fa-rotate-left text-xs"></i>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Delete backup file <?php echo addslashes($bk['filename']); ?>?')" class="inline">
                                            <input type="hidden" name="delete_backup_file" value="<?php echo htmlspecialchars($bk['filename']); ?>">
                                            <button type="submit" class="p-1.5 bg-gray-50 hover:bg-red-50 text-red-600 rounded-lg border border-gray-200 hover:border-red-200 transition cursor-pointer" title="Delete Backup">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="4" class="px-4 py-12 text-center text-gray-400">
                                    <i class="fa-solid fa-hard-drive text-3xl text-gray-300 mb-2 block"></i>
                                    <p class="font-bold text-gray-700">No server backup files saved</p>
                                    <p class="text-[11px] text-gray-400 mt-0.5">Click "Backup to Server" to preserve an instant snapshot.</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tables Included -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-5">
                <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider mb-3">Database Tables (<?php echo count($table_list); ?>)</h3>
                <div class="flex flex-wrap gap-1.5 font-mono text-[11px]">
                    <?php foreach ($table_list as $tbl): ?>
                    <span class="px-2.5 py-1 rounded-lg bg-gray-50 text-gray-700 border border-gray-200">
                        <?php echo htmlspecialchars($tbl); ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- Right 1 Col: Upload & Restore Card -->
        <div class="space-y-6">
            
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6">
                <div class="flex items-center gap-2 mb-4 pb-3 border-b border-gray-100">
                    <span class="p-2 bg-amber-50 text-amber-600 rounded-lg text-xs"><i class="fa-solid fa-upload"></i></span>
                    <h2 class="text-sm font-bold text-gray-900">Upload & Restore SQL</h2>
                </div>

                <div class="bg-amber-50/80 border border-amber-200 text-amber-900 p-3.5 rounded-xl text-xs mb-4 leading-relaxed">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 mr-1"></i>
                    <strong>Caution:</strong> Restoring an SQL backup replaces current table structures and data. Ensure you have downloaded a current copy first.
                </div>

                <form method="POST" enctype="multipart/form-data" onsubmit="return confirm('⚠️ CRITICAL CONFIRMATION\n\nRestoring will overwrite current database records. Proceed?');" class="space-y-4 text-xs">
                    <input type="hidden" name="restore_backup" value="1">
                    
                    <div>
                        <label class="block font-bold text-gray-700 mb-1.5">Select .sql Backup File</label>
                        <input type="file" name="backup_file" accept=".sql" required class="w-full border border-gray-300 rounded-xl p-2 bg-gray-50 text-xs file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:bg-amber-100 file:text-amber-800 file:font-bold">
                    </div>

                    <button type="submit" class="w-full bg-amber-600 hover:bg-amber-700 text-white font-bold py-2.5 px-4 rounded-xl shadow-xs transition flex items-center justify-center gap-2 text-xs cursor-pointer">
                        <i class="fa-solid fa-rotate-left"></i> Restore From File
                    </button>
                </form>
            </div>

        </div>

    </div>

</div>

<!-- ==========================================
     POPUP MODAL: SAFE RESTORE STORED FILE
=============================================== -->
<div id="restoreModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST">
            <input type="hidden" name="restore_backup" value="1">
            <input type="hidden" name="stored_file_restore" id="stored_file_restore_input" value="">
            
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Restore Database Snapshot?</h3>
                <p class="text-xs text-amber-700 font-semibold mb-3">⚠️ This will overwrite your active database with the chosen snapshot.</p>
                <div class="bg-gray-50 p-2.5 rounded-xl border border-gray-200 font-mono text-xs font-bold text-gray-800" id="restoreFileName">
                    backup_file.sql
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="closeRestoreModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-rotate-left"></i> Yes, Restore Snapshot
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openRestoreConfirmModal(fname) {
    document.getElementById('stored_file_restore_input').value = fname;
    document.getElementById('restoreFileName').innerText = fname;
    document.getElementById('restoreModal').classList.remove('hidden');
}

function closeRestoreModal() {
    document.getElementById('restoreModal').classList.add('hidden');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeRestoreModal();
    }
});
</script>

<?php include 'footer.php'; ?>
