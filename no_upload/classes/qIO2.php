<?php
if(!defined('ROOT_PATH')){header('HTTP/1.0 404 Not Found');die();}

require_once "class.DB.php";
require_once "class.fileToStr.php";



//todo: get parsing to actually work (@Videh, does he have a better way to parse than regex)
//make MC answers not just WXYZ, store full text THEN compare. Comparison can be done with MC detection followed by assoc algorithm

class qParser{
	public function __construct(){}
	
	//strParseQs - high-level question-parsing; accepts string of questions to parse, does whatever with them, and returns string of output.
	public function parse($qstr){
		global $database,$ruleSet;
		if(str_replace([" ","	","\n","\r"],'',$qstr)===''){echo "Error: No text submitted.";return '';}
		
		//$t=microtime();
		$nMatches=preg_match_all($this->qregex(), $qstr, $qtext);
		//echo '(TIME-millis:'.(microtime()-$t).':TIME)';
		
		$qs=new qIO();
		for($i=0;$i<$nMatches;$i++){
			try{
		//0 full match
		//1 QPart
		//2 Number
		//3 Subj
		//4 QType
		//5 QuestionText
		//6-9 W-Z
		//10 Answer
				//Indices: 0 full match, Part, Number, Subject, MCQText, ChoicesW, ChoicesX, ChoicesY, ChoicesZ, SAQText, MCa, AnswerText
				$qs->addByArray(array(array(
					"isB"=>strpos('tb',strtolower(substr($qtext[1][$i],0,1))),//--todo-- THIS IS A BIG PROBLEM.
					"Subject"=>array_search(strtolower(substr($qtext[2][$i],0,1)),$ruleSet["SubjChars"]),//--todo-- THIS IS A PROBLEM.
					"isSA"=>$qtext[4][$i]=='Short Answer',
					"Question"=>str_replace(["\r","\n"],'',$qtext[5][$i]),//:O IMPORTANT: single quotes do not escape \n etc!
					"MCW"=>$qtext[6][$i],"MCX"=>$qtext[7][$i],"MCY"=>$qtext[8][$i],"MCZ"=>$qtext[9][$i],
					"MCa"=>substr($qtext[10][$i],0,1),
					"Answer"=>$qtext[10][$i],
					)));
			}
			catch(Exception $e){
				echo "ERROR: ".$e->getMessage();
				//--todo-- DISPLAY ERRORS? OR JUST IGNORING? OR IDK IT SHOULDN'T EVEN HAPPEN
			}
		}
		$qs->commit();
		$parsedQIDs=$qs->getQIDs();
		
		/*echo "Duplicates: none<br><br>";*/
		echo "<b>Total uploaded Question-IDs: ".((count($parsedQIDs)==0)?"no questions entered":arrayToRanges($parsedQIDs)." (".count($parsedQIDs)." total entered)")."</b>";
		return preg_replace($this->qregex(),'',$qstr);//stuff remaining after questions detected
	}
	private function qregex(){
	//dafuq [in regexpal] it works fine except doesn't match mc questions where there's "how" or "law" in the question, or where there's "only" in X
	//also, mislabeled MC as SA passes in no-linebreaks mode
		global $ruleSet;//including SubjRegex
		
		return '/(TOSS-UP|BONUS)\n\s*(?:([0-9]+)[\.\)])?\s*'.$ruleSet['SubjRegex']
			.'\s*(Multiple Choice|Short Answer)\s*([^\n]+)'
			.'(:?\s*W[\s\S]([^\n]+)\s*X[\s\S]([^\n]+)\s*Y[\s\S]([^\n]+)\s*Z[\s\S]([^\n]+))?'
			.'\s*\nANSWER[\s\S]([^\n]+)/i';
		
		/*
				$qs->addByArray(array(array(
					"isB"=>strpos('tb',strtolower(substr($qtext[1][$i],0,1))),//--todo-- THIS IS A BIG PROBLEM.
					"Subject"=>array_search(strtolower(substr($qtext[3][$i],0,1)),$ruleSet["SubjChars"]),//--todo-- THIS IS A PROBLEM.
					"isSA"=>$qtext[4][$i]=='',
					"Question"=>str_replace(["\r","\n"],'',$qtext[4][$i].$qtext[9][$i]),//:O IMPORTANT: single quotes do not escape \n etc!
					"MCW"=>$qtext[5][$i],"MCX"=>$qtext[6][$i],"MCY"=>$qtext[7][$i],"MCZ"=>$qtext[8][$i],
					"MCa"=>(!empty($qtext[10][$i]))?strpos('wxyz',strtolower($qtext[10][$i])):'',//--todo-- what if 1st char ISN'T [WXYZ]!?
					"Answer"=>$qtext[11][$i],
					)));
		
		$e='[\:\.\)]';//Endings: W. or W) or W- or W:. //can't have space because if has "asdfy asdf" as x, will catch "y "
		$mcChoices='';
		$choiceArr=array_merge($ruleSet['MCChoices'],array("ANSWER"));
		for($i=0;$i<4;$i++)$mcChoices.=$choiceArr[$i].'\)((?:(?!'.$choiceArr[$i+1].'\))[\)\. ])*)\s*';
		return '/(TOSS ?\-? ?UP|BONUS)\s*(?:([0-9]+)[\.\)\- ])?\s*'.$ruleSet["SubjRegex"].'\s*(?:Multiple Choice\s*((?:(?!W'.$e.')[\)\. ])*)\s*'.$mcChoices.'|Short Answer\s*((?:(?:(?!ANSWER'.$e.')[\s\S])*)(?:\s*[IVX0-9]+'.$e.'(?:(?!ANSWER'.$e.')(?![IVX0-9]+'.$e.')[\)\. ])*)*))\s*ANSWER'.$e.'*\s*([WXYZ]?)((?:[\)\. ])*)([\n\r]|$)/i';
		*/
	}
	//for($i=0;$i<4;$i++)$mcChoices.=$choiceArr[$i].$e.'((?:(?!'.$choiceArr[$i+1].$e.')[\s\S])*)\s*';
	//return '/(TOSS\-?UP|BONUS)\s*(?:([0-9]+)[\.\)\- ])?\s*'.$subjChoices.'\s*(?:Multiple Choice\s*((?:(?!W'.$e.')[\s\S])*)\s*'.$mcChoices.'|Short Answer\s*((?:(?:(?!ANSWER'.$a.')[^\s\S])*)(?:\s*[IVX0-9]+'.$e.'(?:(?!ANSWER'.$a.')(?![IVX0-9]+'.$e.')[\s\S])*)*))\s*ANSWER'.$a.'*\s*((?:(?![\n\r]|$|TOSS\-?UP|BONUS)[\s\S])*)/i';
}


$QUESTION_FORM = array(//all structure based on THIS.
	"isB"=>false,
	"Subject"=>0,
	"isSA"=>false,
	"Question"=>"",
	"MCChoices"=>["","","",""],
	"Answer"=>"",
);

class Question{
	public $QID=0;
	private $Q = $QUESTION_FORM;
	public $committed = true;
	
	public function __construct(){}
	
	public function /*[that catcher thingy]*/{
		if($funcname in $QUESTION_FORM)
			if($param1)$Q[$funcname]=$param1;
			else return $Q[$funcname];
	}
	
	
	public function loadQID($QID){
		
	}
	public function toHTML(){
		
	}
	public function commit(){
		
	}
}

class qIO{
	$Qs = array();
	public function parseFromFile($filepath){
		
	}
	public function parseFromText($text){
		$qp = new QParser;
	}
	public function clear(){
		$this->Qs=array();
	}
}

?>