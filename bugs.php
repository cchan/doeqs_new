<?php
define('ROOT_PATH','');
require_once ROOT_PATH.'functions.php';

if(posted("bug")){
	logfile('bugs',POST("bug"));
	echo "<pre>".htmlentities($bug)."</pre><br>We got your bug! Thanks!";
}
else{
	echo '<p>Hi, this is the bug-processing page! You can submit a bug or feature request up there in the upper-right corner. Thanks!</p>';
	echo '<p>Or, you can send it to me directly at <a href="mailto:'.$WEBMASTER_EMAIL.'">'.$WEBMASTER_EMAIL.'</a> if even the bug reporting isn\'t working.';
}
?>