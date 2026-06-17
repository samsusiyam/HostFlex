<?php
$page_title = 'Popup Notice & Social';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

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
        $check = mysqli_query($conn, "SELECT id FROM settings WHERE setting_key = '$s_key'");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "UPDATE settings SET setting_value = '$s_value' WHERE setting_key = '$s_key'");
        } else {
            mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('$s_key', '$s_value')");
        }
    }

    if (isset($_POST['social_buttons']) && is_array($_POST['social_buttons'])) {
        $buttons = [];
        $names = $_POST['social_buttons']['name'] ?? [];
        $icons = $_POST['social_buttons']['icon'] ?? [];
        $colors = $_POST['social_buttons']['color'] ?? [];
        $urls = $_POST['social_buttons']['url'] ?? [];
        $deleted_btns = isset($_POST['deleted_btns']) ? (array)$_POST['deleted_btns'] : [];
        $upload_dir = '../uploads/social/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
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

    header('Location: settings-popup.php?s=1');
    exit;
}

if (isset($_GET['s'])) {
    $success = 'Settings updated successfully!';
}

$settings_result = mysqli_query($conn, "SELECT * FROM settings ORDER BY setting_key");
$s = []; while ($row = mysqli_fetch_assoc($settings_result)) { $s[$row['setting_key']] = $row['setting_value']; }

$social_buttons_raw = $s['social_buttons'] ?? '';
$social_buttons = [];
if ($social_buttons_raw) {
    $decoded = json_decode($social_buttons_raw, true);
    if (is_array($decoded)) $social_buttons = $decoded;
}
?>
<?php include 'header.php'; ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Popup Notice & Social</h1>
    <p class="text-gray-500">Popup notification, social links, and FAB button configuration</p>
</div>

