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

const JOEY_VERSION = "0.3.0.0";

/* 
 * Event listeners associated to the joeyOverlay app 
 */

window.addEventListener("load", joeyStartup, false);
window.addEventListener("mousedown",joeyOnMouseDown,false); 

var g_joey_media_content_types = ['flv','mov','wmv','avi','mpeg','mp3','wav']; 
var g_joey_media_url  = null;
var g_joey_media_type = null;

/* this is the url to the image that a context clict was done over */
var g_joey_image_source = null;

/* the browser object */
var g_joey_gBrowser = null;

/* the object that deals with the UI notifications */
var g_joey_statusUpdateService = null;

/* the timer that periodically uploads the url's of all of the tabs */
var g_joey_tab_upload_timer = null;


function joey_upload(updateObject)
{
    this.updateObject = updateObject;

    this.data = null;
    this.file = null;
    this.title= null;
    this.url  = null;
    this.contentType = null;
}

joey_upload.prototype = 
{
    setTitle: function (title)
    {
        this.title = title;
	},

    setURL: function (url)
    {
        this.url= url;
    },

    setFile: function (file)
    {
        this.file = file;
    },

    setData: function (data)
    {
        this.data = data;
    },

    setContentType: function(type)
    {
        this.contentType = type;
    },

    askUserForTitle: function()
    {
    
    try { 
        
            var prompts = Components.classes["@mozilla.org/embedcomp/prompt-service;1"]
                                    .getService(Components.interfaces.nsIPromptService);
            
            var titleObject = {value: this.title}; // default the username to user
            
            var psvc = Components.classes["@mozilla.org/preferences-service;1"]
                                 .getService(Components.interfaces.nsIPrefBranch);

            var askState = psvc.getBoolPref("joey.askForTitle");
            
            if(askState == true ) {
                
                var check = {value: askState};  // default the checkbox to true
                var result = prompts.prompt(null,
                                            joeyString("promptTitle.windowTitle"), 
                                            joeyString("promptTitle.label"),
                                            titleObject,
                                            joeyString("promptTitle.prefQuestion"), 
                                            check);
                if(result) {
                    this.title = titleObject.value;
                }
                psvc.setBoolPref("joey.askForTitle",check.value);       
            } 
            
            } catch (i) { joeyDumpToConsole(i) }
            
            
        
    },

    upload: function()
    {
        
        this.askUserForTitle();

        var joey = Components.classes["@mozilla.com/joey;1"]
                             .createInstance(Components.interfaces.mocoJoey);
        
        joey.setListener(new joey_listener(this, this.updateObject));
    
        if (this.file != null)
        {
            joey.uploadFile(this.title,
                            this.url,
                            this.file,
                            this.contentType);
        }
        else
        {
            joey.uploadData(this.title,
                            this.url,
                            this.data,
                            this.contentType);
        }
    }
}


