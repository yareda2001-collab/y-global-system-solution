(() => {
  const toggle = document.querySelector('.user-portal-page .mobile-nav-toggle');
  const backdrop = document.querySelector('.user-sidebar-backdrop');
  const closeButton = document.querySelector('.user-sidebar-close');
  const closeMenu = () => {
    document.body.classList.remove('mobile-nav-active');
    if (toggle) {
      toggle.classList.remove('bi-x');
      toggle.classList.add('bi-list');
      toggle.setAttribute('aria-label', 'Open account menu');
      toggle.setAttribute('aria-expanded', 'false');
    }
  };

  if (toggle) {
    toggle.setAttribute('role', 'button');
    toggle.setAttribute('tabindex', '0');
    toggle.setAttribute('aria-label', 'Open account menu');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.addEventListener('click', () => {
      const isOpen = document.body.classList.contains('mobile-nav-active');
      toggle.setAttribute('aria-label', isOpen ? 'Close account menu' : 'Open account menu');
      toggle.setAttribute('aria-expanded', String(isOpen));
    });
    toggle.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        toggle.click();
      }
    });
  }

  backdrop?.addEventListener('click', closeMenu);
  closeButton?.addEventListener('click', () => {
    closeMenu();
    toggle?.focus();
  });
  document.querySelectorAll('.user-sidebar-nav a').forEach((link) => {
    link.addEventListener('click', closeMenu);
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && document.body.classList.contains('mobile-nav-active')) {
      closeMenu();
      toggle?.focus();
    }
  });
})();
