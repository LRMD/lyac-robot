<?php
//  http://www.killerphp.com/tutorials/object-oriented-php/
include("class_reg1test.php");
// 'extends' is the keyword that enables inheritance
class Lyac extends Header {
  public function __construct($attachment) {
  parent::__construct($attachment);
    if ($this->TDate_PBand($this->getTDate(), $this->getPBand()) === FALSE) {
    $this->setStatus($this->getStatus() | 8);
    }
  return;
  }
  public function __destruct()  {
  }
// the LYAC funtions
  private function TDate_PBand($TDate, $PBand)  {
  $year = substr($TDate, 0, 4);
  $month = substr($TDate, 4, 2);
  $day = substr($TDate, 6, 2);
  $round = $this->getRound($year, $month, $day, 2);  // 2 - Tuesday
    switch ($round) {
    case 1: // First round of month
      if (($PBand >= 144) && ($PBand <= 148)) return TRUE;
      return FALSE;
    case 2: // Second round of month
      if (($PBand >= 430) && ($PBand <= 440)) return TRUE;
      return FALSE;        
    case 3: // Third round of month
      if (($PBand >= 1240) && ($PBand <= 1300)) return TRUE;
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
  private function getRound($year, $month, $day, $day_of_week)  {
// $day_of_week 0 - Sunday, 1 - Monday, 2 - Tuesday,...,6 - Saturday
//$firstday = date("w", mktime(0, 0, 0, $month, 1, $year));
    if ($this->getFirstDay($year, $month, $day_of_week) == $day) return 1;
    if ($this->getFirstDay($year, $month, $day_of_week) + 7 == $day) return 2;
    if ($this->getFirstDay($year, $month, $day_of_week) + 14 == $day) return 3;
    if ($this->getFirstDay($year, $month, $day_of_week) + 21 == $day) return 4;
//$lastday = date("t", mktime(0, 0, 0, $month, 1, $year));
    if ($this->getFirstDay($year, $month, $day_of_week) + 28 == $day) return 5;
  return FALSE;
}
/**
 * This function calculates the first [WEEKDAY] of a month.
 * The day to find is passed as an integer to the function.
 * To use: Pass the month, year and day (as an integer 0-6) to the function.
 * $day_of_week [0 = sunday, 1 = monday, 2 = tuesday, 3 = wednesday, 4 = thursday, 5 = friday, 6 = saturday]
 * return day
 */
  private function getFirstDay($year, $month, $day_of_week) {
  $num = date("w",mktime(0,0,0,$month,1,$year));
    if($num==$day_of_week)  {
    return date("j",mktime(0,0,0,$month,1,$year));
    }
    elseif($num>$day_of_week) {
    return date("j",mktime(0,0,0,$month,1,$year)+(86400*((7+$day_of_week)-$num)));
    }
    else  {
    return date("j",mktime(0,0,0,$month,1,$year)+(86400*($day_of_week-$num)));
    }
  }
}
?>