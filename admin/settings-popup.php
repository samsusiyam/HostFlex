<?php
$page_title = 'Popup Notice & Floating Action Button';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminRole(['admin']);

$error = '';
$success = '';

function saveSettingHelper2($key, $value) {
    global $conn;
    $s_key = mysqli_real_escape_string($conn, $key);
    $s_value = mysqli_real_escape_string($conn, $value);
    $check = mysqli_query($conn, "SELECT id FROM settings WHERE setting_key = '$s_key'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE settings SET setting_value = '$s_value' WHERE setting_key = '$s_key'");
    } else {
        mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('$s_key', '$s_value')");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if (in_array($key, ['submit', 'save_social_buttons', 'add_social_button', 'deleted_btns'])) continue;
        if ($key === 'social_buttons') continue;
        $s_key = sanitize($key);
        $s_value = mysqli_real_escape_string($conn, $value);
        saveSettingHelper2($s_key, $s_value);
    }

    if (isset($_POST['social_buttons']) && is_array($_POST['social_buttons'])) {
        $buttons = [];
        $names = $_POST['social_buttons']['name'] ?? [];
        $icons = $_POST['social_buttons']['icon'] ?? [];
        $colors = $_POST['social_buttons']['color'] ?? [];
        $urls = $_POST['social_buttons']['url'] ?? [];
        $deleted_btns = isset($_POST['deleted_btns']) ? (array)$_POST['deleted_btns'] : [];
        $upload_dir = '../uploads/social/';
        if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);
        foreach ($names as $i => $name) {
            if (in_array($i, $deleted_btns)) continue;
            if (!trim($name) || !trim($urls[$i] ?? '')) continue;
            $icon = trim($icons[$i] ?? '💬');
            if (isset($_FILES['social_buttons']['tmp_name']['icon'][$i]) && !empty($_FILES['social_buttons']['tmp_name']['icon'][$i])) {
                $file = $_FILES['social_buttons'];
                $ext = strtolower(pathinfo($file['name']['icon'][$i], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif','webp','svg'];
                if (in_array($ext, $allowed)) {
                    $fname = 'social_' . time() . '_' . $i . '.' . $ext;
                    move_uploaded_file($file['tmp_name']['icon'][$i], $upload_dir . $fname);
                    $icon = 'uploads/social/' . $fname;
                }
            }
            $buttons[] = [
                'name' => trim($name),
                'icon' => $icon,
                'color' => trim($colors[$i] ?? '#25D366'),
                'url' => trim($urls[$i] ?? '')
            ];
        }
        saveSettingHelper2('social_buttons', json_encode($buttons));
    }

    logActivity('Updated Popup & FAB Settings', 'Promotional modal and quick social links updated');
    header('Location: settings-popup.php?s=1');
    exit;
}

if (isset($_GET['s'])) {
    $success = 'Popup Notice & FAB settings saved successfully!';
}

$settings_result = mysqli_query($conn, "SELECT * FROM settings ORDER BY setting_key");
$s = []; 
while ($row = mysqli_fetch_assoc($settings_result)) { 
    $s[$row['setting_key']] = $row['setting_value']; 
}

