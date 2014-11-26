<?php
if(!defined('ROOT_PATH')){header('HTTP/1.0 404 Not Found');die();}
/*
config.php

Any configuration stuff.
*/

//Meh... is CONF::$DEBUG_MODE nice enough to make everything public static?

$DEBUG_MODE=true;//True if want lots of debug output. False on real production to hide everything.

$SERVER_DOWN=false;//Teapot on every page if it's true. See top of functions.php. :)

$USER_LOGIN_REQUIRED=false;
/**********************METADATA*********************/
$VERSION_NUMBER='0.3.0';
$WEBMASTER_EMAIL='moose54321@gmail.com';

/************************SESSION*********************/
$SESSION_TIMEOUT_MINUTES=30;
//$MAX_REQUESTS_PER_MINUTE=30;//Still to be implemented. What's a good number, and what's a good response?

/***********************DOEQS************************/
$QStructure=array(//Regexes should have exactly one capturing group in them.
					//Keys in the values array are what ends up being captured from the regex, translating to real value. Use (?| ) to squash multiple options.
					//Everything captured is case-insensitive; we will compare it to the keys in a case-insensitive fashion. Capitals are good though.
	"Part"=>array(
		"regex"=>"(?|(T)OSS[- ]?UP|(B)ONUS)",
		"values"=>array("T"=>"TOSS-UP","B"=>"BONUS")
	),
	"Subject"=>array(
		"regex"=>"(?|(BIO)(?:LOGY)?|(CHEM)(?:ISTRY)?|(PHYS)(?:ICS|ICAL(?: SCIENCE)?)?|(MATH)(?:EMATICS)?|(E)(?:SS)?(?:ARTH)? ?(?:AND)? ?(?:SPACE)? ?(?:SCI(?:ENCE)?)?|(ENERGY))",
		"values"=>array("BIO"=>"Biology","CHEM"=>"Chemistry","PHYS"=>"Physics","MATH"=>"Mathematics","E"=>"Earth and Space Science","ENERGY"=>"Energy")
	),
	"Type"=>array(
		"regex"=>"(?|(M)ultiple[- ]?Choice|(S)hort[- ]?Answer)",
		"values"=>array("M"=>"Multiple Choice","S"=>"Short Answer")
	),
	"Text"=>array(
		"regex"=>"([^\n]+)"
		//No values provided, will assume any string will work.
	),
	"MCChoices"=>array(//Repeatable subgroup.
		"Letter"=>array(
			"regex"=>"(W|X|Y|Z)[\.\)\-]",
			"values"=>array("W"=>"W","X"=>"X","Y"=>"Y","Z"=>"Z")
		),
		"Text"=>array(
			"regex"=>"([^\n]+)"
		),
	),
	"Answer"=>array(
		"regex"=>"ANSWER(?:\:\-\.)?\s+([^\n]+)",
	),
);
$MARK_AS_BAD_THRESHOLD=2;//RANDQ: How many times can a question can be marked bad until being ignored?
$MAX_NUMQS=25;//RANDQ: How many questions can you fetch per pageload?
$DEFAULT_NUMQS=25;//RANDQ: Default number of questions to fetch

/****************FILE TRANSFER LIMITS****************/
//ini_set('memory_limit',$MEMORY_LIMIT=10);
//is it by default megabytes?
ini_set('upload_max_filesize',$UPLOAD_MAX_FILESIZE=2);//MB - not even DOC files will exceed this, unless they have images.
ini_set('post_max_size',$POST_MAX_SIZE=2);//MB - total upload
ini_set('max_file_uploads',$MAX_FILE_UPLOADS=5);//in multi-upload or just multiple file form elements

/********************LOGGING**********************/
$REQUEST_LOG_FILE='request_log.log';
$ERROR_LOG_FILE='error_log.log';
$BUG_REPORT_FILE='bug_log.log';

/******************PAGES AND NAV*****************/
//This specifies not only the navbar, but also the allowed pages accessible. 404 if not in below, even if real file. :)
//[this may cause lots of consternation; I'm sorry. Just make sure to read comments, they're good for you.]
//db entry for each page? [file, title, nav, permission, visibility] db is comp intensive but nicer and live-editable
$pagesTitles=array(//Navbar
	"index"=>"Home",
	"input"=>"Question Entry",
	"randq"=>"Random Question",
	"about"=>"About",
	"login"=>"Login",
	"quizup"=>"QuizUp Edition",
);
$hiddenPagesTitles=array(//Not in navbar but accessible
	"bugs"=>"Bug Report/Feature Request",
);
$adminPagesTitles=array(//In navbar and accessible, but only for admins
	"admin"=>"Admin",
);


date_default_timezone_set("America/Toronto");//(No Boston)
//ini_set('session.gc_maxlifetime',600);
//ini_set('display_errors',false);
//ini_set('log_errors',true);
//ini_set('safe_mode',true);
//ini_set('safe_mode_gid',true);
//register_globals 0
//disable_functions extract mysql_connect
//disable_classes mysql

//Server-specific
@include "config.server.php";

/******************CUSTOM LOCAL*******************/
@include "config.local.php";//If necessary, stuff will be overridden here as local dev settings.

?>