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


/* This is designed to wrk as a singleton,
 * There is a timer function here calling this again
 * and again as an object.
 */

/* 
TODO need to fix the progressElements counter 
*/


function joeyStatusUpdateService() {

    this.statusObjects           = new Array();
    
    this.referenceBoxElements    = new Array();
    this.referenceProgressXULBox = new Array();

    this.globalMessages          = new Array();
    this.temporaryStack          = new Array();
    this.temporaryStackMessages  = new Array();
    
    this.progressElements = 0;
    this.progressBoxObject = document.getBoxObjectFor(document.getElementById("joeyStatusPanel")); 

    this.createInstance = function factory() {
    
        var newSharedObject = new JoeyStatusUpdateClass(this);
        newSharedObject.nameId = Math.random();     
        this.statusObjects[newSharedObject.nameId] = newSharedObject;
        return newSharedObject;
    
    }
    /* Not in use - it should */
    this.destroyInstance = function destroyObject(refElement) {
    
        this.statusObjects[refElement.nameId]=null;
        
    }

    this.createProgressElement = function createBoxElementAndLabel(nameId, currentElement,styleColor) {
    
                            
                        var parentNode = document.getElementById("joeyBackgroundProgressLayers");
                        var progressElement = document.createElement("box"); 
                  
                        progressElement.setAttribute("id","joeyProgressLayer_"+nameId);
                        progressElement.setAttribute("class","joeyProgressLayer");
                        progressElement.setAttribute("style","background-color:"+styleColor);

                        var commentLabel = document.createElement("label");
                        commentLabel.setAttribute("value","");
                        progressElement.appendChild(commentLabel);
                        
                        var classValue = progressElement.getAttribute("class");

                        if(currentElement.contentType) {
                            
                            if(currentElement.contentType.indexOf("video")>-1) {
                                progressElement.setAttribute("class",classValue+" joeyTypeVideo");
                            } else if(currentElement.contentType.indexOf("audio")>-1) {                            
                                progressElement.setAttribute("class",classValue+" joeyTypeAudio");
                            }
                            
                        } else {
                            /* This is our temporary default icon */                       
                            progressElement.setAttribute("class",classValue+" joeyTypeText");     
                        
                        }
                        
                        // this is the base initial size due to the icon content type ( video etc ) 
                        progressElement.setAttribute("width",16);
                    
                        parentNode.appendChild(progressElement);
                        currentElement.progressElement = progressElement;
                        currentElement.labelElement = commentLabel;
                        
                        // we want to account the total of elements displayed.
                        
                        this.progressElements++;
 
    }
    

    this.refreshActions = function refreshUI(nameId) {
    
        var currentElement = this.statusObjects[nameId];    
                  
        while(currentElement.actionQueue.length>0) {
        
                var currentCommand = null;


                // Get rid of the queued elements, temporarily to a stack, til we reach the First.    
                while(currentElement.actionQueue.length>1) {
                    this.temporaryStack.push(currentElement.actionQueue.pop());
                }
                // Consume the first. 
                if(currentElement.actionQueue.length==1) {
                    currentCommand=currentElement.actionQueue.pop();
                } 
                // Put back all the other ( temp stored ) elemnents back to the queue.
                while(this.temporaryStack.length>=1) {
                    currentElement.actionQueue.push(this.temporaryStack.pop());
                }
                
                var newCommand=currentCommand;
                

                if( newCommand == "login") {
                
                    var currentState = currentElement.statusLogin;
                    if ( currentState == 1 ) 
                    {
                        /* need to review if this is working .. */
                        
                        this.globalMessages.push("logginin");
                    } 
                }
             
                if( newCommand == "download" ) {

             
                    if ( ! currentElement.progressElement ) {

                        this.createProgressElement( nameId, currentElement,'darkblue' );
                       
                    } else {
                         
                        var percentage = currentElement.percentage;
                      //  var totalWidth = 100 / this.progressElements;
                       // var totalWidth = 40;
                       // var percentInt = parseInt(percentage*totalWidth);                
                       // currentElement.progressElement.width=16 + percentInt;
                        currentElement.labelElement.value = parseInt(percentage*100)+"%";
                    }
                    
                }

                if( newCommand == "upload" ) {
                        
                        /* We update upload progress meter just if we have the Download/Upload element
                         * ( download == 2 status completed is the assumption */  
                                                
                        if( currentElement.progressElement ) { 
                        
                            var percentage = currentElement.percentage;
                            //var totalWidth = 100 / this.progressElements;
                          //  var totalWidth = 40;
                           // var percentInt = parseInt(percentage*totalWidth); 
                            
                            //if(percentInt > 0) {                 
                            //} else {
                            //  percentInt =0;
                            //} 
                                         
                            //currentElement.progressElement.width=16+totalWidth - percentInt;
                            currentElement.labelElement.value = parseInt(percentage*100)+"%";

                            
                        } else { 
                        
                            this.createProgressElement(nameId,currentElement,'green');

                        }  
                } 
                
                if ( newCommand == "clear") {
                
                }
               
                if ( newCommand == "completed") {
                
                    if(currentElement.uploadStatus==2) {
                    
                            this.globalMessages.push("uploadCompleted-"+nameId);
                            this.globalMessages.push("clear-"+nameId);
                            this.globalMessages.push("delete-"+nameId);
                    } else if (currentElement.downloadStatus==2) {
                            this.globalMessages.push("downloadCompleted-"+nameId);
                            this.globalMessages.push("clear-"+nameId);
                    } 
                    
                      // Kicks the renderer, which is timer-based. 
                        
                        if(!this.rendering) {
                            this.execRender=true;
                            this.renderer();
                        }
                }
                if ( newCommand == "failed") {
                    if(currentElement.uploadStatus==-1) {
                        this.globalMessages.push("uploadFailed-"+nameId); 
                    } else if (currentElement.downloadStatus==-1) {
                        this.globalMessages.push("downloadFailed-"+nameId);
                    } 
                    this.globalMessages.push("clear-"+nameId);
                    this.globalMessages.push("delete-"+nameId);
                
                    if(!this.rendering) {
                        this.execRender=true;
                        this.renderer();
                    }
                }
        } 
    }

    // Global actions consumer and rendering engine. 
    this.execRender = false;
    
    this.renderer = function rendering() {
         
        var currentCommand = null;
           
        // Get rid of the queued elements, temporarily to a stack, til we reach the First.    
        while(this.globalMessages.length>1) {
            this.temporaryStackMessages.push(this.globalMessages.pop());
        }
        // Consume the first. 
        if(this.globalMessages.length==1) {
            currentCommand=this.globalMessages.pop();
        } 
        // Put back all the other ( temp stored ) elemnents back to the queue.
        while(this.temporaryStackMessages.length>=1) {
            this.globalMessages.push(this.temporaryStackMessages.pop());
        }
        
        if(currentCommand) {
            
            if(currentCommand.indexOf("downloadCompleted-")>-1) {
                    var elementName=currentCommand.split("downloadCompleted-")[1];
                    var currentStatusObject = this.statusObjects[elementName];

                    currentStatusObject.progressElement.setAttribute("style","background-color:green;color:white");
                    currentStatusObject.labelElement.setAttribute("value",joeyString("downloadCompleted"));
            }

            if(currentCommand.indexOf("uploadCompleted-")>-1) {
                    var elementName=currentCommand.split("uploadCompleted-")[1];
                    var currentStatusObject = this.statusObjects[elementName];

                    //currentStatusObject.progressElement.setAttribute("style","");
                    currentStatusObject.labelElement.setAttribute("value",joeyString("uploadCompleted"));
                    
            }
            if(currentCommand.indexOf("downloadFailed-")>-1) {
            
                    var elementName=currentCommand.split("downloadFailed-")[1];
                    var currentStatusObject = this.statusObjects[elementName];

                    currentStatusObject.progressElement.setAttribute("style","background-color:red");
                    currentStatusObject.labelElement.setAttribute("value",joeyString("downloadFailed"));
            }  
            if(currentCommand.indexOf("uploadFailed-")>-1) {
            
                    var elementName=currentCommand.split("uploadFailed-")[1];
                    var currentStatusObject = this.statusObjects[elementName];

                    currentStatusObject.progressElement.setAttribute("style","background-color:red");
                    currentStatusObject.labelElement.setAttribute("value",joeyString("uploadFailed"));
                    
            }            
            if(currentCommand.indexOf("clear-")>-1) {

                    var elementName=currentCommand.split("clear-")[1];
                    var currentStatusObject = this.statusObjects[elementName];
                    currentStatusObject.labelElement.setAttribute("value","");
              
            }
         if(currentCommand.indexOf("delete-")>-1) {
            
            try {
                    var elementName=currentCommand.split("delete-")[1];
                    var currentStatusObject = this.statusObjects[elementName];

                    currentStatusObject.progressElement.parentNode.removeChild(currentStatusObject.progressElement);
                   
                    // need to delete the statusObject;
                    
                 } catch (i) { joeyDumpToConsole(i) } 
                    
            }
            if(currentCommand == "logginin") {
                    var value = joeyString("loggingin"); 
                    document.getElementById("joeyStatusTeller").value = value;
            }
           
        } 
        
        if(this.globalMessages.length > 0) {
            setTimeout("g_joey_statusUpdateService.renderer()",3333);
        } else {
            this.execRender=false;
        }        
    }     
}


