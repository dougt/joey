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

import javax.microedition.io.ConnectionNotFoundException;
import javax.microedition.lcdui.Alert;
import javax.microedition.lcdui.AlertType;
import javax.microedition.lcdui.Command;
import javax.microedition.lcdui.CommandListener;
import javax.microedition.lcdui.Display;
import javax.microedition.lcdui.Displayable;
import javax.microedition.lcdui.Gauge;
import javax.microedition.lcdui.Item;
import javax.microedition.lcdui.ItemCommandListener;
import javax.microedition.lcdui.List;
import javax.microedition.media.MediaException;
import javax.microedition.midlet.MIDlet;

import org.bouncycastle.util.encoders.Base64;
import org.mozilla.joey.j2me.views.LoginView;
import org.mozilla.joey.j2me.views.MainMenuView;
import org.mozilla.joey.j2me.views.PreferencesView;
import org.mozilla.joey.j2me.views.UploadsView;
import org.mozilla.joey.j2me.views.DetailsView;
import org.mozilla.joey.j2me.views.RssItemView;

//#if polish.api.mmapi
import de.enough.polish.ui.SnapshotScreen;
//#endif

import java.io.IOException;
import java.util.Date;
import java.util.Hashtable;
import java.util.Timer;
import java.util.TimerTask;
import java.util.Vector;

