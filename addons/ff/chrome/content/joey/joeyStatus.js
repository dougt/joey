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


function joeyStatusUpdateService() {

    this.statusObjects           = new Array();
    
    this.referenceBoxElements    = new Array();
    this.referenceProgressXULBox = new Array();

    this.globalMessages          = new Array();
    this.temporaryStack          = new Array();
    this.temporaryStackMessages  = new Array();
    
    this.progressBoxObject = document.getBoxObjectFor(document.getElementById("joeyUDManager")); 

    this.uniqueCounter = 0;
    
    this.createInstance = function factory() {
    
        var newSharedObject = new JoeyStatusUpdateClass(this);
        var intUniqueID = this.uniqueCounter++;
        newSharedObject.nameId = intUniqueID;   
        this.statusObjects[intUniqueID] = newSharedObject;
        return newSharedObject;
        
    }

    this.createProgressElement = function createBoxElementAndLabel(nameId, currentElement) {
                
            var parentNode = document.getElementById("joeyUDManager");
            var progressElement = document.createElement("stack"); 
                  
            progressElement.setAttribute("id","joeyProgressLayer_"+nameId);
                        
            var progressMeter2 = document.createElement("box");
            progressMeter2.setAttribute("width","100");
            progressMeter2.setAttribute("class","joeyProgressMeter");
            progressMeter2.setAttribute("height","16");
                        
                        progressElement.appendChild(progressMeter2);

                        var commentLabel = document.createElement("label");
                        commentLabel.setAttribute("style","padding-left:16px");
                        progressElement.appendChild(commentLabel);
                        

                        var classValue = commentLabel.getAttribute("class");

                        if(currentElement.contentType) {
                            
                            if(currentElement.contentType.indexOf("video")>-1) {
                                commentLabel.setAttribute("class",classValue+" joeyTypeVideo");

                            } else if(currentElement.contentType.indexOf("audio")>-1) {                            
                                commentLabel.setAttribute("class",classValue+" joeyTypeAudio");
                            }
                            
                        } else {
                            commentLabel.setAttribute("class",classValue+" joeyTypeText");                             
                        }
                        
                    
                        parentNode.appendChild(progressElement);
                        
                        currentElement.progressElement = progressElement;
                        currentElement.progressMeter = progressMeter2;
                        currentElement.labelElement = commentLabel;
                        

    }
    
    this.generalUDUpdate = function refreshGeneralStatusObject() {
    
         var totalPercentage = 0;
         var count =0;
         for ( eKey in this.statusObjects ) { 

            var currentElement = this.statusObjects[eKey];

            if(currentElement) { 
            
                      count++;
                      var percentage = currentElement.percentage;
                      if(percentage>=0) {
                        var localProgress = parseInt(percentage*50);
                        if(currentElement.downloadStatus==2) { 
                            localProgress+=50;
                        }
                        totalPercentage += localProgress;
                      } 
            } 
            
         }
        
        var statusBoxObject = document.getElementById("joeyStatusBox");
        if(count>0) { 
            if(statusBoxObject.collapsed) { 
                statusBoxObject.collapsed=false;
            } 
            
            /* Need to be friendly with the Renderer, so top messages are more important than this */
            if(totalPercentage>0) {
            statusBoxObject.label = joeyString("joeyWord")+": "+parseInt(totalPercentage/count)+"%";
            } else {
            statusBoxObject.label = joeyString("joeyWord")+": waiting..";
            }
        } else {
            statusBoxObject.collapsed=true;
        }
    }
    
    this.accessObject = function retrieveStatusObjectFromHash(keyVariant) {
        var objectRef = this.statusObjects[ parseInt( keyVariant ) ];
        return objectRef;
    }

    this.dumpObject = function dumpStatusObjectFromHash(keyVariant) {
        var intKey = parseInt(keyVariant);
        this.statusObjects[ intKey ] = null;
    }
    
    this.refreshActions = function refreshUI(nameId) {
    
        var currentElement = this.accessObject(nameId);    
        
        while(currentElement.actionQueue.length > 0) {
        
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
             
                if ( newCommand == "download" || newCommand == "upload"  || newCommand=="queued") {

                    if ( ! currentElement.progressElement ) {
                        this.createProgressElement( nameId, currentElement );
                        
                             if(newCommand=="queued") {
                             
                                currentElement.labelElement.setAttribute("value", "Pending: "+ currentElement.referenceTitle );                            
                                this.generalUDUpdate();
                             }
                    } else {

                      var percentage  = currentElement.percentage;
                      
                      if(percentage>=0) {

                          var TotalWidth  = parseInt(this.progressBoxObject.width/2);
                          
                          
                          var curProgress = parseInt(percentage*TotalWidth);
                          var curPercentage = parseInt ( percentage*50);
                          
                          if(currentElement.downloadStatus==2) {
                           curProgress += TotalWidth;
                           curPercentage += 50;
                          }
                          
                          if(newCommand == "download" || newCommand=="upload") {
                            currentElement.labelElement.setAttribute("value", curPercentage +"%"+" of "+ currentElement.referenceTitle );                            
                            currentElement.progressMeter.style.backgroundPosition = curProgress+"px 0px";
                                              
                          } 
                          this.generalUDUpdate();
                     
                      } 
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

    this.execRender = false;
    
    this.renderer = function rendering() {
         
        var currentCommand = null;
           
        // Get rid of the queued elements, temporarily to a stack, til we reach the First.    
        while(this.globalMessages.length > 1) {
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

            var stringParams = currentCommand.split("-");
            var commandValue = stringParams[0];
            var nameValue    = stringParams[1];
                        
            var currentStatusObject = this.accessObject(nameValue);
            
            if(commandValue == "downloadCompleted") {
                    var currentStatusObject = this.accessObject(nameValue);
                    currentStatusObject.labelElement.setAttribute("value",joeyString("downloadCompleted"));
            }

            if(commandValue == "uploadCompleted") {
                    var currentStatusObject = this.accessObject(nameValue);
                    currentStatusObject.labelElement.setAttribute("value",joeyString("uploadCompleted"));
                    document.getElementById("joeyStatusBox").label= joeyString("uploadCompleted");
                    this.generalUDUpdate();
            }
            if(commandValue == "downloadFailed") {
            
                    var currentStatusObject = this.accessObject(nameValue);
                    currentStatusObject.progressElement.setAttribute("style","background-color:red");
                    currentStatusObject.labelElement.setAttribute("value",joeyString("downloadFailed"));
            }  
            if(commandValue == "uploadFailed") {
              
                    var currentStatusObject = this.accessObject(nameValue);
                    currentStatusObject.progressElement.setAttribute("style","background-color:red");
                    currentStatusObject.labelElement.setAttribute("value",joeyString("uploadFailed"));
                    
            }            
            if(commandValue == "clear") {
                    var currentStatusObject = this.accessObject(nameValue);
                    currentStatusObject.labelElement.setAttribute("value","");
                    document.getElementById("joeyStatusBox").label= "";
              
            }
            if(commandValue == "delete") {
            
                    var currentStatusObject = this.accessObject(nameValue);
                    currentStatusObject.progressElement.parentNode.removeChild(currentStatusObject.progressElement);
                    this.dumpObject(nameValue);
                    this.generalUDUpdate();
                    
            }
            if(commandValue == "logginin") {
                    //var value = joeyString("loggingin"); 
                    //document.getElementById("joeyStatusTeller").value = value;
            }
        } 
        
        if(this.globalMessages.length > 0) {
            setTimeout("g_joey_statusUpdateService.renderer()",1111);
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
    referenceTitle  : "",
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
           percentage = from/to;
       } catch (i) { 
           percentage = 0;
       } 
        
        if (verb == "upload") 
        {
            this.percentage = percentage;
            this.uploadStatus = 1;  // 1 = course, 2 completed, -1 failed; 
            this.actionQueue.push(verb);
        }
        if (from==to)
        {
            // this might not be entirely true... basically,
            // at ths point we are waiting to upload...
            this.statusLogin = 1; // 1 = course, 2= completed, -1 failed; 
        }
        if(verb =="queued") {
            this.contentType=contentType;
            this.actionQueue.push(verb);        
        }
        if(verb =="download") {
            this.percentage = percentage;
            this.contentType=contentType;
           
            this.downloadStatus=1;  // 1 = course, 2 completed, -1 failed; 
            this.actionQueue.push(verb);
        } 

        /* adverb */
       
        if(adverb=="completed") 
        {
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
                    var myThis = this;
                    var omega = function myFunc() { myThis.upload() } ;
                    
                    setTimeout(omega, 500); // give enough time for us to leave the busy check
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

