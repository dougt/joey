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

import de.enough.polish.util.Locale;

import java.io.ByteArrayInputStream;

import javax.microedition.lcdui.Form;
import javax.microedition.lcdui.Image;
import javax.microedition.lcdui.ImageItem;
import javax.microedition.lcdui.Item;
import javax.microedition.lcdui.StringItem;

//#if polish.api.mmapi
import javax.microedition.media.Manager;
import javax.microedition.media.Player;
import javax.microedition.media.control.GUIControl;
import javax.microedition.media.control.VideoControl;
//#endif

import de.enough.polish.browser.html.HtmlBrowser;

import org.mozilla.joey.j2me.Upload;

public class DetailsView
	extends Form
{
    private Upload upload;

	public DetailsView(Upload upload)
	{
		//#style detailsScreen
		super(Locale.get("title.details"));

        this.upload = upload;
		update();
	}

	public void update()
	{
		deleteAll();

		//#style input
		Item item = new StringItem(null, this.upload.getId());
		append(item);

        //#style input
        item = new StringItem(null, this.upload.getMimetype());
        append(item);

        if (this.upload.getMimetype().equals("text/plain") ||
            this.upload.getMimetype().equals("microsummary/xml") ||
            this.upload.getMimetype().equals("rss-source/text")  )
        {
            item = new StringItem(null, new String(this.upload.getData()));
            append(item);
        }
        else if (this.upload.getMimetype().substring(0,5).equals("image"))
        {
            Image image = null;
            try
            {
                image = Image.createImage(new ByteArrayInputStream(this.upload.getData()));
            } catch (Exception ignored) {}

            item = new ImageItem(null, image, ImageItem.LAYOUT_CENTER, this.upload.getId());
            append(item);
        }
//#if polish.api.mmapi
        else if (this.upload.getMimetype().equals("audio/mpeg"))
        {
        	try {
                Player player;
                player = Manager.createPlayer(new ByteArrayInputStream(this.upload.getData()), "audio/mpeg");
                player.realize();
                player.prefetch();
                player.start();
            }
            catch(Throwable t) {
                System.out.println("assertion: " + t);
            }

        }
        else if (this.upload.getMimetype().equals("video/3gpp"))
        {
        	try {
            	VideoControl vc;
                Player player;

                // create a player instance
                player = Manager.createPlayer(new ByteArrayInputStream(this.upload.getData()), "video/3gpp");

                // realize the player
                player.realize();
                vc = (VideoControl)player.getControl("VideoControl");
                if(vc != null) {
                    Item video = (Item)vc.initDisplayMode(GUIControl.USE_GUI_PRIMITIVE, null);
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
        else if (this.upload.getMimetype().equals("widget/joey"))
        {
            try {

                System.out.println(new String(this.upload.getData()));

                HtmlBrowser b = new HtmlBrowser();
                b.loadPage( new ByteArrayInputStream( this.upload.getData() ));
                append(b);
            } catch(Exception t) {
                t.printStackTrace();
                System.out.println("assertion: " + t);
            }
        }
        else
        {
            item = new StringItem(null, "Mime type not supported yet (" + this.upload.getMimetype() + ")");
            append(item);
        }
	}
}
