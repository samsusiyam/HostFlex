<?php require_once 'config/database.php'; require_once 'includes/functions.php'; checkMaintenance(); ?>
<?php $onesignal_id = getSetting('onesignal_app_id'); ?>
<?php $tawkto_id = getSetting('tawkto_widget_id'); ?>
<?php $crisp_id = getSetting('crisp_website_id'); ?>
<?php $header_code = getSetting('header_code'); ?>
<?php if ($onesignal_id): ?>
<script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
<script>window.OneSignalDeferred = window.OneSignalDeferred || [];OneSignalDeferred.push(function(OneSignal){OneSignal.init({appId:"<?php echo $onesignal_id; ?>"});});</script>
<?php endif; ?>
<?php if ($tawkto_id): ?>
<script>var Tawk_API=Tawk_API||{},Tawk_LoadStart=new Date();(function(){var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];s1.async=true;s1.src='https://embed.tawk.to/<?php echo $tawkto_id; ?>/default';s1.charset='UTF-8';s1.setAttribute('crossorigin','*');s0.parentNode.insertBefore(s1,s0);})();</script>
<?php endif; ?>
<?php if ($crisp_id): ?>
<script>window.$crisp=[];window.CRISP_WEBSITE_ID="<?php echo $crisp_id; ?>";(function(){d=document;s=d.createElement("script");s.src="https://client.crisp.chat/l.js";s.async=1;d.getElementsByTagName("head")[0].appendChild(s);})();</script>
<?php endif; ?>
<?php if ($header_code): echo $header_code; endif; ?>
<?php if (isMaintenanceMode() && isset($_SESSION['admin_id'])): ?>
<div class="bg-yellow-500 text-white text-center py-2 px-4 text-sm font-medium sticky top-0 z-[999999]">
    <i class="fa fa-tools mr-1"></i> Maintenance Mode is ACTIVE. Visitors see a maintenance page.
</div>
<?php endif; ?>
<header class="flex h-[90px] items-center bg-white dark:bg-gray-900 sticky border-b inset-x-0 m-auto top-0 z-[99999]">
<div class="content flex items-center justify-between">
<a href="/"><img class="h-[50px]" src="/<?php echo ltrim(getSetting('header_logo') ?: 'images/bg.png', '/'); ?>" alt="<?php echo htmlspecialchars(getSetting('site_name') ?: 'Host Nibo'); ?>" /></a>
<div class="hidden xl:flex items-center gap-6 font-normal">
<?php
$menu_items = getMenuItems('header');
$tree = buildMenuTree($menu_items);
foreach ($tree as $item):
    $has_children = isset($item['children']) && !empty($item['children']);
    $url = htmlspecialchars($item['url']);
    $label = htmlspecialchars($item['label']);
    if ($has_children):
?>
<div class="group relative z-50 flex h-[80px] cursor-pointer items-center gap-1">
<span class="font-medium hover:text-blue-600"><?php echo $label; ?></span>
<small class="text-xs ml-1"><i class="fa fa-chevron-down"></i></small>
<div class="absolute top-full hidden flex-col border-t-transparent bg-white text-sm shadow group-hover:flex">
<?php foreach ($item['children'] as $child): ?>
<a href="<?php echo htmlspecialchars($child['url']); ?>" class="whitespace-nowrap border-b px-4 py-2 hover:text-blue-600"><?php echo htmlspecialchars($child['label']); ?></a>
<?php endforeach; ?>
</div>
</div>
<?php else: ?>
<a href="<?php echo $url; ?>" class="font-medium hover:text-blue-600"><?php echo $label; ?></a>
<?php endif; endforeach; ?>
<a href="<?php echo getSetting('whmcs_client_area_url') ?: '#'; ?>" class="btn bg-cyan-600 text-white" data-ripple-light="true"><i class="fa fa-display"></i> Client Area</a>
</div>

<div id="mobile-nav" class="absolute top-full left-0 w-full bg-white shadow-xl border-b xl:hidden flex flex-col gap-3 p-8 font-normal transition-all transform origin-top z-[999999]" style="transform: scaleY(0);">
<?php foreach ($tree as $item):
    $has_children = isset($item['children']) && !empty($item['children']);
    $url = htmlspecialchars($item['url']);
    $label = htmlspecialchars($item['label']);
    if ($has_children):
?>
<div class="group relative flex flex-col">
    <div class="flex items-center justify-between py-2 border-b border-gray-100 font-medium text-gray-800 cursor-pointer" onclick="var s = this.nextElementSibling; if(s.classList.contains('hidden')){ s.classList.remove('hidden'); s.classList.add('flex'); } else { s.classList.remove('flex'); s.classList.add('hidden'); }">
        <span><?php echo $label; ?></span>
        <small class="text-xs ml-1"><i class="fa fa-chevron-down"></i></small>
    </div>
    <div class="hidden flex-col bg-gray-50 text-sm shadow p-2 mt-1 rounded space-y-1">
        <?php foreach ($item['children'] as $child): ?>
        <a href="<?php echo htmlspecialchars($child['url']); ?>" class="whitespace-nowrap px-4 py-2 hover:text-blue-600 border-b border-gray-100 last:border-b-0"><?php echo htmlspecialchars($child['label']); ?></a>
        <?php endforeach; ?>
    </div>
</div>
<?php else: ?>
<a href="<?php echo $url; ?>" class="py-2 border-b border-gray-100 font-medium text-gray-800 hover:text-blue-600"><?php echo $label; ?></a>
<?php endif; endforeach; ?>
<div class="pt-3">
    <a href="<?php echo getSetting('whmcs_client_area_url') ?: '#'; ?>" class="btn bg-cyan-600 text-white w-full flex items-center justify-center gap-2 py-3 rounded-lg font-semibold" data-ripple-light="true">
        <i class="fa fa-display"></i> Client Area
    </a>
</div>
</div>

<div class="xl:hidden w-fit ml-auto">
<button data-ripple-dark="true" id="mobile-nav-toggle" aria-label="Toggle navigation" class="btn bg-gray-100 border text-blue-600 text-xl px-3 py-2 rounded-lg cursor-pointer"><i class="fa fa-bars"></i></button>
</div>
</div>
</header>
