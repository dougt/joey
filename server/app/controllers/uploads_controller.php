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
  var $components = array('Error', 'Joey', 'Pagination', 'RequestHandler', 'Session', 'Storage', 'Transcode', 'Update');
  
  //@todo review these
  var $uses = array('Phone', 'Contentsource', 'Contentsourcetype', 'File', 'Upload','User');
  
  // now with ajax
  
  var $helpers = array('Number','Time', 'Pagination', 'Ajax', 'Javascript', 'Joeyajaxupdate' );
  
  // maybe move this to the storage component or into a table
  var $filetypes = array (
                          "videos"           => array("video/3gpp", "video/flv", "video/mpeg", "video/avi", "video/quicktime"),
                          "audio"            => array("audio/x-wav", "audio/mpeg", "audio/mid", "audio/amr"),
                          "images"           => array("image/png", "image/jpeg", "image/gif", "image/tiff", "image/bmp"),
                          "rss"              => array("rss-source/text"),
                          "text"             => array("text/plain"),
                          "microsummaries"   => array("microsummary/xml", "text/html"),
                          "widgets"          => array("widget/joey"),
                          "browserstuff"     => array("browser/stuff"),
                          "all"              => array("*"),
                          );
  
  /**
   * Set in beforeFilter().  Will hold the session user data.
   */
  var $_user;

  var $securityLevel = 'low';
  
  /**
   * You can thank https://trac.cakephp.org/ticket/1589 for not letting us put this
   * in the constructor.  (Apparently that is not a valid scenario...)
   */
  function beforeFilter() {
    
    parent::beforeFilter();
    
    // Set the local user variable to the Session's User
    $this->_user = $this->Session->read('User');
  }
  
  /**
   * After add(), the UPLOADDIR has the following files:
   *   random.orig.sfx: The original uploaded file. The .sfx indicates the file type. This file name and file type are saved in db ONLY IF there is no transcoded file for this upload.
   *   random.png OR random.3gp: The transcoded file for image or video. The name and type are saved in the db.
   *   previews/random.png: The preview. File name saved in db.
   *
   * @param string type - this is only used if we're adding an rss feed via the URL
   * (no $_POST).  If it $type='rss' there also needs to be a $_GET['source'] with a
   * urlencoded() url.  For example: * /uploads/add/rss/source=http%3a%2f%2fsite.com%2frss
   *
   */
  function add($type='') {
    $this->pageTitle = 'Add an upload';
    
    $this->set('contentsourcetypes', $this->Contentsourcetype->generateList(null,null,null,'{n}.Contentsourcetype.id','{n}.Contentsourcetype.name'));

    // If they specify the type as RSS, and pass in a URL via $_GET, we'll fake the
    // POST data, so we can add the new upload
    //
    // Cake has a weird problem specifying URLs in the URL:  
    //          /uploads/add/rss/http%3A%2F%2Fdigg.com%2Frss%2Findex.xml
    // gives an apache 404 error for some reason - I guess cake is decoding the %2F's
    // for us...awesome.  We'll just use $_GET
    if ($type == 'rss' && array_key_exists('source', $_GET)) {
        $_types = $this->Contentsourcetype->findByName('rss-source/text', array('id'), null, 0);

        $this->data['Contentsourcetype']['id']   = $_types['Contentsourcetype']['id'];
        $this->data['Contentsourcetype']['name'] = 'rss-source/text'; 
        
        $this->data['Upload']['title']    = "RSS. Updating title...."; 

        // @todo make sure the rss= stuff is the right way to handle this...
        $this->data['Contentsource']['source'] = 'rss='.urldecode($_GET['source'])."\r\n";

        unset ($_types);

    }
    
    // They've submitted data
    if (!empty($this->data)) {

        // Fill in the user_id FK.  Cake needs this doubled up for the HABTM relationship
        $this->data['User']['User']['id'] = $this->_user['id'];

        // We've got two use cases.  First, let's check if they've uploaded a file successfully.
        if ( !empty($this->data['File']) && $this->data['File']['Upload']['error'] == 0 ) {

            if ($this->_saveUploadedFile()) {
                if ($this->nbClient) {
                    $this->returnJoeyStatusCode($this->SUCCESS);
                } else {
                    $this->flash('Upload saved.', '/uploads/index');
                }
            } else {
                $this->Error->addError('Failed to save uploaded file.');
            }


            // They uploaded a content source instead of a file
        } else if ( !empty($this->data['Contentsource']['source']) ) {

            if ($this->_saveUploadedContentSource()) {
                if ($this->nbClient) {
                    $this->returnJoeyStatusCode($this->SUCCESS);
                } else {
                    $this->flash('Upload saved.', '/uploads/index');
                }
            } else {
                $this->Error->addError('Failed to save uploaded file.');
            }
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
        $this->set('errors', $this->Error->errors);

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
        $this->Error->addError("Tried to deleteAll for user ({$this->_user['id']}) but failed deleting upload with id ({$id}).", 'general', false, true);
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

    /* 
     * This block detects if the Request has information from the previous page uploads. 
     * In the full web mode, we use this sessionless data forward to pass along 
     * the previous page info. So that after delete information can be produced
     * and point the user to a nice follow up page ( back to the previous scope ) */

    if (array_key_exists('previous',$_POST)) {
      $pageinfo['previous']=$_POST['previous'];
    } else if (array_key_exists('previous',$_GET)) {
      $pageinfo['previous']=$_GET['previous'];
    }
    if (array_key_exists('show',$_POST)) {
      $pageinfo['show']=$_POST['show'];
    } else if (array_key_exists('show',$_GET)) {
      $pageinfo['show']=$_GET['show'];
    }
    if (array_key_exists('type',$_POST)) {
      $pageinfo['type']=$_POST['type'];
    } else if (array_key_exists('type',$_GET)) {
      $pageinfo['type']=$_GET['type'];
    }

    /* Delete view ( rendered stuff ) may use this */ 

    $this->set('pageinfo',$pageinfo);

    /* 
     */ 

    if (!is_numeric($id)) {
      if ($this->nbClient) {

        $this->returnJoeyStatusCode($this->ERROR_DELETE);

      } else {

        $this->render('deleted_failed');

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

        $this->render('deleted_failed');

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

	  $this->set('deleted_message','Content Source Delete Failed');
	  $this->render('deleted_failed');

        }
      }
    }
    
    if ($this->Upload->delete($id)) {
      // Delete the files if they exist
      if (array_key_exists(0,$_item['File'])) {
        if (file_exists(UPLOAD_DIR."/{$this->_user['id']}/{$_item['File'][0]['name']}") && !unlink(UPLOAD_DIR."/{$this->_user['id']}/{$_item['File'][0]['name']}")) {
          $this->Error->addError('Could not delete file ('.UPLOAD_DIR."/{$this->_user['id']}/{$_item['File'][0]['name']}".')', 'general', false, true);
        }
        
        if (!empty($_item['File'][0]['original_name'])) {
          if (file_exists(UPLOAD_DIR."/{$this->_user['id']}/originals/{$_item['File'][0]['original_name']}") && !unlink(UPLOAD_DIR."/{$this->_user['id']}/originals/{$_item['File'][0]['original_name']}")) {
            $this->Error->addError('Could not delete file ('.UPLOAD_DIR."/{$this->_user['id']}/originals/{$_item['File'][0]['original_name']}".')', 'general', false, true);
          }
        }
        
        if (!empty($_item['File'][0]['preview_name'])) {
          if (file_exists(UPLOAD_DIR."/{$this->_user['id']}/previews/{$_item['File'][0]['preview_name']}") && !unlink(UPLOAD_DIR."/{$this->_user['id']}/previews/{$_item['File'][0]['preview_name']}")) {
            $this->Error->addError('Could not delete file ('.UPLOAD_DIR."/{$this->_user['id']}/previews/{$_item['File'][0]['preview_name']}".')', 'general', false, true);
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
        
          $this->render('deleted_success');
        
      }
    } else {
      if ($this->nbClient) {

        $this->returnJoeyStatusCode($this->ERROR_DELETE);

      } else {

          $this->render('deleted_failed');

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
      $joeyClientPageInfoType = $_POST['type'];
    } else if (array_key_exists('type',$_GET)) {
      $_options['types'] = $this->filetypes[ $_GET['type'] ];
      $joeyClientPageInfoType = $_GET['type'];
    }
    
   

    // check to see if we have to deal with "since"
    if (array_key_exists('since',$_POST))
      $_options['since'] = $_POST['since'];
    else if (array_key_exists('since',$_GET))
      $_options['since'] = $_GET['since'];
    
    // We are dealing with a J2ME client here
    if ($this->nbClient) {
      
      // $_GET is for testing really, because a J2ME client will only ever send POST
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
        if (array_key_exists('id',$row['Contentsource']) && !empty($row['Contentsource']['id'])) {
          
          // RSS can result in different output. For example,
          // an RSS with an enclosure may result in a video,
          // or mp3, or even an image.  What we want to do
          // here is ensure that the right mime type is
          // sent.  However, if the mime type is "just"
          // text/html, we want to preserve the
          // "rss-source/text" so that we can special treat
          // this text on j2me midlet.
          //
          // this is a bit nasty, it might be better to have
          // a tag or category attribute so that we do not
          // have to over load the meaning of content type.
          
          if ($row['Contentsourcetype']['name'] == "rss-source/text"  && $row['File']['type'] != "text/html")
            $data[$count]['type'] = $row['File']['type'];
          else
            $data[$count]['type'] = $row['Contentsourcetype']['name'];
        } 
        else 
        {
          $data[$count]['type'] = $row['File']['type'];
        }
        
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
                                   'total'     => count($this->Upload->findAllUploadsForUserId($this->_user['id'],$_options))
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

      /* Informs the view some page information. This will be used by the Delete and possibly AJAX elements. 
         The view will format this variable as markup  */

      $joeyClientPageInfo['limit'] = $limit;

	if(!empty($joeyClientPageInfoType)) {
		$joeyClientPageInfo['type']  = $joeyClientPageInfoType;

	} else {
		$joeyClientPageInfo['type'] = "";
	} 

      $joeyClientPageInfo['page']  = $page; 
      $joeyClientPageInfo['start'] = ($page-1)*$limit;

      $this->set('pageinfo',$joeyClientPageInfo);

      $_phone = $this->User->getPhoneDataByUserId($this->_user['phone_id']);
      
      // Get desired width and height for the transcoded media file
      $_width = intval($_phone['screen_width']);
      $_height = intval($_phone['screen_height']);
      
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
      
      if (BrowserAgent::isIPhone()) {

        $_options['limit'] = 100; // no real reason for this limit.
        $_options['start'] = 0;

        $_options['types'] = $this->filetypes["all"];
        $this->set("uploads", $this->Upload->findAllUploadsForUserId($this->_user['id'], $_options));

        // To make it easier on the view, break these all
        // apart.  Maybe other views will want it this way
        // too.
        $_options['types'] = $this->filetypes["videos"];
        $this->set("videos", $this->Upload->findAllUploadsForUserId($this->_user['id'], $_options));

        $_options['types'] = $this->filetypes["audio"];
        $this->set("audio", $this->Upload->findAllUploadsForUserId($this->_user['id'], $_options));

        $_options['types'] = $this->filetypes["images"];
        $this->set("images", $this->Upload->findAllUploadsForUserId($this->_user['id'], $_options));


        $_options['types'] = $this->filetypes["microsummaries"];
        $this->set("microsummaries", $this->Upload->findAllUploadsForUserId($this->_user['id'], $_options));

        $_options['types'] = $this->filetypes["rss"];
        $this->set("rss", $this->Upload->findAllUploadsForUserId($this->_user['id'], $_options));


        // Stuff below this we are about the original content, not the transcoded content.
        $_options['original'] = "original";  // @todo, i think we can stop doing this.

        $_options['types'] = $this->filetypes["browserstuff"];
        $this->set("browserstuff", $this->Upload->findAllUploadsForUserId($this->_user['id'], $_options));

        $_options['types'] = $this->filetypes["text"];
        $this->set("text", $this->Upload->findAllUploadsForUserId($this->_user['id'], $_options));

        $this->action = 'iphone_index';
        $this->layout = 'iphone';
      }
      else if (BrowserAgent::isMobile()) {
        $this->action = 'mp_index';
        $this->layout = 'mp';
      } else {
        $this->render('index');
      }
    }
  }

    /**
     * Private function to save an uploaded contentsource.  Called from
     * UploadController::Add()
     */
    function _saveUploadedContentSource() {
        // check for duplicates @todo - this doesn't check the user_id (bug 387369)
        /*
        $_contentdup = $this->Contentsource->findBySource($this->data['Contentsource']['source']);
        
        if (!empty($_contentdup)) {
          
          if ($this->nbClient) {
            $this->returnJoeyStatusCode($this->ERROR_DUPLICATE);
          }

          $this->Error->addError('Duplicate content detected - looks like you\'ve already uploaded this.');
        }
        */

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

        if (! ($this->Upload->validates($this->data) && $this->Contentsource->validates($this->data) && $this->Contentsourcetype->validates($this->data)) ) {
            return false;
        }

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

                    // @todo - remove this.  This needs to happen offline (bug 386777)
                    $this->Update->updateContentSourceByUploadId($this->Upload->id, true);

                    $this->Upload->commit();

                    return true;

                } else {
                    $this->Upload->rollback();
                    $this->Error->addError('There was an error saving your upload.');
                }

            } else {
                $this->Upload->rollback();
                $this->Error->addError('There was an error saving your upload.');
            }

        } else {
            $this->Upload->rollback();
            $this->Error->addError('There was an error saving your upload.');
        }

        return false;
          
    }

    /**
     * Private function to save an uploaded file.  Called from
     * UploadController::Add()
     */
    function _saveUploadedFile() {

        // Make sure we're dealing with an uploaded file
        if (!is_uploaded_file($this->data['File']['Upload']['tmp_name'])) {
            $this->Error->addError('Could not locate uploaded file.', 'File/Upload', true, true);
            return false;
        }

        // Check to see if the user has any additonal space.
        $filesize = filesize($this->data['File']['Upload']['tmp_name']);
        if (!$this->User->hasAvailableSpace($this->_user['id'], $filesize)) {

            if ($this->nbClient) {
                $this->returnJoeyStatusCode($this->ERROR_NO_SPACE);
            }

            $_used = $this->Joey->bytesToReadableSize($this->User->totalSpaceUsedByUserId($this->_user['id']));
            $_max  = MAX_DISK_USAGE.' MB'; //this is already in MB
            $_size = $this->Joey->bytesToReadableSize($filesize);
            $this->Error->addError("You don't have enough space to save that file.  You're using {$_used} out of {$_max} and your upload takes up {$_size}.", 'File/Upload');
            unlink($this->data['File']['Upload']['tmp_name']);
            return false;
        }

        // Check for required form fields
        if (! ($this->Upload->validates($this->data) && $this->File->validates($this->data))) {
            return false;
        }

        // Attempt to upload a filetype we don't accept.
        if (!array_key_exists($this->data['File']['Upload']['type'], $this->Storage->suffix)) {
            $this->Error->addError('Could not save your file (invalid filetype).');
            return false;
        }


        // if the type is something that needs to prevent
        // duplicates, AND the URL and type all match,
        // we have a duplicate and we need to update.
        // @todo: maybe move to update.php?
          
        if ($this->data['File']['Upload']['type'] == "browser/stuff")
        {
          $prior_upload = $this->Upload->findDataByTypeAndURL($this->data['File']['Upload']['type'],$this->data['Upload']['referrer']);          

          if (!empty($prior_upload['File']['original_name']))
          {
            $_destination_file = UPLOAD_DIR."/{$this->_user['id']}/originals/".$prior_upload['File']['original_name'];
            
            if (!move_uploaded_file($this->data['File']['Upload']['tmp_name'], $_destination_file)) {
              $this->Error->addError('Failed to move uploaded file. (' . $this->data['File']['Upload']['tmp_name'] . ' -> ' . $_destination_file . ')', 'File/Upload', true, true);
              return false;
            }
            
            $this->Transcode->transcodeFileById($prior_upload['File']['id']);
            return true;
          }
        }

        $_rand = uniqid('',true);
    
        $_destination_file = UPLOAD_DIR."/{$this->_user['id']}/originals/joey-{$_rand}.{$this->Storage->suffix[$this->data['File']['Upload']['type']]}";

        if (!move_uploaded_file($this->data['File']['Upload']['tmp_name'], $_destination_file)) {
            $this->Error->addError('Failed to move uploaded file. (' . $this->data['File']['Upload']['tmp_name'] . ' -> ' . $_destination_file . ')', 'File/Upload', true, true);
            return false;
        }

        $this->data['File']['original_name'] = basename($_destination_file);
        $this->data['File']['original_type'] = $this->data['File']['Upload']['type'];
        $this->data['File']['original_size'] = filesize($_destination_file);  

        // Start our transaction
        $this->Upload->begin();
          
        if ($this->Upload->save($this->data)) {

            $this->data['File']['upload_id'] = $this->Upload->id;

            unset($this->data['File']['Upload']);

            if ($this->File->save($this->data)) {

                $this->Upload->setOwnerForUploadIdAndUserId($this->Upload->id, $this->_user['id']);

                $this->Upload->commit();

                // @todo This needs to happen offline (bug 386777)
                $this->Transcode->transcodeFileById($this->File->id);

                return true;

            } else {
                $this->Upload->rollback();
                $this->Error->addError('Could not save your file.');
            }

        } else {
            $this->Upload->rollback();
            $this->Error->addError('Could not save your upload.');
        }

        return false;
    }

}
?>
