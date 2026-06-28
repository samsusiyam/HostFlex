<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$page_title = 'Analytics Dashboard';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();
checkPermission('settings', 'view');

if (!tableExists('page_views')) {
    @mysqli_query($conn, "CREATE TABLE page_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page_url VARCHAR(500) NOT NULL,
        page_title VARCHAR(255) DEFAULT '',
        referrer VARCHAR(500) DEFAULT '',
        ip_address VARCHAR(45) DEFAULT '',
        user_agent VARCHAR(500) DEFAULT '',
        country VARCHAR(100) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$period = $_GET['period'] ?? '30';
$period = in_array($period, ['7', '30', '90', '365']) ? $period : '30';
$date_from = date('Y-m-d', strtotime("-{$period} days"));

$total_views = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM page_views"))['c'];
$views_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM page_views WHERE DATE(created_at) = CURDATE()"))['c'];
$views_week = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM page_views WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"))['c'];
$unique_ips = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT ip_address) as c FROM page_views WHERE created_at >= '$date_from'"))['c'];
$prev_period_ips = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT ip_address) as c FROM page_views WHERE created_at >= DATE_SUB('$date_from', INTERVAL $period DAY) AND created_at < '$date_from'"))['c'];
$visitor_change = $prev_period_ips > 0 ? round((($unique_ips - $prev_period_ips) / $prev_period_ips) * 100) : ($unique_ips > 0 ? 100 : 0);

$chart_data = [];
$r = mysqli_query($conn, "SELECT DATE(created_at) as day, COUNT(*) as views, COUNT(DISTINCT ip_address) as visitors FROM page_views WHERE created_at >= '$date_from' GROUP BY DATE(created_at) ORDER BY day ASC");
while ($row = mysqli_fetch_assoc($r)) { $chart_data[] = $row; }

$chart_labels = array_map(fn($d) => date('M d', strtotime($d['day'])), $chart_data);
$chart_views = array_column($chart_data, 'views');
$chart_visitors = array_column($chart_data, 'visitors');

$top_pages = [];
$r = mysqli_query($conn, "SELECT page_url, page_title, COUNT(*) as views FROM page_views WHERE created_at >= '$date_from' GROUP BY page_url ORDER BY views DESC LIMIT 10");
while ($row = mysqli_fetch_assoc($r)) { $top_pages[] = $row; }

$referrers = [];
$r = mysqli_query($conn, "SELECT referrer, COUNT(*) as views FROM page_views WHERE created_at >= '$date_from' AND referrer != '' GROUP BY referrer ORDER BY views DESC LIMIT 10");
while ($row = mysqli_fetch_assoc($r)) {
    $host = @parse_url($row['referrer'], PHP_URL_HOST);
    $referrers[] = ['referrer' => $host ?: $row['referrer'], 'views' => $row['views']];
}

$browsers = [];
$r = mysqli_query($conn, "SELECT user_agent, COUNT(*) as views FROM page_views WHERE created_at >= '$date_from' GROUP BY user_agent ORDER BY views DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($r)) {
    $ua = $row['user_agent'];
    $browser = 'Other';
    if (stripos($ua, 'Edg') !== false) $browser = 'Edge';
    elseif (stripos($ua, 'Chrome') !== false) $browser = 'Chrome';
    elseif (stripos($ua, 'Firefox') !== false) $browser = 'Firefox';
    elseif (stripos($ua, 'Safari') !== false) $browser = 'Safari';
    elseif (stripos($ua, 'Opera') !== false || stripos($ua, 'OPR') !== false) $browser = 'Opera';
    $found = false;
    foreach ($browsers as &$b) { if ($b['browser'] === $browser) { $b['views'] += $row['views']; $found = true; break; } }
    if (!$found) $browsers[] = ['browser' => $browser, 'views' => $row['views']];
}
usort($browsers, fn($a, $b) => $b['views'] - $a['views']);

