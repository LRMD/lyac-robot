#!/usr/bin/php -q
<?php
define('CR', "\r");          // carriage return; Mac
define('LF', "\n");          // line feed; Unix
define('CRLF', "\r\n");      // carriage return and line feed; Windows
define('BR', '<br />' . LF); // HTML Break
// http://rubular.com/
fwrite(STDOUT, "File name: ");
$filename = fgets(STDIN);
$filename = str_replace(array("\r\n", "\n", "\r", "\n\r"), '', $filename);
//  Regex for finding valid filename
//   Name of file cannot contain  / ? * : ; { } \
/*  $attachments ="

";  */
@$attachments = file_get_contents($filename);
  if($attachments === false ) {
  fwrite(STDOUT, "file_get_contents($filename) ERROR\n");
  exit(1);
  }
$attachments = remove_utf8_bom($attachments);
// Check the log header
$is_correct_header = TRUE;
// Is the identifier of REG1TEST file? (log recognition)
$num_QSOs = is_REG1TEST($attachments);
  if ($num_QSOs === FALSE)  {
  fwrite(STDOUT, "There is REG1TEST format error\n");        
  $is_correct_header = FALSE;        
  }
// Is the callsign used during the contest (party)?
$callsign = is_PCall($attachments);
  if ($callsign === FALSE)  {
  fwrite(STDOUT, "There is PCall error of REG1TEST file\n");        
  $is_correct_header = FALSE;
  }
fwrite(STDOUT, "PCall=$callsign\n");
// Is the beginning date of the contest (party)?
$TDate = is_TDate($attachments);
  if ($TDate === FALSE)  {
  $TDate = is_qsoDate($attachments);
    if ($TDate === FALSE) {
    fwrite(STDOUT, "There is TDate error of REG1TEST file\n");        
    $is_correct_header = FALSE;
    }       
  }
fwrite(STDOUT, "TDate=$TDate\n");
// Is the own World Wide Locator (WWL, Maidenhead, Universal Locator) used during the contest (party)?
$wwl = is_PWWLo($attachments);
  if ($wwl === FALSE)  {
  fwrite(STDOUT, "There is PWWLo error of REG1TEST file\n");        
  $is_correct_header = FALSE;      
  }
fwrite(STDOUT, "PWWLo=$wwl\n");
// Is the Band used during the contest (party)?
$band = is_PBand($attachments);
  if ($band === FALSE)  {
  fwrite(STDOUT, "There is PBand error of REG1TEST file\n");
  $is_correct_header = FALSE;
  }
fwrite(STDOUT, "PBand=$band\n");
  if  ($is_correct_header === FALSE)  fwrite(STDOUT, "There are log header errors of REG1TEST file\n");  
  else fwrite(STDOUT, "There are OK of log header\n");
// Check QSO Entries
  if (!check_QSOs($attachments, $TDate)) fwrite(STDOUT, "There are QSOs errors of REG1TEST file\n");
  else fwrite(STDOUT, "There are OK of QSOs\n");
