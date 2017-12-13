<?php
/* Function to validate ham radio callsign */
// <prefix><single decimal digit><1 to 3 alpha characters>
function check_call_sign($callsign) {
// '/^\d?[a-z]{1,2}\d{1,4}[a-z]{1,3}$/i'
  return preg_match('=\^d?[a-z]{1,2}\d{1,4}[a-z]{1,3}$=i', $callsign);
}
// Regex for Amateur Radio Callsigns
$pattern = "/^[0-9]?[A-Z]{1,2}[0-9][A-Z]{1,3}/";
// /^([0-9]?[A-Za-z]{1,2})[0-9][A-Za-z]{1,3}$/m
$pattern = "/^[0-9]?[A-Z]{1,2}[0-9][A-Z]{1,3}/";
$pattern = "/^([0-9]?[A-Za-z]{1,2})[0-9][A-Za-z]{1,3}$/m";
$pattern = '/^(\d?[a-z]{1,3}|[a-z]\d[a-z]?)\d[a-z]{1,3}$/';
/* a callsign basically has three parts:
    1) alpha-numeric prefix which could be one of these combinations...
	   A-Z{1,3}               // between one and three letters
	   [A-Z][0-9]             // one letter followed by one number
	   [A-Z][0-9][A-Z]     // one letter, one number, one letter
	   [0-9]([A-Z]{1,3})   // one number followed by between one and three letters

    2) a single number: [0-9]

    3) followed by between one and three letters: [A-Z]{1,3}
*/

function validate_callsign($callsign){
$regex = "[0-9]?"; // first character of prefix MAY be a number (but usually isn't)
$regex.= "[A-Z]{1,2}"; // Must start with 1 or 2 Letters
$regex.= "[0-9]"; //followed by 1 number
$regex.= "[A-Z][0-9]?"; //there MAY be a char and number here IF the 2nd charecter of the prefix was a number
$regex.= "[A-Z]{1,3}"; //and 1 to 3 more letters

  if(preg_match("/".$regex."/i",$callsign) ){
   return true;
  }
}
/*
valid callsigns could include:
A1A
AA1A
AAA1AAA
A1A1A
1A1A
1AA1AA
*/
/*
- zero or one digits
- one or two alphabetics
- one or more digits
- one to three alphabetics (and no more)
*/
// function to check if argument is in format of a valid callsign
function valid_call($callsign){
  return preg_match('/^\d?[a-z]{1,3}\d{1,4}[a-z]{1,3}$/i', $callsign);
/* '/^(\d?[a-zA-Z]{1,3}|[a-zA-Z]\d[a-zA-Z]?)\d[a-zA-Z]{1,3}$/i' */
}
function prefix($callsign)  {
/*
- zero or one digits
- one or two alphabetics
- one or more digits
- one to three alphabetics (and no more)
*/
$pattern = '/^(\d?[a-zA-Z]{1,3}|[a-zA-Z]\d[a-zA-Z]?)\d[a-zA-Z]{1,3}$/i';
  if(preg_match($pattern, $callsign, $matches)){
  return $matches[1];
  }
return false;  
}
/* How to check if mysql returns null/empty */
// [connect…]
$qResult=mysql_query("Select foo from bar;");
while ($qValues=mysql_fetch_assoc($qResult))
     if (is_null($qValues["foo"]))
         echo "No foo data!";
     else
         echo "Foo data=".$qValues["foo"];
// […]

      $query = "UPDATE qsorecords SET confirm = confirm | b'01000000' WHERE qsoID = $qsoID;";
        if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
//  Times of QSOs                        
      $query = "UPDATE qsorecords SET confirm = confirm | b'00000100' WHERE qsoID = $qsoID AND ABS(TIME_TO_SEC(TIMEDIFF('$time', '$Rtime'))) < 600;";
        if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
//  Gridsquares (WWLs) of QSOs
      $query = "UPDATE qsorecords SET confirm = confirm | b'00000010' WHERE qsoID = $qsoID AND '$gridsquare' = '$Rwwl';";
        if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
//  RST (RS) reports of QSOs
      $query = "UPDATE qsorecords SET confirm = confirm | b'00000001' WHERE qsoID = $qsoID AND $rst_r = $rst_s AND $rst_r != 0 AND $rst_s != 0;";
        if (!mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
// compare dates
        $query = "SELECT turnout.date FROM turnout INNER JOIN list ON turnout.listID = list.listID
         WHERE id2call(list.callsignID) = '$callsign' AND id2wwl(list.wwlID) = '$gridsquare' AND list.bandID = $bandID AND turnout.date < '$date';";
          if (!$history = mysql_query($query)) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
                  
        $a = '!abc3@jdk9$38`~]\]2';
$number = str_replace(['+', '-'], '', filter_var($a, FILTER_SANITIZE_NUMBER_FLOAT));
// Output is 39382

?>