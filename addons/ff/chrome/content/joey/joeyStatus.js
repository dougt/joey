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
            this.progressElement.width=percentage;

        }
        else if (from==to)
        {
            // this might not be entirely true... basically,
            // at ths point we are waiting to upload...

            value = joeyString("loggingin"); 
        }
        else if(verb =="download") {
            // value = "Downloading... ("+from+"/"+to+")";
            value = joeyString("downloading") + "("+percentage+"%)";  
            percentage = this.progressBoxObject.width - percentage;
            this.progressElement.width=percentage;
        } 

        document.getElementById("joeyStatusTeller").value=value;
        

        /* adverb */
       
        if(adverb=="completed") 
        {
            this.progressElement.width=0;
            
            if(verb=="download")
                 document.getElementById("joeyStatusTeller").value=joeyString("downloadCompleted");
            else if(verb=="upload")
                 document.getElementById("joeyStatusTeller").value=joeyString("uploadCompleted");
            
            /* Dougt timer status cleanup */
            setTimeout("document.getElementById('joeyStatusTeller').value=''", 600);
        } 
        else if(adverb == "failed")
        {
            if(verb=="download")
                document.getElementById("joeyStatusTeller").value=joeyString("downloadFailed");
            else if(verb=="upload")
                document.getElementById("joeyStatusTeller").value=joeyString("uploadFailed");

            /* Dougt timer status cleanup */
            setTimeout("document.getElementById('joeyStatusTeller').value=''", 600);
        }
    }
}



function joey_listener(upload, updateObject){
    this.upload = upload;
    this.updateObject = updateObject;
}

joey_listener.prototype =
{
    onProgressChange: function (current, total)
    {
        this.updateObject.tellStatus("upload", current, total);
    },

    onStatusChange: function (action, status)
    {
        if (action == "login")
        {
            if (status == 0)
            {
                this.updateObject.loginStatus("login","completed");
            }
            else if (status == -1 )
            {
                this.updateObject.loginStatus("login","failed");

                var prompts = Components.classes["@mozilla.org/embedcomp/prompt-service;1"]
                                        .getService(Components.interfaces.nsIPromptService);

                var result = prompts.confirm(null, joeyString("loginFailedShort"), joeyString("loginFailedQuestion"));
                if (result == true)
                {
                    // Clear the username and password and try again.
                    clearLoginData();
                    setTimeout(this.upload.upload, 500); // give enough time for us to leave the busy check
                }
            }

            return;
        }

        if (action == "upload")
        {
            if (status == 1) {
                this.updateObject.tellStatus("upload",null,null,"completed");
            } 
            else {            
                this.updateObject.tellStatus("upload",null,null,"failed"); 

                var prompts = Components.classes["@mozilla.org/embedcomp/prompt-service;1"]
                                        .getService(Components.interfaces.nsIPromptService);
                prompts.alert(null, joeyString("uploadFailed"), joeyString("uploadFailedDetail"));
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

