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

package org.mozilla.joey.j2me;

import de.enough.polish.event.ThreadedCommandListener;
import de.enough.polish.io.RmsStorage;
import de.enough.polish.ui.UiAccess;
import de.enough.polish.util.Locale;

import javax.microedition.lcdui.Alert;
import javax.microedition.lcdui.AlertType;
import javax.microedition.lcdui.Command;
import javax.microedition.lcdui.CommandListener;
import javax.microedition.lcdui.Display;
import javax.microedition.lcdui.Displayable;
import javax.microedition.lcdui.Item;
import javax.microedition.lcdui.ItemCommandListener;
import javax.microedition.lcdui.List;
import javax.microedition.media.MediaException;
import javax.microedition.midlet.MIDlet;

import org.mozilla.joey.j2me.views.LoginView;
import org.mozilla.joey.j2me.views.MainMenuView;
import org.mozilla.joey.j2me.views.PreferencesView;
import org.mozilla.joey.j2me.views.UploadsView;
import org.mozilla.joey.j2me.views.DetailsView;

//#if polish.api.mmapi
import de.enough.polish.ui.SnapshotScreen;
//#endif

import java.io.IOException;
import java.util.Date;
import java.util.Vector;

public class JoeyController
	implements CommandListener, ItemCommandListener, ResponseHandler
{
	private static final int VIEW_LOGIN = 1;
	private static final int VIEW_MAINMENU = 2;
	private static final int VIEW_PREFERENCES = 3;
	private static final int VIEW_SNAPSHOT = 4;
	private static final int VIEW_UPLOADS = 5;
	private static final int VIEW_DETAILS = 6;

	private static final int ALERT_UPLOADS_DELETE_CONFIRMATION = 7;
	private static final int ALERT_EXIT_CONFIRMATION = 8;
	private static final int ALERT_LOGIN_ERROR = 9;

	public static final String ATTR_UPLOAD = "upload";
	
	public static final Command CMD_EXIT = new Command(Locale.get("command.exit"), Command.EXIT, 1);
//	public static final Command CMD_SELECT = new Command(Locale.get("command.select"), Command.SCREEN, 1);
	public static final Command CMD_SELECT = List.SELECT_COMMAND;
	public static final Command CMD_BACK = new Command(Locale.get("command.back"), Command.BACK, 1);
	public static final Command CMD_LOGIN = new Command(Locale.get("command.login"), Command.SCREEN, 1);
	public static final Command CMD_DELETE = new Command(Locale.get("command.delete"), Command.SCREEN, 1);
	public static final Command CMD_SNAPSHOT = new Command(Locale.get("command.snapshot"), Command.SCREEN, 1);
	public static final Command CMD_YES = new Command(Locale.get("command.yes"), Command.SCREEN, 1);
	public static final Command CMD_NO = new Command(Locale.get("command.no"), Command.BACK, 1);

	private static final String RMS_USERDATA = "userdata";

	private int prevViewId;
	private int currentViewId;
	private Displayable currentView;
	private MIDlet midlet;
	private Display display;
	private UserData userdata;
	private Vector uploads;
	private Upload focusedUpload;
	private RmsStorage storage;
	private CommandListener commandListener;
	private CommunicationController commController;

	public JoeyController(MIDlet midlet)
	{
		this.midlet = midlet;
		this.display = Display.getDisplay(midlet);
		this.uploads = new Vector();
		this.storage = new RmsStorage();
		
		try
		{
			this.userdata = (UserData) this.storage.read(RMS_USERDATA);
		}
		catch (IOException e)
		{
			//#debug info
			System.out.println("no user data stored in the record store");
			
			this.userdata = new UserData();
		}

		this.commandListener = new ThreadedCommandListener(this);
		this.commController = new CommunicationController();
		this.commController.start();
	}
	
	public void startApp()
	{
		showView(VIEW_LOGIN);
	}

	private void showView(int viewId)
	{
		this.prevViewId = this.currentViewId;
		this.currentViewId = viewId;
		this.currentView = getView(viewId);
		this.display.setCurrent(this.currentView);
	}

	private Displayable getView(int viewId)
	{
		Displayable view;
		Alert alert;
		
		switch (viewId)
		{
		case VIEW_LOGIN:
			view = new LoginView(this, this.userdata);
			view.addCommand(CMD_EXIT);
			view.addCommand(CMD_LOGIN);
			view.setCommandListener(this.commandListener);
			return view;
		
		case VIEW_MAINMENU:
			view = new MainMenuView();
			view.addCommand(CMD_EXIT);
			view.addCommand(CMD_SELECT);
			view.setCommandListener(this.commandListener);
			return view;
		
		//#if polish.api.mmapi
		case VIEW_SNAPSHOT:
			//#style snapshotScreen
			view = new SnapshotScreen(Locale.get("title.snapshot"));
			view.addCommand(CMD_SNAPSHOT);
			view.addCommand(CMD_BACK);
			view.setCommandListener(this.commandListener);
			return view;
		//#endif
			
		case VIEW_UPLOADS:
			view = new UploadsView(this, this.uploads);
			view.addCommand(CMD_BACK);
			view.setCommandListener(this.commandListener);
			return view;

		case VIEW_PREFERENCES:
			view = new PreferencesView();
			view.addCommand(CMD_BACK);
			view.setCommandListener(this.commandListener);
			return view;

        case VIEW_DETAILS:
            view = new DetailsView(this.focusedUpload);
            view.addCommand(CMD_BACK);
			view.addCommand(CMD_DELETE);
            view.setCommandListener(this.commandListener);
            return view;
            
		case ALERT_EXIT_CONFIRMATION:
			//#style alertConfirmation
			alert = new Alert(null, Locale.get("alert.exit.msg"), null, AlertType.CONFIRMATION);
			alert.setTimeout(Alert.FOREVER);
			alert.addCommand(CMD_YES);
			alert.addCommand(CMD_NO);
			alert.setCommandListener(this.commandListener);
			return alert;

		case ALERT_LOGIN_ERROR:
			//#style alertConfirmation
			alert = new Alert(null, Locale.get("alert.login.error"), null, AlertType.ERROR);
			alert.setTimeout(Alert.FOREVER);
			alert.setCommandListener(this.commandListener);
			return alert;
		
		case ALERT_UPLOADS_DELETE_CONFIRMATION:
			//#style alertConfirmation
			alert = new Alert( null, Locale.get("uploads.delete.msg"), null, AlertType.CONFIRMATION );
			alert.setTimeout(Alert.FOREVER);
			alert.addCommand(CMD_YES);
			alert.addCommand(CMD_NO);
			alert.setCommandListener(this.commandListener);
			return alert;

		default:
			//#debug fatal
			System.out.println("unknown view: " + viewId);
		
			return null;
		}
	}
	
	private void processCommand(Command command, Displayable displayable, Item item)
	{
		boolean handled = false;
		
		if (command == CMD_EXIT) {
			showView(ALERT_EXIT_CONFIRMATION);
			return;
		}

		switch (this.currentViewId)
		{
		case VIEW_MAINMENU:
			handled = processCommandMainMenu(command);
			break;

		case VIEW_LOGIN:
			handled = processCommandLogin(command);
			break;

		case VIEW_SNAPSHOT:
			handled = processCommandSnapshot(command);
			break;
			
		case VIEW_UPLOADS:
		case ALERT_UPLOADS_DELETE_CONFIRMATION:
			handled = processCommandUploads(command, item);
			break;

		case VIEW_PREFERENCES:
			handled = processCommandPreferences(command);
			break;
            
        case VIEW_DETAILS:
            handled = processCommandDetails(command, item);
            break;

		case ALERT_EXIT_CONFIRMATION:
			handled = processCommandAlertExitConfirmation(command);
			break;

		case ALERT_LOGIN_ERROR:
			showView(VIEW_LOGIN);
			handled = true;
			break;
			
		default:
			//#debug error
			System.out.println("Unknown view: " + this.currentViewId);
			break;
		}
		
		if (! handled) {
			System.err.println("Command [ " + command + " ] not handled for view [ " + this.currentView + " ]");
		}
	}

	private boolean processCommandSnapshot(Command command)
	{
		if (command == CMD_BACK) {
			showView(VIEW_MAINMENU);
			return true;
		}

//#if polish.api.mmapi
		SnapshotScreen view = (SnapshotScreen) this.currentView;

		if (command == CMD_SNAPSHOT) {
			try
			{
				// TODO: Choose the best encoding instead of using the first.
				String[] encodings = SnapshotScreen.getSnapshotEncodings();
				byte[] image = view.getSnapshot(encodings[0]);

				// TODO: Is this the correct format for time? Is this correct for all locales? 
				String modified = new Date().toString();
				Upload upload = new Upload("image/jpeg", image, image, modified);
				this.uploads.addElement(upload);
                this.commController.add(upload, this);
			}
			catch (MediaException e)
			{
				// TODO Auto-generated catch block
				e.printStackTrace();
			}

			return true;
		}
//#endif		
		return false;
	}

	private boolean processCommandDetails(Command command, Item item)
	{
		if (command == CMD_BACK) {
			showView(VIEW_UPLOADS);
			return true;
		}
		else if (command == CMD_DELETE) {
			this.focusedUpload = (Upload) UiAccess.getAttribute(item, ATTR_UPLOAD);
			showView(ALERT_UPLOADS_DELETE_CONFIRMATION);
			return true;
		}
        return false;
    }

	private boolean processCommandUploads(Command command, Item item)
	{
		if (command == CMD_BACK) {
			showView(VIEW_MAINMENU);
			return true;
		}
		else if (command == CMD_SELECT) {
            this.focusedUpload = (Upload) UiAccess.getAttribute(item, ATTR_UPLOAD);
            this.commController.get(this.focusedUpload.getId(), this);
			return true;
		}
		else if (command == CMD_DELETE) {
			this.focusedUpload = (Upload) UiAccess.getAttribute(item, ATTR_UPLOAD);
			showView(ALERT_UPLOADS_DELETE_CONFIRMATION);
			return true;
		}
		else if (command == CMD_YES) {
			// TODO: Show wait alert here while deletion is happening. 
			//showWaitAlert();
            this.commController.delete(this.focusedUpload.getId(), this);
            return true;
		}
		else if (command == CMD_NO) {
			this.focusedUpload = null;
			showView(VIEW_UPLOADS);
			return true;
		}

		return false;
	}

	private boolean processCommandMainMenu(Command command)
	{
		if (command == CMD_SELECT) {
			int index = ((MainMenuView) this.currentView).getCurrentIndex();
			switch (index) {
				case 0:
					this.commController.getIndex(this.uploads, this);
					break;

				case 1:
					showView(VIEW_PREFERENCES);
					break;

				case 2:
					showView(VIEW_SNAPSHOT);
					break;

				default:
					//#debug fatal
					System.out.println("invalid menu item: " + index);
					break;
			}

			return true;
		}
		
		return false;
	}

	private boolean processCommandLogin(Command command)
	{
		LoginView view = (LoginView) this.currentView;

		if (command == CMD_LOGIN) {
			view.saveUserData(this.userdata);

			try
			{
				this.storage.save(this.userdata, RMS_USERDATA);
			}
			catch (IOException e)
			{
				//#debug error
				System.out.println("unable to write userdata to record store");
				
				e.printStackTrace();
			}

            this.commController.login(this.userdata, this);

			return true;
		}

		return false;
	}

	private boolean processCommandPreferences(Command command)
	{
		if (command == CMD_BACK) {
			showView(VIEW_MAINMENU);
			return true;
		}

		return false;
	}

	private boolean processCommandAlertExitConfirmation(Command command)
	{
		if (command == CMD_YES) {
			this.midlet.notifyDestroyed();
		}
		else {
			showView(this.prevViewId);
		}

		return true;
	}

	public void commandAction(Command command, Displayable displayable)
	{
		processCommand(command, displayable, null);
	}

	public void commandAction(Command command, Item item)
	{
		processCommand(command, null, item);
	}

	public void notifyProgress(NetworkRequest request, long current, long total)
    {
        //System.out.println(request + "(" + current + "/" + total + ")");
    }

	public void notifyResponse(NetworkRequest request)
	{
        if (request.responseCode == 511)  // No Active Session
        {
            // We have been logged out.  :-(
            this.commController.login(this.userdata, this);
        }
        else if (request instanceof LoginNetworkRequest)
        {
        	//#debug debug
            System.out.println("LoginNetworkRequest request status: " + request.responseCode);

            if (request.responseCode == 200) // Login ok,
				showView(VIEW_MAINMENU);
            else
				showView(ALERT_LOGIN_ERROR);
        }
        else if (request instanceof IndexNetworkRequest)
        {
        	//#debug debug
            System.out.println("IndexNetworkRequest request status: " + request.responseCode);

            showView(VIEW_UPLOADS);
            ((UploadsView) this.currentView).update(this,((IndexNetworkRequest) request).uploads);
        }
        else if (request instanceof AddNetworkRequest)
        {
        	//#debug debug
            System.out.println("AddNetworkRequest request status: " + request.responseCode);

            // TODO: Do something?
        }
        else if (request instanceof DeleteNetworkRequest)
        {
        	//#debug debug
            System.out.println("DeleteNetworkRequest request status: " + request.responseCode);

            // TODO: ??
            this.uploads.removeElement(this.focusedUpload);
			this.focusedUpload = null;
			showView(VIEW_UPLOADS);
			((UploadsView) this.currentView).update(this, this.uploads);
        }
        else if (request instanceof GetNetworkRequest)
        {
        	//#debug debug
            System.out.println("GetNetworkRequest request status: " + request.responseCode);

            if (request.responseCode == 200) // ok
            {
                this.focusedUpload.setData(request.data);
                showView(VIEW_DETAILS);
            }
        }
	}
}
