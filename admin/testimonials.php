<?php
$page_title = 'Client Testimonials';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

$msg = '';
$error = '';

$upload_dir = '../uploads/testimonials/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

// Handle Delete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_testimonial_id'])) {
    $id = (int)$_POST['delete_testimonial_id'];
    $t = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name, photo FROM testimonials WHERE id = $id"));
    if ($t && $t['photo'] && file_exists('../' . $t['photo'])) {
        unlink('../' . $t['photo']);
    }
    if (mysqli_query($conn, "DELETE FROM testimonials WHERE id = $id")) {
        logActivity('Deleted Testimonial', ($t['name'] ?? 'Unknown') . ' (ID: ' . $id . ')');
        $msg = 'Testimonial deleted successfully.';
    } else {
        $error = 'Database error: ' . mysqli_error($conn);
    }
}

// Handle Add / Edit via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_testimonial'])) {
    $name = sanitize($_POST['name'] ?? '');
    $company = sanitize($_POST['company'] ?? '');
    $review = sanitize($_POST['review'] ?? '');
    $rating = min(5, max(1, (float)($_POST['rating'] ?? 5)));
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $edit_id = (int)($_POST['testimonial_id'] ?? 0);

    if (!$name || !$review) {
        $error = 'Client name and review content are required!';
    } else {
        $photo = isset($_POST['existing_photo']) ? sanitize($_POST['existing_photo']) : '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $fname = 'testimonial_' . time() . '_' . rand(100,999) . '.' . $ext;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $fname)) {
                    if ($photo && file_exists('../' . $photo)) unlink('../' . $photo);
                    $photo = 'uploads/testimonials/' . $fname;
                }
            }
        }

        if ($edit_id > 0) {
            if (mysqli_query($conn, "UPDATE testimonials SET name='$name', company='$company', photo='$photo', rating=$rating, review='$review', sort_order=$sort_order WHERE id=$edit_id")) {
                logActivity('Updated Testimonial', $name . ' (ID: ' . $edit_id . ')');
                $msg = 'Testimonial updated successfully!';
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        } else {
            if (!$sort_order) {
                $max = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(sort_order) as m FROM testimonials"));
                $sort_order = ($max['m'] ?? 0) + 1;
            }
            if (mysqli_query($conn, "INSERT INTO testimonials (name, company, photo, rating, review, sort_order) VALUES ('$name', '$company', '$photo', $rating, '$review', $sort_order)")) {
                logActivity('Created Testimonial', $name);
                $msg = 'New testimonial added successfully!';
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        }
    }
}

