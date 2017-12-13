<?php
// LYAC constants
require_once 'lyac.inc';
// the LYAC funtions
function TDate_PBand($TDate, $PBand) {
  $year = substr($TDate, 0, 4);
  $month = substr($TDate, 4, 2);
  $day = substr($TDate, 6, 2);
  $round = getRound($year, $month, $day, 2);  // 2 - Tuesday
    switch ($round) {
      case 1: // First round of month
          if ( ($PBand >= 144) && ($PBand <= 148) ) return TRUE;
        return FALSE;
      case 2: // Second round of month
          if ( ($PBand >= 430) && ($PBand <= 440) ) return TRUE;
        return FALSE;        
      case 3: // Third round of month
          if ( ($PBand >= 1240) && ($PBand <= 1300) )  return TRUE;
        return FALSE;
      case 4: // Fourth round of month
          if ($PBand < 2300) return FALSE;
        return TRUE;
      case 5: //  Fifth (additional) round of month
        return TRUE;        
      default:
        return FALSE;
    }
}
function TDate_QSOdates($TDate, $testString) {
// may bee there is zero QSO record ?
  $num_QSOs = is_REG1TEST($testString);
    if (($num_QSOs !== false) && ($num_QSOs == 0)) return TRUE;
//  Check format (must bee minimum one QSO record)
  $pattern = '/^([0-9]{2})(0[1-9]|1[0-2])(0[1-9]|[1-2][0-9]|3[0-1]);(2[0-3]|[01][0-9])([0-5][0-9]);([a-zA-Z0-9\/]{3,14});/m'; 
    if (!preg_match($pattern, $testString)) {
//    fwrite(STDOUT, "$testString\n");
    return FALSE;
    }
//  Check values
  $date = substr($TDate, 2, 6);
// QSO lines cycle
  $line = strtok(normalize($testString), "\n");
    while ($line !== false) {
        if (!preg_match($pattern, $line)) {
          $line = strtok("\n");  
          continue;
        }
      $QSOfields = explode(";", $line); // All fields in the QSO record are separated with a semicolon (;).
        if(strcmp($date, $QSOfields[0]) <> 0) {
//        fwrite(STDOUT, "$line\n");  
        return FALSE;
        }
/*
      $month = (int)substr($TDate, 4, 2);
      $hour = (int)substr($QSOfields[1], 0, 2);
        if (($month > 10) || ($month < 4)) {  // November-March
          if (($hour > 22) || ($hour < 17)) {
//          fwrite(STDOUT, "$line\n");  
          return FALSE; // 18:00 - 21:59 GMT
          }
        }
        else  { //  April-October
          if (($hour > 21) || ($hour < 16)) {
//          fwrite(STDOUT, "$line\n");  
          return FALSE; // 17:00 - 20:59 GMT
          }
        }
*/
// Check RST (RS)
// Sent-RST (2 or 3 numbers)
        if (!array_key_exists(4, $QSOfields))  return FALSE;
      $QSOfields[4] = strtoupper($QSOfields[4]);
      $QSOfields[4] = str_replace('A', '9', $QSOfields[4]);
        if (!preg_match('/^[1-5]{1}[1-9]{1,2}$/m', $QSOfields[4]))  return FALSE;
// Received-RST (2 or 3 numbers)
        if (!array_key_exists(6, $QSOfields))  return FALSE;
      $QSOfields[6] = strtoupper($QSOfields[6]);
      $QSOfields[6] = str_replace('A', '9', $QSOfields[6]);
        if (!preg_match('/^[1-5]{1}[1-9]{1,2}$/m', $QSOfields[6]))  return FALSE;
// Check Gridsquares (WWLs)
        if (!array_key_exists(9, $QSOfields))  return FALSE;
//        if (!preg_match('/^[a-zA-Z]{2}\d\d[a-zA-Z]{2}$/m', $QSOfields[9]))  return FALSE;
      $line = strtok("\n");
    }
  return TRUE;
}
?>