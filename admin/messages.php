<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Database connection
include 'connect.php';

// Check if connection is valid
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch logged-in user ID for unread messages
$logged_in_user_id = intval($_SESSION['user_id']);

// Count unread messages for notification bell
$unread_messages_sql = "SELECT COUNT(*) as total FROM messages WHERE receiver_id = ? AND is_seen = 0 AND seen_by_user = 0";
$unread_messages_stmt = $conn->prepare($unread_messages_sql);
$unread_messages_stmt->bind_param("i", $logged_in_user_id);
$unread_messages_stmt->execute();
$unread_messages_result = $unread_messages_stmt->get_result();
$unread_messages = $unread_messages_result->fetch_assoc()['total'];
$unread_messages_stmt->close();

// Count pending reset requests for notification bell
$pending_requests_sql = "SELECT COUNT(*) as pending_count FROM password_reset_requests WHERE status = 'pending'";
$pending_requests_result = $conn->query($pending_requests_sql);
$pending_count = $pending_requests_result->num_rows > 0 ? $pending_requests_result->fetch_assoc()['pending_count'] : 0;

// Handle delete operation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    $delete_sql = "DELETE FROM contact WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    if ($stmt === false) {
        echo "<p class='text-danger text-center'>Prepare failed: " . $conn->error . "</p>";
    } else {
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            echo "<p class='text-success text-center'>Message with ID $delete_id deleted successfully.</p>";
        } else {
            echo "<p class='text-danger text-center'>Error deleting message: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
}

