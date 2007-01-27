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


<?
session_start();

require_once 'XML/RPC/Server.php';

function ffm_authenticate($params) {
  
    $val = $params->getParam(0);
    $username = $val->getval();

    $val = $params->getParam(1);
    $password = $val->getval();

    $password=sha1($password);
  
    //connect to db
    include('config.php');

    $userid = -1;

    $query="select id from user where uname='$username' and pw='$password' ";
    $result=mysql_query($query);
    if($fetched = mysql_fetch_array($result))
    {
      $userid = $fetched['id'];
      $_SESSION['userid'] = $userid;

      return new XML_RPC_Response(new XML_RPC_Value($userid, 'int'));
    }

    // How do we localized this?  Do we need to?
    return new XML_RPC_Response('', -1, "Authentication Failed");
}

function ffm_logout($params) {

    if (empty($_SESSION['userid']))
    {
      return new XML_RPC_Response('', -1, "Not Authenticated");
    }
    $userid = $_SESSION['userid'];

  
    $val = $params->getParam(0);
    $session = $val->getval();
    
    unset($_SESSION['userid']);

    return new XML_RPC_Response(new XML_RPC_Value(0, 'int'));
}


function ffm_upload($params) {

    if (empty($_SESSION['userid']))
    {
      return new XML_RPC_Response('', -1, "Not Authenticated");
    }
    $userid = $_SESSION['userid'];

    $val = $params->getParam(0);
    $name = $val->getval();

    $val = $params->getParam(1);
    $title = $val->getval();

    $val = $params->getParam(2);
    $uri = $val->getval();

    $val = $params->getParam(3);
    $data = $val->getval();

    $val = $params->getParam(4);
    $size = $val->getval();

    $val = $params->getParam(5);
    $type = $val->getval();

    // optionally??
    $val = $params->getParam(6);
    $uuid = $val->getval();

    //connect to db
    include('config.php');
    

    if (!empty($uuid))
    {
	$query = "DELETE FROM upload WHERE " .
		 "owner='$userid' AND uuid='$uuid' AND uri='$uri'";
	mysql_query($query);
    }
    else
    {
	$uuid = "";
    }

    $query = "INSERT INTO upload " .
             "(owner, name, type, uuid, uri, title, size, content, thumbnail, date_created, shared ) ".
             "VALUES " .
             "('$userid', '$name', '$type', '$uuid', '$uri', '$title', '$size', '$data', '', NOW(), 'yes')";

    $result = mysql_query($query);

    if (!$result)
    {
      return new XML_RPC_Response('', -1, "Upload failed.");
    }

    return new XML_RPC_Response(new XML_RPC_Value(mysql_insert_id(), 'int'));
}

function ffm_getCount($params) {

    if (empty($_SESSION['userid']))
    {
      return new XML_RPC_Response('', -1, "Authentication Failed");
    }
    $userid = $_SESSION['userid'];

    //connect to db
    include('config.php');

    $query = "SELECT COUNT( * ) AS num FROM upload where owner = '$userid' ";
    $result = mysql_query($query) or die('Error, query failed'  . mysql_error());
    $value = mysql_result($result,0,"num");

    return new XML_RPC_Response(new XML_RPC_Value($value, 'int'));
}

function ffm_enumerate($params) {

    if (empty($_SESSION['userid']))
    {
      return new XML_RPC_Response('', -1, "Authentication Failed");
    }
    $userid = $_SESSION['userid'];

    $val = $params->getParam(0);
    $order = $val->getval(); // unused

    $val = $params->getParam(1);
    $first = $val->getval();

    $val = $params->getParam(2);
    $last = $val->getval();

    $val = $params->getParam(3);
    $sendData = $val->getval();

    //connect to db
    include('config.php');

    $query = "SELECT owner, id, name, uri, title, date_created, type, content " .
             "FROM upload " .
             "WHERE owner='$userid'";
    // . "LIMIT '$first' , '$last' ";


    $result = mysql_query($query) or die('Error, query failed'  . mysql_error());

    $val = new XML_RPC_Value();
    $loot=array();

    $i=0;

    while($res = mysql_fetch_array($result)) 
    {
      if ($sendData)  // I wonder if there is a cleaner way to do this?
      {
      $loot[$i]=new XML_RPC_Value(array("id" => new XML_RPC_Value("$res[id]"),
                                        "name" => new XML_RPC_Value("$res[name]"),
                                        "uri" => new XML_RPC_Value("$res[uri]"),
                                        "title" => new XML_RPC_Value("$res[title]"),
                                        "date_created" => new XML_RPC_Value("$res[date_created]"),
                                        "type" => new XML_RPC_Value("$res[type]"),
                                        "content" => new XML_RPC_Value("$res[content]")),
                                  "struct");
      }
      else
      {
      $loot[$i]=new XML_RPC_Value(array("id" => new XML_RPC_Value("$res[id]"),
                                        "name" => new XML_RPC_Value("$res[name]"),
                                        "uri" => new XML_RPC_Value("$res[uri]"),
                                        "title" => new XML_RPC_Value("$res[title]"),
                                        "date_created" => new XML_RPC_Value("$res[date_created]"),
                                        "type" => new XML_RPC_Value("$res[type]")),
                                  "struct");
      }
      $i++;
    }

    $val->addArray($loot);

    return new XML_RPC_Response($val);

}


// -----------------------------------------------------
// ffm_upload
//
//  Params:
//    name
//    title
//    uri
//    data
//    size
//    type
//
//  Returns:
//    result code.
// -----------------------------------------------------

// -----------------------------------------------------
// ffm_enumeratorNext
//
//  Params:
//    first
//    last
//
//  Returns:
//    entries
// -----------------------------------------------------


$functions = array('ffmobile.authenticate' => array('function' => 'ffm_authenticate' ),
                   'ffmobile.logout' => array('function' => 'ffm_logout' ),
                   'ffmobile.upload' => array('function' => 'ffm_upload' ),
                   'ffmobile.getCount' => array('function' => 'ffm_getCount' ),
                   'ffmobile.enumerate' => array('function' => 'ffm_enumerate' ),
                   );
                             
$server = new XML_RPC_Server($functions, 1);

?>
