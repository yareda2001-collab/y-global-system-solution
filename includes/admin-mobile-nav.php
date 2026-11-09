<?php
$currentAdminPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="admin-mobile-nav" aria-label="Admin navigation">
  <a href="dashboard.php" <?php echo $currentAdminPage === 'dashboard.php' ? 'aria-current="page"' : ''; ?>>
    <i class="bi bi-house-door" aria-hidden="true"></i>
    <span>Home</span>
  </a>
  <a href="admin_messages.php" <?php echo in_array($currentAdminPage, ['admin_messages.php', 'messages.php'], true) ? 'aria-current="page"' : ''; ?>>
    <i class="bi bi-chat-dots" aria-hidden="true"></i>
    <span>Chat</span>
  </a>
  <a href="index.php" <?php echo in_array($currentAdminPage, ['index.php', 'manage_admin_rights.php'], true) ? 'aria-current="page"' : ''; ?>>
    <i class="bi bi-people" aria-hidden="true"></i>
    <span>Users</span>
  </a>
  <a href="profile_settings.php" <?php echo in_array($currentAdminPage, ['profile_settings.php', 'change_password.php'], true) ? 'aria-current="page"' : ''; ?>>
    <i class="bi bi-person" aria-hidden="true"></i>
    <span>Profile</span>
  </a>
</nav>
