<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/connect.php';

$loggedInUserId = (int) $_SESSION['user_id'];
$adminStmt = $conn->prepare(
    "SELECT u.role, u.status, COALESCE(ar.can_create_admins, 0) AS can_manage_admin_accounts
     FROM users u
     LEFT JOIN admin_rights ar ON ar.admin_id = u.id
     WHERE u.id = ?"
);
$adminStmt->bind_param('i', $loggedInUserId);
$adminStmt->execute();
$currentAdmin = $adminStmt->get_result()->fetch_assoc();
$adminStmt->close();

if (!$currentAdmin || (int) $currentAdmin['status'] !== 1) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$isSuperAdmin = $currentAdmin['role'] === 'super_admin';
$canManageAdminAccounts = $isSuperAdmin || (int) $currentAdmin['can_manage_admin_accounts'] === 1;
if (!$canManageAdminAccounts) {
    http_response_code(403);
    exit('Access denied. Admin account management permission is required.');
}

if (empty($_SESSION['admin_accounts_csrf_token'])) {
    $_SESSION['admin_accounts_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['admin_accounts_csrf_token'];

$message = $_SESSION['admin_accounts_flash']['message'] ?? null;
$messageType = $_SESSION['admin_accounts_flash']['type'] ?? 'success';
unset($_SESSION['admin_accounts_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $_SESSION['admin_accounts_flash'] = [
            'message' => 'Your session could not be verified. Refresh the page and try again.',
            'type' => 'danger',
        ];
    } else {
        $action = $_POST['action'] ?? '';
        $adminId = filter_var($_POST['admin_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$adminId || $adminId < 1) {
            $_SESSION['admin_accounts_flash'] = ['message' => 'Select a valid admin account.', 'type' => 'danger'];
        } else {
            $targetStmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'admin'");
            $targetStmt->bind_param('i', $adminId);
            $targetStmt->execute();
            $targetAdmin = $targetStmt->get_result()->fetch_assoc();
            $targetStmt->close();

            if (!$targetAdmin) {
                $_SESSION['admin_accounts_flash'] = ['message' => 'That admin account no longer exists or cannot be managed.', 'type' => 'danger'];
            } elseif ($action === 'update_details') {
                $username = trim((string) ($_POST['username'] ?? ''));
                $email = trim((string) ($_POST['email'] ?? ''));

                if ($username === '' || mb_strlen($username) > 50) {
                    $_SESSION['admin_accounts_flash'] = ['message' => 'Enter a username of up to 50 characters.', 'type' => 'danger'];
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
                    $_SESSION['admin_accounts_flash'] = ['message' => 'Enter a valid email address of up to 100 characters.', 'type' => 'danger'];
                } else {
                    $duplicateStmt = $conn->prepare('SELECT username, email FROM users WHERE id <> ? AND (username = ? OR email = ?)');
                    $duplicateStmt->bind_param('iss', $adminId, $username, $email);
                    $duplicateStmt->execute();
                    $duplicates = $duplicateStmt->get_result();
                    $usernameTaken = false;
                    $emailTaken = false;
                    while ($duplicate = $duplicates->fetch_assoc()) {
                        $usernameTaken = $usernameTaken || $duplicate['username'] === $username;
                        $emailTaken = $emailTaken || $duplicate['email'] === $email;
                    }
                    $duplicateStmt->close();

                    if ($usernameTaken && $emailTaken) {
                        $_SESSION['admin_accounts_flash'] = ['message' => 'That username and email are already in use.', 'type' => 'danger'];
                    } elseif ($usernameTaken) {
                        $_SESSION['admin_accounts_flash'] = ['message' => 'That username is already in use.', 'type' => 'danger'];
                    } elseif ($emailTaken) {
                        $_SESSION['admin_accounts_flash'] = ['message' => 'That email is already in use.', 'type' => 'danger'];
                    } else {
                        try {
                            $updateStmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ? AND role = 'admin'");
                            $updateStmt->bind_param('ssi', $username, $email, $adminId);
                            $updateStmt->execute();
                            $updateStmt->close();
                            $_SESSION['admin_accounts_flash'] = ['message' => 'Admin account details updated.', 'type' => 'success'];
                        } catch (mysqli_sql_exception $exception) {
                            if ($exception->getCode() === 1062) {
                                $_SESSION['admin_accounts_flash'] = ['message' => 'That username or email was just claimed by another account. Please try different details.', 'type' => 'danger'];
                            } else {
                                error_log('Admin account update failed: ' . $exception->getMessage());
                                $_SESSION['admin_accounts_flash'] = ['message' => 'Could not update this account. Please try again.', 'type' => 'danger'];
                            }
                        }
                    }
                }
            } elseif ($action === 'update_status') {
                $status = $_POST['status'] ?? '';
                if ($status !== '0' && $status !== '1') {
                    $_SESSION['admin_accounts_flash'] = ['message' => 'Choose a valid account status.', 'type' => 'danger'];
                } else {
                    $newStatus = (int) $status;
                    $statusStmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'admin'");
                    $statusStmt->bind_param('ii', $newStatus, $adminId);
                    $statusStmt->execute();
                    $statusStmt->close();
                    $_SESSION['admin_accounts_flash'] = [
                        'message' => $newStatus ? 'Admin account enabled. The admin can sign in again.' : 'Admin account disabled. New sign-ins are blocked.',
                        'type' => 'success',
                    ];
                }
            } else {
                $_SESSION['admin_accounts_flash'] = ['message' => 'Unknown account action.', 'type' => 'danger'];
            }
        }
    }

    header('Location: manage_admin_accounts.php', true, 303);
    exit;
}

$adminsResult = $conn->query(
    "SELECT id, username, email, status, created_at
     FROM users
     WHERE role = 'admin'
     ORDER BY username ASC"
);
if (!$adminsResult) {
    error_log('Could not load admin accounts: ' . $conn->error);
    http_response_code(500);
    exit('Could not load admin accounts. Please try again later.');
}
$admins = $adminsResult->fetch_all(MYSQLI_ASSOC);
$activeAdminCount = 0;
foreach ($admins as $admin) {
    $activeAdminCount += (int) $admin['status'] === 1 ? 1 : 0;
}
$disabledAdminCount = count($admins) - $activeAdminCount;

$unreadMessages = 0;
$unreadStmt = $conn->prepare('SELECT COUNT(*) AS total FROM messages WHERE receiver_id = ? AND is_seen = 0 AND seen_by_user = 0');
$unreadStmt->bind_param('i', $loggedInUserId);
$unreadStmt->execute();
$unreadMessages = (int) $unreadStmt->get_result()->fetch_assoc()['total'];
$unreadStmt->close();

$pendingResult = $conn->query("SELECT COUNT(*) AS total FROM password_reset_requests WHERE status = 'pending'");
if (!$pendingResult) {
    error_log('Could not load pending reset request count: ' . $conn->error);
    http_response_code(500);
    exit('Could not load admin notifications. Please try again later.');
}
$pendingRequests = (int) $pendingResult->fetch_assoc()['total'];
$unread_messages = $unreadMessages;
$pending_count = $pendingRequests;
$conn->close();

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Admin Accounts - Y-Global</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/admin-header.css?v=8" rel="stylesheet">
    <link href="assets/css/admin-create-admin.css?v=2" rel="stylesheet">
    <style>
        .accounts-page { min-height:100vh; padding:94px 1.25rem 2.5rem; background:radial-gradient(circle at 85% 4%,rgba(37,99,235,.08),transparent 25rem),#f3f7fb; }
        .accounts-wrap { max-width:1080px; margin:0 auto; }
        .accounts-hero { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1rem; padding:1.1rem 1.4rem; border:1px solid rgba(255,255,255,.13); border-radius:18px; color:#fff; background:linear-gradient(115deg,#082f43,#0e6079 72%,#2563eb); box-shadow:0 12px 30px rgba(8,47,67,.14); }
        .accounts-hero h1 { margin:0; font-size:clamp(1.25rem,3vw,1.75rem); font-weight:750; letter-spacing:-.03em; }
        .accounts-hero p { margin:.25rem 0 0; color:rgba(255,255,255,.78); font-size:.88rem; }
        .accounts-hero-actions { display:flex; align-items:center; gap:.55rem; }
        .accounts-hero-actions a { display:inline-flex; align-items:center; gap:.4rem; padding:.6rem .8rem; border:1px solid rgba(255,255,255,.24); border-radius:10px; color:#fff; font-size:.82rem; font-weight:650; text-decoration:none; white-space:nowrap; }
        .accounts-hero-actions a:hover { background:rgba(255,255,255,.12); }
        .accounts-hero-actions .primary-action { border-color:#fff; background:#fff; color:#0d4f6b; }
        .accounts-hero-actions .primary-action:hover { background:#e9f5fb; }
        .accounts-hero-actions a:focus-visible,.account-filter:focus-visible,.account-actions .btn:focus-visible { outline:3px solid #facc15; outline-offset:2px; }
        .accounts-summary { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.65rem; margin-bottom:.8rem; }
        .summary-tile { display:flex; align-items:center; gap:.65rem; min-height:62px; padding:.65rem .85rem; border:1px solid #e2eaf1; border-radius:13px; background:#fff; }
        .summary-icon { display:grid; width:34px; height:34px; flex:0 0 34px; place-items:center; border-radius:11px; color:#17617c; background:#e8f4fa; }
        .summary-icon.is-green { color:#147548; background:#e7f7ed; }
        .summary-icon.is-red { color:#a34232; background:#fff0ed; }
        .summary-tile strong { display:block; color:#17384e; font-size:1.05rem; line-height:1.1; }
        .summary-tile span:last-child { color:#748294; font-size:.75rem; }
        .accounts-toolbar { display:grid; grid-template-columns:minmax(180px,1fr) minmax(230px,340px); align-items:center; gap:.8rem; margin-bottom:.75rem; }
        .accounts-search-wrap { position:relative; }
        .accounts-search-wrap i { position:absolute; top:50%; left:.8rem; transform:translateY(-50%); color:#8090a1; }
        .accounts-search { min-height:43px; padding-left:2.35rem; border:1px solid #d5e0e9; border-radius:11px; background:#fff; }
        .accounts-filters { display:flex; flex-wrap:wrap; gap:.4rem; }
        .account-filter { padding:.4rem .7rem; border:1px solid #dce5ed; border-radius:999px; background:#fff; color:#526579; font-size:.79rem; font-weight:650; }
        .account-filter[aria-pressed="true"] { border-color:#0f607e; background:#eaf5fa; color:#0b526d; }
        .account-list-heading { display:flex; align-items:center; justify-content:space-between; gap:.5rem; margin:.8rem 0 .45rem; color:#526579; font-size:.83rem; }
        .account-card { margin-bottom:.55rem; padding:.75rem .9rem; border:1px solid #e1e9f0; border-radius:14px; background:#fff; box-shadow:0 4px 14px rgba(15,44,66,.045); transition:border-color .16s ease,box-shadow .16s ease; }
        .account-card:hover { border-color:#c5d8e6; box-shadow:0 7px 20px rgba(15,44,66,.075); }
        .account-card-main { display:grid; grid-template-columns:minmax(210px,1.2fr) minmax(180px,1fr) auto; align-items:center; gap:.8rem; }
        .account-identity { display:flex; min-width:0; align-items:center; gap:.65rem; }
        .account-avatar { display:grid; width:38px; height:38px; flex:0 0 38px; place-items:center; border-radius:12px; color:#14506b; background:#e7f2f8; }
        .account-identity-copy { min-width:0; }
        .account-identity strong { display:block; overflow:hidden; color:#12344a; font-size:.91rem; text-overflow:ellipsis; white-space:nowrap; }
        .account-identity span,.account-email { display:block; overflow:hidden; color:#718096; font-size:.77rem; text-overflow:ellipsis; white-space:nowrap; }
        .account-state { display:inline-flex; align-items:center; gap:.35rem; padding:.32rem .55rem; border-radius:999px; font-size:.74rem; font-weight:700; white-space:nowrap; }
        .account-state.is-active { color:#147548; background:#e6f7ed; }
        .account-state.is-disabled { color:#a34232; background:#fff0ed; }
        .account-details-meta { display:flex; flex-wrap:wrap; align-items:center; gap:.45rem .8rem; margin-top:.4rem; }
        .account-meta-item { display:inline-flex; align-items:center; gap:.3rem; color:#7b8998; font-size:.72rem; }
        .account-actions { display:flex; align-items:center; gap:.4rem; }
        .account-actions .btn,.account-status-form button { display:inline-flex; min-height:36px; align-items:center; justify-content:center; gap:.3rem; padding:.35rem .65rem; border-radius:9px; font-size:.78rem; font-weight:700; white-space:nowrap; }
        .account-edit { margin-top:.6rem; border-top:1px solid #edf1f5; }
        .account-edit summary { width:max-content; margin-top:.55rem; color:#17617c; cursor:pointer; font-size:.78rem; font-weight:700; list-style:none; }
        .account-edit summary::-webkit-details-marker { display:none; }
        .account-edit summary::before { content:"\f4ca"; display:inline-block; margin-right:.35rem; font-family:"bootstrap-icons"; }
        .account-edit[open] summary { margin-bottom:.65rem; }
        .account-edit[open] summary::before { content:"\f62a"; }
        .account-edit-grid { display:grid; grid-template-columns:1fr 1fr auto; align-items:end; gap:.65rem; padding-bottom:.15rem; }
        .account-edit-grid label { display:block; margin-bottom:.25rem; color:#526579; font-size:.75rem; font-weight:700; }
        .account-edit-grid input { min-height:39px; border-radius:9px; font-size:.86rem; }
        .account-edit-grid .btn { min-height:39px; border-radius:9px; font-size:.8rem; font-weight:650; }
        .account-created { margin-top:.2rem; color:#8793a1; font-size:.7rem; }
        .accounts-empty { padding:2.2rem 1rem; border:1px dashed #cbd8e3; border-radius:16px; background:#fff; color:#64748b; text-align:center; }
        .admin-accounts-page { display:flex; min-height:100vh; flex-direction:column; }
        .admin-accounts-page .accounts-page { flex:1 0 auto; }
        .admin-accounts-page #footer { flex:0 0 auto; padding:1rem .75rem calc(1rem + env(safe-area-inset-bottom)); text-align:center; }
        .admin-accounts-page #footer .copyright { margin:0 !important; }
        .admin-accounts-page #footer p { margin:0; font-size:.82rem; line-height:1.55; }
        @media(max-width:767.98px) {
            body.admin-accounts-page { padding-bottom:calc(58px + env(safe-area-inset-bottom)) !important; }
            body.admin-accounts-page #footer { display:none !important; }
        }
        @media(max-width:780px) {
            .accounts-page { padding:84px .8rem 2rem; }
            .accounts-hero { align-items:flex-start; flex-direction:column; gap:.75rem; padding:1rem; }
            .accounts-hero-actions { width:100%; gap:.5rem; }
            .accounts-hero-actions a { flex:1 1 0; justify-content:center; min-width:0; padding:.62rem .5rem; }
            .accounts-summary { gap:.45rem; }
            .summary-tile { min-height:56px; padding:.55rem; gap:.45rem; }
            .summary-icon { width:30px; height:30px; flex-basis:30px; }
            .summary-tile span:last-child { font-size:.68rem; }
            .accounts-toolbar { grid-template-columns:1fr; gap:.55rem; }
            .accounts-filters { order:2; }
            .account-card-main { grid-template-columns:minmax(0,1fr) auto; gap:.3rem .6rem; }
            .account-email { grid-column:1; padding-left:2.95rem; }
            .account-actions { grid-column:2; grid-row:1 / span 2; flex-direction:row; align-items:center; }
            .account-actions .btn { min-width:84px; }
            .account-details-meta { padding-left:2.95rem; }
            .account-edit-grid { grid-template-columns:1fr; }
            .account-edit-grid .btn { width:100%; }
        }
        @media(max-width:420px) {
            .accounts-hero-actions a { padding:.58rem .35rem; font-size:.72rem; }
            .accounts-summary { grid-template-columns:repeat(3,minmax(0,1fr)); }
            .summary-tile { display:flex; min-height:68px; flex-direction:column; justify-content:center; gap:.2rem; padding:.4rem .2rem; text-align:center; }
            .summary-icon { margin:0 auto .25rem; }
            .summary-tile strong { font-size:1rem; }
            .summary-tile span:last-child { font-size:.66rem; }
            .account-card { padding:.65rem; }
            .account-actions { gap:.25rem; }
            .account-actions .btn { min-width:64px; padding:.3rem .38rem; font-size:.69rem; }
            .account-details-meta { gap:.35rem .55rem; }
            .account-meta-item { font-size:.67rem; }
            .account-list-heading { margin-top:.65rem; }
        }
    </style>
</head>
<body class="admin-app-page admin-create-admin-page admin-accounts-page">
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="header-container">
            <a href="dashboard.php" class="logo d-flex align-items-center">
                <span class="admin-header-mark"><i class="bi bi-globe2" aria-hidden="true"></i></span>
                <span class="admin-header-brand"><strong>Y-Global</strong><small>System Solution</small></span>
            </a>

            <nav id="navmenu" class="navmenu" aria-label="Admin main navigation">
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
                <button class="btn-getstarted" onclick="confirmLogout()" style="background-color: #ef4444;">Log Out</button>
            </nav>
        </div>
    </header>
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>
    <?php include __DIR__ . '/../includes/admin-mobile-nav.php'; ?>

    <main class="main accounts-page">
        <div class="accounts-wrap">
            <section class="accounts-hero">
                <div>
                    <h1>Admin account management</h1>
                    <p>Quickly update details and manage admin access.</p>
                </div>
                <nav class="accounts-hero-actions" aria-label="Account management shortcuts">
                    <?php if ($isSuperAdmin): ?>
                        <a href="manage_admin_rights.php"><i class="bi bi-shield-check" aria-hidden="true"></i> Rights</a>
                    <?php endif; ?>
                    <a class="primary-action" href="create_admin.php"><i class="bi bi-plus-lg" aria-hidden="true"></i> Create admin</a>
                </nav>
            </section>

            <?php if ($message !== null): ?>
                <div class="alert alert-<?php echo h($messageType); ?> alert-dismissible fade show" role="status">
                    <?php echo h((string) $message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <section class="accounts-summary" aria-label="Admin account totals">
                <div class="summary-tile">
                    <span class="summary-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
                    <div><strong><?php echo count($admins); ?></strong><span>Total admins</span></div>
                </div>
                <div class="summary-tile">
                    <span class="summary-icon is-green"><i class="bi bi-check-circle" aria-hidden="true"></i></span>
                    <div><strong><?php echo $activeAdminCount; ?></strong><span>Active</span></div>
                </div>
                <div class="summary-tile">
                    <span class="summary-icon is-red"><i class="bi bi-slash-circle" aria-hidden="true"></i></span>
                    <div><strong><?php echo $disabledAdminCount; ?></strong><span>Disabled</span></div>
                </div>
            </section>

            <div class="accounts-toolbar">
                <div class="accounts-filters" role="group" aria-label="Filter admin accounts">
                    <button class="account-filter" type="button" data-filter="all" aria-pressed="true">All admins</button>
                    <button class="account-filter" type="button" data-filter="active" aria-pressed="false">Active</button>
                    <button class="account-filter" type="button" data-filter="disabled" aria-pressed="false">Disabled</button>
                </div>
                <div class="accounts-search-wrap">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" class="form-control accounts-search" id="adminSearch" placeholder="Search name or email" aria-label="Search admin accounts">
                </div>
            </div>

            <section id="adminAccounts" aria-label="Admin accounts">
                <?php if (!$admins): ?>
                    <div class="accounts-empty">
                        <i class="bi bi-people fs-2 d-block mb-2" aria-hidden="true"></i>
                        No admin accounts yet. Create an admin account to manage it here.
                    </div>
                <?php else: ?>
                    <div class="account-list-heading">
                        <span>Administrator accounts</span>
                        <span><?php echo count($admins); ?> listed</span>
                    </div>
                    <?php foreach ($admins as $admin): ?>
                        <?php $isActive = (int) $admin['status'] === 1; ?>
                        <article class="account-card" data-status="<?php echo $isActive ? 'active' : 'disabled'; ?>" data-search="<?php echo h(strtolower($admin['username'] . ' ' . $admin['email'])); ?>">
                            <div class="account-card-main">
                                <div class="account-identity">
                                    <span class="account-avatar"><i class="bi bi-person-badge" aria-hidden="true"></i></span>
                                    <div class="account-identity-copy">
                                        <strong><?php echo h($admin['username']); ?></strong>
                                        <span>Admin · ID <?php echo (int) $admin['id']; ?></span>
                                    </div>
                                </div>
                                <span class="account-email"><?php echo h($admin['email']); ?></span>
                                <div class="account-actions">
                                    <button class="btn btn-light border edit-account-toggle" type="button" aria-expanded="false" aria-controls="edit-admin-<?php echo (int) $admin['id']; ?>">
                                        <i class="bi bi-pencil" aria-hidden="true"></i> Edit
                                    </button>
                                    <form method="post" class="account-status-form" data-confirm="<?php echo $isActive ? 'Disable this admin account? They will not be able to sign in.' : 'Enable this admin account so they can sign in again?'; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="admin_id" value="<?php echo (int) $admin['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo $isActive ? '0' : '1'; ?>">
                                        <button class="btn <?php echo $isActive ? 'btn-outline-danger' : 'btn-outline-success'; ?>" type="submit">
                                            <i class="bi <?php echo $isActive ? 'bi-person-slash' : 'bi-person-check'; ?>" aria-hidden="true"></i>
                                            <?php echo $isActive ? 'Disable' : 'Enable'; ?>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="account-details-meta">
                                <span class="account-state <?php echo $isActive ? 'is-active' : 'is-disabled'; ?>">
                                    <i class="bi <?php echo $isActive ? 'bi-check-circle-fill' : 'bi-slash-circle-fill'; ?>" aria-hidden="true"></i>
                                    <?php echo $isActive ? 'Active' : 'Disabled'; ?>
                                </span>
                                <span class="account-meta-item"><i class="bi bi-calendar3" aria-hidden="true"></i>Added <?php echo h(date('M j, Y', strtotime($admin['created_at']))); ?></span>
                            </div>

                            <details class="account-edit" id="edit-admin-<?php echo (int) $admin['id']; ?>">
                                <summary>Edit account details</summary>
                                <form method="post" class="account-edit-form">
                                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                <input type="hidden" name="action" value="update_details">
                                <input type="hidden" name="admin_id" value="<?php echo (int) $admin['id']; ?>">
                                <div class="account-edit-grid">
                                    <div>
                                        <label for="username-<?php echo (int) $admin['id']; ?>">Username</label>
                                        <input class="form-control" id="username-<?php echo (int) $admin['id']; ?>" name="username" maxlength="50" value="<?php echo h($admin['username']); ?>" required>
                                    </div>
                                    <div>
                                        <label for="email-<?php echo (int) $admin['id']; ?>">Email address</label>
                                        <input class="form-control" type="email" id="email-<?php echo (int) $admin['id']; ?>" name="email" maxlength="100" value="<?php echo h($admin['email']); ?>" required>
                                    </div>
                                    <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1" aria-hidden="true"></i> Save details</button>
                                </div>
                                </form>
                            </details>
                        </article>
                    <?php endforeach; ?>
                    <div class="accounts-empty d-none" id="noSearchResults">No admin accounts match your search.</div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <footer id="footer" class="footer" style="background-color:#082f43;color:#fff;">
        <div class="container copyright text-center">
            <p><span>Copyright © <?php echo date('Y'); ?></span> <span>All Rights Reserved</span></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin-sidebar.js?v=1"></script>
    <script>
        const searchInput = document.getElementById('adminSearch');
        const filterButtons = document.querySelectorAll('.account-filter');
        const cards = [...document.querySelectorAll('.account-card')];
        const noSearchResults = document.getElementById('noSearchResults');
        let currentFilter = 'all';

        const applyAccountFilters = () => {
            const query = searchInput?.value.trim().toLowerCase() ?? '';
            let visibleCount = 0;
            cards.forEach(card => {
                const matchesSearch = card.dataset.search.includes(query);
                const matchesStatus = currentFilter === 'all' || card.dataset.status === currentFilter;
                const visible = matchesSearch && matchesStatus;
                card.classList.toggle('d-none', !visible);
                visibleCount += visible ? 1 : 0;
            });
            noSearchResults?.classList.toggle('d-none', visibleCount > 0);
        };

        searchInput?.addEventListener('input', applyAccountFilters);
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                currentFilter = button.dataset.filter;
                filterButtons.forEach(filter => filter.setAttribute('aria-pressed', String(filter === button)));
                applyAccountFilters();
            });
        });

        document.querySelectorAll('.edit-account-toggle').forEach(button => {
            button.addEventListener('click', () => {
                const details = document.getElementById(button.getAttribute('aria-controls'));
                details.open = !details.open;
                button.setAttribute('aria-expanded', String(details.open));
                if (details.open) {
                    details.querySelector('input[name="username"]').focus();
                }
            });
        });

        document.querySelectorAll('.account-edit').forEach(details => {
            details.addEventListener('toggle', () => {
                const button = document.querySelector(`[aria-controls="${details.id}"]`);
                button?.setAttribute('aria-expanded', String(details.open));
            });
        });

        document.querySelectorAll('.account-status-form').forEach(form => {
            form.addEventListener('submit', event => {
                if (!window.confirm(form.dataset.confirm)) {
                    event.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
