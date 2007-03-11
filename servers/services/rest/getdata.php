<?php

    require_once(dirname(__FILE__) . '/../../libraries/FileOps.class.php');

    session_start();

    if (empty($_SESSION['userid']))
    {
      echo "-1";
      exit;
    }
    $userid = $_SESSION['userid'];

    include 'opendb.php';

    $first = $_POST['first'];
    $first = mysql_real_escape_string($first);

    $pagesize = $_POST['pagesize'];
    $pagesize = mysql_real_escape_string($pagesize);

    $sendData = $_POST['sendData'];
    $sendData = mysql_real_escape_string($sendData);

    $query = "SELECT owner, id, name, uri, title, date_created, type, content, filename, thumbnailname " .  "FROM uploads " .  "WHERE owner='$userid' " . "LIMIT $first , $pagesize ";


    $result = mysql_query($query) or die('Error, query failed'  . mysql_error());

    while($res = mysql_fetch_array($result)) 
    {
      if ($sendData)  // I wonder if there is a cleaner way to do this?
      {
        echo "$res[id]\n";
        echo "$res[name]\n";
        echo "$res[uri]\n";
        echo "$res[title]\n";
        echo "$res[date_created]\n";
        echo "$res[type]\n";
        echo "$res[content]\n";
        if (empty($res[filename])) {
          echo "\n";
        } else {
          $filedata = base64_encode(FileOps::getFile($res[filename]));
          echo "STUFF";
          echo "$filedata\n";
        }
        if (empty($res[thumbnailname])) {
          echo "\n";
        } else {
          $thumbnaildata = base64_encode(FileOps::getFile($res[thumbnailname]));
          echo "STUFF";
          echo "$thumbnaildata\n";
        }
      } else {
        echo "$res[id]\n";
        echo "$res[name]\n";
        echo "$res[uri]\n";
        echo "$res[title]\n";
        echo "$res[date_created]\n";
        echo "$res[type]\n";
        echo "$res[content]\n";
        echo "$res[filename]\n";
        echo "$res[thumbnailname]\n";
      }
    }

    include 'closedb.php';
?>
