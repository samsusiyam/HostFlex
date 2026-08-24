<?php
$page_title = 'Login Logs';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminRole(['admin']);

$msg = '';
$search = trim($_GET['search'] ?? '');
$where = '';
$no_table = !tableExists('login_logs');
$total = 0;
$pages = 1;
$page = 1;
$logs = [];

if (isset($_POST['clear_all_login_logs'])) {
    if (!$no_table) {
        mysqli_query($conn, "TRUNCATE TABLE login_logs");
        $msg = 'Login logs cleared successfully.';
    }
}

if (!$no_table) {
    if ($search) {
        $search_esc = mysqli_real_escape_string($conn, $search);
        $where = "WHERE username LIKE '%$search_esc%' OR ip_address LIKE '%$search_esc%' OR status LIKE '%$search_esc%'";
    }
    $page = max(1, (int)($_GET['p'] ?? 1));
    $per_page = 30;
    $offset = ($page - 1) * $per_page;
    $total = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM login_logs $where"))['c'] ?? 0);
    $pages = ceil($total / $per_page);
    $logs = mysqli_query($conn, "SELECT * FROM login_logs $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
}
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-shield-halved"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Login History & Security Logs</h1>
            </div>
            <p class="text-xs text-gray-500">Monitor all authentication attempts, successful entries, and blocked password trials.</p>
        </div>
        <div class="flex items-center gap-2">
            <?php if (!$no_table && $total > 0): ?>
            <button type="button" onclick="openClearLoginLogsModal()" class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-trash-can"></i> Clear Login Logs
            </button>
            <?php endif; ?>
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

    <!-- Search & Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-gray-200/80 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="text-xs text-gray-500 font-semibold">
            Recorded Login Sessions: <strong class="text-gray-900"><?php echo number_format($total); ?></strong>
        </div>

        <form method="GET" class="relative w-full md:w-72">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search username, status, IP..." class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-8 pr-3 py-1.5 text-xs text-gray-800 focus:bg-white focus:outline-none focus:border-blue-600 transition">
        </form>
    </div>

    <!-- Login Log Table -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/70 border-b border-gray-200 text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5">User Account</th>
                        <th class="px-4 py-3.5">Login Outcome</th>
                        <th class="px-4 py-3.5">IP Address</th>
                        <th class="px-4 py-3.5">Browser & Device</th>
                        <th class="px-4 py-3.5 text-right">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    <?php if ($no_table): ?>
                    <tr>
                        <td colspan="5" class="px-4 py-16 text-center text-gray-400">
                            <p class="font-bold text-gray-700">Database table not initialized</p>
                        </td>
                    </tr>
                    <?php elseif (mysqli_num_rows($logs) > 0): while ($log = mysqli_fetch_assoc($logs)): 
                        $status = strtolower($log['status']);
                        $is_success = ($status === 'success');
                        $is_2fa = ($status === '2fa_prompt');
                    ?>
                    <tr class="hover:bg-blue-50/20 transition">
                        <td class="px-4 py-3.5 font-bold text-gray-900">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg <?php echo $is_success ? 'bg-emerald-50 text-emerald-600' : ($is_2fa ? 'bg-purple-50 text-purple-600' : 'bg-rose-50 text-rose-600'); ?> font-bold flex items-center justify-center text-[10px] shrink-0 border border-gray-200">
                                    <i class="fa-solid <?php echo $is_success ? 'fa-user-check' : ($is_2fa ? 'fa-shield-halved' : 'fa-user-xmark'); ?>"></i>
                                </div>
                                <span><?php echo htmlspecialchars($log['username'] ?: 'Unknown'); ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3.5">
                            <?php if ($is_success): ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Success
                            </span>
                            <?php elseif ($is_2fa): ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span> 2FA Prompted
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Failed Login
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3.5 font-mono text-[11px] font-bold text-gray-700">
                            <?php echo htmlspecialchars($log['ip_address'] ?: '127.0.0.1'); ?>
                        </td>
                        <td class="px-4 py-3.5 text-gray-500 max-w-xs truncate text-[11px]" title="<?php echo htmlspecialchars($log['user_agent'] ?? ''); ?>">
                            <?php echo htmlspecialchars($log['user_agent'] ?: '—'); ?>
                        </td>
                        <td class="px-4 py-3.5 text-right text-gray-500 text-[11px] whitespace-nowrap">
                            <div class="font-bold text-gray-800"><?php echo date('M d, Y', strtotime($log['created_at'])); ?></div>
                            <div class="text-gray-400"><?php echo date('h:i:s A', strtotime($log['created_at'])); ?></div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="5" class="px-4 py-16 text-center text-gray-400">
                            <i class="fa-solid fa-shield-halved text-4xl text-gray-300 mb-2 block"></i>
                            <p class="font-bold text-gray-700">No login records found</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if (!$no_table && $pages > 1): ?>
        <div class="p-4 border-t border-gray-200 flex items-center justify-between bg-gray-50/50">
            <span class="text-xs text-gray-500 font-semibold">
                Page <?php echo $page; ?> of <?php echo $pages; ?>
            </span>
            <div class="flex items-center gap-1">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <a href="?p=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?php echo $i == $page ? 'bg-blue-600 text-white shadow-xs' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-100'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- ==========================================
     POPUP MODAL: CLEAR ALL LOGIN LOGS
=============================================== -->
<div id="clearLoginLogsModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST">
            <input type="hidden" name="clear_all_login_logs" value="1">
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Clear All Login History?</h3>
                <p class="text-xs text-rose-600 font-semibold mb-3">⚠️ This will delete all authentication trail logs. Proceed?</p>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="closeClearLoginLogsModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-trash-can"></i> Yes, Clear History
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openClearLoginLogsModal() {
    document.getElementById('clearLoginLogsModal').classList.remove('hidden');
}

function closeClearLoginLogsModal() {
    document.getElementById('clearLoginLogsModal').classList.add('hidden');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeClearLoginLogsModal();
    }
});
</script>

<?php include 'footer.php'; ?>
