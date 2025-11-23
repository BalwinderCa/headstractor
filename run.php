<?php

// Configuration
if (is_file('config.php')) {
	require_once('config.php');
}

// Install
if (!defined('DIR_APPLICATION')) {
	header('Location: install/index.php');
	exit;
}


$mysqli = new mysqli( DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE );

$querySeoUrls = "SELECT * FROM oc_seo_url";
$resultSeoUrls = $mysqli->query($querySeoUrls);
$stmt = $mysqli->prepare('UPDATE oc_seo_url SET keyword = ? WHERE seo_url_id = ? LIMIT 1');
while ($rowSeoUrl = $resultSeoUrls->fetch_assoc()) {

    $rowSeoUrl['keyword'] = rtrim($rowSeoUrl['keyword'], '-1');

    $stmt -> bind_param('si', $rowSeoUrl['keyword'], $rowSeoUrl['seo_url_id']);
    $stmt -> execute();

}
$stmt->close();

$mysqli->close();