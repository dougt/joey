/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1
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
 * The Original Code is Mozilla Joey.
 *
 * The Initial Developer of the Original Code is
 * Doug Turner.
 * Portions created by the Initial Developer are Copyright (C) 2007
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *
 * ***** END LICENSE BLOCK ***** */

package org.mozilla.joey.j2me;

import de.enough.polish.io.RedirectHttpConnection;
import de.enough.polish.ui.ScreenInfo;
import de.enough.polish.util.Locale;

import java.io.EOFException;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.util.Hashtable;
import java.util.Vector;

import javax.microedition.io.HttpConnection;
import javax.microedition.lcdui.StringItem;

public class NetworkRequest
{
    /*
     * This is the relative url to which we will send the
     * network request to
     */
	public String requestURL;

    /*
     * This is the content type of the network request which
     * we will be sending.
     */
    public String contenttype;

    /*
     * This is the actual data that is going to be sent as
     * POST data as part of the network request.
     */
    public String postdata;

    /*
     * This is the http responseCode.  This is only valid
     * after a network connection has been completed.
     */ 
	public int responseCode;

    /*
     * This is the raw data that was returned.  This is only
     * valid after a network connection has been completed.
     */ 
	public byte[] data;

	protected ResponseHandler handler;

    public void onStart() {
    }

    public void onStop() {
    }

    public void setResponseHandler(ResponseHandler handler)
	{
		this.handler = handler;
	}
    
 
}