// Fetch all messages
$sql = "SELECT id, name, email, subject, message, created_at FROM contact";
$result = $conn->query($sql);
if ($result === false) {
    echo "<p class='text-danger text-center'>Query failed: " . $conn->error . "</p>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Admin Dashboard - View Messages</title>
    <meta name="description" content="Admin dashboard for managing contact messages">
    <meta name="keywords" content="admin, dashboard, messages">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link href="assets/img/favicon1.png" rel="icon">
    <link href="assets/img/apple-touch-icon1.png" rel="apple-touch-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;300;400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
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
        }
        html {
            height: 100%;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f4f6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
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
            padding: 0.5rem 1rem;
            flex-wrap: wrap;
        }
        .logo h3 {
            color: white;
            margin: 0;
            font-size: 1.5rem;
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
            margin: 0 0.3rem;
        }
        .navmenu ul li a {
            color: white;
            font-weight: 500;
            font-size: 0.9rem;
            padding: 0.5rem 0.6rem;
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
            min-width: 150px;
        }
        .dropdown ul li a {
            color: white;
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
        }
        .dropdown ul li a:hover {
            background-color: #3b82f6;
        }
        .btn-getstarted {
            background: var(--danger-red);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.3s ease;
            white-space: nowrap;
        }
        .btn-getstarted:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        .mobile-nav-toggle {
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            display: none;
        }
        .main {
            flex: 1;
            padding: 80px 20px;
        }
        .footer {
            margin-top: auto;
            background-color: var(--dark-blue);
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
            color: white;
            border-radius: 50%;
            padding: 4px 7px;
            font-size: 0.7rem;
        }
        .container1 {
            max-width: 1200px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            padding-top: 10px;
        }
        h2 {
            color: var(--dark-blue);
            margin-bottom: 20px;
            font-weight: 700;
        }
        .search-container {
            margin-bottom: 20px;
            position: relative;
            max-width: 400px;
        }
        .search-container input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.3s;
        }
        .search-container input:focus {
            border-color: var(--primary-blue);
        }
        .search-container .bi-search {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 20px 0;
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        th {
            background-color: var(--dark-blue);
            color: white;
            font-weight: 500;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        tr:hover {
            background-color: #e9ecef;
            transition: background-color 0.3s;
        }
        .message-container {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            font-size: 14px;
            line-height: 1.5;
            transition: background-color 0.3s;
            max-height: 100px;
            overflow-y: auto;
        }
        .message-container:hover {
            background: #e9ecef;
        }
        .message-container.full {
            max-height: 200px;
        }
        .short-message {
            display: block;
        }
        .full-message {
            display: none;
        }
        .delete-btn, .toggle-btn {
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s, transform 0.2s;
            white-space: nowrap;
        }
        .delete-btn {
            background-color: var(--danger-red);
            color: white;
        }
        .delete-btn:hover {
            background-color: #dc2626;
            transform: translateY(-1px);
        }
        .toggle-btn {
            background-color: var(--primary-blue);
            color: white;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 10px;
        }
        .toggle-btn:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
        }
        .message-card {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        .message-card .field {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .message-card .field-label {
            font-weight: 500;
            color: var(--dark-blue);
            width: 40%;
        }
        .message-card .field-value {
            width: 60%;
            text-align: right;
        }
        .message-card .message-container {
            margin: 10px 0;
        }
        .message-card .actions {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 15px;
        }
        @media (max-width: 1200px) {
            .navmenu ul li {
                margin: 0 0.2rem;
            }
            .navmenu ul li a {
                font-size: 0.85rem;
                padding: 0.4rem 0.5rem;
            }
            .btn-getstarted {
                padding: 0.4rem 0.8rem;
                font-size: 0.85rem;
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
                background: #3b82f6;
                box-shadow: none;
                margin-left: 1rem;
            }
        }
        @media (max-width: 768px) {
            .main {
                padding: 80px 15px;
            }
            .container1 {
                padding: 15px;
                margin: 1rem;
            }
            table {
                display: block;
                overflow-x: auto;
            }
            th, td {
                min-width: 120px;
            }
            .search-container {
                max-width: 100%;
            }
        }
        @media (max-width: 576px) {
            .logo h3 {
                font-size: 1rem;
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
            table {
                display: none;
            }
            .message-card {
                display: block;
            }
            .delete-btn, .toggle-btn {
                padding: 6px 10px;
                font-size: 13px;
            }
            .message-container {
                max-height: 80px;
            }
            .message-container.full {
                max-height: 150px;
            }
        }
        @media (min-width: 577px) {
            .message-card {
                display: none;
            }
        }
    </style>
    <link href="assets/css/admin-header.css?v=8" rel="stylesheet">
</head>
<body class="services-page admin-app-page">
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="header-container">
            <a href="dashboard.php" class="logo d-flex align-items-center">
                <span class="admin-header-mark"><i class="bi bi-globe2" aria-hidden="true"></i></span>
                <span class="admin-header-brand"><strong>Y-Global</strong><small>System Solution</small></span>
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="admin_messages.php">Chat <span class="notification-bell"><i class="bi bi-bell"></i><?php if ($unread_messages > 0): ?><span class="badge"><?php echo $unread_messages; ?></span><?php endif; ?></span></a></li>
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
        <section id="starter-section" class="starter-section section">
            <div class="container1" data-aos="fade-up" data-aos-duration="1000">
                <h2>View Messages</h2>
                <div class="search-container">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" placeholder="Search by name, email, or subject..." oninput="filterTable()">
                </div>
                <table id="messagesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>View</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $short_message = substr($row['message'], 0, 30) . (strlen($row['message']) > 30 ? '...' : '');
                                echo "<tr>
                                        <td data-label='ID'>{$row['id']}</td>
                                        <td data-label='Name'>{$row['name']}</td>
                                        <td data-label='Email'>{$row['email']}</td>
                                        <td data-label='Subject'>{$row['subject']}</td>
                                        <td data-label='Message'>
                                            <div class='message-container' id='message_container_{$row['id']}'>
                                                <span class='short-message' id='message_{$row['id']}'>{$short_message}</span>
                                                <span class='full-message' id='full_message_{$row['id']}'>{$row['message']}</span>
                                            </div>
                                        </td>
                                        <td data-label='View'>
                                            <button class='toggle-btn' onclick='toggleMessage({$row['id']})'>
                                                <i class='bi bi-chevron-down'></i> Show More
                                            </button>
                                        </td>
                                        <td data-label='Action'>
                                            <form class='delete-form' method='post' action='' onsubmit='return confirmDelete(event)'>
                                                <input type='hidden' name='delete_id' value='{$row['id']}'>
                                                <button type='submit' class='delete-btn'>Delete</button>
                                            </form>
                                        </td>
                                    </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center'>No messages found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <?php
                if ($result && $result->num_rows > 0) {
                    $result->data_seek(0);
                    while ($row = $result->fetch_assoc()) {
                        $short_message = substr($row['message'], 0, 30) . (strlen($row['message']) > 30 ? '...' : '');
                        echo "<div class='message-card' id='card_{$row['id']}'>
                                <div class='field'>
                                    <span class='field-label'>ID</span>
                                    <span class='field-value'>{$row['id']}</span>
                                </div>
                                <div class='field'>
                                    <span class='field-label'>Name</span>
                                    <span class='field-value'>{$row['name']}</span>
                                </div>
                                <div class='field'>
                                    <span class='field-label'>Email</span>
                                    <span class='field-value'>{$row['email']}</span>
                                </div>
                                <div class='field'>
                                    <span class='field-label'>Subject</span>
                                    <span class='field-value'>{$row['subject']}</span>
                                </div>
                                <div class='field'>
                                    <span class='field-label'>Message</span>
                                    <span class='field-value'>
                                        <div class='message-container' id='card_message_container_{$row['id']}'>
                                            <span class='short-message' id='card_message_{$row['id']}'>{$short_message}</span>
                                            <span class='full-message' id='card_full_message_{$row['id']}'>{$row['message']}</span>
                                        </div>
                                    </span>
                                </div>
                                <div class='field'>
                                    <span class='field-label'>Created At</span>
                                    <span class='field-value'>{$row['created_at']}</span>
                                </div>
                                <div class='actions'>
                                    <button class='toggle-btn' onclick='toggleMessage({$row['id']})'>
                                        <i class='bi bi-chevron-down'></i> Show More
                                    </button>
                                    <form class='delete-form' method='post' action='' onsubmit='return confirmDelete(event)'>
                                        <input type='hidden' name='delete_id' value='{$row['id']}'>
                                        <button type='submit' class='delete-btn'>Delete</button>
                                    </form>
                                </div>
                            </div>";
                    }
                } else {
                    echo "<div class='message-card text-center'>No messages found</div>";
                }
                ?>
            </div>
        </section>
    </main>
<?php include __DIR__ . '/../includes/admin-mobile-nav.php'; ?>
<footer id="footer" class="footer" style="background-color: #4b5563; color: white;">
        <div class="container copyright text-center mt-4">
            <p><span>Copyright © <?php echo date("Y"); ?></span> <strong class="px-1 sitename">|| Designed by Yared Alebachewu</strong> <span>All Rights Reserved</span></p>
        </div>
    </footer>
    

    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <div id="preloader"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
    <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
    <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
    <script src="assets/vendor/waypoints/noframework.waypoints.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        AOS.init({ duration: 1000 });

        // Mobile Nav Toggle
        const mobileNavToggle = document.querySelector('.mobile-nav-toggle');
        const navMenu = document.querySelector('.navmenu ul');
        mobileNavToggle.addEventListener('click', function() {
            navMenu.classList.toggle('show');
        });

        // Close mobile nav when clicking a link
        document.querySelectorAll('.navmenu ul li a').forEach(link => {
            link.addEventListener('click', () => {
                if (navMenu.classList.contains('show')) {
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
                dropdownMenu.classList.toggle('show');
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

        // Confirm delete action with SweetAlert2
        async function confirmDelete(event) {
            event.preventDefault();
            const form = event.target;
            const result = await Swal.fire({
                title: 'Are you sure?',
                text: 'Do you want to delete this message?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#1e3a8a',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            });
            if (result.isConfirmed) {
                form.submit();
            }
        }

        // Toggle full message
        function toggleMessage(id) {
            const message = document.getElementById('message_' + id);
            const fullMessage = document.getElementById('full_message_' + id);
            const container = document.getElementById('message_container_' + id);
            const cardMessage = document.getElementById('card_message_' + id);
            const cardFullMessage = document.getElementById('card_full_message_' + id);
            const cardContainer = document.getElementById('card_message_container_' + id);
            const toggleBtn = document.querySelectorAll(`[onclick='toggleMessage(${id})']`);

            if (message.style.display === 'none') {
                message.style.display = 'block';
                fullMessage.style.display = 'none';
                container.classList.remove('full');
                cardMessage.style.display = 'block';
                cardFullMessage.style.display = 'none';
                cardContainer.classList.remove('full');
                toggleBtn.forEach(btn => {
                    btn.innerHTML = `<i class='bi bi-chevron-down'></i> Show More`;
                });
            } else {
                message.style.display = 'none';
                fullMessage.style.display = 'block';
                container.classList.add('full');
                cardMessage.style.display = 'none';
                cardFullMessage.style.display = 'block';
                cardContainer.classList.add('full');
                toggleBtn.forEach(btn => {
                    btn.innerHTML = `<i class='bi bi-chevron-up'></i> Show Less`;
                });
            }
        }

        // Auto-search functionality
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const tableRows = document.getElementById('messagesTable').getElementsByTagName('tr');
            const cards = document.getElementsByClassName('message-card');

            // Filter table rows
            for (let i = 0; i < tableRows.length; i++) {
                const cells = tableRows[i].getElementsByTagName('td');
                let match = false;
                for (let j = 1; j <= 3; j++) { // Name, Email, Subject
                    if (cells[j]) {
                        const textValue = cells[j].textContent || cells[j].innerText;
                        if (textValue.toLowerCase().indexOf(filter) > -1) {
                            match = true;
                            break;
                        }
                    }
                }
                tableRows[i].style.display = match ? '' : 'none';
            }

            // Filter cards
            for (let i = 0; i < cards.length; i++) {
                const fields = cards[i].getElementsByClassName('field-value');
                let match = false;
                for (let j = 1; j <= 3; j++) { // Name, Email, Subject
                    if (fields[j]) {
                        const textValue = fields[j].textContent || fields[j].innerText;
                        if (textValue.toLowerCase().indexOf(filter) > -1) {
                            match = true;
                            break;
                        }
                    }
                }
                cards[i].style.display = match ? '' : 'none';
            }
        }

         // Logout Confirmation Dialog
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
                    window.location.href = 'logout.php';
                }
            });
        }
    </script>
<script src="assets/js/admin-sidebar.js?v=1"></script>
</body>
</html>