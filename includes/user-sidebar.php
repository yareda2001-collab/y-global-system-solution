<?php
$currentUserPage = basename($_SERVER['PHP_SELF']);
?>
<button type="button" class="user-sidebar-backdrop" aria-label="Close account menu"></button>
<aside class="user-sidebar" id="user-sidebar" aria-label="Account sidebar">
  <div class="user-sidebar-heading">
    <span class="user-sidebar-mark"><i class="bi bi-grid-1x2-fill" aria-hidden="true"></i></span>
    <div>
      <strong>My workspace</strong>
      <span>Account menu</span>
    </div>
    <button type="button" class="user-sidebar-close" aria-label="Close account menu">
      <i class="bi bi-x-lg" aria-hidden="true"></i>
    </button>
  </div>
  <nav class="user-sidebar-nav" aria-label="Account pages">
    <a href="dashboard.php" <?php echo $currentUserPage === 'dashboard.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-house-door" aria-hidden="true"></i><span>Dashboard</span>
    </a>
    <a href="messages.php" <?php echo $currentUserPage === 'messages.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-chat-dots" aria-hidden="true"></i><span>Messages</span>
    </a>
    <a href="settings.php" <?php echo $currentUserPage === 'settings.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-gear" aria-hidden="true"></i><span>Settings</span>
    </a>
    <a href="update_password.php" <?php echo $currentUserPage === 'update_password.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-shield-lock" aria-hidden="true"></i><span>Update Password</span>
    </a>
    <a href="update_profile_picture.php" <?php echo $currentUserPage === 'update_profile_picture.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-person-circle" aria-hidden="true"></i><span>Profile Picture</span>
    </a>
  </nav>
  <div class="user-sidebar-bottom">
    <button type="button" class="user-sidebar-install" id="install-app" hidden>
      <i class="bi bi-download" aria-hidden="true"></i><span>Install app</span>
    </button>
    <button type="button" class="user-sidebar-logout" onclick="confirmLogout()">
      <i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>Log out</span>
    </button>
  </div>
</aside>
