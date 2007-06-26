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
 *   Wil Clouser <clouserw@mozilla.com> (Original Author)
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

class Upload extends AppModel
{
    var $name = 'Upload';

    var $hasAndBelongsToMany = array('User' =>
                                   array('className'  => 'User',
                                         'joinTable'  => 'uploads_users',
                                         'foreignKey' => 'upload_id',
                                         'associationForeignKey' => 'user_id'
                                        )
                                  );

    var $hasMany = array('File' =>
                           array('className'  => 'File',
                                 'conditions' => '',
                                 'order'      => ''
                                )
                        );

    /* title and referrer should not have line feeds.  Bug 375350. */
    var $validate = array(
                            'title'    => '/^.+$/',
                            'referrer' => '/^.+$/'
                         );


    /** 
     * We want to remember all things that have been delete
     * so that we can sync between the server and various
     * clients.  What we will do is null the model, but mark
     * the model's deleted column.
     */
    function delete($id) {

      if (is_numeric($id)) {
        $this->execute("UPDATE uploads set title=null, referrer=null, deleted=NOW() where id='{$id}'");
        return true;
      }

      return false;
    }
    
    /**
     * Cake isn't setup to handle a role defined in the relationship (mapping table).
     * That means we get to do custom queries anytime we care about the owner flag.
     */
    function findOwnerDataFromUploadId($id) {
        if (is_numeric($id)) {

            $_ret = $this->query("SELECT user_id FROM uploads_users WHERE upload_id='{$id}' AND owner=1");

            if (is_numeric($_ret[0]['uploads_users']['user_id'])) {

                return $this->User->findById($_ret[0]['uploads_users']['user_id']);
            }
        }

        return array();

    }


    function setOwnerForUploadIdAndUserId($upload_id, $user_id) {
        if (is_numeric($upload_id) && is_numeric($user_id)) {

            $this->execute("UPDATE uploads_users SET owner=1, modified=NOW() WHERE upload_id='{$upload_id}' AND user_id='{$user_id}'");
            
            return true;
        }

        return false;
    }

    function findCountForUserId($id) {
      if (!is_numeric($id)) {
        return -1;
      }

      $_query = "
            SELECT COUNT(*) FROM 
            uploads_users 
            WHERE uploads_users.user_id = '{$id}'
        ";

      $data = $this->query($_query);

      
      return $data[0][0]["COUNT(*)"];
    }
    
    function findAllUploadsForUserId($id, $options = array()) {

        if (!is_numeric($id)) {
            return array();
        }

        $_limit = array_key_exists('limit', $options) ? $options['limit'] : null;
        $_start = array_key_exists('start', $options) ? $options['start'] : null;
        $_types = array_key_exists('types', $options) ? $options['types'] : null;
        $_since = array_key_exists('since', $options) ? $options['since'] : null;
        $_deleted = array_key_exists('deleted', $options) ? $options['deleted'] : null;

        $_query = "
            SELECT * FROM 
            uploads_users 
            JOIN uploads as Upload ON uploads_users.upload_id = Upload.id
            JOIN files as File ON Upload.id = File.upload_id
            LEFT JOIN contentsources as Contentsource ON File.id = Contentsource.file_id
            LEFT JOIN contentsourcetypes as Contentsourcetype ON Contentsource.contentsourcetype_id = Contentsourcetype.id
            WHERE uploads_users.user_id = '{$id}'
        ";

        // user doesn't want to see deleted entries
        if ($_deleted == null) {
            $_query .= " AND Upload.deleted IS NULL";
        }

        if ($_types != null) {

            $_query .= " AND";

            $i = 0;
            while (isset ($_types[$i])) {

              if ($i > 0)
                $_query .= " OR ";

              $_query .= " File.type = '" . $_types[$i] . "'";
              $i++;
            }
        }

        if (is_numeric($_since)) {
          $timestamp = date('Y-m-d H:i:s', $_since);
          $_query .= " AND Upload.modified >= '$timestamp'";
        }
        
        if (is_numeric($_limit) && is_numeric($_start)) {
            $_query .= " LIMIT $_start, $_limit";
        } else if (is_numeric($_limit)) {
            $_query .= " LIMIT $_limit";
        }


        $data = $this->query($_query);

        return $data;
    }

    /**
     * I suggest using this function instead of findById() because it brings back
     * exactly what we want. when using findById, to get the contentsource we have to go to 3
     * levels of recursion, and that brings back way too much info.
     */
    function findDataById($id) {

        if (!is_numeric($id)) {
            return array();
        }

        $_query = "
            SELECT * FROM 
            uploads_users 
            JOIN uploads as Upload ON uploads_users.upload_id = Upload.id
            LEFT JOIN files as File ON Upload.id = File.upload_id
            LEFT JOIN contentsources as Contentsource ON File.id = Contentsource.file_id
            LEFT JOIN contentsourcetypes as Contentsourcetype ON Contentsource.contentsourcetype_id = Contentsourcetype.id
            WHERE uploads_users.upload_id = '{$id}'
        ";

        $data = $this->query($_query);

        return $data[0];

    }

}
?>
