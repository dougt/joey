
/* 
 * Utility function used by the operations associated with each type-handler. 
 * such as RSS, Image, MP3, Text, and more.
 */
function joeyPage_toggleIcon(iconReference,toolTip,cssClass,onClick) {

    


}

function joeyMedia_newWindow(urlRef) {

	var newWindow = window.open("about:blank","title");
	newWindow.document.open("text/html");
	newWindow.document.write("<img class='joeymedia-previewimage' src='"+urlRef+"' />");
	newWindow.document.close();

}

/* Helper function to show Text */

function joeyMedia_textShow(filename,uploadIDHandler) {

	// icon image ID format is: "iconhandler-" + uploadIDHandler

	document.getElementById("expandItem-"+ uploadIDHandler).innerHTML="<iframe width='320' height='240' class='joeymedia-previewtext' id='joeymedia-textdisplayed-"+uploadIDHandler+"' class='joeymedia-previewimage' src='"+filename+"' />";


}
/* Helper function to show images */

function joeyMedia_imageShow(filename,uploadIDHandler) {

	// icon image ID format is: "iconhandler-" + uploadIDHandler

	document.getElementById("controlOptions-"+uploadIDHandler).innerHTML="<a href='javascript:' onclick='joeyMedia_newWindow(\""+filename+"\");return false;'>Open in new window</a>";
	document.getElementById("expandItem-"+ uploadIDHandler).innerHTML="<img id='joeymedia-imagedisplayed-"+uploadIDHandler+"' class='joeymedia-previewimage' src='"+filename+"' />";


}

function joeyMedia_changeImage(width,elementRef) {
	document.getElementById(elementRef).style.width=width+"px";
}




/* Joey Bag - JavaScript functions to support the Joey Inner-Browsing Experience */


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

/* 
 * new Joey mediaplayer JS + flash video player implementation 0.2
 * 
 */
 
var seekMax = 266 - 20; /* Maximu */
var seekInit = 2;

var gMediaPlayerArray=new Array();

function joeyMedia_mediaplayer_updateControls(referenceElement,timeInfo) {

    var currentPlayer = gMediaPlayerArray[referenceElement];
    
        var videoDuration = currentPlayer.videoDuration;

		var left = seekInit + parseInt((timeInfo/videoDuration)*seekMax);
		
		currentPlayer.thumbSlider.style.left = left + "px";
       
       
       
}


function joeyMedia_mediaplayer_toggle(videoId,itemId) {

    if(gMediaPlayerArray[itemId]) {
        joeyMedia_mediaplayer_destroyPlayer(itemId);
    } else {
        joeyMedia_mediaplayer_createPlayer(itemId,videoId);    
    }

}

function joeyMedia_mediaplayer_playpause(itemId) {

    var currentPlayer = gMediaPlayerArray[itemId];
    
    if(currentPlayer.flashObject==null) {
        currentPlayer.flashObject = document.getElementById(currentPlayer.flashid);
    }

   if(currentPlayer.playing==true) {
  
        currentPlayer.flashObject.SetVariable("vp_function_pauseresume","go");
        currentPlayer.playButton.className="videobutton-play";  
        currentPlayer.playing = false;
        
    } else {
    
        currentPlayer.playing = true;
        currentPlayer.playButton.className="videobutton-pause";      
        currentPlayer.flashObject.SetVariable("vp_function_pauseresume","go");
        
    }
    
}


function joeyMedia_mediaplayer_stop(itemId) {

    var currentPlayer = gMediaPlayerArray[itemId];
    if(currentPlayer.flashObject==null) {
        currentPlayer.flashObject = document.getElementById(currentPlayer.flashid);
    }

   if(currentPlayer.playing==true) {
        currentPlayer.flashObject.SetVariable("vp_function_pauseresume","go");
        currentPlayer.playButton.className="videobutton-play";  
        currentPlayer.playing = false;
        currentPlayer.flashObject.SetVariable("form_seekPosition",0);
        currentPlayer.flashObject.SetVariable("vp_function_seek","go");
        
    } else {
        currentPlayer.flashObject.SetVariable("form_seekPosition",0);
        currentPlayer.flashObject.SetVariable("vp_function_seek","go");
    }
    joeyMedia_mediaplayer_updateControls(itemId,0);
}

