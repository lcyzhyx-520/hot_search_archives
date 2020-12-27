<?php

require('./filecache.php');
$cache = new FileCache();

$title = '"'.urldecode($_GET['title']).'"';
$table_name = "hot_search";
$get_data_sql = "SELECT title, group_concat(ranking) AS ranks, group_concat(views) as views, group_concat(epoch_time) as dates FROM $table_name WHERE title = $title ORDER BY epoch_time";

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


    $result = $conn->query($get_data_sql);
    $row = $result->fetch_assoc();
    $conn->close();
    return json_encode($row);
}
?>