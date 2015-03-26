<?php // copymovie.php
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");

$config=getconfig();

//$output .=  'movie id: '.$_GET['movieid'].'<br>';

  // get the movie data
  $result = doquery('SELECT * FROM movie WHERE id="'.intval($_GET["movieid"]).'"');
  $row=mysql_fetch_array($result);

  foreach ($row AS $key => $value) 
  {
    $movie_data[$key] = $value;
    //$output .= $movie_data[$key]."<br>";
  }
 // echo $movie_data;


  //$query="INSERT movie VALUES (NULL, ".intval($_COOKIE["userid"]).", \"".greatescape($_POST["movietitle"])."\", \"".intval($_POST["year"])."\", \"".greatescape($_POST["comments"])."\", ".intval($_POST["rating"]).", \"".greatescape($genres)."\", ".intval($region).", now(), ".intval($_POST["runtime"]).", ".intval($_POST["media"]).", \"".greatescape($_POST["director"])."\", \"".greatescape($_POST["sound"])."\", \"".greatescape($_POST["video"])."\", \"".greatescape($_POST["extra"])."\", \"".greatescape($_POST["refno"])."\")";
  $query = 'INSERT INTO movie 
            VALUES (
                    NULL,
                   '.intval($_COOKIE["userid"]).', 
                   "COPY '.addslashes($movie_data['title']).'",
                   "'.$movie_data['reldate'].'",
                   "'.addslashes($movie_data['comments']).'", 
	            NULL, 
                   "'.intval($movie_data['rating']).'", 
                   "'.intval($movie_data['genreid']).'", 
                   "'.intval($movie_data['region']).'",
                   "'.$movie_data['insertdate'].'",
                   "'.intval($movie_data['runtime']).'",
                   "'.intval($movie_data['mediaid']).'", 
                   "'.$movie_data['director'].'", 
                   "'.$movie_data['sound'].'",
                   "'.$movie_data['video'].'",
                   "'.$movie_data['extra'].'",
                   "'.$movie_data['refno'].'",
                   "'.$movie_data['imgurl'].'",
                   "'.$movie_data['locationid'].'"
                   )';
   $output .= 'sql is: '.$query.'<br>';
  doquery($query);

 //echo $output.'<br />'."\n";

  header("Location: listmovies.php");

//$content["body"] =& $output;
//dopage($content);

?>
