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

    /**
     * Save a reference to the controller on startup
     * @param object &$controller the controller using this component
     */
    function startup(&$controller) {
        $this->controller =& $controller;
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
            $this->controller->Error->addError("transcodeAudio error (".implode(',',$_out).") from the command ($_cmd)", 'general', false, true);
            return false;
        }

        return true;
    }

    /*
     * Transcode the input image to PNG and then generate a preview PNG.
     * The file type is implied in the file name suffix.
     */
    function transcodeImage($fromName, $toName, $previewName, $width, $height) {

        $_fromName    = escapeshellarg($fromName);
        $_toName      = escapeshellarg($toName);
        $_previewName = escapeshellarg($previewName);

        $_cmd = CONVERT_CMD." -geometry '{$width}x{$height}' {$_fromName} {$_toName}";    
        exec($_cmd, $_out, $_ret);
        if ($_ret !== 0) {
            $this->controller->Error->addError("transcodeImage error (".implode(',',$_out).") from the command ($_cmd)", 'general', false, true);
            return false;
        }

        // @todo why 1/2?
        $width = intval($width / 2);
        $height = intval($height / 2);
        $_cmd = CONVERT_CMD." -geometry '{$width}x{$height}' {$_toName} {$_previewName}";    
        exec($_cmd, $_out, $_ret);
        if ($_ret !== 0) {
            $this->controller->Error->addError("transcodeImage preview error (".implode(',',$_out).") from the command ($_cmd)", 'general', false, true);
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
            $this->controller->Error->addError("transcodeVideo error (".implode(',',$_out).") from the command ($_cmd)", 'general', false, true);
            return false;
        }

        $width = intval($width / 2);
        $height = intval($height / 2);
        $_cmd = FFMPEG_CMD . " -y -i {$_fromName} -ss 5 -vcodec png -vframes 1 -an -f rawvideo -s '{$width}x{$height}' {$_previewName} 2>&1";
        exec($_cmd, $_out, $_ret);

        if ($_ret !== 0) {
            $this->controller->Error->addError("transcodeVideo preview error (".implode(',',$_out).") from the command ($_cmd)", 'general', false, true);
            return false;
        }

        return true;
    }
}
?>
