<?php
define('ROOT_PATH','');
require_once ROOT_PATH.'functions.php';
restrictAccess('u');//xuca
/*
round.php
Conducts a live online round.
*/


if(posted('get')){

}
elseif(posted('give')){

}



?>
<div id="chatbox"></div>
<input type="text" onkeypress="checkChatKey()"/>
<script>
jQuery.extend({
   postJSON: function( url, data, callback) {
      return jQuery.post(url, data, callback, "json");
   }
});

function getNext(){
	var obj=$.postJSON('round.php',{get:true,current:window.current});
	obj.newItems.forEach(
}
function checkChatKey(e){
if(!e)var e=window.event;
if(e.keyCode==13){addNext(this.value);this.value="";}
}
function addNext(text){
	var obj=$.postJSON('round.php',{give:true,text:text});
}

</script>



