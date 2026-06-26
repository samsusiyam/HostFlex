<meta charset="UTF-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="description" content="<?php echo htmlspecialchars(escSetting('site_description') ?: 'Premium web hosting solutions with exceptional performance, reliability, and 24/7 support.'); ?>">
<link rel="shortcut icon" href="<?php echo escSetting('favicon') ?: 'images/favicon.ico'; ?>" type="image/x-icon" />

<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" as="style" onload="this.rel='stylesheet'">

<link rel="preload" href="images/cloud.webp" as="image" type="image/webp" fetchpriority="high">
<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" as="script">

<style>
*{font-family:inter,sans-serif}.content{width:100%;margin-left:auto;margin-right:auto;padding-left:1rem;padding-right:1rem}@media (min-width:640px){.content{max-width:640px}}@media (min-width:768px){.content{max-width:768px}}@media (min-width:1024px){.content{max-width:1024px}}@media (min-width:1280px){.content{max-width:1280px}}@media (min-width:1536px){.content{max-width:1536px}}
</style>

<link rel="stylesheet" href="styles/styles.css" type="text/css" />
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
<?php include __DIR__ . '/scripts.php'; ?>