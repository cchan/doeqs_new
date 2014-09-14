<?php
define('ROOT_PATH','/');//Need it so that templateify will still have the right CSS path even if it's a 404 in a subfolder.
require_once 'functions.php';//For some reason "/functions.php" doesn't work.
die();//Trigger templateify to not recognize "404.php", and so display the expected 404.
?>