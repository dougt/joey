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
 * Doug Turner.
 * Portions created by the Initial Developer are Copyright (C) 2007
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *
 * ***** END LICENSE BLOCK ***** */

package org.mozilla.joey.j2me.views;

import org.bouncycastle.util.encoders.Base64;

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

//#if polish.api.mmapi
import javax.microedition.media.Manager;
import javax.microedition.media.MediaException;
import javax.microedition.media.Player;
import javax.microedition.media.control.VideoControl;
//#endif

import org.mozilla.joey.j2me.JoeyController;
import org.mozilla.joey.j2me.Upload;

public class DetailsView
	extends Form
{

    private Upload upload;

	public DetailsView(Upload upload)
	{
		//#style uploadScreen
		super(Locale.get("title.uploads"));

        this.upload = upload;
		update();
	}

	public void update()
	{
        Item item = new StringItem(null, upload.getId());
        append(item);

        item = new StringItem(null, upload.getMimetype());
        append(item);

        if (upload.getMimetype().equals("text/plain") ||
            upload.getMimetype().equals("microsummary/xml"))
        {
            item = new StringItem(null, new String(upload.getData()));
            append(item);
        }
        else if (upload.getMimetype().substring(0,5).equals("image"))
        {
            Image image = null;
            try
            {
                image = Image.createImage(new ByteArrayInputStream(upload.getData()));
            } catch (Exception ignored) {}

            item = new ImageItem(null, image, ImageItem.LAYOUT_CENTER, upload.getId());
            append(item);
        }
//#if polish.api.mmapi
        else if (upload.getMimetype().equals("video/3gp"))
        {

            try {
                VideoControl vc;
                Player player;

                // create a player instance
                player = Manager.createPlayer(new ByteArrayInputStream(upload.getData()), "video/3gpp");
                //                player.addPlayerListener(this);
                // realize the player
                player.realize();
                vc = (VideoControl)player.getControl("VideoControl");
                if(vc != null) {
                    Item video = (Item)vc.initDisplayMode(vc.USE_GUI_PRIMITIVE, null);
                    append(video);
                }
                player.prefetch();
                player.start();
            }
            catch(Throwable t) {
                System.out.println("assertion: " + t);
            }
        }
//#endif
	}
}
