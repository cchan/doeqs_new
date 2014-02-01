<?php


/*------------todo next-----------/
Integrate everything into this interface? With click-to-edit, etc. Hm. It should at least look *similar*.
*/


define('ROOT_PATH','');
require_once ROOT_PATH.'functions.php';
require_once ROOT_PATH.'classes/class.qIO.php';
restrictAccess('u');//xuca
/*
randq.php
Fetches random questions.
*/


$q=new qIO;

//docexport functionality
if(csrfVerify()&&posted('getDoc','qidcsv','docexport')){
	function sendfile($contenttype,$ext,$content){
		header("Content-type: $contenttype");
		header('Content-disposition: attachment; filename="Export'.substr(hash('SHA1',mt_rand()),0,16).'.'.$ext.'"');
		echo $content;
		
		global $CANCEL_TEMPLATEIFY;
		$CANCEL_TEMPLATEIFY=true;
		die();
	}
	if($_POST['docexport']=='QIDCSV')
		sendfile('text/plain','txt',$_POST['qidcsv']);
	elseif($_POST['docexport']=='HTML'){
		$q->clear();
		$q->addByQID(explode(',',$_POST['qidcsv']));
		sendfile('text/html','html',$q->allToHTML('<div>[QID %QID%]<br><center><b>%PART%</b></center><br>%SUBJECT% <i>%TYPE%</i> %QUESTION%<br><small>%MCOPTIONS%</small><br>ANSWER: <b>%ANSWER%</b></div><br><br>'));
	}
	else
		alert('Invalid format for export.',-1);
}


//MarkBad functionality
if(csrfVerify()&&posted("markBad","qids")){//--todo-- should be able to EDIT instead of just marking wrong. Also store history of questions viewed - "Views" table (hugeness) so can look back, mark for look back, etc.
	$q->clear();
	$q->addByQID(array_intersect_key($_POST["qids"],array_flip($_POST["markBad"])));//Only do the QIDs that are in markBad.
	$q->markBad();
	alert('Marked question(s) '.arrayToRanges($q->getQIDs()).' as bad.',1);
}



$counts=array("QParts"=>count($ruleSet["QParts"]),"Subjects"=>count($ruleSet["Subjects"]),"QTypes"=>count($ruleSet["QTypes"]));
$fullname=array("QParts"=>"Question Part","Subjects"=>"Subject","QTypes"=>"Question Type");

//Config options, and setting the SESSION variables to new values based on POST variables
$checkboxoptions="<div style='font-size:1.5em;font-weight:bold;'>Options</div>";
if(!sessioned('randq'))$_SESSION["randq"]=array();
foreach($counts as $name=>$count){
	$checkboxoptions.='<div><b>'.$fullname[$name].'</b> ';
	if(csrfVerify()&&posted($name))$_SESSION["randq"][$name]=$_POST[$name];
	elseif(!array_key_exists($name,$_SESSION["randq"]))$_SESSION["randq"][$name]=NULL;//Remembering in $_SESSION
	for($i=0;$i<$count;$i++)
		$checkboxoptions.='<label>'.$ruleSet[$name][$i].' <input type="checkbox" name="'.$name.'[]" value="'.$i.'" '.((is_array($_SESSION["randq"][$name])&&in_array($i,$_SESSION["randq"][$name]))?'checked':'').' /></label> ';
	$checkboxoptions.='</div>';
}
if(csrfVerify()&&posted("numqs")&&val_int($_POST["numqs"]))$_SESSION["randq"]["numqs"]=normRange($_POST["numqs"],1,$MAX_NUMQS);
elseif(!sessioned("numqs"))$_SESSION["randq"]["numqs"]=$DEFAULT_NUMQS;
$checkboxoptions.="<b>Number of Questions</b> (max {$MAX_NUMQS}) <input type='number' name='numqs' value='{$_SESSION["randq"]["numqs"]}' min='1' max='{$MAX_NUMQS}'/>";


//Using the session variables set above to get random questions.
$q->clear();
$addRandError=$q->addRand($_SESSION["randq"]["QParts"],$_SESSION["randq"]["Subjects"],$_SESSION["randq"]["QTypes"],$_SESSION["randq"]["numqs"]);
//--todo-- what's the point of "add" if you're only doing it this once? Overhead w/ $Q? Really OP.
if($addRandError)alert($addRandError,-1);