var g_joey_bundleElement = null;
function joeyString(ref) 
{
    if (g_joey_bundleElement == null)
        g_joey_bundleElement = document.getElementById("joey_properties");

     return g_joey_bundleElement.getString(ref);
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
		g_joey_image_source = imageElement.src;
	else
		g_joey_image_source = null;
    
    try 
    {    
    	var menuItem = document.getElementById('joey_selectedImage');
	    menuItem.setAttribute("hidden", g_joey_image_source == null ? "true" : "false");
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

    var upload = new joey_upload( g_joey_statusUpdateService.createInstance() );
    upload.setFile(file);
    upload.setContentType("text/plain");
    upload.setTitle(focusedWindow.document.title);
    upload.setURL(focusedWindow.location.href);

    upload.upload();
}

function joey_currentTabs()
{
    // Loop through all of the windows looking and tabs.
    try {
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
        
        var upload = new joey_upload( g_joey_statusUpdateService.createInstance() );
        upload.setFile(file);
        upload.setContentType("browser/stuff");
        upload.setTitle("Current Tabs");
        upload.setURL("about:CurrentTabs");
        upload.upload();
    } catch(e) {}
}

function joey_selected()
{
	if (g_joey_image_source)
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

    var data = "rss=" + feedLocation + "\r\n";

    if (icon != null)
        data = data + "icon=" + icon + "\r\n";

    var upload = new joey_upload( g_joey_statusUpdateService.createInstance() );
    upload.setData(data);
    upload.setContentType("rss-source/text");
    upload.setTitle(baseTitle);
    upload.setURL(feedLocation);
    upload.upload();
}

// Check XUL statusbar item
function joey_launchPopup() 
{
  document.getElementById('joeyStatusPopup').showPopup(document.getElementById('joeyStatusButton'),-1,-1,'popup','topright', 'bottomright')
}

function joey_selectedImage()
{
    var focusedWindow = document.commandDispatcher.focusedWindow;

    var statusUpdateObject = g_joey_statusUpdateService.createInstance();
    
    var upload = new joey_upload( statusUpdateObject );
    upload.setTitle(focusedWindow.document.title);
    upload.setURL(focusedWindow.location.href);
    
    JoeyMediaFetcher( statusUpdateObject , upload, g_joey_image_source);
       
}

/* todo: this needs to be per page... */
var httpscanner = {
  observe: function(subject,topic,data){

        try {
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
                
                if(testContentType(g_joey_media_content_types)){
                    document.getElementById("joeyMediaMenuItem").setAttribute("disabled","false");
                 
                    if(contentType.indexOf('video')>-1) {
                        document.getElementById("joeyMediaMenuItem").setAttribute("class","menuitem-iconic");
                        document.getElementById("joeyMediaMenuItem").setAttribute("image","chrome://joey/skin/type_video.png");
                    }
                    if(contentType.indexOf('audio')>-1) {
                        document.getElementById("joeyMediaMenuItem").setAttribute("class","menuitem-iconic");
                        document.getElementById("joeyMediaMenuItem").setAttribute("image","chrome://joey/skin/type_music.png");
                    }
                    document.getElementById("menuItem-joeyMedia-tooltip").setAttribute("value","Upload: "+document.commandDispatcher.focusedWindow.document.title);
                    g_joey_media_type = contentType;
                    g_joey_media_url = mediaLocation;
                }
            }
        } catch (e) { joeyDumpToConsole(e)}
    }
}
var observerService = Components.classes["@mozilla.org/observer-service;1"].getService(Components.interfaces.nsIObserverService);
	observerService.addObserver(httpscanner,"http-on-examine-response",false);

function joey_uploadFoundMedia() // refactor with joey_selectedImage
{
    var focusedWindow = document.commandDispatcher.focusedWindow;

    var statusUpdateObject = g_joey_statusUpdateService.createInstance();
    
    statusUpdateObject.referenceTitle = focusedWindow.document.title;
    
    var upload = new joey_upload( statusUpdateObject );
    upload.setTitle(focusedWindow.document.title);
    upload.setURL(focusedWindow.location.href);
    upload.setContentType(g_joey_media_type);

    statusUpdateObject.tellStatus("queued", null, null, null, g_joey_media_type);

    JoeyMediaFetcher( statusUpdateObject , upload, g_joey_media_url);
    
}

function contentLoaded()
{
    joeyFeedwatcher();
}

