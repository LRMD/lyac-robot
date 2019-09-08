<?php
// A constant to be used as an error return status
define ('MAX_SYSTEM_LOG',8192);  //  Default. Case-sensitive
define ('MAX_LOAD_LOG',16384);

define('MAILGUN_URL', 'https://api.mailgun.net/v3/qrz.lt');
define('MAILGUN_KEY', '');

function sendMailMessage($to, $name, $subject, $body, $path='') {
  return sendmailbymailgun(
    htmlspecialchars($to) . "",
    $name,
    'LYAC Robot', 'owner-lrmd-lyac@qrz.lt',
    $subject,
    $body, strip_tags($body),
    '',
    'lyac@qrz.lt',
    $path
  );
}

function sendmailbymailgun(
  $to,
  $toname,
  $mailfromname,
  $mailfrom,
  $subject,
  $html,
  $text,
  $tag,
  $replyto,
  $file
  ){
  $array_data = array(
  'from'=> $mailfromname .' <'.$mailfrom.'>',
  'to'=>$toname.' <'.$to.'>',
  'subject'=>$subject,
  'html'=>$html,
  'text'=>$text,
  'o:tracking'=>'yes',
  'o:tracking-clicks'=>'yes',
  'o:tracking-opens'=>'yes',
  'o:tag'=>$tag,
  'h:Reply-To'=>$replyto,
  'attachment[1]'=>curl_file_create($file)
  );
  $session = curl_init(MAILGUN_URL.'/messages');
  curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
  curl_setopt($session, CURLOPT_USERPWD, 'api:'.MAILGUN_KEY);
  curl_setopt($session, CURLOPT_POST, true);
  curl_setopt($session, CURLOPT_POSTFIELDS, $array_data);
  curl_setopt($session, CURLOPT_HEADER, false);
  curl_setopt($session, CURLOPT_ENCODING, 'UTF-8');
  curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
  $response = curl_exec($session);
  curl_close($session);
  $results = json_decode($response, true);
  return $results;
}

