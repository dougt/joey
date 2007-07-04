<?php
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
 *
 * The Initial Developer of the Original Code is
 * The Mozilla Foundation.
 * Portions created by the Initial Developer are Copyright (C) 2006
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *   Wil Clouser <clouserw@mozilla.com>
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


vendor('microsummary');
vendor('joeywidget');
vendor('magpierss/rss_fetch.inc');
/**
 * Some mildly associated functions for storing files on the disk.  Maybe there is a
 * better place for this?
 */
class StorageComponent extends Object
{
  
  var $suffix = array ("text/plain" => "txt",
                       "image/png" => "png",
                       "image/jpeg" => "jpg",
                       "image/gif" => "gif",
                       "image/tiff" => "tif",
                       "image/bmp" => "bmp",
                       "video/3gpp" => "3gp",
                       "video/flv" => "flv",
                       "video/mpeg" => "mpg",
                       "video/avi" => "avi",
                       "video/quicktime" => "mov",
                       "audio/x-wav" => "wav",
                       "audio/mpeg" => "mp3",
                       "audio/mid" => "mid",
                       "rss-source/text" => "rss",
                       "microsummary/xml" => "mcs",
                       "widget/joey" => "jwt");
  
  /**
   * Save a reference to the controller on startup
   * @param object &$controller the controller using this component
   */
  function startup(&$controller) {
    $this->controller =& $controller;
  }
  
  /**
   * Check to see if the user has available space for the
   * additional content.
   * @param int user id
   * @param int size (in bytes) of requested space
   */
  
  function hasAvailableSpace($userid, $additional) {
    
    $totalused = $this->controller->User->totalSpaceUsedByUserId($userid);
    // $additional and $totalused is in bytes, MAX_DISK_USAGE is in MB
    if ( ($additional + $totalused) > (MAX_DISK_USAGE * 1024 * 1024)) {
      $this->log($userid . " hasAvailableSpace failed");
      return false;
    }
    return true;
  }
  
