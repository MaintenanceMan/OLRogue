<html>
<head>
<style>
#maze	{font-family:Monospace;}
.b		{color: brown;}
.v		{color: green;}
.r		{color: red;}
.y		{color: yellow;}
.g		{color: gray;}
.o		{color: orange;}
.t		{color: tomato;}
.t		{color: violet;}
.w		{color: white;}
.c		{color: chocolate;}
.s		{color: slategray;}
body	{background-color: black; color: white; }
</style>
<script type="text/javascript">
	// Maze symbology
	var gChWall			= '#';
	var gChEmpty		= '.';
	var gChUser			= '@';
	var gChStairsUp		= '&lt;';
	var gChStairsDown	= '&gt;';
	var gChAmulet		= '&amp;';
	// The symbol of what was at the position of
	// the player before the player walked over it.
	var gChUnder		= gChEmpty;
	// Maze dimensions
	var gMaxWidth = 80;
	var gMaxHeight = 25;
	// 2D array repesenting maze contents
	var gArrMaze;
	// Array containing monsters on this level
	var gArrMonsters;
	// Messages displayed to user
	var gArrMessages;
	// Location in the maze where character is located
	var gUserX;
	var gUserY;
	// Specify how deep our dungeon is.
	var gMinDLevel = 1;
	var gMaxDLevel = 10;
	// Whether user has found and picked up amulet
	var gHasAmulet = false;
	// Whether user has escaped the dungeon with the amulet
	var gWinner = false;
	// Whether the user has been killed
	var gDead = false;
	// How much damage the player can do per turn
	var gMaxDamage = 10;
	// You need to roll a 1 on a d2 die to hit
	var gDiceForHit = 2;
	// The "cost" of an invalid monster move.
	// Used as a sentinal value when comparing
	// other valid moves to find best move.
	var gINVALID_MOVE = 100000;

    <?php
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
			    if (empty($_POST['username']))
			    {
				    echo "<p>Bad user parameter.</p>";
			    }
			    else
			    {
				    $username = $_POST['username'];
				    $SQLstring = "SELECT * FROM $TableName WHERE username='$username'";
				    $QueryResult = @mysql_query($SQLstring,$DBConnect);
				    $Row = mysql_fetch_assoc($QueryResult);
                    // The username of the character
                    echo "var gUserName = '$username';";
                    // ############################
                    // Init Javascript vars from DB
                    // ############################
	                // The level the user is at in the dungeon
	                echo "var gDLevel = {$Row['dlevel']};";
	                // Experience player has earned by killing monster
	                echo "var gExperience = {$Row['experience']};";
	                // How much XP is needed to reach next CLevel
	                echo "var gXPToLevelUp = {$Row['xptolevelup']};";
	                // The level the character has achieved via experience
	                echo "var gCLevel = {$Row['clevel']};";
	                // Maximum number of hitpoints the player can have
	                echo "var gMaxPlayerHP = {$Row['maxplayerhp']};";
	                // Current hit points of the player
	                echo "var gCurHP = {$Row['currhp']};";
				    if (!($QueryResult === TRUE) && !($QueryResult === FALSE))
				    {
					    mysql_free_result($QueryResult);
				    }
			    }
		    }
		    mysql_close($DBConnect);
	    }
    ?>

	// Fills maze with all wall characters
	function allWalls()
	{
		for (var y = 0; y < gMaxHeight; y++)
		{
			for (var x = 0; x < gMaxWidth; x++)
			{
				gArrMaze[y][x] = gChWall;
			}
		}
	}
	function isWall(y,x)
	{
		var bWall = false;
		if (gArrMaze[y][x] == gChWall)
		{
			bWall = true;
		}
		return bWall;
	}
	function isEmpty(y,x)
	{
		var bEmpty = false;
		if (gArrMaze[y][x] == gChEmpty)
		{
			bEmpty = true;
		}
		return bEmpty;
	}
	function isPlayer(y,x)
	{
		var bPlayer = false;
		if (gArrMaze[y][x] == gChUser)
		{
			bPlayer = true;
		}
		return bPlayer;
	}
	function isStairs(y,x)
	{
		var bStairs = false;
		if ((gArrMaze[y][x] == gChStairsUp)
		||	(gArrMaze[y][x] == gChStairsDown))
		{
			bStairs = true;
		}
		return bStairs;
	}
	function isAmulet(y,x)
	{
		var bAmulet = false;
		if (gArrMaze[y][x] == gChAmulet)
		{
			bAmulet = true;
		}
		return bAmulet;
	}
	function drawTriangle()
	{
		// Determine horizontal midpoint of the dungeon.
		var nMidX = Math.floor(gMaxWidth / 2);
		// How far to the left and right of horizontal middle
		// the open space will extend for this row.
		var nTriWidth = 1;
		// Loop through all rows except top and bottom.
		for (var y = 1; y < (gMaxHeight - 1); y++)
		{
			var nLeftX = nMidX - nTriWidth;
			if (nLeftX < 1)
			{
				nLeftX = 1;
			}
			var nRightX = nMidX + nTriWidth;
			if (nRightX > gMaxWidth - 2)
			{
				nRightX = gMaxWidth - 2;
			}
			for (var x = nLeftX; x < nRightX; x++)
			{
				gArrMaze[y][x] = gChEmpty;
			}
			// Expand horizontal width as we travel down.
			if ((y % 2) == 0)
			{
				nTriWidth += 1;
			}
			else
			{
				// Do a double expansion every other row.
				nTriWidth += 2;
			}
		}
	}
	function drawDiamond()
	{
		// Determine horizontal midpoint of the dungeon.
		var nMidX = Math.floor(gMaxWidth / 2);
		// How far to the left and right of horizontal middle
		// the open space will extend for this row.
		var nTriWidth = 1;
		// Whether the width is growing (value 1)
		// or shrinking (value -1) as we progress
		// down the shape veritically.
		var nPolarity = 1;
		// Loop through all rows except top and bottom.
		for (var y = 2; y < (gMaxHeight - 1); y++)
		{
			// Have reached the height where polarity switches?
			var bSwitchDirecitons = false;
			var nLeftX = nMidX - nTriWidth;
			if (nLeftX < 1)
			{
				nLeftX = 1;
				bSwitchDirecitons = true;
			}
			var nRightX = nMidX + nTriWidth;
			if (nRightX > gMaxWidth - 2)
			{
				nRightX = gMaxWidth - 2;
				bSwitchDirecitons = true;
			}
			for (var x = nLeftX; x < nRightX; x++)
			{
				gArrMaze[y][x] = gChEmpty;
			}
			// Expand horizontal width as we travel down.
			if (bSwitchDirecitons)
			{
				nPolarity *= -1;
			}
			nTriWidth += (4 * nPolarity);
		}
	}
	function drawCross()
	{
		// Determine horizontal midpoint of the dungeon.
		var nMidX = Math.floor(gMaxWidth / 2);
		// Quarter of the horizontal maze size
		var nQuarter = Math.floor(gMaxWidth / 4);
		// Loop through all rows except top and bottom.
		for (var y = 1; y < (gMaxHeight - 1); y++)
		{
			var nLeftX = nMidX - nQuarter;
			var nRightX = nMidX + nQuarter;
			for (var x = nLeftX; x < nRightX; x++)
			{
				gArrMaze[y][x] = gChEmpty;
			}
		}
		// Third of the veritical maze size
		var nThird = Math.floor(gMaxHeight / 3);
		for (var y = nThird; y < (nThird * 2); y++)
		{
			var nLeftX = 1;
			var nRightX = gMaxWidth - 1;
			for (var x = nLeftX; x < nRightX; x++)
			{
				gArrMaze[y][x] = gChEmpty;
			}
		}
	}
	function drawBigX()
	{
		// Third of the horizontal maze size
		var nThird = Math.floor(gMaxWidth / 3);
		// Loop through all rows except top and bottom.
		for (var y = 1; y < (gMaxHeight - 1); y++)
		{
			var ratio = gMaxWidth/gMaxHeight - 1;
			var nLeftX = Math.floor(y * ratio) + 1;
			var nRightX = nLeftX + nThird;
			for (var x = nLeftX; x < nRightX; x++)
			{
				gArrMaze[y][x] = gChEmpty;
			}
			var nRightX = gMaxWidth - Math.floor(y * ratio) - 1;
			var nLeftX = nRightX - nThird;
			for (var x = nLeftX; x < nRightX; x++)
			{
				gArrMaze[y][x] = gChEmpty;
			}
		}
	}
	function drawHexagon()
	{
		// Third of the horizontal maze size
		var nThirdX = Math.floor(gMaxWidth / 3);
		// Third of the vertical maze size
		var nThirdY = Math.floor(gMaxHeight / 3);
		// How far to the left and right of the horizontal
		// third of the screen the open space will extend
		// for this row.
		var nTriWidth = 1;
		// Loop through the top third of the shape
		for (var y = 1; y < nThirdY; y++)
		{
			// Have reached the height where polarity switches?
			var nLeftX = nThirdX - nTriWidth;
			if (nLeftX < 1)
			{
				nLeftX = 1;
			}
			var nRightX = (nThirdX * 2) + nTriWidth;
			if (nRightX > gMaxWidth - 2)
			{
				nRightX = gMaxWidth - 2;
			}
			for (var x = nLeftX; x < nRightX; x++)
			{
				gArrMaze[y][x] = gChEmpty;
			}
			// Expand horizontal width as we travel down.
			nTriWidth += 2;
		}
		// Loop through the niddle third of the shape
		for (var y = nThirdY; y < (nThirdY * 2); y++)
		{
			// Have reached the height where polarity switches?
			var nLeftX = nThirdX - nTriWidth;
			if (nLeftX < 1)
			{
				nLeftX = 1;
			}
			var nRightX = (nThirdX * 2) + nTriWidth;
			if (nRightX > gMaxWidth - 2)
			{
				nRightX = gMaxWidth - 2;
			}
			for (var x = nLeftX; x < nRightX; x++)
			{
				gArrMaze[y][x] = gChEmpty;
			}
		}
		// Loop through the bottom third of the shape
		for (var y = (nThirdY * 2); y < (nThirdY * 3); y++)
		{
			// Have reached the height where polarity switches?
			var nLeftX = nThirdX - nTriWidth;
			if (nLeftX < 1)
			{
				nLeftX = 1;
			}
			var nRightX = (nThirdX * 2) + nTriWidth;
			if (nRightX > gMaxWidth - 2)
			{
				nRightX = gMaxWidth - 2;
			}
			for (var x = nLeftX; x < nRightX; x++)
			{
				gArrMaze[y][x] = gChEmpty;
			}
			// Contract horizontal width as we travel down.
			nTriWidth -= 2;
		}
	}
	function drawEllipse()
	{
		// Compute aspect ratio
		var nRatio = gMaxWidth / gMaxHeight;
		// Compute aspect ratio square
		var nRatioSq = nRatio * nRatio;
		// Determine horizontal center point of the dungeon.
		var nCenterX = Math.floor(gMaxWidth / 2);
		// Determine vertical center point of the dungeon.
		var nCenterY = Math.floor(gMaxHeight / 2);
		// Determine max radius that fits on scren without
		// touching the edge of the made.
		var nRadius = nCenterY - 1;
		// Figured out the radius squared
		var nRSquared = nRadius * nRadius;
		// Loop through all rows of the maze
		for (var y = 1; y < (gMaxHeight - 1); y++)
		{
			// Loop through all columns of this row
			for (var x = 1; x < (gMaxWidth - 2); x++)
			{
				// Translate coordinates so that [y][x]
				// of [0][0] is at the center of the maze.
				var nTransY = y - nCenterY;
				var nTransX = x - nCenterX;
				// Compute squares of translated coordinates.
				var nYSquared = nTransY * nTransY;
				var nXSquared = nTransX * nTransX;
				// Factor in aspect ratio to make an ellipse
				var nXSqAspect = nXSquared / nRatioSq; 
				// See whether this spot falls within the circle.
				if (nYSquared + nXSqAspect < nRSquared)
				{
					gArrMaze[y][x] = gChEmpty;
				}
			}
		}
	}
	// Draw a mobius strip, which should look
	// like a figure eight turned on its side.
	function drawMobius()
	{
		// Compute aspect ratio, tapered so left 
		// and right ellipses just barely overlap.
		var nRatio = gMaxWidth / gMaxHeight / 2;
		// Determine horizontal left and right center points.
		var nLeftCenterX = Math.floor(gMaxWidth / 3);
		var nRightCenterX = nLeftCenterX * 2;
		// Determine vertical center point of the dungeon.
		var nCenterY = Math.floor(gMaxHeight / 2);
		// Determine max radius that fits on scren without
		// touching the top or bottom edges of the maze.
		var nRadius = nCenterY - 1;
		// Figured out the radius squared
		var nRSquared = nRadius * nRadius;
		// Loop through all rows of the maze
		for (var y = 1; y < (gMaxHeight - 1); y++)
		{
			// Loop through all columns of this row
			for (var x = 1; x < (gMaxWidth - 2); x++)
			{
				// Translate y-coordinate so 0 is vertical center of maze.
				var nLeftTransY = y - nCenterY;
				// Translate x-coodinate so 0 is left of horizontal center.
				var nLeftTransX = x - nLeftCenterX;
				// Compute sum or squares for left hand ellipse
				var nLeftSumSquares = nLeftTransY * nLeftTransY
									+ nLeftTransX * nLeftTransX / nRatio;				
				// Translate x-coodinate so 0 is right of horizontal center.
				var nRightTransX = x - nRightCenterX;
				// Compute sum or squares for left hand ellipse
				var nRightSumSquares = nLeftTransY * nLeftTransY
									+ nRightTransX * nRightTransX / nRatio;
				// See whether this spot falls within left or right circle.
				if ((nLeftSumSquares < nRSquared)
				|| (nRightSumSquares < nRSquared))
				{
					gArrMaze[y][x] = gChEmpty;
				}
			}
		}
	}
	function drawGrid()
	{
		for (var y = 1; y < (gMaxHeight - 1); y+=2)
		{
			for (var x = 1; x < (gMaxWidth - 1); x++)
			{
				gArrMaze[y][x] = gChEmpty;
			}
		}
		for (var x = 1; x < (gMaxWidth - 1); x+= 2)
		{
			for (var y = 1; y < (gMaxHeight - 1); y++)
			{
				gArrMaze[y][x] = gChEmpty;
			}
		}
	}
	function drawCheckers()
	{
		for (var y = 1; y < (gMaxHeight - 1); y+=2)
		{
			for (var x = 1; x < (gMaxWidth - 1); x+=2)
			{
				gArrMaze[y][x] = gChEmpty;
			}
		}
		for (var y = 2; y < (gMaxHeight - 1); y+=2)
		{
			for (var x = 2; x < (gMaxWidth - 1); x+=2)
			{
				gArrMaze[y][x] = gChEmpty;
			}
		}
	}
	function drawOutline()
	{
		for (var y = 1; y < (gMaxHeight - 1); y++)
		{
			gArrMaze[y][1] = gChEmpty;
			gArrMaze[y][gMaxWidth - 2] = gChEmpty;
		}
		for (var x = 1; x < (gMaxWidth - 1); x++)
		{
			gArrMaze[1][x] = gChEmpty;
			gArrMaze[gMaxHeight - 2][x] = gChEmpty;
		}
	}
	// Will carve out space in the dungeon you can walk around.
	// This will be in the form of a shape based on dLevel.
	function drawShape()
	{
		switch(gDLevel)
		{
			case 1:		drawTriangle();	break;
			case 2:		drawDiamond();	break;
			case 3:		drawCross();	break;
			case 4:		drawBigX();		break;
			case 5:		drawHexagon();	break;
			case 6:		drawEllipse();	break;
			case 7:		drawMobius();	break;
			case 8:		drawGrid();		break;
			case 9:		drawCheckers();	break;
			case 10:	drawOutline();	break;
		}
	}
	// This will choose an empty space in the maze 
	// for where the character will intiially apear.
	function placeUser()
	{
		var bPlaced = false;
		while (!bPlaced)
		{
			gUserX = Math.floor((Math.random()*(gMaxWidth - 2))+1);
			gUserY = Math.floor((Math.random()*(gMaxHeight - 2))+1);
			bPlaced = isEmpty(gUserY,gUserX);
		}
		// Remember what the player stepped on so we 
		// can redraw it when the player moves off spot.
		gChUnder = gArrMaze[gUserY][gUserX];
		gArrMaze[gUserY][gUserX] = gChUser;
	}	
	function placeStairs()
	{
		var nStairsX;
		var nStairsY;
		// Can only climb up out of first dungeon 
		// level if user posesses the amulet.
		if ((gHasAmulet)
		||	(gDLevel > gMinDLevel))
		{
			var bFoundSpot = false;
			while (!bFoundSpot)
			{
				nStairsX = Math.floor((Math.random()*(gMaxWidth - 2))+1);
				nStairsY = Math.floor((Math.random()*(gMaxHeight - 2))+1);
				bFoundSpot = isEmpty(nStairsY,nStairsX);
			}
			gArrMaze[nStairsY][nStairsX] = gChStairsUp;
		}
		if (gDLevel < gMaxDLevel)
		{
			var bFoundSpot = false;
			while (!bFoundSpot)
			{
				nStairsX = Math.floor((Math.random()*(gMaxWidth - 2))+1);
				nStairsY = Math.floor((Math.random()*(gMaxHeight - 2))+1);
				bFoundSpot = isEmpty(nStairsY,nStairsX);
			}
			gArrMaze[nStairsY][nStairsX] = gChStairsDown;
		}
	}
	// Take internal array, and displays content on screen
	function mazeToScreen()
	{
		var ctrl = document.getElementById("maze");
		var strHTML = "";
		for (var y = 0; y < gMaxHeight; y++)
		{
			for (var x = 0; x < gMaxWidth; x++)
			{
				// TO DO: Make the color work more elegant.
				// Put most common symbology first for speed.
				if (gArrMaze[y][x] == '.')
				{
					strHTML += "<span class='y'>.</span>";
				}
				else if (gArrMaze[y][x] == '#')
				{
					strHTML += "<span class='g'>#</span>";
				}
				else if (gArrMaze[y][x] == 'b')
				{
					strHTML += "<span class='t'>b</span>";
				}
				else if (gArrMaze[y][x] == 'o')
				{
					strHTML += "<span class='r'>o</span>";
				}
				else if (gArrMaze[y][x] == 'p')
				{
					strHTML += "<span class='v'>p</span>";
				}
				else if (gArrMaze[y][x] == 'q')
				{
					strHTML += "<span class='p'>q</span>";
				}
				else if (gArrMaze[y][x] == 'Y')
				{
					strHTML += "<span class='w'>Y</span>";
				}
				else if (gArrMaze[y][x] == 'd')
				{
					strHTML += "<span class='c'>d</span>";
				}
				else if (gArrMaze[y][x] == 'D')
				{
					strHTML += "<span class='s'>D</span>";
				}
				else if (gArrMaze[y][x] == '@')
				{
					strHTML += "<span class='v'>@</span>";
				}
				else if (gArrMaze[y][x] == '&lt;')
				{
					strHTML += "<span class='b'>&lt;</span>";
				}
				else if (gArrMaze[y][x] == '&gt;')
				{
					strHTML += "<span class='b'>&gt;</span>";
				}
				else if (gArrMaze[y][x] == '&amp;')
				{
					strHTML += "<span class='o'>&amp;</span>";
				}
				else
				{
					strHTML += gArrMaze[y][x];
				}
			}
			strHTML += "<br />";
		}
		ctrl.innerHTML = strHTML;
	}
	function showStats()
	{
        // Disply stats on screen for user
		var strTab = "&nbsp;&nbsp;&nbsp;&nbsp;";
		var ctrl = document.getElementById("stats");
		var strHTML = "<em>OL Rogue</em>"
					+ strTab + "dLevel:" + gDLevel
					+ strTab + "cLevel:" + gCLevel
					+ strTab + "XP:" + gExperience
					+ strTab + "HP:" + gCurHP;
		ctrl.innerHTML = strHTML;
        // Populate hidden fields for future Save to DB
        document.getElementById("phpusername").value    = gUserName;
        document.getElementById("phpdlevel").value      = gDLevel;
        document.getElementById("phpexperience").value  = gExperience;
        document.getElementById("phpxptolevelup").value = gXPToLevelUp;
        document.getElementById("phpclevel").value      = gCLevel;
        document.getElementById("phpmaxplayerhp").value = gMaxPlayerHP;
        document.getElementById("phpcurrhp").value      = gCurHP;
        // Update DB immediately on death.
        if (gCurHP <= 0)
        {
            document.forms[0].submit();
        }
	}
	function placeAmulet()
	{
		if (gDLevel == gMaxDLevel)
		{
			var bPlaced = false;
			var nAmuletX;
			var nAmuletY;
			while (!bPlaced)
			{
				nAmuletX = Math.floor((Math.random()*(gMaxWidth - 2))+1);
				nAmuletY = Math.floor((Math.random()*(gMaxHeight - 2))+1);
				bPlaced = isEmpty(nAmuletY,nAmuletX);
			}
			gArrMaze[nAmuletY][nAmuletX] = gChAmulet;
		}
	}
	// The monster object. Represents a monster
	// on the same dungeon level as the user.
	// Takes the highest level monster to generate.
	function Monster(nHighType)
	{
		var nMonsterType = rollDice(nHighType);
		switch(nMonsterType)
		{
			case 1:
				this.chSymbol = 'b';
				this.strName = "bat";
				break;
			case 2:
				this.chSymbol = 'o';
				this.strName = "orc";
				break;
			case 3:
				this.chSymbol = 'p';
				this.strName = "leprechaun";
				break;
			case 4:
				this.chSymbol = 'q';
				this.strName = "quagga";
				break;
			case 5:
				this.chSymbol = 'd';
				this.strName = "baby dragon";
				break;
			case 6:
				this.chSymbol = 'd';
				this.strName = "young dragon";
				break;
			case 7:
				this.chSymbol = 'd';
				this.strName = "mature dragon";
				break;
			case 8:
				this.chSymbol = 'D';
				this.strName = "old dragon";
				break;
			case 9:
				this.chSymbol = 'D';
				this.strName = "ancient dragon";
				break;
			case 9:
				this.chSymbol = 'D';
				this.strName = "dragon king";
				break;
			default:
				this.chSymbol = 'D';
				this.strName = "unknown dragon";
				break;
		}
		this.nMaxMonsterHP = 4 + (nMonsterType * 4);
		this.nMaxDamage = 2 + (nMonsterType * 2);
		this.nXPForKill = 5 + (nMonsterType * 5);
		this.nDiceForHit = 2;
		this.chUnder = gChEmpty;
		this.nHealth = this.nMaxMonsterHP;
		this.bDead = false;
		// Determine where monster is initially located
		var nLocX = -1;
		var nLocY = -1;
		var bPlaced = false;
		while (!bPlaced)
		{
			nLocX = Math.floor((Math.random()*(gMaxWidth - 2))+1);
			nLocY = Math.floor((Math.random()*(gMaxHeight - 2))+1);
			bPlaced = isEmpty(nLocY,nLocX);
		}
		// Store initial position
		this.nLocX = nLocX;
		this.nLocY = nLocY;
		// Reserve specified location othe map
		gArrMaze[this.nLocY][this.nLocX] = this.chSymbol;
	}
	// Clears the monster array of all monsters
	function removeMonsters()
	{
		while (gArrMonsters.length > 0)
		{
			gArrMonsters.pop();
		}
	}
	function addMonsters()
	{
		// Get rid of monsters from any previous levels
		removeMonsters();
		// On level n, add n monsters
		for (var i=0; i < gDLevel; i++)
		{
			gArrMonsters.push(new Monster(gDLevel));
		}
	}
	function initLevel()
	{
		allWalls();
		drawShape();
		placeStairs();
		placeAmulet();
		placeUser();
		addMonsters();
		showStats();
		mazeToScreen();
	}
	// Sets up a 2D array for the maze
	function initMazeArray()
	{
		gArrMaze = new Array();
		for (var i = 0; i < gMaxHeight; i++)
		{
			gArrMaze.push(new Array());
		}
	}
	function initMessages()
	{
		gArrMessages = new Array();
		// Start off with placeholders so the first call
		// to message() has something to display on all
		// three lines of scrolling output.
		gArrMessages.push("Legend");
		gArrMessages.push("Angband");
		showHelp();
	}
	// Called when web page loads to set the game up
	function initialize()
	{
		initMessages();
		initMazeArray();
		gArrMonsters = new Array();
		initLevel();
	}
	function heal()
	{
		// Regen 1 point of health per turn
		gCurHP++;
		// Cap the maximum health
		if (gCurHP > gMaxPlayerHP)
		{
			gCurHP = gMaxPlayerHP;
		}
	}
	function message(strMessage)
	{
		gArrMessages.push(strMessage);
		// Find the last three messages
		var nStartIndex = gArrMessages.length - 3;
		// Display the last three messages
		var ctrl1 = document.getElementById("msg1");
		ctrl1.innerHTML = gArrMessages[nStartIndex];
		var ctrl2 = document.getElementById("msg2");
		ctrl2.innerHTML = gArrMessages[nStartIndex + 1];
		var ctrl3 = document.getElementById("msg3");
		ctrl3.innerHTML = gArrMessages[nStartIndex + 2];
		// Keep at most the 25 last messages
		while (gArrMessages.length > 25)
		{
			gArrMessages.shift();
		}
	}
	function checkForDeath()
	{
		if (gCurHP <= 0)
		{
			gDead = true;
			message("You die");
		}
	}
	// Generate random number between 1 and nMax
	function rollDice(nMax)
	{
		var nRand = Math.floor((Math.random()*nMax)+1);
		return nRand;
	}
	function attackPlayer(nIndex)
	{
		// Determine whether the monster hit the player
		if (rollDice(gArrMonsters[nIndex].nDiceForHit) == 1)
		{
			gCurHP -= rollDice(gArrMonsters[nIndex].nMaxDamage);
			message("The " + gArrMonsters[nIndex].strName + " hits you");
			checkForDeath();
		}
		else
		{
			message("The " + gArrMonsters[nIndex].strName + " misses you");
		}
	}
	function healMonster(nIndex)
	{
		gArrMonsters[nIndex].nHealth++;
		if (gArrMonsters[nIndex].nHealth > gArrMonsters[nIndex].nMaxMonsterHP)
		{
			gArrMonsters[nIndex].nHealth = gArrMonsters[nIndex].nMaxMonsterHP;
		}
	}
	// Returns a value that represents how close two points are.
	// Larger values mean the points are further away.
	function getProximity(x1,y1,x2,y2)
	{
		var deltaX = x2 - x1;
		var deltaY = y2 - y1;
		// Square the diffs to eliminate negative bias.
		var nProximity = (deltaX * deltaX) + (deltaY * deltaY);
		return nProximity;
	}
	function isNextToPlayer(i)
	{
		var x = gArrMonsters[i].nLocX;
		var y = gArrMonsters[i].nLocY;
		var nProximity = getProximity(gUserX,gUserY,x,y);
		// Max of 1 space away in both x and y directions
		return nProximity <= 2;
	}
	// Compute a new x-location based on specified index
	// of a monster, and specified well known location
	// around the monster. Here are the well known location:
	//
	//		0	1	2
	//		3	x	4
	//		5	6	7
	//
	function getNewX(nIndex,nPosition)
	{
		var nOrigX = gArrMonsters[nIndex].nLocX;
		var nNewX;
		switch(nPosition)
		{
			case 0:
			case 3:
			case 5:
				nNewX = nOrigX - 1;
				break;
			case 1:
			case 6:
				nNewX = nOrigX;
				break;
			case 2:
			case 4:
			case 7:
				nNewX = nOrigX + 1;
		}
		return nNewX;
	}
	// Compute a new y-location based on specified index
	// of a monster, and specified well known location
	// around the monster. Here are the well known location:
	//
	//		0	1	2
	//		3	x	4
	//		5	6	7
	//
	function getNewY(nIndex,nPosition)
	{
		var nOrigY = gArrMonsters[nIndex].nLocY;
		var nNewY;
		switch(nPosition)
		{
			case 0:
			case 1:
			case 2:
				nNewY = nOrigY - 1;
				break;
			case 3:
			case 4:
				nNewY = nOrigY;
				break;
			case 5:
			case 6:
			case 7:
				nNewY = nOrigY + 1;
		}
		return nNewY;
	}
	// Determine how much closer each of the possible
	// moves for the monster will get to the player.
	function genCostArray(nIndex)
	{
		var arrCost = new Array();
		// Loop through 8 positions around the player
		for (var i=0; i < 8; i++)
		{
			// Look up the new position
			var x = getNewX(nIndex,i);
			var y = getNewY(nIndex,i);
			// Assume cannot move to the spot.
			var nCost = gINVALID_MOVE;
			if (canMoveOver(x,y))
			{
				nCost = getProximity(gUserX,gUserY,x,y);
			}
			arrCost.push(nCost);
		}
		return arrCost;
	}
	// Find the index of the least cost in the specified array.
	function getOptimalIndex(arrCost)
	{
		// Assume first one will be the least (best)
		var nBestIndex = 0;
		var nMinCost = arrCost[0];
		for (var i=1; i < arrCost.length; i++)
		{
			if (arrCost[i] < nMinCost)
			{
				nBestIndex = i;
				nMinCost = arrCost[i];
			}
		}
		return nBestIndex;
	}
	function moveMonster(nIndex)
	{
		// Figure out how far each move puts the monster from the player
		var arrCost = genCostArray(nIndex);
		// Find the index of the optimal move
		var nOptimal = getOptimalIndex(arrCost);
		// Determine if monster should move
		if (arrCost[nOptimal] != gINVALID_MOVE)
		{
			var nNewX = getNewX(nIndex,nOptimal);
			var nNewY = getNewY(nIndex,nOptimal);
			// Erase old location of monster
			var nLocY = gArrMonsters[nIndex].nLocY;
			var nLocX = gArrMonsters[nIndex].nLocX;
			gArrMaze[nLocY][nLocX] = gArrMonsters[nIndex].chUnder;
			// Move monster toward player
			gArrMonsters[nIndex].nLocX = nNewX;
			gArrMonsters[nIndex].nLocY = nNewY;
			// Remember what was under the monster
			gArrMonsters[nIndex].chUnder = gArrMaze[nNewY][nNewX];
			// Display monster at new spot
			gArrMaze[nNewY][nNewX] = gArrMonsters[nIndex].chSymbol;
		}
	}
	function moveMonsters()
	{
		for (var i=0; i < gArrMonsters.length; i++)
		{
			if (!gArrMonsters[i].bDead)
			{
				healMonster(i);
				if (isNextToPlayer(i))
				{
					attackPlayer(i);
				}
				else
				{
					moveMonster(i);
				}
			}
		}
	}
	function processTurn()
	{
		heal();
		moveMonsters();
		// Monsters may have damaged the player.
		// Show latest health statistics.
		showStats();
	}
	function isMonster(y,x)
	{
		var bMonster = false;
		for (var i=0; i < gArrMonsters.length; i++)
		{
			// Is the specified location inhabited by one of
			// the current dungeon level's monster symbology?
			if (gArrMaze[y][x] == gArrMonsters[i].chSymbol)
			{
				bMonster = true;
				break;
			}
		}
		return bMonster;
	}
	function checkForLevelUp()
	{
		if (gExperience >= gXPToLevelUp)
		{
			gCLevel++;
			message("Welcome to level " + gCLevel);
			gXPToLevelUp *= 2;
//			gMaxPlayerHP += 10;
			gMaxPlayerHP *= 2;
		}
	}	
	// TO DO: Refactor this function into subroutines!
	function attackMonster(y,x)
	{
		// Assume no monster at intended destination
		var nIndex = -1;
		// Find out which monster is at the specified location
		for (var i=0; i < gArrMonsters.length; i++)
		{
			if ((gArrMonsters[i].nLocX == x)
			&&	(gArrMonsters[i].nLocY == y))
			{
				// Found matching spot
				nIndex = i;
				break;
			}
		}
		if (nIndex != -1)
		{
			// Found the monster at the specified location.
			// Figure out whether player hit the monster.
			if (rollDice(gDiceForHit)==1)
			{
				gArrMonsters[nIndex].nHealth -= rollDice(gMaxDamage);
				message("You hit the " + gArrMonsters[nIndex].strName);
				// TO DO:	Put all this stuff in Monster class
				if (gArrMonsters[nIndex].nHealth <= 0)
				{
					message("You killed the " + gArrMonsters[nIndex].strName);
					gExperience += gArrMonsters[nIndex].nXPForKill;
					checkForLevelUp();
					gArrMonsters[nIndex].bDead = true;
					var nLocY = gArrMonsters[nIndex].nLocY;
					var nLocX = gArrMonsters[nIndex].nLocX;
					gArrMaze[nLocY][nLocX] = gArrMonsters[nIndex].chUnder;
					gArrMonsters[nIndex].nLocX = -1;
					gArrMonsters[nIndex].nLocY = -1;
				}
			}
			else
			{
				message("You missed the " + gArrMonsters[nIndex].strName);
			}
		}
	}
	// The specified location is one that the character or monster
	// is allowed to walk on top of.
	function canMoveOver(x,y)
	{
		var bCanMoveOver = false;
		if (isEmpty(y,x)
		|| isStairs(y,x)
		|| isAmulet(y,x))
		{
			bCanMoveOver = true;
		}
		return bCanMoveOver;
	}
	function movePlayer(x,y)
	{
		var bTurnTaken = false;
		if (canMoveOver(x,y))
		{
			// Restore what was previously under player
			gArrMaze[gUserY][gUserX] = gChUnder;
			// Choose new position of player
			gUserX = x;
			gUserY = y;
			if (isAmulet(y,x))
			{
				gHasAmulet = true;
				message("You have retrieved the amulet");
				message("Climb back up out of dLevel 1 for the win");
				// Eat the amulet. Player has picked it up.
				// When player moves off this spot, we will
				// no longer display the amulet.
				gChUnder = gChEmpty;
			}
			else
			{
				// Remember what the player stepped on so we 
				// can redraw it when the player moves off spot.
				gChUnder = gArrMaze[gUserY][gUserX];
			}
			gArrMaze[gUserY][gUserX] = gChUser;
			bTurnTaken = true;
		}
		else if (isMonster(y,x))
		{
			attackMonster(y,x);
			bTurnTaken = true;
		}
		if (bTurnTaken)
		{
			// Allow other time based events to transpire.
			processTurn();
			// Show updated location on the screen
			mazeToScreen();
		}
	}
	function showWinner()
	{
		message("You win");
		var ctrl = document.getElementById("maze");
		// Show big winner word, each letter a 
		// different color, in place of the maze.
		var strHTML = "<span STYLE='font-size: 72pt;'>"
					+ "<span class='v'>W</span>"
					+ "<span class='r'>i</span>"
					+ "<span class='y'>n</span>"
					+ "<span class='g'>n</span>"
					+ "<span class='o'>e</span>"
					+ "<span class='b'>r</span>"
					+ "</span>";
		ctrl.innerHTML = strHTML;
	}
	function climbStairs()
	{
		if (gChUnder == gChStairsUp)
		{
			if (gDLevel == gMinDLevel)
			{
				gWinner = true;
				showWinner();
			}
			else
			{
				gDLevel--;
				initLevel();
			}
		}
	}
	function descendStairs()
	{
		if (gChUnder == gChStairsDown)
		{
			gDLevel++;
			initLevel();
		}
	}
	function showHelp()
	{
		message("Welcome to OL Rogue. Find the amulet. Return to level 1, climb up to win.");
		message("e=up d=right x=down s=left r=northeast c=southeast z=southwest w=northwest");
		message("Climb up and down stairs with '<' and '>'. Good luck in the rooms of OL Rogue.");
	}
	function processKey(e)
	{
		if (gWinner || gDead)
		{
			// User cannot do anything else once the game has been won or lost.
			return true;
		}
		var keynum;
		var keychar;
		if(window.event) // IE
		{
			keynum = e.keyCode;
		}
		else if(e.which) // Netscape/Firefox/Opera
		{
			keynum = e.which;
		}
		keychar = String.fromCharCode(keynum);
		// Process commands from user.
		switch(keychar) {
			case "e":
			case "8":
				// North
				movePlayer(gUserX,gUserY-1);
				break;
			case "r":
			case "9":
				// North East
				movePlayer(gUserX+1,gUserY-1);
				break;
			case "d":
			case "6":
				// East
				movePlayer(gUserX+1,gUserY);
				break;
			case "c":
			case "3":
				// South East
				movePlayer(gUserX+1,gUserY+1);
				break;
			case "x":
			case "2":
				// South
				movePlayer(gUserX,gUserY+1);
				break;
			case "z":
			case "1":
				// South West
				movePlayer(gUserX-1,gUserY+1);
				break;
			case "s":
			case "4":
				// West
				movePlayer(gUserX-1,gUserY);
				break;
			case "w":
			case "7":
				// North West
				movePlayer(gUserX-1,gUserY-1);
				break;
			case "<":
				climbStairs();
				break;
			case ">":
				descendStairs();
				break;
			case "h":
			case "?":
				showHelp();
				break;
			}
		return true;
	}
</script>
</head>
<body onload="initialize();" onkeypress="return processKey(event);">
<div style='display:inline;' id="stats"></div>
<form style='display:inline; margin: 10; padding: 0' method='POST' action='Save.php'>
<input type="hidden" name="username" id="phpusername" />
<input type="hidden" name="dlevel" id="phpdlevel" />
<input type="hidden" name="experience" id="phpexperience" />
<input type="hidden" name="xptolevelup" id="phpxptolevelup" />
<input type="hidden" name="clevel" id="phpclevel" />
<input type="hidden" name="maxplayerhp" id="phpmaxplayerhp" />
<input type="hidden" name="currhp" id="phpcurrhp" />
<input style='display:inline;' type='submit' value='Save' />
</form>
<div id="maze"></div>
<div id="msg1"></div>
<div id="msg2"></div>
<div id="msg3"></div>
</body>
</html>