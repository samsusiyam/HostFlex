<?php
$page_title = 'CMS Custom Pages';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

$msg = '';
$error = '';

// Handle Delete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_page_id'])) {
    $id = (int)$_POST['delete_page_id'];
    $p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT title FROM pages WHERE id = $id"));
    if (mysqli_query($conn, "DELETE FROM pages WHERE id = $id")) {
        logActivity('Deleted Page', ($p['title'] ?? 'Unknown') . ' (ID: ' . $id . ')');
        $msg = 'Page deleted successfully.';
    } else {
        $error = 'Database error: ' . mysqli_error($conn);
    }
}

// Handle Add / Edit via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_page'])) {
    $title = sanitize($_POST['title'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    $content = mysqli_real_escape_string($conn, $_POST['content'] ?? '');
    $meta_description = sanitize($_POST['meta_description'] ?? '');
    $meta_keywords = sanitize($_POST['meta_keywords'] ?? '');
    $status = isset($_POST['status']) ? 1 : 0;
    $edit_id = (int)($_POST['page_id'] ?? 0);

    if (empty($slug) && !empty($title)) {
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $title)));
    } else {
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $slug)));
    }

    if (!$title || !$slug) {
        $error = 'Page title and URL slug are required!';
    } else {
        $check = mysqli_query($conn, "SELECT id FROM pages WHERE slug = '$slug'" . ($edit_id ? " AND id != $edit_id" : ""));
        if (mysqli_num_rows($check) > 0) {
            $error = 'Page slug already exists. Please choose a different slug.';
        } elseif ($edit_id > 0) {
            if (mysqli_query($conn, "UPDATE pages SET title='$title', slug='$slug', content='$content', meta_description='$meta_description', meta_keywords='$meta_keywords', status=$status WHERE id=$edit_id")) {
                logActivity('Updated Page', $title . ' (ID: ' . $edit_id . ')');
                $msg = 'Page "' . htmlspecialchars($title) . '" updated successfully!';
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        } else {
            if (mysqli_query($conn, "INSERT INTO pages (title, slug, content, meta_description, meta_keywords, status) VALUES ('$title', '$slug', '$content', '$meta_description', '$meta_keywords', $status)")) {
                logActivity('Created Page', $title);
                $msg = 'New page "' . htmlspecialchars($title) . '" created successfully!';
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        }
    }
}

$pages = mysqli_query($conn, "SELECT * FROM pages ORDER BY title ASC");
$total_pages = mysqli_num_rows($pages);
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-file-lines"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">CMS Custom Pages</h1>
            </div>
            <p class="text-xs text-gray-500">Create and manage content pages like About Us, Privacy Policy, Terms of Service, etc.</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="openAddPageModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs cursor-pointer">
                <i class="fa-solid fa-plus"></i> Add New Page
            </button>
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

    <!-- Pages Table -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <div class="text-xs text-gray-500 font-semibold">
                Total Pages: <strong class="text-gray-900"><?php echo $total_pages; ?></strong>
            </div>
            <div class="relative w-64">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="pageSearchInput" onkeyup="filterPageRows(this.value)" placeholder="Search pages..." class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-8 pr-3 py-1.5 text-xs text-gray-800 focus:bg-white focus:outline-none focus:border-blue-600 transition">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/70 border-b border-gray-200 text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5">Page Title</th>
                        <th class="px-4 py-3.5">URL Permalink</th>
                        <th class="px-4 py-3.5">Meta Description</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    <?php if ($total_pages > 0): while ($p = mysqli_fetch_assoc($pages)): 
                        $p_json = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr class="page-row hover:bg-blue-50/20 transition" data-name="<?php echo strtolower($p['title'] . ' ' . $p['slug'] . ' ' . $p['meta_description']); ?>">
                        <td class="px-4 py-3.5 font-bold text-gray-900">
                            <div class="flex items-center gap-2">
                                <i class="fa-regular fa-file-lines text-blue-500"></i>
                                <span><?php echo htmlspecialchars($p['title']); ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 font-mono text-[11px] text-gray-500">
                            /page/<?php echo htmlspecialchars($p['slug']); ?>
                        </td>
                        <td class="px-4 py-3.5 text-gray-500 max-w-sm truncate">
                            <?php echo htmlspecialchars($p['meta_description'] ?: '—'); ?>
                        </td>
                        <td class="px-4 py-3.5">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold <?php echo $p['status'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'; ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?php echo $p['status'] ? 'bg-emerald-500' : 'bg-rose-500'; ?>"></span>
                                <?php echo $p['status'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="/page/<?php echo urlencode($p['slug']); ?>" target="_blank" class="p-1.5 bg-gray-50 hover:bg-emerald-50 text-emerald-600 rounded-lg border border-gray-200 hover:border-emerald-200 transition cursor-pointer" title="View Public Page">
                                    <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                                </a>
                                <button type="button" onclick='openEditPageModal(<?php echo $p_json; ?>)' class="p-1.5 bg-gray-50 hover:bg-blue-50 text-blue-600 rounded-lg border border-gray-200 hover:border-blue-200 transition cursor-pointer" title="Edit Page">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </button>
                                <button type="button" onclick="openDeletePageModal(<?php echo $p['id']; ?>, '<?php echo addslashes($p['title']); ?>', '<?php echo addslashes($p['slug']); ?>')" class="p-1.5 bg-gray-50 hover:bg-red-50 text-red-600 rounded-lg border border-gray-200 hover:border-red-200 transition cursor-pointer" title="Delete Page">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="5" class="px-4 py-16 text-center text-gray-400">
                            <i class="fa-solid fa-file-circle-plus text-4xl text-gray-300 mb-2 block"></i>
                            <p class="font-bold text-gray-700">No custom pages created yet</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Click "Add New Page" to publish rich CMS pages.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ==========================================
     POPUP MODAL: ADD / EDIT CMS PAGE
=============================================== -->
<div id="pageModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden border border-gray-100 my-6 animate-in fade-in duration-200">
        
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50/70">
            <div class="flex items-center gap-2">
                <span class="p-2 bg-blue-100 text-blue-700 rounded-lg text-xs" id="pageModalIcon"><i class="fa-solid fa-plus"></i></span>
                <h3 class="text-sm font-bold text-gray-900" id="pageModalTitle">Add New CMS Page</h3>
            </div>
            <button type="button" onclick="closePageModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg transition cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Modal Form Body -->
        <form method="POST" id="pageModalForm">
            <input type="hidden" name="save_page" value="1">
            <input type="hidden" name="page_id" id="page_id" value="">

            <div class="p-6 space-y-4 text-xs max-h-[78vh] overflow-y-auto">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Page Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" id="page_title_input" required placeholder="e.g. About Us, Terms of Service" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none" onkeyup="autoGenPageSlug(this.value)">
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">URL Slug <span class="text-red-500">*</span></label>
                        <input type="text" name="slug" id="page_slug_input" required placeholder="e.g. about-us" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Meta Description (SEO)</label>
                        <input type="text" name="meta_description" id="page_meta_description" placeholder="Brief SEO description..." class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Meta Keywords (SEO)</label>
                        <input type="text" name="meta_keywords" id="page_meta_keywords" placeholder="web hosting, about, team" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                </div>

                <!-- Content Editor -->
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Page Content (HTML / Rich Text)</label>
                    <textarea name="content" id="pageContentEditor" rows="12" class="w-full border border-gray-300 rounded-xl p-3 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none font-mono"></textarea>
                </div>

                <div class="pt-2 border-t border-gray-100">
                    <label class="flex items-center gap-2 cursor-pointer select-none font-semibold text-gray-700">
                        <input type="checkbox" name="status" id="page_status" value="1" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span><i class="fa-solid fa-circle-check text-emerald-500 mr-1"></i> Active (Published on site)</span>
                    </label>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t bg-gray-50">
                <button type="button" onclick="closePageModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-floppy-disk"></i> Save Page
                </button>
            </div>
        </form>

    </div>
</div>

<!-- ==========================================
     POPUP MODAL: DELETE CONFIRMATION
=============================================== -->
<div id="deletePageModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST">
            <input type="hidden" name="delete_page_id" id="delete_page_id_input" value="">
            
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Delete CMS Page?</h3>
                <p class="text-xs text-gray-500 mb-4">Are you sure you want to delete this custom page? Visitors attempting to access its URL will see a 404 error.</p>
                
                <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 text-xs text-left mb-2">
                    <div class="font-bold text-gray-900" id="deletePageTitle">Page Title</div>
                    <div class="text-gray-500 mt-0.5 font-mono" id="deletePageSlug">/page/about-us</div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="closeDeletePageModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-trash-can"></i> Yes, Delete Page
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.10.9/tinymce.min.js"></script>
<script>
var tinymceInitialized = false;

function initTinyMCE() {
    if (!tinymceInitialized) {
        tinymce.init({
            selector: '#pageContentEditor',
            height: 380,
            menubar: true,
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen media table help wordcount',
            toolbar: 'undo redo | formatselect | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | image link media table | code fullscreen',
            images_upload_handler: function (blobInfo, success, failure) {
                var formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());
                formData.append('upload', 'tinymce');
                fetch('upload.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(d => { if (d.location) success(d.location); else failure('Upload failed'); })
                    .catch(() => failure('Upload error'));
            }
        });
        tinymceInitialized = true;
    }
}

function openAddPageModal() {
    initTinyMCE();
    document.getElementById('pageModalTitle').innerText = 'Add New CMS Page';
    document.getElementById('pageModalIcon').innerHTML = '<i class="fa-solid fa-plus"></i>';
    document.getElementById('page_id').value = '';
    document.getElementById('page_title_input').value = '';
    document.getElementById('page_slug_input').value = '';
    document.getElementById('page_meta_description').value = '';
    document.getElementById('page_meta_keywords').value = '';
    document.getElementById('page_status').checked = true;

    if (tinymce.get('pageContentEditor')) {
        tinymce.get('pageContentEditor').setContent('');
    } else {
        document.getElementById('pageContentEditor').value = '';
    }

    document.getElementById('pageModal').classList.remove('hidden');
}

function openEditPageModal(p) {
    initTinyMCE();
    document.getElementById('pageModalTitle').innerText = 'Edit Page: ' + p.title;
    document.getElementById('pageModalIcon').innerHTML = '<i class="fa-solid fa-pen"></i>';
    document.getElementById('page_id').value = p.id;
    document.getElementById('page_title_input').value = p.title;
    document.getElementById('page_slug_input').value = p.slug;
    document.getElementById('page_meta_description').value = p.meta_description || '';
    document.getElementById('page_meta_keywords').value = p.meta_keywords || '';
    document.getElementById('page_status').checked = (parseInt(p.status) === 1);

    if (tinymce.get('pageContentEditor')) {
        tinymce.get('pageContentEditor').setContent(p.content || '');
    } else {
        document.getElementById('pageContentEditor').value = p.content || '';
    }

    document.getElementById('pageModal').classList.remove('hidden');
}

function closePageModal() {
    document.getElementById('pageModal').classList.add('hidden');
}

function autoGenPageSlug(val) {
    if (document.getElementById('page_id').value === '') {
        document.getElementById('page_slug_input').value = val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    }
}

document.getElementById('pageModalForm').addEventListener('submit', function() {
    if (tinymce.get('pageContentEditor')) {
        tinymce.triggerSave();
    }
});

function openDeletePageModal(id, title, slug) {
    document.getElementById('delete_page_id_input').value = id;
    document.getElementById('deletePageTitle').innerText = title;
    document.getElementById('deletePageSlug').innerText = '/page/' + slug;
    document.getElementById('deletePageModal').classList.remove('hidden');
}

function closeDeletePageModal() {
    document.getElementById('deletePageModal').classList.add('hidden');
}

function filterPageRows(q) {
    q = q.trim().toLowerCase();
    document.querySelectorAll('.page-row').forEach(row => {
        var text = row.dataset.name || '';
        if (!q || text.includes(q)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Keyboard ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePageModal();
        closeDeletePageModal();
    }
});
</script>

<?php include 'footer.php'; ?>
