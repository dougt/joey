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
