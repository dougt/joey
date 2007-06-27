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
 * Michael Koch.
 * Portions created by the Initial Developer are Copyright (C) 2007
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *
 * ***** END LICENSE BLOCK ***** */

package org.mozilla.joey.j2me;

import de.enough.polish.io.RedirectHttpConnection;
import de.enough.polish.util.ArrayList;
import de.enough.polish.util.TextUtil;

import java.io.EOFException;
import java.io.InputStream;
import java.io.OutputStream;
import java.io.ByteArrayOutputStream;
import java.util.Vector;

import javax.microedition.io.HttpConnection;

public class CommunicationController
	extends Thread
{
	//#if serverUrl:defined
		//#= private String serverURL = "${serverUrl}";
	//#else
		private String serverURL = "http://joey.labs.mozilla.com";
	//#endif

	private String cookieStr;

    private ArrayList queue;

	public CommunicationController(UserData userData)
	{
        this.queue = new ArrayList();

        if (userData.isUseSsl()) {
        	this.serverURL = TextUtil.replace(this.serverURL, "http:", "https:");
        }

        System.out.println("Michael: server url: " + this.serverURL);
	}
	
    
    public synchronized NetworkRequest getNextRequest() 
    {
        try {
            while (this.queue.size() == 0) {
                wait();
            }
        }
        catch (InterruptedException ie)
        {
            //TODO what to do?
            return null;
        }

        return (NetworkRequest) this.queue.remove(0);
    }

    public synchronized void addRequest(NetworkRequest nr)
    {
    	//#debug debug
    	System.out.println("addRequest " + nr);
    	
    	this.queue.add(nr);
    	notify();
    }

    public synchronized void addNextRequest(NetworkRequest nr)
    {
    	//#debug debug
        System.out.println("addNextRequest " + nr);

        this.queue.add(0, nr);
        notify();
    }
    
    public void run()
	{
		while (true) {
            NetworkRequest nr = this.getNextRequest();
            
            if (nr == null) {
            	break; // we are done;
            }

            process(nr);
        }
    }
   
    private void process(NetworkRequest nr)
    {
        RedirectHttpConnection connection = null;
        InputStream in = null;
        
        nr.onStart();

        try {

            //#debug info
            System.out.println("creating connection to: " + this.serverURL + nr.requestURL);

            connection = new RedirectHttpConnection(this.serverURL + nr.requestURL);
            
            if (this.cookieStr != null) {
                connection.setRequestProperty("Cookie", this.cookieStr);
            }
            
            connection.setRequestMethod(HttpConnection.POST);
            connection.setRequestProperty("Content-Type", nr.contenttype);
            
            // Write body content.
            OutputStream out = connection.openOutputStream();
            out.write(nr.postdata.getBytes());
            out.close();

            in = connection.openDataInputStream();
            nr.responseCode = connection.getHeaderFieldInt("X-joey-status", -1);

            /* 

            int i = 0; 
            String header;
            do {
                header = connection.getHeaderField(i);
                if (header!=null) {
                    System.out.println("Header " + i + " " + 
                                       connection.getHeaderFieldKey(i) + " : " + header);
                }
                i++;
            } while (header!=null);
   
            */

            String str = connection.getHeaderField("Set-Cookie");

            if (str != null) {
                int pos = str.indexOf(';');
                this.cookieStr = pos != -1 ? str.substring(0, pos) : str;
            }
            
            long total = 0;
            int read = -1;
            byte[] buffer = new byte[1000];

            ByteArrayOutputStream byteout = new ByteArrayOutputStream();

            while ((read = in.read(buffer)) >= 0)
            {
                byteout.write(buffer, 0, read);
            
                total += read;
                
                nr.onProgress(total, -1);
            }
            nr.data = byteout.toByteArray();

            // System.out.println(new String (nr.data));

        }
        catch (EOFException e)
        {
            //#debug debug
            System.out.println("EOFException:  Data read.");
        }
        /*
          @todo 
          catch (UnknownHostException e)
          {
          //#debug error
          System.out.println("Joey Host not found");
          }
        */
        catch (Exception e)
        {
            e.printStackTrace();
            System.out.println(e);

            //#debug error
            System.out.println("Error requesting url " + nr.requestURL);
        }
        finally {
            try
            {
                if (in != null) {
                    in.close();
                }
				
                if (connection != null) {
                    connection.close();
                }
            }
            catch (Exception e)
            {
                //#debug error
                System.out.println("Cannot close HTTP connection correctly");
            }
        }

        nr.onStop();
    }

	public void login(UserData userData, ResponseHandler handler)
	{
		login(userData, handler, true);
	}

	public void login(UserData userData, ResponseHandler handler, boolean sendSuccessNotification)
	{
        LoginNetworkRequest nr = new LoginNetworkRequest(userData, sendSuccessNotification);
        nr.setResponseHandler(handler);

        addNextRequest(nr);
	}

	public void getIndex(Vector uploads, ResponseHandler handler, int limit, int start)
	{
        IndexNetworkRequest nr = new IndexNetworkRequest(uploads, limit, start);
        nr.setResponseHandler(handler);

        addRequest(nr);
	}

	public void add(String title, byte[] data, ResponseHandler handler)
	{
        AddNetworkRequest nr = new AddNetworkRequest(title, data);
        nr.setResponseHandler(handler);

        addRequest(nr);
	}

    public void delete(String id, ResponseHandler handler)
	{
        DeleteNetworkRequest nr = new DeleteNetworkRequest(id);
        nr.setResponseHandler(handler);

        addRequest(nr);
	}
	
    public void get(Upload upload, ResponseHandler handler)
    {
        GetNetworkRequest nr = new GetNetworkRequest(upload);
        nr.setResponseHandler(handler);

        this.addRequest(nr);
    }

    public String getRawMediaURLFor(String id)
    {
        return this.serverURL + "/files/view/" + id;
    }
}