exit(0);
function is_PCall($testString) {
// Is the callsign used during the contest?
    if (!preg_match('/^PCall=([a-zA-Z0-9\/]{3,14})\s*$/m', $testString, $matches)) return FALSE;
  $PCall = explode("/", strtoupper($matches[1]), 3);  // example SP4/LY1KL/QRP
    switch (sizeof($PCall)) {
      case 1:
        $callsign = $PCall[0];
        break;
      case 2:
//          if ($PCall[1] === "QRP" || $PCall[1] === "P" || $PCall[1] === "AM" || $PCall[1] === "MM" || $PCall[1] === "M")
          if (strlen($PCall[1]) <= 3)
            $callsign = $PCall[0];
          else  $callsign = $PCall[1];
        break;
      case 3:
        $callsign = $PCall[1];
    }
// digital station identification LY1AAA-4
  $Call =  explode("-", $callsign, 2);  // example LY1KL-4
    if (valid_callsign($Call[0])) return $Call[0];
//    if (valid_callsign($Call[0])) $callsign = $Call[0];
    else return FALSE;
//    else $callsign = $Call[0];    
//    else $callsign = $Call[0] . "?";
//  return $callsign;
}
function valid_callsign($callsign)  {
/*
- zero or one digits
- one or two alphabetics
- one or more digits
- one to three alphabetics (and no more)
*/
/*
valid callsigns could include:
A1A
AA1A
AAA1AAA
A1A1A
1A1A
1AA1AA
*/
//  return preg_match('/^\d?[a-zA-Z]{1,2}\d{1,4}[a-zA-Z]{1,4}$/im', $callsign);
  return preg_match('/^\d?[a-zA-Z]{1,3}|[a-zA-Z]\d[a-zA-Z]?\d{1,}[a-zA-Z0-9]{1,7}\s*$/m', $callsign);
}
function is_TDate($testString) {
// Is the beginning date of the contest?
  $pattern = '/^TDate=;{0,1}([2-9]\d{3})((0[1-9]|1[012])(0[1-9]|1\d|2[0-8])|(0[13456789]|1[012])(29|30)|(0[13578]|1[02])31)|(([2-9]\d)(0[48]|[2468][048]|[13579][26])|(([2468][048]|[3579][26])00))0229\s*$/m';
    if (!preg_match($pattern, $testString, $matches)) return FALSE;
//    $year = $matches[1];
//    $month = $matches[3];
//    $day = $matches[4];
  return $matches[1] . $matches[3] . $matches[4];
}
function is_qsoDate($testString) {
// Is the date of the first QSO?
//  Check format (must bee minimum one QSO record)
  $pattern = '/^(\d{2}((0[1-9]|1[012])(0[1-9]|1\d|2[0-8])|(0[13456789]|1[012])(29|30)|(0[13578]|1[02])31)|([02468][048]|[13579][26])0229);'
   . '((2[0-3]|[01][0-9])([0-5][0-9]));'
   . '([a-zA-Z0-9\/]{3,14});/m';
    if (!preg_match($pattern, $testString, $matches))  return FALSE;
//    Date YYMMDD - $matches[1]
//    Date MMDD  - $matches[2]
//    Month MM  - $matches[3]
//    Day DD  - $matches[4]
//    Time HHMM - $matches[9]
//    Hour HH  - $matches[10]
//    Minutes MM - $matches[11]
//    Callsign -  $matches[12]
  return "20" . $matches[1];
}        
function check_QSOs($testString, $TDate) {
  $is_correct_QSOs = TRUE;
// may bee there is zero QSO record ?
  $num_QSOs = is_REG1TEST($testString);
    if (($num_QSOs !== false) && ($num_QSOs == 0)) return $is_correct_QSOs;
//  Check format (must bee minimum one QSO record)
  $pattern = '/^([0-9]{2})(0[1-9]|1[0-2])(0[1-9]|[1-2][0-9]|3[0-1]);(2[0-3]|[01][0-9])([0-5][0-9]);([a-zA-Z0-9\/]{3,14});/m'; 
    if (!preg_match($pattern, $testString)) {
    fwrite(STDOUT, "$testString\n");
    $is_correct_QSOs = FALSE;
    return $is_correct_QSOs;
    }
//  Check values
  $date = substr($TDate, 2, 6);
// Lines cycle
  $line = strtok(normalize($testString), "\n");
    while ($line !== FALSE) {
        if (!preg_match($pattern, $line)) {
          $line = strtok("\n");  
          continue;
        }
      $QSOfields = explode(";", $line); // All fields in the QSO record are separated with a semicolon (;).
        if(strcmp($date, $QSOfields[0]) <> 0) {
        fwrite(STDOUT, "$line $date <> $QSOfields[0] QSO date error\n");
        $is_correct_QSOs = FALSE;
        }
//  remove extra space from a string
      $QSOfields = array_map('trim', $QSOfields);
      $month = (int)substr($TDate, 4, 2);
      $hour = (int)substr($QSOfields[1], 0, 2);
        if (($month > 10) || ($month < 4)) {  // November-March
          if (($hour > 22) || ($hour < 17)) {
          fwrite(STDOUT, "$line QSO time error\n");
          $is_correct_QSOs = FALSE; // 18:00 - 21:59 GMT
          }
        }
        else  { //  April-October
          if (($hour > 21) || ($hour < 16)) {
          fwrite(STDOUT, "$line QSO time error\n");
          $is_correct_QSOs = FALSE; // 17:00 - 20:59 GMT
          }
        }
// Check RST (RS)
// Sent-RST (2 or 3 numbers)
        if (!array_key_exists(4, $QSOfields))  {
        fwrite(STDOUT, "$line QSO Sent-RST error\n");
        $is_correct_QSOs = FALSE;
        }
        else  {        
        $QSOfields[4] = strtoupper($QSOfields[4]);
        $QSOfields[4] = str_replace('A', '9', $QSOfields[4]);
          if (!preg_match('/^[1-5]{1}[1-9]{1,2}$/m', $QSOfields[4])) {
          fwrite(STDOUT, "$line QSO Sent-RST error\n");
          $is_correct_QSOs = FALSE;
          }
        }
// Received-RST (2 or 3 numbers)
        if (!array_key_exists(6, $QSOfields))  {
        fwrite(STDOUT, "$line QSO Received-RST error\n");
        $is_correct_QSOs = FALSE;
        }
        else  {
        $QSOfields[6] = strtoupper($QSOfields[6]);
        $QSOfields[6] = str_replace('A', '9', $QSOfields[6]);
          if (!preg_match('/^[1-5]{1}[1-9]{1,2}$/m', $QSOfields[6])) {
          fwrite(STDOUT, "$line QSO Received-RST error\n");
          $is_correct_QSOs = FALSE;
          }         
        }
// Check Gridsquares (WWLs)
        if (!array_key_exists(9, $QSOfields))  {
        fwrite(STDOUT, "$line QSO Received WWL error\n");
        $is_correct_QSOs = FALSE;        
        }
        elseif (!preg_match('/^[a-zA-Z]{2}\d\d[a-zA-Z]{2}$/m', $QSOfields[9])) {
        fwrite(STDOUT, "$line QSO Received WWL error\n");
        $is_correct_QSOs = FALSE;
        }
      $line = strtok("\n");
    }
  return $is_correct_QSOs;
}
function is_PBand($testString) {
// Is the Band used during the contest?
    if (!preg_match('/^PBand=(\d[,.\d]\d*)\s*([MGmg][Hh][Zz])\s*$/m', $testString, $matches)) return FALSE;
  $band = $matches[1];
  $band = preg_replace('/\,/', '.', $band);
    if ($matches[2] == "GHz") {
      $band = $band * 1000;  
    }        
//    $band = preg_replace('/[.,]/', '', $band);
//    $band = strtr($band, array('.' => '', ',' => ''));
//    $band = str_replace(array('.', ','), '' , $band);
    if ( ($band >= 50) && ($band <= 54) ) {
      $band = 50;
      return FALSE;
      return $band;
    }
    if ( ($band >= 144) && ($band <= 148) ) {
      $band = 144;
      return $band;
    }
    if ( ($band >= 430) && ($band <= 440) ) {
      $band = 432;
      return $band;
    }      
    if ( ($band >= 1240) && ($band <= 1300) ) {
      $band = 1300;
      return $band;
    }
    if ( ($band >= 2300) && ($band <= 2450) ) {
      $band = 2300;
      return $band;
    }
    if ( ($band >= 3400) && ($band <= 3600) ) {
      $band = 3400;
      return FALSE;
      return $band;
    }
    if ( ($band >= 5650) && ($band <= 5850) ) {
      $band = 5700;
      return $band;
    }
    if ( ($band >= 10000) && ($band <= 10500) ) {
      $band = 10000;
      return $band;
    }
    if ( ($band >= 24000) && ($band <= 24250) ) {
      $band = 24000;
      return $band;
    }
    if ( ($band >= 47000) && ($band <= 47200) ) {
      $band = 47000;
      return FALSE;
      return $band;
    }
    if ( ($band >= 75500) && ($band <= 81000) ) {
      $band = 76000;
      return FALSE;
      return $band;
    }
    if ( ($band >= 119980) && ($band <= 120020) ) {
      $band = 120000;
      return FALSE;
      return $band;
    }
    if ( ($band >= 142000) && ($band <= 148000) ) {
      $band = 144000;
      return FALSE;
      return $band;
    }
    if ( ($band >= 241000) && ($band <= 250000) ) {
      $band = 248000;
      return FALSE;
      return $band;
    }
  return FALSE;
}
function is_PWWLo($testString) {
// Is the own World Wide Locator (WWL, Maidenhead, Universal Locator) used during the contest?
    if (!preg_match('/^PWWLo=([a-zA-Z]{2}\d\d[a-zA-Z]{2})\s*$/m', $testString, $matches)) return FALSE;
  return strtoupper($matches[1]);
}
function is_REG1TEST($testString) {
// Is the identifier of REG1TEST file?
    if (!preg_match('/^\[REG1TEST;1\]\s*$/m', $testString)) return FALSE;
// Is the QSO records idenfifier to mark QSO records begins
    if (!preg_match('/^\[QSORecords;([\d]{0,3})\]\s*$/m', $testString, $matches)) return FALSE;
    if (is_numeric($matches[1]))  return (int)$matches[1];
    else return $matches[1];
}
function normalize($s) {
// Normalize line endings
// Convert all line-endings to UNIX format
//  $s = str_replace("\r\n", "\n", $s);
  $s = str_replace(CRLF, LF, $s);
//  $s = str_replace("\r", "\n", $s);
  $s = str_replace(CR, LF, $s);
// Don't allow out-of-control blank lines
//  $s = preg_replace("/\n{2,}/", "\n\n", $s);
  $s = preg_replace("/\n{2,}/", LF . LF, $s);
  return $s;
}
function remove_utf8_bom($text) {
//Remove UTF8 Bom
    $bom = pack('H*','EFBBBF');
    $text = preg_replace("/^$bom/", '', $text);
    return $text;
}
?>
