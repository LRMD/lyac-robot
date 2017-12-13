#!/usr/bin/php -q
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
// DESC - descending order
$query = "SELECT YEAR(date), COUNT(*) FROM rounds GROUP BY date ORDER BY COUNT(*) DESC LIMIT 1;";
  if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
$row = mysql_fetch_array($result);
mysql_free_result($result);
$year = $row['YEAR(date)'];
fwrite(STDOUT,"LYAC: $year\n");
//
@ $participants = fopen("participants.csv", "w");
  if(!$participants) {
  mysql_close($db_connection);
  fwrite(STDOUT, "ERROR\n");
  exit(1);        
  }
// csv separator is a comma ("sep=;" is a semicolon)
  if (fputs($participants, "sep=,\n") === false) {
  mysql_close($db_connection);
  fclose ($participants);
  fwrite(STDOUT, "ERROR\n");
  exit(1);
  }
$record = array('No','Callsign','50MHz','70MHz','144MHz','432MHz','1296MHz','2300MHz','3400MHz','5700MHz','10GHz','unknown','SSB','CW','AM','FM','RTTY','SSTV','ATV');
fputcsv($participants, $record);
$query = "SELECT callsignID, id2call(callsignID) FROM logs GROUP BY id2call(callsignID);";
  if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
$no = 0;
  while ($row = mysql_fetch_array($result)) {
  $participantID = $row[0];
  $no+=1;
//
    foreach ($record as &$value) {
    $value = '';
    }
//
  $record[0] = $no;
  $record[1] = $row[1];
//
  $query = "SELECT bandID FROM logs WHERE callsignID = $participantID GROUP BY bandID;";
    if (!($bands = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
    while ($row = mysql_fetch_array($bands))  $record[$row[0] + 1] = '1';
  mysql_free_result($bands);    
//
  $query = "SELECT qsorecords.modeID FROM qsorecords INNER JOIN logs ON qsorecords.logID = logs.logID WHERE logs.callsignID = $participantID  GROUP BY modeID;";
    if (!($modes = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));  
    while ($row = mysql_fetch_array($modes))  $record[$row[0] + 10] = '1';
  mysql_free_result($modes);
  fputcsv($participants, $record);
  }
mysql_free_result($result);
fclose ($participants);
fwrite(STDOUT, "Number of participants: $no OK\n");
mysql_close($db_connection);
exit(0);
?>