<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/@accessible360/accessible-slick@1.0.1/slick/slick.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/fancybox@3.5.6/dist/jquery.fancybox.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js" defer></script>
<script src="js/scroll.js" defer></script>
<script src="js/ns.js" defer></script>
<script src="js/ns-jquery.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (navigator.modelContext && navigator.modelContext.registerTool) {
        navigator.modelContext.registerTool({
            name: 'search_domain',
            description: 'Searches for domain name availability and pricing on HostNibo',
            parameters: {
                type: 'object',
                properties: {
                    domain: { type: 'string', description: 'The domain name to search for availability' }
                },
                required: ['domain']
            },
            handler: function(params) {
                var form = document.querySelector('form[toolname="search_domain"]');
                if (form) {
                    var input = form.querySelector('input[name="domain"]');
                    if (input) input.value = params.domain;
                    form.submit();
                }
                return { success: true, message: 'Searching for domain: ' + params.domain };
            }
        });
        navigator.modelContext.registerTool({
            name: 'newsletter_subscribe',
            description: 'Subscribes the user to the HostNibo newsletter for latest updates and promotions',
            parameters: {
                type: 'object',
                properties: {
                    email: { type: 'string', description: 'The email address to subscribe to the newsletter' }
                },
                required: ['email']
            },
            handler: function(params) {
                return new Promise(function(resolve) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'subscribe.php', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onload = function() {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            resolve({ success: res.success, message: res.message });
                        } catch(e) {
                            resolve({ success: false, message: 'Subscription failed' });
                        }
                    };
                    xhr.send('email=' + encodeURIComponent(params.email));
});
});
});
</script>
<script>
(function(){
var css=document.createElement('style');
css.textContent='.fade-up{opacity:0;transform:translateY(24px);transition:opacity .6s ease,transform .6s ease}.fade-up.visible{opacity:1;transform:translateY(0)}';
document.head.appendChild(css);
if(!window.matchMedia('(prefers-reduced-motion: reduce)').matches){
var obs=new IntersectionObserver(function(entries){
entries.forEach(function(e){if(e.isIntersecting){e.target.classList.add('visible');obs.unobserve(e.target)}});
},{threshold:0.12});
document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('.fade-up').forEach(function(el){obs.observe(el)})});
}
})();
</script>
<script>
(function(){
var d=document,w=window;
function t(){
var h=new XMLHttpRequest();
h.open('POST','/admin/track.php',true);
h.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
h.send('url='+encodeURIComponent(d.location.pathname)+'&t='+encodeURIComponent(d.title)+'&r='+encodeURIComponent(w.referrer||''));
}
if(d.readyState==='complete'||d.readyState==='interactive')t();else d.addEventListener('DOMContentLoaded',t);
})();
</script>
