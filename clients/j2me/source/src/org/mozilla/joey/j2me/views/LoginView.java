package org.mozilla.joey.j2me.views;

import de.enough.polish.util.Locale;

import javax.microedition.lcdui.Choice;
import javax.microedition.lcdui.ChoiceGroup;
import javax.microedition.lcdui.Form;
import javax.microedition.lcdui.TextField;

public class LoginView
	extends Form
{
	private TextField mUsernameTextField;
	private TextField mPasswordTextField;
	private ChoiceGroup mLoginOptionsChoiceGroup;

	public LoginView()
	{
		//#style loginScreen
		super(Locale.get("title.login"));

		//#style input
		this.mUsernameTextField = new TextField("LoginID:", "", 10, TextField.ANY | TextField.NON_PREDICTIVE);
		append(this.mUsernameTextField);

		//#style input
        this.mPasswordTextField = new TextField("Password:", "", 10, TextField.PASSWORD | TextField.NON_PREDICTIVE);
        append(this.mPasswordTextField);
        
        String[] optionStrings = { "Remember Me" };
        this.mLoginOptionsChoiceGroup = new ChoiceGroup("", Choice.MULTIPLE, optionStrings, null);

        //#style checkbox
        append(this.mLoginOptionsChoiceGroup);
	}
}
