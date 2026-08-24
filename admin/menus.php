<?php
$page_title = 'Menu Manager';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

$msg = '';
$error = '';

// Handle Reorder via AJAX
if (isset($_POST['reorder'])) {
    $ids = $_POST['ids'] ?? '';
    foreach (explode(',', $ids) as $i => $id) {
        $id = (int)$id;
        $sort = ($i + 1) * 10;
        mysqli_query($conn, "UPDATE menu_items SET sort_order = $sort WHERE id = $id");
    }
    exit;
}

// Handle Delete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_menu_id'])) {
    $id = (int)$_POST['delete_menu_id'];
    $m = mysqli_fetch_assoc(mysqli_query($conn, "SELECT label FROM menu_items WHERE id = $id"));
    if (mysqli_query($conn, "DELETE FROM menu_items WHERE id = $id OR parent_id = $id")) {
        logActivity('Deleted Menu Item', ($m['label'] ?? 'Unknown') . ' (ID: ' . $id . ')');
        $msg = 'Menu item and any sub-items deleted successfully.';
    } else {
        $error = 'Database error: ' . mysqli_error($conn);
    }
}

// Handle Add / Edit via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_menu_item'])) {
    $label = sanitize($_POST['label'] ?? '');
    $url = sanitize($_POST['url'] ?? '#');
    $parent_id = (int)($_POST['parent_id'] ?? 0);
    $location = sanitize($_POST['location'] ?? 'header');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $status = isset($_POST['status']) ? 1 : 0;
    $edit_id = (int)($_POST['menu_id'] ?? 0);

    if (!$label) {
        $error = 'Menu item label is required!';
    } else {
        if ($edit_id > 0) {
            if (mysqli_query($conn, "UPDATE menu_items SET label='$label', url='$url', parent_id=$parent_id, location='$location', sort_order=$sort_order, status=$status WHERE id=$edit_id")) {
                logActivity('Updated Menu Item', $label . ' (ID: ' . $edit_id . ')');
                $msg = 'Menu item "' . htmlspecialchars($label) . '" updated successfully!';
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        } else {
            if (!$sort_order) {
                $max = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(sort_order) as m FROM menu_items WHERE parent_id = $parent_id"));
                $sort_order = ($max['m'] ?? 0) + 10;
            }
            if (mysqli_query($conn, "INSERT INTO menu_items (label, url, parent_id, location, sort_order, status) VALUES ('$label', '$url', $parent_id, '$location', $sort_order, $status)")) {
                logActivity('Created Menu Item', $label);
                $msg = 'New menu item "' . htmlspecialchars($label) . '" added successfully!';
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        }
    }
}

$parent_items_query = mysqli_query($conn, "SELECT * FROM menu_items WHERE parent_id = 0 ORDER BY sort_order ASC");
$all_parents = [];
while ($p = mysqli_fetch_assoc($parent_items_query)) {
    $all_parents[] = $p;
}

function renderMenuLocationBlock($location_label, $location_name) {
    global $conn;
    $items = mysqli_query($conn, "SELECT * FROM menu_items WHERE location = '$location_label' OR location = 'both' ORDER BY parent_id, sort_order ASC");
    $parents = [];
    $all_rows = [];
    while ($m = mysqli_fetch_assoc($items)) {
        $all_rows[] = $m;
        if ($m['parent_id'] == 0) $parents[] = $m;
    }
    ?>
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden mb-6">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/70">
            <h3 class="font-bold text-xs text-gray-800 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid <?php echo $location_label === 'header' ? 'fa-heading' : 'fa-shoe-prints'; ?> text-blue-600"></i>
                <?php echo $location_name; ?>
            </h3>
            <span class="text-[11px] text-gray-400"><i class="fa-solid fa-arrows-up-down mr-1"></i> Drag to reorder</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-gray-50/50 border-b border-gray-200 text-gray-600 font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="px-4 py-3 w-8 text-center">#</th>
                        <th class="px-4 py-3">Menu Label</th>
                        <th class="px-4 py-3">Link URL</th>
                        <th class="px-4 py-3">Location</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="sortableMenu<?php echo ucfirst($location_label); ?>" class="divide-y divide-gray-100">
                    <?php if (count($parents) > 0): foreach ($parents as $m): 
                        $children = mysqli_query($conn, "SELECT * FROM menu_items WHERE parent_id = {$m['id']} ORDER BY sort_order ASC");
                        $child_count = mysqli_num_rows($children);
                        $m_json = htmlspecialchars(json_encode($m), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr data-id="<?php echo $m['id']; ?>" class="bg-blue-50/20 hover:bg-blue-50/40 transition cursor-move">
                        <td class="px-4 py-3 text-center text-gray-400"><i class="fa-solid fa-grip-vertical"></i></td>
                        <td class="px-4 py-3 font-bold text-gray-900 flex items-center gap-2">
                            <i class="fa-solid fa-folder text-amber-500"></i>
                            <span><?php echo htmlspecialchars($m['label']); ?></span>
                            <?php if ($child_count > 0): ?>
                            <span class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-bold"><?php echo $child_count; ?> sub-items</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 font-mono text-[11px] text-gray-500"><?php echo htmlspecialchars($m['url']); ?></td>
                        <td class="px-4 py-3 capitalize text-gray-600 font-semibold"><?php echo htmlspecialchars($m['location']); ?></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold <?php echo $m['status'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'; ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?php echo $m['status'] ? 'bg-emerald-500' : 'bg-rose-500'; ?>"></span>
                                <?php echo $m['status'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" onclick='openEditMenuModal(<?php echo $m_json; ?>)' class="p-1.5 bg-white hover:bg-blue-50 text-blue-600 rounded-lg border border-gray-200 hover:border-blue-200 transition cursor-pointer" title="Edit">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </button>
                                <button type="button" onclick="openDeleteMenuModal(<?php echo $m['id']; ?>, '<?php echo addslashes($m['label']); ?>', <?php echo $child_count; ?>)" class="p-1.5 bg-white hover:bg-red-50 text-red-600 rounded-lg border border-gray-200 hover:border-red-200 transition cursor-pointer" title="Delete">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php while ($child = mysqli_fetch_assoc($children)): 
                        $c_json = htmlspecialchars(json_encode($child), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr data-id="<?php echo $child['id']; ?>" class="hover:bg-gray-50 transition cursor-move">
                        <td class="px-4 py-2.5 text-center text-gray-300"><i class="fa-solid fa-grip-vertical text-[10px]"></i></td>
                        <td class="px-4 py-2.5 pl-10 text-gray-700 flex items-center gap-2">
                            <i class="fa-solid fa-turn-up rotate-90 text-gray-300 text-[10px]"></i>
                            <i class="fa-regular fa-file text-gray-400"></i>
                            <span class="font-medium"><?php echo htmlspecialchars($child['label']); ?></span>
                        </td>
                        <td class="px-4 py-2.5 font-mono text-[11px] text-gray-400"><?php echo htmlspecialchars($child['url']); ?></td>
                        <td class="px-4 py-2.5 capitalize text-gray-400"><?php echo htmlspecialchars($child['location']); ?></td>
                        <td class="px-4 py-2.5">
                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold <?php echo $child['status'] ? 'text-emerald-600' : 'text-rose-500'; ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?php echo $child['status'] ? 'bg-emerald-500' : 'bg-rose-500'; ?>"></span>
                                <?php echo $child['status'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" onclick='openEditMenuModal(<?php echo $c_json; ?>)' class="p-1 bg-white hover:bg-blue-50 text-blue-600 rounded-md border border-gray-200 hover:border-blue-200 transition cursor-pointer" title="Edit">
                                    <i class="fa-solid fa-pen text-[10px]"></i>
                                </button>
                                <button type="button" onclick="openDeleteMenuModal(<?php echo $child['id']; ?>, '<?php echo addslashes($child['label']); ?>', 0)" class="p-1 bg-white hover:bg-red-50 text-red-600 rounded-md border border-gray-200 hover:border-red-200 transition cursor-pointer" title="Delete">
                                    <i class="fa-solid fa-trash-can text-[10px]"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                            No menu items configured for this location yet.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-bars-staggered"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Menu Manager</h1>
            </div>
            <p class="text-xs text-gray-500">Configure header and footer navigation menus, parent-child dropdown hierarchies, and links.</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="openAddMenuModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs cursor-pointer">
                <i class="fa-solid fa-plus"></i> Add Menu Item
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

    <!-- Menu Tables -->
    <?php renderMenuLocationBlock('header', 'Header Navigation Menu'); ?>
    <?php renderMenuLocationBlock('footer', 'Footer Navigation Menu'); ?>

</div>

<!-- ==========================================
     POPUP MODAL: ADD / EDIT MENU ITEM
=============================================== -->
<div id="menuModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-100 my-8 animate-in fade-in duration-200">
        
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50/70">
            <div class="flex items-center gap-2">
                <span class="p-2 bg-blue-100 text-blue-700 rounded-lg text-xs" id="menuModalIcon"><i class="fa-solid fa-plus"></i></span>
                <h3 class="text-sm font-bold text-gray-900" id="menuModalTitle">Add Menu Item</h3>
            </div>
            <button type="button" onclick="closeMenuModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg transition cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Modal Form Body -->
        <form method="POST" id="menuModalForm">
            <input type="hidden" name="save_menu_item" value="1">
            <input type="hidden" name="menu_id" id="menu_id" value="">

            <div class="p-6 space-y-4 text-xs max-h-[75vh] overflow-y-auto">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Menu Label <span class="text-red-500">*</span></label>
                        <input type="text" name="label" id="menu_label" required placeholder="e.g. Domains, Web Hosting" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Link URL <span class="text-red-500">*</span></label>
                        <input type="text" name="url" id="menu_url" required placeholder="e.g. /category/shared or #" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Parent Item (Dropdown)</label>
                        <select name="parent_id" id="menu_parent_id" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                            <option value="0">— Top Level (No Parent) —</option>
                            <?php foreach ($all_parents as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Menu Location</label>
                        <select name="location" id="menu_location" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                            <option value="header">Header Menu</option>
                            <option value="footer">Footer Menu</option>
                            <option value="both">Both Header & Footer</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" id="menu_sort_order" value="0" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div class="pt-2 border-t border-gray-100">
                    <label class="flex items-center gap-2 cursor-pointer select-none font-semibold text-gray-700">
                        <input type="checkbox" name="status" id="menu_status" value="1" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span><i class="fa-solid fa-circle-check text-emerald-500 mr-1"></i> Active (Visible in Menus)</span>
                    </label>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t bg-gray-50">
                <button type="button" onclick="closeMenuModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-floppy-disk"></i> Save Menu Item
                </button>
            </div>
        </form>

    </div>
</div>

<!-- ==========================================
     POPUP MODAL: DELETE CONFIRMATION
=============================================== -->
<div id="deleteMenuModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <form method="POST">
            <input type="hidden" name="delete_menu_id" id="delete_menu_id_input" value="">
            
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Delete Menu Item?</h3>
                <p class="text-xs text-gray-500 mb-4">Are you sure you want to remove this menu item from navigation?</p>
                
                <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 text-xs text-left mb-2">
                    <div class="font-bold text-gray-900" id="deleteMenuLabel">Label</div>
                    <div class="text-gray-500 mt-0.5" id="deleteMenuChildren">0 sub-items</div>
                </div>
                <p class="text-[11px] text-amber-600 font-semibold" id="deleteMenuWarning"></p>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
                <button type="button" onclick="closeDeleteMenuModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-trash-can"></i> Yes, Delete Item
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
function openAddMenuModal() {
    document.getElementById('menuModalTitle').innerText = 'Add Menu Item';
    document.getElementById('menuModalIcon').innerHTML = '<i class="fa-solid fa-plus"></i>';
    document.getElementById('menu_id').value = '';
    document.getElementById('menu_label').value = '';
    document.getElementById('menu_url').value = '#';
    document.getElementById('menu_parent_id').value = '0';
    document.getElementById('menu_location').value = 'header';
    document.getElementById('menu_sort_order').value = '0';
    document.getElementById('menu_status').checked = true;

    document.getElementById('menuModal').classList.remove('hidden');
}

function openEditMenuModal(m) {
    document.getElementById('menuModalTitle').innerText = 'Edit Menu Item: ' + m.label;
    document.getElementById('menuModalIcon').innerHTML = '<i class="fa-solid fa-pen"></i>';
    document.getElementById('menu_id').value = m.id;
    document.getElementById('menu_label').value = m.label;
    document.getElementById('menu_url').value = m.url || '#';
    document.getElementById('menu_parent_id').value = m.parent_id || '0';
    document.getElementById('menu_location').value = m.location || 'header';
    document.getElementById('menu_sort_order').value = m.sort_order || 0;
    document.getElementById('menu_status').checked = (parseInt(m.status) === 1);

    document.getElementById('menuModal').classList.remove('hidden');
}

function closeMenuModal() {
    document.getElementById('menuModal').classList.add('hidden');
}

function openDeleteMenuModal(id, label, childCount) {
    document.getElementById('delete_menu_id_input').value = id;
    document.getElementById('deleteMenuLabel').innerText = label;
    document.getElementById('deleteMenuChildren').innerText = childCount > 0 ? (childCount + ' nested sub-item(s)') : 'Top-level item';
    if (childCount > 0) {
        document.getElementById('deleteMenuWarning').innerText = '⚠️ Warning: Deleting this parent item will also delete all its ' + childCount + ' dropdown child items!';
    } else {
        document.getElementById('deleteMenuWarning').innerText = '';
    }
    document.getElementById('deleteMenuModal').classList.remove('hidden');
}

function closeDeleteMenuModal() {
    document.getElementById('deleteMenuModal').classList.add('hidden');
}

// Drag & Drop Sorting
['Header', 'Footer'].forEach(function(loc) {
    var el = document.getElementById('sortableMenu' + loc);
    if (el) {
        new Sortable(el, {
            handle: '.fa-grip-vertical',
            animation: 150,
            onEnd: function() {
                var ids = [];
                el.querySelectorAll('tr[data-id]').forEach(function(tr) {
                    ids.push(tr.dataset.id);
                });
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'menus.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.send('reorder=1&ids=' + ids.join(','));
            }
        });
    }
});

// Keyboard ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMenuModal();
        closeDeleteMenuModal();
    }
});
</script>

<?php include 'footer.php'; ?>
