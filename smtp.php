<?php
// Include the PEAR Mail package
require_once "Mail.php";
// Include the PEAR Mail_Mime packge
require_once ("Mail/mime.php");
// the mail server, username, password, and port
require_once 'smtp.inc';
function sendMessage($to, $subject, $body)  {
// Thear is PEAR::isError BUG. Need change level reporting. 
// Report runtime errors
 log_system('smtp', 'info', "sendMessage to $to start");

 $old_error_reporting_level = error_reporting();
 error_reporting(E_ERROR | E_WARNING | E_PARSE);
//  $to = "ly3ue@qrz.lt";
//  $cc = "";
  $bcc = "ly2en@qrz.lt";
// Configure the mailer (SMTP) mechanism
// Identify the mail server, username, password, and port
// phpinfo() -> OpenSSL Support : enabled  OpenSSL Version : OpenSSL 0.9.8o 01 Jun 2010
// Transport Layer Security (TLS), Secure Sockets Layer (SSL)
//  if($isSSL) $host="tls://$host";
  $smtp = Mail::factory("smtp",
    array(
      "host"     => "tls://" . SMTP_HOST,
//      "host"     => "ssl://" . SMTP_HOST,
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
    "Errors-To"     => "ly2en@qrz.lt",
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

  $recipient = array (
    0 => $to,
    1 => $bcc,
  );  
// Send the message
  if (checkEmail($to) && checkEmail($bcc))  {
    $mail = $smtp->send($recipient, $headers, $body);
    if (PEAR::isError($mail)) {
      echo ($mail->getMessage());
      log_system('smtp', 'error', "smtp failure to ".$recipient[0].": ".$mail->getMessage());
    }
    else {
      log_system('smtp', 'info', "smtp success to ".$recipient[0]);
    }
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
function errorHandler($code, $from, $dateTime) {
// header of message
  $textLT = "\nLT\nATMETIMAS\nNAC/LYAC ataskaita gauta $dateTime nuo $from neprimta nes:\n";
  $textEN = "\nEN\nREJECTION\nThe NAC/LYAC log received $dateTime from $from rejected because: \n";  
  $errors = array(1, 2, 4, 8, 16, 32, 64, 128, 256);
// body of message
    foreach ($errors as $value) {
//  Bitwise AND
    $result = $value & $code;
      if ($result !== 0) {
//  Add  
        switch ($result) {
          case 1:
// The callsign problem
            $textLT .= "Ataskaitoje nëra nurodytas jûsø ðaukinys, arba jis yra klaidingas.\n
             Pasitikrinkite ðaukinio formatà ir atsiøskite ataskaità pakartotinai.\n"; 
            $textEN .= "There is no call sign in your report, or it is wrong.\n
             Please check the format of the call sign and re-send your report again.\n";
          break;
          case 2:
// The beginning date of the contest problem
            $textLT .= "Ataskaitoje nurodyta varþybø data (arba jos formatas) yra klaidinga.\n
             Pasitikrinkite varþybø datos formatà ir atsiøskite ataskaità pakartotinai.\n"; 
            $textEN .= "The date (or it format) of the contest, shown in your report is wrong.\n
             Please check the date format and re-send your report again.\n";      
          break;
          case 4:
// The Band used during the contest problem
            $textLT .= "Ataskaitoje nenurodytas arba nurodytas klaidingas bangø ruoþas.\n
             Pasitikrinkite bangø ruoþà ir atsiøskite ataskaità pakartotinai.\n"; 
            $textEN .= "There is not band specified in your report, or it is specified wrong.\n
             Please check the band format and re-send your report again.\n";      
          break;
          case 8:
// The World Wide Locator (WWL) problem
            $textLT .= "Ataskaitoje nëra nurodytas jûsø WWL lokatorius, arba jis yra klaidingas.\n
             Pasitikrinkite WWL formatà ir atsiøskite ataskaità pakartotinai.\n"; 
            $textEN .= "There is no WWL locator specified in your report, or it is wrong.\n
             Please check the format of the WWL locator and re-send your report again.\n";      
          break;
          case 16:
// Date of contest and date of message mismatch
            $textLT .= "Ataskaitoje nurodyta klaidinga LYAC ryðiø data,\n
             nesutampanti su paskutine ðio bangø ruoþo LYAC turo data.\n
             Pasitikrinkite ryðiø datà ir atsiøskite ataskaità pakartotinai.\n"; 
            $textEN .= "There is wrong LYAC QSO date shown in your report.\n
             The date of QSO does not correspond to latest LYAC tour date.\n
             Please check the QSO date and re-send your report again.\n";      
          break;
          case 32:
// Date of contest and the band mismatch
            $textLT .= "Ataskaitoje nurodyta varþybø data neatitinka turo bangø ruoþo.\n
             Pasitikrinkite uþ kurá turà siunèiama ataskaita ir atsiøskite ataskaità pakartotinai.\n"; 
            $textEN .= "The contest date, specified in your report does not match the band.\n
             Please check for what LYAC tour your report was sent and re-send it again.\n";      
          break;
          case 64:
// Date the contest and QSO dates mismatch
            $textLT .= "Varzybu ir QSO datos nesutampa\n"; 
            $textEN .= "Date of the contest does not match QSO date\n";   
          break;
          case 128:
//  Log is too old
            $textLT .= "Ataskaitø priëmimo laikas uþ ðá LYAC turà yra pasibaigæs.\n
             Ataskaita turi bûti gauta per 14 dienø nuo atitinkamo turo pravedimo dienos.\n
             Ði ataskaita bus priimta tik kontrolei.\n"; 
            $textEN .= "The report submission time for this LYAC/NAC tour is over.\n
             The report should be submitted in 14 days after corresponded contest tour.\n
             This report will be accepted as check log.\n";      
          break;
          case 256:
//  Dublicate log
            $textLT .= "Pakartotinai pateiktas logas\n"; 
            $textEN .= "Duplicate log\n";      
          break;      
          default:
            $textLT .= "Nezinoma klaida: $result\n"; 
            $textEN .= "Unknown error: $result\n";      
          break;
        } 
      }
    }
//  end of message
  $textLT .= "\nPraðymas iðtaisyti klaidas ir atsiøsti ataskaità pakartotinai."; 
  $textEN .= "\nPlease correct errors and send log again.";      
  return $textLT . $textEN;  
}
?>
