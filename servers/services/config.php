<?php 
$dbname="c_joey";
$host="localhost";
$user="root";
$dbh=mysql_connect ($host,$user,"password") or die ('I cannot connect to the database because: ' . mysql_error(). '');
mysql_select_db ("$dbname") or die('I cannot select the database because: ' . mysql_error());
?>
