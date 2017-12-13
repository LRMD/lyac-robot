#!/usr/bin/php -q
<?php
require_once '../database.inc';
require_once 'smtp.php';
date_default_timezone_set("Europe/Vilnius");
$files = array(
    "Callsign" => "Score"
);
// A constant to be used as an error return status
define ('DB_CONNECTION_FAILED',1);
define('WWL_bonus',500); // WWL bonus
//  UBN "Unique" - "Busted(sometimes called 'bad'" - "Not-in-the-log"
// Try connecting to MySQL
$db_connection = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
  if  (!$db_connection) {
  fwrite(STDERR,mysql_error()."\n");
  exit(DB_CONNECTION_FAILED);
  }
mysql_select_db(DB_NAME);
fwrite(STDOUT,"Connected to database\n");
fwrite(STDOUT,"Date(YYYY-MM-DD): ");
$round_date = fgets(STDIN);
// only digits and "-" for mysql
$round_date = preg_replace('/[\x00-\x2C\x2E-\x2F\x3A-\xFF]/', '', $round_date);
$round_date = check_sql_date($round_date);
  if ($round_date === FALSE) {
  fwrite(STDERR,"Invalid date format YYYY-MM-DD \n");
  mysql_close($db_connection);
  exit(DB_CONNECTION_FAILED);  
  }
//
$query = "SELECT date, name FROM rounds WHERE date = '$round_date';";
  if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  if (mysql_num_rows($result) <> 1) {
    // Write to STDERR
    fwrite(STDERR,"Wrong date of the LYAC round");
    mysql_close($db_connection);
    exit(DB_CONNECTION_FAILED);  
  }
