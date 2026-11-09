<?php
session_start();
header('Content-Type: application/json');

error_log("fetch_messages.php: session_id=" . session_id() . ", session_data=" . json_encode($_SESSION));

if (!isset($_SESSION['user_id'])) {
    error_log('fetch_messages.php: Session user_id not set');
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

include '../connect.php';

if ($conn->connect_error) {
    error_log('fetch_messages.php: Database connection failed: ' . $conn->connect_error);
    echo json_encode(['error' => 'Database connection error']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$receiver_id = filter_input(INPUT_GET, 'receiver_id', FILTER_VALIDATE_INT);

error_log("fetch_messages.php: User ID: $user_id, Receiver ID: $receiver_id");

if (!$receiver_id) {
    error_log('fetch_messages.php: Missing or invalid receiver_id');
    echo json_encode(['error' => 'Missing receiver ID']);
    exit;
}

try {
    // Fetch messages between user_id and receiver_id
    $sql = "SELECT id, sender_id, receiver_id, message, is_edited, seen_by_user, created_at
            FROM messages
            WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
            ORDER BY created_at ASC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('fetch_messages.php: Prepare failed: ' . $conn->error);
        echo json_encode(['error' => 'Database query error']);
        exit;
    }
    $stmt->bind_param("iiii", $user_id, $receiver_id, $receiver_id, $user_id);
    if (!$stmt->execute()) {
        error_log('fetch_messages.php: Execute failed: ' . $stmt->error);
        echo json_encode(['error' => 'Database execution error']);
        exit;
    }
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    error_log("fetch_messages.php: Fetched " . count($messages) . " messages for user_id: $user_id, receiver_id: $receiver_id");
    echo json_encode($messages);
    $stmt->close();
} catch (Exception $e) {
    error_log('fetch_messages.php: Exception - ' . $e->getMessage());
    echo json_encode(['error' => 'Server error']);
} finally {
    $conn->close();
}
?>