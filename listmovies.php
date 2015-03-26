<?php
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");

$config=getconfig();

$cols=$config["movcolumns"];

$output="";
$nav="";
$direction="";
$ownedby="";
$genre="";
$filter="";
$url="";
$limit="";

if (saneempty($_GET["page"])) $page=0; else $page=intval($_GET["page"]);

$offset=$page*$config["numperpage"];

$fields="movie.userid, movie.id as movieid, movie.title, movie.genreid, movie.reldate, left(movie.comments,".$config["commentchars"].") as comments, length(comments) as comlen, movie.rating, movie.region, movie.runtime, movie.refno, movie.locationid, ";
$fields.="user.email, concat(user.fname, \" \", user.lname) as name, ";
$fields.="loan.id AS loanid, media.name AS medianame";

$from="movie,user,media";
$joins="LEFT JOIN loan ON ('movie.id' = loan.movieid) LEFT JOIN location ON ('movie.locationid' = location.id)";
$where="movie.userid = user.id AND movie.mediaid = media.id";
$orderby="title";
if (!isset($_GET["showall"])||$config["showall"]==0) {
  $limit=" LIMIT ".$offset.",".$config["numperpage"];
} else {
  $url=addpar($url, "showall=1");
}
if (!saneempty($_GET["movieid"])) {
  $filter[]="<B>Search for:</B> ".$_GET["movieid"];
  if (intval($config["mysqlversion"])>=4.0) {
    $where.=" AND match(movie.ide) against (\"".greatescape($_GET["movieid"])."\" IN BOOLEAN MODE)";
  } else {
    $where.=" AND match(movie.id) against (\"".greatescape($_GET["movieid"])."\")";
  }
  $url=addpar($url, "movieid=".str_replace(" ","+",htmlspecialchars($_GET["movieid"])));
}

if (!saneempty($_GET["userid"])) {
  $ownedby=mysql_result(doquery("SELECT concat(fname, \" \", lname) FROM user WHERE id = ".intval($_GET["userid"])),0);
  $filter[]="<B>Owner:</B> ".$ownedby;
  $where.=" AND movie.userid = ".intval($_GET["userid"]);
  $url=addpar($url, "userid=".intval($_GET["userid"]));
}
if (!saneempty($_GET["genreid"])) {
  $genre=mysql_result(doquery("SELECT name FROM genre WHERE id = ".intval($_GET["genreid"])),0);
  $filter[]="<B>Genre:</B> ".$genre;
  $where.=" AND find_in_set(".intval($_GET["genreid"]).",movie.genreid)";
  $url=addpar($url, "genreid=".intval($_GET["genreid"]));
}
if (!saneempty($_GET["year"])) {
  $filter[]="<B>Released year:</B> ".intval($_GET["year"]);
  $where.=" AND movie.reldate = ".intval($_GET["year"]);
  $url=addpar($url, "year=".intval($_GET["year"]));
}
if (!saneempty($_GET["region"])) {
  $filter[]="<B>Region:</B> ".intval($_GET["region"]);
  $where.=" AND movie.region = ".intval($_GET["region"]);
  $url=addpar($url, "region=".intval($_GET["region"]));
  if (saneempty($_GET["media"])) {
    $filter[]="<B>Media:</B> DVD";
    $where.=" AND movie.mediaid = 1";
    $url=addpar($url, "media=1");
  }
}
if (!saneempty($_GET["media"])) {
  $medianame=mysql_result(doquery("SELECT name FROM media WHERE id = ".intval($_GET["media"])),0);
  $filter[]="<B>Media:</B> ".$medianame;
  $where.=" AND movie.mediaid = ".intval($_GET["media"]);
  $url=addpar($url, "media=".intval($_GET["media"]));
}

if (!saneempty($_GET["locationid"])) {
  $locationname=mysql_result(doquery("SELECT name FROM location WHERE id = ".intval($_GET["locationid"])),0);
  $filter[]="<B>Location:</B> ".$locationname;
  $where.=" AND movie.locationid = ".intval($_GET["locationid"]);
  $url=addpar($url, "locationid=".intval($_GET["locationid"]));
}
if (!saneempty($_GET["s"])) {
  $filter[]="<B>Search for:</B> ".$_GET["s"];
  if (intval($config["mysqlversion"])>=4.0) {
    $where.=" AND match(movie.title) against (\"".greatescape($_GET["s"])."\" IN BOOLEAN MODE)";
  } else {
    $where.=" AND match(movie.title) against (\"".greatescape($_GET["s"])."\")";
  }
  $url=addpar($url, "s=".str_replace(" ","+",htmlspecialchars($_GET["s"])));
}

$printurl="print.php".$url;
$url="listmovies.php".$url;
$navurl=$url;