<?php if ($success): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4"><?php echo $error; ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4 flex items-center"><i class="fa fa-bell text-yellow-600 mr-2"></i> Popup Notice</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-center gap-2 md:col-span-2">
                <input type="hidden" name="popup_notice_enabled" value="0">
                <input type="checkbox" name="popup_notice_enabled" value="1" <?php echo (!isset($s['popup_notice_enabled']) || $s['popup_notice_enabled'] == '1') ? 'checked' : ''; ?> class="h-5 w-5 text-blue-600 border rounded">
                <label class="text-sm font-medium text-gray-700">Enable Popup Notice</label>
            </div>
            <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Popup Title</label><input type="text" name="popup_notice_title" value="<?php echo htmlspecialchars($s['popup_notice_title'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
            <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Popup Message</label><textarea name="popup_notice_message" rows="5" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($s['popup_notice_message'] ?? ''); ?></textarea></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Background Color</label><input type="text" name="popup_notice_bg_color" value="<?php echo htmlspecialchars($s['popup_notice_bg_color'] ?? 'rgba(255,255,255,0.8)'); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" placeholder="rgba(255,255,255,0.8)"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Text Color</label><input type="text" name="popup_notice_text_color" value="<?php echo htmlspecialchars($s['popup_notice_text_color'] ?? '#333'); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" placeholder="#333"></div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4 flex items-center"><i class="fa fa-share-alt text-blue-500 mr-2"></i> Social Links (Footer)</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1"><i class="fab fa-facebook text-blue-600 mr-1"></i> Facebook URL</label><input type="url" name="facebook_url" value="<?php echo htmlspecialchars($s['facebook_url'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1"><i class="fab fa-twitter text-sky-500 mr-1"></i> Twitter URL</label><input type="url" name="twitter_url" value="<?php echo htmlspecialchars($s['twitter_url'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1"><i class="fab fa-linkedin text-blue-700 mr-1"></i> LinkedIn URL</label><input type="url" name="linkedin_url" value="<?php echo htmlspecialchars($s['linkedin_url'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1"><i class="fab fa-youtube text-red-600 mr-1"></i> YouTube URL</label><input type="url" name="youtube_url" value="<?php echo htmlspecialchars($s['youtube_url'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold flex items-center"><i class="fa fa-plus-circle text-green-600 mr-2"></i> FAB Social Buttons (Dynamic)</h2>
            <button type="button" onclick="showAddSocialBtn()" class="bg-green-600 text-white px-4 py-1.5 rounded-lg hover:bg-green-700 transition shadow text-sm font-medium"><i class="fa fa-plus mr-1"></i> Add Button</button>
        </div>
        <p class="text-sm text-gray-500 mb-4">These buttons appear in the floating action button (FAB) on the frontend.</p>
        <div id="socialButtonsContainer">
            <?php if (empty($social_buttons)): ?>
            <div id="noSocialMsg" class="text-center py-8 text-gray-400">
                <i class="fa fa-share-alt text-4xl mb-2"></i>
                <p>No social buttons configured yet.</p>
            </div>
            <?php endif; ?>
            <?php foreach ($social_buttons as $i => $btn): ?>
            <?php $is_img_icon = (strpos($btn['icon'] ?? '', '/') !== false || strpos($btn['icon'] ?? '', '.') !== false); ?>
            <div class="social-btn-row flex items-center gap-3 mb-3 p-3 bg-gray-50 rounded-lg border border-gray-200" data-idx="<?php echo $i; ?>">
                <div class="flex-[3]"><input type="text" name="social_buttons[name][]" value="<?php echo htmlspecialchars($btn['name'] ?? ''); ?>" placeholder="Name" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"></div>
                <div class="flex flex-col items-center gap-1">
                    <?php if ($is_img_icon): ?>
                    <img src="../<?php echo htmlspecialchars($btn['icon']); ?>" class="w-7 h-7 object-contain" alt="icon">
                    <?php else: ?>
                    <span class="text-xl"><?php echo htmlspecialchars($btn['icon'] ?? '💬'); ?></span>
                    <?php endif; ?>
                    <label class="text-[10px] text-blue-600 cursor-pointer hover:underline">Upload<input type="file" name="social_buttons[icon][<?php echo $i; ?>]" accept="image/*" class="hidden" onchange="this.closest('.social-btn-row').querySelector('.icon-preview').src=window.URL.createObjectURL(this.files[0]); this.closest('.social-btn-row').querySelector('.icon-preview').classList.remove('hidden')"></label>
                    <input type="hidden" name="social_buttons[icon][]" value="<?php echo htmlspecialchars($btn['icon'] ?? '💬'); ?>">
                </div>
                <div class="w-24"><input type="text" name="social_buttons[color][]" value="<?php echo htmlspecialchars($btn['color'] ?? '#25D366'); ?>" placeholder="Color" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" style="border-left: 4px solid <?php echo htmlspecialchars($btn['color'] ?? '#25D366'); ?>"></div>
                <div class="flex-[4]"><input type="url" name="social_buttons[url][]" value="<?php echo htmlspecialchars($btn['url'] ?? ''); ?>" placeholder="URL" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"></div>
                <button type="button" onclick="deleteSocialBtn(this)" class="text-red-600 hover:text-red-800 hover:scale-110 transition-transform px-2" title="Delete"><i class="fa fa-trash-alt"></i></button>
            </div>
            <?php endforeach; ?>
        </div>
        <div id="deletedBtnsContainer"></div>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4 flex items-center"><i class="fa fa-cog text-gray-600 mr-2"></i> FAB Button Settings</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-center gap-2">
                <input type="hidden" name="fab_enabled" value="0">
                <input type="checkbox" name="fab_enabled" value="1" <?php echo (isset($s['fab_enabled']) && $s['fab_enabled'] == '1') ? 'checked' : ''; ?> class="h-5 w-5 text-blue-600 border rounded">
                <label class="text-sm font-medium text-gray-700">Enable FAB Button</label>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">FAB Icon</label><input type="text" name="fab_icon" value="<?php echo htmlspecialchars($s['fab_icon'] ?? '💬'); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
        </div>
    </div>

    <button type="submit" name="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition shadow font-medium"><i class="fa fa-save mr-1"></i> Save All Settings</button>
</form>

