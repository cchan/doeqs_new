<?php
if(!defined('ROOT_PATH')){header('HTTP/1.0 404 Not Found');die();}
/*
accountManagement.php
Implements decent account-management scheme.
Also does session stuff.


Dependencies

NEEDED AT INCLUDETIME:
$SESSION_TIMEOUT_MINUTES

NEEDED AT CALLTIME:
posted()
sessioned()
DB
err()?
...



Special Note:
userAccess
//In order of precedence:
//a=admin
//c=captain
//u=regular user
//x=not logged in
*/



//session_name('doeqs');
session_start();
setcookie(session_name(),session_id(),time()+$SESSION_TIMEOUT_MINUTES*60);
function session_total_reset(){//Destroys a session according to the php.net method, plus some modifications.
	// Unset all of the session variables.
	$_SESSION = array();
	unset($_SESSION);

	// If it's desired to kill the session, also delete the session cookie.
	// Note: This will destroy the session, and not just the session data!
	if (ini_get('session.use_cookies')) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000,
			$params['path'], $params['domain'],
			$params['secure'], $params['httponly']
		);
	}

	// Finally, destroy the session.
	session_destroy();
	session_start();
	session_regenerate_id(true);//Regenerates the session ID so that it's hard to attack.
}

//15min session timeout
if (@$_SESSION['LAST_ACTIVITY']&&time()-$_SESSION['LAST_ACTIVITY']>$SESSION_TIMEOUT_MINUTES*60)
	session_total_reset();
$_SESSION['LAST_ACTIVITY'] = time();

// lock session to IP address
if (!isSet($_SESSION['IP_ADDR']))
	$_SESSION['IP_ADDR'] = $_SERVER['REMOTE_ADDR'];
if ($_SESSION['IP_ADDR'] != $_SERVER['REMOTE_ADDR'])
	session_total_reset();

/*
limit_attempts

Returns true if there have been more than $attempts attempts in $seconds seconds to do $process.
*/
function limit_attempts($process,$attempts,$seconds){
	$sp='attempts_'.$process;
	
	if(!sessioned($sp)||!is_array($_SESSION[$sp]))$_SESSION[$sp]=array();
	$_SESSION[$sp][]=time();//Rudimentary, since they could just reset the session key.
	
	foreach($_SESSION[$sp] as $t)
		if(time()-$t>$seconds)
			unset($t);
	
	if(count($_SESSION[$sp])>$attempts)return true;
	return false;
}
function reset_attempts($process){
	$_SESSION['attempts_'.$process]=array();
}



function genRandStr($length=NULL){
	if(!$length)$length=mt_rand(64,96);
    $c = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';$cl = strlen($c);
    $s = '';
    for($i=0;$i<$length;$i++)$s.=$c[mt_rand(0,$cl-1)];
	return $s;
}
function csrfVerify(){//Checks CSRF code validity, and returns whether to proceed. The return value is static. Erases 'ver'.
	static $valid=NULL;
	if(is_null($valid)){
		if(posted('ver')&&sessioned('ver')&&hashEquals($_POST['ver'],$_SESSION['ver'])){
			unset($_POST['ver'],$_SESSION['ver']);
			$valid=true;
		}
		else $valid=false;
		unset($_POST['ver'],$_SESSION['ver']);
	}
	return $valid;
	//--todo-- Exceptions are bad and messy and not being caught. They're not meant to propagate all the way up.
}
function csrfCode(/*$forceNew*/ /*$ver_name*/){//Returns randomly generated CSRF code. The return value is static.
	static $code='';
	if(sessioned('ver')&&$code===$_SESSION['ver'])return $code;
	
    return ($code=$_SESSION['ver']=genRandStr());
}

function generateForm($form,$inputs){
	$csrf=csrfCode();
	$a='';
	foreach($form as $name=>$value)
		$a.=' '.$name.'="'.$value.'" ';
	
	$form="<form $a><input type='hidden' name='ver' value='$csrf'/><table>";
	
	foreach($inputs as $input){
		if($input=='')
			$form.='<tr><td colspan="2">&nbsp;</td></tr>';
		elseif(is_string($input))
			$form.='<tr><td colspan="2">'.$input.'</td></tr>';
		else{
			$elem='<input ';
			foreach($input as $name=>$value)
				if($name!='prompt')
					$elem.=" {$name}=\"{$value}\" ";
			$elem.=' />';
			if(array_key_exists('prompt',$input))$form.="<tr><td>{$input['prompt']}<td>$elem</td></tr>";
			else $form.="<tr><td colspan='2'>$elem</td></tr>";
		}
	}
	$form.='</table></form>';
	return $form;
}



function authError(){
	logout();
	die('Authentication error.');
}

function hashEquals($a,$b){//Compares the *hashes* of two variables to mess with timing attacks.
	$m=microtime();
	return sha1($a.$m.$b)==sha1($b.$m.$a);
}


/*
userAccess()
Returns whether your user has permission to access this page, given the minimum access level in the hierarchy required to get in.

//In order of precedence:
//a=admin
//c=captain
//u=regular user
//x=not logged in
*/

