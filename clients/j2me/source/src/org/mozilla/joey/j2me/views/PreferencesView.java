package org.mozilla.joey.j2me.views;

import de.enough.polish.util.Locale;

import javax.microedition.lcdui.Form;

public class PreferencesView
	extends Form
{
	public PreferencesView()
	{
		//#style inputScreen
		super(Locale.get("form.preferences"));
	}
}
