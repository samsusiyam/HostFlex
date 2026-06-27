<?php require_once 'config/database.php'; require_once 'includes/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include "cdnjs.php"; ?>
<title>404 - Page Not Found</title>
</head>
<body>
<?php include "header.php"; ?>
<section class="section_gap flex items-center justify-center bg-gray-50 font-poppins dark:bg-gray-800 min-h-[60vh]">
<div class="content text-center">
<h1 class="text-[80px] font-extrabold text-blue-600">404</h1>
<h2 class="text-2xl font-bold text-gray-800 dark:text-gray-300 mb-4">Page Not Found</h2>
<p class="text-gray-500 dark:text-gray-400 mb-8">The page you're looking for doesn't exist or has been moved.</p>
<a href="index.php" class="btn btn-blue">Go Back Home</a>
</div>
</section>
<?php include "footer.php"; ?>
</body>
</html>
