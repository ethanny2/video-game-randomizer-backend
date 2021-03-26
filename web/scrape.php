<?php
	  header('Access-Control-Allow-Origin: *');
		header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
		header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

	/* See if the data is correctly sent*/
	//echo($_POST['name']);

	function scrapeRomList($url){
		/* Fill in later*/
		/* Look for <div class="roms"> then <a href where class!=sysname*/
		$output = file_get_contents($url);
		return $output;
	}


	/* Replace space instances with %20*/
	$urlBase = 'https://www.emuparadise.me/roms/search.php?query=';
	// $searchUrl = $urlBase . $_POST['name'];
	$json = file_get_contents('php://input');
	$gameName = json_decode($json)->name;
	$searchUrl = $urlBase . $gameName;
	//$searchUrl = $urlBase . 'Xenosaga';
	//$filteredUrl = filter_var($searchUrl,FILTER_SANITIZE_URL);
	$filteredUrl = str_replace(" ", "%20", $searchUrl);
	//echo("filteredUrl: " . $filteredUrl . "<br/>");
	/*Check if the url does work for what ever reason */
	 $romDivArr = array();

	if(!filter_var($filteredUrl, FILTER_VALIDATE_URL)===false) {

  			//echo("its valid url <br/>");
  			$page = scrapeRomList($filteredUrl);
  			//echo $page;
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
  			//echo $cur;
  			$i=0;
  			while($start!==false && $end!==false){
  					$elemLen = $end-$start; 
  					$element = substr($cur, $start,$elemLen+$offset);
  					$romDivArr[] = $element;
  					$cur =substr($cur, $end+$offset);
  					$start = strpos($cur, '<div class="roms"');
  					$end = strpos($cur, '</div>');	
 				} 
  			//echo print_r($romDivArr);
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
 				echo json_encode($contentArray);
 		}else{
 			echo json_encode("nil");
 			die();
 		}
	}else{
		//json_encode(array('error'=>'invalid_url'));
  		//echo('Error parsing the url<br/>');
  		die();
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

/* For the href to the roms a short hand is used
 on site: <a ...... href="/Sony_Playstation_ISOs/Big_Air[U]/36583"
 actual url href="https://www.emuparadise.me/Sony_Playstation_ISOs/Big_Air_[U]/36583"
 so just append the first part
*/

?>


