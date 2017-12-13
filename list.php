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
// Delete the records of this year from tables turnout, activities and list.
$query = "DELETE FROM turnout WHERE YEAR(date) = '$year';";
  if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
$query = "DELETE FROM activities WHERE YEAR(date) = '$year';";
  if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));  
$query = "DELETE FROM list WHERE YEAR(created) = '$year';";
//  if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
// Cycle of bands and rounds
$query = "SELECT rounds.date, bands.bandID FROM bands INNER JOIN bands_groups ON bands.bandID = bands_groups.bandID INNER JOIN rounds ON bands_groups.group_bands = rounds.group_bands ORDER BY rounds.date;";
  if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  while ($row = mysql_fetch_array($result)) {
  $date = $row[0];
  $bandID = $row[1];
// Table list
// Cycle of logs
  $query = "SELECT logs.callsignID, logs.wwlID, messages.emailID FROM logs
   INNER JOIN attachments ON logs.attachmentID = attachments.attachmentID
    INNER JOIN messages ON attachments.sourceID = messages.messageID
     WHERE logs.date = '$date' and logs.bandID = '$bandID' and attachments.source = 'email';";
    if (!($LOGs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));      
    while ($logs = mysql_fetch_array($LOGs)) {
    $callsignID = $logs[0];
    $wwlID = $logs[1];
    $emailID = $logs[2];
//
    $query = "SELECT listID FROM list WHERE callsignID = '$callsignID' and wwlID = '$wwlID' and bandID = $bandID";
      if (!($HAMs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
    $num_results = mysql_num_rows($HAMs);
    $row = mysql_fetch_array($HAMs);
    mysql_free_result($HAMs);
    $listID = $row['listID']; 
      if ($num_results == 0) {
      $query = "INSERT INTO list VALUES (NULL, $callsignID, $wwlID, $bandID, '$date', 'Yes');";
        if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
      $listID =  mysql_insert_id();
      }
      else  {
      $query = "UPDATE list SET valid = 'Yes' WHERE listID = $listID;";
        if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
      }
    $query = "SELECT * FROM activities WHERE listID = $listID and emailID = $emailID and date = '$date';";
      if (!($HAMs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
    $num_results = mysql_num_rows($HAMs);
    mysql_free_result($HAMs);      
      if ($num_results == 0) {
      $query = "INSERT INTO activities VALUES ($listID, '$date', $emailID);";
        if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
      $query = "DELETE FROM turnout WHERE listID = $listID AND date = '$date';";
        if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));  
      }
    }
  mysql_free_result($LOGs);
// If absent log
  $query = "SELECT qsorecords.callsign FROM qsorecords INNER JOIN logs ON qsorecords.logID = logs.logID WHERE logs.date = '$date' and logs.bandID = '$bandID' GROUP BY callsign HAVING COUNT(*) > 1";
    if (!($QSOs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
    while ($qsos = mysql_fetch_array($QSOs)) {
    $callsign = $qsos[0];    
//    $query = "SELECT qsorecords.gridsquare FROM qsorecords INNER JOIN logs ON qsorecords.logID = logs.logID WHERE logs.date = '$date' and logs.bandID = '$bandID' and qsorecords.callsign = '$callsign' GROUP BY gridsquare";
// Find two most frequent gridsquare values
    $query = "SELECT qsorecords.gridsquare, COUNT(*) FROM qsorecords INNER JOIN logs ON qsorecords.logID = logs.logID WHERE logs.date = '$date' and logs.bandID = $bandID and qsorecords.callsign = '$callsign' GROUP BY gridsquare ORDER BY COUNT(*) DESC LIMIT 2;";
      if (!$WWLs = mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
      if (mysql_num_rows($WWLs) == 2) {
      $wwls = mysql_fetch_array($WWLs);
      $gridsquare = $wwls[0];
      $first_freq = $wwls[1];
      $wwls = mysql_fetch_array($WWLs);
      $second_freq = $wwls[1];
      }
      else if (mysql_num_rows($WWLs) == 1)  {
      $wwls = mysql_fetch_array($WWLs);
      $gridsquare = $wwls[0];
      $first_freq = $wwls[1];
      $second_freq = 0;
      }
      else  {
      $gridsquare = "";
      $first_freq = 0;
      $second_freq = 0;      
      }
    mysql_free_result($WWLs);
      if ($first_freq > $second_freq && $first_freq > 1) {
//
      $query = "SELECT * FROM callsigns WHERE callsign = '$callsign';";
        if (!($CALLSIGNs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
        if (mysql_num_rows($CALLSIGNs) == 0) {
        $query = "INSERT INTO callsigns VALUES (NULL, '$callsign');";
          if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
        }       
      mysql_free_result($CALLSIGNs);
//
      $query = "SELECT * FROM wwls WHERE wwl = '$gridsquare';";
        if (!($WWLs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
        if (mysql_fetch_array($WWLs) == 0) {
        $query = "INSERT INTO wwls VALUES (NULL, '$gridsquare');";
          if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
        $gridsquareID =  mysql_insert_id();
        }
//
      $query = "SELECT * FROM list WHERE callsignID = call2id('$callsign') and wwlID = wwl2id('$gridsquare') and bandID = $bandID";
        if (!($HAMs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
      $num_results = mysql_num_rows($HAMs);
      $row = mysql_fetch_array($HAMs);
      mysql_free_result($HAMs);
      $listID = $row['listID']; 
        if ($num_results == 0) {
        $query = "INSERT INTO list VALUES (NULL, call2id('$callsign'), wwl2id('$gridsquare'), $bandID, '$date', 'Yes');";
          if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
        $listID =  mysql_insert_id();
        }
// Participation history table
      $query = "SELECT listID FROM turnout WHERE listID = $listID and date = '$date'
       UNION
       SELECT listID FROM activities WHERE listID = $listID and date = '$date';";
        if (!$history = mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
      $num_results = mysql_num_rows($history);
      mysql_free_result($history);
        if ($num_results == 0) {
        $query = "INSERT INTO turnout VALUES ($listID, '$date');";
          if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));  
        }
      }
    }
  mysql_free_result($QSOs);        
  }
mysql_free_result($result);
//
$result = mysql_query("SELECT count(*) FROM list;");
$row = mysql_fetch_row($result);
$num = $row[0];
mysql_free_result($result);  
fwrite(STDOUT, "Number of rows table 'list' : $num OK\n");
mysql_close($db_connection);
exit(0);
?>
