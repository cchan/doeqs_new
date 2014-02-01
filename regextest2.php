<?php
header('HTTP/1.0 404 Not Found');die();

require_once 'functions.php';

$qp=new qParser;

$qp->parse(file_get_contents("..."));//tbd. Except don't access files outside of protected object.
?>