if ($_GET["desc"]==1) {
  $direction.=" DESC";
  $navurl=addpar($navurl, "DESC=1");
}
if (!saneempty($_GET["order"])) {
  $validorders=array("movieid","name","title","reldate","genreid","runtime","rating","refno","locationid");
  if (in_array($_GET["order"],$validorders)) {
    $orderby=$_GET["order"];
    $navurl=addpar($navurl, "order=".$_GET["order"]);
    if (saneempty($_GET["desc"])) {
      $url=addpar($url, "desc=1");
    }
  }
}

if (!saneempty($filter)) {
  $output.=table(tr(td("Filters in effect:<BR />\n".implode("; ",$filter))),0,1,0,"","filters");
  $output.="<BR />\n";
}

$output.="<A HREF=\"".$printurl."\"><IMG BORDER=\"0\" SRC=\"images/print.gif\" ALT=\"Print this list\"></A> - Print results\n<BR />\n";

$query="SELECT ".$fields." FROM ".$from." ".$joins." WHERE ".$where." ORDER BY ".$orderby.$direction.$limit;

//$output.=$query."<BR /><BR />";

$result=doquery($query);

if (ckbit(8,$cols)) {
  $genres=array();
  $query="SELECT id, name FROM genre ORDER BY id";
  $genreres=doquery($query);
  while ($genrerow=mysql_fetch_assoc($genreres)) {
    $genres[$genrerow["id"]]=$genrerow["name"];
  }
}

