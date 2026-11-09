(() => {
  const toggle = document.querySelector('.admin-app-page .mobile-nav-toggle');
  const backdrop = document.querySelector('.admin-sidebar-backdrop');
  const closeButton = document.querySelector('.admin-sidebar-close');

  const closeMenu = () => {
    document.body.classList.remove('mobile-nav-active');
    toggle?.classList.remove('bi-x');
    toggle?.classList.add('bi-list');
    toggle?.setAttribute('aria-label', 'Open admin menu');
    toggle?.setAttribute('aria-expanded', 'false');
  };

  if (toggle) {
    toggle.setAttribute('aria-controls', 'admin-sidebar');
    toggle.setAttribute('aria-label', 'Open admin menu');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.addEventListener('click', (event) => {
      event.stopImmediatePropagation();
      const isOpen = document.body.classList.toggle('mobile-nav-active');
      toggle.classList.toggle('bi-list', !isOpen);
      toggle.classList.toggle('bi-x', isOpen);
      toggle.setAttribute('aria-label', isOpen ? 'Close admin menu' : 'Open admin menu');
      toggle.setAttribute('aria-expanded', String(isOpen));
    }, true);
  }

  backdrop?.addEventListener('click', closeMenu);
  closeButton?.addEventListener('click', () => {
    closeMenu();
    toggle?.focus();
  });
  document.querySelectorAll('.admin-sidebar-nav a').forEach((link) => {
    link.addEventListener('click', closeMenu);
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && document.body.classList.contains('mobile-nav-active')) {
      closeMenu();
      toggle?.focus();
    }
  });
})();
