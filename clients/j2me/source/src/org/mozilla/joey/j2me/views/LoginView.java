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
import javax.microedition.lcdui.StringItem;
import javax.microedition.lcdui.TextField;

import org.mozilla.joey.j2me.JoeyController;
import org.mozilla.joey.j2me.UserData;

public class LoginView
	extends Form
{
	private TextField mUsernameTextField;
	private TextField mPasswordTextField;
	private ChoiceGroup mLoginOptionsChoiceGroup;

	public LoginView(JoeyController controller, UserData userdata)
	{
		//#style loginScreen
		super(Locale.get("title.login"));

		//#style input
		this.mUsernameTextField = new TextField(Locale.get("form.login.username"), "", 50, TextField.ANY | TextField.NON_PREDICTIVE);
		this.mUsernameTextField.setString(userdata.getUsername());
		append(this.mUsernameTextField);

		//#style input
        this.mPasswordTextField = new TextField(Locale.get("form.login.password"), "", 50, TextField.PASSWORD | TextField.NON_PREDICTIVE);
        this.mPasswordTextField.setString(userdata.getPassword());
        append(this.mPasswordTextField);

        String[] optionStrings = { Locale.get("form.login.usessl"), Locale.get("form.login.rememberme") };
        this.mLoginOptionsChoiceGroup = new ChoiceGroup("", Choice.MULTIPLE, optionStrings, null);
		boolean[] flags = new boolean[2];
		flags[0] = userdata.isUseSsl();
		flags[1] = userdata.isRememberMe();
		this.mLoginOptionsChoiceGroup.setSelectedFlags(flags);

        //#style checkbox
        append(this.mLoginOptionsChoiceGroup);
        
        //#style button
        StringItem item = new StringItem(null, Locale.get("form.login.login"));
        item.setDefaultCommand(JoeyController.CMD_LOGIN);
        item.setItemCommandListener(controller);
        append(item);
	}
	
	public void saveUserData(UserData userData)
	{
		userData.setUsername(this.mUsernameTextField.getString());
		userData.setPassword(this.mPasswordTextField.getString());
		boolean[] flags = new boolean[2];
		this.mLoginOptionsChoiceGroup.getSelectedFlags(flags);
		userData.setUseSsl(flags[0]);
		userData.setRememberMe(flags[1]);
	}
}
