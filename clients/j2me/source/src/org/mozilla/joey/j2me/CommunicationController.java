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

import java.io.EOFException;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.io.ByteArrayOutputStream;
import java.io.DataOutputStream;
import java.util.Vector;

import javax.microedition.io.HttpConnection;

public class CommunicationController
	extends Thread
{
    private int    progressBeforeNotification = 4096;
	private String serverURL = "http://joey.labs.mozilla.com";
	private String cookieStr;

    private ArrayList queue;

	public CommunicationController()
	{
        this.queue = new ArrayList();
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
            nr.responseCode = connection.getResponseCode();
            
            String str = connection.getHeaderField("Set-Cookie");
            if (str != null) {
                int pos = str.indexOf(';');
                this.cookieStr = pos != -1 ? str.substring(0, pos) : str;
            }
            
            // read everything in.

            int len = (int)connection.getLength();

            System.out.println("getLength: " + len);

            ByteArrayOutputStream baos = null;
            DataOutputStream dos = null;
            
            baos = new ByteArrayOutputStream();
            dos = new DataOutputStream(baos);
            
            int counter = this.progressBeforeNotification;
            long total = 0;
            int ch;

            while ((ch = in.read()) != -1) {
                dos.write((byte) ch);
                total++;
                if (--counter == 0)
                {
                    nr.onProgress(total, -1);
                    counter = this.progressBeforeNotification;
                }
            }

            nr.data = baos.toByteArray();
        }
        catch (EOFException e)
        {
            //#debug debug
            System.out.println("Data read.");
        }
        catch (IOException e)
        {
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
            catch (IOException e)
            {
                //#debug error
                System.out.println("Cannot close HTTP connection correctly");
            }
        }

        nr.onStop();
    }

	public void login(UserData userData, ResponseHandler handler)
	{
        LoginNetworkRequest nr = new LoginNetworkRequest(userData);
        nr.setResponseHandler(handler);

        addNextRequest(nr);
	}

	public void getIndex(Vector uploads, ResponseHandler handler)
	{
        IndexNetworkRequest nr = new IndexNetworkRequest(uploads);
        nr.setResponseHandler(handler);

        addRequest(nr);
	}

	public void add(Upload upload, ResponseHandler handler)
	{
        AddNetworkRequest nr = new AddNetworkRequest(upload);
        nr.setResponseHandler(handler);

        addRequest(nr);
	}

    public void delete(String id, ResponseHandler handler)
	{
        DeleteNetworkRequest nr = new DeleteNetworkRequest(id);
        nr.setResponseHandler(handler);

        addRequest(nr);
	}
	
    public void get(String id, ResponseHandler handler)
    {
        GetNetworkRequest nr = new GetNetworkRequest(id);
        nr.setResponseHandler(handler);

        this.addRequest(nr);
    }
}
