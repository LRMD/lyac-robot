#!/usr/bin/php -q
<?php
require_once 'database.inc';
require_once 'smtp.php';
// A constant to be used as an error return status
define ('DB_CONNECTION_FAILED',1);

// Try connecting to MySQL
@ $db_connection = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
  if  (!$db_connection) {
    // Write to STDERR
    fwrite(STDERR,mysql_error()." DB_CONNECTION_FAILED\n");
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
// QSOs without Cross Reference onfo
  @ $QSO_log = fopen($band . "_QSOs_" . $log_date . ".csv", "w");
    if(!$QSO_log) {
    mysql_close($db_connection);
    fwrite(STDOUT, "ERROR\n");
    exit(1);        
    }
// csv separator is a comma ("sep=;" is a semicolon)
    if (fputs($QSO_log, "sep=,\n") === false) {
    mysql_close($db_connection);
    fclose ($QSO_log);
    fwrite(STDOUT, "ERROR\n");
    exit(1);
    }
  $query = "SELECT logs.logID, bands.band_freq, callsigns.callsign, wwls.wwl FROM logs INNER JOIN callsigns ON callsigns.callsignID = logs.callsignID INNER JOIN wwls ON wwls.wwlID = logs.wwlID INNER JOIN bands ON bands.bandID = logs.bandID WHERE logs.date = '$log_date' and logs.bandID = '$bandID' ORDER BY callsigns.callsign";
    if (!($LOGs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  $qsoNr = 0;
  $totalLogs = 0;
// The logs cycle
    while ($log = mysql_fetch_array($LOGs)) {
    $totalLogs += 1;
    $query = "SELECT modes.mode, qsorecords.time, qsorecords.callsign, qsorecords.gridsquare FROM qsorecords INNER JOIN modes ON qsorecords.modeID = modes.modeID WHERE qsorecords.logID = '$log[0]'";
      if (!($QSOs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
    fputs($QSO_log, "$totalLogs,,,$log_date,,$log[2],$log[3],,,,\n");
    fputs($QSO_log, "No,Band,Mode,Date,Time,Callsign,Grid,Callsign,Grid,QRB(km),QTE(deg)\n");
// The QSOs cycle
      while ($qso = mysql_fetch_array($QSOs)) {
      $qsoNr += 1;
      $QSOdistance = (int)QRB($log[3], $qso[3]);
        if ($QSOdistance == 0) $QSOdistance = 1;
      $QSObearing = QTE($log[3], $qso[3]);
      fputs($QSO_log, "$qsoNr,$log[1],$qso[0],$log_date,$qso[1],$log[2],$log[3],$qso[2],$qso[3],$QSOdistance,$QSObearing\n");
      }
    mysql_free_result($QSOs);
    fputs($QSO_log, ",,,,,,,,,,\n");  
    }
  fclose ($QSO_log);
  mysql_free_result($LOGs);
// QSOs with Cross Reference (xref) info
  $query = "SELECT logs.logID, bands.band_freq, callsigns.callsign, wwls.wwl FROM logs INNER JOIN callsigns ON callsigns.callsignID = logs.callsignID INNER JOIN wwls ON wwls.wwlID = logs.wwlID INNER JOIN bands ON bands.bandID = logs.bandID WHERE logs.date = '$log_date' and logs.bandID = '$bandID' ORDER BY callsigns.callsign";
    if (!($LOGs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
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
// Received Logs
  @ $received_log = fopen($band . "_received_" . $log_date . ".csv", "w");
    if(!$received_log) {
    mysql_close($db_connection);
    fwrite(STDOUT, "ERROR\n");
    exit(1);        
    }
// csv separator is a comma ("sep=;" is a semicolon)
    if (fputs($received_log, "sep=,\n") === false) {
    mysql_close($db_connection);
    fclose ($received_log);
    fwrite(STDOUT, "ERROR\n");
    exit(1);
    }
  fputs($received_log, "No,Callsign,Grid,Source,Property_1,Property_2\n");
  $query = "SELECT id2call(logs.callsignID), id2wwl(logs.wwlID), attachments.source, attachments.sourceID 
   FROM logs INNER JOIN attachments ON logs.attachmentID = attachments.attachmentID
   WHERE logs.date = '$log_date' and logs.bandID = $bandID ORDER BY id2call(logs.callsignID);";
    if (!($LOGs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  $num_received = mysql_num_rows($LOGs);
// The cycle of emails
  $logNr = 0;
    while ($channel = mysql_fetch_array($LOGs)) {
    $logNr += 1;
      switch ($channel[2])
      {
      case 'email':
      $query = "SELECT id2email(emailID), date FROM messages WHERE messageID = $channel[3];";
        if (!($CHANNELs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
      $properties = mysql_fetch_array($CHANNELs);
      mysql_free_result($CHANNELs);
      fputs($received_log, "$logNr,$channel[0],$channel[1],$channel[2],$properties[0],$properties[1]\n");
      break;
      case 'file':
      $query = "SELECT name, date FROM files WHERE fileID = $channel[3];";
// ...
      fputs($received_log, "$logNr,$channel[0],$channel[1],$channel[2], , \n");
      break;
      case 'web':
      $query = "SELECT session, date FROM webs WHERE webID = $channel[3];";
// ...
      fputs($received_log, "$logNr,$channel[0],$channel[1],$channel[2], , \n");     
      break;
      default:
// ...
      fputs($received_log, "$logNr,$channel[0],$channel[1],$channel[2], , \n");      
      break;
      }
    }
  fputs($received_log, ",,,,\n");
  fclose ($received_log);
  mysql_free_result($LOGs);
// Missing Logs
  @ $missing_log = fopen($band . "_missing_" . $log_date . ".csv", "w");
    if(!$missing_log) {
    mysql_close($db_connection);
    fwrite(STDOUT, "ERROR\n");
    exit(1);      
    }
// csv separator is a comma ("sep=;" is a semicolon)
    if (fputs($missing_log, "sep=,\n") === false) {
    mysql_close($db_connection);
    fclose ($missing_log);
    fwrite(STDOUT, "ERROR\n");
    exit(1);
    }
  fputs($missing_log, "No,Callsign,Grid,Count,Logs\n");
  $query = "SELECT qsorecords.callsign, COUNT(*), MAX(qsorecords.gridsquare) FROM qsorecords INNER JOIN logs ON logs.logID = qsorecords.logID WHERE logs.date = '$log_date' and logs.bandID = '$bandID' GROUP BY callsign HAVING COUNT(*) > 0;";
//$query = "SELECT qsorecords.callsign, COUNT(*), MAX(qsorecords.gridsquare) FROM qsorecords, logs WHERE logs.date = '$log_date' and logs.bandID = '$bandID' and logs.logID = qsorecords.logID GROUP BY callsign HAVING COUNT(*) > 0;";
    if (!($callsigns = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  $nr = 0;
    while ($callsign = mysql_fetch_array($callsigns)) {
    $query = "SELECT logID FROM logs WHERE date = '$log_date' and bandID = '$bandID' and callsignID = call2ID('$callsign[0]');";
      if (!($result1 = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  )); 
      if (mysql_num_rows($result1) == 0)  {
        $query = "SELECT id2call(logs.callsignID) FROM logs INNER JOIN qsorecords ON logs.logID = qsorecords.logID WHERE logs.date = '$log_date' and logs.bandID = '$bandID' and qsorecords.callsign = '$callsign[0]' ORDER BY id2call(logs.callsignID);";
          if (!($logs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
        $nr += 1;
        fputs($missing_log, "$nr,$callsign[0],$callsign[2],$callsign[1]");
          while ($log = mysql_fetch_array($logs)) fputs($missing_log, ",$log[0]");
        mysql_free_result($logs);
        fputs($missing_log, "\n");
      }
    }
  $num_missing = $nr;
  fputs($missing_log, ",,,,\n");
  fclose ($missing_log);
  mysql_free_result($callsigns);
//
  $claimedQSOs = array(0,0,0,0,0,0,0,0);
  $claimedQRBs = array(0,0,0,0,0,0,0,0);
  $confirmedQSOs = array(0,0,0,0,0,0,0,0);
  $confirmedQRBs = array(0,0,0,0,0,0,0,0);
  $maxQRBs = array(0,0,0,0,0,0,0,0);
// The QSOs cycle
  $query = "SELECT qsorecords.modeID, qsorecords.callsign, qsorecords.gridsquare, wwls.wwl, logs.callsignID, id2call(logs.callsignID) FROM qsorecords INNER JOIN logs ON logs.logID = qsorecords.logID INNER JOIN wwls ON logs.wwlID = wwls.wwlID WHERE logs.date = '$log_date' and logs.bandID = '$bandID'";
    if (!($QSOs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
    while ($qso = mysql_fetch_array($QSOs)) {
    $QSOdistance = (int)QRB($qso[2], $qso[3]);
      if ($QSOdistance == 0) $QSOdistance = 1;
    $query = "SELECT logID FROM logs WHERE date = '$log_date' and bandID = '$bandID' and callsignID = call2ID('$qso[1]')";
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
  $query = "SELECT logID FROM logs WHERE date = '$log_date' and bandID = $bandID;";
    if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  $totalLogs = mysql_num_rows($result);
  mysql_free_result($result);
  // Count number of logs from LY
  $query = "SELECT logID FROM logs WHERE date = '$log_date' and bandID = $bandID and id2call(callsignID) REGEXP '^LY';";
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
  $SSBqsos = $qsos[1];
  $CWqsos = $qsos[2];
  $FMqsos = $qsos[4];
  $OTHERqsos = $qsos[0] + $qsos[3] + $qsos[5] + $qsos[6] + $qsos[7];
  $SSBpercentageQSOs = $percentageQSOs[1];
  $CWpercentageQSOs = $percentageQSOs[2];
  $FMpercentageQSOs = $percentageQSOs[4];
  $OTHERpercentageQSOs = $percentageQSOs[0] + $percentageQSOs[3] + $percentageQSOs[5] + $percentageQSOs[6] + $percentageQSOs[7];
  $SSBmaxQRBs = $maxQRBs[1];
  $CWmaxQRBs = $maxQRBs[2];
  $FMmaxQRBs = $maxQRBs[4];
  $OTHERmaxQRBs = max($maxQRBs[0], $maxQRBs[3], $maxQRBs[5], $maxQRBs[6], $maxQRBs[7]);
  $SSBaverageQRBs = $averageQRBs[1];
  $CWaverageQRBs = $averageQRBs[2];
  $FMaverageQRBs = $averageQRBs[4];
  $OTHERaverageQRBs = 0;
    if ($OTHERqsos <> 0)  $OTHERaverageQRBs = round(($qrbs[0] + $qrbs[3] + $qrbs[5] + $qrbs[6] + $qrbs[7])/$OTHERqsos);
  $textbodyLT = "\n---LT---\nPreliminari NAC/LYAC $log_date $band turo statistika:\n";
  $textbodyEN = "\n---EN---\nPreliminary NAC/LYAC round statistics of $log_date $band:\n";
  $textbodyLT .= "\tGauta ataskaitø = $totalLogs (LY-kø ataskaitø = $totalLogsLY)\n\tDalyvavo HAMø = $totalCallsigns (LY-kø = $totalLY)\n\tSkirtingø keturþenkliø QRA WWL = $totalWWLs\n\tMaksimalus QSO QRB = $maxQRB km.\n";
  $textbodyEN .= "\tNumber of logs = $totalLogs (from LY = $totalLogsLY)\n\tParticipated HAMs = $totalCallsigns (LY HAMs = $totalLY)\n\tNumber of different 4-digit QRA WWLs = $totalWWLs\n\tMaximum QRB of QSOs = $maxQRB km.\n";
  $textbodyLT .= "\tQSO skaièius = $totalQSOs (SSB-$SSBpercentageQSOs%, CW-$CWpercentageQSOs%, FM-$FMpercentageQSOs%, Kitos-$OTHERpercentageQSOs%)\n";
  $textbodyEN .= "\tTotal number of QSOs = $totalQSOs (SSB-$SSBpercentageQSOs%, CW-$CWpercentageQSOs%, FM-$FMpercentageQSOs%, OTHER-$OTHERpercentageQSOs%)\n";
  $textbodyLT .= "\tVidutinis QRB: SSB-$SSBaverageQRBs km., CW-$CWaverageQRBs km., FM-$FMaverageQRBs km., Kitos-$OTHERaverageQRBs km.\n";
  $textbodyEN .= "\tAverage QRB: SSB-$SSBaverageQRBs km., CW-$CWaverageQRBs km., FM-$FMaverageQRBs km., OTHER-$OTHERaverageQRBs km.\n";
  $textbodyLT .= "\tMaksimalus QRB: SSB-$SSBmaxQRBs km., CW-$CWmaxQRBs km., FM-$FMmaxQRBs km., Kitos-$OTHERmaxQRBs km.\n";
  $textbodyEN .= "\tMaximum QRB: SSB-$SSBmaxQRBs km., CW-$CWmaxQRBs km., FM-$FMmaxQRBs km., OTHER-$OTHERmaxQRBs km.\n";
  $textbodyLT .= "\t73! LYAC komanda\n";
  $textbodyEN .= "\t73! LYAC Team\n";
  $body = $textbodyLT . $textbodyEN;
  fwrite(STDOUT, "$body");
// Send messege
  $query = "SELECT callsigns.callsign, wwls.wwl, emails.email
   FROM logs INNER JOIN callsigns ON callsigns.callsignID = logs.callsignID INNER JOIN wwls ON wwls.wwlID = logs.wwlID INNER JOIN attachments ON logs.attachmentID = attachments.attachmentID INNER JOIN messages ON attachments.sourceID = messages.messageID INNER JOIN emails ON emails.emailID = messages.emailID
   WHERE logs.date = '$log_date' and logs.bandID = '$bandID' and attachments.source = 'email';";
    if (!($eMAILs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
// The cycle of emails
  $mailNr = 0;
  fwrite(STDOUT,"To send this type 'Yes': ");
  $line = fgets(STDIN);
//
  $subject = "To: LY1KL WWL: KO24OR Preliminary NAC/LYAC round statistics of $log_date $band";
  sendMessage("kliauda@zebra.lt", $subject, $body, $band . "_xref_" . $log_date . ".csv");
//
    if (trim($line) == 'Yes') { 
      while ($email = mysql_fetch_array($eMAILs)) {
      $mailNr += 1;
//        if (prefix($email[0]) == "LY") continue;      
      fwrite(STDOUT,"\t$mailNr\t$email[0]\t$email[1]\t$email[2]\n");
      $subject = "To: $email[0] WWL: $email[1] Preliminary NAC/LYAC round statistics of $log_date $band";      
//
       sendMessage($email[2], $subject, $body, $band . "_xref_" . $log_date . ".csv");
//
      }
    }
  mysql_free_result($eMAILs);
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