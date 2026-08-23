<?php
$page_title = 'Blog Posts';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

$msg = '';
$error = '';

// Handle Delete Post
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $post = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image, title FROM blog_posts WHERE id = $id"));
    if ($post && $post['image'] && file_exists('../' . $post['image'])) {
        @unlink('../' . $post['image']);
    }
    mysqli_query($conn, "DELETE FROM blog_posts WHERE id = $id");
    logActivity('Deleted Post', ($post['title'] ?? 'Unknown') . ' (ID: ' . $id . ')');
    header('Location: blogs.php?msg=deleted');
    exit;
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') $msg = 'Post permanently moved to Trash / deleted.';
    elseif ($_GET['msg'] === 'added') $msg = 'Post created successfully!';
    elseif ($_GET['msg'] === 'updated') $msg = 'Post updated successfully!';
    elseif ($_GET['msg'] === 'not_found') $error = 'Post not found.';
}

// Counts for WordPress Status Tabs
$total_all = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM blog_posts"))['c'] ?? 0;
$total_pub = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM blog_posts WHERE status = 1"))['c'] ?? 0;
$total_draft = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM blog_posts WHERE status = 0"))['c'] ?? 0;

// Filter criteria
$status_filter = isset($_GET['status']) ? (string)$_GET['status'] : 'all';
$category_filter = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$search = trim($_GET['search'] ?? '');

$where_clauses = [];
if ($status_filter === 'published') {
    $where_clauses[] = "p.status = 1";
} elseif ($status_filter === 'draft') {
    $where_clauses[] = "p.status = 0";
}

if ($category_filter > 0) {
    $where_clauses[] = "p.category_id = $category_filter";
}

