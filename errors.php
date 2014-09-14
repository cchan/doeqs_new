<?php
class InputException extends Exception{}
class EmailException extends Exception{}
class PasswordException extends Exception{}

class errorHandler{
	//Should use InputException.
	/*public function err_user($description){//Just a mistake from the user.
		//alert(htmlentities($description),-1);
		throw new UserException($description);
	}*/
	public function err_dev($description){//Developer needs to look at the code.
		$d=debug_backtrace();
		error_log_print("DEVERROR", $description, $d[0]['file'], $d[0]['line']);
		
	}
}


//Making up for error_get_last()'s shortcomings.
function error_clear_last(){
	// var_dump or anything else, as this will never be called because of the 0
	set_error_handler('var_dump', 0);
	@$error_clear_tmp523457901;
	restore_error_handler();

	var_dump(error_get_last());
}
function error_get_clear(){
	$a=error_get_last();
	error_clear_last();
	return $a;
}
function error_occurred(){
	$E=error_get_last();
	if($E===NULL || $E['message']==='Undefined variable: error_clear_tmp523457901')return false;
	return true;
}


function getErrPage(){
	//generic error page without any particular stuff.
}

function error_log_print($errno,$errstr,$errfile,$errline){
	$backtrace = (new Exception)->getTraceAsString();
	$err="Error #$errno: '$errstr' at line $errline of file $errfile. Debug Backtrace:\r\n$backtrace\r\n";
	logfile('err',$err);
	
	//Printing out
	global $DEBUG_MODE;
	if($DEBUG_MODE)echo "An error occurred:<br><pre>$err</pre><br>(logged as above)";
	else{echo getErrPage();}
}


/*register_shutdown_function(function(){
	if(error_occurred())error_log_print($error["type"],$error["message"],$error["file"],$error["line"]);
});*/

//Error-handler function. Note: DOES stop execution
function error_catcher($errno,$errstr,$errfile,$errline){
	error_log_print($errno,$errstr,$errfile,$errline);
	cancel_templateify();
	die();//Either way, an error should not let it go on executing.
	//If you want to have errors within classes, implement a class error-catching system yourself and output it to the user that way. Preferably through alerts.
}
set_error_handler('error_catcher', E_ALL|E_STRICT);
ini_set('error_reporting',E_ALL|E_STRICT);
error_reporting(E_ALL|E_STRICT);
if($DEBUG_MODE){
	ini_set('display_errors',1);
	ini_set('log_errors',1);
}
?>