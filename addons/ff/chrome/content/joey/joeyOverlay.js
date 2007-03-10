/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Joey Mozilla Project.
 *
 * The Initial Developer of the Original Code is
 * Doug Turner <dougt@meer.net>.
 * Portions created by the Initial Developer are Copyright (C) 2007
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

var g_joey_name;
var g_joey_data;
var g_joey_data_size;
var g_joey_content_type;
var g_joey_title;
var g_joey_url;
var g_joey_uuid;
var g_joey_binary;

var g_joey_areaWindow = null;

// Recently added by marcio...

var g_joey_gBrowser = null;                // presents the main browser, used by the joey_feed code.
var g_joey_browserStatusHandler = null;    // to track onloction changes in the above browser ( tab browser ) element.
var g_joey_statusUpdateObject = null;      // the proxy object to deal with UI 
var g_joey_historyArray = [];              // This is a very simple version of some sort of history array. So far we put Dougt's alert info in here. 
/* 
 * Event listeners associated to the joeyOverlay app 
 */

window.onmousedown = joeyOnMouseDown; // this may prevent other onmousedown associations. Fixthis
window.addEventListener("load", joeyStartup, false);


var gImageSource;

function joey_listener() {}

joey_listener.prototype =
{
    onStatusChange: function (name, uri, status)
    {

        if (status == 4)
        {
		// this s when the listener is set ( I believe this may be all the Sending conditions ) 
		// login busy
            g_joey_statusUpdateObject.busyMore();

        }
        if (status == 5)
        {
		// this s when the listener is unset  
		// login busy
            g_joey_statusUpdateObject.busyLess();

        }

        if (status == 3)
        {

		// login busy
            g_joey_statusUpdateObject.busyMore();

        }

        if (status == 0)
        {

		// login 0 status worked 
		// removing the busy load 

		g_joey_statusUpdateObject.loginStatus("login");
 		g_joey_historyArray.push("Login fine.");
            g_joey_statusUpdateObject.busyLess();

        }

        if (status == 2)
        {
		// files busy
            g_joey_statusUpdateObject.busyMore();

		// In the future we may want to track/log information about the elements uploaded
            // and build/keep some sort of history.
		g_joey_statusUpdateObject.inventoryMore();

        }

        if (status == 1)
        {

	      g_joey_historyArray.push(name + " " + uri + " " + status);

		// files not busy 

            var joey = Components.classes["@mozilla.com/joey;1"]
                                    .getService(Components.interfaces.mocoJoey);
            joey.setListener(null);


            g_joey_statusUpdateObject.busyLess();

        }

        if (status == -1 ) {

		g_joey_statusUpdateObject.loginStatus("logout");
		g_joey_historyArray.push("Login error -1");

        }

    },

    QueryInterface: function (iid) {
        if (iid.equals(Components.interfaces.mocoJoeyListener) ||
            iid.equals(Components.interfaces.nsISupports))
            return this;

        Components.returnCode = Components.results.NS_ERROR_NO_INTERFACE;
        return null;
    },

};


function uploadDataFromGlobals()
{
    var joey = Components.classes["@mozilla.com/joey;1"]
                            .getService(Components.interfaces.mocoJoey);

    joey.setListener(new joey_listener());

	if (g_joey_binary)
	{
		joey.uploadBinaryData(g_joey_name,
							   g_joey_title,
							   g_joey_url,
							   g_joey_data,
							   g_joey_data_size,
							   g_joey_content_type,
							   g_joey_uuid);
	}
	else
	{
	    joey.uploadData(g_joey_name,
						   g_joey_title,
						   g_joey_url,
						   g_joey_data,
						   g_joey_data_size,
						   g_joey_content_type,
						   g_joey_uuid);

	}
}


function joeyOnMouseDown(e)
{
	if (e.which == 3) 
	{
		var target = e.target;
		var classname = e.target.toString();
		if (classname.match(/ImageElement/)) {
			// Simpler, but probably less efficient syntax: target.src;
			var hie = target.QueryInterface(Components.interfaces.nsIDOMHTMLImageElement);
			if (hie != null)
				// show menu item:
				setImageSource(hie);
			else
				setImageSource(null);
		} else
            setImageSource(null);
	}
}

