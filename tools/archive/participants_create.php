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
$query = "SELECT participantID FROM participants;";
  if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  while ($row = mysql_fetch_array($result)) {
  $participantID = $row[0];
  $query = "SELECT MIN(date), MAX(date) FROM logs WHERE participantID = $participantID ORDER BY date ASC;";
    if (!($LOGs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
    if (mysql_num_rows($LOGs) == 1) {
    $logs = mysql_fetch_array($LOGs);   
    $created = $logs[0];
    $date = $logs[1];
    mysql_free_result($LOGs);
    fwrite(STDOUT, "$participantID\t$created\t$date\n");
    $query = "UPDATE participants SET created = '$created', date = '$date' WHERE participantID = $participantID;";
      if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
    } 
  }
mysql_free_result($result);
fwrite(STDOUT, "OK\n");
mysql_close($db_connection);
exit(0);
?>