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
.fab-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.35); backdrop-filter: blur(4px); z-index: 9998; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; }
.fab-overlay.show { opacity: 1; pointer-events: auto; }
.fab-button { width: 58px; height: 58px; border-radius: 50%; background: linear-gradient(135deg, #0d6efd, #6610f2); box-shadow: 0 6px 20px rgba(13,110,253,0.4); display: flex; justify-content: center; align-items: center; color: white; font-size: 26px; cursor: pointer; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); border: none; outline: none; position: relative; overflow: hidden; z-index: 2; }
.fab-button:hover { transform: scale(1.08); box-shadow: 0 8px 28px rgba(13,110,253,0.5); }
.fab-button:active { transform: scale(0.95); }
.fab-button .ripple { position: absolute; border-radius: 50%; background: rgba(255,255,255,0.35); transform: scale(0); animation: rippleAnim 0.6s ease-out; }
@keyframes rippleAnim { to { transform: scale(4); opacity: 0; } }
.fab-button.open { transform: rotate(45deg); box-shadow: 0 4px 14px rgba(13,110,253,0.3); }
.fab-options { display: flex; flex-direction: column; align-items: flex-end; gap: 12px; margin-bottom: 14px; }
.fab-option { display: flex; align-items: center; gap: 10px; flex-direction: row-reverse; opacity: 0; transform: translateX(30px) scale(0.6); transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); pointer-events: none; }
.fab-options.show .fab-option { opacity: 1; transform: translateX(0) scale(1); pointer-events: auto; }
.fab-option .fab-circle { width: 48px; height: 48px; min-width: 48px; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: white; font-size: 20px; box-shadow: 0 4px 14px rgba(0,0,0,0.25); text-decoration: none; transition: all 0.25s ease; position: relative; }
.fab-option .fab-circle:hover { transform: scale(1.12); box-shadow: 0 6px 20px rgba(0,0,0,0.35); }
.fab-option .fab-circle img { width: 22px; height: 22px; border-radius: 0; }
.fab-option .fab-circle .no-icon { font-size: 18px; font-weight: 700; }
.fab-option .fab-label { font-family: 'Segoe UI', sans-serif; font-size: 13px; font-weight: 600; color: #fff; background: rgba(15,23,42,0.85); backdrop-filter: blur(8px); padding: 5px 14px; border-radius: 20px; white-space: nowrap; box-shadow: 0 2px 8px rgba(0,0,0,0.15); pointer-events: none; }
</style>

<div class="fab-overlay" id="fabOverlay" onclick="toggleFab()"></div>
<div class="fab-container">
<div class="fab-options" id="fabOptions">
<?php $fi = 0; foreach ($social_buttons as $btn):
    $bg_style = !empty($btn['color']) ? 'background:' . $btn['color'] : 'background:#0d6efd';
    $has_name = !empty(trim($btn['name'] ?? ''));
    $icon_html = '';
    $has_icon = false;
    if (!empty($btn['icon'])) {
        if (strpos($btn['icon'], '/') !== false || strpos($btn['icon'], '.') !== false) {
            $icon_html = '<img src="' . htmlspecialchars($btn['icon']) . '" alt="' . htmlspecialchars($btn['name'] ?? '') . '">';
            $has_icon = true;
        } else {
            $icon_html = htmlspecialchars($btn['icon']);
            $has_icon = true;
        }
    }
    if (!$has_icon && $has_name) {
        $icon_html = '<span class="no-icon">' . htmlspecialchars(mb_substr(trim($btn['name']), 0, 1)) . '</span>';
    }
?>
<div class="fab-option" style="transition-delay:<?php echo $fi * 0.05; ?>s">
    <?php if ($has_name): ?><span class="fab-label"><?php echo htmlspecialchars(trim($btn['name'])); ?></span><?php endif; ?>
    <a href="<?php echo htmlspecialchars($btn['url'] ?? '#'); ?>" target="_blank" class="fab-circle" style="<?php echo $bg_style; ?>"><?php echo $icon_html ?: ''; ?></a>
</div>
<?php $fi++; endforeach; ?>
</div>
<button class="fab-button" id="fabBtn" onclick="toggleFab()"><?php echo $fab_icon; ?></button>
</div>

<script>
function toggleFab() {
    var opts = document.getElementById("fabOptions");
    var overlay = document.getElementById("fabOverlay");
    var btn = document.getElementById("fabBtn");
    if (opts) {
        var isOpen = opts.classList.contains("show");
        opts.classList.toggle("show");
        if (overlay) overlay.classList.toggle("show");
        if (btn) btn.classList.toggle("open");
    }
}
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('fabBtn');
    if (btn) {
        btn.addEventListener('click', function(e) {
            var rect = btn.getBoundingClientRect();
            var ripple = document.createElement('span');
            ripple.className = 'ripple';
            var size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - rect.left - size/2) + 'px';
            ripple.style.top = (e.clientY - rect.top - size/2) + 'px';
            btn.appendChild(ripple);
            setTimeout(function() { ripple.remove(); }, 600);
        });
    }
});
</script>
<?php endif; ?>

