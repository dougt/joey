/* by Mgalli, for the JOey projext, this is to be kept with the same MPL */



var gCurrentControlId = null;
var gCurrentElement = null;

function joeyMedia_hidePlayer() {


	var itemId = gCurrentControlId;
	var refElement = gCurrentElement;

	var el = document.getElementById(refElement);

	el.style.display="none";

	document.getElementById("singleVideo").style.left="-400px";
	document.getElementById("singleVideo").style.top="-400px";

	document.getElementById("joeyVideoPlayerController-"+itemId).innerHTML="play";
	document.getElementById("joeyVideoCloseButton-"+itemId).innerHTML="";

	videoTryPause();
	form_playURL = null;


}

function joeyMedia_initPlayer(itemId){


	var el = document.getElementById("expandItem-"+itemId);

	el.style.display="block";
	el.style.height="260px";
	el.style.width="360px";

        gCurrentControlId = itemId;
	gCurrentElement = "expandItem-"+itemId;

	document.getElementById("joeyVideoCloseButton-"+itemId).innerHTML="<a href='javascript:' onclick='joeyMedia_hidePlayer();return false;'> close</a>";


	if(!document.all) {
		var embed = document.getElementById("videoplayerembed")
	}
	
	if(document.all) { 
			gBrowserFlashID="videoplayerobject";
	} else {
			gBrowserFlashID="videoplayerembed";	
	}


 	var pos=findPos(document.getElementById(gCurrentElement));

	document.getElementById("singleVideo").style.left=pos[0]+20+"px";
	document.getElementById("singleVideo").style.top=pos[1]+"px";

        gAllowPlay= true;


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




function videoPlay(videoId,timeStamp) {

	form_playURL = videoId

	getFlash().SetVariable("form_fileURL", form_playURL);

	getFlash().SetVariable("form_bufferTime",5);

	getFlash().SetVariable("vp_function_play","go");
	getFlash().SetVariable("vp_function_seturl","go");

	form_playing = true; 

	setTimeout("videoCheckTime()",2000);


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

function joeyMedia_videoPlayPause(videoId,itemId) {

	if(gAllowPlay) {


			if(form_playURL != videoId ) {

				gCurrentPlaying = 0;

				if(form_playURL)  {

					joeyMedia_hidePlayer();	

				}
				joeyMedia_initPlayer(itemId);
				document.getElementById("joeyVideoPlayerController-"+gCurrentControlId).innerHTML="pause";
				videoPlay(videoId);

			} else {	

			

			if(form_playURL) {

				videoPlayPause();

			} else {
				
				videoPlay(videoId,0);

			}


			}



	} else {

		joeyMedia_initPlayer(itemId);

		joeyMedia_videoPlayPause(videoId,itemId);

	}
}

function videoPlayPause() {

    
	if(pauseFlop) { 
		document.getElementById("joeyVideoPlayerController-"+gCurrentControlId).innerHTML="play";	
		form_playing=false;
		pauseFlop=false;
	} else {
		form_playing=true;
		document.getElementById("joeyVideoPlayerController-"+gCurrentControlId).innerHTML="pause";	
		setTimeout("videoCheckTime()",1000);
		pauseFlop=true;		
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

