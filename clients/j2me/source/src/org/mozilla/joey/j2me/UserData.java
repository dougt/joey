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

import de.enough.polish.io.Externalizable;

import java.io.DataInputStream;
import java.io.DataOutputStream;
import java.io.IOException;

public class UserData
	implements Externalizable
{
	private static final int SERIALIZATION_VERSION = 3;

	private String username;
	private String password;
	private boolean useSsl;
	private long updateInterval;
	private long lastUpdate;
    private boolean rememberMe;

	public UserData()
	{
	}

	public UserData(String username, String password, boolean useSsl, long updateInterval)
	{
		this.username = username;
		this.password = password;
		this.useSsl = useSsl;
		this.updateInterval = updateInterval;
	}

	public String getPassword()
	{
		return this.password;
	}

	public void setPassword(String password)
	{
		this.password = password;
	}

	public String getUsername()
	{
		return this.username;
	}

	public void setUsername(String username)
	{
		this.username = username;
	}

	public boolean isUseSsl()
	{
		return this.useSsl;
	}

	public void setUseSsl(boolean useSsl)
	{
		this.useSsl = useSsl;
	}

	public boolean isRememberMe()
	{
		return this.rememberMe;
	}

	public void setRememberMe(boolean remember)
	{
		this.rememberMe = remember;
	}
	
	public long getLastUpdate()
	{
		return this.lastUpdate;
	}
	
	public void setLastUpdate(long lastUpdate)
	{
		this.lastUpdate = lastUpdate;
	}

	public long getUpdateInterval()
	{
		return this.updateInterval;
	}

	public void setUpdateInterval(long updateInterval)
	{
		this.updateInterval = updateInterval;
	}

	public void read(DataInputStream in)
		throws IOException
	{
		// Version is needed to handle reading different versions
		// of UserData. When writing we will always write the newest
		// version. This means downgrading the Joey J2ME client is
		// not supported.
		int version = in.readInt();

		this.username = in.readUTF();
		this.password = in.readUTF();
		this.useSsl = in.readBoolean();
		this.updateInterval = in.readLong();

		if (version >= 3) {
			this.lastUpdate = in.readLong();
		}

		if (version >= 2) {
			this.rememberMe = in.readBoolean();
		}
	}

	public void write(DataOutputStream out)
		throws IOException
	{
		out.writeInt(SERIALIZATION_VERSION);
		out.writeUTF(this.username);
		out.writeUTF(this.password);
		out.writeBoolean(this.useSsl);
		out.writeLong(this.updateInterval);
		out.writeLong(this.lastUpdate);
        out.writeBoolean(this.rememberMe);
	}
}
