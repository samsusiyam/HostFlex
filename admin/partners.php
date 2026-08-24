<?php
$page_title = 'Partners & Brands';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

$msg = '';
$error = '';

$upload_dir = '../uploads/partners/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

// Handle Delete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_partner_id'])) {
    $id = (int)$_POST['delete_partner_id'];
    $p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name, photo FROM partners WHERE id = $id"));
    if ($p && $p['photo'] && file_exists('../' . $p['photo'])) {
        unlink('../' . $p['photo']);
    }
    if (mysqli_query($conn, "DELETE FROM partners WHERE id = $id")) {
        logActivity('Deleted Partner', ($p['name'] ?? 'Unknown') . ' (ID: ' . $id . ')');
        $msg = 'Partner logo deleted successfully.';
    } else {
        $error = 'Database error: ' . mysqli_error($conn);
    }
}

// Handle Add / Edit via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_partner'])) {
    $name = sanitize($_POST['name'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $edit_id = (int)($_POST['partner_id'] ?? 0);

    if (!$name) {
        $error = 'Partner/Brand name is required!';
    } else {
        $photo = isset($_POST['existing_photo']) ? sanitize($_POST['existing_photo']) : '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg'])) {
                $fname = 'partner_' . time() . '_' . rand(100,999) . '.' . $ext;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $fname)) {
                    if ($photo && file_exists('../' . $photo)) unlink('../' . $photo);
                    $photo = 'uploads/partners/' . $fname;
                }
            }
        }

        if ($edit_id > 0) {
            if (mysqli_query($conn, "UPDATE partners SET name='$name', photo='$photo', sort_order=$sort_order WHERE id=$edit_id")) {
                logActivity('Updated Partner', $name . ' (ID: ' . $edit_id . ')');
                $msg = 'Partner logo updated successfully!';
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        } else {
            if (!$sort_order) {
                $max = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(sort_order) as m FROM partners"));
                $sort_order = ($max['m'] ?? 0) + 1;
            }
            if (mysqli_query($conn, "INSERT INTO partners (name, photo, sort_order) VALUES ('$name', '$photo', $sort_order)")) {
                logActivity('Created Partner', $name);
                $msg = 'New partner brand added successfully!';
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        }
    }
}

