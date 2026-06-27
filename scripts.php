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
            }
        });
        navigator.modelContext.registerTool({
            name: 'send_contact_message',
            description: 'Sends a contact message to HostNibo support team',
            parameters: {
                type: 'object',
                properties: {
                    name: { type: 'string', description: 'The sender full name' },
                    email: { type: 'string', description: 'The sender email address' },
                    subject: { type: 'string', description: 'The subject of the message' },
                    message: { type: 'string', description: 'The message content to send' }
                },
                required: ['name', 'email', 'subject', 'message']
            },
            handler: function(params) {
                var form = document.querySelector('form[toolname="send_contact_message"]');
                if (form) {
                    var nameInput = form.querySelector('input[name="name"]');
                    var emailInput = form.querySelector('input[name="email"]');
                    var subjectInput = form.querySelector('input[name="subject"]');
                    var messageInput = form.querySelector('textarea[name="message"]');
                    if (nameInput) nameInput.value = params.name;
                    if (emailInput) emailInput.value = params.email;
                    if (subjectInput) subjectInput.value = params.subject;
                    if (messageInput) messageInput.value = params.message;
                    form.submit();
                }
                return { success: true, message: 'Contact message submitted' };
            }
        });
    }
});
</script>
