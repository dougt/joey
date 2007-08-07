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


/**
 * A component for anything relating to transcoding files
 */
class TranscodeComponent extends Object
{

    var $components = array('Storage');

    /**
     * Save a reference to the controller on startup
     * @param object &$controller the controller using this component
     */
    function startup(&$controller) {
        $this->controller =& $controller;
    }

    function transcodeFileById($id) {

        if (!is_numeric($id)) {
            return false;
        }

        $_file = $this->controller->File->findById($id, null, null, 0);

        $_owner = $this->controller->Upload->findOwnerDataFromUploadId($_file['File']['upload_id']);

        $_phone_data = $this->controller->User->getPhoneDataByUserId($_owner['User']['id']);

        $_target_dir = UPLOAD_DIR."/{$_owner['User']['id']}";

        if (!is_writable($_target_dir)) {
          $this->controller->Error->addError("Target Directory is not writable  ($_target_dir)", 'transcode:fatal', false, true);
          return false;
        }

        // Get the random name generated for the original file
        preg_match('/^joey-([A-Za-z0-9-].*)\..*$/', $_file['File']['original_name'], $matches);
        $_rand = $matches[1];
        if (empty($_rand)) {
            return false;
        }

        // @todo - this is totally arbitrary
        if ($_phone_data['screen_width'] < 1 || $_phone_data['screen_height'] < 1) {
            $_phone_data['screen_width'] = $_phone_data['screen_height'] = 100;
        }

        $_file['File']['preview_name'] = empty($_file['File']['preview_name']) ?  "joey-{$_rand}.png" : $_file['File']['preview_name'];
        $_file['File']['preview_type'] = empty($_file['File']['preview_type']) ?  "image/png" : $_file['File']['preview_type'];

        // If the upload is a video
        if (in_array(strtolower($_file['File']['original_type']), array('video/flv'))) {
            $_file['File']['name'] = empty($_file['File']['name']) ?  "joey-{$_rand}.3gp" : $_file['File']['name'];
            $_file['File']['type'] = empty($_file['File']['type']) ?  "video/3gpp" : $_file['File']['type'];

            if (!$this->transcodeVideo("{$_target_dir}/originals/{$_file['File']['original_name']}", "{$_target_dir}/{$_file['File']['name']}", "{$_target_dir}/previews/{$_file['File']['preview_name']}", $_phone_data['screen_width'], $_phone_data['screen_height'])) {
                return false;
            }

        // If the upload is an image
        } else if (in_array(strtolower($_file['File']['original_type']), array('image/png', 'image/jpeg', 'image/tiff', 'image/bmp', 'image/gif'))) {

            $_file['File']['name'] = empty($_file['File']['name']) ?  "joey-{$_rand}.png" : $_file['File']['name'];
            $_file['File']['type'] = empty($_file['File']['type']) ?  "image/png" : $_file['File']['type'];

            if (!$this->transcodeImageAndPreview("{$_target_dir}/originals/{$_file['File']['original_name']}","{$_target_dir}/{$_file['File']['name']}", "{$_target_dir}/previews/{$_file['File']['preview_name']}", $_phone_data['screen_width'], $_phone_data['screen_height'])) {
                return false;
            }
 
        } else if (in_array(strtolower($_file['File']['original_type']), array('browser/stuff'))) {

            // this doesn't have a preview.
            $_file['File']['preview_name'] = null;
            $_file['File']['preview_type'] = null;

            $_file['File']['name'] = empty($_file['File']['name']) ?  "joey-{$_rand}.html" : $_file['File']['name'];
            $_file['File']['type'] = empty($_file['File']['type']) ?  "text/html" : $_file['File']['type'];

            if (!$this->transcodeBrowserStuff("", "{$_target_dir}/originals/{$_file['File']['original_name']}","{$_target_dir}/{$_file['File']['name']}")) {
                return false;
            }
        }
        else if (in_array(strtolower($_file['File']['original_type']), array('text/plain'))) {

            // this doesn't have a preview.          
            $_file['File']['preview_name'] = null;
            $_file['File']['preview_type'] = null;

            $_file['File']['name'] = empty($_file['File']['name']) ?  "joey-{$_rand}.txt" : $_file['File']['name'];
            $_file['File']['type'] = empty($_file['File']['type']) ?  "text/plain" : $_file['File']['type'];

            if (!$this->transcodeText("{$_target_dir}/originals/{$_file['File']['original_name']}","{$_target_dir}/{$_file['File']['name']}")) {
                return false;
            }
        }

        // ensure that the files have the correct permissions on disk.
        $old = umask(0);

        chmod("{$_target_dir}/originals/{$_file['File']['original_name']}", 0770);
        chgrp("{$_target_dir}/originals/{$_file['File']['original_name']}", "joey_adm");

        chmod("{$_target_dir}/originals/{$_file['File']['preview_name']}", 0770);
        chgrp("{$_target_dir}/originals/{$_file['File']['preview_name']}", "joey_adm");

        umask($old);

        // update all of the file sizes
        $_file['File']['size'] = filesize("{$_target_dir}/{$_file['File']['name']}");
        $_file['File']['original_size'] = filesize("{$_target_dir}/originals/{$_file['File']['original_name']}");
        $_file['File']['preview_size'] = filesize("{$_target_dir}/previews/{$_file['File']['preview_name']}");

        if ($this->controller->File->save($_file)) {

            $this->controller->Upload->id = $_file['File']['upload_id'];
            $this->controller->Upload->saveField('ever_updated', '1');

            return true;
        }

        return false;

    }

