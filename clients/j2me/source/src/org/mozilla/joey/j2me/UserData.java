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
	private String username;
	private String password;
	private boolean useSsl;

	public UserData()
	{
	}

	public UserData(String username, String password, boolean useSsl)
	{
		this.username = username;
		this.password = password;
		this.useSsl = useSsl;
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
}
