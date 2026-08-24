<?php
$page_title = 'Special Offers & Deals';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

$error = '';
$success = '';

// Handle AJAX Quick Toggle Status
if (isset($_POST['ajax_toggle_status'])) {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $curr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status, title FROM offers WHERE id = $id"));
        if ($curr) {
            $new_val = (int)$curr['status'] === 1 ? 0 : 1;
            mysqli_query($conn, "UPDATE offers SET status = $new_val WHERE id = $id");
            logActivity('Toggled Offer Status', ($curr['title'] ?? 'Offer') . " -> $new_val (ID: $id)");
            echo json_encode(['success' => true, 'new_val' => $new_val]);
            exit;
        }
    }
    echo json_encode(['success' => false]);
    exit;
}

// Handle Delete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_offer_id'])) {
    $id = (int)$_POST['delete_offer_id'];
    $del = mysqli_fetch_assoc(mysqli_query($conn, "SELECT title FROM offers WHERE id = $id"));
    if (mysqli_query($conn, "DELETE FROM offers WHERE id = $id")) {
        logActivity('Deleted Offer', ($del['title'] ?? 'Unknown') . ' (ID: ' . $id . ')');
        $success = 'Special offer deleted successfully.';
    } else {
        $error = 'Database error: ' . mysqli_error($conn);
    }
}

// Handle Add / Edit via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_offer'])) {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $badge = sanitize($_POST['badge'] ?? '');
    $price_label = sanitize($_POST['price_label'] ?? '');
    $link_url = sanitize($_POST['link_url'] ?? '');
    $link_text = sanitize($_POST['link_text'] ?? 'Learn More');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $status = isset($_POST['status']) ? 1 : 0;

    if (isset($_POST['offer_id']) && !empty($_POST['offer_id'])) {
        $id = (int)$_POST['offer_id'];
        $query = "UPDATE offers SET title='$title', description='$description', badge='$badge', price_label='$price_label', link_url='$link_url', link_text='$link_text', sort_order=$sort_order, status=$status WHERE id=$id";
        if (mysqli_query($conn, $query)) {
            logActivity('Updated Offer', $title . ' (ID: ' . $id . ')');
            $success = 'Special offer "' . htmlspecialchars($title) . '" updated successfully!';
        } else {
            $error = 'Database error: ' . mysqli_error($conn);
        }
    } else {
        $query = "INSERT INTO offers (title, description, badge, price_label, link_url, link_text, sort_order, status) VALUES ('$title', '$description', '$badge', '$price_label', '$link_url', '$link_text', $sort_order, $status)";
        if (mysqli_query($conn, $query)) {
            logActivity('Created Offer', $title);
            $success = 'New special offer "' . htmlspecialchars($title) . '" added successfully!';
        } else {
            $error = 'Database error: ' . mysqli_error($conn);
        }
    }
}

