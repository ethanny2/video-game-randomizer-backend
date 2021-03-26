<?php
header('Access-Control-Allow-Origin: *');
require ('../vendor/autoload.php');

$app = new Silex\Application();
$app['debug'] = true;

//Get Heroku ClearDB connection information
$cleardb_url = parse_url(getenv("CLEARDB_DATABASE_URL"));
$cleardb_server = $cleardb_url["host"];
$cleardb_username = $cleardb_url["user"];
$cleardb_password = $cleardb_url["pass"];
$cleardb_db = substr($cleardb_url["path"], 1);
$active_group = 'default';
$query_builder = true;
// Connect to DB
$conn = new mysqli($cleardb_server, $cleardb_username, $cleardb_password, $cleardb_db);

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => 'php://stderr',
  ));

$app->get('/random', function () use ($app) {   
    global $conn;
    // $randomGameRows = "SELECT * FROM giant_bomb_games WHERE `cover` IS NOT NULL ORDER BY RAND() LIMIT 20";
    //Table is around 20MB order by RAND() is getting exponentially slower and may timeout
    $randomGameRows = "SELECT *
    FROM giant_bomb_games AS r1 JOIN
         (SELECT CEIL(RAND() *
                       (SELECT MAX(id)
                          FROM random)) AS id)
          AS r2
   WHERE r1.id >= r2.id
   ORDER BY r1.id ASC
   LIMIT 20";
    $result = $conn->query($randomGameRows);
    $app['monolog']->debug('Testing the Monolog logging.');
    $app['monolog']->info(sprintf("Number rows '%d'.", $result->num_rows));
    if ($result->num_rows > 0) {
        $imageArray = [];
        while ($row = $result->fetch_assoc()) {
            array_push($imageArray, $row);
            $app['monolog']->info(sprintf("pushed game with name '%s' into imageArray.", $row['name']));
            // $imageArray[] = $row;
        }
        $app['monolog']->info(sprintf("imageArray length is '%d'.", count($imageArray)));
        return json_encode($imageArray);
    }
    // $conn->close();
});

$app->get('/', function () use ($app) {
    return "Hello world from index page!";
});

