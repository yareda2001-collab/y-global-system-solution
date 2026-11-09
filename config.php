<?php
require_once __DIR__ . '/includes/load-env.php';

$environmentValue = static function (string $name, string $default = ''): string {
    $value = getenv($name);
    return $value === false ? $default : $value;
};

define('DB_HOST', $environmentValue('APP_DB_HOST'));
define('DB_NAME', $environmentValue('APP_DB_NAME'));
define('DB_USER', $environmentValue('APP_DB_USER'));
define('DB_PASS', $environmentValue('APP_DB_PASS'));

define('GOOGLE_CLIENT_ID', $environmentValue('GOOGLE_CLIENT_ID'));
define('GOOGLE_CLIENT_SECRET', $environmentValue('GOOGLE_CLIENT_SECRET'));
define('GOOGLE_REDIRECT_URI', $environmentValue('GOOGLE_REDIRECT_URI', 'http://localhost/yared/callback.php'));
define('GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USERINFO_URL', 'https://www.googleapis.com/oauth2/v3/userinfo');
