<?php
$page_title = 'SEO Score Checker';
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
];

$r = @$conn->query("SELECT slug, title FROM pages WHERE status = 1");
if ($r) { while ($row = $r->fetch_assoc()) {
    $pages_to_check[] = ['name' => $row['title'], 'url' => '/page.php?slug=' . $row['slug']];
}}
$r = @$conn->query("SELECT slug, title FROM blog_posts WHERE status = 1 LIMIT 10");
if ($r) { while ($row = $r->fetch_assoc()) {
    $pages_to_check[] = ['name' => 'Blog: ' . $row['title'], 'url' => '/blog.php?slug=' . $row['slug']];
}}
$r = @$conn->query("SELECT slug, name FROM blog_categories WHERE status = 1");
if ($r) { while ($row = $r->fetch_assoc()) {
    $pages_to_check[] = ['name' => 'Category: ' . $row['name'], 'url' => '/blog-category.php?slug=' . $row['slug']];
}}

$results = [];
foreach ($pages_to_check as $page) {
    $full_url = $base_url . $page['url'];
    $html = @file_get_contents($full_url);
    if ($html === false) {
        $results[] = ['name' => $page['name'], 'url' => $page['url'], 'score' => 0, 'issues' => ['Page not reachable'], 'checks' => []];
        continue;
    }

    $score = 0;
    $issues = [];
    $checks = [];

    $has_title = (bool) preg_match('/<title[^>]*>(.+?)<\/title>/is', $html, $m);
    $title_len = $has_title ? mb_strlen(strip_tags($m[1])) : 0;
    if ($has_title && $title_len >= 30 && $title_len <= 60) { $score += 20; $checks[] = ['Title', 'OK', "Length: $title_len chars"]; }
    elseif ($has_title) { $score += 10; $checks[] = ['Title', 'Warning', "Length: $title_len (ideal: 30-60)"]; $issues[] = "Title length: $title_len"; }
    else { $checks[] = ['Title', 'Fail', 'Missing']; $issues[] = 'Missing title tag'; }

    $has_desc = (bool) preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.+?)["\']/is', $html, $m);
    $desc_len = $has_desc ? mb_strlen($m[1]) : 0;
    if ($has_desc && $desc_len >= 120 && $desc_len <= 160) { $score += 20; $checks[] = ['Meta Description', 'OK', "Length: $desc_len chars"]; }
    elseif ($has_desc) { $score += 10; $checks[] = ['Meta Description', 'Warning', "Length: $desc_len (ideal: 120-160)"]; $issues[] = "Description length: $desc_len"; }
    else { $checks[] = ['Meta Description', 'Fail', 'Missing']; $issues[] = 'Missing meta description'; }

    $has_h1 = (bool) preg_match('/<h1[\s>]/is', $html);
    if ($has_h1) { $score += 15; $checks[] = ['H1 Tag', 'OK', 'Present']; }
    else { $checks[] = ['H1 Tag', 'Fail', 'Missing']; $issues[] = 'Missing H1 tag'; }

    $has_og = (bool) preg_match('/<meta\s+property=["\']og:title["\']/is', $html);
    if ($has_og) { $score += 15; $checks[] = ['Open Graph', 'OK', 'Present']; }
    else { $checks[] = ['Open Graph', 'Warning', 'Missing og:title']; $issues[] = 'Missing OG tags'; }

    $img_count = preg_match_all('/<img[\s>]/is', $html);
    $img_alt_count = preg_match_all('/<img[^>]+alt=["\'][^"\']+["\']/is', $html);
    if ($img_count > 0 && $img_alt_count >= $img_count) { $score += 15; $checks[] = ['Image Alt Tags', 'OK', "$img_alt_count/$img_count images"]; }
    elseif ($img_count > 0) { $missing = $img_count - $img_alt_count; $score += 5; $checks[] = ['Image Alt Tags', 'Warning', "$missing/$img_count missing alt"]; $issues[] = "$missing images missing alt"; }
    else { $score += 15; $checks[] = ['Image Alt Tags', 'OK', 'No images']; }

    $has_canonical = (bool) preg_match('/<link\s+rel=["\']canonical["\']/is', $html);
    if ($has_canonical) { $score += 10; $checks[] = ['Canonical', 'OK', 'Present']; }
    else { $checks[] = ['Canonical', 'Warning', 'Missing']; $issues[] = 'Missing canonical tag'; }

    $has_schema = (bool) preg_match('/application\/ld\+json/', $html);
    if ($has_schema) { $score += 5; $checks[] = ['Schema.org', 'OK', 'Present']; }
    else { $checks[] = ['Schema.org', 'Warning', 'Missing']; $issues[] = 'Missing structured data'; }

    $results[] = ['name' => $page['name'], 'url' => $page['url'], 'score' => min(100, $score), 'issues' => $issues, 'checks' => $checks];
}

usort($results, function($a, $b) { return $a['score'] - $b['score']; });
$total = count($results);
$avg = $total > 0 ? round(array_sum(array_column($results, 'score')) / $total) : 0;
?>
<?php include 'header.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100"><i class="fa fa-search mr-2 text-blue-600 dark:text-blue-400"></i> SEO Score Checker</h1>
    <p class="text-gray-500 dark:text-gray-400">Analyze SEO health of all pages</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
        <div class="text-4xl font-bold <?php echo $avg >= 80 ? 'text-green-600' : ($avg >= 50 ? 'text-yellow-500' : 'text-red-600'); ?>"><?php echo $avg; ?>%</div>
        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Average Score</div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
        <div class="text-4xl font-bold text-blue-600 dark:text-blue-400"><?php echo $total; ?></div>
        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pages Checked</div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
        <div class="text-4xl font-bold text-red-600 dark:text-red-400"><?php echo count(array_filter($results, fn($r) => $r['score'] < 50)); ?></div>
        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Critical Pages</div>
    </div>
</div>

<div class="space-y-4">
<?php foreach ($results as $r): ?>
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg <?php echo $r['score'] >= 80 ? 'bg-green-500' : ($r['score'] >= 50 ? 'bg-yellow-500' : 'bg-red-500'); ?>">
                <?php echo $r['score']; ?>
            </div>
            <div>
                <h3 class="font-semibold text-gray-800 dark:text-gray-100"><?php echo htmlspecialchars($r['name']); ?></h3>
                <a href="<?php echo htmlspecialchars($r['url']); ?>" target="_blank" class="text-xs text-blue-600 dark:text-blue-400 hover:underline"><?php echo htmlspecialchars($r['url']); ?></a>
            </div>
        </div>
        <div class="text-sm text-gray-400"><?php echo count($r['issues']); ?> issues</div>
    </div>
    <div class="border-t dark:border-gray-700 px-6 py-3 bg-gray-50 dark:bg-gray-750">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-2">
        <?php foreach ($r['checks'] as $c): ?>
            <div class="flex items-center gap-1.5 text-xs">
                <?php if ($c[1] === 'OK'): ?><span class="text-green-500"><i class="fa fa-check-circle"></i></span>
                <?php elseif ($c[1] === 'Warning'): ?><span class="text-yellow-500"><i class="fa fa-exclamation-triangle"></i></span>
                <?php else: ?><span class="text-red-500"><i class="fa fa-times-circle"></i></span><?php endif; ?>
                <span class="text-gray-600 dark:text-gray-300"><?php echo $c[0]; ?></span>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php include 'footer.php'; ?>
