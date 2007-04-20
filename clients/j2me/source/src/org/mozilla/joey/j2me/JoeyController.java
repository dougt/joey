package org.mozilla.joey.j2me;

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
import javax.microedition.midlet.MIDlet;

import org.mozilla.joey.j2me.views.LoginView;
import org.mozilla.joey.j2me.views.MainMenuView;
import org.mozilla.joey.j2me.views.PreferencesView;
import org.mozilla.joey.j2me.views.UploadsView;

//#if polish.api.mmapi
import de.enough.polish.ui.SnapshotScreen;
import de.enough.polish.ui.UiAccess;
//#endif

import java.io.IOException;
import java.util.Hashtable;
import java.util.Vector;

public class JoeyController
	implements CommandListener, ItemCommandListener, ResponseHandler
{
	private static final int VIEW_LOGIN = 1;
	private static final int VIEW_MAINMENU = 2;
	private static final int VIEW_PREFERENCES = 3;
	private static final int VIEW_SNAPSHOT = 4;
	private static final int VIEW_UPLOADS = 5;

	private static final int ALERT_WAIT = 6;
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

		this.commController = new CommunicationController();
		this.commController.setResponseHandler(this);
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
			view.setCommandListener(this);
			return view;
		
		case VIEW_MAINMENU:
			view = new MainMenuView();
			view.addCommand(CMD_EXIT);
			view.addCommand(CMD_SELECT);
			view.setCommandListener(this);
			return view;
		
		//#if polish.api.mmapi
		case VIEW_SNAPSHOT:
			//#style snapshotScreen
			view = new SnapshotScreen(Locale.get("title.snapshot"));
			view.addCommand(CMD_BACK);
			view.setCommandListener(this);
			return view;
		//#endif
			
		case VIEW_UPLOADS:
			view = new UploadsView(this, this.uploads);
			view.addCommand(CMD_BACK);
			view.setCommandListener(this);
			return view;

		case VIEW_PREFERENCES:
			view = new PreferencesView();
			view.addCommand(CMD_BACK);
			view.setCommandListener(this);
			return view;

		case ALERT_EXIT_CONFIRMATION:
			//#style alertConfirmation
			alert = new Alert(null, Locale.get("alert.exit.msg"), null, AlertType.CONFIRMATION);
			alert.setTimeout(Alert.FOREVER);
			alert.addCommand(CMD_YES);
			alert.addCommand(CMD_NO);
			alert.setCommandListener(this);
			return alert;

		case ALERT_LOGIN_ERROR:
			//#style alertConfirmation
			alert = new Alert(null, Locale.get("alert.login.error"), null, AlertType.ERROR);
			alert.setTimeout(Alert.FOREVER);
			alert.setCommandListener(this);
			return alert;
		
		case ALERT_UPLOADS_DELETE_CONFIRMATION:
			//#style alertConfirmation
			alert = new Alert( null, Locale.get("uploads.delete.msg"), null, AlertType.CONFIRMATION );
			alert.setTimeout(Alert.FOREVER);
			alert.addCommand(CMD_YES);
			alert.addCommand(CMD_NO);
			alert.setCommandListener(this);
			return alert;

		case ALERT_WAIT:
			//#style waitAlert
			alert = new Alert( null, Locale.get("alert.wait.msg"), null, AlertType.INFO );
			alert.setTimeout(Alert.FOREVER);
			alert.setCommandListener(this);
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
		
		return false;
	}

	private boolean processCommandUploads(Command command, Item item)
	{
		if (command == CMD_BACK) {
			showView(VIEW_MAINMENU);
			return true;
		}
		else if (command == CMD_SELECT) {
			// TODO: Do something depending on mimetype here.
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
			this.commController.delete(this.focusedUpload.getId());
			this.focusedUpload = null;
			showView(VIEW_UPLOADS);
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
					showView(VIEW_UPLOADS);
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

			if (this.commController.login(this.userdata)) {
				this.commController.getIndex(this.uploads);
				showView(VIEW_MAINMENU);
			}
			else {
				showView(ALERT_LOGIN_ERROR);
			}
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

	/* (non-Javadoc)
	 * @see org.mozilla.joey.j2me.ResponseHandler#notifyResponse(java.util.Hashtable)
	 */
	public void notifyResponse(Hashtable response)
	{
		// TODO: Do something.
	}
}
