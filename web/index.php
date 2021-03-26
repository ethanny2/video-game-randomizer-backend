<?php
// echo "Hello World! This is the index page!";
$url = parse_url($_SERVER['REQUEST_URL'], PHP_URL_PATH);
$uri = explode( '/', $uri );
echo $uri;
?>