function JoeyStatusUpdateClass(parentService) {

  /* We have now the XUL stack with elements in it. 
   * A background Layer and the top layer for 
   * the label. 
   */
 
  this.parentService   = parentService;
  this.nameId          = null;
  this.progressElement = null;

}

/* 
 * UI Wrapper / Deals with the UI 
 * -------
 * TODO: Need to work with multiple instances
 */
JoeyStatusUpdateClass.prototype = 
{

    percentage      : -1,
    contentType     : null,
    actionQueue     : new Array(),
    statusLogin     : 0,
    uploadStatus    : 0,
    downloadStatus  : 0,
    
    destructor: function () 
    {
        this.parentService.destroyInstance(this);
    },
    
    loginStatus: function (aMode,aAdVerb)
    {
	},

    tellStatus:function(verb,from,to,adverb,contentType) 
    {
       var value=null;
       var percentage=0; 
       try { 
           percentage = ((from/to));
       } catch (i) { } 
        
        if (verb == "upload") 
        {

            this.percentage = percentage;
            this.uploadStatus = 1;  // 1 = course, 2 completed, -1 failed; 
            this.actionQueue.push("upload");
 
        }
        if (from==to)
        {
            // this might not be entirely true... basically,
            // at ths point we are waiting to upload...
            this.statusLogin = 1; // 1 = course, 2= completed, -1 failed; 
   
        }
        
        if(verb =="download") {
            this.percentage = percentage;
            this.contentType=contentType;
           
            this.downloadStatus=1;  // 1 = course, 2 completed, -1 failed; 
            this.actionQueue.push("download");
            
        } 

      

        /* adverb */
       
        if(adverb=="completed") 
        {
            //this.progressElement.width=0;
            
            this.percentage = percentage;

            if(verb=="download") { 
                this.downloadStatus = 2;
            } 
            else if(verb=="upload") { 
                this.uploadStatus = 2;
            } 
            this.actionQueue.push("completed");
        } 
        else if(adverb == "failed")
        {
            if(verb=="download") { 
                this.downloadStatus=-1;
            } 
            else if(verb=="upload") { 
                this.uploadStatus=-1;
            } 
            this.actionQueue.push("failed");

        }
        this.parentService.refreshActions(this.nameId);
    }
}


/* 
 * This object is passed to the Component 
 * so that the component can tip and update the UI object 
 */
 
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

