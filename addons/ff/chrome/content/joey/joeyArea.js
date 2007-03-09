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

var orignalXPath = null;
var xpathOffset = 0;

function up()
{
	xpathOffset++;
	
	var newstring = orignalXPath;
	
	for (var i=0; i<xpathOffset; i++)
		newstring = newstring.substring(0,newstring.lastIndexOf("/"));
	
    document.getElementById("xpath").value = newstring;
	onTextChangeInXPathBox();
	search();
}

function down()
{
	xpathOffset--;
	if (xpathOffset<0) xpathOffset=0;

	
	var newstring = orignalXPath;
	
	for (var i=0; i<xpathOffset; i++)
		newstring = newstring.substring(0,newstring.lastIndexOf("/"));
	
    document.getElementById("xpath").value = newstring;
	onTextChangeInXPathBox();	
	search();
}

function joeyAreaNotify()
{

alert(lastResults[0]);

	var html = lastResults[0].innerHTML;
	
    var joey = Components.classes["@mozilla.com/joey;1"]
                            .getService(Components.interfaces.mocoJoey);

  //  joey.setListener(new joey_listener());

    joey.uploadData("Joey! Area Summary",
                       openerURI,
                       openerURI,
                       html,
                       html.length,
                       "text/plain",
                       0);
   
   if (document.getElementById("notifyChange").checked)
   {
   		NotifyIfChange()
   }

}


function NotifyIfChange()
{
	var xpath = document.getElementById("xpath").value;
	
	var generator =
"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\
<generator xmlns=\"http://www.mozilla.org/microsummaries/0.1\" name=\"Joey! Area\">\
<template><transform xmlns=\"http://www.w3.org/1999/XSL/Transform\" version=\"1.0\">\
<output method=\"text\"/><template match=\"/\"><value-of select=\"";
    
    generator += xpath + "\"/></template></transform></template></generator>";

    var joey = Components.classes["@mozilla.com/joey;1"]
                            .getService(Components.interfaces.mocoJoey);

  //  joey.setListener(new joey_listener());

    joey.uploadData("Joey! Area Microsummary",
                    "Microsummary from: " + openerURI,
                    openerURI,
                    generator,
                    generator.length,
                    "microsummary/xml",
                    0);
                       
	alert("uploaded...");
}	


// ================================================================
// Below deals with getting the xpath (borrowed from XPath Checker)
// ================================================================

var currentDocument = null
var openerURI = null
var lastResults = null;

function onLoad() {
    loadXPathForNode(opener.xpathTarget)
    
    document.getElementById("xpath").addEventListener('command',search,false);
    document.getElementById("xpath").addEventListener('input',onTextChangeInXPathBox,false);
    
}

function loadXPathForNode(node) {
    updateNamespacePrefixes(node.ownerDocument)
    orignalXPath = getXPath(node, getPrefixesByNamespace())
    document.getElementById("xpath").value = orignalXPath 
    var newUrl = node.ownerDocument.documentURI
    openerURI = newUrl;
    
    if(newUrl!=getUrl()) {
        loadIFrame(node.ownerDocument)
        // workaround because iframe.onload doesn't work
        setTimeout(loadIFrameDone, 1000)
    } else {
        search();
    }
}

function getUrl() {
    var iframe = document.getElementById("resultsFrame")
    return iframe.contentDocument.location.href
}

function loadIFrame(newDocument) {
    var url = newDocument.documentURI
    currentDocument = newDocument

    var iframe = document.getElementById("resultsFrame")
    var docShell = iframe.docShell

    docShell.allowAuth = false
    docShell.allowJavascript = false
    docShell.allowMetaRedirects = false
    docShell.allowPlugins = false
    docShell.allowSubframes = false

    document.getElementById("resultsDeck").selectedIndex=0
    iframe.contentDocument.location.replace(url)
    document.getElementById("results-caption").label = "Results from "+url
}

function loadIFrameDone() {
    search()
}

function updateNamespacePrefixes(currentDocument) {
    var namespaces = getNamespaces(currentDocument)
    document.getElementById("namespace-box").collapsed = (countProperties(namespaces)==0)

    var newrows = makePrefixRows(namespaces)

    var oldrows = document.getElementById("prefixrows")
    oldrows.parentNode.replaceChild(newrows, oldrows)
}


function makePrefixRows(namespaceUrls) {
    var newRows = document.createElement("rows")

    newRows.setAttribute("flex","1")
    newRows.setAttribute("id","prefixrows")

    var i=0;

    for (var url in namespaceUrls) {

        var label = document.createElement("label")
        label.setAttribute("value", url)
        label.setAttribute("id","ns."+i)

        var prefixBox = document.createElement("textbox")
        prefixBox.setAttribute("flex", "1")
        prefixBox.setAttribute("size", 10)
        prefixBox.setAttribute("id", "prefix."+i)
        prefixBox.setAttribute("value", namespaceUrls[url])
        prefixBox.setAttribute("type", "timed")
        prefixBox.setAttribute("timeout", "500")
        prefixBox.addEventListener('command',search,false)

        var row = document.createElement("row")
        row.setAttribute("align", "center")
        row.appendChild(label)
        row.appendChild(prefixBox)
        newRows.appendChild(row)

        i++
    }

    return newRows
}

