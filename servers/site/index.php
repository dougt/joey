  <!-- ***** BEGIN LICENSE BLOCK *****
   - Version: MPL 1.1/GPL 2.0/LGPL 2.1
   -
   - The contents of this file are subject to the Mozilla Public License Version
   - 1.1 (the "License"); you may not use this file except in compliance with
   - the License. You may obtain a copy of the License at
   - http://www.mozilla.org/MPL/
   -
   - Software distributed under the License is distributed on an "AS IS" basis,
   - WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
   - for the specific language governing rights and limitations under the
   - License.
   -
   - The Original Code is Joey Mozilla Project.
   -
   - The Initial Developer of the Original Code is
   - Doug Turner <dougt@meer.net>.
   - Portions created by the Initial Developer are Copyright (C) 2007
   - the Initial Developer. All Rights Reserved.
   -
   - Contributor(s):
   -
   - Alternatively, the contents of this file may be used under the terms of
   - either the GNU General Public License Version 2 or later (the "GPL"), or
   - the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
   - in which case the provisions of the GPL or the LGPL are applicable instead
   - of those above. If you wish to allow use of your version of this file only
   - under the terms of either the GPL or the LGPL, and not to allow others to
   - use your version of this file under the terms of the MPL, indicate your
   - decision by deleting the provisions above and replace them with the notice
   - and other provisions required by the LGPL or the GPL. If you do not delete
   - the provisions above, a recipient may use your version of this file under
   - the terms of any one of the MPL, the GPL or the LGPL.
   -
   - ***** END LICENSE BLOCK ***** -->
   
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


