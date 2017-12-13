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
$query = "SELECT emailID FROM emails";
  if (!($result = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  while ($row = mysql_fetch_array($result)) {
  $emailID = $row[0];
  $query = "SELECT logs.date FROM logs
   INNER JOIN attachments ON logs.attachmentID = attachments.attachmentID
    INNER JOIN messages ON attachments.messageID = messages.messageID
     INNER JOIN emails ON messages.emailID = emails.emailID
      WHERE emails.emailID = $emailID ORDER BY logs.date LIMIT 1";
    if (!($LOGs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
  $logs = mysql_fetch_array($LOGs);
  $create = $logs[0];
  mysql_free_result($LOGs);
  fwrite(STDOUT, "$emailID\t$create\n");
  $query = "UPDATE emails SET created = '$create' WHERE emailID = $emailID";
    if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  )); 
  }
mysql_free_result($result);
fwrite(STDOUT, "OK\n");
mysql_close($db_connection);
exit(0);
?>