public class JoeyController
	extends Thread
	implements CommandListener, ItemCommandListener, ResponseHandler
{
	private static final int DEFAULT_UPDATE_INTERVAL = 300;

	public static final String VERSION_UNKNOWN = "unknown";

	//#if joey.version:defined
		//#= private static String VERSION = "${joey.version}";
	//#else
		private static final String VERSION = VERSION_UNKNOWN;
	//#endif

	public static final int EVENT_NONE = 0;
	public static final int EVENT_NO = 1;
	public static final int EVENT_YES = 2;
	public static final int EVENT_CANCEL = 3;
	public static final int EVENT_OK = 4;
	public static final int EVENT_BACK = 5;
	public static final int EVENT_EXIT = 6;
	public static final int EVENT_SELECT = 7;
	public static final int EVENT_DELETE = 8;
	public static final int EVENT_MEDIA_OPEN = 9;
	public static final int EVENT_RSS_ITEM = 10;
	public static final int EVENT_SAVE = 11;

	public static final int EVENT_NETWORK_REQUEST_FAILED = 100;
	public static final int EVENT_NETWORK_REQUEST_SUCCESSFUL = 101;
	public static final int EVENT_NETWORK_REQUEST_SUCCESSFUL_PARTIALLY = 102;

	private static final int EVENT_UPDATE_AVAILABLE = 110;

	private static final int VIEW_LOGIN = 1;
	private static final int VIEW_MAINMENU = 2;
	private static final int VIEW_PREFERENCES = 3;
	private static final int VIEW_SNAPSHOT = 4;
	private static final int VIEW_UPLOADS = 5;
	private static final int VIEW_DETAILS = 6;
	private static final int VIEW_RSS_ITEM = 7;

	private static final int ALERT_UPLOADS_DELETE_CONFIRMATION = 8;
	private static final int ALERT_EXIT_CONFIRMATION = 9;
	private static final int ALERT_LOGIN_ERROR = 10;
	private static final int ALERT_WAIT = 11;
	private static final int ALERT_MEDIA_OPEN_ERROR = 12;
	private static final int ALERT_APPLY_UPDATES = 13;
	private static final int ALERT_NEW_VERSION_AVAILABLE = 14;
	private static final int ALERT_ERROR_DISPLAY_DETAILS = 15;
	private static final int ALERT_NETWORK_ERROR = 16;
	private static final int ALERT_SECURITY_ERROR = 17;

	public static final String ATTR_UPLOAD = "upload";

	public static final Command CMD_EXIT = new Command(Locale.get("command.exit"), Command.EXIT, 1);
	public static final Command CMD_SELECT = List.SELECT_COMMAND;
	public static final Command CMD_BACK = new Command(Locale.get("command.back"), Command.BACK, 1);
	public static final Command CMD_LOGIN = new Command(Locale.get("command.login"), Command.SCREEN, 1);
	public static final Command CMD_DELETE = new Command(Locale.get("command.delete"), Command.SCREEN, 10);
	public static final Command CMD_SNAPSHOT = new Command(Locale.get("command.snapshot"), Command.SCREEN, 1);
	public static final Command CMD_OK = new Command(Locale.get("command.ok"), Command.OK, 1);
	public static final Command CMD_CANCEL = new Command(Locale.get("command.cancel"), Command.CANCEL, 1);
	public static final Command CMD_YES = new Command(Locale.get("command.yes"), Command.OK, 1);
	public static final Command CMD_NO = new Command(Locale.get("command.no"), Command.CANCEL, 1);
	public static final Command CMD_MEDIA_OPEN = new Command(Locale.get("command.media_open"), Command.SCREEN, 1);
	public static final Command CMD_SAVE = new Command(Locale.get("command.save"), Command.SCREEN, 10);

	private static final String RMS_USERDATA = "joey_userdata";
	private static final String RMS_UPLOADS = "joey_uploads";

	private int nextEvent = EVENT_NONE;
	private Displayable currentView;
	private MIDlet midlet;
	private Display display;
	/*private*/ UserData userdata;
	private Vector uploads;
	private RmsStorage storage;
	private CommandListener commandListener;
	/*private*/ CommunicationController commController;
	private Displayable uploadsView;
	private Hashtable pendingUpdates;

	//#if polish.api.mmapi
		// We may only create this once, so we cache and reuse it.
		private SnapshotScreen snapshotScreen;
	//#endif
	

	private TimerTask updateTask = new TimerTask()
	{
		public void run()
		{
			//#debug debug
			System.out.println("Running Timer....");

			long lastModified = (new Date().getTime() / 1000) - JoeyController.this.userdata.getUpdateInterval();
			JoeyController.this.commController.getIndexUpdate(JoeyController.this, lastModified);
			JoeyController.this.userdata.setLastUpdate(lastModified);
		}
	};

	public JoeyController(MIDlet midlet)
	{
		this.midlet = midlet;
		this.display = Display.getDisplay(midlet);
		this.uploads = new Vector();

		loadUserdata();

		this.commandListener = new ThreadedCommandListener(this);
		this.commController = new CommunicationController(this);
		this.commController.start();

		this.uploadsView = null;

		//#if polish.api.mmapi
			this.snapshotScreen = (SnapshotScreen) getView(VIEW_SNAPSHOT);
		//#endif
	}

	public UserData getUserData()
	{
		return this.userdata;
	}

	//@todo we should move this into the userdata class.	
	private void loadUserdata()
	{
		this.storage = new RmsStorage();

		try {
			this.userdata = (UserData) this.storage.read(RMS_USERDATA);

			//#debug info
			System.out.println("Loaded userdata");
		}
		catch (IOException e) {
			//#debug info
			System.out.println("no user data stored in the record store");

			// Create new UserData object not using SSL and an
			// default update interval.
			this.userdata = new UserData("", "", false, DEFAULT_UPDATE_INTERVAL);
		}
	}

	private void saveUserdata()
	{
		try {
			if (this.userdata.isRememberMe()) {
				this.storage.save(this.userdata, RMS_USERDATA);
			}
		}
		catch (IOException e) {
			//#debug error
			System.out.println("unable to write userdata to record store");
		}
	}

	private void loadUploadsFromRMS()
	{
		try {
			this.uploads = (Vector) this.storage.read(RMS_UPLOADS);

			//#debug info
			System.out.println("Loaded upload data");
		}
		catch (IOException e) {
			//#debug info
			System.out.println("no uploads data stored in the record store");

			// Create empty uploads vector.
			this.uploads = new Vector();
		}
	}

	private void saveUploadsToRMS()
	{
		try {
			this.storage.save(this.uploads, RMS_UPLOADS);
		}
		catch (IOException e) {
			//#debug error
			System.out.println("unable to write upload data to record store");
		}
	}

	private void showView(Displayable displayable)
	{
		this.currentView = displayable;
		this.display.setCurrent(this.currentView);
	}

	private Displayable showView(int viewId)
	{
		showView(getView(viewId));
		return this.currentView;
	}

	private Displayable getView(int viewId)
	{
		Displayable view;
		Alert alert;
		Gauge gauge;

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
				if (this.uploadsView == null)
				{
					this.uploadsView = new UploadsView(this);
					this.uploadsView.addCommand(CMD_BACK);
					this.uploadsView.setCommandListener(this.commandListener);
				}
	
				((UploadsView) this.uploadsView).setUploads(this.uploads);
				return this.uploadsView;
	
			case VIEW_PREFERENCES:
				view = new PreferencesView();
				view.addCommand(CMD_BACK);
				view.addCommand(CMD_SAVE);
				view.setCommandListener(this.commandListener);
				return view;
	
			case VIEW_DETAILS:
				view = new DetailsView(this);
				view.addCommand(CMD_BACK);
				view.addCommand(CMD_DELETE);
				view.setCommandListener(this.commandListener);
				return view;
	
			case VIEW_RSS_ITEM:
				DetailsView dv = (DetailsView) this.currentView;
				String description = dv.getDescription();
				view = new RssItemView(description);
				view.addCommand(CMD_BACK);
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
				
			case ALERT_NETWORK_ERROR:
				//#style alertConfirmation
				alert = new Alert(null, Locale.get("alert.network.error"), null, AlertType.ERROR);
				alert.setTimeout(Alert.FOREVER);
				alert.setCommandListener(this.commandListener);
				return alert;
				
			case ALERT_SECURITY_ERROR:
				//#style alertConfirmation
				alert = new Alert(null, Locale.get("alert.security.error"), null, AlertType.ERROR);
				alert.setTimeout(Alert.FOREVER);
				alert.setCommandListener(this.commandListener);
				return alert;
	
			case ALERT_UPLOADS_DELETE_CONFIRMATION:
				//#style alertConfirmation
				alert = new Alert(null, Locale.get("uploads.delete.msg"), null, AlertType.CONFIRMATION);
				alert.setTimeout(Alert.FOREVER);
				alert.addCommand(CMD_YES);
				alert.addCommand(CMD_NO);
				alert.setCommandListener(this.commandListener);
				return alert;
	
			case ALERT_WAIT:
				//#style alertWait
				alert = new Alert(null, null, null, AlertType.INFO);
//				alert = new Alert(null, Locale.get("alert.wait.msg"), null, AlertType.INFO);
				//#style gaugeWait
				gauge = new Gauge(null, false, Gauge.INDEFINITE, Gauge.CONTINUOUS_RUNNING);
				alert.setIndicator(gauge);
				alert.setTimeout(Alert.FOREVER);
				alert.addCommand(CMD_CANCEL);
				alert.setCommandListener(this.commandListener);
				return alert;
	
			case ALERT_MEDIA_OPEN_ERROR:
				//#style alertConfirmation
				alert = new Alert(null, Locale.get("alert.media.open.msg"), null, AlertType.ERROR);
				alert.setTimeout(Alert.FOREVER);
				alert.addCommand(CMD_YES);
				alert.setCommandListener(this.commandListener);
				return alert;
	
			case ALERT_APPLY_UPDATES:
				//#style alertConfirmation
				alert = new Alert(null, Locale.get("alert.apply_updates.msg"), null, AlertType.CONFIRMATION);
				alert.setTimeout(Alert.FOREVER);
				alert.addCommand(CMD_YES);
				alert.addCommand(CMD_NO);
				alert.setCommandListener(this.commandListener);
				return alert;

			case ALERT_NEW_VERSION_AVAILABLE:
				//#style alertConfirmation
				alert = new Alert(null, Locale.get("alert.new_version.msg"), null, AlertType.CONFIRMATION);
				alert.setTimeout(Alert.FOREVER);
				alert.addCommand(CMD_YES);
				alert.addCommand(CMD_NO);
				alert.setCommandListener(this.commandListener);
				return alert;

			case ALERT_ERROR_DISPLAY_DETAILS:
				//#style alertError
				alert = new Alert(null, Locale.get("alert.error.display_details"), null, AlertType.ERROR);
				alert.setTimeout(Alert.FOREVER);
				alert.addCommand(CMD_OK);
				alert.setCommandListener(this.commandListener);
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
				notifyEvent(EVENT_BACK);
				break;
	
			case Command.OK:
				if (command == CMD_YES) {
					notifyEvent(EVENT_YES);
				}
				else {
					notifyEvent(EVENT_OK);
				}
				break;
	
			case Command.CANCEL:
				if (command == CMD_NO) {
					notifyEvent(EVENT_NO);
				}
				else {
					notifyEvent(EVENT_CANCEL);
				}
				break;
	
			case Command.SCREEN:
			case Command.ITEM:
				if (command == CMD_SELECT) {
					notifyEvent(EVENT_SELECT);
				}
				else if (command == CMD_LOGIN) {
					notifyEvent(EVENT_SELECT);
				}
				else if (command == CMD_SAVE) {
					notifyEvent(EVENT_SAVE);
				}
				else if (command == CMD_DELETE) {
					notifyEvent(EVENT_DELETE);
				}
				else if (command == CMD_MEDIA_OPEN) {
					notifyEvent(EVENT_MEDIA_OPEN);
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
		if (request.responseCode == 511) // No Active Session
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
			int numUploads = this.uploads.size();
			IndexNetworkRequest indexRequest = (IndexNetworkRequest) request;
			int totalCount = indexRequest.getTotalCount();

			//#debug info
			System.out.println("IndexNetworkRequest request status: " + request.responseCode + " (" + numUploads + "/" + totalCount + ")");

			if (numUploads < totalCount && numUploads > 0) {
				notifyEvent(EVENT_NETWORK_REQUEST_SUCCESSFUL_PARTIALLY);
				this.commController.getIndex(this, 5, numUploads);
				return;
			}
			
			notifyEvent(request.responseCode == 200 ? EVENT_NETWORK_REQUEST_SUCCESSFUL : EVENT_NETWORK_REQUEST_FAILED);
		}
		else if (request instanceof IndexUpdateNetworkRequest)
		{
			//#debug info
			System.out.println("IndexUpdateNetworkRequest request status: " + request.responseCode);

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
		LoginView view = (LoginView) getView(VIEW_LOGIN);

		do {
			showView(view);
			event = waitEvent();
			switch (event) {
				case EVENT_SELECT:
					showView(ALERT_WAIT);
					view.saveUserData(this.userdata);
	
					// Send login request.
					this.commController.login(this.userdata, this);
	
					// Handle events while wait screen is shown.
					do {
						event = waitEvent();
	
						switch (event) {
							case EVENT_NETWORK_REQUEST_SUCCESSFUL:
								break;
		
							case EVENT_NETWORK_REQUEST_FAILED:
								switch (this.commController.getErrorCode()) {
									case CommunicationController.ERROR_NETWORK:
										showView(ALERT_NETWORK_ERROR);
										break;

									case CommunicationController.ERROR_SECURITY:
										showView(ALERT_SECURITY_ERROR);
										break;

									case CommunicationController.ERROR_LOGIN_DATA:
									default:
										showView(ALERT_LOGIN_ERROR);
										break;
								}
								waitEvent();
								event = EVENT_NONE;
								break;

							case EVENT_CANCEL:
								break;
		
							default:
								event = EVENT_NONE;
								break;
						}
					} while (event != EVENT_NETWORK_REQUEST_SUCCESSFUL
						     && event != EVENT_NETWORK_REQUEST_FAILED
						     && event != EVENT_CANCEL
						     && event != EVENT_NONE);
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
			// Check if there is a newer version available
			String currentVersion = this.commController.getCurrentVersion();

			//#debug debug
			System.out.println("Versioninfo: build version: " + VERSION + " newest version: " + currentVersion);

			if (!VERSION_UNKNOWN.equals(currentVersion)
				&& VERSION.compareTo(currentVersion) < 0) {
				showView(ALERT_NEW_VERSION_AVAILABLE);
				event = waitYesNo();

				if (event == EVENT_YES) {
					String updateUrl = null;
					//#= updateUrl = "${joey.update.url}";

					// Do platform request on update URL.
					try {
						this.midlet.platformRequest(updateUrl);
					}
					catch (ConnectionNotFoundException e) {
						// TODO Auto-generated catch block
						e.printStackTrace();
					}
					
					// Destroy midlet.
					this.midlet.notifyDestroyed();
					return;
				}

				showView(ALERT_WAIT);
			}

			// After a successful login, save the user info and load uploads from RMS.
			saveUserdata();

			// Load all known uploads from RMS.
			loadUploadsFromRMS();

			// Get all updates since the last check.
			this.commController.getIndexUpdate(this, this.userdata.getLastUpdate());
			
			do {
				event = waitEvent();
			} while (event != EVENT_NETWORK_REQUEST_SUCCESSFUL);

			// Start continuous update timer.
			Timer timer = new Timer();
			timer.schedule(this.updateTask, this.userdata.getUpdateInterval() * 1000, this.userdata.getUpdateInterval() * 1000);

			do {
				MainMenuView mainMenu =
					(MainMenuView) showView(VIEW_MAINMENU);

				event = waitEventAndProcessUpdates();

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

			// Stop timer.
			timer.cancel();
		}

		saveUploadsToRMS();
		this.midlet.notifyDestroyed();
	}

	private void doSnapshot()
	{
//#if polish.api.mmapi
		int event;
		showView(this.snapshotScreen);

		do {
			event = waitEventAndProcessUpdates();

			switch (event) {
				case EVENT_SELECT:
					try
					{
						// TODO: Choose the best encoding instead of using the first.
						String[] encodings = SnapshotScreen.getSnapshotEncodings();
						byte[] image = this.snapshotScreen.getSnapshot(encodings[0]);
	
						// TODO: Is this the correct format for time? Is this correct for all locales? 
						long modified = new Date().getTime();
	
						// TODO: Send this to the server, then do an an update.
						Upload upload = new Upload(-1, "image/jpeg", "Camera snapshot", image, image, modified, "");
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
		PreferencesView view = (PreferencesView) showView(VIEW_PREFERENCES);
		view.setUpdateInterval(this.userdata.getUpdateInterval());

		do {
			event = waitEventAndProcessUpdates();

			switch (event) {
				case EVENT_SAVE:
					this.userdata.setUpdateInterval(view.getUpdateInterval());
					break;

				case EVENT_BACK:
					break;
	
				default:
					event = EVENT_NONE;
					break;
			}
		} while (event != EVENT_BACK
				 && event != EVENT_SAVE);
	}

	private int doExitConfirmation()
	{
		showView(ALERT_EXIT_CONFIRMATION);
		return waitYesNo() == EVENT_YES ? EVENT_EXIT : EVENT_NONE;
	}

	public void doUploads()
	{
		int event;

		do {
			UploadsView view = (UploadsView) showView(VIEW_UPLOADS);
			event = waitEventAndProcessUpdates();

			switch (event) {
				case EVENT_SELECT:
					doUploadDetails(view.getCurrentUpload());
					break;
			}
		} while (event != EVENT_BACK);
	}

	private void doUploadDetails(Upload upload)
	{
		int event = EVENT_NONE;
		boolean fetchData = true;

		//#if polish.api.mmapi

		// we need to make a decision here if we should
		// fetch the data before showing the details view.
		// For now, lets assume we can't because we haven't
		// been able to.

		if (upload.getMimetype().equals("video/3gpp")
			|| upload.getMimetype().equals("audio/mpeg")) {
			fetchData = true;
		}
		//#endif

		if (fetchData == true) {
			showView(ALERT_WAIT);
			this.commController.get(upload, this);

			do {
				event = waitEvent();
			} while (event != EVENT_NETWORK_REQUEST_SUCCESSFUL
					 && event != EVENT_NETWORK_REQUEST_FAILED);
		}

		if (event != EVENT_NETWORK_REQUEST_FAILED) {
		do {
			DetailsView view = (DetailsView) showView(VIEW_DETAILS);

			try {
				view.setUpload(upload);
			}
			catch (Throwable t) {
				//#debug debug
				t.printStackTrace();
				
				showView(ALERT_ERROR_DISPLAY_DETAILS);
				waitEvent();
				break;
			}

			event = waitEvent();

			switch (event) {
				case EVENT_RSS_ITEM:
					showView(VIEW_RSS_ITEM);

					while (waitEvent() != EVENT_BACK) {
						//spin
					}

					break;
	
				case EVENT_MEDIA_OPEN:
					try {
						String requestURL = this.commController.getRawMediaURLFor(upload.getId());
						this.midlet.platformRequest(requestURL);
					}
					catch (Exception e) {
						showView(ALERT_MEDIA_OPEN_ERROR);
						waitYesNo();
						event = EVENT_BACK;
					}

					break;
	
				case EVENT_DELETE:
					showView(ALERT_UPLOADS_DELETE_CONFIRMATION);

					if (waitYesNo() == EVENT_YES) {
						showView(ALERT_WAIT);
						this.commController.delete(upload.getId(), this);

						do {
							event = waitEvent();
	
							// Go back to uploads view on successful deletion.
							if (event == EVENT_NETWORK_REQUEST_SUCCESSFUL) {
								event = EVENT_BACK;
							}
						} while (event != EVENT_NETWORK_REQUEST_FAILED
								 && event != EVENT_BACK);
					}

					break;
			}
		} while (event != EVENT_BACK);
		}
		else {
			showView(ALERT_ERROR_DISPLAY_DETAILS);
			waitEvent();
		}
	}

	public int waitOkCancel()
	{
		int event;

		do {
			event = waitEvent();
		} while (event != EVENT_OK && event != EVENT_CANCEL);

		return event;
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

	private int waitEventAndProcessUpdates()
	{
		int event = EVENT_NONE;

		do {
			event = waitEvent();

			if (event == EVENT_UPDATE_AVAILABLE) {
				// Save current view.
				Displayable oldView = this.display.getCurrent();

				showView(ALERT_APPLY_UPDATES);
				event = waitYesNo();

				if (event == EVENT_YES) {
					// Apply pending updates.
					processIndexUpdates(this.pendingUpdates);
				}

				// Forget pending updates.
				this.pendingUpdates = null;

				// Reset old view.
				this.display.setCurrent(oldView);

				// Delete event as processed.
				event = EVENT_NONE;
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

	public synchronized void processIndexUpdates(Hashtable parsedData)
	{
		int count = Integer.parseInt((String) parsedData.get("count"));

		//#debug info
		System.out.println("number of updated elements: " + count);

		for (int i = 1; i <= count; i++) {
			int foundIndex = -1;
			long id = Long.parseLong((String) parsedData.get("id." + i));

			for (int j = 0; j < this.uploads.size(); j++) {
				Upload upload = (Upload) this.uploads.elementAt(j);

				if (id == upload.getId()) {
					foundIndex = j;
					break;
				}
			}

			if (foundIndex != -1) {
				String deleted = (String) parsedData.get("deleted." + i);

				if (deleted != null && deleted.equals("1")) {
					//#debug info
					System.out.println("found deleted element (this is okay): " + id);

					// Delete upload.
					this.uploads.removeElementAt(foundIndex);

					// Continue with next upload.
					continue;
				}
			}

			String referrer = getDataString(parsedData, "referrer." + i);
			String preview = getDataString(parsedData, "preview." + i);
			String mimetype = getDataString(parsedData, "type." + i);
			long modified = 0;

			try {
				// Server sends seconds, we need to handle milliseconds.
				modified = Long.parseLong((getDataString(parsedData, "modified." + i))) * 1000;
			}
			catch (NumberFormatException e) {
				// Ignore. modified variable is set to 0 in this case;
			}

			String title = getDataString(parsedData, "title." + i);

			// Previews are optional.
			byte[] previewBytes = null;

			try {
				if (preview.length() > 0) {
					previewBytes = Base64.decode(preview);
				}
			}
			catch (Exception ex) {
				System.out.println("Base64 decode failed " + ex);
			}

			//#debug info
			System.out.println("Updating upload: " + id + " " + mimetype + " " + title);

			Upload upload = new Upload(id, mimetype, title, previewBytes, null, modified, referrer);

			if (foundIndex == -1) {
				//#debug info
				System.out.println("added new element: " + id);

				this.uploads.addElement(upload);
			}
			else {
				//#debug info
				System.out.println("replace existing element: " + id);

				this.uploads.removeElementAt(foundIndex);
				this.uploads.insertElementAt(upload, foundIndex);
			}
		}
	}

	private String getDataString(Hashtable parsedData, String name)
	{
		String str = (String) parsedData.get(name);

		if (str == null) {
			str = "";
		}

		return str;
	}
}