  /**
   * @return mixed true on success, false on failure
   */
  function createFileForUploadId($id, $type) {
    
    if (!is_numeric($id)) { return false; }
    if (empty($type))     { return false; }
    
    $rand = uniqid();
    
    $_filename    = "joey-{$rand}.{$this->suffix[$type]}";
    $_previewname = "joey-{$rand}.png";
    
    $_file = new File();
    $_file->set('upload_id', $id);
    $_file->set('name', basename($_filename));
    $_file->set('size', 0);
    $_file->set('type', "text/html"); // text/html because we will update this later.
    
    $_file->set('original_name', basename($_previewname));
    $_file->set('original_type', "text/html"); // text/html because we will update this later.
    $_file->set('original_size', 0);
    
    $_file->set('preview_name', basename($_previewname));
    $_file->set('preview_type', "image/png");
    $_file->set('preview_size', 0);
    
    if (!$_file->save()) {
      return false;
    }
    
    
    if ( !touch(UPLOAD_DIR."/{$this->controller->_user['id']}/{$_filename}")) {
      return false;
    }
    
    
    return $_file->getLastInsertId();
  }
  
  
  /**
   * Given a file id, this will update the file from it's associated content
   * source.  (Obviously, this only works if there is a contentsource for the
   * upload).
   *
   * @param int ID of the Upload that is associated with the file to update
   * @return mixed true on success, false on failure
   */
  function updateFileByUploadId($id, $forceUpdate)
  {
    $_upload = $this->controller->Upload->FindDataById($id);
    
    // This Upload doesn't have a contentsource
    if (empty($_upload['Contentsourcetype']['name'])) {
      return false;
    }
    
    if (empty($_upload['File']))
    {
      if ($this->createFileForUploadId($id, $_upload['Contentsourcetype']['name']) == false) {
        return false;
      }
      
      // this not should not fail since we just saved it.  Since this is the same
      // query that we did at the beginning, the built in cake-cache will give us
      // the same results unless we temporarily disable the cache.
      $this->controller->Upload->cacheQueries = false;
      $_upload = $this->controller->Upload->FindDataById($id);
      $this->controller->Upload->cacheQueries = true;
    }
    
    $_owner = $this->controller->Upload->findOwnerDataFromUploadId($id);
    
    // There is a small chance this could be an empty array
    if (empty($_owner)) { return false; }
    
    // check to see if we should do anything
    if (! CONTENTSOURCE_REFRESH_ALWAYS && $forceUpdate == false) 
    {
      $expiry = strtotime($_upload['File']['modified'] . " + " . CONTENTSOURCE_REFRESH_TIME . " minutes");
      $nowstamp = strtotime("now");
      
      if (($expiry == false) || $expiry > $nowstamp)
        return true; //  don't process anything just yet.
    }
    
    // These are the file to operate on:
    $userid = $_owner['User']['id'];

    $_filename = UPLOAD_DIR."/{$userid}/{$_upload['File']['name']}";
    $_previewname = UPLOAD_DIR."/{$userid}/previews/{$_upload['File']['preview_name']}";
    $_orignalname = UPLOAD_DIR."/{$userid}/originals/{$_upload['File']['original_name']}";
    
    if (!empty($_upload['Contentsource']['source'])) {
      
      // Depending on the type, update the file
      switch ($_upload['Contentsourcetype']['name']) {
        
      case 'rss-source/text':
        if (!$this->handleRssUpdate($_upload, $_filename, $_previewname, $_orignalname, UPLOAD_DIR."/{$userid}"))
        {
          return false;
        }
        break;
        
      case 'microsummary/xml':
        
        $ms = new microsummary();
        $ms->load($_upload['Contentsource']['source']);
        $rv = $ms->execute($_upload['Upload']['referrer'], true);
        
        if ($rv == 2)
        {
          // The XPATH has been updated based on the
          // hint passed.  Lets save this new content
          // source to the database
          
          $updated_source =  $ms->save();
          
          // save in db:
          
          $this->controller->Contentsource->id = $_upload['Contentsource']['id'];
          $this->controller->Contentsource->saveField('source', $updated_source);
          
        }
        
        if (empty($ms->result)) {
          $ms->result = "XPATH is broken..  this feature doesn't work for the content you have selected. ";
          $this->log("Microsummary ". $_upload['Contentsource']['id'] . "does not have an xpath result");
        }
        
        // does the user have enough space to proceed
        if ($this->controller->Storage->hasAvailableSpace($userid,
                                                          strlen($ms->result) - filesize($_filename)) == false) {
          $this->log("User " . $userid . " is out of space.");
          return false;
        }
        
        // write the file.
        if (!file_put_contents($_filename, $ms->result)) {
          $this->log("file_put_contents failed for " . $_filename);
          return false;
        }
        
        // need to update the size and date in the db.
        $this->controller->File->id = $_upload['File']['id'];
        $this->controller->File->saveField('size',filesize($_filename));
        
        break;
        
        
      case 'widget/joey':
        
        $jw = new joeywidget();
        $jw->load($_upload['Contentsource']['source']);
        
        //@todo check for available space
        
        // write the file.
        if (!file_put_contents($_filename, $jw->content)) {
          $this->log("file_put_contents failed for " . $_filename);
          return false;
        }
        if (!file_put_contents($_previewname, $jw->preview)) {
          $this->log("file_put_contents failed for " . $_previewname);
          return false;
        }
        
        // need to update the size and date in the db.
        $this->controller->File->id = $_upload['File']['id'];
        $this->controller->File->saveField('size',filesize($_filename));
        $this->controller->File->saveField('preview_size',filesize($_filename));
        
        break;
        
        // We don't support whatever they're trying to update.  :(
      default:
        return false;
      }
    }
    
    // If we've made it this far without failing, we're good
    return true;
  }
  

  /*
   * Transcode the input audio to AMR.
   * The file type is implied in the file name suffix
   */
  function transcodeAudio ($fromName, $toName) {
    
    $_fromName    = escapeshellarg($fromName);
    $_toName      = escapeshellarg($toName);
    
    // /usr/local/bin/ffmpeg -i test.mp3 -ar 8000 -ac 1 -ab 7400 -f amr -acodec libamr_nb test.amr

    $_cmd = FFMPEG_CMD . " -y -i {$_fromName}  -ar 8000 -ac 1 -ab 7400 -f amr {$_toName}  2>&1";    
    exec($_cmd, $_out, $_ret);

    if ($_ret !== 0) {
      echo "\n". $_cmd."\n";
      $this->log("transcodeAudio failed: " . $_cmd);
      return false;
    }
    
    return true;
  }
  
