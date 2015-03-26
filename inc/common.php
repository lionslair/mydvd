<?
function greatescape($text)
{
  if (!ini_get("magic_quotes_gpc")) {
    return (addslashes($text));
  } else {
    return $text;
  }
}

function ungreatescape($text)
{
  if (ini_get("magic_quotes_gpc")) {
    return (stripslashes($text));
  } else {
    return $text;
  }
}

function saneempty($value)
{
  if (strlen($value)==0)
    return TRUE;
  else
    return FALSE;
}

function addpar($url, $par)
{
  if (strpos($url,"?")===FALSE) $chr="?"; else $chr="&";
  return $url.$chr.$par;
}

function getconfig($user="")
{
  $config=array();
  $result=doquery("SELECT * FROM config");

  while ($row=mysql_fetch_assoc($result)) {
    $config[$row["item"]]=$row["value"];
  }

  // Only certain values can be user defined
  $validconfs=array("showimdb", "defregion", "defmedia", "theme", "movcolumns","movienav");

  if (!saneempty($user)) {
    $result=doquery("SELECT item, value FROM userprefs WHERE userid=".$user);
    while ($row=mysql_fetch_assoc($result)) {
      if (in_array($row["item"],$validconfs)) $config[$row["item"]]=$row["value"];
    }
  } else if (isset($_COOKIE["userid"])) {
    $result=doquery("SELECT item, value FROM userprefs WHERE userid=".intval($_COOKIE["userid"]));
    while ($row=mysql_fetch_assoc($result)) {
      if (in_array($row["item"],$validconfs)) $config[$row["item"]]=$row["value"];
    }
  }

  return $config;
}

function dopage($content)
{
  global $config;

  require("themes/".$config["theme"]."/header.php");
  echo $content["body"];
  require("themes/".$config["theme"]."/footer.php");
}
function ckbit($bit,$value)
{
  if (($value&$bit)==$bit) return TRUE; else return FALSE;
}
function redirect($location)
{
  if (strpos($_SERVER["SERVER_SOFTWARE"], "IIS")!==FALSE) {
    // Do a meta refresh. Resolves refresh problem when setting cookies
    // with Microsoft IIS servers 5.0 and lower.
    echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0;URL=".$location."\">";
  } else {
    header ("Location: ".$location);
  }
}


function get_genre($genreid) {  // added function to get the genre
  $sql = 'SELECT name FROM genre WHERE id = "'.$genreid.'"';
  //echo $sql;  // echo

  $res = mysql_query($sql) or die("Mysql error!");

  $num_rows = mysql_num_rows($res);  // get the number of rows

  if ($num_rows > 0) {
    $row = mysql_fetch_array($res);
    $genre = $row['name'];
  } else {
    $genre = "No genre found";
  }
  return $genre;
}

//---------------------------------------------------------------------

// added function to get the genre
function get_location($locationid)
{
  $sql = 'SELECT name FROM location WHERE id = "'.$locationid.'"';
  //echo $sql;  // echo

  $res = mysql_query($sql) or die("Mysql error!");

  $num_rows = mysql_num_rows($res);  // get the number of rows

  if ($num_rows > 0)
  {
    $row = mysql_fetch_array($res);
    $strLocation = $row['name'];
  }
  else
  {
    $strLocation = "No Location found";
  }
  return $strLocation;
}
?>
