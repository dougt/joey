<html>
<body>

<p>Use this form to retrieve data for the current userfrom the DB. It returns
"-1" if the user is not logged in. Or, it returns the entries in the following
format. Use "view source" in your browser to view the returned data -- they are
not rendered properly in the main browser window.</p>

<pre>
$id\n
$name\n
$uri\n
$title\n
$date_created\n
$type\n
$content\n
$filename\n      OR [base64 encoded file data]\n
$thumbnailname\n OR [base64 encoded file data]\n
</pre>

<form action="../getdata.php" method="POST">

First entry: <input type="text" name="first" value="0"/>
<br/>
Number of entries: <input type="text" name="pagesize" value="10"/>
<br/>
Include data? <input type="text" name="sendData"/>
<br/>
<input type="submit" value="Get Data"/>

</form>

</body>
</html>
