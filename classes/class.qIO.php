<?php
if(!defined('ROOT_PATH')){header('HTTP/1.0 404 Not Found');die();}

/*qIO.php
Contains powerful question-interface class qIO.

DOCUMENTATION OF DATABASE
--todo-- fill this out

refactor "isB" "isSA" => hm
*/

/*
class Question{
	public $QID=0;
	private $QID,$isB,$Subject,$isSA,$Question,$MCChoices,$Answer;
	public $committed = true;
	
	public function __construct(){
		
	}
	
	public function //[that catcher thingy]
	{
		if($funcname in $QUESTION_FORM)
			if($param1)$Q[$funcname]=$param1;
			else return $Q[$funcname];
	}
	
	public function loadArr($arr){
	
	}
	public function loadQID($QID){
		
	}
	public function toHTML(){
		
	}
	public function update(){
		
	}
	public function commit(){
		
	}
}*/

class qIO{//Does all the validation... for you! By not trusting you at all. ;)
	//private $QID,$isB,$Subject,$isSA,$Question,$MCChoices,$Answer;
	private $Questions;
	
	public $error;
	
	public function __construct(){
		//$this->QID=$this->isB=$this->Subject=$this->isSA=$this->Question=$this->MCChoices=$this->Answer=array();
	}
	public function __destruct(){
		if(!is_null($this->Questions))foreach($this->Questions as $q)if($q[0]==0)$this->err('destruct: Uncommitted added questions.');
	}
	public function clear(){
		if(!is_null($this->Questions))foreach($this->Questions as $q)if($q[0]==0)$this->err('clear: Uncommitted added questions.');
		$this->Questions=array();
		return $this;
	}
	public function addQResult($qresult){
		while($row=$qresult->fetch_assoc())
			$this->addAssocArr($row);
	}
	public function addAssocArr($qarr){
		$this->Questions[]=array($qarr['QID'],$qarr['isB'],$qarr['Subject'],$qarr['isSA'],
			$qarr['Question'],$qarr['MCW'],$qarr['MCX'],$qarr['MCY'],$qarr['MCZ'],
			$qarr['Answer']);
	}
	public function addRand($QParts,$Subjects,$QTypes,$num){//arrays of the numbers to include eg subj [0,1,4] for b,c,e
		global $MARK_AS_BAD_THRESHOLD, $ruleSet, $MAX_NUMQS, $DEFAULT_NUMQS;
		
		$num=normRange($num,1,$MAX_NUMQS,$DEFAULT_NUMQS);
		
		$where = new WhereClause('and');
		$where->add('MarkBad<%i',$MARK_AS_BAD_THRESHOLD);
		$where->add('Deleted=0');
		
		$db=array("isB","Subject","isSA");
		foreach(array("QParts","Subjects","QTypes") as $i=>$name){
			if(!val('*i+',$indices=eval('$'.$name.';')))continue;//Fetches and verifies array of index values that the user may want.
			$indices=array_values(array_unique($indices));//Eliminates stupidity
			
			$sub = $where->addClause('or');
			foreach($indices as $index)
				if(inRange($index,0,count($ruleSet[$name])-1))//Make sure the index is correct.
					$sub->add('%b=%i',$db[$i],$index);//Inserts the index into the proper DB field
		}
		$qresult=DB::queryRaw("SELECT * FROM questions WHERE %l ORDER BY TimesViewed ASC, ".SQLRAND('QID')." LIMIT %i", $where, $num);
		
		//Order by TimesViewed, and then randomize within each TimesViewed value.
		//NOTE: TimesViewed is despite categories, and if you have something like 2 10 10 10, you'll get the 2 at least 8 times in a row.
			//The assumption that there is a large pool for _each_ possible classification (2*5*2=20 of them) eliminates this problem.
		
		if($qresult->num_rows!=$num)$this->user_err("Not enough such questions exist.");
		$this->addQResult($qresult);
		
		$this->updateIs(range(count($this->Questions)-$num,count($this->Questions)-1),"TimesViewed=TimesViewed+1");//--todo-- do this in user-specific storage instead.
		
		return $this;
	}
	public function addByQID($qids){
		if(!is_array($qids))$this->err('QIDs not array');
		
		$where = new WhereClause('and');
		foreach($qids as $qid)
			if(!val('i+',$QID))continue;//Invalid QID.
			else $where->add('QID=',$qid);
		$where->add('Deleted=0');
		
		$qresult=DB::queryRaw('SELECT * FROM questions WHERE %l LIMIT %i',$where,count($qids));
		if($qresult->num_rows<count($qids))$this->user_err('Some QIDs do not exist.');//--todo-- but cannot continue execution
		
		$this->updateQIDs($qids,"TimesViewed=TimesViewed+1");
		$this->addQResult($qresult);
		
		return $this;
	}
	public function addByArray($qarrArray){//Add to the array of questions, using an array of $qarr
		global $ruleSet;
		
		if(!val('**s',$qarrArray))$this->err('addByArray: Invalid input');
		//The structure is an array of associative ("isB","Question","Answer",...) arrays
		
		foreach($qarrArray as $qarr){
			if(!arrayKeysNotEmpty($qarr,array('isB','Subject','isSA','Question')))$this->err('addByArray: Missing parameters');
			
			//Check the validity of these.
			//Handle JS-side too...
			$qarr['isB']=($qarr['isB']==1);
			if(!array_key_exists($qarr['Subject'],$ruleSet['Subjects']))$this->user_err('Invalid subject');
			$qarr['isSA']=($qarr['isSA']==1);
			if($qarr['Question']=='')$this->user_err('Blank question');
			
			//Deal with MC vs SA answers
			if(!$qarr['isSA']){
				if(!arrayKeysNotEmpty($qarr,array('MCW','MCX','MCY','MCZ','MCa')))$this->err('Missing parameters.');
				if(!(array_key_exists($qarr['MCa'],$ruleSet['MCChoices'])))//Relies on the fact that $arr[1]==$arr['1']
					$this->user_err('Invalid MC answer chosen');
				$qarr['Answer']=$qarr['MCa'];//If it's an MC, the answer stored is 0,1,2,3 for W,X,Y,Z
			}else{
				if(!array_key_exists('Answer',$qarr))$this->err('Missing parameters.');
				if($qarr['Answer']=='')$this->user_err('Blank answer');
				$qarr['MCW']=$qarr['MCX']=$qarr['MCY']=$qarr['MCZ']=NULL;
			}
			
			//var_dump($qarr);
			//Hm. Start value for QID = 0.
			$qarr['QID']=0;
			$this->addAssocArr($qarr);
		}
		
		return $this;
	}
	
