<?php

function errorHandler($code, $from, $dateTime)
{
   // header of message
   $textLT = "<h5>ATMETIMAS</h5>
    <p>NAC/LYAC ataskaita gauta $dateTime nuo $from buvo NEPRIIMTA nes:</p><ul>";
   $textEN = "<h5>REJECTION</h5>
    <p>The NAC/LYAC log received $dateTime from $from has been REJECTED because: <p><ul>";
   $errors = array(1, 2, 4, 8, 16, 32, 64, 128, 256);

   foreach ($errors as $value) {
      //  Bitwise AND
      $result = $value & $code;
      if ($result !== 0) {
         //  Add
         switch ($result) {
            case 1:
               // The callsign problem
               $textLT .= "<li>Ataskaitoje nėra nurodytas Jūsų šaukinys, arba jis yra klaidingas.\n
             Pasitikrinkite šaukinio formatą ir atsiųskite ataskaitą pakartotinai.\n";
               $textEN .= "<li>There is no callsign in your report, or the callsign is wrong.\n
             Please check the format of the callsign and re-send your report again.\n";
               break;
            case 2:
               // The beginning date of the contest problem
               $textLT .= "<li>Ataskaitoje nurodyta varžybų data (arba jos formatas) yra klaidinga.\n
             Pasitikrinkite varžybų datos formatą ir atsiųskite ataskaitą pakartotinai.\n";
               $textEN .= "<li>The date of the contest in your report is unreadable.\n
             Please check the date format and re-send your report again.\n";
               break;
            case 4:
               // The Band used during the contest problem
               $textLT .= "<li>Ataskaitoje nenurodytas arba nurodytas klaidingas bangų ruožas.\n
             Pasitikrinkite bangų ruožo ir atsiųskite ataskaitą pakartotinai.\n";
               $textEN .= "<li>The band specified in your report is invalid.\n
             Please check the band format and re-send your report again.\n";
               break;
            case 8:
               // The World Wide Locator (WWL) problem
               $textLT .= "<li>Ataskaitoje nėra nurodytas Jūsų WWL lokatorius, arba jis yra klaidingas.\n
             Pasitikrinkite WWL formatą ir atsiųskite ataskaitą pakartotinai.\n";
               $textEN .= "<li>There is no WWL locator given in your report or it is incorrect.\n
             Please check the format of the WWL locator and re-send your report again.\n";
               break;
            case 16:
               // Date of contest and date of message mismatch
               $textLT .= "<li>Ataskaitoje nurodyta klaidinga LYAC ryšių data,\n
             nesutampanti su paskutine šio bangų ruožo LYAC turo data.\n
             Pasitikrinkite ryšio datą ir atsiųskite ataskaitą pakartotinai.\n";
               $textEN .= "<li>Your report has incorrect LYAC QSO date.\n
             The date of QSO does not correspond to latest LYAC round date.\n
             Please check the date and re-send your report again.\n";
               break;
            case 32:
               // Date of contest and the band mismatch
               $textLT .= "<li>Ataskaitoje nurodyta varžybų data neatitinka turo bangų ruožo.</p>
             Pasitikrinkite už kurį turą siunčiama ataskaita ir atsiųskite ataskaitą pakartotinai.\n";
               $textEN .= "<li>The contest date, specified in your report does not match the band.\n
             Please check for what LYAC round your report was sent and re-send it again.\n";
               break;
            case 64:
               // Date the contest and QSO dates mismatch
               $textLT .= "<li>Varžybų ir QSO datos nesutampa\n";
               $textEN .= "<li>Date of the contest does not match the QSO date\n";
               break;
            case 128:
               //  Log is too old
               $textLT .= "<li>Ataskaitų priėmimo laikas už šį LYAC turą yra pasibaigęs.\n
             Ataskaita turi būti gauta per 14 dienų nuo atitinkamo turo pravedimo dienos.\n
             Ši ataskaita bus priimta tik kontrolei.\n";
               $textEN .= "<li>The report submission time for this LYAC/NAC round is over.\n
             The report should be submitted no later than 14 days after a contest round.\n
             This report will be accepted as check log.\n";
               break;
            case 256:
               //  Dublicate log
               $textLT .= "<li>Pakartotinai pateikta ataskaita\n";
               $textEN .= "<li>Duplicate log\n";
               break;
            default:
               $textLT .= "<li>Nežinoma klaida: $result\n";
               $textEN .= "<li>Unknown error: $result\n";
               break;
         }
      }
   }
   //  end of message
   $textLT .= "</ul>
    <p>Prašome ištaisyti klaidas ir atsiųsti ataskaitą pakartotinai.</p>";
   $textEN .= "</ul>
    <p>Please correct the errors and send the log again.</p>";
   return $textLT . '<hr />' . $textEN;
}
