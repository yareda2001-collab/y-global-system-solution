<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
include '../connect.php';
$user_id = (int)$_SESSION['user_id'];
// Fetch user info
$sql = "SELECT username, role FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("messages.php: Prepare failed for user info: " . $conn->error);
    die("Database error");
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
    error_log("messages.php: User ID $user_id not found");
    session_destroy();
    header("Location: ../login.php");
    exit;
}
$user_role = $user['role'];
// Fetch unread message count
$unread_sql = "SELECT COUNT(*) as unread FROM messages WHERE receiver_id = ? AND seen_by_user = 0";
$unread_stmt = $conn->prepare($unread_sql);
if (!$unread_stmt) {
    error_log("messages.php: Prepare failed for unread count: " . $conn->error);
    die("Database error");
}
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_count = (int)$unread_stmt->get_result()->fetch_assoc()['unread'];
$stmt->close();
$unread_stmt->close();
$conn->close();
// Ensure selected_user_id is an integer
$selected_user_id = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#033f5a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="manifest" href="../manifest.php?v=6">
    <link rel="apple-touch-icon" href="../assets/img/pwa-192.png">
    <title>Messages - Y-Global System Solution</title>
    <link href="../assets/img/favicon1.png" rel="icon">
    <link href="../assets/img/apple-touch-icon1.png" rel="apple-touch-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="../assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="../assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
    <link href="../assets/css/messages.css?v=4" rel="stylesheet">
    <link href="../assets/css/user-sidebar.css?v=9" rel="stylesheet">
    <link href="../assets/css/user-footer.css?v=3" rel="stylesheet">
    <link href="../assets/css/user-mobile.css?v=4" rel="stylesheet">
    <style>
        html, body { height: 100%; margin: 0; font-family: 'Roboto', sans-serif; }
        body { display: flex; flex-direction: column; }
        .main { flex: 1; }
        .header { background-color: #033f5a; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); }
        .navmenu ul li a { color: white; font-family: 'Poppins', sans-serif; font-weight: 500; transition: color 0.3s ease; }
        .navmenu ul li a:hover { color: #00c4ff; }
        .navmenu ul li a.active { color: #00c4ff; border-bottom: 2px solid #00c4ff; }
        .chat-container { max-width: 1200px; margin: 2rem auto; background: white; border-radius: 15px; padding: 0; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15); display: flex; height: 70vh; }
        .sidebar { width: 30%; border-right: 1px solid #e5e7eb; display: flex; flex-direction: column; background: #f9fafb; position: relative; }
        .search-container { padding: 1rem; border-bottom: 1px solid #e5e7eb; position: relative; }
        .search-input { width: 100%; padding: 0.5rem 2rem 0.5rem 1rem; border-radius: 12px; border: 1px solid #d1d5db; background: #ffffff; font-size: 0.9rem; color: #1f2937; transition: all 0.3s ease; }
        .search-input:focus { border-color: #033f5a; box-shadow: 0 0 8px rgba(3, 63, 90, 0.2); outline: none; }
        .search-input::placeholder { color: #9ca3af; font-style: italic; }
        .clear-search { display: none; position: absolute; right: 2rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: #6b7280; cursor: pointer; font-size: 1.2rem; }
        .clear-search:hover { color: #033f5a; }
        .user-list { list-style: none; padding: 0; margin: 0; overflow-y: auto; flex: 1; max-height: calc(70vh - 60px); scrollbar-width: thin; scrollbar-color: #033f5a #e5e7eb; }
        .user-list::-webkit-scrollbar { width: 8px; }
        .user-list::-webkit-scrollbar-track { background: #e5e7eb; border-radius: 4px; }
        .user-list::-webkit-scrollbar-thumb { background: #033f5a; border-radius: 4px; }
        .user-list::-webkit-scrollbar-thumb:hover { background: #005f8a; }
        .user-list li { padding: 1rem; border-bottom: 1px solid #e5e7eb; cursor: pointer; transition: background 0.2s; }
        .user-list li:hover { background: #f1f5f9; }
        .user-list li.active { background: #e6f3ff; }
        .user-list .username { font-weight: 500; color: #1f2937; }
        .user-list .unread-count { background: #dc3545; color: white; border-radius: 50%; padding: 2px 8px; font-size: 0.75rem; margin-left: 10px; }
        .chat-area { width: 70%; padding: 2rem; display: flex; flex-direction: column; position: relative; }
        .chat-box { flex: 1; overflow: hidden; border: 1px solid #e5e7eb; border-radius: 12px; background: #f9fafb; margin-bottom: 1rem; }
        .message-container { max-height: 100%; overflow-y: auto; padding: 1rem; }
        .message { margin-bottom: 1rem; padding: 0.75rem 1rem; border-radius: 12px; max-width: 70%; word-wrap: break-word; position: relative; transition: background 0.2s; }
        .message.user { background: #033f5a; color: white; margin-left: auto; text-align: left; border-bottom-right-radius: 2px; }
        .message.other { background: #e5e7eb; color: #1f2937; margin-right: auto; text-align: left; border-bottom-left-radius: 2px; }
        .message.error { background: #f8d7da; color: #721c24; }
        .message:hover { filter: brightness(95%); }
        .message-timestamp { font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem; opacity: 0.8; }
        .message-actions { display: none; position: absolute; top: 5px; right: 5px; }
        .message.user:hover .message-actions { display: flex; gap: 5px; }
        .message-actions button { background: rgba(0, 0, 0, 0.1); border: none; color: #ffffff; cursor: pointer; padding: 4px 8px; border-radius: 4px; transition: background 0.2s; }
        .message-actions button:hover { background: rgba(0, 0, 0, 0.3); }
        .seen-tick { font-size: 0.75rem; color: #00c4ff; margin-left: 5px; }
        .edited-label { font-size: 0.75rem; color: #6b7280; margin-left: 5px; font-style: italic; }
        .notification-bell { position: relative; display: inline-block; margin-left: 10px; }
        .notification-bell .badge { position: absolute; top: -10px; right: -10px; background: #dc3545; color: white; border-radius: 50%; padding: 5px 8px; font-size: 0.75rem; }
        .footer { background-color: #033f5a; }
        .footer a { color: #00c4ff; transition: color 0.3s ease; }
        .footer a:hover { color: #ffffff; }
        .loading-overlay { display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.8); justify-content: center; align-items: center; z-index: 10; }
        .loading-spinner { border: 4px solid #f3f3f3; border-top: 4px solid #033f5a; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .logout-btn {
            background-color: #dc3545;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
            text-align: center;
        }
        .logout-btn:hover {
            background-color: #b02a37;
        }
        .mobile-nav .logout-btn {
            margin: 1rem;
            padding: 10px 20px;
        }
        .mobile-nav-toggle { color: white; font-size: 1.5rem; cursor: pointer; }
        .mobile-nav-active .navmenu {
            display: block;
            position: fixed;
            top: 0;
            right: 0;
            width: 250px;
            height: 100%;
            background: #033f5a;
            padding: 2rem 1rem;
            box-shadow: -2px 0 5px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            overflow-y: auto;
        }
        .mobile-nav-active .navmenu ul {
            flex-direction: column;
            gap: 1rem;
        }
        .mobile-nav-active .navmenu ul li a {
            color: white;
            font-size: 1rem;
            padding: 0.5rem;
            display: block;
        }
        @media (min-width: 1200px) {
            .mobile-nav-toggle { display: none; }
            .navmenu { display: block !important; }
        }
        @media (max-width: 1199px) {
            .navmenu { display: none; }
            .mobile-nav-toggle { display: block; }
        }
        @media (max-width: 576px) {
            .chat-container {
                flex-direction: row;
                height: auto;
                min-height: 70vh;
                margin: 1rem;
            }
            .sidebar {
                width: 40%;
                max-height: 70vh;
                overflow-y: auto;
                border-right: 1px solid #e5e7eb;
            }
            .chat-area {
                width: 60%;
                padding: 1rem;
                display: flex;
                flex-direction: column;
            }
            .search-container {
                padding: 0.5rem;
            }
            .search-input {
                font-size: 0.8rem;
                padding: 0.4rem 1.5rem 0.4rem 0.8rem;
            }
            .clear-search {
                right: 1.5rem;
                font-size: 1rem;
            }
            .user-list {
                max-height: calc(70vh - 50px);
                scrollbar-width: thin;
                scrollbar-color: #033f5a #e5e7eb;
            }
            .user-list::-webkit-scrollbar { width: 6px; }
            .user-list::-webkit-scrollbar-track { background: #e5e7eb; }
            .user-list::-webkit-scrollbar-thumb { background: #033f5a; }
            .user-list::-webkit-scrollbar-thumb:hover { background: #005f8a; }
            .user-list li {
                padding: 0.5rem;
                font-size: 0.85rem;
            }
            .user-list .username {
                font-size: 0.85rem;
            }
            .user-list .unread-count {
                font-size: 0.65rem;
                padding: 1px 6px;
            }
            .chat-box {
                margin-bottom: 0.5rem;
                max-height: 50vh;
            }
            .message-container {
                max-height: 100%;
                padding: 0.5rem;
            }
            .message {
                font-size: 0.85rem;
                padding: 0.5rem 0.75rem;
                max-width: 80%;
            }
            .message-timestamp {
                font-size: 0.65rem;
            }
            .message-actions button {
                padding: 3px 6px;
                font-size: 0.7rem;
            }
            .navmenu ul {
                flex-direction: column;
                gap: 0.5rem;
            }
            .navmenu ul li a {
                color: white;
                font-size: 0.85rem;
                padding: 0.5rem;
            }
            .notification-bell .badge {
                font-size: 0.65rem;
                padding: 3px 6px;
            }
            .modal-content {
                margin: 0.5rem;
            }
            .modal-body textarea {
                font-size: 0.85rem;
                min-height: 80px;
            }
            .btn-custom {
                padding: 0.4rem 1rem;
                font-size: 0.8rem;
            }
        }
        .modal-content {
            border-radius: 10px;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        .modal-header {
            background-color: #033f5a;
            color: white;
            border-bottom: none;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .modal-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
        }
        .modal-body {
            padding: 1.5rem;
            font-size: 1rem;
        }
        .modal-body textarea {
            resize: none;
            min-height: 100px;
        }
        .modal-footer {
            border-top: none;
            justify-content: center;
            padding: 1rem;
        }
        .btn-custom {
            padding: 0.5rem 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .btn-custom-primary {
            background-color: #00c4ff;
            color: white;
            border: none;
        }
        .btn-custom-primary:hover {
            background-color: #0099cc;
            color: white;
        }
        .btn-custom-secondary {
            background-color: #6b7280;
            color: white;
            border: none;
        }
        .btn-custom-secondary:hover {
            background-color: #4b5563;
            color: white;
        }

        /* Updated Chat Form Styles */
        .chat-form {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .chat-form textarea {
            flex: 1;
            border: 1px solid #d1d5db;
            border-radius: 14px;
            resize: none;
            padding: 0.85rem 1.1rem;
            min-height: 52px;
            max-height: 160px;
            font-size: 0.95rem;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            font-family: 'Roboto', sans-serif;
            color: #1f2937;
            width: 100%;
            line-height: 1.5;
        }

        .chat-form textarea:focus {
            border-color: #033f5a;
            box-shadow: 0 0 12px rgba(3, 63, 90, 0.3);
            outline: none;
            transform: translateY(-1px);
        }

        .chat-form textarea::placeholder {
            color: #9ca3af;
            font-style: italic;
        }

        .send-button-container {
            display: flex;
            justify-content: center;
        }

        .chat-form .btn-send {
            background: linear-gradient(135deg, #033f5a, #005f8a);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.4s ease;
            box-shadow: 0 4px 15px rgba(3, 63, 90, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 120px;
            justify-content: center;
        }

        .chat-form .btn-send:hover {
            background: linear-gradient(135deg, #005f8a, #033f5a);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(3, 63, 90, 0.4);
        }

        .chat-form .btn-send:active {
            transform: translateY(0);
        }

        .chat-form .btn-send i {
            font-size: 1.1rem;
        }

        /* Mobile adjustments for send button */
        @media (max-width: 576px) {
            .chat-form {
                gap: 0.5rem;
            }
            .chat-form textarea {
                min-height: 44px;
                max-height: 120px;
                font-size: 0.9rem;
                padding: 0.65rem 0.9rem;
            }
            .chat-form .btn-send {
                width: 100%;
                padding: 0.75rem;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body class="messages-page mobile-app-page user-portal-page">
    <header id="header" class="header d-flex align-items-center sticky-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center">
            <a href="dashboard.php" class="logo d-flex align-items-center me-auto">
                <span class="user-header-mark"><i class="bi bi-globe2" aria-hidden="true"></i></span>
                <span class="user-header-brand"><strong>Y-Global</strong><small>System Solution</small></span>
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="update_password.php">Update Password</a></li>
                    <li><a href="update_profile_picture.php">Update Profile Picture</a></li>
                    <li><a href="settings.php">Settings</a></li>
                    <li><a href="messages.php">Messages</a></li>
                    <li><button class="logout-btn" onclick="confirmLogout()">Log Out</button></li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
        </div>
    </header>
    <?php include __DIR__ . '/../includes/user-sidebar.php'; ?>
    <main class="main">
        <section class="chat-section">
            <div class="container chat-container<?php echo $selected_user_id > 0 ? ' conversation-active' : ''; ?>" data-aos="fade-up">
                <div class="sidebar">
                    <div class="sidebar-heading">
                        <span class="sidebar-heading-icon"><i class="bi bi-chat-dots" aria-hidden="true"></i></span>
                        <div>
                            <h2>Conversations</h2>
                            <p>Choose someone to message</p>
                        </div>
                        <span class="unread-summary"><?php echo $unread_count; ?> unread</span>
                    </div>
                    <div class="search-container">
                        <i class="bi bi-search search-icon" aria-hidden="true"></i>
                        <input type="text" class="search-input" id="searchInput" placeholder="Search conversations..." aria-label="Search conversations">
                        <button class="clear-search" id="clearSearch"><i class="bi bi-x"></i></button>
                    </div>
                    <div class="loading-overlay" id="userLoading">
                        <div class="loading-spinner"></div>
                    </div>
                    <ul class="user-list" id="userList"></ul>
                </div>
                <div class="chat-area">
                    <div class="chat-heading">
                        <button type="button" class="back-to-conversations" id="backToConversations" aria-label="Back to conversations">
                            <i class="bi bi-arrow-left" aria-hidden="true"></i>
                        </button>
                        <span class="chat-heading-icon" id="selectedUserAvatar" aria-hidden="true"><i class="bi bi-chat-square-text"></i></span>
                        <div>
                            <h1 id="selectedUserTitle">Messages</h1>
                            <p id="selectedUserSubtitle">Your conversations, all in one place</p>
                        </div>
                    </div>
                    <div class="loading-overlay" id="chatLoading">
                        <div class="loading-spinner"></div>
                    </div>
                    <div class="chat-box" id="chatBox">
                        <div class="message-container">
                            <div class="chat-empty-state">
                                <span class="chat-empty-icon"><i class="bi bi-chat-square-heart" aria-hidden="true"></i></span>
                                <h2>Your messages, together</h2>
                                <p>Select a conversation to view messages or start a new one.</p>
                            </div>
                        </div>
                    </div>
                    <form class="chat-form" id="chatForm" style="display: none;">
                        <textarea class="form-control" id="messageInput" placeholder="Type your message..." required></textarea>
                        <div class="send-button-container">
                            <button type="button" class="btn btn-send" id="sendMessageButton">
                                <i class="bi bi-send"></i> Send
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>
    <?php include __DIR__ . '/../includes/user-mobile-nav.php'; ?>
    <footer id="footer" class="footer dark-background">
        <div class="container footer-top">
            <div class="row gy-4">
                <div class="col-lg-4 col-md-6 footer-about">
                    <a href="dashboard.php" class="logo d-flex align-items-center">
                        <span class="sitename">Y-Global System Solution</span>
                    </a>
                    <div class="footer-contact pt-3">
                        <p>Ethiopia, Addis Ababa</p>
                        <p class="mt-3"><strong>Phone:</strong> <span>+251 926-862-255</span></p>
                        <p><strong>Email:</strong> <span>yared1357@gmail.com</span></p>
                    </div>
                    <div class="social-links d-flex mt-4">
                        <a href="https://www.facebook.com/profile.php?id=100057756135221"><i class="bi bi-facebook"></i></a>
                        <a href="mailto:yared1357@gmail.com"><i class="bi bi-envelope"></i></a>
                        <a href="https://www.linkedin.com/in/yared-alebachewu-84b20b252/"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 footer-links">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="update_password.php">Update Password</a></li>
                        <li><a href="update_profile_picture.php">Update Profile Picture</a></li>
                        <li><a href="settings.php">Settings</a></li>
                        <li><a href="messages.php">Messages</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-3 footer-links">
                    <h4>Our Services</h4>
                    <ul>
                        <li>Website Designing</li>
                        <li>Graphics Designing</li>
                        <li>Video Editing</li>
                        <li>Logo Designing</li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-12 footer-newsletter">
                    <h4>From Developer</h4>
                    <p>I am always ready to work with you and to satisfy your needs. We are very dedicated for our customers orders and we work hard to create everything as our customers need.</p>
                    <p>Working with us always makes happier and very satisfied and makes your organization and profession very competitive internationally.</p>
                </div>
            </div>
        </div>
        <div class="container copyright text-center mt-4">
            <p><span>Copyright© <?php echo date("Y"); ?></span> <strong class="px-1 sitename">|| Designed by Yared Alebachewu</strong> <span>All Rights Reserved</span></p>
        </div>
    </footer>
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <div id="preloader"></div>
    <div class="modal fade" id="customModal" tabindex="-1" aria-labelledby="customModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-custom btn-custom-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-custom btn-custom-primary" id="modalConfirmBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/vendor/aos/aos.js"></script>
    <script src="../assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="../assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
    <script src="../assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
    <script src="../assets/vendor/purecounter/purecounter_vanilla.js"></script>
    <script src="../assets/vendor/waypoints/noframework.waypoints.js"></script>
    <script src="../assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/user-sidebar.js?v=3"></script>
    <script src="../assets/js/pwa-install.js?v=2"></script>
    <script>
        // Initialize AOS
        AOS.init({ duration: 1000 });
        // Define confirmLogout globally
        function confirmLogout() {
            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you want to log out of your account?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Yes, Log Out',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusCancel: true,
                customClass: {
                    popup: 'animate-fadeIn',
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = './logout.php';
                }
            });
        }
        // Debug script loading
        console.log('Script block loaded');
        document.addEventListener('DOMContentLoaded', () => {
            // DOM Elements
            const userList = document.getElementById('userList');
            const chatContainer = document.querySelector('.chat-container');
            const selectedUserTitle = document.getElementById('selectedUserTitle');
            const selectedUserSubtitle = document.getElementById('selectedUserSubtitle');
            const selectedUserAvatar = document.getElementById('selectedUserAvatar');
            const backToConversations = document.getElementById('backToConversations');
            const chatBox = document.getElementById('chatBox');
            const chatForm = document.getElementById('chatForm');
            const messageInput = document.getElementById('messageInput');
            const sendMessageButton = document.getElementById('sendMessageButton');
            const searchInput = document.getElementById('searchInput');
            const clearSearch = document.getElementById('clearSearch');
            const userLoading = document.getElementById('userLoading');
            const chatLoading = document.getElementById('chatLoading');
            const customModal = new bootstrap.Modal(document.getElementById('customModal'));
            const modalTitle = document.getElementById('customModalLabel');
            const modalBody = document.getElementById('modalBody');
            const modalConfirmBtn = document.getElementById('modalConfirmBtn');
            // Variables
            let selectedUserId = <?php echo json_encode($selected_user_id, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_NUMERIC_CHECK); ?>;
            let lastUnreadCount = <?php echo json_encode($unread_count, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_NUMERIC_CHECK); ?>;
            let allUsers = [];
            let currentSearchQuery = '';
            let currentAction = null;
            let currentMessageId = null;
            let currentMessage = null;
            let currentMessages = [];
            let fetchUsersRetryCount = 0;
            let fetchMessagesRetryCount = 0;
            const maxRetries = 3;
            // Debug variable values
            console.log('selectedUserId:', selectedUserId, 'typeof:', typeof selectedUserId);
            console.log('lastUnreadCount:', lastUnreadCount, 'typeof:', typeof lastUnreadCount);
            // Resize textarea dynamically
            function resizeTextarea() {
                if (messageInput) {
                    messageInput.style.height = 'auto';
                    messageInput.style.height = `${Math.min(messageInput.scrollHeight, 160)}px`;
                }
            }
            if (messageInput) {
                messageInput.addEventListener('input', resizeTextarea);
                resizeTextarea();
            }
            // Escape HTML to prevent XSS
            function escapeHtml(text) {
                if (typeof text !== 'string') return '';
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;',
                    '\n': ' ',
                    '\r': ''
                };
                return text.replace(/[&<>"'\n\r]/g, char => map[char]);
            }
            // Escape attributes
            function escapeAttr(text) {
                if (typeof text !== 'string') return '';
                return text
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;')
                    .replace(/\\/g, '\\\\')
                    .replace(/\n/g, ' ')
                    .replace(/\r/g, '');
            }
            // Compare arrays
            function arraysEqual(arr1, arr2) {
                if (arr1.length !== arr2.length) return false;
                return arr1.every((item, index) => JSON.stringify(item) === JSON.stringify(arr2[index]));
            }
            // Show error
            function showError(message) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger alert-dismissible fade show';
                errorDiv.role = 'alert';
                errorDiv.innerHTML = `${escapeHtml(message)}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
                chatBox.querySelector('.message-container').prepend(errorDiv);
                errorDiv.scrollIntoView({ behavior: 'smooth' });
            }
            // Send message
            function sendMessage() {
                const message = messageInput ? messageInput.value.trim() : '';
                if (!message || !selectedUserId) {
                    showError('Please type a message and select a user.');
                    return;
                }
                chatLoading.style.display = 'flex';
                fetch('./send_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `receiver_id=${selectedUserId}&message=${encodeURIComponent(message)}`
                })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.text();
                })
                .then(text => {
                    console.log('send_message.php response:', text);
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            messageInput.value = '';
                            resizeTextarea();
                            fetchMessages();
                            fetchUsers();
                        } else {
                            showError(`Error sending message: ${data.error || 'Unknown error'}`);
                        }
                    } catch (e) {
                        console.error('Error parsing send_message response:', e.message, 'Response:', text);
                        showError('Error parsing server response');
                    }
                })
                .catch(error => {
                    console.error('Fetch error in sendMessage:', error.message);
                    showError(`Error sending message: ${error.message}`);
                })
                .finally(() => chatLoading.style.display = 'none');
            }
            // Bind Send button
            if (sendMessageButton) {
                sendMessageButton.addEventListener('click', () => {
                    console.log('Send button clicked, message:', messageInput.value, 'userId:', selectedUserId);
                    sendMessage();
                });
            }
            // Bind Enter key
            if (chatForm && messageInput) {
                messageInput.addEventListener('keydown', e => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        console.log('Enter key pressed, message:', messageInput.value);
                        sendMessage();
                    }
                });
            }
            // Fetch users
            function fetchUsers() {
                userLoading.style.display = 'flex';
                fetch('./fetch_users.php', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}, URL: ./fetch_users.php`);
                    }
                    return response.text();
                })
                .then(text => {
                    console.log('fetch_users.php response:', text);
                    if (!text.trim() || text.trim().startsWith('<')) {
                        if (fetchUsersRetryCount < maxRetries) {
                            fetchUsersRetryCount++;
                            console.log(`Retrying fetchUsers (attempt ${fetchUsersRetryCount}/${maxRetries})`);
                            setTimeout(fetchUsers, 1000);
                            return;
                        } else {
                            throw new Error('Received invalid response (likely HTML) after max retries');
                        }
                    }
                    fetchUsersRetryCount = 0;
                    try {
                        const data = JSON.parse(text);
                        if (data.error) {
                            userList.innerHTML = `<li class="alert alert-danger">${escapeHtml(data.error)}</li>`;
                            allUsers = [];
                            return;
                        }
                        const newUsers = Array.isArray(data) ? data : [];
                        if (newUsers.length === 0) {
                            userList.innerHTML = `<li class="alert alert-info">No other users available</li>`;
                            allUsers = [];
                            return;
                        }
                        if (!arraysEqual(newUsers, allUsers)) {
                            allUsers = newUsers;
                            renderUsers(currentSearchQuery ? allUsers.filter(user =>
                                user.username.toLowerCase().includes(currentSearchQuery) ||
                                user.role.toLowerCase().includes(currentSearchQuery)
                            ) : allUsers);
                        }
                    } catch (e) {
                        console.error('Parsing error in fetchUsers:', e.message, 'Response:', text);
                        userList.innerHTML = `<li class="alert alert-danger">Error parsing user data: ${escapeHtml(e.message)}</li>`;
                        allUsers = [];
                    }
                })
                .catch(error => {
                    console.error('Fetch error in fetchUsers:', error.message);
                    userList.innerHTML = `<li class="alert alert-danger">Error loading users: ${escapeHtml(error.message)}</li>`;
                    allUsers = [];
                })
                .finally(() => userLoading.style.display = 'none');
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
                    const initials = username.trim().charAt(0).toUpperCase() || '?';
                    li.dataset.userId = user.id;
                    li.className = parseInt(selectedUserId, 10) === parseInt(user.id, 10) ? 'active' : '';
                    li.innerHTML = `<span class="conversation-avatar" aria-hidden="true">${escapeHtml(initials)}</span><span class="conversation-copy"><span class="username">${escapeHtml(username)}</span><span class="conversation-role">${escapeHtml(user.role)}</span></span>${
                        user.unread_count > 0 ? `<span class="unread-count">${user.unread_count}</span>` : ''
                    }`;
                    if (parseInt(selectedUserId, 10) === parseInt(user.id, 10) && selectedUserTitle) {
                        selectedUserTitle.textContent = username;
                        if (selectedUserSubtitle) selectedUserSubtitle.textContent = 'Conversation';
                        if (selectedUserAvatar) {
                            selectedUserAvatar.textContent = initials;
                            selectedUserAvatar.classList.add('has-initial');
                        }
                    }
                    li.addEventListener('click', () => selectUser(user.id, username));
                    userList.appendChild(li);
                });
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
            // Select user - FIXED
            function selectUser(userId, username) {
                selectedUserId = userId;
                if (selectedUserTitle) {
                    selectedUserTitle.textContent = username || 'Messages';
                }
                if (selectedUserSubtitle) {
                    selectedUserSubtitle.textContent = username ? 'Conversation' : 'Your conversations, all in one place';
                }
                if (selectedUserAvatar) {
                    selectedUserAvatar.textContent = username ? username.trim().charAt(0).toUpperCase() : '';
                    selectedUserAvatar.classList.toggle('has-initial', Boolean(username));
                    if (!username) selectedUserAvatar.innerHTML = '<i class="bi bi-chat-square-text" aria-hidden="true"></i>';
                }
                if (chatContainer) {
                    chatContainer.classList.add('conversation-active');
                }
                document.querySelectorAll('.user-list li').forEach(li =>
                    li.classList.toggle('active', parseInt(li.dataset.userId, 10) === parseInt(userId, 10))
                );
                window.history.pushState({}, '', `messages.php?receiver_id=${userId}`);
                if (chatForm) {
                    chatForm.style.display = 'flex';
                }
                fetchMessages();
                if (messageInput) {
                    resizeTextarea();
                    messageInput.focus();
                }
            }
            if (backToConversations) {
                backToConversations.addEventListener('click', () => {
                    if (chatContainer) {
                        chatContainer.classList.remove('conversation-active');
                    }
                    searchInput?.focus();
                });
            }
            // Fetch messages
            function fetchMessages() {
                if (!selectedUserId) {
                    chatBox.innerHTML = '<div class="message-container"><div class="chat-empty-state"><span class="chat-empty-icon"><i class="bi bi-chat-square-heart" aria-hidden="true"></i></span><h2>Your messages, together</h2><p>Select a conversation to view messages or start a new one.</p></div></div>';
                    if (chatForm) chatForm.style.display = 'none';
                    return;
                }
                chatLoading.style.display = 'flex';
                fetch(`./fetch_messages.php?receiver_id=${selectedUserId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.text();
                })
                .then(text => {
                    console.log('fetch_messages.php response:', text);
                    if (!text.trim() || text.trim().startsWith('<')) {
                        if (fetchMessagesRetryCount < maxRetries) {
                            fetchMessagesRetryCount++;
                            console.log(`Retrying fetchMessages (attempt ${fetchMessagesRetryCount}/${maxRetries})`);
                            setTimeout(fetchMessages, 1000);
                            return;
                        } else {
                            throw new Error('Received invalid response (likely HTML) after max retries');
                        }
                    }
                    fetchMessagesRetryCount = 0;
                    try {
                        const data = JSON.parse(text);
                        if (data.error) {
                            chatBox.innerHTML = `<div class="message-container"><div class="alert alert-danger">${escapeHtml(data.error)}</div></div>`;
                            currentMessages = [];
                            return;
                        }
                        if (data.length === 0) {
                            chatBox.innerHTML = '<div class="message-container"><div class="chat-empty-state"><span class="chat-empty-icon"><i class="bi bi-chat-square-dots" aria-hidden="true"></i></span><h2>No messages yet</h2><p>Send a message to start the conversation.</p></div></div>';
                            currentMessages = [];
                            return;
                        }
                        if (!arraysEqual(data, currentMessages)) {
                            currentMessages = data;
                            chatBox.innerHTML = '<div class="message-container"></div>';
                            const messageContainer = chatBox.querySelector('.message-container');
                            data.forEach(msg => {
                                const isSent = msg.sender_id === <?php echo $user_id; ?>;
                                const div = document.createElement('div');
                                div.className = `message ${isSent ? 'user' : 'other'}`;
                                div.dataset.messageId = msg.id;
                                div.innerHTML = `<div>${escapeHtml(msg.message)}${msg.is_edited ? '<span class="edited-label">(Edited)</span>' : ''}${
                                    isSent && msg.seen_by_user ? '<span class="seen-tick">✓✓</span>' : isSent ? '<span class="seen-tick">✓</span>' : ''
                                }</div><div class="message-timestamp">${new Date(msg.created_at).toLocaleString()}</div>${
                                    isSent ? `<div class="message-actions"><button class="edit-btn" data-message-id="${msg.id}" data-message="${escapeAttr(msg.message)}" title="Edit"><i class="bi bi-pencil"></i></button><button class="delete-btn" data-message-id="${msg.id}" title="Delete"><i class="bi bi-trash"></i></button></div>` : ''
                                }`;
                                messageContainer.appendChild(div);
                            });
                            messageContainer.scrollTop = messageContainer.scrollHeight;
                        }
                        if (data.some(msg => msg.receiver_id === <?php echo $user_id; ?> && !msg.seen_by_user)) {
                            fetch('./mark_messages_seen.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: `sender_id=${selectedUserId}`
                            })
                            .then(resp => {
                                if (resp.ok) {
                                    fetchUsers();
                                    updateNotification();
                                }
                            })
                            .catch(error => showError(`Error marking messages as seen: ${error.message}`));
                        }
                    } catch (e) {
                        console.error('Parsing error in fetchMessages:', e.message, 'Response:', text);
                        chatBox.innerHTML = `<div class="message-container"><div class="alert alert-danger">Error parsing messages: ${escapeHtml(e.message)}</div></div>`;
                        currentMessages = [];
                    }
                })
                .catch(error => {
                    console.error('Fetch error in fetchMessages:', error.message);
                    chatBox.innerHTML = `<div class="message-container"><div class="alert alert-danger">Error fetching messages: ${escapeHtml(error.message)}</div></div>`;
                    currentMessages = [];
                })
                .finally(() => chatLoading.style.display = 'none');
            }
            // Update notification
            function updateNotification() {
                fetch('./fetch_unread_count.php', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.text();
                })
                .then(text => {
                    console.log('fetch_unread_count.php response:', text);
                    try {
                        const data = JSON.parse(text);
                        const bell = document.getElementById('notificationBell');
                        if (bell && data.unread_count !== lastUnreadCount) {
                            bell.innerHTML = `<i class="bi bi-bell"></i>${data.unread_count > 0 ? `<span class="badge">${data.unread_count}</span>` : ''}`;
                            lastUnreadCount = data.unread_count;
                        }
                    } catch (e) {
                        console.error('Error parsing unread count:', e.message, 'Response:', text);
                    }
                })
                .catch(error => showError(`Error updating notification: ${error.message}`));
            }
            // Show delete modal
            function showDeleteModal(messageId) {
                currentAction = 'delete';
                currentMessageId = messageId;
                modalTitle.textContent = 'Confirm Delete';
                modalBody.innerHTML = 'Are you sure you want to delete this message? This action cannot be undone.';
                modalConfirmBtn.textContent = 'Delete';
                customModal.show();
                modalConfirmBtn.focus();
            }
            // Show edit modal
            function showEditModal(messageId, oldMessage) {
                currentAction = 'edit';
                currentMessageId = messageId;
                currentMessage = oldMessage;
                modalTitle.textContent = 'Edit Message';
                modalBody.innerHTML = `<textarea class="form-control" id="editMessageInput" rows="3">${escapeHtml(oldMessage)}</textarea>`;
                modalConfirmBtn.textContent = 'Save';
                customModal.show();
                document.getElementById('editMessageInput').focus();
            }
            // Modal confirm action
            modalConfirmBtn.addEventListener('click', () => {
                if (currentAction === 'delete') {
                    deleteMessage(currentMessageId);
                } else if (currentAction === 'edit') {
                    const newMessage = document.getElementById('editMessageInput').value.trim();
                    if (newMessage && newMessage !== currentMessage) {
                        editMessage(currentMessageId, newMessage);
                    }
                }
                customModal.hide();
            });
            // Delete message
            function deleteMessage(messageId) {
                chatLoading.style.display = 'flex';
                fetch('./delete_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `message_id=${messageId}`
                })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.text();
                })
                .then(text => {
                    console.log('delete_message.php response:', text);
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            fetchMessages();
                        } else {
                            showError(`Error deleting message: ${data.error || 'Unknown error'}`);
                        }
                    } catch (e) {
                        console.error('Error parsing delete_message response:', e.message, 'Response:', text);
                        showError('Error parsing server response');
                    }
                })
                .catch(error => showError(`Error deleting message: ${error.message}`))
                .finally(() => chatLoading.style.display = 'none');
            }
            // Edit message
            function editMessage(messageId, newMessage) {
                chatLoading.style.display = 'flex';
                fetch('./edit_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `message_id=${messageId}&new_message=${encodeURIComponent(newMessage)}`
                })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.text();
                })
                .then(text => {
                    console.log('edit_message.php response:', text);
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            fetchMessages();
                        } else {
                            showError(`Error editing message: ${data.error || 'Unknown error'}`);
                        }
                    } catch (e) {
                        console.error('Error parsing edit_message response:', e.message, 'Response:', text);
                        showError('Error parsing server response');
                    }
                })
                .catch(error => showError(`Error editing message: ${error.message}`))
                .finally(() => chatLoading.style.display = 'none');
            }
            // Handle edit/delete clicks
            chatBox.addEventListener('click', e => {
                if (e.target.closest('.edit-btn')) {
                    const btn = e.target.closest('.edit-btn');
                    const messageId = btn.dataset.messageId;
                    const oldMessage = btn.dataset.message;
                    showEditModal(messageId, oldMessage);
                }
                if (e.target.closest('.delete-btn')) {
                    const btn = e.target.closest('.delete-btn');
                    const messageId = btn.dataset.messageId;
                    showDeleteModal(messageId);
                }
            });
            // Initial load and periodic updates
            try {
                fetchUsers();
                updateNotification();
                if (selectedUserId) {
                    fetchMessages();
                    if (chatForm) {
                        chatForm.style.display = 'flex';
                    }
                    if (messageInput) {
                        resizeTextarea();
                        messageInput.focus();
                    }
                }
                setInterval(() => {
                    fetchUsers();
                    if (selectedUserId) fetchMessages();
                    updateNotification();
                }, 6000);
            } catch (e) {
                console.error('Error during initial load:', e.message);
                showError('Initialization error: ' + e.message);
            }
        });
    </script>
</body>
</html>