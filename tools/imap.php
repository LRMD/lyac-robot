#!/usr/bin/php -q
<?php
// imap.php
// Include required configuration
//require("config.php");
require_once 'pop3.inc';
// Assign values to the session variables
// connect to the POP server
// open mailbox POP3 connection
// (@ suppress any errors resulting from function call)
//fwrite(STDOUT, "@imap_open('{" . $POPhostname . $POPparameters . "}INBOX'" . ", '" . $POPusername . "', '" . $POPpassword . "');\n");
//$popStream = @imap_open("{" . $POPhostname . $POPparameters . "}INBOX", $POPusername, $POPpassword);
fwrite(STDOUT, "@imap_open('{" . POP3_HOST . ":" . POP3_PORT . "/" . POP3_PARAMETERS . "}INBOX'" . ", '" . POP3_USER . "', '" . POP3_PASSWORD . "');\n");
$popStream = @imap_open("{" . POP3_HOST . ":" . POP3_PORT . "/" . POP3_PARAMETERS . "}INBOX", POP3_USER, POP3_PASSWORD);
// See if a connection was made
  if (imap_last_error())  {
  fwrite(STDOUT, imap_last_error() . "\n");
  print_r(imap_errors());
  print_r(imap_alerts());
  fwrite(STDERR,"imap_open() ERROR\n");
  exit(1);
  }
  else	{
  fwrite(STDOUT,"imap_open() OK\n");
  $message_count = imap_num_msg($popStream);
  fwrite(STDOUT, "Number of messages in the current mailbox:" . $message_count  . "\n");
  imap_close($popStream);
  exit(0);
  }
?>                     