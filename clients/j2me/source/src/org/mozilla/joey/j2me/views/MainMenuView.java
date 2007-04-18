package org.mozilla.joey.j2me.views;

import de.enough.polish.ui.Choice;
import de.enough.polish.ui.List;
import de.enough.polish.util.Locale;

public class MainMenuView
	extends List
{
	public MainMenuView()
	{
		//#style mainmenuScreen
		super(Locale.get("title.mainmenu"), Choice.IMPLICIT);

		//#style menuentry
		append(Locale.get("mainmenu.view"), null);

		//#style menuentry
		append(Locale.get("mainmenu.preferences"), null);

		//#if polish.api.mmapi
			//#style menuentry
			append(Locale.get("mainmenu.snapshot"), null);
		//#endif
	}
}
