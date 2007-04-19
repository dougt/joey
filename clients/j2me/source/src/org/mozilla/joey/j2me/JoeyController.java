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
//#endif

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
	
	private static final Command CMD_EXIT = new Command(Locale.get("command.exit"), Command.EXIT, 1);
//	private static final Command CMD_SELECT = new Command(Locale.get("command.select"), Command.SCREEN, 1);
	private static final Command CMD_SELECT = List.SELECT_COMMAND;
	private static final Command CMD_BACK = new Command(Locale.get("command.back"), Command.BACK, 1);
	public static final Command CMD_LOGIN = new Command(Locale.get("command.login"), Command.SCREEN, 1);
	private static final Command CMD_DELETE = new Command(Locale.get("command.delete"), Command.SCREEN, 1);

	private static final Command CMD_YES = new Command(Locale.get("command.yes"), Command.SCREEN, 1);
	private static final Command CMD_NO = new Command(Locale.get("command.no"), Command.BACK, 1);

	private int prevViewId;
	private int currentViewId;
	private Displayable currentView;
	private MIDlet midlet;
	private Display display;
	private Vector uploads;
	private CommunicationController downloadThread;

	public JoeyController(MIDlet midlet)
	{
		this.midlet = midlet;
		this.display = Display.getDisplay(midlet);
		this.uploads = new Vector();
		this.downloadThread = new CommunicationController();
		this.downloadThread.setResponseHandler(this);
		this.downloadThread.start();

		this.uploads.addElement(new Upload("Test 1", null, null));
		this.uploads.addElement(new Upload("Test 2", null, null));
		this.uploads.addElement(new Upload("Test 3", null, null));
		this.uploads.addElement(new Upload("Test 4", null, null));
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
		
		switch (viewId)
		{
		case VIEW_LOGIN:
			view = new LoginView(this);
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
			view = new UploadsView(this.uploads);
			view.addCommand(CMD_BACK);
			view.addCommand(CMD_SELECT);
			view.addCommand(CMD_DELETE);
			view.setCommandListener(this);
			return view;

		case VIEW_PREFERENCES:
			view = new PreferencesView();
			view.addCommand(CMD_BACK);
			view.setCommandListener(this);
			return view;

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
			showExitAlert();
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
			handled = processCommandUploads(command);
			break;

		case VIEW_PREFERENCES:
			handled = processCommandPreferences(command);
			break;

		case ALERT_EXIT_CONFIRMATION:
			if (command == CMD_YES) {
				this.midlet.notifyDestroyed();
			}
			else {
				showView(this.prevViewId);
			}
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

	private boolean processCommandUploads(Command command)
	{
		if (command == CMD_BACK) {
			showView(VIEW_MAINMENU);
			return true;
		}
		else if (command == CMD_DELETE) {
			showUploadDeleteAlert();
			return true;
		}
		else if (command == CMD_YES) {
			// TODO: Show wait alert here while deletion is happening. 
			//showWaitAlert();
			this.downloadThread.delete(((List) this.currentView).getSelectedIndex());
			showView(VIEW_UPLOADS);
			return true;
		}
		else if (command == CMD_NO) {
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
		if (command == CMD_LOGIN) {
			showView(VIEW_MAINMENU);
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
	
	private void showUploadDeleteAlert()
	{
		//#style alertConfirmation
		Alert alert = new Alert( null, Locale.get("uploads.delete.msg"), null, AlertType.CONFIRMATION );
		alert.setTimeout(Alert.FOREVER);
		alert.addCommand(CMD_YES);
		alert.addCommand(CMD_NO);
		alert.setCommandListener(this);
		this.display.setCurrent(alert);

		this.prevViewId = this.currentViewId;
		this.currentViewId = ALERT_UPLOADS_DELETE_CONFIRMATION;
	}

	private void showExitAlert()
	{
		//#style alertConfirmation
		Alert alert = new Alert(null, Locale.get("alert.exit.msg"), null, AlertType.CONFIRMATION);
		alert.setTimeout(Alert.FOREVER);
		alert.addCommand(CMD_YES);
		alert.addCommand(CMD_NO);
		alert.setCommandListener(this);
		this.display.setCurrent(alert);

		this.prevViewId = this.currentViewId;
		this.currentViewId = ALERT_EXIT_CONFIRMATION;
	}

	private void showWaitAlert()
	{
		//#style waitAlert
		Alert alert = new Alert( null, Locale.get("alert.wait.msg"), null, AlertType.INFO );
		alert.setTimeout(Alert.FOREVER);
		alert.setCommandListener(this);
		this.display.setCurrent(alert);

		this.prevViewId = this.currentViewId;
		this.currentViewId = ALERT_WAIT;
	}
}
