<?php
$page_title = 'Homepage Editor';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();
checkPermission('settings', 'edit');

$error = '';
$success = '';

function saveSettingHelper($key, $value) {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_sections'])) {
    validateCSRFToken($_POST['csrf_token'] ?? '');
    $deleted = isset($_POST['deleted']) ? (array)$_POST['deleted'] : [];
    $new_sections = [];
    if (isset($_POST['sections']) && is_array($_POST['sections'])) {
        foreach ($_POST['sections'] as $idx => $sec) {
            if (in_array($idx, $deleted) || in_array($idx, $_POST['deleted'] ?? [])) continue;
            $type = $sec['type'] ?? '';
            if (!$type) continue;
            $content = $sec['content'] ?? [];
            $section = [
                'type' => $type,
                'enabled' => isset($sec['enabled']) ? '1' : '0',
                'sort_order' => (int)($sec['sort_order'] ?? 0),
                'content' => []
            ];
            if ($type === 'hero') {
                $section['content']['tagline'] = $content['tagline'] ?? '';
                $section['content']['description'] = $content['description'] ?? '';
                $section['content']['image'] = $content['image'] ?? 'images/cloud.jpg';
                $section['content']['button_text'] = $content['button_text'] ?? 'Get Started';
                $section['content']['button_url'] = $content['button_url'] ?? '';
                $section['content']['chat_text'] = $content['chat_text'] ?? 'Live Chat';
                $section['content']['chat_url'] = $content['chat_url'] ?? '';
            } elseif ($type === 'domain_search') {
                $section['content']['search_url'] = $content['search_url'] ?? '';
                $pricing = [];
                if (!empty($content['pricing_items'])) {
                    $lines = array_filter(array_map('trim', explode("\n", $content['pricing_items'])));
                    foreach ($lines as $line) {
                        $parts = explode('|', $line);
                        if (count($parts) >= 2) {
                            $pricing[] = ['tld' => trim($parts[0]), 'price' => trim($parts[1])];
                        }
                    }
                }
                $section['content']['pricing'] = $pricing;
            } elseif ($type === 'features') {
                $section['content']['heading'] = $content['heading'] ?? '';
                $cards = [];
                if (!empty($content['cards_data'])) {
                    $lines = array_filter(array_map('trim', explode("\n", $content['cards_data'])));
                    foreach ($lines as $line) {
                        $parts = explode('|', $line, 3);
                        if (count($parts) >= 3) {
                            $cards[] = ['icon' => trim($parts[0]), 'title' => trim($parts[1]), 'description' => trim($parts[2])];
                        }
                    }
                } elseif (!empty($content['cards']) && is_array($content['cards'])) {
                    foreach ($content['cards'] as $card) {
                        $icon = $card['icon'] ?? '';
                        $title = $card['title'] ?? '';
                        $description = $card['description'] ?? '';
                        if ($title || $description) {
                            $cards[] = ['icon' => $icon, 'title' => $title, 'description' => $description];
                        }
                    }
                }
                $section['content']['cards'] = $cards;
            } elseif ($type === 'bottom_cta') {
                $section['content']['heading'] = $content['heading'] ?? '';
                $section['content']['description'] = $content['description'] ?? '';
                $section['content']['image'] = $content['image'] ?? 'images/tp.png';
            } elseif ($type === 'refund') {
                $section['content']['heading'] = $content['heading'] ?? '';
                $section['content']['text'] = $content['text'] ?? '';
                $section['content']['image'] = $content['image'] ?? 'images/refund.png';
            } elseif ($type === 'blog') {
                $section['content']['heading'] = $content['heading'] ?? 'Latest Blog';
                $section['content']['count'] = (int)($content['count'] ?? 3);
            } elseif ($type === 'categories') {
                $section['content']['heading'] = $content['heading'] ?? 'Our Hosting Plans';
                $section['content']['count'] = (int)($content['count'] ?? 4);
            } elseif ($type === 'testimonials') {
                $section['content']['heading'] = $content['heading'] ?? 'What Our Clients Say';
                $section['content']['count'] = (int)($content['count'] ?? 4);
            } elseif ($type === 'faqs') {
                $section['content']['heading'] = $content['heading'] ?? 'Frequently Asked Questions';
            } elseif ($type === 'partners') {
                $section['content']['heading'] = $content['heading'] ?? 'Our Partners';
            } elseif ($type === 'custom_html') {
                $section['content']['html'] = $content['html'] ?? '';
            }
            $new_sections[] = $section;
        }
    }
    $json_value = json_encode($new_sections, JSON_PRETTY_PRINT);
    saveSettingHelper('homepage_sections', $json_value);

    foreach ($new_sections as $sec) {
        $c = $sec['content'];
        if ($sec['type'] === 'hero') {
            saveSettingHelper('hero_tagline', $c['tagline'] ?? '');
            saveSettingHelper('hero_description', $c['description'] ?? '');
            saveSettingHelper('hero_image', $c['image'] ?? 'images/cloud.jpg');
            saveSettingHelper('hero_button_text', $c['button_text'] ?? 'Get Started');
            saveSettingHelper('hero_button_url', $c['button_url'] ?? '');
            saveSettingHelper('hero_chat_text', $c['chat_text'] ?? 'Live Chat');
            saveSettingHelper('hero_chat_url', $c['chat_url'] ?? '');
        } elseif ($sec['type'] === 'features') {
            saveSettingHelper('features_section_enabled', $sec['enabled']);
            saveSettingHelper('features_heading', $c['heading'] ?? '');
            $lines = [];
            if (!empty($c['cards'])) {
                foreach ($c['cards'] as $card) {
                    $lines[] = ($card['icon'] ?? '') . '|' . ($card['title'] ?? '') . '|' . ($card['description'] ?? '');
                }
            }
            saveSettingHelper('features_data', implode("\n", $lines));
        } elseif ($sec['type'] === 'bottom_cta') {
            saveSettingHelper('bottom_cta_enabled', $sec['enabled']);
            saveSettingHelper('bottom_cta_heading', $c['heading'] ?? '');
            saveSettingHelper('bottom_cta_description', $c['description'] ?? '');
            saveSettingHelper('bottom_cta_image', $c['image'] ?? 'images/tp.png');
        } elseif ($sec['type'] === 'refund') {
            saveSettingHelper('refund_section_enabled', $sec['enabled']);
            saveSettingHelper('refund_heading', $c['heading'] ?? '');
            saveSettingHelper('refund_text', $c['text'] ?? '');
            saveSettingHelper('refund_image', $c['image'] ?? 'images/refund.png');
        } elseif ($sec['type'] === 'domain_search') {
            saveSettingHelper('whmcs_domain_search_url', $c['search_url'] ?? '');
        }
    }
    header('Location: settings-homepage.php?s=1');
    exit;
}
if (isset($_GET['s'])) {
    $success = 'Homepage sections saved successfully!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_section_img'])) {
    validateCSRFToken($_POST['csrf_token'] ?? '');
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $v = validateImageUpload($_FILES['image'], ['jpg','jpeg','png','gif','webp']);
        if ($v === true) {
            $upload_dir = '../uploads/homepage/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $fname = 'section_' . time() . '_' . rand(100,999) . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $fname);
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'path' => 'uploads/homepage/' . $fname]);
            exit;
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['ok' => false]);
    exit;
}
if (!isset($sections)) {
    $raw = getSetting('homepage_sections');
    $sections = [];
    if ($raw) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) $sections = $decoded;
    }
    if (empty($sections)) {
        $sections = [
            ['type' => 'hero', 'enabled' => getSetting('hero_tagline') !== '' ? '1' : '1', 'sort_order' => 1, 'content' => [
                'tagline' => getSetting('hero_tagline') ?: getSetting('site_tagline') ?: 'Fast & Reliable Web Hosting',
                'description' => getSetting('hero_description') ?: getSetting('site_description') ?: 'Experience premium hosting with exceptional performance.',
                'image' => getSetting('hero_image') ?: 'images/cloud.jpg',
                'button_text' => getSetting('hero_button_text') ?: 'Get Started',
                'button_url' => getSetting('hero_button_url') ?: '',
                'chat_text' => getSetting('hero_chat_text') ?: 'Live Chat',
                'chat_url' => getSetting('hero_chat_url') ?: ''
            ]],
            ['type' => 'domain_search', 'enabled' => '1', 'sort_order' => 2, 'content' => [
                'search_url' => getSetting('whmcs_domain_search_url') ?: '#',
                'pricing' => [
                    ['tld' => '.com', 'price' => '999'],
                    ['tld' => '.online', 'price' => '455'],
                    ['tld' => '.xyz', 'price' => '250']
                ]
            ]],
            ['type' => 'features', 'enabled' => getSetting('features_section_enabled') !== '' ? getSetting('features_section_enabled') : '1', 'sort_order' => 3, 'content' => [
                'heading' => getSetting('features_heading') ?: 'Why Choose Us',
                'cards' => []
            ]],
            ['type' => 'bottom_cta', 'enabled' => getSetting('bottom_cta_enabled') !== '' ? getSetting('bottom_cta_enabled') : '1', 'sort_order' => 4, 'content' => [
                'heading' => getSetting('bottom_cta_heading') ?: 'Do you have any questions?',
                'description' => getSetting('bottom_cta_description') ?: '',
                'image' => getSetting('bottom_cta_image') ?: 'images/tp.png'
            ]],
            ['type' => 'refund', 'enabled' => getSetting('refund_section_enabled') !== '' ? getSetting('refund_section_enabled') : '1', 'sort_order' => 5, 'content' => [
                'heading' => getSetting('refund_heading') ?: '7-Day Money Back Guarantee',
                'text' => getSetting('refund_text') ?: '',
                'image' => getSetting('refund_image') ?: 'images/refund.png'
            ]]
        ];
    }
}

