<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<style>
body	{background-color: black; color: white; }
</style>
<title>OL Rogue</title>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
</head>
<body>
<h2>OL Rogue Login</h2>
<p>Choose a new name and password to start a new game.</p>
<img style="float:left;margin-right:20px;"  src='sg.jpg' />
<?php
	// TO DO: Connect to rawkrank server instead
	$DBConnect = @mysql_connect("localhost","jpendell_user","vabtha");
	if ($DBConnect === FALSE)
	{
		echo "<p>Unable to connect to the database server.</p>"
			. "<p>Error code " . myself_errono()
			. ": " . mysql_error() . "</p>";
	}
	else
	{
		$DBName = "jpendell_rogue";
		if (!@mysql_select_db($DBName, $DBConnect))
		{
			echo "<p>Cannot find database!</p>";
		}
		else
		{
			echo "<form method='POST' action='OLRogue.php'>";
			echo "<p>Username: <input type='text' name='username' /></p>";
			echo "<p>Password: <input type='text' name='password' /></p>";
			echo "<p><input type='submit' value='Login' /></p>";
			echo "</form>";
		}
		mysql_close($DBConnect);
	}
?>
</body>
</html>