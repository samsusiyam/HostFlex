<meta charset="UTF-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<?php if (empty($skip_default_meta)): ?><meta name="description" content="<?php echo htmlspecialchars(escSetting('site_description') ?: 'Premium web hosting solutions with exceptional performance, reliability, and 24/7 support.'); ?>"><?php endif; ?>
<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
<link rel="shortcut icon" href="<?php echo escSetting('favicon') ?: 'images/favicon.ico'; ?>" type="image/x-icon" />
<?php
$og_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$og_title = escSetting('site_name') ?: 'HostNibo';
$og_description = escSetting('site_description') ?: 'Premium web hosting solutions with exceptional performance, reliability, and 24/7 support.';
$og_image = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/' . (escSetting('og_image') ?: 'images/bg.png');
?>
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?php echo $og_title; ?>">
<meta property="og:title" content="<?php echo $og_title; ?>">
<meta property="og:description" content="<?php echo $og_description; ?>">
<meta property="og:url" content="<?php echo $og_url; ?>">
<meta property="og:image" content="<?php echo $og_image; ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo $og_title; ?>">
<meta name="twitter:description" content="<?php echo $og_description; ?>">
<meta name="twitter:image" content="<?php echo $og_image; ?>">
<link rel="canonical" href="<?php echo $og_url; ?>">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "<?php echo addslashes(escSetting('site_name') ?: 'HostNibo'); ?>",
  "url": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>",
  "logo": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/' . (escSetting('header_logo') ?: 'images/bg.png'); ?>",
  "description": "<?php echo addslashes($og_description); ?>",
  "contactPoint": {
    "@type": "ContactPoint",
    "contactType": "sales",
    "email": "<?php echo addslashes(escSetting('site_email') ?: ''); ?>",
    "telephone": "<?php echo addslashes(escSetting('site_phone') ?: ''); ?>"
  },
  "sameAs": <?php
    $social = json_decode(getSetting('social_links') ?: '[]', true);
    $same_as = [];
    if (is_array($social)) {
      foreach ($social as $s) {
        if (!empty($s['url'])) $same_as[] = $s['url'];
      }
    }
    echo json_encode($same_as);
  ?>
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "<?php echo addslashes(escSetting('site_name') ?: 'HostNibo'); ?>",
  "url": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/index.php?domain={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
</script>

<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=optional" as="style" onload="this.rel='stylesheet'">

<link rel="preload" href="images/cloud.jpg" as="image" type="image/jpeg" fetchpriority="high">
<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/webfonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/webfonts/fa-brands-400.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/webfonts/fa-regular-400.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" as="script">

<style>
/* critical: base + above-fold layout */.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border-width:0}*,::before,::after{box-sizing:border-box}body{margin:0;font-family:Inter,sans-serif;line-height:1.5;-webkit-text-size-adjust:100%}img{max-width:100%;height:auto}a{color:inherit;text-decoration:inherit}h1,h2,h3,h4,h5,h6,p,figure,pre,blockquote,dl,dd,hr,fieldset,legend,ol,ul,menu{margin:0}button,input,optgroup,select,textarea{font-family:inherit;font-size:100%;font-weight:inherit;line-height:inherit;color:inherit;margin:0;padding:0}button{cursor:pointer}picture{display:block}i[class*="fa-"]{display:inline-block;width:1em;text-align:center}section{contain:layout style}.content{width:100%;margin-left:auto;margin-right:auto;padding-left:1rem;padding-right:1rem}.flex{display:flex}.items-center{align-items:center}.justify-between{justify-content:space-between}.sticky{position:sticky;top:0}.z-\[99999\]{z-index:99999}.h-\[90px\]{height:90px}.bg-white{background-color:#fff}.border-b{border-bottom:1px solid #e5e7eb}.border-t-transparent{border-top-color:transparent}.inset-x-0{left:0;right:0}.m-auto{margin:auto}.h-\[50px\]{height:50px}.hidden{display:none}.font-medium{font-weight:500}.font-normal{font-weight:400}.w-fit{width:fit-content}.ml-auto{margin-left:auto}.mx-auto{margin:0 auto}.ml-1{margin-left:.25rem}.relative{position:relative}.flex-col{flex-direction:column}.justify-center{justify-content:center}.flex-grow{flex-grow:1}.gap-6{gap:1.5rem}.gap-4{gap:1rem}.gap-3{gap:.75rem}.gap-2{gap:.5rem}.gap-1{gap:.25rem}.gap-x-2{column-gap:.5rem}.text-\[36px\]{font-size:36px;line-height:45px}.text-xl{font-size:1.25rem}.text-sm{font-size:.875rem}.text-xs{font-size:.75rem;line-height:1rem}.font-extrabold{font-weight:800}.font-bold{font-weight:700}.capitalize{text-transform:capitalize}.text-\[\#111827\]{color:#111827}.text-blue-600{color:#2563eb}.whitespace-nowrap{white-space:nowrap}.grid{display:grid}.grid-cols-1{grid-template-columns:repeat(1,minmax(0,1fr))}.grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}.w-12{width:3rem}.h-14{height:3.5rem}@media(min-width:640px){.sm\:grid-cols-4{grid-template-columns:repeat(4,minmax(0,1fr))}}@media(min-width:1024px){.lg\:grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}}.btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;white-space:nowrap;border-radius:.5rem;border:1px solid transparent;padding:.75rem 1rem;font-size:.875rem;font-weight:600;cursor:pointer;text-align:center;transition:all .15s}.btn-blue{background-color:#2563eb;color:#fff}.btn-purple{background:#7c3aed;color:#fff}.\!px-8{padding-left:2rem!important;padding-right:2rem!important}.border{border-width:1px}.bg-gray-200{background:#e5e7eb}.bg-cyan-600{background-color:#0891b2}.bg-blue-50{background:#eff6ff}.rounded-xl{border-radius:.75rem}.shadow{box-shadow:0 1px 3px 0 rgba(0,0,0,.1),0 1px 2px -1px rgba(0,0,0,.1)}.shadow-xl{box-shadow:0 20px 25px -5px rgba(0,0,0,.1),0 8px 10px -6px rgba(0,0,0,.1)}.w-auto{width:auto}.mt-32{margin-top:8rem}.mb-10{margin-bottom:2.5rem}.py-8{padding-top:2rem;padding-bottom:2rem}.py-3{padding-top:.75rem;padding-bottom:.75rem}.py-2{padding-top:.5rem;padding-bottom:.5rem}.px-6{padding-left:1.5rem;padding-right:1.5rem}.px-4{padding-left:1rem;padding-right:1rem}input{display:block;width:100%;padding:.5rem .75rem;border:1px solid #d1d5db;border-radius:.5rem;font-size:.875rem}section.py-16{padding-top:4rem;padding-bottom:4rem}@media(min-width:640px){.content{max-width:640px}.sm\:gap-8{gap:2rem}.sm\:px-6{padding-left:1.5rem;padding-right:1.5rem}}@media(min-width:768px){.content{max-width:768px}.md\:gap-12{gap:3rem}.md\:gap-8{gap:2rem}.md\:max-w-\[600px\]{max-width:600px}}@media(min-width:1024px){.content{max-width:1024px}.lg\:grid{display:grid}.lg\:block{display:block}.lg\:pr-12{padding-right:3rem}}@media(min-width:1280px){.content{max-width:1280px}.xl\:flex{display:flex}.xl\:hidden{display:none}.xl\:text-\[46px\]{font-size:46px}}@media(min-width:1536px){.content{max-width:1536px}.\32xl\:flex-row{flex-direction:row}}@font-face{font-family:'Font Awesome 6 Free';font-style:normal;font-weight:900;font-display:swap;src:url(https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/webfonts/fa-solid-900.woff2) format('woff2')}@font-face{font-family:'Font Awesome 6 Brands';font-style:normal;font-weight:400;font-display:swap;src:url(https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/webfonts/fa-brands-400.woff2) format('woff2')}
</style>

<link rel="stylesheet" href="styles/styles.css" type="text/css" media="print" onload="this.media='all'" />
<noscript><link rel="stylesheet" href="styles/styles.css" type="text/css" /></noscript>
<link rel="stylesheet" href="styles/custom.css" type="text/css" media="print" onload="this.media='all'" />
<noscript><link rel="stylesheet" href="styles/custom.css" type="text/css" /></noscript>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" media="print" onload="this.media='all'" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@accessible360/accessible-slick@1.0.1/slick/slick.min.css" media="print" onload="this.media='all'" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@accessible360/accessible-slick@1.0.1/slick/accessible-slick-theme.min.css" media="print" onload="this.media='all'" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/fancybox@3.5.6/dist/jquery.fancybox.min.css" media="print" onload="this.media='all'" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cooltipz-css/cooltipz.min.css" media="print" onload="this.media='all'">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" media="print" onload="this.media='all'">
<noscript>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@accessible360/accessible-slick@1.0.1/slick/slick.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@accessible360/accessible-slick@1.0.1/slick/accessible-slick-theme.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/fancybox@3.5.6/dist/jquery.fancybox.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cooltipz-css/cooltipz.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
</noscript>
<?php $header_code = getSetting('header_code'); if ($header_code): ?><?php echo $header_code; ?><?php endif; ?>
<?php include __DIR__ . '/scripts.php'; ?>