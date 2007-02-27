<?php

    function __autoload($class_name) {
     require_once $class_name . '.class.php';
    }
    
    session_start();
    
    if (empty($_SESSION['userid'])) {
      echo "-1";
      exit;
    }
    
    $userid = $_SESSION['userid'];
    $fileOps = new FileOps ($userid);

    $name = $_POST['name'];
    $name = mysql_real_escape_string($name);

    $title = $_POST['title'];
    $title = mysql_real_escape_string($title);

    $uri = $_POST['uri'];
    $uri = mysql_real_escape_string($uri);

    $size = $_POST['size'];
    $size = mysql_real_escape_string($size);

    // optionally??
    $uuid = $_POST['uuid'];
    $uuid = mysql_real_escape_string($uuid);

    //connect to db
    include 'opendb.php';
    $query = "";

    if (!empty($uuid)) {
      // Delete files if exist
      $query  = "SELECT filename, thumbnailname FROM uploads WHERE " .
		 "owner='$userid' AND uuid='$uuid' AND uri='$uri'";
      $result = mysql_query($query);
      while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
        unlink($row['filename']);
        unlink($row['thumbnailname']);
      }
      
      $query = "DELETE FROM uploads WHERE " .
		 "owner='$userid' AND uuid='$uuid' AND uri='$uri'";
	  mysql_query($query);
    } else {
	  $uuid = "";
    }  
    
    $type = $_POST['type'];
    $type = strtolower($type);
    $data = $_POST['data'];
    if ($fileOps->isFile($type)) {
      // Save the data to file
      $data = base64_decode($data);
      $filename = $fileOps->saveFile ($type, $data);
      $thumbnailname = $fileOps->generateThumbnail ();
      
      $type = mysql_real_escape_string($type);
      $filename = mysql_real_escape_string($filename);
      $thumbnailname = mysql_real_escape_string($thumbnailname);
      
      $query = "INSERT INTO uploads " .
             "(owner, name, type, uuid, uri, title, size, filename, thumbnailname, date_created, shared ) ".
             "VALUES " .
             "('$userid', '$name', '$type', '$uuid', '$uri', '$title', '$size', '$filename', '$thumbnailname', NOW(), 'yes')";
      
    } else {
      
      $type = mysql_real_escape_string($type);
      $data = mysql_real_escape_string($data);
      $query = "INSERT INTO uploads " .
             "(owner, name, type, uuid, uri, title, size, content, date_created, shared ) ".
             "VALUES " .
             "('$userid', '$name', '$type', '$uuid', '$uri', '$title', '$size', '$data', NOW(), 'yes')";
    }

    $result = mysql_query($query);

    if (!$result) {
      echo "-2";
    } else {
      echo mysql_insert_id();
    }

    include 'closedb.php';
?>
