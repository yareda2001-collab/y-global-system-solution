# y-global-system-solution

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

Configure `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`,
`APP_DB_HOST`, `APP_DB_NAME`, `APP_DB_USER`, `APP_DB_PASS`, `ADMIN_DB_*`, and
`LOCAL_DB_*` in the hosting provider's environment-variable or secrets settings.
If the provider only supports web-server configuration, use its protected
Apache/PHP-FPM environment configuration. Match the OAuth redirect URI exactly
to the deployed callback URL and register it with Google. Do not upload `.env`
to a public document root; if using a file-based setup, keep it outside that
root and adjust the loader path accordingly.
