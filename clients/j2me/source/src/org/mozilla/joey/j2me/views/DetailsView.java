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
import javax.microedition.lcdui.Displayable;
import javax.microedition.lcdui.Command;
import javax.microedition.lcdui.StringItem;

//#if polish.api.mmapi
import javax.microedition.media.Manager;
import javax.microedition.media.Player;
import javax.microedition.media.control.VideoControl;
//#endif

import de.enough.polish.ui.UiAccess;

import de.enough.polish.browser.html.HtmlBrowser;
import de.enough.polish.browser.rss.*;

import org.mozilla.joey.j2me.JoeyController;
import org.mozilla.joey.j2me.Upload;

class RssItemCommandListener extends DefaultRssItemCommandListener {
 
    private JoeyController controller;
    private DetailsView view;

    public void setController(DetailsView view, JoeyController controller) {
        this.controller = controller;
        this.view = view;
    }

    public void commandAction(Command command, Displayable displayable) {}

    public void commandAction(Command command, Item item) {
        
		if (command == RssTagHandler.CMD_RSS_ITEM_SELECT) {
            
            RssItem rssItem = (RssItem) UiAccess.getAttribute(item, RssTagHandler.ATTR_RSS_ITEM);
            
			if (rssItem != null) {
                this.view.setDescription(rssItem.getDescription());
                this.controller.notifyEvent(JoeyController.EVENT_RSS_ITEM);
			}
		}
    }
}

public class DetailsView
	extends Form
{
    private Upload upload;
    private JoeyController controller;
    private String description;

	public DetailsView(JoeyController controller)
	{
		//#style detailsScreen
		super(Locale.get("title.details"));

        this.controller = controller;
	}

    public String getDescription()
    {
        return description;
    }

    public void setDescription(String description)
    {
        this.description = description;
    }

	public void setUpload(Upload upload)
	{
        this.upload = upload;
		update();
	}

	public void update()
	{
        setDescription(null);
		deleteAll();


        if (this.upload.getMimetype().equals("rss-source/text") )
        {
            try {
                RssItemCommandListener listener = new RssItemCommandListener();
                listener.setController(this, this.controller);

                RssBrowser rb = new RssBrowser(listener);
                
                removeCommand(RssTagHandler.CMD_GO_TO_ARTICLE);

                rb.loadPage( new ByteArrayInputStream( this.upload.getData() ));
                append(rb);
            }
            catch(Exception e) {
                //#style input
                Item item = new StringItem(null, "Could not create the viewer");
                append(item);
            }
        }
        else if (this.upload.getMimetype().equals("text/plain") ||
                 this.upload.getMimetype().equals("microsummary/xml") )
        {
            //#style textcontent
            Item item = new StringItem(null, new String(this.upload.getData()));
            append(item);
        }
        else if (this.upload.getMimetype().substring(0,5).equals("image"))
        {
            Image image = null;
            try
            {
                image = Image.createImage(new ByteArrayInputStream(this.upload.getData()));
            } catch (Exception ignored) {}

            //#style imagecontent
            Item item = new ImageItem(null, image, ImageItem.LAYOUT_CENTER, this.upload.getId());
            append(item);
        }
//#if polish.api.mmapi
        else if (this.upload.getMimetype().equals("audio/amr"))
        {
            if (this.upload.getData() != null) {
                try {
                    Player player;
                    player = Manager.createPlayer(new ByteArrayInputStream(this.upload.getData()), "audio/amr");
                    player.start();
                }
                catch(Exception t) {
                
                    //#style input
                    Item item = new StringItem(null, "Could not create player for audio/mpeg: " + t);
                    append(item);
                }
            }
            else
            {
                //#style button
                Item item = new StringItem(null, Locale.get("media.browser.open"));
                item.setDefaultCommand(JoeyController.CMD_MEDIA_OPEN);
                item.setItemCommandListener(controller);
                append(item);
            }
        }
        else if (this.upload.getMimetype().equals("video/3gpp"))
        {

            if ( this.upload.getData() != null) {

                // it would be cool if j2me polish had a video item.

                try {
                    VideoControl vc;
                    Player player;
                    
                    // create a player instance
                    player = Manager.createPlayer(new ByteArrayInputStream(this.upload.getData()), "video/3gpp");
                    
                    // realize the player
                    player.realize();

                    vc = (VideoControl)player.getControl("VideoControl");
                        
                    vc.initDisplayMode(VideoControl.USE_DIRECT_VIDEO, this);
                    
                    int canvasWidth = getWidth();
                    int canvasHeight = getHeight();
                    int displayWidth = vc.getDisplayWidth();
                    int displayHeight = vc.getDisplayHeight();
                    int x = (canvasWidth - displayWidth) / 2;
                    int y = (canvasHeight - displayHeight) / 2;
                    vc.setDisplayLocation(x, y);
                    vc.setVisible(true);
                    
                    player.prefetch();
                    player.start();
                }
                catch(Exception t) {
                    //#style input
                    Item item = new StringItem(null, "Could not create player for video: " + t);
                    append(item);
                }
            }
            else
            {
                //#style button
                Item item = new StringItem(null, Locale.get("media.browser.open"));
                item.setDefaultCommand(JoeyController.CMD_MEDIA_OPEN);
                item.setItemCommandListener(this.controller);
                append(item);
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
            Item item = new StringItem(null, "Mime type not supported yet (" + this.upload.getMimetype() + ")");
            append(item);
        }
	}
}
