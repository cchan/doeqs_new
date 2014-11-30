<html>
<head>
<title>DOE Round</title>
<style type="text/css">
body{
font-family:Arial,sans-serif;
}
body>section{
background-color:black;
position:absolute;
padding:10px;
color:white;
}




#question-display{
top:0px;height:340px;
left:90px;right:340px;
background-color:#ccc;
color:#000;
font-size:2em;
}
.q.part{
text-align:center;font-weight:bold;
font-size:1.5em;
}
.q.subj{
}
.q.type{
font-style:italic;
}
.q.text{
}
.q.mc div{
}





#user-control{
top:380px;bottom:0px;
left:90px;right:340px;
text-align:center;
}





#stats-display{
top:0px;bottom:0px;
right:0px;width:300px;
}





#main-menu{
top:20px;bottom:20px;
left:0px;width:50px;
}




</style>
</head>
<body>
<section id="question-display"></section>
<section id="user-control">
	<button style="background-color:#CC0000;border-width:5px;border-radius:100px;width:400px;height:200px;" onclick="buzz();"><h1 style="font-size:4em;">BUZZ</h1><h4>spacebar</h4></button>
</section>
<section id="stats-display">
<span id="timer"></span>
</section>
<section id="main-menu">MainMenu</section>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<script type="text/javascript">
function Question(jsonStr){//also get metadata
	var obj=JSON.parse(jsonStr);
	if(obj[0]==1||obj[0]==0)this.part=obj[0];else alert("error");
	if(obj[1]==1||obj[1]==2||obj[1]==3||obj[1]==4||obj[1]==5)this.subj=obj[1];else alert("error");
	if(obj[2])this.text=obj[2];else alert("error");
	if(obj.hasOwnProperty(3)&&obj[3].length==4)this.MCChoices=obj[3];
	this.display=function(){
		var html="<div class='q part'>"+(["TOSS-UP","BONUS"][this.part-1])+"</div>";
		html+="<div><span class='q subj'>"+(["EARTH AND SPACE SCIENCE","BIOLOGY","CHEMISTRY","PHYSICS","MATHEMATICS"][this.subj-1])+"</span>&nbsp;";
		html+="<span class='q type'>"+((this.hasOwnProperty("MCChoices"))?"Multiple Choice":"Short Answer")+"</span>&nbsp;&mdash;&nbsp;";
		html+="<span class='q text'>"+this.text+"</span>";
		html+="</div>";
		if(this.hasOwnProperty("MCChoices")){
			html+="<div class='q mc'>";
			for(var i=0;i<4;i++)html+="<div>"+(["W","X","Y","Z"][i])+") "+this.MCChoices[i]+"</div>";
			html+="</div>";
		}
		$("#question-display").html(html);
	}
}
var Q=new Question('[1,5,"What is the square root of two?",["1","2","4","18239"]]');
Q.display();


function userControl(){
	
}
function buzz(){
	alert("buzz");
}

window.onkeypress=function(e){
	if(e.keyCode==32)buzz();
	if(e.keyCode==13)timer(5);
}

function timer(seconds){
	var diffMillis=(new Date()).getTime()-window.timerInitTime;
	if(diffMillis<=1000*window.timerLength)return;
	
	window.timerInitTime=(new Date()).getTime();
	window.timerLength=seconds;
	window.timerInterval=setInterval(
		function(){
			var diffMillis=(new Date()).getTime()-window.timerInitTime;//Time since we started
			$("#timer").text(timeFormat(diffMillis));//Format it decently
			if(diffMillis>1000*window.timerLength){clearInterval(window.timerInterval);$("#timer").text("0.000");}//Reset if it's past time
		}
		,10);
}

function timeFormat(millis){
	var date = new Date(window.timerLength*1000-diffMillis);
	date.getHours();
	return date.getMinutes() + ":" + date.getSeconds() + "." + date.getMilliseconds()
}
</script>
</body>
</html>