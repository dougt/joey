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

    // ajax
    var $components = array('Joey', 'Pagination', 'Session', 'Storage', 'RequestHandler');

//@todo review these
    var $uses = array('Phone', 'Contentsource', 'Contentsourcetype', 'File', 'Upload','User');

    // now with ajax

    var $helpers = array('Number','Time', 'Pagination', 'Ajax', 'Javascript');

    // maybe move this to the storage component or into a table
    var $filetypes = array (
                            "videos"           => array("video/3gpp", "video/flv", "video/mpeg", "video/avi", "video/quicktime"),
                            "audio"            => array("audio/x-wav", "audio/mpeg", "audio/mid"),
                            "images"           => array("image/png", "image/jpeg", "image/gif", "image/tiff", "image/bmp"),
                            "rss"              => array("rss-source/text"),
                            "text"             => array("text/plain"),
                            "microsummaries"   => array("microsummary/xml"),
                            "widgets"          => array("widget/joey"),
                            "all"              => array("*"),
                            );

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


    function add_rss()
    {
      $rss_source = $_GET['rss'];

      // put this rss link into a format that we care about.
      $rss_source = "rss=" . $rss_source . "\r\n";

      // Fill in the user_id FK.  Cake needs this doubled up for the HABTM relationship
      $this->data['User']['User']['id'] = $this->_user['id'];

      $this->Upload->settings['throw_error'] = true;
      $this->Contentsource->settings['throw_error'] = true;

      // check for duplicates
      $_contentdup = $this->Contentsource->findBySource($rss_source);
      
      // Allow for now!
      //      if (!empty($_contentdup)) {
      //        $this->flash('Error - Duplicate found.', '/uploads/index');
      //        return;
      //      }

      // Hard coded.  These values must match your db.  if
      // you didn't change anything in
      // app/config/sql/joey.sql, you should be golden.

      $this->data['Contentsourcetype']['id'] = 2;
      $this->data['Contentsourcetype']['name'] = "rss-source/text"; 

      $this->data['Upload']['title']    = "RSS. Updating title...."; 
      $this->data['Upload']['referrer'] = "n/a"; 

      $this->data['Contentsource']['source'] = $rss_source;


      if (!$this->Upload->validates($this->data) || !$this->Contentsource->validates($this->data) || !$this->Contentsourcetype->validates($this->data)) 
      {
          $this->flash('RSS Add Failed. Upload could not ve validated', '/uploads/index');
          return;
      }

      // Start our transaction.  
      $this->Upload->begin();

      if ($this->Upload->save($this->data) == false)
      {
          $this->Upload->rollback();
          $this->flash('RSS Add Failed. Upload could not be saved', '/uploads/index');
          return;
      }

      // Create a new file row
      if (($_file_id = $this->Storage->createFileForUploadId($this->Upload->id, $this->data['Contentsourcetype']['name'])) == false) 
      {
        $this->Upload->rollback();
        $this->flash('RSS Add Failed.  Could not create file for upload.', '/uploads/index');
        return;
      }

      // gg cake
      $this->data['Contentsource']['file_id']              = $_file_id;
      $this->data['Contentsource']['contentsourcetype_id'] = $this->data['Contentsourcetype']['id'];
      
      if ($this->Contentsource->save($this->data) == false) 
      {
          $this->Upload->rollback();
          $this->flash('RSS Add Failed. Could not save content source', '/uploads/index');
          return;
      }

      if ($this->Upload->setOwnerForUploadIdAndUserId($this->Upload->id, $this->_user['id']) == false)
      {
        $this->Upload->rollback();
        $this->flash('RSS Add Failed. Could not set owner of upload.', '/uploads/index');
        return;
      }

      if ($this->Storage->updateFileByUploadId($this->Upload->id, true) == false)
      {
        $this->Upload->rollback();
        $this->flash('RSS Add Failed. Could not update file.', '/uploads/index');
        return;
      }

      $this->Upload->commit();
      $this->flash('RSS Added.', '/uploads/index');
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
            
            // Fill in the user_id FK.  Cake needs this doubled up for the HABTM relationship
            $this->data['User']['User']['id'] = $this->_user['id'];

            // We've got two use cases.  First, let's check if they've uploaded a
            // file successfully.
            if ( !empty($this->data['File']) && $this->data['File']['Upload']['error'] == 0 ) {

                if (!is_uploaded_file($this->data['File']['Upload']['tmp_name'])) {
                    $this->File->invalidate('Upload');
                    $this->set('error_fileupload', 'Could not locate uploaded file.');
                }

                // Check to see if the user has any additonal space.
                $filesize = filesize($this->data['File']['Upload']['tmp_name']);

                if ($this->Storage->hasAvailableSpace($this->_user['id'], $filesize) == false) {
                    if ($this->nbClient) {
                      $this->returnJoeyStatusCode($this->ERROR_NO_SPACE);
                    } else {
                      $_used = $this->Joey->bytesToReadableSize($this->User->totalSpaceUsedByUserId($this->_user['id']));
                      $_max  = MAX_DISK_USAGE.' MB'; //this is already in MB
                      $_size = $this->Joey->bytesToReadableSize($filesize);
                      $this->set('error_fileupload', "You don't have enough space to save that file. You're using {$_used} out of {$_max} and your upload takes up {$_size}.");
                    }
                    $this->File->invalidate('Upload');
                    unlink($this->data['File']['Upload']['tmp_name']);
                }

                // Check if our form validates.  This only checks the stuff in the
                // Upload and File models, (ie. are required fields filled in?)
                if ($this->Upload->validates($this->data) && $this->File->validates($this->data)) {
                    
                    // Get desired width and height for the transcoded media file
                    $_phone = $this->Phone->findById($this->_user['phone_id']);
                    $_width = intval ($_phone['Phone']['screen_width']);
                    $_height = intval ($_phone['Phone']['screen_height']);

                    // @todo fix data?
                    if ($_width < 1 || $_height < 1) {
                      // we have really no idea what the size should be, so lets just say
                      $_width  = 100;
                      $_height = 100;
                    }

                    // Put our file away, generate the transcode file for mobile, as well as the preview. 
                    $_ret = $this->Storage->processUpload($this->data['File']['Upload']['tmp_name'], $this->_user['id'], $this->data['File']['Upload']['type'], $_width, $_height);
                    
                    if ($_ret == null) {
                      $this->File->invalidate('Upload');
                      $this->set('error_fileupload', 'Could not move uploaded file.');
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

                    if ($this->Upload->save($this->data)) {

                      $this->data['File']['upload_id'] = $this->Upload->id;

                      // This doesn't really matter anymore, but might as well whack it
                      unset($this->data['File']['Upload']);

                      if ($this->File->save($this->data)) {

                        $this->Upload->setOwnerForUploadIdAndUserId($this->Upload->id, $this->_user['id']);

                        $this->Upload->commit();

                        if ($this->nbClient) {
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

                // check for duplicates
                $_contentdup = $this->Contentsource->findBySource($this->data['Contentsource']['source']);

                if (!empty($_contentdup)) {
                  
                  if ($this->nbClient) {
                    $this->returnJoeyStatusCode($this->ERROR_DUPLICATE);
                  } else {
                    $this->flash('Error - Duplicate found.', '/uploads/index');
                  }
                  return;
                }

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
                    } else {
                        $this->Contentsource->invalidate('name');
                    }
                } else if(!empty($this->data['Contentsourcetype']['id'])) {
                    // As it turns out, we need the type as a string too
                    $_contentsource = $this->Contentsourcetype->findById($this->data['Contentsourcetype']['id'], array('name'), null, 0);
                    $this->data['Contentsourcetype']['name'] = $_contentsource['Contentsourcetype']['name'];
                }

                unset($_contentsource);

                if ($this->Upload->validates($this->data) && $this->Contentsource->validates($this->data) && $this->Contentsourcetype->validates($this->data)) {

                    // Start our transaction.  It doesn't matter what model we start
                    // or end it on, all saves will be a part of it.
                    $this->Upload->begin();

                    // This shouldn't ever fail, since we validated it
                    if ($this->Upload->save($this->data)) {
                        // Create a new file row
                        if (($_file_id = $this->Storage->createFileForUploadId($this->Upload->id, $this->data['Contentsourcetype']['name'])) !== false) {

                            // gg cake
                            $this->data['Contentsource']['file_id']              = $_file_id;
                            $this->data['Contentsource']['contentsourcetype_id'] = $this->data['Contentsourcetype']['id'];

                            if ($this->Contentsource->save($this->data)) {

                                $this->Upload->setOwnerForUploadIdAndUserId($this->Upload->id, $this->_user['id']);
                                $this->Storage->updateFileByUploadId($this->Upload->id, true);

                                $this->Upload->commit();

                                if ($this->nbClient) {
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

                    } else {
                        $this->Upload->rollback();
                        $this->set('error_mesg', 'There was an error saving your upload.');
                    }

                }
            } else {
                // Something is wrong.  Either there was an error uploading the file, or
                // they sent an incomplete POST.  Either way, not much we can do.
                $this->set('error_mesg', 'Failing: There was an error saving your upload.');
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
      $data = $this->User->findAllById($this->_user['id']);
      $_count = 0;

      foreach ($data[0]['Upload'] as $row) {
        
        $id = $row['id'];

        if ($this->delete($id) == false) {
          $this->log("deleteAll: Delete of " . $id . " failed.");
        }  else {
          $_count++;
        }
        
      }

      $this->flash("{$_count} items deleted", '/uploads/index', 2);
    }

    function deleteByFileID($id)
    {
      $_item = $this->File->findById($id);
      if (is_numeric($_item['Upload']['id'])) {
          $this->delete($_item['Upload']['id']);
      }
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

        $_item = $this->Upload->findById($id, null,null,2);//@todo this pulls way too much data

        if (!empty($_item['Upload']['deleted']))
          return; // do nothing (hide) for deleted items.

        $_owner = $this->Upload->findOwnerDataFromUploadId($id);

        // Check for access
        if (empty($_owner) || ($_owner['User']['id'] != $this->_user['id'])) {
            if ($this->nbClient) {
                $this->returnJoeyStatusCode($this->ERROR_NOAUTH);
            } else {
                $this->flash('Delete failed', '/uploads/index',2);
            }
        }


        $this->Upload->begin();

        // If this is a content source upload, kill that too.
        if (!empty( $_item['File'][0]['Contentsource'] )) {
          $csid = $_item['File'][0]['Contentsource'][0]['id'];

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
                
                if (!empty($_item['File'][0]['original_name'])) {
                    if (!unlink(UPLOAD_DIR."/{$this->_user['id']}/originals/{$_item['File'][0]['original_name']}")) {
                      // Don't make this fatal.  If we couldn't unlink, it is a warning
                      //$this->Upload->rollback();
                      //$this->flash('Delete failed', '/uploads/index',2);
                    }
                }
                
                if (!empty($_item['File'][0]['preview_name'])) {
                    if (!unlink(UPLOAD_DIR."/{$this->_user['id']}/previews/{$_item['File'][0]['preview_name']}")) {
                      // Don't make this fatal.  If we couldn't unlink, it is a warning
                      //$this->Upload->rollback();
                      //$this->flash('Delete failed', '/uploads/index',2);
                    }
                }
            }

            
            // Delete the file record.
            $_file_id = $_item['File'][0]['id'];
            $this->File->delete($_file_id);


            $this->Upload->commit();
            if ($this->nbClient) {
                $this->returnJoeyStatusCode($this->SUCCESS);
            } else {

              //  $this->flash('Upload Deleted', '/uploads/index',2);

	      // ajax

	      #this->render('ajaxdeleted','ajax');


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
      
      $this->set('uploads', $this->Upload->findAllUploadsForUserId($this->_user['id']));

    }

    function index()
    {
        $_options = array();

        // check to see if we have a filter "type"
        if (array_key_exists('type',$_POST)) {
          $_options['types'] = $this->filetypes[ $_POST['type'] ];
        } else if (array_key_exists('type',$_GET)) {
          $_options['types'] = $this->filetypes[ $_GET['type'] ];
        }

        // check to see if we have to deal with "since"
        if (array_key_exists('since',$_POST))
          $_options['since'] = $_POST['since'];
        else if (array_key_exists('since',$_GET))
          $_options['since'] = $_GET['since'];

        // We are dealing with a J2ME client here
        if ($this->nbClient) {

          // testing $_GET is for testing really....
            if (array_key_exists('limit',$_GET)) { $_options['limit'] = $_GET['limit']; }
            if (array_key_exists('start',$_GET)) { $_options['start'] = $_GET['start']; }

            if (array_key_exists('limit',$_POST)) { $_options['limit'] = $_POST['limit']; }
            if (array_key_exists('start',$_POST)) { $_options['start'] = $_POST['start']; }
        
            // the nbclient needs to see deleted items.
            $_options['deleted'] = true;

            $data = $this->Upload->findAllUploadsForUserId($this->_user['id'], $_options);
            
            $total_count = $this->Upload->findCountForUserId($this->_user['id']);

            $count = 0;
            foreach ($data as $row) {

                if (!empty($row['File']['preview_name']) && file_exists(UPLOAD_DIR."/{$this->_user['id']}/previews/{$row['File']['preview_name']}")) {
                    $preview_data = file_get_contents (UPLOAD_DIR."/{$this->_user['id']}/previews/{$row['File']['preview_name']}");
                    $data[$count]['preview'] = base64_encode($preview_data);
                } else {
                    $data[$count]['preview'] = '';
                }
                
                // left joins bring back null rows
                if (array_key_exists('id',$row['Contentsource']) && !empty($row['Contentsource']['id'])) 
                {

                  // RSS can result in differnt output. For
                  // example, an RSS with an enclosure may
                  // result in a video, or mp3, or even an
                  // image.  What we want to do here is
                  // ensure that the right mime type is
                  // sent.  However, if the mime type is
                  // "just" text/html, we want to preserve
                  // the "rss-source/text" so that we can
                  // special treat this text on j2me midlet.
                  //
                  // this is a bit nasty, it might be better
                  // to have a tag or category attribute so
                  // that we do not have to over load the
                  // meaning of content type.

                  if ($row['Contentsourcetype']['name'] == "rss-source/text"  && $row['File']['type'] != "text/html")
                    $data[$count]['type'] = $row['File']['type'];
                  else
                    $data[$count]['type'] = $row['Contentsourcetype']['name'];
                } 
                else 
                {
                    $data[$count]['type'] = $row['File']['type'];
                }


                echo $row['Contentsourcetype']['name'] . "\n";
                echo $row['File']['type'];
                
                $count++;
            }
            
            $this->set('uploads', $data);
            $this->set('count', $count);
            $this->set('total_count', $total_count);
            $this->layout = NULL;
            $this->action = 'j2me_index';
            header("X-joey-status: 200");
        } else {
            // Render a page for the browser client

            $this->pageTitle = 'Uploads';

            $_pagination_options = array(
                                'direction' => 'DESC',
                                'sortBy'    => 'id',
                                'total'     => count($this->Upload->findAllUploadsForUserId($this->_user['id']))
                            );

            list(,$limit,$page) = $this->Pagination->init(array(), array(), $_pagination_options);

            // @todo need to calculate $start
            $_options['limit'] = $limit;
            $_options['start'] = ($page-1)*$limit;

            // @todo sometimes this is neg -- why?
            if ($_options['start'] < 0)
              $_options['start'] = 0;

            $data = $this->Upload->findAllUploadsForUserId($this->_user['id'], $_options);

            $this->set('uploads', $data);

            $_phone = $this->Phone->findById($this->_user['phone_id'], null, null, 0);

            // Get desired width and height for the transcoded media file
            $_width = intval($_phone['Phone']['screen_width']);
            $_height = intval($_phone['Phone']['screen_height']);

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
                $this->action = 'mp_index';
                $this->layout = 'mp';
            } else {
                $this->render('index');
            }
        }
    }
}
?>
