<?php
$page_title = 'Permalink Settings';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminRole(['admin']);

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blog_permalink_structure = sanitize(trim($_POST['blog_permalink_structure'] ?? 'post_name'));
    $allowed_structures = ['post_name', 'category_post_name', 'category_post_id', 'post_id'];
    if (!in_array($blog_permalink_structure, $allowed_structures)) {
        $blog_permalink_structure = 'post_name';
    }

    // Save to settings
    $check = mysqli_query($conn, "SELECT id FROM settings WHERE setting_key = 'blog_permalink_structure'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE settings SET setting_value = '$blog_permalink_structure' WHERE setting_key = 'blog_permalink_structure'");
    } else {
        mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('blog_permalink_structure', '$blog_permalink_structure')");
    }

    logActivity('Updated Permalink Settings', "Blog Structure: $blog_permalink_structure");
    header('Location: settings-permalinks.php?s=1');
    exit;
}

if (isset($_GET['s'])) {
    $msg = 'Permalink settings updated successfully!';
}

$current_structure = getSetting('blog_permalink_structure') ?: 'post_name';
$site_url = getSiteUrl();
?>
<?php include 'header.php'; ?>

<div class="mb-6">
    <div class="flex items-center gap-3">
        <h1 class="text-2xl font-bold text-gray-900">Permalink Settings</h1>
    </div>
    <p class="text-gray-500 text-xs md:text-sm mt-1">Configure custom URL routing structures for your blog articles and categories</p>
</div>

<?php if ($msg): ?>
<div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg text-sm mb-6 flex items-center gap-2 shadow-sm">
    <i class="fa fa-check-circle text-green-500"></i>
    <span><?php echo $msg; ?></span>
</div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 md:p-8 max-w-4xl">
    <form method="POST">
        <h2 class="text-base font-bold text-gray-900 mb-2 flex items-center gap-2">
            <i class="fa fa-link text-blue-600"></i> Blog Post URL Structure
        </h2>
        <p class="text-xs md:text-sm text-gray-500 mb-6">
            Choose how your blog URLs appear to visitors and search engines. Changing this setting will automatically route all blog links to the selected structure.
        </p>

        <div class="space-y-4 mb-8">
            
            <!-- Structure 1: Post name (Default) -->
            <label class="flex items-start gap-3 p-4 rounded-xl border border-gray-200 hover:border-blue-500 hover:bg-blue-50/20 cursor-pointer transition <?php echo $current_structure === 'post_name' ? 'border-blue-600 bg-blue-50/30' : ''; ?>">
                <input type="radio" name="blog_permalink_structure" value="post_name" <?php echo $current_structure === 'post_name' ? 'checked' : ''; ?> class="mt-1 text-blue-600 focus:ring-blue-500">
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-gray-900">Post Name (Recommended for SEO)</span>
                        <span class="text-[11px] bg-green-100 text-green-700 font-bold px-2 py-0.5 rounded">Default</span>
                    </div>
                    <p class="text-xs font-mono text-gray-500 mt-1">
                        <code><?php echo htmlspecialchars($site_url); ?>blog/<strong>%postname%</strong></code>
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Example: <code><?php echo htmlspecialchars($site_url); ?>blog/how-to-choose-best-web-hosting</code></p>
                </div>
            </label>

            <!-- Structure 2: Category and Post name -->
            <label class="flex items-start gap-3 p-4 rounded-xl border border-gray-200 hover:border-blue-500 hover:bg-blue-50/20 cursor-pointer transition <?php echo $current_structure === 'category_post_name' ? 'border-blue-600 bg-blue-50/30' : ''; ?>">
                <input type="radio" name="blog_permalink_structure" value="category_post_name" <?php echo $current_structure === 'category_post_name' ? 'checked' : ''; ?> class="mt-1 text-blue-600 focus:ring-blue-500">
                <div class="flex-1">
                    <span class="text-sm font-bold text-gray-900">Category and Post Name</span>
                    <p class="text-xs font-mono text-gray-500 mt-1">
                        <code><?php echo htmlspecialchars($site_url); ?>blog/<strong>%category%</strong>/<strong>%postname%</strong></code>
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Example: <code><?php echo htmlspecialchars($site_url); ?>blog/hosting-tips/how-to-choose-best-web-hosting</code></p>
                </div>
            </label>

            <!-- Structure 3: Category and Post ID -->
            <label class="flex items-start gap-3 p-4 rounded-xl border border-gray-200 hover:border-blue-500 hover:bg-blue-50/20 cursor-pointer transition <?php echo $current_structure === 'category_post_id' ? 'border-blue-600 bg-blue-50/30' : ''; ?>">
                <input type="radio" name="blog_permalink_structure" value="category_post_id" <?php echo $current_structure === 'category_post_id' ? 'checked' : ''; ?> class="mt-1 text-blue-600 focus:ring-blue-500">
                <div class="flex-1">
                    <span class="text-sm font-bold text-gray-900">Category and Post ID</span>
                    <p class="text-xs font-mono text-gray-500 mt-1">
                        <code><?php echo htmlspecialchars($site_url); ?>blog/<strong>%category%</strong>/<strong>%post_id%</strong></code>
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Example: <code><?php echo htmlspecialchars($site_url); ?>blog/hosting-tips/12</code></p>
                </div>
            </label>

            <!-- Structure 4: Numeric / ID -->
            <label class="flex items-start gap-3 p-4 rounded-xl border border-gray-200 hover:border-blue-500 hover:bg-blue-50/20 cursor-pointer transition <?php echo $current_structure === 'post_id' ? 'border-blue-600 bg-blue-50/30' : ''; ?>">
                <input type="radio" name="blog_permalink_structure" value="post_id" <?php echo $current_structure === 'post_id' ? 'checked' : ''; ?> class="mt-1 text-blue-600 focus:ring-blue-500">
                <div class="flex-1">
                    <span class="text-sm font-bold text-gray-900">Numeric Post ID</span>
                    <p class="text-xs font-mono text-gray-500 mt-1">
                        <code><?php echo htmlspecialchars($site_url); ?>blog/<strong>%post_id%</strong></code>
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Example: <code><?php echo htmlspecialchars($site_url); ?>blog/12</code></p>
                </div>
            </label>

        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-sm transition flex items-center gap-2">
            <i class="fa fa-save"></i> Save Changes
        </button>
    </form>
</div>

<?php include 'footer.php'; ?>
