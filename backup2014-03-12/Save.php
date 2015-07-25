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
	$username		= $_POST['username'];
	$dlevel			= $_POST['dlevel'];
	$experience		= $_POST['experience'];
	$xptolevelup	= $_POST['xptolevelup'];
	$clevel			= $_POST['clevel'];
	$maxplayerhp	= $_POST['maxplayerhp'];
	$currhp			= $_POST['currhp'];
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
			if ($currhp <=0)
			{
				// Player has died. Get rid of the record.
				$SQLstring = "DELETE FROM $TableName WHERE username='$username'";
				$QueryResult = @mysql_query($SQLstring,$DBConnect);
				if ($QueryResult === FALSE)
				{
					echo "<p>Problem accessing database.</p>"
					. "<p>Error code " . mysql_errno($DBConnect)
					. ": " . mysql_error($DBConnect) . "</p>";
				}
				else
				{
					echo "<p>You die.</p>";
				}
			}
			else
			{
				$SQLstring = "UPDATE $TableName"
							. " SET dlevel=$dlevel,"
							. " experience=$experience,"
							. " xptolevelup=$xptolevelup,"
							. " clevel=$clevel,"
							. " maxplayerhp=$maxplayerhp,"
							. " currhp=$currhp"
							. " WHERE username='$username'";
				$QueryResult = @mysql_query($SQLstring,$DBConnect);
				if ($QueryResult === FALSE)
				{
					echo "<p>Problem saving game.</p>"
					. "<p>Error code " . mysql_errno($DBConnect)
					. ": " . mysql_error($DBConnect) . "</p>";
				}
				else
				{
					echo "<p>Game saved.</p>";
				}
			}
			if (!($QueryResult === TRUE) && !($QueryResult === FALSE))
			{
				mysql_free_result($QueryResult);
			}
		}
		mysql_close($DBConnect);
		echo "<p><a href='Login.php'>Play</a> again?</p>";
	}
?>
</body>
</html>