$social_buttons_raw = $s['social_buttons'] ?? '';
$social_buttons = [];
if ($social_buttons_raw) {
    $decoded = json_decode($social_buttons_raw, true);
    if (is_array($decoded)) $social_buttons = $decoded;
}
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-yellow-50 text-yellow-600 rounded-lg text-sm"><i class="fa-solid fa-bell"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Popup Notice & Floating Buttons</h1>
            </div>
            <p class="text-xs text-gray-500">Configure promotional modal alerts, floating action buttons (FAB), and footer social channels.</p>
        </div>
    </div>

    <!-- Alert Notification -->
    <?php if ($success): ?>
    <div class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between bg-emerald-50 text-emerald-800 border border-emerald-200">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span><?php echo htmlspecialchars($success); ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 cursor-pointer"><i class="fa-solid fa-xmark text-sm"></i></button>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        
        <!-- 1. Popup Notice -->
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-4 text-xs">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <span class="p-2 bg-yellow-50 text-yellow-600 rounded-lg text-xs"><i class="fa-solid fa-bullhorn"></i></span>
                    <h2 class="text-sm font-bold text-gray-900">Promotional Alert Popup Modal</h2>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="popup_notice_enabled" value="0">
                    <input type="checkbox" name="popup_notice_enabled" value="1" <?php echo (!isset($s['popup_notice_enabled']) || $s['popup_notice_enabled'] == '1') ? 'checked' : ''; ?> class="sr-only peer">
                    <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-yellow-500"></div>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block font-bold text-gray-700 mb-1">Notice Heading Title</label>
                    <input type="text" name="popup_notice_title" value="<?php echo htmlspecialchars($s['popup_notice_title'] ?? 'Special Eid Mega Discount! 🌙'); ?>" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-yellow-500 focus:border-yellow-500 outline-none">
                </div>

                <div class="md:col-span-2">
                    <label class="block font-bold text-gray-700 mb-1">Notice Message Content (HTML allowed)</label>
                    <textarea name="popup_notice_message" rows="4" class="w-full border border-gray-300 rounded-xl p-3 text-xs focus:ring-1 focus:ring-yellow-500 focus:border-yellow-500 outline-none leading-relaxed"><?php echo htmlspecialchars($s['popup_notice_message'] ?? 'Get up to 50% discount on all cPanel Web Hosting packages this week!'); ?></textarea>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Modal Background (RGBA / HEX)</label>
                    <input type="text" name="popup_notice_bg_color" value="<?php echo htmlspecialchars($s['popup_notice_bg_color'] ?? '#ffffff'); ?>" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs font-mono">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Text Color</label>
                    <input type="text" name="popup_notice_text_color" value="<?php echo htmlspecialchars($s['popup_notice_text_color'] ?? '#1e293b'); ?>" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs font-mono">
                </div>
            </div>
        </div>

        <!-- 2. Floating Action Button (FAB) & Social Icons -->
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-4 text-xs">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <span class="p-2 bg-emerald-50 text-emerald-600 rounded-lg text-xs"><i class="fa-solid fa-comment-dots"></i></span>
                    <h2 class="text-sm font-bold text-gray-900">Floating Action Quick Buttons (FAB)</h2>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="showAddSocialBtn()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-xl font-bold transition flex items-center gap-1 cursor-pointer">
                        <i class="fa-solid fa-plus"></i> Add Channel
                    </button>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="fab_enabled" value="0">
                        <input type="checkbox" name="fab_enabled" value="1" <?php echo (isset($s['fab_enabled']) && $s['fab_enabled'] == '1') ? 'checked' : ''; ?> class="sr-only peer">
                        <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>
            </div>

            <div id="socialButtonsContainer" class="space-y-3">
                <?php if (empty($social_buttons)): ?>
                <div id="noSocialMsg" class="text-center py-8 text-gray-400">
                    <i class="fa-solid fa-comments text-3xl mb-1"></i>
                    <p class="font-bold text-gray-600">No floating buttons created yet</p>
                </div>
                <?php endif; ?>
                <?php foreach ($social_buttons as $i => $btn): ?>
                <?php $is_img = (str_contains($btn['icon'] ?? '', '/') || str_contains($btn['icon'] ?? '', '.')); ?>
                <div class="social-btn-row flex flex-col sm:flex-row items-stretch sm:items-center gap-3 p-3.5 bg-gray-50 rounded-xl border border-gray-200" data-idx="<?php echo $i; ?>">
                    <div class="w-full sm:w-44">
                        <input type="text" name="social_buttons[name][]" value="<?php echo htmlspecialchars($btn['name'] ?? ''); ?>" placeholder="Channel Name" class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs font-bold">
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg"><?php echo htmlspecialchars($btn['icon'] ?? '💬'); ?></span>
                        <input type="hidden" name="social_buttons[icon][]" value="<?php echo htmlspecialchars($btn['icon'] ?? '💬'); ?>">
                    </div>
                    <div class="w-full sm:w-28">
                        <input type="text" name="social_buttons[color][]" value="<?php echo htmlspecialchars($btn['color'] ?? '#25D366'); ?>" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs font-mono font-bold" style="border-left: 4px solid <?php echo htmlspecialchars($btn['color'] ?? '#25D366'); ?>">
                    </div>
                    <div class="flex-1">
                        <input type="url" name="social_buttons[url][]" value="<?php echo htmlspecialchars($btn['url'] ?? ''); ?>" placeholder="https://wa.me/8801700000000" class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs">
                    </div>
                    <button type="button" onclick="deleteSocialBtn(this)" class="text-red-500 hover:text-red-700 p-1.5 rounded-lg hover:bg-red-50 transition cursor-pointer" title="Delete Channel">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <div id="deletedBtnsContainer"></div>
        </div>

        <!-- 3. Footer Social Profiles -->
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-4 text-xs">
            <div class="flex items-center gap-2 pb-3 border-b border-gray-100">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-xs"><i class="fa-solid fa-share-nodes"></i></span>
                <h2 class="text-sm font-bold text-gray-900">Official Social Media Profiles (Footer Links)</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-gray-700 mb-1"><i class="fa-brands fa-facebook text-blue-600 mr-1"></i> Facebook Page URL</label>
                    <input type="url" name="facebook_url" value="<?php echo htmlspecialchars($s['facebook_url'] ?? ''); ?>" placeholder="https://facebook.com/hostnibo" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1"><i class="fa-brands fa-x-twitter text-slate-800 mr-1"></i> Twitter / X URL</label>
                    <input type="url" name="twitter_url" value="<?php echo htmlspecialchars($s['twitter_url'] ?? ''); ?>" placeholder="https://twitter.com/hostnibo" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1"><i class="fa-brands fa-linkedin text-blue-700 mr-1"></i> LinkedIn URL</label>
                    <input type="url" name="linkedin_url" value="<?php echo htmlspecialchars($s['linkedin_url'] ?? ''); ?>" placeholder="https://linkedin.com/company/hostnibo" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1"><i class="fa-brands fa-youtube text-red-600 mr-1"></i> YouTube Channel</label>
                    <input type="url" name="youtube_url" value="<?php echo htmlspecialchars($s['youtube_url'] ?? ''); ?>" placeholder="https://youtube.com/@hostnibo" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" name="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-xs transition flex items-center gap-2 text-xs cursor-pointer">
                <i class="fa-solid fa-floppy-disk"></i> Save All Popup & Social Settings
            </button>
        </div>

    </form>

