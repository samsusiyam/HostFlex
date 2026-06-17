<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
checkAdminLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp','svg'];
    if (!in_array($ext, $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type']);
        exit;
    }
    $upload_dir = '../uploads/content/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    $fname = 'img_' . time() . '_' . rand(100,999) . '.' . $ext;
    move_uploaded_file($file['tmp_name'], $upload_dir . $fname);
    echo json_encode(['location' => 'uploads/content/' . $fname]);
    exit;
}
http_response_code(400);
echo json_encode(['error' => 'No file uploaded']);
