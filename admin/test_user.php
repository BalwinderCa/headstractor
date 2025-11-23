<?php
require_once('../config.php');

echo "=== Basic Tests ===<br>";
echo "Session save handler: " . ini_get('session.save_handler') . "<br>";
echo "Session save path: " . ini_get('session.save_path') . "<br>";
echo "Session cookie secure: " . ini_get('session.cookie_secure') . "<br>";

// Test basic session
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Session status: " . session_status() . "<br>";

$_SESSION['test'] = 'working';
echo "Test session set<br>";

// Test database connection
try {
    $db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
    if ($db->connect_error) {
        echo "DB Connection failed: " . $db->connect_error . "<br>";
    } else {
        echo "DB Connection: SUCCESS<br>";
        
        // Check if oc_session table exists and has data
        $result = $db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "session");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "Sessions in database: " . $row['count'] . "<br>";
        }
        
        // Check user table
        $result = $db->query("SELECT username, status FROM " . DB_PREFIX . "user WHERE username = 'admin'");
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            echo "Admin user found - Status: " . $user['status'] . "<br>";
        } else {
            echo "Admin user NOT found<br>";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>