function deleteOldFiles($path, $days) {
  if ($handle = opendir($path)) {
/* This is the correct way to loop over the directory. */
    while (false !== ($file = readdir($handle))) {
      if ($file[0] == '.' || is_dir("$path/$file")) {
// ignore hidden files and directories
      continue;
      }
      if ((time() - filemtime("$path/$file")) > ($days *86400)) { //  days      
      unlink("$path/$file");
      }
    }
  closedir($handle);
  }
}
function createDirectoryStructure($path, $TDate) {
  $path .= "/";
// if nedd create a subfolder ./YEAR and make this folder world-writable (CHMOD 0777).
  $path .= substr($TDate, 0, 4) . "/";
  if(!is_dir($path)) if(!mkdir($path, 0777))  return FALSE;
// if nedd create a subfolder ./YEAR/MONTH and make this folder world-writable (CHMOD 0777).
  $path .= substr($TDate, 4, 2) . "/";
  if(!is_dir($path)) if(!mkdir($path, 0777))  return FALSE;
  $path .= select_directory($TDate);
  if(!is_dir($path)) if(!mkdir($path, 0777))  return FALSE;
  return  $path;  
}
function select_directory($TDate) {
  $year = substr($TDate, 0, 4);
  $month = substr($TDate, 4, 2);
  $day = substr($TDate, 6, 2);
  $round = getRound($year, $month, $day, 2);  // 2 - Tuesday
    switch ($round) {
      case 1: // First round of month
        $directory = "144";
        break;
      case 2: // Second round of month
        $directory = "432";
        break;
      case 3: // Third round of month
        $directory = "1296";
        break;
      case 4: // Fourth round of month
        $directory = "microwave";
        break;
      case 5: //  Fifth (additional) round of month
        $directory = "additional";
        break;        
      default:
        $directory = "trash";
    }
  return $directory;
}
function log_system($facility, $priority, $message) {
  $path = getcwd() . "/logs/";
  $log_record = array('Timestamp','Facility','Priority','Message');
  $filename = "systemLog";
  $extension = "csv";
  $file = $path . $filename . '.' . $extension;
  $archive = $path . $filename . '.old.' . $extension;
  if(!is_dir($path))  {
    if(!mkdir($path, 0777))  die("main:error:An error occurred while creating directory: $path");  //  Equivalent to exit
  }   
  if(is_file($file))  {  
    $log_file = fopen($file, "a") or die("main:error:an error occurred while open file: $filename");
  } else  {
    $log_file = fopen($file, "w") or die("main:error:an error occurred while open file: $filename");
    fputs($log_file, "sep=,\n");  // csv separator is a comma ("sep=;" is a semicolon)
    fputcsv($log_file, $log_record);
    }
  foreach ($log_record as &$value) {
    $value = ' ';
  }
  $log_record[0] = date("Y/m/d h:i:s", time());
  $log_record[1] = $facility;
  $log_record[2] = $priority;
  $log_record[3] = $message;
  echo "[".join('] [',$log_record)."]\n";
  fputcsv($log_file, $log_record);
  fclose($log_file);
  if (filesize($file) > MAX_SYSTEM_LOG)  {
    if (is_file($archive))  unlink($archive);
  rename($file, $archive);
  }
}
function log_load($mail,$date,$part,$size,$QSOnumber,$callsign,$tdate,$band,$wwl,$error) {
  $path = getcwd() . "/logs/";
  $load_record = array('Timestamp','e-mail','Date','Part','Size','Number of QSO','Callsign','Date of party','Band','Gridsquare','Error code');
  $filename = "loadLog";
  $extension = "csv";
  $file = $path . $filename . '.' . $extension;
  $archive = $path . $filename . '.old.' . $extension;  
  if(!is_dir($path))  {
    if(!mkdir($path, 0777))  die("main:error:An error occurred while creating directory: $path");  //  Equivalent to exit
  }
  if(is_file($file))  {
    $load_file = fopen($file, "a") or die("main:error:an error occurred while open file: $filename");
  } else  {
    $load_file = fopen($file, "w") or die("main:error:an error occurred while open file: $filename");
    fputs($load_file, "sep=,\n");  // csv separator is a comma ("sep=;" is a semicolon)
//  fputcsv($load_file, $load_record, ';', '"');
    fputcsv($load_file, $load_record);
  }
  foreach ($load_record as &$value) {
    $value = ' ';
    }
  $load_record[0] = date("Y/m/d h:i:s", time());
  $load_record[1] = $mail;
  $load_record[2] = $date;
  $load_record[3] = $part;
  $load_record[4] = $size;
  $load_record[5] = $QSOnumber;
  $load_record[6] = $callsign;
  $load_record[7] = $tdate;
  $load_record[8] = $band;
  $load_record[9] = $wwl;
  $load_record[10] = $error;
  fputcsv($load_file, $load_record);
  fclose($load_file);
  if (filesize($file) > MAX_LOAD_LOG) {
    if (is_file($archive))  unlink($archive);
  rename($file, $archive);
  }
}
function week_day($year, $month, $day)  {
// 0 (for Sunday) through 6 (for Saturday)
  return date("w", mktime(0,0,0,$month,$day,$year));
}
/**
 * This function calculates the first [WEEKDAY] of a month.
 * The day to find is passed as an integer to the function.
 * To use: Pass the month, year and day (as an integer 0-6) to the function.
 * $day_of_week [0 = sunday, 1 = monday, 2 = tuesday, 3 = wednesday, 4 = thursday, 5 = friday, 6 = saturday]
 * return day
 */
