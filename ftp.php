<?php
//  the ftp server, username, password, and port
require_once 'ftp.inc';
function copyFtp($local_dir, $remote_dir) {
// variables
  $_server  = FTP_HOST;
  $_user_name = FTP_USER;
  $_user_pass = FTP_PASSWORD;

  $uploadfunction = moveFolder($_server, $_user_name, $_user_pass, $local_dir, $remote_dir);

  return $uploadfunction;
}

function ftp_mksubdirs($ftpcon,$ftpbasedir,$ftpath){
   ftp_chdir($ftpcon, $ftpbasedir); // /var/www/uploads
   $parts = explode('/',$ftpath); // 2013/06/11/username
   foreach($parts as $part){
      if(!ftp_chdir($ftpcon, $part)){
         ftp_mkdir($ftpcon, $part);
	 log_system('main', 'ftp', "mkdir $part");
         ftp_chdir($ftpcon, $part);
         //ftp_chmod($ftpcon, 0777, $part);
      }
   }
}

//Move or Upload Folder Using PHP FTP Functions
function moveFolder($_server, $_user_name, $_user_pass, $local_dir, $remote_dir)  {
// set up basic connection
  $_conn_id = ftp_connect($_server);

// login with username and password
  $_login_result = ftp_login($_conn_id, $_user_name, $_user_pass);

// check connection
  if ((!$_conn_id) || (!$_login_result))  {
    $_error = "FTP connection has failed!";
    $_error .= "Attempted to connect to $_server for user $_user_name";
    $result = false;
  }
  else  {
    $_error = "Connected to $_server, for user $_user_name";
    $result = true;
  }

  $conn_id = $_conn_id;

  @ ftp_mkdir($conn_id, $remote_dir);

  $handle = opendir($local_dir);
  while (($file = readdir($handle)) !== false)  {
    if (($file != '.') && ($file != '..')) {
      if (is_dir($local_dir . $file)) {
//recursive call
        moveFolder($conn_id, $local_dir . $file . '/', $remote_dir . $file . '/');
      }
      else $f[] = $file;
    }
  }
  closedir($handle);
  if (count($f))  {
    sort($f);
    @ ftp_chdir($conn_id, $remote_dir);
      foreach ($f as $files) {
      $from = @ fopen("$local_dir$files", 'r');
      $moveFolder = ftp_fput($conn_id, $files, $from, FTP_BINARY);
// check upload status        
      if (!$moveFolder) {
      $this->_error = "FTP upload has failed! From: " . $local_dir . " To: " . $remote_dir;
      $result = false;
      }
      else  {
      $this->_error = "Uploaded $local_dir to $remote_dir as $this->_server";
      $result = true;
      }
    }
  }
  return $result;
}
?>
