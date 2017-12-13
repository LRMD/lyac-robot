<?php
// if php.ini register_globals = Off
$ec = $_GET['ec'];
switch($ec)
{
case 1:
$message = "An error occurred while performing your request. Please <a
href=logout.php>log in again</a>.";
break;
case 2:
$message = "An error occurred while connecting to the mail pop3 server. Function: imap_open().
Please <a href=logout.php>log in again</a>.";
break;
case 3:
$message = "An error occurred while creating directory. Function: mkdir().
Please <a href=logout.php>log in again</a>.";
break;
case 4:
$message = "An error occurred while creating file. Function: touch().
Please <a href=logout.php>log in again</a>.";
break;
case 5:
$message = "An error occurred while opening file. Function: fopen().
Please <a href=logout.php>log in again</a>.";
break;
case 6:
$message = "An error occurred while connecting to the MYSQL DB. Function: mysql_pconnect().
Please <a href=logout.php>log in again</a>.";
break;
case 7:
$message = "An error occurred while selecting the lyac database. Function: mysql_select_db ().
Please <a href=logout.php>log in again</a>.";
break;
case 8:
$message = "An error occurred while checking compatibly current Unix timestamp and mail message date in Unix time.
Please check system date and time <a href=logout.php>log in again</a>.";
break;
// everything else
default:
$message = "An unspecified error occurred while performing your request.
Please <a href=logout.php>log in again</a>.";
break;
}
?>
<html>
  <head>
  </head>
  <body bgcolor="White">
    <?php echo "<p align='center'><b>Error: </b>$message</p>"; ?>
  </body>
</html>