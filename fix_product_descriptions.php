<?php

ini_set('max_execution_time', 10800);
ini_set('max_input_time', 0);
ini_set('session.gc_maxlifetime', 10800);
ini_set('memory_limit', '4096M');

if($_SERVER['REMOTE_ADDR'] == '186.31.92.92' && false) {
    require_once('config.php');
    $mysqli = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

    // Check connection
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    // Get all repeated entries with their counts
    $query = "SELECT product_id, language_id, description FROM oc_product_description WHERE description LIKE '%http%'";

    $result = $mysqli->query($query);

    if (!$result) {
        die("Query failed: " . $mysqli->error);
    }

    // Fetch results into an array
    $data = array();
    $urlPattern = '/\bhttp[s]?:\/\/[^()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|\/))/';
    $searchDirectory = "/home/onlineorders/public_html";
    while ($row = $result->fetch_assoc()) {


        
        // Find and print URLs
        $row["urls"] = [];
        $row["urls_new"] = [];
        if (preg_match_all($urlPattern, $row["description"], $matches)) {
            foreach ($matches[0] as $url) {
       
                $url = substr($url, 0, strpos($url, '&quot;'));
                $newUrl = $url;
                $filename = basename($url);
                $filename = str_replace(' ','20', $filename);

                $findCommand = "find " . escapeshellarg($searchDirectory) . " -type f -name " . escapeshellarg($filename) . " -print -quit";

                $output = null;
                $return_var = null;
                $filepath = null;
                exec($findCommand, $output, $return_var);

                if(!empty($output[0])) {
                    $filepath = $output[0];
                    $newUrl = str_replace("/home/onlineorders/public_html/", "https://www.headstractor.com.au/", $filepath);
                }

                $row["description_new"] = str_replace($url, $newUrl, $row["description"]);

               
                array_push($row["urls"], $url);
                array_push($row["urls_new"], $newUrl);
            }
        }

       $updateQuery = "UPDATE oc_product_description SET description = ? WHERE product_id = ? AND language_id = ?";
       $stmt = $mysqli->prepare($updateQuery);
       $stmt->bind_param('sii', $row["description_new"], $row["product_id"], $row["language_id"]);
       $stmt->execute();

       $data[] = $row;
        
    }

  

    // Close the database connection
    $mysqli->close();

    // Output the results in JSON format
    header('Content-Type: application/json');
    echo json_encode(['data' => $data, 'queries' => $queries], JSON_PRETTY_PRINT);
}
?>
