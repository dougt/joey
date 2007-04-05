package org.mozilla.joey.j2me.views;

import de.enough.polish.util.Locale;

import javax.microedition.lcdui.Choice;
import javax.microedition.lcdui.List;

public class UploadsView extends List
{
	public UploadsView()
	{
		//#style uploadScreen
		super(Locale.get("title.uploads"), Choice.IMPLICIT);
	}
}
