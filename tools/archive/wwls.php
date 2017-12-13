<?php
require_once 'database.inc';
// A constant to be used as an error return status
define ('DB_CONNECTION_FAILED',1);
// Try connecting to MySQL
@ $db_connection = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
  if  (!$db_connection) {
    // Write to STDERR
    fwrite(STDERR,mysql_error()."\n");
    exit(DB_CONNECTION_FAILED);
  }
mysql_select_db(DB_NAME);
fwrite(STDOUT,"Connected to database\n");
//
@ $wwls = fopen("wwls.csv", "w");
  if(!$wwls) {
  mysql_close($db_connection);
  fwrite(STDOUT, "ERROR\n");
  exit(1);        
  }
// csv separator is a comma ("sep=;" is a semicolon)
  if (fputs($wwls, "sep=,\n") === false) {
  mysql_close($db_connection);
  fclose ($wwls);
  fwrite(STDOUT, "ERROR\n");
  exit(1);
  }
fputs($wwls, "No,wwl,Created,Date\n");
// Cycle of wwls
$query = "SELECT * FROM wwls;";
  if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
$Nr = 0;
  while ($row = mysql_fetch_array($result)) {
  $Nr += 1;
  fputs($wwls, "$Nr,$row[1],$row[2],$row[3]\n");        
  }
mysql_free_result($result);
fclose ($wwls);
fwrite(STDOUT, "OK\n");
mysql_close($db_connection);
exit(0);
?>
