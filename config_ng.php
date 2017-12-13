<?php
// Server information
// -------------------------------------------------------------
// These variables contain the server's hostname (or IP address)
// and port to connect to the server as well as the domain part
// "{" remote_system_name [":" port] [flags] "}" [mailbox_name]
// (behind the @) to add to e-mail addresses.
$POPhostname = "mail.example.com";
$POPusername = "login@example.com";
$POPpassword = "password";
$POPparameters = ":110/pop3/tls/novalidate-cert"; // :port/flags

// POP3 default is 110
//$POPparameters = ":110/pop3/notls";

// connect to MySQL Server
$DBserver = "localhost";
$DBusername = "db_username";
$DBpassword = "db_password";

// connect to FTP server (port 21)
$FTPhostname = "ftp.example.com";
$FTPusername = "ftp_login";
$FTPpassword = "ftp_password";

?>
