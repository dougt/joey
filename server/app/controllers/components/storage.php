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


//vendor('magpierss/rss_fetch.inc');
vendor('microsummary');

/**
 * Some mildly associated functions for storing files on the disk.  Maybe there is a
 * better place for this?
 */
class StorageComponent extends Object
{
    /**
     * Save a reference to the controller on startup
     * @param object &$controller the controller using this component
     */
    function startup(&$controller) {
        $this->controller =& $controller;
    }

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
     * Given a file id, this will update the file from it's associated content
     * source.  (Obviously, this only works if there is a contentsource for the
     * upload).
     *
     * @param int ID of the Upload that is associated with the file to update
     * @return boolean true on success, false on failure
     */
    function updateFileByUploadId($id)
    {

      $_upload = $this->controller->Upload->FindById($id);

      if (empty($_upload['File']))
      {
        // Create a unique file for our new upload
        $_filename = $this->uniqueFilenameForUser($_upload['Upload']['user_id']);
        
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
      
      // This is the file to operate on:
      $_filename = UPLOAD_DIR."/{$_upload['User']['id']}/{$_upload['File'][0]['name']}";

      // Make sure our file exists, and we can write to it
      if (! (is_writable($_filename) && is_file($_filename)) ) {
          return false;
      }

      // Lets find out what kind of update this is. if findBy___() had more
      // recursion, we wouldn't need this extra query, but then we get a lot more
      // info back than we need.
      $_contentsourcetype = $this->controller->Contentsourcetype->FindById($_upload['Contentsource'][0]['contentsourcetype_id']);

        // Depending on the type, update the file
      switch ($_contentsourcetype['Contentsourcetype']['name']) {

          case 'rss-source/text':
              // @todo this
              return false;
              break;

          case 'microsummary/xml':

              $ms = new microsummary();
              $ms->load($_upload['Contentsource'][0]['source']);
              $ms->execute($_upload['Upload']['referrer']);


              // PHP5 and Firefox don't seam to be
              // compatible all of the time.  We need to
              // investigate this a bit.
              if (empty($ms->result))
                die("microsummary does not exist for this page");

              // write the file.
              $fh = fopen($_filename, 'w') or die("can't open transcode file");
              fwrite($fh, $ms->result) or die("can't write transcode file");
              fclose($fh);

              // need to update the size and date in the db.
              $this->controller->File->id = $id;
              $this->controller->File->saveField('size',filesize($_filename));
              // what is the date function here?  $this->controller->File->saveField('modified',NOW());

              break;

              // We don't support whatever they're trying to update.  :(
          default:
              return false;
      }
      
      // If we've made it this far without failing, we're good
      return true;
    }
 

    /*
     * translateFile
     *
     * This function translates files based on their file
     * type. This allows us to store files of a given type
     * in a canonical form.
     */
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
        
        $_cmd = FFMPEG_CMD . " -y -i {$tempfile} -ab 32 -b 15000 -ac 1 -ar 8000 -vcodec h263 -s qcif -r 12 {$filename}";
        
        exec($_cmd, $_out, $_ret);
        
        rename ($filename, $origname);
        
        // Leave around for debugging.
        //unlink($tempfile);
        
        if ($_ret !== 0) {
          
          // bad things happened.  @todo, log $_out to a file.
          return "";
        }
        
        return "video/3gp";
      }
      
      return "";
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
