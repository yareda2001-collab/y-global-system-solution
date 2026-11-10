<?php
$currentAdminPage = basename($_SERVER['PHP_SELF']);
$sidebarUnreadMessages = (int)($unread_messages ?? $unread_count ?? 0);
$sidebarPendingRequests = (int)($pending_count ?? 0);
$sidebarCanManageAdminAccounts = ($_SESSION['role'] ?? '') === 'super_admin';
if (!$sidebarCanManageAdminAccounts && isset($conn) && $conn instanceof mysqli && isset($_SESSION['user_id'])) {
  try {
    $sidebarPermissionStmt = $conn->prepare(
      'SELECT can_create_admins FROM admin_rights WHERE admin_id = ?'
    );
    if ($sidebarPermissionStmt) {
      $sidebarUserId = (int) $_SESSION['user_id'];
      $sidebarPermissionStmt->bind_param('i', $sidebarUserId);
      $sidebarPermissionStmt->execute();
      $sidebarPermission = $sidebarPermissionStmt->get_result()->fetch_assoc();
      $sidebarCanManageAdminAccounts = (int) ($sidebarPermission['can_create_admins'] ?? 0) === 1;
      $sidebarPermissionStmt->close();
    } else {
      error_log('Could not load admin account management navigation permission: ' . $conn->error);
    }
  } catch (mysqli_sql_exception | Error $error) {
    error_log('Could not load admin account management navigation permission: ' . $error->getMessage());
  }
}
?>
<button type="button" class="admin-sidebar-backdrop" aria-label="Close admin menu"></button>
<aside class="admin-sidebar" id="admin-sidebar" aria-label="Admin menu">
  <div class="admin-sidebar-heading">
    <span class="admin-sidebar-mark"><i class="bi bi-grid-1x2-fill" aria-hidden="true"></i></span>
    <div>
      <strong>Admin workspace</strong>
      <span>Management menu</span>
    </div>
    <button type="button" class="admin-sidebar-close" aria-label="Close admin menu">
      <i class="bi bi-x-lg" aria-hidden="true"></i>
    </button>
  </div>
  <nav class="admin-sidebar-nav" aria-label="Admin pages">
    <a href="dashboard.php" <?php echo $currentAdminPage === 'dashboard.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-house-door" aria-hidden="true"></i><span>Dashboard</span>
    </a>
    <a href="admin_messages.php" <?php echo $currentAdminPage === 'admin_messages.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-chat-dots" aria-hidden="true"></i><span>Chat</span>
      <?php if ($sidebarUnreadMessages > 0): ?>
        <span class="admin-sidebar-badge" aria-label="<?php echo $sidebarUnreadMessages; ?> unread messages"><?php echo $sidebarUnreadMessages > 99 ? '99+' : $sidebarUnreadMessages; ?></span>
      <?php endif; ?>
    </a>
    <a href="messages.php" <?php echo $currentAdminPage === 'messages.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-envelope" aria-hidden="true"></i><span>Contact messages</span>
    </a>
    <a href="manage_reset_requests.php" <?php echo $currentAdminPage === 'manage_reset_requests.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-key" aria-hidden="true"></i><span>Reset requests</span>
      <?php if ($sidebarPendingRequests > 0): ?>
        <span class="admin-sidebar-badge" aria-label="<?php echo $sidebarPendingRequests; ?> pending reset requests"><?php echo $sidebarPendingRequests > 99 ? '99+' : $sidebarPendingRequests; ?></span>
      <?php endif; ?>
    </a>
    <a href="index.php" <?php echo $currentAdminPage === 'index.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-people" aria-hidden="true"></i><span>Users</span>
    </a>
    <a href="create_admin.php" <?php echo $currentAdminPage === 'create_admin.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-person-plus" aria-hidden="true"></i><span>Admins</span>
    </a>
    <a href="manage_admin_rights.php" <?php echo $currentAdminPage === 'manage_admin_rights.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-shield-check" aria-hidden="true"></i><span>Admin rights</span>
    </a>
    <?php if ($sidebarCanManageAdminAccounts): ?>
      <a href="manage_admin_accounts.php" <?php echo $currentAdminPage === 'manage_admin_accounts.php' ? 'aria-current="page"' : ''; ?>>
        <i class="bi bi-person-gear" aria-hidden="true"></i><span>Manage admin accounts</span>
      </a>
    <?php endif; ?>
    <div class="admin-sidebar-group">
      <div class="admin-sidebar-group-title<?php echo in_array($currentAdminPage, ['blog.php', 'create_post.php', 'manage_blog.php'], true) ? ' is-active' : ''; ?>">
        <i class="bi bi-journal-text" aria-hidden="true"></i><span>Blog</span>
      </div>
      <div class="admin-sidebar-subnav">
        <a href="blog.php" <?php echo $currentAdminPage === 'blog.php' ? 'aria-current="page"' : ''; ?>>
          <i class="bi bi-eye" aria-hidden="true"></i><span>View Blog</span>
        </a>
        <a href="create_post.php" <?php echo $currentAdminPage === 'create_post.php' ? 'aria-current="page"' : ''; ?>>
          <i class="bi bi-plus-circle" aria-hidden="true"></i><span>Create Blog</span>
        </a>
        <a href="manage_blog.php" <?php echo $currentAdminPage === 'manage_blog.php' ? 'aria-current="page"' : ''; ?>>
          <i class="bi bi-pencil-square" aria-hidden="true"></i><span>Manage Blog</span>
        </a>
      </div>
    </div>
    <div class="admin-sidebar-group">
      <div class="admin-sidebar-group-title<?php echo in_array($currentAdminPage, ['createportfolio.php', 'manageportfolio.php'], true) ? ' is-active' : ''; ?>">
        <i class="bi bi-briefcase" aria-hidden="true"></i><span>Portfolio</span>
      </div>
      <div class="admin-sidebar-subnav">
        <a href="createportfolio.php" <?php echo $currentAdminPage === 'createportfolio.php' ? 'aria-current="page"' : ''; ?>>
          <i class="bi bi-plus-circle" aria-hidden="true"></i><span>Create Portfolio</span>
        </a>
        <a href="manageportfolio.php" <?php echo $currentAdminPage === 'manageportfolio.php' ? 'aria-current="page"' : ''; ?>>
          <i class="bi bi-collection" aria-hidden="true"></i><span>Manage Portfolio</span>
        </a>
      </div>
    </div>
    <a href="profile_settings.php" <?php echo $currentAdminPage === 'profile_settings.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-person-circle" aria-hidden="true"></i><span>Profile</span>
    </a>
    <a href="change_password.php" <?php echo $currentAdminPage === 'change_password.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-shield-lock" aria-hidden="true"></i><span>Password</span>
    </a>
  </nav>
  <div class="admin-sidebar-bottom">
    <button type="button" class="admin-sidebar-logout" onclick="confirmLogout()">
      <i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>Log out</span>
    </button>
  </div>
</aside>
