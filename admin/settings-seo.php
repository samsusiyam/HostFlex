<?php
$page_title = 'Global SEO & Meta Settings';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminRole(['admin']);

$upload_dir = '../uploads/seo/';
if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if ($key === 'submit') continue;
        $s_key = sanitize($key);
        $s_value = mysqli_real_escape_string($conn, $value);
        $check = mysqli_query($conn, "SELECT id FROM settings WHERE setting_key = '$s_key'");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "UPDATE settings SET setting_value = '$s_value' WHERE setting_key = '$s_key'");
        } else {
            mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('$s_key', '$s_value')");
        }
    }

    if (isset($_FILES['og_image_file']) && $_FILES['og_image_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['og_image_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            $fname = 'og_image_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['og_image_file']['tmp_name'], $upload_dir . $fname)) {
                $path = 'uploads/seo/' . $fname;
                mysqli_query($conn, "UPDATE settings SET setting_value = '$path' WHERE setting_key = 'og_image'");
            }
        }
    }

    logActivity('Updated SEO Settings', 'Global meta tags and OpenGraph image updated');
    header('Location: settings-seo.php?s=1');
    exit;
}

$success = isset($_GET['s']) ? 'SEO settings updated successfully!' : '';
$settings_result = mysqli_query($conn, "SELECT * FROM settings ORDER BY setting_key");
$s = []; 
while ($row = mysqli_fetch_assoc($settings_result)) { 
    $s[$row['setting_key']] = $row['setting_value']; 
}
?>
<?php include 'header.php'; ?>

<div class="space-y-6">
    
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-magnifying-glass-chart"></i></span>
                <h1 class="text-2xl font-bold text-gray-900">Global SEO & Meta Tags</h1>
            </div>
            <p class="text-xs text-gray-500">Configure global search engine rankings, meta descriptions, OpenGraph social previews, and author tags.</p>
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
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left 2 Cols: Form Fields -->
            <div class="lg:col-span-2 space-y-6">
                
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-4 text-xs">
                    <div class="flex items-center gap-2 pb-3 border-b border-gray-100">
                        <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-xs"><i class="fa-solid fa-tags"></i></span>
                        <h2 class="text-sm font-bold text-gray-900">Meta Tags & Search Ranking</h2>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Global Meta Title</label>
                        <input type="text" name="meta_title" id="seoTitleInput" value="<?php echo htmlspecialchars($s['meta_title'] ?? ($s['site_name'] ?? 'Host Nibo - Premium Web Hosting in Bangladesh')); ?>" placeholder="Host Nibo - Best Web Hosting in Bangladesh" oninput="updateGooglePreview()" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <div class="flex justify-between text-[11px] text-gray-400 mt-1">
                            <span>Recommended: 50-60 characters</span>
                            <span id="titleCount">0 / 60</span>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Global Meta Description</label>
                        <textarea name="meta_description" id="seoDescInput" rows="3" placeholder="High speed NVMe web hosting, domain registration, and cloud servers in Bangladesh." oninput="updateGooglePreview()" class="w-full border border-gray-300 rounded-xl p-3 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none leading-relaxed"><?php echo htmlspecialchars($s['meta_description'] ?? ($s['site_description'] ?? '')); ?></textarea>
                        <div class="flex justify-between text-[11px] text-gray-400 mt-1">
                            <span>Recommended: 120-160 characters</span>
                            <span id="descCount">0 / 160</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Meta Keywords</label>
                            <input type="text" name="meta_keywords" value="<?php echo htmlspecialchars($s['meta_keywords'] ?? 'hosting, cpanel, nvme hosting, domain bd, vps'); ?>" placeholder="hosting, domain, bd hosting" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            <span class="text-[11px] text-gray-400 mt-1 block">Comma-separated terms</span>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Meta Author / Publisher</label>
                            <input type="text" name="meta_author" value="<?php echo htmlspecialchars($s['meta_author'] ?? 'Host Nibo'); ?>" placeholder="Host Nibo Team" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>
                    </div>
                </div>

                <!-- OpenGraph Social Image -->
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 space-y-4 text-xs">
                    <div class="flex items-center gap-2 pb-3 border-b border-gray-100">
                        <span class="p-2 bg-purple-50 text-purple-600 rounded-lg text-xs"><i class="fa-solid fa-share-nodes"></i></span>
                        <h2 class="text-sm font-bold text-gray-900">Social Media Share Preview Image (OpenGraph)</h2>
                    </div>

                    <?php $og_image = $s['og_image'] ?? 'images/bg.png'; ?>
                    <div class="flex flex-col sm:flex-row items-center gap-4">
                        <div class="w-48 h-28 rounded-xl bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center shrink-0">
                            <img id="ogImagePreview" src="/<?php echo ltrim($og_image, '/'); ?>" class="w-full h-full object-cover" alt="OG Image">
                        </div>
                        <div class="flex-1 space-y-2 w-full">
                            <label class="block font-bold text-gray-700">Upload Social Share Banner (1200x630 px)</label>
                            <input type="file" name="og_image_file" accept="image/*" class="w-full border border-gray-300 rounded-xl p-1.5 text-[11px] bg-gray-50 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:bg-purple-50 file:text-purple-700 file:font-bold" onchange="previewOgImage(this)">
                            <input type="text" name="og_image" value="<?php echo htmlspecialchars($og_image); ?>" class="w-full border border-gray-300 rounded-xl px-3 py-1.5 text-xs font-mono">
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right 1 Col: Live Google Search Snippet Simulation -->
            <div class="space-y-6">
                
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-5">
                    <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <i class="fa-brands fa-google text-blue-600"></i> Google Search SERP Preview
                    </h3>

                    <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs space-y-1">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-6 h-6 rounded-full bg-blue-50 flex items-center justify-center text-xs font-bold text-blue-600">H</div>
                            <div class="text-[11px] leading-tight">
                                <span class="text-gray-800 font-semibold block">hostnibo.com</span>
                                <span class="text-gray-400 text-[10px]">https://hostnibo.com</span>
                            </div>
                        </div>
                        <div class="text-sm font-semibold text-blue-700 hover:underline leading-snug cursor-pointer" id="serpTitlePreview">
                            Host Nibo - Premium Web Hosting in Bangladesh
                        </div>
                        <div class="text-[12px] text-gray-600 leading-relaxed pt-1" id="serpDescPreview">
                            High speed NVMe web hosting, domain registration, and cloud servers in Bangladesh.
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-2">Live simulation of how your website appears on Google Desktop Search.</p>
                </div>

            </div>

        </div>

        <div class="flex items-center gap-3">
            <button type="submit" name="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-xs transition flex items-center gap-2 text-xs cursor-pointer">
                <i class="fa-solid fa-floppy-disk"></i> Save SEO Configuration
            </button>
        </div>

    </form>

</div>

<script>
function updateGooglePreview() {
    var title = document.getElementById('seoTitleInput').value.trim() || 'Host Nibo - Premium Web Hosting in Bangladesh';
    var desc = document.getElementById('seoDescInput').value.trim() || 'High speed NVMe web hosting, domain registration, and cloud servers in Bangladesh.';
    
    document.getElementById('serpTitlePreview').innerText = title;
    document.getElementById('serpDescPreview').innerText = desc;

    document.getElementById('titleCount').innerText = document.getElementById('seoTitleInput').value.length + ' / 60';
    document.getElementById('descCount').innerText = document.getElementById('seoDescInput').value.length + ' / 160';
}

function previewOgImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('ogImagePreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

document.addEventListener('DOMContentLoaded', updateGooglePreview);
</script>

<?php include 'footer.php'; ?>
