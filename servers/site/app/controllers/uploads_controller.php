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

// This is outside of cake :-/
require_once dirname(__FILE__) . '/../../../libraries/FileOps.class.php';

vendor('BrowserAgent.class');

class UploadsController extends AppController
{
    var $name = 'Uploads';

    var $helpers = array('Number','Time');

    var $fallback_image = '';

    function __construct() {
        parent::__construct();

        // The content-type for this is hardcoded below
        $this->fallback_image = WWW_ROOT.'img'.DS.'na.png';
    }

    function index()
    {
        $user = $this->Session->read('User');
        $this->set('user', $user);

        $this->set('uploads', $this->Upload->findAllByOwner($user['id']));

        if (BrowserAgent::isMobile()) {
            $this->render('mp_index', 'mp');
        } else {
            $this->render('index');
        }
    }

    function view($id)
    {
        if(! (isset($id) && is_numeric($id))) {
            $this->redirect('/uploads/index');
            exit;
        }

        $user = $this->Session->read('User');

        $item = $this->Upload->findById($id);

        if ($item['Upload']['owner'] != $user['id']) {
            $this->redirect('/uploads/index');
            exit;
        }

        $this->layout = null;

        if (array_key_exists(1,$this->params['pass']) && $this->params['pass'][1] == 'thumbnail') {
            $_thumbnail = true;
        } else {
            $_thumbnail = false;
        }

        $fileOps = new FileOps ($user['id']);

        if($_thumbnail && !empty($item['Upload']['thumbnailname'])) {
            $basename = $item['Upload']['thumbnailname'];
        } else {
            $basename = $item['Upload']['filename'];
        }

        // SpecialCase++ @todo
        if ($item['Upload']['type'] == "microsummary/xml") {         
            $this->set('content_type', 'text/plain');
        } else {
            $this->set('content_type', $item['Upload']['type']);
        }

        $filename = $fileOps->getFilename($basename);

        // the default is, well the default.
        $this->set('content_type', $item['Upload']['type']);
        
        if (is_readable($filename) && is_file($filename)) {
            // SpecialCase++ @todo
            if ($item['Upload']['type'] == "microsummary/xml") {         
                $this->set('content_type', 'text/plain');
            } else if ($item['Upload']['type'] == "video/flv") {
              if ($_thumbnail == true)
              {
                // video uploads uses a png a the thumbnail.
                $this->set('content_type', 'image/png');
              }
              else
              {
                // video uploads uses a 3gp to download to phones
                $this->set('content_disposition', 'filename=' . basename($filename));
                $this->set('content_type', 'video/avi');  // This is all fucked up.  For windows you have to lie.  We need to store this "hack" mime type in a table.
              }
            }
        } else {
            // We can't read their file - fallback
            $filename = $this->fallback_image;
            $this->set('content_type', 'image/png'); //hardcoded :-/
        }

        $this->set('content_length', filesize($filename));
        $this->set('content', file_get_contents($filename));

        /*  
         This looks cleaner, but it doesn't do the right stuff.  
         XXXX FIX - dougt
        if (is_readable($filename) && is_file($filename)) {
            // It's a file, grab the info
            $this->set('content_length', filesize($filename));
            $this->set('content', file_get_contents($filename));
        } elseif (!empty($item['Upload']['content'])) {
            // It's coming out of the database
            $this->set('content_type', $item['Upload']['type']);
            $this->set('content', $item['Upload']['content']);
        } else {
            // We can't read their file - fallback
            $filename = $this->fallback_image;
            $this->set('content_type', 'image/png'); //hardcoded :-/
            $this->set('content_length', filesize($filename));
            $this->set('content', file_get_contents($filename));
        }
        */


    }

    function delete($id)
    {
        if(! (isset($id) && is_numeric($id)))
        {
            $this->redirect('/uploads/index');
            exit;
        }

        $user = $this->Session->read('User');

        $item = $this->Upload->findById($id);

        if ($item['Upload']['owner'] == $user['id']) {
            $this->Upload->delete($id);
        }

        $this->redirect('/uploads/index');	
    }

    function rss()
    {
        $this->layout = 'xml'; 
        $user = $this->Session->read('User');
        $this->set('uploads', $this->Upload->findAllByOwner($user['id']));
    }

}
?>