    function transcodeText($fromName, $toName)
    {
      $_ret = copy ($fromName, $toName);
      if ($_ret !== 0) {
        $this->controller->Error->addError("transcodeText error ($fromName -> $toName)", 'transcode:text', false, true);
        return false;
      }
      return true;
    }

    function transcodeBrowserStuff($url, $fromName, $toName)
    {
      $_ret = copy ($fromName, $toName);
      if ($_ret !== 0) {
        $this->controller->Error->addError("transcodeBrowserStuff error ($fromName -> $toName)", 'transcode:browser', false, true);
        return false;
      }
      return true;
    }
    
    /*
     * Transcode the input audio to AMR.
     * The file type is implied in the file name suffix
     */
    function transcodeAudio($fromName, $toName) {

        $_fromName    = escapeshellarg($fromName);
        $_toName      = escapeshellarg($toName);

        $_cmd = FFMPEG_CMD . " -y -i {$_fromName}  -ar 8000 -ac 1 -ab 7400 -f amr {$_toName}  2>&1";    
        exec($_cmd, $_out, $_ret);

        if ($_ret !== 0) {
            $this->controller->Error->addError("transcodeAudio error (".implode(',',$_out).") from the command ($_cmd)", 'transcode:audio', false, true);
            return false;
        }

        return true;
    }

    /*
     * Transcode the input image to PNG
     * The file type is implied in the file name suffix.
     */
    function transcodeImage($fromName, $toName, $width, $height) {

        $_fromName    = escapeshellarg($fromName);
        $_toName      = escapeshellarg($toName);

        $_cmd = CONVERT_CMD." -geometry '{$width}x{$height}' {$_fromName} {$_toName}";    
        exec($_cmd, $_out, $_ret);
        if ($_ret !== 0) {
            $this->controller->Error->addError("transcodeImage error (".implode(',',$_out).") from the command ($_cmd)", 'transcode:image', false, true);
            return false;
        }

        return true;
    }

    /*
     * Transcode the input image to PNG and then generate a preview PNG.
     * The file type is implied in the file name suffix.
     */
    function transcodeImageAndPreview($fromName, $toName, $previewName, $width, $height) {

        if (!$this->transcodeImage($fromName, $toName, $width, $height)) {
            return false;
        }

        if (!$this->transcodeImage($toName, $previewName, intval($width/2), intval($height/2))) {
            return false;
        }

        return true;
    }

    /*
     * Transcode the input video to 3GP and then generate a preview PNG.
     * The file type is implied in the file name suffix
     */
    function transcodeVideo($fromName, $toName, $previewName, $width, $height, $userdir) {

        $_fromName    = escapeshellarg($fromName);
        $_toName      = escapeshellarg($toName);
        $_previewName = escapeshellarg($previewName);

        $tmpfname = tempnam(UPLOAD_DIR.'/cache', "ffmpeg.log");

        $_cmd = FFMPEG_CMD . " -y -i {$_fromName} -ab 12.2k -ac 1 -acodec libamr_nb -ar 8000 -vcodec h263 -r 10 -s qcif -b 44K -pass 1 -passlogfile " . $tmpfname . " {$_toName} 2>&1";
        exec($_cmd, $_out, $_ret);

        unlink($tmpfname);

        if ($_ret !== 0) {
            $this->controller->Error->addError("transcodeVideo error (".implode(',',$_out).") from the command ($_cmd)", 'transcode:video', false, true);
            return false;
        }

        $width = intval($width / 2);
        $height = intval($height / 2);
        $_cmd = FFMPEG_CMD . " -y -i {$_fromName} -ss 5 -vcodec png -vframes 1 -an -f rawvideo -s '{$width}x{$height}' {$_previewName} 2>&1";
        exec($_cmd, $_out, $_ret);

        if ($_ret !== 0) {
            $this->controller->Error->addError("transcodeVideo preview error (".implode(',',$_out).") from the command ($_cmd)", 'transcode:video', false, true);
            return false;
        }

        return true;
    }
}
?>
