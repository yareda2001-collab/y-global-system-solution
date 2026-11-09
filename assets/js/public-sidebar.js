(() => {
  const toggle = document.querySelector('.public-sidebar ~ .mobile-nav-toggle') ||
    document.querySelector('#header .mobile-nav-toggle');
  const backdrop = document.querySelector('.public-sidebar-backdrop');
  const closeButton = document.querySelector('.public-sidebar-close');

  const closeMenu = () => {
    document.body.classList.remove('mobile-nav-active');
    toggle?.classList.remove('bi-x');
    toggle?.classList.add('bi-list');
    toggle?.setAttribute('aria-label', 'Open navigation menu');
    toggle?.setAttribute('aria-expanded', 'false');
  };

  if (toggle) {
    toggle.addEventListener('click', (event) => {
      event.stopImmediatePropagation();
      const isOpen = document.body.classList.toggle('mobile-nav-active');
      toggle.classList.toggle('bi-list', !isOpen);
      toggle.classList.toggle('bi-x', isOpen);
      toggle.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');
      toggle.setAttribute('aria-expanded', String(isOpen));
    }, true);
  }

  backdrop?.addEventListener('click', closeMenu);
  closeButton?.addEventListener('click', () => {
    closeMenu();
    toggle?.focus();
  });
  document.querySelectorAll('.public-sidebar-nav a').forEach((link) => {
    link.addEventListener('click', closeMenu);
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && document.body.classList.contains('mobile-nav-active')) {
      closeMenu();
      toggle?.focus();
    }
  });
})();