$devices = [];
$r = mysqli_query($conn, "SELECT user_agent, COUNT(*) as views FROM page_views WHERE created_at >= '$date_from'");
while ($row = mysqli_fetch_assoc($r)) {
    $ua = $row['user_agent'];
    $device = 'Desktop';
    if (preg_match('/Mobile|Android|iPhone|iPad/i', $ua)) {
        if (preg_match('/iPad|Tablet/i', $ua)) $device = 'Tablet';
        else $device = 'Mobile';
    }
    $found = false;
    foreach ($devices as &$d) { if ($d['device'] === $device) { $d['views'] += $row['views']; $found = true; break; } }
    if (!$found) $devices[] = ['device' => $device, 'views' => $row['views']];
}
usort($devices, fn($a, $b) => $b['views'] - $a['views']);
?>
<?php include 'header.php'; ?>
<div class="mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100"><i class="fa fa-chart-line mr-2 text-blue-600 dark:text-blue-400"></i> Analytics Dashboard</h1>
            <p class="text-gray-500 dark:text-gray-400">Track your website visitors and page views</p>
        </div>
        <div class="flex gap-2">
            <?php foreach (['7' => '7 Days', '30' => '30 Days', '90' => '90 Days', '365' => '1 Year'] as $val => $label): ?>
            <a href="?period=<?php echo $val; ?>" class="px-3 py-1.5 rounded text-sm font-medium <?php echo $period == $val ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-blue-50 dark:hover:bg-gray-700 border dark:border-gray-700'; ?>"><?php echo $label; ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-blue-500">
        <p class="text-gray-500 dark:text-gray-400 text-sm">Total Views (All Time)</p>
        <h3 class="text-2xl font-bold dark:text-gray-100"><?php echo number_format($total_views); ?></h3>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-green-500">
        <p class="text-gray-500 dark:text-gray-400 text-sm">Views Today</p>
        <h3 class="text-2xl font-bold dark:text-gray-100"><?php echo number_format($views_today); ?></h3>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-purple-500">
        <p class="text-gray-500 dark:text-gray-400 text-sm">Unique Visitors (<?php echo $period; ?>d)</p>
        <h3 class="text-2xl font-bold dark:text-gray-100"><?php echo number_format($unique_ips); ?></h3>
        <?php if ($visitor_change != 0): ?>
        <p class="text-sm mt-1 <?php echo $visitor_change > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
            <i class="fa fa-arrow-<?php echo $visitor_change > 0 ? 'up' : 'down'; ?>"></i> <?php echo abs($visitor_change); ?>% vs previous
        </p>
        <?php endif; ?>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-yellow-500">
        <p class="text-gray-500 dark:text-gray-400 text-sm">Views This Week</p>
        <h3 class="text-2xl font-bold dark:text-gray-100"><?php echo number_format($views_week); ?></h3>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
    <h2 class="font-semibold text-gray-700 dark:text-gray-200 mb-4">Traffic Overview</h2>
    <canvas id="trafficChart" height="100"></canvas>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-4 border-b dark:border-gray-700">
            <h2 class="font-semibold text-gray-700 dark:text-gray-200">Top Pages</h2>
        </div>
        <div class="p-4">
            <?php if (empty($top_pages)): ?>
                <p class="text-gray-400 dark:text-gray-500 text-center py-4 text-sm">No data yet. Install tracking script on your frontend.</p>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($top_pages as $i => $page): ?>
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-gray-400 dark:text-gray-500 w-5"><?php echo $i + 1; ?>.</span>
                        <span class="text-gray-700 dark:text-gray-300 truncate" title="<?php echo htmlspecialchars($page['page_url']); ?>"><?php echo htmlspecialchars($page['page_title'] ?: $page['page_url']); ?></span>
                    </div>
                    <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 px-2 py-0.5 rounded text-xs font-medium ml-2 whitespace-nowrap"><?php echo number_format($page['views']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-4 border-b dark:border-gray-700">
            <h2 class="font-semibold text-gray-700 dark:text-gray-200">Top Referrers</h2>
        </div>
        <div class="p-4">
            <?php if (empty($referrers)): ?>
                <p class="text-gray-400 dark:text-gray-500 text-center py-4 text-sm">No referrer data yet.</p>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($referrers as $i => $ref): ?>
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-gray-400 dark:text-gray-500 w-5"><?php echo $i + 1; ?>.</span>
                        <span class="text-gray-700 dark:text-gray-300 truncate"><?php echo htmlspecialchars($ref['referrer']); ?></span>
                    </div>
                    <span class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 px-2 py-0.5 rounded text-xs font-medium ml-2 whitespace-nowrap"><?php echo number_format($ref['views']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-4 border-b dark:border-gray-700">
            <h2 class="font-semibold text-gray-700 dark:text-gray-200">Browsers</h2>
        </div>
        <div class="p-4">
            <?php if (empty($browsers)): ?>
                <p class="text-gray-400 dark:text-gray-500 text-center py-4 text-sm">No data yet.</p>
            <?php else: ?>
            <?php $max_browser = $browsers[0]['views']; ?>
            <div class="space-y-3">
                <?php foreach ($browsers as $b): ?>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($b['browser']); ?></span>
                        <span class="text-gray-500 dark:text-gray-400"><?php echo number_format($b['views']); ?></span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo $max_browser > 0 ? ($b['views'] / $max_browser * 100) : 0; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-4 border-b dark:border-gray-700">
            <h2 class="font-semibold text-gray-700 dark:text-gray-200">Devices</h2>
        </div>
        <div class="p-4">
            <?php if (empty($devices)): ?>
                <p class="text-gray-400 dark:text-gray-500 text-center py-4 text-sm">No data yet.</p>
            <?php else: ?>
            <?php $max_device = $devices[0]['views']; ?>
            <div class="space-y-3">
                <?php
                $icons = ['Desktop' => 'fa-desktop', 'Mobile' => 'fa-mobile-alt', 'Tablet' => 'fa-tablet-alt'];
                $colors = ['Desktop' => 'bg-blue-500', 'Mobile' => 'bg-green-500', 'Tablet' => 'bg-purple-500'];
                foreach ($devices as $d): ?>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-700 dark:text-gray-300"><i class="fa <?php echo $icons[$d['device']] ?? 'fa-laptop'; ?> mr-1"></i><?php echo htmlspecialchars($d['device']); ?></span>
                        <span class="text-gray-500 dark:text-gray-400"><?php echo number_format($d['views']); ?></span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="<?php echo $colors[$d['device']] ?? 'bg-gray-500'; ?> h-2 rounded-full" style="width: <?php echo $max_device > 0 ? ($d['views'] / $max_device * 100) : 0; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mt-6">
    <h2 class="font-semibold text-gray-700 dark:text-gray-200 mb-3">Tracking Setup</h2>
    <p class="text-gray-500 dark:text-gray-400 text-sm mb-3">Add this tracking script before <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">&lt;/body&gt;</code> on your frontend pages:</p>
    <pre class="bg-gray-900 text-green-400 p-4 rounded-lg text-xs overflow-x-auto"><code>&lt;script&gt;
