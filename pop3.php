<?php
// the pop3 server, username, password, and port
require_once 'pop3.inc';
function extract_attachments($connection, $message_number) { 
  $attachments = array();
  $structure = imap_fetchstructure($connection, $message_number);
  $msg_parts =  buildparts($structure);
  $parts_array = explode("|", $msg_parts);
  for ($sub_part = 0; $sub_part < count($parts_array); $sub_part++) {
    $sub_part_array = explode("=", $parts_array[$sub_part]); 
// $sub_part_array[0] - part number string (section)
// $sub_part_array[1] - encoding
    $attachments[$sub_part] = imap_fetchbody($connection, $message_number, $sub_part_array[0]);
    if ($sub_part_array[1] == 3) { // 3 = BASE64
      $attachments[$sub_part] = base64_decode($attachments[$sub_part]);
    }
    if ($sub_part_array[1] == 4) { // 4 = QUOTED-PRINTABLE
      $attachments[$sub_part] = quoted_printable_decode($attachments[$sub_part]);
    }        
  }          
  return $attachments;  
}
function buildparts(&$struct, $part_number = "") {
  switch ($struct->type):
    case 1: /* multipart */
      $scan = array ();
      $i = 1;
      foreach ($struct->parts as $part)
        $scan[] = buildparts ($part, $part_number.".".$i++);
    return implode("|", $scan);
    break;
/*    case 2:
      return "{".buildparts ($struct->parts[0], $part_number)."}"; */
    default:
    $str =  substr($part_number, 1);
    if (strlen($str) == 0 ) $str = "1"; 
    return $str . "=" . $struct->encoding;
    break;
  endswitch;
}
function decode_imap_text($str){
    $result = '';
    $decode_header = imap_mime_header_decode($str);
    foreach ($decode_header AS $obj) {
        $result .= htmlspecialchars(rtrim($obj->text, "\t"));
    }
    return $result;
}
?>
