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
 * Some mildly associated functions for storing files on the disk.  Maybe there is a
 * better place for this?
 */
class StorageComponent extends Object
{

    /**
     * Will create a preview file on disk.  I'm not sure if this is really the best
     * place for this code, but it'll work for now.
     *
     * @param string Filename to make a preview of
     * @return mixed false on failure, the previews filename on success
     */
    function generatePreview($filename, $filetype) {

        // Dunno what they gave us, but it's not useful to us
        if (! (is_readable($filename) && is_file($filename)) ) {
            return false;
        }

        // Prepare our file and preview names for the exec()
        $_filename = escapeshellarg($filename);
        $_previewname = escapeshellarg(dirname($filename).'/previews/'.basename($filename).'.png');

        if (strncasecmp($filetype, 'image', 5) == 0) {

            $_cmd = CONVERT_CMD." -geometry '100x100' {$_filename} {$_previewname}";

            exec($_cmd, $_out, $_ret);

            if ($_ret !== 0) {
                // bad things happened.  @todo, log $_out to a file.
                return false;
            }

            return basename($filename.'.png');

        } else if (strncasecmp($filetype, 'video', 5) == 0) {

            $_cmd = FFMPEG_CMD . " -i {$_filename} -ss 5 -s '100x100' -vframes 1 -f mjpeg {$_previewname}";

            exec($_cmd, $_out, $_ret);

            if ($_ret !== 0) {
                // bad things happened.  @todo, log $_out to a file.
                return false;
            }

            return basename($filename.'.png');
        }

        // We don't support generating a preview on whatever filetype they gave us
        return false;

    }

    /**
     * Will create a unique empty file in a users upload directory.
     *
     * @param userid The user ID to associate the file with
     * @return mixed false if something goes wrong, the filename if all goes well
     */
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

}
?>
