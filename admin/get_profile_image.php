<?php
session_start();
require_once 'connect.php'; // Your database connection file

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Verify admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("HTTP/1.1 403 Forbidden");
    header("Content-Type: image/png");
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    exit;
}

// Configure paths
$base_dir = $_SERVER['DOCUMENT_ROOT'];
$uploads_dir = $base_dir . '/uploads/profile_pictures/';
$default_image = $base_dir . '/assets/img/default-profile.png';

// Validate and sanitize input
$requested_image = isset($_GET['image']) ? trim($_GET['image']) : '';

// Security checks
if (empty($requested_image) || preg_match('/[^a-zA-Z0-9_\-\.]/', $requested_image) || strpos($requested_image, '..') !== false) {
    serve_default_image($default_image);
}

// Database validation
$valid_image = false;
try {
    $stmt = $conn->prepare("SELECT id FROM users WHERE profile_picture = ?");
    $stmt->bind_param("s", $requested_image);
    $stmt->execute();
    $stmt->store_result();
    $valid_image = ($stmt->num_rows > 0);
    $stmt->close();
} catch (Exception $e) {
    error_log("Database error in get_profile_image: " . $e->getMessage());
    serve_default_image($default_image);
}

if (!$valid_image) {
    serve_default_image($default_image);
}

// Construct full image path
$image_path = $uploads_dir . $requested_image;

// Security checks for the file
if (!file_exists($image_path) || !is_readable($image_path)) {
    error_log("Profile image not accessible: " . $image_path);
    serve_default_image($default_image);
}

// Verify this is actually an image
$mime_type = mime_content_type($image_path);
if (strpos($mime_type, 'image/') !== 0) {
    error_log("Invalid MIME type for image: " . $mime_type);
    serve_default_image($default_image);
}

// Security check for image contents
if (!getimagesize($image_path)) {
    error_log("Invalid image file: " . $image_path);
    serve_default_image($default_image);
}

// Set caching headers (1 day)
header("Cache-Control: max-age=86400, public, must-revalidate");
header("Pragma: cache");
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($image_path));

// Serve the image
readfile($image_path);
exit;

// Helper function to serve default image
function serve_default_image($default_path) {
    if (file_exists($default_path) && is_readable($default_path)) {
        header('Content-Type: image/png');
        header('Content-Length: ' . filesize($default_path));
        readfile($default_path);
    } else {
        // Ultimate fallback - 1x1 transparent pixel
        header('Content-Type: image/png');
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    }
    exit;
}
?>