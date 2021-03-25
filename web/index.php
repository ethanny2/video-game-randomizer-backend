<?php
   header('Access-Control-Allow-Origin: *');

	function scrape($url){
   		$url = str_replace('<i class="fa fa-play</i></a>', '', $url);
   		$url = trim($url);
  		$output = file_get_contents($url);
  		return $output;
	}

 	/*URL to fetch data from */ 
  $url = 'https://gramho.com/profile/playboicarti/279519379';
	if(!filter_var($url, FILTER_VALIDATE_URL)) {
  		json_encode(array('error'=>'invalid_url'));
  		die();
	}else {
		$page = scrape($url);
		$start='<div class="photo">';
		$end = "</div>";
		$result = fetchdata($page, $start, $end);
		//No way to tell if video from result need to follow link
		$hrefStart='<a href="';
		$hrefEnd = '">';
		$followUrl = fetchdata($result,$hrefStart,$hrefEnd);
		/* Rescrape now we can tell if its a video or photo*/
		$page = scrape($followUrl);
		//If there is a <div class="single-photo"> inside is a video</div>
    //If there is a <div class="item"> inside is an img</div>
		$videoStart='<div class="single-photo">';
		$videoEnd = '</div>';
		$isVideo = fetchdata($page,$videoStart,$videoEnd);
    if(empty($isVideo)){
			$photoStart='<div class="item">';
			$photoEnd = '</div>';
			$photo = fetchdata($page,$photoStart,$photoEnd);
			//Whole image tag with alt and src; why not send this to front-end
			echo json_encode(array('pic'=>$photo));
		 }else {
			 	//Whole image tag with alt and src; why not send this to front-end
			echo json_encode(array('vid'=>$isVideo));
		 }
	}

	function fetchdata($data, $start, $end){
	 	$data = stristr($data, $start); // Strip code from startposition to end of code
	 	$data = substr($data, strlen($start)); // Stripping $start
	 	$stop = stripos($data, $end); // Get position of endpoint
	 	$data = substr($data, 0, $stop); // Stripcode from startposition to endposition
	 	return $data; // return the scraped data 
  }

?>

