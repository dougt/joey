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
			item.setItemCommandListener(controller);
			UiAccess.setAttribute(item, "upload", upload);
			append(item);
		}
	}
}
