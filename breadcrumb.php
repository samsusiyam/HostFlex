<?php
if (!function_exists('escSetting')) { require_once __DIR__ . '/includes/functions.php'; }
$site_name = escSetting('site_name') ?: 'Home';
?>
<nav aria-label="Breadcrumb" class="mb-6">
    <ol class="flex flex-wrap items-center gap-1 text-sm" itemscope itemtype="https://schema.org/BreadcrumbList">
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="/" itemprop="item" class="text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition"><span itemprop="name"><?php echo $site_name; ?></span></a>
            <meta itemprop="position" content="1">
        </li>
<?php
$position = 2;
foreach ($breadcrumbs as $crumb):
    $is_last = ($crumb === end($breadcrumbs));
?>
        <li class="text-gray-300 dark:text-gray-600 mx-0.5" <?php if ($is_last): ?>aria-current="page"<?php endif; ?> itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </li>
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
<?php if ($is_last && empty($crumb['url'])): ?>
            <span class="text-gray-800 dark:text-gray-200 font-medium" itemprop="name"><?php echo htmlspecialchars($crumb['label']); ?></span>
<?php else: ?>
            <a href="<?php echo htmlspecialchars($crumb['url'] ?? '#'); ?>" itemprop="item" class="text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition"><span itemprop="name"><?php echo htmlspecialchars($crumb['label']); ?></span></a>
<?php endif; ?>
            <meta itemprop="position" content="<?php echo $position++; ?>">
        </li>
<?php endforeach; ?>
    </ol>
</nav>
