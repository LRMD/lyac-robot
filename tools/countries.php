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
//
@ $members = fopen("countries.csv", "w");
  if(!$members) {
  mysql_close($db_connection);
  fwrite(STDOUT, "ERROR\n");
  exit(1);        
  }
// csv separator is a comma ("sep=;" is a semicolon)
  if (fputs($members, "sep=,\n") === false) {
  mysql_close($db_connection);
  fclose ($members);
  fwrite(STDOUT, "ERROR\n");
  exit(1);
  }
fputs($members, "No,Callsign,Grid,Band,Created,country\n");
$query = "SELECT callsigns.callsign, wwls.wwl, bands.band_freq, list.created FROM list
 INNER JOIN callsigns ON list.callsignID = callsigns.callsignID
 INNER JOIN wwls ON list.wwlID = wwls.wwlID
 INNER JOIN bands ON list.bandID = bands.bandID ORDER BY callsigns.callsign"; 
  if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
// Members cycle
$Nr = 0;
  while ($row = mysql_fetch_array($result)) {
  $Nr += 1;

  $query = "SELECT code, PHPpattern FROM countries;";
    if (!($country = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
    while ($code = mysql_fetch_array($country)) {
      if (preg_match($code[1], $row[0])) break;
    }
  mysql_free_result($country);

  fputs($members, "$Nr, $row[0], $row[1], $row[2], $row[3], $code[0]\n");
  }
mysql_free_result($result);  
fclose ($members);
$query = "SELECT countries.code,
(SELECT count(*) FROM callsigns WHERE callsigns.callsign REGEXP countries.MYSQLpattern),
 countries.country FROM countries;";
  if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  while ($row = mysql_fetch_array($result)) {
  fwrite(STDOUT, "$row[0]\t$row[1]\t$row[2]\n");
  }
mysql_free_result($result);
//
$result = mysql_query("SELECT count(*) FROM callsigns;");
$row = mysql_fetch_row($result);
mysql_free_result($result);  
fwrite(STDOUT, "ALL\t$row[0]\n");
//
$result = mysql_query("SELECT count(*) FROM list;");
$row = mysql_fetch_row($result);
mysql_free_result($result);  
fwrite(STDOUT, "Number of rows table 'list' : $row[0] OK\n");
mysql_close($db_connection);
exit(0);
?>
