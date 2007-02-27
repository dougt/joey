<?php
   session_start();

   if (empty($_SESSION['userid'])) {
      echo "-1";
      exit;
    }
    $userid = $_SESSION['userid']; 

    include 'opendb.php';

    $query="SELECT COUNT( * ) AS num FROM uploads where owner = '$userid' ";
    $result=mysql_query($query);
    $value = mysql_result($result,0,"num");
    echo "$value";
    
    include 'closedb.php';
?>
