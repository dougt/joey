<?php

class FileOps {
    
  var $randname;
  var $type;
  var $filedir;
  var $filename;
  var $thumbnailname;
  var $convert = '/usr/bin/convert';

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
    $orgfilename = $this->filedir . $this->randname . $this->fileTypes[$this->type];
    $fh = fopen($orgfilename, 'w') or die("can't open file");
    fwrite($fh, $data);
    fclose($fh);

    $this->filename = $this->filedir . $this->randname . '.png';
    if (strcmp($this->type, 'image/png') != 0) {
      $command = "$this->convert '$orgfilename' '$this->filename'";
      exec($command, $returnarray, $returnvalue);
      // unlink($orgfilename);
    }

    return $this->filename;
  }

  function getFile ($filename) {
    $fh = fopen($filename, 'r') or die("can't open file");
    $data = fread($fh, filesize($filename));
    fclose($fh);
    return $data;
  }
  
  function generateThumbnail ($type) {

    $this->thumbnailname = $this->filedir . 'thumbnail-' . $this->randname . '.png';

    $command = "$this->convert -geometry '100x100' '$this->filename' '$this->thumbnailname'";
    exec($command, $returnarray, $returnvalue);
    
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
