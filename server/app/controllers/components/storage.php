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

  var $components = array('Transcode');
  
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
   * Creates a file row and a file on disk for an upload id
   * @todo revisit this - I think there should be a better way.  This fills in
   * filenames for all types, regardless if they exist or not...
   * @return mixed id on success, false on failure
   */
  function createFileForUploadId($id, $type) {
    
    if (!is_numeric($id) || empty($type)) { 
        return false; 
    }

    if (!array_key_exists($type, $this->suffix)) {
        return false;
    }
    
    $rand = uniqid('',true);
    
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
    
    
    if (!touch(UPLOAD_DIR."/{$this->controller->_user['id']}/{$_filename}")) {
        return false;
    }
    
    return $_file->getLastInsertId();
  }
  
}
?>
