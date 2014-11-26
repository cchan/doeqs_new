<?php
/*
functions.php
Any useful functions, and lots of includes. Main codebase.

INCLUSION NOTE:
At the top of each USER-ACCESSIBLE file
	define('ROOT_PATH',''); //including final slashes, except when empty. (e.g. classes/, ../)
	require_once ROOT_PATH.'functions.php';
	restrictAccess('x');//xuca

USEFUL TIPS LIST:
'\n' is just slash n; "\n" is newline.
.htaccess files MUST have a newline at the end to work. For the matter, all files should. It just makes things work more properly.
Never, ever, ever, EVER trust $_SERVER['REQUEST_URI']. Only for creating links, and extremely carefully for 
	-e.g. counting the number of slashes to see how many directories you have to go up, using that for ROOT_PATH construction,
	what if the attacker inserts extra random meaningless slashes, and designs the URL so that it accesses some system file?
	And then we end up accessing and dumping the file? Well OOPS.
Root path is horrible to determine dynamically.
Before you do anything, READ config.php and its comments. Preferably also this file and its comments.
:( if no error shows up it might be that you're require-ing a file that doesn't exist, or which is having a parse error. Check for parse errors at http://www.piliapp.com/php-syntax-check/
Note that most versions of PHP (as of now) don't support [1,2,3] array literals. You must use array(1,2,3).
*/

require_once 'conf/config.php';//Config.

require_once 'classes/Mustache/Autoloader.php';
Mustache_Autoloader::register();

require_once 'classes/meekrodb.2.3.class.php';//Precisely just a more complex and secure version of my own DB class :(
DB::$host = $DB_SERVER;
DB::$user = $DB_USERNAME;
DB::$password = $DB_PASSWORD;
DB::$dbName = $DB_DATABASE;
//DB::$throw_exception_on_error=true;DB::$throw_exception_on_nonsql_error=true;

function SQLRAND($primary_key = 0){//Replaces SQL's terrible RAND function. Does it have enough entropy?
										//$primary_key is the name of the unique column in the table.
	//Recommendation:	NEWID is for generating unique values, not for randomness. I think that's good enough.
	//					RAND is just not random enough, plus it only executes once per query I think (O_o)
	//					The primary key is guaranteed to be unique, so that's a reassurance.
	//					mt_rand() is actually a good generator, but it doesn't generate new values;
	//						i.e. the value is concatenated in PHP, so in SQL it will be always the same during sorting.
	//						so it amounts to a salt right now.
	//				And SHA1 just mixes it all together, and CONV makes it usable for sorting.
	//				[MySQL seems to always use BIGINTs in arithmetic, so nothing should overflow.]
	//It's slower but since question shuffling is the most important use of randomness in the system, it MUST work effectively.
	return " SHA1(UUID()+RAND()+".$primary_key."+".mt_rand().") ";
}

require_once 'accountManagement.php';//Account and session management.

//qIO, fileToStr, qParser, etc.
function require_class(){
	foreach(func_get_args() as $class_name)
		if(val('f',$class_name)&&file_exists(ROOT_PATH.'classes/class.'.$class_name.'.php'))
			{require_once ROOT_PATH.'classes/class.'.$class_name.'.php';}
		else
			err('bad Require');
}

/**************DOWNTIME****************/
if(isSet($SERVER_DOWN)&&$SERVER_DOWN===true){
	header("HTTP/1.0 418 I'm a teapot");
	echo <<<HEREDOC
<h1>Error <a href='http://tools.ietf.org/html/rfc2324#section-2.3.2'>418 I'm a teapot</a></h1>";
echo "<p>In other words, the server went crazy and we're fixing it. Check back in a moment to see if it's back.</p>";
echo "<p>Thanks for your patience, and we hope it'll be working again soon!</p>";
echo "<p>-DOEQs Dev Team</p>
HEREDOC;
	die();
}

/****************LOGGING*******************/
function logfile($file,$str=NULL){
	if(!val('f',$file)){err_dev('Invalid log file name.');return false;}
	
	if(val('s',$str))$str=str_replace(array("\n\n","\r\n\r\n"),"\r\n",strval($str)).' -- ';
	else $str = '';
	
	$log=$str.$_SERVER['REMOTE_ADDR'].' '.date('l, F j, Y h:i:s A').' '.$_SERVER['REQUEST_URI']."\r\n\r\n";
	file_put_contents(__DIR__.'/logs/'.$file.'.log',$log,FILE_APPEND);
	return true;
}
logfile('req','Request');

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