$app->post('/query', function () use ($app) {
    global $conn;    // Connect to DB
    $json = file_get_contents('php://input');
    $reqObj = json_decode($json);
    $platforms;
    $genres;
    $scores;
    $times;
    $years;

    /* Numerial ranges look like this 
      $scores =[
        "70 79",
        "80 89",
        "90 100",
        "70 79",
        "80 89",
        "90 100"
      ];
    */
    //Start formating all the data
    $singleQuery = "SELECT * FROM giant_bomb_games WHERE (  ";
    if (!empty($reqObj->platformArray)) {
        $platforms = $reqObj->platformArray;
    }
    if (!empty($reqObj->genreArray)) {
        $genres = $reqObj->genreArray;
    }
    if (!empty($reqObj->scoreArray)) {
        $scores = $reqObj->scoreArray;
    }
    if (!empty($reqObj->timeArray)) {
        $times = $reqObj->timeArray;
    }
    if (!empty($reqObj->yearArray)) {
        $years = $reqObj->yearArray;
    }

  
    

    if (empty($platforms) && empty($genres) && empty($scores) && empty($years) && empty($times)) {
        //  echo "Nothing choosen, get random game.\n";
        $randomRowGames = "SELECT * FROM giant_bomb_games ORDER BY RAND() LIMIT 1";
        $result = $conn->query($randomRowGames);
        $chosenID;
        $gameArray;
        $contentArray = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $chosenID = $row["id"];
                $explodedPlat = explode("|", $row['platforms']);
                $exploedGenre = explode("|", $row['genres']);
                if (!$explodedPlat) {
                    $explodedPlat = $row['platforms'];
                }
                if (!$exploedGenre) {
                    $exploedGenre = $row['genres'];
                }
                $contentArray = ['name' => utf8_encode($row['name']) , 'id' => utf8_encode($row['id']) , 'cover' => utf8_encode($row['cover']) , 'releaseDate' => utf8_encode($row['release_data']) , 'summary' => utf8_encode($row['summary']) , 'rating' => utf8_encode($row['rating']) , 'info' => utf8_encode($row['url']) , 'main_story' => utf8_encode($row['main_story']) , 'main_extras' => utf8_encode($row['main_extras']) , 'completionist' => utf8_encode($row['completionist']) , 'combined' => utf8_encode($row['combined']) , 'genres' => $exploedGenre, 'platforms' => $explodedPlat];
                /* Format the genres and platforms*/
            }
        }
        else {
            // echo "The random search with no selection failed!\n";
            die();
        }
        return json_encode($contentArray);
    }
    else {
        /*****************FOR ANY Filter Selection******/
        if (!empty($platforms)) {
            $legnthPlat = count($platforms);
            $singleQuery .= " ( ";
            for ($i = 0;$i < $legnthPlat;$i++) {
                if ($i == $legnthPlat - 1) {
                    if (empty($genres) && empty($scores) && empty($times) && empty($years)) {
                        $singleQuery .= " platforms LIKE " . "'%" . $platforms[$i] . "|%'" . "OR platforms=" . "'" . $platforms[$i] . "'" . ")";
                    }
                    else {
                        $singleQuery .= " platforms LIKE " . "'%" . $platforms[$i] . "|%'" . "OR platforms=" . "'" . $platforms[$i] . "'" . " ) AND ";
                    }
                }
                else {
                    $singleQuery .= " platforms LIKE " . "'%" . $platforms[$i] . "|%'" . "OR platforms=" . "'" . $platforms[$i] . "'" . " OR ";
                }
            } /* End of for loop*/
        }

        if (!empty($genres)) {
            $legnthGenres = count($genres);
            $singleQuery .= " ( ";
            // echo "IN THE GENRE PARTS \n";
            for ($i = 0;$i < $legnthGenres;$i++) {
                if ($i == $legnthGenres - 1) {
                    if (empty($scores) && empty($times) && empty($years)) {
                        $singleQuery .= " genres LIKE " . "'%" . $genres[$i] . "%') ";
                    }
                    else {
                        $singleQuery .= " genres LIKE " . "'%" . $genres[$i] . "%') AND ";
                    }
                }
                else {
                    $singleQuery .= " genres LIKE " . "'%" . $genres[$i] . "%' OR ";
                }
            } /* End of for loop*/
        }

        if (!empty($scores)) {
            $legnthScores = count($scores);
            $singleQuery .= " ( ";
            for ($i = 0;$i < $legnthScores;$i++) {
                if ($scores[$i] != "blank") {
                    $spaceIndex = strpos($scores[$i], " ");
                    $lastIndex = strlen($scores[$i]);
                    $secondValue = substr($scores[$i], $spaceIndex);
                    $firstValue = substr($scores[$i], 0, $spaceIndex);
                    if ($i == $legnthScores - 1) {
                        if (empty($times) && empty($years)) {
                            $singleQuery .= "rating BETWEEN " . $firstValue . " AND " . $secondValue . ") ";
                        }
                        else {
                            $singleQuery .= "rating BETWEEN " . $firstValue . " AND " . $secondValue . ") AND ";
                        }
                    }
                    else {
                        $singleQuery .= " rating BETWEEN " . $firstValue . " AND " . $secondValue . " OR ";
                    }
                }
                else if ($scores[$i] == "blank") {
                    if ($i == $legnthScores - 1) {
                        if (empty($times) && empty($years)) {
                            $singleQuery .= " rating IS NULL ) ";
                        }
                        else {
                            $singleQuery .= " rating IS NULL ) AND ";
                        }
                    }
                    else {
                        $singleQuery .= " rating IS NULL OR ";
                    }
                } /*End of setting loop. */
            } /* End of for loop*/
        }

        if (!empty($times)) {
            $legnthTimes = count($times);
            $singleQuery .= " ( ";
            for ($i = 0;$i < $legnthTimes;$i++) {
                if ($times[$i] != "--" && $times[$i] != "100+") {
                    $spaceIndex = strpos($times[$i], " ");
                    $lastIndex = strlen($times[$i]);
                    $secondValue = substr($times[$i], $spaceIndex);
                    $firstValue = substr($times[$i], 0, $spaceIndex);
                    $firstValInt = (int)$firstValue;
                    $secondValInt = (int)$secondValue;
                    for ($j = $firstValInt;$j < $secondValInt + 1;$j++) {
                        if ($j == $secondValInt && $i != $legnthTimes - 1) {
                            //end of j
                            // echo"END OF j loop \n \n";
                            $singleQuery .= " main_story = " . '"' . $j . ' Hours"' . " OR ";
                            $singleQuery .= " main_story = " . '"' . $j . "½ Hours" . '"' . " OR ";
                        }
                        else if ($j == $secondValInt && $i == $legnthTimes - 1) {
                            //end of the whole i
                            // echo"END OF j loop and i \n \n";
                            if (empty($years)) {
                                $singleQuery .= " main_story = " . '"' . $j . ' Hours"' . " OR ";
                                $singleQuery .= " main_story = " . '"' . $j . "½ Hours" . '"' . ") ";
                            }
                            else {
                                $singleQuery .= " main_story = " . '"' . $j . ' Hours"' . " OR ";
                                $singleQuery .= " main_story = " . '"' . $j . "½ Hours" . '"' . ") AND ";
                            }

                        }
                        else {
                            //not end
                            $singleQuery .= " main_story = " . '"' . $j . ' Hours"' . " OR ";
                            $singleQuery .= " main_story = " . '"' . $j . "½ Hours" . '"' . " OR ";
                        }
                    } /*End of j loop */
                }
                else if ($times[$i] == "100+") {
                    if ($i == $legnthTimes - 1) {
                        //End
                        if (empty($years)) {
                            $singleQuery .= " main_story LIKE" . '"' . '_00%' . '") ';
                        }
                        else {
                            $singleQuery .= " main_story LIKE" . '"' . '_00%' . '" ) AND ';
                        }
                    }
                    else {
                        $singleQuery .= " main_story LIKE" . '"' . '_00%' . '"' . " OR ";
                    }

                }
                else if ($times[$i] == "--") {
                    if ($i == $legnthTimes - 1) {
                        // End
                        if (empty($years)) {
                            $singleQuery .= " main_story LIKE" . '"' . '--' . '") ';
                        }
                        else {
                            $singleQuery .= " main_story LIKE" . '"' . '--' . '") AND ';
                        }
                    }
                    else {
                        $singleQuery .= "main_story LIKE" . '"' . '--' . '"' . " OR ";
                    }
                }
            } /* End of for loop*/
        }

        if (!empty($years)) {
            $legnthYears = count($years);
            $singleQuery .= " ( ";
            for ($i = 0;$i < $legnthYears;$i++) {
                if ($years[$i] == "blank") {
                    if ($i == $legnthYears - 1) {
                        $singleQuery .= " release_data=" . '"' . '")';
                    }
                    else {
                        $singleQuery .= " release_data=" . '"' . '" OR';
                    }
                }
                else {
                    if ($i == $legnthYears - 1) {
                        $singleQuery .= " release_data LIKE " . "'%" . $years[$i] . "%')";
                    }
                    else {
                        $singleQuery .= " release_data LIKE " . "'%" . $years[$i] . "%' OR ";
                    }
                }
            } /* End of for loop*/
        }

        $singleQuery .= " ) ORDER BY RAND() LIMIT 1";
        $filterResult = $conn->query($singleQuery);
        $chosenID;
        /*Should only have 1 result */
        if ($filterResult->num_rows > 0) {
            $contentArray2 = array();
            while ($row = $filterResult->fetch_assoc()) {
                $explodedPlat = explode("|", $row['platforms']);
                $exploedGenre = explode("|", $row['genres']);
                if (!$explodedPlat) {
                    $explodedPlat = $row['platforms'];
                }
                if (!$exploedGenre) {
                    $exploedGenre = $row['genres'];
                }
                $chosenID = $row["id"];
                $contentArray2 = ['name' => utf8_encode($row['name']) , 'id' => utf8_encode($row['id']) , 'cover' => utf8_encode($row['cover']) , 'releaseDate' => utf8_encode($row['release_data']) , 'summary' => utf8_encode($row['summary']) , 'rating' => utf8_encode($row['rating']) , 'info' => utf8_encode($row['url']) , 'main_story' => utf8_encode($row['main_story']) , 'main_extras' => utf8_encode($row['main_extras']) , 'completionist' => utf8_encode($row['completionist']) , 'combined' => utf8_encode($row['combined']) , 'genres' => $exploedGenre, 'platforms' => $explodedPlat];
            }
            return json_encode($contentArray2);            
        }
        else {
            $sorry = ['Sorry' => "Sorry Nothing found! =p"];
            return json_encode($sorry);
        }
    } /*End of the else if user choose anything */
    // $conn->close();
});


