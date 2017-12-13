<?php
// Include the PEAR Mail package
require_once "Mail.php";
// Include the PEAR Mail_Mime packge
require_once ("Mail/mime.php");
require_once ("Mail/mail.php");     // adds the enhanced send function 
// the mail server, username, password, and port
require_once 'smtp.inc';
function sendMessage($to, $subject, $body)  {
// Thear is PEAR::isError BUG. Need change level reporting. 
// Report runtime errors
 $old_error_reporting_level = error_reporting();
 error_reporting(E_ERROR | E_WARNING | E_PARSE); 
//  $to = "lrmd-lyac@qrz.lt";
//  $cc = "";
  $bcc = "kostas.liaudanskas@gmail.com";
// Configure the mailer (SMTP) mechanism
// Identify the mail server, username, password, and port
  $smtp = Mail::factory("smtp",
    array(
      "host"     => "ssl://" . SMTP_HOST,
      "username" => SMTP_USER,
      "password" => SMTP_PASSWORD,
      "auth"     => true,
      "port"     => SMTP_PORT,
    )
  );
// Set up the mail headers
  $headers = array(
    "From"    => "owner-lrmd-lyac@qrz.lt",
    "To"      => $to,
//    "Cc"      => $cc,
//    "Bcc"     => $bcc,
    "Subject" => $subject,
    "Errors-To"     => "kliauda@zebra.lt",
    "MIME-Version"  => "1.0",
  );
  $mime = new Mail_mime("\n");
  $mime->setTXTBody($body);
  if (func_num_args() >= 4)  {
    for ($i = 4; $i <= func_num_args(); $i++)  {
      $filename = func_get_arg ($i-1);
        if(is_file($filename)) $mime->addAttachment($filename);
    }
  }
//do not ever try to call these lines in reverse order  
  $body = $mime->get(array('text_charset' => 'iso-8859-13'));
  $headers = $mime->headers($headers);
//
  $recipient = array (
    0 => $to,
//    1 => $cc,
    1 => $bcc,
  );  
// Send the message

  if (checkEmail($to) && checkEmail($bcc))  {
    $mail = $smtp->send($recipient, $headers, $body);	
    if (PEAR::isError($mail)) echo ($mail->getMessage());
  }
// Restore default reporting level
  error_reporting($old_error_reporting_level);
  return;
}
function  checkEmail($email) {
  $pattern = "/^(.+)@([^\(\);:,<>]+\.[a-zA-Z]{2,4})/";
    if (!preg_match($pattern, $email)) {
    return false;
    }
  return true;
}
