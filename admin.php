<?php
define('ROOT_PATH','');
require_once ROOT_PATH.'functions.php';
restrictAccess('a');//xuca

//separate face of this page: "Are you sure?"
//echo $_SESSION["admin-ver"]=genRandStr();
//if($_POST["admin-ver"]===$_SESSION["admin-ver"])

//for particularly dangerous ones "Reenter password to do this action"
echo '<b style="color:green">';
if(csrfVerify()){
	if(isSet($_POST["logout"])){
		session_total_reset();
		die("logged out");
	}
	elseif(isSet($_POST["truncQs"])){
		$database->query("TRUNCATE TABLE questions");
		alert("TRUNCATE TABLE executed.<br><br>",1);
	}
	elseif(isSet($_POST["timesViewed"])){
		$database->query("UPDATE questions SET TimesViewed=0");
		alert("All questions' times-viewed-s zeroed.<br><br>",1);
	}
	elseif(isSet($_POST["markBad"])){
		$database->query("UPDATE questions SET MarkBad=0");
		alert("All questions' marked-as-bad-s zeroed.<br><br>",1);
	}
	elseif(isSet($_POST["optimizeTables"])){
		$database->query("OPTIMIZE TABLE users,questions");
		alert("OPTIMIZE TABLE executed<br><br>",1);
	}
	elseif(isSet($_POST["qInt"])){
	//Subject in {0,1,2,3,4}
	//isB and isSA in {0,1}
	//Question not blank or null
	//MCs exist for all isSA=1
	//Answer in {0,1,2,3} for isSA=1, not blank or null for isSA=0
	//Rating within reason (not below -3, since it won't even appear then)
	//TimesViewed positive, within reason
	//TimestampEntered within reason
	}
	elseif(isSet($_POST["setStandard"])){
	//Modifies the database entry checked against for file integrity test.
	}
	elseif(isSet($_POST["fileInt"])){
	//checks existence and sizes of all files
	//verifies nonexistence of any other files
	}
}
echo '</b>';
	
$filesTotalSize=dirsize(__DIR__);

?>
<form action="admin.php" method="POST">
	<input type="hidden" name='ver' value="<?=csrfCode()?>"/>
	<fieldset>
		<legend>Database</legend>
		<fieldset>
			<legend>Users</legend>
			<?php 
				$q=$database->query_assoc('SELECT COUNT(*) AS n FROM users');
				echo "<div>Number of users in database: <b>{$q['n']}</b></div>";
			?>
		</fieldset>
		<fieldset>
			<legend>Questions</legend>
			<?=database_stats();?>
			<?php //Do a separate CONFIRM page ?>
			<input type="submit" name="truncQs" value="Delete All Questions" class="confirm"/><br>
			<input type="submit" name="timesViewed" value="Reset TimesVieweds" class="confirm"/><br>
			<input type="submit" name="markBad" value="Reset Marked-As-Bad's" class="confirm"/><br>
			<input type="submit" name="qInt" value="Integrity Check" disabled/>
		</fieldset>
		<input type="submit" name="optimizeTables" value="Optimize Tables"/><br>
		<a href="<?=$PMA_LINK?>">PHPMyAdmin</a>
	</fieldset>
	<fieldset>
		<legend>Server Files</legend>
		<div>Total size: <?php echo $filesTotalSize;?> bytes</div>
		<input type="submit" name="setStandard" value="Set current state as integrity check standard" disabled/>
		<input type="submit" name="fileInt" value="Files Integrity Check" disabled/><br>
	</fieldset>
	<input type="submit" name="logout" value="Logout"/><br>
	</form>
	<script type="text/javascript">
	var c=document.getElementsByClassName("confirm");
	for(var i=0;i<c.length;i++)c[i].onclick=function(){return confirm('Are you sure you want to "'+this.value+'"?');}
	</script>
</form>