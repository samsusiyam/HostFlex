<?php
$page_title = 'Blog Posts';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

$msg = '';
$error = '';

// Handle AJAX Quick Toggle Status
if (isset($_POST['ajax_toggle_status'])) {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $curr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status, title FROM blog_posts WHERE id = $id"));
        if ($curr) {
            $new_val = (int)$curr['status'] === 1 ? 0 : 1;
            mysqli_query($conn, "UPDATE blog_posts SET status = $new_val WHERE id = $id");
            logActivity('Toggled Blog Status', ($curr['title'] ?? 'Post') . " -> " . ($new_val ? 'Published' : 'Draft') . " (ID: $id)");
            echo json_encode(['success' => true, 'new_val' => $new_val]);
            exit;
        }
    }
    echo json_encode(['success' => false]);
    exit;
}

// Handle Delete via POST (Modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post_id'])) {
    $id = (int)$_POST['delete_post_id'];
    $post = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image, title FROM blog_posts WHERE id = $id"));
    if ($post && $post['image'] && file_exists('../' . $post['image'])) {
        @unlink('../' . $post['image']);
    }
    if (mysqli_query($conn, "DELETE FROM blog_posts WHERE id = $id")) {
        logActivity('Deleted Post', ($post['title'] ?? 'Unknown') . ' (ID: ' . $id . ')');
        $msg = 'Post "' . htmlspecialchars($post['title'] ?? '') . '" permanently deleted.';
    } else {
        $error = 'Failed to delete post: ' . mysqli_error($conn);
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') $msg = 'Blog post created successfully!';
    elseif ($_GET['msg'] === 'updated') $msg = 'Blog post updated successfully!';
    elseif ($_GET['msg'] === 'not_found') $error = 'Blog post not found.';
}

// Counts for Status Tabs
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