/****************DATA VALIDATION****************/
function val($type /*,$x1,$x2,...*/){
	$args=func_get_args();
	array_shift($args);
	if(!count($args)){
		err_dev('val(): Nothing to validate.');
		return false;
	}
	
	if(is_string($type)&&strpos($type,',')!==false)$type=explode(',',$type);
	
	if(is_array($type)){//MULTIPLE TYPES, MULTIPLE VALIDATEES
		if(count($type)!=count($args)){err_dev('val(): #types != #validatees');return false;}
		foreach($args as $arg)
			if(!val(array_shift($type),$arg))
				return false;
		return true;
	}
	elseif(is_string($type)&&count($args)>1){//SINGLE TYPE, MULTIPLE VALIDATEES
		foreach($args as $arg)
			if(!val($type,$arg))
				return false;
		return true;
	}
	elseif(!is_string($type)){err_dev('val(): $type neither string nor array');return false;}//Invalid $type.
	
	$x=$args[0];
	
	if(substr($type,0,1)=='*'){//SINGLE ARRAY-TYPE, TO VALIDATE A SINGLE ARRAY VALIDATEE
		//e.g. "*i" validates an array of integers, **i validates an array of arrays of integers
		if(!is_array($x))return false;
		if($type=='*')return true;
		foreach($x as $xi)
			if(!val(substr($type,1),$xi))return false;
		return true;
	}
	
	//SINGLE TYPE, SINGLE VALIDATEE
	switch($type){//None of these can begin with '*'
		case 's':case 'string':		return is_string($x);
		
		case 'i-':	return is_int($x) && $x < 0;
		case 'i0-':	return is_int($x) && $x <= 0;
		case 'i':	return is_int($x);
		case 'i0+':	return is_int($x) && $x >= 0;
		case 'i+':	return is_int($x) && $x > 0;
		
		case 'num':	return is_numeric($x);
		
		case 'aln':	return is_string($x) && ctype_alnum($x);
		case 'abc':	return is_string($x) && ctype_alpha($x);
		
		case 'f':case 'file':		return is_string($x)
										&& preg_match('/^[A-Za-z0-9]([A-Za-z0-9\_\-\.]+[A-Za-z0-9])?$/i',$x)
										&& !strpos($x,'..');
		
		default:					err_dev('val(): Invalid validation type.');return false;
	}
}

function inRange($n,$a,$b){//Checks if $n is in range [$a,$b].
	if(!val('i',$n))return NULL;
	if($a>$b)err('inRange: invalid range');
	if($n<$a)return -1;
	if($n>$b)return 1;
	return 0;
}
function normRange($n,$a,$b,$default=NULL){//Normalizes $n to the range [$a,$b]; if $n is invalid it sets to $default.
	$i=inRange($n,$a,$b);
	if($i===NULL)return $default;
	if($i===-1)return $a;
	if($i===1)return $b;
	return $n;
}

function arrayKeysExist($ARR,$indices){
	if(!val('*',$ARR))return false;
	foreach($indices as $index)
		if(!array_key_exists($index,$ARR))return false;
	return true;
}
function arrayKeysNotEmpty($ARR,$indices){//'' is empty - however, empty() takes 0 and '0' as empty :(
	if(!val('*',$ARR))return false;
	foreach($indices as $index)
		if(!array_key_exists($index,$ARR)||is_null($ARR[$index])||$ARR[$index]==='')return false;
	return true;
}
//--todo--Naming conventions... parametric conventions...
function anyIndicesEmpty($array/*, var1, var2, ...,varN*/){//it's NOT anyIndicesEmpty. '' is empty.
	$args=func_get_args();
	array_shift($args);
	foreach($args as $arg)
		if(!array_key_exists($arg,$array)||empty($array[$arg])/*&&$array[$arg]==='0'*/)return true;
	return false;
}

/****************ARRAY OPERATIONS*****************/
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
function Array2DTranspose($arr){//Transposes a 2d array (aka flipping x and y)
    $out = array();
    foreach ($arr as $key => $subarr)
		foreach ($subarr as $subkey => $subvalue)
			$out[$subkey][$key] = $subvalue;
    return $out;
}

/***************HTTP Data Exist/Get*******************/
//:( Always gonna be string type anyway...
function posted(){return arrayKeysExist($_POST,func_get_args());}
function POST($index){if(posted($index))return $_POST[$index];else return NULL;}

function getted(){return arrayKeysExist($_GET,func_get_args());}
function GET($index){if(getted($index))return $_GET[$index];else return NULL;}

