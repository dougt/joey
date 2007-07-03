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

import org.bouncycastle.util.encoders.Base64;

public class IndexUpdateNetworkRequest
	extends NetworkRequest
{
	private Vector uploads;
	private int count;

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

			this.count = Integer.parseInt((String) parsedData.get("count"));

			//#debug info
			System.out.println("number of updated elements: " + this.count);

			for (int i = 1; i <= this.count; i++) {
				int foundIndex = -1;
				String id = (String) parsedData.get("id." + i);

				for (int j = 0; j < this.uploads.size(); j++) {
					Upload upload = (Upload) this.uploads.elementAt(j);

					if (id.equals(upload.getId())) {
						foundIndex = j;
						break;
					}
				}

				if (foundIndex != -1) {
					String deleted = (String) parsedData.get("deleted." + i);

					if (deleted != null && deleted.equals("1")) {
						//#debug info
						System.out.println("found deleted element (this is okay): " + id);

						// Delete upload.
						this.uploads.removeElementAt(foundIndex);

						// Continue with next upload.
						continue;
					}
				}

				String referrer = (String) parsedData.get("referrer." + i);
				String preview = (String) parsedData.get("preview." + i);
				String mimetype = (String) parsedData.get("type." + i);
				String modified = (String) parsedData.get("modified." + i);
				String title = (String) parsedData.get("title." + i);

				// Previews are optional.
				byte[] previewBytes = null;

				try {
					previewBytes = Base64.decode(preview);
				} 
				catch (Exception ex) {
					System.out.println("Base64 decode failed " + ex);
				}

				//#debug info
				System.out.println("Updating upload: " + id + " " + mimetype  + " " + title);

				Upload upload = new Upload(id, mimetype, title, previewBytes, null, modified, referrer);
				
				if (foundIndex == -1) {
					//#debug info
					System.out.println("added new element: " + id);
					
					this.uploads.addElement(upload);
				}
				else {
					//#debug info
					System.out.println("replace existing element: " + id);
					
					this.uploads.removeElementAt(foundIndex);
					this.uploads.insertElementAt(upload, foundIndex);
				}
			}	
    	}

    	if (this.handler != null) {
    		this.handler.notifyResponse(this);
    	}
    }
}
