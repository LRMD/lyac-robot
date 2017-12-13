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
fwrite(STDOUT,"Number of week/round/band (1,2,3,4): ");
$band = fgets(STDIN);
$band = ereg_replace("[\r\n]", '', $band);
$band = check_band($band);
  if ($band === FALSE) {
    // Write to STDERR
    fwrite(STDERR,"Invalid number of week/round/band (1,2,3,4)\n");
    mysql_close($db_connection);
    exit(DB_CONNECTION_FAILED);  
  } 
$query = "SELECT * FROM logs INNER JOIN rounds ON rounds.date = logs.date WHERE rounds.week = '$band'";
  if (!($LOGs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
// Logs cycle
  while ($log = mysql_fetch_array($LOGs)) {
  $query = "SELECT * FROM qsorecords WHERE qsorecords.logID = '$log[0]'";
    if (!($QSOs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
// QSOs cycle
    while ($qso = mysql_fetch_array($QSOs)) {
    $query = "SELECT * FROM qsorecords WHERE callsign = (SELECT callsign FROM callsigns WHERE callsignID = '$log[2]') and logID = (SELECT logID FROM logs WHERE date = '$log[3]' and callsignID = (SELECT callsignID FROM callsigns WHERE callsign = '$qso[4]'))";
      if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
      if (mysql_num_rows($result) == 1)  {
      $row = mysql_fetch_array($result);
      fwrite(STDOUT, "$qso[4]<->$row[4]\n");
      }
      else  {
      fwrite(STDOUT, "$qso[4]<-> ?\n");
      }
      mysql_free_result($result);
    }
  mysql_free_result($QSOs); 
  }
mysql_free_result($LOGs);
fwrite(STDOUT, "OK\n");
mysql_close($db_connection);
exit(0);
function check_band($data)  {
//  YYYY-MM-DD format
$pattern = '/^[1-4]$/m';
  if(!preg_match($pattern, $data, $matches))
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