function joeyMedia_mediaplayer_createPlayer(itemId,videoId) {

        /* Remove this: test only */
        /* RRR */
        
        videoId = "/js/test.flv";

        var width = 320;
        var height = 240;   
 	    var strVideoEmbed = ' <div id="mediaplayer-'+itemId+'" style="text-align:center;" class="videoPlayer"> <object align="middle" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" id="videoplayerobject-'+itemId+'" width="'+width+'" height="'+height+'"><param value="/app/webroot/vendor/webflv.swf?fileName='+videoId+'&refId='+itemId+'" name="movie"><param value="high" name="quality"><param value="true" name="swLiveConnect"><param value="#000000" name="bgcolor"> <embed pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" allowScriptAccess="sameDomain" align="middle" bgcolor="#000000" swLiveConnect="true" quality="high" src="/app/webroot/vendor/webflv.swf?fileName='+videoId+'&refId='+itemId+'" mayscript="true" id="videoplayerembed-'+itemId+'" width="'+width+'" height="'+height+'"></embed> </object> </div>' ;
	    var el = document.getElementById("expandItem-"+itemId);
	 
	    el.style.display="block";
	    el.innerHTML = strVideoEmbed;

	    document.getElementById("joeyVideoPlayerController-"+itemId).innerHTML="close";

        var flashid="videoplayerembed-"+itemId;

	    if(document.all) { 
			    flashid="videoplayerobject-"+itemId;
	    } 
	    
	    /* 
	     * We create the flash video object 
	     */
	     
	    gMediaPlayerArray[itemId] = { 
	    
	        flashid:flashid,
	        itemId:itemId,
	        flashObject:null,
	        expandDiv:el,
	        playing:false,
	        playButton:null,
	        videoDuration:null,
	        thumbSlider:null
	    };
    
        var playerElement = document.createElement("div");
        playerElement.setAttribute("style","position:relative;width:314px;height:24px;");
        
        var playButton = document.createElement("a");
        playButton.setAttribute("class","videobutton-pause");
        playButton.setAttribute("href","javascript:");
        playButton.setAttribute("style","position:absolute;top:2px;left:2px;width:22px;height:22px;");
        playButton.setAttribute("onclick","joeyMedia_mediaplayer_playpause('"+itemId+"');return false");   

        playerElement.appendChild(playButton);

        gMediaPlayerArray[itemId].playButton = playButton; 
        
        var rewindInit = document.createElement("a");
        rewindInit.setAttribute("class","videobutton-stop");
        rewindInit.setAttribute("href","javascript:");
        rewindInit.setAttribute("style","position:absolute;top:2px;left:26px;width:22px;height:22px;");     
        rewindInit.setAttribute("onclick","joeyMedia_mediaplayer_stop('"+itemId+"');return false");   
        playerElement.appendChild(rewindInit);

        var sliderElement = document.createElement("div");
        sliderElement.setAttribute("style","position:absolute;border:1px solid gray;top:4px;left:52px;background-color:black;width:267px;height:18px;");
        playerElement.appendChild(sliderElement);
       
        var dragElement = document.createElement("div");
        dragElement.setAttribute("class","videobutton-thumb");
        dragElement.setAttribute("style","position:absolute;width:18px;height:16px;");
        sliderElement.appendChild(dragElement);
  
        gMediaPlayerArray[itemId].thumbSlider = dragElement;
        
        document.getElementById("mediaplayer-"+itemId).appendChild(playerElement);        

        gMediaPlayerArray[itemId].playing = true;
           
        joeyMedia_mediaplayer_kickDuration();   
           
}

function joeyMedia_mediaplayer_kickDuration() {

    for(var key in gMediaPlayerArray) {
    
        var currentPlayer = gMediaPlayerArray[key];
        try { 
            if(currentPlayer.playing) { 
                if(currentPlayer.flashObject==null) {
                    currentPlayer.flashObject = document.getElementById(currentPlayer.flashid);
                }     
                currentPlayer.flashObject.SetVariable("vp_function_checktime","go");
            }
        } catch (i) {
            /* nothing yet */
 
        }
    
    }

    setTimeout("joeyMedia_mediaplayer_kickDuration()",1000);
    
}

