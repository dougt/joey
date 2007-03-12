<?php

    require_once(dirname(__FILE__) . '/../../libraries/FileOps.class.php');
    
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

    if (isset($_POST['data']))
      $data = $_POST['data'];
    else
      $data = "not set";

     if ($fileOps->isFile($type)) {
      
      $filename = "";

      if (isset($_POST['data']))
      {
        // Save the data to file
        $data = base64_decode($data);
        $filename = $fileOps->saveFile ($type, $data);
      }
      else
      {
        $filename = $fileOps->moveFile ($type, $_FILES['joeyfile']['tmp_name']);
      }

      // $thumbnail name is '' if the file type is not image or video
      $thumbnailname = $fileOps->generateThumbnail ();

      $filename = mysql_real_escape_string($filename);
      $thumbnailname = mysql_real_escape_string($thumbnailname);
      $type = mysql_real_escape_string($type);

      $query = "INSERT INTO uploads " .
             "(owner, name, type, uuid, uri, title, size, filename, thumbnailname, created, modified, shared ) ".
             "VALUES " .
             "('$userid', '$name', '$type', '$uuid', '$uri', '$title', '$size', '$filename', '$thumbnailname', NOW(), NOW(), 'yes')";
      
    } else {
      
      $type = mysql_real_escape_string($type);
      $data = mysql_real_escape_string($data);
      $query = "INSERT INTO uploads " .
             "(owner, name, type, uuid, uri, title, size, content, created, modified, shared ) ".
             "VALUES " .
             "('$userid', '$name', '$type', '$uuid', '$uri', '$title', '$size', '$data', NOW(), NOW(), 'yes')";
    }

    $result = mysql_query($query);

    if (!$result) {
      echo "-2";
    } else {
      echo mysql_insert_id();
    }

    include 'closedb.php';
?>
