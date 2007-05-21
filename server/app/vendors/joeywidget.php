<?php

class joeywidget {

  var $preview_url;
  var $content_url;

  var $content;
  var $preview;

  function load($url)
  {
    $this->preview_url = null;
    $this->content_url = null;

    $widget_content = $this->fetch($url);

    $do = preg_match("/preview_url:[ \t]*(.*)/", $widget_content, $matches);
    if ($do == true) {
        $this->preview_url = $matches['1'];
    }

    $do = preg_match("/content_url:[ \t]*(.*)/", $widget_content, $matches);
    if ($do == true) {
        $this->content_url = $matches['1'];
    }

    // go fetch the preview
    $this->preview = $this->fetch($this->preview_url);

    // go fetch the content
    $this->content = $this->fetch($this->content_url);
    
  }

  // curl utility function
  function fetch($url) {
    $ch = curl_init();
    curl_setopt ($ch, CURLOPT_USERAGENT, "MobiViewer 1.0");
    curl_setopt($ch, CURLOPT_URL,$url); // set url to post to
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-Charset:utf-8')); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
      return false;
    }
    curl_close($ch);
    
    return $result;
  }

}


?>