  /*
   * Transcode the input image to PNG and then generate a preview PNG.
   * The file type is implied in the file name suffix.
   */
  function transcodeImage ($fromName, $toName, $previewName, $width, $height) {
    
    $_fromName    = escapeshellarg($fromName);
    $_toName      = escapeshellarg($toName);
    $_previewName = escapeshellarg($previewName);
    
    $_cmd = CONVERT_CMD." -geometry '{$width}x{$height}' {$_fromName} {$_toName}";    
    exec($_cmd, $_out, $_ret);
    if ($_ret !== 0) {
      $this->log("transcodeImage failed: " . $_cmd);
      return false;
    }
    
    // @todo why 1/2?
    $width = intval($width / 2);
    $height = intval($height / 2);
    $_cmd = CONVERT_CMD." -geometry '{$width}x{$height}' {$_toName} {$_previewName}";    
    exec($_cmd, $_out, $_ret);
    if ($_ret !== 0) {
      $this->log("transcodeImage failed: " . $_cmd);
      return false;
    }
    
    return true;
  }
  
  /*
   * Transcode the input video to 3GP and then generate a preview PNG.
   * The file type is implied in the file name suffix
   */
  function transcodeVideo ($fromName, $toName, $previewName, $width, $height, $userdir) {
    
    $_fromName    = escapeshellarg($fromName);
    $_toName      = escapeshellarg($toName);
    $_previewName = escapeshellarg($previewName);
    
    // ffmpeg -i video_clip.mpg -s qcif -vcodec h263 -acodec mp3 -ac 1 -ar 8000 -ab 32 -y clip.3gp
    // ffmpeg -y -i joey-4679ffa2e0a2c.flv -ab 12.2k -ac 1 -acodec libamr_nb -ar 8000 -vcodec h263 -r 10 -s qcif -b 44K -pass 1 test.3gp
    
    $tmpfname = tempnam($userdir, "ffmpeg.log");
    
    $_cmd = FFMPEG_CMD . " -y -i {$_fromName} -ab 12.2k -ac 1 -acodec libamr_nb -ar 8000 -vcodec h263 -r 10 -s qcif -b 44K -pass 1 -passlogfile " . $tmpfname . " {$_toName} 2>&1";
    exec($_cmd, $_out, $_ret);
    
    unlink($tmpfname);
    
    if ($_ret !== 0) {
      $this->log("transcodeVideo failed: " . $_out);
    }
    
    $width = intval($width / 2);
    $height = intval($height / 2);
    $_cmd = FFMPEG_CMD . " -y -i {$_fromName} -ss 5 -vcodec png -vframes 1 -an -f rawvideo -s '{$width}x{$height}' {$_previewName} 2>&1";
    
    
    $this->log(">: " . $_cmd);
    exec($_cmd, $_out, $_ret);
    if ($_ret !== 0) {
      $this->log("transcodeVideo failed: " . $_out);
      return false;
    }
    return true;
  }
  
  
  function processUpload($tmpfilename, $userid, $type, $width, $height) {
    if (!is_numeric($userid)) {
      return null;
    }
    if (!is_dir(UPLOAD_DIR."/{$userid}")) {
      return null;
    }
    if (!array_key_exists($type, $this->suffix)) {
      return null;
    }
    
    $_ret = array ('default_name' => '', 'default_type' => '', 'original_name' => '', 'original_type' => '', 'preview_name' => '', 'preview_type' => '');
    
    $rand = uniqid();
    
    if (strcasecmp($type, 'video/flv') == 0) {
      
      $_ret['original_name'] = UPLOAD_DIR."/{$userid}/originals/"."joey-".$rand.".".$this->suffix[$type];
      $_ret['original_type'] = $type;
      $_ret['default_name'] = UPLOAD_DIR."/{$userid}/"."joey-".$rand.".3gp";
      $_ret['default_type'] = "video/3gpp";
      $_ret['preview_name'] = UPLOAD_DIR."/{$userid}/previews/"."joey-".$rand.".png";
      $_ret['preview_type'] = "image/png";
      
      if (!move_uploaded_file($tmpfilename, $_ret['original_name'])) {
        return null;
      }
      
      if (!$this->transcodeVideo($_ret['original_name'], 
                                 $_ret['default_name'], 
                                 $_ret['preview_name'], 
                                 $width, 
                                 $height,
                                 UPLOAD_DIR."/{$userid}")) 
      {
        return null;
      }
      
      return $_ret;
      
    } else if (strcasecmp($type, 'image/png') == 0 ||
               strcasecmp($type, 'image/jpeg') == 0 ||
               strcasecmp($type, 'image/tiff') == 0 ||
               strcasecmp($type, 'image/bmp') == 0 ||
               strcasecmp($type, 'image/gif') == 0) {
      
      $_ret['original_name'] = UPLOAD_DIR."/{$userid}/originals/"."joey-".$rand.".".$this->suffix[$type];
      $_ret['original_type'] = $type;
      $_ret['default_name'] = UPLOAD_DIR."/{$userid}/"."joey-".$rand.".png";
      $_ret['default_type'] = "image/png";
      $_ret['preview_name'] = UPLOAD_DIR."/{$userid}/previews/"."joey-".$rand.".png";
      $_ret['preview_type'] = "image/png";
      
      if (!move_uploaded_file($tmpfilename, $_ret['original_name'])) {
        return null;
      }
      
      if (!$this->transcodeImage($_ret['original_name'], $_ret['default_name'], $_ret['preview_name'], $width, $height)) {
        return null;
      }
      
      return $_ret;
      
    } else {
      
      $_ret['default_name'] = UPLOAD_DIR."/{$userid}/"."joey-".$rand.".".$this->suffix[$type];
      $_ret['default_type'] = $type;
      
      if (!move_uploaded_file($tmpfilename, $_ret['default_name'])) {
        return null;
      }
      
      return $_ret;
    }
  }
  
