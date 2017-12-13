<?php
// REG1TEST for LYAC constants
require_once 'reg1test.inc';
// the reg1test format funtions
function is_REG1TEST($testString) {
// Is the identifier of REG1TEST file?
    if (!preg_match('/^\[REG1TEST;1\]\s*$/m', $testString)) return FALSE;
// Is the QSO records idenfifier to mark QSO records begins
    if (!preg_match('/^\[QSORecords;([\d]{0,3})\]\s*$/m', $testString, $matches)) return FALSE;
    if (is_numeric($matches[1]))  return (int)$matches[1];
    else return $matches[1];
}
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
function CallSign($testString) {
  $PCall = explode("/", strtoupper($testString), 3);  // example SP4/LY1KL/QRP
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
    if (valid_callsign($Call[0])) $callsign = $Call[0];
    else $callsign = $Call[0];    
//    else $callsign = $Call[0] . "?";
  return $callsign;  
}
/* Function to validate ham radio callsign */
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
//  return preg_match('/^(\d?[a-zA-Z]{1,3}|[a-zA-Z]\d[a-zA-Z]?)\d[a-zA-Z1-9]{1,7}$/i', $callsign);
//  return preg_match('/^\d?[a-zA-Z]{1,2}\d{1,4}[a-zA-Z]{1,4}$/im', $callsign);
  return preg_match('/^\d?[a-zA-Z]{1,3}|[a-zA-Z]\d[a-zA-Z]?\d{1,}[a-zA-Z1-9]{1,7}\s*$/m', $callsign);
}
function prefix($callsign)  {
$callsign = strtoupper($callsign);
$pattern = '/^(\d?[a-zA-Z]{1,3}|[a-zA-Z]\d[a-zA-Z]?)(\d{1,})([a-zA-Z0-9]{1,7})\s*$/m';
  if(preg_match($pattern, $callsign, $matches)) {
  return $matches[1];
  }
return false;  
}
function is_PWWLo($testString) {
// Is the own World Wide Locator (WWL, Maidenhead, Universal Locator) used during the contest?
    if (!preg_match('/^PWWLo=([a-zA-Z]{2}\d\d[a-zA-Z]{2})\s*$/m', $testString, $matches)) return FALSE;
  return strtoupper($matches[1]);
}
function is_TDate($testString) {
// Is the beginning date of the contest?
  $pattern = '/[\n\r]TDate=;{0,1}([2-9]\d{3})((0[1-9]|1[012])(0[1-9]|1\d|2[0-8])|(0[13456789]|1[012])(29|30)|(0[13578]|1[02])31)|(([2-9]\d)(0[48]|[2468][048]|[13579][26])|(([2468][048]|[3579][26])00))0229/';
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
function select_mode($mode_TX, $send_rst)  {
// From Mode code of REG1TEST format selects the TX mode
    switch ($mode_TX) {
      case '0':
        $pattern = '/^[12345][123456789][123456789]$/m';
          if (preg_match($pattern, $send_rst)) return 'CW';
        $pattern = '/^[12345][123456789]$/m';
//          if (preg_match($pattern, $send_rst)) return 'SSB';          
        return 'unknown';
      case '1':
        return 'SSB';
      case '2':
        return 'CW';
      case '3':
        return 'SSB';
      case '4':
        return 'CW';
      case '5':
        return 'AM';
      case '6':
        return 'FM';
      case '7':
        return 'RTTY';
      case '8':
        return 'SSTV';
      case '9':
        return 'ATV';
      default:
        $pattern = '/^[12345][123456789][123456789]$/m';
          if (preg_match($pattern, $send_rst)) return 'CW';
        $pattern = '/^[12345][123456789]$/m';
//          if (preg_match($pattern, $send_rst)) return 'SSB';
        return 'unknown';                       
    }
}
function is_PSect($testString) {
// Is the section (class, category, group) of log?
    if (!preg_match('/^PSect=([\x20-\x7E]{1,64})\s*$/m', $testString, $matches)) return FALSE;
// remove all spaces
  $PSect = str_replace(' ', '_', $matches[1]);
  $PSect = strtoupper($PSect);
  $PSect = substr(trim($PSect), 0, 63);
  return $PSect;
}
function is_PClub($testString) {
// Club station where points can be accumulated
    if (!preg_match('/^PClub=([\x20-\x7E]{1,64})\s*$/m', $testString, $matches)) return FALSE;
// remove all spaces
  $PClub = str_replace(' ', '_', $matches[1]);
  $PClub = strtoupper($PClub);
  $PClub = substr(trim($PClub), 0, 63);
  return $PClub;
}
?>
