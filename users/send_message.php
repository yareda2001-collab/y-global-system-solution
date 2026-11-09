<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    error_log('send_message.php: Session user_id not set');
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

include '../connect.php';

$sender_id = (int)$_SESSION['user_id'];
$receiver_id = filter_input(INPUT_POST, 'receiver_id', FILTER_VALIDATE_INT);
$message = isset($_POST['message']) && is_string($_POST['message'])
    ? trim($_POST['message'])
    : '';

if (!$receiver_id || !$message) {
    echo json_encode(['error' => 'Missing receiver ID or message']);
    exit;
}

try {
    $sql = "SELECT id
            FROM users
            WHERE id = ?
              AND LOWER(CAST(status AS CHAR)) IN ('1', 'active', 'enabled')";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Receiver check prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("i", $receiver_id);
    if (!$stmt->execute()) {
        throw new RuntimeException('Receiver check failed: ' . $stmt->error);
    }
    $receiver_exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if (!$receiver_exists) {
        echo json_encode(['error' => 'Receiver not found or inactive']);
        $conn->close();
        exit;
    }

    $sql = "INSERT INTO messages
                (sender_id, receiver_id, message, is_admin, is_seen, seen_by_user, is_edited, created_at)
            VALUES (?, ?, ?, 0, 0, 0, 0, NOW())";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Message insert prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("iis", $sender_id, $receiver_id, $message);
    if (!$stmt->execute()) {
        throw new RuntimeException('Message insert failed: ' . $stmt->error);
    }

    echo json_encode(['success' => true, 'message_id' => $stmt->insert_id]);
    $stmt->close();
} catch (Throwable $e) {
    error_log('send_message.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Message could not be sent. Please try again.']);
}

$conn->close();
?>