<html>
<body>

<p>Please use this form to login for this browser session. The return value
will be displayed in a popup window. If the return value is a positibe integer
(i.e., the user id), you are logged in successfully. If it is -1, the login has
failed.</p>

<p>Use the browser BACK button to return to the previous page after you logged in.</p>

<form action="../login.php" method="POST" target="status">

Username: <input type="text" name="username" value=""/>
<br/>
Password: <input type="password" name="password" value=""/>
<br/>
<input type="submit" value="Login" onClick="showStatus();"/>

</form>

<script> 
function showStatus() 
{ 
    window.open("Login Status","status","width=300,height=200,toolbar=0"); 
} 
</script>


</body>
</html>
