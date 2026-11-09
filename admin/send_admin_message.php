<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['user_id'])) {
    error_log('send_admin_message.php: Session invalid');
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

include 'connect.php';

$admin_id = (int)$_SESSION['user_id'];
$receiver_id = filter_input(INPUT_POST, 'receiver_id', FILTER_VALIDATE_INT);
$message = isset($_POST['message']) && is_string($_POST['message'])
    ? trim($_POST['message'])
    : '';

error_log("send_admin_message.php: Admin ID: $admin_id, Receiver ID: $receiver_id");

if (!$receiver_id || !$message) {
    error_log('send_admin_message.php: Invalid receiver ID or empty message');
    echo json_encode(['success' => false, 'error' => 'Invalid receiver ID or empty message']);
    exit;
}

// Accept the active-status formats used by both local and hosted databases.
$sql = "SELECT id
        FROM users
        WHERE id = ?
          AND LOWER(CAST(status AS CHAR)) IN ('1', 'active', 'enabled')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $receiver_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $stmt->close();
    error_log('send_admin_message.php: Receiver does not exist');
    echo json_encode(['success' => false, 'error' => 'Receiver does not exist']);
    exit;
}
$stmt->close();

try {
    $sql = "INSERT INTO messages (sender_id, receiver_id, message, is_admin, is_seen, seen_by_user, is_edited, created_at)
            VALUES (?, ?, ?, 1, 0, 0, 0, NOW())";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("iis", $admin_id, $receiver_id, $message);
    if ($stmt->execute()) {
        error_log("send_admin_message.php: Message sent by Admin ID: $admin_id to Receiver ID: $receiver_id");
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    $stmt->close();
} catch (Exception $e) {
    error_log('send_admin_message.php: Error - ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}

$conn->close();
?>