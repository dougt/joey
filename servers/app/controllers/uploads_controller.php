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

vendor('BrowserAgent.class');

class UploadsController extends AppController
{
    var $name = 'Uploads';

    var $components = array('Session','Storage');

    var $uses = array('Contentsource', 'Contentsourcetype', 'File', 'Upload');

    var $helpers = array('Number','Time');

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

    function add()
    {
        $this->pageTitle = 'Add an upload';

        $this->set('contentsourcetypes', $this->Contentsourcetype->generateList(null,null,null,'{n}.Contentsourcetype.id','{n}.Contentsourcetype.name'));

        // They've submitted data
        if (!empty($this->data)) {
            
            // Fill in the user_id FK
            $this->data['Upload']['user_id'] = $this->_user['id'];

            // We've got two use cases.  First, let's check if they've uploaded a
            // file successfully.
            if ( !empty($this->data['File']) && $this->data['File']['Upload']['error'] == 0 ) {

                if (!is_uploaded_file($this->data['File']['Upload']['tmp_name'])) {
                    $this->File->invalidate('Upload');
                    $this->set('error_fileupload', 'Could not locate uploaded file.');
                }

                // Check if our form validates.  This only checks the stuff in the
                // Upload and File models, (ie. are required fields filled in?)
                if ($this->Upload->validates($this->data) && $this->File->validates($this->data)) {

                    // Create a unique file for our new upload
                    $_filename = $this->Storage->uniqueFilenameForUser($this->_user['id']); 

                    if ($_filename !== false) {

                        // Put our file away.  This better not ever fail, but on the
                        // off chance it does, we'll invalidate the file upload.
                        // This will make the save below fail, so the db won't get
                        // out of sync.
                        if (!move_uploaded_file($this->data['File']['Upload']['tmp_name'], $_filename)) {
                            $this->File->invalidate('Upload');
                            $this->set('error_fileupload', 'Could not move uploaded file.');
                        }

                        // Start our transaction
                        $this->Upload->begin();

                        // We've already validated it - there isn't really any reason
                        // this should fail.
                        if ($this->Upload->save($this->data)) {

                            // Some data massaging to get the POST data into a form cake can use
                            $this->data['File']['upload_id'] = $this->Upload->id;
                            $this->data['File']['name'] = basename($_filename);
                            $this->data['File']['size'] = filesize($_filename);
                            $this->data['File']['type'] = $this->data['File']['Upload']['type'];

                            // Ask our storage component to generate a preview for
                            // us.  If it fails, I'm considering it a disappointing, 
                            // but non-fatal error
                            if (($_previewname = $this->Storage->generatePreview($_filename, $this->data['File']['Upload']['type'])) !== false) {
                                $this->data['File']['preview'] = $_previewname;
                            } else {
                                // If you want it to be a fatal error, invalidate the
                                // file here.
                            }

                            $_newtype = $this->Storage->translateFile($_filename, $this->data['File']['Upload']['type']);
                            if ($_newtype != "") {
                              $this->data['File']['type'] = $_newtype;
                            }

                            // This doesn't really matter anymore, but might as well whack it
                            unset($this->data['File']['Upload']);

                            if ($this->File->save($this->data)) {
                                $this->Upload->commit();
                                if ($this->nbClient) {
                                    $this->nbFlash($this->Upload->id);
                                } else {
                                    $this->flash('Upload saved.', '/uploads/index');
                                }
                            } else {
                                $this->Upload->rollback();
                                $this->set('error_mesg', 'Could not save your file.');
                            }

                        } else {
                            $this->Upload->rollback();
                            $this->set('error_mesg', 'Could not save your upload.');
                        }

                    // Something went wrong trying to create a temporary file.  This
                    // could happen if apache can't write to the UPLOAD_DIR directory
                    } else {
                        $this->File->invalidate('Upload');
                        $this->set('error_fileupload', 'There was an error creating a temporary file.');
                    }
                }

            // They uploaded a content source instead of a file
            } else if ( !empty($this->data['Contentsource']['source']) ) {

                $this->Upload->settings['throw_error'] = true;
                $this->Contentsource->settings['throw_error'] = true;

                // Remote clients don't know the ID's ahead of time, so they will
                // submit the type as a string.  Here we look for that, and look up
                // the matching id.
                if (array_key_exists('name', $this->data['Contentsourcetype']) && !empty ($this->data['Contentsourcetype']['name'])) {
                    $_contentsource = $this->Contentsourcetype->findByName($this->data['Contentsourcetype']['name'], array('id'), null, 0);

                    // We found an ID that matched - Substitute that into the
                    // request, and move on.  If we don't find one that matches,
                    // they're trying to submit a type that we currently don't
                    // support.  In that case, invalidate.
                    if (array_key_exists('id', $_contentsource['Contentsourcetype']) && is_numeric($_contentsource['Contentsourcetype']['id'])) {
                        $this->data['Contentsourcetype']['id'] = $_contentsource['Contentsourcetype']['id'];
                        unset($_contentsource, $this->data['Contentsourcetype']['name']);
                    } else {
                        $this->Contentsource->invalidate('name');
                    }
                }

                if ($this->Upload->validates($this->data) && $this->Contentsource->validates($this->data) && $this->Contentsourcetype->validates($this->data)) {

                    // Start our transaction.  It doesn't matter what model we start
                    // or end it on, all saves will be a part of it.
                    $this->Upload->begin();

                    // This shouldn't ever fail, since we validated it
                    if ($this->Upload->save($this->data)) {
                        // gg cake
                        $this->data['Contentsource']['contentsourcetype_id'] = $this->data['Contentsourcetype']['id'];
                        $this->data['Contentsource']['upload_id']            = $this->Upload->id;

                        if ($this->Contentsource->save($this->data)) {
                            $this->Upload->commit();

                            $this->Storage->updateFileById($this->Upload->id);

                            if ($this->nbClient) {
                                $this->nbFlash($this->Upload->id);
                            } else {
                                $this->flash('Upload saved.', '/uploads/index');
                            }

                        } else {
                            $this->Upload->rollback();
                            $this->set('error_mesg', 'There was an error saving your upload.');
                        }

                    } else {
                        $this->Upload->rollback();
                        $this->set('error_mesg', 'There was an error saving your upload.');
                    }

                }
            } else {
                // Something is wrong.  Either there was an error uploading the file, or
                // they sent an incomplete POST.  Either way, not much we can do.
                $this->set('error_mesg', 'Incomplete POST data.  Failing.');
            }

            //@todo Really, they only need a file OR a contentsource[type], but this
            //will tell them they need all of them.  Since this form isn't going to
            //go live anyway, I'll leave it, but if we ever need to make the form
            //public, this is something to fix.
            $this->Upload->validates($this->data);
            $this->File->validates($this->data);
            $this->Contentsource->validates($this->data);
            $this->Contentsourcetype->validates($this->data);

            // Send the errors to the form 
            $this->validateErrors($this->Upload, $this->File);

            if ($this->nbClient) {
                $this->nbFlash(NB_CLIENT_ERROR_UPLOAD_FAIL);
            }

        }
    }