function joeyMedia_mediaplayer_destroyPlayer(itemId) {

    var currentPlayer = gMediaPlayerArray[itemId];
    
    currentPlayer.expandDiv.innerHTML="";
	currentPlayer.expandDiv.style.display="none";
	
	document.getElementById("joeyVideoPlayerController-"+itemId).innerHTML="play";

	gMediaPlayerArray[itemId]=null;

}

function visinoteembed_DoFSCommand(command, args) {

    if(command=="flashready") {
   
        
    }

    if(command=="filename") {
    
        
        
    }
	if(command=="timeevent") {

        var argsArray = args.split("::");
        
        var timeInfo = parseFloat(argsArray[0]);
        var referenceElement = argsArray[1];
        
        joeyMedia_mediaplayer_updateControls(referenceElement,timeInfo);

	}

	if(command=="bufferlength") {


	}


	if(command=="onmetadata") { 

              var metaString = args.split("::");
              var referenceId = metaString[0];
              var objectString = metaString[1];

              if(objectString == "duration") {

                      var durationTime = metaString[2];

                      /* Global for current max duration time for the current video */ 
                      
                      var video_timemax = parseFloat(durationTime);
                      
                      gMediaPlayerArray[referenceId].videoDuration=video_timemax; 
                      
              }


	} 
}


/*
 * RssViewer Function  
 * This is Taken from Mozilla Minimo 
 */

/* 
 * Rss Fetch is the main Global Function
 * It uses the blenderObject class to simply mix XSLT with XML. In this version, 
 * the XSLT template is provided here in the code, inlined. Check the following 
 * stringXMLtemplate. 
 */

function joeyMedia_rssfetch(targetDoc, targetElementId, refDocument, itemId) {

	
	var targetElement = document.getElementById(targetElementId);
	if(targetElement.className!="loaded") {

       
	var stringXMLtemplate = '<'+'xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:rss="http://purl.org/rss/1.0/" > <xsl:output method="html" indent="yes"/> <xsl:template match="/"> <div>  <xsl:for-each select="/rdf:RDF/rss:channel"> <div id="pagetitle" style="display:none"><xsl:value-of select="rss:title"/></div> </xsl:for-each> <xsl:for-each select="/rdf:RDF/rss:channel"> <div style="padding:.3em;"><xsl:value-of select="rss:description"/></div> </xsl:for-each> <xsl:for-each select="/rdf:RDF/rss:item"> <div class="item"> <a> <xsl:attribute name="href"> <xsl:value-of select="rss:link"/> </xsl:attribute> 	<xsl:value-of select="rss:title"/> </a> </div> </xsl:for-each> <xsl:for-each select="/rss/channel/title"> <div id="pagetitle" style="display:none"><xsl:value-of select="."/></div> </xsl:for-each> <xsl:for-each select="/rss/channel/description"> <div style="padding:.3em;"><xsl:value-of select="."/></div> </xsl:for-each> <xsl:for-each select="/rss/channel/item"> <div class="item"> <a> <xsl:attribute name="href"> <xsl:value-of select="link"/> </xsl:attribute> 	<xsl:value-of select="title"/> </a> </div> </xsl:for-each> </div> </xsl:template> </xsl:stylesheet>' ;

	var testLoad=new blenderObject();

	testLoad.xmlSet(refDocument);
	testLoad.xslSerialize(stringXMLtemplate);

	testLoad.setTargetDocument(targetDoc);
	testLoad.setTargetElement(document.getElementById(targetElementId));



	testLoad.setCallback(function () { 


		var elementCloseButton = document.getElementById("joeyPlayerController-"+itemId);
		elementCloseButton.innerHTML="close";
		
 		var elementTarget = document.getElementById(targetElementId);

		elementTarget.className='loaded';
			

	});


	testLoad.setCallbackLoading( function () {

		
	document.getElementById(targetElementId).setAttribute("class","joey-loading");
	document.getElementById(targetElementId).setAttribute("style","display:block;width:90%;;border:1px solid gray;background-color:#444444;padding:1em;padding-left:3em;;margin:.5em ;");

	document.getElementById(targetElementId).innerHTML="Loading ..";

	});


	testLoad.run();


	} else {

	
                var elementCloseButton = document.getElementById("joeyPlayerCloseButton-"+itemId);
                elementCloseButton.innerHTML="";

                document.getElementById(targetElementId).className='';
                document.getElementById(targetElementId).innerHTML='';
		document.getElementById(targetElementId).style.display='none';


                var elementLoadingButton = document.getElementById("joeyPlayerController-"+itemId);
                elementLoadingButton.innerHTML="Open";
                elementLoadingButton.setAttribute("class","");



	}

}

