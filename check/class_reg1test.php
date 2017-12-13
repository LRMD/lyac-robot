<?php
//  http://www.killerphp.com/tutorials/object-oriented-php/
include("class_callsign.php");
// http://rubular.com/
class Header {
//  Associative Array
  private $header = array(
//  "TName" => '',  //  name of the contest
  'TDate' => '',  //  beginning and ending dates of the contest
  'PCall' => '',  //  callsign
  'PWWLo' => '',  //  World Wide Locator
//  "PExch" => "",  //  own Exchange 
//  "PAdr1" => "",  //  address of the QTH line 1
//  "PAdr2" => "",  //  address of the QTH line 2
  'PSect' => '',  //  section the station is participating  
  'PBand' => '',  //  band
  'PClub' => '',  //  radio club
//  "RName" => "",  //  name of responsible operator
//  "RCall" => "",  //  callsign of responsible
//  "RAdr1" => "",  //  address line 1 of responsible operator
//  "RAdr2" => "",  //  address line 2 of responsible operator
//  "RPoCo" => "",  //  postal code of responsible operator
//  "RCity" => "",  //  city of responsible operator
//  "RCoun" => "",  //  country of responsible operator
//  "RPhon" => "",  //  phone number of responsible operator
//  "RHBBS" => "",  //  home BBS of responsible operator
//  "MOpe1" => "",  //  multi operator line 1
//  "MOpe2" => "",  //  multi operator line 2
//  "STXEq" => "",  //  TX equipment
//  "SPowe" => "",  //  TX power [W]
//  "SRXEq" => "",  //  RX equipment
//  "SAnte" => "",  //  antenna
//  "SAntH" => "",  //  antenna height above ground level [m];height above sea level [m]
//  "CQSOs" => "",  //  claimed number of valid QSOs;band multiplier
//  "CQSOP" => "",  //  claimed number of QSO-points
//  "CWWLs" => "",  //  claimed number of WWLs;bonus per each new WWL;WWL multiplier
//  "CWWLB" => "",  //  claimed number of WWL bonus points
//  "CExcs" => "",  //  claimed number of exchanges;bonus per each new exchange;exchange multiplier
//  "CExcB" => "",  //  claimed number of exchange bonus points
//  "CDXCs" => "",  //  claimed number of DXCCs;bonus per each new DXCC;DXCC multiplier
//  "CDXCB" => "",  //  claimed number DXCC bonus
//  "CToSc" => "",  //  claimed total score
//  "CODXC" => "",  //  call;WWL;distance
  );
  private $status;
  private $code;
  public function __construct($attachment)  {
// Is the identifier of REG1TEST file? (REG1TEST format log recognition)
  $this->status = 0;  // no errors
    if ($this->is_REG1TEST($attachment) === FALSE)  {
      foreach ($this->header as &$value) {
      $value = '';
      }
    $this->status = 255;
    return;      
    }
    if (preg_match('/^PCall=([a-zA-Z0-9\/]{3,14})\s*$/m', $attachment, $matches)) {
//  Calling another class method
    $Callsign = new Callsign($matches[1]);
      if ($Callsign->getPrefix() != "") {
      $this->header['PCall'] =  $Callsign->getPrefix() . $Callsign->getNumeral() . $Callsign->getSuffix();
      $this->code = $Callsign->getCountry();
      }    
    }
    else  {
    $this->status = $this->status | 128;
    $this->header['PCall'] = '';
    $this->code = '';    
    }
    if (($this->header['PWWLo'] = $this->is_PWWLo($attachment)) === FALSE)  {
    $this->status = $this->status | 64;   
    $this->header['PWWLo'] = '';
    }    
    if (($this->header['TDate'] = $this->is_TDate($attachment)) === FALSE)  {
    $this->status = $this->status | 32;   
    $this->header['TDate'] = '';
    }
    if (($this->header['PBand'] = $this->is_PBand($attachment)) === FALSE)  {
    $this->status = $this->status | 16;   
    $this->header['PBand'] = '';
    }
    if (($this->header['PSect'] = $this->is_PSect($attachment)) === FALSE)  {
    $this->status = $this->status | 2;   
    $this->header['PSect'] = '';
    }
    if (($this->header['PClub'] = $this->is_PClub($attachment)) === FALSE)  {
    $this->status = $this->status | 1;   
    $this->header['PClub'] = '';
    }
  return;
  }
  public function __destruct()  {
  }
  private function is_REG1TEST($testString) {
// REG1TEST recognition
    if (!preg_match('/^\[REG1TEST;1\]\s*$/m', $testString)) return FALSE;
  return TRUE;
  }
  private function is_PCall($testString) {
// Is the callsign used during the contest?
      if (!preg_match('/^PCall=([a-zA-Z0-9\/]{3,14})\s*$/m', $testString, $matches)) return FALSE;
    $PCall = explode("/", strtoupper($matches[1]), 3);  // example SP4/LY1KL-4/QRP
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
      if (preg_match('/^\d?[a-zA-Z]{1,3}|[a-zA-Z]\d[a-zA-Z]?\d{1,}[a-zA-Z0-9]{1,7}\s*$/m', $Call[0])) return $Call[0];
      else return FALSE;
  }
  private function is_PWWLo($testString) {
// Is the own World Wide Locator (WWL, Maidenhead, Universal Locator) used during the contest?
    if (!preg_match('/^PWWLo=([a-zA-Z]{2}\d\d[a-zA-Z]{2})\s*$/m', $testString, $matches)) return FALSE;
  return strtoupper($matches[1]);
  }
  private function is_TDate($testString) {
// Is the beginning date of the contest?
    $pattern = '/^TDate={0,1}([2-9]\d{3})((0[1-9]|1[012])(0[1-9]|1\d|2[0-8])|(0[13456789]|1[012])(29|30)|(0[13578]|1[02])31)|(([2-9]\d)(0[48]|[2468][048]|[13579][26])|(([2468][048]|[3579][26])00))0229/m';
      if (!preg_match($pattern, $testString, $matches)) return FALSE;
//    $year = $matches[1];
//    $month = $matches[3];
//    $day = $matches[4];
    return $matches[1] . $matches[3] . $matches[4];
  }
  private function is_PBand($testString) {
// Is the Band used during the contest?
      if (!preg_match('/^PBand=(\d[,.\d]\d*)\s*([MGmg][Hh][Zz])\s*$/m', $testString, $matches)) return FALSE;
    $band = $matches[1];
    $band = preg_replace('/\,/', '.', $band);
      if ($matches[2] == "GHz") {
      $band = $band * 1000;  
      }
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
  private function is_PSect($testString) {
// Is the section (class, category, group) of log?
      if (!preg_match('/^PSect=([\x20-\x7E]{1,64})\s*$/m', $testString, $matches)) return FALSE;
// remove all spaces
    $PSect = str_replace(' ', '_', $matches[1]);
    $PSect = substr(trim(strtoupper($PSect)), 0, 63);
    return $PSect;
  }
  private function is_PClub($testString) {
// Club station where points can be accumulated
      if (!preg_match('/^PClub=([\x20-\x7E]{1,64})\s*$/m', $testString, $matches)) return FALSE;
// remove all spaces
    $PClub = str_replace(' ', '_', $matches[1]);
    $PClub = substr(trim(strtoupper($PClub)), 0, 63);
    return $PClub;
  }
  public function getPCall() {
    return $this->header['PCall'];
  }
  public function getPWWLo() {
    return $this->header['PWWLo'];
  }
  public function getTDate() {
    return $this->header['TDate'];
  }
  public function getPBand() {
    return $this->header['PBand'];
  }
  public function getPSect() {
    return $this->header['PSect'];
  }
  public function getPClub() {
    return $this->header['PClub'];
  }
  public function getStatus() {
    return $this->status;
  }
  public function setStatus($new_status) {
    $this->status = $new_status;
  }
  public function getCountry() {
    return $this->code;
  }     
}
?>