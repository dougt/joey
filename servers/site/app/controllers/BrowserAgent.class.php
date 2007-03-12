<?php
class BrowserAgent {

  function isMobile () {
    $agent=$_SERVER["HTTP_USER_AGENT"];

    // If mobile phone, see http://www.developershome.com/wap/detection/detection.asp?page=userAgentHeader
    $res = preg_match('/nokia|motorola|mot\-|samsung|sec\-|lg\-|sonyericsson|sie\-|up\.b|up\//i', $agent);

    return $res;
  }

}
?>
