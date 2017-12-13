#!/usr/bin/php -q
<?php
// Check your email
// imap.php
// Include required configuration
require_once 'pop3.inc';
// Assign values to the session variables
date_default_timezone_set('UTC');
// connect to the POP server
// open mailbox POP3 connection
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
  $timestamp = time();
  fwrite(STDOUT, "Number of messages in the current mailbox: $message_count\nUNIX timestamp: $timestamp\n");

// Get our messages from the last week
// $emails = imap_search($popStream, 'SINCE '. date('d-M-Y',strtotime("-1 week")));
  $emails = imap_search($popStream, 'ALL');
    if (!count($emails)) {
    fwrite(STDOUT, "No e-mails found.\n");
    }
    else {
// If we've got some email IDs, sort them from new to old and show them
/* put the newest emails on top */
    rsort($emails);
      foreach($emails as $email_id) {
// Fetch the email's overview and show subject, from and date.
      $overview = imap_fetch_overview($popStream, $email_id, 0);
      $msgno = $overview[0]->msgno; // message sequence number in the mailbox
      $msg_header = imap_headerinfo($popStream, $msgno); 
//      $msg_header = imap_header($popStream, $msgno);
      
//      print_r($overview[0]);
//      break;
	       if (!isset($overview[0]->subject)) {
	       $subject = "";
	       }
	       else {
	       $subject = decode_imap_text($overview[0]->subject);
	       }      
      $from = decode_imap_text($overview[0]->from);
      $date = $overview[0]->date;
        if ((int)$msg_header->udate >= (int)$timestamp) {
        $time_err = "ERR";
        }
        else  {
        $time_err = "OK";
        }
      fwrite(STDOUT, "$msgno $date \t $from \t $subject \n");
      fwrite(STDOUT, "\tudate\t\t$msg_header->udate $time_err\n\tdate\t\t$msg_header->date\n\tMailDate\t$msg_header->MailDate\n\n");
      }
    }
// Close our imap stream.
  imap_close($popStream);
  exit(0);
  }
// A function to decode MIME message header extensions and get the text
// For example "=?ISO-8859-1?Q?Keld_J=F8rn_Simonsen?=" should print "Keld Jørn Simonsen"
function decode_imap_text($str){
    $result = '';
    $decode_header = imap_mime_header_decode($str);
    foreach ($decode_header AS $obj) {
        $result .= htmlspecialchars(rtrim($obj->text, "\t"));
    }
    return $result;
}
?>                     