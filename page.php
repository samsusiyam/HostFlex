<?php require_once 'config/database.php'; require_once 'includes/functions.php'; checkMaintenance();

$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';
$page = getPageBySlug($slug);

if (!$page) {
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/404.php';
    exit;
}
$skip_default_meta = true;
$site_name = escSetting('site_name') ?: 'Host Nibo';
$page_title_seo = !empty($page['meta_title']) ? $page['meta_title'] : (stripos($page['title'], $site_name) !== false ? $page['title'] : $page['title'] . " - " . $site_name);
$page_meta = $page['meta_description'] ?: "Learn more about " . $page['title'] . " at " . $site_name . ". Everything you need to know about our web hosting services and policies.";
$page_kw = $page['meta_keywords'] ?: $page['title'] . ", " . $site_name;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include "cdnjs.php"; ?>
<title><?php echo htmlspecialchars($page_title_seo); ?></title>
<meta name="description" content="<?php echo htmlspecialchars($page_meta); ?>">
<meta name="keywords" content="<?php echo htmlspecialchars($page_kw); ?>">
</head>
<body>
<?php include "header.php"; ?>
<?php include "contact-btn.php"; ?>
<section class="section_gap">
<div class="content">
<?php $breadcrumbs = [['label' => $page['title']]]; include __DIR__ . '/breadcrumb.php'; ?>
<div class="mb-8">
<h1 class="text-3xl md:text-4xl font-extrabold text-gray-800"><?php echo htmlspecialchars($page['title']); ?></h1>
</div>
<div class="page-content text-gray-800 leading-relaxed">
<?php echo $page['content']; ?>
</div>

<style>
.page-content h2 { font-size: 1.5rem; font-weight: 700; margin-top: 1.5rem; margin-bottom: 0.75rem; }
.page-content h3 { font-size: 1.25rem; font-weight: 600; margin-top: 1.25rem; margin-bottom: 0.5rem; }
.page-content p { margin-bottom: 1rem; line-height: 1.75; color: #374151; }
.page-content ul, .page-content ol { margin-bottom: 1rem; padding-left: 1.5rem; }
.page-content ul { list-style-type: disc; }
.page-content ol { list-style-type: decimal; }
.page-content li { margin-bottom: 0.25rem; line-height: 1.75; }
.page-content img { max-width: 100%; height: auto; border-radius: 0.5rem; margin: 1rem 0; }
</style>
</div>
</section>
<?php include "footer.php"; ?>
</body>
</html>

