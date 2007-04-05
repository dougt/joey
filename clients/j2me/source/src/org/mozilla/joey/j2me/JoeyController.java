package org.mozilla.joey.j2me;

import de.enough.polish.util.Locale;

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
import org.mozilla.joey.j2me.views.UploadsView;

//#if polish.api.mmapi
import de.enough.polish.ui.SnapshotScreen;
//#endif

import java.util.Hashtable;

public class JoeyController
	implements CommandListener, ItemCommandListener, ResponseHandler
{
	private static final int VIEW_LOGIN = 1;
	private static final int VIEW_MAINMENU = 2;
	private static final int VIEW_SNAPSHOT = 3;
	private static final int VIEW_UPLOADS = 4;
	
	private static final Command CMD_EXIT = new Command(Locale.get("command.exit"), Command.EXIT, 1);
//	private static final Command CMD_SELECT = new Command(Locale.get("command.select"), Command.SCREEN, 1);
	private static final Command CMD_SELECT = List.SELECT_COMMAND;
	private static final Command CMD_BACK = new Command(Locale.get("command.back"), Command.BACK, 1);
	private static final Command CMD_LOGIN = new Command(Locale.get("command.login"), Command.SCREEN, 1);

	private int currentViewId;
	private Displayable currentView;
	private MIDlet midlet;
	private Display display;
	private CommunicationController downloadThread;

	public JoeyController(MIDlet midlet)
	{
		this.midlet = midlet;
		this.display = Display.getDisplay(midlet);
		this.downloadThread = new CommunicationController();
		this.downloadThread.setResponseHandler(this);
		this.downloadThread.start();
	}
	
	public void startApp()
	{
		showView(VIEW_LOGIN);
	}

	private void showView(int viewId)
	{
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
			view = new LoginView();
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
			view = new UploadsView();
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
			this.midlet.notifyDestroyed();
			handled = true;
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
			handled = processCommandUploads(command);
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
		
		return false;
	}

	private boolean processCommandMainMenu(Command command)
	{
		System.out.println("processCommandMainMenu 1");
		if (command == CMD_SELECT) {
			switch (((MainMenuView) this.currentView).getCurrentIndex()) {
				case 0:
					showView(VIEW_UPLOADS);
					break;

				case 1:
					showView(VIEW_SNAPSHOT);
					break;
			}

			return true;
		}
		System.out.println("processCommandMainMenu 2");
		
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