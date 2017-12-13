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
// Read the input
fwrite(STDOUT,"Date(YYYY-MM-DD): ");
$log_date = fgets(STDIN);
// only digits and "-" for mysql
$log_date = preg_replace('/[\x00-\x2C\x2E-\x2F\x3A-\xFF]/', '', $log_date);
$log_date = check_sql_date($log_date);
  if ($log_date === FALSE) {
    // Write to STDERR
    fwrite(STDERR,"Invalid date format YYYY-MM-DD \n");
    mysql_close($db_connection);
    exit(DB_CONNECTION_FAILED);  
  }
//
$query = "SELECT name FROM rounds WHERE date = '$log_date'";
  if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  if (mysql_num_rows($result) <> 1) {
    // Write to STDERR
    fwrite(STDERR,"Wrong date of the LYAC round");
    mysql_close($db_connection);
    exit(DB_CONNECTION_FAILED);  
  }
$row = mysql_fetch_array($result);
$round_name = $row[0];
mysql_free_result($result);
//
$query = "SELECT bands.bandID, bands.band FROM bands INNER JOIN bands_groups ON bands.bandID = bands_groups.bandID INNER JOIN rounds ON bands_groups.group_bands = rounds.group_bands WHERE rounds.date = '$log_date'";
  if (!($BANDs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
// The Band cycle
  while ($bands = mysql_fetch_array($BANDs)) {
  $bandID = $bands[0];
  $band = $bands[1];
//
  $query = "SELECT logs.logID, bands.band_freq, callsigns.callsign, wwls.wwl FROM logs INNER JOIN callsigns ON callsigns.callsignID = logs.callsignID INNER JOIN wwls ON wwls.wwlID = logs.wwlID INNER JOIN bands ON bands.bandID = logs.bandID WHERE logs.date = '$log_date' and logs.bandID = '$bandID' ORDER BY callsigns.callsign";
    if (!($LOGs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
    if (mysql_num_rows($LOGs) == 0) break;
//
  @ $QSO_xref_log = fopen($band . "_xref_" . $log_date . ".csv", "w");
    if(!$QSO_xref_log) {
    mysql_close($db_connection);
    fwrite(STDOUT, "ERROR\n");
    exit(1);        
    }
// csv separator is a comma ("sep=;" is a semicolon)
    if (fputs($QSO_xref_log, "sep=,\n") === false) {
    mysql_close($db_connection);
    fclose ($QSO_xref_log);
    fwrite(STDOUT, "ERROR\n");
    exit(1);
    }
  fputs($QSO_xref_log, ",,,,,,,,,,,,,\n");
// Logs cycle
  $logNr = 0;
    while ($log = mysql_fetch_array($LOGs)) {
    $logNr += 1;
    $query = "SELECT time, id2mode(modeID), rst_s, rst_r, callsign, gridsquare FROM qsorecords WHERE logID = '$log[0]'";
      if (!($QSOs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
    fputs($QSO_xref_log, "LOG,Date,Band,,,Callsign,,,,,,,,Grid\n");  
    fputs($QSO_xref_log, "$logNr,$log_date,$log[1],,,$log[2],,,,,,,,$log[3]\n");
    fputs($QSO_xref_log, "QSO,Time,Mode,RST_S,RST_R,Callsign,Grid,QRB(km),CrossReference,Time,Mode,RST_R,RST_S,Grid\n");
// QSOs cycle
    $qsoNr = 0;
      while ($qso = mysql_fetch_array($QSOs)) {
      $qsoNr += 1;
      $QSOdistance = (int)QRB($log[3], $qso[5]);
        if ($QSOdistance == 0) $QSOdistance = 1;
      $QSObearing = QTE($log[3], $qso[5]);
      $query = "SELECT logs.logID, id2call(logs.callsignID), id2wwl(logs.wwlID) FROM logs INNER JOIN callsigns ON callsigns.callsignID = logs.callsignID WHERE logs.date = '$log_date' and logs.bandID = $bandID and callsigns.callsign = '$qso[4]';";
        if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
        if (mysql_num_rows($result) == 1)  {
        $row = mysql_fetch_array($result);
        $reverse_log = $row[0];
        $reverse_sign = $row[1];
        $reverse_wwl = $row[2];
        mysql_free_result($result);
        $query = "SELECT time, id2mode(modeID), rst_s, rst_r, gridsquare FROM qsorecords WHERE logID = '$reverse_log' and callsign = '$log[2]'";
          if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
          if (mysql_num_rows($result) == 1)  {
          $row = mysql_fetch_array($result);
          fputs($QSO_xref_log, "$qsoNr,$qso[0],$qso[1],$qso[2],$qso[3],$qso[4],$qso[5],$QSOdistance,<->,$row[0],$row[1],$row[3],$row[2],$row[4]\n");
          }
          else  {
          fputs($QSO_xref_log, "$qsoNr,$qso[0],$qso[1],$qso[2],$qso[3],$qso[4],$qso[5],$QSOdistance,ERROR,,,,,\n");
          }
          mysql_free_result($result);
        }
        else  {
        mysql_free_result($result);
        $query = "SELECT callsigns.callsign FROM callsigns INNER JOIN logs ON callsigns.callsignID = logs.callsignID INNER JOIN qsorecords ON logs.logID = qsorecords.logID WHERE logs.date = '$log_date' and logs.bandID = '$bandID' and qsorecords.callsign = '$qso[4]' ORDER BY callsigns.callsign";
          if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
        $number = mysql_num_rows($result);
        fputs($QSO_xref_log, "$qsoNr,$qso[0],$qso[1],$qso[2],$qso[3],$qso[4],$qso[5],$QSOdistance,MISSING,$number,,,,");
        mysql_free_result($result);
        fputs($QSO_xref_log, "\n");
        }
      }
    mysql_free_result($QSOs);
    fputs($QSO_xref_log, ",,,,,,,,,,,,,\n"); 
    }
  fclose ($QSO_xref_log);
  mysql_free_result($LOGs);
  }
mysql_free_result($BANDs);
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
function QRB($grid1, $grid2)  {
// Calculates distance (QRB) between grid squares
  $lat1 = grid2lat($grid1);
  $lon1 = grid2long($grid1);
  $lat2 = grid2lat($grid2);
  $lon2 = grid2long($grid2);
// $distance = (3958*3.1415926*sqrt(($lat2-$lat1)*($lat2-$lat1) + cos($lat2/57.29578)*cos($lat1/57.29578)*($lon2-$lon1)*($lon2-$lon1))/180);
  $distance = (6372.797*3.1415926*sqrt(($lat2-$lat1)*($lat2-$lat1) + cos($lat2/57.29578)*cos($lat1/57.29578)*($lon2-$lon1)*($lon2-$lon1))/180);
  return  round($distance);                                    
}
function QTE($grid1, $grid2)  {
// Calculates azimuth (Bearing, QTE) from first grid square to second
  $lat1 = grid2lat($grid1);
  $lon1 = grid2long($grid1);
  $lat2 = grid2lat($grid2);
  $lon2 = grid2long($grid2);
// great-circle bearing
//$bearing = (rad2deg(atan2(sin(deg2rad($lon2) - deg2rad($lon1)) * cos(deg2rad($lat2)), cos(deg2rad($lat1)) * sin(deg2rad($lat2)) - sin(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($lon2) - deg2rad($lon1)))) + 360) % 360;
//return round($bearing);
// bearing of a rhumb line
//difference in longitudinal coordinates
  $dLon = deg2rad($lon2) - deg2rad($lon1);
//difference in the phi of latitudinal coordinates
  $dPhi = log(tan(deg2rad($lat2) / 2 + pi() / 4) / tan(deg2rad($lat1) / 2 + pi() / 4));
//we need to recalculate $dLon if it is greater than pi
  if(abs($dLon) > pi()) {
    if($dLon > 0) {
      $dLon = (2 * pi() - $dLon) * -1;
    }
    else {
      $dLon = 2 * pi() + $dLon;
    }
  }
//return the angle, normalized
  return (rad2deg(atan2($dLon, $dPhi)) + 360) % 360;                                       
}
function grid2long($gridsquare) {
//  Returns the longitude coordinates for the given grid coordinates
  $gridsquare = strtoupper($gridsquare);  //  make a string uppercase
  $longitude = (ord(substr($gridsquare, 0, 1)) - 65)*20 - 180;
  $longitude = $longitude + (int)substr($gridsquare, 2, 1)*2;
  $longitude = $longitude + ((ord(substr($gridsquare, 4, 1)) - 65)*5 + 2.5)/60;
  return  $longitude;
}
function grid2lat($gridsquare) {
//  Returns the latitude coordinates for the given grid coordinates
  $gridsquare = strtoupper($gridsquare);  //  make a string uppercase
// uppercase ascii offset is 65 (lowercase - 97)
  $latitude = (ord(substr($gridsquare, 1, 1)) - 65)*10 - 90;
  $latitude = $latitude + (int)substr($gridsquare, 3, 1);
  $latitude = $latitude + ((ord(substr($gridsquare, 5, 1)) - 65)*2.5 + 1.25)/60;
  return  $latitude;        
}
?>