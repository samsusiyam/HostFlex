<?php require_once 'config/database.php'; require_once 'includes/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include "cdnjs.php"; ?>
<title>404 - Page Not Found</title>
<style>
.error-bg { position: relative; overflow: hidden; }
.error-bg::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(59,130,246,0.05) 0%, transparent 60%);
    animation: pulse-slow 6s ease-in-out infinite;
}
@keyframes pulse-slow { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
.float-letter {
    display: inline-block;
    animation: float-up 2s ease-in-out infinite;
}
@keyframes float-up {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}
</style>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
<?php include "header.php"; ?>

<section class="error-bg min-h-[70vh] flex items-center justify-center px-4">
<div class="text-center max-w-xl mx-auto relative z-10">
    <div class="mb-6">
        <span class="float-letter text-[100px] md:text-[140px] font-extrabold text-blue-600 dark:text-blue-400 leading-none" style="animation-delay: 0s;">4</span>
        <span class="float-letter text-[100px] md:text-[140px] font-extrabold text-blue-400 dark:text-blue-300 leading-none" style="animation-delay: 0.3s;">0</span>
        <span class="float-letter text-[100px] md:text-[140px] font-extrabold text-blue-600 dark:text-blue-400 leading-none" style="animation-delay: 0.6s;">4</span>
    </div>

    <h2 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-gray-100 mb-3">Oops! Page Not Found</h2>
    <p class="text-gray-500 dark:text-gray-400 mb-8 text-lg">The page you're looking for doesn't exist or has been moved.</p>

    <div class="flex flex-wrap justify-center gap-3 mb-8">
        <a href="index.php" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-full font-medium transition shadow-lg shadow-blue-500/25">
            <i class="fa fa-home"></i> Go Home
        </a>
        <a href="javascript:history.back()" class="inline-flex items-center gap-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 px-6 py-3 rounded-full font-medium hover:border-blue-400 hover:text-blue-600 transition">
            <i class="fa fa-arrow-left"></i> Go Back
        </a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 max-w-md mx-auto">
        <a href="index.php" class="flex flex-col items-center gap-1.5 p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 transition group">
            <i class="fa fa-home text-gray-400 group-hover:text-blue-600 transition"></i>
            <span class="text-xs text-gray-500 dark:text-gray-400 group-hover:text-blue-600">Home</span>
        </a>
        <a href="blogs.php" class="flex flex-col items-center gap-1.5 p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 transition group">
            <i class="fa fa-blog text-gray-400 group-hover:text-blue-600 transition"></i>
            <span class="text-xs text-gray-500 dark:text-gray-400 group-hover:text-blue-600">Blog</span>
        </a>
        <a href="contact.php" class="flex flex-col items-center gap-1.5 p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 transition group">
            <i class="fa fa-envelope text-gray-400 group-hover:text-blue-600 transition"></i>
            <span class="text-xs text-gray-500 dark:text-gray-400 group-hover:text-blue-600">Contact</span>
        </a>
        <a href="offers.php" class="flex flex-col items-center gap-1.5 p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 transition group">
            <i class="fa fa-tag text-gray-400 group-hover:text-blue-600 transition"></i>
            <span class="text-xs text-gray-500 dark:text-gray-400 group-hover:text-blue-600">Offers</span>
        </a>
    </div>
</div>
</section>

<?php include "footer.php"; ?>
</body>
</html>
