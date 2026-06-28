<?php
$page_title = 'Broken Link Checker';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();
checkPermission('settings', 'view');

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

$pages_to_check = [
    ['name' => 'Homepage', 'url' => '/'],
    ['name' => 'Blog', 'url' => '/blogs.php'],
    ['name' => 'Contact', 'url' => '/contact.php'],
    ['name' => 'Offers', 'url' => '/offers.php'],
    ['name' => 'Sitemap', 'url' => '/sitemap.php'],
];

$r = @$conn->query("SELECT slug, title FROM pages WHERE status = 1");
if ($r) { while ($row = $r->fetch_assoc()) {
    $pages_to_check[] = ['name' => $row['title'], 'url' => '/page.php?slug=' . $row['slug']];
}}
$r = @$conn->query("SELECT slug, title FROM blog_posts WHERE status = 1 LIMIT 10");
if ($r) { while ($row = $r->fetch_assoc()) {
    $pages_to_check[] = ['name' => 'Blog: ' . $row['title'], 'url' => '/blog.php?slug=' . $row['slug']];
}}

$all_links = [];
$broken_links = [];
$checked_urls = [];

foreach ($pages_to_check as $page) {
    $full_url = $base_url . $page['url'];
    $html = @file_get_contents($full_url);
    if ($html === false) {
        $broken_links[] = [
            'source_page' => $page['name'],
            'source_url' => $page['url'],
            'link_url' => $page['url'],
            'link_text' => '(page unreachable)',
            'status' => 'Page Not Reachable',
            'http_code' => 0,
        ];
        continue;
    }

    preg_match_all('/<a[^>]+href=["\']([^"\']+)["\']/i', $html, $matches);
    if (empty($matches[1])) continue;

    foreach ($matches[1] as $href) {
        $href = trim($href);
        if (empty($href) || $href === '#' || strpos($href, 'mailto:') === 0 || strpos($href, 'tel:') === 0 || strpos($href, 'javascript:') === 0) continue;
        if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg|css|js|ico|woff|woff2|ttf|eot|map)(\?|$)/i', $href)) continue;

        if (strpos($href, 'http') === 0) {
            $check_url = $href;
        } elseif (strpos($href, '//') === 0) {
            $check_url = 'https:' . $href;
        } elseif (strpos($href, '/') === 0) {
            $check_url = $base_url . $href;
        } else {
            $dir = dirname(parse_url($full_url, PHP_URL_PATH) ?: '/');
            $check_url = $base_url . $dir . '/' . $href;
        }

        $all_links[] = $check_url;

        if (isset($checked_urls[$check_url])) {
            if ($checked_urls[$check_url] !== true) {
                $broken_links[] = [
                    'source_page' => $page['name'],
                    'source_url' => $page['url'],
                    'link_url' => $check_url,
                    'link_text' => $href,
                    'status' => $checked_urls[$check_url],
                    'http_code' => 0,
                ];
            }
            continue;
        }

        $ch = curl_init($check_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'HostNibo Link Checker/1.0',
        ]);
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($http_code >= 400 || $http_code === 0) {
            $status = $error ?: ($http_code >= 400 ? "HTTP $http_code" : "Connection Failed");
            $checked_urls[$check_url] = $status;
            $broken_links[] = [
                'source_page' => $page['name'],
                'source_url' => $page['url'],
                'link_url' => $check_url,
                'link_text' => $href,
                'status' => $status,
                'http_code' => $http_code,
            ];
        } else {
            $checked_urls[$check_url] = true;
        }
    }
}

$total_checked = count($checked_urls);
$total_broken = count($broken_links);
$total_ok = $total_checked - $total_broken;
?>
<?php include 'header.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100"><i class="fa fa-unlink mr-2 text-red-600 dark:text-red-400"></i> Broken Link Checker</h1>
    <p class="text-gray-500 dark:text-gray-400">Scan your website for broken links</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center border-l-4 border-blue-500">
        <div class="text-4xl font-bold text-blue-600 dark:text-blue-400"><?php echo number_format($total_checked); ?></div>
        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Links Checked</div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center border-l-4 border-green-500">
        <div class="text-4xl font-bold text-green-600 dark:text-green-400"><?php echo number_format($total_ok); ?></div>
        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Working Links</div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center border-l-4 border-red-500">
        <div class="text-4xl font-bold <?php echo $total_broken > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'; ?>"><?php echo number_format($total_broken); ?></div>
        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Broken Links</div>
    </div>
</div>

<?php if ($total_broken > 0): ?>
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden mb-6">
    <div class="p-4 border-b dark:border-gray-700 bg-red-50 dark:bg-red-900/20">
        <h2 class="font-semibold text-red-700 dark:text-red-400"><i class="fa fa-exclamation-triangle mr-1"></i> Broken Links Found</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">Source Page</th>
                    <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">Broken URL</th>
                    <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-gray-700">
                <?php foreach ($broken_links as $link): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-4 py-3">
                        <a href="<?php echo htmlspecialchars($link['source_url']); ?>" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline"><?php echo htmlspecialchars($link['source_page']); ?></a>
                    </td>
                    <td class="px-4 py-3">
                        <a href="<?php echo htmlspecialchars($link['link_url']); ?>" target="_blank" class="text-red-600 dark:text-red-400 hover:underline break-all"><?php echo htmlspecialchars($link['link_url']); ?></a>
                    </td>
                    <td class="px-4 py-3">
                        <span class="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 px-2 py-0.5 rounded text-xs font-medium"><?php echo htmlspecialchars($link['status']); ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center mb-6">
    <div class="text-5xl text-green-500 mb-4"><i class="fa fa-check-circle"></i></div>
    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-2">All Links Working!</h2>
    <p class="text-gray-500 dark:text-gray-400">No broken links found across <?php echo count($pages_to_check); ?> pages checked.</p>
</div>
<?php endif; ?>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <div class="p-4 border-b dark:border-gray-700 flex justify-between items-center">
        <h2 class="font-semibold text-gray-700 dark:text-gray-200">Pages Scanned (<?php echo count($pages_to_check); ?>)</h2>
    </div>
    <div class="p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
            <?php foreach ($pages_to_check as $p): ?>
            <div class="flex items-center gap-2 text-sm">
                <i class="fa fa-check-circle text-green-500"></i>
                <span class="text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($p['name']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
