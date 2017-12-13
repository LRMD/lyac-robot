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
// Truncate table contacts
$query = "TRUNCATE TABLE contacts;";
  if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
// Cycle of bands and rounds
$query = "SELECT rounds.date, bands.bandID FROM bands INNER JOIN bands_groups ON bands.bandID = bands_groups.bandID INNER JOIN rounds ON bands_groups.group_bands = rounds.group_bands ORDER BY rounds.date;";
  if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  while ($row = mysql_fetch_array($result)) {
  $date = $row[0];
  $bandID = $row[1];
// Cycle of logs
  $query = "SELECT callID(logs.participantID), logs.wwlID, messages.emailID FROM logs INNER JOIN attachments ON logs.attachmentID = attachments.attachmentID INNER JOIN messages ON attachments.messageID = messages.messageID WHERE logs.date = '$date' and logs.bandID = '$bandID';";
    if (!($LOGs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));      
    while ($logs = mysql_fetch_array($LOGs)) {
    $callsignID = $logs[0];
    $wwlID = $logs[1];
    $emailID = $logs[2];
    if(!($qResult = mysql_query("SELECT listID FROM list WHERE callsignID = '$callsignID' and wwlID = '$wwlID' and bandID = $bandID;"))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
    $num_results = mysql_num_rows($qResult);
      if ($num_results == 1)  {
      $qValues=mysql_fetch_assoc($qResult);
      $listID = $qValues["listID"];
      }
      else  {
      fwrite(STDOUT,"Unexpected error\n");
      mysql_close($db_connection);
      exit(DB_CONNECTION_FAILED);
      }
    mysql_free_result($qResult);
    $query = "SELECT contactID FROM contacts WHERE emailID = $emailID and listID = $listID;";
      if (!($HAMs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
    $num_results = mysql_num_rows($HAMs);
    $row = mysql_fetch_array($HAMs);
    mysql_free_result($HAMs);
    $contactID = $row['contactID'];      
      if ($num_results == 0) {
      $query = "INSERT INTO contacts VALUES (NULL, $listID, $emailID, '$date', '$date');";
        if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
      $contactID = mysql_insert_id();
      }
      else  {
      $query = "UPDATE contacts SET date = '$date' WHERE contactID = $contactID;";
        if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
      }
    }
  mysql_free_result($LOGs);            
  }
mysql_free_result($result);
fwrite(STDOUT, "OK\n");
mysql_close($db_connection);
exit(0);
?>