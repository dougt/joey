/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Joey Mozilla Project.
 *
 * The Initial Developer of the Original Code is
 * Doug Turner <dougt@meer.net>.
 * Portions created by the Initial Developer are Copyright (C) 2007
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */
 
 <?php 

if(isset($_POST['Submit'])){

  //NEED TO CHECK IF FIELDS ARE FILLED IN
  if( empty($_POST['name']) && (empty($_POST['email']))){
    echo "name+email";
    exit();
  }

 if( empty($_POST['pw1']) && (empty($_POST['pw2']))){
    echo "password";
    exit();
  }
  $name=$_POST['name'];
  $email=$_POST['email'];
  
  $pw1=$_POST['pw1'];
  $pw2=$_POST['pw2'];
  
  if("$pw1" !== "$pw2"  ){
     echo "pw match";    
    exit();
  }

  $ip = $_SERVER['REMOTE_ADDR'];
  
  //connect to the db server , check if uname exist
  include('config.php');
  $query=("Select * from user where uname='$name'");
  $result= mysql_query($query); 
  $num=mysql_num_rows($result);
  
  if ($num > 0) {//Username already exist
    echo "user exists";
    exit();
  }
  else
  {
    //if username does not exist insert user details
    $query=( "INSERT INTO user (uname, pw, email, date_joined, ip, level) VALUES ('$name',sha1('$pw1'), '$email', NOW(), '$ip', 'Normal')");
  
    if (@mysql_query ($query))
    {
      header("location:beta.php");
      exit;
    }
  }
  mysql_close();
}
?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
  <title>Joey! Start Page</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>

<center>
<h1><span>Joey! Register</span></h1>
</center>

<body>

<form name="form1" action="register.php" method="post">

<div style="width: 400px; margin-left: 29%;">
<div style="float: left">
<p>User:
<p>Email:
<p>Password:
<p>Repeat Password:
</div>
<div style="float: right">
<p><input name="name" type="text">
<p><input name="email" type="text">
<p><input name="pw1" type="password">
<p><input name="pw2" type="password">
<p><input name="Submit" type="submit">
</div>
</div>

</form>

</body>