$row = mysql_fetch_array($result);
$date = $row[0];
$round_name = $row[1];
mysql_free_result($result);
//
$query = "SELECT bands.bandID, bands.band, bands.factor FROM bands
 INNER JOIN bands_groups ON bands.bandID = bands_groups.bandID
  INNER JOIN rounds ON bands_groups.group_bands = rounds.group_bands WHERE rounds.date = '$date';";
  if (!($BANDs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
// The Band cycle
  while ($bands = mysql_fetch_array($BANDs)) {
  $bandID = $bands[0];
  $band = $bands[1];
  $factor = $bands[2];
//
  $query = "UPDATE qsorecords SET confirm = b'00000000'
   WHERE logID IN (SELECT logID FROM logs WHERE date = '$date' AND bandID = $bandID);";
    if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
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
//
  $path = "$date/$band";
    if(!is_dir($path))  {
      if(!mkdir($path, 0777, TRUE)) {
      mysql_close($db_connection);
      fwrite(STDOUT, "ERROR:An error occurred while creating directory: $path\n");
      exit(1);
      }      
    }
  @ $SCORE = fopen("$path/SCORE_$date" . ".csv", "w");
    if(!$SCORE) {
    mysql_close($db_connection);
    fwrite(STDOUT, "ERROR\n");
    exit(1);        
    }
// csv separator is a comma ("sep=;" is a semicolon)
    if (fputs($SCORE, "sep=,\n") === false) {
    mysql_close($db_connection);
    fclose ($SCORE);
    fwrite(STDOUT, "ERROR\n");
    exit(1);
    }
  fputs($SCORE, "Callsign,Score\n");   
  $query = "SELECT logID, id2call(callsignID), id2wwl(wwlID) FROM logs WHERE bandID = $bandID AND date = '$date' ORDER BY id2call(callsignID);";
    if (!($LOGs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
// The logs cycle
    while ($log = mysql_fetch_array($LOGs)) {
    $logID = $log[0];
    $callsign = $log[1];
    $wwl = $log[2];
// number of QSO can bee zero
//    $query = "SELECT $factor*IFNULL(SUM(GREATEST(QRB(qsorecords.gridsquare, '$wwl'),1)),0) + 500*COUNT(DISTINCT SUBSTRING(gridsquare,1,4)), COUNT(DISTINCT SUBSTRING(gridsquare,1,4)) FROM qsorecords WHERE logID = $logID;";
    $query = "SELECT $factor*IFNULL(SUM(GREATEST(QRB(qsorecords.gridsquare, '$wwl'),1)),0) + 500*COUNT(DISTINCT SUBSTRING(gridsquare,1,4)), COUNT(DISTINCT SUBSTRING(gridsquare,1,4))
     FROM qsorecords WHERE logID = $logID AND (confirm = 16 OR confirm = 32 OR confirm = 71);";
      if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
    $row = mysql_fetch_array($result);
    mysql_free_result($result);
    $score = $row[0];
      if ($score == 0)  continue; // to next log
    $num_wwls = $row[1];  //  four characters grid square
    fputs($SCORE, "$callsign,$score\n");
    @ $UBN = fopen("$path/$callsign" . "_$score" . ".csv", "w");
      if(!$UBN) {
      mysql_close($db_connection);
      fwrite(STDOUT, "ERROR\n");
      exit(1);        
      }
// csv separator is a comma ("sep=;" is a semicolon)
      if (fputs($UBN, "sep=,\n") === false) {
      mysql_close($db_connection);
      fclose ($UBN);
      fwrite(STDOUT, "ERROR\n");
      exit(1);
      }
// Remember score of callsing
    $files["$callsign"]="$score";
//  Row of the log  $LOGrow
    $LOGrow = array('LYAC','Band','Date','Callsign','WWL');
    fputcsv($UBN, $LOGrow);
    $LOGrow[0] = 'QSO LOG';
    $LOGrow[1] = $band;
    $LOGrow[2] = $date;
    $LOGrow[3] = $callsign;
    $LOGrow[4] = $wwl;
    fputcsv($UBN, $LOGrow);
//  Rows of QSOs  $QSOrow
    $QSOrow = array('UTC','Callsign','RSTs','RSTr','WWL','QRB km','Status');
    fputcsv($UBN, $QSOrow);
    $query = "SELECT time, callsign, rst_s, rst_r, gridsquare, GREATEST(QRB(gridsquare, '$wwl'),1), confirm FROM qsorecords WHERE logID = $logID;";
      if (!($QSOs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
      while ($qso = mysql_fetch_array($QSOs)) {
//
        foreach ($QSOrow as &$value) {
        $value = ' ';
        }
      $QSOrow[0] = (string)$qso[0];
      $QSOrow[1] = (string)$qso[1];
      $QSOrow[2] = (string)$qso[2];
      $QSOrow[3] = (string)$qso[3];
      $QSOrow[4] = (string)$qso[4];
        if ($qso[6] == 16 || $qso[6] == 32 || $qso[6] == 71) $QSOrow[5] = (string)$qso[5];
        else $QSOrow[5] = (string)'0';
        switch ($qso[6])  {
        case 0:
        $QSOrow[6] = 'not included - might bee QSO error';
        break;
        case 16:
        $QSOrow[6] = 'included - log absent, but history of participation OK';
        break;
        case 32:
        $QSOrow[6] = 'included - log absent, but participation confirmed';
        break;
        case 64:
        $QSOrow[6] = 'not included - time, WWL and RST errors';
        break;
        case 65:
        $QSOrow[6] = 'not included - time and WWL errors';
        break;
        case 66:
        $QSOrow[6] = 'not included - time and RST errors';
        break;
        case 67:
        $QSOrow[6] = 'not included - time error';
        break;
        case 68:
        $QSOrow[6] = 'not included - WWL and RST errors';
        break;
        case 69:
        $QSOrow[6] = 'not included - WWL error';
        break;
        case 70:
        $QSOrow[6] = 'not included - RST error';
        break;        
        case 71:
        $QSOrow[6] = 'included - QSO confirmed';
        break;
        case 255:
        $QSOrow[6] = 'not included, QSO not in the log';
        break;                                
        default:
        $QSOrow[6] = (string)$qso[6];
        }
      fputcsv($UBN, $QSOrow);      
      }
    mysql_free_result($QSOs);  
    foreach ($QSOrow as &$value) $value = (string)' ';
    fputcsv($UBN, $QSOrow);
    $query = "SELECT multiplier($logID), sumQRB($logID), grids($logID), score($logID);";
      if (!($total = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
    $summary = mysql_fetch_array($total);
    mysql_free_result($total);
    foreach ($QSOrow as &$value) $value = (string)' ';
    $QSOrow[0] = 'Multiplier';
    $QSOrow[1] = (string)$summary[0];   
    $QSOrow[2] = 'sumQSB';
    $QSOrow[3] = (string)$summary[1];
    $QSOrow[4] = 'Mult*sumQSB';
    $QSOrow[5] = (string)$summary[0]*$summary[1];
    fputcsv($UBN, $QSOrow);    
    foreach ($QSOrow as &$value) $value = (string)' ';
    $QSOrow[0] = 'WWL bonus';
    $QSOrow[1] = "500";   
    $QSOrow[2] = 'sumWWLs';
    $QSOrow[3] = (string)$summary[2];
    $QSOrow[4] = 'Bonus*WWLs';
    $QSOrow[5] = (string)500*$summary[2];
    fputcsv($UBN, $QSOrow);
    foreach ($QSOrow as &$value) $value = (string)' ';
    $QSOrow[4] = 'Total score';
    $QSOrow[5] = (string)$summary[3];
    fputcsv($UBN, $QSOrow);
    fclose ($UBN);
    }
  fclose ($SCORE);
  mysql_free_result($LOGs);

    if (Zip("$date/$band", "$date/$date.zip") === true) {
    $textbodyLT = "\n---LT---\nPreliminarus NAC/LYAC $date $band turo rezultatai:\n";
    $textbodyEN = "\n---EN---\nPreliminary score of NAC/LYAC round of $date $band:\n";
    $textbodyLT .= "\n\t73! LYAC komanda\n";
    $textbodyEN .= "\n\t73! LYAC Team\n";
    $subject = "TEST: Preliminary NAC/LYAC round score of $date $band";    
    $body = $textbodyLT . $textbodyEN . preg_replace("/,/", "\t", str_replace("sep=,", "", file_get_contents("$path/SCORE_$date" . ".csv")));
    fwrite(STDOUT, "$body");
// Send messege
    $query = "SELECT callsigns.callsign, wwls.wwl, emails.email
     FROM logs INNER JOIN callsigns ON callsigns.callsignID = logs.callsignID INNER JOIN wwls ON wwls.wwlID = logs.wwlID INNER JOIN attachments ON logs.attachmentID = attachments.attachmentID INNER JOIN messages ON attachments.sourceID = messages.messageID
      INNER JOIN emails ON emails.emailID = messages.emailID
       WHERE logs.date = '$date' and logs.bandID = '$bandID' and attachments.source = 'email';";
      if (!($eMAILs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
// The cycle of emails
    $mailNr = 0;
    fwrite(STDOUT,"To send this type 'Yes': ");
    $line = fgets(STDIN);
    sendMessage("ly2en@qrz.lt", $subject, $body, "$path/$callsign" . "_" . $files[$callsign] . ".csv", $date . "/" . $date . ".zip");
      if (trim($line) == 'Yes') { 
        while ($email = mysql_fetch_array($eMAILs)) {
        $mailNr += 1;
//          if (!isset($files[$email[0]])) continue; // if log with zero score
          if (!array_key_exists($email[0], $files)) continue; // if log with zero score
        $file = "$path/$email[0]" . "_" . $files[$email[0]] . ".csv";
//          if (prefix($email[0]) == "LY") continue;
        fwrite(STDOUT,"$mailNr\t$email[0]\t$email[1]\t$email[2]\t$file\n");
        $subject = "To: $email[0] WWL: $email[1] Preliminary NAC/LYAC round score of $date $band";        
//
// sendMessage($email[2], $subject, $body, "$path/$email[0]" . "_" . $files[$email[0]] . ".csv", $date . "/" . $date . ".zip");
//
        }
      }
    mysql_free_result($eMAILs);
    fwrite(STDOUT, "OK\n");
    }
    else  {
    fwrite(STDOUT, "Zip archive ERROR\n");
    }
  }
mysql_free_result($BANDs);
mysql_close($db_connection);
exit(0);
function check_sql_date($date)  {
//  YYYY-MM-DD format
$pattern = '/^(19[0-9][0-9]|20[0-9][0-9])-(0[1-9]|1[0-2])-(0[1-9]|1[0-9]|2[0-9]|3[0-1])$/m';
  if(!preg_match($pattern, $date, $matches))  return FALSE;
return $matches[0];
}
function Zip($source, $destination) {
  if (!extension_loaded('zip') || !file_exists($source)) {
  return false;
  }
  $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
    return false;
    }
  $source = str_replace('\\', '/', realpath($source));
    if (is_dir($source) === true) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
      foreach ($files as $file) {
      $file = str_replace('\\', '/', realpath($file));
        if (is_dir($file) === true) {
        $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
        }
        else if (is_file($file) === true) {
        $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
        }
      }
    }
    else if (is_file($source) === true) {
    $zip->addFromString(basename($source), file_get_contents($source));
    }
  return $zip->close();
}
/* Function to validate ham radio callsign */
function valid_callsign($callsign)  {
/*
- zero or one digits
- one or two alphabetics
- one or more digits
- one to three alphabetics (and no more)
*/
/*
valid callsigns could include:
A1A
AA1A
AAA1AAA
A1A1A
1A1A
1AA1AA
*/
  return preg_match('/^(\d?[a-zA-Z]{1,3}|[a-zA-Z]\d[a-zA-Z]?)\d[a-zA-Z1-9]{1,7}$/i', $callsign);
}
function prefix($callsign)  {
$callsign = strtoupper($callsign);
$pattern = '/^(\d?[a-zA-Z]{1,3}|[a-zA-Z]\d[a-zA-Z]?)\d[a-zA-Z1-9]{1,7}$/i';
  if(preg_match($pattern, $callsign, $matches)) {
  return $matches[1];
  }
return false;  
}
?>