    function delete($id)
    {
        if (!is_numeric($id)) {
            $this->flash('Delete failed', '/uploads/index',2);
        }

        $_item = $this->Upload->findById($id);

        // Check for access
        if ($_item['Upload']['user_id'] != $this->_user['id']) {
            $this->flash('Delete failed', '/uploads/index',2);
        }

        $this->Upload->begin();

        if ($this->Upload->delete($id)) {
            // Delete the files if they exist
            if (array_key_exists(0,$_item['File'])) {
                if (!unlink(UPLOAD_DIR."/{$this->_user['id']}/{$_item['File'][0]['name']}")) {
                    $this->Upload->rollback();
                    $this->flash('Delete failed', '/uploads/index',2);
                }
                if (!empty($_item['File'][0]['preview'])) {
                    if (!unlink(UPLOAD_DIR."/{$this->_user['id']}/previews/{$_item['File'][0]['preview']}")) {
                        $this->Upload->rollback();
                        $this->flash('Delete failed', '/uploads/index',2);
                    }
                }
            }

            $this->Upload->commit();
            $this->flash('Upload Deleted', '/uploads/index',2);
        } else {
            $this->flash('Delete failed', '/uploads/index',2);
        }
    }

    function index()
    {
        $this->pageTitle = 'Uploads';

        // Send all the upload data to the view
        $this->set('uploads', $this->Upload->findAllByUserId($this->_user['id']));

        if (BrowserAgent::isMobile()) {
            // We're not using render here, because it would conflict with nbFlash()
            // above (it would render both, instead of just one)
            $this->action = 'mp_index';
            $this->layout = 'mp';
        } else {
            $this->render('index');
        }
    }

}
?>
