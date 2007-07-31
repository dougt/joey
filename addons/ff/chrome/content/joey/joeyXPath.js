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






/**
 * Determine whether a node's text content is entirely whitespace.
 *
 * @param nod  A node implementing the |CharacterData| interface (i.e.,
 *             a |Text|, |Comment|, or |CDATASection| node
 * @return     True if all of the text content of |nod| is whitespace,
 *             otherwise false.
 */
function is_all_ws( nod )
{
  // Use ECMA-262 Edition 3 String and RegExp features
  return !(/[^\t\n\r ]/.test(nod.data));
}


/**
 * Determine if a node should be ignored by the iterator functions.
 *
 * @param nod  An object implementing the DOM1 |Node| interface.
 * @return     true if the node is:
 *                1) A |Text| node that is all whitespace
 *                2) A |Comment| node
 *             and otherwise false.
 */

function is_ignorable( nod )
{
  return ( nod.nodeType == 8) || // A comment node
         ( (nod.nodeType == 3) && is_all_ws(nod) ); // a text node, all ws
}

/**
 * Version of |previousSibling| that skips nodes that are entirely
 * whitespace or comments.  (Normally |previousSibling| is a property
 * of all DOM nodes that gives the sibling node, the node that is
 * a child of the same parent, that occurs immediately before the
 * reference node.)
 *
 * @param sib  The reference node.
 * @return     Either:
 *               1) The closest previous sibling to |sib| that is not
 *                  ignorable according to |is_ignorable|, or
 *               2) null if no such node exists.
 */
function node_before( sib )
{
  while ((sib = sib.previousSibling)) {
    if (!is_ignorable(sib)) return sib;
  }
  return null;
}

function joey_buildXPath(targetElement)
{
    if (targetElement == null)
        return null;

    var buffer = "";
    var cur = targetElement;

    do {

        var name = "";
        var sep = "/";
        var occur = 0;
        var ignore = false;
        var type = targetElement.nodeType; 

        //        alert(buffer);

        if (type == Node.DOCUMENT_NODE)
        {
            buffer = "/" + buffer;
            break;
        }
        else if (type == Node.ATTRIBUTE_NODE)
        {
            sep = "@";
            name = cur.nodeName;
            next = cur.parentNode;
        }
        else
        {
            if (type == Node.ELEMENT_NODE) {
                if (cur.nodeName.toLowerCase() == "a"      || cur.nodeName.toLowerCase() == "img" ||
                    cur.nodeName.toLowerCase() == "ul"     || cur.nodeName.toLowerCase() == "document" ||
                    cur.nodeName.toLowerCase() == "document" ||
                    cur.nodeName.toLowerCase() == "font"   || cur.nodeName.toLowerCase() == "#document" )
                    ignore = true;

                var id = null;

                try {// why would this throw?
                    id = cur.getAttribute('id');
                } catch (e) {}

                if (id != null) {
                    
                    if (buffer == "")
                    {
                        buffer = "id('"+id+"')";
                        return buffer;
                    }

                    buffer = "id('" + id + "')" + buffer;
                    return buffer;
                }
            }

            name = cur.nodeName.toLowerCase();
            next = cur.parentNode;

            // now figure out the index
            var tmp = node_before(cur);
            while (tmp != null) {
                if (name == tmp.nodeName.toLowerCase()) {
                    occur++;
                }
                tmp = node_before(tmp);
            }
            occur++;
            
            if (type != Node.ELEMENT_NODE) {

                // fix the names for those nodes where xpath query and dom node name don't match
                if (type == Node.COMMENT_NODE) {
                    ignore = true;
                    name = 'comment()';
                }
                else if (type == Node.PI_NODE) {
                    ignore = true;
                    name = 'processing-instruction()';
                }
                else if (type == Node.TEXT_NODE) {
                    ignore = true;
                    name = 'text()';
                }
                // anything left here has not been coded yet (cdata is broken)
                else {
                    name = '';
                    sep = '';
                    occur = 0;
                }
            }
        }

        if (cur.nodeName.toLowerCase() == "html" ||
            cur.nodeName.toLowerCase() == "body" )
            occur = 0;

        if (ignore == true) {
        }
        else if (occur == 0) {
            buffer = sep + name + buffer;
        }
        else {
            buffer = sep + name + '[' + occur + ']' + buffer;
        }
        ignore = false;
        
        cur = next;
        
    } while (cur != null);

    return buffer;
}
