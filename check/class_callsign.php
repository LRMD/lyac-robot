<?php
//  http://www.killerphp.com/tutorials/object-oriented-php/
// http://rubular.com/
// Call sign parser
class Callsign {
  private $sign = array ('before' => '',
                        'prefix' => '',
                        'numeral' => '',
                        'suffix' => '',
                        'after' => '');
  private $prefixes = array(
  'BY' => '/^([Ee][U-Wu-w][a-zA-Z]?)\d/m',  // EUA-EWZ
  'CZ' => '/^([Oo][K-Lk-l][a-zA-Z]?)\d/m',  // OKA-OLZ
  'DK' => '/^([Oo][U-Zu-z][a-zA-Z]?|[Xx][Pp][a-zA-Z]?|5[P-Qp-q][a-zA-Z]?)\d/m', // OUA-OZZ XPA-XPZ 5PA-5QZ
  'EE' => '/^([Ee][Ss][a-zA-Z]?)\d/m',  // ESA-ESZ
  'FI' => '/^([Oo][F-Jf-j][a-zA-Z]?)\d/m',  // OFA-OJZ
  'DE' => '/^([Dd][A-Ra-r][a-zA-Z]?|[Yy][2-9][a-zA-Z]?)\d/m', // DAA-DRZ Y2A-Y9Z
  'LV' => '/^([Yy][Ll][a-zA-Z]?)\d/m',  // YLA-YLZ
  'LT' => '/^([Ll][Yy][a-zA-Z]?)\d/m',  // LYA-LYZ
  'NO' => '/^([Jj][W-Xw-x][a-zA-Z]?|[Ll][A-Na-n][a-zA-Z]?|3[Yy][a-zA-Z]?)\d/m', // JWA-JXZ LAA-LNZ 3YA-3YZ
  'PL' => '/^([Hh][Ff][a-zA-Z]?|[Ss][N-Rn-r][a-zA-Z]?|3[Zz][a-zA-Z]?)\d/m', // HFA-HFZ SNA-SRZ 3ZA-3ZZ
  'RU' => '/^([Rr][a-zA-Z]?[a-zA-Z]?|[Uu][A-Ia-i][a-zA-Z]?)\d/m', // RAA-RZZ UAA-UIZ
  'SK' => '/^([Oo][Mm][a-zA-Z]?)\d/m',  // OMA-OMZ
  'SE' => '/^([Ss][A-Ma-m][a-zA-Z]?|7[Ss][a-zA-Z]?|8[Ss][a-zA-Z]?)\d/m',  // SAA-SMZ 7SA-7SZ 8SA-8SZ
  'UA' => '/^([Ee][M-Om-o][a-zA-Z]?|[Uu][R-Zr-z][a-zA-Z]?)\d/m',  // EMA-EOZ URA-UZZ
  '*' => '/^(\d?[a-zA-Z]{1,3}|[a-zA-Z]\d[a-zA-Z]?)(\d{1,})([a-zA-Z0-9]{1,7})\s*$/m'); // default standart callsign
  private $countryCode;
  public function __construct($callsign)  {
  $parts = explode("/", strtoupper($callsign), 3);  // example SP4/LY1KL-4/QRP
    switch (sizeof($parts)) {
      case 1:
        $this->sign['before'] = '';
        $call = $parts[0];
        $this->sign['after'] = '';
        break;
      case 2:
          if (strlen($parts[1]) <= 3) {
          $this->sign['before'] = '';
          $call = $parts[0];
          $this->sign['after'] = $parts[1];
          }
          else  {
          $this->sign['before'] = $parts[0];
          $call = $parts[1];
          $this->sign['after'] = '';
          }
        break;
      case 3:
        $this->sign['before'] = $parts[0];
        $call = $parts[1];
        $this->sign['after'] = $parts[2];
        break;        
    }
// if is digital station identification (LY1KL-4) remove this
  $Call =  explode("-", $call, 2);  // example LY1KL-4
    foreach($this->prefixes as $code => $pattern) 
      if (preg_match($pattern, $Call[0], $matches)) break;
      else  $code = ''; 
  $this->countryCode = $code;
    switch ($this->countryCode) {
    case '*': // unnow prefix
      $this->sign['prefix'] = $matches[1];
      $this->sign['numeral'] = $matches[2];
      $this->sign['suffix'] = $matches[3];            
      break;
    case '':  // bad callgign
        foreach ($this->sign as &$value) {
        $value = '';
        }            
      break;
    default:
      $this->sign['prefix'] = $matches[1];
      $matches = explode($this->sign['prefix'], $Call[0], 2); // Split a string by string
        if (preg_match('/^(\d{1,})([a-zA-Z0-9]{1,7})\s*$/m', $matches[1], $Call)) {
        $this->sign['numeral'] = $Call[1];
        $this->sign['suffix'] = $Call[2];
        }                
    }
  return;
  }
  public function __destruct()  {
  }
  public function getBefore() {
    return $this->sign['before'];
  }
  public function getPrefix() {
    return $this->sign['prefix'];
  }
  public function getNumeral() {
    return $this->sign['numeral'];
  }
  public function getSuffix() {
    return $this->sign['suffix'];
  }
  public function getAfter() {
    return $this->sign['after'];
  }
  public function getCountry() {
    return $this->countryCode;
  } 
}
?>