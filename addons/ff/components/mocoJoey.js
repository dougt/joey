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


var moco_joey_url = "https://joey.labs.mozilla.com/services";


function debug(str)
{
   var console = Components.classes["@mozilla.org/consoleservice;1"]
						.getService(Components.interfaces.nsIConsoleService);  
   console.logStringMessage("Joey!: "+ str);
}

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
                
                if (pass.host == moco_joey_url) {
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
            passwordManager.addUser(moco_joey_url, u.value, u.value);
        
        this.joey_username = u.value;
        this.joey_password = p.value;
    },
    
	uploadData: function(name, title, url, data, size, type, uuid)
	{
        var b = new G_Base64();
		this.uploadDataInternal( name, 
							b.encodeByteArray(b.arrayifyString(title)),
							b.encodeByteArray(b.arrayifyString(url)), 
							b.encodeByteArray(b.arrayifyString(data)),
							size, 
							type,
							uuid);

	},

	uploadBinaryData: function(name, title, url, data, size, type, uuid)
	{
		var b = new G_Base64();
		this.uploadDataInternal( name, 
							b.encodeByteArray(b.arrayifyString(title)),
							b.encodeByteArray(b.arrayifyString(url)), 
							b.encodeByteArray(data),
							size, 
							type,
							uuid);
	},

    uploadDataInternal: function(name, title, url, data, size, type, uuid)
    {
        if (this.joey_in_progress == true)
            return -1;
            

        this.joey_name  = name;
        this.joey_title = title;
        this.joey_url = url;
        this.joey_content_type = type;
        this.joey_data = data;
        this.joey_data_size = size;
        this.joey_type = type;
        this.joey_uuid = uuid;

        debug("XXXXXXXXXXXXXXXX  The uuid for this upload is: " + uuid + " " + this.joey_uuid);

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
    
    
    loginCallback: function (self)
	{
		if (self.xmlhttp.readyState==4)
		{ 
			if (self.xmlhttp.status==200)
			{
                if (self.xmlhttp.responseText.indexOf('-1') == -1)
				{
					self.joey_hasLogged = true;
					
					if (self.joey_listener != null)
						self.joey_listener.onStatusChange(self.joey_name, self.joey_url, 0);
					
					// continue going.
					self.uploadDataFromGlobals();
					return;
				}
			}
			
			debug("problem logging in... " + self.joey_listener);
			
			if (self.joey_listener != null)
			{
				self.joey_listener.onStatusChange(self.joey_name, self.joey_url, -1);
			}
			
			self.joey_hasLogged=false;
			self.joey_in_progress = false;
			self.joey_listener = null;
		}   
	},

	loginToService: function()
	{
		this.xmlhttp = Components.classes["@mozilla.org/xmlextras/xmlhttprequest;1"]
							.createInstance(Components.interfaces.nsIXMLHttpRequest);
		

        var url  = moco_joey_url + "/rest/login.php";
        var data = "username=" + this.joey_username + "&password=" + this.joey_password;

		this.xmlhttp.open("POST", url, true);
		
		var self = this;
     	this.xmlhttp.onreadystatechange = function() {self.loginCallback(self);}
     	this.xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		this.xmlhttp.send(data);
	},
    
	uploadCallback: function(self)
	{
		debug("uploadCallback " + this.xmlhttp.readyState + "(" + self + ")");
		if (self.xmlhttp.readyState==4)
		{ 
			var listener = self.joey_listener;
			self.joey_listener = null;
			self.joey_in_progress = false;

            

			if (self.xmlhttp.status==200)
			{
				if (self.xmlhttp.responseText.indexOf('-1') == -1)
				{
					if (listener != null)
						listener.onStatusChange(self.joey_name, self.joey_url, 1);
					
					return;
				}
			}
			
			if (listener != null)
				listener.onStatusChange(self.joey_name, self.joey_url, -2);
		}
	},


	uploadDataFromGlobals: function ()
	{
		this.xmlhttp = Components.classes["@mozilla.org/xmlextras/xmlhttprequest;1"]
     							 .createInstance(Components.interfaces.nsIXMLHttpRequest);
			

        var url  = moco_joey_url + "/rest/upload.php";
        var data = "name=" + this.joey_name +  "&title=" + this.joey_title +
                   "&uri="  + this.joey_url  + "&size="  + this.joey_data_size +
                   "&uuid=" + this.joey_uuid + "&type="  + this.joey_content_type +
                   "&data=" + encodeURIComponent(this.joey_data); 

		this.xmlhttp.open("POST", url, true);

		var self = this;
     	this.xmlhttp.onreadystatechange = function() {self.uploadCallback(self);}
     	this.xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		this.xmlhttp.send(data);
		
		//debug("uploadData:");
		//debug(this.joey_name);
		//debug(this.joey_title);
		//debug(this.joey_url);
		//debug(this.joey_content_type);
		//debug(this.joey_data_size);
		//debug(this.joey_data);
		//debug(this.joey_uuid);
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
