<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

ensureBlogSchema();

$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM users WHERE id = " . (int)$_SESSION['admin_id']));
$admin_username = $admin['username'] ?? 'Admin';

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = ($post_id > 0);
$page_title = $is_edit ? 'Edit Post' : 'Add New Post';

$msg = '';
$error = '';

// Handle AJAX category creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_new_category'])) {
    header('Content-Type: application/json');
    $name = sanitize($_POST['cat_name'] ?? '');
    if (!$name) {
        echo json_encode(['success' => false, 'message' => 'Category name is required']);
        exit;
    }
    $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $name)));
    $existing = mysqli_query($conn, "SELECT id FROM blog_categories WHERE slug = '$slug'");
    if (mysqli_num_rows($existing) > 0) {
        $row = mysqli_fetch_assoc($existing);
        echo json_encode(['success' => true, 'id' => $row['id'], 'name' => $name, 'existing' => true]);
        exit;
    }
    $res = mysqli_query($conn, "INSERT INTO blog_categories (name, slug, status) VALUES ('$name', '$slug', 1)");
    if ($res) {
        $new_id = mysqli_insert_id($conn);
        echo json_encode(['success' => true, 'id' => $new_id, 'name' => $name]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

// Fetch existing post data if editing
$post = [
    'id' => 0,
    'title' => '',
    'slug' => '',
    'content' => '',
    'excerpt' => '',
    'image' => '',
    'category_id' => 0,
    'author' => $admin_username,
    'status' => 1,
    'show_featured_image' => 1,
    'show_toc' => 1,
    'show_author' => 1,
    'meta_description' => '',
    'meta_keywords' => '',
    'created_at' => date('Y-m-d H:i:s')
];

if ($is_edit) {
    $res = mysqli_query($conn, "SELECT * FROM blog_posts WHERE id = $post_id");
    if ($res && mysqli_num_rows($res) > 0) {
        $post = mysqli_fetch_assoc($res);
    } else {
        header('Location: blogs.php?msg=not_found');
        exit;
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_post'])) {
    $title = sanitize($_POST['title'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    $content = $_POST['content'] ?? '';
    $excerpt = sanitize($_POST['excerpt'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $author = sanitize($_POST['author'] ?? $admin_username);
    $status = (int)($_POST['status'] ?? 1);
    $show_featured_image = isset($_POST['show_featured_image']) ? 1 : 0;
    $show_toc = isset($_POST['show_toc']) ? 1 : 0;
    $show_author = isset($_POST['show_author']) ? 1 : 0;
    $created_at = !empty($_POST['created_at']) ? date('Y-m-d H:i:s', strtotime($_POST['created_at'])) : date('Y-m-d H:i:s');
    $meta_description = sanitize($_POST['meta_description'] ?? '');
    $meta_keywords = sanitize($_POST['meta_keywords'] ?? '');

    // Generate slug from title if empty
    if (empty($slug) && !empty($title)) {
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $title)));
    } else {
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $slug)));
    }

    if (!$title) {
        $error = 'Please enter a post title.';
    } elseif (!$slug) {
        $error = 'Please provide a post URL slug.';
    } else {
        // Check slug uniqueness
        $slug_check_query = "SELECT id FROM blog_posts WHERE slug = '$slug'" . ($is_edit ? " AND id != $post_id" : "");
        $slug_check = mysqli_query($conn, $slug_check_query);
        if (mysqli_num_rows($slug_check) > 0) {
            $slug = $slug . '-' . time();
        }

        $content_esc = mysqli_real_escape_string($conn, $content);
        $cat_sql = $category_id > 0 ? $category_id : 'NULL';

        // Image upload handling
        $image = $post['image']; // Default to existing
        if (isset($_POST['remove_featured_image']) && $_POST['remove_featured_image'] == '1') {
            if ($image && file_exists('../' . $image)) {
                @unlink('../' . $image);
            }
            $image = '';
        }

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp','svg'];
            if (in_array($ext, $allowed)) {
                $upload_dir = '../uploads/blog/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $fname = 'blog_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $fname)) {
                    if ($post['image'] && file_exists('../' . $post['image'])) {
                        @unlink('../' . $post['image']);
                    }
                    $image = 'uploads/blog/' . $fname;
                }
            }
        }

        if ($is_edit) {
            $update_sql = "UPDATE blog_posts SET 
                title = '$title',
                slug = '$slug',
                content = '$content_esc',
                excerpt = '$excerpt',
                image = '$image',
                category_id = $cat_sql,
                author = '$author',
                status = $status,
                show_featured_image = $show_featured_image,
                show_toc = $show_toc,
                show_author = $show_author,
                created_at = '$created_at',
                meta_description = '$meta_description',
                meta_keywords = '$meta_keywords'
                WHERE id = $post_id";
            
            if (mysqli_query($conn, $update_sql)) {
                logActivity('Updated Blog Post', "$title (ID: $post_id)");
                header('Location: blog-edit.php?id=' . $post_id . '&msg=updated');
                exit;
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        } else {
            $insert_sql = "INSERT INTO blog_posts 
                (title, slug, content, excerpt, image, category_id, author, status, show_featured_image, show_toc, show_author, created_at, meta_description, meta_keywords) 
                VALUES 
                ('$title', '$slug', '$content_esc', '$excerpt', '$image', $cat_sql, '$author', $status, $show_featured_image, $show_toc, $show_author, '$created_at', '$meta_description', '$meta_keywords')";
            
            if (mysqli_query($conn, $insert_sql)) {
                $new_id = mysqli_insert_id($conn);
                logActivity('Created Blog Post', "$title (ID: $new_id)");
                header('Location: blog-edit.php?id=' . $new_id . '&msg=created');
                exit;
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        }
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') $msg = 'Post published successfully!';
    if ($_GET['msg'] === 'updated') $msg = 'Post updated successfully!';
    if ($_GET['msg'] === 'restored') $msg = 'Post restored successfully from Trash!';
}

// Handle Move to Trash from Edit Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_move_trash']) && $is_edit) {
    mysqli_query($conn, "UPDATE blog_posts SET deleted_at = NOW() WHERE id = $post_id");
    logActivity('Moved Post to Trash', ($post['title'] ?? 'Unknown') . ' (ID: ' . $post_id . ')');
    header("Location: blogs.php?status=trash");
    exit;
}

