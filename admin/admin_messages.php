<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Database connection
include 'connect.php';

$admin_id = (int)$_SESSION['user_id'];

// Fetch logged-in admin's details
$admin_sql = "SELECT username, role FROM users WHERE id = ?";
$admin_stmt = $conn->prepare($admin_sql);
if (!$admin_stmt) {
    error_log("admin_messages.php: Prepare failed for admin info: " . $conn->error);
    die("Database error");
}
$admin_stmt->bind_param("i", $admin_id);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();
$logged_in_admin = $admin_result->num_rows > 0 ? $admin_result->fetch_assoc() : null;
$is_super_admin = $logged_in_admin && $logged_in_admin['role'] === 'super_admin';
if (!$logged_in_admin) {
    error_log("admin_messages.php: Admin ID $admin_id not found");
    session_destroy();
    header("Location: login.php");
    exit;
}
$admin_stmt->close();

// Fetch total unread message count
$unread_sql = "SELECT COUNT(*) as unread FROM messages WHERE receiver_id = ? AND seen_by_user = 0";
$unread_stmt = $conn->prepare($unread_sql);
if (!$unread_stmt) {
    error_log("admin_messages.php: Prepare failed for unread count: " . $conn->error);
    die("Database error");
}
$unread_stmt->bind_param("i", $admin_id);
$unread_stmt->execute();
$unread_count = $unread_stmt->get_result()->fetch_assoc()['unread'];
$unread_stmt->close();

// Count pending requests for notification bell
$sql = "SELECT COUNT(*) as pending_count FROM password_reset_requests WHERE status = 'pending'";
$result = $conn->query($sql);
$pending_count = $result->num_rows > 0 ? $result->fetch_assoc()['pending_count'] : 0;

$conn->close();

