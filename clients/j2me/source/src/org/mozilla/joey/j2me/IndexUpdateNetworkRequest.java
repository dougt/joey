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

import java.util.Hashtable;
import java.util.Vector;

import javax.microedition.io.HttpConnection;

public class IndexUpdateNetworkRequest
	extends NetworkRequest
{
	private Vector uploads;

	public IndexUpdateNetworkRequest(Vector uploads, long lastModified)
	{
		StringBuffer sb = new StringBuffer();
		sb.append("rest=1&since=");
		sb.append(lastModified);

		this.requestURL = "/uploads/index";
		this.contenttype = "application/x-www-form-urlencoded";
		this.postdata = sb.toString();

		this.uploads = uploads;
	}

    public void onStop()
    {
    	if (this.responseCode == HttpConnection.HTTP_OK) {
            Hashtable parsedData = new Hashtable();
            StringBuffer sb = new StringBuffer();

            for (int i = 0; i < this.data.length; i++) {
                char ch = (char) this.data[i];
                
                if (ch == '\n') {
                    String line = sb.toString();
                    int pos = line.indexOf('=');
                    if (pos > 0) {
                        parsedData.put(line.substring(0, pos).trim(),
                                 line.substring(pos + 1).trim());
                    }
                    sb.setLength(0);
                }
                else {
                    sb.append(ch);
                }
            }
            
            // TODO: Parse correctly but we should not duplicate code from IndexNetworkRequest here.
    	}

    	if (this.handler != null) {
    		this.handler.notifyResponse(this);
    	}
    }
}
