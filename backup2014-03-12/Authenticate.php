<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>OL Rogue</title>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
</head>
<body>
<h2>OL Rogue</h2>
<?php
	// TO DO: Roll the contents of this file into main rr.php script.
	function startGame($username)
	{
		echo "<form action='rr.php'  method='post'>";
		echo "<input type='text' name='username' value='";
		echo $username;
		echo "' />";
		echo "<input type='submit' />";
		echo "</form>";
		echo "<SCRIPT LANGUAGE='JavaScript'>document.forms[0].submit();</SCRIPT>";
	}
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
			$TableName = "players";
			if (empty($_POST['username'])
			||	empty($_POST['password']))
			{
				echo "<p>You must enter a username and password.</p>";
			}
			else
			{
				$username = $_POST['username'];
				$password = $_POST['password'];
				$SQLstring = "SELECT COUNT(*) FROM $TableName WHERE username='$username'";
				$QueryResult = @mysql_query($SQLstring,$DBConnect);
				$Row = mysql_fetch_row($QueryResult);
				if ($Row[0] == "0")
				{
					// Creating a new user
					$SQLstring = "INSERT INTO players(username,password,dlevel,experience,xptolevelup,clevel,maxplayerhp,currhp) VALUES ('$username','$password',1,0,10,1,10,10)";
					$QueryResult = @mysql_query($SQLstring,$DBConnect);
					$nRows = mysql_affected_rows($DBConnect);
					if ($nRows == 1)
					{
						startGame($username);
					}
					else
					{
						echo "<p>Problem creating user</p>";
					}
				}
				else
				{
					// Check that password is correct
					$SQLstring = "SELECT password FROM players WHERE username='$username'";
					$QueryResult = @mysql_query($SQLstring,$DBConnect);
					$Row = mysql_fetch_row($QueryResult);
					if ($Row[0] == $password)
					{
						startGame($username);
					}
					else
					{
						echo "<p>Invalid password</p>";
					}
				}
				if (!($QueryResult === TRUE) && !($QueryResult === FALSE))
				{
					mysql_free_result($QueryResult);
				}
			}
		}
		mysql_close($DBConnect);
	}
?>
</body>
</html>