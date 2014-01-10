<?php
// PsychStudioXpress provides tools to behavioral and social science researchers.
// Copyright (C) 2011 William Kelly Hudgins
// This program is free software: you can redistribute it and/or modify it
// under the terms of the GNU General Public License as published by
// the Free Software Foundation, version 3.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
// or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
// more details.
//
// You should have received a copy of the GNU General Public License along with
// this program. If not, see <http://www.gnu.org/licenses>.
//
// If you have questions, please email wkhudgins@psychstudioxpress.net

// PsychStudioXpress
// vKVS, Version 1.0
// vkvs.php
//
// Provides a virtual kitchen video system

include 'conf.php';

session_start();

// Make a MySQL Connection
mysql_connect("$server", "$mysql_user", "$mysql_password") or die(mysql_error());
$database = mysql_select_db("$mysql_db");

// HTML Header
echo "
<html>
<head><title>".$project_title." ".$usage." ".$build_no."</title>
</head>";

// Clean Input Function
function clean($source) 
{
$whattostrp = array("'", ")", "(", ";","*","-",">","<");
$source = str_replace($whattostrp, "", "$source");
$source=stripslashes($source);
$source=strip_tags($source);
$source=mysql_real_escape_string($source);
return $source;
}

// Encryption Function
function crypto($source)
{
$salt[0] =  "aBdsajASD243Hasd";
$salt[1] = "aazcdkfs";
$crypt[0] = crc32($source);
$crypt[1] = crypt($source, $salt[0]);
$crypt[2] = md5($source);
$crypt = implode($salt[1], $crypt);
return sha1($source.$crypt);
} 

