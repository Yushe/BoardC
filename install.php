<?php

	require "lib/config.php";
	error_reporting(0);
	
	function filter_bool(&$bool){
		if (!isset($bool)) return false;
		else return (bool) $bool;
	}
	
	function filter_int(&$int){
		if (!isset($int)) return 0;
		else return (int) $int;
	}
	
	function filter_string(&$string){
		if (!isset($string)) return "";
		else return (string) $string;
	}
	
	function ctime(){return time()+$GLOBALS['config']['default-time-zone'];}
	function printdate($t){return date($GLOBALS['config']['default-date-format']." ".$GLOBALS['config']['default-time-format'], $t+$GLOBALS['config']['default-time-zone']);}
	
	function query($q){
		global $sql, $errors, $q_errors, $ok;
		$res = $sql->exec($q);
		if ($res === false) {$errors++; $q_errors[] = $q; print "$q NG!\n";}
		else $ok++;
	}

	function dialog($desc, $contents, $buttons, $title="Installer"){
		global $config;
		die("
<!doctype html>
<html>
	<head>
		<title>BoardC Installer</title>
			<style type='text/css'>
				body {
					background: #999;
					font-family: Verdana, Geneva, sans-serif;
					font-size: 13px;
					color: #fff;
					
					background-color: #000F1F;
					background-image: url('images/themes/night/starsbg.png');
				}
				body, table {
					color: #DDDDDD;
					font:13px verdana;
				}

				.danger{
					color: #FF0000 !important;
				}
				.selected{
					color: #FFFF00 !important;
				}
				.disabled{
					color: #888888 !important;
				}
				.notice{
					color: #FFF !important;
				}
				.fonts {
					font: 10px verdana;
				}
				.c{
					text-align: center;
				}

				.w{
					width: 100%;
				}

				a:link,a:visited,a:active,a:hover{text-decoration:none;font-weight:bold;}
				a:link		{color: #BEBAFE}
				a:visited	{color: #9990C0}
				a:active	{color: #CFBEFF}
				a:hover		{color: #CECAFE}

				table.main{
					border-spacing: 0px;
					color: #fff;
					border-top:	#000000 1px solid;
					border-left: #000000 1px solid;
				}

				td.light,td.dim,td.head,td.dark{
					border-right:	#000000 1px solid;
					border-bottom:	#000000 1px solid;
				}

				.light{
					background: #111133;
					
				}
				.dim{
					background: #11112B;
				}
				.head{
					background: #302048;
					color: #FFEEFF;
				}
				.dark{
					background: #2F2F5F;
					color: #FFEEFF;
				}


				textarea, input, select, button {
					border: 1px solid #663399;
					background-color: #000000;
					color: #DDDDDD;
				  font:	10pt verdana;
				}
				.submit {
					border: 2px solid #663399;
				}
				input.radio {
					border:	none;
					background: none;
					color: #DDDDDD;
					font:	10pt verdana;
				}
			</style>
			
			<link rel='icon' type='image/png' href='images/favicon.png'>
			
		</head>
		<body>
			<table class='main c w fonts'>
				<tr>
					<td colspan=3 class='light b'><a href='".$config['board-url']."'>".$config['board-title']."</a><br/><a href='install.php'>Restart installation</a></td>
				</tr>
				<tr>
					<td class='dim' style='width: 120px'>
						<nobr>Views: 0</nobr>
					</td>
					<td class='dim'>
						PRE-RELEASE<br/>VERSION
					</td>
					<td class='dim' style='width: 120px'>
						<nobr>".printdate(ctime())."</nobr>
					</td>
					
				</tr>			
				<tr><td colspan=3 class='dim'></td></tr>
			</table>
			<br/>
			<center><form method='POST' action='install.php'><table class='main'>
				<tr>
					<td class='head c'><center><b>$title</b></center></td>
				</tr>
				<tr><td class='light c'>$desc</td></tr>
				<tr><td class='dim'><center>$contents</center></td></tr>
				<tr><td class='dark c'>$buttons</td></tr>
			</table></form>
			<br/>
			
			<table class='main c fonts'><tr><td class='light'>
			BoardC ".$config['board-version']."<br/>
			&copy; 2016 Kak
			</td></tr></table>
			</center>		
			
		</body>
		</html>");
	}
	
	$step = filter_int($_POST['step']);
	
	if ($step){
		require "lib/mysql.php";
		$sql = new mysql;
		$connection = $sql->connect($sqlhost,$sqluser,$sqlpass,$sqlpersist);
	}
	if (!$step){
		dialog(	"This will setup BoardC Pre-Release v0.22a",
				"BoardC will be configured under these settings:<br/><br/>

					<table class='special head'>
					<tr><td class='light'>SQL Host:</td><td class='light'>$sqlhost</td></tr>
					<tr><td class='light'>SQL User:</td><td class='light'>$sqluser</td></tr>
					<tr><td class='light'>SQL Password:</td><td class='light'>$sqlpass</td></tr>
					<tr><td class='light'>SQL Database:</td><td class='light'>$sqldb</td></tr>
					<tr><td class='light'>Deleted User ID:</td><td class='light'>".$config['deleted-user-id']."</td></tr>
					</table><br/>
					
				If these are correct, click 'Continue'. Otherwise, edit config.php in the 'lib' directory.",
				"<input type='submit' name='start' value='Continue'><input type='hidden' name='step' value=1>");
	}				
	else if ($step == 1){
		$width = "style='width: 200px'";
		dialog(	"Login information and setup options",
		"This will be used to login to the board.<br/><br/>

			<table class='special head'>
			<tr><td class='dark c' colspan='2'><b>User ID #1 Login info</b></td></tr>
			<tr><td class='light'>Username:</td><td class='light'><input type='text' name='username' $width></td></tr>
			<tr><td class='light'>Password:</td><td class='light'><input type='text' name='pass1' $width></td></tr>
			<tr><td class='light'>Retype Password:</td><td class='light'><input type='text' name='pass2' $width></td></tr>
			<tr><td class='dark c' colspan='2'><b>Setup options</b></td></tr>
			<tr><td class='light' colspan='2'><input type='checkbox' name='addforum' value=1 checked> Create sample forums/categories</td></tr>
			<tr><td class='light' colspan='2'><input type='checkbox' name='additems' value=1 checked> Create sample item shop item(s)</td></tr>
			<tr><td class='light' colspan='2'><input type='checkbox' name='autodel' value=1 > Delete install.php if the installation completes</td></tr>
			</table><br/>
			
		Click Install to start executing the SQL commands. This may take more than 30 seconds.<br/>WARNING: This will drop the specified database!",
		"<input type='submit' name='start' value='Install'><input type='hidden' name='step' value=2>");
	}
	else if ($step == 2){
		$name = filter_string($_POST['username']);
		$pass1 = filter_string($_POST['pass1']);
		$pass2 = filter_string($_POST['pass2']);
		
		$return = "<input type='submit' name='start' value='Return'><input type='hidden' name='step' value=1>";
		
		if (!$name) dialog("", "You have left the username field empty!", $return, "Error");
		if (!$pass1) dialog("", "You have left the password field empty!", $return, "Error");
		if ($pass1 != $pass2) dialog("", "The passwords you entered don't match.", $return, "Error");
		
		// Here we go
		print "<!doctype html><title>Installer</title><body style='background: #008; color: #fff;'>
		<pre><b style='background: #fff; color: #008'>BoardC Installer</b>\n\nInstalling...";

		$sql->query("DROP DATABASE IF EXISTS `$sqldb`");	
		$sql->start();
		
		$errors = 0;
		$q_errors = array();
		$ok = 0;
		
		set_time_limit(0); //Fatal error:  Maximum execution time of 30 seconds exceeded in C:\xampp\htdocs\boardx\lib\mysql.php on line 128
		
query("
CREATE DATABASE `$sqldb`; DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
$sql->selectdb($sqldb);

query("
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` text COLLATE utf8mb4_unicode_ci,
  `user` int(32) NOT NULL,
  `time` int(32) NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `nohtml` tinyint(1) NOT NULL DEFAULT '0',
  `nosmilies` tinyint(1) NOT NULL DEFAULT '0',
  `nolayout` tinyint(1) NOT NULL DEFAULT '0',
  `avatar` int(32) NOT NULL DEFAULT '0',
  `forum` int(32) NOT NULL DEFAULT '0',
  `lastedited` int(32) NOT NULL DEFAULT '0',
  `rev` int(5) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
CREATE TABLE `announcements_old` (
  `id` int(32) NOT NULL,
  `aid` int(32) NOT NULL,
  `name` text NOT NULL,
  `title` text NOT NULL,
  `text` text NOT NULL,
  `time` int(32) NOT NULL,
  `rev` int(4) NOT NULL,
  `nohtml` tinyint(1) NOT NULL DEFAULT '0',
  `nosmilies` tinyint(1) NOT NULL DEFAULT '0',
  `nolayout` tinyint(1) NOT NULL DEFAULT '0',
  `avatar` int(32) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
CREATE TABLE `categories` (
  `id` int(32) NOT NULL,
  `name` varchar(128) NOT NULL,
  `powerlevel` int(1) NOT NULL DEFAULT '0',
  `ord` int(32) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
CREATE TABLE `failed_logins` (
  `id` int(32) NOT NULL,
  `ip` varchar(32) NOT NULL,
  `attempt` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
CREATE TABLE `forummods` (
  `id` int(32) NOT NULL,
  `fid` int(32) NOT NULL,
  `uid` int(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
CREATE TABLE `forums` (
  `id` int(32) NOT NULL,
  `name` varchar(256) NOT NULL,
  `title` varchar(256) NOT NULL,
  `powerlevel` int(1) NOT NULL DEFAULT '0',
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `threads` int(32) NOT NULL DEFAULT '0',
  `posts` int(32) NOT NULL DEFAULT '0',
  `category` int(32) NOT NULL DEFAULT '0',
  `ord` int(32) NOT NULL DEFAULT '0',
  `theme` int(32) DEFAULT NULL,
  `lastpostid` int(32) DEFAULT NULL,
  `lastpostuser` int(32) DEFAULT NULL,
  `lastposttime` int(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
CREATE TABLE `ipbans` (
  `id` int(32) NOT NULL,
  `ip` varchar(32) DEFAULT NULL,
  `time` int(32) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
CREATE TABLE `jstrap` (
  `id` int(32) NOT NULL,
  `user` int(32) NOT NULL,
  `ip` varchar(32) NOT NULL,
  `source` text NOT NULL,
  `filtered` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
CREATE TABLE `misc` (
  `disable` tinyint(1) NOT NULL,
  `views` int(32) NOT NULL,
  `theme` int(32) DEFAULT NULL,
  `threads` int(32) NOT NULL DEFAULT '0',
  `posts` int(32) NOT NULL DEFAULT '0',
  `noposts` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
INSERT INTO `misc` (`disable`, `views`, `theme`, `threads`, `posts`, `noposts`) VALUES ('0', '0', NULL, '0', '0', '0');");
query("
CREATE TABLE `hits` (
  `id` int(32) NOT NULL,
  `ip` varchar(32) NOT NULL,
  `time` int(32) NOT NULL,
  `page` text NOT NULL,
  `useragent` text NOT NULL,
  `user` int(32) NOT NULL DEFAULT '0',
  `forum` int(32) NOT NULL DEFAULT '0',
  `referer` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
CREATE TABLE `new_announcements` (
  `id` int(32) NOT NULL,
  `user0` tinyint(1) NOT NULL DEFAULT '0',
  `user1` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Used for per-user tracking new announcements';");
query("
CREATE TABLE `new_posts` (
  `id` int(32) NOT NULL,
  `user0` tinyint(1) NOT NULL DEFAULT '0',
  `user1` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Used for per-user tracking new posts';");
query("
CREATE TABLE `pms` (
  `id` int(32) NOT NULL,
  `name` text NOT NULL,
  `title` text NOT NULL,
  `user` int(32) NOT NULL,
  `userto` int(32) NOT NULL,
  `time` int(32) NOT NULL,
  `text` text NOT NULL,
  `nohtml` tinyint(1) NOT NULL DEFAULT '0',
  `nosmilies` tinyint(1) NOT NULL DEFAULT '0',
  `nolayout` tinyint(1) NOT NULL DEFAULT '0',
  `avatar` int(32) NOT NULL DEFAULT '0',
  `new` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
CREATE TABLE `poll_votes` (
  `id` int(11) NOT NULL,
  `user` int(32) NOT NULL,
  `thread` int(32) NOT NULL,
  `vote` int(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
CREATE TABLE `posts` (
  `id` int(32) NOT NULL,
  `text` text NOT NULL,
  `time` int(32) NOT NULL,
  `thread` int(32) NOT NULL,
  `user` int(32) NOT NULL,
  `rev` int(4) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `nohtml` tinyint(1) NOT NULL DEFAULT '0',
  `nosmilies` tinyint(1) NOT NULL DEFAULT '0',
  `nolayout` tinyint(1) NOT NULL DEFAULT '0',
  `lastedited` int(32) NOT NULL DEFAULT '0',
  `avatar` int(32) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
CREATE TABLE `posts_old` (
  `id` int(32) NOT NULL,
  `pid` int(32) NOT NULL,
  `text` text NOT NULL,
  `time` int(32) NOT NULL,
  `rev` int(4) NOT NULL,
  `nohtml` tinyint(1) NOT NULL DEFAULT '0',
  `nosmilies` tinyint(1) NOT NULL DEFAULT '0',
  `nolayout` tinyint(1) NOT NULL DEFAULT '0',
  `avatar` int(32) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
CREATE TABLE `ratings` (
  `id` int(32) NOT NULL,
  `userfrom` int(32) NOT NULL,
  `userto` int(32) NOT NULL,
  `rating` int(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
CREATE TABLE `shop_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `title` varchar(128) NOT NULL,
  `ord` int(32) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
CREATE TABLE `shop_items` (
  `id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `title` text NOT NULL,
  `cat` int(32) NOT NULL,
  `hp` varchar(32) NOT NULL,
  `mp` varchar(32) NOT NULL,
  `atk` varchar(32) NOT NULL,
  `def` varchar(32) NOT NULL,
  `intl` varchar(32) NOT NULL,
  `mdf` varchar(32) NOT NULL,
  `dex` varchar(32) NOT NULL,
  `lck` varchar(32) NOT NULL,
  `spd` varchar(32) NOT NULL,
  `coins` varchar(32) NOT NULL DEFAULT '0',
  `gcoins` varchar(32) NOT NULL DEFAULT '0',
  `special` int(32) NOT NULL DEFAULT '0',
  `ord` int(32) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
CREATE TABLE `themes` (
  `id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `file` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
INSERT INTO `themes` (`id`, `name`, `file`) VALUES
(1, 'Default', 'default.css'),
(2, 'Night (Jul)', 'night.css'),
(3, 'Hydra''s Blue Thing (Alternate)', 'hbluealt.css');");
query("
CREATE TABLE `threads` (
  `id` int(32) NOT NULL,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `time` int(32) NOT NULL,
  `forum` int(32) NOT NULL,
  `user` int(32) NOT NULL,
  `sticky` tinyint(1) NOT NULL,
  `closed` tinyint(1) NOT NULL,
  `views` int(32) NOT NULL DEFAULT '0',
  `replies` int(32) NOT NULL DEFAULT '0',
  `icon` text,
  `ispoll` tinyint(1) NOT NULL DEFAULT '0',
  `lastpostid` int(32) DEFAULT NULL,
  `lastpostuser` int(32) DEFAULT NULL,
  `lastposttime` int(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
CREATE TABLE `tor` (
  `id` int(32) NOT NULL,
  `ip` varchar(32) NOT NULL,
  `time` int(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
CREATE TABLE `users` (
  `id` int(32) NOT NULL,
  `name` varchar(32) NOT NULL,
  `displayname` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `powerlevel` int(1) NOT NULL DEFAULT '0',
  `sex` int(1) NOT NULL DEFAULT '2',
  `namecolor` varchar(6) DEFAULT NULL,
  `lastip` varchar(32) DEFAULT NULL,
  `ban_expire` int(32) DEFAULT '0',
  `since` int(32) NOT NULL DEFAULT '0',
  `ppp` int(3) NOT NULL DEFAULT '25',
  `tpp` int(3) NOT NULL DEFAULT '25',
  `head` text,
  `sign` text,
  `dateformat` varchar(32) DEFAULT NULL,
  `timeformat` varchar(32) DEFAULT NULL,
  `lastpost` int(32) NOT NULL DEFAULT '0',
  `lastview` int(32) NOT NULL DEFAULT '0',
  `lastforum` int(32) NOT NULL DEFAULT '0',
  `bio` text,
  `posts` int(32) NOT NULL DEFAULT '0',
  `threads` int(32) NOT NULL DEFAULT '0',
  `email` varchar(64) NOT NULL,
  `homepage` varchar(64) NOT NULL,
  `youtube` varchar(64) NOT NULL,
  `twitter` varchar(64) NOT NULL,
  `facebook` varchar(64) NOT NULL,
  `homepage_name` varchar(64) NOT NULL,
  `tzoff` int(2) NOT NULL DEFAULT '0',
  `realname` varchar(64) NOT NULL,
  `location` varchar(64) NOT NULL,
  `birthday` int(32) DEFAULT NULL,
  `theme` int(8) NOT NULL DEFAULT '1',
  `showhead` tinyint(1) NOT NULL DEFAULT '1',
  `signsep` int(3) NOT NULL DEFAULT '1',
  `icon` text,
  `coins` int(32) NOT NULL DEFAULT '0',
  `gcoins` int(32) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
INSERT INTO `users` (`id`, `name`, `password`, `lastip`, `since`, `powerlevel`) VALUES
('1', '$name','".password_hash($pass1, PASSWORD_DEFAULT)."','".$_SERVER['REMOTE_ADDR']."','".ctime()."', '5'),
('".$config['deleted-user-id']."', 'Deleted user', 'rip','".$_SERVER['REMOTE_ADDR']."','".ctime()."', '-2');
");
query("
CREATE TABLE `users_rpg` (
  `id` int(11) NOT NULL,
  `hp` int(32) NOT NULL DEFAULT '1',
  `mp` int(32) NOT NULL DEFAULT '1',
  `atk` int(32) NOT NULL DEFAULT '1',
  `def` int(32) NOT NULL DEFAULT '1',
  `intl` int(32) NOT NULL DEFAULT '1',
  `dex` int(32) NOT NULL DEFAULT '1',
  `lck` int(32) NOT NULL DEFAULT '1',
  `spd` int(32) NOT NULL DEFAULT '1',
  `mdf` int(32) NOT NULL DEFAULT '1',
  `item1` int(32) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
INSERT INTO users_rpg (`id`, `hp`, `mp`, `atk`, `def`, `intl`, `dex`, `lck`, `spd`, `mdf`) VALUES
('1', '1', '1', '1', '1', '1', '1', '1', '1', '1'),
('".$config['deleted-user-id']."', '0', '0', '0', '0', '0', '0', '0', '0', '0');");
query("
CREATE TABLE `user_avatars` (
  `id` int(32) NOT NULL,
  `user` int(32) NOT NULL,
  `file` int(16) NOT NULL,
  `title` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
query("
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `announcements_old`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `failed_logins`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `forummods`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `forums`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `ipbans`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `jstrap`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `hits`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `new_announcements`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `new_posts`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `pms`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `poll_votes`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `posts_old`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `themes`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `threads`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `tor`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `user_avatars`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `shop_categories`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `shop_items`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `users_rpg`
  ADD PRIMARY KEY (`id`);
");

// Sample forums
if (filter_int($_POST['addforum'])){
	
	query("
	INSERT INTO `categories` (`id`, `name`, `powerlevel`, `ord`) VALUES
	(1, 'Main', 0, 1),
	(2, 'Game Over', 0, 100);");
	query("
	INSERT INTO `forums` (`id`, `name`, `title`, `powerlevel`, `hidden`, `threads`, `posts`, `category`, `ord`) VALUES
	(1, 'General forum', 'For everybody!', 0, 0, 0, 0, 1, 1),
	(2, 'General staff forum', 'Not for everybody!', 2, 0, 0, 0, 1, 0),
	(3, 'The trash', 'Definitely not for everybody!', 2, 0, 0, 0, 2, 10);");
	
	query("
	ALTER TABLE `categories`
	  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
	ALTER TABLE `forums`
	  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;");
}
else{
	query("
	ALTER TABLE `categories`
	  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT;
	ALTER TABLE `forums`
	  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT;");
}

// Sample shop items
if (filter_int($_POST['additems'])){
	query("
	INSERT INTO `shop_categories` (`id`, `name`, `title`, `ord`) VALUES
	(1, 'Sample category', 'This is a sample description', 0);");
	query("
	INSERT INTO `shop_items` (`id`, `name`, `title`, `cat`, `hp`, `mp`, `atk`, `def`, `intl`, `mdf`, `dex`, `lck`, `spd`, `coins`, `gcoins`, `special`, `ord`) VALUES
	(1, 'Test item?', 'It does not actually do anything! (or is it?)', 1, '+1000', '-10', 'x45', '/2', '+2', '+0', '+56', '+9999', '+1', '0', '0', 1, 0);");
	
	query("
	ALTER TABLE `shop_categories`
	  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
	ALTER TABLE `shop_items`
	  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;");
}
else{
	query("
	ALTER TABLE `shop_categories`
	  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
	ALTER TABLE `shop_items`
	  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");	
}


// Auto increments

query("
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `announcements_old`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT;

ALTER TABLE `failed_logins`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT; 
ALTER TABLE `forummods`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT;

ALTER TABLE `hits`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT;
ALTER TABLE `ipbans`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT;
ALTER TABLE `jstrap`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT;
ALTER TABLE `new_announcements`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT;
ALTER TABLE `new_posts`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT;
ALTER TABLE `pms`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT;
ALTER TABLE `poll_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `posts`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `posts_old`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT;
ALTER TABLE `ratings`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT;

ALTER TABLE `themes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `threads`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `tor`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=".($config['deleted-user-id']+1).";
ALTER TABLE `user_avatars`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users_rpg`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=".($config['deleted-user-id']+1).";
");
		
		print "\n\nQueries: ".($ok+$errors)." | Errors: $errors\n";
		
		if (!$errors){
			$c = $sql->end();
			if ($c !== false){
				if (!file_exists("userpic")) mkdir("userpic");
				if (!file_exists("userpic/1")) mkdir("userpic/1");
				
				if (filter_int($_POST['autodel'])){
					$otheraction = "now";
					unlink("install.php");
				}
				else{
					$otheraction = "(and <i>should</i>) delete this file and";
				}
				
				die("Operation completed successfully.\nYou can $otheraction login <a href='login.php' style='background: #fff'>here</a>.");
			}
			else die("An unknown error occurred while closing the transaction.");
		}
		else{
			$sql->undo();
			die("Installation failed.");
		}
		
	}
	
		
		
	
	
?>
