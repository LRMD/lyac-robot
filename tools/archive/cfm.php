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
// Read the input
fwrite(STDOUT,"Date(YYYY-MM-DD): ");
$logs_date = fgets(STDIN);
// only digits and "-" for mysql
$log_date = preg_replace('/[\x00-\x2C\x2E-\x2F\x3A-\xFF]/', '', $log_date);
$logs_date = check_sql_date($logs_date);
  if ($logs_date === FALSE) {
    // Write to STDERR
    fwrite(STDERR,"Invalid date format YYYY-MM-DD \n");
    mysql_close($db_connection);
    exit(DB_CONNECTION_FAILED);  
  }
//
$query = "SELECT * FROM rounds WHERE date = '$logs_date'";
  if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  if (mysql_num_rows($result) <> 1) {
    // Write to STDERR
    fwrite(STDERR,"Wrong date of the LYAC round");
    mysql_close($db_connection);
    exit(DB_CONNECTION_FAILED);  
  }
$row = mysql_fetch_array($result);
$date = $row[1];
mysql_free_result($result);
// Delete Cross-reference table QSOs records of $date
/*  $query = "DELETE FROM qsopairs WHERE qsoID1 IN (SELECT qsorecords.qsoID FROM qsorecords INNER JOIN logs ON qsorecords.logID = logs.logID WHERE logs.date = '$date');";
  if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));  */
$query = "DELETE FROM qsopairs WHERE qsoID2 IN (SELECT qsorecords.qsoID FROM qsorecords INNER JOIN logs ON qsorecords.logID = logs.logID WHERE logs.date = '$date');";
  if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
$query = "UPDATE qsorecords SET confirm = b'00000000' WHERE logID IN (SELECT logID FROM logs WHERE date = '$date');";
  if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
// Delete records, where are created of $date, from list, activities and turnout tables
$query = "DELETE FROM list WHERE created = '$date';";
//  if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
$query = "DELETE FROM activities WHERE created = '$date';";
//  if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
$query = "DELETE FROM turnout WHERE created = '$date';";
//  if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
// Cycle of bands
$query = "SELECT bands_groups.bandID FROM bands_groups INNER JOIN rounds ON bands_groups.group_bands = rounds.group_bands WHERE rounds.date = '$date'";
  if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  while ($row = mysql_fetch_array($result)) {
  $bandID = $row[0];
// Prepare list table (add new records of $date)

// Prepare QSOs cross reference table of $date
// Cycle of logs and QSOs
  $query = "SELECT logs.logID, callID(logs.participantID), qsorecords.qsoID ,qsorecords.callsign FROM logs INNER JOIN qsorecords ON qsorecords.logID = logs.logID INNER JOIN bands ON bands.bandID = logs.bandID WHERE logs.date = '$date' and logs.bandID = $bandID";
    if (!($QSOs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));      
    while ($qsos = mysql_fetch_array($QSOs)) {
    $logID = $qsos[0];
    $callsignID = $qsos[1];    
    $qsoID = $qsos[2];
    $callsign = $qsos[3];
//  Find reversal QSO record
    $query = "SELECT qsorecords.qsoID FROM qsorecords INNER JOIN logs ON qsorecords.logID = logs.logID WHERE logs.date = '$date' and logs.bandID = $bandID and logs.participantID = (SELECT participantID FROM participants WHERE callsignID = call2id('$callsign')) and qsorecords.callsign = (SELECT callsign FROM callsigns WHERE callsignID = $callsignID);";
      if (!($reverseQSOs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
      if (mysql_num_rows($reverseQSOs) == 1)  {
      $row = mysql_fetch_array($reverseQSOs);
      $reverseID = $row[0];
      $query = "UPDATE qsorecords SET confirm = confirm | b'01000000' WHERE qsoID = $qsoID or qsoID = $reverseID;";
        if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
/*      $query = "SELECT * FROM qsopairs WHERE (qsoID1 = $qsoID and qsoID2 = $reverseID) or (qsoID1 = $reverseID and qsoID2 = $qsoID)";
        if (!($XREF = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
        if (mysql_num_rows($XREF) == 0) {
        $query = "INSERT INTO qsopairs VALUES ($qsoID, $reverseID)";
          if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));    
        }
      mysql_free_result($XREF); */



      }
    mysql_free_result($reverseQSOs); 
    }
  mysql_free_result($QSOs);            
  }
mysql_free_result($result);



fwrite(STDOUT, "OK\n");
mysql_close($db_connection);
exit(0);
function check_sql_date($date)  {
//  YYYY-MM-DD format
$pattern = '/^(19[0-9][0-9]|20[0-9][0-9])-(0[1-9]|1[0-2])-(0[1-9]|1[0-9]|2[0-9]|3[0-1])$/m';
  if(!preg_match($pattern, $date, $matches))
  {
    return FALSE;
  }
return $matches[0];
}
?>
