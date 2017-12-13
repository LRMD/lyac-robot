<?php
// logout.php - destroy session
// destroy session variables and send back to login page
session_start();
session_destroy();
header("Location:index.php");
?>