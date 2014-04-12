<?php
/*
functions.php
Any useful functions, and lots of includes. Main codebase.

//--todo-- make consistent 404s

INCLUSION NOTE:
At the top of each USER-ACCESSIBLE file
	define('ROOT_PATH','');
	require_once ROOT_PATH.'functions.php';
	restrictAccess('x');//xuca
[restrictAccess is optional if any-accessible]
At the top of each NON USER-ACCESSIBLE INCLUDEABLE file
*/	if(!defined('ROOT_PATH')){header('HTTP/1.0 404 Not Found');die();}/*
At the top of each PRIVATE file
	header('HTTP/1.0 404 Not Found');die();

USEFUL TIPS LIST:
'\n' is just slash n; "\n" is newline.
.htaccess files MUST have a newline at the end to work. For the matter, all files should. It just makes things work more properly.
Never, ever, ever, EVER trust $_SERVER['REQUEST_URI']. Only for creating links, and extremely carefully for 
	-e.g. counting the number of slashes to see how many directories you have to go up, using that for ROOT_PATH construction,
	what if the attacker inserts extra random meaningless slashes, and designs the URL so that it accesses some system file?
	And then we end up accessing and dumping the file? Well OOPS.
Root path is horrible to determine dynamically.
Before you do anything, READ config.php and its comments. Preferably also this file and its comments.
*/

/****************LOGGING*******************/
function logfile($file,$str=NULL){
	$file=preg_replace("/[^A-Z0-9]/i",'',stripslashes(strval($file)));
	
	if($str!==NULL)$str=str_replace(["\n\n","\r\n\r\n"],"\r\n",strval($str)).' -- ';
	
	$log=$str.$_SERVER['REMOTE_ADDR'].' '.date('l, F j, Y h:i:s A').' '.$_SERVER['REQUEST_URI']."\r\n\r\n";
	file_put_contents(__DIR__.'/logs/'.$file.'.log',$log,FILE_APPEND);
}
logfile('req','Request');


/**************DOWNTIME****************/
if(isSet($SERVER_DOWN)&&$SERVER_DOWN===true){
	header("HTTP/1.0 418 I'm a teapot");
	echo "<h1>Error <a href='http://tools.ietf.org/html/rfc2324#section-2.3.2'>418 I'm a teapot</a></h1>";
	echo "<p>In other words, the server went crazy and we're fixing it. Check back in a moment to see if it's back.</p>";
	echo "<p>Thanks for your patience, and we hope to be back soon!</p>";
	echo "<p>-DOEQs Dev Team</p>";
	die();
}
function get404(){
	header("HTTP/1.0 404 Not Found");
	$ru=basename($_SERVER['REQUEST_URI']);
	$str=<<<HEREDOC
<p>Oops, the page <a href="{$_SERVER['REQUEST_URI']}">$ru</a> wasn't found.</p>
<p>If you typed the address, check that it's entered correctly.</p>
<p>Otherwise, you can try waiting a bit then reloading the page.</p>
<p>-DOEQs Dev Team</p>
HEREDOC;
	logfile('err','404 Not Found');
	return $str;
}


/****************INCLUDES******************/
require_once 'conf/config.php';//Config.
require_once 'classes/class.DB.php';//Safe, consistent (MySQL) databasing.
$database=new DB($DB_SERVER,$DB_USERNAME,$DB_PASSWORD,$DB_DATABASE);//Surprisingly, it's faster if we load it every page.
require_once 'accountManagement.php';//Account and session management.
//also: DB, qIO, fileToStr, qParser, etc.
function require_class(){
	foreach(func_get_args() as $class_name)
		require_once 'classes/class.'.stripslashes($class_name).'.php';
}

/******************FILES*******************/
/*
 * dirsize($path)
 *
 * Calculate the size of a directory by iterating its contents
 *
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.2.0
 * @link        http://aidanlister.com/2004/04/calculating-a-directories-size-in-php/
 * @param       string   $directory    Path to directory
 */
function dirsize($path)
{
    // Init
    $size = 0;

    // Trailing slash
    if (substr($path, -1, 1) !== DIRECTORY_SEPARATOR) {
        $path .= DIRECTORY_SEPARATOR;
    }

    // Sanity check
    if (is_file($path)) {
        return filesize($path);
    } elseif (!is_dir($path)) {
        return false;
    }

    // Iterate queue
    $queue = array($path);
    for ($i = 0, $j = count($queue); $i < $j; ++$i)
    {
        // Open directory
        $parent = $i;
        if (is_dir($queue[$i]) && $dir = @dir($queue[$i])) {
            $subdirs = array();
            while (false !== ($entry = $dir->read())) {
                // Skip pointers
                if ($entry == '.' || $entry == '..') {
                    continue;
                }

                // Get list of directories or filesizes
                $path = $queue[$i] . $entry;
                if (is_dir($path)) {
                    $path .= DIRECTORY_SEPARATOR;
                    $subdirs[] = $path;
                } elseif (is_file($path)) {
                    $size += filesize($path);
                }
            }

            // Add subdirectories to start of queue
            unset($queue[0]);
            $queue = array_merge($subdirs, $queue);

            // Recalculate stack size
            $i = -1;
            $j = count($queue);

            // Clean up
            $dir->close();
            unset($dir);
        }
    }

    return $size;
}


