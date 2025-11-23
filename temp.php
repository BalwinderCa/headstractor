<?php
// Define your IP address that will be allowed access
$allowedIP = '2604:3d09:e179:8530:2db7:b88a:b24:cf4f'; // Replace with your actual IP address

// Get the visitor's IP address
$visitorIP = $_SERVER['REMOTE_ADDR'];

// Check if the visitor's IP matches the allowed IP
if ($visitorIP === $allowedIP) {
    // If IP matches, show the PHP info
    phpinfo();
} else {
    // If IP doesn't match, deny access
    header('HTTP/1.1 403 Forbidden');
    echo '<h1>403 Forbidden</h1>';
    echo '<p>You are not authorized to access this page.</p>';
    exit();
}
?>