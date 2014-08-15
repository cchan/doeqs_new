<?php
define('ROOT_PATH','../');
require_once ROOT_PATH.'functions.php';
require_once ROOT_PATH.'classes/class.qIO.php';
restrictAccess('u');//xuca

$q=new qIO;
$q->addRand(null,null,[0],1);
?>

<style>
body{
font-size:30pt;
background-color:black;
color:white;
font-family:Segoe UI, Segoe, Frutiger, WeblySleekUI, CartoGothic STD, Open Sans, Arial, sans-serif;
}
.anschoices button{
font-family:"Robot!Head",
transition:background-color 0.5s;
font-size:inherit;
background-color:white;
color:black;
display:block;
width:100%;
text-align:center;
border-radius:20px;
border:none;
margin-top:10px;
cursor:pointer;
height:15%;
}
.anschoices button:hover{
background-color:yellow;
}
</style>

<?php
echo $q->allToHTML(<<<HEREDOC
<div style='font-weight:bold;text-align:center;' class="part">Science Bowl [QID %QID%]</div>
<div><span class="subject">%SUBJECT%</span> <i><span class="type">%TYPE%</span></i> <span class="qtext">%QUESTION%</span></div>
<div class='anschoices'>
	<button id='W' onclick='select("W")'>%W%</button>
	<button id='X' onclick='select("X")'>%X%</button>
	<button id='Y' onclick='select("Y")'>%Y%</button>
	<button id='Z' onclick='select("Z")'>%Z%</button>
</div>
<script>var answer="%ANSCHOICE%";</script>
HEREDOC
);
?>

<script>
var answered=false;
function select(letter){
	if(answered)return;
	answered=true;
	if(letter==answer)document.getElementById(letter).style.backgroundColor='#0f0';
	else{
		document.getElementById(letter).style.backgroundColor='#f00';
		document.getElementById(answer).style.backgroundColor='#0c0';
	}
	setTimeout(function(){window.location.reload(true);},500);
}
</script>

<?php $CANCEL_TEMPLATEIFY=true; ?>