/****************INTEGERS****************/
function val_int($n){//Validates that it's an integer
	if(!is_numeric($n)||intval($n)!=$n)
		return false;
	return true;
}
function normRange($n,$a,$b){//Normalizes $n to the range [$a,$b] (if it's smaller than $a, $a; if it's larger than $b, $b; otherwise $n same.)
	$n=intval($n);
	if($a>$b)err('normRange: invalid range');
	if($n<$a)return $a;
	if($n>$b)return $b;
	return $n;
}

/******************ARRAYS*****************/
function anyIndicesEmpty($array/*, var1, var2, ...,varN*/){//it's NOT anyIndicesNull. '' is empty.
	$args=func_get_args();
	array_shift($args);//shift off the $array one
	foreach($args as $arg)
		if(!array_key_exists($arg,$array)||empty($array[$arg])/*&&$array[$arg]==='0'*/)return true;
	return false;
}
function randomizeArr($arr){//Randomly permute an array - yes, it works! in what amounts to O(n)!
//realization several months later... this is exactly the Fisher-Yates shuffle. But I discovered it myself, hmph!
	for($i=count($arr)-1;$i>0;$i--){
		$ind=mt_rand(0,$i);//Get the index of the one to swap with.
		$tmp=$arr[$ind];$arr[$ind]=$arr[$i];$arr[$i]=$tmp;//Swap with the last one.
	}
	return $arr;
}
function arrayToRanges($arr){//Converts [1,2,3,5,6,8,9,10] to the human-readable "1-3, 5-6, 8-10"
	if(count($arr)==0)return '';
	if(count($arr)==1)return $arr[0];
	sort($arr);
	$string='';
	$string.=$arr[0];
	for($i=1;$i < count($arr);$i++){
		if($arr[$i] > $arr[$i-1]+1){
			if($i>=2&&$arr[$i-1]==$arr[$i-2]+1)$string.=$arr[$i-1];
				$string.=', '.$arr[$i];
		}
		elseif($arr[$i]==$arr[$i-1]+1&&($i<2||$arr[$i]>$arr[$i-2]+2))$string.='-';
	}
	if($arr[count($arr)-1]==$arr[count($arr)-2]+1)$string.=$arr[count($arr)-1];
	return $string;
}
function Array2DTranspose($arr){//Transposes a 2d array (aka flipping x and y; aka flipping around its primary axis)
    $out = array();
    foreach ($arr as $key => $subarr)
		foreach ($subarr as $subkey => $subvalue)
			$out[$subkey][$key] = $subvalue;
    return $out;
}

/***************HTTP Data Existence*******************/
function posted(){
	$args=func_get_args();
	foreach($args as $arg)
		if(!array_key_exists($arg,$_POST))return false;
	return true;
}
function getted(){
	$args=func_get_args();
	foreach($args as $arg)
		if(!array_key_exists($arg,$_GET))return false;
	return true;
}
function sessioned(){
	$args=func_get_args();
	foreach($args as $arg)
		if(!array_key_exists($arg,$_SESSION))return false;
	return true;
}
function ifpost($n){
	if(posted($n))return htmlentities($_POST[$n]);
	else return '';
}

