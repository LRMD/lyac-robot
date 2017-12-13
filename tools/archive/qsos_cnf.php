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
// Clear confirm info of QSOs
$query = "UPDATE qsorecords SET confirm = 0";
  if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
// Mark QSO pairs
$query = "SELECT qsoID1, qsoID2 FROM qsopairs";
  if (!($QSOs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  while ($qsos = mysql_fetch_array($QSOs))  {
  $query = "UPDATE qsorecords SET confirm = confirm + 1 WHERE qsoID = $qsos[0] or qsoID = $qsos[1]";
    if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));  
  }
mysql_free_result($QSOs);
// Cycle of QSO pairs to check gridsquares (WWLs)
$query = "SELECT A.qsoID, B.qsoID, id2wwl(C.wwlID), id2wwl(D.wwlID), A.gridsquare, B.gridsquare FROM qsopairs INNER JOIN qsorecords A ON qsopairs.qsoID1 = A.qsoID INNER JOIN qsorecords B ON qsopairs.qsoID2 = B.qsoID INNER JOIN logs C ON A.logID = C.logID INNER JOIN logs D ON B.logID = D.logID";
  if (!($QSOs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  while ($qsos = mysql_fetch_array($QSOs))  {
    if (strcmp(strtoupper($qsos[2]), strtoupper($qsos[5])) <> 0) {
    $query = "UPDATE qsorecords SET confirm = confirm + 2 WHERE qsoID = $qsos[1]";
      if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
    }
    if (strcmp(strtoupper($qsos[3]), strtoupper($qsos[4])) <> 0) {
    $query = "UPDATE qsorecords SET confirm = confirm + 2 WHERE qsoID = $qsos[0]";
      if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
    }
  } 
mysql_free_result($QSOs);
// Cycle of QSO pairs to check time (10 minits limit)
$query = "SELECT A.qsoID, B.qsoID FROM qsopairs INNER JOIN qsorecords A ON qsopairs.qsoID1 = A.qsoID INNER JOIN qsorecords B ON qsopairs.qsoID2 = B.qsoID WHERE ABS(TIME_TO_SEC(TIMEDIFF(B.time, A.time))) > 600";
  if (!($QSOs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  while ($qsos = mysql_fetch_array($QSOs))  {
  $query = "UPDATE qsorecords SET confirm = confirm + 4 WHERE qsoID = $qsos[0] or qsoID = $qsos[1]";
    if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));  
  }
mysql_free_result($QSOs);
fwrite(STDOUT, "OK\n");
mysql_close($db_connection);
exit(0);
?>