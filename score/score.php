<?php 
require_once '../database.inc';
require_once 'smtp.php';
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
    // Write to STDERR
    fwrite(STDERR,mysql_error()." DB_CONNECTION_FAILED\n");
    exit(DB_CONNECTION_FAILED);
  }
mysql_select_db(DB_NAME);
echo("Connected to database\n");
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
$query = "SELECT date, name FROM rounds WHERE date = '$log_date';";
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
//      if ($score == 0)  continue; // to next log
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
    sendMessage("simonas@5grupe.lt", $subject, $body, "$path/$callsign" . "_" . $files[$callsign] . ".csv", $date . "/" . $date . ".zip");
      if (trim($line) == 'Yes') { 
        while ($email = mysql_fetch_array($eMAILs)) {
        $mailNr += 1;
//          if (!isset($files[$email[0]])) continue; // if log with zero score
          if (!array_key_exists($email[0], $files)) continue; // if log with zero score
        $file = "$path/$email[0]" . "_" . $files[$email[0]] . ".csv";
        fwrite(STDOUT,"$mailNr\t$email[0]\t$email[1]\t$email[2]\t$file\n");
        $subject = "To: $email[0] Preliminary NAC/LYAC round score of $date $band";
        
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
  if(!preg_match($pattern, $date, $matches))
  {
    return FALSE;
  }
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
?>
