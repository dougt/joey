package org.mozilla.joey.j2me;

import de.enough.polish.io.RedirectHttpConnection;

import java.io.EOFException;
import java.io.IOException;
import java.io.InputStream;
import java.util.Hashtable;

public class CommunicationController
	extends Thread
{
	private ResponseHandler handler;
	private String requestURL;
	private Hashtable requestData;
	private Object lock;

	public CommunicationController()
	{
	}

	public void run()
	{
		// Create lock here to make sure the thread owns the lock.
		this.lock = new Object();
		
		while (true) {
			try
			{
				synchronized (this.lock)
				{
					System.out.println("before wait");
					this.lock.wait();
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

						// TODO: Write request data to outputstream

						in = connection.openInputStream();

						int ch;
						while ((ch = in.read()) != -1) {
							if (ch == '\n') {
								String line = sb.toString();
								int pos = line.indexOf('=');
								data.put(line.substring(0, pos).trim(),
								         line.substring(pos + 1).trim());
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
	
	public void requestURL(String url, Hashtable data)
	{
		synchronized (this.lock)
		{
			this.requestURL = url;
			this.requestData = data;
			this.lock.notify();
		}
	}
	
	public void notifyResponse(Hashtable response)
	{
		if (this.handler != null) {
			this.handler.notifyResponse(response);
		}
	}
}
