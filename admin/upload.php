<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

function sanitizeSvgFile($filepath) {
    $content = file_get_contents($filepath);
    if ($content === false) return false;
    // Strip scripts, event handlers, and data URIs
    $content = preg_replace('/<script[\s\S]*?<\/script>/i', '', $content);
    $content = preg_replace('/(?:\bon\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+))/i', '', $content);
    $content = preg_replace('/javascript:[^"\'\s>]+/i', '', $content);
    file_put_contents($filepath, $content);
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Upload error code: ' . $file['error']]);
        exit;
    }
    
    // Max 10MB
    if ($file['size'] > 10 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'File size exceeds 10MB limit']);
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp','svg'];
    if (!in_array($ext, $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type']);
        exit;
    }

    $upload_dir = '../uploads/content/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $fname = 'img_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $upload_dir . $fname;
    
    if (move_uploaded_file($file['tmp_name'], $target)) {
        if ($ext === 'svg') {
            sanitizeSvgFile($target);
        }
        echo json_encode(['location' => 'uploads/content/' . $fname]);
        exit;
    }
}
http_response_code(400);
echo json_encode(['error' => 'No file uploaded or upload failed']);

