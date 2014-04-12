<?php
/*
class.user.php

Required at runtime:
$database

*/

class UserSession{
	private $ARR;
	public function __construct(){
		if(sessioned('USER')&&is_array($_SESSION['USER']))
			$this->ARR=$_SESSION['USER'];
		else
			$ARR=NULL;
	}
	public function __destruct(){
		
	}
	public function login($email,$pass){
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
	public function logout(){
		global $DOEQS_URL;
		$_SESSION['USER']
		alert('Successfully logged out.',1,'login');
		header('Location: '.$DOEQS_URL.'login.php');
	}
	public function name(){
		if($ARR==NULL)return false;
	}
	public function access(){
		if($ARR==NULL)return false;
	}
	public function restrict(){
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
	
}

?>