function joeyStartup()
{
    window.document.getElementById("content").addEventListener("DOMContentLoaded", contentLoaded, false);
    
    var wm = Components.classes["@mozilla.org/appshell/window-mediator;1"]
                       .getService(Components.interfaces.nsIWindowMediator);
    
    gWin = wm.getMostRecentWindow("navigator:browser");
    
    g_joey_gBrowser = gWin.gBrowser;


    g_joey_statusUpdateService = new joeyStatusUpdateService();

    var pref = Components.classes["@mozilla.org/preferences-service;1"]
                         .getService(Components.interfaces.nsIPrefBranch);

    /* 
     * First Run function..
     */ 
    try {
        var firstRun = pref.getCharPref("joey.lastversion"); 
        var url = getJoeyServerURL() + "/version/index/" + JOEY_VERSION;
        var showNotes = false;

        if(firstRun == "firstrun") {
            url += "/firstrun";
            showNotes = true;
        } 
        else if(firstRun != JOEY_VERSION) {
            showNotes = true;
        }

        if (showNotes == true) {
            setTimeout(function() { 
                    window.openUILinkIn(url, "tab");
                    pref.setCharPref("joey.lastversion", JOEY_VERSION);
                }, 500);
        }

    } catch(i) { joeyDumpToConsole(i) } 

    // kick off the tab uploading thread.  TODO shouldn't
    // this not be init'ed more than once?  does this get
    // inited per window?
    g_run_tab_upload();
}


function g_run_tab_upload()
{
    var psvc = Components.classes["@mozilla.org/preferences-service;1"]
                         .getService(Components.interfaces.nsIPrefBranch);

    var enabled = false;
    var timeout = 300000; // 5min

    if (psvc.prefHasUserValue("joey.tab.upload.timeout"))
        timeout = psvc.getIntPref("joey.tab.upload.timeout");

    if (psvc.prefHasUserValue("joey.tab.upload.enabled"))
        enabled = psvc.getBoolPref("joey.tab.upload.enabled");

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


function joeyFeedwatcher()
{
    var psvc = Components.classes["@mozilla.org/preferences-service;1"]
                         .getService(Components.interfaces.nsIPrefBranch);

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

function toXMLString(str) {
    return str.replace(/&/g, "&amp;").replace(/>/g, "&gt;").replace(/</g, "&lt;").replace(/\'/g, "&apos;").replace(/\"/g, "&quot;");
}


function joey_selectedTarget(targetElement)
{
    var xpath = joey_buildXPath(targetElement);

    /*
      var xpath = prompt("enter an xpath");

    if (!confirm (xpath))
        return;
    */

    var focusedWindow = document.commandDispatcher.focusedWindow;

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

    var focusedWindow = document.commandDispatcher.focusedWindow;
    
    var upload = new joey_upload(g_joey_statusUpdateService.createInstance());
    upload.setContentType("microsummary/xml");
    upload.setTitle("Microsummary from : " + focusedWindow.location.href);
    upload.setURL(focusedWindow.location.href);
    upload.setData(str);
    upload.upload();
}

/* 
 * 
 */
function joey_enableSelection() {

    g_joeySelectorService.init(g_joey_gBrowser, joey_selectedTarget);
    g_joeySelectorService.enable();

}


/* 
 * Status Manager 
 */
 function joey_launchUDManagerPopup() 
{
  document.getElementById('joeyUDManager').showPopup(document.getElementById('joeyStatusBox'),-1,-1,'popup','topright', 'bottomright')
}


gJoeyDumpWindow = null;
gJoeyDumpWindowLastRef =null;

function joeyDumpToWindow(aMessage) {

    try { 
    if(!gJoeyDumpWindow) {

        gJoeyDumpWindow = window.open("chrome://joey/content/dump.html","dumpwindow","resizable=1,scrollbars=1,width=700,height=500");
        gJoeyDumpWindowLastRef = gJoeyDumpWindow.document.getElementById("ref1");
        
    }
    
    var newElement = gJoeyDumpWindow.document.createElement("div");
    var newText = gJoeyDumpWindow.document.createTextNode(aMessage);
    newElement.appendChild(newText);
    gJoeyDumpWindowLastRef = gJoeyDumpWindow.document.getElementById("dumparea").insertBefore(newElement,gJoeyDumpWindowLastRef);


    } catch(i) { joeyDumpToConsole(i) } 
    
}