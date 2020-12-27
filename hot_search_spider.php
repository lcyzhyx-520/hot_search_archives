<?php

include "simple_html_dom.php";

$html = new simple_html_dom();

$NUM_OF_ATTEMPTS = 5;
$attempts = 0;

$hot_search_list = [];
do {
    try
    {
        $html->load_file('https://www.enlightent.cn/research/top/getWeiboRank.do?type=realTimeHotSearchList');
        $hot_search_list = json_decode($html, true);
    } catch (Throwable $t) {
        $attempts++;
        continue;
    }
    break;

} while($attempts < $NUM_OF_ATTEMPTS);

$trends = [];

foreach ($hot_search_list as $value) {
    $trend['ranking'] = $value['ranking'];
    $trend['title'] = $value['keywords'];
    $trend['views'] = $value['searchNums'];
    $trends[] = $trend;
}

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

$conn->query("SET NAMES utf8");

$table_name = "hot_search";

$create_table_sql = "CREATE TABLE IF NOT EXISTS $table_name (
    id INT(15) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
    title VARCHAR(50) NOT NULL,
    ranking INT(3) NOT NULL,
    views INT(15) NOT NULL,
    epoch_time INT(15) NOT NULL
)";

$conn->query($create_table_sql) 
    or die('Failed to create table'.$conn->error);

$time = time();
foreach ($trends as $trend) {
    $title = $trend['title'];
    $ranking = (int)$trend['ranking'];
    $views = (int)$trend['views'];
    $insert_data_sql = "INSERT INTO $table_name (title, ranking, views, epoch_time) VALUES ('$title', $ranking, $views, $time)";
    $conn->query($insert_data_sql) or die('Failed to insert data'.$conn->error);
}

$conn->close();
?>

