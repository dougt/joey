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

import de.enough.polish.io.Serializable;

public class UserData
	implements Serializable
{

	public static final int JOEY_RMS_VERSION = 1;

    private int version;

	private String username;
	private String password;
	private boolean useSsl;
	private long updateInterval;

	public UserData()
	{
        this.version = JOEY_RMS_VERSION;
	}

	public UserData(String username, String password, boolean useSsl, long updateInterval)
	{
		this.username = username;
		this.password = password;
		this.useSsl = useSsl;
		this.updateInterval = updateInterval;
        this.version = JOEY_RMS_VERSION;
	}

    public int getVersion()
    {
        return this.version;
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

	public long getUpdateInterval()
	{
		return this.updateInterval;
	}

	public void setUpdateInterval(long updateInterval)
	{
		this.updateInterval = updateInterval;
	}
}
