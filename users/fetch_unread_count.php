<?php
session_start();
header('Content-Type: application/json');

error_log("fetch_unread_count.php: session_id=" . session_id() . ", session_data=" . json_encode($_SESSION));

if (!isset($_SESSION['user_id'])) {
    error_log('fetch_unread_count.php: Session user_id not set');
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

include '../connect.php';

if ($conn->connect_error) {
    error_log('fetch_unread_count.php: Database connection failed: ' . $conn->connect_error);
    echo json_encode(['error' => 'Database connection error']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
error_log("fetch_unread_count.php: Fetching unread count for user_id: $user_id");

try {
    // Verify user exists
    $sql = "SELECT username FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('fetch_unread_count.php: Prepare failed for user check: ' . $conn->error);
        echo json_encode(['error' => 'Database query error']);
        exit;
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        error_log('fetch_unread_count.php: Invalid user_id: ' . $user_id);
        echo json_encode(['error' => 'Invalid user ID']);
        exit;
    }
    $stmt->close();

    // Fetch unread message count
    $sql = "SELECT COUNT(*) as unread_count FROM messages WHERE receiver_id = ? AND seen_by_user = 0";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('fetch_unread_count.php: Prepare failed for unread count: ' . $conn->error);
        echo json_encode(['error' => 'Database query error']);
        exit;
    }
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        error_log('fetch_unread_count.php: Execute failed: ' . $stmt->error);
        echo json_encode(['error' => 'Database execution error']);
        exit;
    }
    $result = $stmt->get_result()->fetch_assoc();
    $unread_count = (int)($result['unread_count'] ?? 0);
    error_log("fetch_unread_count.php: Unread count for user_id $user_id: $unread_count");
    echo json_encode(['unread_count' => $unread_count]);
    $stmt->close();
} catch (Exception $e) {
    error_log('fetch_unread_count.php: Exception - ' . $e->getMessage());
    echo json_encode(['error' => 'Server error']);
} finally {
    $conn->close();
}
?>