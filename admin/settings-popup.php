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
        if (in_array($key, ['submit', 'save_social_buttons', 'add_social_button', 'deleted_btns', 'deleted_links'])) continue;
        if ($key === 'social_buttons' || $key === 'social_links') continue;
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

    if (isset($_POST['social_links']) && is_array($_POST['social_links'])) {
        $links = [];
        $names = $_POST['social_links']['name'] ?? [];
        $icons = $_POST['social_links']['icon'] ?? [];
        $colors = $_POST['social_links']['color'] ?? [];
        $urls = $_POST['social_links']['url'] ?? [];
        $deleted_links = isset($_POST['deleted_links']) ? (array)$_POST['deleted_links'] : [];
        foreach ($names as $i => $name) {
            if (in_array($i, $deleted_links)) continue;
            if (!trim($name) || !trim($urls[$i] ?? '')) continue;
            $links[] = [
                'name' => trim($name),
                'icon' => trim($icons[$i] ?? 'fab fa-globe'),
                'color' => trim($colors[$i] ?? '#1877f2'),
                'url' => trim($urls[$i] ?? '')
            ];
        }
        saveSettingHelper2('social_links', json_encode($links));
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
            <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">"Don't show again" Label</label><input type="text" name="popup_hide_label" value="<?php echo htmlspecialchars($s['popup_hide_label'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" placeholder="Don't show again today"></div>
            <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Close Button Text</label><input type="text" name="popup_close_text" value="<?php echo htmlspecialchars($s['popup_close_text'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" placeholder="❌ Close"></div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold flex items-center"><i class="fa fa-share-alt text-blue-500 mr-2"></i> Social Links (Footer)</h2>
            <button type="button" onclick="showAddSocialLink()" class="bg-green-600 text-white px-4 py-1.5 rounded-lg hover:bg-green-700 transition shadow text-sm font-medium"><i class="fa fa-plus mr-1"></i> Add Link</button>
        </div>
        <p class="text-sm text-gray-500 mb-4">These social icons appear in the footer.</p>
        <div id="socialLinksContainer">
            <?php
            $social_links_raw = $s['social_links'] ?? '';
            $social_links = [];
            if ($social_links_raw) {
                $decoded = json_decode($social_links_raw, true);
                if (is_array($decoded)) $social_links = $decoded;
            }
            // Migrate old individual settings to new format
            if (empty($social_links)) {
                $old_links = [
                    ['name' => 'Facebook', 'icon' => 'fab fa-facebook', 'color' => '#1877f2', 'url' => $s['facebook_url'] ?? ''],
                    ['name' => 'Twitter', 'icon' => 'fab fa-twitter', 'color' => '#1da1f2', 'url' => $s['twitter_url'] ?? ''],
                    ['name' => 'LinkedIn', 'icon' => 'fab fa-linkedin', 'color' => '#0a66c2', 'url' => $s['linkedin_url'] ?? ''],
                    ['name' => 'YouTube', 'icon' => 'fab fa-youtube', 'color' => '#ff0000', 'url' => $s['youtube_url'] ?? ''],
                ];
                foreach ($old_links as $link) {
                    if (!empty($link['url'])) $social_links[] = $link;
                }
            }
            if (empty($social_links)): ?>
            <div id="noSocialLinksMsg" class="text-center py-8 text-gray-400">
                <i class="fa fa-share-alt text-4xl mb-2"></i>
                <p>No social links configured yet.</p>
            </div>
            <?php endif; ?>
            <?php foreach ($social_links as $i => $link): ?>
            <div class="social-link-row flex items-center gap-3 mb-3 p-3 bg-gray-50 rounded-lg border border-gray-200" data-idx="<?php echo $i; ?>">
                <div class="flex-[3]"><input type="text" name="social_links[name][]" value="<?php echo htmlspecialchars($link['name'] ?? ''); ?>" placeholder="Name (e.g. Facebook)" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"></div>
                <div class="w-36"><input type="text" name="social_links[icon][]" value="<?php echo htmlspecialchars($link['icon'] ?? 'fab fa-globe'); ?>" placeholder="FA class (fab fa-facebook)" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 font-mono text-xs"></div>
                <div class="w-28"><input type="text" name="social_links[color][]" value="<?php echo htmlspecialchars($link['color'] ?? '#1877f2'); ?>" placeholder="Color" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" style="border-left:4px solid <?php echo htmlspecialchars($link['color'] ?? '#1877f2'); ?>"></div>
                <div class="flex-[4]"><input type="url" name="social_links[url][]" value="<?php echo htmlspecialchars($link['url'] ?? ''); ?>" placeholder="URL" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"></div>
                <button type="button" onclick="deleteSocialLink(this)" class="text-red-600 hover:text-red-800 hover:scale-110 transition-transform px-2" title="Delete"><i class="fa fa-trash-alt"></i></button>
            </div>
            <?php endforeach; ?>
        </div>
        <div id="deletedLinksContainer"></div>
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
                    <img src="<?php echo $is_img_icon ? '../' . htmlspecialchars($btn['icon']) : ''; ?>" class="w-7 h-7 object-contain icon-preview<?php echo $is_img_icon ? '' : ' hidden'; ?>" alt="icon">
                    <span class="text-xl icon-emoji<?php echo $is_img_icon ? ' hidden' : ''; ?>"><?php echo htmlspecialchars($btn['icon'] ?? '💬'); ?></span>
                    <label class="text-[10px] text-blue-600 cursor-pointer hover:underline">Upload<input type="file" name="social_buttons[icon][<?php echo $i; ?>]" accept="image/*" class="hidden" onchange="var r=this.closest('.social-btn-row');r.querySelector('.icon-preview').src=window.URL.createObjectURL(this.files[0]);r.querySelector('.icon-preview').classList.remove('hidden');r.querySelector('.icon-preview').style.display='';r.querySelector('.icon-emoji').classList.add('hidden');r.querySelector('.icon-hidden').value=''"></label>
                    <input type="hidden" name="social_buttons[icon][]" class="icon-hidden" value="<?php echo htmlspecialchars($btn['icon'] ?? '💬'); ?>">
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

<div id="addSocialLinkModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold mb-4"><i class="fa fa-plus-circle text-green-600 mr-2"></i> Add Footer Social Link</h3>
        <div class="grid grid-cols-1 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Name</label><input type="text" id="newLinkName" placeholder="Facebook" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Font Awesome Class</label><input type="text" id="newLinkIcon" value="fab fa-facebook" placeholder="fab fa-facebook" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 font-mono text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Background Color</label><input type="text" id="newLinkColor" value="#1877f2" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">URL</label><input type="url" id="newLinkUrl" placeholder="https://facebook.com/yourpage" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
        </div>
        <div class="mt-6 flex items-center gap-3">
            <button type="button" onclick="addSocialLink()" class="bg-green-600 text-white px-5 py-2 rounded-lg hover:bg-green-700 transition shadow font-medium"><i class="fa fa-plus mr-1"></i> Add</button>
            <button type="button" onclick="closeAddSocialLink()" class="text-gray-600 px-4 py-2 border rounded-lg hover:bg-gray-50 transition"><i class="fa fa-times mr-1"></i> Cancel</button>
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
    html += '<img src="' + (hasFile ? URL.createObjectURL(fileInput.files[0]) : '') + '" class="w-7 h-7 object-contain icon-preview"' + (hasFile ? '' : ' style="display:none"') + '>';
    html += '<span class="text-xl icon-emoji' + (hasFile ? ' hidden' : '') + '">' + escHtml(icon) + '</span>';
    html += '<label class="text-[10px] text-blue-600 cursor-pointer hover:underline">Upload<input type="file" name="social_buttons[icon][' + rnd + ']" accept="image/*" class="hidden" onchange="var r=this.closest(\'.social-btn-row\');r.querySelector(\'.icon-preview\').src=window.URL.createObjectURL(this.files[0]);r.querySelector(\'.icon-preview\').style.display=\'\';r.querySelector(\'.icon-emoji\').classList.add(\'hidden\');r.querySelector(\'.icon-hidden\').value=\'\'"></label>';
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
function showAddSocialLink() {
    document.getElementById('addSocialLinkModal').classList.remove('hidden');
}
function closeAddSocialLink() {
    document.getElementById('addSocialLinkModal').classList.add('hidden');
}
function addSocialLink() {
    var name = document.getElementById('newLinkName').value.trim();
    var icon = document.getElementById('newLinkIcon').value.trim() || 'fab fa-globe';
    var color = document.getElementById('newLinkColor').value.trim() || '#1877f2';
    var url = document.getElementById('newLinkUrl').value.trim();
    if (!name || !url) { alert('Name and URL are required.'); return; }
    var container = document.getElementById('socialLinksContainer');
    var msg = document.getElementById('noSocialLinksMsg');
    if (msg) msg.style.display = 'none';
    var rnd = Date.now();
    var html = '<div class="social-link-row flex items-center gap-3 mb-3 p-3 bg-gray-50 rounded-lg border border-gray-200">';
    html += '<div class="flex-[3]"><input type="text" name="social_links[name][]" value="' + escHtml(name) + '" placeholder="Name (e.g. Facebook)" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"></div>';
    html += '<div class="w-36"><input type="text" name="social_links[icon][]" value="' + escHtml(icon) + '" placeholder="FA class" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 font-mono text-xs"></div>';
    html += '<div class="w-28"><input type="text" name="social_links[color][]" value="' + escHtml(color) + '" placeholder="Color" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" style="border-left:4px solid ' + escHtml(color) + '"></div>';
    html += '<div class="flex-[4]"><input type="url" name="social_links[url][]" value="' + escHtml(url) + '" placeholder="URL" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"></div>';
    html += '<button type="button" onclick="deleteSocialLink(this)" class="text-red-600 hover:text-red-800 hover:scale-110 transition-transform px-2" title="Delete"><i class="fa fa-trash-alt"></i></button>';
    html += '</div>';
    container.insertAdjacentHTML('beforeend', html);
    document.getElementById('newLinkName').value = '';
    document.getElementById('newLinkIcon').value = 'fab fa-globe';
    document.getElementById('newLinkColor').value = '#1877f2';
    document.getElementById('newLinkUrl').value = '';
    closeAddSocialLink();
}
function deleteSocialLink(btn) {
    if (!confirm('Delete this link?')) return;
    var row = btn.closest('.social-link-row');
    if (row) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'deleted_links[]';
        input.value = row.dataset.idx !== undefined ? row.dataset.idx : 'x';
        document.getElementById('deletedLinksContainer').appendChild(input);
        row.remove();
    }
}
function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>

<?php include 'footer.php'; ?>
