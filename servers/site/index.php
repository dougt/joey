<?php

session_start();

if (empty($_SESSION['userid'])){
  header("location:login.php");
  exit;
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
  <title>WebLoot! Start Page</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>


<?php
echo "<div style=\"float: right\"><a href=\"logout.php\">Logout</a></div><p><p>";
?>
<a href="webloot.xpi">Download the extension</a>
<a href="Generic-midp2-en_US-webloot.jar">Download the midlet</a>
<center>
<h1><span>WebLoot!</span></h1>
</center>

<div id="leftcol">

<?php

include 'config.php';
$ownerid = $_SESSION['userid']; 
$query = "SELECT id, name, uri, title, date_created, type, size FROM upload where owner='$ownerid'";
$result = mysql_query($query) or die('Error, query failed');

if(mysql_num_rows($result) == 0)
{
  echo "Database is empty <br>";
}
else
{
  while($fetched= mysql_fetch_array($result))
  {
      $name = $fetched['name'];
      echo "<div style=\"border: solid; border-color:black; background: grey; font-family:verdana;\">";
      echo "<b>Text Clipping " . $fetched[id] . "</b>";
      echo "<p> <b>Name</b>: " . $name;
      echo "<br><b>Type</b>: " . $fetched['type'];
      echo "<br><b>Date</b>: " . $fetched['date_created'];
      echo "<br><b>URI</b>: " .  base64_decode($fetched['uri']);
      echo "<br><b>Title</b>: " .  base64_decode($fetched['title']);
      echo "<br><b>Size</b>: " . $fetched['size'];

      echo "<a href=view.php?id=";
      echo $fetched[id];
      echo ">";
      echo "<p>Click to view";
      echo "</a>";

      echo "<a href=view.php?doom=1&id=";
      echo $fetched[id];
      echo ">";
      echo "<p>Click to delete";
      echo "</a>";

      echo "</div>";
    echo "<br>";
  }
}
?>

</div>