if (!empty($search)) {
    $search_esc = mysqli_real_escape_string($conn, $search);
    $where_clauses[] = "(p.title LIKE '%$search_esc%' OR p.slug LIKE '%$search_esc%' OR p.content LIKE '%$search_esc%')";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Pagination
$page = max(1, (int)($_GET['p'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

$count_query = mysqli_query($conn, "SELECT COUNT(*) as c FROM blog_posts p $where_sql");
$total_filtered = mysqli_fetch_assoc($count_query)['c'] ?? 0;
$pages = ceil($total_filtered / $per_page);

$posts_query = "SELECT p.*, c.name as category_name, c.slug as category_slug 
                FROM blog_posts p 
                LEFT JOIN blog_categories c ON p.category_id = c.id 
                $where_sql 
                ORDER BY p.created_at DESC 
                LIMIT $per_page OFFSET $offset";
$posts = mysqli_query($conn, $posts_query);

// Fetch all categories for filter dropdown
$categories_res = mysqli_query($conn, "SELECT * FROM blog_categories WHERE status = 1 ORDER BY name ASC");
$all_cats = [];
while ($c = mysqli_fetch_assoc($categories_res)) {
    $all_cats[] = $c;
}
?>
<?php include 'header.php'; ?>

<!-- Top Heading & Actions -->
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900">Blog Posts</h1>
            <a href="blog-edit.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3.5 py-1.5 rounded-lg text-xs md:text-sm shadow-sm transition inline-flex items-center gap-1.5">
                <i class="fa fa-plus text-xs"></i> Add New Post
            </a>
        </div>
        <p class="text-gray-500 text-xs md:text-sm mt-1">Manage, write, and optimize your blog publications</p>
    </div>
</div>

<?php if ($msg): ?>
<div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg text-sm mb-6 flex items-center gap-2 shadow-sm">
    <i class="fa fa-check-circle text-green-500"></i>
    <span><?php echo $msg; ?></span>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg text-sm mb-6 flex items-center gap-2 shadow-sm">
    <i class="fa fa-circle-exclamation text-red-500"></i>
    <span><?php echo $error; ?></span>
</div>
<?php endif; ?>

<!-- WordPress Status Filter Tabs -->
<div class="flex flex-wrap items-center gap-2 text-xs md:text-sm font-medium mb-4 text-gray-600 border-b pb-3">
    <a href="blogs.php<?php echo $search ? '?search=' . urlencode($search) : ''; ?>" 
       class="px-3 py-1 rounded-md <?php echo $status_filter === 'all' ? 'bg-blue-50 text-blue-700 font-bold' : 'hover:text-blue-600'; ?>">
        All <span class="text-xs text-gray-400 font-normal">(<?php echo $total_all; ?>)</span>
    </a>
    <span class="text-gray-300">|</span>
    <a href="blogs.php?status=published<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
       class="px-3 py-1 rounded-md <?php echo $status_filter === 'published' ? 'bg-blue-50 text-blue-700 font-bold' : 'hover:text-blue-600'; ?>">
        Published <span class="text-xs text-gray-400 font-normal">(<?php echo $total_pub; ?>)</span>
    </a>
    <span class="text-gray-300">|</span>
    <a href="blogs.php?status=draft<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
       class="px-3 py-1 rounded-md <?php echo $status_filter === 'draft' ? 'bg-blue-50 text-blue-700 font-bold' : 'hover:text-blue-600'; ?>">
        Drafts <span class="text-xs text-gray-400 font-normal">(<?php echo $total_draft; ?>)</span>
    </a>
</div>

<!-- Filter Bar -->
<div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
    <form method="GET" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
        <?php if ($status_filter !== 'all'): ?>
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
        <?php endif; ?>
        
        <!-- Category Filter -->
        <select name="cat" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-3 py-2 text-xs focus:border-blue-600 focus:outline-none bg-white">
            <option value="0">All Categories</option>
            <?php foreach ($all_cats as $c): ?>
            <option value="<?php echo $c['id']; ?>" <?php echo $category_filter == $c['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($c['name']); ?>
            </option>
            <?php endforeach; ?>
        </select>

        <!-- Search Bar -->
        <div class="relative flex-1 sm:w-64">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search posts..." 
                   class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg text-xs focus:border-blue-600 focus:outline-none">
            <i class="fa fa-search absolute left-2.5 top-2.5 text-gray-400 text-xs"></i>
        </div>

        <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-4 py-2 rounded-lg text-xs transition">
            Filter
        </button>

        <?php if ($search || $category_filter > 0 || $status_filter !== 'all'): ?>
        <a href="blogs.php" class="text-xs text-red-600 hover:underline">
            <i class="fa fa-times mr-1"></i> Reset Filters
        </a>
        <?php endif; ?>
    </form>

    <div class="text-xs text-gray-500 font-medium whitespace-nowrap">
        Showing <?php echo mysqli_num_rows($posts); ?> of <?php echo $total_filtered; ?> items
    </div>
</div>

<!-- Posts Table (WordPress Style) -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-gray-50/75 border-b border-gray-200 text-xs uppercase font-bold text-gray-500 tracking-wider">
                <tr>
                    <th scope="col" class="py-3.5 px-4 w-12 text-center">Image</th>
                    <th scope="col" class="py-3.5 px-4">Title</th>
                    <th scope="col" class="py-3.5 px-4">Author</th>
                    <th scope="col" class="py-3.5 px-4">Categories</th>
                    <th scope="col" class="py-3.5 px-4">Status</th>
                    <th scope="col" class="py-3.5 px-4">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (mysqli_num_rows($posts) > 0): ?>
                    <?php while ($post = mysqli_fetch_assoc($posts)): ?>
                    <tr class="hover:bg-blue-50/30 transition group">
                        <!-- Featured Image Thumbnail -->
                        <td class="py-3 px-4 text-center">
                            <?php if (!empty($post['image'])): ?>
                                <img src="/<?php echo ltrim($post['image'], '/'); ?>" alt="" class="w-10 h-10 object-cover rounded-lg border border-gray-200 shadow-xs inline-block">
                            <?php else: ?>
                                <div class="w-10 h-10 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-400 text-xs inline-block">
                                    <i class="fa fa-image"></i>
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- Title + WordPress Quick Action Links -->
                        <td class="py-3 px-4">
                            <a href="blog-edit.php?id=<?php echo $post['id']; ?>" class="font-bold text-gray-900 hover:text-blue-600 text-sm block">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                            
                            <!-- Hover Quick Action Row (WordPress Style) -->
                            <div class="flex items-center gap-2 mt-1 text-xs opacity-80 group-hover:opacity-100 transition">
                                <a href="blog-edit.php?id=<?php echo $post['id']; ?>" class="text-blue-600 hover:text-blue-800 font-medium">Edit</a>
                                <span class="text-gray-300">|</span>
                                <a href="<?php echo getBlogPostUrl($post); ?>" target="_blank" class="text-emerald-600 hover:text-emerald-800 font-medium">View</a>
                                <span class="text-gray-300">|</span>
                                <a href="blogs.php?delete=<?php echo $post['id']; ?>" onclick="return confirm('Are you sure you want to delete this post?')" class="text-red-600 hover:text-red-800 font-medium">Trash</a>
                            </div>
                        </td>

                        <!-- Author -->
                        <td class="py-3 px-4 text-xs font-medium text-gray-700">
                            <?php echo htmlspecialchars($post['author'] ?: 'Admin'); ?>
                        </td>

                        <!-- Categories -->
                        <td class="py-3 px-4 text-xs">
                            <?php if (!empty($post['category_name'])): ?>
                                <a href="blogs.php?cat=<?php echo $post['category_id']; ?>" class="inline-block bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-xs font-medium transition">
                                    <?php echo htmlspecialchars($post['category_name']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-gray-400 italic">Uncategorized</span>
                            <?php endif; ?>
                        </td>

                        <!-- Status Badge -->
                        <td class="py-3 px-4">
                            <?php if ($post['status']): ?>
                                <span class="inline-flex items-center gap-1 bg-green-50 text-green-700 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-green-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Published
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-gray-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Draft
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Date -->
                        <td class="py-3 px-4 text-xs text-gray-500 whitespace-nowrap">
                            <div class="font-medium text-gray-800"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></div>
                            <div class="text-[11px] text-gray-400"><?php echo date('h:i A', strtotime($post['created_at'])); ?></div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="py-12 text-center text-gray-400">
                            <i class="fa fa-newspaper text-4xl mb-3 block text-gray-300"></i>
                            <p class="text-base font-semibold text-gray-700">No blog posts found</p>
                            <p class="text-xs text-gray-500 mt-1">Get started by creating your very first article!</p>
                            <a href="blog-edit.php" class="inline-flex items-center gap-1.5 bg-blue-600 text-white text-xs font-bold px-4 py-2 rounded-lg mt-4 shadow-sm hover:bg-blue-700 transition">
                                <i class="fa fa-plus"></i> Create Post
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="p-4 border-t border-gray-200 flex items-center justify-between bg-gray-50/50">
        <span class="text-xs text-gray-500">
            Page <?php echo $page; ?> of <?php echo $pages; ?>
        </span>
        <div class="flex items-center gap-1">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <?php 
                $query_params = $_GET;
                $query_params['p'] = $i;
                $page_url = 'blogs.php?' . http_build_query($query_params);
                ?>
                <a href="<?php echo $page_url; ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?php echo $i == $page ? 'bg-blue-600 text-white shadow-xs' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-100'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
