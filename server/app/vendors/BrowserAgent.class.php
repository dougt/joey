<?php
class BrowserAgent {

  function isIPhone () {

    if(isset($_SERVER["HTTP_USER_AGENT"]))
    {
      $agent=$_SERVER["HTTP_USER_AGENT"];

      $res = preg_match('/iPhone/i', $agent);
      
      return $res;
    }
    return false;
  }

  function isMobile () {
    if(isset($_SERVER["HTTP_USER_AGENT"]))
    {
      $agent=$_SERVER["HTTP_USER_AGENT"];
      
      // If mobile phone, see http://www.developershome.com/wap/detection/detection.asp?page=userAgentHeader
      $res = preg_match('/nokia|symbian|motorola|mot\-|samsung|sec\-|lg\-|sonyericsson|sie\-|up\.b|up\//i', $agent);
      
      return $res;
    }
    return false;
  }
}
?>
