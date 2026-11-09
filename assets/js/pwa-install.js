(() => {
  const installButton = document.querySelector('#install-app');
  if (!installButton) return;

  const isStandalone = () =>
    window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;

  if (isStandalone()) return;

  installButton.hidden = false;
  let installPrompt = null;

  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPrompt = event;
  });

  installButton.addEventListener('click', async () => {
    if (!installPrompt) {
      const isAppleMobile = /iPhone|iPad|iPod/i.test(navigator.userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
      const instructions = isAppleMobile
        ? 'To install, tap the Share button in Safari, then choose Add to Home Screen.'
        : 'To install, open your browser menu and choose Install app or Add to Home Screen.';
      window.alert(instructions);
      return;
    }

    installPrompt.prompt();
    const choice = await installPrompt.userChoice;
    if (choice.outcome === 'accepted') {
      installButton.hidden = true;
    }
    installPrompt = null;
  });

  window.addEventListener('appinstalled', () => {
    installButton.hidden = true;
    installPrompt = null;
  });

  if ('serviceWorker' in navigator) {
    const manifestLink = document.querySelector('link[rel="manifest"]');
    if (manifestLink) {
      const serviceWorkerUrl = new URL('service-worker.js', manifestLink.href);
      window.addEventListener('load', () => {
        navigator.serviceWorker.register(serviceWorkerUrl).catch((error) => {
          console.error('Service worker registration failed:', error);
        });
      });
    }
  }
})();
