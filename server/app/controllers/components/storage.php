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
vendor('joeywidget');

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
        $_file->set('type', "text/html"); //@todo dougt: why is this text/html?
        //$_file->set('preview_name', basename($_previewname));
        //$_file->set('preview_type', "image/png");
        //$_file->set('preview_size', 0);

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
      if (false && $forceUpdate == false) 
      {
        $expiry = strtotime($_upload['File']['modified'] . " + " . CONTENTSOURCE_REFRESH_TIME . " minutes");
        $nowstamp = strtotime("now");

        if (($expiry == false) || $expiry > $nowstamp)
          return true; //  don't process anything just yet.
      }

      // These are the file to operate on:
      $_filename = UPLOAD_DIR."/{$_owner['User']['id']}/{$_upload['File']['name']}";
      $_previewname = UPLOAD_DIR."/{$_owner['User']['id']}/previews/{$_upload['File']['preview_name']}";

      if (!empty($_upload['Contentsource']['source'])) {

          // Depending on the type, update the file
          switch ($_upload['Contentsourcetype']['name']) {

              case 'rss-source/text':

                // Go get the rss feed.
                $rss = fetch_rss( $_upload['Contentsource']['source'] );
                if (empty($rss)) {
                    return false;
                }
                
                $rss_result = "RSS: " . $rss->channel['title'] . "\n\n";
                foreach ($rss->items as $item) {
                  $rss_result = $rss_result . "-----\n\n" . $item['title'] . "\n\n" . $item['description'] . "\n\n";
                }

                // does the user have enough space to proceed
                if ($this->controller->Storage->hasAvailableSpace($_owner['User']['id'],
                                                                  strlen($rss_result) - filesize($_filename)) == false) 
                {
                  $this->log("User " . $_owner['User']['id'] . " is out of space.");
                  return false;
                }

                // write the file.
                if (!file_put_contents($_filename, $rss_result)) {
                  $this->log("file_put_contents failed for " . $_filename);
                  return false;
                }
                
                // need to update the size and date in the db.
                $this->controller->File->id = $_upload['File']['id'];
                $this->controller->File->saveField('size',filesize($_filename));
                
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
                  if ($this->controller->Storage->hasAvailableSpace($_owner['User']['id'],
                                                                    strlen($ms->result) - filesize($_filename)) == false) {
                    $this->log("User " . $_owner['User']['id'] . " is out of space.");
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
  function transcodeVideo ($fromName, $toName, $previewName, $width, $height) {
   
    $_fromName    = escapeshellarg($fromName);
    $_toName      = escapeshellarg($toName);
    $_previewName = escapeshellarg($previewName);
    
    $_cmd = FFMPEG_CMD . " -y -i {$_fromName} -ab 32 -b 15000 -ac 1 -ar 8000 -vcodec h263 -s qcif -r 12 {$_toName}";
    exec($_cmd, $_out, $_ret);
    if ($_ret !== 0) {
      $this->log("transcodeVideo failed: " . $_cmd);
      return false;
    }
    
    $width = intval($width / 2);
    $height = intval($height / 2);
    $_cmd = FFMPEG_CMD . " -i {$_fromName} -ss 5 -s '{$width}x{$height}' -vframes 1 -f mjpeg {$_previewName}";
    exec($_cmd, $_out, $_ret);
    if ($_ret !== 0) {
      $this->log("transcodeVideo failed: " . $_cmd);
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

            if (!$this->transcodeVideo($_ret['original_name'], $_ret['default_name'], $_ret['preview_name'], $width, $height)) {
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

}
?>