function setImageSource(imageElement)
{
	if (imageElement != null)
		gImageSource = imageElement.src;
	else
		gImageSource = null;

    try {    
    	var menuItem = document.getElementById('g_joey_selectedImage');
	    menuItem.setAttribute("hidden", gImageSource == null ? "true" : "false");
    } catch (e) {}
}

function replaceAll( str, from, to ) {
    // regular expression faster?
    
    var idx = str.indexOf( from );
    
    while ( idx > -1 ) {
        str = str.replace( from, to );
        idx = str.indexOf( from );
    }
    
    return str;
}

function joey_buildMenuHistoryContainer() {

  for (var i = 0; i < g_joey_historyArray.length; i++) {

    var labelItem = g_joey_historyArray[i];
    var menuElement=document.createElement("menuitem");
    menuElement.setAttribute("label",labelItem);
    document.getElementById("joeyHistoryMenuContainer").appendChild(menuElement);	

  }

}

function joey_clearMenuHistoryContainer() {

   var menuContainer=document.getElementById("joeyHistoryMenuContainer");
   while(menuContainer.firstChild) {
     menuContainer.removeChild(menuContainer.firstChild);
   }

}

function joey_launchCloudSite() {

	// marcio 1 
	g_joey_gBrowser.loadURI("https://joey.labs.mozilla.com/site/uploads");

}

function joey_selectedText() 
{    


    var focusedWindow = document.commandDispatcher.focusedWindow;
    var selection = focusedWindow.getSelection().toString();
    
    selection = replaceAll(selection, "\t", "\r\n");
    
    g_joey_name = "Untitled";
    g_joey_data = selection;
    g_joey_data_size = selection.length;
    g_joey_binary = false;
    g_joey_content_type = "text/plain";
    g_joey_title = focusedWindow.document.title;
    g_joey_url  = focusedWindow.location.href;
    g_joey_uuid = "";    

    uploadDataFromGlobals();
}


function joey_selected()
{
	if (gImageSource)
		return joey_selectedImage();

    var focusedWindow = document.commandDispatcher.focusedWindow;
    var selection = focusedWindow.getSelection().toString();
    
	if (selection != null || selection != "")
		return joey_selectedText();
		
	joey_selectedArea();
	
}

function joey_feed(feedLocation) {

    /* We can detect which type of feed and send additional information 
     * as well, such as the title. On the other hand, the feed title 
     * may change, so that could be something chechec ( and refreshed ) 
     * on the server 
     */
        
    g_joey_name = "Untitled";
    g_joey_data = feedLocation;
    g_joey_data_size = feedLocation.length;
    g_joey_binary = false;
    g_joey_content_type = "rss-source/text";
    g_joey_title = "Feed / We can put a title in it with one more client call. ";
    g_joey_url  = feedLocation;
    g_joey_uuid = "";    
    uploadDataFromGlobals();
}

// Check XUL statusbar item
function joey_launchPopup() {

  document.getElementById('joeyStatusPopup').showPopup(document.getElementById('joeyStatusButton'),-1,-1,'popup','topright', 'bottomright')

}

function getImageDataCallback(content_type, data, length)
{
	if (length>0)
    { 
        g_joey_data = data;
        g_joey_data_size = length;
        g_joey_content_type = content_type;
        uploadDataFromGlobals();
        return;
	}
    else
        alert("Problem uploading image to joey!\n");
}


function JoeyStatusUpdateClass() {
}

/* 
 * Probably this shoul work as a stack
 * Because we may have multiple joey action events 
 * going on at the same time. We may end up with a stack head counter here.  
 */
JoeyStatusUpdateClass.prototype = {

	/* 
 	 * We have to separate the login information from the 
       * loading status processes 
       */
	
	busyCounter:0,

      inventoryCounter:0, // this is very simple for now. IN the future maybe more complex. It represents the local history list.

	busyMore: function () {

		this.busyCounter++;
		this.busyRefresh();
	},
	busyLess: function () {

		this.busyCounter--;
		this.busyRefresh();
 	}, 
      inventoryMore: function () {
         this.inventoryCounter++;
         document.getElementById("joeyInventoryButton").label=this.inventoryCounter;
	},
	busyRefresh: function () {
		if(this.busyCounter>0) {
			document.getElementById("joeyWorkingButton").setAttribute("collapsed","false");
		} else {
			document.getElementById("joeyWorkingButton").setAttribute("collapsed","true");

		}
	},
	loginStatus: function (aMode) {
		if(aMode == "logout") {
			// logout mode.
			document.getElementById("joeyStatusButton").className="";
 			
		} else { 
			// login mode.
			document.getElementById("joeyStatusButton").className="login";
			
		}
	}
}


