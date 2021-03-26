<?php
header('Access-Control-Allow-Origin: *');
//Get Heroku ClearDB connection information
$cleardb_url = parse_url(getenv("CLEARDB_DATABASE_URL"));
$cleardb_server = $cleardb_url["host"];
$cleardb_username = $cleardb_url["user"];
$cleardb_password = $cleardb_url["pass"];
$cleardb_db = substr($cleardb_url["path"],1);
$active_group = 'default';
$query_builder = TRUE;
// Connect to DB
$conn = new mysqli($cleardb_server, $cleardb_username, $cleardb_password, $cleardb_db);
// $conn = new mysqli($servername, $username, $password, $dbname, $port);
$randomGameRows = "SELECT * FROM giant_bomb_games WHERE `cover` IS NOT NULL ORDER BY RAND() LIMIT 20";
$result = $conn->query($randomGameRows);
$imageArray = [];
if($result->num_rows > 0){
  while($row = $result->fetch_assoc()){
    array_push($imageArray, $row);
  }
  // echo var_dump($imageArray);
  echo json_encode($imageArray);
}
$conn->close();
?>