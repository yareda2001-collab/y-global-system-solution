<?php
session_start();
header('Content-Type: application/json');

error_log("mark_messages_seen.php: session_id=" . session_id() . ", session_data=" . json_encode($_SESSION));

if (!isset($_SESSION['user_id'])) {
    error_log('mark_messages_seen.php: Session user_id not set');
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

include '../connect.php';

if ($conn->connect_error) {
    error_log('mark_messages_seen.php: Database connection failed: ' . $conn->connect_error);
    echo json_encode(['error' => 'Database connection error']);
    exit;
}

$receiver_id = (int)$_SESSION['user_id'];
$sender_id = filter_input(INPUT_POST, 'sender_id', FILTER_VALIDATE_INT);

error_log("mark_messages_seen.php: Receiver ID: $receiver_id, Sender ID: $sender_id");

if (!$sender_id) {
    error_log('mark_messages_seen.php: Missing or invalid sender_id');
    echo json_encode(['error' => 'Missing sender ID']);
    exit;
}

try {
    $sql = "UPDATE messages SET seen_by_user = 1 WHERE receiver_id = ? AND sender_id = ? AND seen_by_user = 0";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('mark_messages_seen.php: Prepare failed: ' . $conn->error);
        echo json_encode(['error' => 'Database query error']);
        exit;
    }
    $stmt->bind_param("ii", $receiver_id, $sender_id);
    if (!$stmt->execute()) {
        error_log('mark_messages_seen.php: Execute failed: ' . $stmt->error);
        echo json_encode(['error' => 'Database execution error']);
        exit;
    }
    $affected_rows = $stmt->affected_rows;
    error_log("mark_messages_seen.php: Marked $affected_rows messages as seen for sender_id: $sender_id");
    echo json_encode(['success' => true, 'affected_rows' => $affected_rows]);
    $stmt->close();
} catch (Exception $e) {
    error_log('mark_messages_seen.php: Exception - ' . $e->getMessage());
    echo json_encode(['error' => 'Server error']);
} finally {
    $conn->close();
}
?>