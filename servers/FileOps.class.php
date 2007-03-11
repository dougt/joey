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
    $this->filename = $this->filedir . $this->randname . $this->fileTypes[$this->type];
    $fh = fopen($this->filename, 'w') or die("can't open file");
    fwrite($fh, $data);
    fclose($fh);

    if ((strcasecmp($this->type, 'image/png') != 0) && (strncasecmp($this->type, 'image', 5) == 0)) {
      $orgfilename = $this->filename;
      $this->filename = $this->filedir . $this->randname . '.png';
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
  
  // $thumbnail name is '' if the file type is not image or video
  function generateThumbnail () {
    if (strncasecmp($this->type, 'image', 5) == 0) {
      $this->thumbnailname = $this->filedir . 'thumbnail-' . $this->randname . '.png';

      $command = "$this->convert -geometry '100x100' '$this->filename' '$this->thumbnailname'";
      exec($command, $returnarray, $returnvalue);
    
      return $this->thumbnailname;
    } elseif (strncasecmp($this->type, 'video', 5) == 0) {
      // TODO: add video support
      return '';
    } else {
      return '';
    }
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
