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
		this.mUsernameTextField = new TextField("LoginID:", "", 10, TextField.ANY | TextField.NON_PREDICTIVE);
		this.mUsernameTextField.setString(userdata.getUsername());
		append(this.mUsernameTextField);

		//#style input
        this.mPasswordTextField = new TextField("Password:", "", 10, TextField.PASSWORD | TextField.NON_PREDICTIVE);
        this.mPasswordTextField.setString(userdata.getPassword());
        append(this.mPasswordTextField);

        String[] test = { "Use SSL for data encryption" };
        ChoiceGroup test2 = new ChoiceGroup(null, Choice.MULTIPLE, test, null);
        append(test2);
        
        String[] optionStrings = { "Remember Me" };
        this.mLoginOptionsChoiceGroup = new ChoiceGroup("", Choice.MULTIPLE, optionStrings, null);
        // TODO: Read this option from userdata.

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
	}
}