(function(){
  var d=document,w=window,b=document.body;
  function t(){
    var h=new XMLHttpRequest();
    h.open('POST','/admin/track.php',true);
    h.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
    h.send('url='+encodeURIComponent(d.location.pathname)+'&t='+encodeURIComponent(d.title)+'&r='+encodeURIComponent(w.referrer||''));
  }
  if(b)t();else document.addEventListener('DOMContentLoaded',t);
})();
&lt;/script&gt;</code></pre>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const labels = <?php echo json_encode($chart_labels); ?>;
    const views = <?php echo json_encode($chart_views); ?>;
    const visitors = <?php echo json_encode($chart_visitors); ?>;
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(75,85,99,0.3)' : 'rgba(0,0,0,0.06)';
    const textColor = isDark ? '#9ca3af' : '#6b7280';

    new Chart(document.getElementById('trafficChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'Page Views', data: views, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', fill: true, tension: 0.4 },
                { label: 'Unique Visitors', data: visitors, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.4 }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { labels: { color: textColor } } },
            scales: {
                x: { ticks: { color: textColor, maxTicksLimit: 15 }, grid: { color: gridColor } },
                y: { ticks: { color: textColor }, grid: { color: gridColor }, beginAtZero: true }
            }
        }
    });
});
</script>
<?php include 'footer.php'; ?>
