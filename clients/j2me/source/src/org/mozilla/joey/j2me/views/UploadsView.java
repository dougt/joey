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
import de.enough.polish.ui.IconItem;
import de.enough.polish.ui.UiAccess;
import de.enough.polish.util.Locale;

import java.io.ByteArrayInputStream;
import java.util.Vector;

import javax.microedition.lcdui.Canvas;
import javax.microedition.lcdui.Choice;
import javax.microedition.lcdui.ChoiceGroup;
import javax.microedition.lcdui.Graphics;
import javax.microedition.lcdui.Image;
import javax.microedition.lcdui.Item;
import javax.microedition.lcdui.StringItem;

import org.mozilla.joey.j2me.JoeyController;
import org.mozilla.joey.j2me.Upload;

public class UploadsView
	extends FramedForm
{
	public static final int COMMAND_ID_VIDEOS = 0;
	public static final int COMMAND_ID_MUSIC = 1;
	public static final int COMMAND_ID_PICTURES = 2;
	public static final int COMMAND_ID_RSS = 3;
	public static final int COMMAND_ID_TEXT = 4;
	public static final int COMMAND_ID_MICROSUM = 5;
	
	private ChoiceGroup commands;
    private JoeyController controller;
    private Vector uploads;

	public UploadsView(JoeyController controller)
	{
		//#style uploadScreen
		super(Locale.get("title.uploads"));

        this.controller = controller;
		initCommandsArea();
	}

    public void setUploads(Vector uploads)
    {
        this.uploads = uploads;
        this.update();
    }

	private void initCommandsArea()
	{
        // Order of the elements matter.  If you change
        // them, be sure to change the values above

		//#style commandsarea
		this.commands = new ChoiceGroup(null, Choice.EXCLUSIVE);

		append(Graphics.BOTTOM, this.commands);

		Image imgVideo = null;
		Image imgMusic = null;
		Image imgPictures = null;
		Image imgRss = null;
		Image imgText = null;
		Image imgMs = null;
        
		try {
            imgVideo = Image.createImage("/video.png");
			imgMusic = Image.createImage("/music.png");
			imgPictures = Image.createImage("/pictures.png");
			imgRss = Image.createImage("/rss.png");
			imgText = Image.createImage("/text.png");
			imgMs = Image.createImage("/ms.png");
		}
		catch (Exception e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}

		//#style commandsitem
		this.commands.append(null, imgVideo);

		//#style commandsitem
		this.commands.append(null, imgMusic);

		//#style commandsitem
		this.commands.append(null, imgPictures);
		
		//#style commandsitem
		this.commands.append(null, imgRss);
		
		//#style commandsitem
		this.commands.append(null, imgText);
		
		//#style commandsitem
		this.commands.append(null, imgMs);

/*

dougt -- not sure if we should have preferences and the
camera here or in the MainMenu view.

//#if polish.api.mmapi
		//#style commandsitem
		this.commands.append(null, imgCamera);
//#endif

		//#style commandsitem
		this.commands.append(null, imgPrefs);

*/
		this.commands.setDefaultCommand(JoeyController.CMD_SELECT);
		this.commands.setItemCommandListener(this.controller);
	}


    private Item getImagePreviewForUpdate(Upload upload)
    {
        try {
        	String title = upload.getTitle();
        	Image image = Image.createImage(new ByteArrayInputStream(upload.getPreview()));

        	if (title == null) {
//        		title = Locale.get("No title available");
        		title = "No title available";
        	}

        	//#if true
	            //#style uploadItem
	            //#= return new IconItem(title, image);
        	//#else
        		return null;
        	//#endif
        }
        catch (Exception e) {
            return null;
        }
    }

    private void updateDefaultUploadsView(String title, String mimeType)
    {
        int size = this.uploads.size();
        for (int i = 0; i < size; i++) 
        {
			Upload upload = (Upload) this.uploads.elementAt(i); 

			// TODO: this should be removed since we should clear these out of RMS
            if (upload.isDeleted() == true)
                continue;

            if (upload.getMimetype() == null)
                continue;
            
            Item uploadItem = null;
            String mimetypeOfUpload = upload.getMimetype();
            if (mimetypeOfUpload != null && mimetypeOfUpload.startsWith(mimeType)) {
                uploadItem = getImagePreviewForUpdate(upload);
                
                if (uploadItem == null) {
                    //#style uploadItem
                    uploadItem = new StringItem(null, upload.getTitle());
                }

                if (uploadItem == null) {
                    continue;
                }
                
                uploadItem.setDefaultCommand(JoeyController.CMD_SELECT);
                uploadItem.setItemCommandListener(this.controller);
                UiAccess.setAttribute(uploadItem, JoeyController.ATTR_UPLOAD, upload);
                append(uploadItem);
            }
        }
    }

    private void updateVideos()
    {
        updateDefaultUploadsView("Joey Videos", "video");
    }

    private void updateMusic()
    {
        updateDefaultUploadsView("Joey Music", "audio");
    }

    private void updatePictures()
    {
        updateDefaultUploadsView("Joey Pictures", "image");
    }
    
    private void updateRSS()
    {
        updateDefaultUploadsView("Joey RSS", "rss");

        /*
        Item command = new StringItem(null, "Joey RSS");
        append(command);

        int size = this.uploads.size();
        for (int i = 0; i < size; i++) 
        {
			Upload upload = (Upload) this.uploads.elementAt(i); 

            if (upload.isDeleted() == true)  // todo this shoudl be removed since we should clear these out of RMS
                continue;
            
            Item uploadItem = null;
            if (upload.getMimetype().substring(0,3).equals("rss")) 
            {
                //#style rssUploadItem
                Container c = new Container(false);
                c.allowCycling = false;
                
                //#style uploadItem
                Item title = new StringItem(null, upload.getTitle());
                c.add( title );
                c.add( getImagePreviewForUpdate(upload));
                uploadItem = c;
                
                uploadItem = getImagePreviewForUpdate(upload);
                if (uploadItem == null)
                {
                    //#style uploadItem
                    uploadItem = new StringItem(null, upload.getTitle());
                }
            }

            if (uploadItem == null)
                continue;
            
			uploadItem.setDefaultCommand(JoeyController.CMD_SELECT);
			uploadItem.setItemCommandListener(this.controller);
			UiAccess.setAttribute(uploadItem, JoeyController.ATTR_UPLOAD, upload);
			append(uploadItem);
        }
     */
    }

    private void updateText()
    {
        updateDefaultUploadsView("Joey Text", "text");
    }

    private void updateMicrosummaries()
    {
        updateDefaultUploadsView("Joey Microsummaries", "microsumm");
    }

	public void update()
	{
		this.container.clear();
        int commandid = this.commands.getSelectedIndex();

        switch (commandid) 
        {
        case COMMAND_ID_VIDEOS:
            updateVideos();
            break;
            
        case COMMAND_ID_MUSIC:
            updateMusic();
            break;
            
        case COMMAND_ID_PICTURES:
            updatePictures();
            break;
            
        case COMMAND_ID_RSS:
            updateRSS();
            break;
            
        case COMMAND_ID_TEXT:
            updateText();
            break;
            
        case COMMAND_ID_MICROSUM:
            updateMicrosummaries();
            break;
        }
	}

	protected boolean handleKeyPressed(int keyCode, int gameAction)
	{
        boolean handled = false;

		if ((gameAction == LEFT && keyCode != Canvas.KEY_NUM4) ||
            (gameAction == RIGHT && keyCode != Canvas.KEY_NUM6)) {

			handled = UiAccess.handleKeyPressed(this.commands, keyCode, gameAction);

            if (handled) {
            	update();

                if (this.container.size() > 0) {
                	this.container.focus(0);
                }
            }
		}
        else if ((gameAction == UP && keyCode != Canvas.KEY_NUM2) || 
                 (gameAction == DOWN && keyCode != Canvas.KEY_NUM8)) {
            return UiAccess.handleKeyPressed(this.container, keyCode, gameAction);
        }

        this.currentlyActiveContainer = this.container;

        if (!handled ) {
        	handled = super.handleKeyPressed(keyCode, gameAction);
        }

        return handled;
    }


	public Upload getCurrentUpload()
	{
		return (Upload) UiAccess.getAttribute(getCurrentItem(), JoeyController.ATTR_UPLOAD);
	}
}
