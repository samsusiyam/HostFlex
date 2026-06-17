<?php
$page_title = 'Email Templates';
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

// Ensure table exists and seed defaults
$check = mysqli_query($conn, "SHOW TABLES LIKE 'email_templates'");
if (mysqli_num_rows($check) == 0) {
    mysqli_query($conn, "CREATE TABLE email_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        variables VARCHAR(255) NOT NULL DEFAULT '',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $seeds = [
        [
            'Contact Auto-Reply',
            'Thank you for contacting {site_name}',
            "<p>Dear {name},</p>\n<p>Thank you for reaching out to us. We have received your message and will get back to you shortly.</p>\n<p><strong>Your message:</strong></p>\n<p>{message}</p>\n<p>Best regards,<br>{site_name} Team</p>",
            'name,email,message,site_name,site_url'
        ],
        [
            'Contact Forward (Admin)',
            'New Contact Message: {subject}',
            "<h3>New Contact Message</h3>\n<p><strong>Name:</strong> {name}</p>\n<p><strong>Email:</strong> {email}</p>\n<p><strong>Subject:</strong> {subject}</p>\n<p><strong>Message:</strong><br>{message}</p>",
            'name,email,subject,message,site_name,site_url'
        ]
    ];

    $stmt = mysqli_prepare($conn, "INSERT INTO email_templates (name, subject, body, variables) VALUES (?, ?, ?, ?)");
    foreach ($seeds as $s) {
        mysqli_stmt_bind_param($stmt, 'ssss', $s[0], $s[1], $s[2], $s[3]);
        mysqli_stmt_execute($stmt);
    }
    mysqli_stmt_close($stmt);
}

// Auto-seed Contact Forward template for existing installations
$check_fwd = mysqli_query($conn, "SELECT id FROM email_templates WHERE name = 'Contact Forward (Admin)'");
if (mysqli_num_rows($check_fwd) == 0) {
    mysqli_query($conn, "INSERT INTO email_templates (name, subject, body, variables) VALUES (
        'Contact Forward (Admin)',
        'New Contact Message: {subject}',
        '<h3>New Contact Message</h3>\n<p><strong>Name:</strong> {name}</p>\n<p><strong>Email:</strong> {email}</p>\n<p><strong>Subject:</strong> {subject}</p>\n<p><strong>Message:</strong><br>{message}</p>',
        'name,email,subject,message,site_name,site_url'
    )");
}

// POST handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $subject = sanitize($_POST['subject']);
    $body = mysqli_real_escape_string($conn, $_POST['body']);
    $variables = sanitize($_POST['variables']);

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        mysqli_query($conn, "UPDATE email_templates SET name='$name', subject='$subject', body='$body', variables='$variables' WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO email_templates (name, subject, body, variables) VALUES ('$name', '$subject', '$body', '$variables')");
    }
    header('Location: email-templates.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM email_templates WHERE id = $id");
    header('Location: email-templates.php');
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $r = mysqli_query($conn, "SELECT * FROM email_templates WHERE id = $id");
    $edit = mysqli_fetch_assoc($r);
}

$templates = mysqli_query($conn, "SELECT * FROM email_templates ORDER BY name ASC");
?>
<?php include 'header.php'; ?>
<div class="mb-6 flex justify-between items-center">
    <div><h1 class="text-2xl font-bold text-gray-800">Email Templates</h1><p class="text-gray-500">Manage transactional email templates for your site</p></div>
    <a href="?action=add" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"><i class="fa fa-plus"></i> Add Template</a>
</div>

<?php if (isset($_GET['action']) || $edit): ?>
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4"><?php echo $edit ? 'Edit Template' : 'Add Template'; ?></h2>
    <form method="POST">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?php echo $edit['id']; ?>"><?php endif; ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" value="<?php echo $edit ? htmlspecialchars($edit['name']) : ''; ?>" required class="w-full border rounded px-3 py-2" placeholder="e.g. Welcome Email">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                <input type="text" name="subject" value="<?php echo $edit ? htmlspecialchars($edit['subject']) : ''; ?>" required class="w-full border rounded px-3 py-2" placeholder="e.g. Welcome to {site_name}!">
                <p class="text-xs text-gray-400 mt-1">Use variables like <code class="bg-gray-100 px-1 rounded">{name}</code>, <code class="bg-gray-100 px-1 rounded">{site_name}</code></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Variables (comma-separated)</label>
                <input type="text" name="variables" value="<?php echo $edit ? htmlspecialchars($edit['variables']) : ''; ?>" class="w-full border rounded px-3 py-2" placeholder="name,email,site_name" readonly onfocus="this.removeAttribute('readonly')">
                <p class="text-xs text-gray-400 mt-1">Available: <code class="bg-gray-100 px-1 rounded">{name}</code> <code class="bg-gray-100 px-1 rounded">{email}</code> <code class="bg-gray-100 px-1 rounded">{site_name}</code> <code class="bg-gray-100 px-1 rounded">{site_url}</code> <code class="bg-gray-100 px-1 rounded">{reset_link}</code> <code class="bg-gray-100 px-1 rounded">{message}</code></p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Body (HTML)</label>
                <textarea name="body" rows="15" class="w-full border rounded px-3 py-2 font-mono text-sm"><?php echo $edit ? htmlspecialchars($edit['body']) : ''; ?></textarea>
                <p class="text-xs text-gray-400 mt-1">Use any variables listed above wrapped in curly braces, e.g. <code class="bg-gray-100 px-1 rounded">{name}</code></p>
            </div>
        </div>
        <div class="mt-4 flex items-center space-x-4">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700"><?php echo $edit ? 'Update' : 'Add'; ?></button>
            <a href="email-templates.php" class="text-gray-600 px-4 py-2 border rounded hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full">
        <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Updated</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th></tr></thead>
        <tbody class="divide-y divide-gray-200">
            <?php while ($t = mysqli_fetch_assoc($templates)): ?>
            <tr><td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($t['name']); ?></td><td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($t['subject']); ?></td><td class="px-6 py-4 text-sm"><?php echo $t['updated_at']; ?></td><td class="px-6 py-4 text-sm space-x-2"><a href="?edit=<?php echo $t['id']; ?>" class="text-blue-600"><i class="fa fa-edit"></i></a> <a href="?delete=<?php echo $t['id']; ?>" onclick="return confirm('Delete this template?')" class="text-red-600"><i class="fa fa-trash"></i></a></td></tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include 'footer.php'; ?>