<?php if ($popup_enabled === '' || $popup_enabled === '1'):
    $popup_bg = escSetting('popup_notice_bg_color') ?: 'rgba(255,255,255,0.8)';
    $popup_text_color = escSetting('popup_notice_text_color') ?: '#333';
    $wa = getSetting('whatsapp_number');
    $tg = getSetting('telegram_link');
?>
<div id="popupNotice" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:<?php echo $popup_bg; ?>;backdrop-filter:blur(14px);border-radius:18px;box-shadow:0 8px 35px rgba(0,0,0,0.2);padding:26px 22px;z-index:9999;width:92%;max-width:360px;font-family:'Segoe UI','Helvetica Neue',sans-serif;text-align:center;animation:fadeIn 0.35s ease-in-out;border:1px solid rgba(13,110,253,0.1);">
<h2 style="font-size:20px;margin-bottom:14px;font-weight:700;text-transform:uppercase;background:linear-gradient(90deg,#0d6efd,#6610f2);-webkit-background-clip:text;-webkit-text-fill-color:transparent;letter-spacing:1px;"><?php echo escSetting('popup_notice_title') ?: '📢 Notice'; ?></h2>
<div style="font-size:14px;color:<?php echo $popup_text_color; ?>;line-height:1.7;margin-bottom:12px;"><?php echo nl2br(getSetting('popup_notice_message')); ?></div>
<div style="margin-top:16px;">
<?php if ($wa): ?><a href="https://wa.me/<?php echo htmlspecialchars($wa); ?>" target="_blank" style="background:linear-gradient(135deg,#25D366,#128C7E);color:white;padding:10px 18px;border-radius:8px;text-decoration:none;font-size:13px;margin-right:8px;font-weight:600;display:inline-block;transition:all 0.3s ease;">💬 WhatsApp</a><?php endif; ?>
<?php if ($tg): ?><a href="<?php echo htmlspecialchars($tg); ?>" target="_blank" style="background:linear-gradient(135deg,#0088cc,#005f99);color:white;padding:10px 18px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;display:inline-block;transition:all 0.3s ease;">📨 Telegram</a><?php endif; ?>
</div>
<div style="margin-top:16px;font-size:12.5px;color:#555;"><label style="cursor:pointer;"><input type="checkbox" id="dontShow" style="margin-right:6px;"> <?php echo escSetting('popup_hide_label') ?: "Don't show again today"; ?></label></div>
<button onclick="closePopup()" style="margin-top:18px;background:linear-gradient(135deg,#0d6efd,#6610f2);color:white;border:none;padding:10px 20px;border-radius:8px;cursor:pointer;font-size:14px;font-weight:600;transition:all 0.3s ease;"><?php echo escSetting('popup_close_text') ?: '❌ Close'; ?></button>
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
