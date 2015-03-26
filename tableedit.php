<?php
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");

$config=getconfig();

$output="";
$rows="";
$cells="";

if ($admin==FALSE) {
        header("Location: index.php");
        die();
}

$tables=array();
$tables[]=array("name" => "genre", "key" => "id", "desc" => "name", "usedin" => "movie", fkey => "genreid");
$tables[]=array("name" => "media", "key" => "id", "desc" => "name", "usedin" => "movie", fkey => "mediaid");
$tables[]=array("name" => "region", "key" => "code", "desc" => "name", "usedin" => "user", fkey => "regioncode");
$tables[]=array("name" => "country", "key" => "code", "desc" => "name", "usedin" => "user", fkey => "countrycode");
$tables[]=array("name" => "location", "key" => "id", "desc" => "name", "usedin" => "movie", fkey => "locationid");

$action=$_GET["action"];

if (!saneempty($action)) {
  // Get the table info
  foreach ($tables as $table) {
    if ($table["name"]==$_GET["table"] || $table["name"]==$_POST["table"]) $data=$table;
  }
  switch($action) {
    case "edit":
      $value="";
      $output.=form_begin("tableedit.php","GET");
      $output.=input_hidden("action","commit");
      $output.=input_hidden("table",$_GET["table"]);
      if ($_GET["id"] != "0") {
        $result=mysql_fetch_row(doquery("select ".$data["desc"]." from ".$data["name"]." where ".$data["key"]." = \"".greatescape($_GET["id"])."\""));
        $value=$result[0];
        $output.=input_hidden("iu","u");
      } else {
        $output.=input_hidden("iu","i");
      }

      $output.="Edit ".$data["name"].":<BR />\n".input_text("desc",30,64,$value)."<BR />\n";

      if ($data["name"] != "genre" && $data["name"] != "media" && $data["name"] != "location" && $_GET["id"]=="0") {
        $output.="2 digit ".$data["name"]." code:<BR />\n".input_text("id",2,4)."<BR />\n";
      } else {
        $output.=input_hidden("id",$_GET["id"]);
      }

      $output.=submit("Save");
      $output.=form_end();
    break;
    case "delete":
      $result=mysql_fetch_row(doquery("select count(*) from ".$data["usedin"]." where ".$data["fkey"]." = \"".greatescape($_GET["id"])."\""));
      if ($result[0]!=0) {
        $output.="This ".$data["name"]." is in use and cannot be deleted until records using this value have been removed\n";
      } else {
        doquery("delete from ".$data["name"]." where ".$data["key"]." = \"".greatescape($_GET["id"])."\"");
        header("Location: tableedit.php");
        die();
      }
    break;
    case "commit":
      if ($_GET["iu"]=="i") {
        if ($data["name"]=="genre"||$data["name"]=="media"||$data["name"]=="location") {
          $query="insert ".$data["name"]." values (NULL, \"".greatescape($_GET["desc"])."\")";
        } else {
          $result=doquery("select * from ".$data["name"]." where ".$data["key"]." = \"".greatescape($_GET["id"])."\"");
          if (mysql_num_rows($result)!=0) {
            $output.=greatescape($_GET["id"])." is already in use. Pick another code\n";
          } else {
            $query="insert ".$data["name"]." values (\"".greatescape($_GET["id"])."\",\"".greatescape($_GET["desc"])."\")";
          }
        }
      } else {
        if ($data["name"]=="genre"||$data["name"]=="media"||$data["name"]=="location") {
          $query="update ".$data["name"]." set name = \"".greatescape($_GET["desc"])."\" where id=\"".intval($_GET["id"])."\"";
        } else {
          $query="update ".$data["name"]." set ".$data["desc"]." = \"".greatescape($_GET["desc"])."\" where ".$data["key"]." = \"".greatescape($_GET["id"])."\"";
        }
      }
      if (saneempty($output)) {
        doquery($query);
        header("Location: tableedit.php");
        die();
      }
    break;
  }
} else {
  foreach ($tables as $table) {
    $result=doquery("select ".$table["key"].", ".$table["desc"]." from ".$table["name"]." order by ".$table["desc"]);
    $rows=tr(td(ucwords($table["key"]),"","tablehead").td(ucwords($table["name"]),"","tablehead").td("Action","","tablehead"));
    $c=0;

    while ($row=mysql_fetch_row($result)) {
      if ($c++%2==0) $class="tablecell0"; else $class="tablecell1";
      $rows.=tr(td($row[0],"",$class).td($row[1],"",$class).td("<A HREF=\"tableedit.php?action=edit&table=".$table["name"]."&id=".$row[0]."\">Edit</A> | <A HREF=\"tableedit.php?action=delete&table=".$table["name"]."&id=".$row[0]."\">Delete</A>","",$class));
    }

    $form=form_begin("tableedit.php","GET");
    $form.=input_hidden("table",$table["name"]);
    $form.=input_hidden("action","edit");
    $form.=input_hidden("id",0);
    $form.=submit("New ".$table["name"]);
    $form.=form_end();

    $cells.=td($form.table($rows,0,1,0,"","configtable"),"","","CENTER","TOP");
  }

  $output.=table(tr($cells),0,0,0,"100%");
}

$content["body"] =& $output;
dopage($content);

?>