$section_types = ['hero', 'domain_search', 'features', 'blog', 'categories', 'testimonials', 'faqs', 'partners', 'bottom_cta', 'refund', 'custom_html'];
$type_labels = ['hero' => 'Hero', 'domain_search' => 'Domain Search', 'features' => 'Features', 'blog' => 'Blog', 'categories' => 'Categories', 'testimonials' => 'Testimonials', 'faqs' => 'FAQ', 'partners' => 'Partners', 'bottom_cta' => 'Bottom CTA', 'refund' => 'Refund', 'custom_html' => 'Custom HTML'];
$type_colors = ['hero' => 'blue', 'domain_search' => 'purple', 'features' => 'green', 'blog' => 'cyan', 'categories' => 'red', 'testimonials' => 'yellow', 'faqs' => 'indigo', 'partners' => 'pink', 'bottom_cta' => 'orange', 'refund' => 'teal', 'custom_html' => 'gray'];
$type_icons = ['hero' => 'fa-film', 'domain_search' => 'fa-search', 'features' => 'fa-th-large', 'blog' => 'fa-blog', 'categories' => 'fa-th-large', 'testimonials' => 'fa-star', 'faqs' => 'fa-question-circle', 'partners' => 'fa-handshake', 'bottom_cta' => 'fa-question-circle', 'refund' => 'fa-shield-alt', 'custom_html' => 'fa-code'];
?>
<?php include 'header.php'; ?>
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Homepage Editor</h1>
        <p class="text-gray-500">Drag, edit, and manage homepage sections</p>
    </div>
