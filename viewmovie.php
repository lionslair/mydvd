<?
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");

$config=getconfig();

$output="";
$rows="";

if (!isset($_GET["movieid"])||!is_numeric($_GET["movieid"])) {
  header("Location: listmovies.php");
}

$movieid=intval($_GET["movieid"]);

$genres=array();
$query="SELECT id, name FROM genre ORDER BY id";
$genreres=doquery($query);

while ($genrerow=mysql_fetch_assoc($genreres)) {
  $genres[$genrerow["id"]]=$genrerow["name"];
}

$fields ="user.email, user.fname, user.lname, movie.refno, ";
$fields.="movie.userid, movie.title, movie.reldate, movie.comments, movie.user_comments, movie.genreid, movie.rating, movie.runtime, movie.region, movie.director, movie.sound, movie.video, movie.extra, movie.locationid, ";
$fields.="movie.imgurl, "; // added the movie url field
$fields.="loan.userid as loanedto, loan.loanee, loan.loaneeemail, date_format(loan.loandate,\"".$config["dateformat"]."\") as loandate, ";
$fields.="media.name as medianame";

$from="movie, user, media";

$joins ="LEFT JOIN loan ON 'movie.id' = loan.movieid LEFT JOIN location ON 'movie.locationid' = location.id";
$where ="movie.userid = user.id AND movie.mediaid = media.id AND movie.id = ".$movieid;

$query="SELECT ".$fields." FROM ".$from." ".$joins." WHERE ".$where;
//echo $query;

$mov=mysql_fetch_assoc(doquery($query));

$genreout=array();
$tmp=explode(",",$mov["genreid"]);
foreach ($tmp as $val) {
  $genreout[]=$genres[$val];
}

$rows.=tr(td("<SPAN CLASS=\"movtitle\">".htmlspecialchars($mov["title"])."</SPAN>","100%", "movtitle","","","2"));
$rows.=tr(td("<B>Movie details</B>","40%").td("<B>User details</B>","60%"));


$r=tr(td("Owned by:","120","movdetails").td($mov["fname"]." ".$mov["lname"]." (<A HREF=\"mailto:".$mov["email"]."\">".$mov["email"]."</A>)","","movdetails"));
if ($mov["locationid"] == 0)
{
 $strLocation = 'No location has been set';
}
else
{
 $strLocation = get_location($mov["locationid"]);
}
$r.=tr(td("Location:","120","movdetails").td($strLocation,"","movdetails"));

if ($mov["loanedto"]==NULL) {
  $loaned="Not on loan";
} else if ($mov["loanedto"]==0) {
  $loaned="On loan to <A HREF=\"mailto:".$mov["loaneeemail"]."\">".$mov["loanee"]."</A> since ".$mov["loandate"];
} else {
  $result=doquery("select fname, lname, email from user where id = ".$mov["loanedto"]);
  $row=mysql_fetch_assoc($result);
  $loaned="On loan to <A HREF=\"mailto:".$row["email"]."\">".$row["fname"]." ".$row["lname"]."</A> since ".$mov["loandate"];
}

$r.=tr(td("Loan status:","","movdetails").td($loaned,"","movdetails"));

$r.=tr(td("Comments:","","movdetails","","TOP").td(nl2br(htmlspecialchars($mov["comments"])),"","movdetails"));
$r.=tr(td("User Comments:","","movdetails","","TOP").td(nl2br(htmlspecialchars($mov["user_comments"])),"","movdetails"));

// Left column

if ($mov["rating"]==0) {
  $stars="Not rated";
} else {
  if ($mov["rating"] <=5) {
    $img="themes/".$config["theme"]."/images/blackstar.gif";
  } else {
    $img="themes/".$config["theme"]."/images/redstar.gif";
  }
  for ($i=1;$i<=$mov["rating"];$i++) {
    if ($i<6) $stars.="<IMG SRC=\"".$img."\" ALT=\"+\">";
  }
}

if (strtoupper($mov["medianame"])=="DVD") {
  $media=$mov["medianame"]." (Region ".$mov["region"].")";
} else {
  $media=$mov["medianame"];
}

$l=tr(td("Release date:","120","movdetails").td($mov["reldate"],"","movdetails"));
$l.=tr(td("Director:","","movdetails").td(htmlspecialchars($mov["director"]),"","movdetails"));
$l.=tr(td("Genre(s):","","movdetails").td(implode(" / ",$genreout),"","movdetails"));
$l.=tr(td("Media:","","movdetails").td($media,"","movdetails"));
$l.=tr(td("Sound:","","movdetails").td(htmlspecialchars($mov["sound"]),"","movdetails"));
$l.=tr(td("Video:","","movdetails").td(htmlspecialchars($mov["video"]),"","movdetails"));
$l.=tr(td("Extra info:","","movdetails").td(htmlspecialchars($mov["extra"]),"","movdetails"));
$l.=tr(td("Reference No.:","","movdetails").td(htmlspecialchars($mov["refno"]),"","movdetails"));
$l.=tr(td("Length (minutes):","","movdetails").td($mov["runtime"],"","movdetails"));
$l.=tr(td("User rating:","","movdetails").td($stars,"","movdetails"));

if (!empty($mov["imgurl"])) {
  $l.=tr(td("Cover Image:","","movdetails").td("<img src=\"".htmlspecialchars($mov["imgurl"])."\">","","movdetails")); // add the cover img if there is one
}

$rows.=tr(td(table($l,0,0,0),"","","","TOP").td(table($r,0,0,0),"","","","TOP"));

$output.=table($rows,0,1,4,"100%");

if ($mov["userid"]==$_COOKIE["userid"]||($config["allowadminedit"]==1&&$admin)) {
  $output.="<P>";
  $output.=form_begin("editmovie.php","GET");
  $output.=input_hidden("movieid",$movieid);
  $output.=submit("Edit this movie");
  $output.=form_end();
}

$content["body"] =& $output;
dopage($content);
?>
