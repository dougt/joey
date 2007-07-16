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
var g_joey_media_type = null;
var g_joey_areaWindow = null;

var g_joey_gBrowser = null;                // presents the main browser, used by the joey_feed code.
var g_joey_browserStatusHandler = null;    // to track onloction changes in the above browser ( tab browser ) element.
var g_joey_statusUpdateObject = null;      // the proxy object to deal with UI 

var g_joey_bundleElement = null;

var g_joey_mediaContentTypes = ['flv','mov','wmv','avi','mpeg','mp3','wav']; 

var g_joey_tab_upload_timer = null;

function joeyString(ref) {

     return g_joey_bundleElement.getString(ref);

}

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


                var prompts = Components.classes["@mozilla.org/embedcomp/prompt-service;1"]
                                        .getService(Components.interfaces.nsIPromptService);

                var result = prompts.confirm(null, joeyString("loginFailedShort"), joeyString("loginFailedQuestion"));
                if (result == true)
                {
                    // Clear the username and password and try again.
                    clearLoginData();
                    setTimeout(uploadDataFromGlobals, 500); // give enough time for us to leave the busy check
                }
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

                var prompts = Components.classes["@mozilla.org/embedcomp/prompt-service;1"]
                                        .getService(Components.interfaces.nsIPromptService);
                prompts.alert(null, joeyString("uploadFailed"), joeyString("uploadFailed"));
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

    // marcio

    try {
                     
        var prompts = Components.classes["@mozilla.org/embedcomp/prompt-service;1"]
                                .getService(Components.interfaces.nsIPromptService);
        
        var titleObject = {value: g_joey_title}; // default the username to user


        var psvc = Components.classes["@mozilla.org/preferences-service;1"]
                         .getService(Components.interfaces.nsIPrefBranch);

        var askState = psvc.getBoolPref("joey.askForTitle");
     
        if(askState == true ) {
        
            var check = {value: askState};  // default the checkbox to true
            
            var result = prompts.prompt(null, joeyString("promptTitle.windowTitle"), 
                                                           joeyString("promptTitle.label"),
                                                           titleObject,
                                                           joeyString("promptTitle.prefQuestion"), 
                                                           check);
            
            if(result) {
                g_joey_title = titleObject.value;
            }
            
            
            psvc.setBoolPref("joey.askForTitle",check.value);       
            
            
        } 
                              
    } catch (i) { joeyDumpToConsole(i) }


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

function joey_launchCloudSite() 
{
	g_joey_gBrowser.loadURI(getJoeyServerURL()+"/uploads");
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

    uploadDataFromGlobals(false);
}

