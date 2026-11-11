<?php
require_once __DIR__ . '/../includes/load-env.php';

$servername = getenv('HOSTING_DB_HOST');
$username = getenv('HOSTING_DB_USER');
$password = getenv('HOSTING_DB_PASS');
$database = getenv('HOSTING_DB_NAME');

$conn = null;
$db_config_error = null;

if ($servername === false || $username === false || $password === false || $database === false) {
    $db_config_error = 'Hosting database configuration is incomplete. Set the HOSTING_DB_* environment variables.';
    error_log($db_config_error);
} else {
    $conn = mysqli_connect($servername, $username, $password, $database);
    if (!$conn) {
        $db_config_error = 'Hosting database connection failed.';
        error_log('Hosting database connection failed: ' . mysqli_connect_error());
    }
}
?>
