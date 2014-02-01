<?php
require_once "qIO.php";

/*echo "<form method='post'><textarea name='regex' cols='100'>$regex</textarea><input type='submit'/></form><br><br><br>";
if(isSet($_POST["regex"]))$regex=$_POST["regex"];
else */
echo "<h2>Question Parser</h2>";
echo "<form method='post'><textarea name='question' rows='20' cols='100'></textarea><input type='submit'/></form><br><br><br>";
if(isSet($_POST["question"])){
	$regex='/[TOSS-UP]+\s*([0-9]+\))?\s*(BIO(LOGY)?|CHEM(ISTRY)?|PHYS(|ICS|ICAL SCIENCE)|MATH(EMATICS)?|E(SS|ARTHSCI|ARTH SCIENCE|ARTH AND SPACE SCIENCE))\s*(Multiple Choice\s*([^\r\n]+)\s*W([^\r\n]+)\s*X([^\r\n]+)\s*Y([^\r\n]+)\s*Z([^\r\n]+)|Short Answer\s*([^\r\n]+(\s*[IV0-9]+[^\r\n]+)*))\s*[ANSWER]+:\s*([^\r\n]+)\s*[BONUS]+\s*([0-9]+\))?\s*(BIO(LOGY)?|CHEM(ISTRY)?|PHYS(|ICS|ICAL SCIENCE)|MATH(EMATICS)?|E(SS|ARTHSCI|ARTH SCIENCE|ARTH AND SPACE SCIENCE))\s*(Multiple Choice\s*([^\r\n]+)\s*W([^\r\n]+)\s*X([^\r\n]+)\s*Y([^\r\n]+)\s*Z([^\r\n]+)|Short Answer\s*([^\r\n]+(\s*[IV0-9]+[^\r\n]+)*))\s*[ANSWER]+:\s*([^\r\n]+)/i';
	preg_match_all($regex, $_POST["question"], $questiontexts);
	//foreach($questiontexts[0] as $questionN=>$questionText)echo "<div style='border:solid 1px #000000;'>$questionN: </div>";
	//0 full match, (1 number, 2 subject, [5 extras], 8 full text plus type, 9 MC qtext, 10 W, 11 X, 12 Y, 13 Z, 14 SA qtext, 15 answer)*2
	echo count($questiontexts[1])." questions successfully loaded. (not <i>actually</i> uploaded yet!)<br><br>";
	$lastOne=0;
	echo "Question parsing errors (using our rudimentary missing-question detection mechanism): ";
	for($i=0;$i<count($questiontexts[1]);$i++){
		if($questiontexts[1][$i]==""){echo " [Missing number near question $i] ";continue;}
		$next=(int)$questiontexts[1][$i];
		if($lastOne>=$next){echo " [Messed up numbering near question $lastOne] ";continue;}
		for($j=$lastOne+1;$j<$next;$j++)echo " [Missing or mis-parsed question ".$j."] ";
		$lastOne=$next;
	}
	echo " ...no other errors<br><br>(Common syntax errors include multi-line question statement, improperly labeled (as MC or SA), missing some necessary components (like multiple choices and an answer), really horrible misspellings.)";
}
?>