function JoeyImageStreamListener(aCallbackFunc,aStatusUpdate) {
  this.mCallbackFunc = aCallbackFunc;
  this.mStatusUpdate = aStatusUpdate;
}

JoeyImageStreamListener.prototype = {
  mBytes: [],
  mStream: null,
  mCount: 0,
  mChannel : null,
  mContentType : null,
  
  // nsIStreamListener
  onStartRequest: function (aRequest, aContext) {
    this.mStream = Components.classes['@mozilla.org/binaryinputstream;1'].createInstance(Components.interfaces.nsIBinaryInputStream);
    this.mChannel = aRequest.QueryInterface(Components.interfaces.nsIChannel);
    try
    {
		var http = aRequest.QueryInterface(Components.interfaces.nsIHttpChannel);
		this.mContentType = http.contentType;

		this.mStatusUpdate.busyMore();		


	} catch (ex) { alert(ex); }	
  },

  onDataAvailable: function (aRequest, aContext, aStream, aSourceOffset, aLength) {
  
	this.mStream.setInputStream(aStream);
	
	var chunk = this.mStream.readByteArray(aLength);
	this.mBytes = this.mBytes.concat(chunk);
	this.mCount += aLength;
  },

  onStopRequest: function (aRequest, aContext, aStatus) {
  
	if (Components.isSuccessCode(aStatus))
	{	
		this.mCallbackFunc(this.mContentType, this.mBytes, this.mCount);
		this.mStatusUpdate.busyLess();

	} 
	else {
		// request failed
		this.mCallbackFunc(null, null, 0);


	}
    
    this.mChannel = null;
  },

  // nsIChannelEventSink
  onChannelRedirect: function (aOldChannel, aNewChannel, aFlags) {
  	this.mChannel = aNewChannel;
  },

  // nsIInterfaceRequestor
  getInterface: function (aIID) {
    try {
      return this.QueryInterface(aIID);
    } catch (e) {
      throw Components.results.NS_NOINTERFACE;
    }
  },

  // nsIProgressEventSink (not implementing will cause annoying exceptions)
  onProgress : function (aRequest, aContext, aProgress, aProgressMax) { },
  onStatus : function (aRequest, aContext, aStatus, aStatusArg) { },

  // nsIHttpEventSink (not implementing will cause annoying exceptions)
  onRedirect : function (aOldChannel, aNewChannel) { },

  // we are faking an XPCOM interface, so we need to implement QI
  QueryInterface : function(aIID) {
    if (aIID.equals(Components.interfaces.nsISupports) ||
        aIID.equals(Components.interfaces.nsIInterfaceRequestor) ||
        aIID.equals(Components.interfaces.nsIChannelEventSink) || 
        aIID.equals(Components.interfaces.nsIProgressEventSink) ||
        aIID.equals(Components.interfaces.nsIHttpEventSink) ||
        aIID.equals(Components.interfaces.nsIStreamListener))
      return this;

    throw Components.results.NS_NOINTERFACE;
  }
};


function joey_selectedImage()
{
    var focusedWindow = document.commandDispatcher.focusedWindow;
    
    g_joey_name = "Untitled-image";
    g_joey_title = focusedWindow.document.title;
    g_joey_url = focusedWindow.location.href;
    g_joey_uuid = "";    
    g_joey_binary = true;
    
    // g_joey_data, g_joey_data_size, g_joey_content_type
    // will be filled in when we have the image data.
    
    
      // the IO service
	var ioService = Components.classes["@mozilla.org/network/io-service;1"]
                              .getService(Components.interfaces.nsIIOService);

      // create an nsIURI
      var uri = ioService.newURI(gImageSource, null, null);
	
	// get an listener
	var listener = new JoeyImageStreamListener(getImageDataCallback, g_joey_statusUpdateObject)

	// get a channel for that nsIURI
	var channel = ioService.newChannelFromURI(uri);
	channel.asyncOpen(listener, null);
	
}

