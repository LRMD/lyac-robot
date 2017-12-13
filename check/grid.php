<?php
require_once 'database.inc';
// A constant to be used as an error return status
define ('DB_CONNECTION_FAILED',1);
// Try connecting to MySQL
@ $db_connection = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
  if  (!$db_connection) {
  fwrite(STDERR,mysql_error()."\n");
  exit(DB_CONNECTION_FAILED);
  }
mysql_select_db(DB_NAME);
fwrite(STDOUT,"Connected to database\n");
fwrite(STDOUT,"Date(YYYY-MM-DD): ");
$round_date = fgets(STDIN);
// only digits and "-" for mysql
$round_date = preg_replace('/[\x00-\x2C\x2E-\x2F\x3A-\xFF]/', '', $round_date);
$round_date = check_sql_date($round_date);
  if ($round_date === FALSE) {
  fwrite(STDERR,"Invalid date format YYYY-MM-DD \n");
  mysql_close($db_connection);
  exit(DB_CONNECTION_FAILED);  
  }
//
$query = "SELECT * FROM rounds WHERE date = '$round_date';";
  if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  if (mysql_num_rows($result) <> 1) {
  fwrite(STDERR,"Wrong date of the LYAC round");
  mysql_close($db_connection);
  exit(DB_CONNECTION_FAILED);  
  }
mysql_free_result($result);
//
$query = "SELECT bands.bandID, bands.band FROM bands INNER JOIN bands_groups ON bands.bandID = bands_groups.bandID INNER JOIN rounds ON bands_groups.group_bands = rounds.group_bands WHERE rounds.date = '$round_date';";
  if (!($BANDs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
// The Band cycle
  while ($bands = mysql_fetch_array($BANDs)) {
  $bandID = $bands[0];
  $band = $bands[1];
//
  $query = "SELECT logID, id2call(callsignID), id2wwl(wwlID) FROM logs WHERE logs.date = '$round_date' and logs.bandID = $bandID;";
    if (!($LOGs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
    if (mysql_num_rows($LOGs) == 0) break;
// Logs cycle
  $nr = 0;
    while ($log = mysql_fetch_array($LOGs)) {
    $nr += 1;
    $logID = $log[0];
    $callsign = $log[1];
    $wwl = $log[2];
    $query = "SELECT gridsquare, COUNT(*) FROM qsorecords WHERE logID IN (SELECT logID FROM logs WHERE id2call(callsignID) IN (SELECT callsign FROM qsorecords  WHERE logID = $logID) AND date = '$round_date' AND bandID = $bandID) AND callsign='$callsign' GROUP BY gridsquare ORDER BY COUNT(*);";
      if (!$WWLs = mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
      if  (mysql_num_rows($WWLs) == 0)  {
      fwrite(STDOUT,"$nr\t$callsign\t?\n");
      mysql_free_result($WWLs);
      continue;
      }
      while ($wwls = mysql_fetch_array($WWLs)) {
        if  ($wwls[0] == $wwl)  break;
      }
    mysql_free_result($WWLs);
      if  ($wwls[0] == $wwl) fwrite(STDOUT,"$nr\t$callsign\tOK\n");
      else  fwrite(STDOUT,"$nr\t$callsign\tERROR\n");    
    }
  mysql_free_result($LOGs);
  }
mysql_free_result($BANDs);
fwrite(STDOUT, "OK\n");
mysql_close($db_connection);
exit(0);
function check_sql_date($date)  {
//  YYYY-MM-DD format
$pattern = '/^(19[0-9][0-9]|20[0-9][0-9])-(0[1-9]|1[0-2])-(0[1-9]|1[0-9]|2[0-9]|3[0-1])$/m';
  if(!preg_match($pattern, $date, $matches))  return FALSE;
return $matches[0];
}
?>