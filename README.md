# y-global-system-solution

## Install as an app

The public and signed-in user pages can be installed as a Progressive Web App
when served over HTTPS. Upload the manifest endpoint, `manifest.json`,
`service-worker.js`, `offline.html`, the PWA icons under `assets/img/`, and
`assets/js/pwa-install.js` together with the site. Also upload the updated page
files and shared navigation includes so the install button and current manifest
version are present on the hosted pages.

After uploading, verify that `manifest.php`, `service-worker.js`, `offline.html`,
both PWA icons, and `assets/js/pwa-install.js` load successfully over HTTPS.
Reload the site after the service worker activates; if an older version remains,
clear the site's cached data/service worker and reload. On Chrome/Edge, use
**Install app** in the account or public navigation menu; if the browser doesn't
offer an automatic prompt, use its menu's **Install app** or **Add to Home
Screen** action. On iPhone/iPad, open the site in Safari and use **Share → Add
to Home Screen**. Browser install prompts are controlled by each browser and
can only appear on supported browsers over HTTPS when the manifest and service
worker are valid.

## Local configuration

Copy `.env.example` to `.env` in the project root and fill in the values for your
own environment. Do not commit `.env`; it contains credentials. The application
loads it through `includes/load-env.php`, while variables already supplied by
the server environment take precedence. The root `.htaccess` denies web access
to `.env` on Apache; on hosting, keep `.env` outside the document root whenever
possible.

### XAMPP

1. Copy `.env.example` to
   `/Applications/XAMPP/xamppfiles/htdocs/yared/.env`.
2. Set `LOCAL_DB_HOST`, `LOCAL_DB_NAME`, `LOCAL_DB_USER`, and `LOCAL_DB_PASS`
   to match your local MySQL database. Use a password-protected database
   account where possible.
3. Set `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` to the credentials for
   your Google OAuth web client. Set `GOOGLE_REDIRECT_URI` to
   `http://localhost/yared/callback.php` and register that exact URI in the
   Google OAuth client configuration.

### PHP hosting

The admin area can use a separate hosting database configuration through
`admin/connect1.php`. Set `APP_DB_PROFILE=hosting` and provide `HOSTING_DB_HOST`,
`HOSTING_DB_NAME`, `HOSTING_DB_USER`, and `HOSTING_DB_PASS` through the host's
protected environment-variable settings. Other application connections still
use `LOCAL_DB_HOST`, `LOCAL_DB_NAME`, `LOCAL_DB_USER`, and `LOCAL_DB_PASS`.
Keep actual values out of the repository and do not upload `.env`. If the
hosting plan does not support protected environment variables, use a
provider-supported private configuration file stored outside the public web
root; do not put credentials in a tracked file.

Configure `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`,
`APP_DB_HOST`, `APP_DB_NAME`, `APP_DB_USER`, `APP_DB_PASS`, `ADMIN_DB_*`, and
`LOCAL_DB_*` in the hosting provider's environment-variable or secrets settings.
If the provider only supports web-server configuration, use its protected
Apache/PHP-FPM environment configuration. Match the OAuth redirect URI exactly
to the deployed callback URL and register it with Google. Do not upload `.env`
to a public document root; if using a file-based setup, keep it outside that
root and adjust the loader path accordingly.
