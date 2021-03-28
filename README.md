
[![GitHub issues](https://img.shields.io/github/issues/ethanny2/video-game-randomizer-backend)](https://github.com/ethanny2/video-game-randomizer-backend/issues)[![GitHub forks](https://img.shields.io/github/forks/ethanny2/video-game-randomizer-backend)](https://github.com/ethanny2/video-game-randomizer-backend/network)[![GitHub stars](https://img.shields.io/github/stars/ethanny2/video-game-randomizer-backend)](https://github.com/ethanny2/video-game-randomizer-backend/stargazers)[![GitHub license](https://img.shields.io/github/license/ethanny2/video-game-randomizer-backend)](https://github.com/ethanny2/video-game-randomizer-backend)[![Twitter Badge](https://img.shields.io/badge/chat-twitter-blue.svg)](https://twitter.com/ArrayLikeObj)

# Comprehensive Video Game Randomizer Backend

## [https://game-randomizer.netlify.app/random](https://game-randomizer.netlify.app/random)


<p align="center">
  <img  src="https://media0.giphy.com/media/8K3y2mP4XiFYXs7OIL/giphy.gif" alt="Demo gif">
</p>

## Background

The concept for this site was to give video game players/fans the ability to programatically search/filter through a comprehensive game database that was built by compiling information through multiple video game data APIs ([Giant Bomb](https://www.giantbomb.com/) , [IGBD](https://www.igdb.com/discover)) and web scraping ([How long to beat](https://howlongtobeat.com/), [MetaCritic](https://www.metacritic.com/)).

Seen in the gif above is the infinite scroll cover art browser; which lets you randomly find a game you're interested in based on its cover.

Unlike similar sites/ APIs this includes **nearly all released video games from around 1980 - 2015**.

**Goals (for rehosting site)** : 
   - Migrate PHP backend from a traditional hosting service (was on HostGator previously) to a free tier Heroku application.
   - Upgrading to PHP 7 to use composer and create REST endpoints.
   - Importing my backup SQL file to the ClearDB hosted database.
   - Rewrite the link scraper if format of site changed in the past couple years.

## Technology used
- PHP Scraping
- Silex for routing / making a RESTful API
- Beautiful Soup for scraping Metacritic and How Long To Beat.
- Handling JSON POST data as PHP
- Connecting to a remote mysql database via environment variables (Cleardb)

## Endpoints

### GET /random

Queries the game database to fetch 10 random entries in the table and send all of their fields/columns back to the front-end as JSON. Because the mySQL database is over 20MB large the ```RAND()``` gets exponetially slower and was loading slower than expected. The work around was to re-write the query using a JOIN and aliases.

```
// Slower random query
// $randomGameRows = "SELECT * FROM giant_bomb_games WHERE `cover` IS NOT NULL ORDER BY RAND() LIMIT 20";
$randomGameRows = "SELECT * FROM giant_bomb_games AS t1 JOIN (SELECT id FROM giant_bomb_games ORDER BY RAND() LIMIT 20) as t2 ON t1.id=t2.id";
$result = $conn->query($randomGameRows);
$imageArray = [];
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    array_push($imageArray, $row);
  }
}
$json =  json_encode($imageArray, JSON_UNESCAPED_UNICODE);
```


### POST /scrape 
- Required POST Data Format : ```{name: ""}```

This route requires a JSON payload with a name property. It uses the name to scrape possible game links from the ISO website [Emuparadise](https://www.emuparadise.me/). Because this endpoint uses the search functionality built into Emuparadise the search results are only as accurate as that site's search bar.
```
$urlBase = 'https://www.emuparadise.me/roms/search.php?query=';
$json = file_get_contents('php://input');
$gameName = json_decode($json)->name;
$searchUrl = $urlBase . $gameName;
$filteredUrl = str_replace(" ", "%20", $searchUrl);
...
...
```

### POST /query
- POST Data Format : ```{genreArray: ["Action", "Shooter"], scoreArray : ["70 79"], platformArray: ["Playstation"], timeArray: ["10 19"], yearArray: []}```
- You may omit or include any combination of these arrays to narrow down your filtered random game.
- Omitting all the values (they are all optional) will yield a 100% random game
- The above example query says:
  - *Find me a random action and/or shooter game that has an average score of 70 - 79 that was released on the Playstation in any year*
- Ranges of values (score, time) are represented by a string with 2 numbers separated with a space.

```
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
            return json_encode($contentArray2, JSON_UNESCAPED_UNICODE);            
        }
```
