document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
  toggle.addEventListener('click', () => {
    const input = document.getElementById(toggle.dataset.passwordToggle);
    if (!input) {
      console.error('Password visibility toggle target was not found.');
      return;
    }

    const isVisible = input.type === 'password';
    input.type = isVisible ? 'text' : 'password';
    toggle.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
    toggle.setAttribute('aria-pressed', String(isVisible));
    toggle.querySelector('i').className = isVisible ? 'bi bi-eye-slash' : 'bi bi-eye';
  });
});
