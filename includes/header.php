<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$pageTitle = [
  'index.php' => 'Home',
  'about.php' => 'About',
  'about copy.php' => 'About',
  'services.php' => 'Services',
  'blog.php' => 'Blog',
  'portfolio.php' => 'Portfolio',
  'contact.php' => 'Contact',
  'signup.php' => 'Sign Up',
  'login.php' => 'Login',
  'forgot_password.php' => 'Forgot Password',
][$currentPage] ?? 'Y-Global';
?>
<header id="header" class="header d-flex align-items-center sticky-top">
  <div class="public-header-container">
    <a href="index.php" class="logo d-flex align-items-center me-auto">
      <span class="public-header-mark"><i class="bi bi-globe2" aria-hidden="true"></i></span>
      <span class="public-header-brand"><strong>Y-Global</strong><small>System Solution</small></span>
    </a>

    <nav id="navmenu" class="navmenu">
      <ul>
        <li><a href="index.php" <?php echo $currentPage === 'index.php' ? 'class="active"' : ''; ?>>Home</a></li>
        <li><a href="about.php" <?php echo $currentPage === 'about.php' ? 'class="active"' : ''; ?>>About</a></li>
        <li><a href="services.php" <?php echo $currentPage === 'services.php' ? 'class="active"' : ''; ?>>Services</a></li>
        <li><a href="blog.php" <?php echo $currentPage === 'blog.php' ? 'class="active"' : ''; ?>>Blog</a></li>
        <li><a href="portfolio.php" <?php echo $currentPage === 'portfolio.php' ? 'class="active"' : ''; ?>>Portfolio</a></li>
        <li><a href="contact.php" <?php echo $currentPage === 'contact.php' ? 'class="active"' : ''; ?>>Contact</a></li>
        <li><a href="signup.php" class="public-header-cta<?php echo $currentPage === 'signup.php' ? ' active' : ''; ?>">Sign Up</a></li>
        <li><a href="login.php" <?php echo $currentPage === 'login.php' ? 'class="active"' : ''; ?>>Login</a></li>
      </ul>
      <button type="button" class="mobile-nav-toggle d-xl-none bi bi-list" aria-label="Open navigation menu" aria-controls="public-sidebar" aria-expanded="false"></button>
    </nav>
  </div>
</header>
<button type="button" class="public-sidebar-backdrop" aria-label="Close navigation menu"></button>
<aside class="public-sidebar" id="public-sidebar" aria-label="Website navigation">
  <div class="public-sidebar-heading">
    <span class="public-sidebar-mark"><i class="bi bi-globe2" aria-hidden="true"></i></span>
    <div>
      <strong>Y-Global</strong>
      <span><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <button type="button" class="public-sidebar-close" aria-label="Close navigation menu">
      <i class="bi bi-x-lg" aria-hidden="true"></i>
    </button>
  </div>
  <nav class="public-sidebar-nav" aria-label="Main pages">
    <a href="index.php" <?php echo $currentPage === 'index.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-house-door" aria-hidden="true"></i><span>Home</span>
    </a>
    <a href="about.php" <?php echo in_array($currentPage, ['about.php', 'about copy.php'], true) ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-person" aria-hidden="true"></i><span>About</span>
    </a>
    <a href="services.php" <?php echo $currentPage === 'services.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-grid" aria-hidden="true"></i><span>Services</span>
    </a>
    <a href="blog.php" <?php echo $currentPage === 'blog.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-journal-text" aria-hidden="true"></i><span>Blog</span>
    </a>
    <a href="portfolio.php" <?php echo $currentPage === 'portfolio.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-briefcase" aria-hidden="true"></i><span>Portfolio</span>
    </a>
    <a href="contact.php" <?php echo $currentPage === 'contact.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-chat-dots" aria-hidden="true"></i><span>Contact</span>
    </a>
    <a href="signup.php" <?php echo $currentPage === 'signup.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-person-plus" aria-hidden="true"></i><span>Sign Up</span>
    </a>
    <a href="login.php" <?php echo $currentPage === 'login.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i><span>Login</span>
    </a>
    <a href="forgot_password.php" <?php echo $currentPage === 'forgot_password.php' ? 'aria-current="page"' : ''; ?>>
      <i class="bi bi-key" aria-hidden="true"></i><span>Forgot Password</span>
    </a>
  </nav>
</aside>
<script src="assets/js/public-sidebar.js?v=1" defer></script>
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('./service-worker.js').catch((error) => {
        console.error('Service worker registration failed:', error);
      });
    });
  }
</script>
