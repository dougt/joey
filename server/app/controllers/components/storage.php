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


vendor('magpierss/rss_fetch.inc');
vendor('microsummary');

/**
 * Some mildly associated functions for storing files on the disk.  Maybe there is a
 * better place for this?
 */
class StorageComponent extends Object
{

    var $suffix = array ("txt" => "text/plain", "text/plain" => "txt",
                         "png" => "image/png", "image/png" => "png",
                         "jpg" => "image/jpeg", "image/jpeg" => "jpg",
                         "gif" => "image/gif", "image/gif" => "gif",
                         "tif" => "image/tiff", "image/tiff" => "tif",
                         "bmp" => "image/bmp", "image/bmp" => "bmp",
                         "3gp" => "video/3gp", "video/3gp" => "3gp",
                         "flv" => "video/flv", "video/flv" => "flv",
                         "mpg" => "video/mpeg", "video/mpeg" => "mpg",
                         "avi" => "video/avi", "video/avi" => "avi",
                         "mov" => "video/quicktime", "video/quicktime" => "mov",
                         "wav" => "audio/x-wav", "audio/x-wav" => "wav",
                         "mp3" => "audio/mpeg", "audio/mpeg" => "mp3",
                         "mid" => "audio/mid", "audio/mid" => "mid",
                         "rss" => "rss-source/text", "rss-source/text" => "rss",
                         "mcs" => "microsummary/xml", "microsummary/xml" => "mcs");



    function isImageFile($filenamesuffix)
    {
      if (strcasecmp($filenamesuffix, 'png') == 0 ||
          strcasecmp($filenamesuffix, 'gif') == 0 ||
          strcasecmp($filenamesuffix, 'jpg') == 0 ||
          strcasecmp($filenamesuffix, 'tif') == 0 ||
          strcasecmp($filenamesuffix, 'bmp') == 0 )
        return true;
      return false;
    }


    function isVideoFile($filenamesuffix)
    {
      if (strcasecmp($filenamesuffix, 'flv') == 0 ||
          strcasecmp($filenamesuffix, '3pg') == 0 )
        return true;
      return false;
    }


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
     */

    function hasAvailableSpace($userid, $additional) {
      
      $totalused = $this->controller->File->totalSpaceUsed($userid);
      // $additional and $totalused is in bytes, MAX_DISK_USAGE is in MB
      if ( ($additional + $totalused) > (MAX_DISK_USAGE * 1024 * 1024)) {
        return false;
      }
      
      return true;
    }

    /**
     * Will create a preview file on disk.  I'm not sure if this is really the best
     * place for this code, but it'll work for now.
     *
     * @param string Filename to make a preview of
     * @return mixed false on failure, the previews filename on success
     */
    function generatePreview($filename, $width, $height) {

        // Dunno what they gave us, but it's not useful to us
        if (! (is_readable($filename) && is_file($filename)) ) {
            return false;
        }
        
        $filenamesuffix = substr($filename, -3, 3);
        
        $previewname = dirname($filename).'/previews/'.basename($filename, $filenamesuffix).'png';

        // Prepare our file and preview names for the exec()
        $_filename = escapeshellarg($filename);
        $_previewname = escapeshellarg($previewname);

        if ($this->isImageFile($filenamesuffix)) { 

            $_cmd = CONVERT_CMD." -geometry '{$width}x{$height}' {$_filename} {$_previewname}";

            exec($_cmd, $_out, $_ret);

            if ($_ret !== 0) {
                // bad things happened.  @todo, log $_out to a file.
                return false;
            }

            return basename($previewname);

        } else if ($this->isVideoFile($filenamesuffix)) {

            $_cmd = FFMPEG_CMD . " -i {$_filename} -ss 5 -s '{$width}x{$height}' -vframes 1 -f mjpeg {$_previewname}";

            exec($_cmd, $_out, $_ret);

            if ($_ret !== 0) {
                // bad things happened.  @todo, log $_out to a file.
                return false;
            }

            return basename($previewname);

        } 

        // We don't support generating a preview on whatever filetype they gave us
        return false;
    }


