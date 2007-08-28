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
 * The Original Code is Kubla CMS
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

class FilesController extends AppController
{
    var $name = 'Files';

    var $components = array('Session','Storage');

    var $uses = array('Contentsource', 'Contentsourcetype', 'File', 'Upload', 'User');

    var $helpers = array('Number','Time');

    var $securityLevel = 'low';

    /**
     * Set in the constructor.  This is just a friendlier way to say "no preview
     * available" - this must be a .png
     */
    var $fallback_image = '';

    /**
     * Set in beforeFilter().  Will hold the session user data.
     */
    var $_user;

    /**
     * You can thank https://trac.cakephp.org/ticket/1589 for not letting us put this
     * in the constructor.  (Apparently that is not a valid scenario...)
     */
    function beforeFilter() {

        parent::beforeFilter();

        // Set the local user variable to the Session's User
        $this->_user = $this->Session->read('User');
    }

    function __construct() {

        parent::__construct();

        // The content-type for this is hardcoded below (to .png)
        $this->fallback_image = WWW_ROOT.'img'.DS.'na.png';
    }
    
    function delete($id)
    {
      $_item = $this->File->findById($id);
      $this->redirect('/uploads/delete/'.$_item['Upload']['id']);
      exit();
    }

    function wrapper($id)
    {
        $this->layout = null;

        // Make a note if they are asking for the preview
        if (array_key_exists(1,$this->params['pass']) && $this->params['pass'][1] == 'preview') {
            $_preview = true;
        } else {
            $_preview = false;
        }
        
        // Make a note if they are asking for the original
        if (array_key_exists(1,$this->params['pass']) && $this->params['pass'][1] == 'original') {
            $_original = true;
        } else {
            $_original = false;
        }


        // Must be careful with this because we aren't checking for ownership here.
        $_item = $this->File->findById($id);

        // maybe always return iframe?
        if (false && strstr($_item['File']['type'], "image"))
        { 
          if ($_original == true)
            $out = "<img src=\"".FULL_BASE_URL."/files/view/".$id."/original\">";
          else if ($_preview == true)
            $out = "<img src=\"".FULL_BASE_URL."/files/view/".$id."/preview\">";
          else
            $out = "<img src=\"".FULL_BASE_URL."/files/view/".$id."\">";
        }
        else
        {
          if ($_original == true)
            $out =  "<iframe marginwidth=\"0\" marginheight=\"0\" frameborder=\"0\" vspace=\"0\" hspace=\"0\" style=\"width:100%; height:100%\" src=\"".FULL_BASE_URL."/files/view/".$id."/original\"></iframe>";
          else if ($_preview == true)
            $out =  "<iframe marginwidth=\"0\" marginheight=\"0\" frameborder=\"0\" vspace=\"0\" hspace=\"0\" style=\"width:100%; height:100%\" src=\"".FULL_BASE_URL."/files/view/".$id."/preview\"></iframe>";
          else
            $out =  "<iframe marginwidth=\"0\" marginheight=\"0\" frameborder=\"0\" vspace=\"0\" hspace=\"0\" style=\"width:100%; height:100%\" src=\"".FULL_BASE_URL."/files/view/".$id."\"></iframe>";
        }
        
        // @todo use view?

        echo $out;
        exit(); // todo "return"-ing causes the default layout to render in the browser.  not sure why
        return;
    }

    function view($id)
    {
        $_associated_user = false;

        $_item = $this->File->findById($id);

        $_users = $this->Upload->findById($_item['Upload']['id']);

        foreach ($_users['User'] as $user) {
            if ($user['id'] == $this->_user['id']) {
                $_associated_user = true;
            }
        }

        if (!$_associated_user) {
            if ($this->nbClient) {
                $this->returnJoeyStatusCode($this->ERROR_NOAUTH);
            }
            $this->flash('Invalid ID requested', '/uploads/index');
        }

        $this->layout = null;

        // Make a note if they are asking for the preview
        if (array_key_exists(1,$this->params['pass']) && $this->params['pass'][1] == 'preview') {
            $_preview = true;
        } else {
            $_preview = false;
        }
        
        // Make a note if they are asking for the original
        if (array_key_exists(1,$this->params['pass']) && $this->params['pass'][1] == 'original') {
            $_original = true;
        } else {
            $_original = false;
        }

        if ($_preview && !empty($_item['File']['preview_name'])) {
            $_filename = UPLOAD_DIR."/{$this->_user['id']}/previews/{$_item['File']['preview_name']}";

            $_filetype = $_item['File']['preview_type'];
            $_filesize = $_item['File']['preview_size'];
            
        } else if ($_original && !empty($_item['File']['original_name'])) {
            $_filename = UPLOAD_DIR."/{$this->_user['id']}/originals/{$_item['File']['original_name']}";
            $_filetype = $_item['File']['original_type'];
            $_filesize = $_item['File']['original_size'];
        } else {
            // Send the whole file
            $_filename = UPLOAD_DIR."/{$this->_user['id']}/{$_item['File']['name']}";
            $_filetype = $_item['File']['type'];
            $_filesize = $_item['File']['size'];
        }


        // We can't read the file for whatever reason.  Fallback to the default.
        // (This actually sends the image if the complete content is missing too. hmm)
        if (! (is_readable($_filename) && is_file($_filename))) {
            $_filename = $this->fallback_image;
            $_filetype = 'image/png';
            $_filesize = filesize($_filename);

            if ($this->nbClient) {
                $this->returnJoeyStatusCode($this->ERROR_FILE);
            }
        }

        $this->set('content_type', $_filetype);
        $this->set('content_length', $_filesize);

        // @TODO  - for some files, this requires a TON of memory.  I am seeing these errors when PHP memory cap is 100M:
        // Allowed memory size of 104857600 bytes exhausted (tried to allocate 26001408 bytes)
        // We need to stream this content to the view instead of just reading it all into a variable.
        $this->set('content', file_get_contents($_filename));

        if ($this->nbClient) {
            header("X-joey-status: 200");
        }
        // @todo decide what we need to do about cheesy hacks like this
        if ($_filetype == 'video/3gpp') {          
          $this->set('content_disposition', 'filename=' . basename($_filename));
          $this->set('content_type', 'video/3gpp');
        }
    }
}
?>
