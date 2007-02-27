<?php
    session_start();
    if (empty($_SESSION['userid'])) {
      echo "-1";
    } else {
      unset($_SESSION['userid']);
      echo "0";
    }
?>
