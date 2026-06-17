<?php require_once 'config/database.php'; require_once 'includes/functions.php';

// Load social_buttons JSON
$social_buttons_raw = getSetting('social_buttons');
$social_buttons = [];
if ($social_buttons_raw) {
    $decoded = json_decode($social_buttons_raw, true);
    if (is_array($decoded)) $social_buttons = $decoded;
}
// Fallback: if no JSON social buttons, use individual settings
if (empty($social_buttons)) {
    $wa = getSetting('whatsapp_number');
    $wag = getSetting('whatsapp_group');
    $tg = getSetting('telegram_link');
    if ($wa) $social_buttons[] = ['name'=>'WhatsApp','icon'=>'💬','color'=>'#25D366','url'=>'https://wa.me/'.$wa];
    if ($wag) $social_buttons[] = ['name'=>'WhatsApp Group','icon'=>'👥','color'=>'#128C7E','url'=>$wag];
    if ($tg) $social_buttons[] = ['name'=>'Telegram','icon'=>'📨','color'=>'#0088cc','url'=>$tg];
}
$fab_enabled = getSetting('fab_enabled');
$fab_icon = getSetting('fab_icon') ?: '💬';
$popup_enabled = getSetting('popup_notice_enabled');
?>
<?php if ($fab_enabled === '' || $fab_enabled === '1'): ?>
<style>
.fab-container { position: fixed; bottom: 25px; right: 25px; z-index: 9999; display: flex; flex-direction: column; align-items: flex-end; }
.fab-button { width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #0d6efd, #6610f2); box-shadow: 0 6px 18px rgba(0,0,0,0.25); display: flex; justify-content: center; align-items: center; color: white; font-size: 26px; cursor: pointer; transition: all 0.3s ease; }
.fab-button:hover { transform: rotate(90deg) scale(1.05); box-shadow: 0 8px 22px rgba(0,0,0,0.35); }
.fab-options { display: flex; flex-direction: column; gap: 10px; margin-bottom: 12px; opacity: 0; transform: translateY(20px); pointer-events: none; transition: all 0.35s ease; }
.fab-options.show { opacity: 1; transform: translateY(0); pointer-events: auto; }
.fab-options a { text-decoration: none; color: white; font-weight: 600; padding: 10px 16px; border-radius: 50px; font-family: 'Segoe UI', sans-serif; font-size: 14px; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.25); transition: all 0.3s ease; }
.fab-options a:hover { transform: translateY(-2px) scale(1.05); box-shadow: 0 6px 14px rgba(0,0,0,0.3); }
.fab-options img { width: 20px; height: 20px; }
</style>

<div class="fab-container">
<div class="fab-options" id="fabOptions">
<?php foreach ($social_buttons as $btn):
    $bg_style = !empty($btn['color']) ? 'background:' . $btn['color'] : '';
    $icon_html = '';
    if (!empty($btn['icon'])) {
        if (strpos($btn['icon'], '/') !== false || strpos($btn['icon'], '.') !== false) {
            $icon_html = '<img src="' . htmlspecialchars($btn['icon']) . '" alt="' . htmlspecialchars($btn['name'] ?? '') . '">';
        } else {
            $icon_html = htmlspecialchars($btn['icon']);
        }
    }
?>
<a href="<?php echo htmlspecialchars($btn['url'] ?? '#'); ?>" target="_blank" style="<?php echo $bg_style; ?>"><?php echo $icon_html; ?> <?php echo htmlspecialchars($btn['name'] ?? ''); ?></a>
<?php endforeach; ?>
</div>
<div class="fab-button" onclick="toggleFab()"><?php echo $fab_icon; ?></div>
</div>

<script>
function toggleFab() { var el = document.getElementById("fabOptions"); if (el) el.classList.toggle("show"); }
</script>
<?php endif; ?>

<?php if ($popup_enabled === '' || $popup_enabled === '1'):
    $popup_bg = getSetting('popup_notice_bg_color') ?: 'rgba(255,255,255,0.8)';
    $popup_text_color = getSetting('popup_notice_text_color') ?: '#333';
    $wa = getSetting('whatsapp_number');
    $tg = getSetting('telegram_link');
?>
<div id="popupNotice" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:<?php echo $popup_bg; ?>;backdrop-filter:blur(14px);border-radius:18px;box-shadow:0 8px 35px rgba(0,0,0,0.2);padding:26px 22px;z-index:9999;width:92%;max-width:360px;font-family:'Segoe UI','Helvetica Neue',sans-serif;text-align:center;animation:fadeIn 0.35s ease-in-out;border:1px solid rgba(13,110,253,0.1);">
<h2 style="font-size:20px;margin-bottom:14px;font-weight:700;text-transform:uppercase;background:linear-gradient(90deg,#0d6efd,#6610f2);-webkit-background-clip:text;-webkit-text-fill-color:transparent;letter-spacing:1px;"><?php echo getSetting('popup_notice_title') ?: '📢 নোটিশ'; ?></h2>
<div style="font-size:14px;color:<?php echo $popup_text_color; ?>;line-height:1.7;margin-bottom:12px;"><?php echo nl2br(getSetting('popup_notice_message')); ?></div>
<div style="margin-top:16px;">
<?php if ($wa): ?><a href="https://wa.me/<?php echo $wa; ?>" target="_blank" style="background:linear-gradient(135deg,#25D366,#128C7E);color:white;padding:10px 18px;border-radius:8px;text-decoration:none;font-size:13px;margin-right:8px;font-weight:600;display:inline-block;transition:all 0.3s ease;">💬 WhatsApp</a><?php endif; ?>
<?php if ($tg): ?><a href="<?php echo $tg; ?>" target="_blank" style="background:linear-gradient(135deg,#0088cc,#005f99);color:white;padding:10px 18px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;display:inline-block;transition:all 0.3s ease;">📨 Telegram</a><?php endif; ?>
</div>
<div style="margin-top:16px;font-size:12.5px;color:#555;"><label style="cursor:pointer;"><input type="checkbox" id="dontShow" style="margin-right:6px;"> <?php echo getSetting('popup_hide_label') ?: 'আজকের জন্য আর দেখাবেন না'; ?></label></div>
<button onclick="closePopup()" style="margin-top:18px;background:linear-gradient(135deg,#0d6efd,#6610f2);color:white;border:none;padding:10px 20px;border-radius:8px;cursor:pointer;font-size:14px;font-weight:600;transition:all 0.3s ease;"><?php echo getSetting('popup_close_text') ?: '❌ বন্ধ করুন'; ?></button>
</div>

<style>
@keyframes fadeIn { from { opacity: 0; transform: scale(0.9) translate(-50%, -50%); } to { opacity: 1; transform: scale(1) translate(-50%, -50%); } }
#popupNotice a:hover, #popupNotice button:hover { opacity: 0.95; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
</style>

<script>
window.onload = function() { if (!localStorage.getItem("noticeClosed")) { var el = document.getElementById('popupNotice'); if (el) el.style.display = 'block'; } };
function closePopup() { if (document.getElementById('dontShow') && document.getElementById('dontShow').checked) { localStorage.setItem("noticeClosed", "true"); } var el = document.getElementById('popupNotice'); if (el) el.style.display = 'none'; }
</script>
<?php endif; ?>