    /**
     * Given a file id, this will update the file from it's associated content
     * source.  (Obviously, this only works if there is a contentsource for the
     * upload).
     *
     * @param int ID of the Upload that is associated with the file to update
     * @return boolean true on success, false on failure
     */
    function updateFileByUploadId($id, $forceUpdate)
    {
      $_upload = $this->controller->Upload->FindById($id);

      if (empty($_upload['File']))
      {
        // Create a unique file for our new upload

        $_contentsourcetype = $this->controller->Contentsourcetype->FindById($_upload['Contentsource'][0]['contentsourcetype_id']);
        $type = $_contentsourcetype['Contentsourcetype']['name'];

        $_filename = $this->uniqueFilenameForUserType($_upload['Upload']['user_id'], $type);
        
        if ($_filename !== false) {
          $_file = new File();
          $_file->set('name', basename($_filename));
          $_file->set('upload_id', $id);
          $_file->set('size', 0);
          $_file->set('type', "text/plain");
          if (!$_file->save()) {
            return false;
          }
          
          // this not should not fail since we just saved it.  Since this is the same
          // query that we did at the beginning, the built in cake-cache will give us
          // the same results unless we temporarily disable the cache.
          $this->controller->Upload->cacheQueries = false;
          $_upload = $this->controller->Upload->FindById($id);
          $this->controller->Upload->cacheQueries = true;

        } else {
          // bad things happened.  @todo, log $_out to a file.
          return false;
        }
      }
      
      // check to see if we should do anything
      if (false && $forceUpdate == false)
      {
        $expiry = strtotime($_upload['File'][0]['modified'] . " + " . CONTENTSOURCE_REFRESH_TIME . " minutes");
        $nowstamp = strtotime("now");

        //echo "this is the intial: " . date('l dS \o\f F Y h:i:s A', strtotime($_upload['File'][0]['modified'])) . "<p>";        
        //echo "this is the expiry: " . date('l dS \o\f F Y h:i:s A', $expiry) . "<p>";
        //echo "this is the now stamp " . date('l dS \o\f F Y h:i:s A', $nowstamp);

        
        if (($expiry == false) || $expiry > $nowstamp)
          return true; //  don't process anything just yet.
      }

      // This is the file to operate on:
      $_filename = UPLOAD_DIR."/{$_upload['User']['id']}/{$_upload['File'][0]['name']}";

      // Lets find out what kind of update this is. if findBy___() had more
      // recursion, we wouldn't need this extra query, but then we get a lot more
      // info back than we need.
      $_contentsourcetype = $this->controller->Contentsourcetype->FindById($_upload['Contentsource'][0]['contentsourcetype_id']);

        // Depending on the type, update the file
      switch ($_contentsourcetype['Contentsourcetype']['name']) {

          case 'rss-source/text':

            // Go get the rss feed.
            $rss = fetch_rss( $_upload['Contentsource'][0]['source'] );
            if (empty($rss)) {
                return false;
            }
            
            $rss_result = "Channel Title: " . $rss->channel['title'] . "\n";
            foreach ($rss->items as $item) {
              //$href = $item['link'];
              $title = $item['title'];
              $rss_result = $rss_result . $title . "\n";
            }

            // does the user have enough space to proceed
            if ($this->controller->Storage->hasAvailableSpace($_upload['User']['id'],
                                                              strlen($rss_result) - filesize($_filename)) == false) 
            {
              // @todo we should log this and maybe change
              // the file content to indicate the error.
              break;
            }

            // write the file.
            if (!file_put_contents($_filename, $rss_result)) {
                return false;
            }
            
            // need to update the size and date in the db.
            $this->controller->File->id = $id;
            $this->controller->File->saveField('size',filesize($_filename));
            
            
            break;

          case 'microsummary/xml':

              $ms = new microsummary();
              $ms->load($_upload['Contentsource'][0]['source']);
              $ms->execute($_upload['Upload']['referrer']);

              // @todo PHP5 and Firefox don't seam to be
              // compatible all of the time.  We need to
              // investigate this a bit.
              if (empty($ms->result)) {
                  $ms->result = "XPATH is broken..  this feature doesn't work for the content you have selected. ";
              }

              // does the user have enough space to proceed
              if ($this->controller->Storage->hasAvailableSpace($_upload['User']['id'],
                                                                strlen($ms->result) - filesize($_filename)) == false) {
                // @todo we should log this and maybe change
                // the file content to indicate the error.
                break;
              }

              // write the file.
              if (!file_put_contents($_filename, $ms->result)) {
                  return false;
              }

              // need to update the size and date in the db.
              $this->controller->File->id = $id;
              $this->controller->File->saveField('size',filesize($_filename));

              break;

              // We don't support whatever they're trying to update.  :(
          default:
              return false;
      }
      
      // If we've made it this far without failing, we're good
      return true;
    }
    
