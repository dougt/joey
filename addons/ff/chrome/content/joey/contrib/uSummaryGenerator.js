/* ***** BEGIN LICENSE BLOCK *****
 *   Version: MPL 1.1/GPL 2.0/LGPL 2.1
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
 * The Original Code is Microsummary Generator Builder.
 *
 * The Initial Developer of the Original Code is
 * Johannes la Poutré.
 * Portions created by the Initial Developer are Copyright (C) 2006
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * Marcio Galli <mgalli@mgalli.com>
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


/* Keeping this for now 
 * as a singleton service like object. I did a search and replace too all the function names...
 */

var uSummaryGen_pageDoc = null;
var uSummaryGen_selectedNode = null;
var uSummaryGen_returnObject = { title:null, xpinclude:null, generatorText:null };

function uSummaryGen_xPathInit(fromDoc,fromNode) {

    uSummaryGen_pageDoc = fromDoc;
    uSummaryGen_selectedNode = fromNode;
    
    // A Quiet Variation by Marcio, stores values in the above uSummaryGen_returnObject...
    
	uSummaryGen_setXpdetailsQuiet(uSummaryGen_selectedNode);
	uSummaryGen_XPathBuilder.buildTemplate();
	
	uSummaryGen_returnObject.generatorText = uSummaryGen_XPathBuilder.generatorDoc;
	
	return uSummaryGen_returnObject;
}

/* 
 * Need to review this function to expose in this API ( JS uSummaryGenerator API ) or not 
 */
 
function uSummaryGen_dbg(aMessage) {
  var consoleService = Components.classes["@mozilla.org/consoleservice;1"]
              .getService(Components.interfaces.nsIConsoleService);
  consoleService.logStringMessage("uSummaryDialog: " + aMessage);
}

/* 
 * Uses uSummaryGen_XPathComp
 * Used by setXpdetails...
 */
function getXPathComponent(elt) {
	var type = elt.nodeName.toLowerCase();
	var xpc = new uSummaryGen_XPathComp(type, elt.id);
	for (;;) {
		elt = elt.previousSibling;
		if (! elt) break;
		if (type == elt.nodeName.toLowerCase()) xpc.incrementPos();
	}
	return xpc;
}

function uSummaryGen_setXpdetailsQuiet(elt) {

	uSummaryGen_XPathBuilder.add(getXPathComponent(elt));
						
	for(;;) {
		elt = elt.parentNode;
		if (! elt) break;
		// fill in root part, avoids GM clutter
		if ("body" == elt.nodeName.toLowerCase()) {
			uSummaryGen_XPathBuilder.add(new this.uSummaryGen_XPathComp("body"));
			uSummaryGen_XPathBuilder.add(new this.uSummaryGen_XPathComp("html"));
			break;
		}
		try {
			uSummaryGen_XPathBuilder.add(this.getXPathComponent(elt));
		} catch (e) {
			uSummaryGen_dbg(e.toString());
		}
	}
	
	var title = uSummaryGen_pageDoc.getElementsByTagName('title');
	if (title.length) {
		title = title[0].textContent;
	} else {
		// title element missing? Use host name.
		title = uSummaryGen_pageDoc.location.hostname;
	}

	uSummaryGen_returnObject.title = title;
	
	var include = "^" + uSummaryGen_XPathBuilder.toRegexString(uSummaryGen_pageDoc.location.href);

	uSummaryGen_returnObject.xpinclude = include;
	
}

/* 
 * Core class of his system 
 */ 
 
// -------------------- XPath Component Class ----------------
uSummaryGen_XPathComp = function (path, id, pos) {
	this.path = path;      // XPath component
	this.id = id || null;  // ID attribute
	this.pos = pos || 1;   // position amongst siblings of same type
	this.useId = false;
	this.incrementPos = function() {
		this.pos++;
	};
	this.toString = function() {
		var str;
		if (this.id && this.useId) {
			str = "//" + this.path + "[@id='" + this.id + "']";
		} else if (this.pos > 1) {
			str = "/" + this.path + "[" + this.pos + "]";
		} else {
			str = "/" + this.path;
		}
		return str;
	};
	this.toDOMObj = function() {
		return this._select();
	};
	this._select = function() {
		var cnt;
		if (! this.id) {
			// HTML span element
			cnt = document.createElementNS(
					"http://www.w3.org/1999/xhtml","html:span"
				);
			cnt.appendChild(document.createTextNode(this.toString()));
			return cnt;
		}

		// choice between id or path component
		// XUL elements
		cnt = document.createElement("menulist");
		var _this = this;
		cnt.addEventListener('ValueChange', function(evt) {
				_this.useId = (evt.target.selectedIndex == 1);
			}, true);
		var sel, opt0, opt1;
		sel = document.createElement("menupopup");
		this.useId = false;
		opt0 = document.createElement("menuitem");
		opt0.setAttribute("label", this.toString());
		sel.appendChild(opt0);
		if (this.id) {
			this.useId = true;
			opt1 = document.createElement("menuitem");
			opt1.setAttribute("label", this.toString());
			opt1.setAttribute("selected", "true");
			sel.appendChild(opt1);
		}
		cnt.appendChild(sel);
		return cnt;
	};
}; // Class uSummaryGen_XPathComp

