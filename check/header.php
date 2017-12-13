#!/usr/bin/php -q
<?php
define('CR', "\r");          // carriage return; Mac
define('LF', "\n");          // line feed; Unix
define('CRLF', "\r\n");      // carriage return and line feed; Windows
define('BR', '<br />' . LF); // HTML Break 
include("class_lyac.php");
// http://rubular.com/
fwrite(STDOUT, "File name: ");
$filename = fgets(STDIN);
$filename = str_replace(array("\r\n", "\n", "\r", "\n\r"), '', $filename);
@$attachments = file_get_contents($filename);
  if($attachments === false ) {
  fwrite(STDOUT, "file_get_contents($filename) ERROR\n");
  exit(1);
  }
$attachments = remove_utf8_bom($attachments);
$attachments = normalize($attachments);
// create an object
$log = new Lyac($attachments);
// show object properties 
echo $log->getPCall()."\n"; 
echo $log->getPWWLo()."\n"; 
echo $log->getTDate()."\n"; 
echo $log->getPBand()."\n";
echo $log->getPSect()."\n"; 
echo $log->getPClub()."\n";
echo $log->getStatus()."\n";
echo $log->getCountry()."\n";
unset($log);
exit(0);

function remove_utf8_bom($text) {
//Remove UTF8 Bom
    $bom = pack('H*','EFBBBF');
    $text = preg_replace("/^$bom/", '', $text);
    return $text;
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
?> 