    function transcodeFile($filename, $width, $height) {
      // Dunno what they gave us, but it's not useful to us
      if (! (is_readable($filename) && is_file($filename)) ) {
        return null;
      }
      
      // Return values for the transcoded file -- save to DB
      $ret = array ('name' => '', 'type' => '');
      $filenamesuffix = substr($filename, -3, 3);
      
      if (strcasecmp($filenamesuffix, 'png') == 0 ||
          strcasecmp($filenamesuffix, 'gif') == 0 ||
          strcasecmp($filenamesuffix, 'jpg') == 0 ||
          strcasecmp($filenamesuffix, 'tif') == 0 ||
          strcasecmp($filenamesuffix, 'bmp') == 0) { 
        
        $targetfilename = dirname($filename)."/".basename($filename, '.orig.'.$filenamesuffix).'.png';
        $_targetfilename = escapeshellarg($targetfilename);
        $_filename = escapeshellarg($filename);
        $_cmd = CONVERT_CMD." -geometry '{$width}x{$height}' {$_filename} {$_targetfilename}";
        
        exec($_cmd, $_out, $_ret);
        
        if ($_ret !== 0) {
          // bad things happened.  @todo, log $_out to a file.
          return null;
        } else {
          $ret['name'] = $targetfilename;
          $ret['type'] = "image/png"; 
          return $ret;
        }
      
      } else if (strcasecmp($filenamesuffix, 'flv') == 0) {
        $targetfilename = dirname($filename)."/".basename($filename, '.orig.'.$filenamesuffix).'.3gp';
        $_targetfilename = escapeshellarg($targetfilename);
        $_filename = escapeshellarg($filename);
        $_cmd = FFMPEG_CMD . " -y -i {$_filename} -ab 32 -b 15000 -ac 1 -ar 8000 -vcodec h263 -s qcif -r 12 {$_targetfilename}";
        
        exec($_cmd, $_out, $_ret);
        
        if ($_ret !== 0) {
          // bad things happened.  @todo, log $_out to a file.
          return null;
        } else {
          $ret['name'] = $targetfilename;
          $ret['type'] = "video/3gp";  
          return $ret;
        }
      }
      
      // nothing to do
      return null;
    }
     

    /*
     * translateFile
     *
     * This function translates files based on their file
     * type. This allows us to store files of a given type
     * in a canonical form.
     */
    /*
    function translateFile($filename, $filetype) {
      // Dunno what they gave us, but it's not useful to us
      if (! (is_readable($filename) && is_file($filename)) ) {
        return "";
      }
      
      if (strcasecmp($filetype, 'video/flv') == 0) {
        
        // High-end
        //$command = "$this->ffmpeg -y -i " . $orgfilename ." -ab 32 -ac 1 -ar 8000 -vcodec h263 -s qcif -r 12 " . $this->filename;
        
        // lowend
        //$command = "$this->ffmpeg -y -i " . $orgfilename ." -ab 32 -b 15000 -ac 1 -ar 8000 -vcodec h263 -s qcif -r 12 " . $this->filename;
        
        $tempfile = $filename . ".orig";
        
        rename ($filename, $tempfile);
        
        $origname = $filename;
        $filename = $origname . ".3gp";
        
        $_tempfile = escapeshellarg($tempfile);
        $_filename = escapeshellarg($filename);
        $_cmd = FFMPEG_CMD . " -y -i {$_tempfile} -ab 32 -b 15000 -ac 1 -ar 8000 -vcodec h263 -s qcif -r 12 {$_filename}";
        
        exec($_cmd, $_out, $_ret);
        
        rename ($filename, $origname);

        // Leave around for debugging.
        // @todo - If you're going to leave this code here, it should key off DEBUG
        // being set
        //unlink($tempfile);
        
        if ($_ret !== 0) {
          
          // bad things happened.  @todo, log $_out to a file.
          return "";
        }
        
        return "video/3gp";
      }
      
      return $type;
  }
  */


  function uniqueFilenameForUserType($userid, $type) {
        if (!is_numeric($userid)) {
            return false;
        }
        if (!is_dir(UPLOAD_DIR."/{$userid}")) {
            return false;
        }
        $rand = uniqid ();
        if (array_key_exists($type, $this->suffix)) {
          $_filename = UPLOAD_DIR."/{$userid}/"."joey-".$rand.".orig.".$this->suffix[$type];
        } else {
          $_filename = UPLOAD_DIR."/{$userid}/"."joey-".$rand.".orig";
        }
        return $_filename;
  }


    /**
     * Will create a unique empty file in a users upload directory.
     *
     * @param userid The user ID to associate the file with
     * @return mixed false if something goes wrong, the filename if all goes well
     */
    /*
    function uniqueFilenameForUser($userid) {
        if (!is_numeric($userid)) {
            return false;
        }
        if (!is_dir(UPLOAD_DIR."/{$userid}")) {
            return false;
        }
        $_filename = tempnam(UPLOAD_DIR."/{$userid}", 'joey-');

        // If tempnam can't create a unique file in the requested directory, it will
        // fall back to the system's temp dir.  This isn't good for us, so we double
        // check here, and if it fell back, we'll return false.
        if (strpos($_filename, UPLOAD_DIR) === false) {
            unlink($_filename);
            return false;
        } else {
            return $_filename;
        }
    }
    */

}
?>
