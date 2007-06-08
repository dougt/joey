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
	extends Thread
	implements CommandListener, ItemCommandListener, ResponseHandler
{
	public static final int EVENT_NONE = 0;
	public static final int EVENT_NO = 1;
	public static final int EVENT_YES = 2;
	public static final int EVENT_CANCEL = 3;
	public static final int EVENT_OK = 4;
	public static final int EVENT_BACK = 5;
	public static final int EVENT_EXIT = 6;
	public static final int EVENT_SELECT = 7;
	public static final int EVENT_DELETE = 8;

	public static final int EVENT_NETWORK_REQUEST_SUCCESSFUL = 100;
	public static final int EVENT_NETWORK_REQUEST_FAILED = 101;

	private static final int VIEW_LOGIN = 1;
	private static final int VIEW_MAINMENU = 2;
	private static final int VIEW_PREFERENCES = 3;
	private static final int VIEW_SNAPSHOT = 4;
	private static final int VIEW_UPLOADS = 5;
	private static final int VIEW_DETAILS = 6;

	private static final int ALERT_UPLOADS_DELETE_CONFIRMATION = 7;
	private static final int ALERT_EXIT_CONFIRMATION = 8;
	private static final int ALERT_LOGIN_ERROR = 9;
	private static final int ALERT_WAIT = 10;

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

	private int nextEvent = EVENT_NONE;
	private Displayable currentView;
	private MIDlet midlet;
	private Display display;
	private UserData userdata;
	private Vector uploads;
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
	
	private Displayable showView(int viewId)
	{
		this.currentView = getView(viewId);
		this.display.setCurrent(this.currentView);
		return this.currentView;
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
            view = new DetailsView();
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

		case ALERT_WAIT:
			//#style alertWait
			alert = new Alert(null, Locale.get("alert.wait.msg"), null, AlertType.INFO);
			alert.setTimeout(Alert.FOREVER);
			return alert;

		default:
			//#debug fatal
			System.out.println("unknown view: " + viewId);
		
			return null;
		}
	}

	public void commandAction(Command command, Displayable displayable)
	{
		processCommand(command);
	}

	public void commandAction(Command command, Item item)
	{
		processCommand(command);
	}
	
	private void processCommand(Command command)
	{
		switch (command.getCommandType()) {
			case Command.EXIT:
				notifyEvent(EVENT_EXIT);
				break;

			case Command.BACK:
				if (command == CMD_NO) {
					notifyEvent(EVENT_NO);
				}
				else {
					notifyEvent(EVENT_BACK);
				}
				break;

			case Command.OK:
				notifyEvent(EVENT_OK);
				break;

			case Command.CANCEL:
				notifyEvent(EVENT_CANCEL);
				break;

			case Command.SCREEN:
			case Command.ITEM:
				if (command == CMD_SELECT) {
					notifyEvent(EVENT_SELECT);
				}
				else if (command == CMD_LOGIN) {
					notifyEvent(EVENT_SELECT);
				}
				else if (command == CMD_YES) {
					notifyEvent(EVENT_YES);
				}
				else {
					//#debug info
					System.out.println("Unknown command: " + command.getLabel());
				}
				break;

			default:
				//#debug info
				System.out.println("Unknown command type: " + command.getCommandType());
				break;
		}
	}

	public void notifyProgress(NetworkRequest request, long current, long total)
    {
        //System.out.println(request + "(" + current + "/" + total + ")");
    }

	public void notifyResponse(NetworkRequest request)
	{
        if (request.responseCode == 511)  // No Active Session
        {
        	//#debug info
            System.out.println("Response said we are not logged in. Login and retry...");

            // We have been logged out. Retry.
        	this.commController.addNextRequest(request);
            this.commController.login(this.userdata, this, false);
        }
        else if (request instanceof LoginNetworkRequest)
        {
        	//#debug info
            System.out.println("LoginNetworkRequest request status: " + request.responseCode);

            if (request.responseCode == 200) {
            	if (((LoginNetworkRequest) request).sendSuccessNotification()) {
            		notifyEvent(EVENT_NETWORK_REQUEST_SUCCESSFUL);
            	}
            }
            else {
            	notifyEvent(EVENT_NETWORK_REQUEST_FAILED);
            }
        }
        else if (request instanceof IndexNetworkRequest)
        {
        	//#debug info
            System.out.println("IndexNetworkRequest request status: " + request.responseCode);

            notifyEvent(request.responseCode == 200 ? EVENT_NETWORK_REQUEST_SUCCESSFUL : EVENT_NETWORK_REQUEST_FAILED);
        }
        else if (request instanceof AddNetworkRequest)
        {
        	//#debug info
            System.out.println("AddNetworkRequest request status: " + request.responseCode);

            notifyEvent(request.responseCode == 200 ? EVENT_NETWORK_REQUEST_SUCCESSFUL : EVENT_NETWORK_REQUEST_FAILED);
        }
        else if (request instanceof DeleteNetworkRequest)
        {
        	//#debug info
            System.out.println("DeleteNetworkRequest request status: " + request.responseCode);

            notifyEvent(request.responseCode == 200 ? EVENT_NETWORK_REQUEST_SUCCESSFUL : EVENT_NETWORK_REQUEST_FAILED);
        }
        else if (request instanceof GetNetworkRequest)
        {
        	//#debug info
            System.out.println("GetNetworkRequest request status: " + request.responseCode);

            notifyEvent(request.responseCode == 200 ? EVENT_NETWORK_REQUEST_SUCCESSFUL : EVENT_NETWORK_REQUEST_FAILED);
        }
	}

	public void run()
	{
		int event;

		// Handle application login screen.
		do {
			LoginView view = (LoginView) showView(VIEW_LOGIN);
			
			event = waitEvent();
			switch (event) {
				case EVENT_SELECT:
					showView(ALERT_WAIT);
					view.saveUserData(this.userdata);

					try {
						this.storage.save(this.userdata, RMS_USERDATA);
					}
					catch (IOException e) {
						//#debug error
						System.out.println("unable to write userdata to record store");
									
						e.printStackTrace();
					}

					// Send login request.
					this.commController.login(this.userdata, this);

					// Handle events while wait screen is shown.
					do {
						event = waitEvent();
						
						switch (event) {
							case EVENT_NETWORK_REQUEST_SUCCESSFUL:
								break;
	
							case EVENT_NETWORK_REQUEST_FAILED:
								view = (LoginView) showView(VIEW_LOGIN);
								event = EVENT_NONE;
								break;
	
							default:
								event = EVENT_NONE;
								break;
						}
					} while (event != EVENT_NETWORK_REQUEST_SUCCESSFUL
							 && event != EVENT_NETWORK_REQUEST_FAILED);
					break;

				case EVENT_EXIT:
					event = doExitConfirmation();
					break;

				default:
					event = EVENT_NONE;
					break;
			}
		} while (event != EVENT_NETWORK_REQUEST_SUCCESSFUL
				 && event != EVENT_EXIT);

		// Handle main menu screen.
		if (event == EVENT_NETWORK_REQUEST_SUCCESSFUL) {
			do {
				MainMenuView mainMenu =
					(MainMenuView) showView(VIEW_MAINMENU);

				event = waitEvent();

				switch (event) {
					case EVENT_EXIT:
						event = doExitConfirmation();
						break;

					case EVENT_SELECT:
						switch (mainMenu.getSelectedIndex()) {
							// Uploads
							case 0:
								doUploads();
								break;

							case 1:
								doPreferences();
								break;
								
							case 2:
								doSnapshot();
								break;

							default:
								event = EVENT_NONE;
								break;
						}

						break;
				}
			} while (event != EVENT_EXIT);
		}

		this.midlet.notifyDestroyed();
	}

	private void doSnapshot()
	{
//#if polish.api.mmapi
		int event;
		SnapshotScreen view = (SnapshotScreen) showView(VIEW_SNAPSHOT);
		
		do {
			event = waitEvent();
			
			switch (event) {
				case EVENT_SELECT:
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
					break;

				case EVENT_BACK:
					break;

				default:
					event = EVENT_NONE;
					break;
			}
		} while (event != EVENT_BACK);

        //#endif
	}
	
	private void doPreferences()
	{
		int event;
		showView(VIEW_PREFERENCES);
		
		do {
			event = waitEvent();

			switch (event) {
				case EVENT_BACK:
					break;

				default:
					event = EVENT_NONE;
					break;
			}
		} while (event != EVENT_BACK);
	}

	private int doExitConfirmation()
	{
		showView(ALERT_EXIT_CONFIRMATION);
		return waitYesNo() == EVENT_YES ? EVENT_EXIT : EVENT_NONE;
	}

	public void doUploads()
	{
		int event;
		showView(ALERT_WAIT);
		this.commController.getIndex(this.uploads, this);
		event = waitEvent();
		
		if (event == EVENT_NETWORK_REQUEST_SUCCESSFUL) {
			do {
				UploadsView view = (UploadsView) showView(VIEW_UPLOADS);
				event = waitEvent();
				
				switch (event) {
					case EVENT_SELECT:
						doUploadDetails(view.getCurrentUpload());
						break;
	
					case EVENT_DELETE:
						showView(ALERT_UPLOADS_DELETE_CONFIRMATION);
						if (waitYesNo() == EVENT_YES) {
							showView(ALERT_WAIT);
							this.commController.delete(view.getCurrentUpload().getId(), this);
							event = waitEvent();
						}
						break;
				}
			} while (event != EVENT_BACK);
		}
	}

	private void doUploadDetails(Upload upload)
	{
		int event;

		showView(ALERT_WAIT);
		this.commController.get(upload, this);
		event = waitEvent();

		DetailsView view = (DetailsView) showView(VIEW_DETAILS);
		view.setUpload(upload);

		do {
			event = waitEvent();
			
			switch (event) {
				case EVENT_DELETE:
					showView(ALERT_UPLOADS_DELETE_CONFIRMATION);
					if (waitYesNo() == EVENT_YES) {
						showView(ALERT_WAIT);
						this.commController.delete(upload.getId(), this);
						event = waitEvent();

						if (event == EVENT_NETWORK_REQUEST_SUCCESSFUL) {
							event = EVENT_BACK;
						}
					}
					break;
			}
		} while (event != EVENT_BACK);
	}

	public int waitOkCancel()
	{
		int event;

		do {
			event = waitEvent();
		} while (event != EVENT_OK && event != EVENT_CANCEL);
		
		return EVENT_OK;
	}

	public int waitYesNo()
	{
		int event;

		do {
			event = waitEvent();
		} while (event != EVENT_YES && event != EVENT_NO);
		
		return event;
	}

	private int waitEvent()
	{
		int event = EVENT_NONE;

		do {
			synchronized (this) {
				try {
					// Wait for notification for next event.
					wait();
	
					event = this.nextEvent;
					this.nextEvent = EVENT_NONE;
				}
				catch (InterruptedException e) {
					// Ignore this.
				}
			}
		} while (event == EVENT_NONE);

		return event;
	}

	public void notifyEvent(int event)
	{
		synchronized (this) {
			this.nextEvent = event;
			notify();
		}
	}
}
