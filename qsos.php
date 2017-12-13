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
// Round statistics calculation 
  $claimedQSOs = array(0,0,0,0,0,0,0,0);
  $claimedQRBs = array(0,0,0,0,0,0,0,0);
  $confirmedQSOs = array(0,0,0,0,0,0,0,0);
  $confirmedQRBs = array(0,0,0,0,0,0,0,0);
  $maxQRBs = array(0,0,0,0,0,0,0,0);
// The QSOs cycle
  $query = "SELECT qsorecords.modeID, qsorecords.callsign, qsorecords.gridsquare, wwls.wwl, logs.callsignID, id2call(logs.callsignID) FROM qsorecords INNER JOIN logs ON logs.logID = qsorecords.logID INNER JOIN wwls ON logs.wwlID = wwls.wwlID WHERE logs.date = '$log_date' and logs.bandID = '$bandID'";
    if (!($QSOs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
    if (mysql_num_rows($QSOs) == 0) break;
    while ($qso = mysql_fetch_array($QSOs)) {
    $QSOdistance = (int)QRB($qso[2], $qso[3]);
      if ($QSOdistance == 0) $QSOdistance = 1;
    $query = "SELECT logID FROM logs WHERE date = '$log_date' and bandID = '$bandID' and callsignID = call2id('$qso[1]')";
      if (!($LOGs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
      if (mysql_num_rows($LOGs) == 0)  {
      $claimedQSOs[$qso[0] - 1] += 1;
      $claimedQRBs[$qso[0] - 1] += $QSOdistance;
        if ($maxQRBs[$qso[0] - 1] < $QSOdistance) $maxQRBs[$qso[0] - 1] = $QSOdistance;
      }
      else  {
      $log = mysql_fetch_array($LOGs);
      $query = "SELECT qsoID FROM qsorecords WHERE logID = '$log[0]' and callsign = (SELECT callsign FROM callsigns WHERE callsignID = '$qso[4]')";
        if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
        if (mysql_num_rows($result) == 1)  {
        $confirmedQSOs[$qso[0] - 1] += 0.5;
        $confirmedQRBs[$qso[0] - 1] += $QSOdistance/2;
          if ($maxQRBs[$qso[0] - 1] < $QSOdistance) $maxQRBs[$qso[0] - 1] = $QSOdistance;
        }
        else  {
        fwrite(STDOUT, "$qso[5] -> $qso[1] ?\n");
        }
      mysql_free_result($result);
      }
    mysql_free_result($LOGs);
    }
  mysql_free_result($QSOs);
// Count differenrent 4 digits WWL gridsquares
  $query = "SELECT DISTINCT SUBSTRING(qsorecords.gridsquare,1,4) FROM qsorecords INNER JOIN logs ON qsorecords.logID = logs.logID WHERE logs.date = '$log_date' and logs.bandID = '$bandID'
            UNION DISTINCT
            SELECT DISTINCT SUBSTRING(wwls.wwl,1,4) FROM wwls INNER JOIN logs ON wwls.wwlID = logs.wwlID WHERE logs.date = '$log_date' and logs.bandID = '$bandID'";
    if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  $totalWWLs = mysql_num_rows($result);
  mysql_free_result($result);
  // Count participants (callsigns)
  $query = "SELECT DISTINCT qsorecords.callsign FROM qsorecords INNER JOIN logs ON qsorecords.logID = logs.logID WHERE logs.date = '$log_date' and logs.bandID = '$bandID'
            UNION DISTINCT
            SELECT DISTINCT callsigns.callsign FROM callsigns INNER JOIN logs ON callsigns.callsignID = logs.callsignID WHERE logs.date = '$log_date' and logs.bandID = '$bandID'";
    if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  $totalCallsigns = mysql_num_rows($result);
  mysql_free_result($result);
// Count participants from Lithuania (LY callsigns)
  $query = "SELECT DISTINCT qsorecords.callsign FROM qsorecords INNER JOIN logs ON qsorecords.logID = logs.logID WHERE logs.date = '$log_date' and logs.bandID = '$bandID' and qsorecords.callsign REGEXP '^LY'
            UNION DISTINCT
            SELECT DISTINCT callsigns.callsign FROM callsigns INNER JOIN logs ON callsigns.callsignID = logs.callsignID WHERE logs.date = '$log_date' and logs.bandID = '$bandID' and callsigns.callsign REGEXP '^LY'";
    if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  $totalLY = mysql_num_rows($result);
  mysql_free_result($result);
  // Count number of logs
  $query = "SELECT logID FROM logs WHERE logs.date = '$log_date' and logs.bandID = $bandID;";
    if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  $totalLogs = mysql_num_rows($result);
  mysql_free_result($result);
  // Count number of logs from LY
  $query = "SELECT logID FROM logs WHERE logs.date = '$log_date' and logs.bandID = $bandID and id2call(callsignID) REGEXP '^LY';";
    if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  $totalLogsLY = mysql_num_rows($result);
  mysql_free_result($result);
  // Max distance of QSOs
  $query = "SELECT IFNULL(MAX(QRB(qsorecords.gridsquare, id2wwl(logs.wwlID))),0) FROM qsorecords INNER JOIN logs ON qsorecords.logID = logs.logID WHERE logs.date = '$log_date' and logs.bandID = '$bandID'";
    if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  $row = mysql_fetch_array($result);
  $maxQRB = $row[0];
  mysql_free_result($result);
// Prepare message
  $qsos = array(0,0,0,0,0,0,0,0);
  $qrbs = array(0,0,0,0,0,0,0,0);
  $QSOsCNF = array(0,0,0,0,0,0,0,0);
  $QRBsCNF = array(0,0,0,0,0,0,0,0);
    foreach($claimedQSOs as $key => $value) {
      $qsos[$key] = $claimedQSOs[$key] + $confirmedQSOs[$key];
        if ($qsos[$key] <> 0) {
        $QSOsCNF[$key] = round($confirmedQSOs[$key]/$qsos[$key]*100);
        }
    }
    foreach($claimedQRBs as $key => $value) {
      $qrbs[$key] = $claimedQRBs[$key] + $confirmedQRBs[$key];
        if ($qrbs[$key] <> 0) {
        $QRBsCNF[$key] = round($confirmedQRBs[$key]/$qrbs[$key]*100);
        }
    }
  $totalQSOs = array_sum($qsos);
  $totalQRBs = array_sum($qrbs);
  $percentageQSOs = array(0,0,0,0,0,0,0,0);
  $averageQRBs = array(0,0,0,0,0,0,0,0);
    if ($totalQSOs <> 0)  {
      foreach($qsos as $key => $value) {
        $percentageQSOs[$key] = round($qsos[$key]/$totalQSOs*100);
      }
    }
    foreach($qrbs as $key => $value) {
      if ($qsos[$key] <> 0) {
        $averageQRBs[$key] = round($qrbs[$key]/$qsos[$key], 0);
      }
    }
  fwrite(STDOUT, "\n\t*** Statistic of the round ***\n");
  fwrite(STDOUT, "Date=$log_date\nRound= $round_name\nBand= $band\nLogs=$totalLogs\nLY logs=$totalLogsLY\nCallsigns=$totalCallsigns\nLY callsigns=$totalLY\nWWLs=$totalWWLs\nQSOs=$totalQSOs\nmaxQRB=$maxQRB km\n");
  fwrite(STDOUT, "\n\t*** QSOs statistic ***\n");
  fwrite(STDOUT,"\tunknown\tSSB\tCW\tAM\tFM\tRTTY\tSSTV\tATV\n");
  fwrite(STDOUT,"QSOs\t$qsos[0]\t$qsos[1]\t$qsos[2]\t$qsos[3]\t$qsos[4]\t$qsos[5]\t$qsos[6]\t$qsos[7]\n");
  fwrite(STDOUT,"QSOs%\t$percentageQSOs[0]%\t$percentageQSOs[1]%\t$percentageQSOs[2]%\t$percentageQSOs[3]%\t$percentageQSOs[4]%\t$percentageQSOs[5]%\t$percentageQSOs[6]%\t$percentageQSOs[7]%\n");
  fwrite(STDOUT,"CNF QSO\t$QSOsCNF[0]%\t$QSOsCNF[1]%\t$QSOsCNF[2]%\t$QSOsCNF[3]%\t$QSOsCNF[4]%\t$QSOsCNF[5]%\t$QSOsCNF[6]%\t$QSOsCNF[7]%\n");
  fwrite(STDOUT,"QRBs\t$qrbs[0]\t$qrbs[1]\t$qrbs[2]\t$qrbs[3]\t$qrbs[4]\t$qrbs[5]\t$qrbs[6]\t$qrbs[7]\n");
  fwrite(STDOUT,"maxQRBs\t$maxQRBs[0]km\t$maxQRBs[1]km\t$maxQRBs[2]km\t$maxQRBs[3]km\t$maxQRBs[4]km\t$maxQRBs[5]km\t$maxQRBs[6]km\t$maxQRBs[7]km\n");
  fwrite(STDOUT,"aveQRBs\t$averageQRBs[0]km\t$averageQRBs[1]km\t$averageQRBs[2]km\t$averageQRBs[3]km\t$averageQRBs[4]km\t$averageQRBs[5]km\t$averageQRBs[6]km\t$averageQRBs[7]km\n");  
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