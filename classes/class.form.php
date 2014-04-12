<?php
if(!defined('ROOT_PATH')){header('HTTP/1.0 404 Not Found');die();}
/*
class.form.php
Form manipulation.

*/

class form{
	private $formName;
	
	public function __construct($formName){
		$this->formName=$formName;
		if($_SESSION[$formName])
	}
	
	public function __destruct(){
	
	}
	
	public function attrib($name,$value){
	
	}
	
	public function text($attribs){
		return &$this;
	}
	public function num(){
	
	}
	public function captcha(){
	
	}
	public function html(){
		
	}
}

?>