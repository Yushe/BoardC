<?php
	// based on admin-deluser.php
	require "lib/function.php";

	if (!powlcheck(4))
		errorpage("You're not an admin!");
		
	pageheader("Ban Button");
		
	print adminlinkbar();
	
	$check = $sql->resultq("SELECT powerlevel FROM users WHERE id = ".$config['deleted-user-id']);
	if ($check != "-2")
		errorpage("Deleted user ID not configured properly. (User missing or ID points to a normal user)", false);
	
	if (isset($_POST['rip'])){
		
		if (!filter_int($_POST['del']))
			errorpage("For whatever reason, no user was selected. <a href='?'>Try again</a>.", false);

		
		$dest = $config['deleted-user-id'];
		$id = $_POST['del'];
		

		$data = $sql->fetchq("SELECT id, name, lastip FROM users ORDER BY id DESC");
		if ($data['id'] != $id)
			errorpage("Sorry, but you've either been ninja'd by someone else, or this isn't the last registered user. <a href='?'>Try again</a>", false);		
		
		$sql->start();
		
		$c[] = $sql->query("DELETE FROM users WHERE id = $id");
		$c[] = $sql->query("ALTER TABLE users AUTO_INCREMENT=$id");
		$c[] = $sql->query("DELETE FROM users_rpg WHERE id = $id");
		$c[] = $sql->query("ALTER TABLE users_rpg AUTO_INCREMENT=$id");
		$c[] = $sql->query("DELETE FROM user_avatars WHERE user = $id");
		$c[] = $sql->query("DELETE FROM posts WHERE user = $id");
		$c[] = $sql->query("DELETE FROM pms WHERE user = $id OR userto = $id");
		$c[] = $sql->query("UPDATE threads SET user=$dest WHERE user = $id");
		$c[] = $sql->query("DELETE FROM ratings WHERE userfrom = $id OR userto = $id");
		$c[] = $sql->query("ALTER TABLE new_posts DROP COLUMN user$id");
		$c[] = $sql->query("ALTER TABLE new_announcements DROP COLUMN user$id");
		
		
		if (filter_int($_POST['ipban'])) ipban("", false, $data['lastip']);
		
		foreach(glob("userpic/$id/*") as $f)
			unlink("$f");
		rmdir("userpic/$id");
		$sql->end();
		errorpage("User ID #$id (".$data['name'].") deleted!<br/>Click <a href='?'>here</a> to delete more.", false);
		
	}
	
	
	$user = $sql->fetchq("
	SELECT id, name, displayname, namecolor, powerlevel, sex, icon, powerlevel, posts, since, lastip, lastview
	FROM users
	WHERE powerlevel < 1
	AND id != ".$config['deleted-user-id']."
	AND id != ".$loguser['id']."
	ORDER BY id DESC
	");
	
	if (!$user || $user['id'] != $sql->resultq("SELECT MAX(id) FROM users"))
		errorpage("There are no more users that can be deleted! You can go <a href='index.php'>home</a> now.", false);
	
	$list = "";
	$lazy = htmlspecialchars(input_filters($sql->resultq("SELECT page FROM hits WHERE user=".$user['id']." ORDER BY id DESC")));
	
	print "
	<form method='POST' action='admin-quickdel.php'>
	<center><table class='main c'>
		<tr><td class='head'>Press Start Button</td></tr>
		
		<tr><td class='light'>
			By pushing The Button&trade;, you will delete the latest registered user!<br/>Next up on the chopping block:
		</td></tr>
		<tr>
		<td class='dim'>
			<br/><center>
			<table class='main c'>
			<tr><td class='light'>User:</td><td class='light'>".makeuserlink(false, $user, true)."</td></tr>
			<tr><td class='light'>Posts:</td><td class='light'>".$user['posts']."</td></tr>
			<tr><td class='light'>IP Address:</td><td class='light'>".$user['lastip']."</td></tr>
			<tr><td class='light'>Registered:</td><td class='light'>".choosetime(ctime()-$user['since'])." ago</td></tr>
			<tr><td class='light'>Last view:</td><td class='light'>$lazy, ".choosetime(ctime()-$user['lastview'])." ago</td></tr>
			</table></center><br/>
			
		</td>
		<tr><td class='dark'><input type='submit' name='rip' value='DELETE'><input type='checkbox' name='ipban' value=1 checked>IP Ban<input type='hidden' name='del' value='".$user['id']."'></td></tr>
		</table></center>
	</form>
	
	";
	
	pagefooter();

?>