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
$VERSION_NUMBER='0.2.2';
$WEBMASTER_EMAIL='moose54321@gmail.com';

/************************SESSION*********************/
$SESSION_TIMEOUT_MINUTES=30;
//$MAX_REQUESTS_PER_MINUTE=30;//Still to be implemented. What's a good number, and what's a good response?

/***********************DOEQS************************/
$ruleSet=array(//...to be honest, this is annoying.
	"Subjects"=>array("BIOLOGY","CHEMISTRY","PHYSICS","MATHEMATICS","EARTH AND SPACE SCIENCE"),
	"SubjRegex"=>'(BIO(?:LOGY)?|CHEM(?:ISTRY)?|PHYS(?:ICS|ICAL(?: SCIENCE)?)?|MATH(?:EMATICS)?|E(?:SS)?(?:ARTH)? ? ?(?:AND)? ?(?:SPACE)? ?(?:SCI(?:ENCE)?)?)',
	"QTypes"=>array("Multiple Choice","Short Answer"),
	"QParts"=>array("TOSS-UP","BONUS"),
	"MCChoices"=>array("W","X","Y","Z"),
	"SubjChars"=>str_split('bcpme'),
	"TypeChars"=>str_split('ms'),
	"PartChars"=>str_split('tb'),
);
$MARK_AS_BAD_THRESHOLD=2;//RANDQ: How many times can a question can be marked bad until being ignored?
$MAX_NUMQS=25;//RANDQ: How many questions can you fetch per pageload?
$DEFAULT_NUMQS=25;//RANDQ: Default number of questions to fetch

/****************FILE TRANSFER LIMITS****************/
ini_set('upload_max_filesize',2);//MB
ini_set('post_max_size',2);//MB
ini_set('max_file_uploads',5);//in multi-upload or just multiple file form elements

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
ini_set('session.gc_maxlifetime',600);
ini_set('display_errors',false);
ini_set('log_errors',true);
ini_set('safe_mode',true);
ini_set('safe_mode_gid',true);
//register_globals 0
//disable_functions extract mysql_connect
//disable_classes mysql

//Server-specific
@include "config.server.php";

/******************CUSTOM LOCAL*******************/
@include "config.local.php";//If necessary, stuff will be overridden here as local dev settings.

?>