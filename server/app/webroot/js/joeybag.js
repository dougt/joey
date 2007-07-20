/* 
 *  Joey Ajax and control functions
 */ 

function execJoey(scriptInfo) {

        var showLimit = scriptInfo.split(",")[1];
        var pageFrom = scriptInfo.split(",")[0];
        var pageType = scriptInfo.split(",")[2];

        var command = scriptInfo.split(',')[3];


	if(command == "okay-reload") {

	        new Ajax.Updater('content',"/uploads/index?show="+showLimit+"&page="+pageFrom+"&type="+pageType,{asynchronous:true,evalScripts:true, requestHeaders:['X-Update','content']});


	} else {
		alert('Opa! I believe Joey failed to delete - or something - please report this problem' );
	} 


}

/* 
 * This is joey project  - the Web FlV vidoe player functions
 */ 

var gCurrentControlId = null;
var gCurrentElement = null;

function joeyMedia_destroyPlayer(itemId) {

	//remove the player from the markup
	document.getElementById("expandItem-"+itemId).innerHTML="";
	document.getElementById("expandItem-"+itemId).style.display="none";

	
	document.getElementById("joeyVideoPlayerController-"+itemId).innerHTML="play";
        document.getElementById("joeyVideoCloseButton-"+itemId).innerHTML="";

	gJoeyMediaHash[itemId]=null;

}


function joeyMedia_initPlayer(itemId){


 	var strVideoEmbed = ' <div id="singleVideo" style="text-align:center;" class="videoPlayer"> <object align="middle" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" id="videoplayerobject" width="320" height="240"><param value="/app/webroot/vendor/webflv.swf" name="movie"><param value="high" name="quality"><param value="true" name="swLiveConnect"><param value="#000000" name="bgcolor"> <embed pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" allowScriptAccess="sameDomain" align="middle" bgcolor="#000000" swLiveConnect="true" quality="high" src="/app/webroot/vendor/webflv.swf" mayscript="true" id="videoplayerembed" width="320" height="240"></embed> </object> </div>' ;


	var el = document.getElementById("expandItem-"+itemId);

	el.style.display="block";

        gCurrentControlId = itemId;
	gCurrentElement = "expandItem-"+itemId;

	el.innerHTML = strVideoEmbed;

	document.getElementById("joeyVideoCloseButton-"+itemId).innerHTML="<a href='javascript:' onclick='joeyMedia_destroyPlayer(\""+itemId+"\");return false;'> close</a>";

	if(document.all) { 
			gBrowserFlashID="videoplayerobject";
	} else {
			gBrowserFlashID="videoplayerembed";	
	}


}

function findPos(obj) {
	var curleft = curtop = 0;
	if (obj.offsetParent) {
		curleft = obj.offsetLeft
		curtop = obj.offsetTop
		while (obj = obj.offsetParent) {
			curleft += obj.offsetLeft
			curtop += obj.offsetTop
		}
	}
	return [curleft,curtop];
}

var form_playURL = null;
var oldX=0;
var video_timemax;
var seekMax = 376;
var pauseFlop = true;
var form_headTime;
var form_playing=false;

function joeyMedia_delayedVideoPlay(videoId,itemId) {

	gJoeyMediaHash[itemId]=1;
	joeyMedia_updateControl(itemId,"pause");
	setTimeout("videoPlay('"+videoId+"')",2000);

}


function videoPlay(videoId,timeStamp) {

	form_playURL = videoId
	getFlash().SetVariable("form_fileURL", form_playURL);
	getFlash().SetVariable("form_bufferTime",5);
	getFlash().SetVariable("form_seekPosition",0);
	getFlash().SetVariable("vp_function_seek","go");
	getFlash().SetVariable("vp_function_play","go");
	getFlash().SetVariable("vp_function_seturl","go");

	form_playing = true; 

}

function videoCheckTime() {
	if(form_playing) {
		getFlash().SetVariable("vp_function_checktime","go");
	} 
}

function videoSeek(timePosition, videoid) {

	/* if not initialized */ 
	if(!form_playURL) {
	
		videoPlay(videoid,0);
	
	}
	newTimePosition = timePosition;
	getFlash().SetVariable("form_seekPosition",newTimePosition);
	getFlash().SetVariable("vp_function_seek","go");
}

