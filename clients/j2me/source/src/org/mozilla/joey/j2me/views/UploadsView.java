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

    private JoeyController controller;
    private Vector uploads;


	public static final int COMMAND_ID_VIDEOS = 0;
	public static final int COMMAND_ID_MUSIC = 1;
	public static final int COMMAND_ID_PICTURES = 2;
	public static final int COMMAND_ID_RSS = 3;
	public static final int COMMAND_ID_TEXT = 4;
	public static final int COMMAND_ID_MICROSUM = 5;


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
		
		try
		{
            imgVideo = Image.createImage("/video_16x16.png");
			imgMusic = Image.createImage("/music_16x16.png");
			imgPictures = Image.createImage("/pictures_16x16.png");
			imgRss = Image.createImage("/rss_16x16.png");
			imgText = Image.createImage("/text_16x16.png");
			imgMs = Image.createImage("/ms_16x16.png");
		}
		catch (Exception e)
		{
			// TODO Auto-generated catch block
			e.printStackTrace();
		}

		//#style commandsitem
		this.commands.append(null, imgVideo);
		this.commands.setDefaultCommand(JoeyController.CMD_SELECT);
		this.commands.setItemCommandListener(this.controller);

		//#style commandsitem
		this.commands.append(null, imgMusic);
		this.commands.setDefaultCommand(JoeyController.CMD_SELECT);
		this.commands.setItemCommandListener(this.controller);

		//#style commandsitem
		this.commands.append(null, imgPictures);
		this.commands.setDefaultCommand(JoeyController.CMD_SELECT);
		this.commands.setItemCommandListener(this.controller);
		
		//#style commandsitem
		this.commands.append(null, imgRss);
		this.commands.setDefaultCommand(JoeyController.CMD_SELECT);
		this.commands.setItemCommandListener(this.controller);
		
		//#style commandsitem
		this.commands.append(null, imgText);
		this.commands.setDefaultCommand(JoeyController.CMD_SELECT);
		this.commands.setItemCommandListener(this.controller);
		
		//#style commandsitem
		this.commands.append(null, imgMs);
		this.commands.setDefaultCommand(JoeyController.CMD_SELECT);
		this.commands.setItemCommandListener(this.controller);
	}


    private Item getImagePrviewForUpdate(Upload upload)
    {

        Image image = null;
        try
        {
            image = Image.createImage(new ByteArrayInputStream(upload.getPreview()));

            //#style uploadItem
            return new ImageItem(null, image, ImageItem.LAYOUT_CENTER, upload.getId());

        }
        catch (Exception e)
        {
            //@todo localize.

            //#style uploadItem
            return new StringItem(null, "Could not display image");
        }
    }

	public void update()
	{
		this.container.clear();


        int commandid = this.commands.getSelectedIndex();


        String categoryTitle;
        // Set up the background.  
        switch (commandid) 
        {
        case COMMAND_ID_VIDEOS:
            categoryTitle = "Joey Videos";
            break;
            
        case COMMAND_ID_MUSIC:
            categoryTitle = "Joey Music";
            break;
            
        case COMMAND_ID_PICTURES:
            categoryTitle = "Joey Pictures";
            break;
            
        case COMMAND_ID_RSS:
            categoryTitle = "Joey RSS";
            break;
            
        case COMMAND_ID_TEXT:
            categoryTitle = "Joey Text";
            break;
            
        case COMMAND_ID_MICROSUM:
            categoryTitle = "Joey Microsummaries";
            break;
            
        default:
            categoryTitle = "Joey has no idea what this is.";
        }

        Item command = new StringItem(null, categoryTitle);
        append(command);

        int size = this.uploads.size();

        for (int i = 0; i < size; i++) 
        {

			Upload upload = (Upload) this.uploads.elementAt(i); 
            if (upload.isDeleted() == true)
                continue;

            Item uploadItem = null;
            switch (commandid) 
            {

                case COMMAND_ID_VIDEOS:
                    if (upload.getMimetype().equals("video/3gpp")) 
                    {
                        uploadItem = getImagePrviewForUpdate(upload);
                    }
                    break;

                case COMMAND_ID_MUSIC:
                    if (upload.getMimetype().substring(0,5).equals("audio")) 
                    {
                        //#style uploadItem
                        uploadItem = new StringItem(null, upload.getTitle());
                    }
                    break;

                case COMMAND_ID_PICTURES:
                    if (upload.getMimetype().substring(0,5).equals("image")) 
                    {
                        uploadItem = getImagePrviewForUpdate(upload);
                    }
                    break;


                case COMMAND_ID_RSS:
                    if (upload.getMimetype().substring(0,3).equals("rss")) 
                    {

                        // What I really want to do is
                        // return a container of an ICON and
                        // the Title for the RSS Feed, but I
                        // can't -- for whatever reason, the
                        // container icon doesn't get
                        // selected.

                        /*
                        //#style rssUploadItem
                        Container c = new Container(false);
                        c.allowCycling = false;

                        //#style uploadItem
                        Item title = new StringItem(null, upload.getTitle());
                        c.add( title );
                        c.add( getImagePrviewForUpdate(upload));
                        uploadItem = c;

                        */

                        uploadItem = getImagePrviewForUpdate(upload);
                    }
                    this.container.focus(0);

                    break;

                case COMMAND_ID_TEXT:
                    if (upload.getMimetype().substring(0,4).equals("text")) 
                    {
                        //#style uploadItem
                        uploadItem = new StringItem(null, upload.getTitle());
                    }
                    break;

                case COMMAND_ID_MICROSUM:
                    if (upload.getMimetype().substring(0,9).equals("microsumm")) 
                    {
                        //#style uploadItem
                        uploadItem = new StringItem(null, upload.getTitle());
                    }
                    break;

                default:
                {
                    //@todo localize

                    //#style uploadItem
                    uploadItem = new StringItem(null, "mime type not supported yet.");
                }
            }

            if (uploadItem == null)
                continue;

			uploadItem.setDefaultCommand(JoeyController.CMD_SELECT);
			uploadItem.setItemCommandListener(this.controller);
			UiAccess.setAttribute(uploadItem, JoeyController.ATTR_UPLOAD, upload);
			append(uploadItem);

        }
	}

	protected boolean handleKeyPressed(int keyCode, int gameAction)
	{

        boolean handled = false;

		if ((gameAction == LEFT && keyCode != Canvas.KEY_NUM4) ||
            (gameAction == RIGHT && keyCode != Canvas.KEY_NUM6)) {

			UiAccess.handleKeyPressed( this.commands, keyCode, gameAction);

            update();
            handled = true;
		}
        else if ((gameAction == UP && keyCode != Canvas.KEY_NUM2) || 
                 (gameAction == DOWN && keyCode != Canvas.KEY_NUM8)) {

            UiAccess.handleKeyPressed(this.container, keyCode, gameAction);
            handled = true;
        }


        this.currentlyActiveContainer = this.container;
        this.container.focus(0);
        return handled;
    }


	public Upload getCurrentUpload()
	{
		return (Upload) UiAccess.getAttribute(getCurrentItem(), JoeyController.ATTR_UPLOAD);
	}
}
