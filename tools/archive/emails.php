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

// Cycle of bands and rounds
$query = "SELECT rounds.date, bands.bandID FROM bands INNER JOIN bands_groups ON bands.bandID = bands_groups.bandID INNER JOIN rounds ON bands_groups.group_bands = rounds.group_bands";
  if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  while ($row = mysql_fetch_array($result)) {
  $date = $row[0];
  $bandID = $row[1];
// Cycle of logs
  $query = "SELECT messages.emailID FROM logs INNER JOIN attachments ON logs.attachmentID = attachments.attachmentID INNER JOIN messages ON attachments.messageID = messages.messageID WHERE logs.date = '$date' and logs.bandID = '$bandID'";
    if (!($LOGs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));      
    while ($logs = mysql_fetch_array($LOGs)) {
    $emailID = $logs[0];
    $query = "UPDATE emails SET date = '$date' WHERE emailID = '$emailID'";
      if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  )); 
    }
  mysql_free_result($LOGs);        
  }
mysql_free_result($result);
//
@ $emails = fopen("emails.csv", "w");
  if(!$emails) {
  mysql_close($db_connection);
  fwrite(STDOUT, "ERROR\n");
  exit(1);        
  }
// csv separator is a comma ("sep=;" is a semicolon)
  if (fputs($emails, "sep=,\n") === false) {
  mysql_close($db_connection);
  fclose ($emails);
  fwrite(STDOUT, "ERROR\n");
  exit(1);
  }
fputs($emails, "No,e-mail,Created,Date\n");
$query = "SELECT * FROM emails";
  if (!($eMail = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
$Nr = 0;
  while ($row = mysql_fetch_array($eMail)) {
  $Nr += 1;
  fputs($emails, "$Nr,$row[1],$row[2],$row[3]\n");
  }
mysql_free_result($eMail);  
fclose ($emails);
fwrite(STDOUT, "OK\n");
mysql_close($db_connection);
exit(0);
?>
