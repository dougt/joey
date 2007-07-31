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
    

    init: function(browser, callback) {
        this.browser=browser;
        this.callback = callback;
    },

    enable: function () {
        if (this.enabled == true)
            this.disable();
        
        this.enabled = true;
        
        this.associatedDocument = this.browser.selectedBrowser.contentDocument;

        this.createBox();
        
        var thisInstance = this;
        
        sigmaCall = function(e) { thisInstance.mouseMoveListener(e) };
        
    	this.browser.selectedBrowser
    	               .contentDocument
    	               .addEventListener("mousemove"
    	                                 ,sigmaCall
    	                                 ,false);

        omegaCall = function(e) { thisInstance.mouseClickListener(e) };
        
    	this.browser.selectedBrowser.contentDocument.addEventListener("mousedown"
                                                                      ,omegaCall 
                                                                      ,false);
    

        deltaCall = function(e) { thisInstance.keyDownListener(e) };
        
    	this.browser.selectedBrowser.contentDocument.addEventListener("keydown"
                                                                      ,deltaCall 
                                                                      ,false);

	    this.runtimer();    // timer-based refresh function..
    },
    
    disable: function () {

        if (this.enabled == false)
            return;
    
        clearTimeout(this.timer);
        this.timer = null;
        
        if (sigmaCall !=null)
            this.browser.selectedBrowser.contentDocument.removeEventListener("mousemove"
                                                                             ,sigmaCall
                                                                             ,false);        
        if (omegaCall != null)
            this.browser.selectedBrowser.contentDocument.removeEventListener("mousedown"
                                                                             ,omegaCall 
                                                                             ,false);

        if (deltaCall != null)
            this.browser.selectedBrowser.contentDocument.removeEventListener("keydown"
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
                
                this.callback(this.currentEvent.target);
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
        } catch (ignore) {}
    }, 
    
    removeBox: function () {

        try {
            
            if(this.currentElementTop.parentNode) {
                this.currentElementTop.parentNode.removeChild(this.currentElementTop);
        	}        	 
        } catch (i) {} 
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
