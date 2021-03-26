<?php
header('Access-Control-Allow-Origin: *');
require('../vendor/autoload.php');

$app = new Silex\Application();
$app['debug'] = true;

$app->get('/', function() use($app) {
  echo "Hello world from index page!";
});


$app->get('/random', function() use($app) {
  echo "Hello world from random page!";
});


?>