	//Generally doesn't need to be used. (works automatically)
	public function commit(){
		global $ruleSet;
		
		$rows=array();
		foreach($this->Questions as $qarr){
			if($qarr[0]!=0)continue;//only commit non-committed new ones, which have default QID 0.
			$rows[]=array(
				'isB'=>$qarr[1],'Subject'=>$qarr[2],'isSA'=>$qarr[3],
				'Question'=>$qarr[4],
				'MCW'=>$qarr[5],'MCX'=>$qarr[6],'MCY'=>$qarr[7],'MCZ'=>$qarr[8],
				'Answer'=>$qarr[9]
			);
		}
		if(count($rows)==0)return $this;//if there weren't any questions to commit
		
		DB::insert('questions',$rows);
		
		for($i=0;$i<count($this->Questions);$i++)//For every question, get its inserted id
			$this->Questions[$i][0]=DB::insertId+$i;//Set QIDs; adding $i because it only returns the first insert_id, and it's almost certainly consecutive
													//With Meekro it almost certainly still does so.
		
		//:( duplicate questions???
		//http://stackoverflow.com/questions/18932/how-can-i-remove-duplicate-rows no idea what it does
		DB::query('UPDATE questions SET Deleted=1 WHERE QID NOT IN (SELECT MIN(QID) FROM questions WHERE Deleted=0 GROUP BY Question)');
		
		return $this;
	}
	