$items = mysqli_query($conn, "SELECT * FROM testimonials ORDER BY sort_order ASC, id DESC");
$total_testimonials = mysqli_num_rows($items);
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-comments"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Client Testimonials</h1>
            </div>
            <p class="text-xs text-gray-500">Manage client reviews, star ratings, feedback, and customer avatars.</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="openAddTestimonialModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs cursor-pointer">
                <i class="fa-solid fa-plus"></i> Add Testimonial
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

    <!-- Testimonials Table -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <div class="text-xs text-gray-500 font-semibold">
                Total Reviews: <strong class="text-gray-900"><?php echo $total_testimonials; ?></strong>
            </div>
            <div class="relative w-64">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="testimonialSearchInput" onkeyup="filterTestimonialRows(this.value)" placeholder="Search client or review..." class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-8 pr-3 py-1.5 text-xs text-gray-800 focus:bg-white focus:outline-none focus:border-blue-600 transition">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/70 border-b border-gray-200 text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5 w-16 text-center">Client</th>
                        <th class="px-4 py-3.5">Name & Role</th>
                        <th class="px-4 py-3.5">Rating</th>
                        <th class="px-4 py-3.5">Review Excerpt</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    <?php if ($total_testimonials > 0): while ($row = mysqli_fetch_assoc($items)): 
                        $t_json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr class="testimonial-row hover:bg-blue-50/20 transition" data-name="<?php echo strtolower($row['name'] . ' ' . $row['company'] . ' ' . $row['review']); ?>">
                        <td class="px-4 py-3.5 text-center">
                            <?php if (!empty($row['photo'])): ?>
                            <img src="/<?php echo ltrim($row['photo'], '/'); ?>" class="w-10 h-10 rounded-full object-cover border border-gray-200 mx-auto" alt="">
                            <?php else: ?>
                            <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 font-bold flex items-center justify-center mx-auto text-xs border border-blue-100">
                                <?php echo strtoupper(substr($row['name'], 0, 2)); ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3.5 font-bold text-gray-900">
                            <div><?php echo htmlspecialchars($row['name']); ?></div>
                            <div class="text-[11px] text-gray-400 font-normal"><?php echo htmlspecialchars($row['company'] ?: 'Verified Customer'); ?></div>
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="flex items-center text-amber-400 gap-0.5 text-xs">
                                <?php 
                                $r = (int)$row['rating'];
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $r ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star text-gray-300"></i>';
                                }
                                ?>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 text-gray-600 max-w-md">
                            <p class="line-clamp-2">"<?php echo htmlspecialchars($row['review']); ?>"</p>
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" onclick='openEditTestimonialModal(<?php echo $t_json; ?>)' class="p-1.5 bg-gray-50 hover:bg-blue-50 text-blue-600 rounded-lg border border-gray-200 hover:border-blue-200 transition cursor-pointer" title="Edit Testimonial">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </button>
                                <button type="button" onclick="openDeleteTestimonialModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['name']); ?>')" class="p-1.5 bg-gray-50 hover:bg-red-50 text-red-600 rounded-lg border border-gray-200 hover:border-red-200 transition cursor-pointer" title="Delete Testimonial">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="5" class="px-4 py-16 text-center text-gray-400">
                            <i class="fa-solid fa-comments text-4xl text-gray-300 mb-2 block"></i>
                            <p class="font-bold text-gray-700">No testimonials added yet</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Click "Add Testimonial" to show social proof and customer ratings.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ==========================================
     POPUP MODAL: ADD / EDIT TESTIMONIAL
=============================================== -->
<div id="testimonialModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-100 my-8 animate-in fade-in duration-200">
        
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50/70">
            <div class="flex items-center gap-2">
                <span class="p-2 bg-blue-100 text-blue-700 rounded-lg text-xs" id="testimonialModalIcon"><i class="fa-solid fa-plus"></i></span>
                <h3 class="text-sm font-bold text-gray-900" id="testimonialModalTitle">Add Client Testimonial</h3>
            </div>
            <button type="button" onclick="closeTestimonialModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg transition cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Modal Form Body -->
        <form method="POST" id="testimonialModalForm" enctype="multipart/form-data">
            <input type="hidden" name="save_testimonial" value="1">
            <input type="hidden" name="testimonial_id" id="testimonial_id" value="">
            <input type="hidden" name="existing_photo" id="testimonial_existing_photo" value="">

            <div class="p-6 space-y-4 text-xs max-h-[75vh] overflow-y-auto">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Client Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="testimonial_name" required placeholder="e.g. Siyam Ahmed" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Company / Designation</label>
                        <input type="text" name="company" id="testimonial_company" placeholder="e.g. CEO at TechFlow" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                </div>

                <!-- Rating -->
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Rating</label>
                    <select name="rating" id="testimonial_rating" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                        <option value="5">★★★★★ (5 Stars - Excellent)</option>
                        <option value="4">★★★★☆ (4 Stars - Good)</option>
                        <option value="3">★★★☆☆ (3 Stars - Average)</option>
                        <option value="2">★★☆☆☆ (2 Stars - Poor)</option>
                        <option value="1">★☆☆☆☆ (1 Star - Very Poor)</option>
                    </select>
                </div>

                <!-- Avatar Upload -->
                <div class="bg-gray-50/60 p-4 rounded-xl border border-gray-200 space-y-3">
                    <label class="block font-bold text-gray-700">Client Avatar / Photo</label>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-white border border-gray-200 flex items-center justify-center shrink-0 overflow-hidden" id="avatarPreviewBox">
                            <img id="avatarPreview" src="/images/user-default.png" class="h-full w-full object-cover" onerror="this.src='/images/bg.png'" alt="">
                        </div>
                        <div class="flex-1">
                            <input type="file" name="photo" id="testimonial_photo" accept="image/*" class="w-full border border-gray-300 rounded-lg p-1 text-[11px] bg-white file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[11px] file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" onchange="previewAvatar(this)">
                        </div>
                    </div>
                </div>

                <!-- Review Text -->
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Review / Testimonial Text <span class="text-red-500">*</span></label>
                    <textarea name="review" id="testimonial_review" rows="4" required placeholder="Write what the customer said about your service..." class="w-full border border-gray-300 rounded-xl p-3 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" id="testimonial_sort_order" value="0" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t bg-gray-50">
                <button type="button" onclick="closeTestimonialModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-floppy-disk"></i> Save Review
                </button>
            </div>
        </form>

    </div>