$app->post('/scrape', function () use ($app) {
    /* Replace space instances with %20*/
    $urlBase = 'https://www.emuparadise.me/roms/search.php?query=';
    $json = file_get_contents('php://input');
    $gameName = json_decode($json)->name;
    $searchUrl = $urlBase . $gameName;
    $filteredUrl = str_replace(" ", "%20", $searchUrl);
    /*Check if the url does work for what ever reason */
    $romDivArr = array();
    if (!filter_var($filteredUrl, FILTER_VALIDATE_URL) === false) {
        $page = scrapeRomList($filteredUrl);
        /* Where to start the scrape*/
        $start = '<span style="color:#aaa;line-height:2em;text-align:left;">';
        $end = '</span>';
        $result = fetchdata($page, $start, $end);
        $start = 0;
        $cur = substr($result, $start);
        $start = strpos($cur, '<div class="roms"');
        $end = strpos($cur, '</div>');
        /* Constant how man chars from < of </div> 6 b/c of whitespace i guess*/
        $offset = 6;
        $i = 0;
        while ($start !== false && $end !== false) {
            $elemLen = $end - $start;
            $element = substr($cur, $start, $elemLen + $offset);
            $romDivArr[] = $element;
            $cur = substr($cur, $end + $offset);
            $start = strpos($cur, '<div class="roms"');
            $end = strpos($cur, '</div>');
        }
        /* Get all the <a data-filter to </a>*/
        if (!empty($romDivArr)) {
            $linkArr = array();
            $nameArr = array();
            $contentArray = array();
            $posStart = '<a data-filter';
            $posEnd = '</a>';
            $posStart2 = 'href="';
            $posEnd2 = '"';
            $i = 0;
            foreach ($romDivArr as $value) {
                /* Get name from link as well*/
                $currentResult = fetchdata($value, $posStart, $posEnd);
                $linkData = fetchdata($currentResult, $posStart2, $posEnd2);
                $temp = substr($linkData, 1);
                $len = strpos($temp, '/');
                $temp2 = substr($temp, $len + 1);
                $unregName = substr($temp2, 0, strpos($temp2, '/'));
                $regName = str_replace("_", " ", $unregName);
                $nameArr[] = $regName;
                $linkArr[] = 'https://www.emuparadise.me' . $linkData . '-download';
                $contentArray[$i] = array();
                $contentArray[$i]['name'] = $regName;
                $contentArray[$i]['link'] = 'https://www.emuparadise.me' . $linkData . '-download';
                $i++;
            }
            return json_encode($contentArray);
        }
        else {
            echo json_encode("nil");
            die();
        }
    }
    else {
        die();
    }
});

function scrapeRomList($url) {
    /* Look for <div class="roms"> then <a href where class!=sysname*/
    $output = file_get_contents($url);
    return $output;
}

function fetchdata($data, $start, $end) {
    //echo $data;
    $data = stristr($data, $start);
    //echo $data;
    $data = substr($data, strlen($start));
    //echo $data;
    $stop = stripos($data, $end);
    $data = substr($data, 0, $stop);
    return $data;
}

$app->run();
?>
