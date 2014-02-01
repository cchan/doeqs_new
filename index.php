<?php
define('ROOT_PATH','');
require_once ROOT_PATH.'functions.php';
/*
index.php
Homepage.
*/
?>
<br>
This is <b>DOE Question Database version <?php echo $VERSION_NUMBER;?></b>!
<br>
<br>
<div><b>Help!</b> As you can see, this is quite a plain website. Does anyone want to help with HTML/CSS?</div>
<br>
<br>
<i>Feel free to put questions in the database, but keep a copy for yourself just in case something goes wrong over here.</i>
<br>
<?=database_stats();?>