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
