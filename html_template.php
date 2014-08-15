<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>%title% | DOE Question Database %version%</title>
        <meta name="description" content=''>
        <meta name="viewport" content="width=device-width">

        <!-- Place favicon.ico and apple-touch-icon.png in the root directory -->

        <link rel="stylesheet" href="%root%css/normalize.css">
		<link rel="stylesheet" href="%root%css/style.css"/>
		
        <script src="%root%js/modernizr-2.6.2.min.js"></script>
    </head>
    <body class="noJQuery">
        <!--[if lt IE 7]>
            <p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p>
        <![endif]-->

		<form id="bugrept" action='%root%bugs.php' method='POST'>
			<b>Bug Report/Feature Request:</b>
			<img src='%root%img/loadingCircle.gif' id='loadimg' style='display:none;width:100%;' alt='Loading...' />
			<div id="thanksmessage" style="display:none;">Submitted! Thanks for the report; we'll be on it immediately.</div>
			<div id="hidden">
			<div class="text"><b>Tell us everything about the bug:</b> what happened and what should have, where, on what browser/system, how we might reproduce it, etc.</div>
			<div class="text"><b>Or suggest an idea!</b> Tell us anything you can think of!</div>
			<textarea name="bug" id="bugrepttext"></textarea>
			<input id="bugsubbtn" type="submit" value="Send"/>
			</div>
			<a href="#" id="clicker"></a>
		</form>
		<div id="main-wrapper">
			<h1>%title%</h1>
			<div id="nav-wrapper">%nav%</div>
			<br>
			<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
			<script>window.jQuery || document.write('<script src="%root%js/jquery.min.js"><\/script>')</script>
			<script>$(function(){
				$("#bugrept #hidden").hide();
				$("#bugrept #clicker").text("[expand]").click(function(){
					if(this.innerHTML=="[expand]"){
						this.innerHTML="[close]";
						$(this).siblings("#hidden").show();
					}
					else{
						this.innerHTML="[expand]";
						$(this).siblings("#hidden").hide();
					}
					if(!e)var e=window.event;
					e.preventDefault();
					return false;
				});
				window.bugthanksorigheight=$('#bugrept #thanksmessage').css('height');
				$("#bugrept #bugsubbtn").click(function(e){
					if($('#bugrepttext').text()!=''){
						$.post('bugs.php',{bug:$('#bugrepttext').text()}).done(function(){
							$('#bugrept #clicker').text('[expand]').show();
							$('#bugrept #thanksmessage').show();
							$('#bugrepttext').text('');
							
							$('#bugrept #loadimg').hide();
							setTimeout(function(){
								$('#bugrept #thanksmessage').animate({height:'1px',display:'none'});
								setTimeout(function(){
									$('#bugrept #thanksmessage').hide().css({height:window.bugthanksorigheight});
								},400);
							},5000);
						});
						$('#bugrept #hidden, #bugrept #clicker').hide();
						$('#bugrept #loadimg').show();
					}
					
					if(!e)var e=window.event;
					e.preventDefault();
					return false;
				});
			});</script>
			<div id="content">
			%content%
			</div>
		</div>
		<div id="footer">Copyright &copy;2013-present Lexington Science Bowl Team. All rights reserved.</div>
		<div style='font-size:0.7em;text-align:center;'>Page took %loadtime% ms to load.</div>
		<br>
    </body>
</html>
