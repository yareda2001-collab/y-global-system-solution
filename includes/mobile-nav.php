<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="mobile-app-nav" aria-label="Mobile navigation">
  <a href="index.php" <?php echo $currentPage === 'index.php' ? 'aria-current="page"' : ''; ?>>
    <i class="bi bi-house-door" aria-hidden="true"></i>
    <span>Home</span>
  </a>
  <a href="about.php" <?php echo in_array($currentPage, ['about.php', 'about copy.php'], true) ? 'aria-current="page"' : ''; ?>>
    <i class="bi bi-person" aria-hidden="true"></i>
    <span>About</span>
  </a>
  <a href="services.php" <?php echo $currentPage === 'services.php' ? 'aria-current="page"' : ''; ?>>
    <i class="bi bi-grid" aria-hidden="true"></i>
    <span>Services</span>
  </a>
  <a href="contact.php" <?php echo $currentPage === 'contact.php' ? 'aria-current="page"' : ''; ?>>
    <i class="bi bi-chat-dots" aria-hidden="true"></i>
    <span>Contact</span>
  </a>
</nav>