/**********************PAGE GENERATION*************************/
//Upon shutdown, templateify() will run, emptying the output buffer into a page template and then sending *that* instead.
ob_start();
$TIME_START=microtime(true);
function templateify(){
	global $CANCEL_TEMPLATEIFY;//In case, for example, you want to send an attachment through this page.
	if(@$CANCEL_TEMPLATEIFY)return;
	
	global $pagesTitles,$hiddenPagesTitles,$adminPagesTitles;
	
	$pagename=basename($_SERVER['REQUEST_URI'],'.php');//--TODO-- needs to be full relative paths - e.g. classes/about.php not 404
		//likewise, links in navbar must be absolute or relative to ROOT_PATH
	if($pagename==''||$pagename=='doeqs_new')$pagename='index';
	if(array_key_exists($pagename,$pagesTitles)){
		$title=$pagesTitles[$pagename];
		$content=ob_get_clean();
	}
	elseif(array_key_exists($pagename,$hiddenPagesTitles)){
		$title=$hiddenPagesTitles[$pagename];
		$content=ob_get_clean();
	}
	elseif(array_key_exists($pagename,$adminPagesTitles)&&userAccess('a')){
		$title=$adminPagesTitles[$pagename].' [Admin-Only Page]';
		$content=ob_get_clean();
	}
	else{
		$title='Error 404 Not Found';
		$content=get404();
		ob_clean();
	}
	$content=fetch_alerts_html().$content;
	
	$nav='[';
	foreach($pagesTitles as $p=>$t)
		$nav.="&nbsp;&middot;&nbsp;<a href='$p.php'>$t</a>";
	if(userAccess('a')){
		$nav.='&nbsp;&mdash;&nbsp;';
		foreach($adminPagesTitles as $p=>$t)
			$nav.="<a href='/$p.php'>$t</a>";
	}
	$nav.='&nbsp;&middot;&nbsp;]';
	if(userAccess('u'))$nav.='&nbsp;&nbsp;&nbsp;<form action="login.php" method="POST" style="display:inline-block;"><input type="hidden" name="ver" value="<?=csrfCode();?>"/><input type="submit" name="logout" value="Log Out" /></form>';

	
	//tried OB to get file contents which died for some reason...
	$template=file_get_contents(__DIR__.'/html_template.php');//--todo-- don't access files outside of protected object
	
	global $VERSION_NUMBER,$TIME_START;
	echo str_replace(['%title%','%content%','%nav%','%version%','%loadtime%','%root%'],[$title,$content,$nav,$VERSION_NUMBER,substr(1000*(microtime(true)-$TIME_START),0,6),ROOT_PATH],$template);
	ob_flush();
	flush();
}
register_shutdown_function('templateify');


/**********************ERROR HANDLING**********************/
//Shorthand function for trigger_error.
function err($description){
	trigger_error($description,E_USER_ERROR);
}
//Error-handler function.
function error_catcher($errno,$errstr,$errfile,$errline){
	global $DEBUG_MODE;
	ob_start();
	debug_print_backtrace();
	$backtrace=str_replace("\n","\r\n",ob_get_clean());
	$err="Error #$errno: '$errstr' at line $errline of file $errfile. Debug Backtrace:\r\n$backtrace\r\n";
	
	logfile('err',$err);
	
	//Printing out
	if($DEBUG_MODE){
		echo "An error occurred:<br><pre>$err</pre><br>(logged as above)";
	}
	else{
		ob_clean();//Shh, nothing happened!
		echo 'An error occurred!';
	}
	
	die();//Either way, an error should not let it go on executing.
	//If you want to have errors within classes, implement a class error-catching system yourself and output it to the user that way. Preferably through alerts.
}
set_error_handler('error_catcher', E_ALL);
ini_set('error_reporting',E_ALL);
error_reporting(E_ALL);
//Strict: No notices allowed, either.


/*******************ALERTS*********************/
//Also assumes that templateify() will add it in via fetch_alerts_html()
//Call this to add an alert to be displayed at the top.
//Text: the alert text
//Disposition: negative means bad (red), positive means good (green), zero means neutral (black)
function alert($text,$disposition=0,$page_name=NULL){
	//Check that it's a valid page.
	
	if(is_null($page_name))
		$page_name=basename($_SERVER['REQUEST_URI']);
	$sp='alerts_'.$page_name;
	
	if(!sessioned($sp))$_SESSION[$sp]=array();
	$_SESSION[$sp][]=[$text,$disposition];
}
function fetch_alerts_html(){
	$page_name=basename($_SERVER['REQUEST_URI']);
	$sp='alerts_'.$page_name;
	
	$html='';
	
	if(sessioned($sp)){
		foreach($_SESSION[$sp] as $alert){
			if($alert[1]>0)$disposition='pos';
			else if($alert[1]<0)$disposition='neg';
			else $disposition='neut';
			$html.="<div class='alert_{$disposition}'>{$alert[0]}</div>";
		}
		unset($_SESSION[$sp]);
	}
	
	return $html;
}


/***********************MISC************************/
function database_stats(){//Returns the database statistics as an HTML string.
	//Note: huh hm try caching? Time the slowest parts of the code.
	
	global $database,$ruleSet;
	$ret='<div>Question Database Stats:';
	$totalN=0;
	$q=$database->query('SELECT Subject, COUNT(*) AS nQs FROM questions WHERE Deleted=0 GROUP BY Subject');
	
	while($r=$q->fetch_assoc()){
		$totalN+=$r['nQs'];
		$ret.="<br>{$ruleSet['Subjects'][$r['Subject']]}: <b>".($r['nQs']).'</b>';
	}
	$ret.="<br>Total: <b>$totalN</b>";
	$ret.='</div>';
	return $ret;
}

function sendfile($contenttype,$ext,$content){
	header("Content-type: $contenttype");
	header('Content-disposition: attachment; filename="Export'.substr(hash('SHA1',mt_rand()),0,16).'.'.$ext.'"');
	echo $content;
	
	global $CANCEL_TEMPLATEIFY;
	$CANCEL_TEMPLATEIFY=true;
	die();
}

?>