function sessioned(){return arrayKeysExist($_SESSION,func_get_args());}
function SESSION($index){if(sessioned($index))return $_SESSION[$index];else return NULL;}

//Note: never trust REQUEST_URI or stuff like that. Can be spoofed.
function servered(){return arrayKeysExist($_SERVER,func_get_args());}
function SERVER($index){if(servered($index))return $_SERVER[$index];else return NULL;}

function filed(){return arrayKeysExist($_FILES,func_get_args());}
function FILES($index){if(filed($index))return $_FILE[$index];else return NULL;}

/**********************PAGE GENERATION*************************/
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

//Upon shutdown, templateify() will run, emptying the output buffer into a page template and then sending *that* instead.
ob_start();//Collect EVERYTHING that's outputted.
$TIME_START=microtime(true);//For page load time measurement. --todo-- log this?
function templateify(){
	global $CANCEL_TEMPLATEIFY;//In case, for example, you want to send an attachment.
	if(@$CANCEL_TEMPLATEIFY)return;
	
	global $pagesTitles,$hiddenPagesTitles,$adminPagesTitles;
	
	$pagename=basename($_SERVER['REQUEST_URI'],'.php');
	//--TODO-- needs to be full relative paths - e.g. "classes/about.php" gets About.
		//likewise, links in navbar must be absolute or relative to ROOT_PATH
	
	if(!val('f',$pagename))$pagename='404';
	
	//Make this consistent with the _actual_ 404s with htaccess ("foafi/dshiafos.php")
	if($pagename==''||$pagename=='doeqs_new')$pagename='index';//--todo-- hax
	if(array_key_exists($pagename,$pagesTitles)){
		$title=$pagesTitles[$pagename];
		$content=ob_get_clean();
	}
	elseif(array_key_exists($pagename,$hiddenPagesTitles)){
		$title=$hiddenPagesTitles[$pagename];
		$content=ob_get_clean();
	}
	elseif(array_key_exists($pagename,$adminPagesTitles)&&userAccess('a')){
		$title=$adminPagesTitles[$pagename].' <i>[Admin-Only Page]</i>';
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
		$nav.="&nbsp;&middot;&nbsp;<a href='".ROOT_PATH."$p.php'>$t</a>";
	if(userAccess('a')){
		$nav.='&nbsp;&mdash;&nbsp;';
		foreach($adminPagesTitles as $p=>$t)
			$nav.="<a href='".ROOT_PATH."$p.php'>$t</a>";
	}
	$nav.='&nbsp;&middot;&nbsp;]';
	if(userAccess('u'))$nav.='&nbsp;&nbsp;&nbsp;<form action="login.php" method="POST" style="display:inline-block;"><input type="hidden" name="ver" value="<?=csrfCode();?>"/><input type="submit" name="logout" value="Log Out" /></form>';
	
	//tried OB to get file contents which died for some reason...
	$template=file_get_contents(__DIR__.'/html_template.html');//--todo-- don't access files outside of protected object
	
	global $VERSION_NUMBER,$TIME_START;
	echo str_replace(array('%title%','%content%','%nav%','%version%','%loadtime%','%root%'),array($title,$content,$nav,$VERSION_NUMBER,substr(1000*(microtime(true)-$TIME_START),0,6),ROOT_PATH),$template);
	ob_flush();
	flush();
}
register_shutdown_function('templateify');

$CANCEL_TEMPLATEIFY=false;
function cancel_templateify(){
	global $CANCEL_TEMPLATEIFY;
	$CANCEL_TEMPLATEIFY=true;
}


/*******************ALERTS*********************/
//Also assumes that templateify() will add it in via fetch_alerts_html()
//Call this to add an alert to be displayed at the top.
//Text: the alert text
//Disposition: negative means bad (red), positive means good (green), zero means neutral (black)
function alert($text,$disposition=0,$page_name=NULL){
	//Check that it's a valid page.
	
	if(is_null($page_name))
		$page_name='';//basename($_SERVER['REQUEST_URI']);
	$sp='alerts_'.$page_name;
	
	if(!sessioned($sp))$_SESSION[$sp]=array();
	$_SESSION[$sp][]=array($text,$disposition);
}
function fetch_alerts_html(){
	$page_name='';//basename($_SERVER['REQUEST_URI']);
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
	
	global $ruleSet;
	$ret='<div>Question Database Stats:';
	$totalN=0;
	$q=DB::queryRaw('SELECT Subject, COUNT(*) AS nQs FROM questions WHERE Deleted=0 GROUP BY Subject');
	
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