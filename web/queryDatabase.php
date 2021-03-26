<?php
  // Allow from any origin
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");


error_reporting(0);
$conn = new mysqli($servername, $username, $password, $dbname, $port);
$json = file_get_contents('php://input');
$reqObj = json_decode($json);
// echo json_encode(!empty($reqObj->scoreArray));
// echo json_encode($reqObj->genreArray);
// echo  json_encode( [1,2,3]) ;


 $platforms;
 $genres;
 $scores;
 $times;
 $years;

  //Start formating all the data 
   $singleQuery= "SELECT * FROM giant_bomb_games WHERE (  ";
   if(!empty($reqObj->platformArray)){
    $platforms = $reqObj->platformArray;
   }
   if(!empty($reqObj->genreArray)){
    $genres = $reqObj->genreArray;
   }
  if(!empty($reqObj->scoreArray)){
    $scores = $reqObj->scoreArray;
   }
if(!empty($reqObj->timeArray)){
    $times = $reqObj->timeArray;
   }
if(!empty($reqObj->yearArray)){
    $years = $reqObj->yearArray;
  }
// $scores =[
//   "70 79",
//   "80 89",
//   "90 100",
//   "70 79",
//   "80 89",
//   "90 100"
// ];


if(empty($platforms) && empty($genres) && empty($scores) && empty($years) && empty($times)){
  //  echo "Nothing choosen, get random game.\n";
    $randomRowGames = "SELECT * FROM giant_bomb_games ORDER BY RAND() LIMIT 1";
    $result = $conn->query($randomRowGames);
    $chosenID; 
    $gameArray;
    $contentArray =[];
  if($result->num_rows > 0){
         while($row = $result->fetch_assoc()){
                $chosenID = $row["id"];
                $explodedPlat =explode("|", $row['platforms']);
                $exploedGenre= explode("|",$row['genres']);
                if(!$explodedPlat){
                  $explodedPlat = $row['platforms'];
                } 
                if(!$exploedGenre){
                  $exploedGenre = $row['genres'];
                }
                    $contentArray = [
                 'name'=> utf8_encode($row['name']),
                 'id'=> utf8_encode($row['id']),
                 'cover'=> utf8_encode($row['cover']),
                 'releaseDate'=>utf8_encode($row['release_data']),
                 'summary'=>utf8_encode($row['summary']),
                 'rating'=>utf8_encode($row['rating']),
                 'info'=> utf8_encode($row['url']),
                 'main_story'=>utf8_encode($row['main_story']),
                 'main_extras'=>utf8_encode($row['main_extras']),
                 'completionist'=>utf8_encode($row['completionist']),
                 'combined'=>utf8_encode($row['combined']),
                 'genres'=>$exploedGenre,
                 'platforms'=>$explodedPlat
                  ];
                  /* Format the genres and platforms*/            
              }
  }else{
           // echo "The random search with no selection failed!\n";
  }
  echo json_encode($contentArray);
}else{
/*****************FOR ANY Filter Selection******/

/* SELECT * FROM `giant_bomb_games` WHERE platforms="Playstation" OR platforms LIKE "%Playstation|%" */
if(!empty($platforms)){
  $legnthPlat = count($platforms);
  $singleQuery .= " ( ";
  for($i=0 ; $i<$legnthPlat;$i++){
    if($i==$legnthPlat-1){
      if(empty($genres) && empty($scores) && empty($times) && empty($years)){               
               $singleQuery.= " platforms LIKE " ."'%".$platforms[$i]."|%'". "OR platforms="."'".$platforms[$i]."'". ")";
      }else{
              $singleQuery.= " platforms LIKE " ."'%".$platforms[$i]."|%'". "OR platforms="."'".$platforms[$i]."'"." ) AND ";
      }
    }else{
        $singleQuery.= " platforms LIKE " ."'%".$platforms[$i]."|%'". "OR platforms="."'".$platforms[$i]."'" ." OR ";
    }
  } /* End of for loop*/
} 

  if(!empty($genres)){
  $legnthGenres = count($genres);
   $singleQuery .= " ( ";
  // echo "IN THE GENRE PARTS \n";
  for($i=0 ; $i<$legnthGenres;$i++){
    if($i==$legnthGenres-1){
       if( empty($scores) && empty($times) && empty($years)){
                $singleQuery.= " genres LIKE " ."'%".$genres[$i]."%') ";
      }else{
                $singleQuery.= " genres LIKE " ."'%".$genres[$i]."%') AND ";
      }
    }else{
        $singleQuery.= " genres LIKE " ."'%".$genres[$i]."%' OR ";
    }
  } /* End of for loop*/
}

if(!empty($scores)){
  $legnthScores = count($scores);
  $singleQuery .= " ( ";
  for($i=0 ; $i<$legnthScores;$i++){
     if($scores[$i]!="blank"){
            $spaceIndex = strpos($scores[$i], " "); 
            $lastIndex =  strlen($scores[$i]); 
            $secondValue =   substr($scores[$i], $spaceIndex);
            $firstValue =  substr($scores[$i],0,$spaceIndex);
            if($i==$legnthScores-1){
                  if( empty($times) && empty($years)){
                      $singleQuery.= "rating BETWEEN " . $firstValue . " AND " . $secondValue.") ";
                  }else{
                  $singleQuery.= "rating BETWEEN " . $firstValue . " AND " . $secondValue.") AND ";
                  }
            }else{
                $singleQuery.= " rating BETWEEN " . $firstValue . " AND " . $secondValue ." OR ";
            }
          }else if($scores[$i]=="blank"){
            /* For the including the nulls scores*/
          //  echo "In settings for blank scores!\n\n";
             if($i==$legnthScores-1){
                  if( empty($times) && empty($years)){
                        $singleQuery.= " rating IS NULL ) ";
                  }else{
                        $singleQuery.= " rating IS NULL ) AND ";
                  }
             }else{
                        $singleQuery.= " rating IS NULL OR ";
             }
        }/*End of setting loop. */
  } /* End of for loop*/
}


if(!empty($times)){
  $legnthTimes = count($times);  
  $singleQuery .= " ( ";
  for($i=0 ; $i<$legnthTimes;$i++){
        if($times[$i]!="--" && $times[$i]!="100+"){
            $spaceIndex = strpos($times[$i], " "); 
            $lastIndex =  strlen($times[$i]); 
            $secondValue =   substr($times[$i], $spaceIndex);
            $firstValue =  substr($times[$i],0,$spaceIndex);
            $firstValInt = (int)$firstValue;
            $secondValInt = (int)$secondValue;
           // echo "first val is ". $firstValue . " second val is " . $secondValue . "\n";
            for($j=$firstValInt;$j<$secondValInt+1;$j++){
                // echo "i value is: " . $i . " j value is : ". $j . "\n\n"; 
                // echo "Length of time array is " . $legnthTimes ."\n\n";
                // echo "SecondVal int is " . $secondValInt ."\n\n";
              if($j==$secondValInt&& $i!=$legnthTimes-1){
                //end of j
                // echo"END OF j loop \n \n";
                 $singleQuery .= " main_story = " . '"'. $j .  ' Hours"'. " OR ";
                 $singleQuery .= " main_story = " . '"'. $j . "½ Hours".'"'." OR ";
              }else if($j==$secondValInt && $i==$legnthTimes-1){
                //end of the whole i
                 // echo"END OF j loop and i \n \n";
                if(  empty($years)){
                    $singleQuery .= " main_story = " . '"'. $j .  ' Hours"'. " OR ";
                    $singleQuery .= " main_story = " . '"'. $j . "½ Hours".'"' . ") ";
                }else{
                    $singleQuery .= " main_story = " . '"'. $j .  ' Hours"'. " OR ";
                    $singleQuery .= " main_story = " . '"'. $j . "½ Hours".'"' . ") AND ";
                }
                 
              }else{
                //not end
                 $singleQuery .= " main_story = " . '"'. $j .  ' Hours"'. " OR ";
                 $singleQuery .= " main_story = " . '"'. $j. "½ Hours" . '"'. " OR ";
              }
            } /*End of j loop */
          }else if($times[$i]=="100+"){
              // echo "IN THE 100+ STRING SETTINGS FOR TIME \n";
             if($i == $legnthTimes-1){
              //End
              if( empty($years)){
                     $singleQuery.=" main_story LIKE". '"'. '_00%' . '") ' ;
              }else{
                     $singleQuery.=" main_story LIKE". '"'. '_00%' . '" ) AND ' ;
              }   
             }else{
              $singleQuery .= " main_story LIKE" . '"'. '_00%'  .  '"'. " OR ";
             }

          }else if($times[$i]=="--"){
             // echo "In the blank time record setting \n";
              if($i == $legnthTimes-1){
              // End
                 if( empty($years)){
                    $singleQuery.=" main_story LIKE". '"'. '--' . '") ' ;
                   }else{
                    $singleQuery.=" main_story LIKE". '"'. '--' . '") AND ' ;
                  }  
             }else{
              $singleQuery .= "main_story LIKE" . '"'. '--'  .  '"'. " OR ";
             }
          }
  } /* End of for loop*/
} 


  if(!empty($years)){
  $legnthYears = count($years);
  $singleQuery .= " ( ";
  for($i=0 ; $i<$legnthYears;$i++){
    if($years[$i]=="blank"){
     if($i==$legnthYears-1){
            $singleQuery.= " release_data=" . '"' . '")';
        }else{
            $singleQuery.= " release_data=" . '"' . '" OR';
      }      
    }else{
        if($i==$legnthYears-1){
            $singleQuery.= " release_data LIKE " ."'%".$years[$i]."%')";
        }else{
            $singleQuery.= " release_data LIKE " ."'%".$years[$i]."%' OR ";
      } 
    }
  } /* End of for loop*/
}


// echo "Done constructing query! \n\n";
$singleQuery.= " ) ORDER BY RAND() LIMIT 1";
//  echo  $singleQuery . "\n\n";
/* Run the query ****************************/
$filterResult = $conn-> query($singleQuery);
$chosenID;
// echo "Called fitler result already!!\n\n";
/*Should only have 1 result */
 if($filterResult->num_rows > 0 ){
  // echo "Number of results is greater than 1 \n";
   $contentArray2 =array();
    while($row = $filterResult->fetch_assoc()){
        // echo "Something found for the users query! " .  " id = " . $row['id'] . "\n";
         // echo  "Name: " . $row["name"]. "  id:  " . $row["id"]. " cover url : " . $row["cover"] . "\n summary: "
         //                .$row["summary"] . " time_to_beat: " . $row['time_to_beat'] . " rating: " . $row[$rating] . " info url: " .
         //                $row["url"] ;
         //                "<br>" ."\n";

         //                echo "\n\n\n\n\n";
                         $explodedPlat =explode("|", $row['platforms']);
                         $exploedGenre= explode("|",$row['genres']);
         //                echo "Exploded genre before -> " . var_dump($exploedGenre) . "\n";
         //                echo "Exploded plat before -> " . var_dump($explodedPlat). "\n";
                        if(!$explodedPlat){
                          $explodedPlat = $row['platforms'];
                        } 
                        if(!$exploedGenre){
                          $exploedGenre = $row['genres'];
                        }
                        // echo "Exploded genre after - > " . var_dump($exploedGenre) . " \n";
                        // echo "Exploded platform after -> " . var_dump($explodedPlat) . "\n";
         $chosenID = $row["id"];
                         $contentArray2 = [
                         'name'=> utf8_encode($row['name']),
                         'id'=> utf8_encode($row['id']),
                         'cover'=> utf8_encode($row['cover']),
                         'releaseDate'=>utf8_encode($row['release_data']),
                         'summary'=>utf8_encode($row['summary']),
                         'rating'=>utf8_encode($row['rating']),
                         'info'=> utf8_encode($row['url']),
                         'main_story'=>utf8_encode($row['main_story']),
                         'main_extras'=>utf8_encode($row['main_extras']),
                         'completionist'=>utf8_encode($row['completionist']),
                         'combined'=>utf8_encode($row['combined']),
                         'genres'=>$exploedGenre,
                         'platforms'=>$explodedPlat
                          ];
    }
    //echo "Echoing final contents of the contentArray!\n";
    echo  json_encode($contentArray2);
     //echo "LAST JASON ERROR "  . json_last_error();
     }else{
      $sorry= ['Sorry'=>"Sorry Nothing found! =p"];
  echo json_encode($sorry);
}














}/*End of the else if user choose anything */






  






  

   $conn->close();
?>
