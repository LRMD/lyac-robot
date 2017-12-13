#!/usr/bin/php -q
<?php

require_once 'database.inc';
// A constant to be used as an error return status
define ('DB_CONNECTION_FAILED',1);
date_default_timezone_set("Europe/Vilnius");
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
$query = "UPDATE qsorecords SET confirm = b'00000000';";
  if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));  
// Cycle of bands and rounds
$query = "SELECT rounds.date, bands.bandID FROM bands INNER JOIN bands_groups ON bands.bandID = bands_groups.bandID INNER JOIN rounds ON bands_groups.group_bands = rounds.group_bands";
  if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  while ($row = mysql_fetch_array($result)) {
  $date = $row[0];
  $bandID = $row[1];
// Cycle of logs and QSOs
  $query = "SELECT qsorecords.qsoID, qsorecords.callsign, qsorecords.gridsquare, logs.callsignID, qsorecords.time, qsorecords.rst_r FROM qsorecords
   INNER JOIN logs ON logs.logID = qsorecords.logID WHERE logs.date = '$date' and logs.bandID = $bandID;";
    if (!($QSOs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));      
    while ($qsos = mysql_fetch_array($QSOs)) {
    $qsoID = $qsos[0];
    $callsign = $qsos[1]; // to find reversal log
    $gridsquare = $qsos[2];
    $callsignID = $qsos[3]; // to find reversal QSO
    $time = $qsos[4];
    $rst_r = $qsos[5];
    $confirm = 0; // confirm code of this QSO
//  Find reversal log record to check
    $query = "SELECT logID FROM logs
     WHERE date = '$date' AND bandID = $bandID AND callsignID = call2id('$callsign') LIMIT 1;";
      if (!($reverseLOG = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
      if (mysql_num_rows($reverseLOG) == 1) {  // if is reversal log
//  Find reversal QSO record to check
      $query = "SELECT qsorecords.time, id2wwl(logs.wwlID), qsorecords.rst_s FROM qsorecords INNER JOIN logs ON qsorecords.logID = logs.logID WHERE logs.date = '$date' and logs.bandID = $bandID and logs.callsignID = call2id('$callsign') and qsorecords.callsign = id2call($callsignID);";
        if (!($reverseQSOs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
        if (mysql_num_rows($reverseQSOs) == 1)  {
        $row = mysql_fetch_array($reverseQSOs);
        mysql_free_result($reverseQSOs);  //  Reverse log
        $Rtime = $row[0];
        $Rwwl = $row[1];
        $rst_s = $row[2]; // use to check
//  Bitwise Operators
        $confirm = $confirm | 64;
/*
//  QSO control numbers
        if ( ... )  $confirm = $confirm | 8;
*/
//        $deltaMinutes = round(abs(strtotime($time) - strtotime($Rtime)) / 60, 0, PHP_ROUND_HALF_DOWN);
        $deltaMinutes = round(abs(strtotime($time) - strtotime($Rtime)) / 60, 2);
//  Times of QSOs (10-minute rule)
//          if (round(abs(strtotime($time) - strtotime($Rtime)) / 60, 2) < 10)  $confirm = $confirm | 4;
//          if ($deltaMinutes < 10)  $confirm = $confirm | 4;
          if ($deltaMinutes%60 < 10 || $deltaMinutes%60 > 50)  $confirm = $confirm | 4; // % - remainder after division
//  Gridsquares (WWLs) of QSOs
          if ($gridsquare == $Rwwl)  $confirm = $confirm | 2;
//  RST (RS) reports of QSOs
          if ($rst_r == $rst_s && $rst_r <> 0)  $confirm = $confirm | 1;
        }
//  N = Not-In-Log
        else  $confirm = 255; // ERROR (reverse log exists, but reverse QSO absent)  
      }
      else  { // if absent reversal log
      $query = "SELECT listID FROM list WHERE id2call(callsignID) = '$callsign' AND id2wwl(wwlID) = '$gridsquare' AND bandID = $bandID AND valid = 'Yes' LIMIT 1;";
        if (!($archive = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
        if (mysql_num_rows($archive) == 1) {  // if is archive records
        $row = mysql_fetch_array($archive);
        mysql_free_result($archive);
        $listID = $row['listID'];
        $query = "SELECT date FROM turnout WHERE listID = $listID AND date <= '$date' ORDER BY date DESC LIMIT 2;";
          if (!$history = mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
        $num_turnout = mysql_num_rows($history);
        $row = mysql_fetch_array($history);
        $turnout_date = $row[0];
        mysql_free_result($history);
        $query = "SELECT date FROM activities WHERE listID = $listID AND date <= '$date' ORDER BY date DESC LIMIT 2;";
          if (!$history = mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
        $num_activities = mysql_num_rows($history);
        mysql_free_result($history);        
          if ($num_turnout > 0 && $turnout_date == $date)  $confirm = $confirm | 32;
          else if ($num_turnout > 0 || $num_activities > 0)  $confirm = $confirm | 16;
        }
      }
    $query = "UPDATE qsorecords SET confirm = $confirm WHERE qsoID = $qsoID;";
      if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
    mysql_free_result($reverseLOG);
    }
  mysql_free_result($QSOs);            
  }
mysql_free_result($result);
//
$result = mysql_query("SELECT count(*) FROM qsorecords;");
$row = mysql_fetch_row($result);
$num = $row[0];
mysql_free_result($result);
fwrite(STDOUT, "Number of rows table 'qsorecords' : $num OK\n");
mysql_close($db_connection);
exit(0);
?>