if (mysql_num_rows($result)==0) {
  $output.="No Movies found";
} else {
  // Build nav bar
  $grandtotal=mysql_result(doquery("SELECT count(*) FROM ".$from." ".$joins." WHERE ".$where),0);

  if (isset($_GET["showall"])&&$config["showall"]==1) {
    $first="1";
    $last=$grandtotal;
  } else {
    $first=($page*$config["numperpage"])+1;
    $last=$first+$config["numperpage"]-1;
    if ($last>$grandtotal) $last=$grandtotal;
  }

  $nowshowing.="Showing movie #".$first." to #".$last." of ".$grandtotal." total\n";

  if (!isset($_GET["showall"])||$config["showall"]==0) {
    $pages=floor(($grandtotal+$config["numperpage"]-1)/$config["numperpage"]);

    if ($pages>1) {
      if ($page>0) {
        $nav.="<A HREF=\"".addpar($navurl,"page=".($page-1))."\"><-- Previous</A> [";
      } else {
        $nav.="<-- Previous [";
      }

      for ($i=1;$i<=$pages;$i++) {
        if ($page+1==$i) {
          $nav.=" <B>".$i."</B>";
        } else {
          $nav.=" <A HREF=\"".addpar($navurl,"page=".($i-1))."\">".$i."</A>";
        }
      }

      if (($page+1)*$config["numperpage"] < $grandtotal) {
        $nav.=" ] <A HREF=\"".addpar($navurl,"page=".($page+1))."\">Next --></A>\n";
      } else {
        $nav.=" ] Next -->\n";
      }
      if ($config["showall"]==1) $nav.=" (<A HREF=\"".addpar($navurl,"showall=1")."\">Show all</A>)\n";
    }

    // Set base url to current page.
    $url=addpar($url,"page=".$page);
  }


  $cells="";
  if (ckbit(4096,$cols)) $cells .=td("<A CLASS=\"tblhead\" HREF=\"".addpar($url,"order=movieid")."\">ID</A>","40","tablehead"); // added so murray has a unique ref number for all items
  if (ckbit(1,$cols) && saneempty($_GET["userid"])) $cells.=td("<A CLASS=\"tblhead\" HREF=\"".addpar($url,"order=name")."\">Owner</A>","","tablehead");
  if (ckbit(2,$cols)) $cells.=td("<A CLASS=\"tblhead\" HREF=\"".addpar($url,"order=title")."\">Title</A>","250","tablehead");
  if (ckbit(4,$cols) && saneempty($_GET["year"])) $cells.=td("<A CLASS=\"tblhead\" HREF=\"".addpar($url,"order=reldate")."\">Year</A>","","tablehead");
  //if (ckbit(8,$cols)) $cells.=td("Genre(s)","","tablehead");
  if (ckbit(8,$cols) && saneempty($_GET["genreid"])) $cells.=td("<A CLASS=\"tblhead\" HREF=\"".addpar($url,"order=genreid")."\">Genre(s)</A>","","tablehead");
  if (ckbit(16,$cols)) $cells.=td("Comments","","tablehead");
  if (ckbit(32,$cols) && saneempty($_GET["region"])) $cells.=td("<A CLASS=\"tblhead\" HREF=\"".addpar($url,"order=region")."\">Region</A>","","tablehead");
  if (ckbit(8192,$cols) && saneempty($_GET["location"])) $cells.=td("<A CLASS=\"tblhead\" HREF=\"".addpar($url,"order=locationid")."\">Location</A>","","tablehead");
  if (ckbit(64,$cols)) $cells.=td("<A CLASS=\"tblhead\" HREF=\"".addpar($url,"order=runtime")."\">Length</A>","","tablehead");
  if (ckbit(128,$cols) && saneempty($_GET["media"])&&saneempty($_GET["region"])) $cells.=td("<A CLASS=\"tblhead\" HREF=\"".addpar($url,"order=medianame")."\">Media</A>","","tablehead");
  if (ckbit(256,$cols)) $cells.=td("<A CLASS=\"tblhead\" HREF=\"".addpar($url,"order=rating")."\">Rating</A>","80","tablehead");
  if (ckbit(256,$cols)) $cells.=td("<A CLASS=\"tblhead\" HREF=\"".addpar($url,"order=refno")."\">Ref No.</A>","40","tablehead");
  if (ckbit(512,$cols)) $cells.=td("IMDb","","tablehead");
  if (ckbit(1024,$cols)) $cells.=td("Loan","","tablehead");
  if (ckbit(2048,$cols)) $cells.=td("Copy","","tablehead");


  $rows=tr($cells);
  $c=0;

  while ($row=mysql_fetch_assoc($result)) {
    $cells="";
    if ($c++%2==0) $class="tablecell0"; else $class="tablecell1";
    if (ckbit(4096,$cols)) $cells .=td($row["movieid"],"",$class); // added for murray so he has a unique iref number
    if (ckbit(1,$cols) && saneempty($_GET["userid"])) $cells.=td($row["name"],"",$class);
    if (ckbit(2,$cols)) $cells.=td("<A HREF=\"viewmovie.php?movieid=".$row["movieid"]."\">".htmlspecialchars($row["title"])."</A>","",$class);

    if (ckbit(4,$cols) && saneempty($_GET["year"])) $cells.=td($row["reldate"],"",$class);
    if (ckbit(8,$cols)) {
      $genreout=array();
      $tmp=explode(",",$row["genreid"]);
      foreach ($tmp as $val) {
        $genreout[]=$genres[$val];
      }
      $cells.=td(implode(" / ",$genreout),"",$class);
    }
    if (ckbit(16,$cols)) {
      $comments=htmlspecialchars($row["comments"]);
      if ($row["comlen"]>$config["commentchars"]) $comments.=" <A HREF=\"viewmovie.php?movieid=".$row["movieid"]."\">(Read more..)</A>";
      $cells.=td($comments,"",$class);
    }
    if (ckbit(32,$cols)) {
      if (saneempty($_GET["region"])) {
        $region=$row["region"];
        if (saneempty($row["region"])) $region="--";
        $cells.=td($region,"",$class);
      }
    }
    if (ckbit(8192,$cols))
    {
      if (saneempty($_GET["location"]))
      {
        $location=get_location($row["locationid"]);
        if (saneempty($row["locationid"])) $location = "--";
        $cells .= td($location,"",$class);
      }
    }
    if (ckbit(64,$cols)) $cells.=td($row["runtime"],"",$class);
    if (ckbit(128,$cols) && saneempty($_GET["media"]) && saneempty($_GET["region"])) $cells.=td($row["medianame"],"",$class);
    if (ckbit(256,$cols)) {
      $stars="";
      if ($row["rating"]==0) {
        $stars="Not rated";
      } else {
        if ($row["rating"] <=5) {
          $img="themes/".$config["theme"]."/images/blackstar.gif";
        } else {
          $img="themes/".$config["theme"]."/images/redstar.gif";
        }
        for ($i=1;$i<=$row["rating"];$i++) {
          if ($i<6) $stars.="<IMG SRC=\"".$img."\" ALT=\"+\">";
        }
      }
      $cells.=td($stars,"",$class);
    }
    if (ckbit(64,$cols)) $cells.=td($row["refno"],"",$class);
    if (ckbit(512,$cols)) $cells.=td("<A TARGET=\"_blank\" HREF=\"http://us.imdb.com/Tsearch?title=".urlencode($row["title"])."&type=substring&year=".$row["reldate"]."\">IMDb</A>","",$class);
    if (ckbit(1024,$cols)) {
      if (!saneempty($row["loanid"])) {
        $cells.=td("<A HREF=\"listloans.php\">On Loan</A>","",$class);
      } else if ($row["userid"]==$_COOKIE["userid"]) {
        $cells.=td("<A HREF=\"loan.php?movieid=".$row["movieid"]."\">Loan out</A>","",$class);
      } else {
        $cells.=td("","",$class);
      }
    }
    if (ckbit(2048,$cols)) {
      $cells.=td("<a href=\"copymovie.php?movieid=".$row['movieid']."\">copy</a>","",$class); // added by nathan to copy moveis
    }
    $rows.=tr($cells);
  }

  $output.="<BR />\n";

  if (($config["movienav"]&1)==1) $output.=table(tr(td($nowshowing)).tr(td($nav)),0,0,0,"","movienavtop");
  $output.=table($rows,0,1,0,"","datatable");
  if (($config["movienav"]&2)==2) $output.=table(tr(td($nav)).tr(td($nowshowing)),0,0,0,"","movienavbottom");
}

$content["body"] =& $output;
dopage($content);

?>
