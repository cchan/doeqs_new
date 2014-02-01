<?php
if(!defined('ROOT_PATH')){header('HTTP/1.0 404 Not Found');die();}
/*
class.DATA.php

Required at loadtime:
nothing

Required at calltime:
DB
err()

*/

$SESS=new SESS;//Can I request it before they're declared?
$DATA=new DATA;

final class SESS extends array_manip{
	private $sess;
	public function __construct(){
		session_start();
		setcookie(session_name(),session_id(),time()+$SESSION_TIMEOUT_MINUTES*60);
		
		//15min session timeout
		if (@$_SESSION['LAST_ACTIVITY']&&time()-$_SESSION['LAST_ACTIVITY']>$SESSION_TIMEOUT_MINUTES*60)
			$this->session_total_destroy();
		$_SESSION['LAST_ACTIVITY'] = time();

		// lock session to IP address
		if (!isSet($_SESSION['IP_ADDR']))
			$_SESSION['IP_ADDR'] = $_SERVER['REMOTE_ADDR'];
		if ($_SESSION['IP_ADDR'] != $_SERVER['REMOTE_ADDR'])
			$this->session_total_destroy();
		
		
		$this->sess=$_SESSION;
		$this->session_total_destroy();
		session_destroy();//so it can't be accessed
	}
	public function __destruct(){
		$this->session_total_destroy();
		$_SESSION=$this->sess;
	}
	
	/*
	Attempt-processing
	*/
	public function limit_attempts($process,$attempts,$seconds){
		$sp='attempts_'.$process;
		
		if(!$this->has($sp)||!is_array($this->sess[$sp]))$this->sess[$sp]=array();
		$this->sess[$sp][]=time();//Rudimentary, since they could just reset the session key.
		
		foreach($_SESSION[$sp] as $t)
			if(time()-$t>$seconds)
				unset($t);
		
		if(count($_SESSION[$sp])>$attempts)return true;
		return false;
	}
	public function reset_attempts($process){
		$_SESSION['attempts_'.$process]=array();
	}
	
	/*Adding and subtracting stuff.*/
	public function add($key,$val){$this->arr_add($this->sess,$key,$val);}
	public function has(){return $this->arr_has($this->sess,func_get_args());}
	public function get($key){return arr_retrieve($this->sess,$key);}
	public function reset(){$this->sess=array();}
	
	
	public function session_total_destroy(){//Destroys a session according to the php.net method, plus some modifications.
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
}

final class DATA extends array_manip{
	private $p,$g,$f,$c,$s;
	public function __construct(){
		$this->p=$_POST;$_POST=array();unset($_POST);//Redundancy because in some earlier versions of PHP unset() had a vulnerability.
		$this->g=$_GET;$_GET=array();unset($_GET);
		$this->f=$_FILE;$_FILE=array();unset($_FILE);
		$this->c=$_COOKIE;$_COOKIE=array();unset($_COOKIE);
		$this->s=$_SERVER;$_SERVER=array();unset($_SERVER);
	}
	public function __destruct(){
		$_COOKIE=$this->c;
	}
	
