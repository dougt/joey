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

import java.io.EOFException;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.util.Hashtable;
import java.util.Vector;

import javax.microedition.io.HttpConnection;
import javax.microedition.lcdui.StringItem;

public class CommunicationController
	extends Thread
{
	private ResponseHandler handler;
	private String requestURL;
	private String requestData;
	private Upload uploadData;
	private Object lock;
	private Object lock2;
	
	private String serverURL = "http://joey.labs.mozilla.com";
	private int responseCode;
	private String cookieStr;
	private Hashtable data;

	public CommunicationController()
	{
		this.lock = new Object();
		this.lock2 = new Object();

		//#style waitScreen
		StringItem item = new StringItem(null, Locale.get("screeninfo.wait.msg"));
		ScreenInfo.setItem(item);
		ScreenInfo.setVisible(false);
	}
	
	public void run()
	{
		while (true) {
			try
			{
				synchronized (this.lock)
				{
					if (this.requestURL == null) {
						this.lock.wait();
					}
				}
			}
			catch (InterruptedException e)
			{
				//#debug
				System.out.println("Download thread was interrupted...stopping it");

				break;
			}
			catch (Error e)
			{
				e.printStackTrace();
			}

			if (this.requestURL != null) {
				synchronized (this.lock)
				{
					ScreenInfo.setVisible(true);
					try
					{
						Thread.sleep(5000);
					}
					catch (InterruptedException e1)
					{
						// TODO Auto-generated catch block
						e1.printStackTrace();
					}
					Hashtable data = new Hashtable();
					RedirectHttpConnection connection = null;
					InputStream in = null;
					
					try
					{
						//#debug debug
						System.out.println("requesting url " + this.requestURL);

						connection = new RedirectHttpConnection(this.requestURL);

						if (this.cookieStr != null) {
							connection.setRequestProperty("Cookie", this.cookieStr);
						}
						
						connection.setRequestMethod(HttpConnection.POST);

						if (this.uploadData == null) {
							connection.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");

							if (this.requestData != null) {
								OutputStream out = connection.openOutputStream();
								out.write(this.requestData.getBytes());
								out.close();
							}
						}
						else {
							connection.setRequestProperty("Content-Type", "multipart/form-data, boundary=111222111");

							// Create multipart body content.
							String content = this.uploadData.getData();
							StringBuffer body = new StringBuffer();
							body.append("--111222111\r\n");
							body.append("Content-disposition: form-data;name=\"rest\"\r\n\r\n1\r\n");
							body.append("--111222111\r\n");
							body.append("Content-disposition: form-data;name=\"data[Upload][title]\"\r\n\r\n");
							body.append(this.uploadData.getName());
							body.append("\r\n--111222111\r\n");
							body.append("Content-disposition: form-data;name=\"data[Upload][referrer]\"\r\n\r\n");
							body.append("http://www.heise.de/\r\n");
							body.append("--111222111\r\n");
							body.append("Content-disposition: form-data;name=\"data[File][Upload]\";filename=\"data[File][Upload]\"\r\n");
							body.append("Content-Type: text/plain\r\n");
							body.append("Content-Length: " + content.length() + "\r\n\r\n");
							body.append(content);
							body.append("\r\n--111222111--\r\n");

							// Write body content.
							OutputStream out = connection.openOutputStream();
							out.write(body.toString().getBytes());
							out.close();
						}

						in = connection.openDataInputStream();
						this.responseCode = connection.getResponseCode();
						
						String str = connection.getHeaderField("Set-Cookie");
						if (str != null) {
							int pos = str.indexOf(';');
							this.cookieStr = pos != -1 ? str.substring(0, pos) : str;
						}

						int ch;
						StringBuffer sb = new StringBuffer();
						while ((ch = in.read()) != -1) {
							if (ch == '\n') {
								String line = sb.toString();
								int pos = line.indexOf('=');
								if (pos > 0) {
									data.put(line.substring(0, pos).trim(),
									         line.substring(pos + 1).trim());
								}
								sb.setLength(0);
							}
							else {
								sb.append((char) ch);
							}
						}
						
						in.close();
						connection.close();
					}
					catch (EOFException e)
					{
						//#debug debug
						System.out.println("Data read.");
					}
					catch (IOException e)
					{
						//#debug error
						System.out.println("Error requesting url " + this.requestURL);
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

					this.data = data;
					notifyResponse(data);
					this.requestURL = null;
					ScreenInfo.setVisible(false);
				}
				synchronized (this.lock2)
				{
					this.lock2.notify();
				}
			}
		}
	}
	
	public void setResponseHandler(ResponseHandler handler)
	{
		this.handler = handler;
	}
	
	public void requestURL(String url)
	{
		requestURL(url, null);
	}
	
	public void requestURL(String url, String requestData)
	{
		synchronized (this.lock)
		{
			this.requestURL = this.serverURL + url;
			this.requestData = requestData;
			this.uploadData = null;
			this.lock.notify();
		}
	}
	
	public int requestURLSynchronous(String url, String requestData)
	{
		requestURL(url, requestData);
		
		synchronized (this.lock2)
		{
			try
			{
				this.lock2.wait();
			}
			catch (InterruptedException e)
			{
				// TODO Auto-generated catch block
				e.printStackTrace();
			}
		}
		
		return this.responseCode;
	}
	
	public int requestURLSynchronousMultipart(String url, Upload uploadData)
	{
		synchronized (this.lock)
		{
			this.requestURL = this.serverURL + url;
			this.requestData = null;
			this.uploadData = uploadData;
			this.lock.notify();
		}
		
		synchronized (this.lock2)
		{
			try
			{
				this.lock2.wait();
			}
			catch (InterruptedException e)
			{
				// TODO Auto-generated catch block
				e.printStackTrace();
			}
		}
		
		return this.responseCode;
	}
	
	public void notifyResponse(Hashtable response)
	{
		if (this.handler != null) {
			this.handler.notifyResponse(response);
		}
	}
	
	public boolean login(UserData userData)
	{
		StringBuffer sb = new StringBuffer();
		sb.append("rest=1&data[User][username]=");
		sb.append(userData.getUsername());
		sb.append("&data[User][password]=");
		sb.append(userData.getPassword());
		
		int responseCode = requestURLSynchronous("/users/login", sb.toString());
		
		return responseCode == HttpConnection.HTTP_OK;
	}
	
	public boolean getIndex(Vector uploads)
	{
		StringBuffer sb = new StringBuffer();
		sb.append("rest=1&limit=5&start=0");
		
		int responseCode = requestURLSynchronous("/uploads/index", sb.toString());

		if (responseCode == HttpConnection.HTTP_OK) {
			int count = Integer.parseInt((String) this.data.get("count"));

			for (int i = 1; i <= count; i++) {
				String id = (String) this.data.get("id." + i);
				String referrer = (String) this.data.get("referrer." + i);
				String preview = (String) this.data.get("preview." + i);
				String mimetype = (String) this.data.get("type." + i);
				String modified = (String) this.data.get("modified." + i);

				int foundIndex = -1;

				for (int j = 0; j < uploads.size(); j++) {
					Upload upload = (Upload) uploads.elementAt(j);

					if (upload.isShared() && id.equals(upload.getId())) {
						foundIndex = j;
						break;
					}
				}

				if (foundIndex != -1) {
					uploads.removeElementAt(foundIndex);
				}

				uploads.addElement(new Upload(id, mimetype, preview, modified, referrer));
			}
		}

		return responseCode == HttpConnection.HTTP_OK;
	}
	
	public boolean add()
	{
		Upload upload = new Upload(null, null, null);
		int responseCode = requestURLSynchronousMultipart("/uploads/add", upload);

		return responseCode == HttpConnection.HTTP_OK;
	}
	
	public boolean delete(String id)
	{
		String requestData = "rest=1";

		int responseCode = requestURLSynchronous("/uploads/delete/" + id, requestData);

		return responseCode == HttpConnection.HTTP_OK;
	}
	
	public boolean getById(int id)
	{
		String requestData = "rest=1";

		int responseCode = requestURLSynchronous("/files/view/" + id, requestData);

		return responseCode == HttpConnection.HTTP_OK;
	}
	
	public boolean getPreviewById(int id)
	{
		// TODO: Implement me.
		return false;
	}
	
	public boolean getContentById(int id)
	{
		// TODO: Implement me.
		return false;
	}
}