function joey_currentTabs()
{
    // Loop through all of the windows looking and tabs.

    var wm = Components.classes["@mozilla.org/appshell/window-mediator;1"]
                      .getService(Components.interfaces.nsIWindowMediator);

    var windows = wm.getEnumerator(null);

    var window_count = 1; // silly humans like 1 based counters.

    var upload_content = "<ul>";

    while (windows.hasMoreElements()) {

        upload_content += "<li><h2>Browser Window #" + window_count + "</h2></li><ul>";

        var tabs = windows.getNext().getBrowser().mTabs;

        for (var i=0; i<tabs.length; i++)
        {
            var title = tabs[i].linkedBrowser.contentTitle || tabs[i].linkedBrowser.currentURI.spec;

            upload_content += "<li><a href='" + tabs[i].linkedBrowser.currentURI.spec + "'>"+title+"</a></li>";
        }

        upload_content += "</ul>";
        window_count++;
    }

    upload_content += "</ul>";

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
    foStream.write(upload_content, upload_content.length);
    foStream.close();

    g_joey_file = file;
    g_joey_isfile = true;
    g_joey_content_type = "browser/stuff";
    g_joey_title = "Current Tabs";
    g_joey_url  = "about:CurrentTabs";

    uploadDataFromGlobals(false);
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

function joey_feed()
{
    /* We can detect which type of feed and send additional information 
     * as well, such as the title. On the other hand, the feed title 
     * may change, so that could be something chechec ( and refreshed ) 
     * on the server 
     */
    
    var feedLocation = g_joey_gBrowser.mCurrentBrowser.feeds[0].href;
    var baseTitle = g_joey_gBrowser.mCurrentBrowser.feeds[0].title || feedLocation;
    var icon = g_joey_gBrowser.mCurrentBrowser.mIconURL;

    g_joey_data = "rss=" + feedLocation + "\r\n";

    if (icon != null)
        g_joey_data = g_joey_data + "icon=" + icon + "\r\n";

    g_joey_isfile = false;
    g_joey_content_type = "rss-source/text";
    g_joey_title = baseTitle;
    g_joey_url  = feedLocation;
    uploadDataFromGlobals(false);
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
        joeyDumpToConsole("Download successful... (" + content_type + ")");
        
        g_joey_statusUpdateObject.tellStatus("download",null,null,"completed");
        
        g_joey_content_type = content_type;
        g_joey_file = file;
        uploadDataFromGlobals(false);
        return;
	}
    else {
    
        /* This should become failed? */
        
        g_joey_statusUpdateObject.tellStatus("download",null,null,"failed");
        joeyDumpToConsole("Problem downloading media to joey!\n");
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
	},

    tellStatus:function(verb,from,to,adverb) 
    {
        var value; 
        var percentage = parseInt((from/to)*parseInt(this.progressBoxObject.width));

        // account for roundoff error that we have been seeing.
        if (percentage > 100)
            percentage = 100;
        
        if (verb == "upload") 
        {
            // value = "Uploading... ("+from+"/"+to+")";
            value = joeyString("uploading") + "(" + percentage + "%)";
        }
        else
        {
            if (from==to)
            {
                // this might not be entirely true... basically, at ths point we are waiting to upload...
                value = joeyString("loggingin"); 
            }
            else 
            {
                // value = "Downloading... ("+from+"/"+to+")";
                value = joeyString("downloading") + "("+percentage+"%)";  
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
                 document.getElementById("joeyStatusTeller").value=joeyString("downloadCompleted");
            } 
            if(verb=="upload") {
                 document.getElementById("joeyStatusTeller").value=joeyString("uploadCompleted");
            } 
            /* Dougt timer status cleanup */
            setTimeout("document.getElementById('joeyStatusTeller').value=''", 600);
        }

        if(adverb == "failed") {
            if(verb=="download") {
                 document.getElementById("joeyStatusTeller").value=joeyString("downloadFailed");
            } 
            if(verb=="upload") {
                 document.getElementById("joeyStatusTeller").value=joeyString("uploadFailed");
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
      catch (ex) { joeyDumpToConsole(ex); }	
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

function rev(str)
 {
   if (!str) return '';
   var revstr='';
   for (i=str.length-1;i>=0;i--){revstr+=str.charAt(i)}
   return revstr;
 }


var httpscanner = {
  observe: function(subject,topic,data){
        var response=subject.QueryInterface(Components.interfaces.nsIHttpChannel);
	    var contentType=response.getResponseHeader('Content-Type');         

        function testContentType(types){
                var isMediaFile = false;
                for(var i=types.length;i>=0;i--){
					if(contentType.indexOf(types[i])>-1 || mediaLocation.indexOf('.'+types[i])>-1) isMediaFile = true;
                }
                return isMediaFile;
        }

        if(contentType.indexOf('video')>-1 || contentType.indexOf('audio')>-1 || contentType.indexOf('octet')>-1){
                var mediaLocation = subject.QueryInterface(Components.interfaces.nsIChannel).URI;
                        mediaLocation=mediaLocation.prePath+mediaLocation.path;

                if(testContentType(g_joey_mediaContentTypes)){
                        joeyDumpToConsole("media content found: "+ mediaLocation);
                        document.getElementById("joeyMediaMenuItem").setAttribute("hidden","false");
                        g_joey_media_type = contentType;
                        g_joey_media_url = mediaLocation;
                }
        }
	}
}
var observerService = Components.classes["@mozilla.org/observer-service;1"].getService(Components.interfaces.nsIObserverService);
	observerService.addObserver(httpscanner,"http-on-examine-response",false);

function joey_uploadFoundMedia() // refactor with joey_selectedImage
{
    var focusedWindow = document.commandDispatcher.focusedWindow;
    
    g_joey_title = focusedWindow.document.title;
    g_joey_url = focusedWindow.location.href;
    g_joey_isfile = true;
    g_joey_content_type = g_joey_media_type;

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

function joeyStartup()
{
    var wm = Components.classes["@mozilla.org/appshell/window-mediator;1"]
                      .getService(Components.interfaces.nsIWindowMediator);
    
    gWin = wm.getMostRecentWindow("navigator:browser");
    
    g_joey_gBrowser = gWin.gBrowser;
    
    g_joey_browserStatusHandler = new joeyBrowserStatusHandler();

    g_joey_gBrowser.addProgressListener( g_joey_browserStatusHandler , Components.interfaces.nsIWebProgress.NOTIFY_STATE_DOCUMENT);


    var psvc = Components.classes["@mozilla.org/preferences-service;1"]
                         .getService(Components.interfaces.nsIPrefBranch);

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
    } catch(i) { joeyDumpToConsole(i) } 

    /* 
     *  Bundle object for local string access
     */
    
    g_joey_bundleElement = document.getElementById("joey_properties");
    // We can use now joeyString to get strings...     

    
    // kick off the tab uploading thread:
    g_run_tab_upload();
}


function g_run_tab_upload()
{
    var enabled = false;
    var timeout = 300000; // 5min
    try {
        timeout = psvc.getIntPref("joey.tab.upload.timeout");
        enabled = psvc.getBoolPref("joey.tab.upload.enabled");
    } catch(i) { joeyDumpToConsole(i) } 


    // only run if the timer is active.  this allows us to
    // simple call this function to kick of the thread and
    // not have to do a upload right when we start.
    if (g_joey_tab_upload_timer != null && enabled == true)
        joey_currentTabs();

    g_joey_tab_upload_timer = setTimeout("g_run_tab_upload()", timeout);
}


function joeyDumpToConsole(aMessage) {
    try {   
        var psvc = Components.classes["@mozilla.org/preferences-service;1"]
                             .getService(Components.interfaces.nsIPrefBranch);

        if(psvc.getBoolPref("joey.enable_logging")) {

            var cs = Components.classes["@mozilla.org/consoleservice;1"]
                               .getService(Components.interfaces.nsIConsoleService);

            cs.logStringMessage("joey: " + aMessage);
        }
    } 
    catch (i) {}
}

/* 
 * Prefs launcher 
 */
function joeyLaunchPreferences() {
        window.open("chrome://joey/content/joeyOptions.xul",
                    "welcome", 
                    "chrome,resizable=yes");
}


function joeyRegisterFeedListener() {

    var psvc = Components.classes["@mozilla.org/preferences-service;1"]
                         .getService(Components.interfaces.nsIPrefBranch);

    var url = "https://joey.labs.mozilla.com";
    if (psvc.prefHasUserValue("joey.service_url"))
        url = psvc.getCharPref("joey.service_url");

    navigator.registerContentHandler('application/vnd.mozilla.maybe.feed',
                                     url + '/uploads/add/rss/?source=%s', 
                                     'Joey!');
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
         /* via state changes we notify our local startDocumentLoad and endDocumentLoad
         * Joey uses endDocumentLoad to do a little sniffing in the doc, check feeds, 
         * check media items, and possibly more..
         */
         
        const nsIWebProgressListener = Components.interfaces.nsIWebProgressListener;
        const nsIChannel = Components.interfaces.nsIChannel;
        if (aStateFlags & nsIWebProgressListener.STATE_START) {
            if (aStateFlags & nsIWebProgressListener.STATE_IS_NETWORK &&
                aRequest && aWebProgress.DOMWindow == content)
              this.startDocumentLoad(aRequest);
              
        }
        else if (aStateFlags & nsIWebProgressListener.STATE_STOP) {
          if (aStateFlags & nsIWebProgressListener.STATE_IS_NETWORK) {
            if (aWebProgress.DOMWindow == content) {
              if (aRequest)
                this.endDocumentLoad(aRequest, aStatus);
            }
          }
        }
    },

    onProgressChange : function(aWebProgress, aRequest, aCurSelfProgress, aMaxSelfProgress, aCurTotalProgress, aMaxTotalProgress)
    {
    },

    onLocationChange : function(aWebProgress, aRequest, aLocation)
    {

    },
    
    onStatusChange : function(aWebProgress, aRequest, aStatus, aMessage)
    {
    },
    
    startDocumentLoad : function(aRequest)
    {
    },
    
    endDocumentLoad : function(aRequest, aStatus)
    {
        setTimeout(g_joeySelectorService.disable, 0);
        setTimeout(g_joeyFeedwatcher, 0);
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


function g_joeyFeedwatcher()
{

    if (psvc.getBoolPref("joey.watchRSS") == false)
        return;

    // we need to tie into a way to purge via clear private data!

    var feedWatchLimit = 10;
    try {
        feedWatchLimit = psvc.getIntPref("joey.rss-watch-limit");
    } catch(a) {}


    try {
        
        // this will change with Places.

        var feedLocation = g_joey_gBrowser.mCurrentBrowser.feeds[0].href;
        
        // make into a pref name.  (slashes have to be removed)
        feedLocation = feedLocation.replace(/\//g, "s");
            
        var psvc = Components.classes["@mozilla.org/preferences-service;1"]
                             .getService(Components.interfaces.nsIPrefBranch);

        var seen = 0;
        try {
            seen = psvc.getIntPref("joey.rss." + feedLocation + ".seen");
        } catch(a) {}

        seen++;
        
        if (seen > feedWatchLimit)
        {
            joey_feed();
            seen = 0;
        }

        psvc.setIntPref("joey.rss." + feedLocation + ".seen", seen);

    }
    catch(ignore) {}
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

var sigmaCall = null;
var omegaCall = null;
var deltaCall = null;

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

        joeyDumpToConsole("g_joeySelectorService enable");

        if (this.enabled == true)
            this.disable();

        this.enabled = true;

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

        joeyDumpToConsole("g_joeySelectorService disable");
    
        clearTimeout(this.timer);
        this.timer = null;

        if (sigmaCall !=null)
            g_joey_gBrowser.selectedBrowser
         	               .contentDocument
    	                   .removeEventListener("mousemove"
                                                ,sigmaCall
                                                ,false);        
        if (omegaCall != null)
            g_joey_gBrowser.selectedBrowser
         	               .contentDocument
    	                   .removeEventListener("mousedown"
                                                ,omegaCall 
                                                ,false);

        if (deltaCall != null)
            g_joey_gBrowser.selectedBrowser
        	               .contentDocument
        	               .removeEventListener("keydown"
                                                ,deltaCall 
                                                ,false);

        if (this.removeBox)
            this.removeBox();

        this.associatedDocument    = null;   
        this.currentEvent          = null;
        this.previousTargetElement = null;
           
        this.enabled = false;

    },

    mouseMoveListener: function (e) {
		if (this.previousTargetElement != e.target) {
			this.currentEvent = e;
			this.previousTargetElement = e.target;
		}                                 
    },

    mouseClickListener: function (e) {
    
    try {

        if(e.button == 0) {
            /* 
             * We may revisit this to elect target elements 
             * if they make sense. For example I assume we dont want to elect 
             * the hole page. .. or not :) 
             */
             
	        joey_selectedTarget(this.currentEvent.target);
            e.preventDefault(); // eat the event
        }            

	} catch (e) {}

	    this.disable();
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
                    joeyDumpToConsole(ignore);
                }
                
            
    }, 

    removeBox: function () {
    
        try {
        
            if(this.currentElementTop.parentNode) {
                this.currentElementTop.parentNode.removeChild(this.currentElementTop);
        	}        	 
        	
        } catch (i) { joeyDumpToConsole(i) } 
         	        	
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


/**
 * Determine whether a node's text content is entirely whitespace.
 *
 * @param nod  A node implementing the |CharacterData| interface (i.e.,
 *             a |Text|, |Comment|, or |CDATASection| node
 * @return     True if all of the text content of |nod| is whitespace,
 *             otherwise false.
 */
function is_all_ws( nod )
{
  // Use ECMA-262 Edition 3 String and RegExp features
  return !(/[^\t\n\r ]/.test(nod.data));
}


/**
 * Determine if a node should be ignored by the iterator functions.
 *
 * @param nod  An object implementing the DOM1 |Node| interface.
 * @return     true if the node is:
 *                1) A |Text| node that is all whitespace
 *                2) A |Comment| node
 *             and otherwise false.
 */

function is_ignorable( nod )
{
  return ( nod.nodeType == 8) || // A comment node
         ( (nod.nodeType == 3) && is_all_ws(nod) ); // a text node, all ws
}

/**
 * Version of |previousSibling| that skips nodes that are entirely
 * whitespace or comments.  (Normally |previousSibling| is a property
 * of all DOM nodes that gives the sibling node, the node that is
 * a child of the same parent, that occurs immediately before the
 * reference node.)
 *
 * @param sib  The reference node.
 * @return     Either:
 *               1) The closest previous sibling to |sib| that is not
 *                  ignorable according to |is_ignorable|, or
 *               2) null if no such node exists.
 */
function node_before( sib )
{
  while ((sib = sib.previousSibling)) {
    if (!is_ignorable(sib)) return sib;
  }
  return null;
}

function joey_buildXPath(targetElement)
{
    if (targetElement == null)
        return null;

    var buffer = "";
    var cur = targetElement;

    do {

        var name = "";
        var sep = "/";
        var occur = 0;
        var ignore = false;
        var type = targetElement.nodeType; 

        //        alert(buffer);

        if (type == Node.DOCUMENT_NODE)
        {
            buffer = "/" + buffer;
            break;
        }
        else if (type == Node.ATTRIBUTE_NODE)
        {
            sep = "@";
            name = cur.nodeName;
            next = cur.parentNode;
        }
        else
        {
            if (type == Node.ELEMENT_NODE) {
                if (cur.nodeName.toLowerCase() == "a"      || cur.nodeName.toLowerCase() == "img" ||
                    cur.nodeName.toLowerCase() == "ul"     || cur.nodeName.toLowerCase() == "document" ||
                    cur.nodeName.toLowerCase() == "document" ||
                    cur.nodeName.toLowerCase() == "font"   || cur.nodeName.toLowerCase() == "#document" )
                    ignore = true;

                var id = null;

                try {// why would this throw?
                    id = cur.getAttribute('id');
                } catch (e) {}

                if (id != null) {
                    
                    if (buffer == "")
                    {
                        buffer = "id('"+id+"')";
                        return buffer;
                    }

                    buffer = "id('" + id + "')" + buffer;
                    return buffer;
                }
            }

            name = cur.nodeName.toLowerCase();
            next = cur.parentNode;

            // now figure out the index
            var tmp = node_before(cur);
            while (tmp != null) {
                if (name == tmp.nodeName.toLowerCase()) {
                    occur++;
                }
                tmp = node_before(tmp);
            }
            occur++;
            
            if (type != Node.ELEMENT_NODE) {

                // fix the names for those nodes where xpath query and dom node name don't match
                if (type == Node.COMMENT_NODE) {
                    ignore = true;
                    name = 'comment()';
                }
                else if (type == Node.PI_NODE) {
                    ignore = true;
                    name = 'processing-instruction()';
                }
                else if (type == Node.TEXT_NODE) {
                    ignore = true;
                    name = 'text()';
                }
                // anything left here has not been coded yet (cdata is broken)
                else {
                    name = '';
                    sep = '';
                    occur = 0;
                }
            }
        }

        if (cur.nodeName.toLowerCase() == "html" ||
            cur.nodeName.toLowerCase() == "body" )
            occur = 0;

        if (ignore == true) {
        }
        else if (occur == 0) {
            buffer = sep + name + buffer;
        }
        else {
            buffer = sep + name + '[' + occur + ']' + buffer;
        }
        ignore = false;
        
        cur = next;
        
    } while (cur != null);

    return buffer;
}

function toXMLString(str) {
    return str.replace(/&/g, "&amp;").replace(/>/g, "&gt;").replace(/</g, "&lt;").replace(/\'/g, "&apos;").replace(/\"/g, "&quot;");
}


function joey_selectedTarget(targetElement)
{
    g_joey_isfile = false;

    var focusedWindow = document.commandDispatcher.focusedWindow;
    g_joey_url  = focusedWindow.location.href;
    g_joey_title = "Microsummary from : " + focusedWindow.location.href; 
    g_joey_content_type = "microsummary/xml";

    var xpath = joey_buildXPath(targetElement);

    /*
      var xpath = prompt("enter an xpath");

    if (!confirm (xpath))
        return;
    */

    var uuidGenerator =  Components.classes["@mozilla.org/uuid-generator;1"].getService(Components.interfaces.nsIUUIDGenerator);
    var uuid = uuidGenerator.generateUUID();
    var uuidString = uuid.toString();

    var str = '<?xml version="1.0" encoding="UTF-8"?>\n'
		+ '<generator xmlns="http://www.mozilla.org/microsummaries/0.1" name="Microsummary for '
		+ toXMLString(focusedWindow.location.href) + '" '
		+ 'uri="urn:' + uuidString + '">\n'
		+ ' <pages>\n'
		+ '   <include>' 
		+ toXMLString(focusedWindow.location.href)
		+ '</include>\n';

    var hint = toXMLString(targetElement.innerHTML);
    hint ="";

    str += ' </pages>\n'
		+ ' <template>\n'
		+ '   <transform xmlns="http://www.w3.org/1999/XSL/Transform" version="1.0">\n'
		+ '     <output method="text"/>\n'
		+ '     <template match="/">\n'
		+ '       <value-of select="' + xpath + '"/>\n'
		+ '     </template>\n'
		+ '   </transform>\n'
		+ ' </template>\n'
        + '<hint>' + hint + '</hint>'
		+ '</generator>\n';

    //    alert(str);

    g_joey_data = str;

    uploadDataFromGlobals(false);
}

/* 
 * 
 */
function joey_enableSelection() {

    g_joeySelectorService.enable();

}