// Handle Restore from Edit Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_restore_trash']) && $is_edit) {
    mysqli_query($conn, "UPDATE blog_posts SET deleted_at = NULL WHERE id = $post_id");
    logActivity('Restored Post from Trash', ($post['title'] ?? 'Unknown') . ' (ID: ' . $post_id . ')');
    header("Location: blog-edit.php?id=$post_id&msg=restored");
    exit;
}

// Fetch categories
$categories_res = mysqli_query($conn, "SELECT * FROM blog_categories WHERE status = 1 ORDER BY name ASC");
$categories = [];
while ($cat = mysqli_fetch_assoc($categories_res)) {
    $categories[] = $cat;
}

// Fetch authors
$authors_res = mysqli_query($conn, "SELECT username FROM users ORDER BY username ASC");
$authors = [];
while ($a = mysqli_fetch_assoc($authors_res)) {
    $authors[] = $a['username'];
}
if (!in_array($admin_username, $authors)) {
    $authors[] = $admin_username;
}

$site_url = getSiteUrl();
?>
<?php include 'header.php'; ?>

<!-- WordPress Style Editor Header -->
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b pb-4">
    <div class="flex items-center gap-3">
        <a href="blogs.php" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-blue-600 bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-sm transition">
            <i class="fa fa-arrow-left text-xs"></i> All Posts
        </a>
        <h1 class="text-2xl font-bold text-gray-900"><?php echo $is_edit ? 'Edit Post' : 'Add New Post'; ?></h1>
        <?php if ($is_edit): ?>
            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full <?php echo $post['status'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'; ?>">
                <?php echo $post['status'] ? 'Published' : 'Draft'; ?>
            </span>
        <?php endif; ?>
    </div>
    
    <?php if ($is_edit && !empty($post['slug'])): ?>
    <div class="flex items-center gap-2">
        <a href="<?php echo getBlogPostUrl($post); ?>" target="_blank" class="inline-flex items-center gap-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 text-xs md:text-sm font-medium px-3.5 py-2 rounded-lg shadow-sm transition">
            <i class="fa fa-external-link-alt text-blue-600"></i> View Post
        </a>
    </div>
    <?php endif; ?>
</div>

<?php if ($is_edit && !empty($post['deleted_at'])): ?>
<div class="bg-rose-50 border-l-4 border-rose-500 text-rose-800 px-4 py-3 rounded-lg text-sm mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm">
    <div class="flex items-center gap-2">
        <i class="fa fa-trash-can text-rose-500 text-base"></i>
        <span>This article is currently in the <strong>Trash</strong> (Moved on <?php echo date('M d, Y', strtotime($post['deleted_at'])); ?>). It is hidden from your live website.</span>
    </div>
    <form method="POST" class="inline">
        <button type="submit" name="action_restore_trash" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5 shadow-xs cursor-pointer">
            <i class="fa fa-rotate-left"></i> Restore Article
        </button>
    </form>
</div>
<?php endif; ?>

<?php if ($msg): ?>
<div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg text-sm mb-6 flex items-center justify-between shadow-sm">
    <div class="flex items-center gap-2">
        <i class="fa fa-check-circle text-green-500 text-base"></i>
        <span><?php echo $msg; ?></span>
    </div>
    <?php if ($is_edit): ?>
    <a href="/blog/<?php echo htmlspecialchars($post['slug']); ?>" target="_blank" class="font-bold underline hover:text-green-900 text-xs">View Live Post &rarr;</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg text-sm mb-6 flex items-center gap-2 shadow-sm">
    <i class="fa fa-circle-exclamation text-red-500 text-base"></i>
    <span><?php echo $error; ?></span>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" id="postForm">
    <input type="hidden" name="save_post" value="1">
    <input type="hidden" name="remove_featured_image" id="removeFeaturedImage" value="0">
    <input type="hidden" name="status" id="postStatusInput" value="<?php echo $post['status']; ?>">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Main Column (Left 2/3) -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Title Input (WordPress Big Title Style) -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <input type="text" name="title" id="postTitle" value="<?php echo htmlspecialchars($post['title']); ?>" placeholder="Add title" required
                       class="w-full text-xl md:text-2xl font-bold text-gray-900 placeholder-gray-400 border-0 border-b-2 border-gray-200 focus:border-blue-600 focus:ring-0 px-0 pb-3 transition" autocomplete="off">
                
                <!-- WordPress Style Permalink Bar -->
                <div class="mt-3 text-xs text-gray-500 flex flex-wrap items-center gap-1.5 font-mono" id="permalinkBox">
                    <span class="font-sans text-gray-600 font-semibold">Permalink:</span>
                    <span class="text-gray-400"><?php echo htmlspecialchars($site_url); ?>blog/</span>
                    <span id="slugDisplay" class="bg-yellow-100 px-2 py-0.5 rounded text-gray-900 font-bold"><?php echo htmlspecialchars($post['slug'] ?: 'post-slug'); ?></span>
                    
                    <div id="slugEditGroup" class="hidden inline-flex items-center gap-1">
                        <input type="text" name="slug" id="slugInput" value="<?php echo htmlspecialchars($post['slug']); ?>" class="border border-blue-500 px-2 py-0.5 rounded text-xs text-gray-900 focus:outline-none w-48 font-mono">
                        <button type="button" onclick="saveSlugEdit()" class="bg-blue-600 text-white px-2 py-0.5 rounded text-xs hover:bg-blue-700">OK</button>
                        <button type="button" onclick="cancelSlugEdit()" class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-xs hover:bg-gray-300">Cancel</button>
                    </div>

                    <button type="button" id="slugEditBtn" onclick="toggleSlugEdit()" class="border border-gray-300 bg-gray-50 hover:bg-gray-100 text-gray-700 px-2 py-0.5 rounded text-xs ml-1 transition">
                        Edit
                    </button>
                </div>
            </div>

            <!-- Content Area with TinyMCE -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-3 bg-gray-50 border-b flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-600"><i class="fa fa-paragraph mr-1"></i> Post Content</span>
                        <span id="autoSaveIndicator" class="text-[11px] text-emerald-600 font-medium hidden"><i class="fa-solid fa-cloud-arrow-up"></i> Auto-saved locally</span>
                    </div>
                    <div class="text-xs text-gray-400 flex items-center gap-3 font-medium">
                        <span id="wordCount"><i class="fa-solid fa-pen-nib mr-1"></i>0 words</span>
                        <span id="readingTime"><i class="fa-regular fa-clock mr-1"></i>0 min read</span>
                        <span id="charCount">0 chars</span>
                    </div>
                </div>
                <div>
                    <textarea name="content" id="blogContent" rows="22"><?php echo htmlspecialchars($post['content']); ?></textarea>
                </div>
            </div>

            <!-- Excerpt Meta Box -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 bg-gray-50 border-b flex items-center justify-between cursor-pointer" onclick="$('#excerptBody').slideToggle(200)">
                    <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa fa-align-left text-gray-500"></i> Excerpt
                    </h3>
                    <i class="fa fa-chevron-down text-xs text-gray-400"></i>
                </div>
                <div id="excerptBody" class="p-5">
                    <textarea name="excerpt" id="postExcerpt" rows="3" placeholder="Write an optional hand-crafted summary of your post..." class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:border-blue-600 focus:outline-none"><?php echo htmlspecialchars($post['excerpt']); ?></textarea>
                    <p class="text-xs text-gray-400 mt-1.5">Excerpts are optional summaries displayed in blog cards and search engines.</p>
                </div>
            </div>

            <!-- Yoast / RankMath Style SEO Snippet Preview Box -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 bg-gray-50 border-b flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa fa-magnifying-glass text-blue-600"></i> Search Engine Optimization (SEO)
                    </h3>
                    <div class="flex items-center gap-1 bg-gray-200 p-0.5 rounded-lg text-xs">
                        <button type="button" onclick="switchSeoPreview('desktop')" id="btnSeoDesktop" class="px-2.5 py-1 rounded-md font-semibold bg-white text-gray-800 shadow-xs"><i class="fa fa-desktop mr-1"></i> Desktop</button>
                        <button type="button" onclick="switchSeoPreview('mobile')" id="btnSeoMobile" class="px-2.5 py-1 rounded-md font-semibold text-gray-600 hover:text-gray-900"><i class="fa fa-mobile-alt mr-1"></i> Mobile</button>
                    </div>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Google Snippet Simulation -->
                    <div class="p-4 bg-white border border-gray-200 rounded-xl shadow-xs" id="seoPreviewContainer">
                        <div class="text-xs text-gray-500 flex items-center gap-1 mb-1">
                            <span class="w-4 h-4 rounded-full bg-blue-600 text-white text-[9px] flex items-center justify-center font-bold">H</span>
                            <span class="text-gray-700 font-medium"><?php echo htmlspecialchars(getSetting('site_name') ?: 'Host Nibo'); ?></span>
                            <span>› blog ›</span>
                            <span id="seoSlugPreview" class="text-gray-500 font-mono"><?php echo htmlspecialchars($post['slug'] ?: 'your-post'); ?></span>
                        </div>
                        <h4 id="seoTitlePreview" class="text-blue-800 hover:underline text-lg font-medium leading-snug cursor-pointer mb-1">
                            <?php echo htmlspecialchars($post['title'] ?: 'Post Title Preview - ' . (getSetting('site_name') ?: 'Host Nibo')); ?>
                        </h4>
                        <p id="seoDescPreview" class="text-xs text-gray-600 leading-relaxed">
                            <?php echo htmlspecialchars($post['meta_description'] ?: ($post['excerpt'] ?: 'Provide a meta description by editing the snippet below. If you don\'t, search engines will try to find a relevant part of this post to show in the search results.')); ?>
                        </p>
                    </div>

                    <!-- SEO Meta Title -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">SEO Title</label>
                            <span id="seoTitleCount" class="text-xs text-gray-400">0 / 60 chars</span>
                        </div>
                        <input type="text" name="meta_keywords" id="seoTitleInput" value="<?php echo htmlspecialchars($post['meta_keywords']); ?>" placeholder="<?php echo htmlspecialchars($post['title'] ?: 'Leave blank to use main post title'); ?>"
                               class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:border-blue-600 focus:outline-none">
                        <div class="w-full bg-gray-100 h-1 rounded-full mt-1.5 overflow-hidden">
                            <div id="seoTitleBar" class="bg-green-500 h-full w-0 transition-all duration-300"></div>
                        </div>
                    </div>

                    <!-- SEO Meta Description -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">Meta Description</label>
                            <span id="seoDescCount" class="text-xs text-gray-400">0 / 160 chars</span>
                        </div>
                        <textarea name="meta_description" id="seoDescInput" rows="3" placeholder="Write a catchy 150-160 character description to boost search click-through rate..."
                                  class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:border-blue-600 focus:outline-none"><?php echo htmlspecialchars($post['meta_description']); ?></textarea>
                        <div class="w-full bg-gray-100 h-1 rounded-full mt-1.5 overflow-hidden">
                            <div id="seoDescBar" class="bg-green-500 h-full w-0 transition-all duration-300"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Sidebar Column (Right 1/3 - WordPress Meta Boxes) -->
        <div class="space-y-6">
            
            <!-- 1. Publish Box (WordPress Style) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 bg-gray-50 border-b flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa fa-cloud-arrow-up text-blue-600"></i> Publish
                    </h3>
                </div>
                <div class="p-5 space-y-4 text-sm">
                    <div class="flex items-center justify-between py-1 border-b border-gray-100">
                        <span class="text-gray-600 flex items-center gap-2"><i class="fa fa-key text-xs text-gray-400"></i> Status:</span>
                        <select id="statusSelect" onchange="updateStatus(this.value)" class="border border-gray-300 rounded-lg px-2.5 py-1 text-xs font-semibold text-gray-800 bg-white">
                            <option value="1" <?php echo $post['status'] == 1 ? 'selected' : ''; ?>>Published</option>
                            <option value="0" <?php echo $post['status'] == 0 ? 'selected' : ''; ?>>Draft</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between py-1 border-b border-gray-100">
                        <span class="text-gray-600 flex items-center gap-2"><i class="fa fa-eye text-xs text-gray-400"></i> Visibility:</span>
                        <span class="font-bold text-gray-800 text-xs">Public</span>
                    </div>

                    <div class="py-1 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 flex items-center gap-2"><i class="fa fa-calendar-alt text-xs text-gray-400"></i> Published on:</span>
                            <div class="flex items-center gap-1.5">
                                <span id="dateDisplay" class="text-xs font-semibold text-gray-800"><?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?></span>
                                <button type="button" onclick="$('#dateEditBox').slideToggle(150)" class="text-xs text-blue-600 hover:underline font-semibold ml-1">Edit</button>
                            </div>
                        </div>
                        <div id="dateEditBox" class="hidden mt-2 pt-2 border-t border-gray-100">
                            <input type="datetime-local" name="created_at" id="createdAtInput" value="<?php echo date('Y-m-d\TH:i', strtotime($post['created_at'])); ?>" class="w-full border border-gray-300 rounded px-2.5 py-1 text-xs text-gray-800 focus:outline-none focus:border-blue-600">
                            <div class="flex justify-end gap-1 mt-1.5">
                                <button type="button" onclick="applyDateEdit()" class="bg-blue-600 text-white px-2 py-0.5 rounded text-[11px] font-semibold">OK</button>
                                <button type="button" onclick="$('#dateEditBox').slideUp(150)" class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-[11px]">Cancel</button>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-2 flex flex-col gap-2">
                        <button type="submit" onclick="$('#postStatusInput').val(1)" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg shadow-sm transition flex items-center justify-center gap-2">
                            <i class="fa fa-check"></i> <?php echo $is_edit ? 'Update Post' : 'Publish Post'; ?>
                        </button>
                        
                        <button type="submit" onclick="$('#postStatusInput').val(0)" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-lg text-xs transition">
                            <i class="fa fa-save mr-1"></i> Save as Draft
                        </button>
                    </div>

                    <?php if ($is_edit): ?>
                    <div class="pt-2 border-t text-center">
                        <?php if (!empty($post['deleted_at'])): ?>
                        <button type="submit" name="action_restore_trash" class="text-xs text-emerald-600 hover:text-emerald-800 font-bold flex items-center justify-center gap-1 mx-auto cursor-pointer">
                            <i class="fa-solid fa-rotate-left mr-1"></i> Restore from Trash
                        </button>
                        <?php else: ?>
                        <button type="submit" name="action_move_trash" onclick="return confirm('Move this article to the Trash?')" class="text-xs text-rose-600 hover:text-rose-800 font-semibold flex items-center justify-center gap-1 mx-auto cursor-pointer">
                            <i class="fa-solid fa-trash-can mr-1"></i> Move to Trash
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. Categories Box (WordPress Style Checklists + Inline Adder) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 bg-gray-50 border-b flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa fa-tags text-emerald-600"></i> Categories
                    </h3>
                </div>
                <div class="p-5">
                    <!-- Categories Checklist -->
                    <div class="max-h-48 overflow-y-auto space-y-2 border border-gray-200 rounded-lg p-3 bg-gray-50/50 mb-3" id="categoriesList">
                        <label class="flex items-center gap-2.5 text-xs text-gray-700 hover:text-blue-600 cursor-pointer">
                            <input type="radio" name="category_id" value="0" <?php echo empty($post['category_id']) ? 'checked' : ''; ?> class="text-blue-600 focus:ring-blue-500">
                            <span class="italic text-gray-500">Uncategorized</span>
                        </label>
                        <?php
                        $categories = mysqli_query($conn, "SELECT * FROM blog_categories ORDER BY name ASC");
                        while ($cat = mysqli_fetch_assoc($categories)):
                        ?>
                        <label class="flex items-center gap-2.5 text-xs text-gray-700 hover:text-blue-600 cursor-pointer">
                            <input type="radio" name="category_id" value="<?php echo $cat['id']; ?>" <?php echo $post['category_id'] == $cat['id'] ? 'checked' : ''; ?> class="text-blue-600 focus:ring-blue-500">
                            <span><?php echo htmlspecialchars($cat['name']); ?></span>
                        </label>
                        <?php endwhile; ?>
                    </div>

                    <!-- Inline Add Category Form -->
                    <div class="pt-2 border-t">
                        <button type="button" onclick="$('#addCatBox').slideToggle(150)" class="text-xs font-semibold text-blue-600 hover:text-blue-800 flex items-center gap-1">
                            <i class="fa fa-plus text-[10px]"></i> Add New Category
                        </button>
                        <div id="addCatBox" class="hidden mt-3 space-y-2">
                            <input type="text" id="newCatName" placeholder="New category name" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-xs focus:border-blue-600 focus:outline-none">
                            <button type="button" onclick="ajaxAddCategory()" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-1.5 px-3 rounded-lg text-xs transition flex items-center justify-center gap-1">
                                <i class="fa fa-plus"></i> Add Category
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Featured Image Box (WordPress Style Image Upload) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 bg-gray-50 border-b flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa fa-image text-indigo-600"></i> Featured Image
                    </h3>
                </div>
                <div class="p-5 text-center">
                    <div id="imagePreviewArea" class="<?php echo !empty($post['image']) ? '' : 'hidden'; ?> mb-4">
                        <div class="relative group rounded-xl overflow-hidden border border-gray-200 shadow-xs bg-gray-100 max-h-48 flex items-center justify-center">
                            <img id="featuredImgPreview" src="/<?php echo ltrim($post['image'] ?? '', '/'); ?>" class="max-h-48 w-full object-cover">
                        </div>
                        <div class="mt-2 flex justify-center gap-3">
                            <button type="button" onclick="$('#featuredImageInput').click()" class="text-xs text-blue-600 hover:underline font-semibold">
                                Replace Image
                            </button>
                            <span class="text-gray-300">|</span>
                            <button type="button" onclick="removeFeaturedImage()" class="text-xs text-red-600 hover:underline font-semibold">
                                Remove featured image
                            </button>
                        </div>
                    </div>

                    <div id="imageUploadArea" class="<?php echo empty($post['image']) ? '' : 'hidden'; ?>">
                        <div onclick="$('#featuredImageInput').click()" class="border-2 border-dashed border-gray-300 hover:border-blue-500 rounded-xl p-6 cursor-pointer bg-gray-50/50 hover:bg-blue-50/30 transition text-center group">
                            <i class="fa fa-cloud-arrow-up text-3xl text-gray-400 group-hover:text-blue-600 mb-2 transition"></i>
                            <p class="text-xs font-bold text-gray-700 group-hover:text-blue-600">Set featured image</p>
                            <p class="text-[11px] text-gray-400 mt-1">PNG, JPG, WEBP up to 5MB</p>
                        </div>
                    </div>

                    <input type="file" name="image" id="featuredImageInput" accept="image/*" class="hidden" onchange="handleImageSelect(this)">
                    
                    <div class="mt-4 pt-3 border-t text-left">
                        <label class="flex items-start gap-2 cursor-pointer">
                            <input type="checkbox" name="show_featured_image" value="1" <?php echo ($post['show_featured_image'] ?? 1) ? 'checked' : ''; ?> class="rounded text-blue-600 focus:ring-blue-500 mt-0.5">
                            <div>
                                <span class="text-xs text-gray-800 font-semibold block">Show featured image on post</span>
                                <span class="text-[11px] text-gray-500 block leading-tight">If unchecked, image only appears in blog listing & social share</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- 4. Author Box -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 bg-gray-50 border-b flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa fa-user-pen text-gray-600"></i> Author
                    </h3>
                </div>
                <div class="p-5 space-y-3">
                    <select name="author" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:border-blue-600 focus:outline-none bg-white">
                        <?php foreach ($authors as $auth): ?>
                        <option value="<?php echo htmlspecialchars($auth); ?>" <?php echo $post['author'] === $auth ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($auth); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="pt-2 border-t text-left">
                        <label class="flex items-start gap-2 cursor-pointer">
                            <input type="checkbox" name="show_author" value="1" <?php echo ($post['show_author'] ?? 1) ? 'checked' : ''; ?> class="rounded text-blue-600 focus:ring-blue-500 mt-0.5">
                            <div>
                                <span class="text-xs text-gray-800 font-semibold block">Display author on post</span>
                                <span class="text-[11px] text-gray-500 block leading-tight">Uncheck to hide author name and bio card</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- 5. Post Display Options Box -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 bg-gray-50 border-b flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa fa-sliders text-blue-600"></i> Post Options
                    </h3>
                </div>
                <div class="p-5 space-y-3 text-left">
                    <label class="flex items-start gap-2 cursor-pointer">
                        <input type="checkbox" name="show_toc" value="1" <?php echo ($post['show_toc'] ?? 1) ? 'checked' : ''; ?> class="rounded text-blue-600 focus:ring-blue-500 mt-0.5">
                        <div>
                            <span class="text-xs text-gray-800 font-semibold block">Table of Contents (TOC)</span>
                            <span class="text-[11px] text-gray-500 block leading-tight">Auto-generate clickable Table of Contents on this article</span>
                        </div>
                    </label>
                </div>
            </div>

        </div>

    </div>
</form>

<!-- TinyMCE Script & WordPress Editor Enhancements -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.10.9/tinymce.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize TinyMCE with rich features
    tinymce.init({
        selector: '#blogContent',
        height: 520,
        menubar: 'file edit view insert format tools table help',
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen media table paste wordcount',
        toolbar: 'undo redo | formatselect | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table | removeformat code fullscreen',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 1.6; color: #1e293b; padding: 15px; } img { max-width: 100%; height: auto; border-radius: 8px; }',
        images_upload_handler: function (blobInfo, success, failure) {
            var formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            fetch('upload.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(d => { if (d.location) success(d.location); else failure('Upload failed'); })
                .catch(() => failure('Upload error'));
        },
        setup: function (editor) {
            editor.on('input change keyup', function () {
                updateStats(editor);
            });
            editor.on('init', function () {
                updateStats(editor);
            });
        }
    });

    function updateStats(editor) {
        var text = editor.getContent({ format: 'text' }).trim();
        var words = text ? text.split(/\s+/).filter(Boolean).length : 0;
        var chars = text.length;
        var minutes = Math.max(1, Math.ceil(words / 200));
        $('#wordCount').html('<i class="fa-solid fa-pen-nib mr-1"></i>' + words + ' words');
        $('#readingTime').html('<i class="fa-regular fa-clock mr-1"></i>' + minutes + ' min read');
        $('#charCount').text(chars + ' chars');
    }

    // Auto-save draft to localStorage every 20 seconds
    const draftKey = 'hostnibo_blog_draft_<?php echo $post['id']; ?>';
    setInterval(function() {
        if (typeof tinymce !== 'undefined' && tinymce.get('blogContent')) {
            var content = tinymce.get('blogContent').getContent();
            var title = $('#postTitle').val();
            if (title || content) {
                var draftData = {
                    title: title,
                    slug: $('#slugInput').val(),
                    content: content,
                    excerpt: $('#postExcerpt').val(),
                    meta_description: $('#seoDescInput').val(),
                    saved_at: new Date().toLocaleTimeString()
                };
                try {
                    localStorage.setItem(draftKey, JSON.stringify(draftData));
                    $('#autoSaveIndicator').html('<i class="fa-solid fa-cloud-arrow-up"></i> Auto-saved ' + draftData.saved_at).removeClass('hidden').fadeIn();
                } catch(e) {}
            }
        }
    }, 20000);

    // Auto generate slug from title when new
    <?php if (!$is_edit): ?>
    $('#postTitle').on('input', function() {
        var title = $(this).val();
        var slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        if (!$('#slugInput').data('manually-edited')) {
            $('#slugInput').val(slug);
            $('#slugDisplay').text(slug || 'post-slug');
            $('#seoSlugPreview').text(slug || 'post-slug');
        }
        $('#seoTitlePreview').text(title ? (title + ' - <?php echo addslashes(getSetting('site_name') ?: 'Host Nibo'); ?>') : 'Post Title Preview');
    });
    <?php else: ?>
    $('#postTitle').on('input', function() {
        var title = $(this).val();
        $('#seoTitlePreview').text(title ? (title + ' - <?php echo addslashes(getSetting('site_name') ?: 'Host Nibo'); ?>') : 'Post Title Preview');
    });
    <?php endif; ?>

    // SEO Meta updates
    $('#seoTitleInput').on('input', function() {
        var val = $(this).val();
        var count = val.length;
        $('#seoTitleCount').text(count + ' / 60 chars');
        var pct = Math.min(100, (count / 60) * 100);
        $('#seoTitleBar').css('width', pct + '%').toggleClass('bg-yellow-500', count > 60).toggleClass('bg-green-500', count <= 60 && count > 0);
        if (val) {
            $('#seoTitlePreview').text(val);
        } else {
            var mainTitle = $('#postTitle').val();
            $('#seoTitlePreview').text(mainTitle ? (mainTitle + ' - <?php echo addslashes(getSetting('site_name') ?: 'Host Nibo'); ?>') : 'Post Title Preview');
        }
    });

    $('#seoDescInput').on('input', function() {
        var val = $(this).val();
        var count = val.length;
        $('#seoDescCount').text(count + ' / 160 chars');
        var pct = Math.min(100, (count / 160) * 100);
        $('#seoDescBar').css('width', pct + '%').toggleClass('bg-yellow-500', count > 160).toggleClass('bg-green-500', count <= 160 && count > 0);
        $('#seoDescPreview').text(val || 'Provide a meta description to see how this post will look in Google search results.');
    });

    // Form submit save
    $('#postForm').on('submit', function() {
        tinymce.triggerSave();
    });
});

