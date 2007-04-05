package org.mozilla.joey.j2me;

import javax.microedition.midlet.MIDlet;
import javax.microedition.midlet.MIDletStateChangeException;

public class JoeyMIDlet extends MIDlet
{
     protected void startApp()
     	throws MIDletStateChangeException
     {
    	 JoeyController controller = new JoeyController(this);
    	 controller.startApp();
     }

     protected void pauseApp()
     {
          // TODO: Implement this method.
     }

     protected void destroyApp(boolean unconditional)
     	throws MIDletStateChangeException
     {
          // TODO: Implement this method.
     }
}