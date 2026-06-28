<?php require_once 'config/database.php'; require_once 'includes/functions.php'; checkMaintenance();

$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';
$page = getPageBySlug($slug);

if (!$page) {
    header('HTTP/1.0 404 Not Found');
    echo '<h1>404 - Page Not Found</h1>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include "cdnjs.php"; ?>
<title><?php echo htmlspecialchars($page['title']); ?> - <?php echo escSetting('site_name'); ?></title>
<meta name="description" content="<?php echo htmlspecialchars($page['meta_description']); ?>">
<meta name="keywords" content="<?php echo htmlspecialchars($page['meta_keywords']); ?>">
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

