<?php
require_once 'database.inc';
require_once 'smtp.php';
// A constant to be used as an error return status
define ('DB_CONNECTION_FAILED',1);

// Try connecting to MySQL
@ $db_connection = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
  if  (!$db_connection) {
    // Write to STDERR
    fwrite(STDERR,mysql_error()." DB_CONNECTION_FAILED\n");
    exit(DB_CONNECTION_FAILED);
  }
mysql_select_db(DB_NAME);
fwrite(STDOUT,"Connected to database\n");
// Prepare message
$subject = "LYAC TEST";
$body = "Gerbiamas LYAC dalyvi. 

      Dėkojame Jums už dalyvavimą LYAC aktyvumo vakaruose 2013 metais. Šiuo
metu LYAC organizacinis komitetas analizuoja atsiųstas ataskaitas ir atlieka rezultatų
vertinimus. Tikimės, kad 2013 metų galutinius rezultatus ir nugalėtojus pavyks
paskelbti jau sausio mėnesį. 
      Kaip žinia, be jau įprastų apdovanojimų, 2013 metais buvo įsteigtas
papildomas apdovanojimas dalyviui, kuris parodys geriausia metų rezultatą
nesinaudodamas LYAC/NAC metu on-line pokalbių svetainėmis, pvz. ON4KST chat. 
      Jei Jūs 2013 metais LYAC aktyvumo vakarų metu nesinaudojote panašiais
resursais (not assisted kategorija) ir pretenduojate į papildomai įsteigtą apdovanojimą,
prašome el.adresu www.qrz.lt/lyac iki sausio 13d. LYAC organizaciniam komitetui
atsiųsti laišką su sekančio turinio deklaracija:

=============================================================

Šiuo pareiškiu, kad LYAC varžybose laikiausi varžybų taisyklių  ir radijo mėgėjų
darbo tvarkos reikalavimų. Visi įrašai 2013 metais pateiktose ataskaitoje mano
nuomone yra teisingi. 
2013 metais visų LYAC turų metu, siekdamas rezultato, nesinaudojau jokiomis
realaus laiko Interneto pokalbio svetainėmis (pvz. ON4KST) ar panašiais resursais. 

Šaukinys, Vardas, Pavardė. 



LYAC Komanda.
www.qrz.lt/lyac 
";
fwrite(STDOUT, "$body");
// Send messege
$query = "SELECT id2call(logs.callsignID), id2email(messages.emailID)
 FROM logs INNER JOIN attachments ON logs.attachmentID = attachments.attachmentID INNER JOIN messages ON attachments.sourceID = messages.messageID
  WHERE attachments.source = 'email' and id2call(logs.callsignID) REGEXP '^LY' GROUP BY id2email(messages.emailID) ORDER BY id2call(logs.callsignID);";
    if (!($eMAILs = mysql_query($query))) die("Error " . mysql_errno(  ) . " : " . mysql_error(  ));
// The cycle of emails
$mailNr = 0;
fwrite(STDOUT,"To send this type 'Yes': ");
$line = fgets(STDIN);
//
sendMessage("kliauda@zebra.lt", $subject, $body, "LYAC_2013_metais.doc");
//
  if (trim($line) == 'Yes') { 
    while ($email = mysql_fetch_array($eMAILs)) {
    $mailNr += 1;
    fwrite(STDOUT,"\t$mailNr\t$email[0]\t$email[1]\n");
    $subject = "LYAC to " . $email[0];
//
//      sendMessage($email[1], $subject, $body, "LYAC_2013_metais.doc");
//
    }
  }
  mysql_free_result($eMAILs);
fwrite(STDOUT, "OK\n");
mysql_close($db_connection);
exit(0);
?>