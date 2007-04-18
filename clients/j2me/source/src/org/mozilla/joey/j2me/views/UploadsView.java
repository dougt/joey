package org.mozilla.joey.j2me.views;

import de.enough.polish.util.Locale;

import java.util.Vector;

import javax.microedition.lcdui.Choice;
import javax.microedition.lcdui.List;

import org.mozilla.joey.j2me.Upload;

public class UploadsView extends List
{
	public UploadsView(Vector uploads)
	{
		//#style uploadScreen
		super(Locale.get("title.uploads"), Choice.IMPLICIT);

		for (int i = 0; i < uploads.size(); i++) {
			Upload upload = (Upload) uploads.elementAt(i); 

			//#style uploadItem
			append(upload.getName(), null);
		}
	}
}
