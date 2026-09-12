<?php 
require_once 'config/database.php'; 
require_once 'includes/functions.php'; 
require_once 'includes/mail.php'; 
checkMaintenance();

if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'contact.php') !== false) {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: /contact");
    exit;
}

$success = $_SESSION['contact_success'] ?? '';
$error = $_SESSION['contact_error'] ?? '';
unset($_SESSION['contact_success'], $_SESSION['contact_error']);

$recaptcha_enabled = getSetting('recaptcha_enabled') === '1';
$recaptcha_site_key = getSetting('recaptcha_site_key');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(strip_tags($_POST['name'] ?? ''));
    $email = trim(strip_tags($_POST['email'] ?? ''));
    $subject = trim(strip_tags($_POST['subject'] ?? ''));
    $message = trim(strip_tags($_POST['message'] ?? ''));

    // Normalize slashes and literal escaped newlines from bots/APIs
    $name = stripslashes($name);
    $subject = stripslashes($subject);
    $message = stripslashes($message);
    $message = str_replace(['\r\n', '\r', '\n', '\t'], ["\n", "\n", "\n", "    "], $message);

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'All fields are required. Please fill in all the details.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    }

    if (!$error && $recaptcha_enabled) {
        $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
        $secret_key = getSetting('recaptcha_secret_key');
        if (empty($recaptcha_response)) {
            $error = 'Please complete the reCAPTCHA verification.';
        } else {
            $verify = @file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret_key&response=$recaptcha_response");
            $captcha = json_decode($verify);
            if (!$captcha || empty($captcha->success)) {
                $error = 'reCAPTCHA verification failed. Please try again.';
            }
        }
    }

    if (!$error) {
        $stmt = mysqli_prepare($conn, "INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $subject, $message);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                $site_name = getSetting('site_name') ?: 'Host Nibo';
                $admin_email = getSetting('site_email');
                $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $site_url = $proto . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

                // Send auto-reply to submitter using Contact Auto-Reply template
                $tpl = mysqli_query($conn, "SELECT * FROM email_templates WHERE name = 'Contact Auto-Reply' LIMIT 1");
                if ($tpl && $tpl_row = mysqli_fetch_assoc($tpl)) {
                    $body = str_replace(
                        ['{name}', '{email}', '{message}', '{site_name}', '{site_url}'],
                        [htmlspecialchars($name), htmlspecialchars($email), nl2br(htmlspecialchars($message)), $site_name, $site_url],
                        $tpl_row['body']
                    );
                    $subj = str_replace(['{site_name}', '{site_url}'], [$site_name, $site_url], $tpl_row['subject']);
                    sendMail($email, $subj, $body);
                }

                // Forward to admin with reply-to set to submitter
                if ($admin_email) {
                    $forward_body = "
                    <div style=\"font-family: Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px;\">
                        <h3 style=\"margin-top: 0; color: #2563eb; font-size: 18px;\">New Contact Message</h3>
                        <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
                        <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                        <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
                        <p><strong>Message:</strong><br><div style=\"background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0; white-space: pre-wrap;\">" . nl2br(htmlspecialchars($message)) . "</div></p>
                    </div>";
                    sendMail($admin_email, "Contact Inquiry: $subject", $forward_body, $email);
                }

                $_SESSION['contact_success'] = 'Your message has been sent successfully! Our support team will get back to you shortly.';
                header("Location: /contact");
                exit;
            } else {
                mysqli_stmt_close($stmt);
                $_SESSION['contact_error'] = 'Something went wrong while saving your message. Please try again.';
                header("Location: /contact");
                exit;
            }
        } else {
            $_SESSION['contact_error'] = 'Database error. Please try again.';
            header("Location: /contact");
            exit;
        }
    } else {
        $_SESSION['contact_error'] = $error;
        header("Location: /contact");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include "cdnjs.php"; ?>
<title>Contact Us - <?php echo htmlspecialchars(getSetting('site_name') ?: 'Host Nibo'); ?></title>
<?php echo renderSeoTags([
    'title' => 'Contact Us - ' . (getSetting('site_name') ?: 'Host Nibo'),
    'description' => getSetting('contact_page_subheading') ?: 'Get in touch with our 24/7 web hosting support and sales team.'
]); ?>
</head>
<body>
<?php include "header.php"; ?>
<?php include "contact-btn.php"; ?>
<section class="section_gap flex items-center bg-gray-50 font-poppins dark:bg-gray-800">
<div class="content">
<div class="mb-14 text-center pb-4">
<h2 class="pb-2 mb-2 text-xl font-bold text-gray-800 md:text-4xl dark:text-gray-300"><?php echo getSetting('contact_page_heading') ?: 'Contact Us'; ?></h2>
<p class="text-lg text-gray-500 sm:text-xl dark:text-gray-400"><?php echo getSetting('contact_page_subheading') ?: 'We would love to hear from you.'; ?></p>
</div>

<!-- Beautiful Alert Notifications -->
<?php if ($success): ?>
<div class="max-w-4xl mx-auto mb-8 animate-in fade-in slide-in-from-top-4 duration-300">
    <div class="p-5 rounded-2xl bg-emerald-50/90 dark:bg-emerald-950/40 border-2 border-emerald-500/30 text-emerald-900 dark:text-emerald-200 shadow-lg shadow-emerald-500/5 flex items-start gap-4">
        <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-emerald-500/20 text-lg">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="flex-1 min-w-0 pt-0.5">
            <h4 class="text-base font-bold text-emerald-800 dark:text-emerald-300 mb-1">Message Sent Successfully!</h4>
            <p class="text-xs text-emerald-700 dark:text-emerald-400 leading-relaxed"><?php echo htmlspecialchars($success); ?></p>
        </div>
        <button onclick="this.closest('.animate-in').remove()" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300 p-1.5 transition cursor-pointer">
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>
    </div>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="max-w-4xl mx-auto mb-8 animate-in fade-in slide-in-from-top-4 duration-300">
    <div class="p-5 rounded-2xl bg-red-50/90 dark:bg-red-950/40 border-2 border-red-500/30 text-red-900 dark:text-red-200 shadow-lg shadow-red-500/5 flex items-start gap-4">
        <div class="w-10 h-10 rounded-xl bg-red-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-red-500/20 text-lg">
            <i class="fa-solid fa-circle-exclamation"></i>
        </div>
        <div class="flex-1 min-w-0 pt-0.5">
            <h4 class="text-base font-bold text-red-800 dark:text-red-300 mb-1">Submission Failed</h4>
            <p class="text-xs text-red-700 dark:text-red-400 leading-relaxed"><?php echo htmlspecialchars($error); ?></p>
        </div>
        <button onclick="this.closest('.animate-in').remove()" class="text-red-500 hover:text-red-700 dark:hover:text-red-300 p-1.5 transition cursor-pointer">
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>
    </div>
</div>
<?php endif; ?>

<div class="px-3 py-4">
<form method="POST" id="publicContactForm" class="rounded-3xl shadow-xl shadow-blue-900/5 border border-gray-100 dark:border-gray-800 dark:bg-gray-900 bg-white p-8 md:p-12 max-w-4xl mx-auto transition">
<div class="flex flex-wrap -mx-3">
<div class="w-full md:w-1/2 px-3 mb-5">
<label class="flex items-center gap-1.5 mb-2 text-xs font-bold text-gray-700 uppercase tracking-wider dark:text-gray-300">
    <i class="fa-solid fa-user text-blue-600 text-[11px]"></i> Your Name <span class="text-red-500">*</span>
</label>
<input name="name" type="text" placeholder="e.g. John Doe" required class="block w-full px-4 py-3.5 text-sm text-gray-800 bg-gray-50/80 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 dark:focus:ring-blue-900/30 dark:text-gray-200 dark:border-gray-700 dark:bg-gray-800 transition">
</div>
<div class="w-full md:w-1/2 px-3 mb-5">
<label class="flex items-center gap-1.5 mb-2 text-xs font-bold text-gray-700 uppercase tracking-wider dark:text-gray-300">
    <i class="fa-solid fa-envelope text-blue-600 text-[11px]"></i> Your Email <span class="text-red-500">*</span>
</label>
<input name="email" type="email" placeholder="e.g. john@example.com" required class="block w-full px-4 py-3.5 text-sm text-gray-800 bg-gray-50/80 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 dark:focus:ring-blue-900/30 dark:text-gray-200 dark:border-gray-700 dark:bg-gray-800 transition">
</div>
</div>
<div class="mb-5">
<label class="flex items-center gap-1.5 mb-2 text-xs font-bold text-gray-700 uppercase tracking-wider dark:text-gray-300">
    <i class="fa-solid fa-tag text-blue-600 text-[11px]"></i> Subject <span class="text-red-500">*</span>
</label>
<input name="subject" type="text" placeholder="e.g. Inquiry regarding Cloud Hosting" required class="block w-full px-4 py-3.5 text-sm text-gray-800 bg-gray-50/80 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 dark:focus:ring-blue-900/30 dark:text-gray-200 dark:border-gray-700 dark:bg-gray-800 transition">
</div>
<div class="mb-6">
<label class="flex items-center gap-1.5 mb-2 text-xs font-bold text-gray-700 uppercase tracking-wider dark:text-gray-300">
    <i class="fa-solid fa-message text-blue-600 text-[11px]"></i> Message <span class="text-red-500">*</span>
</label>
<textarea name="message" rows="5" placeholder="Write your message or inquiry here..." required class="block w-full px-4 py-3.5 text-sm text-gray-800 bg-gray-50/80 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 dark:focus:ring-blue-900/30 dark:text-gray-200 dark:border-gray-700 dark:bg-gray-800 transition"></textarea>
</div>
<div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-3 border-t border-gray-100 dark:border-gray-800">
<?php if ($recaptcha_enabled && $recaptcha_site_key): ?>
<div><div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_site_key; ?>"></div></div>
<?php else: ?>
<div class="text-xs text-gray-400 flex items-center gap-1.5">
    <i class="fa-solid fa-shield-halved text-blue-500"></i> We usually respond within 24 hours.
</div>
<?php endif; ?>
<button type="submit" id="contactSubmitBtn" class="group relative w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-9 py-3.5 text-sm font-bold text-white bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 hover:from-blue-700 hover:via-indigo-700 hover:to-blue-800 active:scale-[0.98] rounded-xl shadow-lg shadow-blue-600/25 hover:shadow-xl hover:shadow-blue-600/35 transition-all duration-200 cursor-pointer overflow-hidden">
    <span class="relative z-10 flex items-center gap-2.5">
        <span>Send Message</span>
        <i class="fa-solid fa-paper-plane text-xs transition-transform duration-200 group-hover:translate-x-1 group-hover:-translate-y-0.5"></i>
    </span>
    <span class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity duration-200"></span>
</button>
</div>
</form>
</div>
<div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
<?php $c_email = getSetting('site_email'); if ($c_email): ?>
<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow text-center"><div class="text-blue-600 text-3xl mb-3"><i class="fa fa-envelope"></i></div><h3 class="font-semibold mb-2">Email</h3><p class="text-gray-600 dark:text-gray-400"><?php echo $c_email; ?></p></div>
<?php endif; ?>
<?php $c_phone = getSetting('site_phone'); if ($c_phone): ?>
<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow text-center"><div class="text-blue-600 text-3xl mb-3"><i class="fa fa-phone"></i></div><h3 class="font-semibold mb-2">Phone</h3><p class="text-gray-600 dark:text-gray-400"><?php echo $c_phone; ?></p></div>
<?php endif; ?>
<?php $c_addr = getSetting('site_address'); if ($c_addr): ?>
<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow text-center"><div class="text-blue-600 text-3xl mb-3"><i class="fa fa-map-marker"></i></div><h3 class="font-semibold mb-2">Address</h3><p class="text-gray-600 dark:text-gray-400"><?php echo $c_addr; ?></p></div>
<?php endif; ?>
</div>
</div>
</section>
<?php include "footer.php"; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@accessible360/accessible-slick@1.0.1/slick/slick.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/fancybox@3.5.6/dist/jquery.fancybox.min.js"></script>
<script src="https://unpkg.com/alpinejs@3.14.9/dist/cdn.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
<script src="https://unpkg.com/@material-tailwind/html@3.0.0-beta.7/scripts/ripple.js"></script>
<script src="https://unpkg.com/@material-tailwind/html@2.0.0/scripts/collapse.js"></script>
<script src="https://unpkg.com/@material-tailwind/html@2.0.0/scripts/dialog.js"></script>
<script src="https://unpkg.com/@material-tailwind/html@2.0.0/scripts/dismissible.js"></script>
<script type="module" src="https://unpkg.com/@material-tailwind/html@2.0.0/scripts/popover.js"></script>
<script src="https://unpkg.com/@material-tailwind/html@2.0.0/scripts/tabs.js"></script>
<script type="module" src="https://unpkg.com/@material-tailwind/html@2.0.0/scripts/tooltip.js"></script>
<script src="/js/scroll.js"></script>
<script src="/js/ns.js"></script>
<script src="/js/ns-jquery.js"></script>
<?php if ($recaptcha_enabled && $recaptcha_site_key): ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('publicContactForm');
    if (form) {
        form.addEventListener('submit', function() {
            var btn = document.getElementById('contactSubmitBtn');
            if (btn) {
                btn.disabled = true;
                btn.classList.add('opacity-80', 'cursor-not-allowed');
                btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin text-xs"></i> <span>Sending Message...</span>';
            }
        });
    }
});
</script>
</body>
</html>

