<?php require_once 'config/database.php'; require_once 'includes/functions.php'; require_once 'includes/mail.php'; checkMaintenance();

$success = '';
$error = '';
$recaptcha_enabled = getSetting('recaptcha_enabled') === '1';
$recaptcha_site_key = getSetting('recaptcha_site_key');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Raw values for email templates (not double-escaped)
    $raw_name = trim($_POST['name']);
    $raw_email = trim($_POST['email']);
    $raw_subject = trim($_POST['subject']);
    $raw_message = $_POST['message'];
    // Sanitized values for DB storage
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);

    if ($recaptcha_enabled) {
        $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
        $secret_key = getSetting('recaptcha_secret_key');
        $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret_key&response=$recaptcha_response");
        $captcha = json_decode($verify);
        if (!$captcha->success) {
            $error = 'Please complete the reCAPTCHA verification.';
        }
    }

    if (!$error) {
        $query = "INSERT INTO contacts (name, email, subject, message) VALUES ('$name', '$email', '$subject', '$message')";
        if (mysqli_query($conn, $query)) {
            $site_name = getSetting('site_name') ?: 'HostFlex';

            // Send auto-reply to submitter using Contact Auto-Reply template
            $tpl = mysqli_query($conn, "SELECT * FROM email_templates WHERE name = 'Contact Auto-Reply' LIMIT 1");
            if ($tpl_row = mysqli_fetch_assoc($tpl)) {
                $body = str_replace(
                    ['{name}', '{email}', '{message}', '{site_name}', '{site_url}'],
                    [htmlspecialchars($raw_name), htmlspecialchars($raw_email), nl2br(htmlspecialchars($raw_message)), $site_name, $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']],
                    $tpl_row['body']
                );
                $subj = str_replace(['{site_name}', '{site_url}'], [$site_name, $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']], $tpl_row['subject']);
                sendMail($email, $subj, $body);
            }

            // Forward to admin with reply-to set to submitter
            $forward_emails_raw = getSetting('contact_forward_emails');
            $forward_emails = [];
            if ($forward_emails_raw) {
                $lines = explode("\n", $forward_emails_raw);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line && filter_var($line, FILTER_VALIDATE_EMAIL)) {
                        $forward_emails[] = $line;
                    }
                }
            }
            if (empty($forward_emails)) {
                $fallback = getSetting('site_email') ?: getSetting('smtp_from_email');
                if ($fallback) $forward_emails[] = $fallback;
            }
            if (!empty($forward_emails)) {
                // Load forward email template
                $fwd_tpl = mysqli_query($conn, "SELECT * FROM email_templates WHERE name = 'Contact Forward (Admin)' LIMIT 1");
                $fwd_subj = "Contact: $raw_subject";
                $fwd_body = "<h3>New Contact Message</h3>
                    <p><strong>Name:</strong> " . htmlspecialchars($raw_name) . "</p>
                    <p><strong>Email:</strong> " . htmlspecialchars($raw_email) . "</p>
                    <p><strong>Subject:</strong> " . htmlspecialchars($raw_subject) . "</p>
                    <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($raw_message)) . "</p>";
                if ($fwd_tpl_row = mysqli_fetch_assoc($fwd_tpl)) {
                    $fwd_subj = str_replace(
                        ['{name}', '{email}', '{subject}', '{message}', '{site_name}', '{site_url}'],
                        [$raw_name, $raw_email, $raw_subject, nl2br(htmlspecialchars($raw_message)), $site_name, $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']],
                        $fwd_tpl_row['subject']
                    );
                    $fwd_body = str_replace(
                        ['{name}', '{email}', '{subject}', '{message}', '{site_name}', '{site_url}'],
                        [htmlspecialchars($raw_name), htmlspecialchars($raw_email), htmlspecialchars($raw_subject), nl2br(htmlspecialchars($raw_message)), $site_name, $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']],
                        $fwd_tpl_row['body']
                    );
                }
                foreach ($forward_emails as $forward_to) {
                    $mail_err = '';
                    $forward_sent = sendMail($forward_to, $fwd_subj, $fwd_body, $email, $mail_err);
                    if (!$forward_sent) {
                        error_log("Contact forward failed to $forward_to: $mail_err");
                    }
                }
            }

            $success = 'Your message has been sent successfully.';
        } else {
            $error = 'Something went wrong. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include "cdnjs.php"; ?>
<title>Contact - <?php echo escSetting('site_name'); ?></title>
</head>
<body>
<?php include "header.php"; ?>
<?php include "contact-btn.php"; ?>
<section class="section_gap flex items-center bg-gray-50 font-poppins dark:bg-gray-800" id="main-content">
<div class="content">
<div class="mb-20 text-center pb-7">
<h2 class="pb-2 mb-2 text-xl font-bold text-gray-800 md:text-4xl dark:text-gray-300"><?php echo escSetting('contact_page_heading') ?: 'Contact Us'; ?></h2>
<p class="text-lg text-gray-500 sm:text-xl dark:text-gray-400"><?php echo escSetting('contact_page_subheading') ?: 'We would love to hear from you.'; ?></p>
</div>
<?php if ($success): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 max-w-4xl mx-auto"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 max-w-4xl mx-auto"><?php echo $error; ?></div><?php endif; ?>
<div class="px-3 py-6">
<form method="POST" toolname="send_contact_message" tooldescription="Sends a contact message to HostNibo support team with name, email, subject, and message" class="rounded shadow dark:bg-gray-900 bg-gray-50 p-12 max-w-4xl mx-auto" aria-label="Contact form">
<div class="flex flex-wrap">
<div class="w-full md:w-1/2 px-3 md:mb-4">
<label class="block mb-3 font-bold text-gray-700 uppercase dark:text-gray-400">Name</label>
<input name="name" type="text" placeholder="Your Name" required toolparamdescription="The sender's full name" aria-label="Your name" class="block w-full px-4 py-3 mb-3 leading-tight text-gray-700 bg-gray-100 border rounded lg:mb-0 dark:text-gray-400 dark:border-gray-800 dark:bg-gray-800">
</div>
<div class="w-full px-3 md:w-1/2 md:mb-0">
<label class="block mb-3 font-bold text-gray-700 uppercase dark:text-gray-400">Email</label>
<input name="email" type="email" placeholder="Your Email" required toolparamdescription="The sender's email address" aria-label="Your email address" class="block w-full px-4 py-3 mb-3 leading-tight text-gray-700 bg-gray-100 border rounded dark:placeholder-gray-500 dark:text-gray-400 dark:border-gray-800 dark:bg-gray-800">
</div>
</div>
<div class="px-3 mb-6">
<label class="block mb-3 font-bold text-gray-700 uppercase dark:text-gray-400">Subject</label>
<input name="subject" type="text" placeholder="Your Subject" required toolparamdescription="The subject of the message" aria-label="Message subject" class="block w-full px-4 py-3 mb-3 leading-tight text-gray-700 bg-gray-100 border rounded dark:placeholder-gray-500 dark:text-gray-400 dark:border-gray-800 dark:bg-gray-800">
</div>
<div class="px-3 mb-6">
<label class="block mb-3 font-bold text-gray-700 uppercase dark:text-gray-400">Message</label>
<textarea name="message" placeholder="Write your message here..." required toolparamdescription="The message content to send" aria-label="Your message" class="block w-full px-4 py-10 leading-tight text-gray-700 bg-gray-100 rounded dark:placeholder-gray-500 dark:text-gray-400 dark:border-gray-800 dark:bg-gray-800"></textarea>
</div>
<div class="px-6">
<?php if ($recaptcha_enabled && $recaptcha_site_key): ?>
<div class="mb-4"><div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptcha_site_key, ENT_QUOTES, 'UTF-8'); ?>"></div></div>
<?php endif; ?>
<button class="px-4 py-2 font-medium text-gray-100 bg-blue-600 rounded shadow hover:bg-blue-700 dark:bg-blue-500" aria-label="Send contact message">Send Message</button>
</div>
</form>
</div>
<div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
<?php $c_email = escSetting('site_email'); if ($c_email): ?>
<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow text-center"><div class="text-blue-600 text-3xl mb-3"><i class="fa fa-envelope"></i></div><h3 class="font-semibold mb-2">Email</h3><p class="text-gray-600 dark:text-gray-400"><?php echo $c_email; ?></p></div>
<?php endif; ?>
<?php $c_phone = escSetting('site_phone'); if ($c_phone): ?>
<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow text-center"><div class="text-blue-600 text-3xl mb-3"><i class="fa fa-phone"></i></div><h3 class="font-semibold mb-2">Phone</h3><p class="text-gray-600 dark:text-gray-400"><?php echo $c_phone; ?></p></div>
<?php endif; ?>
<?php $c_addr = escSetting('site_address'); if ($c_addr): ?>
<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow text-center"><div class="text-blue-600 text-3xl mb-3"><i class="fa fa-map-marker"></i></div><h3 class="font-semibold mb-2">Address</h3><p class="text-gray-600 dark:text-gray-400"><?php echo $c_addr; ?></p></div>
<?php endif; ?>
</div>
</div>
</section>
<?php include "footer.php"; ?>
<?php if ($recaptcha_enabled && $recaptcha_site_key): ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>
</body>
</html>

