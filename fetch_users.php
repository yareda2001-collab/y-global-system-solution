<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    error_log('fetch_users.php: Session user_id not set');
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

include '../connect.php';

$user_id = (int)$_SESSION['user_id'];

error_log("fetch_users.php: User ID: $user_id");

try {
    // Fetch all active users except the current user
    $sql = "SELECT u.id, u.username, u.role
            FROM users u
            WHERE u.id != ? AND u.status = 1
            ORDER BY u.username";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('fetch_users.php: Prepare failed: ' . $conn->error);
        echo json_encode(['error' => 'Database error']);
        exit;
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Add unread_count and last_message
    foreach ($users as &$user) {
        $other_user_id = $user['id'];
        // Unread count
        $unread_sql = "SELECT COUNT(*) as unread_count FROM messages WHERE receiver_id = ? AND sender_id = ? AND seen_by_user = 0";
        $unread_stmt = $conn->prepare($unread_sql);
        if (!$unread_stmt) {
            error_log('fetch_users.php: Unread prepare failed: ' . $conn->error);
            continue;
        }
        $unread_stmt->bind_param("ii", $user_id, $other_user_id);
        $unread_stmt->execute();
        $user['unread_count'] = $unread_stmt->get_result()->fetch_assoc()['unread_count'];
        $unread_stmt->close();
        // Last message
        $last_sql = "SELECT MAX(created_at) as last_message FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)";
        $last_stmt = $conn->prepare($last_sql);
        if (!$last_stmt) {
            error_log('fetch_users.php: Last message prepare failed: ' . $conn->error);
            continue;
        }
        $last_stmt->bind_param("iiii", $other_user_id, $user_id, $user_id, $other_user_id);
        $last_stmt->execute();
        $user['last_message'] = $last_stmt->get_result()->fetch_assoc()['last_message'] ?? null;
        $last_stmt->close();
    }

    // Sort by last_message (newest first)
    usort($users, function($a, $b) {
        $a_time = $a['last_message'] ? strtotime($a['last_message']) : 0;
        $b_time = $b['last_message'] ? strtotime($b['last_message']) : 0;
        return $b_time - $a_time;
    });

    error_log("fetch_users.php: Fetched " . count($users) . " users");
    echo json_encode($users);
} catch (Exception $e) {
    error_log('fetch_users.php: Error - ' . $e->getMessage());
    echo json_encode(['error' => 'Server error']);
}

$conn->close();
?>