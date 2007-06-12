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

import de.enough.polish.ui.FramedForm;
import de.enough.polish.ui.UiAccess;
import de.enough.polish.util.Locale;

import java.io.ByteArrayInputStream;
import java.io.IOException;
import java.util.Vector;

import javax.microedition.lcdui.Canvas;
import javax.microedition.lcdui.Choice;
import javax.microedition.lcdui.ChoiceGroup;
import javax.microedition.lcdui.Graphics;
import javax.microedition.lcdui.Image;
import javax.microedition.lcdui.ImageItem;
import javax.microedition.lcdui.Item;
import javax.microedition.lcdui.StringItem;

import org.mozilla.joey.j2me.JoeyController;
import org.mozilla.joey.j2me.Upload;

public class UploadsView
	extends FramedForm
{
	private ChoiceGroup commands;

	public UploadsView(JoeyController controller, Vector uploads)
	{
		//#style uploadScreen
		super(Locale.get("title.uploads"));

		initCommandsArea(controller);
		update(controller, uploads);
	}

	private void initCommandsArea(JoeyController controller)
	{
		//#style commandsarea
		this.commands = new ChoiceGroup(null, Choice.EXCLUSIVE);
		append(Graphics.BOTTOM, this.commands);

		Image imgSelect = null;
		Image imgDelete = null;
		Image img3 = null;
		Image img4 = null;
		Image img5 = null;
		Image img6 = null;
		
		try
		{
			imgSelect = Image.createImage("/icon_red.png");
			imgDelete = Image.createImage("/icon_delete.png");
			img3 = Image.createImage("/icon_green.png");
			img4 = Image.createImage("/icon_blue.png");
			img5 = Image.createImage("/icon_white.png");
			img6 = Image.createImage("/icon_yellow.png");
		}
		catch (IOException e)
		{
			// TODO Auto-generated catch block
			e.printStackTrace();
		}

		//#style commandsitem
		this.commands.append(null, imgSelect);
		this.commands.setDefaultCommand(JoeyController.CMD_SELECT);
		this.commands.setItemCommandListener(controller);
		
		//#style commandsitem
		this.commands.append(null, imgDelete);
		this.commands.setDefaultCommand(JoeyController.CMD_DELETE);
		this.commands.setItemCommandListener(controller);

		//#style commandsitem
		this.commands.append(null, img3);
		this.commands.setDefaultCommand(JoeyController.CMD_SELECT);
		this.commands.setItemCommandListener(controller);
		
		//#style commandsitem
		this.commands.append(null, img4);
		this.commands.setDefaultCommand(JoeyController.CMD_SELECT);
		this.commands.setItemCommandListener(controller);
		
		//#style commandsitem
		this.commands.append(null, img5);
		this.commands.setDefaultCommand(JoeyController.CMD_SELECT);
		this.commands.setItemCommandListener(controller);
		
		//#style commandsitem
		this.commands.append(null, img6);
		this.commands.setDefaultCommand(JoeyController.CMD_SELECT);
		this.commands.setItemCommandListener(controller);
	}

	public void update(JoeyController controller, Vector uploads)
	{
		this.container.clear();

		for (int i = 0; i < uploads.size(); i++) {
			Upload upload = (Upload) uploads.elementAt(i); 
			Image image = null;

            if (upload.isDeleted() == true)
                continue;

            try
            {
                image = Image.createImage(new ByteArrayInputStream(upload.getPreview()));
            }
            catch (Exception e)
            {
                // this is going to fail for string data.
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

	protected boolean handleKeyPressed(int keyCode, int gameAction)
	{
		if ((gameAction == LEFT && keyCode != Canvas.KEY_NUM4)
			|| (gameAction == RIGHT && keyCode != Canvas.KEY_NUM6) 
			|| (gameAction == FIRE && keyCode != Canvas.KEY_NUM5)) {
			return UiAccess.handleKeyPressed( this.commands, keyCode, gameAction);
		}

		boolean handled = super.handleKeyPressed(keyCode, gameAction);

		if (handled && this.container.size() > 0
			&& this.currentlyActiveContainer == this.bottomFrame ) {
			this.currentlyActiveContainer = this.container;
			this.container.focus(0);
			//#= this.bottomFrame.defocus(StyleSheet.commandsareaStyle);
		}

		return handled;
	}

	public Upload getCurrentUpload()
	{
		return (Upload) UiAccess.getAttribute(getCurrentItem(), JoeyController.ATTR_UPLOAD);
	}
}
