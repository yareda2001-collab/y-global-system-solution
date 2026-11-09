<?php
require_once __DIR__ . '/includes/load-env.php';

$servername = getenv('LOCAL_DB_HOST');
$username = getenv('LOCAL_DB_USER');
$password = getenv('LOCAL_DB_PASS');
$database = getenv('LOCAL_DB_NAME');

if ($servername === false || $username === false || $password === false || $database === false) {
    error_log('Database configuration is incomplete; set the LOCAL_DB_* environment variables.');
    http_response_code(500);
    exit('Database configuration is incomplete.');
}

// Create connection
$conn = mysqli_connect($servername, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
