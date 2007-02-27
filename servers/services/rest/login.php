<?php
    session_start();

    include 'opendb.php';

    $username = $_POST['username'];
    $username = strtolower($username);
    $username = mysql_real_escape_string($username);
    // echo "$username\n";

    $password = $_POST['password'];
    // $password = mysql_real_escape_string($password);
    $password=sha1($password);
    // echo "$password\n";

    $userid = -1;

    $query="select id from users where uname='$username' and password='$password' ";
    $result=mysql_query($query);
    
    if ($fetched = mysql_fetch_array($result)) {
      $userid = $fetched['id'];
      $_SESSION['userid'] = $userid;
      echo "$userid";
    } else {
      echo "-1";
    }
    
    include 'closedb.php';
?>
