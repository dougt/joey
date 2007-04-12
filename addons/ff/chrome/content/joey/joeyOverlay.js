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
 * Marcio Galli 
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

var g_joey_data;
var g_joey_content_type;
var g_joey_title;
var g_joey_url;
var g_joey_isfile;
var g_joey_media_url  = null;
var g_joey_areaWindow = null;

var g_joey_consoleService    = null;
var g_joey_console = joeyDumpToNull; // default is a function that does nothing.

var g_joey_serverURL = "https://joey.labs.mozilla.com"; 

var g_joey_gBrowser = null;                // presents the main browser, used by the joey_feed code.
var g_joey_browserStatusHandler = null;    // to track onloction changes in the above browser ( tab browser ) element.
var g_joey_statusUpdateObject = null;      // the proxy object to deal with UI 
var g_joey_historyArray = [];              // This is a very simple version of some sort of history array. So far we put Dougt's alert info in here. 
/* 
 * Event listeners associated to the joeyOverlay app 
 */

window.addEventListener("load", joeyStartup, false);

var gImageSource;

function joey_listener() {}

joey_listener.prototype =
{
    onProgressChange: function (current, total)
    {
        g_joey_statusUpdateObject.tellStatus("upload", current, total);
    },

    onStatusChange: function (action, status)
    {
        if (action == "login")
        {
            if (status == 0)
            {
                g_joey_statusUpdateObject.loginStatus("login","completed");
            }
            else if (status == -1 )
            {
                g_joey_statusUpdateObject.loginStatus("login","failed");
            }

            return;
        }

        if (action == "upload")
        {
            if (status == 1) {
                g_joey_statusUpdateObject.tellStatus("upload",null,null,"completed");
            } 
            else {            
                g_joey_statusUpdateObject.tellStatus("upload",null,null,"failed"); 
            }
            return;
        }
    },

    QueryInterface: function (iid)
    {
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
                         .createInstance(Components.interfaces.mocoJoey);
    
    joey.setListener(new joey_listener());
    
	if (g_joey_isfile)
	{
		joey.uploadFile(g_joey_title,
                        g_joey_url,
                        g_joey_file,
                        g_joey_content_type);
	}
	else
	{
	    joey.uploadData(g_joey_title,
                        g_joey_url,
                        g_joey_data,
                        g_joey_content_type);
	}
}


function joeyOnMouseDown(e)
{
	if (e.which == 3) 
	{

		var target = e.target;
		var classname = e.target.toString();

		if (classname.match(/ImageElement/))
        {
			// Simpler, but probably less efficient syntax: target.src;
			var hie = target.QueryInterface(Components.interfaces.nsIDOMHTMLImageElement);
			if (hie != null)
				// show menu item:
				setImageSource(hie);
			else
				setImageSource(null);
		} 
        else
        {
            setImageSource(null);
        }
        
        var selectedRange=g_joey_gBrowser.selectedBrowser.contentDocument.getSelection();
        if( selectedRange && selectedRange.toString() ) {
           document.getElementById("joey_selectedText").hidden=false;
        } else {
          document.getElementById("joey_selectedText").hidden=true;
        }

    }
}

function setImageSource(imageElement)
{
	if (imageElement != null)
		gImageSource = imageElement.src;
	else
		gImageSource = null;
    
    try 
    {    
    	var menuItem = document.getElementById('joey_selectedImage');
	    menuItem.setAttribute("hidden", gImageSource == null ? "true" : "false");
    } 
    catch (e) {}
}

function replaceAll( str, from, to )
{
    // regular expression faster?
    
    var idx = str.indexOf( from );
    
    while ( idx > -1 ) {
        str = str.replace( from, to );
        idx = str.indexOf( from );
    }
    
    return str;
}

function joey_buildMenuHistoryContainer() 
{
    for (var i = 0; i < g_joey_historyArray.length; i++)
    {
        var labelItem = g_joey_historyArray[i];
        var menuElement=document.createElement("menuitem");
        menuElement.setAttribute("label",labelItem);
        document.getElementById("joeyHistoryMenuContainer").appendChild(menuElement);	
    }
}

function joey_clearMenuHistoryContainer()
{
    var menuContainer=document.getElementById("joeyHistoryMenuContainer");
    while(menuContainer.firstChild) 
    {
        menuContainer.removeChild(menuContainer.firstChild);
    }
}

function joey_launchCloudSite() 
{
	g_joey_gBrowser.loadURI(g_joey_serverURL+"/uploads");
}

function joey_selectedText() 
{
    var focusedWindow = document.commandDispatcher.focusedWindow;
    var selection = focusedWindow.getSelection().toString();
    
    selection = replaceAll(selection, "\t", "\r\n");
    
    var file = Components.classes["@mozilla.org/file/directory_service;1"]
                         .getService(Components.interfaces.nsIProperties)
                         .get("TmpD", Components.interfaces.nsIFile);
    file.append("joey-selected-text.tmp");
    file.createUnique(Components.interfaces.nsIFile.NORMAL_FILE_TYPE, 0664);

    // file is nsIFile, data is a string
    var foStream = Components.classes["@mozilla.org/network/file-output-stream;1"]
                             .createInstance(Components.interfaces.nsIFileOutputStream);

    // use 0x02 | 0x10 to open file for appending.
    foStream.init(file, 0x02 | 0x08 | 0x20, 0664, 0); // write, create, truncate
    foStream.write(selection, selection.length);
    foStream.close();

    g_joey_file = file;
    g_joey_isfile = true;
    g_joey_content_type = "text/plain";
    g_joey_title = focusedWindow.document.title;
    g_joey_url  = focusedWindow.location.href;

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

function joey_feed(feedLocation)
{
    /* We can detect which type of feed and send additional information 
     * as well, such as the title. On the other hand, the feed title 
     * may change, so that could be something chechec ( and refreshed ) 
     * on the server 
     */
    
    g_joey_data = feedLocation;
    g_joey_isfile = false;
    g_joey_content_type = "rss-source/text";
    g_joey_title = "Feed / We can put a title in it with one more client call. ";
    g_joey_url  = feedLocation;
    uploadDataFromGlobals();
}

// Check XUL statusbar item
function joey_launchPopup() 
{
  document.getElementById('joeyStatusPopup').showPopup(document.getElementById('joeyStatusButton'),-1,-1,'popup','topright', 'bottomright')
}

/* FIXME to be as an instance */

function getMediaCallback(content_type, file)
{
	if (length>0)
    { 
        g_joey_console("Download successful...");
        
        g_joey_statusUpdateObject.tellStatus("download",null,null,"completed");
        
        g_joey_file = file;
        g_joey_content_type = content_type;
        uploadDataFromGlobals();
        return;
	}
    else {
    
        /* This should become failed? */
        
        g_joey_statusUpdateObject.tellStatus("download",null,null,"failed");
        g_joey_console("Problem downloading media to joey!\n");
    }
}


function JoeyStatusUpdateClass() {

  /* We have now the XUL stack with elements in it. 
   * A background Layer and the top layer for 
   * the label. 
   */
 
  this.progressElement   = document.getElementById("joeyProgressLayer");
  this.progressBoxObject = document.getBoxObjectFor(document.getElementById("joeyStatusTeller"));  

}

/* 
 * UI Wrapper / Deals with the UI 
 * -------
 * TODO: Need to be smart / keep history 
 * TODO: Need to work with multiple instances
 */
JoeyStatusUpdateClass.prototype = 
{
	/* 
 	 * We have to separate the login information from the 
     * loading status processes 
     */
	
    loginStatus: function (aMode,aAdVerb)
    {
		if(aMode == "logout")
        {
			// logout mode.
			document.getElementById("joeyStatusButton").className="";
		}
        else
        { 
			// login mode.
			if(aAdVerb == "completed")  {
    			document.getElementById("joeyStatusButton").className="login";
            } else {
    			document.getElementById("joeyStatusButton").className="login_failed";            
            } 
		}
	},
    tellMode : function (aMode) {
       
       if(aMode.indexOf("capture.add") > -1 ) {
       
        var previousClassName = document.getElementById("joeyStatusButton").className;
        
        document.getElementById("joey_selectContextual").disabled=true;
        
        document.getElementById("joeyStatusButton").className=previousClassName + " capture"; 
    
       } 
              
       if(aMode.indexOf("capture.remove") > -1 ) {
       
        var previousClassName = document.getElementById("joeyStatusButton").className;

        document.getElementById("joey_selectContextual").disabled=false;
        
        document.getElementById("joeyStatusButton").className=previousClassName.split("capture")[0]; 
    
       } 
       
    },
    tellStatus:function(verb,from,to,adverb) 
    {
        var value; 
        var percentage = parseInt((from/to)*parseInt(this.progressBoxObject.width));

        if (verb == "upload") {

            // value = "Uploading... ("+from+"/"+to+")";
            value = "Uploading... ("+percentage+"%)";
	  }
        else
        {
            if (from==to)

                // this might not be entirely true... basically, at ths point we are waiting to upload...
                value = "Logging in..."; 

            else {

                // value = "Downloading... ("+from+"/"+to+")";
                value = "Downloading... ("+percentage+"%)";

		} 

        }   

        if(verb =="upload") {
              this.progressElement.width=percentage;
        } else {
              if(verb =="download") {
                percentage = this.progressBoxObject.width - percentage;
                this.progressElement.width=percentage;
              } 
        } 
        document.getElementById("joeyStatusTeller").value=value;
        
        /* adverb */
       
        if(adverb=="completed") {
            this.progressElement.width=0;
            if(verb=="download") {
                 document.getElementById("joeyStatusTeller").value="Download completed";
            } 
            if(verb=="upload") {
                 document.getElementById("joeyStatusTeller").value="Upload completed";
            } 
            /* Dougt timer status cleanup */
            setTimeout("document.getElementById('joeyStatusTeller').value=''", 600);
        }

        if(adverb == "failed") {
            if(verb=="download") {
                 document.getElementById("joeyStatusTeller").value="Download failed";
            } 
            if(verb=="upload") {
                 document.getElementById("joeyStatusTeller").value="Upload failed";
            } 
            /* Dougt timer status cleanup */
            setTimeout("document.getElementById('joeyStatusTeller').value=''", 600);
        }
                  
    }
    
}

/* 
 * This is nice for the Download + Progress functional
 */
 
function JoeyMediaFetcherStreamListener(aCallbackFunc)
{
  this.mCallbackFunc = aCallbackFunc;
}

JoeyMediaFetcherStreamListener.prototype = 
{
  mStream: null,
  mContentType : null,
  
  // nsIStreamListener
  onStartRequest: function (aRequest, aContext) 
  {
      this.file = Components.classes["@mozilla.org/file/directory_service;1"]
                            .getService(Components.interfaces.nsIProperties)
                            .get("TmpD", Components.interfaces.nsIFile);

      this.file.append("joeymedia.tmp");

      this.file.createUnique(Components.interfaces.nsIFile.NORMAL_FILE_TYPE, 0664);

      var fos = Components.classes["@mozilla.org/network/file-output-stream;1"]
                          .createInstance(Components.interfaces.nsIFileOutputStream);

      fos.init(this.file, 0x02 | 0x08 | 0x20, 0644, 0);
      
      this.mstream = Components.classes["@mozilla.org/binaryoutputstream;1"].createInstance(Components.interfaces.nsIBinaryOutputStream);
      this.mstream.setOutputStream(fos);

      try
      {
          var http = aRequest.QueryInterface(Components.interfaces.nsIHttpChannel);
          this.mContentType = http.contentType;
      } 
      catch (ex) { g_joey_console(ex); }	
  },

  onDataAvailable: function (aRequest, aContext, aStream, aSourceOffset, aLength)
  {
      var bis = Components.classes["@mozilla.org/binaryinputstream;1"]
                          .createInstance(Components.interfaces.nsIBinaryInputStream);
      bis.setInputStream(aStream);
      
      var n=0;
      while(n<aLength) {
          var data = bis.readByteArray( bis.available() );
          this.mstream.writeByteArray( data, data.length );
          n += data.length;
      }
  },

  onStopRequest: function (aRequest, aContext, aStatus)
  {
      if (Components.isSuccessCode(aStatus))
      {	
          this.mstream.close(); 
          this.mCallbackFunc(this.mContentType, this.file);
          
      } 
      else
      {
          // request failed
          this.mCallbackFunc(null, null, 0);
      }
  },

  // nsIChannelEventSink
  onChannelRedirect: function (aOldChannel, aNewChannel, aFlags) 
  {
  },
  
  // nsIInterfaceRequestor
  getInterface: function (aIID)
  {
      try 
      {
          return this.QueryInterface(aIID);
      } 
      catch (e) {}
  },

  // nsIProgressEventSink (not implementing will cause annoying exceptions)
  onProgress : function (aRequest, aContext, aProgress, aProgressMax) 
  { 
      g_joey_statusUpdateObject.tellStatus("download", aProgress, aProgressMax);
  },

  onStatus : function (aRequest, aContext, aStatus, aStatusArg) { },
  
  // nsIHttpEventSink (not implementing will cause annoying exceptions)
  onRedirect : function (aOldChannel, aNewChannel) { },
  
  // we are faking an XPCOM interface, so we need to implement QI
  QueryInterface : function(aIID) 
  {
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
    
    g_joey_title = focusedWindow.document.title;
    g_joey_url = focusedWindow.location.href;
    g_joey_isfile = true;
    
    // g_joey_data, g_joey_content_type
    // will be filled in when we have the image data.
    
    // the IO service
	var ioService = Components.classes["@mozilla.org/network/io-service;1"]
                              .getService(Components.interfaces.nsIIOService);

    // create an nsIURI
    var uri = ioService.newURI(gImageSource, null, null);
	
	// get an listener
	var listener = new JoeyMediaFetcherStreamListener(getMediaCallback);
    
    // get a channel for that nsIURI
    var channel = ioService.newChannelFromURI(uri);
    channel.notificationCallbacks = listener;
    channel.asyncOpen(listener, null);
}

function loot_setttings()
{
    var joey = Components.classes["@mozilla.com/joey;1"]
                         .createInstance(Components.interfaces.mocoJoey);
    joey.setLoginInfo();
}


function grabAll(elem)
{

    dump(elem);

    if (elem instanceof Components.interfaces.nsIDOMHTMLEmbedElement)
    {
        var base = Components.classes["@mozilla.org/network/standard-url;1"]
                             .createInstance(Components.interfaces.nsIURI);

        base.spec = g_joey_gBrowser.contentDocument.location.href;

        // youtube specific.  humm.
        var url = base.prePath;
        url += "/get_video.php?";
        url += elem.src.substring(elem.src.indexOf('?')+1);

        // great found something -- what about multi embed tags? dougt
        
		document.getElementById("joeyMediaMenuItem").setAttribute("hidden","false");
        g_joey_media_url = url;

        //        alert(url);
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

    //    alert('in');

    // reset these on new page load.  should probably move this somewhere else.
    document.getElementById("joeyMediaMenuItem").setAttribute("hidden","true");
    g_joey_media_url = null;
    
    var doc = g_joey_gBrowser.contentDocument;
    
    var iterator = doc.createTreeWalker(doc, NodeFilter.SHOW_ELEMENT, grabAll, true);
    setTimeout(doGrab, 16, iterator);
}

function joey_uploadFoundMedia() // refactor with joey_selectedImage
{
    var focusedWindow = document.commandDispatcher.focusedWindow;
    
    g_joey_title = focusedWindow.document.title;
    g_joey_url = focusedWindow.location.href;
    g_joey_isfile = true;
    
    // g_joey_data, g_joey_content_type
    // will be filled in when we have the media data.
    
    // the IO service
	var ioService = Components.classes["@mozilla.org/network/io-service;1"]
                              .getService(Components.interfaces.nsIIOService);

    // create an nsIURI
    var uri = ioService.newURI(g_joey_media_url, null, null);
	
	// get an listener
	var listener = new JoeyMediaFetcherStreamListener(getMediaCallback);
    
    // get a channel for that nsIURI
    var channel = ioService.newChannelFromURI(uri);
    channel.notificationCallbacks = listener;
	channel.asyncOpen(listener, null);
}

/* 
 * This refreshes feed information in the menu. 
 * The currentBrowser.feeds array is populated from the 
 * joeyLinkAddedHandler. 
 */

function joeySetCurrentFeed()
{
	try
    {
		var currentFeedURI = g_joey_gBrowser.mCurrentBrowser.feeds;
        
		if(currentFeedURI ||currentFeedURI.length>0) 
        {
			document.getElementById("joey_activeRSSLink").setAttribute("hidden","false");
			document.getElementById("joey_activeRSSLink").setAttribute("oncommand","joey_feed('"+currentFeedURI[0].href+"')");
		} 
	} 
    catch(i)
    {
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

    g_joey_gBrowser.addProgressListener( g_joey_browserStatusHandler , Components.interfaces.nsIWebProgress.NOTIFY_STATE_DOCUMENT);


    var psvc = Components.classes["@mozilla.org/preferences-service;1"]
                         .getService(Components.interfaces.nsIPrefBranch);


    /* 
     * Console Service 
     */
     
    g_joey_consoleService = Components.classes["@mozilla.org/consoleservice;1"]
                               .getService(Components.interfaces.nsIConsoleService);

    
    try {   
            if(psvc.getBoolPref("joey.enable_logging")) {
                g_joey_console = joeyDumpToConsole;
                g_joey_console("Console Logging enabled!");
            }
    } catch (i) {
        alert(i);
    }

    try {   
        var url = g_joey_serverURL;
        
        if (psvc.prefHasUserValue("joey.service_url"))
            url = psvc.getCharPref("joey.service_url");
        
        g_joey_serverURL=url;
        
    } catch (i) { g_joey_console(i); } 




    g_joey_statusUpdateObject = new JoeyStatusUpdateClass();

    /* 
     * Joey Event Listeners 
     */

    window.addEventListener("mousedown",joeyOnMouseDown,false); 

    /* 
     * First Run function..
     */ 
    try {
       if(psvc.getBoolPref("joey.firstRun")) {
            psvc.setBoolPref("joey.firstRun",false);     
            joeyLaunchPreferences();        
       }
    } catch(i) { g_joey_console(i) } 

}

/* 
 * Console Logging Service / See joeyStartup code... 
 * The global function name is g_joey_console = joeyDumpToNull. 
 * If the Pref joey.enable_logging = true, then g_joey_console = joeyDumpToConsole 
 * 
 */

function joeyDumpToNull(aMessage) {
    // Does nothing.
}
function joeyDumpToConsole(aMessage) {
      g_joey_consoleService.logStringMessage("joey: " + aMessage);
}

/* 
 * Prefs launcher 
 */
function joeyLaunchPreferences() {
        window.open("chrome://joey/content/joeyOptions.xul",
                    "welcome", 
                    "chrome,resizable=yes");
}
                                            
/* 
 * From FF. We may be able to use a detection service instead this copied code. 
 */ 
 
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
    
    if (etype == "application/rss+xml" || etype == "application/atom+xml" || 
        (etype == "text/xml" || etype == "application/xml" || etype == "application/rdf+xml") && 
        rssTitleRegex.test(etitle))
    {
        const targetDoc = event.target.ownerDocument;
        
        var browsers = g_joey_gBrowser.browsers;
        var shellInfo = null;
        
        for (var i = 0; i < browsers.length; i++)
        {
            
            var shell = findChildShell(targetDoc, browsers[i].docShell, null);
            if (shell) shellInfo = { shell: shell, browser: browsers[i] };
        }
    
        //var shellInfo = this._getContentShell(targetDoc);
        
        var browserForLink = shellInfo.browser;
        
        if(!browserForLink) return;
        
        if(browserForLink.feeds) 
        {
            joeySetCurrentFeed();
            // We do this now because we dont want to messe up with Existing feeds info in Firefox. 
            return; 
        }     

        // Do we really nees this? MArcio, remove the following or review it. 
        
        var feeds = [];
        if (browserForLink.feeds != null) feeds = browserForLink.feeds;
        var wrapper = event.target;
        feeds.push({ href: wrapper.href, type: etype, title: wrapper.title});

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

function findChildShell(aDocument, aDocShell, aSoughtURI) 
{

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
    for (var i = 0; i < node.childCount; ++i)
    {
        var docShell = node.getChildAt(i);
        docShell = findChildShell(aDocument, docShell, aSoughtURI);
        if (docShell) return docShell;
    }
    return null;
}

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
        setTimeout(joeySetCurrentFeed, 600);        
        setTimeout(joeyCheckForMedia, 600); // this needs to be called after the page loads! dougt

        setTimeout(g_joeySelectorService.disable, 0);
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

/* 
 * Joey Element Selection Service Singleton
 * ----------------------------------------
 * This implementation is to be moved outside
 * this JS file. This uses the /contrib/uSummaryGenerator.js
 * This is the UI contextual function that allows and end-user 
 * to pick an area and then the uSummaryGenerator service is 
 * called so that we can wrap the generator XML info and  
 * upload it to Joey!. 
 */

var g_joeySelectorService = {

    currentEvent         :null,
    previousTargetElement:null,
    associatedDocument   :null,
    enabled              :false,
    currentElementLeft   :null,
    currentElementTop    :null,
    currentElementRight  :null,
    currentElementBottom :null,
    timer                :null,
    
    enable: function () {

        g_joey_console("g_joeySelectorService enable");

        if (this.enabled == true)
            this.disable();

        this.enabled = true;

	    g_joey_statusUpdateObject.tellMode("capture.add"); // we will need also to lock the menu...
	    
        this.associatedDocument = g_joey_gBrowser.selectedBrowser.contentDocument;

        this.createBox();
        
        var thisInstance = this;
        
        sigmaCall = function(e) { thisInstance.mouseMoveListener(e) };
        
    	g_joey_gBrowser.selectedBrowser
    	               .contentDocument
    	               .addEventListener("mousemove"
    	                                 ,sigmaCall
    	                                 ,false);

        omegaCall = function(e) { thisInstance.mouseClickListener(e) };
        
    	g_joey_gBrowser.selectedBrowser
    	               .contentDocument
    	               .addEventListener("mousedown"
    	                                 ,omegaCall 
    	                                 ,false);
    

        deltaCall = function(e) { thisInstance.keyDownListener(e) };
        
    	g_joey_gBrowser.selectedBrowser
    	               .contentDocument
    	               .addEventListener("keydown"
    	                                 ,deltaCall 
    	                                 ,false);

	    this.runtimer();    // timer-based refresh function..

	    
    },
    
    disable: function () {

        if (this.enabled == false)
            return;

        g_joey_console("g_joeySelectorService disable");
    
        clearTimeout(this.timer);
        this.timer = null;

    	g_joey_gBrowser.selectedBrowser
    	               .contentDocument
    	               .removeEventListener("mousemove"
    	                                 ,sigmaCall
    	                                 ,false);        
    	g_joey_gBrowser.selectedBrowser
    	               .contentDocument
    	               .removeEventListener("mousedown"
    	                                 ,omegaCall 
    	                                 ,false);

    	g_joey_gBrowser.selectedBrowser
    	               .contentDocument
    	               .removeEventListener("keydown"
    	                                 ,deltaCall 
    	                                 ,false);

        this.removeBox();
        this.associatedDocument    = null;   
        this.currentEvent          = null;
        this.previousTargetElement = null;
           
        g_joey_statusUpdateObject.tellMode("capture.remove");

        this.enabled = false;

    },

    mouseMoveListener: function (e) {
		if (this.previousTargetElement != e.target) {
			this.currentEvent = e;
			this.previousTargetElement = e.target;
		}                                 
    },

    mouseClickListener: function (e) {
    
	    this.disable();

        if(e.button == 0) {
            /* 
             * We may revisit this to elect target elements 
             * if they make sense. For example I assume we dont want to elect 
             * the hole page. .. or not :) 
             */
             
	        joey_selectedTarget(this.currentEvent.target);
            e.preventDefault(); // eat the event
        }            
    },

    keyDownListener: function (e) {
        this.disable();
        e.preventDefault();
    },

    createBox: function () {

                var newDiv= this.associatedDocument.createElementNS("http://www.w3.org/1999/xhtml", "div");
                newDiv.style.position="absolute";
                newDiv.style.zIndex="1000";
                newDiv.style.background="url(chrome://joey/skin/selector-tile.png)";
                newDiv.style.border="0px";
                newDiv.style.height="4px";

                this.currentElementTop=newDiv;
                                   	
                var newDiv= this.associatedDocument.createElementNS("http://www.w3.org/1999/xhtml", "div");
                newDiv.style.position="absolute";
                newDiv.style.zIndex="1000";
                newDiv.style.background="url(chrome://joey/skin/selector-tile.png)";
                newDiv.style.left="0px";          	
                newDiv.style.border="0px";          	
                newDiv.style.height="4px";

                this.currentElementBottom=newDiv;
                this.currentElementTop.appendChild(this.currentElementBottom);
    	
                var newDiv= this.associatedDocument.createElementNS("http://www.w3.org/1999/xhtml", "div");
                newDiv.style.position="absolute";
                newDiv.style.zIndex="1000";
                newDiv.style.top="0px";
                newDiv.style.left="0px";
                newDiv.style.width="4px";
                newDiv.style.background="url(chrome://joey/skin/selector-tile.png)";
                newDiv.style.border="0px";
                
                this.currentElementLeft=newDiv;
                this.currentElementTop.appendChild(this.currentElementLeft);
    	
                var newDiv = this.associatedDocument.createElementNS("http://www.w3.org/1999/xhtml", "div");
                newDiv.style.position="absolute";
                newDiv.style.zIndex="1000";
                newDiv.style.top="0px";
                newDiv.style.width="4px";
                newDiv.style.background="url(chrome://joey/skin/selector-tile.png)";
                newDiv.style.border="0px";
                
                this.currentElementRight=newDiv;
                this.currentElementTop.appendChild(this.currentElementRight);
                
                try {
                    this.associatedDocument.body.appendChild(this.currentElementTop);
                } catch (ignore) {
                    g_joey_console(ignore);
                }
                
            
    }, 

    removeBox: function () {
    
        try {
        
            if(this.currentElementTop.parentNode) {
                this.currentElementTop.parentNode.removeChild(this.currentElementTop);
        	}        	 
        	
        } catch (i) { g_joey_console(i) } 
         	        	
    },

    runtimer: function() {

        try {

            /* 
             * We want UI to be smooth so we keep this at 150 miliseconds. 
             * Otherwise the Contextual Box moves too much in the screen for every little DOM node.
             *
             */ 
            if (this.currentEvent && this.associatedDocument) {
                
                var currentDocument = this.associatedDocument;
                
                var boxObject = currentDocument.getBoxObjectFor(this.currentEvent.target);
                
                const borderSize=4;
                
                var boxObjectX = boxObject.x - borderSize;
                var boxObjectY = boxObject.y - borderSize;
                var rawWidth = boxObject.width;
                var rawHeight = boxObject.height;
                
                var restWidth = rawWidth % 4;
                var restHeight = rawHeight % 4;
                
                var boxCounterWidth = (rawWidth - restWidth)/4 + 1; 
                var boxCounterHeight = (rawHeight- restHeight)/4 + 1; 
                
                var boxObjectWidth  = ( rawWidth - restWidth )  + ( borderSize * 2 );
                var boxObjectHeight = ( rawHeight - restHeight )  + ( borderSize * 2 ) ;
                
                var modOddWidth = boxCounterWidth % 2;
                var modOddHeight = boxCounterHeight % 2;            
            
                if( parseInt(modOddWidth) == 0) {
                    boxObjectWidth+=4;
                    boxObjectX-=4;
                }
            
                if( parseInt(modOddHeight) == 0) {
                    boxObjectHeight+=4;
                    boxObjectY-=4;
                }
                
                this.currentElementTop.style.top=boxObjectY+"px";
                this.currentElementTop.style.left=boxObjectX+"px";
                this.currentElementTop.style.width=boxObjectWidth+"px";
                this.currentElementBottom.style.top=boxObjectHeight+"px";
                this.currentElementBottom.style.width=boxObjectWidth+4+"px";
                this.currentElementLeft.style.height=boxObjectHeight+"px";
                this.currentElementRight.style.left=boxObjectWidth+"px";
                this.currentElementRight.style.height=boxObjectHeight+4+"px";
                
            } // end of current event...
            
            if (this.associatedDocument) {
                this.timer = setTimeout("g_joeySelectorService.runtimer()",122);
            }	
        }
        catch(e)
        {
            // if any of this fails, just kill it.
            setTimeout(g_joeySelectorService.disable, 0);
        }
    } // end of runtimer  
    
}

function joey_selectedTarget(targetElement)
{
    var obj = uSummaryGen_xPathInit(content.document,targetElement);
    g_joey_data = obj.generatorText;
    g_joey_isfile = false;

    var focusedWindow = document.commandDispatcher.focusedWindow;
    g_joey_url  = focusedWindow.location.href;
    g_joey_title = "Microsummary from : " + focusedWindow.location.href; 
    g_joey_content_type = "microsummary/xml";

    uploadDataFromGlobals();
	
}

/* 
 * 
 */
function joey_enableSelection() {

    g_joeySelectorService.enable();

}