// If the vKVS system isn't configured this auto-installs it
if (!$database)
{
echo "
<body><center><form action=\"\" method=\"post\">
<table width=\"30%\">
<tr><th colspan=\"2\">Configure first user</th></tr>
<tr><td>Username:</td><td><input type=\"text\" name=\"username\" /></td></tr>
<tr><td>Name:</td><td><input type=\"text\" name=\"name\" /></td></tr>
<tr><td>Password:</td><td><input type=\"password\" name=\"password\" /></td></tr>
<tr><td>Retype Password:</td><td><input type=\"password\" name=\"password2\" /></td></tr>
<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Configure first user\"/></td></tr>
</table>
</form></center>";
if (!isset($_POST['username'])) { die(); }

	// If the user has submitted intital configuration data
	if(isset($_POST['username']) AND isset($_POST['password']))
	{
	$usern = clean($_POST['username']);
	$name = clean($_POST['name']);
	$passw= md5($_POST["password"]);
	$passw2= md5($_POST["password2"]);
		if ($passw != $passw2)
		{
		die('Passwords do not match, please try again');
		}

	// Encrypts password
		$x = 1;
		while ($x <= 3) 
		{
		$passw=md5($passw);
		$x = $x + 1;
		}
	$passw = crc32($passw);	
	$passw = sha1($passw);
	$passw = md5($passw);
	$passw = crypto($passw);
	$passw = md5($passw);

$set_sql = mysql_query("SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'")or die(mysql_error());
$create_db = mysql_query("CREATE DATABASE IF NOT EXISTS `$mysql_db` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci")or die(mysql_error());
mysql_select_db("$mysql_db")or die(mysql_error());

// This table keeps a listing of all items and what menu they belong to
$create_menu = mysql_query("CREATE TABLE IF NOT EXISTS `menu` (
  `UID` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique Identifier',
  `ID` int(11) NOT NULL COMMENT 'ID within a given menu, pulled by the RNG',
  `Name` varchar(255) NOT NULL COMMENT 'Name of the menu item, shown on the vKVS',
  `Weight` int(11) NOT NULL COMMENT 'Determines the \"sales volume\" of the item',
  `menu` varchar(255) NOT NULL COMMENT 'The menu this item is part of',
  PRIMARY KEY (`UID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1")or die(mysql_error());

// This table is merely an index of menus
$create_menusindex = mysql_query("CREATE TABLE IF NOT EXISTS `menus` (
  `name` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1")or die(mysql_error());

// This table keeps all data related to virtual orders
$create_orders = mysql_query("CREATE TABLE IF NOT EXISTS `orders` (
  `orderID` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `save_time` varchar(255) NOT NULL,
  `serve_time` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  `order` longtext NOT NULL,
  PRIMARY KEY (`orderID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1")or die(mysql_error());

// This table keeps all user info
$create_users = mysql_query("CREATE TABLE IF NOT EXISTS `users` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `superadmin` int(11) NOT NULL,
  `menu` varchar(255) NOT NULL,
  `users` int(11) NOT NULL,
  `error` int(11) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1")or die(mysql_error());


$insert_user = mysql_query("INSERT INTO `users` (`username`, `password`, `name`, `superadmin`) VALUES
('$usern', '$passw', '$name', '1')")or die(mysql_error());
echo "The vKVS database has been properly set up.  To complete set up login to the vKVS, go to the <a href=\"vkvs.php?action=admin\">admin CP</a>, add a menu, and then set it as your default menu";
die();
}
} // End auto-install

	// Compares and verifies login input data
	if(isset($_POST['username']) AND isset($_POST['password']))
	{
	$usern = clean($_POST['username']);
	$passw= md5($_POST["password"]);

	// Encrypts password
		$x = 1;
		while ($x <= 3) 
		{
		$passw=md5($passw);
		$x = $x + 1;
		}
	$passw = crc32($passw);	
	$passw = sha1($passw);
	$passw = md5($passw);
	$passw = crypto($passw);
	$passw = md5($passw);

	// Compares user credentials with the DB
	$result = mysql_query("SELECT * FROM users WHERE username='$usern' AND password='$passw'");
		// Logs in the user
		if ($r = mysql_fetch_array($result)) 
		{
		$_SESSION['ID'] = $r['ID'];
		$_SESSION['menu'] = $r['menu'];
		}
			else 
			{
			echo "Invalid credentials, please try again.";
			}
	} // Ends login processing
	
// If the user is logged in, the actual vKVS is ran
if (isset($_SESSION['ID'])) 
{

// Determines the preferred menu
$menu = $_SESSION['menu'];

// Fixes the Novel Problem
if ($_GET['reset'] == 1)
{
$fix_error = mysql_query("UPDATE users SET error = '0' WHERE ID ='$_SESSION[ID]'")or die(mysql_error());	
}
	
// Prevents vKVS from running when administrator tools are open
if ($_GET['action'] != 'admin' and $_GET['action'] != 'specialfunc')	
{

// Browser check
$msie = strpos($_SERVER["HTTP_USER_AGENT"], 'MSIE') ? true : false; $firefox = strpos($_SERVER["HTTP_USER_AGENT"], 'Firefox') ? true : false; $safari = strpos($_SERVER["HTTP_USER_AGENT"], 'Safari') ? true : false; $chrome = strpos($_SERVER["HTTP_USER_AGENT"], 'Chrome') ? true : false;

// Sets up the auto refresh of the page
$rand = mt_rand(30,150);
header("refresh: $rand, url=vkvs.php");

// This if statement ensures that a new order isn't generated everytime one is served or recalled
if (!isset($_GET['xml'])) 
{
// Grab the number of rows in the table
$query = mysql_query("SELECT * FROM menu WHERE menu='$menu'")or die(mysql_error());
$max = mysql_num_rows($query); 

$savd = 0;

// This loop contains all the code to create one order
while ($savd != 1)
{

// This loop contains the code to determine order size
$totald = 0;
while ($totald != 1)
{
$totald = 0;
$number_of_items = mt_rand(1,9);


// The following block of code ensures the variance among order sizes is realistic
if ($number_of_items == 1) 
{
$totald = 1;
}
	else if ($number_of_items == 2) 
	{
	$weight = mt_rand(1,2);
	
		if ($weight == 1)
		{
		$totald = 1;
		}
	}
		else if ($number_of_items == 3) 
		{
		$weight = mt_rand(1,3);
	
			if ($weight == 1)
			{
			$totald = 1;
			}
		}
			else if ($number_of_items == 4) 
			{
			$weight = mt_rand(1,5);
	
				if ($weight == 1)
				{
				$totald = 1;
				}
			}
				else if ($number_of_items == 5) 
				{
				$weight = mt_rand(1,15);

					if ($weight == 1)
					{
					$totald = 1;
					}
				}
					else if ($number_of_items == 6) 
					{
					$weight = mt_rand(1,18);

						if ($weight == 1)
						{
						$totald = 1;
						}
					}
						else if ($number_of_items == 7) 
						{
						$weight = mt_rand(1,20);

							if ($weight == 1)
							{
							$totald = 1;
							}
						}		
				else if ($number_of_items == 8) 
				{
				$weight = mt_rand(1,25);

					if ($weight == 1)
					{
					$totald = 1;
					}
				}
			else if ($number_of_items == 9) 
			{
			$weight = mt_rand(1,30);

				if ($weight == 1)
				{
				$totald = 1;
				}
			}
				
					// This sets the actual order size
					if ($totald == 1)
					{
					$number_of_items = $number_of_items;
					}
}  // Closes the $totald loop

// This loop runs until the each item in the order is generated
$x = 0;
while ($x != $number_of_items)
{
$placed_order = 0;

// This loop actually decides what a given item is
while ($placed_order != 1)
{
$placed_order = 0;

// Determines what is pulled from the DB
$rand = mt_rand(1,$max);

// This actually pulls a menu item, so in a sense it "places" an order
$place_order = mysql_query("SELECT * FROM menu WHERE ID=$rand AND menu='$menu'")or die(mysql_error());
$item = mysql_fetch_array($place_order);

// The following code ensures that some items are ordered more than others

// A staple item commonly ordered
if ($item['Weight'] == 1) 
{
$placed_order = 1;
}
	// A staple item ordered with slightly less frequency
	else if ($item['Weight'] == 2) 
	{
	// One half chance that if this item is pulled from the DB it will be ordered
	$weight = mt_rand(1,4);
	
		 if ($weight == 1)
		{
		$placed_order = 1;
		}
	}
		// A heavier, meal, staple item
		else if ($item['Weight'] == 3) 
		{
		// One eigth chance that if this item is pulled from the DB it will be ordered
		$weight = mt_rand(1,8);
	
			if ($weight == 1)
			{
			$placed_order = 1;
			}
		}
			// A rare item
			else if ($item['Weight'] == 4) 
			{
			// One 20th chance that if this item is pulled from the DB it will be ordered. Used to be 14th.
			$weight = mt_rand(1,20);
	
				if ($weight == 1)
				{
				$placed_order = 1;
				}
			}
				// A highly rare item
				else if ($item['Weight'] == 5) 
				{
				// One 30th chance that if this item is pull from the DB it will be ordered
				$weight = mt_rand(1,30);

				
					// This actually assigns if an item is ordered
					if ($weight == 1)
					{
					$placed_order = 1;
					}
				}
				

if ($placed_order == 1)
{
// Actual Menu items are here
	if (!isset($inserted))
	{
	$order = "$item[Name] <br />";	
	$now = time();
	$save_order = mysql_query("INSERT INTO orders (`userID`, `save_time`, `status`, `order`) VALUES ('$_SESSION[ID]', '$now', '1', '$order')")or die(mysql_error());
	}
		$inserted = 1;
		if ($inserted == 1)
		{
		$retrive_order = mysql_query("SELECT * FROM orders WHERE userID='$_SESSION[ID]' AND status='1'")or die(mysql_error());
		$old_order = mysql_fetch_array($retrive_order);
		$existing_order = $old_order['order'];
		$new_order = "$item[Name] <br />";
		$new_order = $existing_order.$new_order;
		$update_order = mysql_query("UPDATE orders SET `order`='$new_order' WHERE orderID='$old_order[orderID]'")or die(mysql_error());
		}
$x = $x + 1;
} // Closes the $placed_order if statement
} // Closes the $placed_order loop
} // Closes the $number_of_items loop
$savd = 1;
		$retrive_order = mysql_query("SELECT * FROM orders WHERE userID='$_SESSION[ID]' AND status='1'")or die(mysql_error());
		$old_order = mysql_fetch_array($retrive_order);
		$update_order = mysql_query("UPDATE orders SET `status`='2' WHERE orderID='$old_order[orderID]'")or die(mysql_error());
		//if ($chrome) {
echo "<iframe height=\"0\" width=\"0\" src=\"beep.mp3\"></iframe>"; //}

} // Closes the $savd loop	
} // Ends generating orders


	// Begin Serving Off Orders

if (isset($_GET['serve']) AND $_GET['serve'] == 'SERVE')
{

$orderID = clean($_GET['orderID']);
$now = time();
$serve_off = mysql_query("UPDATE orders SET `status`='3' WHERE orderID='$orderID'")or die(mysql_error());
$serve_off = mysql_query("UPDATE orders SET `serve_time`='$now' WHERE orderID='$orderID'")or die(mysql_error());

}

// Begin problem solving error
$check_for_error = mysql_query("SELECT error FROM users WHERE ID='$_SESSION[ID]'")or die(mysql_error());
$problem = mysql_fetch_array($check_for_error);
if ($problem['error'] == 1)
{
echo "<script type=\"text/javascript\">
function error()
{
alert(\"Vital System Error \\nError detected at 0x800004B3:0xD04D67.\\nSystem is curretly unstable, please reset.\");
}
</script>
<body onload=\"error()\">";
}
else { echo "<body>"; }

// Begin Primary GUI

$retrive_orders = mysql_query("SELECT * FROM orders WHERE userID=$_SESSION[ID] AND status=2 ORDER BY save_time ASC")or die(mysql_error());

echo "<table width=\"100%\" border=\"5\"><tr>";


// Used in determining when a TR is closed
$k = 0;
$odd = 1; 

while($orders = mysql_fetch_array($retrive_orders))
{
$k = $k + 1;

echo "<td width=\"50%\">
<table width=\"100%\">
<tr><td>Order No.&nbsp;".$orders['orderID']."</td>
<td align=\"right\">
<form method=\"GET\" action=\"\">
<input type=\"hidden\" name=\"orderID\" value=\"".$orders['orderID']."\" />
<input type=\"hidden\" name=\"xml\" value=\"".md5("nicole")."\" />
<input type=\"submit\" name=\"serve\" value=\"SERVE\" />
</form>
</td>
</tr>

<tr><td colspan=\"2\">".$orders['order']."</td></tr></table>

</td>";

// Used to determine when a TR is closed
if ($odd != $k%2) { echo "</tr><tr>"; }

}

// Used to acount for odd ending empty cells
if ($odd == $k%2){ echo "<td width=\"50%\"></td></tr>"; }

// 3600 seconds is one hour in unix.
$time = time() - 3600;

// Gets the times the orders were placed
$retrive_user = mysql_query("SELECT `save_time` FROM orders WHERE `userID`=$_SESSION[ID]")or die(mysql_error());
$retrive_hour = mysql_query("SELECT `save_time` FROM orders WHERE `userID`=$_SESSION[ID] AND `save_time` >= $time")or die(mysql_error());
$user = mysql_fetch_array($retrive_user);
$hour = mysql_fetch_array($retrive_hour);
// Gets the times the orders were served
$retrive_user2 = mysql_query("SELECT `serve_time` FROM orders WHERE `userID`=$_SESSION[ID]")or die(mysql_error());
$retrive_hour2 = mysql_query("SELECT `serve_time` FROM orders WHERE `userID`=$_SESSION[ID] AND `save_time` >= $time")or die(mysql_error());
$user2 = mysql_fetch_array($retrive_user2);
$hour2 = mysql_fetch_array($retrive_hour2);
// Sums save times
$sum_user = array_sum($user);
$sum_hour = array_sum($hour);
// Sums serve times
$sum_user2 = array_sum($user2);
$sum_hour2 = array_sum($hour2);
// Calculates the Difference
$sum_user = $sum_user2 - $sum_user;
$sum_hour = $sum_hour2 - $sum_hour;
// Determines the total number of applicable orders
$total_user = count($user);
$total_hour = count($hour);
// Calculates the averages
$hour_avg = $sum_hour/$total_hour;
$user_avg = $sum_user/$total_user;

echo "</table><hr />
<center>
<table><tr><td width=\"20%\"><a href=\"vkvs.php?action=admin\">Administration Tools</a></td>
<td width=\"20%\" align=\"right\"><a href=\"vkvs.php?action=specialfunc\">Special Functions</a></td>
<td width=\"20%\" align=\"center\">";

// Exit Recall Link
if (isset($_GET['action']) AND $_GET['action'] == 'recall')
{
echo "<a href=\"vkvs.php?xml=".md5("grinsell")."\">Exit Recall</a>";
}
	// Recall Link
	else 
	{
	echo "<a href=\"vkvs.php?action=recall&amp;xml=".md5("grinsell")."\">Recall</a>";
	}
echo "</td>
		<td width=\"20%\"><a href=\"vkvs.php?action=logout&amp;xml=".md5("cosine")."\">Logout</a></td>
		<td width=\"20%\">[".$hour_avg."/".$user_avg."]</tr></table></center>";
		
		
// Begin Recall
if (isset($_GET['action']) AND $_GET['action'] == 'recall')
{
// Only Recalls 4 orders
$recall_orders = mysql_query("SELECT * FROM orders WHERE userID=$_SESSION[ID] AND status=3 ORDER BY serve_time DESC LIMIT 0, 4")or die(mysql_error());

echo "<hr />
<marquee scrollamount=\"15\">RECALL&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;RECALL&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;RECALL&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;RECALL</marquee>
<table width=\"100%\" border=\"5\"><tr>";


$k = 0;
while($orders = mysql_fetch_array($recall_orders))
{
$k = $k + 1;

echo "<td width=\"50%\">
<table width=\"100%\"><tr><td>Order No.&nbsp;".$orders['orderID']."</td><td align=\"right\">

</td></tr>
<tr><td colspan=\"2\">".$orders['order']."</td></tr></table>
</td>";

// Used to determine when a TR is closed
if ($odd != $k%2) { echo "</tr><tr>"; }

}


// Used to acount for odd ending empty cells
if ($odd == $k%2){ echo "<td width=\"50%\"></td></tr>"; }
} // Ends Recall
} // Ends Admin Check
} // Ends if logged in


// Begin Admin Tools

if (isset($_GET['action']) AND $_GET['action'] == 'admin')
{
	// Compares and verifies admin login data
	if(isset($_POST['username']) AND isset($_POST['password']))
	{
	$usern = clean($_POST['username']);
	$passw= md5($_POST["password"]);

	// Encrypts password
		$x = 1;
		while ($x <= 3) 
		{
		$passw=md5($passw);
		$x = $x + 1;
		}
	$passw = crc32($passw);	
	$passw = sha1($passw);
	$passw = md5($passw);
	$passw = crypto($passw);
	$passw = md5($passw);

	// Compares user credentials with the DB
	$result = mysql_query("SELECT * FROM users WHERE username='$usern' AND password='$passw'");
		// Logs in the user
		if ($r = mysql_fetch_array($result)) 
		{
		$_SESSION['admin'] = 1;
		$_SESSION['sadmin'] = $r['superadmin'];
		}
			else 
			{
			echo "Invalid credentials, please try again.";
			}
	} // Ends veritifcation of admin data
// If user is logged in
if (isset($_SESSION['admin']) and $_SESSION['admin'] == 1)
{
// 3600 seconds is one hour in unix.
$time = time() - 3600;

// Gets the times the orders were placed
$retrive_user = mysql_query("SELECT `save_time` FROM orders WHERE `userID`=$_SESSION[ID]")or die(mysql_error());
$retrive_hour = mysql_query("SELECT `save_time` FROM orders WHERE `userID`=$_SESSION[ID] AND `save_time` >= $time")or die(mysql_error());
$user = mysql_fetch_array($retrive_user);
$hour = mysql_fetch_array($retrive_hour);
// Gets the times the orders were served
$retrive_user2 = mysql_query("SELECT `serve_time` FROM orders WHERE `userID`=$_SESSION[ID]")or die(mysql_error());
$retrive_hour2 = mysql_query("SELECT `serve_time` FROM orders WHERE `userID`=$_SESSION[ID] AND `save_time` >= $time")or die(mysql_error());
$user2 = mysql_fetch_array($retrive_user2);
$hour2 = mysql_fetch_array($retrive_hour2);
// Sums save times
$sum_user = array_sum($user);
$sum_hour = array_sum($hour);
// Sums serve times
$sum_user2 = array_sum($user2);
$sum_hour2 = array_sum($hour2);
// Calculates the Difference
$sum_user = $sum_user2 - $sum_user;
$sum_hour = $sum_hour2 - $sum_hour;
// Determines the total number of applicable orders
$total_user = count($user);
$total_hour = count($hour);
// Calculates the averages
$hour_avg = $sum_hour/$total_hour;
$user_avg = $sum_user/$total_user;

// Dispalys the GUI
echo "<center><h3>".$project_title." ".$usage." ".$build_no." Administration Tools</h3></center>
<center><h4>Times</h4></center>
		Average Serve Times for the hour: ".$hour_avg." seconds<br />
		Average Serve for user: ".$user_avg." seconds<br />
		<small>Note: Serve times for the user are actually serve times for the given administrator, to get
 values to represent a single participant all serve values for a given administrator must be deleted after
each trial.
		<hr />";
		
		// If the user is a super admin
		if ($_SESSION['sadmin'] == 1)
		{
		echo "<center><h4>Add a user</h4></center>
		<form action=\"\" method=\"post\">
		<table width=\"30%\">
		<tr><th colspan=\"2\">Add user</th></tr>
		<tr><td>Username:</td><td><input type=\"text\" name=\"username2\" /></td></tr>
		<tr><td>Password:</td><td><input type=\"password\" name=\"password\" /></td></tr>
		<tr><td>Name:</td><td><input type=\"text\" name=\"name\" /></td></tr>
		<tr><td>Super Admin:</td><td><select name=\"sadmin\">
										<option value=\"0\">No</option>
										<option value=\"1\">Yes</option>
									</select>
		</td></tr>
		<tr><td>Default Menu:</td><td><input type=\"text\" name=\"dmenu\" /></td></tr>
		<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Add user\"/></td></tr>
		</table>
		</form>
		<hr />
		<center><h4>Modify Menu</h4>
		<table>
			<tr>";
			if ($_GET['menu'] != 'add') { echo "<th><a href=\"vkvs.php?action=admin&menu=add\">Add Menu</a></th>"; }
			if ($_GET['menu'] != 'change') { echo "<th><a href=\"vkvs.php?action=admin&menu=change\">Change Current Menu</a></th>"; }
			if ($_GET['menu'] != 'delete') { echo "<th><a href=\"vkvs.php?action=admin&menu=delete\">Delete Menu</a></th>"; }
			if ($_GET['menu'] != 'modify') { echo "<th><a href=\"vkvs.php?action=admin&menu=modify\">Modify Menu</a></th>"; }
			echo "</tr>
		</table>";
		
			// Menu Controls
			
				// Change Current Menu
				if (isset($_GET['menu']))
				{
				if ($_GET['menu'] == 'change')
				{
					// Actually changes the menu setting
					if (isset($_POST['nmenu']))
					{
					$nmenu = clean($_POST['nmenu']);
					$update = mysql_query("UPDATE users SET menu='$nmenu' WHERE ID='$_SESSION[ID]'")or die(mysql_error());
					$_SESSION['menu'] = $nmenu;
					echo "<center><br />Menu changed completed.</center><br />";
					}
					
				echo "<center>
				<form method='post' action=''>
				<table><tr><th>Current Menu: <u>".$_SESSION['menu']."</u></th></tr>
				<tr><th align=\"center\">To the current menu, please select from the choices below:</th></tr>
				<tr><td align=\"center\"><select name=\"nmenu\">";
				
				$get_menus = mysql_query("SELECT * FROM menus")or die(mysql_erorr());
				while ($menus = mysql_fetch_array($get_menus))
				{
				echo "<option value=\"".$menus['name']."\">".$menus['name']."</option>";
				}
				echo "
						</select></td></tr><tr><td></td></tr>
						<tr><td align=\"center\"><input type=\"submit\" value=\"Change Current Menu\" /></td></tr></table></form>
						</center>";
				} // End Change current menu
				
				// Delete Menu
				if ($_GET['menu'] == 'delete')
				{
					// Actually deletes a menu setting
					if (isset($_POST['dmenu']))
					{
					$dmenu = clean($_POST['dmenu']);
					// Removes the menu from the list of avaliable options
					$delete = mysql_query("DELETE FROM menus WHERE name='$dmenu'")or die(mysql_error());
					$delete = mysql_query("DELETE FROM menu WHERE menu='$dmenu'")or die(mysql_error());
					// Chanegs all users who used this menu to default
					$change_to_default = mysql_query("UPDATE users SET menu='$default_menu' WHERE menu='$dmenu'")or die(mysql_error());
					$_SESSION['menu'] = $default_menu; // Ensures the current menu isn't the one just deleted
					echo "<center><br />".$dmenu." menu sucessfully deleted.</center><br />";
					}
					
				echo "<center>
				<form method='post' action=''>
				<table><tr><th>Current Menu: <u>".$menu."</u></th></tr>
				<tr><th align=\"center\">To the delete a menu, please select from the choices below:</th></tr>
				<tr><td align=\"center\"><select name=\"dmenu\">";
				
				$get_menus = mysql_query("SELECT * FROM menus")or die(mysql_erorr());
				while ($menus = mysql_fetch_array($get_menus))
				{
				echo "<option value=\"".$menus['name']."\">".$menus['name']."</option>";
				}
				echo "
						</select></td></tr><tr><td></td></tr>
						<tr><td align=\"center\"><input type=\"submit\" value=\"Delete Selected Menu\" /></td></tr></table></form>";
				} // End delete menu
								
				// Modification of Menus				
				if ($_GET['menu'] == 'modify')
				{
					// If the user hasn't submitted modification data
					if (!isset($_POST['submit_check']) AND $_GET['menu'] == 'modify')
					{
					echo "<center>
					<table><tr><th>Current active menu: <u>".$menu."</u></th></tr></table>";
					
						// GUI for selecting a menu to modify
						if (!isset($_GET['modmenu']))
						{
						echo "<form method='get' action=''><tr><th align=\"center\">To the modify a menu, please select from the choices below:</th></tr>
						<tr><td align=\"center\"><select name=\"modmenu\">";
				
						$get_menus = mysql_query("SELECT * FROM menus")or die(mysql_erorr());
						while ($menus = mysql_fetch_array($get_menus))
						{
						echo "<option value=\"".$menus['name']."\">".$menus['name']."</option>";
						}
						echo "
							</select></td></tr><tr><td></td></tr>
							<input type=\"hidden\" name=\"action\" value=\"admin\" />
							<input type=\"hidden\" name=\"menu\" value=\"modify\" />
							<tr><td align=\"center\"><input type=\"submit\" value=\"Modify Selected Menu\" /></td></tr></form></table>";
						} // End GUI for selecting a menu to modify
				// GUI for modifying a menu, once a menu has been selected
				if (isset($_GET['modmenu']))
				{
				$modmenu = clean($_GET['modmenu']);
				$get_items = mysql_query("SELECT * FROM menu WHERE menu='$modmenu'")or die(mysql_error());
				echo "<form method='post' action=''><table><tr><th colspan=\"3\" align=\"center\">Modify ".$modmenu." menu</th></tr>
				<tr><th>Item Name</th><th>Sales Volume</th><th>Menu</th></tr>";
				$k = 0;
				while ($item = mysql_fetch_array($get_items))
				{
				$k = $k + 1;
				echo "<tr><td><input type=\"text\" name=\"Name".$k."\" value=\"".$item['Name']."\" /></td>
					<td><select name=\"Weight".$k."\">";
					
					if ($item['Weight'] == '1')
					{
					echo "<option value=\"1\">Staple item commonly ordered</option>
					<option value=\"2\">Staple item ordered with slightly less frequency</option>
					<option value=\"3\">A heavier meal</option>
					<option value=\"4\">A rare-ish item</option>
					<option value=\"5\">A highly rare item</option>";
					}
						else if ($item['Weight'] == '2')
						{
						echo "<option value=\"2\">Staple item ordered with slightly less frequency</option>
						option value=\"1\">Staple item commonly ordered</option>
						<option value=\"3\">A heavier meal</option>
						<option value=\"4\">A rare-ish item</option>
						<option value=\"5\">A highly rare item</option>";
						}
							else if ($item['Weight'] == '3')
							{
							echo "<option value=\"3\">A heavier meal</option>
							<option value=\"1\">Staple item commonly ordered</option>
							<option value=\"2\">Staple item ordered with slightly less frequency</option>
							<option value=\"4\">A rare-ish item</option>
							<option value=\"5\">A highly rare item</option>";
							}
								else if ($item['Weight'] == '4')
								{
								echo "<option value=\"4\">A rare-ish item</option>
								<option value=\"1\">Staple item commonly ordered</option>
								<option value=\"2\">Staple item ordered with slightly less frequency</option>
								<option value=\"3\">A heavier meal</option>
								<option value=\"5\">A highly rare item</option>";
								}		
									else if ($item['Weight'] == '5')
									{
									echo "<option value=\"5\">A highly rare item</option>
									<option value=\"1\">Staple item commonly ordered</option>
									<option value=\"2\">Staple item ordered with slightly less frequency</option>
									<option value=\"3\">A heavier meal</option>
									<option value=\"4\">A rare-ish item</option>";
									}											
					echo "</select></td><td>
					<select name=\"menu".$k."\">
					<option value=\"".$item['menu']."\">".$item['menu']."</option>";
					$get_menus = mysql_query("SELECT * FROM menus WHERE name != '$item[menu]'")or die(mysql_error());
					while ($menus = mysql_fetch_array($get_menus))
					{
					echo "<option value=\"".$menus['name']."\">".$menus['name']."</option>";
					}
					echo "</select></td></tr>
										<input type=\"hidden\" name=\"uid".$k."\" value=\"".$item['UID']."\" />
										<input type=\"hidden\" name=\"n\" value=\"".$k."\" />
";				
					} // End While Loop

					echo "<tr><td colspan=\"2\" align=\"center\">
					<input type=\"hidden\" name=\"submit_check\" value=\"1\" />
					<input type=\"submit\" value=\"Update Menu\" /></td></tr>
					</table></form>";

				} // End the GUI for modying a menu
					} // End if the uesr hasn't submitted data
					
			// If the User has submited modification data
			if (isset($_POST['submit_check']))
			{
				$k = 0;
				while ($k < $_POST['n'])
				{
				$k = $k + 1;
				$name = clean($_POST[Name.$k]);
				$weight = clean($_POST[Weight.$k]);
				$menu = clean($_POST[menu.$k]);
				$uid = clean($_POST[uid.$k]);
				$modify_menu = mysql_query("UPDATE menu SET `Name`='$name', `Weight`='$weight', `menu`='$menu' 
				WHERE UID='$uid'")or die(mysql_error());
				}
				echo "<u>".$menu."</u> menu sucessfully modified.";
			} // Ends if the user has submited data
		} // Ends menu modification
				// Adding new menu				
				if ($_GET['menu'] == 'add')
				{
				echo "<center>
				<table><tr><th>Current active menu: <u>".$menu."</u></th></tr></table>";

						// First screen setting up a new menu
						if (!isset($_GET['newmenu']))
						{
						echo "
						<form method='get' action=''>Number of menu items:
						<input type=\"hidden\" name=\"action\" value=\"admin\" />
						<input type=\"hidden\" name=\"menu\" value=\"add\" />
						<input type=\"text\" name=\"n\" size=\"3\" />&nbsp;&nbsp;
						<input type=\"text\" name=\"newmenu\" value=\"Enter Menu Name\" />
						<input type=\"submit\" name=\"run\" value=\"Set Study\" />
						</form>
						<!-- Add explaination of how to set up a study with multiple IVs later -->";
						}
						
				//If a new menu has been configured for set up
				if (isset($_GET['newmenu']))
				{
				// If the user hasn't submitted data
				if (!isset($_POST['submit_check']) AND isset($_GET['n']))
				{
				echo "<table><form method='post' action=''>
				<tr><th colspan=\"2\">Add a Study</th><tr>
				<tr><th>Item Name</th><th>Sales Volume</th></tr>";
				$k = 0;
				$query = '';
					while ($k < $_GET['n'])
					{
					$k = $k + 1;
					echo "<tr><td><input type=\"text\" name=\"Name".$k."\" /></td>
						<td><select name=\"Weight".$k."\">
						<option value=\"1\">Staple item commonly ordered</option>
						<option value=\"2\">Staple item ordered with slightly less frequency</option>
						<option value=\"3\">A heavier meal</option>
						<option value=\"4\">A rare-ish item</option>
						<option value=\"5\">A highly rare item</option>
						</select></td></tr>";
					} // End while loop
				echo "<input type=\"hidden\" name=\"submit_check\" value=\"1\" />
					  <input type=\"hidden\" name=\"n\" value=\"".$k."\" />
				<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Create Menu\" /></td></tr>";
				} // End if the user hasn't submitted data
							
			// If the User has submited data
			if (isset($_POST['submit_check']))
			{
				$k = 0;
				while ($k < $_POST['n'])
				{
				$k = $k + 1;
				$name = clean($_POST[Name.$k]);
				$weight = clean($_POST[Weight.$k]);
				$menu = clean($_GET['newmenu']);
								
					// Makes a Query to add values into the DB
					if ($k < $_POST['n'])
					{ $query = $query."('".$k."', '$name', '$weight', '$menu'), "; }
					else { $query = $query."('".$k."', '$name', '$weight', '$menu')"; }
				}
			// Actually inserts the new menu into the DB
			$insert_items = mysql_query("INSERT INTO menu (`ID`, `Name`, `Weight`, `menu`) VALUES $query")or die(mysql_error());
			$insert_menu = mysql_query("INSERT INTO menus (`name`) VALUES ('$menu')")or die(mysql_error());
			echo "<u>".$menu."</u> sucessfully created.";
			} // Ends if the user has submited data
			echo "</table></form>";
		} // Ends if a new menu has been configured for set up
		} // Ends menu addition
				} // End menu controls
				
			// Add user
			if(isset($_POST['username2']) AND isset($_POST['password']))
			{
			$usern = clean($_POST['username2']);
			$passw= md5($_POST["password"]);
			$name = clean($_POST['name']);
			$sadmin = clean($_POST['sadmin']);
			$dmenu = clean($_POST['dmenu']);

			// Encrypts password
			$x = 1;
				while ($x <= 3) 
				{
				$passw=md5($passw);
				$x = $x + 1;
				}
			$passw = crc32($passw);	
			$passw = sha1($passw);
			$passw = md5($passw);
			$passw = crypto($passw);
			$passw = md5($passw);
			
			$add_user = mysql_query("INSERT INTO `vkvs`.`users` (`username`, `password`, `name`, `superadmin`, `menu`) VALUES ('$usern', '$passw', '$name', '$sadmin', '$dmenu')")or 
			die(mysql_error());
			echo "User account ".$usern." has been created.";
			} // Ends if a user has been added
		} // End Super Admin Section
		if ($_GET['flush'] == 1)
		{
		$flush_orders = mysql_query("DELETE FROM orders WHERE userID = '$_SESSION[ID]'")or die(mysql_error());
		$reset_proble  = mysql_query("UPDATE users SET error = '0' WHERE ID='$_SESSION[ID]'")or die(mysql_error());
		echo "<hr />vKVS flushed!";
		}
		echo "</center><hr /><a href=\"vkvs.php?action=exitadmin\">Return to Primary KVS</a>&nbsp;&nbsp;<a href=\"vkvs.php?action=admin&flush=1\">
		Flush vKVS</a>&nbsp;&nbsp;<a href=\"vkvs.php?action=admin\">Admin Home</a>
		<a href=\"vkvs.php?action=logout&amp;xml=".md5("cosine")."\">Logout</a>";
} // Ends if user is verified

// GUI for a login section to determine that an administrator, not a participant, is requesting admin 
if ($_SESSION['admin'] != 1) // This makes sure the login only shows up when a user isn't logged in
{
echo "<center>
<form action=\"\" method=\"post\">
<table width=\"30%\">
<tr><th colspan=\"2\">Adminstrator Authorization Required</th></tr>
<tr><td>Username:</td><td><input type=\"text\" name=\"username\" /></td></tr>
<tr><td>Password:</td><td><input type=\"password\" name=\"password\" /></td></tr>
<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Login\"/></td></tr>
</table>
</form></center>";
} // Ends GUI for admin login

} // Ends Admin

// Special Functios GUI

if (isset($_GET['action']) AND $_GET['action'] == 'specialfunc')
{
echo "<body>
<script type=\"text/javascript\">
function system_message()
{
// Work around
document.write('<center><h3>Speed of Service vKVS 1.0 Beta Special Functions</h3></center>');
document.write('<form method=\'get\' action=\'\'>');
document.write('<input type=\"hidden\" name=\"action\" value=\"specialfunc\" />');
document.write('<table width=\"100%\"><tr>');
document.write('<td><input type=\"submit\" name=\"function\"  value=\"Set Buffer\" /></td>');
document.write('<td><input type=\"submit\" name=\"function\"  value=\"Reset vKVS System\" onclick=\"system_message()\" /></td>');
document.write('<td><input type=\"submit\" name=\"function\"  value=\"Print Pick List\" /></td>');
document.write('<td><input type=\"submit\" name=\"function\" value=\"Factor Waste\" /></td>');

document.write('</tr>');
document.write('</table>');
document.write('</form>');
document.write('<hr />');
document.write('Resetting vKVS...<br />');
setTimeout(\"document.write('Flushing caches...<br />')\",6000);
setTimeout(\"document.write('Closing system...<br />')\",16000);
setTimeout(\"document.write('Opening system...<br />')\",31000);
setTimeout(\"document.write('Connecting to <code>vkvs@\$db_serv</code>...<br />')\",40000);
setTimeout(\"document.write('Assembling scripts...<br />')\",47000);
setTimeout(\"document.write('Opening up system, locking connections...<br />')\",55000);
setTimeout(\"document.write('The vKVS has been reset<br />')\",65000);
setTimeout(\"location.href = 'vkvs.php?xml=97babc91b9b96e0bb8780bedc2ddedbd&reset=1';\",72000);
}
</script>

<center><h3>".$project_title." ".$usage." ".$build_no." Special Functions</h3></center>
<form method='get' action=''>
<input type=\"hidden\" name=\"action\" value=\"specialfunc\" />
<table width=\"100%\"><tr>
<td><input type=\"submit\" name=\"function\"  value=\"Set Buffer\" /></td>
<td><input type=\"submit\" name=\"function\"  value=\"Reset vKVS System\" onclick=\"system_message()\" /></td>
<td><input type=\"submit\" name=\"function\"  value=\"Print Picklist\" /></td>
<td><input type=\"submit\" name=\"function\" value=\"Factor Waste\" /></td>
</tr>
</table>
</form>
<hr />";
if ($_GET['function'] == 'Set Buffer') 
{
echo "The Buffer Mode has been disabled by your manager.<hr />";
}
		if ($_GET['function'] == 'Print Picklist')
		{ 
		echo "Error: Please connect a valid printer.<hr />"; 
		}
			if ($_GET['function'] == 'Factor Waste')
			{
			echo "Waste is currently being factored.<hr />"; 
			}

echo "<center><a href=\"vkvs.php?xml=".md5("cosine")."\">Return</a>";
} // End Special Functions


// Login  GUI
if (!isset($_SESSION['ID']))
{
echo "<center>
<form action=\"\" method=\"post\">
<table width=\"30%\">
<tr><th colspan=\"2\">Activate vKVS</th></tr>
<tr><td>Username:</td><td><input type=\"text\" name=\"username\" /></td></tr>
<tr><td>Password:</td><td><input type=\"password\" name=\"password\" /></td></tr>
<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Activate System\"/></td></tr>
</table>
</form></center>";
} // Ends login GUI

// Admin Logout
if (isset($_GET['action']) AND $_GET['action'] == 'exitadmin')
{
$_SESSION['admin'] = '';
header('location: vkvs.php?xml=97babc91b9b96e0bb8780bedc2ddedbd');
}

// Begin Logout

if (isset($_GET['action']) AND $_GET['action'] == 'logout')
{
session_destroy();
header('location: vkvs.php');
}
// HTML Footer
echo "</body></html>";
?>
