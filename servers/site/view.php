/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
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
 * The Original Code is Joey Mozilla Project.
 *
 * The Initial Developer of the Original Code is
 * Doug Turner <dougt@meer.net>.
 * Portions created by the Initial Developer are Copyright (C) 2007
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */
 
 <?php

session_start();

$userid = $_SESSION['userid'];

if (empty($userid)){
  header("location:login.php");
  exit;
}


    $id = $_GET['id'];
    $doom = $_GET['doom'];

//  $type = $_GET['type']; //ddd

    // some basic sanity checks
    if(isset($id) && is_numeric($id))
    {
      include 'config.php';

      // Are we going to doom this entry?
      if(isset($doom))
      {
        
        $sql = "DELETE from upload where id='$id' and owner='$userid'";
        
        $result = mysql_query("$sql");
        
        if ($result)
        {
          echo "Item was removed.";
        }
        else
        {
          echo "Item could not be removed.";
        }
        
	echo "<FORM><INPUT type='button' name='back' value='Click to go back' onClick='history.back()'></FORM>";

	return;
      }
      else
      {

        // we are viewing an item.

        if (isset($type) && $type == "thumb")
          $use_thumb = true;
        else
          $use_thumb = false;
        
        $use_thumb = false; // fix!
        
        if ($use_thumb)
          $sql = "SELECT type, thumbnail FROM upload WHERE id='$id' and owner='$userid'";
        else
          $sql = "SELECT type, content FROM upload WHERE id='$id' and owner='$userid'";
        
        // the result of the query
        $result = mysql_query("$sql") or die("Invalid query: " . mysql_error());
        
        if(mysql_num_rows($result) == 0)
        {
          echo "Item does not exist<br>";
        }
        else
        {
 
          $fetched= mysql_fetch_array($result);
          
          if ($fetched['type'] == "microsummary/xml")
          {         
          	header("Content-type: text/plain");
		echo base64_decode($fetched['content']);
          }
	  else
          {
	  	$contenttype = "Content-type: " . $fetched['type']; 
          	header($contenttype);
          
          	if ($use_thumb)
            	    echo base64_decode($fetched['thumbnail']);
          	else
        	    echo base64_decode($fetched['content']);
          }
        }
      }
    }
    else
    {
      echo 'Please use a real id number';
    }
?>