function getPrefixesByNamespace() {
    var result = new Object
    var reversed = getNamespacesByPrefix()
    for (prefix in reversed) {
        result[reversed[prefix]] = prefix
    }
    return result
}

function getNamespacesByPrefix() {
    if ( document.getElementById("namespace-box").collapsed ) return null

    var prefixes = new Object
    var i = 0
    while (true) {
        var nsElt = document.getElementById("ns."+i)
        if (nsElt==null) break
        var ns = nsElt.value

        var prefix = document.getElementById("prefix."+i).value
        prefix = prefix.replace("^ *", "").replace(" *$", "")
        if(prefix.length>0) {
            prefixes[prefix] = ns
        }
        i++
    }

    return prefixes
}

function onTextChangeInXPathBox() {
    var xpath = document.getElementById("xpath").value
    var isValid = isValidXPath(xpath, getNamespacesByPrefix())
    document.getElementById("status").value = isValid ? "" : "Syntax error"
    
}

function search() {
    var xpath = document.getElementById("xpath").value
    var prefixes = getNamespacesByPrefix()

    if(!isValidXPath(xpath, prefixes)) {
        document.getElementById("status").value = "Syntax error"
        return
    }

    var resultList = getXPathNodes(currentDocument, xpath, prefixes)
	lastResults = resultList;
	
    updateStatus(resultList)
    
    if(resultList.length>0 && resultList[0].nodeType==Node.ELEMENT_NODE)
    {
        if(currentDocument instanceof HTMLDocument)
        {
            updateHtmlResults(resultList)
        }
        else 
        {
			alert("text");
            updateTextResults(serializeXML(resultList))
        }
    } 
    else
    {
    	alert("text");
        updateTextResults(getNodeValues(resultList))
    }
}

function updateStatus(results) {
    var status;
    if(results.length==0) {
      status = "No matches found"
    } else if(results.length==1) {
      status = "One match found"
    } else if(results.length>1) {
      status = results.length+" matches found"
    }
    document.getElementById("status").value = status
}

function getNodeValues(resultList) {
    var result = []
    for (var i in resultList) {
        result.push(resultList[i].nodeValue)
    }
    return result
}

function serializeXML(resultList) {
    var serializer = new XMLSerializer()
    var result = []
    for (var i in resultList) {
        result.push(serializer.serializeToString(resultList[i]))
    }
    return result
}

function updateTextResults(items) {
    var newRows = document.createElement("rows")
    newRows.setAttribute("flex","1")
    newRows.setAttribute("style","overflow: auto")
    newRows.setAttribute("id","resultrows")

    for (var i in items) {
        var label = document.createElement("label")
        label.setAttribute("value", (parseInt(i)+1)+":")

        var content = makeTextBox(items[i])

        var row = document.createElement("row")
        row.setAttribute("flex",1)
        row.appendChild(label)
        row.appendChild(content)
        newRows.appendChild(row)
    }

    var oldRows = document.getElementById("resultrows")
    oldRows.parentNode.replaceChild(newRows, oldRows)
    document.getElementById("resultsDeck").selectedIndex=1
}

function makeTextBox(content) {
    var textbox = document.createElement("textbox")
    textbox.setAttribute("flex",1)
    textbox.setAttribute("value",content)
    textbox.setAttribute("readonly",true)
    textbox.setAttribute("multiline",true)
    textbox.setAttribute("minheight","20")
    return textbox
}

function updateHtmlResults(results) {
    var doc = document.getElementById("resultsFrame").contentDocument
    doc.body.innerHTML = "<table><tbody></tbody></table>"

    for (var i in results) {
        var node = results[i]
        var label = (parseInt(i)+1)+":"

        var row = doc.createElement("tr")
        row.appendChild(doc.createElement("td"))
        row.appendChild(doc.createElement("td"))
        row.firstChild.innerHTML = label
        row.lastChild.appendChild(node.cloneNode(true))

        doc.body.lastChild.lastChild.appendChild(row)
    }
    
    document.getElementById("resultsDeck").selectedIndex=2
}

// ================================================================

function isValidXPath(xpath, prefixes) {
    var evaluator = new XPathEvaluator()
    try {
        evaluator.createExpression(xpath, makeResolver(prefixes))
    } catch(e) {
        return false;
    }

    if(xpath=='/' || xpath=='.') return false;

    return true;
}

