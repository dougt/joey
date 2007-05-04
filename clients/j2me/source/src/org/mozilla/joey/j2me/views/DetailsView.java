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

        System.out.println(" asdfasdf " + upload.getMimetype().substring(0,5));

        if (upload.getMimetype().equals("text/plain"))
        {
            item = new StringItem(null, new String(Base64.decode(upload.getData())));
            append(item);
        }
        else if (upload.getMimetype().substring(0,5).equals("image"))
        {
            Image image = null;
            try
            {
                image = Image.createImage(new ByteArrayInputStream(Base64.decode(upload.getData())));
            } catch (Exception ignored) {}

            item = new ImageItem(null, image, ImageItem.LAYOUT_CENTER, upload.getId());
            append(item);
        }
        else if (upload.getMimetype().equals("video/3gp"))
        {
            //@todo
        }
        
        
	}
}
