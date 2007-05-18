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

    var $components = array('Session','Storage', 'Pagination');

    var $uses = array('Phone', 'Contentsource', 'Contentsourcetype', 'File', 'Upload');

    var $helpers = array('Number','Time', 'Pagination');

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

    /**
     * After add(), the UPLOADDIR has the following files:
     *   random.orig.sfx: The original uploaded file. The .sfx indicates the file type. This file name and file type are saved in db ONLY IF there is no transcoded file for this upload.
     *   random.png OR random.3gp: The transcoded file for image or video. The name and type are saved in the db.
     *   previews/random.png: The preview. File name saved in db.
     */
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

                    // Check to see if the user has any additonal space.
                    $filesize = filesize($this->data['File']['Upload']['tmp_name']);
                    if ($this->Storage->hasAvailableSpace($this->_user['id'], $filesize) == false) {
                        if ($this->nbClient) {
                          // $this->nbFlash(NB_CLIENT_ERROR_OUT_OF_SPACE);
                          $this->returnJoeyStatusCode($this->ERROR_NO_SPACE);
                        } else {
                          $this->File->invalidate('Upload');
                          $this->set('error_fileupload', 'Out of space.');
                        }
                        // unlink($_filename);
                        // Go ahead and just bail.
                        return;
                    }
                    
                    // Get desired width and height for the transcoded media file
                    $_phone = $this->Phone->findById($this->_user['phone_id']);
                    $_width = intval ($_phone['Phone']['screen_width']);
                    $_height = intval ($_phone['Phone']['screen_height']);

                    // @todo fix data?
                    if ($_width < 1 || $_height < 1)
                    {
                      // we have really no idea what the
                      // size should be, so lets just say

                      $_width  = 100;
                      $_height = 100;
                      
                    }

                    // Put our file away, generate the transcode file for mobile, as well as the preview.  This better not ever fail, but on the
                    // off chance it does, we'll invalidate the file upload.
                    // This will make the save below fail, so the db won't get
                    // out of sync.
                    $_ret = $this->Storage->processUpload($this->data['File']['Upload']['tmp_name'], $this->_user['id'], $this->data['File']['Upload']['type'], $_width, $_height);
                    
                    if ($_ret == null) {
                            $this->File->invalidate('Upload');
                            $this->set('error_fileupload', 'Could not move uploaded file.');
                            return;
                    }
                    
                    $this->data['File']['name'] = basename($_ret['default_name']);
                    $this->data['File']['type'] = $_ret['default_type'];
                    $this->data['File']['size'] = filesize($_ret['default_name']);
                    
                    if (!empty($_ret['original_name'])) {
                      $this->data['File']['original_name'] = basename($_ret['original_name']);
                      $this->data['File']['original_type'] = $_ret['original_type'];
                      $this->data['File']['original_size'] = filesize($_ret['original_name']);  
                    }
                    
                    if (!empty($_ret['preview_name'])) {
                      $this->data['File']['preview_name'] = basename($_ret['preview_name']);
                      $this->data['File']['preview_type'] = $_ret['preview_type'];
                      $this->data['File']['preview_size'] = filesize($_ret['preview_name']);  
                    }
                        

                    // Start our transaction
                    $this->Upload->begin();

                    // We've already validated it - there isn't really any reason
                    // this should fail.
                    if ($this->Upload->save($this->data)) {

                      $this->data['File']['upload_id'] = $this->Upload->id;

                      // This doesn't really matter anymore, but might as well whack it
                      unset($this->data['File']['Upload']);

                      if ($this->File->save($this->data)) {
                        $this->Upload->commit();
                        if ($this->nbClient) {
                          // $this->nbFlash($this->Upload->id);
                          $this->returnJoeyStatusCode($this->SUCCESS);
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

                            $this->Storage->updateFileByUploadId($this->Upload->id, true);

                            if ($this->nbClient) {
                                // $this->nbFlash($this->Upload->id);
                                $this->returnJoeyStatusCode($this->SUCCESS);
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
                // $this->nbFlash(NB_CLIENT_ERROR_UPLOAD_FAIL);
                $this->returnJoeyStatusCode($this->ERROR_UPLOAD);
            }

        }

        // handle XHTML MP browser
        if (BrowserAgent::isMobile()) {
          $this->action = 'mp_add';
          $this->layout = 'mp';
        }
    }

    function deleteAll()
    {
      $data = $this->Upload->findAllByUser_id($this->_user['id']);
      $count = 0;

      foreach ($data as $row) {
        
        $id = $data[$count]['Upload']['id'];
        if ($this->delete($id) == false) {
          $this->log("deleteAll: Delete of " . $id . " failed.");
        } 
        
        $count = $count + 1;
      }

      $this->flash("{$count} items deleted", '/uploads/index', 2);
    }

    function delete($id)
    {
        if (!is_numeric($id)) {
            if ($this->nbClient) {
                $this->returnJoeyStatusCode($this->ERROR_DELETE);
            } else {
                $this->flash('Delete failed', '/uploads/index',2);
            }
        }

        $_item = $this->Upload->findById($id);

        // Check for access
        if ($_item['Upload']['user_id'] != $this->_user['id']) {
            if ($this->nbClient) {
                $this->returnJoeyStatusCode($this->ERROR_NOAUTH);
            } else {
                $this->flash('Delete failed', '/uploads/index',2);
            }
        }

        $this->Upload->begin();

        // If this is a content source upload, kill that too.
        if (!empty( $_item['Contentsource'] ))
        {
          $csid = $_item['Contentsource'][0]['id'];

          if (! $this->Contentsource->delete($csid)) {
              $this->Upload->rollback();
              if ($this->nbClient) {
                  $this->returnJoeyStatusCode($this->ERROR_DELETE);
              } else { 
                  $this->flash('Content Source Delete Failed', '/uploads/index',2);
              }
          }
        }

        if ($this->Upload->delete($id)) {
            // Delete the files if they exist
            if (array_key_exists(0,$_item['File'])) {
                if (!unlink(UPLOAD_DIR."/{$this->_user['id']}/{$_item['File'][0]['name']}")) {
                      // Don't make this fatal.  If we couldn't unlink, it is a warning
                      //$this->Upload->rollback();
                      //$this->flash('Delete failed', '/uploads/index',2);
                }
                
                if (!empty($_item['File'][0]['original'])) {
                    if (!unlink(UPLOAD_DIR."/{$this->_user['id']}/originals/{$_item['File'][0]['original']}")) {
                      // Don't make this fatal.  If we couldn't unlink, it is a warning
                      //$this->Upload->rollback();
                      //$this->flash('Delete failed', '/uploads/index',2);
                    }
                }
                
                if (!empty($_item['File'][0]['preview'])) {
                    if (!unlink(UPLOAD_DIR."/{$this->_user['id']}/previews/{$_item['File'][0]['preview']}")) {
                      // Don't make this fatal.  If we couldn't unlink, it is a warning
                      //$this->Upload->rollback();
                      //$this->flash('Delete failed', '/uploads/index',2);
                    }
                }
            }

            $this->Upload->commit();
            if ($this->nbClient) {
                $this->returnJoeyStatusCode($this->SUCCESS);
            } else {
                $this->flash('Upload Deleted', '/uploads/index',2);
            }
        } else {
            if ($this->nbClient) {
                $this->returnJoeyStatusCode($this->ERROR_DELETE);
            } else {
                $this->flash('Delete failed', '/uploads/index',2);
            }
        }
    }

    function rss()
    {
      $this->layout = 'xml';
      
      $criteria=array('user_id' => $this->_user['id']);
      $data = $this->Upload->findAll($criteria, NULL, "Upload.modified DESC", 15);
      $this->set('uploads', $data);

    }

    function index()
    {
        if ($this->nbClient) {

            // Index always returns 200.
            header ("X-joey-status: 200");

            // We are dealing with a J2ME client here
            if (array_key_exists('limit',$_POST)) {
                $limit = $_POST['limit'];
            } else {
                // @todo this should have a (smaller) cap on it to avoid a DOS
                $limit = 100000;
            }
            if (array_key_exists('start',$_POST)) {
                $start = $_POST['start'];
            } else {
                $start = 0;
            }
            
            $criteria=array('user_id' => $this->_user['id']);
            $data = $this->Upload->findAll($criteria, NULL, 'Upload.modified DESC', $limit, $start, 3);
            $count = 0;
            foreach ($data as $row) {
                if (empty($row['File'][0]['preview_name'])) {
                    $data[$count]['preview'] = '';
                } else {
                    $preview_data = file_get_contents (UPLOAD_DIR."/{$this->_user['id']}/previews/{$row['File'][0]['preview_name']}");
                    $data[$count]['preview'] = base64_encode($preview_data);
                }
                
                if (array_key_exists(0,$row['Contentsource'])) {
                    $data[$count]['type'] = $row['Contentsource'][0]['Contentsourcetype']['name'];
                } else {
                    $data[$count]['type'] = $row['File'][0]['type'];
                }
                
                $count = $count + 1;
            }
            
            $this->set('uploads', $data);
            $this->set('count', $count);
            $this->layout = NULL;
            $this->action = 'j2me_index';
        
        } else {
            // Render a page for the browser client
            
            $this->pageTitle = 'Uploads';

            $criteria=array('user_id' => $this->_user['id']);
            $options=array('sortBy' => 'id', 'direction' => 'DESC');
            list($order,$limit,$page) = $this->Pagination->init($criteria, NULL, $options); // Added
            $data = $this->Upload->findAll($criteria, NULL, $order, $limit, $page); // Extra parameters added
        
            $this->set('uploads', $data);
 

            // Get desired width and height for the transcoded media file
            $_phone = $this->Phone->findById($this->_user['phone_id']);
            $_width = intval ($_phone['Phone']['screen_width']);
            $_height = intval ($_phone['Phone']['screen_height']);

            //@todo fix data?
            if ($_width < 1 || $_height < 1)
            {
              // we have really no idea what the
              // size should be, so lets just say
              $_width  = 100;
              $_height = 100;
            }

            // @todo previews are hardcoded to be 1/2.  fix up? 
            $this->set('upload_preview_width', $_width /2);
            $this->set('upload_preview_height', $_height/2);

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
    
    function j2me_index()
    {
            // We are dealing with a J2ME client here
            if (array_key_exists('limit',$_POST)) {
                $limit = $_POST['limit'];
            } else {
                // @todo this should have a (smaller) cap on it to avoid a DOS
                $limit = 100000;
            }
            if (array_key_exists('start',$_POST)) {
                $start = $_POST['start'];
            } else {
                $start = 0;
            }
            
            $criteria=array('user_id' => $this->_user['id']);
            $data = $this->Upload->findAll($criteria, NULL, NULL, $limit, $start, 3);
            $count = 0;
            foreach ($data as $row) {
                if (empty($row['File'][0]['preview_name'])) {
                    $data[$count]['preview'] = '';
                } else {
                    $preview_data = file_get_contents (UPLOAD_DIR."/{$this->_user['id']}/previews/{$row['File'][0]['preview_name']}");
                    $data[$count]['preview'] = base64_encode($preview_data);
                }
                
                if (array_key_exists(0,$row['Contentsource'])) {
                    $data[$count]['type'] = $row['Contentsource'][0]['Contentsourcetype']['name'];
                } else {
                    $data[$count]['type'] = $row['File'][0]['type'];
                }
                
                $count = $count + 1;
            }
            
            $this->set('uploads', $data);
            $this->set('count', $count);
            $this->layout = NULL;
            $this->action = 'j2me_index';
    }

}
?>
