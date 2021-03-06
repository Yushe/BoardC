<?php

	require "lib/function.php";
	

	$pid 		= filter_int($_GET['pid']);
	$usermode 	= filter_int($_GET['user']); // Leave this for now
	
	if (filter_int($_GET['id']))
		$lookup = intval($_GET['id']);
	
	else if ($pid){
		$lookup = getthreadfrompost($pid);
		if ($lookup){
			/*
				Query changed to count the post position in the thread directly in the query
				rather than using a separate for loop
			*/
			$i 	= $sql->resultq("SELECT COUNT(id) FROM posts WHERE thread=$lookup AND id < $pid");
			for($i, $_GET['page'] = 0; $i>=$loguser['ppp']; $i-=$loguser['ppp'])
				$_GET['page']++;
		}
	}
	else if ($usermode){
		trigger_error("Accessed thread.php with ?user=$usermode arg.", E_USER_DEPRECATED);
		errorpage("This page was accessed with a deprecated ?user argument.<br>Click <a href='showposts.php?id=$usermode'>here</a> to access the proper page.");
	}
	else errorpage("No thread selected.");

	/*
		Thread data loading
	*/
	$tdata = getthreadinfo($lookup, $_GET['pid']);
	
	$thread 	= $tdata[0];
	$forum 		= $tdata[1];
	$error_id 	= $tdata[2];
	if (!$pid)
		$pid	= $tdata[3];
	
	if ($thread['rthread'])
		$lookup = $_GET['id'] = $thread['rthread'];
	//print $lookup;
	
	unset($tdata);
	
	$ismod = ismod(isset($thread['forum']) ? $thread['forum'] : false);
	
	// online update, revised
	update_hits(filter_int($forum['id']));
	
	if ($error_id){
		// id => print error, irc error, kill
		$username = ($loguser['id'] ? "User ID #".$loguser['id']."(".$loguser['name'].")" : "IP ".$_SERVER['REMOTE_ADDR']);
		
		$threadbug = array(
			1 => array("You're not allowed to view the thread","$username accessed restricted thread ID #$lookup", true),
			2 => array("The thread with ID #$lookup doesn't exist.", "$username accessed nonexisting thread ID #$lookup", true),
			3 => array(filter_int($_GET['pid']) ? "A post with ID #$pid does exist, but it's in an invalid thread. (ID #$lookup)" : "A thread with ID #$lookup doesn't exist, but there are posts associated with it.", "$username accessed valid posts in invalid thread ID #$lookup", false),
			4 => array("A thread with ID #$lookup does exist, but it's in an invalid forum. (ID #".$forum['id'].")", "$username accessed valid thread ID #$lookup in invalid forum ID #".$forum['id'], false),
			5 => array("There is no post in the database with ID #$pid", "$username accessed nonexisting post #$pid", true),
		);
		
		trigger_error($threadbug[$error_id][1], E_USER_NOTICE);
		

		if (!$ismod)
			errorpage("Couldn't enter the thread. Either it doesn't exist or you're not allowed to view it.");
		else if ($threadbug[$error_id][2])
			errorpage($threadbug[$error_id][0]);
		
		$threadbug_txt = "<div style='text-align: center; color: yellow; padding: 3px; border: 5px dotted yellow; background: #000;'><b>Thread error: ".$threadbug[$error_id][0]."</b></div>";
	}
	else{
		$threadbug_txt = "";
		if (!($bot || $proxy || $tor))
			$sql->query("UPDATE threads SET views=".($thread['views']+1)." WHERE id = $lookup");
	}
	
	$mergewhere			= "";
	$showmergecheckbox 	= false;
	
	if (isset($forum['theme'])) $loguser['theme'] = filter_int($forum['theme'])-1;
	/*
	Reminder: the only two error_ids that can get here are 3 and 4
	*/
	
	if (filter_int($_GET['vote'])){

		if ($thread['ispoll'] && $loguser['id']){
			
			$done = $sql->resultq("SELECT id FROM poll_votes WHERE user = ".$loguser['id']." AND thread = $lookup AND vote = ".filter_int($_GET['vote']));
			
			if ($done){ // delete your vote when clicking on something you already voted on
				$sql->query("DELETE from poll_votes WHERE id = $done");
				header("Location: thread.php?id=$lookup");
				x_die();
			}
			else if (!$thread['polldata'][2]) // multiple votes flag
				$sql->query("DELETE from poll_votes WHERE user = ".$loguser['id']." AND thread = $lookup");

			$sql->query("INSERT INTO poll_votes (user, thread, vote) VALUES (".$loguser['id'].", $lookup, ".filter_int($_GET['vote']).")");
			
		}

		header("Location: thread.php?id=$lookup");
	}
	else if (isset($_GET['votes'])){
		
		if (!powlcheck(4) || !$thread['ispoll']) header("Location: thread.php?id=$lookup");
		
		
		$votes = $sql->query("
			SELECT p.vote, $userfields
			FROM poll_votes p
			LEFT JOIN users u ON p.user = u.id
			WHERE p.thread = $lookup
		");
		
		$txt 	= "";
		$total 	= 0;
		$votedb = array(0);
		$txtdb 	= array("");
		
		
		while ($vote = $sql->fetch($votes)){
			$votedb[$vote['vote']] 	= filter_int($votedb[$vote['vote']]) + 1;
			$txtdb[$vote['vote']][] = makeuserlink(false, $vote, true);
			$total++;
		}
		
		// text
		for($i=3,$n=1;isset($thread['polldata'][$i]);$i+=2,$n++)
			$txt .= "
				<tr>
					<td class='light c'><b>".$thread['polldata'][$i]."</b></td>
					<td class='dim c'>".filter_int($votedb[$n])."</td>
					<td class='dim c'>".(isset($txtdb[$n]) ? implode(", ", $txtdb[$n]) : "None")."</td>
				</tr>
			";
		
		pageheader($thread['title']." - Poll votes");
		
		print "<br><center><table class='main'>
		
		<tr><td class='head c' colspan=3>Poll votes for <b>".$thread['title']."</b></td></tr>
		
		<tr>
			<td class='head c'>Option</td>
			<td class='head c'>Votes</td>
			<td class='head c'>Users</td>
		</tr>
		$txt
		<tr>
			<td class='dark c'><i><b>Total votes<b></i></td>
			<td class='dark c'>$total</td>
			<td class='dark c'>&nbsp;</td>
		</tr>
		<tr><td class='light c' colspan=3><a href='thread.php?id=$lookup'>Return to the poll</a></td></tr>
		</table></center>
		";
		
		pagefooter();
		
	}
	else if (isset($_GET['tkill'])){
		if (!powlcheck(5))
			errorpage("Don't you know you shouldn't play with nuclear bombs?");
		
		if (isset($_POST['return']))
			header("Location: thread.php?id=$lookup");
	
		else if (isset($_POST['dokill'])){
			$sql->start();
								$diff	= $sql->exec ("DELETE FROM posts WHERE thread = $lookup");
			if ($error_id != 3)	$c[]	= $sql->query("DELETE FROM threads WHERE id = $lookup");
			//d($diff);
			if (!$error_id)		$c[]	= $sql->query("UPDATE forums SET posts=(posts-$diff),threads=(threads-1) WHERE id=".$thread['forum']);
								$c[]	= $sql->query("UPDATE misc SET posts=(posts-$diff),threads=(threads-1)");
			update_last_post($forum['id'], false, true);
			if ($diff)
				if ($sql->finish($c))
					header("Location: forum.php?id=".$forum['id']);//errorpage("Erased thread from the database!");
			errorpage("Couldn't delete the thread.");
		}
		
		pageheader($thread['name']." - Delete Thread");
		
		print "
			<center><form method='POST' action='thread.php?id=$lookup&tkill'><table class='main c'>
				<tr><td class='head'>Delete Thread</td></tr>
				
				<tr><td class='light'>Are you sure you want to delete this thread?<br><small>This action is irreversible!</small></td></tr>
				<tr><td class='dim'><input type='submit' name='dokill' value='Yes'> <input type='submit' name='return' value='No'></td></tr>
			
			</table></form></center>";
		pagefooter();		
	}
	
	else if (isset($_GET['tren'])){
		if (!$ismod && $thread['user'] != $loguser['id'])
			errorpage("You have no permission to do that!");
		
		if ($error_id == 3)
			errorpage("Renaming invalid threads wouldn't work anyway");
		
		$rname 	= isset($_POST['newname']) 	? $_POST['newname']  : $thread['name'];
		$rtitle = isset($_POST['newtitle']) ? $_POST['newtitle'] : $thread['title'];
		
		if (isset($_POST['dorename'])){
			
			if (!filter_string($_POST['newname']))
				errorpage("You have left the thread name empty! (only the thread title is optional)");
			
			if ($thread['ispoll']){
				/*if (!filter_string($_POST['newtitle']))
					errorpage("You have left the thread title empty!", false);
			
				$thread['polldata'][0] = input_filters($_POST['newtitle']);
				$_POST['newtitle'] = implode("\0",$thread['polldata']);
				*/
				$sql->queryp("UPDATE threads SET name = ? WHERE id = ".$lookup, array(input_filters($_POST['newname'])));
			}

			else $sql->queryp("UPDATE threads SET name = ?, title = ? WHERE id = ".$lookup, array(input_filters($_POST['newname']), input_filters($_POST['newtitle'])));
			
			header("Location: thread.php?id=$lookup");//errorpage("The thread has been renamed.", false);
			
		}
		
		pageheader($thread['name']." - Rename Thread");
		print "
		<center>
			<form action='thread.php?id=$lookup&tren' method='POST'>
				<table class='main'>
					<tr><td colspan=2 class='head c'>Rename Thread</td></tr>
					
					<tr>
						<td class='light' style='width: 100px;'>Name:</td>
						<td class='dim'><input style='width: 400px;' type='text' name='newname' value=\"".htmlspecialchars($rname)."\"></td>
					</tr>
					".($thread['ispoll'] ? "" : "
					<tr>
						<td class='light'>Title:</td>
						<td class='dim'><input style='width: 400px;' type='text' name='newtitle' value=\"".htmlspecialchars($rtitle)."\"></td>
					</tr>"
					)."
					<tr><td class='dim' colspan=2><input type='submit' name='dorename' value='Rename'></td></tr>
				</table>
			</form>
		</center>";
		
		pagefooter();
	}
	else if (isset($_GET['tmove'])){
		if (!$ismod)
			errorpage("No.");
		if ($error_id == 3)
			errorpage("You should move or erase the posts associated with this thread - don't move something that doesn't exist.");
		
		if (isset($_POST['domove'])){
			
			$newforum = filter_int($_POST['forumjump2']);
			
			if (!$sql->resultq("SELECT 1 FROM forums WHERE id = $newforum"))
				errorpage("uh no, that forum doesn't exist");
			
			$sql->start();
			
			$c[] = $sql->query("UPDATE threads SET forum = $newforum WHERE id = $lookup");
			
			//Don't forget to move stats as well bsafsjkagkg
			if (!$error_id)
				$c[] = $sql->query("UPDATE forums SET threads=threads-1,posts=posts-".($thread['replies']+1)." WHERE id = ".$forum['id']);
			
			$c[] = $sql->query("UPDATE forums SET threads=threads+1,posts=posts+".($thread['replies']+1)." WHERE id = $newforum");
			
			update_last_post($forum['id'],	false, true);
			update_last_post($newforum,		false, true);
			
			if ($sql->finish($c)) header("Location: thread.php?id=$lookup");//errorpage("The thread has been moved.", false);
			else errorpage("Couldn't move the thread.");
		}
		
		pageheader($thread['name']." - Move Thread");
		
		print "
		<center>
			<form action='thread.php?id=$lookup&tmove' method='POST'>
				<table class='main'>
					<tr><td colspan=2 class='head c'>Move Thread</td></tr>
					<tr>
						<td class='light' style='width: 100px;'>Current forum:</td>
						<td class='dim'>".htmlspecialchars($forum['name'])."</td>
					</tr>
					<tr>
						<td class='light'>New Forum:</td>
						<td class='dim'>".doforumjump($thread['forum'], true)."</td>
					</tr>
					<tr><td class='dim' colspan=2><input type='submit' name='domove' value='Move'></td></tr>
				</table>
			</form>
		</center>";
		
		pagefooter();
	}
	else if (isset($_GET['tstick'])){
		
		if (!$ismod)		errorpage("You're not a moderator.");
		if ($error_id == 3)	errorpage("Doesn't work in bad threads.");
		
		$sql->query("UPDATE threads SET sticky = NOT sticky WHERE id = $lookup");
		header("Location: thread.php?id=$lookup");//errorpage("Thread ".($thread['sticky'] ? "un" : "")."sticked!");
	}
	else if (isset($_GET['tclose'])){
		
		if (!$ismod)		errorpage("No.");
		if ($error_id == 3)	errorpage("Also doesn't work in bad threads.");
		
		$sql->query("UPDATE threads SET closed = NOT closed WHERE id = $lookup");
		header("Location: thread.php?id=$lookup");//errorpage("Thread ".($thread['closed'] ? "opened" : "closed")."!");
	}
	else if (isset($_GET['tnoob'])){
		if (!$ismod){
			$sql->query("UPDATE posts SET noob = 1 WHERE user = ".$loguser['id']);
			errorpage("Hello World!");
		}
		if ($error_id == 3)	errorpage("Not a good thread to do this.");
		
		$sql->query("UPDATE threads SET noob = NOT noob WHERE id = $lookup");
		header("Location: thread.php?id=$lookup");
	}
	else if (isset($_GET['ttrash'])){
		
		if (!$ismod)		errorpage("You have no permission to do this!");
		if ($error_id == 3)	errorpage("Nope, this doesn't work either.");
		
		if (!$sql->resultq("SELECT 1 FROM forums WHERE id = ".$config['trash-id']))
			errorpage("The trash forum id (config.php - \$config['trash-id']) is not configured properly. The forum id referenced doesn't exist.");
		
		$sql->start();
		
		$c[] = $sql->query("UPDATE threads SET forum = ".$config['trash-id']." WHERE id = $lookup");
		
		if (!$error_id)
			$c[] = $sql->query("UPDATE forums SET threads=threads-1,posts=posts-".($thread['replies']+1)." WHERE id = ".$forum['id']);
		
		$c[] = $sql->query("UPDATE forums SET threads=threads+1,posts=posts+".($thread['replies']+1)." WHERE id = ".$config['trash-id']);
		
		if ($sql->finish($c)) header("Location: thread.php?id=$lookup");//errorpage("The thread has been trashed.", false);
		else errorpage("Couldn't trash the thread.");
		
	}
	else if (isset($_GET['ticon'])){
		if ($loguser['id'] != $thread['user'] && !$ismod)
			errorpage("You have no permission to do this!");
		
		if ($error_id == 3)
			errorpage("Doesn't work.");
		
		// snip from new.php
		if (filter_string($_POST['icon_c'])) 	$icon = $_POST['icon_c'];
		else if (filter_string($_POST['icon'])) $icon = $_POST['icon'];
		else 									$icon = $thread['icon'];
		
		if (isset($_POST['doicon'])){
			$sql->queryp("UPDATE threads SET icon=? WHERE id = $lookup", array(input_filters($icon)));
			header("Location: thread.php?id=$lookup");
		}
		
		$icons = getthreadicons();
		$icon_sel[$icon] = "checked";
		
		$icon_txt = "";
		$i = 0;

		foreach($icons as $link){
			if ($i == 10){
				$i = 0;
				$icon_txt .= "<br>";
			}
			$link = trim($link);
			$icon_txt .= "<nobr><input type='radio' name='icon' value=\"$link\" ".filter_string($icon_sel[$link])."><img src='$link'></nobr>&nbsp;&nbsp;&nbsp;&nbsp;";
			$i++;
		}
		$icon_txt .= "<br>
		<nobr><input type='radio' name='icon' value=0 ".filter_string($icon_sel[0])."> None&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Custom: <input type='text' name='icon_c' value=\"".filter_string($_POST['icon_c'])."\"></nobr>";
		
		pageheader("Change thread icon");
		print "<br><form method='POST' action='thread.php?id=$lookup&ticon'>
		<center><table class='main'>
			<tr><td class='head c'><b>Thread icons</b></td></tr>
			<tr><td class='light'>$icon_txt</td></tr>
			<tr><td class='dark'><input type='submit' name='doicon' value='Change'></td></tr>
		</table></center></form>
		";
		
		pagefooter();
		
	}
	else if (isset($_GET['tmerge'])){
		
		if (!$ismod)
			errorpage("You're not allowed to do this.");
		
		//c_merge[]
		if (isset($_POST['domerge'])){
			
			if (isset($_POST['return']))
				header("Location: thread.php?id=$lookup");
			
			// This action is quite risky. Check these things to prevent "fun" stuff from happening
			if (!isset($_POST['c_merge']) && !isset($_POST['a_merge']))
				errorpage("No posts selected.");
			if (!$sql->resultq("SELECT 1 FROM threads WHERE ID = ".filter_int($_POST['moveto'])))
				errorpage("The thread ID you have typed doesn't exist.");
			if (!canviewforum($sql->resultq("SELECT forum FROM threads WHERE id = ".filter_int($_POST['moveto']))))
				errorpage("The thread you have chosen is a restricted forum!");
			
			if(isset($_POST['domerge2'])){
				$sql->start();
				
				if (filter_int($_POST['a_merge'])){
					$x = ""; //query addendum
				}
				else{
					array_map('intval', $_POST['c_merge']);
					$x = "id IN (".implode(", ", $_POST['c_merge']).") AND";
				}
				
				$destination = intval($_POST['moveto']);
				$c[] = $sql->query("UPDATE posts SET thread=$destination WHERE $x thread=$lookup");
				
				update_last_post($lookup);
				update_last_post($destination);
				
				// Update the correct reply count here, as while local mods can merge posts, thread-fix is an admin tool
				$c[] = $sql->query("UPDATE threads SET replies = (SELECT COUNT(id) FROM posts WHERE thread = $lookup)-1 WHERE id = $lookup");
				$c[] = $sql->query("UPDATE threads SET replies = (SELECT COUNT(id) FROM posts WHERE thread = $destination)-1 WHERE id = $destination");
				
				if ($sql->finish($c)) header("Location: thread.php?id=$destination");
				else errorpage("The posts could not be moved.");
				//errorpage($sql->finish($c) ? "Posts moved!<br><small>(Now run Thread Fix)</small>": "Couldn't move the posts.");
				
			}
			
			$phide = "";

			if (isset($_POST['a_merge'])){
				$whatposts = "all the posts";
				$phide = "<input type='hidden' name='a_merge' value=1>";
				$showposts = "";
				$txt_mini = "";
			}
			else {
				$whatposts = "these posts";
				foreach ($_POST['c_merge'] as $onevar){
					
					if (!is_numeric($onevar)){
						// What are you trying to do
						trigger_error("Thread merge -- Post ID from user #".$loguser['id']." (".$loguser['name'].") contained non int $onevar", E_USER_NOTICE);
						userban($loguser['id'], false , false, "", "The user was banned.");
						errorpage("Uh, nope");
					}
					$phide .= "<input type='hidden' name='c_merge[]' value='$onevar'>";
					$filteredstuff[] = $onevar;
					
				}
				
				// copied from minipostlist, slightly different query
					$new_check = $loguser['id'] ? "(p.time > n.user{$loguser['id']})" : "0";
					
					$posts = $sql->query("
						SELECT 	p.id, p.text, p.time, p.rev, p.user, p.deleted, p.thread, u.lastip ip, 1 nolayout, p.nohtml, p.nosmilies, p.lastedited,
								o.time rtime, NULL title, $userfields welpwelp, $new_check new
						FROM posts p
						LEFT JOIN users        u ON p.user   = u.id
						LEFT JOIN threads_read n ON p.thread = n.id
						LEFT JOIN posts_old    o ON o.time   = (SELECT MIN(o.time) FROM posts_old o WHERE o.pid = p.id)
						WHERE p.id IN (".implode(", ", $filteredstuff).") AND thread=$lookup
					");
					
					if (!$posts) // Assume someone has edited the checkboxes to point to posts IDs not in the thread
						errorpage("No.");
					
					$txt_mini = "<br><table class='main w'><tr><td colspan=2 class='dark'>Posts to move:</td></tr>";
					
					
					while($post = $sql->fetch($posts))
						$txt_mini .= threadpost($post, true);
					
				$txt_mini .= "</table>";
				//end of copy
			}
			
			pageheader("Move posts");
			
			print "
				<center><form method='POST' action='thread.php?id=$lookup&tmerge'><table class='main'>
					<tr><td class='head c'>Move Posts</td></tr>
					
					<tr><td class='light c'>Are you sure you want to move $whatposts to the thread with ID #".$_POST['moveto']."?<br>Thread Name: ".$sql->resultq("SELECT name FROM threads WHERE id = ".filter_int($_POST['moveto']))."</td></tr>
					<tr><td class='dim c'><input type='hidden' name='domerge'><input type='hidden' name='moveto' value=".$_POST['moveto'].">$phide<input type='submit' name='domerge2' value='Yes'> <input type='submit' name='return' value='No'></td></tr>
				
				</table></form></center>$txt_mini";
			
			pagefooter();			
		}

		$showmergecheckbox = true;
		$mergewhere = "
		<form method='POST' action='thread.php?id=$lookup&tmerge'>
			<table class='main w'>
				<tr><td class='head c' colspan=2>Check the posts you want to move, then enter the ID of the destination thread.</td></tr>
				<tr>
					<td class='light' style='min-width: 200px'>
						<nobr><input type='checkbox' name='a_merge' value=1> Move All Posts</nobr>
					</td>
					<td class='dim w'>
						Destination thread ID:<input type='text' name='moveto'> <input type='submit' name='domerge' value='Merge Posts'>
					</td>
				</tr>
			</table>";
	}
	else if ($ismod){
		// Stop this nonsense. No more delete/undelete refresh cycles.
		// Apply post actions that actually alter the db here and header() away
		
		if (isset($_GET['noob']) && !$thread['noob']){ // If the thread is forced in noob mode, don't do anything
			$sanityCheck = $sql->resultq("SELECT id FROM posts WHERE id = ".intval($_GET['noob'])." AND thread = $lookup");
			if ($sanityCheck){
				$sql->query("UPDATE posts SET noob = NOT noob WHERE id = ".intval($_GET['noob']));
			}
			header("Location: ?pid={$_GET['noob']}#{$_GET['noob']}");
		}				
		else if (isset($_GET['hide'])){ // Hide is actually delete. Delete is actually erase. CONSISTENCY!
			$sanityCheck = $sql->resultq("SELECT id FROM posts WHERE id = ".intval($_GET['hide'])." AND thread = $lookup");
			if ($sanityCheck){
				$sql->query("UPDATE posts SET deleted = NOT deleted WHERE id = ".intval($_GET['hide']));
			}
			header("Location: ?pid={$_GET['hide']}#{$_GET['hide']}");
		}

		else if (powlcheck(5) && filter_int($_GET['del'])){
			
			$_GET['del'] = intval($_GET['del']);
			// Deleting posts from the database by accident a good thing does not make. Added a confirmation prompt.
			
			if (isset($_POST['return']))
				header("Location: thread.php?pid={$_GET['del']}#{$_GET['del']}");
			
			else if (isset($_POST['dokill'])){
				//errorpage("{$_GET['del']} - {$lookup}");
				$sql->start();
				
				$sql->query("DELETE FROM posts WHERE id = ".$_GET['del']);
				$sql->query("DELETE FROM posts_old WHERE pid = ".$_GET['del']);
				
				// Note: Negative replies can't be reached here even if we delete the starting post, as a thread with zero real posts is automatically deleted.
				$sql->query("UPDATE threads SET replies=replies-1 WHERE id=$lookup");
				
				if (!$error_id)
					$sql->query("UPDATE forums SET posts=posts-1 WHERE id = ".$forum['id']);
				
				$sql->query("UPDATE misc SET posts=posts-1");
				$tmpUser = $sql->resultq("SELECT user FROM posts WHERE id = ".$_GET['del']);
				$sql->query("UPDATE users SET posts=posts-1 WHERE id = $tmpUser");
				
				if ($error_id != 3){
					// As deleting threads by accident is a BAD thing, actually count the real number of posts before doing anything fancy
					$realposts = $sql->resultq("SELECT COUNT(id) FROM posts WHERE thread = $lookup");
					if (!$realposts && !$error_id){// we have deleted the last post, delete the thread too
						$sql->query("DELETE FROM threads WHERE id = $lookup");
						$sql->query("UPDATE user SET threads = threads - 1 WHERE id = ".$thread['user']);
						update_last_post($forum['id'], false, true);
						$sql->end();
						header("Location: forum.php?id=".$forum['id']);
					}						
					else update_last_post($lookup); 
				}
				//update_last_post($lookup);
				$sql->end();
			}
			
			pageheader($thread['name']." - Delete Post");
			
			print "
				<center><form method='POST' action='thread.php?pid={$_GET['del']}&del={$_GET['del']}'><table class='main c'>
					<tr><td class='head'>Delete Post</td></tr>
					
					<tr><td class='light'>Are you sure you want to delete this post (ID #{$_GET['del']}) from thread (ID #{$thread['id']})?<br><small>This action is irreversible!</small></td></tr>
					<tr><td class='dim'><input type='submit' name='dokill' value='Yes'> <input type='submit' name='return' value='No'></td></tr>
				
				</table></form></center>";
			pagefooter();
		}	
	}
	
	pageheader($thread['name'], true, $forum['id']);
	
	$newreply_txt = ($loguser['id'] && $loguser['powerlevel']>=0 && (!$miscdata['noposts'] || powlcheck(4))) ? "<a href='new.php?act=newpoll&id=".$forum['id']."'><img src='images/text/newpoll.png'></a> - <a href='new.php?act=newthread&id=".$forum['id']."'><img src='images/text/newthread.png'></a> - <a href='new.php?act=newreply&id=$lookup'><img src='images/text/newreply.png'></a>" : "";
	
	if ($thread['closed']){
		$newreply = "<img src='images/text/threadclosed.png'>";
		if ($ismod) $newreply .= " - $newreply_txt";
	}
	else $newreply = $newreply_txt;
	
	print "<table class='main w fonts'><tr><td class='light c'>".onlineusers($forum['id'])."</td></tr></table>
	
	<table class='w'><tr><td class='w'><a href='index.php'>".$config['board-name']."</a> - <a href='forum.php?id=".$forum['id']."'>".$forum['name']."</a> - ".htmlspecialchars($thread['name'])."</td><td>&nbsp;</td><td style='text align: right'><nobr>$newreply</nobr></td></tr></table>";

	
	// Mod Thread Controls
	$killthread = powlcheck(5) ? "| <a class='danger' href='thread.php?id=$lookup&tkill'>Erase</a>" : "";
	
	// better prevent from clicking than fool moderators
	$w = $error_id == 3 ? "s" : "a";
	if ($ismod)
		print "<br><table class='main w fonts'>
					<tr>
						<td class='dark'>
							Moderating options:
							".($thread['ispoll'] ? "<$w href='new.php?id=$lookup&act=editpoll'>Edit poll</$w>" : "<$w href='thread.php?id=$lookup&tren'>Rename</$w>")." |
							<$w href='thread.php?id=$lookup&ticon'>Change icon</$w> |
							<$w href='thread.php?id=$lookup&tmove'>Move</$w> |
							<$w href='thread.php?id=$lookup&tstick'>".($thread['sticky'] ? "Uns" : "S")."tick</$w> |
							<$w href='thread.php?id=$lookup&tclose'>".($thread['closed'] ? "Open" : "Close")."</$w> |
							<$w href='thread.php?id=$lookup&tnoob'>". ($thread['noob'] ? "Un" : "N")."00b</$w> |
							".($forum['id']==$config['trash-id'] ? "" : "<$w href='thread.php?id=$lookup&ttrash'>Trash</$w> |")."
							<a href='thread.php?id=$lookup&tmerge'>Merge</a>
							$killthread
						</td>
					</tr>
				</table>$mergewhere
				";
	else if ($loguser['id'] == $thread['user'])
		print "<br><table class='main w fonts'><tr><td class='dark'>
				Thread options:
				<a href='thread.php?id=$lookup&tren'>Rename</a> |
				<a href='thread.php?id=$lookup&ticon'>Change icon</a>
				</td></tr></table>
				";
	
	// Massive query to fetch almost everything threadpost needs
	$skiplayout = $loguser['showhead'] ? "" : "NULL"; // Set header and signature as blank if they are disabled
	
	$new_check = $loguser['id'] ? "(p.time > n.user{$loguser['id']})" : "0";
	
	$posts = $sql->query("
		SELECT 	p.id, p.text, p.time, p.rev, p.user, p.deleted, p.thread, p.nohtml, p.nosmilies, p.nolayout, p.avatar, o.time rtime,
				p.lastedited, ".($thread['noob'] ? "1 " : "p.")."noob, $new_check new,
				$skiplayout u.head, $skiplayout u.sign, u.lastip ip, u.title, $userfields temp, u.posts, u.since, u.location, u.lastview, u.lastpost, u.rankset
		FROM posts AS p
		
		LEFT JOIN users        u ON p.user   = u.id
		LEFT JOIN posts_old    o ON o.time   = (SELECT MIN(o.time) FROM posts_old o WHERE o.pid = p.id)
		LEFT JOIN threads_read n ON p.thread = n.id
		
		WHERE p.thread = $lookup
		ORDER BY p.id ASC
		LIMIT ".(filter_int($_GET['page'])*$loguser['ppp']).", ".$loguser['ppp']."
	");// offset, limit
	

	if ($posts){
		
		$postids = getpostcount($lookup);
			
		// Page numbers
		$pagectrl = dopagelist($thread['replies']+1, $loguser['ppp'], "thread", $showmergecheckbox ? "&tmerge" : "");

		print $pagectrl;
		
		if ($thread['ispoll'])
			print poll_print($thread['polldata']);
		
		while ($post = $sql->fetch($posts)){

			$post['postcur'] = array_search($post['id'], $postids[$post['user']])+1;
			
			if ($ismod){
				// Mod post actions with no alteration to the database, making them refresh safe.
				// Pin post (unhides)
				if (filter_int($_GET['pin']) == $post['id'])
					$post['deleted'] = false;
				
				// Get old version of post
				if (isset($_GET['rev']) && filter_int($_GET['pid'])==$post['id'])
					$post = array_replace($post, $sql->fetchq("SELECT text,rev crev,time,nohtml,nosmilies,nolayout,avatar FROM posts_old  WHERE pid = ".$post['id']." AND rev = ".filter_int($_GET['rev'])));
			}
			
			// To enable updating the last view date
			if ($post['new']) $set = true;

			print threadpost($post, false, $showmergecheckbox);

		}
		print $pagectrl;
		
		if ($showmergecheckbox)
			print "</form>";
		
		if (isset($set))
			$sql->query("UPDATE threads_read SET user{$loguser['id']} = ".ctime()." WHERE id = $lookup");
	}
	// This "thread is empty" message doesn't display anymore due to the code considering threads with 0 posts invalid
	// (and if the last post in a thread is deleted, the thread is erased automatically anyway)
	else print "
		<center><table class='main c'>
			<tr><td class='light'>
				The thread is empty. There are no posts to show.<br>To create a new post, click New Reply.
			</td></tr>
		</table></center></form>
		";
	

	/*if ($loguser['id']) */print "
	<table class='w'><tr>
		<td>".doforumjump($forum['id'])."</td>
		<td style='text-align: right;'>$newreply</td></tr>
	</table>";
	
	pagefooter();

?>