<div class="space-y-6">
    
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-newspaper"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Blog Posts</h1>
            </div>
            <p class="text-xs text-gray-500">Manage, write, organize, and optimize your blog publications and guides.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="blog-edit.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs cursor-pointer">
                <i class="fa-solid fa-pen-nib"></i> Write New Post
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($msg): ?>
    <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between bg-emerald-50 text-emerald-800 border border-emerald-200">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span><?php echo htmlspecialchars($msg); ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 cursor-pointer"><i class="fa-solid fa-xmark text-sm"></i></button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between bg-red-50 text-red-800 border border-red-200">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-red-600 text-sm"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 cursor-pointer"><i class="fa-solid fa-xmark text-sm"></i></button>
    </div>
    <?php endif; ?>

    <!-- Status Tabs & Filter Controls -->
    <div class="bg-white p-4 rounded-2xl border border-gray-200/80 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        
        <!-- Status Filter Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto w-full md:w-auto pb-1 md:pb-0 text-xs font-semibold">
            <a href="blogs.php<?php echo $search ? '?search=' . urlencode($search) : ''; ?>" 
               class="px-3.5 py-1.5 rounded-xl transition <?php echo $status_filter === 'all' ? 'bg-blue-600 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100'; ?>">
                All Posts (<?php echo $total_all; ?>)
            </a>
            <a href="blogs.php?status=published<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
               class="px-3.5 py-1.5 rounded-xl transition <?php echo $status_filter === 'published' ? 'bg-blue-600 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100'; ?>">
                Published (<?php echo $total_pub; ?>)
            </a>
            <a href="blogs.php?status=draft<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
               class="px-3.5 py-1.5 rounded-xl transition <?php echo $status_filter === 'draft' ? 'bg-blue-600 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100'; ?>">
                Drafts (<?php echo $total_draft; ?>)
            </a>
        </div>

        <!-- Filter Form -->
        <form method="GET" class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
            <?php if ($status_filter !== 'all'): ?>
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
            <?php endif; ?>
            
            <!-- Category Filter Dropdown -->
            <select name="cat" onchange="this.form.submit()" class="border border-gray-200 rounded-xl px-3 py-1.5 text-xs focus:border-blue-600 focus:outline-none bg-gray-50 text-gray-700 font-medium">
                <option value="0">All Categories</option>
                <?php foreach ($all_cats as $c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo $category_filter == $c['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>

            <!-- Search Bar -->
            <div class="relative flex-1 sm:w-56">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search posts..." 
                       class="w-full pl-8 pr-3 py-1.5 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:border-blue-600 transition">
                <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
            </div>

            <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold px-3 py-1.5 rounded-xl text-xs transition cursor-pointer">
                Filter
            </button>

            <?php if ($search || $category_filter > 0 || $status_filter !== 'all'): ?>
            <a href="blogs.php" class="text-xs text-rose-600 hover:underline font-semibold ml-1">
                <i class="fa-solid fa-xmark mr-0.5"></i> Reset
            </a>
            <?php endif; ?>
        </form>

    </div>

    <!-- Posts Table -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <div class="text-xs text-gray-500 font-semibold">
                Showing <strong class="text-gray-900"><?php echo mysqli_num_rows($posts); ?></strong> of <strong class="text-gray-900"><?php echo $total_filtered; ?></strong> articles
            </div>
            <a href="blog-categories.php" class="text-xs text-blue-600 hover:underline font-semibold flex items-center gap-1">
                <i class="fa-solid fa-folder-tree text-[11px]"></i> Manage Categories
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/70 border-b border-gray-200 text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5 w-14 text-center">Image</th>
                        <th class="px-4 py-3.5">Post Title</th>
                        <th class="px-4 py-3.5">Category</th>
                        <th class="px-4 py-3.5">Author</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5">Date</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    <?php if (mysqli_num_rows($posts) > 0): ?>
                        <?php while ($post = mysqli_fetch_assoc($posts)): 
                            $post_url = getBlogPostUrl($post);
                        ?>
                        <tr class="hover:bg-blue-50/20 transition group">
                            
                            <!-- Thumbnail -->
                            <td class="px-4 py-3.5 text-center">
                                <?php if (!empty($post['image'])): ?>
                                    <img src="/<?php echo ltrim($post['image'], '/'); ?>" alt="" class="w-10 h-10 object-cover rounded-xl border border-gray-200 shadow-xs inline-block">
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-xl bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-400 text-xs inline-block">
                                        <i class="fa-solid fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Title + Slug -->
                            <td class="px-4 py-3.5 font-bold text-gray-900">
                                <a href="blog-edit.php?id=<?php echo $post['id']; ?>" class="hover:text-blue-600 transition block text-sm">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                                <div class="text-[11px] text-gray-400 font-mono font-normal truncate max-w-sm mt-0.5">
                                    <?php echo htmlspecialchars($post['slug']); ?>
                                </div>
                            </td>

                            <!-- Category -->
                            <td class="px-4 py-3.5">
                                <?php if (!empty($post['category_name'])): ?>
                                    <a href="blogs.php?cat=<?php echo $post['category_id']; ?>" class="inline-block bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-100 px-2 py-0.5 rounded-lg text-[11px] font-bold transition">
                                        <?php echo htmlspecialchars($post['category_name']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400 italic">Uncategorized</span>
                                <?php endif; ?>
                            </td>

                            <!-- Author -->
                            <td class="px-4 py-3.5 text-gray-600 font-medium">
                                <span class="inline-flex items-center gap-1">
                                    <i class="fa-solid fa-user-circle text-gray-400 text-xs"></i>
                                    <?php echo htmlspecialchars($post['author'] ?: 'Admin'); ?>
                                </span>
                            </td>

                            <!-- 1-Click AJAX Status Toggle -->
                            <td class="px-4 py-3.5">
                                <button type="button" onclick="togglePostStatus(<?php echo $post['id']; ?>, this)" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition <?php echo $post['status'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'; ?>" title="Click to toggle Published/Draft">
                                    <span class="w-1.5 h-1.5 rounded-full <?php echo $post['status'] ? 'bg-emerald-500' : 'bg-amber-500'; ?>"></span>
                                    <span><?php echo $post['status'] ? 'Published' : 'Draft'; ?></span>
                                </button>
                            </td>

                            <!-- Date -->
                            <td class="px-4 py-3.5 text-gray-500 whitespace-nowrap text-[11px]">
                                <div class="font-bold text-gray-800"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></div>
                                <div class="text-gray-400"><?php echo date('h:i A', strtotime($post['created_at'])); ?></div>
                            </td>

                            <!-- Action Buttons -->
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="<?php echo htmlspecialchars($post_url); ?>" target="_blank" class="p-1.5 bg-gray-50 hover:bg-emerald-50 text-emerald-600 rounded-lg border border-gray-200 hover:border-emerald-200 transition cursor-pointer" title="View Live Post">
                                        <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                                    </a>
                                    <a href="blog-edit.php?id=<?php echo $post['id']; ?>" class="p-1.5 bg-gray-50 hover:bg-blue-50 text-blue-600 rounded-lg border border-gray-200 hover:border-blue-200 transition cursor-pointer" title="Edit Article">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </a>
                                    <button type="button" onclick="openDeleteBlogModal(<?php echo $post['id']; ?>, '<?php echo addslashes($post['title']); ?>', '<?php echo addslashes($post['image'] ?? ''); ?>', '<?php echo date('M d, Y', strtotime($post['created_at'])); ?>')" class="p-1.5 bg-gray-50 hover:bg-red-50 text-red-600 rounded-lg border border-gray-200 hover:border-red-200 transition cursor-pointer" title="Delete Post">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </div>
                            </td>

                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center text-gray-400">
                                <i class="fa-solid fa-newspaper text-4xl mb-3 block text-gray-300"></i>
                                <p class="text-base font-bold text-gray-700">No blog posts found</p>
                                <p class="text-xs text-gray-500 mt-1">Get started by creating your very first article!</p>
                                <a href="blog-edit.php" class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2 rounded-xl mt-4 shadow-xs transition cursor-pointer">
                                    <i class="fa-solid fa-pen-nib"></i> Write First Post
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
            <span class="text-xs text-gray-500 font-semibold">
                Page <?php echo $page; ?> of <?php echo $pages; ?>
            </span>
            <div class="flex items-center gap-1">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <?php 
                    $query_params = $_GET;
                    $query_params['p'] = $i;
                    $page_url = 'blogs.php?' . http_build_query($query_params);
                    ?>
                    <a href="<?php echo $page_url; ?>" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?php echo $i == $page ? 'bg-blue-600 text-white shadow-xs' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-100'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- ==========================================
     POPUP MODAL: DELETE CONFIRMATION
=============================================== -->
<div id="deleteBlogModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST">
            <input type="hidden" name="delete_post_id" id="delete_post_id_input" value="">
            
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Delete Blog Article?</h3>
                <p class="text-xs text-gray-500 mb-4">Are you sure you want to permanently delete this post? This cannot be undone.</p>
                
                <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-200 text-xs text-left mb-2 flex items-center gap-3">
                    <div class="w-12 h-12 rounded-lg bg-gray-200 border border-gray-300 shrink-0 overflow-hidden flex items-center justify-center" id="deletePostImgBox">
                        <i class="fa-solid fa-image text-gray-400"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-bold text-gray-900 truncate text-xs sm:text-sm" id="deletePostTitle">Post Title</div>
                        <div class="text-[11px] text-gray-400 mt-0.5" id="deletePostDate">Date</div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="closeDeleteBlogModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-trash-can"></i> Yes, Delete Post
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function togglePostStatus(id, btn) {
    var fd = new FormData();
    fd.append('ajax_toggle_status', '1');
    fd.append('id', id);

    fetch('blogs.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (data.new_val === 1) {
                btn.className = 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition bg-emerald-50 text-emerald-700 border border-emerald-200';
                btn.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span>Published</span>';
            } else {
                btn.className = 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition bg-amber-50 text-amber-700 border border-amber-200';
                btn.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span><span>Draft</span>';
            }
        }
    });
}

function openDeleteBlogModal(id, title, img, date) {
    document.getElementById('delete_post_id_input').value = id;
    document.getElementById('deletePostTitle').innerText = title;
    document.getElementById('deletePostDate').innerText = 'Created: ' + date;
    
    var imgBox = document.getElementById('deletePostImgBox');
    if (img) {
        imgBox.innerHTML = '<img src="/' + img.replace(/^\/+/, '') + '" class="w-full h-full object-cover">';
    } else {
        imgBox.innerHTML = '<i class="fa-solid fa-image text-gray-400"></i>';
    }
    
    document.getElementById('deleteBlogModal').classList.remove('hidden');
}

function closeDeleteBlogModal() {
    document.getElementById('deleteBlogModal').classList.add('hidden');
}

// Keyboard ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDeleteBlogModal();
    }
});
</script>

<?php include 'footer.php'; ?>
