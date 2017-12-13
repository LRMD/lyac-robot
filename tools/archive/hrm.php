<?php
// HAM relationship management (HRM)
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
//
@ $hrm = fopen("HRM.csv", "w");
  if(!$hrm) {
  mysql_close($db_connection);
  fwrite(STDOUT, "ERROR\n");
  exit(1);        
  }
// csv separator is a comma ("sep=;" is a semicolon)
  if (fputs($hrm, "sep=,\n") === false) {
  mysql_close($db_connection);
  fclose ($hrm);
  fwrite(STDOUT, "ERROR\n");
  exit(1);
  }
fputs($hrm, "No,Callsign,Grid,Band,e-mail,Created,Date\n");
$query = "SELECT callsigns.callsign, wwls.wwl, bands.band_freq, emails.email, contacts.created, contacts.date FROM contacts
 INNER JOIN list ON contacts.listID = list.listID
 INNER JOIN callsigns ON list.callsignID = callsigns.callsignID
 INNER JOIN wwls ON list.wwlID = wwls.wwlID
 INNER JOIN bands ON list.bandID = bands.bandID
 INNER JOIN emails ON contacts.emailID =  emails.emailID ORDER BY callsigns.callsign"; 
  if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
// Members cycle
$Nr = 0;
  while ($row = mysql_fetch_array($result)) {
  $Nr += 1;
  fputs($hrm, "$Nr, $row[0], $row[1], $row[2], $row[3], $row[4], $row[5]\n");
  }
mysql_free_result($result);  
fclose ($hrm);
fwrite(STDOUT, "OK\n");
mysql_close($db_connection);
exit(0);
?>
