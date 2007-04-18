package org.mozilla.joey.j2me;

import de.enough.polish.rmi.Remote;
import de.enough.polish.rmi.RemoteException;

import java.util.Hashtable;

public interface JoeyServer
	extends Remote
{
	public int login(User user)
		throws RemoteException;
	
	public Hashtable getIndex(int id)
		throws RemoteException;
	
	public int delete(int id)
		throws RemoteException;
	
	public int add(String name, String description, byte[] data)
		throws RemoteException;

	public Hashtable view(int id)
		throws RemoteException;
	
	public Hashtable preview(int id)
		throws RemoteException;
	
	public Hashtable original(int id)
		throws RemoteException;
}
