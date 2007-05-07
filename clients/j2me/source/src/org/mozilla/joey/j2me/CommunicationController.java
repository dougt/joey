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
import de.enough.polish.ui.ScreenInfo;
import de.enough.polish.util.Locale;
import de.enough.polish.util.ArrayList;

import java.io.EOFException;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.io.ByteArrayOutputStream;
import java.io.DataOutputStream;
import java.util.Hashtable;
import java.util.Vector;

import javax.microedition.io.HttpConnection;
import javax.microedition.lcdui.StringItem;



public class CommunicationController
	extends Thread
{
	private String serverURL = "http://joey.labs.mozilla.com";
	private String cookieStr;

    private ArrayList queue;

	public CommunicationController()
	{
        queue = new ArrayList();
	}
	
    
    public synchronized NetworkRequest getNextRequest() 
    {

        try {
            while (queue.size() == 0) {
                wait();
            }
        }
        catch (InterruptedException ie)
        {
            //TODO what to do?
            return null;
        }

        return (NetworkRequest) queue.remove(0);
    }

    public synchronized void addRequest(NetworkRequest nr)
    {
        System.out.println("addRequest " + nr);

        queue.add(nr);
        notify();
    }
    
    public void run()
	{
        NetworkRequest nr;

		while (true) {
            nr = this.getNextRequest();
            
            if (nr == null)
                return; // we are done;
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

            if (len == -1 ) {
                ByteArrayOutputStream baos = null;
                DataOutputStream dos = null;
                
                baos = new ByteArrayOutputStream();
                dos = new DataOutputStream(baos);
                
                int ch;
                while ((ch = in.read()) != -1) {
                    dos.write((byte) ch);
                }
                nr.data = baos.toByteArray();
            }
            else
            {
                nr.data = new byte[len];
                in.read(nr.data, 0, len);
            }
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

        this.addRequest(nr);
        return;
	}

	public void getIndex(Vector uploads, ResponseHandler handler)
	{
        IndexNetworkRequest nr = new IndexNetworkRequest(uploads);
        nr.setResponseHandler(handler);

        this.addRequest(nr);
        return;
	}

	public void add(Upload upload, ResponseHandler handler)
	{
        AddNetworkRequest nr = new AddNetworkRequest(upload);
        nr.setResponseHandler(handler);

        this.addRequest(nr);
        return;
	}

    public void delete(String id, ResponseHandler handler)
	{
        DeleteNetworkRequest nr = new DeleteNetworkRequest(id);
        nr.setResponseHandler(handler);

        this.addRequest(nr);
        return;
	}
	
    public void get(String id, ResponseHandler handler)
    {
        GetNetworkRequest nr = new GetNetworkRequest(id);
        nr.setResponseHandler(handler);

        this.addRequest(nr);
        return;
    }
}
