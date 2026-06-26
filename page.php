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
<div class="mb-8">
<h1 class="text-3xl md:text-4xl font-extrabold text-gray-800"><?php echo htmlspecialchars($page['title']); ?></h1>
</div>
<div class="prose max-w-none text-gray-700 leading-relaxed">
<?php echo $page['content']; ?>
</div>
</div>
</section>
<?php include "footer.php"; ?>
</body>
</html>

