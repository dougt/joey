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


 <? 
session_start();

if(isset($_POST['submit'])){
  
  //transfer to shorter var
  $n=$_POST['uname'];
  $p=sha1($_POST['upass']);

  // Don't we have to do something to ensure that n and p
  // are safe to pass to mysql_query.
  
  include('config.php');
  
  $query="select * from user where uname='$n'  and pw='$p' ";
  $result=mysql_query($query);
  
  if($fetched= mysql_fetch_array($result)){

    //put in session vars
    
    $mytime=time();
    $mytime=date("H:i:s A",$mytime);

    $_SESSION['time'] = $mytime;
    $_SESSION['username'] = $n;
    $_SESSION['userid'] = $fetched['id'];
    
    //goto next page
    header("location:index.php");
    exit;
  }
  else
  {
    header( "Location:login.php" ); 
    exit();
  }
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <title>Joey Login</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <link href="styleLog.css" rel="stylesheet" type="text/css">
</head>

<body>
<center>

<form name="login" action="login.php" method="post">
<h1><span>Joey!</span></h1>
Username: <input name="uname" type="text" id="uname" size="50">
<p>
Password: <input name="upass" type="password" id="upass" size="50">

<div align="center">
<a href="password.php">Forgotten your password?</a>
<a href="register.php">Register</a>
</div>
<p>
<input type="submit" name="submit" value="Login">

</form>
</center>

</body>
