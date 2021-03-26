<?php
header('Access-Control-Allow-Origin: *');
require('../vendor/autoload.php');

$app = new Silex\Application();
$app['debug'] = true;

$app->get('/', function() use($app) {
  return "Hello world from index page!";
});


$app->get('/random', function() use($app) {
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
    return json_encode($imageArray);
  }else{
    return "No results";
  }
  $conn->close();
});

$app->post('/scrape', function() use($app) {
  /* Replace space instances with %20*/
	$urlBase = 'https://www.emuparadise.me/roms/search.php?query=';
	$json = file_get_contents('php://input');
	$gameName = json_decode($json)->name;
	$searchUrl = $urlBase . $gameName;
	$filteredUrl = str_replace(" ", "%20", $searchUrl);
	/*Check if the url does work for what ever reason */
	 $romDivArr = array();
	if(!filter_var($filteredUrl, FILTER_VALIDATE_URL)===false) {
  			$page = scrapeRomList($filteredUrl);
  			/* Where to start the scrape*/
  			$start='<span style="color:#aaa;line-height:2em;text-align:left;">';
  			$end='</span>';
  			$result = fetchdata($page, $start, $end);	
  			$start =0;
  			$cur = substr($result,$start);
  			$start = strpos($cur, '<div class="roms"');
  			$end = strpos($cur, '</div>');
  			/* Constant how man chars from < of </div> 6 b/c of whitespace i guess*/
  			$offset = 6;
  			$i=0;
  			while($start!==false && $end!==false){
  					$elemLen = $end-$start; 
  					$element = substr($cur, $start,$elemLen+$offset);
  					$romDivArr[] = $element;
  					$cur =substr($cur, $end+$offset);
  					$start = strpos($cur, '<div class="roms"');
  					$end = strpos($cur, '</div>');	
 				} 
 			/* Get all the <a data-filter to </a>*/
 			if(!empty($romDivArr)){
 				$linkArr = array();
 				$nameArr= array();
 				$contentArray = array();
 				$posStart='<a data-filter';
 				$posEnd='</a>';
 				$posStart2= 'href="';
 				$posEnd2 = '"';
 				$i=0;
 				foreach ($romDivArr as $value) {
 					/* Get name from link as well*/
 					$currentResult = fetchdata($value,$posStart,$posEnd);
 					$linkData = fetchdata($currentResult,$posStart2,$posEnd2);
 					//echo $linkData . '<br/>';
 					$temp = substr($linkData, 1);
 					//echo $temp . '<br>';
 					$len = strpos($temp, '/');
 					//echo $len .'<br>';
 					$temp2 = substr($temp, $len+1);
 					$unregName = substr($temp2, 0,strpos($temp2, '/'));
 					$regName = str_replace("_", " ", $unregName);
 					$nameArr[] = $regName;
 					$linkArr[] = 'https://www.emuparadise.me'.$linkData.'-download';
 					$contentArray[$i]=array();
 					$contentArray[$i]['name']=$regName;
 					$contentArray[$i]['link']='https://www.emuparadise.me'.$linkData.'-download';
 					$i++;
 				}
 				return json_encode($contentArray);
 		}else{
 			echo json_encode("nil");
 			die();
 		}
	}else{
  		die();
	}

});

function scrapeRomList($url){
  /* Look for <div class="roms"> then <a href where class!=sysname*/
	$output = file_get_contents($url);
	return $output;
}

function fetchdata($data,$start,$end){
  //echo $data;
  $data = stristr($data, $start);
  //echo $data;
  $data = substr($data, strlen($start));
  //echo $data;
  $stop = stripos($data, $end);
  $data = substr($data, 0,$stop);
  return $data;
}





$app->run();
?>