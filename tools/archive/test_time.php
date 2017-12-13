<?php
date_default_timezone_set("Europe/Vilnius");
      $time  = "20:35:00";
      $Rtime = "18:38:00";
      $minutes = round(abs(strtotime($time) - strtotime($Rtime)) / 60,2);
      echo "$time\t$Rtime\t$minutes\n";
?>