function getFirstDay($year, $month, $day_of_week){
  $num = date("w",mktime(0,0,0,$month,1,$year));
    if($num==$day_of_week) {
      return date("j",mktime(0,0,0,$month,1,$year));
    }
    elseif($num>$day_of_week) {
      return date("j",mktime(0,0,0,$month,1,$year)+(86400*((7+$day_of_week)-$num)));
    }
    else {
      return date("j",mktime(0,0,0,$month,1,$year)+(86400*($day_of_week-$num)));
    }
}
function getRound($year, $month, $day, $day_of_week) {
// $day_of_week 0 - Sunday, 1 - Monday, 2 - Tuesday,...,6 - Saturday
//$firstday = date("w", mktime(0, 0, 0, $month, 1, $year));
  if (getFirstDay($year, $month, $day_of_week) == $day) return 1;
  if (getFirstDay($year, $month, $day_of_week) + 7 == $day) return 2;
  if (getFirstDay($year, $month, $day_of_week) + 14 == $day) return 3;
  if (getFirstDay($year, $month, $day_of_week) + 21 == $day) return 4;
//$lastday = date("t", mktime(0, 0, 0, $month, 1, $year));
  if (getFirstDay($year, $month, $day_of_week) + 28 == $day) return 5;
  return FALSE;
}
function EmptyDir($dir) {
  $handle=opendir($dir);
    while (($file = readdir($handle))!==false) {
    @unlink($dir.'/'.$file);
    }
  closedir($handle);
}
function delete_directory($dirname) {
  if (is_dir($dirname))
    $dir_handle = opendir($dirname);
  if (!$dir_handle)
    return false;
  while($file = readdir($dir_handle)) {
    if ($file != "." && $file != "..") {
      if (!is_dir($dirname."/".$file))
        unlink($dirname."/".$file);
      else
        delete_directory($dirname.'/'.$file);    
    }
  }
  closedir($dir_handle);
  rmdir($dirname);
  return true;
}
function grid2long($gridsquare) {
//  Returns the longitude coordinates for the given grid coordinates
  $gridsquare = strtoupper($gridsquare);  //  make a string uppercase
  $longitude = (ord(substr($gridsquare, 0, 1)) - 65)*20 - 180;
  $longitude = $longitude + (int)substr($gridsquare, 2, 1)*2;
  $longitude = $longitude + ((ord(substr($gridsquare, 4, 1)) - 65)*5 + 2.5)/60;
  return  $longitude;
}
function grid2lat($gridsquare) {
//  Returns the latitude coordinates for the given grid coordinates
  $gridsquare = strtoupper($gridsquare);  //  make a string uppercase
// uppercase ascii offset is 65 (lowercase - 97)
  $latitude = (ord(substr($gridsquare, 1, 1)) - 65)*10 - 90;
  $latitude = $latitude + (int)substr($gridsquare, 3, 1);
  $latitude = $latitude + ((ord(substr($gridsquare, 5, 1)) - 65)*2.5 + 1.25)/60;
  return  $latitude;        
}
function QRB($grid1, $grid2)  {
// Calculates distance (QRB) between grid squares
  $lat1 = grid2lat($grid1);
  $lon1 = grid2long($grid1);
  $lat2 = grid2lat($grid2);
  $lon2 = grid2long($grid2);
// $distance = (3958*3.1415926*sqrt(($lat2-$lat1)*($lat2-$lat1) + cos($lat2/57.29578)*cos($lat1/57.29578)*($lon2-$lon1)*($lon2-$lon1))/180);
  $distance = (6372.797*3.1415926*sqrt(($lat2-$lat1)*($lat2-$lat1) + cos($lat2/57.29578)*cos($lat1/57.29578)*($lon2-$lon1)*($lon2-$lon1))/180);
  return  round($distance);                                    
}
function QTE($grid1, $grid2)  {
// Calculates azimuth (Bearing, QTE) from first grid square to second
  $lat1 = grid2lat($grid1);
  $lon1 = grid2long($grid1);
  $lat2 = grid2lat($grid2);
  $lon2 = grid2long($grid2);
// great-circle bearing
//$bearing = (rad2deg(atan2(sin(deg2rad($lon2) - deg2rad($lon1)) * cos(deg2rad($lat2)), cos(deg2rad($lat1)) * sin(deg2rad($lat2)) - sin(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($lon2) - deg2rad($lon1)))) + 360) % 360;
//return round($bearing);
// bearing of a rhumb line
//difference in longitudinal coordinates
  $dLon = deg2rad($lon2) - deg2rad($lon1);
//difference in the phi of latitudinal coordinates
  $dPhi = log(tan(deg2rad($lat2) / 2 + pi() / 4) / tan(deg2rad($lat1) / 2 + pi() / 4));
//we need to recalculate $dLon if it is greater than pi
  if(abs($dLon) > pi()) {
    if($dLon > 0) {
      $dLon = (2 * pi() - $dLon) * -1;
    }
    else {
      $dLon = 2 * pi() + $dLon;
    }
  }
//return the angle, normalized
  return (rad2deg(atan2($dLon, $dPhi)) + 360) % 360;                                       
}
define('CR', "\r");          // carriage return; Mac
define('LF', "\n");          // line feed; Unix
define('CRLF', "\r\n");      // carriage return and line feed; Windows
define('BR', '<br />' . LF); // HTML Break
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
