<?php

session_start();

require_once 'XML/RPC/Server.php';

function ffm_authenticate($params) {
  
    $val = $params->getParam(0);
    $username = $val->getval();
    $username = mysql_real_escape_string($username);
    $username = strtolower($username);

    $val = $params->getParam(1);
    $password = $val->getval();
    $password = mysql_real_escape_string($password);

    $password=sha1($password);
  
    //connect to db
    include('config.php');

    $userid = -1;

    $query="select id from users where email='$username' and password='$password' ";
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
    $name = mysql_real_escape_string($name);

    $val = $params->getParam(1);
    $title = $val->getval();
    $title = mysql_real_escape_string($title);

    $val = $params->getParam(2);
    $uri = $val->getval();
    $uri = mysql_real_escape_string($uri);

    $val = $params->getParam(3);
    $data = $val->getval();
    $data = mysql_real_escape_string($data);

    $val = $params->getParam(4);
    $size = $val->getval();
    $size = mysql_real_escape_string($size);

    $val = $params->getParam(5);
    $type = $val->getval();
    $type = mysql_real_escape_string($type);

    // optionally??
    $val = $params->getParam(6);
    $uuid = $val->getval();
    $uuid = mysql_real_escape_string($uuid);

    //connect to db
    include('config.php');
    

    if (!empty($uuid))
    {
	$query = "DELETE FROM uploads WHERE " .
		 "owner='$userid' AND uuid='$uuid' AND uri='$uri'";
	mysql_query($query);
    }
    else
    {
	$uuid = "";
    }

    $query = "INSERT INTO uploads " .
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

    $query = "SELECT COUNT( * ) AS num FROM uploads where owner = '$userid' ";
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
    $first = mysql_real_escape_string($first);

    $val = $params->getParam(2);
    $last = $val->getval();
    $last = mysql_real_escape_string($last);

    $val = $params->getParam(3);
    $sendData = $val->getval();
    
    //connect to db
    include('config.php');

    $query = "SELECT owner, id, name, uri, title, date_created, type, content " .
             "FROM uploads " .
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