function joey_selectedArea()
{
	if(g_joey_areaWindow==null || g_joey_areaWindow.closed) 
	{
       xpathTarget = gContextMenu.target
       g_joey_areaWindow = window.open("chrome://joey/content/joeyArea.xul",
                                "xpathchecker", 
                                 "chrome,resizable=yes");
    }
    else 
    {
        g_joey_areaWindow.loadXPathForNode(gContextMenu.target)
    }
}


function loot_setttings()
{
    var joey = Components.classes["@mozilla.com/joey;1"]
                            .getService(Components.interfaces.mocoJoey);

    joey.setLoginInfo();
}


function grabAll(elem)
{
    if (elem instanceof Components.interfaces.nsIDOMHTMLEmbedElement)
    {

        var base = Components.classes["@mozilla.org/network/standard-url;1"]
                             .createInstance(Components.interfaces.nsIURI);

        base.spec = g_joey_gBrowser.contentDocument.location.href;
        var url = base.resolve(elem.src)

        alert(url);
    }

    return NodeFilter.FILTER_ACCEPT;
}

function doGrab(iterator)
{
    for (var i = 0; i < 50; ++i)
        if (!iterator.nextNode())
            return;
    
    setTimeout(doGrab, 16, iterator);
}

function joeyCheckForMedia()
{
    var doc = g_joey_gBrowser.contentDocument;

    var iterator = doc.createTreeWalker(doc, NodeFilter.SHOW_ELEMENT, grabAll, true);
    setTimeout(doGrab, 16, iterator);
}

/* 
 * This refreshes feed information in the menu. 
 * The currentBrowser.feeds array is populated from the 
 * joeyLinkAddedHandler. 
 */

function joeySetCurrentFeed()
{


	try {

		var currentFeedURI = g_joey_gBrowser.mCurrentBrowser.feeds;

		if(currentFeedURI ||currentFeedURI.length>0) {
	
			document.getElementById("joey_activeRSSLink").setAttribute("hidden","false");
	
			document.getElementById("joey_activeRSSLink").setAttribute("oncommand","joey_feed('"+currentFeedURI[0].href+"')");
		
	
		} 
	
	} catch(i) {

		document.getElementById("joey_activeRSSLink").setAttribute("oncommand","");
		document.getElementById("joey_activeRSSLink").setAttribute("hidden","true");
	} 
}



function joeyStartup()
{

    var wm = Components.classes["@mozilla.org/appshell/window-mediator;1"]
                     .getService(Components.interfaces.nsIWindowMediator);

    gWin = wm.getMostRecentWindow("navigator:browser");

    g_joey_gBrowser = gWin.gBrowser;

    g_joey_gBrowser.addEventListener("DOMLinkAdded", joeyLinkAddedHandler, false);

    g_joey_browserStatusHandler = new joeyBrowserStatusHandler();

    g_joey_gBrowser.addProgressListener( g_joey_browserStatusHandler , Components.interfaces.nsIWebProgress.NOTIFY_ALL);

    g_joey_statusUpdateObject = new JoeyStatusUpdateClass();

}

