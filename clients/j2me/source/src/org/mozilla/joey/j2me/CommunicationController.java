package org.mozilla.joey.j2me;

import de.enough.polish.io.RedirectHttpConnection;

import java.io.EOFException;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.util.Hashtable;

import javax.microedition.io.HttpConnection;

public class CommunicationController
	extends Thread
{
	private ResponseHandler handler;
	private String requestURL;
	private String requestData;
	private Object lock;
	private Object lock2;
	
	private String serverURL = "http://joey.labs.mozilla.com";
	private String username = "mkoch2";
	private String password = "mkoch";
	private int responseCode;
	private String cookieStr;

	public CommunicationController()
	{
		this.lock = new Object();
		this.lock2 = new Object();
	}
	
	public void run()
	{
		while (true) {
			try
			{
				synchronized (this.lock)
				{
					System.out.println("before wait");
					if (this.requestURL == null) {
						this.lock.wait();
					}
					System.out.println("after wait");
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
					Hashtable data = new Hashtable();
					StringBuffer sb = new StringBuffer();
					RedirectHttpConnection connection = null;
					InputStream in = null;
					
					try
					{
						System.out.println("requesting url " + this.requestURL);
						connection = new RedirectHttpConnection(this.requestURL);

						if (this.cookieStr != null) {
							connection.setRequestProperty("Cookie", this.cookieStr);
						}
						
						connection.setRequestMethod(HttpConnection.POST);
						connection.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");

						if (this.requestData != null) {
							OutputStream out = connection.openOutputStream();
							out.write(this.requestData.getBytes());
							out.close();
						}

						in = connection.openDataInputStream();
						this.responseCode = connection.getResponseCode();
						System.out.println("Michael: responseCode: " + this.responseCode);
						
						String str = connection.getHeaderField("Set-Cookie");
						if (str != null) {
							int pos = str.indexOf(';');
							this.cookieStr = pos != -1 ? str.substring(0, pos) : str;
						}

						int ch;
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
						//debug
						System.out.println("Data read.");
					}
					catch (IOException e)
					{
						// TODO Auto-generated catch block
						e.printStackTrace();
						
						//debug error
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

					System.out.println("notify response");
					notifyResponse(data);
					this.requestURL = null;
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
	
	public void notifyResponse(Hashtable response)
	{
		if (this.handler != null) {
			this.handler.notifyResponse(response);
		}
	}
	
	public boolean login()
	{
		StringBuffer sb = new StringBuffer();
		sb.append("rest=1&data[User][username]=");
		sb.append(this.username);
		sb.append("data[User][password]=");
		sb.append(this.password);
		
		int responseCode = requestURLSynchronous("/users/login", sb.toString());
		
		System.out.println("HTTP code: " + responseCode);
		return responseCode == HttpConnection.HTTP_OK;
	}
	
	public boolean getIndex()
	{
		StringBuffer sb = new StringBuffer();
		sb.append("rest=1&limit=5&start=0");
		
		int responseCode = requestURLSynchronous("/uploawgds/index", sb.toString());

		System.out.println("HTTP code: " + responseCode);
		return responseCode == HttpConnection.HTTP_OK;
	}
	
	public void add()
	{
	}
	
	public boolean delete(int id)
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