// Sanitize selected_user_id
$selected_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Admin Messages - Y-Global System Solution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="./assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="./assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="./assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="./assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link href="./assets/img/favicon1.png" rel="icon">
    <link href="./assets/img/apple-touch-icon1.png" rel="apple-touch-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="./assets/css/main.css" rel="stylesheet">
    <link href="../assets/css/messages.css?v=4" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #3b82f6;
            --dark-blue: #1e3a8a;
            --light-blue: #93c5fd;
            --success-green: #10b981;
            --warning-orange: #f59e0b;
            --danger-red: #ef4444;
            --gray-600: #4b5563;
            --gray-400: #9ca3af;
            --white: #ffffff;
            --background: #f3f4f6;
            --border-radius: 12px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        html {
            height: 100%;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
            color: var(--gray-600);
        }
        .header {
            background-color: var(--dark-blue);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        .header-container {
            width: 98%;
            max-width: 1920px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            flex-wrap: wrap;
        }
        .logo h3 {
            color: var(--white);
            margin: 0;
            font-size: 1.6rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .navmenu {
            flex-grow: 1;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }
        .navmenu ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            flex-wrap: wrap;
            align-items: center;
        }
        .navmenu ul li {
            margin: 0 0.5rem;
        }
        .navmenu ul li a {
            color: var(--white);
            font-weight: 500;
            font-size: 0.95rem;
            padding: 0.5rem 0.75rem;
            text-decoration: none;
            transition: color 0.3s ease, transform 0.3s ease;
            white-space: nowrap;
        }
        .navmenu ul li a:hover {
            color: var(--light-blue);
            transform: translateY(-2px);
        }
        .navmenu ul li a.active {
            color: var(--light-blue);
            border-bottom: 3px solid var(--light-blue);
        }
        .dropdown ul {
            background-color: #2d4b9e;
            border-radius: 8px;
            padding: 0.5rem 0;
            min-width: 160px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .dropdown ul li a {
            color: var(--white);
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        .dropdown ul li a:hover {
            background-color: var(--primary-blue);
        }
        .btn-getstarted {
            background: var(--danger-red);
            color: var(--white);
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.3s ease;
            white-space: nowrap;
        }
        .btn-getstarted:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        .mobile-nav-toggle {
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
            display: none;
        }
        .main {
            flex: 1;
            padding: 80px 20px 40px;
            margin-top: 90px;
            min-height: calc(100vh - 200px);
        }
        .footer {
            margin-top: auto;
            background-color: var(--dark-blue);
            color: var(--white);
            padding: 1.5rem 0;
        }
        .footer .copyright p {
            margin: 0;
            font-size: 0.9rem;
        }
        .notification-bell {
            position: relative;
            display: inline-block;
            margin-left: 5px;
        }
        .notification-bell .badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: var(--danger-red);
            color: var(--white);
            border-radius: 50%;
            padding: 4px 7px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .chat-container {
            max-width: 1200px;
            margin: 2rem auto;
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 0;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: row;
            min-height: calc(100vh - 140px);
            position: relative;
        }
        .sidebar {
            width: 30%;
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            background: #f9fafb;
            position: relative;
            transition: transform 0.3s ease;
        }
        .sidebar.collapsed {
            transform: translateX(-100%);
            position: absolute;
            z-index: 10;
            width: 60%;
            height: 100%;
        }
        .sidebar-toggle {
            display: none;
            position: absolute;
            top: 1rem;
            right: -40px;
            background: var(--dark-blue);
            color: var(--white);
            border: none;
            padding: 0.5rem;
            border-radius: 0 8px 8px 0;
            cursor: pointer;
            z-index: 11;
        }
        .sidebar-toggle.show {
            display: block;
        }
        .search-container {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            position: relative;
        }
        .search-input {
            width: 100%;
            padding: 0.5rem 2rem 0.5rem 1rem;
            border-radius: 12px;
            border: 1px solid #d1d5db;
            background: var(--white);
            font-size: 0.9rem;
            color: #1f2937;
            transition: all 0.3s ease;
        }
        .search-input:focus {
            border-color: var(--dark-blue);
            box-shadow: 0 0 8px rgba(30, 58, 138, 0.2);
            outline: none;
        }
        .search-input::placeholder {
            color: var(--gray-400);
            font-style: italic;
        }
        .clear-search {
            display: none;
            position: absolute;
            right: 2rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            font-size: 1.2rem;
        }
        .clear-search:hover {
            color: var(--dark-blue);
        }
        .user-list {
            list-style: none;
            padding: 0;
            margin: 0;
            overflow-y: auto;
            flex: 1;
        }
        .user-list::-webkit-scrollbar {
            width: 8px;
        }
        .user-list::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        .user-list::-webkit-scrollbar-thumb {
            background: var(--gray-400);
            border-radius: 4px;
        }
        .user-list::-webkit-scrollbar-thumb:hover {
            background: var(--gray-600);
        }
        .user-list li {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            cursor: pointer;
            transition: background 0.2s;
            touch-action: manipulation;
        }
        .user-list li:hover {
            background: #f1f5f9;
        }
        .user-list li.active {
            background: #e6f3ff;
        }
        .user-list .username {
            font-weight: 500;
            color: #1f2937;
        }
        .user-list .unread-count {
            background: var(--danger-red);
            color: var(--white);
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 0.75rem;
            margin-left: 10px;
        }
        .chat-area {
            width: 70%;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
        }
        .chat-area.full-width {
            width: 100%;
        }
        .chat-box {
            flex: 1;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #f9fafb;
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .message-container {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            -webkit-overflow-scrolling: touch;
        }
        .message {
            margin-bottom: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            max-width: 70%;
            word-wrap: break-word;
            position: relative;
            transition: background 0.2s;
        }
        .message.admin {
            background: var(--dark-blue);
            color: var(--white);
            margin-left: auto;
            text-align: left;
            border-bottom-right-radius: 2px;
        }
        .message.user {
            background: #e5e7eb;
            color: #1f2937;
            margin-right: auto;
            text-align: left;
            border-bottom-left-radius: 2px;
        }
        .message:hover {
            filter: brightness(95%);
        }
        .message-timestamp {
            font-size: 0.75rem;
            color: var(--gray-400);
            margin-top: 0.25rem;
            opacity: 0.8;
        }
        .message-actions {
            display: none;
            position: absolute;
            top: 5px;
            right: 5px;
        }
        .message.admin:hover .message-actions {
            display: flex;
            gap: 5px;
        }
        .message-actions button {
            background: rgba(0, 0, 0, 0.1);
            border: none;
            color: var(--white);
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .message-actions button:hover {
            background: rgba(0, 0, 0, 0.3);
        }
        .seen-tick {
            font-size: 0.75rem;
            color: var(--light-blue);
            margin-left: 5px;
        }
        .chat-form {
            display: flex;
            align-items: flex-end;
            gap: 0.5rem;
        }
        .chat-form textarea {
            flex: 1;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            resize: none;
            padding: 0.75rem 1rem;
            min-height: 44px;
            max-height: 150px;
            font-size: 0.9rem;
            background: var(--white);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            color: #1f2937;
        }
        .chat-form textarea:focus {
            border-color: var(--dark-blue);
            box-shadow: 0 0 8px rgba(30, 58, 138, 0.4);
            outline: none;
            transform: scale(1.01);
        }
        .chat-form textarea:hover {
            border-color: var(--dark-blue);
            background: #f9fafb;
        }
        .chat-form textarea::placeholder {
            color: var(--gray-400);
            font-style: italic;
        }
        .chat-form .btn-send {
            background-color: var(--dark-blue);
            color: var(--white);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.3s ease;
            white-space: nowrap;
        }
        .chat-form .btn-send:hover {
            background-color: #025c8a;
            transform: translateY(-2px);
        }
        .chat-form .btn-send:active {
            transform: translateY(0);
        }
        .edited-label {
            font-size: 0.75rem;
            color: var(--gray-400);
            margin-left: 5px;
            font-style: italic;
        }
        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow);
        }
        .modal-header {
            background: var(--dark-blue);
            color: var(--white);
            border-top-left-radius: var(--border-radius);
            border-top-right-radius: var(--border-radius);
        }
        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
        }
        .modal-body {
            padding: 1.5rem;
        }
        .modal-footer {
            border-top: none;
            padding: 1rem 1.5rem;
        }
        .modal-footer .btn {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            transition: background 0.2s;
        }
        .btn-primary {
            background-color: var(--dark-blue);
            border: none;
        }
        .btn-primary:hover {
            background-color: #025c8a;
        }
        .btn-danger {
            background-color: var(--danger-red);
            border: none;
        }
        .btn-danger:hover {
            background-color: #b02a37;
        }
        .btn-secondary {
            background-color: var(--gray-600);
            border: none;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        @media (min-width: 1201px) {
            .chat-container {
                max-height: 90vh;
                overflow: hidden;
            }
            .sidebar {
                max-height: calc(90vh - 4rem);
                overflow-y: auto;
            }
            .user-list {
                overflow-y: auto;
            }
        }
        @media (max-width: 1200px) {
            .navmenu ul li {
                margin: 0 0.3rem;
            }
            .navmenu ul li a {
                font-size: 0.9rem;
                padding: 0.4rem 0.6rem;
            }
            .btn-getstarted {
                padding: 0.4rem 1rem;
                font-size: 0.9rem;
            }
            .sidebar {
                max-height: calc(100vh - 120px - 2rem);
                overflow-y: auto;
            }
            .sidebar.collapsed {
                max-height: calc(100vh - 120px - 2rem);
                overflow-y: auto;
            }
            .user-list {
                overflow-y: auto;
            }
        }
        @media (max-width: 992px) {
            .header-container {
                justify-content: space-between;
                align-items: center;
            }
            .logo {
                order: 1;
            }
            .mobile-nav-toggle {
                display: block;
                order: 2;
                margin-right: 0.5rem;
            }
            .navmenu {
                order: 3;
                display: flex;
                align-items: center;
            }
            .btn-getstarted {
                margin-left: 0.5rem;
                order: 4;
            }
            .navmenu ul {
                display: none;
            }
            .navmenu ul.show {
                display: flex;
                flex-direction: column;
                position: absolute;
                top: 60px;
                left: 0;
                right: 0;
                background: #2d4b9e;
                padding: 1rem;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                z-index: 1000;
            }
            .navmenu ul li {
                margin: 0.5rem 0;
            }
            .navmenu ul li a {
                font-size: 1rem;
                padding: 0.5rem;
            }
            .dropdown ul {
                position: static;
                background: var(--primary-blue);
                box-shadow: none;
                margin-left: 1rem;
            }
        }
        @media (max-width: 768px) {
            .main {
                padding: 80px 15px 30px;
            }
            .chat-container {
                flex-direction: row;
                margin: 1rem;
                min-height: calc(100vh - 120px);
                padding: 0;
            }
            .sidebar {
                width: 45%;
                max-height: calc(100vh - 120px - 2rem);
                overflow-y: auto;
            }
            .sidebar.collapsed {
                width: 80%;
                max-height: calc(100vh - 120px - 2rem);
                overflow-y: auto;
            }
            .sidebar-toggle {
                display: block;
            }
            .chat-area {
                width: 55%;
                padding: 0.8rem;
            }
            .chat-area.full-width {
                width: 100%;
            }
            .search-container {
                padding: 0.5rem;
            }
            .search-input {
                padding: 0.4rem 1.5rem 0.4rem 0.8rem;
                font-size: 0.8rem;
            }
            .clear-search {
                right: 1.5rem;
                font-size: 1rem;
            }
            .user-list li {
                padding: 0.5rem;
            }
            .user-list .username {
                font-size: 0.85rem;
            }
            .user-list .unread-count {
                padding: 1px 6px;
                font-size: 0.65rem;
            }
            .chat-box {
                margin-bottom: 0.5rem;
            }
            .message-container {
                padding: 0.5rem;
            }
            .message {
                padding: 0.5rem 0.8rem;
                max-width: 80%;
                font-size: 0.85rem;
            }
            .message-timestamp {
                font-size: 0.65rem;
            }
            .chat-form textarea {
                font-size: 0.8rem;
                min-height: 36px;
                max-height: 100px;
                padding: 0.5rem 0.8rem;
            }
            .chat-form .btn-send {
                font-size: 0.8rem;
                padding: 0.4rem 0.8rem;
            }
            .chat-area h3 {
                font-size: 1rem;
                margin-bottom: 0.5rem;
            }
        }
        @media (max-width: 576px) {
            .chat-container {
                margin: 0.5rem;
                min-height: calc(100vh - 100px);
            }
            .sidebar {
                width: 50%;
                max-height: calc(100vh - 100px - 1rem);
                overflow-y: auto;
            }
            .sidebar.collapsed {
                width: 90%;
                max-height: calc(100vh - 100px - 1rem);
                overflow-y: auto;
            }
            .chat-area {
                width: 50%;
                padding: 0.5rem;
            }
            .search-input {
                font-size: 0.75rem;
                padding: 0.3rem 1.2rem 0.3rem 0.6rem;
            }
            .clear-search {
                font-size: 0.9rem;
            }
            .user-list .username {
                font-size: 0.8rem;
            }
            .user-list .unread-count {
                font-size: 0.6rem;
            }
            .message {
                font-size: 0.8rem;
                padding: 0.4rem 0.6rem;
            }
            .message-timestamp {
                font-size: 0.6rem;
            }
            .chat-form {
                flex-wrap: wrap;
            }
            .chat-form textarea {
                font-size: 0.75rem;
                min-height: 32px;
                padding: 0.4rem 0.6rem;
                margin-bottom: 0.5rem;
            }
            .chat-form .btn-send {
                font-size: 0.75rem;
                padding: 0.3rem 0.6rem;
                width: 100%;
            }
            .chat-area h3 {
                font-size: 0.9rem;
            }
            .logo h3 {
                font-size: 1.2rem;
            }
            .mobile-nav-toggle {
                font-size: 1.3rem;
            }
            .navmenu ul.show {
                top: 50px;
            }
            .btn-getstarted {
                padding: 0.4rem 0.8rem;
                font-size: 0.85rem;
            }
            .modal-body {
                padding: 1rem;
            }
            .modal-body textarea {
                font-size: 0.8rem;
            }
            .modal-footer .btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.85rem;
            }
        }
    </style>
    <style>
        .admin-messages-page .main {
            box-sizing: border-box;
            min-height: calc(100vh - 78px);
            margin-top: 0;
            padding: 98px 1rem 2rem;
        }
        .admin-messages-page .chat-section {
            padding: 0;
        }
        .admin-messages-page .chat-heading {
            flex: 0 0 auto;
        }
        .admin-messages-page .chat-heading-copy {
            min-width: 0;
        }
        .admin-messages-page .chat-heading h1 {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .admin-messages-page .back-to-conversations {
            display: none;
        }
        .admin-messages-page .user-list li.alert {
            display: block;
            margin: 0.6rem;
            padding: 0.85rem;
            cursor: default;
        }
        @media (max-width: 767.98px) {
            .admin-messages-page .main {
                min-height: calc(100vh - 74px);
                padding: 82px 0.75rem 1rem;
            }
            .admin-messages-page .chat-container {
                min-height: 440px;
                height: min(72dvh, 680px);
            }
            .admin-messages-page .chat-container.conversation-active .sidebar {
                display: none;
            }
            .admin-messages-page .chat-container.conversation-active .chat-area {
                display: flex;
            }
            .admin-messages-page .chat-container:not(.conversation-active) .chat-area {
                display: none;
            }
            .admin-messages-page .back-to-conversations {
                display: grid;
                flex: 0 0 auto;
                width: 40px;
                height: 40px;
                place-items: center;
                border: 1px solid #e0e9ee;
                border-radius: 11px;
                background: #fff;
                color: #17637e;
                font-size: 1.15rem;
            }
        }
    </style>
    <link href="assets/css/admin-header.css?v=8" rel="stylesheet">
</head>
<body class="dashboard-page messages-page admin-messages-page admin-app-page">
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="header-container">
            <a href="dashboard.php" class="logo d-flex align-items-center">
                <span class="admin-header-mark"><i class="bi bi-globe2" aria-hidden="true"></i></span>
                <span class="admin-header-brand"><strong>Y-Global</strong><small>System Solution</small></span>
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="admin_messages.php">Chat <span class="notification-bell"><i class="bi bi-bell"></i><?php if ($unread_count > 0): ?><span class="badge"><?php echo $unread_count; ?></span><?php endif; ?></span></a></li>
                    <li><a href="manage_reset_requests.php">Reset <span class="notification-bell"><i class="bi bi-bell"></i><?php if ($pending_count > 0): ?><span class="badge"><?php echo $pending_count; ?></span><?php endif; ?></span></a></li>
                    <li><a href="messages.php">Contact messages</a></li>
                    <li><a href="create_admin.php">Admins</a></li>
                    <li><a href="manage_admin_rights.php">Rights</a></li>
                    <li class="dropdown"><a href="blog.php"><span>Blog</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                        <ul>
                            <li><a href="create_post.php">Create Blog</a></li>
                            <li><a href="blog.php">View Blog</a></li>
                            <li><a href="manage_blog.php">Manage Blog</a></li>
                        </ul>
                    </li>
                    <li class="dropdown"><a href="createportfolio.php"><span>Portfolio</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                        <ul>
                            <li><a href="createportfolio.php">Create Portfolio</a></li>
                            <li><a href="manageportfolio.php">Manage Portfolio</a></li>
                        </ul>
                    </li>
                    <li><a href="index.php">Users</a></li>
                    <li><a href="change_password.php">Password</a></li>
                    <li><a href="profile_settings.php">Profile</a></li>
                </ul>
                <button type="button" class="mobile-nav-toggle bi bi-list" aria-label="Open admin menu" aria-controls="admin-sidebar" aria-expanded="false"></button>
                 <button class="btn-getstarted" onclick="confirmLogout()" style="background-color: var(--danger-red);">Log Out</button>
             </nav>
        </div>
    </header>
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

    <main class="main">
        <section class="chat-section">
            <div class="container chat-container<?php echo $selected_user_id > 0 ? ' conversation-active' : ''; ?>" data-aos="fade-up">
                <div class="sidebar" id="sidebar">
                    <div class="sidebar-heading">
                        <span class="sidebar-heading-icon"><i class="bi bi-chat-dots" aria-hidden="true"></i></span>
                        <div>
                            <h2>Conversations</h2>
                            <p>Manage user messages</p>
                        </div>
                        <span class="unread-summary"><?php echo (int)$unread_count; ?> unread</span>
                    </div>
                    <div class="search-container">
                        <i class="bi bi-search search-icon" aria-hidden="true"></i>
                        <input type="text" class="search-input" id="searchInput" placeholder="Search users...">
                        <button class="clear-search" id="clearSearch"><i class="bi bi-x"></i></button>
                    </div>
                    <ul class="user-list" id="userList"></ul>
                </div>
                <div class="chat-area" id="chatArea">
                    <div class="chat-heading">
                        <button type="button" class="back-to-conversations" id="backToConversations" aria-label="Back to conversations">
                            <i class="bi bi-arrow-left" aria-hidden="true"></i>
                        </button>
                        <span class="chat-heading-icon" id="selectedUserAvatar" aria-hidden="true"><i class="bi bi-chat-square-text"></i></span>
                        <div class="chat-heading-copy">
                            <h1 id="selectedUserTitle">Messages</h1>
                            <p id="selectedUserSubtitle">Select a user to view the conversation</p>
                        </div>
                    </div>
                    <div class="chat-box" id="chatBox">
                        <div class="message-container">
                            <div class="chat-empty-state">
                                <span class="chat-empty-icon"><i class="bi bi-chat-square-heart" aria-hidden="true"></i></span>
                                <h2>Select a conversation</h2>
                                <p>Choose a user from the list to view messages or start a conversation.</p>
                            </div>
                        </div>
                    </div>
                    <form class="chat-form" id="chatForm" style="display: none;">
                        <textarea class="form-control" id="messageInput" placeholder="Type your message... (Enter to send, Shift+Enter for new line)" required></textarea>
                        <button type="button" class="btn btn-send" id="sendMessageButton"><i class="bi bi-send" aria-hidden="true"></i> Send</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <div class="modal fade" id="editMessageModal" tabindex="-1" aria-labelledby="editMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editMessageModalLabel">Edit Message</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <textarea class="form-control" id="editMessageInput" rows="3" placeholder="Edit your message..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveEditButton">Save</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="deleteMessageModal" tabindex="-1" aria-labelledby="deleteMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteMessageModalLabel">Delete Message</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this message? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteButton">Delete</button>
                </div>
            </div>
        </div>
    </div>
<?php include __DIR__ . '/../includes/admin-mobile-nav.php'; ?>
<footer id="footer" class="footer" style="background-color: #4b5563; color: white;">
        <div class="container copyright text-center">
            <p>Copyright © <?php echo date("Y"); ?> || Designed by Yared Alebachewu. All Rights Reserved</p>
        </div>
    </footer>

    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <div id="preloader"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./assets/vendor/aos/aos.js"></script>
    <script src="./assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="./assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
    <script src="./assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
    <script src="./assets/vendor/purecounter/purecounter_vanilla.js"></script>
    <script src="./assets/vendor/waypoints/noframework.waypoints.js"></script>
    <script src="./assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="./assets/js/main.js"></script>
    <script>
        AOS.init({ duration: 1000 });

        // Mobile Nav Toggle
        const mobileNavToggle = document.querySelector('.mobile-nav-toggle');
        const navMenu = document.querySelector('.navmenu ul');
        if (mobileNavToggle && navMenu) {
            mobileNavToggle.addEventListener('click', () => {
                navMenu.classList.toggle('show');
            });
        }

        // Close mobile nav when clicking a link
        document.querySelectorAll('.navmenu ul li a').forEach(link => {
            link.addEventListener('click', () => {
                if (navMenu && navMenu.classList.contains('show')) {
                    navMenu.classList.remove('show');
                }
            });
        });

        // Handle dropdown toggles
        document.querySelectorAll('.toggle-dropdown').forEach(dropdown => {
            dropdown.addEventListener('click', function(e) {
                e.preventDefault();
                const parentLi = this.parentElement;
                const dropdownMenu = parentLi.querySelector('ul');
                if (dropdownMenu) {
                    dropdownMenu.classList.toggle('show');
                }
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown ul').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });

        // Chat functionality
        document.addEventListener('DOMContentLoaded', () => {
            // DOM Elements
            const userList = document.getElementById('userList');
            const chatBox = document.getElementById('chatBox');
            const chatForm = document.getElementById('chatForm');
            const messageInput = document.getElementById('messageInput');
            const sendMessageButton = document.getElementById('sendMessageButton');
            const searchInput = document.getElementById('searchInput');
            const clearSearch = document.getElementById('clearSearch');
            const chatContainer = document.querySelector('.chat-container');
            const selectedUserTitle = document.getElementById('selectedUserTitle');
            const selectedUserSubtitle = document.getElementById('selectedUserSubtitle');
            const selectedUserAvatar = document.getElementById('selectedUserAvatar');
            const backToConversations = document.getElementById('backToConversations');

            // Variables
            let selectedUserId = <?php echo json_encode($selected_user_id); ?> || 0;
            let allUsers = [];
            let currentSearchQuery = '';

            // Resize textarea dynamically
            function resizeTextarea() {
                if (messageInput) {
                    messageInput.style.height = 'auto';
                    messageInput.style.height = `${Math.min(messageInput.scrollHeight, 150)}px`;
                }
            }

            if (messageInput) {
                messageInput.addEventListener('input', resizeTextarea);
            }

            // Escape HTML to prevent XSS
            function escapeHtml(text) {
                if (typeof text !== 'string') return '';
                return text
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            // Escape attributes to prevent injection
            function escapeAttr(text) {
                if (typeof text !== 'string') return '';
                return text
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;')
                    .replace(/\\/g, '\\\\');
            }

            // Send a message
            function sendMessage() {
                const message = messageInput?.value.trim();
                if (!message || !selectedUserId) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please type a message and select a user.',
                        confirmButtonColor: '#1e3a8a'
                    });
                    return;
                }
                fetch('./send_admin_message.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `receiver_id=${selectedUserId}&message=${encodeURIComponent(message)}`
                })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        messageInput.value = '';
                        resizeTextarea();
                        fetchMessages();
                        fetchUsers();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: `Error sending message: ${data.error || 'Unknown error'}`,
                            confirmButtonColor: '#1e3a8a'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: `Error sending message: ${error.message}`,
                        confirmButtonColor: '#1e3a8a'
                    });
                });
            }

            if (sendMessageButton) {
                sendMessageButton.addEventListener('click', sendMessage);
            }

            if (chatForm && messageInput) {
                messageInput.addEventListener('keydown', e => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });
            }

            // Fetch users
            function fetchUsers() {
                fetch('./fetch_users.php')
                    .then(response => {
                        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                        return response.text();
                    })
                    .then(text => {
                        try {
                            const data = JSON.parse(text);
                            allUsers = Array.isArray(data) ? data : [];
                            renderUsers(currentSearchQuery ? allUsers.filter(user =>
                                user.username.toLowerCase().includes(currentSearchQuery) ||
                                user.role.toLowerCase().includes(currentSearchQuery)
                            ) : allUsers);
                        } catch (e) {
                            console.error('Error parsing JSON:', e, 'Response:', text);
                            if (userList) {
                                userList.innerHTML = '<li class="alert alert-danger">Error loading users</li>';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Fetch users error:', error);
                        if (userList) {
                            userList.innerHTML = `<li class="alert alert-danger">Error loading users: ${escapeHtml(error.message)}</li>`;
                        }
                    });
            }

            // Render users
            function renderUsers(users) {
                if (!userList) return;
                userList.innerHTML = '';
                if (!Array.isArray(users) || users.length === 0) {
                    userList.innerHTML = `<li class="alert alert-info">No users found</li>`;
                    return;
                }
                users.forEach(user => {
                    const li = document.createElement('li');
                    const username = typeof user.username === 'string' ? user.username : '';
                    const role = typeof user.role === 'string' ? user.role : '';
                    const initials = username.trim().charAt(0).toUpperCase() || '?';
                    li.dataset.userId = user.id;
                    li.className = Number(selectedUserId) === Number(user.id) ? 'active' : '';
                    li.innerHTML = `<span class="conversation-avatar" aria-hidden="true">${escapeHtml(initials)}</span><span class="conversation-copy"><span class="username">${escapeHtml(username)}</span><span class="conversation-role">${escapeHtml(role)}</span></span>${
                        Number(user.unread_count) > 0 ? `<span class="unread-count">${Number(user.unread_count)}</span>` : ''
                    }`;
                    if (Number(selectedUserId) === Number(user.id)) {
                        updateSelectedUser(username);
                        chatForm.style.display = 'flex';
                    }
                    li.addEventListener('click', () => {
                        selectUser(user.id, username);
                    });
                    userList.appendChild(li);
                });
            }

            function updateSelectedUser(username) {
                const initials = username.trim().charAt(0).toUpperCase() || '?';
                if (selectedUserTitle) selectedUserTitle.textContent = username || 'Messages';
                if (selectedUserSubtitle) selectedUserSubtitle.textContent = 'User conversation';
                if (selectedUserAvatar) {
                    selectedUserAvatar.textContent = initials;
                    selectedUserAvatar.classList.add('has-initial');
                }
            }

            // Filter users
            function filterUsers(query) {
                currentSearchQuery = query.trim().toLowerCase();
                const filteredUsers = allUsers.filter(user =>
                    user.username.toLowerCase().includes(currentSearchQuery) ||
                    user.role.toLowerCase().includes(currentSearchQuery)
                );
                renderUsers(filteredUsers);
                if (clearSearch) {
                    clearSearch.style.display = currentSearchQuery ? 'block' : 'none';
                }
            }

            if (searchInput && clearSearch) {
                searchInput.addEventListener('input', () => filterUsers(searchInput.value));
                clearSearch.addEventListener('click', () => {
                    currentSearchQuery = '';
                    searchInput.value = '';
                    clearSearch.style.display = 'none';
                    renderUsers(allUsers);
                    searchInput.focus();
                });
            }

            // Select a user
            function selectUser(userId, username) {
                selectedUserId = userId;
                document.querySelectorAll('.user-list li').forEach(li =>
                    li.classList.toggle('active', Number(li.dataset.userId) === Number(userId))
                );
                if (username) updateSelectedUser(username);
                chatContainer?.classList.add('conversation-active');
                window.history.pushState({}, '', `admin_messages.php?user_id=${userId}`);
                chatForm.style.display = 'flex';
                fetchMessages();
            }

            backToConversations?.addEventListener('click', () => {
                chatContainer?.classList.remove('conversation-active');
            });

            // Fetch messages
            function fetchMessages() {
                if (!selectedUserId || !chatBox) {
                    chatBox.innerHTML = '<div class="message-container"><div class="chat-empty-state"><span class="chat-empty-icon"><i class="bi bi-chat-square-heart" aria-hidden="true"></i></span><h2>Select a conversation</h2><p>Choose a user from the list to view messages or start a conversation.</p></div></div>';
                    chatForm.style.display = 'none';
                    return;
                }
                fetch(`./fetch_admin_messages.php?user_id=${selectedUserId}`)
                    .then(response => {
                        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                        return response.text();
                    })
                    .then(text => {
                        try {
                            const data = JSON.parse(text);
                            chatBox.innerHTML = '<div class="message-container"></div>';
                            const messageContainer = chatBox.querySelector('.message-container');
                            if (data.error) {
                                messageContainer.innerHTML = `<div class="alert alert-danger">${escapeHtml(data.error)}</div>`;
                                return;
                            }
                            if (!Array.isArray(data) || data.length === 0) {
                                messageContainer.innerHTML = '<div class="chat-empty-state"><span class="chat-empty-icon"><i class="bi bi-chat-square-heart" aria-hidden="true"></i></span><h2>No messages yet</h2><p>Send a message to start this conversation.</p></div>';
                                return;
                            }
                            data.forEach(msg => {
                                const isSent = Number(msg.sender_id) === <?php echo $admin_id; ?>;
                                const div = document.createElement('div');
                                div.className = `message ${isSent ? 'admin user' : 'other'}`;
                                div.dataset.messageId = msg.id;
                                div.innerHTML = `
                                    <div>
                                        ${escapeHtml(msg.message)}
                                        ${msg.is_edited ? '<span class="edited-label">(Edited)</span>' : ''}
                                        ${isSent && msg.seen_by_user ? '<span class="seen-tick">✓✓</span>' : isSent ? '<span class="seen-tick">✓</span>' : ''}
                                    </div>
                                    <div class="message-timestamp">${new Date(msg.created_at).toLocaleString()}</div>
                                    ${isSent ? `
                                        <div class="message-actions">
                                            <button class="edit-btn" data-message-id="${msg.id}" data-message="${escapeAttr(msg.message)}" title="Edit"><i class="bi bi-pencil"></i></button>
                                            <button class="delete-btn" data-message-id="${msg.id}" title="Delete"><i class="bi bi-trash"></i></button>
                                        </div>
                                    ` : ''}
                                `;
                                messageContainer.appendChild(div);
                            });
                            document.querySelectorAll('.edit-btn').forEach(btn => {
                                btn.addEventListener('click', () => {
                                    const messageId = btn.dataset.messageId;
                                    const oldMessage = btn.dataset.message;
                                    const editInput = document.getElementById('editMessageInput');
                                    if (editInput) editInput.value = oldMessage;
                                    const modal = new bootstrap.Modal(document.getElementById('editMessageModal'));
                                    modal.show();
                                    const saveButton = document.getElementById('saveEditButton');
                                    if (saveButton) {
                                        saveButton.onclick = () => {
                                            const newMessage = editInput?.value.trim();
                                            if (newMessage && newMessage !== oldMessage) {
                                                editMessage(messageId, newMessage);
                                            }
                                            modal.hide();
                                        };
                                    }
                                });
                            });
                            document.querySelectorAll('.delete-btn').forEach(btn => {
                                btn.addEventListener('click', () => {
                                    const messageId = btn.dataset.messageId;
                                    const modal = new bootstrap.Modal(document.getElementById('deleteMessageModal'));
                                    modal.show();
                                    const confirmButton = document.getElementById('confirmDeleteButton');
                                    if (confirmButton) {
                                        confirmButton.onclick = () => {
                                            deleteMessage(messageId);
                                            modal.hide();
                                        };
                                    }
                                });
                            });
                            messageContainer.scrollTop = messageContainer.scrollHeight;
                            if (data.some(msg => msg.receiver_id === <?php echo $admin_id; ?> && !msg.seen_by_user)) {
                                fetch('./mark_messages_seen.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: `sender_id=${selectedUserId}`
                                })
                                .then(resp => {
                                    if (resp.ok) {
                                        fetchUsers();
                                        updateNotification();
                                    }
                                })
                                .catch(error => {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: `Error marking messages as seen: ${error.message}`,
                                        confirmButtonColor: '#1e3a8a'
                                    });
                                });
                            }
                        } catch (e) {
                            console.error('Error parsing messages:', e, 'Response:', text);
                            chatBox.innerHTML = '<div class="message-container"><div class="alert alert-danger">Error loading messages</div></div>';
                        }
                    })
                    .catch(error => {
                        chatBox.innerHTML = `<div class="message-container"><div class="alert alert-danger">Error fetching messages: ${escapeHtml(error.message)}</div></div>`;
                    });
            }

            // Update notification bell
            function updateNotification() {
                fetch('./fetch_unread_count.php')
                    .then(response => {
                        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                        return response.json();
                    })
                    .then(data => {
                        const bell = document.querySelector('.notification-bell');
                        if (bell) {
                            bell.innerHTML = `<i class="bi bi-bell"></i>${data.unread_count > 0 ? `<span class="badge">${data.unread_count}</span>` : ''}`;
                        }
                    })
                    .catch(error => {
                        console.error('Notification update error:', error);
                    });
            }

            // Delete a message
            function deleteMessage(messageId) {
                fetch('./delete_message.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `message_id=${messageId}`
                })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        fetchMessages();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: `Error deleting message: ${data.error || 'Unknown error'}`,
                            confirmButtonColor: '#1e3a8a'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: `Error deleting message: ${error.message}`,
                        confirmButtonColor: '#1e3a8a'
                    });
                });
            }

            // Edit a message
            function editMessage(messageId, newMessage) {
                fetch('./edit_message.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `message_id=${messageId}&new_message=${encodeURIComponent(newMessage)}`
                })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        fetchMessages();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: `Error editing message: ${data.error || 'Unknown error'}`,
                            confirmButtonColor: '#1e3a8a'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: `Error editing message: ${error.message}`,
                        confirmButtonColor: '#1e3a8a'
                    });
                });
            }

            // Initial load and periodic updates
            fetchUsers();
            if (selectedUserId) fetchMessages();
            updateNotification();
            setInterval(() => {
                fetchUsers();
                if (selectedUserId) fetchMessages();
                updateNotification();
            }, 5000);

            // Logout Confirmation
            function confirmLogout() {
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Do you want to log out of your account?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3b82f6',
                    cancelButtonColor: '#ef4444',
                    confirmButtonText: 'Yes, Log Out',
                    cancelButtonText: 'Cancel'
                }).then(result => {
                    if (result.isConfirmed) {
                        window.location.href = 'logout.php';
                    }
                });
            }
        });
    </script>
<script src="assets/js/admin-sidebar.js?v=1"></script>
</body>
</html>