<?php
if($_SERVER['REMOTE_ADDR'] == '186.31.92.92' && false) {
    require_once('config.php');
    $mysqli = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

    // Check connection
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    // Get all repeated entries with their counts
    $query = "SELECT * FROM oc_seo_url";
    $result = $mysqli->query($query);

    if (!$result) {
        die("Query failed: " . $mysqli->error);
    }

    // Fetch results into an array
    $data = array();
    $queries = [];
    while ($row = $result->fetch_assoc()) {

        $row['keyword_new'] = trim($row['keyword']);
        $row["keyword_new"] = preg_replace('/\s+/', ' ', $row['keyword_new']);
        $row["keyword_new"] = str_replace(' ', '-', $row['keyword_new']);
        $row["keyword_new"] = strtolower($row["keyword_new"]);
        $row['keyword_new'] = str_replace(['&quot;', 'andamp', 'andquot'], '', $row['keyword_new']);
        $row["keyword_new"] = preg_replace('/[^a-z0-9-]+/', '', $row["keyword_new"]);        
        $row["keyword_new"] = preg_replace('/-+/', '-', $row["keyword_new"]); 
        $row['keyword_new'] = trim($row['keyword_new'], '-');  
        
        $data[] = $row;

        if($row['keyword_new'] != $row['keyword']) {
            $query = "UPDATE oc_seo_url SET keyword = '" . $row['keyword_new']  . "' WHERE seo_url_id = " . $row['seo_url_id'];
            $mysqli->query($query);
            $queries[] = $query;
        }
        
    }

  

    // Close the database connection
    $mysqli->close();

    // Output the results in JSON format
    header('Content-Type: application/json');
    echo json_encode(['data' => $data, 'queries' => $queries], JSON_PRETTY_PRINT);
}
?>