function videoTryPause() {
	if(pauseFlop) { 
		form_playing=false;
		pauseFlop=false;
		getFlash().SetVariable("vp_function_pauseresume","go");
	}
}


var gJoeyMediaHash = new Array();
var gCurrentVideoPlaying = null;

function joeyMedia_videoPlayPause(videoId,itemId) {

	if(  gJoeyMediaHash[itemId] > 0) {

		videoPlayPause();
		return;

	} 

	if( !gCurrentVideoPlaying ) {

                joeyMedia_initPlayer(itemId);
                joeyMedia_delayedVideoPlay(videoId,itemId);
		gCurrentVideoPlaying = itemId;

		
	} else {

		gJoeyMediaHash[gCurrentVideoPlaying]=null;
		joeyMedia_destroyPlayer(gCurrentVideoPlaying);


		gCurrentVideoPlaying = itemId;

		joeyMedia_initPlayer(itemId);	
		joeyMedia_delayedVideoPlay(videoId,itemId);

	}
	
}

function joeyMedia_updateControl(itemId,toString) {

	  document.getElementById("joeyVideoPlayerController-"+itemId).innerHTML=toString;

}

function videoPlayPause() {

	if(gJoeyMediaHash[gCurrentControlId]==1) { 

		document.getElementById("joeyVideoPlayerController-"+gCurrentControlId).innerHTML="play";	
		gJoeyMediaHash[gCurrentControlId]=2;


	} else {

		document.getElementById("joeyVideoPlayerController-"+gCurrentControlId).innerHTML="pause";	
		gJoeyMediaHash[gCurrentControlId]=1;

	}

	getFlash().SetVariable("vp_function_pauseresume","go");


}

function init() {

	resize();

	video_timemax = parseFloat(document.getElementById("videolength").innerHTML);
	document.getElementById("timeshow").innerHTML = parseInt(document.getElementById("videolength").innerHTML);

	init_seeker();
	
	gAllowPlay= true;
	
}	

gAllowPlay= false;
gCurrentPlaying = 0;
gCurrentVideo = null;

function init_seeker() {

	// soon support dragger IE. 

	if(!document.all) {

		document.getElementById("dragpoint").addEventListener("mousedown",seekerClick,false);

	}
}

function seekerClick(e) {
	videoTryPause();
	oldX=e.clientX;
	document.addEventListener("mousemove",seekerMove,false);
	document.addEventListener("mouseup",seekerUp,false);
	seekerDragging=true;
}


var seekerDragging = false;


function seekerMove(e) {


	newX=e.clientX;

	deltaX=newX-oldX;

	oldX=newX;

	markupLeft = parseInt(document.getElementById("dragpoint").style.left);

	markupLeft+=deltaX;

	if(markupLeft>=0 && markupLeft <= seekMax) {

		document.getElementById("dragpoint").style.left=markupLeft+"px";
	}

}

function seekerUp(e) {

	document.removeEventListener("mousemove",seekerMove,false);
	document.removeEventListener("mouseup",seekerUp,false);

	positionSec =  ( parseInt(document.getElementById("dragpoint").style.left) / seekMax )* video_timemax ;

	seekerDragging = false;
	videoSeek(positionSec);
	form_headTime = positionSec;
	visual_updateDisplay(positionSec);

}

function seekerTryUpdate() {
	if(!seekerDragging) {

		left=parseInt((form_headTime/video_timemax)*seekMax);

		document.getElementById("dragpoint").style.left = left+"px";

	}
}

function visual_updateDisplay(vv) {

		document.getElementById("timeshow").innerHTML=vv;

}

function getFlash() {
      return document.getElementById(gBrowserFlashID);
}

function visinoteembed_DoFSCommand(command, args) {

	if(command=="timeevent") {

		gCurrentPlaying = args;

		//visual_updateDisplay(args);
		//form_headTime=args;
		//seekerTryUpdate();
		//if(form_playing) {setTimeout("videoCheckTime()",1000);} 

	}

	if(command=="bufferlength") {


	}
}

