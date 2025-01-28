<?php

require_once 'main.inc';
require_once 'functions.php';
require_once 'mysql.php';
require_once 'pop3.php';
require_once 'ftp.php';
require_once 'reg1test.php';
require_once 'errorhandler.php';
require_once 'lyac.php';

set_time_limit(MAX_EXECUTION_TIME);
date_default_timezone_set(TIMEZONE);
header('Content-type: text/plain');
$path = getcwd() . "/logs/";

if (!is_dir($path)) {
    if (!mkdir($path, 0777)) {
        die("main:error:An error occurred while creating directory: $path");
    }  //  Equivalent to exit
}

log_system('main', 'info', 'LYAC ROBOT INIT');

$db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD);
if (!$db) {
    log_system('main', 'error', 'mysql connect error');
    die('Cannot connect to MySQL database. Giving up.');
}

log_system('main', 'info', 'mysql connected');

if (!mysqli_select_db($db, DB_NAME)) {
    mysqli_close($db);
    die("Error: Can\'t select lyac database: " . mysqli_errno($db) . " : " . mysqli_error($db));
}

log_system('main', 'info', 'mysql lyac database found');

$query = "SET time_zone = '+00:00';";

if (!($result = mysqli_query($db, $query))) {
    die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
}

$popStream = @imap_open("{".POP3_HOST.":".POP3_PORT."/".POP3_PARAMETERS."}INBOX", POP3_USER, POP3_PASSWORD);
  if (imap_last_error()) {
      log_system('main', 'error', imap_last_error());
      mysqli_close($db);
  }
log_system('main', 'info', 'lyac pop3 connect OK');

//Connect to the FTP server
@$ftpStream = ftp_connect(FTP_HOST);
if (!$ftpStream) {
    log_system('main', 'warn', 'ftp connect error');
}
log_system('main', 'info', 'ftp connect success');

$login = ftp_login($ftpStream, FTP_USER, FTP_PASSWORD);

if (!$login) {
    log_system('main', 'warn', 'ftp login ERROR');
} else {
    log_system('main', 'info', 'ftp login success');
}
//  Get number of e-mails
$num_msg = imap_num_msg($popStream);
//  prepare dump ddirectory for all parts of messages
$path = getcwd() . "/dump/";
  if (!is_dir($path)) {
      if (!mkdir($path, 0777)) {
          imap_close($popStream);
          mysqli_close($db);
      }
  }
log_system('main', 'info', "$num_msg messages");
log_load('', '', '', '', '', '', '', '', '', '');
// main cycle
// iterate through messages

$good_logs = 0;

