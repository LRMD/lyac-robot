<?php
// index.php
header('content-type: text/html; charset: utf-8');
// Include required configuration
require("config.php");
if(isset($_POST['submit'])) {
session_start(); // start up your PHP session!
$_SESSION["LYAC_uname"] = "LYAC";
// Assign values to the session variables
// connect to the POP server
$_SESSION["POP_HOST"] = $POPhostname;
$_SESSION["POP_USER"] = $POPusername;
$_SESSION["POP_PASS"] = $POPpassword;
$_SESSION["POP_PARAM"] = $POPparameters;
// connect to the MYSQL server
$_SESSION["DB_SERVER"] = $DBserver;
$_SESSION["DB_USER"] = $DBusername;
$_SESSION["DB_PASS"] = $DBpassword;
// connect to the FTP server
$_SESSION["FTP_HOST"] = $FTPhostname;
$_SESSION["FTP_USER"] = $FTPusername;
$_SESSION["FTP_PASS"] = $FTPpassword;
// redirect user to the list page
header("Location: main.php");
}
?>
<html>
  <head>
    <title>LYAC</title>
<!--
    <link rel="shortcut icon" type="image/x-icon" href="http://lyac:8080/favicon.ico" />
    <link rel="icon" href="http://lyac:8080/favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="http://lyac:8080/favicon.ico" type="image/x-icon" />
-->
    <link rel="shortcut icon" href="http://lyac:8080/favicon.ico" type="image/x-icon" />
  </head>
  <body>       
    <form method="POST" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
      <table border="0" cellspacing="5" cellpadding="5" align="center">
        <tr>
          <td colspan="2"><h2 align="center">LYAC</h2></td>
        </tr>
        <tr>
          <td colspan="2"><h3 align="center">Please check and confirm</h3></td>
        </tr>
        <tr>
          <td>POP3 Server</td>
          <td><?php echo $POPhostname; ?></td>
        </tr>
        <tr>
          <td>POP3 Username</td>
          <td><?php echo $POPusername; ?></td>
        </tr>
        <tr>
          <td>POP3 Password</td>
          <td><?php echo $POPpassword; ?></td>
        </tr>
        <tr>
          <td>MYSQL Server</td>
          <td><?php echo $DBserver; ?></td>
        </tr>
        <tr>
          <td>MYSQL Username</td>
          <td><?php echo $DBusername; ?></td>
        </tr>
        <tr>
          <td>MYSQL Password</td>
          <td><?php echo $DBpassword; ?></td>
        </tr>
        <tr>
          <td>FTP Server</td>
          <td><?php echo $FTPhostname; ?></td>
        </tr>
        <tr>
          <td>FTP Username</td>
          <td><?php echo $FTPusername; ?></td>
        </tr>
        <tr>
          <td>FTP Password</td>
          <td><?php echo $FTPpassword; ?></td>
        </tr>
        <tr>
          <td colspan="2"><h4 align="center">Please check and confirm</h4></td>
        </tr>        
        <tr>
          <td><a href="javascript:window.close();void(0)"><p align="center">EXIT&nbsp;</p></a></td>
          <td><input name="submit" type="submit" value="CONFIRM"></td>
        </tr>
      </table>
    </form>    
  </body>
</html>                       