  function fetchURL($url)
  {
    $useragent = "Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-US; rv:1.8.1.4) Gecko/20070515 Firefox/2.0.0.4";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
    $result = curl_exec($ch);
    
    //    print_r( curl_getInfo( $ch ) );
    curl_close($ch);
    return $result;
  }
  
  function handleRssUpdate($_upload, $_filename, $_previewname, $_orignalname, $userdir)
  {
    // Parse out the two parts of an rss content source: the rss url, and the icon url.
    preg_match("/rss=(.*)\r\n/", $_upload['Contentsource']['source'], $rss_url);
    preg_match("/icon=(.*)\r\n/", $_upload['Contentsource']['source'], $icon_url);
    
    $rss_url = $rss_url[1];
    
    // the icon url is optional.
    if (!empty($icon_url))
      $icon_url = $icon_url[1];
    else
      unset($icon_url);
    
    if (($result = $this->fetchURL($rss_url)) == false) {
      return false;
    }
    
    // default error output.
    $output = "rss does not exists for this any longer. try again later";
    
    $rss = new MagpieRSS( $result );
    if ( !$rss or $rss->ERROR) 
      return false;
    
    $title = $rss->channel['title'];
    
    // reset the upload's title to this.                  
    $_title = mysql_real_escape_string($title);
    $this->controller->Upload->id = $_upload['Upload']['id'];
    $this->controller->Upload->saveField('title', $_title);
    
    // Okay, check to see if this is a podcast (something
    // that contains a audio enclosure).  If it is, then we
    // treat it as updatable media file.  If it doesn't
    // include an enclosure, then we treat it as a simple
    // rss feed.
    
    if ($rss->incontent == "enclosure")
    {
      // Podcast!
      $lastpubdate = 0;
      foreach ($rss->items as $item) {
        // the RSS pubDate must be an RFC-822 date-time
        $pubdate = strtotime($item['pubdate']);
        
        //Invalid format
        if ($pubdate === false) {
          return false;
        }
        
        if ($pubdate > $lastpubdate || $lastpubdate == 0)
        {
          $lastitem = $item;
          $lastpubdate = $pubdate;
        }
      }
      
      $podcast = $item['enclosure'][0];
      
      // these are big files, do not fetch if the file has
      // not change.  if the file exists and the dates
      // match, we can return early.
      if (file_exists($_filename) && filemtime($_filename) < $lastpubdate)
        return false;
      
      if (($output = $this->fetchURL($podcast['url'])) == false) {
        return false;
      }
      // .png/.rss comes in, append with the real suffix for this content.
      //      unlink($_orignalname);
      //      unlink($_filename);
      $_orignalname = str_replace(".png", "." . $this->suffix[ $podcast['type']], $_orignalname);
      $_filename = str_replace(".rss", ".amr", $_filename);

      $filelen = file_put_contents($_orignalname, $output);
      if ($filelen <= 0) {
        $this->log("file_put_contents failed for " . $tmpfname);
        return false;
      }

      $result = $this->transcodeAudio($_orignalname, $_filename);
      if (!$result)
      {
        echo "transcodeAudio failed";
        return false;
      }

      if (isset($lastpubdate))
        touch($_filename, $lastpubdate);
      
      // need to update the size and date in the db.
      $this->controller->File->id = $_upload['File']['id'];

      $this->controller->File->saveField('size', $filelen);
      $this->controller->File->saveField('name', basename($_filename));
      $this->controller->File->saveField('type',"audio/amr");

      $this->controller->File->saveField('original_size',filesize($_orignalname));
      $this->controller->File->saveField('original_name',basename($_orignalname));
      $this->controller->File->saveField('original_type', $podcast['type']);
      
      // There is no preview for this rss.  Remove any
      // preview name so that the view can use the default
      // image/icon.
      $this->controller->File->saveField('preview_name', "");      
      
      
      // all done.
      return true;
    }
    
    
    // this is a "normal" rss feed
    if (!file_put_contents($_orignalname, $result)) {
      $this->log("file_put_contents failed for " . $_orignalname);
      return false;
    }

    // @todo This is really something that should be in a view.
    $output = "Channel Title: " . $title;
    $output .= "<ul>";
    foreach ($rss->items as $item) {
      if (isset($item['link']))
      {
        $href = $item['link'];
        $output .= "<li> <a href=" . $href . ">" . $item['title'] . "</a>";
      }
      else
      {
        $output .= "<li> ".$item['title'];
      }
      if (isset($item['description']))
        $output .= "<br>" . $item['description'];
      $output .= "</li>";
    }
    
    
    if (!file_put_contents($_filename, $output)) {
      $this->log("file_put_contents failed for " . $_filename);
      return false;
    }
    
    if (!file_exists($_previewname))
    {
      // we probably only should do the stuff below if
      // the preview is missing.
      
      if (!isset($icon_url))
      {
        // lets try to guess what it is.  This
        // doesn't always work, but it shows a
        // need for a generic way to get a
        // graphical icon for a given web
        // resource.
        
        $guess = parse_url($rss_url, PHP_URL_HOST);
        $result = $this->fetchURL($guess);
        
        // load into new dom document, and look for something that looks like:
        //
        // <link rel="icon" href="/favicon.ico"
        
        $d = new DOMDocument();
        $d->preserveWhiteSpace = false;
        $d->resolveExternals = true; // for character entities
        @ $d->loadHTML($result);
        
        $links = $d->getElementsByTagName('link');
        
        for ($i = 0; $i < $links->length; $i++) {
          $value = $links->item($i)->nodeValue;
          $rel = $links->item($i)->getAttribute('rel');
          if ($rel == "icon")
          {
            $href = $links->item($i)->getAttribute('href');
            $icon_url = $guess . $href;
            
            //done.
            $i = $links->length;
          }
        }
      }
      
      if (isset($icon_url))
      {
        // Grab the icon content.
        $result = $this->fetchURL($icon_url);
        
        // this is a ICO file (probably).  We need to convert to a PNG.                
        
        // figure out the extension of the favicon
        $url = pathinfo($icon_url);
        $extension = $url["extension"];
        
        if (empty($extension))
          $extension = "tmp";
        
        $tmpname = $_previewname . "." .$extension;
        
        if (!file_put_contents($tmpname, $result)) {
          echo ("file_put_contents failed for " . $tmpname);
          return false;
        }
        
        $_file_from = escapeshellarg("{$extension}:{$tmpname}");
        $_file_to   = escapeshellarg("{$_previewname}");
        
        $_cmd = CONVERT_CMD . " -geometry 16x16 {$_file_from} {$_file_to} 2>&1";    
        exec($_cmd, $_out, $_ret);
        unlink($tmpname);
        
        if ($_ret !== 0) {
          $this->log("transcodeImage failed: " . $_cmd);
          return false;
        }
      }
      
      // need to update the size and date in the db.
      $this->controller->File->id = $_upload['File']['id'];
      $this->controller->File->saveField('size',filesize($_filename));
      $this->controller->File->saveField('original_size',filesize($_orignalname));
      
      if (isset($icon_url))
      {
        $this->controller->File->saveField('preview_size',filesize($_previewname));
      }
      else
      {
        // There is no preview for this rss.  Remove any
        // preview name so that the view can use the default
        // image/icon.
        $this->controller->File->saveField('preview_name', "");      
      }
    }
    return true;
  }
  
}
?>