	public function toHTML($i,$formatstr){//Return nice HTML for question $i, based on $formatstr replacements.
		if(empty($this->Questions)||count($this->Questions)==0)return '';
		global $ruleSet;
		$MCOptions='';
		//QID,isB,Subject,isSA,Question,MCW,MCX,MCY,MCZ,Answer
		//static $x=false;if(!$x){$x=true;var_dump($this->Questions);}
		return str_replace(
			array(
				'%N%',
				'%QID%',
				'%PART%',
				'%SUBJECT%',
				'%TYPE%',
				'%QUESTION%',
				'%W%',
				'%X%',
				'%Y%',
				'%Z%',//--todo-- make this extensible to non-WXYZ somehow
				'%ANSWER%','%ANSCHOICE%'
			),
			array(
				$i,
				$this->Questions[$i][0],
				$ruleSet['QParts'][intval($this->Questions[$i][1])],
				$ruleSet['Subjects'][intval($this->Questions[$i][2])],
				$ruleSet['QTypes'][intval($this->Questions[$i][3])],
				nl2br(strip_tags($this->Questions[$i][4])),
				(intval($this->Questions[$i][3]))?'':$this->Questions[$i][5],
				(intval($this->Questions[$i][3]))?'':$this->Questions[$i][6],
				(intval($this->Questions[$i][3]))?'':$this->Questions[$i][7],
				(intval($this->Questions[$i][3]))?'':$this->Questions[$i][8],
				(intval($this->Questions[$i][3]))?
					strip_tags($this->Questions[$i][9])//short answer, just there
					:$ruleSet['MCChoices'][$this->Questions[$i][9]].') '.$this->Questions[$i][5+$this->Questions[$i][9]],//mc, it's 0-3 of WXYZ
				//$ruleSet['MCChoices'][$this->Questions[$i][9]]
			),
			$formatstr);
		
		//--todo--test xss
	}
	public function allToHTML($formatstr){//Return nice HTML
		if(empty($this->Questions)||count($this->Questions)==0)return 'No questions selected';
		$ret='';
		for($i=0;$i<count($this->Questions);$i++)$ret.=$this->toHTML($i,$formatstr);
		return $ret;
	}
	
	//returns QIDs of the questions
	public function getQIDs(){if(empty($this->Questions)||count($this->Questions)==0)return array();return array_map(array($this,'getQID'),range(0,count($this->Questions)-1));}
	public function getQID($i){if(empty($this->Questions)||count($this->Questions)==0)return 0;return $this->Questions[$i][0];}
	
	
	//Error handler
	//--todo-- NEED A SEPARATE ERROR HANDLER FOR ERRORS YOU PASS BACK TO THE USER
	//--todo-- CAN YOU PASS THE LINE NUMBER OF THE ERROR ON TO THE NEXT ERROR FUNCTION?
	private function err($str){
		err('qIO: '.$str);
	}
	private function user_err($str){$this->error=$str;}
	//Returns the size.
	public function count(){
		if(empty($this->Questions))return 0;return count($this->Questions);
	}
	
	public function markBad($i=-1){//Rate question $i. Default rate all.
		static $rated=array();
		
		if($i===-1){//Default action: update ALL.
			$range=range(0,count($this->Questions)-1);
			$this->updateIs(array_diff($range,$rated),'MarkBad=MarkBad+1');
			$rated=$range;
			return $this;
		}
		
		if(array_key_exists($i,$rated))return $this;//Don't re-rate it.
		DB::query('UPDATE questions SET markBad=MarkBad+1 WHERE QID=%i LIMIT 1',$this->Questions[$i][0]);
		$rated[$i]=true;
		
		return $this;
	}
	private function updateQIDs($qids,$setstr){
		if(empty($this->Questions)||count($this->Questions)==0)return;
		//$setstr is risky.
		
		$where=new WhereClause('or');
		foreach($qids as $qid)
			$where->add("QID=%i",$qid);
		DB::query("UPDATE questions SET $setstr WHERE (%l) LIMIT %i",$where,count($this->Questions));
	}
	private function updateIs($is,$setstr){
		if(empty($this->Questions)||count($this->Questions)==0)return;
		//$setstr is risky.
		$where=new WhereClause('or');
		foreach($is as $i)
			$where->add("QID=%i",$this->Questions[$i][0]);
		DB::query("UPDATE questions SET $setstr WHERE (%l) LIMIT %i",$where,count($this->Questions));
	}
};
function getExportSize(){
	$a=DB::queryFirstField('SELECT COUNT(*) FROM questions WHERE Deleted=0');//Not including deleted ones!
	return 'Estimated size: '.($a/3).'KB';//about 3000 bytes estimated per question. Eh.
}
function exportQuestionsCSV(){
	DB::query("SELECT * INTO OUTFILE 'questionsExport.csv' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\r\n' FROM questions WHERE Deleted=0");
}

?>