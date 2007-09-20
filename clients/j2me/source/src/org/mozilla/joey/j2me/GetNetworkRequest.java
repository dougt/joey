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

public class GetNetworkRequest
    extends NetworkRequest
{
    
    public Upload upload;

    public GetNetworkRequest(Upload upload)
    {
        this.upload = upload;

        this.requestURL = "/files/view/" + upload.getId();
        this.contenttype = "application/x-www-form-urlencoded";
        this.postdata = "rest=1";
    }

    public void onStop() {

		if (this.responseCode == 200) {
            // Forward the data from the network request to the
            // Uploads object.
            this.upload.setData(this.data);
        }

        if (this.handler != null) {
            this.handler.notifyResponse(this);
        }
    }

}
