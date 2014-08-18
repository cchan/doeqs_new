<?php
define('ROOT_PATH','');
require_once ROOT_PATH.'functions.php';
require_once ROOT_PATH.'classes/recaptchalib.php';
/*
login.php
Login, logout, whatever.
*/


if(posted('logout')){
	logout();
	alert('Successfully logged out.',1);
}
elseif(csrfVerify()){
	if(limit_attempts('login',10,300))
		alert('Too many attempts. (no more than ten in five minutes)',-1);
	elseif(posted('login')){//Is the submit button submitted for all browsers? hm
		//Naturally all this stuff is useless without proper SSL security. Shhhhhhhhhhh.
		if(loginEmailPass($_POST['email'],$_POST['pass'])){
			reset_attempts('login');
			if(isSet($_SESSION['login_redirect_back'])){//--todo-- redirect back immediately, don't redirect
				$lr=$_SESSION['login_redirect_back'];
				alert('Logged in!',1,basename($lr));
				unset($_SESSION['login_redirect_back']);
				header('Location: '.$lr);
			}
			else
				alert('Successfully logged in!',1);
		}
		else{
			logout();
			alert('Incorrect username or password.',-1);
		}
	}
	elseif(posted('signup')){
		//What happens when no access to the captcha server?
		if(!chkCaptcha())
			alert('Invalid reCAPTCHA entry; try again.',-1);
		else{
			$err=newProfileError($_POST['s_email'],$_POST['s_pass'],$_POST['s_confpass']);
			if($err===false){
				alert('Successfully signed up; you can now log in.',1);
				$signup_success=true;
				reset_attempts('login');
			}
			else alert(htmlentities($err),-1);
		}
	}
}

if(userAccess('u'))echo "Currently logged in as <b>{$_SESSION['email']}</b>.";
else{?>

<table id="loginformtable"><tr>
	<td>
	<?=generateForm(array('action'=>'login.php','method'=>'POST','autocomplete'=>'off'),array(
		'<h2>Sign Up</h2>',
		array('prompt'=>'Email:','name'=>'s_email','value'=>isSet($signup_success)?'':POST('s_email'),'autofocus'=>'autofocus'),
		array('prompt'=>'Password:','name'=>'s_pass','type'=>'password'),
		array('prompt'=>'Again:','name'=>'s_confpass','type'=>'password'),
		'Captcha:<br>'.getCaptcha(),
		array('name'=>'signup','type'=>'submit','value'=>'Sign Up')
	))?>
	Register to gain access to all features of the site! To be added soon: question tracking, subjects, common words, etc.
	</td>
	<td>
	<?=generateForm(array('action'=>'login.php','method'=>'POST'),array(
		'<h2>Log In</h2>',
		array('prompt'=>'Email:','name'=>'email','value'=>isSet($signup_success)?POST('s_email'):POST('email'),'autofocus'=>'autofocus'),
		array('prompt'=>'Password:','name'=>'pass','type'=>'password'),
		'',
		array('name'=>'login','type'=>'submit','value'=>'Log In')
	))?>
	</td>
</tr></table>
<script>if($('[name="email"]').val()​​​​​​​​​​​​​​​​​​​​​.trim().length>0)$('[name="pass"]').focus();</script>
<?php }?>