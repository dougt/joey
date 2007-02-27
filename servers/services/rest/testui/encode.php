<html>
<body>

<p>Please upload the file here</p>

<form enctype="multipart/form-data"
      action="encode.php" method="POST">
  <!-- MAX_FILE_SIZE must precede the file input field -->
  <input type="hidden" name="MAX_FILE_SIZE" value="30000" />
  <!-- Name of input element determines name in $_FILES array -->
  Select file: <input name="userfile" type="file" /><br/>
  <input type="submit" value="Encode" />
</form>

<p>The Base64 Encoded data in file <?=$_FILES['userfile']['name']?> is as follows.</p>

<pre>
<?php
$uploadname = "tmp";
if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadname)) {
  $fh = fopen ($uploadname, 'r');
  $data = fread ($fh, filesize($uploadname));
  fclose ($fh);
  $data = base64_encode($data);
  echo "$data";
} else {
   echo "Possible file upload attack!\n";
}
?>
</pre>

