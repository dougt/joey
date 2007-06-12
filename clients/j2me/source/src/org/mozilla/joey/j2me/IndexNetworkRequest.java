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

import java.util.Vector;
import java.util.Hashtable;

import javax.microedition.io.HttpConnection;
import org.bouncycastle.util.encoders.Base64;

public class IndexNetworkRequest
    extends NetworkRequest
{
    private Vector uploads;
    private int count;
    private int totalCount;

    public IndexNetworkRequest(Vector uploads, int limit, int start)
    {
		StringBuffer sb = new StringBuffer();
		sb.append("rest=1&limit=");
		sb.append(limit);
		sb.append("&start=");
		sb.append(start);

        this.requestURL = "/uploads/index";
        this.contenttype = "application/x-www-form-urlencoded";
        this.postdata = sb.toString();

        this.uploads = uploads;
    }

    public void onStop() {

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


			this.count = Integer.parseInt((String) parsedData.get("count"));
			this.totalCount = Integer.parseInt((String) parsedData.get("total_count"));
            
            // TODO: make this so much smarter.

			for (int i = 1; i <= this.count; i++) {
				String id = (String) parsedData.get("id." + i);
				String referrer = (String) parsedData.get("referrer." + i);
				String preview = (String) parsedData.get("preview." + i);
				String mimetype = (String) parsedData.get("type." + i);
				String modified = (String) parsedData.get("modified." + i);

                String deleted = (String) parsedData.get("deleted." + i);
                
                if (deleted != null && deleted.equals("1"))
                {
                    System.out.println("found deleted element (this is okay): " + id);

                    // XXX
                    this.uploads.addElement(new Upload(id, true));
                    //ignore for now.
                    continue;
                }

				int foundIndex = -1;
                
				for (int j = 0; j < this.uploads.size(); j++) {
					Upload upload = (Upload) this.uploads.elementAt(j);
                    
					if (upload.isShared() && id.equals(upload.getId())) {
						foundIndex = j;
						break;
					}
				}
                
				if (foundIndex != -1) {
					this.uploads.removeElementAt(foundIndex);
				}
                
                // previews are optional.
                byte[] previewBytes = null;
                try {
                    previewBytes = Base64.decode(preview);
                } catch (Exception ex) {}

				this.uploads.addElement(new Upload(id, mimetype, previewBytes, modified, referrer));
			}
            
        }
        if (this.handler != null)
            this.handler.notifyResponse(this);
    }

	public int getCount()
	{
		return this.count;
	}

	public int getTotalCount()
	{
		return this.totalCount;
	}
}