////
/// loads the XSL style and data-source and mix them into a new doc. 
//

function blenderObject() {

	this.xmlHttp = new XMLHttpRequest();

	this.xmlRef = null;

	this.xslRef=document.implementation.createDocument("http://www.w3.org/1999/XSL/Transform","stylesheet",null);

	this.xmlUrl="";
	this.xslUrl="";

	var myThis=this;

	var lambda=function thisScopeFunction() { myThis.xmlLoaded(); }
	var omega=function thisScopeFunction2() { myThis.xslLoaded(); }
	var gamma = function thisScopeFunction3(e) { myThis.xmlLoading(e); } 

	this.xslRef.addEventListener("load",omega,false);

	this.xmlHttp.onreadystatechange = gamma;


	this.xmlLoadedState=false;
	this.xslLoadedState=false;

}

blenderObject.prototype.xmlLoaded = function () {
	this.xmlLoadedState=true;
	this.apply();
}

blenderObject.prototype.setCallback = function (callbackRefFunction) {
	this.callbackRefFunction = callbackRefFunction;
}

blenderObject.prototype.setCallbackLoading = function (callbackRefFunction) {
	this.callbackRefLoadingFunction = callbackRefFunction;
}

blenderObject.prototype.xslSerialize = function (stringXML) {

	this.xslLoadedState=true;
	var parserXML=new DOMParser();
	this.xslRef = parserXML.parseFromString(stringXML,"text/xml");
}

blenderObject.prototype.xslLoaded = function () {
	this.xslLoadedState=true;
	this.apply();
}

blenderObject.prototype.xmlLoading = function (e) {

	if(this.xmlHttp.readyState ==4 ) {

		if(this.xmlHttp.status == 200) {

			this.xmlRef = this.xmlHttp.responseXML;

			this.xmlLoadedState=true;
			this.apply();
			return;

		}
	}

	if(this.xmlHttp.readyState == 1 ) {
		this.callbackRefLoadingFunction();
	}



}

blenderObject.prototype.xmlSet = function (urlstr) {
	this.xmlUrl=urlstr;
}

blenderObject.prototype.xslSet = function (urlstr) {
	this.xslUrl=urlstr;
}

blenderObject.prototype.setTargetDocument = function (targetDoc) {
	this.targetDocument=targetDoc;
}

blenderObject.prototype.setTargetElement = function (targetEle) {
	this.targetElement=targetEle;
}

blenderObject.prototype.apply = function () {
	if(this.xmlLoadedState&&this.xslLoadedState) {

		var xsltProcessor = new XSLTProcessor();
		var htmlFragment=null;
		try {
			xsltProcessor.importStylesheet(this.xslRef);
			htmlFragment = xsltProcessor.transformToFragment(this.xmlRef, this.targetDocument);

			this.targetElement.setAttribute("style","display:block;width:90%;;border:1px solid gray;background-color:#444444;padding:1em;margin:.5em;");
			this.targetElement.setAttribute("class","");

			this.targetElement.innerHTML="";
  		      this.targetElement.appendChild(htmlFragment.firstChild);

			this.callbackRefFunction();

		} catch (e) {

			// This can dispatch event in case of some failure in the processing...

		}




	}
}

blenderObject.prototype.run = function () {
	try {


		this.xmlHttp.open('GET', this.xmlUrl, true); 
		this.xmlHttp.send(null);

	} catch (e) {


	}

}




