<?
if(!defined('ROOT_PATH')){header('HTTP/1.0 404 Not Found');die();}

/*
config.server.sample.php

Sample of config.server.php, which contains server-specific settings, as well as anything secret
*/

$DOEQS_URL='http://doeqswebsite.com/';
$WEBMASTER_EMAIL='hello@doeqswebsite.com';
date_default_timezone_set("America/New York");

/********************DATABASE ACCESS*******************/
$DB_SERVER = "doeqs_server.db";
$DB_USERNAME = "DOEQS_PHP_USER";
$DB_PASSWORD = "A VERY BAD PASSWORD";
$DB_DATABASE = "DOE_QUESTIONS";

/***********************Universal Salt*********************/
//Don't change it unless you know what you're doing.
$universalSalt='blah blah blah generate something random from grc.org or something like that yeah';

/***********************RECAPTCHA**********************/
$RECAPTCHA_publickey = '[recaptcha public key here]';
$RECAPTCHA_privatekey = '[recaptcha private key here]';
?>