</div>

<?php if ($success): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4"><?php echo $error; ?></div><?php endif; ?>

<form method="POST" id="sectionsForm" enctype="multipart/form-data">
<?= csrfField() ?>
<div id="sectionsContainer">
<?php foreach ($sections as $idx => $section):
    $type = $section['type'];
    $content = $section['content'] ?? [];
    $color = $type_colors[$type] ?? 'gray';
    $icon = $type_icons[$type] ?? 'fa-cog';
    $label = $type_labels[$type] ?? ucfirst($type);
    $enabled = ($section['enabled'] ?? '1') === '1';
?>
<div class="section-card bg-white rounded-xl shadow-md border border-gray-200 mb-5 overflow-hidden" data-index="<?php echo $idx; ?>">
    <div class="flex items-center justify-between px-5 py-3 bg-gray-50 border-b border-gray-200">
        <div class="flex items-center gap-3 flex-1">
            <span class="text-gray-400 cursor-move"><i class="fa fa-grip-vertical"></i></span>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-700"><i class="fa <?php echo $icon; ?> mr-1"></i> <?php echo $label; ?></span>
            <input type="number" name="sections[<?php echo $idx; ?>][sort_order]" value="<?php echo $section['sort_order'] ?? 0; ?>" class="w-16 text-center border rounded text-sm px-2 py-1" placeholder="Order">
            <input type="hidden" name="sections[<?php echo $idx; ?>][type]" value="<?php echo $type; ?>">
        </div>
        <div class="flex items-center gap-3">
            <label class="flex items-center cursor-pointer select-none text-sm">
                <input type="checkbox" name="sections[<?php echo $idx; ?>][enabled]" value="1" <?php echo $enabled ? 'checked' : ''; ?> class="mr-1.5 rounded">
                <span class="text-gray-600 font-medium">Active</span>
            </label>
            <button type="button" onclick="toggleEdit(<?php echo $idx; ?>)" class="text-blue-600 hover:text-blue-800 hover:scale-110 transition-transform" title="Edit"><i class="fa fa-pencil-alt"></i></button>
            <button type="button" onclick="deleteSection(<?php echo $idx; ?>)" class="text-red-600 hover:text-red-800 hover:scale-110 transition-transform" title="Delete"><i class="fa fa-trash-alt"></i></button>
        </div>
    </div>
    <div class="section-edit-body px-5 py-4 <?php echo $idx === 0 ? '' : 'hidden'; ?>" id="editBody<?php echo $idx; ?>">
        <?php if ($type === 'hero'): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Tagline</label><input type="text" name="sections[<?php echo $idx; ?>][content][tagline]" value="<?php echo htmlspecialchars($content['tagline'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
            <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea name="sections[<?php echo $idx; ?>][content][description]" rows="3" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($content['description'] ?? ''); ?></textarea></div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                <div class="flex items-center gap-2">
                    <input type="text" name="sections[<?php echo $idx; ?>][content][image]" value="<?php echo htmlspecialchars($content['image'] ?? 'images/cloud.jpg'); ?>" class="flex-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" id="heroImg<?php echo $idx; ?>">
                    <label class="bg-gray-200 text-gray-700 px-3 py-2 rounded cursor-pointer text-sm hover:bg-gray-300 whitespace-nowrap"><i class="fa fa-upload mr-1"></i> Upload<input type="file" accept="image/*" class="hidden" onchange="uploadSectionImg(this, 'heroImg<?php echo $idx; ?>', 'heroPrev<?php echo $idx; ?>')"></label>
                </div>
                <img src="../<?php echo htmlspecialchars($content['image'] ?? 'images/cloud.jpg'); ?>" class="mt-2 max-h-24 rounded border" id="heroPrev<?php echo $idx; ?>">
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Button Text</label><input type="text" name="sections[<?php echo $idx; ?>][content][button_text]" value="<?php echo htmlspecialchars($content['button_text'] ?? 'Get Started'); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Button URL</label><input type="text" name="sections[<?php echo $idx; ?>][content][button_url]" value="<?php echo htmlspecialchars($content['button_url'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Chat Text</label><input type="text" name="sections[<?php echo $idx; ?>][content][chat_text]" value="<?php echo htmlspecialchars($content['chat_text'] ?? 'Live Chat'); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Chat URL</label><input type="text" name="sections[<?php echo $idx; ?>][content][chat_url]" value="<?php echo htmlspecialchars($content['chat_url'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
        </div>
        <?php elseif ($type === 'domain_search'): ?>
        <div class="grid grid-cols-1 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Search URL</label><input type="text" name="sections[<?php echo $idx; ?>][content][search_url]" value="<?php echo htmlspecialchars($content['search_url'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pricing Items</label>
                <textarea name="sections[<?php echo $idx; ?>][content][pricing_items]" rows="5" class="w-full border rounded-lg px-3 py-2 font-mono text-sm focus:ring-2 focus:ring-blue-500"><?php
                    $pricing = $content['pricing'] ?? [];
                    foreach ($pricing as $p) {
                        echo htmlspecialchars(($p['tld'] ?? '') . '|' . ($p['price'] ?? '')) . "\n";
                    }
                ?></textarea>
                <p class="text-xs text-gray-400 mt-1">One per line: TLD|Price (e.g., .com|999)</p>
            </div>
        </div>
        <?php elseif ($type === 'features'): ?>
        <?php
        $cards = $content['cards'] ?? [];
        if (empty($cards)) {
            $features_data_raw = getSetting('features_data');
            if (trim($features_data_raw ?? '') !== '') {
                $lines = explode("\n", $features_data_raw);
                foreach ($lines as $line) {
                    $parts = explode('|', $line, 3);
                    if (count($parts) >= 3) {
                        $cards[] = ['icon' => trim($parts[0]), 'title' => trim($parts[1]), 'description' => trim($parts[2])];
                    }
                }
            }
        }
        ?>
        <div class="grid grid-cols-1 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Heading</label><input type="text" name="sections[<?php echo $idx; ?>][content][heading]" value="<?php echo htmlspecialchars($content['heading'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Feature Cards</label>
                <div id="featuresContainer<?php echo $idx; ?>" class="space-y-3" data-card-count="<?php echo count($cards); ?>">
                    <?php foreach ($cards as $ci => $card): ?>
                    <div class="card-item border rounded-lg p-3 bg-gray-50">
                        <div class="flex gap-2 mb-2">
                            <div class="flex-1">
                                <input type="text" name="sections[<?php echo $idx; ?>][content][cards][<?php echo $ci; ?>][icon]" placeholder="Icon path" value="<?php echo htmlspecialchars($card['icon'] ?? ''); ?>" class="w-full border rounded px-3 py-2 text-sm" id="cardIcon<?php echo $idx; ?>_<?php echo $ci; ?>">
                            </div>
                            <label class="bg-gray-200 text-gray-700 px-3 py-2 rounded cursor-pointer text-sm hover:bg-gray-300 whitespace-nowrap"><i class="fa fa-upload"></i><input type="file" accept="image/*" class="hidden" onchange="uploadSectionImg(this, 'cardIcon<?php echo $idx; ?>_<?php echo $ci; ?>', 'cardPrev<?php echo $idx; ?>_<?php echo $ci; ?>')"></label>
                            <button type="button" onclick="this.closest('.card-item').remove()" class="text-red-600 hover:text-red-800 px-2"><i class="fa fa-trash"></i></button>
                        </div>
                        <img src="../<?php echo htmlspecialchars($card['icon'] ?? ''); ?>" class="mb-2 max-h-16 rounded border" id="cardPrev<?php echo $idx; ?>_<?php echo $ci; ?>" onerror="this.style.display='none'">
                        <input type="text" name="sections[<?php echo $idx; ?>][content][cards][<?php echo $ci; ?>][title]" placeholder="Title" value="<?php echo htmlspecialchars($card['title'] ?? ''); ?>" class="w-full border rounded px-3 py-2 text-sm mb-2">
                        <textarea name="sections[<?php echo $idx; ?>][content][cards][<?php echo $ci; ?>][description]" placeholder="Description" rows="2" class="w-full border rounded px-3 py-2 text-sm"><?php echo htmlspecialchars($card['description'] ?? ''); ?></textarea>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" onclick="addFeatureCard(<?php echo $idx; ?>)" class="mt-2 text-sm bg-green-600 text-white px-3 py-1.5 rounded hover:bg-green-700"><i class="fa fa-plus mr-1"></i> Add Card</button>
            </div>
        </div>
        <?php elseif ($type === 'bottom_cta'): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Heading</label><input type="text" name="sections[<?php echo $idx; ?>][content][heading]" value="<?php echo htmlspecialchars($content['heading'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
            <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea name="sections[<?php echo $idx; ?>][content][description]" rows="3" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($content['description'] ?? ''); ?></textarea></div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                <div class="flex items-center gap-2">
                    <input type="text" name="sections[<?php echo $idx; ?>][content][image]" value="<?php echo htmlspecialchars($content['image'] ?? 'images/tp.png'); ?>" class="flex-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" id="ctaImg<?php echo $idx; ?>">
                    <label class="bg-gray-200 text-gray-700 px-3 py-2 rounded cursor-pointer text-sm hover:bg-gray-300 whitespace-nowrap"><i class="fa fa-upload mr-1"></i> Upload<input type="file" accept="image/*" class="hidden" onchange="uploadSectionImg(this, 'ctaImg<?php echo $idx; ?>', 'ctaPrev<?php echo $idx; ?>')"></label>
                </div>
                <img src="../<?php echo htmlspecialchars($content['image'] ?? 'images/tp.png'); ?>" class="mt-2 max-h-24 rounded border" id="ctaPrev<?php echo $idx; ?>">
            </div>
        </div>
        <?php elseif ($type === 'refund'): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Heading</label><input type="text" name="sections[<?php echo $idx; ?>][content][heading]" value="<?php echo htmlspecialchars($content['heading'] ?? ''); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
            <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Text</label><textarea name="sections[<?php echo $idx; ?>][content][text]" rows="3" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($content['text'] ?? ''); ?></textarea></div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                <div class="flex items-center gap-2">
                    <input type="text" name="sections[<?php echo $idx; ?>][content][image]" value="<?php echo htmlspecialchars($content['image'] ?? 'images/refund.png'); ?>" class="flex-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" id="refundImg<?php echo $idx; ?>">
                    <label class="bg-gray-200 text-gray-700 px-3 py-2 rounded cursor-pointer text-sm hover:bg-gray-300 whitespace-nowrap"><i class="fa fa-upload mr-1"></i> Upload<input type="file" accept="image/*" class="hidden" onchange="uploadSectionImg(this, 'refundImg<?php echo $idx; ?>', 'refundPrev<?php echo $idx; ?>')"></label>
                </div>
                <img src="../<?php echo htmlspecialchars($content['image'] ?? 'images/refund.png'); ?>" class="mt-2 max-h-24 rounded border" id="refundPrev<?php echo $idx; ?>">
            </div>
        </div>
        <?php elseif ($type === 'blog'): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Heading</label><input type="text" name="sections[<?php echo $idx; ?>][content][heading]" value="<?php echo htmlspecialchars($content['heading'] ?? 'Latest Blog'); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Posts to Show</label><input type="number" name="sections[<?php echo $idx; ?>][content][count]" value="<?php echo (int)($content['count'] ?? 3); ?>" min="1" max="20" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
        </div>
        <?php elseif ($type === 'categories'): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Heading</label><input type="text" name="sections[<?php echo $idx; ?>][content][heading]" value="<?php echo htmlspecialchars($content['heading'] ?? 'Our Hosting Plans'); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Categories to Show</label><input type="number" name="sections[<?php echo $idx; ?>][content][count]" value="<?php echo (int)($content['count'] ?? 4); ?>" min="1" max="20" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
        </div>
        <?php elseif ($type === 'testimonials'): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Heading</label><input type="text" name="sections[<?php echo $idx; ?>][content][heading]" value="<?php echo htmlspecialchars($content['heading'] ?? 'What Our Clients Say'); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Count to Show</label><input type="number" name="sections[<?php echo $idx; ?>][content][count]" value="<?php echo (int)($content['count'] ?? 4); ?>" min="1" max="20" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
        </div>
        <p class="text-xs text-gray-400 mt-2">Manage testimonials from <a href="testimonials.php" class="text-blue-600 hover:underline">Testimonials page</a></p>
        <?php elseif ($type === 'faqs'): ?>
        <div class="grid grid-cols-1 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Heading</label><input type="text" name="sections[<?php echo $idx; ?>][content][heading]" value="<?php echo htmlspecialchars($content['heading'] ?? 'Frequently Asked Questions'); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
        </div>
        <p class="text-xs text-gray-400 mt-2">Manage FAQs from <a href="faqs.php" class="text-blue-600 hover:underline">FAQs page</a></p>
        <?php elseif ($type === 'partners'): ?>
        <div class="grid grid-cols-1 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Heading</label><input type="text" name="sections[<?php echo $idx; ?>][content][heading]" value="<?php echo htmlspecialchars($content['heading'] ?? 'Our Partners'); ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>
        </div>
        <p class="text-xs text-gray-400 mt-2">Manage partners from <a href="partners.php" class="text-blue-600 hover:underline">Partners page</a></p>
        <?php elseif ($type === 'custom_html'): ?>
        <div class="grid grid-cols-1 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Raw HTML Content</label><textarea name="sections[<?php echo $idx; ?>][content][html]" rows="8" class="w-full border rounded-lg px-3 py-2 font-mono text-sm focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($content['html'] ?? ''); ?></textarea></div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<div id="deletedContainer"></div>

<div class="flex items-center gap-4 mb-6">
    <button type="submit" name="save_sections" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition shadow font-medium"><i class="fa fa-save mr-1"></i> Save All Sections</button>
    <button type="button" onclick="showAddSection()" class="bg-green-600 text-white px-5 py-2.5 rounded-lg hover:bg-green-700 transition shadow font-medium"><i class="fa fa-plus-circle mr-1"></i> Add Section</button>
</div>
</form>

<div id="addSectionModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold mb-4"><i class="fa fa-plus-circle text-green-600 mr-2"></i> Add New Section</h3>
        <div class="grid grid-cols-2 gap-3">
            <?php foreach ($section_types as $st): ?>
            <?php if ($st === 'custom_html') continue; ?>
            <button type="button" onclick="addNewSection('<?php echo $st; ?>')" class="flex flex-col items-center gap-2 p-4 border-2 border-dashed border-gray-300 rounded-xl hover:border-<?php echo $type_colors[$st]; ?>-500 hover:bg-<?php echo $type_colors[$st]; ?>-50 transition-all">
                <i class="fa <?php echo $type_icons[$st]; ?> text-2xl text-<?php echo $type_colors[$st]; ?>-600"></i>
                <span class="text-sm font-medium text-gray-700"><?php echo $type_labels[$st]; ?></span>
            </button>
            <?php endforeach; ?>
        </div>
        <button type="button" onclick="closeAddSection()" class="mt-4 w-full text-center text-gray-500 hover:text-gray-700 text-sm font-medium"><i class="fa fa-times mr-1"></i> Cancel</button>
    </div>
</div>

<script>
var sectionIndex = <?php echo count($sections); ?>;
var featureCardIdx = Date.now();

function uploadSectionImg(input, inputId, previewId) {
    var file = input.files[0];
    if (!file) return;
    var fd = new FormData();
    fd.append('image', file);
    fd.append('upload_section_img', '1');
    fetch('', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.ok && data.path) {
            document.getElementById(inputId).value = data.path;
            var prev = document.getElementById(previewId);
            if (prev) { prev.src = '../' + data.path; prev.style.display = ''; }
        }
    });
}

function addFeatureCard(idx) {
    var ci = featureCardIdx++;
    var container = document.getElementById('featuresContainer' + idx);
    if (!container) return;
    var div = document.createElement('div');
    div.className = 'card-item border rounded-lg p-3 bg-gray-50';
    div.innerHTML = '<div class="flex gap-2 mb-2">' +
        '<div class="flex-1"><input type="text" name="sections[' + idx + '][content][cards][' + ci + '][icon]" placeholder="Icon path" class="w-full border rounded px-3 py-2 text-sm" id="cardIcon' + idx + '_' + ci + '"></div>' +
        '<label class="bg-gray-200 text-gray-700 px-3 py-2 rounded cursor-pointer text-sm hover:bg-gray-300 whitespace-nowrap"><i class="fa fa-upload"></i><input type="file" accept="image/*" class="hidden" onchange="uploadSectionImg(this, \'cardIcon' + idx + '_' + ci + '\', \'cardPrev' + idx + '_' + ci + '\')"></label>' +
        '<button type="button" onclick="this.closest(\'.card-item\').remove()" class="text-red-600 hover:text-red-800 px-2"><i class="fa fa-trash"></i></button></div>' +
        '<img class="mb-2 max-h-16 rounded border hidden" id="cardPrev' + idx + '_' + ci + '">' +
        '<input type="text" name="sections[' + idx + '][content][cards][' + ci + '][title]" placeholder="Title" class="w-full border rounded px-3 py-2 text-sm mb-2">' +
        '<textarea name="sections[' + idx + '][content][cards][' + ci + '][description]" placeholder="Description" rows="2" class="w-full border rounded px-3 py-2 text-sm"></textarea>';
    container.appendChild(div);
}

function toggleEdit(idx) {
    var body = document.getElementById('editBody' + idx);
    if (body) body.classList.toggle('hidden');
}

function deleteSection(idx) {
    if (!confirm('Delete this section?')) return;
    var card = document.querySelector('.section-card[data-index="' + idx + '"]');
    if (card) {
        card.style.display = 'none';
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'deleted[]';
        input.value = idx;
        document.getElementById('deletedContainer').appendChild(input);
    }
}

function showAddSection() {
    document.getElementById('addSectionModal').classList.remove('hidden');
}

function closeAddSection() {
    document.getElementById('addSectionModal').classList.add('hidden');
}

function addNewSection(type) {
    closeAddSection();
    var idx = sectionIndex++;
    var container = document.getElementById('sectionsContainer');
    var html = getSectionHTML(type, idx);
    container.insertAdjacentHTML('beforeend', html);
    var body = document.getElementById('editBody' + idx);
    if (body) body.classList.remove('hidden');
}

function getSectionHTML(type, idx) {
    var common = '<div class="section-card bg-white rounded-xl shadow-md border border-gray-200 mb-5 overflow-hidden" data-index="' + idx + '">';
    common += '<div class="flex items-center justify-between px-5 py-3 bg-gray-50 border-b border-gray-200">';
    common += '<div class="flex items-center gap-3 flex-1">';
    common += '<span class="text-gray-400 cursor-move"><i class="fa fa-grip-vertical"></i></span>';
    var labels = <?php echo json_encode($type_labels); ?>;
    var colors = <?php echo json_encode($type_colors); ?>;
    var icons = <?php echo json_encode($type_icons); ?>;
    common += '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-' + colors[type] + '-100 text-' + colors[type] + '-700"><i class="fa ' + icons[type] + ' mr-1"></i> ' + labels[type] + '</span>';
    common += '<input type="number" name="sections[' + idx + '][sort_order]" value="0" class="w-16 text-center border rounded text-sm px-2 py-1" placeholder="Order">';
    common += '<input type="hidden" name="sections[' + idx + '][type]" value="' + type + '">';
    common += '</div><div class="flex items-center gap-3">';
    common += '<label class="flex items-center cursor-pointer select-none text-sm"><input type="checkbox" name="sections[' + idx + '][enabled]" value="1" checked class="mr-1.5 rounded"><span class="text-gray-600 font-medium">Active</span></label>';
    common += '<button type="button" onclick="toggleEdit(' + idx + ')" class="text-blue-600 hover:text-blue-800 transition-transform" title="Edit"><i class="fa fa-pencil-alt"></i></button>';
    common += '<button type="button" onclick="deleteSection(' + idx + ')" class="text-red-600 hover:text-red-800 transition-transform" title="Delete"><i class="fa fa-trash-alt"></i></button>';
    common += '</div></div>';
    common += '<div class="section-edit-body px-5 py-4" id="editBody' + idx + '">';

    var body = '';
    if (type === 'hero') {
        body = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
        body += '<div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Tagline</label><input type="text" name="sections[' + idx + '][content][tagline]" value="" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>';
        body += '<div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea name="sections[' + idx + '][content][description]" rows="3" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></textarea></div>';
        body += '<div><label class="block text-sm font-medium text-gray-700 mb-1">Image</label><div class="flex items-center gap-2"><input type="text" name="sections[' + idx + '][content][image]" value="images/cloud.jpg" class="flex-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" id="heroImg' + idx + '"><label class="bg-gray-200 text-gray-700 px-3 py-2 rounded cursor-pointer text-sm hover:bg-gray-300 whitespace-nowrap"><i class="fa fa-upload mr-1"></i> Upload<input type="file" accept="image/*" class="hidden" onchange="uploadSectionImg(this, \'heroImg' + idx + '\', \'heroPrev' + idx + '\')"></label></div><img src="../images/cloud.jpg" class="mt-2 max-h-24 rounded border" id="heroPrev' + idx + '"></div>';
        body += '<div><label class="block text-sm font-medium text-gray-700 mb-1">Button Text</label><input type="text" name="sections[' + idx + '][content][button_text]" value="Get Started" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>';
        body += '<div><label class="block text-sm font-medium text-gray-700 mb-1">Button URL</label><input type="text" name="sections[' + idx + '][content][button_url]" value="" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>';
        body += '<div><label class="block text-sm font-medium text-gray-700 mb-1">Chat Text</label><input type="text" name="sections[' + idx + '][content][chat_text]" value="Live Chat" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>';
        body += '<div><label class="block text-sm font-medium text-gray-700 mb-1">Chat URL</label><input type="text" name="sections[' + idx + '][content][chat_url]" value="" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>';
        body += '</div>';
    } else if (type === 'domain_search') {
        body = '<div class="grid grid-cols-1 gap-4">';
        body += '<div><label class="block text-sm font-medium text-gray-700 mb-1">Search URL</label><input type="text" name="sections[' + idx + '][content][search_url]" value="" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>';
        body += '<div><label class="block text-sm font-medium text-gray-700 mb-1">Pricing Items (one per line: TLD|Price)</label><textarea name="sections[' + idx + '][content][pricing_items]" rows="5" class="w-full border rounded-lg px-3 py-2 font-mono text-sm focus:ring-2 focus:ring-blue-500">.com|999\n.online|455\n.xyz|250</textarea></div>';
        body += '</div>';
    } else if (type === 'features') {
        body = '<div class="grid grid-cols-1 gap-4">';
        body += '<div><label class="block text-sm font-medium text-gray-700 mb-1">Heading</label><input type="text" name="sections[' + idx + '][content][heading]" value="Why Choose Us" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>';
        body += '<div><label class="block text-sm font-medium text-gray-700 mb-2">Feature Cards</label><div id="featuresContainer' + idx + '" class="space-y-3" data-card-count="0"></div>';
        body += '<button type="button" onclick="addFeatureCard(' + idx + ')" class="mt-2 text-sm bg-green-600 text-white px-3 py-1.5 rounded hover:bg-green-700"><i class="fa fa-plus mr-1"></i> Add Card</button></div>';
        body += '</div>';
    } else if (type === 'bottom_cta') {
        body = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
        body += '<div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Heading</label><input type="text" name="sections[' + idx + '][content][heading]" value="Do you have any questions?" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>';
        body += '<div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea name="sections[' + idx + '][content][description]" rows="3" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></textarea></div>';
        body += '<div><label class="block text-sm font-medium text-gray-700 mb-1">Image</label><div class="flex items-center gap-2"><input type="text" name="sections[' + idx + '][content][image]" value="images/tp.png" class="flex-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" id="ctaImg' + idx + '"><label class="bg-gray-200 text-gray-700 px-3 py-2 rounded cursor-pointer text-sm hover:bg-gray-300 whitespace-nowrap"><i class="fa fa-upload mr-1"></i> Upload<input type="file" accept="image/*" class="hidden" onchange="uploadSectionImg(this, \'ctaImg' + idx + '\', \'ctaPrev' + idx + '\')"></label></div><img src="../images/tp.png" class="mt-2 max-h-24 rounded border" id="ctaPrev' + idx + '"></div>';
        body += '</div>';
    } else if (type === 'refund') {
        body = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
        body += '<div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Heading</label><input type="text" name="sections[' + idx + '][content][heading]" value="7-Day Money Back Guarantee" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>';
        body += '<div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Text</label><textarea name="sections[' + idx + '][content][text]" rows="3" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></textarea></div>';
        body += '<div><label class="block text-sm font-medium text-gray-700 mb-1">Image</label><div class="flex items-center gap-2"><input type="text" name="sections[' + idx + '][content][image]" value="images/refund.png" class="flex-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" id="refundImg' + idx + '"><label class="bg-gray-200 text-gray-700 px-3 py-2 rounded cursor-pointer text-sm hover:bg-gray-300 whitespace-nowrap"><i class="fa fa-upload mr-1"></i> Upload<input type="file" accept="image/*" class="hidden" onchange="uploadSectionImg(this, \'refundImg' + idx + '\', \'refundPrev' + idx + '\')"></label></div><img src="../images/refund.png" class="mt-2 max-h-24 rounded border" id="refundPrev' + idx + '"></div>';
        body += '</div>';
    } else if (type === 'blog') {
        body = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
        body += '<div><label class="block text-sm font-medium text-gray-700 mb-1">Heading</label><input type="text" name="sections[' + idx + '][content][heading]" value="Latest Blog" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>';
        body += '<div><label class="block text-sm font-medium text-gray-700 mb-1">Posts to Show</label><input type="number" name="sections[' + idx + '][content][count]" value="3" min="1" max="20" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>';
        body += '</div>';
    } else if (type === 'categories') {
        body = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
        body += '<div><label class="block text-sm font-medium text-gray-700 mb-1">Heading</label><input type="text" name="sections[' + idx + '][content][heading]" value="Our Hosting Plans" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>';
        body += '<div><label class="block text-sm font-medium text-gray-700 mb-1">Categories to Show</label><input type="number" name="sections[' + idx + '][content][count]" value="4" min="1" max="20" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>';
        body += '</div>';
    } else if (type === 'testimonials') {
        body = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
        body += '<div><label class="block text-sm font-medium text-gray-700 mb-1">Heading</label><input type="text" name="sections[' + idx + '][content][heading]" value="What Our Clients Say" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>';
        body += '<div><label class="block text-sm font-medium text-gray-700 mb-1">Count to Show</label><input type="number" name="sections[' + idx + '][content][count]" value="4" min="1" max="20" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>';
        body += '</div>';
    } else if (type === 'faqs') {
        body = '<div class="grid grid-cols-1 gap-4">';
        body += '<div><label class="block text-sm font-medium text-gray-700 mb-1">Heading</label><input type="text" name="sections[' + idx + '][content][heading]" value="Frequently Asked Questions" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>';
        body += '</div>';
    } else if (type === 'partners') {
        body = '<div class="grid grid-cols-1 gap-4">';
        body += '<div><label class="block text-sm font-medium text-gray-700 mb-1">Heading</label><input type="text" name="sections[' + idx + '][content][heading]" value="Our Partners" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"></div>';
        body += '</div>';
    } else if (type === 'custom_html') {
        body = '<div class="grid grid-cols-1 gap-4">';
        body += '<div><label class="block text-sm font-medium text-gray-700 mb-1">Raw HTML</label><textarea name="sections[' + idx + '][content][html]" rows="8" class="w-full border rounded-lg px-3 py-2 font-mono text-sm focus:ring-2 focus:ring-blue-500"></textarea></div>';
        body += '</div>';
    }

    common += body + '</div></div>';
    return common;
}

function initSortable() {
    var container = document.getElementById('sectionsContainer');
    if (typeof Sortable !== 'undefined' && container) {
        Sortable.create(container, {
            handle: '.fa-grip-vertical',
            animation: 150,
            ghostClass: 'bg-blue-50',
            onEnd: function() {
                var cards = container.querySelectorAll('.section-card');
                cards.forEach(function(card, idx) {
                    var orderInput = card.querySelector('input[name*="[sort_order]"]');
                    if (orderInput) orderInput.value = (idx + 1) * 10;
                });
            }
        });
    }
}
document.addEventListener('DOMContentLoaded', initSortable);
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<?php include 'footer.php'; ?>
