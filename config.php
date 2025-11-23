<?php
// HTTP
define('HTTP_SERVER', 'https://www.headstractor.com.au/');

// HTTPS
define('HTTPS_SERVER', 'https://www.headstractor.com.au/');

// DIR
define('DIR_APPLICATION', '/home/onlineorders/public_html/catalog/');
define('DIR_SYSTEM', '/home/onlineorders/public_html/system/');
define('DIR_IMAGE', '/home/onlineorders/public_html/image/');
define('DIR_STORAGE', '/home/onlineorders/storage/');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/theme/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_MODIFICATION', DIR_STORAGE . 'modification/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');

// DB
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', 'localhost');
define('DB_USERNAME', 'headstractor_user');
define('DB_PASSWORD', 'eJhwbNtAKY4LnzDygQqd5m');
define('DB_DATABASE', 'headstractor_latest');
define('DB_PORT', '3306');
define('DB_PREFIX', 'oc_');

// OpenCart API
define('OPENCART_SERVER', 'https://www.opencart.com/');

// Session Configuration
define('SESSION_DRIVER', 'db');
define('SESSION_LIFETIME', 7200);
define('SESSION_SECURE', true);
define('SESSION_HTTP_ONLY', true);
define('SESSION_SAME_SITE', 'Lax');

// Error Reporting (Comment out in production)
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// Session Debugging (Comment out in production)
// error_log("Session Debug - Start");
// error_log("Session Save Path: " . session_save_path());
// error_log("Session ID: " . session_id()); 