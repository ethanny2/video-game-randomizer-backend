<?php
// echo "Hello World! This is the index page!";
$url = parse_url($_SERVER['REQUEST_URL'], PHP_URL_PATH);
$uri = explode( '/', $uri );
$restAction  = end($uri);
if($uri){
  if(str_contains($restAction, "random")){
    echo "RANDOM";
  }else if(str_contains($restAction, "query")){
    echo "QUERY";
  }else if(str_contains($restAction, "scrape")){
    echo "SCRAPE";
  }
}
?>

