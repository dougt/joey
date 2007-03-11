<?php
class BrowserAgent {

  function isMobile () {
    $agent=$_SERVER["HTTP_USER_AGENT"];
    return preg_match('/nokia/i', $agent);
  }

}
?>
