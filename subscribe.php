<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT id, status FROM subscribers WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$check = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($check)) {
    mysqli_stmt_close($stmt);
    if ($row['status'] === 'unsubscribed') {
        $up = mysqli_prepare($conn, "UPDATE subscribers SET status = 'active' WHERE id = ?");
        mysqli_stmt_bind_param($up, "i", $row['id']);
        mysqli_stmt_execute($up);
        mysqli_stmt_close($up);
        echo json_encode(['success' => true, 'message' => 'You have been resubscribed!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'You are already subscribed!']);
    }
    exit;
}
mysqli_stmt_close($stmt);

$ins = mysqli_prepare($conn, "INSERT INTO subscribers (email) VALUES (?)");
mysqli_stmt_bind_param($ins, "s", $email);
if (mysqli_stmt_execute($ins)) {
    mysqli_stmt_close($ins);
    echo json_encode(['success' => true, 'message' => 'Successfully subscribed!']);
} else {
    mysqli_stmt_close($ins);
    echo json_encode(['success' => false, 'message' => 'Subscription failed. Please try again.']);
}
