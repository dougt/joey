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

class User extends AppModel
{
    var $name = 'User';

    var $belongsTo = array('Operator' =>
                           array('className'  => 'Operator',
                                 'conditions' => '',
                                 'order'      => ''
                                ),
                           'Phone' =>
                           array('className'  => 'Phone',
                                 'conditions' => '',
                                 'order'      => ''
                                )
                         );

    var $hasAndBelongsToMany = array('Upload' =>
                                   array('className'  => 'Upload',
                                         'joinTable'  => 'uploads_users',
                                         'foreignKey' => 'user_id',
                                         'associationForeignKey' => 'upload_id'
                                        )
                                  );

    var $validate = array(
                            'username'    => '/^\w+$/',
                            'password'    => VALID_NOT_EMPTY,
                            'phonenumber' => VALID_NOT_EMPTY,
                            'email'       => VALID_EMAIL
                         );

    /**
     * Retrieve basic phone information for the user.  This is just a shortcut
     * function.
     * @param int user_id
     * @return array phone information
     */
    function getPhoneDataByUserId($user_id)
    {
        if (!is_numeric($user_id)) {
            return array();
        }

        $_result = $this->findById($user_id, null, null, 0);

        return $_result['Phone'];

    }

    /**
     * Check to see if the user has available space for the
     * additional content.
     * @param int user id
     * @param int size (in bytes) of requested space
     */

    function hasAvailableSpace($userid, $additional) {

        $totalused = $this->totalSpaceUsedByUserId($userid);
        // $additional and $totalused is in bytes, MAX_DISK_USAGE is in MB
        if ( ($additional + $totalused) > (MAX_DISK_USAGE * 1024 * 1024)) {
            return false;
        }
        return true;
    }

    /**
     * Check the total space used for a user
     * @param int user id
     */
    function totalSpaceUsedByUserId($user_id)
    {
        // Just double check
        if (!is_numeric($user_id)) {
            return 0;
        }

        $query = "SELECT sum(files.size+files.original_size+files.preview_size) as `total`
                    FROM files 
                    JOIN uploads ON files.upload_id = uploads.id
                    JOIN uploads_users ON uploads.id = uploads_users.upload_id
                    WHERE uploads_users.user_id = '{$user_id}'";

        $ret = $this->query($query);

        // If they don't have uploads, this is null
        if (empty($ret[0][0]['total'])) {
            return 0;
        }

        return $ret[0][0]['total'];
    }

}
?>