$checkboxoptions.='<input type="hidden" name="qidcsv" value="'.implode(',',$q->getQIDs()).'" />';
$checkboxoptions.='<br><input type="submit" value="Next" onclick="return confirm(\'Not all questions are revealed. Are you sure?\');"/>';
$checkboxoptions.='<br><br><div><b>Export Below as Document:</b> <select name="docexport"><option value="QIDCSV">Question-ID comma-separated values</option><option value="HTML">HTML</option></select><input type="submit" name="getDoc" value="Export"/></div>';
?>
<style>
.question{
transition:background-color 0.5s;
-webkit-transition:background-color 0.5s;
-moz-transition:background-color 0.5s;
-o-transition:background-color 0.5s;
-ms-transition:background-color 0.5s;
background-color:white;
}
</style>
<div class='alert_neut'><b>Hotkeys:</b> space to display next hidden answer, backspace to hide last revealed answer, enter for fetching more questions</div>
<br>
<form action="randq.php" method="POST" id="nextq">
<input type="hidden" name='ver' value="<?=csrfCode();?>"/><?php //can just copy code to submit any invalid request ?>
<div id='options'>
<?php echo $checkboxoptions;?>
</div>
<div id='questions'>
<?php
//QID,isB,Subject,isSA,Question,MCW,MCX,MCY,MCZ,Answer
if(!$addRandError)echo $q->allToHTML(<<<HEREDOC
<div class='question'>
<span style='display:inline-block;width:40%;'>[QID %QID%]</span><span style='display:inline-block;width:59%;text-align:right;font-size:0.8em;'><a href="#" class="editbtn">[Edit]</a></span>
<div>Mark as Bad: <input type="checkbox" name="markBad[]" value="%N%"/></div>
<input type="hidden" name="qids[]" value="%QID%"/>
<div style='font-weight:bold;text-align:center;' class="part">%PART%</div>
<div><span class="subject">%SUBJECT%</span> <i><span class="type">%TYPE%</span></i> <span class="qtext">%QUESTION%</span></div>
<div style="font-size:0.9em;">%MCOPTIONS%</div>
<br>ANSWER: <span class='hiddenanswer'><span class='ans'>%ANSWER%</span> <span class='hov'>[hover to show]</span></span>
<br>
<a href="#">Back to Top</a>
</div>
HEREDOC
);
?>
</div>
<br><input type="submit" value="Next"/>
</form>
<script type="text/javascript">
function flash(jQObject,color){
	jQObject.css({'background-color':color});
	window.jQObject=jQObject;
	setTimeout(function(){jQObject.css({'background-color':'#fff'});},500);
}

$(function(){$('body').removeClass('noJQuery');
	$(document).keydown(function(e){
		if(!e)var e=window.event;
		if(e.keyCode==13)//enter
			if($(".question .ans").filter(function(){return $(this).css("display")=="none";}).length==0//Either there's none left
				||confirm("Not all questions are revealed. Are you sure?"))
				window.nextq.submit();//or confirm
		if(e.keyCode==32){//space
			if(parseInt(document.body.scrollTop)<100){
				$('body').animate({scrollTop:$('.question').first().offset().top-100});
				flash($('.question').first(),'#9f9');
				e.preventDefault();
				return false;
			}
			var scrollTo=$(".question .ans").filter(function(){return $(this).css("display")=="none";}).first().css("display","inline")
				.siblings(".hov").text("[click to hide]").parents(".question");
			flash(scrollTo,'#9f9');
			if(scrollTo.length>0)$('body').animate({scrollTop:scrollTo.offset().top-100},500);
			e.preventDefault();
			return false;
		}
		if(e.keyCode==8){//backspace
			var scrollTo=$(".question .ans").filter(function(){return $(this).css("display")=="inline";}).last().css("display","none")
				.siblings(".hov").text("[click to show]").parents(".question");
			flash(scrollTo,'#f99');
			if(scrollTo.length>0)$('body').animate({scrollTop:scrollTo.offset().top-100},500);
			e.preventDefault();
			return false;
		}
	})/*.keyup(function(e){
		if(!e)var e=window.event;
	})*/;

	$(".hiddenanswer").click(
		function(){
			if($(this).children(".ans").is(':visible'))$(this).children(".hov").text("[click to show]");
			else $(this).children(".hov").text("[click to hide]");
			$(this).children(".ans").toggle();
		});
	$(".hiddenanswer").children(".ans").hide();
	$(".hiddenanswer").children(".hov").text("[click to show]");
	
	$(".editbtn").click(function(){alert("Nope, this doesn't work yet.");return false;});
});
</script>