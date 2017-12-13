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
@ $emails = fopen("emails.csv", "w");
  if(!$emails) {
  mysql_close($db_connection);
  fwrite(STDOUT, "ERROR\n");
  exit(1);        
  }
// csv separator is a comma ("sep=;" is a semicolon)
  if (fputs($emails, "sep=,\n") === false) {
  mysql_close($db_connection);
  fclose ($emails);
  fwrite(STDOUT, "ERROR\n");
  exit(1);
  }
fputs($emails, "No,Callsign,WWL,Band,e-mail,Created\n");
$query = "SELECT id2call(list.callsignID), id2wwl(list.wwlID), id2band(list.bandID), id2email(activities.emailID), list.created
 FROM activities INNER JOIN list ON activities.listID = list.listID
 GROUP BY id2email(activities.emailID)
 ORDER BY id2call(list.callsignID);";
  if (!($eMail = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
$Nr = 0;
  while ($row = mysql_fetch_array($eMail)) {
  $Nr += 1;
  fputs($emails, "$Nr,$row[0],$row[1],$row[2],$row[3],$row[4]\n");
  }
mysql_free_result($eMail);  
fclose ($emails);
fwrite(STDOUT, "OK\n");
mysql_close($db_connection);
exit(0);
?>