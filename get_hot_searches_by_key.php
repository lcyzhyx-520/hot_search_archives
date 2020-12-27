<?php

require('./filecache.php');
$cache = new FileCache();

$key = "'%".urldecode($_GET['key'])."%'";
$table_name = "hot_search";
$get_data_sql = "SELECT title, min(epoch_time) as min_time, min(ranking) as min_ranking FROM $table_name WHERE title LIKE $key GROUP BY title ORDER BY min_time DESC";

$cached_val = $cache->get($get_data_sql);

if($cached_val) {
    echo $cached_val;
} else {
    $encoded_results = query($get_data_sql);
    $cache->set($get_data_sql, $encoded_results, 300);
    echo $encoded_results;
}

function query($get_data_sql) {
    $settings = parse_ini_file("config.ini");
    $hostname = $settings['db_hostname'];
    $username = $settings['db_username'];
    $password = $settings['db_password'];
    $database = $settings['db_database'];

    $conn=mysqli_connect($hostname, $username, $password);
    if ($conn->connect_error) {
        die("Connection failure: " . $conn->connect_error);
    }
    $conn->select_db($database) or die("Can\'t use selected db : " . $conn->error);
    $conn->query("SET NAMES 'utf8'");
    $conn->query("SET GLOBAL group_concat_max_len=102400;");
    $conn->query("SET SESSION group_concat_max_len=102400;");

    date_default_timezone_set('PRC'); 
    $results = [];

    $re = $conn->query($get_data_sql);
    if($re) {
        while($row = $re->fetch_assoc()) {
            $result['title'] = $row['title'];
            $result['min_ranking'] = $row['min_ranking'];
            $dateInLocal = date("Y-m-d H:i", $row['min_time']);
            $result['min_time'] = $dateInLocal;
            $results[] = $result;
        };
    }
    $conn->close();
    return json_encode($results);
}

?>