<div id="addSocialModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold mb-4"><i class="fa fa-plus-circle text-green-600 mr-2"></i> Add Social Button</h3>
        <div class="grid grid-cols-1 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Name</label><input type="text" id="newBtnName" placeholder="WhatsApp" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Icon</label>
                <div class="flex gap-2 items-start">
                    <input type="text" id="newBtnIcon" value="💬" placeholder="Emoji or leave empty" class="flex-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <div class="flex flex-col items-center gap-1">
                        <label class="text-xs bg-gray-100 px-3 py-2 rounded cursor-pointer hover:bg-gray-200 border"><i class="fa fa-image"></i> Upload</label>
                        <input type="file" id="newBtnIconFile" accept="image/*" class="hidden" onchange="document.getElementById('newBtnIconPreview').src=window.URL.createObjectURL(this.files[0]);document.getElementById('newBtnIconPreview').classList.remove('hidden');document.getElementById('newBtnIcon').value='';">
                        <img id="newBtnIconPreview" class="hidden w-8 h-8 object-contain mt-1">
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-1">Enter emoji or upload an image</p>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Background Color</label><input type="text" id="newBtnColor" value="#25D366" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">URL</label><input type="url" id="newBtnUrl" placeholder="https://wa.me/88016XXXXXXXX" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
        </div>
        <div class="mt-6 flex items-center gap-3">
            <button type="button" onclick="addSocialBtn()" class="bg-green-600 text-white px-5 py-2 rounded-lg hover:bg-green-700 transition shadow font-medium"><i class="fa fa-plus mr-1"></i> Add</button>
            <button type="button" onclick="closeAddSocial()" class="text-gray-600 px-4 py-2 border rounded-lg hover:bg-gray-50 transition"><i class="fa fa-times mr-1"></i> Cancel</button>
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
    var fileInput = document.getElementById('newBtnIconFile');
    var hasFile = fileInput && fileInput.files.length > 0;
    if (!name || !url) { alert('Name and URL are required.'); return; }
    var container = document.getElementById('socialButtonsContainer');
    var msg = document.getElementById('noSocialMsg');
    if (msg) msg.style.display = 'none';
    var rnd = Date.now();
    var html = '<div class="social-btn-row flex items-center gap-3 mb-3 p-3 bg-gray-50 rounded-lg border border-gray-200">';
    html += '<div class="flex-[3]"><input type="text" name="social_buttons[name][]" value="' + escHtml(name) + '" placeholder="Name" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"></div>';
    html += '<div class="flex flex-col items-center gap-1">';
    var previewStyle = hasFile ? '' : ' style="display:none"';
    html += '<img src="' + (hasFile ? URL.createObjectURL(fileInput.files[0]) : '') + '" class="w-7 h-7 object-contain icon-preview"' + previewStyle + '>';
    html += '<label class="text-[10px] text-blue-600 cursor-pointer hover:underline">Upload<input type="file" name="social_buttons[icon][' + rnd + ']" accept="image/*" class="hidden" onchange="var r=this.closest(\'.social-btn-row\');r.querySelector(\'.icon-preview\').src=window.URL.createObjectURL(this.files[0]);r.querySelector(\'.icon-preview\').style.display=\'\';r.querySelector(\'.icon-hidden\').value=\'\'"></label>';
    html += '<input type="hidden" name="social_buttons[icon][]" class="icon-hidden" value="' + (hasFile ? '' : escHtml(icon)) + '">';
    html += '</div>';
    html += '<div class="w-24"><input type="text" name="social_buttons[color][]" value="' + escHtml(color) + '" placeholder="Color" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" style="border-left: 4px solid ' + escHtml(color) + '"></div>';
    html += '<div class="flex-[4]"><input type="url" name="social_buttons[url][]" value="' + escHtml(url) + '" placeholder="URL" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"></div>';
    html += '<button type="button" onclick="deleteSocialBtn(this)" class="text-red-600 hover:text-red-800 hover:scale-110 transition-transform px-2" title="Delete"><i class="fa fa-trash-alt"></i></button>';
    html += '</div>';
    container.insertAdjacentHTML('beforeend', html);
    document.getElementById('newBtnName').value = '';
    document.getElementById('newBtnIcon').value = '💬';
    document.getElementById('newBtnColor').value = '#25D366';
    document.getElementById('newBtnUrl').value = '';
    document.getElementById('newBtnIconFile').value = '';
    document.getElementById('newBtnIconPreview').classList.add('hidden');
    closeAddSocial();
}
function deleteSocialBtn(btn) {
    if (!confirm('Delete this button?')) return;
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
function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>

<?php include 'footer.php'; ?>
