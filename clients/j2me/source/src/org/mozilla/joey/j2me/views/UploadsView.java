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

		//#style uploadItem
		append("Test 1", null);
		
		//#style uploadItem
		append("Test 2", null);
		
		//#style uploadItem
		append("Test 3", null);
		
		//#style uploadItem
		append("Test 4", null);
		
		//#style uploadItem
		append("Test 5", null);
		
		//#style uploadItem
		append("Test 6", null);
	}
}
