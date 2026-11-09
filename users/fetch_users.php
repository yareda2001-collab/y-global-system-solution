<?php
session_start();
header('Content-Type: application/json');

error_log("fetch_users.php: session_id=" . session_id() . ", session_data=" . json_encode($_SESSION));

if (!isset($_SESSION['user_id'])) {
    error_log('fetch_users.php: Session user_id not set');
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

include '../connect.php';

if ($conn->connect_error) {
    error_log('fetch_users.php: Database connection failed: ' . $conn->connect_error);
    echo json_encode(['error' => 'Database connection error']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
error_log("fetch_users.php: Fetching users for user_id: $user_id");

try {
    // Fetch all active users except the logged-in user
    $sql = "SELECT id, username, role FROM users WHERE id != ? AND status = 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('fetch_users.php: Prepare failed: ' . $conn->error);
        echo json_encode(['error' => 'Database query error']);
        exit;
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Add unread message count for each user
    $result = [];
    foreach ($users as $user) {
        $sql = "SELECT COUNT(*) as unread_count FROM messages WHERE receiver_id = ? AND sender_id = ? AND seen_by_user = 0";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log('fetch_users.php: Prepare failed for unread count: ' . $conn->error);
            continue;
        }
        $stmt->bind_param("ii", $user_id, $user['id']);
        $stmt->execute();
        $unread_count = $stmt->get_result()->fetch_assoc()['unread_count'];
        $stmt->close();
        $result[] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'unread_count' => (int)$unread_count
        ];
    }
    error_log("fetch_users.php: Returning " . count($result) . " users");
    echo json_encode($result);
} catch (Exception $e) {
    error_log('fetch_users.php: Exception - ' . $e->getMessage());
    echo json_encode(['error' => 'Server error']);
} finally {
    $conn->close();
}
?>