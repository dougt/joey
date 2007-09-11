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

/* 
 * This is nice for the Download + Progress functional
 */
 
function JoeyMediaFetcher(updateStatus, upload, url)
{
	var ioService = Components.classes["@mozilla.org/network/io-service;1"].getService(Components.interfaces.nsIIOService);
    var uri = ioService.newURI(url, null, null);
    var channel = ioService.newChannelFromURI(uri);
    var listener = new JoeyMediaFetcherStreamListener(updateStatus, upload);
    channel.notificationCallbacks = listener;
	channel.asyncOpen(listener, null);
}


function JoeyMediaFetcherStreamListener(updateStatus, upload)
{
    this.upload = upload;
    this.updateStatus = updateStatus;
}

JoeyMediaFetcherStreamListener.prototype = 
{
  stream: null,
  contenttype : null,
  
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
      
      this.stream = Components.classes["@mozilla.org/binaryoutputstream;1"].createInstance(Components.interfaces.nsIBinaryOutputStream);
      this.stream.setOutputStream(fos);

      try
      {
          var http = aRequest.QueryInterface(Components.interfaces.nsIHttpChannel);
          this.contenttype = http.contentType;
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
          this.stream.writeByteArray( data, data.length );
          n += data.length;
      }
  },

  onStopRequest: function (aRequest, aContext, aStatus)
  {
      if (Components.isSuccessCode(aStatus))
      {	
          this.updateStatus.tellStatus("download",null,null,"completed");

          this.stream.close(); 
 
 try { 
          this.upload.setContentType(this.contenttype);
          this.upload.setFile(this.file);
          this.upload.upload();
  
 } catch (i) { joeyDumpToConsole(i) }
 
 
      } 
      else
      {
          // request failed
          this.updateStatus.tellStatus("download",null,null,"failed");
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
      this.updateStatus.tellStatus("download", aProgress, aProgressMax, null, this.contenttype);
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