</div>

<!-- ==========================================
     POPUP MODAL: DELETE CONFIRMATION
=============================================== -->
<div id="deleteTestimonialModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST">
            <input type="hidden" name="delete_testimonial_id" id="delete_testimonial_id_input" value="">
            
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Delete Testimonial?</h3>
                <p class="text-xs text-gray-500 mb-4">Are you sure you want to delete this review? It will be removed from your website.</p>
                
                <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 text-xs text-left mb-2">
                    <div class="font-bold text-gray-900" id="deleteTestimonialName">Client Name</div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="closeDeleteTestimonialModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-trash-can"></i> Yes, Delete Review
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddTestimonialModal() {
    document.getElementById('testimonialModalTitle').innerText = 'Add Client Testimonial';
    document.getElementById('testimonialModalIcon').innerHTML = '<i class="fa-solid fa-plus"></i>';
    document.getElementById('testimonial_id').value = '';
    document.getElementById('testimonial_existing_photo').value = '';
    document.getElementById('testimonial_name').value = '';
    document.getElementById('testimonial_company').value = '';
    document.getElementById('testimonial_rating').value = '5';
    document.getElementById('testimonial_review').value = '';
    document.getElementById('testimonial_sort_order').value = '0';
    document.getElementById('testimonial_photo').value = '';
    document.getElementById('avatarPreview').src = '/images/bg.png';

    document.getElementById('testimonialModal').classList.remove('hidden');
}

function openEditTestimonialModal(item) {
    document.getElementById('testimonialModalTitle').innerText = 'Edit Testimonial: ' + item.name;
    document.getElementById('testimonialModalIcon').innerHTML = '<i class="fa-solid fa-pen"></i>';
    document.getElementById('testimonial_id').value = item.id;
    document.getElementById('testimonial_existing_photo').value = item.photo || '';
    document.getElementById('testimonial_name').value = item.name;
    document.getElementById('testimonial_company').value = item.company || '';
    document.getElementById('testimonial_rating').value = item.rating || '5';
    document.getElementById('testimonial_review').value = item.review || '';
    document.getElementById('testimonial_sort_order').value = item.sort_order || 0;
    document.getElementById('testimonial_photo').value = '';
    
    if (item.photo) {
        document.getElementById('avatarPreview').src = '/' + item.photo.replace(/^\/+/, '');
    } else {
        document.getElementById('avatarPreview').src = '/images/bg.png';
    }

    document.getElementById('testimonialModal').classList.remove('hidden');
}

function closeTestimonialModal() {
    document.getElementById('testimonialModal').classList.add('hidden');
}

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function openDeleteTestimonialModal(id, name) {
    document.getElementById('delete_testimonial_id_input').value = id;
    document.getElementById('deleteTestimonialName').innerText = name;
    document.getElementById('deleteTestimonialModal').classList.remove('hidden');
}

function closeDeleteTestimonialModal() {
    document.getElementById('deleteTestimonialModal').classList.add('hidden');
}

function filterTestimonialRows(q) {
    q = q.trim().toLowerCase();
    document.querySelectorAll('.testimonial-row').forEach(row => {
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
        closeTestimonialModal();
        closeDeleteTestimonialModal();
    }
});
</script>

<?php include 'footer.php'; ?>
