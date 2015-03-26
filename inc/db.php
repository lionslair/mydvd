<?
$sql_vars = array(
  host => "localhost",      // SQL server host
  username => "dvd",      // Username
  password => "toephoutriuPhi4c",    // Password
  dbname => "dvd",      // Database name
  db => 0,        // Database handle
);

function sql_conn()
{
  global $sql_vars;

  $db = mysql_connect($sql_vars["host"], $sql_vars["username"], $sql_vars["password"]) or die(mysql_error($db));
  $sql_vars["db"] = $db;
  mysql_select_db($sql_vars["dbname"], $db) or die(mysql_error($db));
}

function doquery($query)
{
  $result=mysql_query($query);
  return $result;
}

function restoarray($resdata)
{
  while ($row=mysql_fetch_row($resdata)) {
    $data[]=$row;
  }
  return $data;
}

sql_conn();

?>
