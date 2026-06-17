<footer style="background: #111827; color: #ffffff" id="footer" class>
<div class="pt-12">
<div class="content grid grid-cols-1 lg:grid-cols-11 lg:gap-10">
<div class="col-span-3 space-y-6 py-3 row-span-2">
<h3><img class="max-h-[40px] max-w-[200px] sm:max-w-[400px] rounded" src="<?php echo getSetting('footer_logo') ?: 'images/logo-white.png'; ?>" alt="<?php echo getSetting('site_name'); ?>"></h3>
<p style="color: #ffffff" class="text-sm max-w-[350px]"><?php echo getSetting('footer_description'); ?></p>
<br>
<a href="//www.dmca.com/Protection/Status.aspx?ID=65bbde93-ced3-47fc-b61c-569d89434dd2" title="DMCA.com Protection Status" class="dmca-badge"><img src="https://images.dmca.com/Badges/dmca_protected_sml_120m.png?ID=65bbde93-ced3-47fc-b61c-569d89434dd2" alt="DMCA.com Protection Status"></a>
<script src="https://images.dmca.com/Badges/DMCABadgeHelper.min.js"></script>
<div class="flex flex-wrap gap-2">
<?php $fb = getSetting('facebook_url'); if ($fb): ?><a class="circle-btn w-10 h-10 bg-[#1877f2] text-white" href="<?php echo $fb; ?>"><i class="fab fa-facebook"></i></a><?php endif; ?>
<?php $li = getSetting('linkedin_url'); if ($li): ?><a class="circle-btn w-10 h-10 bg-[#0a66c2] text-white" href="<?php echo $li; ?>"><i class="fab fa-linkedin"></i></a><?php endif; ?>
<?php $yt = getSetting('youtube_url'); if ($yt): ?><a class="circle-btn w-10 h-10 bg-[#ff0000] text-white" href="<?php echo $yt; ?>"><i class="fab fa-youtube"></i></a><?php endif; ?>
<?php $tw = getSetting('twitter_url'); if ($tw): ?><a class="circle-btn w-10 h-10 bg-[#1da1f2] text-white" href="<?php echo $tw; ?>"><i class="fab fa-twitter"></i></a><?php endif; ?>
</div>
<div class="mt-6">
    <h3 class="text-lg font-semibold mb-3">Newsletter</h3>
    <p class="text-sm text-gray-300 mb-3">Subscribe to get latest updates</p>
    <form id="newsletterForm" class="flex flex-col gap-2" onsubmit="return subscribeNewsletter(event)">
        <div class="flex gap-2">
            <input type="email" id="newsletterEmail" placeholder="Your email" required class="flex-1 px-3 py-2 rounded text-sm text-gray-900">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 whitespace-nowrap"><i class="fa fa-paper-plane"></i></button>
        </div>
        <div id="newsletterMsg" class="text-xs mt-1"></div>
    </form>
</div>
</div>
<?php
$footer_items = getMenuItems('footer');
$footer_tree = buildMenuTree($footer_items);
foreach ($footer_tree as $parent):
    $has_children = isset($parent['children']) && !empty($parent['children']);
?>
<div class="col-span-2 space-y-6 py-3">
<div>
<h3 class="mb-8"><?php echo htmlspecialchars($parent['label']); ?></h3>
<div class="space-y-3">
<?php if ($has_children): foreach ($parent['children'] as $child): ?>
<a style="color: #ffffff" class="flex items-center gap-1 text-sm group" href="<?php echo htmlspecialchars($child['url']); ?>"><span class="circle-btn bg-opacity-25 w-4 h-4 text-xs text-white"><i class="fa fa-play fa-2xs group-hover:hidden"></i><i class="fa fa-pause fa-2xs hidden group-hover:block"></i></span><span><?php echo htmlspecialchars($child['label']); ?></span></a>
<?php endforeach; else: ?>
<a style="color: #ffffff" class="flex items-center gap-1 text-sm group" href="<?php echo htmlspecialchars($parent['url']); ?>"><span class="circle-btn bg-opacity-25 w-4 h-4 text-xs text-white"><i class="fa fa-play fa-2xs group-hover:hidden"></i><i class="fa fa-pause fa-2xs hidden group-hover:block"></i></span><span><?php echo htmlspecialchars($parent['label']); ?></span></a>
<?php endif; ?>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
<div class="border-t border-b border-gray-400 border-opacity-25">
<div class="content py-4 flex justify-between flex-col items-center lg:flex-row gap-4">
<p style="color: #ffffff" class="text-center py-6"><?php echo getSetting('footer_copyright'); ?></p>
</div>
</div>
</div>
</footer>
<script>
function subscribeNewsletter(e) {
    e.preventDefault();
    var email = document.getElementById('newsletterEmail').value.trim();
    var msgDiv = document.getElementById('newsletterMsg');
    if (!email) { msgDiv.innerHTML = '<span class="text-red-400">Please enter your email</span>'; return false; }
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'subscribe.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        try {
            var res = JSON.parse(xhr.responseText);
            msgDiv.innerHTML = res.success ? '<span class="text-green-400">' + res.message + '</span>' : '<span class="text-red-400">' + res.message + '</span>';
            if (res.success) { document.getElementById('newsletterEmail').value = ''; }
        } catch(e) { msgDiv.innerHTML = '<span class="text-red-400">Something went wrong</span>'; }
    };
    xhr.send('email=' + encodeURIComponent(email));
    return false;
}
</script>
<?php $footer_code = getSetting('footer_code'); if ($footer_code): echo $footer_code; endif; ?>
