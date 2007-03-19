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


var moco_joey_url = "https://joey.labs.mozilla.com/site";

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
      this.mStream = Components.classes['@mozilla.org/binaryinputstream;1']
                               .createInstance(Components.interfaces.nsIBinaryInputStream);
  },

  onDataAvailable: function (aRequest, aContext, aStream, aSourceOffset, aCount)
  {
      this.mStream.setInputStream(aStream);
      var chunk = this.mStream.readByteArray(aCount);
      this.mBytes = this.mBytes.concat(chunk);
      this.mCountRead += aCount;
      
      // do the notification here.
  },

  onStopRequest: function (aRequest, aContext, aStatus)
  {
      this.mCallbackFunc(this.mOwner, aStatus, this.mBytes);
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


function mocoJoey() {}

mocoJoey.prototype = 
{	
	joey_hasLogged: false,
	joey_in_progress: false,
	joey_listener: null,                     
	joey_username: "",
	joey_password: "",
	joey_name: "",
	joey_data: "",
	joey_data_size: 0,
	joey_content_type: "",
	joey_title: "",
	joey_url: "",
	joey_uuid: "",
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
                    return;
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
            return;
        }

        // result is true if OK was pressed, false if cancel was pressed. username.value,
        // password.value, and check.value are set if OK was pressed.
        
        if (check.value)
            passwordManager.addUser(getJoeyURL(), u.value, u.value);
        
        this.joey_username = u.value;
        this.joey_password = p.value;
    },
    
	uploadData: function(name, title, url, data, size, type, uuid)
	{
        this.joey_isfile = false;

		this.uploadDataInternal( name, 
                                 title,
                                 url, 
                                 data,
                                 size, 
                                 type,
                                 uuid);

	},

	uploadBinaryData: function(name, title, url, data, size, type, uuid)
	{
        this.joey_isfile = false;

        var b = new G_Base64();
		this.uploadDataInternal( name, 
                                 title,
                                 url, 
                                 b.encodeByteArray(data),
                                 size, 
                                 type,
                                 uuid);
	},

    uploadFile: function(name, title, url, file, type, uuid)
    {
        this.joey_isfile = true;

		this.uploadDataInternal( name, 
                                 title,
                                 url, 
                                 file,
                                 -1, 
                                 type,
                                 uuid);
    },

    uploadDataInternal: function(name, title, url, data, size, type, uuid)
    {
        if (this.joey_in_progress == true)
            return -1;

        this.joey_in_progress = true;

        this.joey_name  = name;
        this.joey_title = title;
        this.joey_url = url;
        this.joey_content_type = type;
        this.joey_data = data;
        this.joey_data_size = size;
        this.joey_type = type;
        this.joey_uuid = uuid;

        // debug("XXXXXXXXXXXXXXXX  The uuid for this upload is: " + uuid + " " + this.joey_uuid);

        // kick off the action
        if (this.joey_hasLogged == false)
        {
            this.setLoginInfo();
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
        if (bytes.indexOf('-1') == -1)
        {
            self.joey_hasLogged = true;
			
            if (self.joey_listener != null)
                self.joey_listener.onStatusChange("login", 0);
            
            // continue going.
            self.uploadDataFromGlobals();
            return;
        }
        
        debug("problem logging in... " + self.joey_listener);
		
        if (self.joey_listener != null)
        {
            self.joey_listener.onStatusChange("login", -1);
        }
        self.joey_hasLogged=false;
        self.joey_in_progress = false;
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
        self.joey_in_progress = false;
        
        if (bytes.indexOf('-1') == -1)
        {
            if (listener != null)
                listener.onStatusChange("upload", 1);  // 1 = okay all good. 
            
            return;
        }

        if (listener != null)
            listener.onStatusChange("upload", -1);
        
        debug ('upload failed');
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
                    createParam("data[Upload][referrer]",   this.joey_url) +
                    createParam("data[Contentsource][source]",   this.joey_url) +
                    createParam("data[Contentsourcetype][name]", this.joey_content_type);

        if (fileBuffer == null)
        {
            start += createParam("data[Contentsource][source]", this.joey_data);
        }

        preamble.setData(start, start.length);

        //        debug(start);

        var postamble = Components.classes["@mozilla.org/io/string-input-stream;1"]
                                  .createInstance(Components.interfaces.nsIStringInputStream);

        var endstring = "\r\n--"+BOUNDARY+"--\r\n";
        postamble.setData(endstring, endstring.length);

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
        
        //        debug (" request sent!! " );
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


// btoa: From 
//       http://lxr.mozilla.org/mozilla1.8/source/toolkit/components/url-classifier/content/moz/base64.js

/**
 * Base64 en/decoder. Useful in contexts that don't have atob/btoa, or
 * when you need a custom encoding function (e.g., websafe base64).
 *
 * @constructor
 */
function G_Base64() {
  this.byteToCharMap_ = {};
  this.charToByteMap_ = {};
  this.byteToCharMapWebSafe_ = {};
  this.charToByteMapWebSafe_ = {};
  this.init_();
}

/**
 * Our default alphabet. Value 64 (=) is special; it means "nothing."
 */ 
G_Base64.ENCODED_VALS = "ABCDEFGHIJKLMNOPQRSTUVWXYZ" +
                        "abcdefghijklmnopqrstuvwxyz" +
                        "0123456789+/=";

/**
 * Our websafe alphabet. Value 64 (=) is special; it means "nothing."
 */ 
G_Base64.ENCODED_VALS_WEBSAFE = "ABCDEFGHIJKLMNOPQRSTUVWXYZ" +
                                "abcdefghijklmnopqrstuvwxyz" +
                                "0123456789-_=";

/**
 * We want quick mappings back and forth, so we precompute two maps.
 */
G_Base64.prototype.init_ = function() {
  for (var i = 0; i < G_Base64.ENCODED_VALS.length; i++) {
    this.byteToCharMap_[i] = G_Base64.ENCODED_VALS.charAt(i);
    this.charToByteMap_[this.byteToCharMap_[i]] = i;
    this.byteToCharMapWebSafe_[i] = G_Base64.ENCODED_VALS_WEBSAFE.charAt(i);
    this.charToByteMapWebSafe_[this.byteToCharMapWebSafe_[i]] = i;
  }
}

/**
 * Base64-encode an array of bytes.
 *
 * @param input An array of bytes (numbers with value in [0, 255]) to encode
 *
 * @param opt_webSafe Boolean indicating we should use the alternative alphabet 
 *
 * @returns String containing the base64 encoding
 */
G_Base64.prototype.encodeByteArray = function(input, opt_webSafe) {

//  if (!(input instanceof Array))
//    throw new Error("encodeByteArray takes an array as a parameter");

  var byteToCharMap = opt_webSafe ? 
                      this.byteToCharMapWebSafe_ :
                      this.byteToCharMap_;

  var output = [];

  var i = 0;
  while (i < input.length) {

    var byte1 = input[i];
    var haveByte2 = i + 1 < input.length;
    var byte2 = haveByte2 ? input[i + 1] : 0;
    var haveByte3 = i + 2 < input.length;
    var byte3 = haveByte3 ? input[i + 2] : 0;

    var outByte1 = byte1 >> 2;
    var outByte2 = ((byte1 & 0x03) << 4) | (byte2 >> 4);
    var outByte3 = ((byte2 & 0x0F) << 2) | (byte3 >> 6);
    var outByte4 = byte3 & 0x3F;

    if (!haveByte3) {
      outByte4 = 64;
      
      if (!haveByte2)
        outByte3 = 64;
    }
    
    output.push(byteToCharMap[outByte1]);
    output.push(byteToCharMap[outByte2]);
    output.push(byteToCharMap[outByte3]);
    output.push(byteToCharMap[outByte4]);

    i += 3;
  }

  return output.join("");
}

/**
 * Base64-decode a string.
 *
 * @param input String to decode
 *
 * @param opt_webSafe Boolean indicating we should use the alternative alphabet 
 * 
 * @returns Array of bytes representing the decoded value.
 */
G_Base64.prototype.decodeString = function(input, opt_webSafe) {

  if (input.length % 4)
    throw new Error("Length of b64-encoded data must be zero mod four");

  var charToByteMap = opt_webSafe ? 
                      this.charToByteMapWebSafe_ :
                      this.charToByteMap_;

  var output = [];

  var i = 0;
  while (i < input.length) {

    var byte1 = charToByteMap[input.charAt(i)];
    var byte2 = charToByteMap[input.charAt(i + 1)];
    var byte3 = charToByteMap[input.charAt(i + 2)];
    var byte4 = charToByteMap[input.charAt(i + 3)];

    if (byte1 === undefined || byte2 === undefined ||
        byte3 === undefined || byte4 === undefined)
      throw new Error("String contains characters not in our alphabet: " +
                      input);

    var outByte1 = (byte1 << 2) | (byte2 >> 4);
    output.push(outByte1);
    
    if (byte3 != 64) {
      var outByte2 = ((byte2 << 4) & 0xF0) | (byte3 >> 2);
      output.push(outByte2);
      
      if (byte4 != 64) {
        var outByte3 = ((byte3 << 6) & 0xC0) | byte4;
        output.push(outByte3);
      }
    }

    i += 4;
  }

  return output;
}

/**
 * Helper function that turns a string into an array of numbers. 
 *
 * @param str String to arrify
 *
 * @returns Array holding numbers corresponding to the UCS character codes
 *          of each character in str
 */
G_Base64.prototype.arrayifyString = function(str) {
  var output = [];
  for (var i = 0; i < str.length; i++)
    output.push(str.charCodeAt(i));
  return output;
}

/**
 * Helper function that turns an array of numbers into the string
 * given by the concatenation of the characters to which the numbesr
 * correspond (got that?).
 *
 * @param array Array of numbers representing characters
 *
 * @returns Stringification of the array
 */ 
G_Base64.prototype.stringifyArray = function(array) {
  var output = [];
  for (var i = 0; i < array.length; i++)
    output[i] = String.fromCharCode(array[i]);
  return output.join("");
}
