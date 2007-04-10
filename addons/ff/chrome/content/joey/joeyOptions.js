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
 * Ask Marcio <mgalli@mgalli.com>.
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

/* 
 * Init get from the preferences and updates the checkboxes in the XUL panel. 
 */
 
var g_joey_prefService = null;
var g_joey_optionsCheckboxes = ['joey.enable_logging', 'joey.remember_username'];

function init() {
    try {
        g_joey_prefService = Components.classes["@mozilla.org/preferences-service;1"]
                             .getService(Components.interfaces.nsIPrefBranch);
        
        for (  indexKey in g_joey_optionsCheckboxes ) {
            var element = g_joey_optionsCheckboxes[indexKey];
            document.getElementById("checkbox_"+element).checked = g_joey_prefService.getBoolPref(element);
        } 
     } catch (i) { alert(i) }
}

/* 
 * Get from the XUL UI and updadtes the preference service..
 */
function doOkay() {
    try {
        for (  indexKey in g_joey_optionsCheckboxes ) {
            var element = g_joey_optionsCheckboxes[indexKey];
            g_joey_prefService.setBoolPref(element,document.getElementById("checkbox_"+element).checked);
        } 
     
        var wm = Components.classes["@mozilla.org/appshell/window-mediator;1"]
                        .getService(Components.interfaces.nsIWindowMediator);
        win = wm.getMostRecentWindow("navigator:browser");
        win.g_joey_serverURL = document.getElementById("serviceUrl").value;

     } catch (i) { alert(i) }
}
