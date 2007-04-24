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


var moco_joey_url = "https://joey.labs.mozilla.com";

var g_joey_hasLogged = false;
var g_joey_in_progress = false;

function getJoeyURL()
{
    var psvc = Components.classes["@mozilla.org/preferences-service;1"]
                         .getService(Components.interfaces.nsIPrefBranch);

    var url = moco_joey_url;
    if (psvc.prefHasUserValue("joey.service_url"))
        url = psvc.getCharPref("joey.service_url");

    return url;
}

function debug(str)
{
   var console = Components.classes["@mozilla.org/consoleservice;1"]
						.getService(Components.interfaces.nsIConsoleService);  
   console.logStringMessage("Joey!: "+ str);
}



function JoeyStreamListener(self, aCallbackFunc, aListener)
{
    this.mOwner = self;
    this.mCallbackFunc = aCallbackFunc;
}

JoeyStreamListener.prototype = 
{
  mBytes: [],
  mStream: null,
  mCount: 0,
  mOwner: null,
  
  // nsIStreamListener
  onStartRequest: function (aRequest, aContext) 
  {
      this.mStream = Components.classes["@mozilla.org/scriptableinputstream;1"]
                               .createInstance(Components.interfaces.nsIScriptableInputStream);

  },

  onDataAvailable: function (aRequest, aContext, aStream, aSourceOffset, aCount)
  {
      this.mStream.init(aStream);
      this.mBytes += this.mStream.read(aCount);
      this.mCountRead += aCount;
  },

  onStopRequest: function (aRequest, aContext, aStatus)
  {      
      var httpResponse = -1;
      try {
          var httpChannel = aRequest.QueryInterface(Components.interfaces.nsIHttpChannel);
          httpResponse = httpChannel.responseStatus;
      }
      catch (e) {}

      this.mCallbackFunc(this.mOwner, httpResponse, this.mBytes);
  },

  // nsIChannelEventSink
  onChannelRedirect: function (aOldChannel, aNewChannel, aFlags) 
  {
  },
  
  // nsIInterfaceRequestor
  getInterface: function (aIID)
  {
      try {
          return this.QueryInterface(aIID);
      }
      catch(ex)
      {}
  },

  // nsIProgressEventSink
  onProgress : function (aRequest, aContext, aProgress, aProgressMax) 
  { 
      if (this.mOwner != null && this.mOwner.joey_listener != null)
          this.mOwner.joey_listener.onProgressChange(aProgress, aProgressMax);
  },

  onStatus : function (aRequest, aContext, aStatus, aStatusArg) { },
  
  // nsIHttpEventSink (not implementing will cause annoying exceptions)
  onRedirect : function (aOldChannel, aNewChannel) { },
  
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


function mocoJoey() {}

mocoJoey.prototype = 
{	
	joey_listener: null,                     
	joey_username: "",
	joey_password: "",
	joey_data: "",
	joey_content_type: "",
	joey_title: "",
	joey_url: "",
	xmlhttp: null,

    setLoginInfo: function()
    {
        this.joey_username = "";
        this.joey_password = "";
        
        var passwordManager = Components.classes["@mozilla.org/passwordmanager;1"]
                                        .getService(Components.interfaces.nsIPasswordManager);
    
        var e = passwordManager.enumerator;
    
        while (e.hasMoreElements())
        {
            try 
            {
                var pass = e.getNext().QueryInterface(Components.interfaces.nsIPassword);
                
                if (pass.host == getJoeyURL()) {
                    this.joey_username = pass.user;
                    this.joey_password = pass.password;
                    return true;
                }
            } 
            catch (ex) {}
        }
    
        var prompts = Components.classes["@mozilla.org/embedcomp/prompt-service;1"]
                                .getService(Components.interfaces.nsIPromptService);
        
        var u = {value: ""}; // default the username to user
        var p = {value: ""}; // default the password to pass
        var check = {value: true};  // default the checkbox to true
        
        var result = prompts.promptUsernameAndPassword(null, 
                                                       "Title", 
                                                       "Enter username and password:",
                                                       u,
                                                       p,
                                                       "Save", 
                                                       check);
        
        if (!result)
        {
            // cancel was pressed.
            return false;
        }

        // result is true if OK was pressed, false if cancel was pressed. username.value,
        // password.value, and check.value are set if OK was pressed.
        
        if (check.value)
            passwordManager.addUser(getJoeyURL(), u.value, u.value);
        
        this.joey_username = u.value;
        this.joey_password = p.value;
        return true;
    },
    
	uploadData: function(title, url, data, type)
	{
        this.joey_isfile = false;

		this.uploadDataInternal( title,
                                 url, 
                                 data,
                                 type);

	},

    uploadFile: function(title, url, file, type)
    {
        this.joey_isfile = true;

		this.uploadDataInternal( title,
                                 url, 
                                 file,
                                 type);
    },

    uploadDataInternal: function(title, url, data, type)
    {
        if (g_joey_in_progress == true)
            return -1;

        g_joey_in_progress = true;

        this.joey_title = title;
        this.joey_url = url;
        this.joey_content_type = type;
        this.joey_data = data;

        // kick off the action
        if (g_joey_hasLogged == false)
        {
            if (this.setLoginInfo() == false)
            {
                g_joey_in_progress = false;
                this.joey_title = null;
                this.joey_url = null;
                this.joey_content_type = null;
                this.joey_data = null;
                return -1;
            }
            this.loginToService();
        }
        else
           this.uploadDataFromGlobals();

    }, 

    setListener: function(listener)
    {
        this.joey_listener = listener;
    },

    QueryInterface: function (iid) {
        if (iid.equals(Components.interfaces.mocoJoey) ||
            iid.equals(Components.interfaces.nsISupports))
            return this;

        Components.returnCode = Components.results.NS_ERROR_NO_INTERFACE;
        return null;
    },
    
    
    loginCallback: function (self, status, bytes)
	{
        if (status == 200)
        {
            g_joey_hasLogged = true;
			
            if (self.joey_listener != null)
                self.joey_listener.onStatusChange("login", 0);
            
            // continue going.
            self.uploadDataFromGlobals();
            return;
        }
        
        if (self.joey_listener != null)
        {
            self.joey_listener.onStatusChange("login", -1);
        }
        g_joey_hasLogged=false;
        g_joey_in_progress = false;
        self.setListener(null);
	},

	loginToService: function()
	{
        // get an listener
        var listener = new JoeyStreamListener(this, this.loginCallback, null);

        // the IO service
        var ioService = Components.classes["@mozilla.org/network/io-service;1"]
                                  .getService(Components.interfaces.nsIIOService);

        // create an nsIURI
        var urlstring  = getJoeyURL() + "/users/login";
        var uri = ioService.newURI(urlstring, null, null);
	
        // get a channel for that nsIURI
        var channel = ioService.newChannelFromURI(uri);

        // Create an input stream with the right data.
        var postData = "rest=1&data[User][username]=" + this.joey_username + "&data[User][password]=" + this.joey_password;
        var inputStream = Components.classes["@mozilla.org/io/string-input-stream;1"]
                                    .createInstance(Components.interfaces.nsIStringInputStream);
        inputStream.setData(postData, postData.length);

        // set the input stream on the channel.
        var uploadChannel = channel.QueryInterface(Components.interfaces.nsIUploadChannel);
        uploadChannel.setUploadStream(inputStream, "application/x-www-form-urlencoded", -1);
        var httpChannel = channel.QueryInterface(Components.interfaces.nsIHttpChannel);
        httpChannel.requestMethod = "POST";         // order important - setUploadStream resets to PUT

        //channel.notificationCallbacks = listener;
        channel.asyncOpen(listener, null);
	},
    
	uploadCallback: function (self, status, bytes)
	{
        var listener = self.joey_listener;
        self.setListener(null);
        g_joey_in_progress = false;

        if (listener == null)
            return;

        if (status == 200)
        {
            listener.onStatusChange("upload", 1);  // 1 = okay all good. 
            return;
        }

        if (status == 517) // Out of Space for Upload
        {
            // Not enough space left for user
            listener.onStatusChange("upload", -2);
            return;
        }
        
        if (status == 511)
        {
            // set the hasLogged to false, and try again.
            g_joey_in_progress = false;
            g_joey_hasLogged = false;

            this.uploadDataInternal( this.joey_title,
                                     this.joey_url, 
                                     this.joey_file,
                                     this.joey_content_type);
            return;
        }

        //  TODO: if it is a No Active Session error, try
        //  relogging in a # of times.  
        //
        //  TODO: map more of the http status codes to
        //  something the client might want to deal with.
        //  General error

        listener.onStatusChange("upload", -1);
        return;
	},


	uploadDataFromGlobals: function ()
	{
        const BOUNDARY="111222111";
        
        var mis=Components.classes["@mozilla.org/io/multiplex-input-stream;1"]
                          .createInstance(Components.interfaces.nsIMultiplexInputStream);

        var fileBuffer = null;

        if (this.joey_isfile == true)
        {
            var fin=Components.classes["@mozilla.org/network/file-input-stream;1"]
                              .createInstance(Components.interfaces.nsIFileInputStream);

            fin.init(this.joey_data, 0x1, 0, 0);

            fileBuffer=Components.classes["@mozilla.org/network/buffered-input-stream;1"]
                          .createInstance(Components.interfaces.nsIBufferedInputStream);
            fileBuffer.init(fin, 4096);
        }

        var preamble = Components.classes["@mozilla.org/io/string-input-stream;1"]
                                 .createInstance(Components.interfaces.nsIStringInputStream);

        function createParam( name , value )
        {
            return "--"+BOUNDARY+"\r\n" + 
                   "Content-disposition: form-data;name=\"" + name + "\"\r\n\r\n" +
                   value + "\r\n";
        }

        var start = createParam("rest", "1") +
                    createParam("data[Upload][title]", this.joey_title) +
                    createParam("data[Upload][referrer]", this.joey_url);

        if (fileBuffer == null)
        {
            start += createParam("data[Contentsourcetype][name]", this.joey_content_type) +
                     createParam("data[Contentsource][source]", this.joey_data);
        }

        preamble.setData(start, start.length);

        mis.appendStream(preamble);

        if (fileBuffer != null)
        {
            var filePreamble = Components.classes["@mozilla.org/io/string-input-stream;1"]
                                         .createInstance(Components.interfaces.nsIStringInputStream);
            
            var filePreambleString =  "--"+BOUNDARY+"\r\n" + 
                "Content-disposition: form-data;name=\"data[File][Upload]\";filename=\"data[File][Upload]\"\r\n" +
                "Content-Type: " + this.joey_content_type + "\r\n" +
                "Content-Length: " + this.joey_data.fileSize + "\r\n\r\n";
            
            filePreamble.setData(filePreambleString, filePreambleString.length);
            
            mis.appendStream(filePreamble);
            mis.appendStream(fileBuffer);
        }
        

        var postamble = Components.classes["@mozilla.org/io/string-input-stream;1"]
                                  .createInstance(Components.interfaces.nsIStringInputStream);

        var endstring = "\r\n--"+BOUNDARY+"--\r\n";
        postamble.setData(endstring, endstring.length);

        mis.appendStream(postamble);

        // get an listener
        var listener = new JoeyStreamListener(this, this.uploadCallback, null);

        // the IO service
        var ioService = Components.classes["@mozilla.org/network/io-service;1"]
                                  .getService(Components.interfaces.nsIIOService);

        // create an nsIURI
        var urlstring  = getJoeyURL() + "/uploads/add";
        var uri = ioService.newURI(urlstring, null, null);
	
        // get a channel for that nsIURI
        var channel = ioService.newChannelFromURI(uri);

        // set the input stream on the channel.
        var uploadChannel = channel.QueryInterface(Components.interfaces.nsIUploadChannel);
        uploadChannel.setUploadStream(mis, "multipart/form-data, boundary="+BOUNDARY, mis.available());
        var httpChannel = channel.QueryInterface(Components.interfaces.nsIHttpChannel);
        httpChannel.requestMethod = "POST";         // order important - setUploadStream resets to PUT

        channel.notificationCallbacks = listener;
        channel.asyncOpen(listener, null);
	},
};

var myModule = {
    firstTime: true,

    registerSelf: function (compMgr, fileSpec, location, type) 
    {
        if (this.firstTime) 
        {
            this.firstTime = false;
            throw Components.results.NS_ERROR_FACTORY_REGISTER_AGAIN;
        }

        compMgr = compMgr.QueryInterface(Components.interfaces.nsIComponentRegistrar);
        compMgr.registerFactoryLocation(this.myCID,
                                        "Joey Mozilla Component",
                                        this.myProgID,
                                        fileSpec,
                                        location,
                                        type);
    },

    getClassObject: function (compMgr, cid, iid) 
    {
        if (!cid.equals(this.myCID))
            throw Components.results.NS_ERROR_NO_INTERFACE;

        if (!iid.equals(Components.interfaces.nsIFactory))
            throw Components.results.NS_ERROR_NOT_IMPLEMENTED;

        return this.myFactory;
    },

    myCID: Components.ID("{66b3290c-6c74-4f15-8132-d6cc74e5d37f}"),
    myProgID: "@mozilla.com/joey;1",

    myFactory: 
    {
        createInstance: function (outer, iid) 
        {
            if (outer != null)
                throw Components.results.NS_ERROR_NO_AGGREGATION;
            return (new mocoJoey()).QueryInterface(iid);
        }
    },

    canUnload: function(compMgr) 
    {
        return true;
    }
};

function NSGetModule(compMgr, fileSpec) 
{
    return myModule;
}
