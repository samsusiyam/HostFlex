<?php
if (!function_exists('escSetting')) { require_once __DIR__ . '/includes/functions.php'; }
$site_name = escSetting('site_name') ?: 'Home';
?>
<nav aria-label="Breadcrumb" class="mb-6">
    <ol class="flex flex-wrap items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" itemscope itemtype="https://schema.org/BreadcrumbList">
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="/" itemprop="item" class="hover:text-blue-600 dark:hover:text-blue-400 transition"><span itemprop="name"><?php echo $site_name; ?></span></a>
            <meta itemprop="position" content="1">
        </li>
<?php
$position = 2;
foreach ($breadcrumbs as $crumb):
    $is_last = ($crumb === end($breadcrumbs));
?>
        <li class="before:content-['/'] before:mx-1.5 before:text-gray-300 dark:before:text-gray-600" <?php if ($is_last): ?>aria-current="page"<?php endif; ?> itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
<?php if ($is_last && empty($crumb['url'])): ?>
            <span class="text-gray-800 dark:text-gray-200 font-medium" itemprop="name"><?php echo htmlspecialchars($crumb['label']); ?></span>
<?php else: ?>
            <a href="<?php echo htmlspecialchars($crumb['url'] ?? '#'); ?>" itemprop="item" class="hover:text-blue-600 dark:hover:text-blue-400 transition"><span itemprop="name"><?php echo htmlspecialchars($crumb['label']); ?></span></a>
<?php endif; ?>
            <meta itemprop="position" content="<?php echo $position++; ?>">
        </li>
<?php endforeach; ?>
    </ol>
</nav>