var uSummaryGen_XPathBuilder = {
	aComps: [],
	xpath: "",
	generatorDoc: null,
	domParser: Components.classes["@mozilla.org/xmlextras/domparser;1"].
			createInstance(Components.interfaces.nsIDOMParser),

	add: function(component) {
		this.aComps.push(component);
	},
	toString: function() {
		var res = [];
		for (var i = 0; i< this.aComps.length; i++) {
			var xpc = this.aComps[i];
			res.push(xpc.toString());
			// use @id attribue as anchor point
			if (xpc.useId) break;
		}
		return res.reverse().join("");
	},
	attachToDom: function(attachNode) {
		for (var i = this.aComps.length - 1; i >=0; --i) {
			attachNode.appendChild(this.aComps[i].toDOMObj());
		}
	},
	getXpath: function() {
		var xpath = this.toString();
        //		return "normalize-space(string(" + xpath + "))";
        return xpath;
	},
	testXPath: function(mode) {
		var res;
		var btnAccept = document.getElementById('usDlg').getButton('accept');
		if ('edit' == mode) {
			try {
				var xmlDom = this.domParser.parseFromString(
						document.getElementById('xpeditbox').value, "text/xml"
					);
				// Ack! domParser throws no exception but returns error doc instead
				if (xmlDom.documentElement.tagName == 'parsererror') {
					res = xmlDom.documentElement.firstChild.nodeValue;
					res = res.substring(0, res.indexOf("\n"));
					var n = document.getElementById("xpresult");
					n.firstChild.nodeValue = res;
					n.style.color = "red";
					btnAccept.disabled = true;				
					return false;
				}
				var xslt = xmlDom.getElementsByTagName('transform')[0];
				var proc = new XSLTProcessor();
				proc.importStylesheet(xslt);
				res = proc.transformToDocument(uSummaryGen_pageDoc).documentElement.firstChild.nodeValue;
			} catch (e) {
				var n = document.getElementById("xpresult");
				n.firstChild.nodeValue = "XSLT/XPath format error";
				n.style.color = "red";				
				btnAccept.disabled = true;				
				return false;
			}
		} else {
			xpath = this.getXpath();
			try {
				res = document.evaluate(
						xpath, 
						uSummaryGen_pageDoc, 
						null, 
						XPathResult.ANY_TYPE, 
						null
					).stringValue;
			} catch (e) {
				res = "Error: " + e.toString();
				btnAccept.disabled = true;				
				return false;
			}
		}
		var n = document.getElementById("xpresult");
		n.firstChild.nodeValue = res;
		n.style.color = "black";
		btnAccept.disabled = false;				
		return true;				
	},

	toXMLString: function(str) {
		return str.replace(/&/g, "&amp;").replace(/>/g, "&gt;").replace(/</g, "&lt;").replace(/\'/g, "&apos;").replace(/\"/g, "&quot;");
	},
	
	toRegexString: function(str) {
		return str.replace(/(\.|\?|\$|\^)/g, '\\$1');
	},

	buildTemplate: function() {
		var xpath = this.getXpath();
		
		// modified by marcio
		var title = uSummaryGen_returnObject.title;
		
		var uuidGenerator =  Components.classes["@mozilla.org/uuid-generator;1"]
			.getService(Components.interfaces.nsIUUIDGenerator);
		var uuid = uuidGenerator.generateUUID();
		var uuidString = uuid.toString();
		var str = '<?xml version="1.0" encoding="UTF-8"?>\n'
		+ '<generator xmlns="http://www.mozilla.org/microsummaries/0.1" name="Microsummary for '
		+ this.toXMLString(title) + '" '
		+ 'uri="urn:' + uuidString + '">\n'
		+ ' <pages>\n'
		+ '   <include>' 
		+ this.toXMLString(uSummaryGen_returnObject.xpinclude)
		+ '</include>\n';
		
		// modified by marcio, removed this xpexclude that was a value originated from the UI.
        /*
		if (document.getElementById("xpexclude").value.length) {
			str += '   <exclude>'
			 + this.toXMLString(document.getElementById("xpexclude").value)
			 + '</exclude>\n';
		}*/
		
		str += ' </pages>\n'
		+ ' <template>\n'
		+ '   <transform xmlns="http://www.w3.org/1999/XSL/Transform" version="1.0">\n'
		+ '     <output method="text"/>\n'
		+ '     <template match="/">\n'
		+ '       <value-of select="' + xpath + '"/>\n'
		+ '     </template>\n'
		+ '   </transform>\n'
		+ ' </template>\n'
		+ '</generator>\n';

		this.generatorDoc = str;

	},
	install: function() {
		// parse XML to DOM object
		xmlDom = this.domParser.parseFromString(this.generatorDoc, "text/xml");
		// install through microsummaryService
		var microsummaryService = 
			Components.classes["@mozilla.org/microsummary/service;1"].
			getService(Components.interfaces.nsIMicrosummaryService);
	
		var generator = microsummaryService.installGenerator(xmlDom);
		
	}

}; // uSummaryGen_XPathBuilder