//$_SESSION["user_verification_code"]='';
function userAccess($minPrivilegeLevel){
	$minPrivilegeLevel=strtolower($minPrivilegeLevel);
	
	if(sessioned('permissions'))$_SESSION['permissions']=strtolower($_SESSION['permissions']);
	else $_SESSION['permissions']='x';
	
	$hierarchy='xuca';//hierarchy, from lowest to highest
	
	if(count($minPrivilegeLevel)!==1)error("Invalid permission level '$minPrivilegeLevel'");
	if(!sessioned('email'))$nUser=0;
	else $nUser=strpos($hierarchy,$_SESSION['permissions']);
	$nAllowed=strpos($hierarchy,$minPrivilegeLevel);
	
	if($nUser===false)error("Invalid session permission level '{$_SESSION["permissions"]}'");
	if($nAllowed===false)error("Invalid input permission level '$minPrivilegeLevel'");
	
	else return $nUser>=$nAllowed;
}
function restrictAccess($minPrivilegeLevel){
	global $USER_LOGIN_REQUIRED;
	if($minPrivilegeLevel=='u' && !$USER_LOGIN_REQUIRED)
		$minPrivilegeLevel='x';
	if(!userAccess($minPrivilegeLevel))
		forceLogin();
}

if(sessioned('user_v')&&(!array_key_exists('v',$_COOKIE)||$_COOKIE['v']!=$_SESSION['user_v']))authError();
function loginEmailPass($email,$pass){
	if(!filter_var($email, FILTER_VALIDATE_EMAIL))return false;
	
	global $database;
	$q=$database->query_assoc('SELECT email, passhash, permissions, salt FROM users WHERE email=%0%',[$email]);
	if(!$q)return false;
	
	$passhash=saltyStretchyHash($pass,$q['salt']);
	if(!hashEquals($q['passhash'],$passhash))return false;
	
	$_SESSION['email']=$q['email'];
	$_SESSION['permissions']=$q['permissions'];
	$_SESSION['user_v']=genRandStr();
	setcookie('v',$_SESSION['user_v']);//passed back and forth and verified above.
	
	return true;
}
function forceLogin(){
	global $DOEQS_URL;
	session_total_reset();
	alert('Oops, you need to log in to access <i>'.basename($_SERVER['REQUEST_URI']).'</i>.',-1,'login.php');
	$_SESSION['login_redirect_back']=$_SERVER['REQUEST_URI'];
	header('Location: '.$DOEQS_URL.'login.php');
	die();
}
function logout(){//--todo--uhhhhhh that's it? shouldn't it be whitelist-style erasure? idk, since $_SESSION['attempts_*'] needs to stay alive
	foreach($_SESSION as $name=>$val)
		if(strpos($name,'attempt_')===false)unset($_SESSION[$name]);
}
function saltyStretchyHash($pass,$salt){//WAAAY overdoing it. Messing with any sort of brute force attack.
	if(!$salt){err('Needs salt');return;}
	$universalSalt='sGh,mGo%Js(Kv/8o"xxN;}tPXR+*RW27FhgT<59R`AoaRP=)(pos3{<i%Yj#R^DSaei~sx"8#y7|&fx[EiLi$M{,n+V=?)T~gNky{(w|H|=+F\FQmo~-Gojg9<lB@+';
	$hash='';
    for($i=0;$i<274;$i++)$hash=hash('whirlpool',$universalSalt.hash('sha512',$i.$pass.$hash.$salt));
	usleep(mt_rand(0,10000));//Messing up timing attacks :P
    return $hash;
}
/*From MySQL Docs:
Passwords or other sensitive values supplied as arguments to encryption functions are sent in plaintext
 to the MySQL server unless an SSL connection is used. Also, such values will appear in any MySQL logs
 to which they are written. To avoid these types of exposure, applications can encrypt sensitive values
 on the client side before sending them to the server. The same considerations apply to encryption keys.
 To avoid exposing these, applications can use stored procedures to encrypt and decrypt values on the server side.
Therefore, always use PHP-side hashing unless it doesn't matter cryptographically. (we don't use SSL, unfortunately, shhhhh)
 */

/*
newProfileError
Creates a new profile with $email, $pass/$confpass. Initated to permissions 'u', regular user.
Returns false if there were no errors, and the text of the error if there were errors.
*/
function newProfileError($email,$pass,$confpass){
	global $database;
	
	if(!filter_var($email, FILTER_VALIDATE_EMAIL))return 'Invalid email.';
	if($pass!==$confpass)return 'Passwords do not match.';
	if(strlen($pass)<8)return 'Password too short (must be at least 8 characters).';
	
	if($database->query_assoc('SELECT 1 from users WHERE email=%0% LIMIT 1',[$email]))
		return 'That email already exists.';
	
	$permissions='u';//regular user
	
	$salt=genRandStr();
	$passhash=saltyStretchyHash($pass,$salt);//All of this is for nothing without end-to-end SSL.
	
	$database->query('INSERT INTO users (email, passhash, permissions, salt) VALUES (%0%,%1%,%2%,%3%)',[$email,$passhash,$permissions,$salt]);
	
	return false;//'no error'
}

function chkCaptcha(){
	require_once('classes/recaptchalib.php');
	global $RECAPTCHA_privatekey;
	$resp = recaptcha_check_answer ($RECAPTCHA_privatekey,
		$_SERVER["REMOTE_ADDR"],
		$_POST["recaptcha_challenge_field"],
		$_POST["recaptcha_response_field"]);
	return $resp->is_valid;
}
function getCaptcha(){
	require_once('classes/recaptchalib.php');
	global $RECAPTCHA_publickey;
	return recaptcha_get_html($RECAPTCHA_publickey);
}
?>