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

$check = mysqli_query($conn, "SELECT id, status FROM subscribers WHERE email = '" . mysqli_real_escape_string($conn, $email) . "' LIMIT 1");
if ($row = mysqli_fetch_assoc($check)) {
    if ($row['status'] === 'unsubscribed') {
        mysqli_query($conn, "UPDATE subscribers SET status = 'active' WHERE id = " . $row['id']);
        echo json_encode(['success' => true, 'message' => 'You have been resubscribed!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'You are already subscribed!']);
    }
    exit;
}

$sql = "INSERT INTO subscribers (email) VALUES ('" . mysqli_real_escape_string($conn, $email) . "')";
if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Successfully subscribed!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Subscription failed. Please try again.']);
}
