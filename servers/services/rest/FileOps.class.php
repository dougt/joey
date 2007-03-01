<?php

class FileOps {
    
  var $randname;
  var $type;
  var $filedir;
  var $filename;
  var $thumbnailname;

  var $fileTypes = array ('image/jpeg' => '.jpg',
                          'image/gif' => '.gif',
                          'image/png' => '.png',
                          'video/mpeg' => '.mpg',
                          'audio/mpeg' => '.mp3',
                          'audio/x-wav' => '.wav');
    
  function FileOps ($userid) {
    $this->randname = uniqid();
    $this->filedir = '/data/uploads/' . $userid . '/';
    
    if (!file_exists($this->filedir)) {
      mkdir ($this->filedir);
    }
  }
  
  function saveFile ($type, $data) {
    $this->type = $type;
    $this->filename = $this->filedir . $this->randname . $this->fileTypes[$this->type];
    $fh = fopen($this->filename, 'w') or die("can't open file");
    fwrite($fh, $data);
    fclose($fh);
    return $this->filename;
  }

  function getFile ($filename) {
    $fh = fopen($filename, 'r') or die("can't open file");
    $data = fread($fh, filesize($filename));
    fclose($fh);
    return $data;
  }
  
  function generateThumbnail ($type) {
    $this->thumbnailname = $this->filedir . 'thumbnail-' . $this->randname . $this->fileTypes[$this->type];
    
    // Just copy it for now
    $fh1 = fopen($this->filename, 'r') or die("can't open file");
    $contents = fread($fh1, filesize($this->filename));
    fclose($fh1);
    $fh2 = fopen($this->thumbnailname, 'w') or die("can't open file");
    fwrite($fh2, $contents);
    fclose($fh2);
    
    return $this->thumbnailname;
    
  }
  
  function isFile ($type) {
    foreach ($this->fileTypes as $mimeType=>$fileSuffix) {
      if ($type == $mimeType) {
        return true;
      }
    }
    return false;
  }
    
}
?>    