// Permalink Editing Controls
function toggleSlugEdit() {
    $('#slugDisplay, #slugEditBtn').hide();
    $('#slugEditGroup').removeClass('hidden').show();
    $('#slugInput').focus();
}

function saveSlugEdit() {
    var slug = $('#slugInput').val().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    $('#slugInput').val(slug).data('manually-edited', true);
    $('#slugDisplay').text(slug || 'post-slug').show();
    $('#seoSlugPreview').text(slug || 'post-slug');
    $('#slugEditBtn').show();
    $('#slugEditGroup').hide();
}

function cancelSlugEdit() {
    $('#slugDisplay, #slugEditBtn').show();
    $('#slugEditGroup').hide();
}

// Status Switcher
function updateStatus(val) {
    $('#postStatusInput').val(val);
}

// Date Editor
function applyDateEdit() {
    var val = $('#createdAtInput').val();
    if (val) {
        var d = new Date(val);
        var formatted = d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }) + ' ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
        $('#dateDisplay').text(formatted);
    }
    $('#dateEditBox').slideUp(150);
}

// Featured Image Handling
function handleImageSelect(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#featuredImgPreview').attr('src', e.target.result);
            $('#imageUploadArea').addClass('hidden');
            $('#imagePreviewArea').removeClass('hidden');
            $('#removeFeaturedImage').val('0');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeFeaturedImage() {
    $('#featuredImageInput').val('');
    $('#featuredImgPreview').attr('src', '');
    $('#imagePreviewArea').addClass('hidden');
    $('#imageUploadArea').removeClass('hidden');
    $('#removeFeaturedImage').val('1');
}

// AJAX Quick Category Adder
function ajaxAddCategory() {
    var name = $('#newCatName').val().trim();
    if (!name) {
        alert('Please enter a category name.');
        return;
    }
    $.post('blog-edit.php', { ajax_new_category: 1, cat_name: name }, function(res) {
        if (res.success) {
            var newRadio = `
                <label class="flex items-center gap-2.5 text-xs text-gray-700 hover:text-blue-600 cursor-pointer">
                    <input type="radio" name="category_id" value="${res.id}" checked class="text-blue-600 focus:ring-blue-500">
                    <span>${res.name}</span>
                </label>
            `;
            $('#categoriesList').append(newRadio);
            $('#newCatName').val('');
            $('#addCatBox').slideUp(150);
        } else {
            alert(res.message || 'Could not add category');
        }
    }, 'json');
}

// SEO Snippet Device Switcher
function switchSeoPreview(device) {
    if (device === 'mobile') {
        $('#btnSeoMobile').addClass('bg-white text-gray-800 shadow-xs').removeClass('text-gray-600');
        $('#btnSeoDesktop').removeClass('bg-white text-gray-800 shadow-xs').addClass('text-gray-600');
        $('#seoPreviewContainer').css('max-width', '375px');
    } else {
        $('#btnSeoDesktop').addClass('bg-white text-gray-800 shadow-xs').removeClass('text-gray-600');
        $('#btnSeoMobile').removeClass('bg-white text-gray-800 shadow-xs').addClass('text-gray-600');
        $('#seoPreviewContainer').css('max-width', '100%');
    }
}
</script>

<?php include 'footer.php'; ?>