function joeyLinkAddedHandler(event)
{

 /* 
   * Taken from browser.js - yes this should be in tabbrowser
   */
  
  var erel = event.target.rel;
  var etype = event.target.type;
  var etitle = event.target.title;
  var ehref = event.target.href;
  
  const alternateRelRegex = /(^|\s)alternate($|\s)/i;
  const rssTitleRegex = /(^|\s)rss($|\s)/i;
  
  if (!alternateRelRegex.test(erel) || !etype) return;
  
  etype = etype.replace(/^\s+/, "");
  etype = etype.replace(/\s+$/, "");
  etype = etype.replace(/\s*;.*/, "");
  etype = etype.toLowerCase();
  
  if (etype == "application/rss+xml" || etype == "application/atom+xml" || (etype == "text/xml" || etype == "application/xml" || etype == "application/rdf+xml") && rssTitleRegex.test(etitle))
  {
    const targetDoc = event.target.ownerDocument;
    
    var browsers = g_joey_gBrowser.browsers;
    var shellInfo = null;
    
    for (var i = 0; i < browsers.length; i++) {

      var shell = findChildShell(targetDoc, browsers[i].docShell, null);
      if (shell) shellInfo = { shell: shell, browser: browsers[i] };
    }
    
    //var shellInfo = this._getContentShell(targetDoc);
    
    var browserForLink = shellInfo.browser;
    
    if(!browserForLink) return;

    if(browserForLink.feeds) {
       joeySetCurrentFeed();


	  // We do this now because we dont want to messe up with Existing feeds info in Firefox. 

	  return; 
    }     

    // Do we really nees this? MArcio, remove the following or review it. 

    var feeds = [];
    if (browserForLink.feeds != null) feeds = browserForLink.feeds;
    var wrapper = event.target;
    feeds.push({ href: wrapper.href, type: etype, title: wrapper.title});

   // marcio 3 
   // We dont want to add more feed information on it. 
   // browserForLink.feeds = feeds;
    
    if (browserForLink == g_joey_gBrowser || browserForLink == g_joey_gBrowser.mCurrentBrowser) {
       joeySetCurrentFeed();
    } 
  }
}

/* 
 * We should check this again. Maybe we can reuse this function from the main browser 
 * ( browser.js ) 
 */

function findChildShell(aDocument, aDocShell, aSoughtURI) {

const nsIWebNavigation         = Components.interfaces.nsIWebNavigation;
const nsIInterfaceRequestor    = Components.interfaces.nsIInterfaceRequestor;
const nsIDOMDocument           = Components.interfaces.nsIDOMDocument;
const nsIDocShellTreeNode      = Components.interfaces.nsIDocShellTreeNode;

  aDocShell.QueryInterface(nsIWebNavigation);
  aDocShell.QueryInterface(nsIInterfaceRequestor);
  var doc = aDocShell.getInterface(nsIDOMDocument);
  if ((aDocument && doc == aDocument) || 
      (aSoughtURI && aSoughtURI.spec == aDocShell.currentURI.spec))
    return aDocShell;
  
  var node = aDocShell.QueryInterface(nsIDocShellTreeNode);
  for (var i = 0; i < node.childCount; ++i) {
    var docShell = node.getChildAt(i);
    docShell = findChildShell(aDocument, docShell, aSoughtURI);
    if (docShell) return docShell;
  }
  return null;
}

/* 
 *
 */

function joeyBrowserStatusHandler() {}

joeyBrowserStatusHandler.prototype = 
{

 QueryInterface : function(aIID)
  {
    if (aIID.equals(Components.interfaces.nsIWebProgressListener) ||
        aIID.equals(Components.interfaces.nsIXULBrowserWindow) ||
        aIID.equals(Components.interfaces.nsISupportsWeakReference) ||
        aIID.equals(Components.interfaces.nsISupports))
    {
      return this;
    }
    throw Components.results.NS_NOINTERFACE;
  },
  
  init : function()
  {

  },
  
  destroy : function()
  {
  },
  
  onStateChange : function(aWebProgress, aRequest, aStateFlags, aStatus)
  {
     
  },
  onProgressChange : function(aWebProgress, aRequest, aCurSelfProgress, aMaxSelfProgress, aCurTotalProgress, aMaxTotalProgress)
  {

  },
  onLocationChange : function(aWebProgress, aRequest, aLocation)
  {
      setTimeout(joeyCheckForMedia, 600); // this needs to be called after the page loads! dougt

      domWindow = aWebProgress.DOMWindow;

      if (domWindow == domWindow.top) {
        //this.urlBar.value = aLocation.spec;

		joeySetCurrentFeed();        
      }    
  },
  
  onStatusChange : function(aWebProgress, aRequest, aStatus, aMessage)
  {
  },
  startDocumentLoad : function(aRequest)
  {
  },
  endDocumentLoad : function(aRequest, aStatus)
  {
  },
  onSecurityChange : function(aWebProgress, aRequest, aState)
  {
  },
  setJSStatus : function(status)
  {
  },
  setJSDefaultStatus : function(status)
  {
  },
  setDefaultStatus : function(status)
  {
  },
  setOverLink : function(link, b)
  {
  }

}
