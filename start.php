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
fwrite(STDOUT,"YEAR(YYYY): ");
$year = fgets(STDIN);
$year = ereg_replace("[\r\n]", '', $year);
$year = check_year($year);
  if ($year === FALSE) {
    // Write to STDERR
    fwrite(STDERR,"Invalid year\n");
    mysql_close($db_connection);
    exit(DB_CONNECTION_FAILED);  
  }
$query = "TRUNCATE attachments;";
  if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
$query = "TRUNCATE logs;";
  if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
$query = "TRUNCATE messages;";
  if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
$query = "TRUNCATE qsorecords;";
  if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
$query = "TRUNCATE qsotrash;";
  if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
$query = "TRUNCATE rounds;";
  if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
    for ( $month = 1; $month <= 12; $month++ ) {
    $day = getFirstDay($year, $month, 2);
      for ( $round = 1; $round <= 4; $round++ ) {
        $sql_date = $year . "-" . $month . "-" . $day;
          switch ($round) {
            case 1: // First round of month
              $name = "VHF";
              $bands_group = 1;
            break;
            case 2: // Second round of month
              $name = "UHF";
              $bands_group = 2;
            break;
            case 3: // Third round of month
              $name = "SHF";
              $bands_group = 3;
            break;
            case 4: // Fourth round of month
              $name = "Microwave";
              $bands_group = 4;
            break;
          }
        $query = "INSERT INTO rounds VALUES(NULL,'$sql_date', '$name', $bands_group);";
          if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
        fwrite(STDOUT,"$sql_date\t$bands_group\t$name\n");
        $day += 7;
      }
  }
fwrite(STDOUT, "OK\n");
mysql_close($db_connection);
exit(0);
/**
 * This function calculates the first [WEEKDAY] of a month.
 * The day to find is passed as an integer to the function.
 * To use: Pass the month, year and day (as an integer 0-6) to the function.
 * $day_of_week [0 = sunday, 1 = monday, 2 = tuesday, 3 = wednesday, 4 = thursday, 5 = friday, 6 = saturday]
 * return day
 */
function getFirstDay($year, $month, $day_of_week) {
  $num = date("w",mktime(0,0,0,$month,1,$year));
    if($num==$day_of_week) {
      return date("j",mktime(0,0,0,$month,1,$year));
    }
    elseif($num>$day_of_week) {
      return date("j",mktime(0,0,0,$month,1,$year)+(86400*((7+$day_of_week)-$num)));
    }
    else {
      return date("j",mktime(0,0,0,$month,1,$year)+(86400*($day_of_week-$num)));
    }
}
function check_year($date)  {
    if(!preg_match('/^(\d{4})\s*$/', $date, $matches))
    {
        return FALSE;
    }
    return $matches[1];
} 
?>