for ($msg = 1; $msg <= $num_msg; $msg++) {
    $reg1test = false;
    $reg1test_error = false;

    $headers = imap_headerinfo($popStream, $msg);
//    $name = substr(utf8_decode(imap_utf8($headers->from[0]->personal)), 0, 127);
    if (isset($headers->from[0]->personal)) {
        $name = decode_imap_text($headers->from[0]->personal);
    } else {
        $name = "<No personal>";
    }
    $from = $headers->from[0]->mailbox . '@' . $headers->from[0]->host;
    $reply_to = $headers->reply_to[0]->mailbox . '@' . $headers->reply_to[0]->host;
    $cc = "";
    if (isset($headers->cc[0]->mailbox)) {
        $cc = substr($headers->cc[0]->mailbox . '@' . $headers->cc[0]->host, 0, 1023);
    }
//    $subject = substr(utf8_decode(imap_utf8($headers->subject)), 0, 127);  // string => UTF-8 => ISO-8859-1
    if (isset($headers->subject)) {
        $subject = decode_imap_text($headers->subject);
    } else {
        $subject = "<No subject>";
    }

    log_system('main', 'info', "processing message from $name");

    $udate = $headers->udate;

    // check compatibly current PC Unix timestamp and mail message Unix timestamp
    /*
          if ((int)time() < (int)$udate)  {
            imap_close($popStream);
            mysqli_close($db);
            header("Location: error.php?ec=8");
          }
    */
    $structure = imap_fetchstructure($popStream, $msg);
    $attachments = extract_attachments($popStream, $msg);
    unset($headers);  // destroys the $headers

    // attachments cycle
    for ($part = 0; $part < sizeof($attachments); $part++) {
        $attachments[$part] = remove_utf8_bom($attachments[$part]); //Remove UTF8 Bom
        $partfile = getcwd() . "/dump/" . $from . "_" . date('Y-m-d_H-i-s', $udate) . "_" . $part;
        $dumpfile = $partfile . ".dump";
        @$part_dump = fopen($dumpfile, "w");
        if (!$part_dump) {
            imap_close($popStream);
            mysqli_close($db);
        }
        fputs($part_dump, $attachments[$part]);
        fclose($part_dump);
        // set date and time of file same as e-mail date and time
        touch($dumpfile, $udate);
        $size =  strlen($attachments[$part]);
        // *** Check Way of REG1TEST ***
        // Is the identifier of REG1TEST file? (log recognition)
        $num_QSOs = is_REG1TEST($attachments[$part]);

        // Skip if there are no attachments
        if ($num_QSOs === false) {
            continue;
        }

        // if REG1TEST log rename dump file to edi file
        log_system('main', 'info', "Found attachment $part with $num_QSOs QSOs");
        if (file_exists($partfile . ".edi")) {
            unlink($partfile . ".edi");
        }
        rename($dumpfile, $partfile . ".edi");
        $reg1test = true; // message with reg1test attachment
      $error = 0; // no errors

      // Is the callsign used during the contest (party)?
        $callsign = is_PCall($attachments[$part]);
        if ($callsign === false) {
            $error = $error | 1;
            $callsign = 'ERROR';
            $country = 'ERROR';
        } elseif (($country = prefix($callsign)) === false) {
            $country = 'ERROR';
        }

        // Is the beginning date of the contest (party)?
        $TDate = is_TDate($attachments[$part]);
        if ($TDate === false) {
            $TDate = is_qsoDate($attachments[$part]);
            if ($TDate === false) {
                $error = $error | 2;
                $TDate = '19700101'; //  Unix time
            }
        }

        // Is the Band used during the contest (party)?
        $band = is_PBand($attachments[$part]);
        if ($band === false) {
            $error = $error | 4;
            $band = 'ERROR';
        }

        // Is the own World Wide Locator (WWL, Maidenhead, Universal Locator) used during the contest (party)?
        $wwl = is_PWWLo($attachments[$part]);
        if ($wwl === false) {
            $error = $error | 8;
            $wwl = 'ERROR';
        }
        // Cross checking date of contest (party) (TDate) and the band (PBand) of contest (party)
        if (!TDate_PBand($TDate, $band)) {
            $error = $error | 16;
        }

        // Cross checking dates of contest (party) TDate and timestamp of messages
        $year = substr($TDate, 0, 4);
        $month = substr($TDate, 4, 2);
        $day = substr($TDate, 6, 2);
        if (mktime(0, 0, 0, $month, $day, $year) > $udate) {
            $error = $error | 32;
        }
        // Cross checking date the contest (party) (TDate) and dates of QSO
        if (!TDate_QSOdates($TDate, $attachments[$part])) {
            $error = $error | 64;
        }
        //  Logs no later than 14 days after the event (contest - party)
        if (($udate - mktime(0, 0, 0, $month, $day, $year)) > 15*86400) {
            $error = $error | 128;
        }

        if ($error != 0) {
            $body = errorHandler($error, $from, date("Y-m-d H:m:s", $udate));
            $tmp = $body;

            $status = sendMailMessage(
          $reply_to,
          $name,
          "Rejection of NAC/LYAC log $callsign",
          $body,
          $partfile . ".edi"
        );
            log_system('mail', 'info', 'Reply to '.$name.': Response from MailGun API: ' . $status['message']);
            log_load($from, date(DATE_RFC822, $udate), $part, strlen($attachments[$part]), $num_QSOs, $callsign, $TDate, $band, $wwl, $error);
            imap_delete($popStream, $msg);
            log_system('imap', 'warn', "Processed and rejected " . date(DATE_RFC822, $udate) . " message $from has been deleted");
            $reg1test_error = true; // reg1test attachment with error
            continue;
        }


        // Is LYAC date current year?
        $sql_date = $year . "-" . $month . "-" . $day;
        $sql_udate = gmdate('Y-m-d H:i:s', $udate); // GMT/UTC date/time
        $round_id = get_round_id($sql_date);

        if ($round_id == 0) {
            $reg1test_error = true; // reg1test attachment with error
            log_load($from, date(DATE_RFC822, $udate), $part, strlen($attachments[$part]), $num_QSOs, $callsign, $TDate, $band, $wwl, 'round error');
            // log_system('logs','warn',"\$sql_date: $sql_date, \$sql_udate: $sql_udate");
            continue;
        }

        // Is dublicate of the log ?
        $size = strlen($attachments[$part]);
        $hash = hash('md5', $attachments[$part]);
        $query = "SELECT attachmentID FROM attachments WHERE hash = '$hash' AND size = '$size'";
        if (!$result = mysqli_query($db, $query)) {
            die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
        }
        $num_results = mysqli_num_rows($result);
        mysqli_free_result($result);
        if ($num_results > 0) {
            log_load($from, date(DATE_RFC822, $udate), $part, strlen($attachments[$part]), $num_QSOs, $callsign, $TDate, $band, $wwl, 'dublicate');
            continue;
        } else {
            log_load($from, date(DATE_RFC822, $udate), $part, strlen($attachments[$part]), $num_QSOs, $callsign, $TDate, $band, $wwl, $error);
        }
        // in which section station participates ?
        $section = is_PSect($attachments[$part]);
        if ($section === false) {
            $section = '';
        }
        //the radio club where operator(s) are member
        $club = is_PClub($attachments[$part]);
        if ($club === false) {
            $club = '';
        }
        // save attachment (edi file)
        $path = getcwd();
        if (!$path = createDirectoryStructure($path, $TDate)) {
            imap_close($popStream);
            mysqli_close($db);
            log_system('main', 'error', "an error occurred while create directory: " . getcwd());
            die("main:error:an error occurred while create directory: " . getcwd());
        }
        $filepath = $path . "/" . $callsign . "_" . $band . "_" . $month . ".edi";
        if (!$fp = fopen($filepath, "w")) {
            imap_close($popStream);
            mysqli_close($db);
            log_system('main', 'error', "an error occurred while create directory: " . getcwd());
            die("main:error:an error occurred while open file: " . $callsign . "_" . $band . "_" . $month . ".edi");
        }
        fputs($fp, $attachments[$part]) or die("main:unable to write EDI for $callsign");
        log_system('main', 'info', "wrote $callsign contest data ($TDate) to ". $path . "/" . $callsign . "_" . $band . "_" . $month . ".edi");
        fclose($fp);

        $year = substr($TDate, 0, 4);
        $month = substr($TDate, 4, 2);
        $day = substr($TDate, 6, 2);
        $round = getRound($year, $month, $day, 2);  // 2 - Tuesday
        $ftpdir = "$year/$month/".select_directory($TDate);
        $ftpfile = "$callsign"."_".$band."_".$month.".edi";
        ftp_mksubdirs($ftpStream, '/lyaclogs/', $ftpdir);
        if (ftp_put($ftpStream, "/lyaclogs/$ftpdir/$ftpfile", $filepath, FTP_BINARY)) {
            log_system('main', 'ftp', "uploaded $callsign ".select_directory($TDate)." contest data");
        }

        // set date and time of file same as e-mail date and time
        touch($path . "/" . $callsign . "_" . $band . "_" . $month . ".edi", $udate);
//
        // load log into DB
//
        // Bands
        $sql_band = $band . " MHz";
        $query = "SELECT bandID FROM bands WHERE band_freq = '$sql_band'";
        if (!($result = mysqli_query($db, $query))) {
            die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
        }
        $num_results = mysqli_num_rows($result);
        $row = mysqli_fetch_array($result);
        mysqli_free_result($result);
        $bandID = $row['bandID'];
        if ($num_results == 0) {
            die("Error: band");
        }
        // e-Mails
        $query = "SELECT emailID FROM emails WHERE (email = '$from')";
        if (!($result = mysqli_query($db, $query))) {
            die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
        }
        $num_results = mysqli_num_rows($result);
        $row = mysqli_fetch_array($result);
        mysqli_free_result($result);
        $emailID = $row['emailID'];
        if ($num_results == 0) {
            $query = "INSERT INTO emails VALUES (NULL, '$from');";
            if (!mysqli_query($db, $query)) {
                die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
            }
            $emailID = mysqli_insert_id($db);
        }
        //  CallSigns
        $query = "SELECT callsignID FROM callsigns  WHERE (callsign = '$callsign')";
        if (!($result = mysqli_query($db, $query))) {
            die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
        }
        $num_results = mysqli_num_rows($result);
        $row = mysqli_fetch_array($result);
        mysqli_free_result($result);
        $callsignID = $row['callsignID'];
        if ($num_results == 0) {
            $query = "INSERT INTO callsigns VALUES (NULL, '$callsign');";
            if (!mysqli_query($db, $query)) {
                die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
            }
            $callsignID = mysqli_insert_id($db);
        }
        // WWLs
        $query = "SELECT wwlID FROM wwls WHERE wwl = '$wwl';";
        if (!($result = mysqli_query($db, $query))) {
            die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
        }
        $num_results = mysqli_num_rows($result);
        $row = mysqli_fetch_array($result);
        mysqli_free_result($result);
        $wwlID = $row['wwlID'];
        if ($num_results == 0) {
            $query = "INSERT INTO wwls VALUES (NULL, '$wwl');";
            if (!mysqli_query($db, $query)) {
                die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
            }
            $wwlID =  mysqli_insert_id($db);
        }
        // Messages
        $query = "SELECT messageID FROM messages WHERE date = '$sql_udate' AND emailID = $emailID;";
        if (!($result = mysqli_query($db, $query))) {
            die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
        }
        $num_results = mysqli_num_rows($result);
        $row = mysqli_fetch_row($result);
        mysqli_free_result($result);
        $messageID = $row[0];
        if ($num_results == 0) {
            $name = mysqli_real_escape_string($db, $name);
            $subject = mysqli_real_escape_string($db, $subject);
            $cc = mysqli_real_escape_string($db, $cc);
            //  YYYY-MM-DD HH:MM:SS format (MySQL datetime format) date('Y-m-d H:i:s', $udate);
            $query = "INSERT INTO messages VALUES (NULL, (SELECT emailID FROM emails WHERE email = '$from'), '$sql_udate', '$name', '$subject', '$cc', NOW());";
            if (!mysqli_query($db, $query)) {
                die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
            }
            $messageID = mysqli_insert_id($db);
        }
        // Attachments
        $size = strlen($attachments[$part]);
        $hash = hash('md5', $attachments[$part]);
        $query = "INSERT INTO attachments VALUES (NULL, 'email', $messageID, '$hash' , $size);";
        if (!mysqli_query($db, $query)) {
            die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
        }
        $attachmentID = mysqli_insert_id($db);
        // Logs
        $query = "SELECT logID, wwlID FROM logs WHERE date = '$sql_date' AND callsignID = $callsignID AND bandID = $bandID;";
        if (!$result = mysqli_query($db, $query)) {
            die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
        }
        $num_results = mysqli_num_rows($result);
        $row = mysqli_fetch_row($result);
        mysqli_free_result($result);
        $logID = $row[0];
        $old_wwlID = $row[1];
        if ($num_results == 0) {
            // The new log
            $query = "INSERT INTO logs VALUES (NULL, $attachmentID, $callsignID, '$sql_date' , $bandID, $wwlID, '$section', '$club');";
            if (!mysqli_query($db, $query)) {
                die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
            }
            $logID = mysqli_insert_id($db);
        } else {
            // The correction of the log
            $query = "INSERT INTO qsotrash SELECT logs.attachmentID, qsorecords.qsoID, qsorecords.logID, qsorecords.time, qsorecords.modeID, qsorecords.callsign, qsorecords.rst_s, qsorecords.rst_r, qsorecords.gridsquare
         FROM logs INNER JOIN qsorecords ON logs.logID = qsorecords.logID WHERE logs.logID = $logID;";
            if (!mysqli_query($db, $query)) {
                die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
            }
            // Delete QSO records of the log
            $query = "DELETE FROM qsorecords WHERE logID = '$logID'";
            if (!mysqli_query($db, $query)) {
                die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
            }
            // Update attachmentID, wwlID, section and club of the log
            $query = "UPDATE logs SET attachmentID = $attachmentID, wwlID = $wwlID, section = '$section', club = '$club' WHERE logID = $logID;";
            if (!mysqli_query($db, $query)) {
                die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
            }
//
            $query = "SELECT listID, created FROM list WHERE callsignID = $callsignID and wwlID = $old_wwlID and bandID = $bandID;";
            if (!$HAMs = mysqli_query($db, $query)) {
                die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
            }
            $row = mysqli_fetch_array($HAMs);
            $listID = $row['listID'];
            $created = $row['created'];
            mysqli_free_result($HAMs);
            $query = "DELETE FROM turnout WHERE listID = $listID and date = '$sql_date';";
            if (!mysqli_query($db, $query)) {
                die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
            }
            $query = "DELETE FROM activities WHERE listID = $listID and date = '$sql_date';";
            if (!mysqli_query($db, $query)) {
                die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
            }
            if ($old_wwlID <> $wwlID) {
                // If was change WWL of old log
                if ($created == $sql_date) {
                    $query = "DELETE FROM list WHERE listID = $listID;";
                    if (!mysqli_query($db, $query)) {
                        die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
                    }
                }
            }
        }
        // LYAC list of participants
        $query = "SELECT listID FROM list WHERE callsignID = '$callsignID' and wwlID = '$wwlID' and bandID = $bandID;";
        if (!($HAMs = mysqli_query($db, $query))) {
            die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
        }
        $num_results = mysqli_num_rows($HAMs);
        $row = mysqli_fetch_array($HAMs);
        mysqli_free_result($HAMs);
        $listID = $row['listID'];
        if ($num_results == 0) {
            $query = "INSERT INTO list VALUES (NULL, '$callsignID', '$wwlID', $bandID, '$sql_date', 'Yes');";
            if (!mysqli_query($db, $query)) {
                die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
            }
            $listID =  mysqli_insert_id($db);
        } else {
            $query = "UPDATE list SET valid = 'Yes' WHERE listID = $listID;";
            if (!mysqli_query($db, $query)) {
                die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
            }
        }
        // HAMs relationship of participants
        $query = "SELECT * FROM activities WHERE listID = $listID and date = '$sql_date';";
        if (!($HAMs = mysqli_query($db, $query))) {
            die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
        }
        $num_results = mysqli_num_rows($HAMs);
        mysqli_free_result($HAMs);
        if ($num_results == 0) {
            $query = "INSERT INTO activities VALUES ($listID, '$sql_date', $emailID);";
            if (!mysqli_query($db, $query)) {
                die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
            }
            $query = "DELETE FROM turnout WHERE listID = $listID and date = '$sql_date';";
            if (!mysqli_query($db, $query)) {
                die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
            }
        }
        // QSOrecords
        // I need to know the absolute path to where I am now, ie where this script is running from...
        $path = getcwd() . "/logs";
        $QSOlog = $path . "/" . $callsign . "_" . $band . "_" . $month . ".csv";
        $QSOrow = array('No','Band','Mode','Date','Time','Callsign','Grid','Callsign','Grid','QRB km.','QTE deg.');
        @ $QSO_log = fopen($QSOlog, "w");
        if (!$QSO_log) {
            imap_close($popStream);
            mysqli_close($db);
            header("Location: error.php?ec=5");
        }
        fputs($QSO_log, "sep=,\n");  // csv separator is a comma ("sep=;" is a semicolon)
        //  fputcsv($QSO_log, $QSOrow, ';', '"');
        fputcsv($QSO_log, $QSOrow);
        $totalDistance = 0;
        $maxDistance = 0;
        $totalQSOnum = 0;
        // Lines cycle
        $pattern = '/^(\d{2}((0[1-9]|1[012])(0[1-9]|1\d|2[0-8])|(0[13456789]|1[012])(29|30)|(0[13578]|1[02])31)|([02468][048]|[13579][26])0229);'
       . '(2[0-3]|[01][0-9])([0-5][0-9]);'
       . '[a-zA-Z0-9\/]{3,14};/m';
        $line = strtok(normalize($attachments[$part]), "\n");
        while ($line !== false) {
            if (!preg_match($pattern, $line)) {
                $line = strtok("\n");
                continue;
            }
            $QSOfields = explode(";", $line); // All fields in the QSO record are separated with a semicolon (;).
            if (sizeof($QSOfields) < 10) { // minimum need QSO fields of REG1TEST format
              $line = strtok("\n");
                continue;
            }
            //  remove extra space from a string
            $QSOfields = array_map('trim', $QSOfields);
            $QSOdate = "20" . substr($QSOfields[0], 0, 2) . "-" . substr($QSOfields[0], 2, 2) . "-" . substr($QSOfields[0], 4, 2);
            if (!preg_match('/^(2[0-3]|[01][0-9])([0-5][0-9])/m', $QSOfields[1])) { // is corect time format HHMM?
                $line = strtok("\n");
                continue;
            }
            $QSOtime = substr($QSOfields[1], 0, 2) . ":" . substr($QSOfields[1], 2, 2) . ":00";
            $QSOcallsign = CallSign($QSOfields[2]);
//          $QSOcallsign = strtoupper($QSOfields[2]);
            // if CW Aurora QSO report A (a) change to 9
            $QSOfields[4] = strtoupper($QSOfields[4]);
            $QSOfields[4] = str_replace('A', '9', $QSOfields[4]);
            $QSOfields[6] = strtoupper($QSOfields[6]);
            $QSOfields[6] = str_replace('A', '9', $QSOfields[6]);
            $QSOmode = select_mode($QSOfields[3], $QSOfields[4]);
            $QSOrst_s = intval(substr(trim($QSOfields[4]), -3));
            $QSOno_s = intval(substr(trim($QSOfields[5]), -4));
            $QSOrst_r = intval(substr(trim($QSOfields[6]), -3));
            $QSOno_r = intval(substr(trim($QSOfields[7]), -4));
            if (!preg_match('/^[a-zA-Z]{2}\d\d[a-zA-Z]{2}/m', $QSOfields[9])) { // is corect WWL?
                $line = strtok("\n");
                continue;
            }
            $QSOgridsquare = strtoupper($QSOfields[9]);
            // Check Duplicate-QSO?
            $query = "SELECT qsoID FROM qsorecords WHERE logID = '$logID' AND callsign = '$QSOcallsign';";
            if (!($result = mysqli_query($db, $query))) {
                die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
            }
            $num_results = mysqli_num_rows($result);
            mysqli_free_result($result);
            if ($num_results > 0) {
                $line = strtok("\n");
                continue; // dublicate QSO
            }
            // Check myself-QSO?
            if ($QSOcallsign == $callsign) {
                $line = strtok("\n");
                continue; // oneself myself QSO
            }
            $QSOdistance = (int)QRB($wwl, $QSOgridsquare);
            if ($QSOdistance == 0) {
                $QSOdistance = 1;
            }
            $query = "INSERT INTO qsorecords VALUES (NULL, $logID, '$QSOtime', (SELECT modeID FROM modes WHERE mode = '$QSOmode'), '$QSOcallsign', $QSOrst_s, $QSOrst_r, '$QSOgridsquare', NULL)";
            if (!mysqli_query($db, $query)) {
                die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
            }
            $totalQSOnum += 1;
            $QSOrow[0] = $totalQSOnum;
            $QSOrow[1] = $band;
            $QSOrow[2] = $QSOmode;
            $QSOrow[3] = $QSOdate;
            $QSOrow[4] = $QSOtime;
            $QSOrow[5] = $callsign;
            $QSOrow[6] = $wwl;
            $QSOrow[7] = $QSOcallsign;
            $QSOrow[8] = $QSOgridsquare;
            $QSOrow[9] = $QSOdistance;
            $QSOrow[10] = QTE($wwl, $QSOgridsquare);
            $totalDistance = $totalDistance + $QSOdistance;
            if ($QSOdistance > $maxDistance) {
                $maxDistance = $QSOdistance;
            }
            //  fputcsv($QSO_log, $QSOrow, ';', '"');
            fputcsv($QSO_log, $QSOrow);
            $line = strtok("\n");
        }
//
        foreach ($QSOrow as &$value) {
            $value = ' ';
        }
        $QSOrow[8] = 'Total QRB km';
        $QSOrow[9] = $totalDistance;
        fputcsv($QSO_log, $QSOrow);
        $QSOrow[8] = 'Average QRB of QSO km';
        if ($totalQSOnum <> 0) {
            $QSOrow[9] = round($totalDistance/$totalQSOnum);
        } else {
            $QSOrow[9] = 0;
        }
        fputcsv($QSO_log, $QSOrow);
        $QSOrow[8] = 'Maximum QRB km';
        $QSOrow[9] = $maxDistance;
        fputcsv($QSO_log, $QSOrow);
        fclose($QSO_log);
        // number of QSO can bee zero
        $query = "SELECT IFNULL(SUM(GREATEST(QRB(qsorecords.gridsquare, id2wwl($wwlID)),1)),0), ROUND(IFNULL(AVG(QRB(qsorecords.gridsquare, id2wwl($wwlID))),0)),"
       . " COUNT(gridsquare), COUNT(DISTINCT SUBSTRING(gridsquare,1,4)), IFNULL(MAX(QRB(qsorecords.gridsquare, id2wwl($wwlID))),0)"
       . " FROM qsorecords WHERE logID = '$logID'";
        if (!($result = mysqli_query($db, $query))) {
            die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
        }
        $row = mysqli_fetch_array($result);
        mysqli_free_result($result);
        $score_QSO = $row[0];
        $average_QSO = $row[1];
        $num_QSOs = $row[2];
        $num_WWLs = $row[3];
        $max_QSO = $row[4];
        // sent attachment to FTP server

        $textbodyLT = "\nLT\nNAC/LYAC ataskaitos PATVIRTINIMAS\n";
        $textbodyEN = "\nEN\nCONFIRMATION of the NAC/LYAC log\n";
        $textbodyLT .= "<p>Sveiki,</p>
      <p>Gauta <b>$callsign</b> WWL $wwl LYAC ataskaita už $sql_date $sql_band bangų ruožo turą.</p>
      <p>Ataskaitos duomenys:</p>
      <p><ul>
       <li><u>QSO kiekis</u> - $num_QSOs</li>
       <li><u>Skirtingų QRA lokatorių</u> - $num_WWLs</li>
       <li><u>Suma visų QSO QRB</u> - $score_QSO km</li>
       <li><u>Vidutinis QSO QRB</u> - $average_QSO km</li>
       <li><u>Maksimalus QSO QRB</u> - $max_QSO km</li>
      </ul></p>
      <p><b>Dėmesio</b>: aplankykite naują LYAC roboto svetainę: http://lyac.qrz.lt/</p>
      <p>Nuoširdžiai Jūsų,</p>
      <p> - <br />LYAC komanda<br />
      http://www.qrz.lt/lyac/</p>";

        $textbodyEN .= "<p>Hello,</p>
      <p><b>$callsign</b> (WWL $wwl) successfully submitted QSO log for $sql_date $sql_band band NAC/LYAC tour.</p>
      <p>Log details:</p>
      <p><ul>
       <li><u>Number of QSO</u> - $num_QSOs</li>
       <li><u>Number of unique QRA locators</u> - $num_WWLs</li>
       <li><u>The sum QRBs of all QSOs</u> - $score_QSO km</li>
       <li><u>Average QRB of QSO</u> - $average_QSO km</li>
       <li><u>Maximum QRB of QSOs</u> - $max_QSO km</li>
      </ul></p>
      <p><b>Update</b>: please visit the new LYAC website at: http://lyac.qrz.lt/</p>
      <p>Sincerely yours,</p>
      <p> -
      <br />LYAC team
      <br />http://www.qrz.lt/lyac/
      </p>";
        $prefix = substr($callsign, 0, 2);
        $body = ($prefix == "LY" ? $textbodyLT : $textbodyEN);
        $status = sendMailMessage(
          $reply_to,
          $name,
          "Confirmation of NAC/LYAC log: $callsign",
          $body,
          $QSOlog
        );
        log_system('mail', 'info', 'Response from MailGun API: ' . $status['message']);
    }

    // delete message, if are reg1test attachments and all reg1test attachments without errors
    if ($reg1test and !$reg1test_error) {
        imap_delete($popStream, $msg);
        log_system('imap', 'warn', "Processed " . date(DATE_RFC822, $udate) . " message $from has been deleted");
        $good_logs++;
    }
    // delete older than 30 days messages
    if ((time() - $udate) > 30*86400) {
        // imap_delete ($popStream, $msg);
      // log_system('imap', 'warn', "Old " . date(DATE_RFC822, $udate) . " message $from pending deletion");
    }
    // here ends main loop
}
if ($good_logs > 0) {
  $textbodyLT = "NAC/LYAC ataskaitų robotas\n";
  $textbodyEN = "The NAC/LYAC robot\n";
  $textbodyLT .= "Ataskaitų krovimo roboto logai\n";
  $textbodyEN .= "Logs of loader robot\n";
  $body = $textbodyLT . $textbodyEN;
  $subject = "LYAC robot: To LYAC Team";
  sendMailMessage(
      "simonas@5grupe.lt",
    "LYAC admin",
    $subject,
    $body,
    // getcwd() . "/logs/systemLog.csv"
    getcwd() . "/logs/loadLog.csv"
  );
}
else {
  log_system('mail','info','No new logs ingested. Not sending email report');
}
$syslogDate = date('Y-m-d');
$syslogTime = date('H-i-s');
ftp_mksubdirs($ftpStream, '/lyaclogs', "syslogs/$syslogDate");
if (ftp_put($ftpStream, '/lyaclogs/syslogs/'.$syslogDate.'/systemLog-'.$syslogTime.'.csv', getcwd()."/logs/systemLog.csv", FTP_BINARY)) {
    log_system('main', 'info', "FTP upload systemLog.csv");
} else {
    log_system('main', 'error', 'FTP upload of systemLog.csv failed');
}

if (ftp_put($ftpStream, '/lyaclogs/syslogs/'.$syslogDate.'/loadLog-'.$syslogTime.'.csv', getcwd()."/logs/loadLog.csv", FTP_BINARY)) {
    log_system('main', 'info', "FTP upload loadLog.csv");
} else {
    log_system('main', 'error', 'FTP upload of loadLog.csv failed');
}
//clean up
imap_expunge($popStream);
log_system('imap', 'warn', "Expunging LYAC mailbox ... ");
imap_close($popStream, CL_EXPUNGE);
  if (!mysqli_close($db)) {
      die("Error " . mysqli_errno($db) . " : " . mysqli_error($db));
  }
deleteOldFiles(getcwd() . "/dump", 30);
deleteOldFiles(getcwd() . "/logs", 30);
log_system('main', 'info', 'lyac finish');
//Close FTP connection
if ($ftpStream) {
    ftp_close($ftpStream);
}