$items = mysqli_query($conn, "SELECT * FROM partners ORDER BY sort_order ASC, id DESC");
$total_partners = mysqli_num_rows($items);
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-handshake"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Partners & Brands</h1>
            </div>
            <p class="text-xs text-gray-500">Manage client and partner logos displayed on your homepage trust badges.</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="openAddPartnerModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs cursor-pointer">
                <i class="fa-solid fa-plus"></i> Add Partner Logo
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

    <!-- Partner Grid -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
            <div class="text-xs text-gray-500 font-semibold">
                Total Brands: <strong class="text-gray-900"><?php echo $total_partners; ?></strong>
            </div>
        </div>

        <?php if ($total_partners > 0): ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php while ($row = mysqli_fetch_assoc($items)): 
                $p_json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
            ?>
            <div class="bg-gray-50/70 hover:bg-blue-50/30 rounded-2xl border border-gray-200/80 p-4 flex flex-col items-center justify-between text-center transition group relative">
                <div class="h-16 w-full flex items-center justify-center p-2 mb-2">
                    <?php if (!empty($row['photo'])): ?>
                    <img src="/<?php echo ltrim($row['photo'], '/'); ?>" class="max-h-12 max-w-full object-contain grayscale group-hover:grayscale-0 transition" alt="">
                    <?php else: ?>
                    <span class="text-gray-400 text-xs font-bold"><?php echo htmlspecialchars($row['name']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="w-full pt-2 border-t border-gray-200/60 flex items-center justify-between text-xs">
                    <span class="font-bold text-gray-800 text-[11px] truncate max-w-[90px]"><?php echo htmlspecialchars($row['name']); ?></span>
                    <div class="flex items-center gap-1">
                        <button type="button" onclick='openEditPartnerModal(<?php echo $p_json; ?>)' class="text-blue-600 hover:text-blue-800 p-1 cursor-pointer" title="Edit">
                            <i class="fa-solid fa-pen text-[10px]"></i>
                        </button>
                        <button type="button" onclick="openDeletePartnerModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['name']); ?>', '<?php echo addslashes($row['photo']); ?>')" class="text-red-500 hover:text-red-700 p-1 cursor-pointer" title="Delete">
                            <i class="fa-solid fa-trash-can text-[10px]"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-16 text-gray-400">
            <i class="fa-solid fa-handshake text-4xl text-gray-300 mb-2 block"></i>
            <p class="font-bold text-gray-700">No partner logos uploaded yet</p>
            <p class="text-[11px] text-gray-400 mt-0.5">Click "Add Partner Logo" to display client trust badges on your site.</p>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- ==========================================
     POPUP MODAL: ADD / EDIT PARTNER
=============================================== -->
<div id="partnerModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 my-8 animate-in fade-in duration-200">
        
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50/70">
            <div class="flex items-center gap-2">
                <span class="p-2 bg-blue-100 text-blue-700 rounded-lg text-xs" id="partnerModalIcon"><i class="fa-solid fa-plus"></i></span>
                <h3 class="text-sm font-bold text-gray-900" id="partnerModalTitle">Add Partner Logo</h3>
            </div>
            <button type="button" onclick="closePartnerModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg transition cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Modal Form Body -->
        <form method="POST" id="partnerModalForm" enctype="multipart/form-data">
            <input type="hidden" name="save_partner" value="1">
            <input type="hidden" name="partner_id" id="partner_id" value="">
            <input type="hidden" name="existing_photo" id="partner_existing_photo" value="">

            <div class="p-6 space-y-4 text-xs">
                
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Brand / Company Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="partner_name" required placeholder="e.g. cPanel, Cloudflare, Softaculous" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <!-- Logo Upload -->
                <div class="bg-gray-50/60 p-4 rounded-xl border border-gray-200 space-y-3">
                    <label class="block font-bold text-gray-700">Partner Logo File (PNG, SVG, JPG, WebP)</label>
                    <div class="flex items-center gap-3">
                        <div class="w-16 h-12 rounded-xl bg-white border border-gray-200 flex items-center justify-center shrink-0 p-1 overflow-hidden" id="logoPreviewBox">
                            <img id="partnerLogoPreview" src="/images/bg.png" class="max-h-10 max-w-full object-contain" alt="">
                        </div>
                        <div class="flex-1">
                            <input type="file" name="photo" id="partner_photo" accept="image/*" class="w-full border border-gray-300 rounded-lg p-1 text-[11px] bg-white file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[11px] file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" onchange="previewPartnerLogo(this)">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" id="partner_sort_order" value="0" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t bg-gray-50">
                <button type="button" onclick="closePartnerModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-floppy-disk"></i> Save Logo
                </button>
            </div>
        </form>

    </div>
</div>

<!-- ==========================================
     POPUP MODAL: DELETE CONFIRMATION
=============================================== -->
<div id="deletePartnerModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST">
            <input type="hidden" name="delete_partner_id" id="delete_partner_id_input" value="">
            
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Delete Partner Logo?</h3>
                <p class="text-xs text-gray-500 mb-4">Are you sure you want to remove this brand logo from your website?</p>
                
                <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 text-xs text-center mb-2">
                    <div class="font-bold text-gray-900" id="deletePartnerName">Brand Name</div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="closeDeletePartnerModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-trash-can"></i> Yes, Delete Logo
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddPartnerModal() {
    document.getElementById('partnerModalTitle').innerText = 'Add Partner Logo';
    document.getElementById('partnerModalIcon').innerHTML = '<i class="fa-solid fa-plus"></i>';
    document.getElementById('partner_id').value = '';
    document.getElementById('partner_existing_photo').value = '';
    document.getElementById('partner_name').value = '';
    document.getElementById('partner_sort_order').value = '0';
    document.getElementById('partner_photo').value = '';
    document.getElementById('partnerLogoPreview').src = '/images/bg.png';

    document.getElementById('partnerModal').classList.remove('hidden');
}

function openEditPartnerModal(item) {
    document.getElementById('partnerModalTitle').innerText = 'Edit Partner: ' + item.name;
    document.getElementById('partnerModalIcon').innerHTML = '<i class="fa-solid fa-pen"></i>';
    document.getElementById('partner_id').value = item.id;
    document.getElementById('partner_existing_photo').value = item.photo || '';
    document.getElementById('partner_name').value = item.name;
    document.getElementById('partner_sort_order').value = item.sort_order || 0;
    document.getElementById('partner_photo').value = '';
    
    if (item.photo) {
        document.getElementById('partnerLogoPreview').src = '/' + item.photo.replace(/^\/+/, '');
    } else {
        document.getElementById('partnerLogoPreview').src = '/images/bg.png';
    }

    document.getElementById('partnerModal').classList.remove('hidden');
}

function closePartnerModal() {
    document.getElementById('partnerModal').classList.add('hidden');
}

function previewPartnerLogo(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('partnerLogoPreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function openDeletePartnerModal(id, name, photo) {
    document.getElementById('delete_partner_id_input').value = id;
    document.getElementById('deletePartnerName').innerText = name;
    document.getElementById('deletePartnerModal').classList.remove('hidden');
}

function closeDeletePartnerModal() {
    document.getElementById('deletePartnerModal').classList.add('hidden');
}

// Keyboard ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePartnerModal();
        closeDeletePartnerModal();
    }
});
</script>

<?php include 'footer.php'; ?>
