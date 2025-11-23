<?php
if($_SERVER['REMOTE_ADDR'] == '186.31.92.92') {


    $servername = "localhost";
    $username = "root";
    $password = "1dUMldVfX96UU4LQ";
    $dbname = "headsdev";

    $databases = array();

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    echo '<h2>SHOW DATABASES</h2>';
    if($stmt = $conn->query("SHOW DATABASES")){
        echo "No of records : ".$stmt->num_rows."<br>";
        while ($row = $stmt->fetch_assoc()) {
            // echo $row['Database']."<br>";
            $databases[] = $row['Database'];
        }
    }else{
        echo $conn->error;
    }

    foreach($databases as $database) {
        if($database == 'headsdev' || $database == 'headstractor_prev') {
            $conn->query("USE $database");
            if ($stmt = $conn->query("SHOW TABLES")) {
                // echo "No of records : " . $stmt->num_rows . "<br>";
                $database_tables[$database] = array();
                while ($row = $stmt->fetch_assoc()) {
                    //var_dump($row);
                    // echo $row['Tables_in_' . $database] . "<br>";
                    $database_tables[$database][$row['Tables_in_' . $database]] = $row['Tables_in_' . $database];
                }
            } else {
                echo $conn->error;
            }
        }
    }

/*

    echo '<pre>';
    var_dump($databases);
    echo '</pre>';

    echo '<pre>';
    var_dump($database_tables);
    echo '</pre>';
*/

//    foreach($database_tables as $database => $tables) {

        foreach($database_tables['headstractor_prev'] as $table) {
            echo $table . ' : ';
            if(isset($database_tables['headsdev']['oc_' . $table])) {
                echo $database_tables['headsdev']['oc_' . $table] . "<br><br>";


                $conn->query("USE headsdev");
                if($stmt = $conn->query("SHOW CREATE TABLE oc_$table")){
                    while ($row = $stmt->fetch_assoc()) {
                        // echo $row['Database']."<br>";
                        echo '<pre>';
                        var_dump($row["Create Table"]);
                        echo '</pre>';
                    }
                }else{
                    echo $conn->error;
                }

                $conn->query("USE headstractor_prev");
                if($stmt = $conn->query("SHOW CREATE TABLE $table")){
                    while ($row = $stmt->fetch_assoc()) {
                        // echo $row['Database']."<br>";
                        echo '<pre>';
                        var_dump($row["Create Table"]);
                        echo '</pre>';
                    }
                }else{
                    echo $conn->error;
                }



            } else {
                echo '<strong>not exists</strong>' . '<br>';
            }
        }
//    }


}