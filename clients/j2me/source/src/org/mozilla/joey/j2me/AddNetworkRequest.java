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

import org.bouncycastle.util.encoders.Base64;

public class AddNetworkRequest
    extends NetworkRequest
{
    public AddNetworkRequest(String title, byte[] data)
    {
        String content = new String(data);

        StringBuffer sb = new StringBuffer();

        sb.append("--111222111\r\n");
        sb.append("Content-disposition: form-data;name=\"rest\"\r\n\r\n1\r\n");
        sb.append("--111222111\r\n");
        sb.append("Content-disposition: form-data;name=\"data[Upload][title]\"\r\n\r\n");
        sb.append(title);
        sb.append("\r\n--111222111\r\n");
        sb.append("Content-disposition: form-data;name=\"data[Upload][referrer]\"\r\n\r\n");
        sb.append("http://www.mozilla.org/\r\n");
        sb.append("--111222111\r\n");
        sb.append("Content-disposition: form-data;name=\"data[File][Upload]\";filename=\"data[File][Upload]\"\r\n");
        sb.append("Content-Type: text/plain\r\n");
        sb.append("Content-Length: " + content.length() + "\r\n\r\n");
        sb.append(content);
        sb.append("\r\n--111222111--\r\n");
        
        this.requestURL = "/uploads/add";
        this.contenttype = "multipart/form-data, boundary=111222111";
        this.postdata = sb.toString();
    }
}
