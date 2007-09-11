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

package org.mozilla.joey.j2me.views;

import de.enough.polish.util.Locale;

import javax.microedition.lcdui.Choice;
import javax.microedition.lcdui.ChoiceGroup;
import javax.microedition.lcdui.Form;
import javax.microedition.lcdui.TextField;

public class PreferencesView
	extends Form
{
	private ChoiceGroup server;
	private TextField updateInterval;
	
	public PreferencesView()
	{
		//#style inputScreen
		super(Locale.get("form.preferences"));
		
		//#style radiobox
		this.server = new ChoiceGroup(Locale.get("form.preferences.server"), Choice.EXCLUSIVE);
		append(this.server);

		//#style radioButton
		this.server.append("joey.labs.mozilla.com", null);

		//#style radioButton
		this.server.append("dougt.joey-dev.labs.mozilla.com", null);

		//#style radioButton
		this.server.append("clouserw.joey-dev.labs.mozilla.com", null);

		//#style input
		this.updateInterval = new TextField(Locale.get("form.preferences.update_interval"), "", 10, TextField.DECIMAL);
		append(this.updateInterval);
	}

	public String getServer()
	{
		return this.server.getString(this.server.getSelectedIndex());
	}

	public void setServer(String server)
	{
		// iterate over all entries and set the right one to selected.
		for (int index = 0; index < this.server.size(); index++) {
			if (this.server.getString(index).equals(server)) {
				this.server.setSelectedIndex(index, true);
				break;
			}
		}
	}
	
	public long getUpdateInterval()
	{
		String tmp = this.updateInterval.getString();

		if (tmp != null) {
			try {
				return Long.parseLong(tmp);
			}
			catch (NumberFormatException e) {
				return -1;
			}
		}

		return -1;
	}

	public void setUpdateInterval(long updateInterval)
	{
		this.updateInterval.setString(Long.toString(updateInterval));
	}
}