</div>

<!-- ==========================================
     POPUP MODAL: ADD FAB CHANNEL
=============================================== -->
<div id="addSocialModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50/70">
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <i class="fa-solid fa-plus text-emerald-600"></i> Add FAB Social Channel
            </h3>
            <button type="button" onclick="closeAddSocial()" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-base"></i></button>
        </div>
        <div class="p-6 space-y-4 text-xs">
            <div>
                <label class="block font-bold text-gray-700 mb-1">Channel Name (e.g. WhatsApp, Telegram)</label>
                <input type="text" id="newBtnName" placeholder="WhatsApp Support" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
            </div>
            <div>
                <label class="block font-bold text-gray-700 mb-1">Emoji Icon</label>
                <input type="text" id="newBtnIcon" value="💬" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 outline-none text-center font-bold">
            </div>
            <div>
                <label class="block font-bold text-gray-700 mb-1">Theme Color</label>
                <input type="text" id="newBtnColor" value="#25D366" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs font-mono">
            </div>
            <div>
                <label class="block font-bold text-gray-700 mb-1">Direct Chat URL</label>
                <input type="url" id="newBtnUrl" placeholder="https://wa.me/8801700000000" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 px-6 py-3.5 border-t bg-gray-50">
            <button type="button" onclick="closeAddSocial()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-xl font-bold transition text-xs cursor-pointer">Cancel</button>
            <button type="button" onclick="addSocialBtn()" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold transition text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                <i class="fa-solid fa-plus"></i> Add Channel
            </button>
        </div>
    </div>
</div>

<script>
function showAddSocialBtn() {
    document.getElementById('addSocialModal').classList.remove('hidden');
}
function closeAddSocial() {
    document.getElementById('addSocialModal').classList.add('hidden');
}
function addSocialBtn() {
    var name = document.getElementById('newBtnName').value.trim();
    var icon = document.getElementById('newBtnIcon').value.trim() || '💬';
    var color = document.getElementById('newBtnColor').value.trim() || '#25D366';
    var url = document.getElementById('newBtnUrl').value.trim();

    if (!name || !url) { 
        alert('Please fill in channel name and URL.'); 
        return; 
    }

    var container = document.getElementById('socialButtonsContainer');
    var msg = document.getElementById('noSocialMsg');
    if (msg) msg.style.display = 'none';

    var html = '<div class="social-btn-row flex flex-col sm:flex-row items-stretch sm:items-center gap-3 p-3.5 bg-gray-50 rounded-xl border border-gray-200">';
    html += '<div class="w-full sm:w-44"><input type="text" name="social_buttons[name][]" value="' + name + '" class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs font-bold"></div>';
    html += '<div class="flex items-center gap-2"><span class="text-lg">' + icon + '</span><input type="hidden" name="social_buttons[icon][]" value="' + icon + '"></div>';
    html += '<div class="w-full sm:w-28"><input type="text" name="social_buttons[color][]" value="' + color + '" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs font-mono font-bold" style="border-left: 4px solid ' + color + '"></div>';
    html += '<div class="flex-1"><input type="url" name="social_buttons[url][]" value="' + url + '" class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs"></div>';
    html += '<button type="button" onclick="deleteSocialBtn(this)" class="text-red-500 hover:text-red-700 p-1.5 rounded-lg hover:bg-red-50 transition cursor-pointer"><i class="fa-solid fa-trash-can"></i></button>';
    html += '</div>';

    container.insertAdjacentHTML('beforeend', html);
    document.getElementById('newBtnName').value = '';
    document.getElementById('newBtnUrl').value = '';
    closeAddSocial();
}

function deleteSocialBtn(btn) {
    var row = btn.closest('.social-btn-row');
    if (row) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'deleted_btns[]';
        input.value = row.dataset.idx !== undefined ? row.dataset.idx : 'x';
        document.getElementById('deletedBtnsContainer').appendChild(input);
        row.remove();
    }
}
</script>

<?php include 'footer.php'; ?>