function makeResolver(prefixes) {
    if(prefixes==null) return null

    function namespaceResolver(prefix) {
      return prefixes[prefix]
    }

    return namespaceResolver
}

function getXPathNodes(document, xpath, prefixes) {

    var resolver = makeResolver(prefixes)

    // hack for xpaths using namespaces not working; mysteriously works if we evaluate this xpath first
    if(resolver!=null) {
        document.evaluate("//*", document, null, XPathResult.ANY_TYPE, null)
    }

    var xpathResult = document.evaluate(xpath, document, makeResolver(prefixes), XPathResult.ANY_TYPE, null);
    var result = [];
    var item = xpathResult.iterateNext();
    while (item != null) {
        result.push(item);
        item = xpathResult.iterateNext();
    }
    return result;
}


function getXPath(targetNode, prefixesByNamespace) {
    var useLowerCase = (targetNode.ownerDocument instanceof HTMLDocument)
    var nodePath = getNodePath(targetNode)
    var nodeNames = []
    var start = "/"
    for (var i in nodePath) {
        var nodeIndex
        var node = nodePath[i]
        if (node.nodeType == 1) { // && node.tagName != "TBODY") {
            if (i == 0 && node.hasAttribute("id")) {
                nodeNames.push("id('" + node.getAttribute("id") + "')");
                start = ""
            } else {
                if(node.namespaceURI!=null) {
                    var namespace = node.namespaceURI
                    var prefix = prefixesByNamespace[node.namespaceURI]
                    var tagName = prefix+":"+node.localName
                } else if (useLowerCase) {
                    var tagName = node.tagName.toLowerCase()
                } else {
                    var tagName = node.tagName
                }
                nodeIndex = getNodeIndex(node)
                if (nodeIndex != null) {
                    nodeNames.push(tagName + "[" + nodeIndex + "]");
                } else {
                    nodeNames.push(tagName);
                }
            }
        } else if (node.nodeType == 3) {
            nodeIndex = getTextNodeIndex(node)
            if (nodeIndex != null) {
                nodeNames.push("text()[" + nodeIndex + "]");
            } else {
                nodeNames.push("text()");
            }
        }
    }
    return start + nodeNames.join("/")

}

function getNodeIndex(node) {
    if (node.nodeType != 1 || node.parentNode == null) return null
    var list = getChildNodesWithTagName(node.parentNode, node.tagName)
    if (list.length == 1 && list[0] == node) return null
    for (var i = 0; i < list.length; i++) {
        if (list[i] == node) return i + 1
    }
    throw "couldn't find node in parent's list: " + node.tagName
}

function getTextNodeIndex(node) {
    var list = getChildTextNodes(node.parentNode)
    if (list.length == 1 && list[0] == node) return null
    for (var i = 0; i < list.length; i++) {
        if (list[i] == node) return i + 1
    }
    throw "couldn't find node in parent's list: " + node.tagName
}

function getChildNodesWithTagName(parent, tagName) {
    var result = []
    var child = parent.firstChild
    while (child != null) {
        if (child.tagName && child.tagName == tagName) {
            result.push(child)
        }
        child = child.nextSibling
    }
    return result
}

function getChildTextNodes(parent) {
    var result = []
    var child = parent.firstChild
    while (child != null) {
        if (child.nodeType==3) {
            result.push(child)
        }
        child = child.nextSibling
    }
    return result
}

function getNodePath(node) {
    var result = []
    while (node.nodeType == 1 || node.nodeType == 3) {
        result.unshift(node)
        if (node.nodeType == 1 && node.hasAttribute("id")) return result
        node = node.parentNode
    }
    return result
}

function getNamespaces(node) {
    var namespaces = new Object;
    var prefixes = new Object;

    addNamespaces(node, namespaces, prefixes)

    return namespaces
}

function addNamespaces(node, namespaces, prefixes) {
    if (node.namespaceURI!=null) {
        if(namespaces[node.namespaceURI]==null) {
            var prefix = choosePrefix(node, prefixes)
            namespaces[node.namespaceURI] = prefix
            prefixes[prefix] = 1
        }
    }

    var child = node.firstChild
    while (child!=null) {
        addNamespaces(child, namespaces, prefixes)
        child = child.nextSibling
    }
}

function choosePrefix(node, prefixes) {
    if (node.prefix!=null && prefixes[node.prefix]==null) return node.prefix

    var lastPart = node.namespaceURI.replace(/.*\//,"")
    if(lastPart.length==0) {
        var choice = "a"
    } else {
        var choice = lastPart.charAt(0).toLowerCase()
    }

    if(prefixes[choice]==null) return choice

    var suffix = 1
    while(prefixes[choice+suffix]!=null) {
        suffix++
    }
    return choice+suffix
}

function countProperties(obj) {
    var result = 0
    for (p in obj) {
        result++
    }
    return result
}