$offers = mysqli_query($conn, "SELECT * FROM offers ORDER BY sort_order ASC, id DESC");
$total_offers = mysqli_num_rows($offers);
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-tags"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Offers & Deals</h1>
            </div>
            <p class="text-xs text-gray-500">Manage limited-time promotional deals, pricing tags, and discount badges.</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="openAddOfferModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs cursor-pointer">
                <i class="fa-solid fa-plus"></i> Add New Offer
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($success): ?>
    <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between bg-emerald-50 text-emerald-800 border border-emerald-200">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span><?php echo htmlspecialchars($success); ?></span>
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

    <!-- Offers Table -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <div class="text-xs text-gray-500 font-semibold">
                Total Offers: <strong class="text-gray-900"><?php echo $total_offers; ?></strong>
            </div>
            <div class="relative w-64">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="offerSearchInput" onkeyup="filterOfferRows(this.value)" placeholder="Search offers..." class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-8 pr-3 py-1.5 text-xs text-gray-800 focus:bg-white focus:outline-none focus:border-blue-600 transition">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/70 border-b border-gray-200 text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5 w-12 text-center">Order</th>
                        <th class="px-4 py-3.5">Promo Title</th>
                        <th class="px-4 py-3.5">Badge</th>
                        <th class="px-4 py-3.5">Price Label</th>
                        <th class="px-4 py-3.5">Button & Link</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    <?php if ($total_offers > 0): while ($offer = mysqli_fetch_assoc($offers)): 
                        $offer_json = htmlspecialchars(json_encode($offer), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr class="offer-row hover:bg-blue-50/20 transition" data-name="<?php echo strtolower($offer['title'] . ' ' . $offer['badge'] . ' ' . $offer['price_label']); ?>">
                        <td class="px-4 py-3.5 text-center font-bold text-gray-400">
                            <?php echo $offer['sort_order']; ?>
                        </td>
                        <td class="px-4 py-3.5 font-bold text-gray-900">
                            <div><?php echo htmlspecialchars($offer['title']); ?></div>
                            <?php if ($offer['description']): ?>
                            <div class="text-[11px] text-gray-400 font-normal truncate max-w-xs"><?php echo htmlspecialchars($offer['description']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3.5">
                            <?php if ($offer['badge']): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-600 border border-rose-200">
                                <?php echo htmlspecialchars($offer['badge']); ?>
                            </span>
                            <?php else: ?>
                            <span class="text-gray-300">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3.5 font-bold text-blue-600">
                            <?php echo htmlspecialchars($offer['price_label'] ?: '—'); ?>
                        </td>
                        <td class="px-4 py-3.5">
                            <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($offer['link_text'] ?: 'Learn More'); ?></span>
                            <?php if ($offer['link_url']): ?>
                            <a href="<?php echo htmlspecialchars($offer['link_url']); ?>" target="_blank" class="text-gray-400 hover:text-blue-600 ml-1" title="View Link"><i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i></a>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3.5">
                            <button type="button" onclick="toggleOfferStatus(<?php echo $offer['id']; ?>, this)" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition <?php echo $offer['status'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'; ?>" title="Click to toggle Active/Inactive">
                                <span class="w-1.5 h-1.5 rounded-full <?php echo $offer['status'] ? 'bg-emerald-500' : 'bg-rose-500'; ?>"></span>
                                <span><?php echo $offer['status'] ? 'Active' : 'Inactive'; ?></span>
                            </button>
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" onclick='openEditOfferModal(<?php echo $offer_json; ?>)' class="p-1.5 bg-gray-50 hover:bg-blue-50 text-blue-600 rounded-lg border border-gray-200 hover:border-blue-200 transition cursor-pointer" title="Edit Offer">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </button>
                                <button type="button" onclick="openDeleteOfferModal(<?php echo $offer['id']; ?>, '<?php echo addslashes($offer['title']); ?>', '<?php echo addslashes($offer['price_label']); ?>')" class="p-1.5 bg-gray-50 hover:bg-red-50 text-red-600 rounded-lg border border-gray-200 hover:border-red-200 transition cursor-pointer" title="Delete Offer">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="7" class="px-4 py-16 text-center text-gray-400">
                            <i class="fa-solid fa-tags text-4xl text-gray-300 mb-2 block"></i>
                            <p class="font-bold text-gray-700">No promotional offers found</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Click "Add New Offer" to create your first promotion.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ==========================================
     POPUP MODAL: ADD / EDIT OFFER
=============================================== -->
<div id="offerModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3 sm:p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-100 my-auto sm:my-8 animate-in fade-in duration-200 flex flex-col max-h-[90vh]">
        
        <!-- Modal Header -->
        <div class="shrink-0 flex items-center justify-between px-4 sm:px-6 py-3.5 sm:py-4 border-b bg-gray-50/70">
            <div class="flex items-center gap-2">
                <span class="p-2 bg-blue-100 text-blue-700 rounded-lg text-xs" id="offerModalIcon"><i class="fa-solid fa-plus"></i></span>
                <h3 class="text-sm font-bold text-gray-900" id="offerModalTitle">Add New Offer</h3>
            </div>
            <button type="button" onclick="closeOfferModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg transition cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Modal Form Body -->
        <form method="POST" id="offerModalForm" class="flex flex-col flex-1 overflow-hidden">
            <input type="hidden" name="save_offer" value="1">
            <input type="hidden" name="offer_id" id="offer_id" value="">

            <div class="p-4 sm:p-6 space-y-4 text-xs flex-1 overflow-y-auto">
                
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Offer Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="offer_title" required placeholder="e.g. 50% Off First Year Hosting" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="offer_description" rows="3" placeholder="Explain the discount details and requirements..." class="w-full border border-gray-300 rounded-xl p-3 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Badge Tag</label>
                        <input type="text" name="badge" id="offer_badge" placeholder="e.g. LIMITED TIME, 50% OFF" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        
                        <!-- Quick Badge Preset Chips -->
                        <div class="flex flex-wrap gap-1 mt-1.5">
                            <button type="button" onclick="document.getElementById('offer_badge').value = '50% OFF'" class="text-[10px] bg-rose-50 text-rose-700 px-2 py-0.5 rounded border border-rose-200 hover:bg-rose-100 transition cursor-pointer">50% OFF</button>
                            <button type="button" onclick="document.getElementById('offer_badge').value = 'LIMITED TIME'" class="text-[10px] bg-amber-50 text-amber-700 px-2 py-0.5 rounded border border-amber-200 hover:bg-amber-100 transition cursor-pointer">LIMITED TIME</button>
                            <button type="button" onclick="document.getElementById('offer_badge').value = 'SPECIAL DEAL'" class="text-[10px] bg-blue-50 text-blue-700 px-2 py-0.5 rounded border border-blue-200 hover:bg-blue-100 transition cursor-pointer">SPECIAL DEAL</button>
                            <button type="button" onclick="document.getElementById('offer_badge').value = 'FLASH SALE'" class="text-[10px] bg-purple-50 text-purple-700 px-2 py-0.5 rounded border border-purple-200 hover:bg-purple-100 transition cursor-pointer">FLASH SALE</button>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Price / Discount Label</label>
                        <input type="text" name="price_label" id="offer_price_label" placeholder="e.g. ৳99/mo or Save 50%" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Link URL</label>
                        <input type="text" name="link_url" id="offer_link_url" placeholder="e.g. /offers or https://..." class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Button Text</label>
                        <input type="text" name="link_text" id="offer_link_text" value="Learn More" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" id="offer_sort_order" value="0" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div class="pt-2 border-t border-gray-100">
                    <label class="flex items-center gap-2 cursor-pointer select-none font-semibold text-gray-700">
                        <input type="checkbox" name="status" id="offer_status" value="1" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span><i class="fa-solid fa-circle-check text-emerald-500 mr-1"></i> Active (Visible on Offers page & homepage)</span>
                    </label>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="shrink-0 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2 px-4 sm:px-6 py-3.5 sm:py-4 border-t bg-gray-50">
                <button type="button" onclick="closeOfferModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition text-xs flex items-center justify-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-floppy-disk"></i> Save Offer
                </button>
            </div>
        </form>

    </div>
</div>

<!-- ==========================================
     POPUP MODAL: DELETE CONFIRMATION
=============================================== -->
<div id="deleteOfferModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST">
            <input type="hidden" name="delete_offer_id" id="delete_off_id" value="">
            
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Delete Special Offer?</h3>
                <p class="text-xs text-gray-500 mb-4">Are you sure you want to delete this promotional offer? It will be removed immediately.</p>
                
                <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 text-xs text-left mb-2">
                    <div class="font-bold text-gray-900" id="deleteOfferTitle">Offer Title</div>
                    <div class="text-gray-500 mt-0.5" id="deleteOfferPrice">Price: —</div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="closeDeleteOfferModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-trash-can"></i> Yes, Delete Offer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddOfferModal() {
    document.getElementById('offerModalTitle').innerText = 'Add New Offer';
    document.getElementById('offerModalIcon').innerHTML = '<i class="fa-solid fa-plus"></i>';
    document.getElementById('offer_id').value = '';
    document.getElementById('offer_title').value = '';
    document.getElementById('offer_description').value = '';
    document.getElementById('offer_badge').value = '';
    document.getElementById('offer_price_label').value = '';
    document.getElementById('offer_link_url').value = '';
    document.getElementById('offer_link_text').value = 'Learn More';
    document.getElementById('offer_sort_order').value = '0';
    document.getElementById('offer_status').checked = true;

    document.getElementById('offerModal').classList.remove('hidden');
}

function openEditOfferModal(offer) {
    document.getElementById('offerModalTitle').innerText = 'Edit Offer: ' + offer.title;
    document.getElementById('offerModalIcon').innerHTML = '<i class="fa-solid fa-pen"></i>';
    document.getElementById('offer_id').value = offer.id;
    document.getElementById('offer_title').value = offer.title;
    document.getElementById('offer_description').value = offer.description || '';
    document.getElementById('offer_badge').value = offer.badge || '';
    document.getElementById('offer_price_label').value = offer.price_label || '';
    document.getElementById('offer_link_url').value = offer.link_url || '';
    document.getElementById('offer_link_text').value = offer.link_text || 'Learn More';
    document.getElementById('offer_sort_order').value = offer.sort_order || 0;
    document.getElementById('offer_status').checked = (parseInt(offer.status) === 1);

    document.getElementById('offerModal').classList.remove('hidden');
}

function closeOfferModal() {
    document.getElementById('offerModal').classList.add('hidden');
}

function toggleOfferStatus(id, btn) {
    var fd = new FormData();
    fd.append('ajax_toggle_status', '1');
    fd.append('id', id);

    fetch('offers.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (data.new_val === 1) {
                btn.className = 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition bg-emerald-50 text-emerald-700 border border-emerald-200';
                btn.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span>Active</span>';
            } else {
                btn.className = 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition bg-rose-50 text-rose-700 border border-rose-200';
                btn.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span><span>Inactive</span>';
            }
        }
    });
}

function openDeleteOfferModal(id, title, price) {
    document.getElementById('delete_off_id').value = id;
    document.getElementById('deleteOfferTitle').innerText = title;
    document.getElementById('deleteOfferPrice').innerText = 'Price Label: ' + (price || '—');
    document.getElementById('deleteOfferModal').classList.remove('hidden');
}

function closeDeleteOfferModal() {
    document.getElementById('deleteOfferModal').classList.add('hidden');
}

function filterOfferRows(q) {
    q = q.trim().toLowerCase();
    document.querySelectorAll('.offer-row').forEach(row => {
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
        closeOfferModal();
        closeDeleteOfferModal();
    }
});
</script>

<?php include 'footer.php'; ?>