	public function needOnly($getkeys,$postkeys,$filekeys,$serverkeys){//Declare all the ones you need, and delete all else.
		
	}
	public function get($name){return arr_retrieve($this->g,$name);}
	public function getted(){return $this->arr_has($this->g,func_get_args());}
	public function ifget($n){if($this->posted($n))return htmlentities($this->g[$n]);else return '';}
	public function post($name){return arr_retrieve($this->p,$name);}
	public function posted(){return $this->arr_has($this->p,func_get_args());}
	public function ifpost($n){if($this->posted($n))return htmlentities($this->p[$n]);else return '';}
	public function file($name){return arr_retrieve($this->f,$name);}
	public function filed($name){return $this->arr_has($this->f,func_get_args());}
	public function server($name){return arr_retrieve($this->s,$name);}
	public function servered(){return $this->arr_has($this->s,func_get_args());}
	public function cookie($name){return arr_retrieve($this->s,$name);}
	public function cookied(){return $this->arr_has($this->s,func_get_args());}
	
}

class array_manip{
	private function arr_add(&$array,$key,$value){
		$array[$this->sanitize_key($key)]=$this->sanitize($value);
	}
	private function arr_has(&$array_searched,$array_keys){
		foreach($array_keys as $key)
			if(!array_key_exists($key,$array_searched))return false;
		return true;
	}
	private function arr_retrieve(&$arr,$key){
		if(!$this->arr_has($key))err('Array retrieval failed');
		else if(NULL===$this->sanitize_key($key))
		else return $this->sanitize($arr[$key]);
	}
	private function sanitize($str){//scan for suspicious patterns like SQL injections
		return $str;
		//return NULL;//on error
	}
	private function sanitize_key($str){
		if(preg_match("/[^A-Za-z0-9\_\-\.]/"))err('Array retrieval failed');
		return $str;
	}
	private function arr_remove_all_except(&$arr_on,$arr_keys){
		$newarr;
		foreach($arr_keys as $key)
			if(array_key_exists($key,$arr_on))
				$this->arr_add($newarr,$key,$value);
		$arr_on=$newarr;
	}
}

if(sessioned('user_v')&&(!array_key_exists('v',$_COOKIE)||$_COOKIE['v']!=$_SESSION['user_v']))authError();

class USER{//Can I bind the session to the user?
	public $err;
	private $email,$permissions;
	public function __construct($email,$pass,$confpass=NULL){
		$this->err=false;
		if($confpass){
				if(!filter_var($email, FILTER_VALIDATE_EMAIL))$this->err='Invalid email.';
				if($pass!==$confpass)$this->err='Passwords do not match.';
				if(strlen($pass)<8)$this->err='Password too short (must be at least 8 characters).';
				
				global $database;
				if($database->query_assoc('SELECT 1 from users WHERE email=%0%',[$email]))
					$this->err='That email already exists.';
				
				$permissions='u';//regular user
				
				$salt=genRandStr();
				$passhash=saltyStretchyHash($pass,$salt);//All of this is for nothing without end-to-end SSL.
				
				$database->query('INSERT INTO users (email, passhash, permissions, salt) VALUES (%0%,%1%,%2%,%3%)',[$email,$passhash,$permissions,$salt]);
				
				$this->email=$email;
				$this->permissions=$permissions;
		}
		else{
				if(!filter_var($email, FILTER_VALIDATE_EMAIL))$this->err='Email or password incorrect.';
				
				global $database;
				$q=$database->query_assoc('SELECT email, passhash, permissions, salt FROM users WHERE email=%0%',[$email]);
				if(!$q)$this->err='Email or password incorrect.';
				
				$passhash=saltyStretchyHash($pass,$q['salt']);
				if(!hashEquals($q['passhash'],$passhash))$this->err='Email or password incorrect.';
				
				$_SESSION['user_v']=genRandStr();
				setcookie('v',$_SESSION['user_v']);//passed back and forth and verified above.
				
				$this->email=$q['email'];
				$this->permissions=$q['permissions'];
		}
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
	public function access($minPrivilegeLevel){
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
	public function restrictAccess($minPrivilegeLevel){
		if(!$this->access($minPrivilegeLevel))
			$this->forceLogin();
	}
	public function forceLogin(){
		global ROOT_PATH;
		session_total_reset();
		alert('Oops, you need to log in to access <i>'.basename($_SERVER['REQUEST_URI']).'</i>.',-1,'login.php');
		$_SESSION['login_redirect_back']=$_SERVER['REQUEST_URI'];
		header('Location: '.ROOT_PATH.'login.php');
		die();
	}
	public function logout(){
		foreach($_SESSION as $s)
			if(strpos($s,'attempt_')===false)
				unset($s);
	}
}
?>