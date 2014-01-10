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
// make_problem.php
//
// Provides ability to create and fix a fake error in vKVS.
// Must be configured manually.

include 'conf.php';

session_start();
// Make a MySQL Connection
mysql_connect("$server", "$mysql_user", "$mysql_password") or die(mysql_error());
$database = mysql_select_db("$mysql_db");

/* Rows to break should be of this format:
	<a href=make_problem.php?id=USER_ID>Username</a>
   ROws to fix should be of this format:
	<a href=make_problem.php?id=USER_ID&fix=1>Username</a>
*/
?>
Break:<br />
<a href=make_problem.php?id=1>Admin</a><br />
Fix:<hr />
<a href=make_problem.php?id=1&fix=1>Admin</a><br />
<?php
if (!isset($_GET['fix']))
{
$problem = mysql_query("UPDATE users SET error = '1' WHERE ID='$_GET[id]'")or die(mysql_error());
echo broke;
}
if ($_GET['fix'] == 1)
{
$problem = mysql_query("UPDATE users SET error = '0' WHERE ID='$_GET[id]'")or die(mysql_error());
echo fixed;
}
?>
