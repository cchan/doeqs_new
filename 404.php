<?php
#Place at root.
define('ROOT_PATH',__DIR__);//I don't know why this is so annoying.
require_once ROOT_PATH.'/functions.php';
echo '<h1>Error 404 Not Found</h1>'.get404();//Templateify will override this.
die();
?>