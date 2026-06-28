<?php
$page_title = 'Database Backup';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();
checkPermission('backup', 'view');

$msg = '';
$error = '';

if (isset($_GET['download'])) {
    checkPermission('backup', 'create');
    $tables = [];
    $result = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_array($result)) {
        $tables[] = $row[0];
    }

    $output = "-- " . getSetting('site_name') . " Database Backup\n";
    $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($tables as $table) {
        $create = mysqli_fetch_assoc(mysqli_query($conn, "SHOW CREATE TABLE `$table`"));
        $output .= "-- Table: $table\n";
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

    $filename = 'backup-' . date('Y-m-d-H-i-s') . '.sql';
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=$filename");
    echo $output;
    exit;
}

if (isset($_GET['s'])) {
    $msg = 'Backup downloaded successfully!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_backup'])) {
    checkPermission('backup', 'create');
    validateCSRFToken($_POST['csrf_token'] ?? '');
    if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a valid SQL file to restore.';
    } else {
        $ext = strtolower(pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'sql') {
            $error = 'Only .sql files are allowed.';
        } else {
            $sql = file_get_contents($_FILES['backup_file']['tmp_name']);
            if ($sql === false || trim($sql) === '') {
                $error = 'Could not read the uploaded file or file is empty.';
            } else {
                try {
                    mysqli_begin_transaction($conn);
                    if (mysqli_multi_query($conn, $sql)) {
                        do {
                            if ($result = mysqli_store_result($conn)) {
                                mysqli_free_result($result);
                            }
                        } while (mysqli_next_result($conn));
                    }
                    $err = mysqli_error($conn);
                    if ($err) {
                        throw new Exception($err);
                    }
                    mysqli_commit($conn);
                    $msg = 'Backup restored successfully!';
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $error = 'Query failed: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<?php include 'header.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Database Backup</h1>
    <p class="text-gray-500 dark:text-gray-400">Download a complete SQL backup of your database</p>
</div>
<?php if ($msg): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 dark:bg-green-900/30 dark:border-green-700 dark:text-green-300"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 dark:bg-red-900/30 dark:border-red-700 dark:text-red-300"><?php echo $error; ?></div><?php endif; ?>

<div class="bg-white rounded-lg shadow p-6 max-w-2xl dark:bg-gray-800">
    <div class="text-center py-8">
        <i class="fa fa-database text-6xl text-blue-600 mb-4 dark:text-blue-400"></i>
        <h2 class="text-xl font-semibold text-gray-800 mb-2 dark:text-gray-100">Download Database Backup</h2>
        <p class="text-gray-500 mb-6 dark:text-gray-400">Click the button below to download a complete SQL dump of all tables including schema and data.</p>
        <a href="?download=1" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition shadow dark:hover:bg-blue-600"><i class="fa fa-download mr-2"></i> Download Backup (.sql)</a>
    </div>
    <div class="border-t pt-4 mt-4 dark:border-gray-600">
        <h3 class="font-semibold text-gray-700 mb-2 dark:text-gray-200">Included Tables:</h3>
        <?php
        $result = mysqli_query($conn, "SHOW TABLES");
        echo '<div class="flex flex-wrap gap-2">';
        while ($row = mysqli_fetch_array($result)) {
            echo '<span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded dark:bg-gray-700 dark:text-gray-300">' . $row[0] . '</span>';
        }
        echo '</div>';
        ?>
    </div>
</div>
<div class="bg-white rounded-lg shadow p-6 max-w-2xl mt-6">
    <div class="text-center py-8">
        <i class="fa fa-upload text-6xl text-amber-600 mb-4 dark:text-amber-400"></i>
        <h2 class="text-xl font-semibold text-gray-800 mb-2 dark:text-gray-100">Restore Backup</h2>
        <p class="text-gray-500 mb-4 dark:text-gray-400">Upload a previously downloaded <code>.sql</code> backup file to restore your database.</p>
        <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 px-4 py-3 rounded mb-6 text-sm text-left inline-block max-w-md dark:bg-yellow-900/30 dark:border-yellow-700 dark:text-yellow-300">
            <i class="fa fa-exclamation-triangle mr-1"></i> This will overwrite existing data. We recommend backing up first.
        </div>
        <form method="post" enctype="multipart/form-data" onsubmit="return confirm('Are you sure you want to restore this backup? Existing data will be overwritten.');">
            <?= csrfField() ?>
            <div class="mb-4">
                <input type="file" name="backup_file" accept=".sql" class="block w-full text-sm text-gray-600 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 dark:file:bg-amber-900/50 dark:file:text-amber-300">
            </div>
            <button type="submit" name="restore_backup" class="bg-amber-600 text-white px-8 py-3 rounded-lg hover:bg-amber-700 transition shadow dark:hover:bg-amber-500"><i class="fa fa-upload mr-2"></i> Restore Database</button>
        </form>
    </div>
</div>
<div class="bg-white rounded-lg shadow p-6 max-w-2xl mt-6 dark:bg-gray-800">
    <div class="flex items-center gap-3 mb-4">
        <i class="fa fa-clock text-2xl text-green-600 dark:text-green-400"></i>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Automatic Backups</h2>
    </div>
    <p class="text-gray-500 dark:text-gray-400 mb-4">Set up a cron job to automatically backup your database daily. Keeps the last 7 backups.</p>
    <div class="bg-gray-50 dark:bg-gray-700 rounded p-4 font-mono text-sm text-gray-700 dark:text-gray-300 mb-4 overflow-x-auto">
        <code>0 2 * * * php /home/hostnibo/public_html/auto-backup.php</code>
    </div>
    <p class="text-xs text-gray-400 dark:text-gray-500">Add the above line to your server's crontab (cPanel → Cron Jobs). Backups are saved to <code>/backups/</code> directory.</p>
</div>
<?php include 'footer.php'; ?>
