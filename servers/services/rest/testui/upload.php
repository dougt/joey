<?php
  session_start ();
?>
<html>
<body>

<?php
  if (empty($_SESSION['userid'])) {
    echo 'You have not logged in yet! You must login for the following form to work correctly! Please <a href="login.php">click here</a> to login.<br/>';
  } else {
    echo 'Please <a href="../logout.php">click here</a> to logout of the session.<br/>';
  }
?>

<p>Upload you data! The popup page should display a positive integer forthe new
record ID. If it displays a negative number, the upload has failed.</p>

<form action="../upload.php" method="POST" target="status">

name: <input type="text" name="name"/><br/>
title: <input type="text" name="title"/><br/>
uri: <input type="text" name="uri"/><br/>
size: <input type="text" name="size"/><br/>
uuid: <input type="text" name="uuid"/><br/>
type: <input type="text" name="type"/><br/>
content OR based 64 encoded data for media file: (Use <a target="_blank" href="http://www.motobit.com/util/base64-decoder-encoder.asp">this form</a> to find out the Base64 encoding of your file)<br/>
<textarea name="data" cols="80"></textarea><br/>
<input type="submit" value="upload" onClick="showStatus();"/>

</form>

<script> 
function showStatus() 
{ 
    window.open("Upload Status","status","width=300,height=200,toolbar=0"); 
} 
</script>

</body>
</html>

