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
<div class="mb-20 text-center pb-7">
<h2 class="pb-2 mb-2 text-xl font-bold text-gray-800 md:text-4xl dark:text-gray-300"><?php echo getSetting('contact_page_heading') ?: 'Contact Us'; ?></h2>
<p class="text-lg text-gray-500 sm:text-xl dark:text-gray-400"><?php echo getSetting('contact_page_subheading') ?: 'We would love to hear from you.'; ?></p>
</div>

<?php if ($success): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 max-w-4xl mx-auto flex items-center justify-between">
    <div class="flex items-center gap-2">
        <i class="fa-solid fa-circle-check text-green-600"></i>
        <span><?php echo htmlspecialchars($success); ?></span>
    </div>
    <button onclick="this.parentElement.remove()" class="text-green-500 hover:text-green-700 cursor-pointer">&times;</button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 max-w-4xl mx-auto flex items-center justify-between">
    <div class="flex items-center gap-2">
        <i class="fa-solid fa-circle-exclamation text-red-600"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
    <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 cursor-pointer">&times;</button>
</div>
<?php endif; ?>

<div class="px-3 py-6">
<form method="POST" id="publicContactForm" class="rounded shadow dark:bg-gray-900 bg-gray-50 p-12 max-w-4xl mx-auto">
<div class="flex flex-wrap">
<div class="w-full md:w-1/2 px-3 md:mb-4">
<label class="block mb-3 font-bold text-gray-700 uppercase dark:text-gray-400">Name</label>
<input name="name" type="text" placeholder="Your Name" required class="block w-full px-4 py-3 mb-3 leading-tight text-gray-700 bg-gray-100 border rounded lg:mb-0 dark:text-gray-400 dark:border-gray-800 dark:bg-gray-800">
</div>
<div class="w-full px-3 md:w-1/2 md:mb-0">
<label class="block mb-3 font-bold text-gray-700 uppercase dark:text-gray-400">Email</label>
<input name="email" type="email" placeholder="Your Email" required class="block w-full px-4 py-3 mb-3 leading-tight text-gray-700 bg-gray-100 border rounded dark:placeholder-gray-500 dark:text-gray-400 dark:border-gray-800 dark:bg-gray-800">
</div>
</div>
<div class="px-3 mb-6">
<label class="block mb-3 font-bold text-gray-700 uppercase dark:text-gray-400">Subject</label>
<input name="subject" type="text" placeholder="Your Subject" required class="block w-full px-4 py-3 mb-3 leading-tight text-gray-700 bg-gray-100 border rounded dark:placeholder-gray-500 dark:text-gray-400 dark:border-gray-800 dark:bg-gray-800">
</div>
<div class="px-3 mb-6">
<label class="block mb-3 font-bold text-gray-700 uppercase dark:text-gray-400">Message</label>
<textarea name="message" placeholder="Write your message here..." required class="block w-full px-4 py-10 leading-tight text-gray-700 bg-gray-100 rounded dark:placeholder-gray-500 dark:text-gray-400 dark:border-gray-800 dark:bg-gray-800"></textarea>
</div>
<div class="px-6">
<?php if ($recaptcha_enabled && $recaptcha_site_key): ?>
<div class="mb-4"><div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_site_key; ?>"></div></div>
<?php endif; ?>
<button type="submit" id="contactSubmitBtn" data-ripple-light="true" class="btn btn-blue !px-8 font-semibold shadow-xs">
    Send Message
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

