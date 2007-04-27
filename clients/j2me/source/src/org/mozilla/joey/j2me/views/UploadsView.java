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

import de.enough.polish.ui.UiAccess;
import de.enough.polish.util.Locale;

import java.io.ByteArrayInputStream;
import java.io.IOException;
import java.util.Vector;

import javax.microedition.lcdui.Form;
import javax.microedition.lcdui.Image;
import javax.microedition.lcdui.ImageItem;
import javax.microedition.lcdui.Item;
import javax.microedition.lcdui.StringItem;

import org.bouncycastle.util.encoders.Base64;
import org.mozilla.joey.j2me.JoeyController;
import org.mozilla.joey.j2me.Upload;

public class UploadsView
	extends Form
{
	public UploadsView(JoeyController controller, Vector uploads)
	{
		//#style uploadScreen
		super(Locale.get("title.uploads"));

		update(controller, uploads);
	}

	public void update(JoeyController controller, Vector uploads)
	{
		deleteAll();

		for (int i = 0; i < uploads.size(); i++) {
			Upload upload = (Upload) uploads.elementAt(i); 
			Image image = null;
			
			if ("image/png".equals(upload.getMimetype())) {
				try
				{
					byte[] data = Base64.decode(upload.getPreview());
					image = Image.createImage(new ByteArrayInputStream(data));
				}
				catch (IOException e)
				{
					//#debug error
					System.out.println("cannot convert preview into image");
				}
			}

			Item item;

			if (image != null) {
				//#style uploadItem
				item = new ImageItem(null, image, ImageItem.LAYOUT_CENTER, upload.getId());
			}
			else {
				//#style uploadItem
				item = new StringItem(null, upload.getId());
			}

			item.setDefaultCommand(JoeyController.CMD_SELECT);
			item.addCommand(JoeyController.CMD_DELETE);
			item.setItemCommandListener(controller);
			UiAccess.setAttribute(item, JoeyController.ATTR_UPLOAD, upload);
			append(item);
		}
	}
}
