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
<div class="content text-center max-w-lg mx-auto">
<h1 class="text-[80px] font-extrabold text-blue-600 leading-none">404</h1>
<h2 class="text-2xl font-bold text-gray-800 dark:text-gray-300 mb-3">Page Not Found</h2>
<p class="text-gray-500 dark:text-gray-400 mb-8">The page you're looking for doesn't exist or has been moved.</p>

<form action="/" method="GET" class="mb-10">
<div class="flex items-center gap-2 max-w-md mx-auto">
    <input type="text" name="q" placeholder="Search our site..." class="flex-1 px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
    <button type="submit" class="bg-blue-600 text-white px-5 py-3 rounded-lg hover:bg-blue-700 transition"><i class="fa fa-search"></i></button>
</div>
</form>

<div class="mb-8">
    <p class="text-sm font-medium text-gray-400 dark:text-gray-500 mb-3">Popular pages:</p>
    <div class="flex flex-wrap justify-center gap-3">
        <a href="index.php" class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 px-4 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:border-blue-400 hover:text-blue-600 transition"><i class="fa fa-home mr-1"></i> Home</a>
        <a href="blogs.php" class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 px-4 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:border-blue-400 hover:text-blue-600 transition"><i class="fa fa-blog mr-1"></i> Blog</a>
        <a href="contact.php" class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 px-4 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:border-blue-400 hover:text-blue-600 transition"><i class="fa fa-envelope mr-1"></i> Contact</a>
        <a href="offers.php" class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 px-4 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:border-blue-400 hover:text-blue-600 transition"><i class="fa fa-tag mr-1"></i> Offers</a>
    </div>
</div>

<a href="index.php" class="btn btn-blue"><i class="fa fa-arrow-left mr-2"></i> Go Back Home</a>
</div>
</section>
<?php include "footer.php"; ?>
</body>
</html>
