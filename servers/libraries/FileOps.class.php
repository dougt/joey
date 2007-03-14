<?php

class FileOps {
    
  var $randname;
  var $type;
  var $filedir;
  // var $filepath;
  var $filename;
  var $thumbnailname;
  var $convert = '/usr/bin/convert';
  var $ffmpeg = '/home/dougt/ffmpeg/ffmpeg/ffmpeg';

  var $fileTypes = array ('image/jpeg' => '.jpg',
                          'image/gif' => '.gif',
                          'image/png' => '.png',
                          'video/mpeg' => '.mpg',
                          'video/flv' => '.flv',
                          'audio/mpeg' => '.mp3',
                          'audio/x-wav' => '.wav');
    
  function FileOps ($userid) {
    $this->randname = uniqid();
    $this->filedir = '/data/uploads/' . $userid . '/';
    // $this->filepath = '/fxmobile/libraries/data/uploads/' . $userid . '/';
    
    if (!file_exists($this->filedir)) {
      mkdir ($this->filedir);
    }
  }

  // function getFilepath () {
  //   return $this->filepath;
  // }
  
  function moveFile ($type, $file) {
    
    $this->type = $type;
    $this->filename = $this->filedir . $this->randname . $this->fileTypes[$this->type];
    
    if(!move_uploaded_file($file, $this->filename))
    {
      echo "There was a problem when uploding!";
      print_r($_FILES);
      die("can't open file");
    }
    
    if ((strcasecmp($this->type, 'image/png') != 0) && (strncasecmp($this->type, 'image', 5) == 0)) {
      $orgfilename = $this->filename;
      $this->filename = $this->filedir . $this->randname . '.png';
      $command = "$this->convert '$orgfilename' '$this->filename'";
      exec($command, $returnarray, $returnvalue);
      // unlink($orgfilename);
    }


    if (! strcasecmp($this->type, 'video/flv'))
    {
      $orgfilename = $this->filename;
      $this->filename = $this->filedir . $this->randname . '.3gp';

      // we need to figure out the right values here.  This sorta works, but it not optimal.
      // ffmpeg -i video.flv -ab 32 -ac 1 -ar 8000 -vcodec h263 -s qcif -r 10 video.3gp

      $command = "$this->ffmpeg -y -i " . $orgfilename ." -ab 32 -ac 1 -ar 8000 -vcodec h263 -s qcif -r 10 " . $this->filename;

      exec($command, $returnarray, $returnvalue);


      // unlink ($orgfilename);
    }
    
    return basename($this->filename);
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

    return basename($this->filename);
  }

  function getFile ($basename) {
    $filename = $this->filedir . $basename;
    $fh = fopen($filename, 'r') or die("can't open file");
    $data = fread($fh, filesize($filename));
    fclose($fh);
    return $data;
  }
  
  function getFilename ($basename) {
    $filename = $this->filedir . $basename;
    return $filename;
  }


  // $thumbnail name is '' if the file type is not image or video
  function generateThumbnail () {
    if (strncasecmp($this->type, 'image', 5) == 0) {
      $this->thumbnailname = $this->filedir . 'thumbnail-' . $this->randname . '.png';

      $command = "$this->convert -geometry '100x100' '$this->filename' '$this->thumbnailname'";
      exec($command, $returnarray, $returnvalue);
    
      return basename($this->thumbnailname);
    } 
    elseif (strncasecmp($this->type, 'video', 5) == 0) 
    {
      $this->thumbnailname = $this->filedir . 'thumbnail-' . $this->randname . '.png';

      $command = "$this->ffmpeg -i " . $this->filename ." -ss 5 -s 100x100 -vframes 1 -f mjpeg " . $this->thumbnailname;

      exec($command, $returnarray, $returnvalue);
    
      return basename($this->thumbnailname);
    } 
    else {
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
