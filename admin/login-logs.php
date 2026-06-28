<?php
$page_title = 'Login Logs';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();
checkPermission('logs', 'view');

$search = trim($_GET['search'] ?? '');
$where = '';
$no_table = !tableExists('login_logs');
$total = 0;
$pages = 1;
$page = 1;
$logs = [];

if (!$no_table) {
    if ($search) {
        $search_esc = mysqli_real_escape_string($conn, $search);
        $where = "WHERE username LIKE '%$search_esc%' OR ip_address LIKE '%$search_esc%'";
    }
    $page = max(1, (int)($_GET['p'] ?? 1));
    $per_page = 30;
    $offset = ($page - 1) * $per_page;
    $total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM login_logs $where"))['c'];
    $pages = ceil($total / $per_page);
    $logs = mysqli_query($conn, "SELECT * FROM login_logs $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
}

if (isset($_GET['clear']) && $_GET['clear'] == '1') {
    checkPermission('logs', 'delete');
    if (!$no_table) mysqli_query($conn, "TRUNCATE TABLE login_logs");
    header('Location: login-logs.php');
    exit;
}
?>
<?php include 'header.php'; ?>
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Login Logs</h1>
        <p class="text-gray-500 dark:text-gray-400">Track all login attempts to the admin panel</p>
    </div>
    <?php if (!$no_table): ?>
    <div class="flex gap-2">
        <a href="?clear=1" onclick="return confirm('Clear all login logs?')" class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700 dark:hover:bg-red-600"><i class="fa fa-trash mr-1"></i> Clear Logs</a>
    </div>
    <?php endif; ?>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden dark:bg-gray-800">
    <div class="p-4 border-b flex items-center justify-between dark:border-gray-600">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by username or IP..." class="border rounded px-3 py-2 text-sm dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
            <button type="submit" class="bg-blue-600 text-white px-3 py-2 rounded text-sm"><i class="fa fa-search"></i></button>
            <?php if ($search): ?><a href="login-logs.php" class="bg-gray-300 text-gray-700 px-3 py-2 rounded text-sm dark:bg-gray-600 dark:text-gray-200">Clear</a><?php endif; ?>
        </form>
        <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo $total; ?> total</span>
    </div>
    <table class="w-full">
        <thead class="bg-gray-50 border-b dark:bg-gray-700 dark:border-gray-600">
            <tr>
                <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Username</th>
                <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Status</th>
                <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">IP Address</th>
                <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">User Agent</th>
                <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Time</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-600">
            <?php if ($no_table): ?>
            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">Please run <code>config/migrate-system.php</code> to create required tables.</td></tr>
            <?php elseif (mysqli_num_rows($logs) > 0): while ($log = mysqli_fetch_assoc($logs)): ?>
            <tr class="hover:bg-gray-50 text-sm dark:hover:bg-gray-700">
                <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($log['username']); ?></td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-1 rounded-full <?php echo $log['status'] == 'success' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'; ?>"><?php echo ucfirst($log['status']); ?></span>
                </td>
                <td class="px-4 py-3 text-gray-500 font-mono dark:text-gray-400"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                <td class="px-4 py-3 text-gray-500 max-w-xs truncate dark:text-gray-400" title="<?php echo htmlspecialchars($log['user_agent'] ?? ''); ?>"><?php echo htmlspecialchars(substr($log['user_agent'] ?? '-', 0, 50)); ?>...</td>
                <td class="px-4 py-3 text-gray-500 whitespace-nowrap dark:text-gray-400"><?php echo date('d M Y H:i', strtotime($log['created_at'])); ?></td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">No login logs yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php if (!$no_table && $pages > 1): ?>
<div class="flex justify-center mt-4 gap-1">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a href="?p=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="px-3 py-1 rounded text-sm <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'; ?>"><?php echo $i; ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>
<?php include 'footer.php'; ?>
