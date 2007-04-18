package org.mozilla.joey.j2me;

import de.enough.polish.io.RedirectHttpConnection;

import java.io.DataInputStream;
import java.io.DataOutputStream;
import java.io.IOException;

import javax.microedition.io.Connector;
import javax.microedition.io.HttpConnection;
import javax.microedition.midlet.MIDlet;
import javax.microedition.midlet.MIDletStateChangeException;

public class JoeyMIDlet extends MIDlet
{
     protected void startApp()
     	throws MIDletStateChangeException
     {
//    	 try
//    	 {
////    		 HttpConnection c = (HttpConnection) Connector.open( "http://michaelyuan.com/fxmobile/uploads/index", Connector.READ_WRITE );
//	    	 HttpConnection c = (HttpConnection) Connector.open( "http://joey.labs.mozilla.com/uploads/index", Connector.READ_WRITE );
////    		 HttpConnection c = new RedirectHttpConnection( "http://michaelyuan.com/fxmobile/uploads/index" );
//    		 c.setRequestMethod( HttpConnection.POST );
//    		 DataOutputStream out = c.openDataOutputStream();
//    		 
//    		 out.write( "rest=1".getBytes() );
//    		 // send request and read return values:
//    		 DataInputStream in = c.openDataInputStream();
//    		 int status = c.getResponseCode();
//    		 
//    		 System.err.println("Michael: first post: " + status);
//    	 }
//    	 catch (IOException e)
//    	 {	
//    		 // TODO Auto-generated catch block
//    		 e.printStackTrace();
//    	 }
    	 
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