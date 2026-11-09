<?php
$currentUserPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="mobile-app-nav" aria-label="Account navigation" style="display: none">
  <a href="dashboard.php" <?php echo $currentUserPage === 'dashboard.php' ? 'aria-current="page"' : ''; ?>>
    <i class="bi bi-house-door" aria-hidden="true"></i>
    <span>Home</span>
  </a>
  <a href="messages.php" <?php echo $currentUserPage === 'messages.php' ? 'aria-current="page"' : ''; ?>>
    <i class="bi bi-chat-dots" aria-hidden="true"></i>
    <span>Messages</span>
  </a>
  <a href="settings.php" <?php echo in_array($currentUserPage, ['settings.php', 'update_password.php'], true) ? 'aria-current="page"' : ''; ?>>
    <i class="bi bi-gear" aria-hidden="true"></i>
    <span>Settings</span>
  </a>
  <a href="update_profile_picture.php" <?php echo $currentUserPage === 'update_profile_picture.php' ? 'aria-current="page"' : ''; ?>>
    <i class="bi bi-person" aria-hidden="true"></i>
    <span>Profile</span>
  </a>
</nav>
