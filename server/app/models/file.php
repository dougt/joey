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

class File extends AppModel
{
    var $name = 'File';

    var $belongsTo = array(
                           'Upload' =>
                           array('className'  => 'Upload',
                                 'conditions' => '',
                                 'order'      => ''
                                )
    );
    var $hasMany = array('Contentsource' =>
                           array('className'  => 'Contentsource',
                                 'conditions' => '',
                                 'order'      => ''
                                )
                        );

    /* name and type should not have line feeds.  Bug 375350. */
    var $validate = array(
                            'name'  => '/^.+$/',
                            'type'  => '/^.+$/'
                         );


    /** 
     * We want to remember all things that have been delete
     * so that we can sync between the server and various
     * clients.  What we will do is null the model, but mark
     * the model's deleted column.
     */
    function delete($id) {

      if (is_numeric($id)) {
        $this->execute("UPDATE files set name=null, size=0, type=null, original_name=null, original_type=null, original_size=0, preview_name=null, preview_type=null, preview_size=null, deleted=NOW() where id={$id}");
      }
      return true;
    }

    function findOwnerDataFromFileId($id) {
        if (is_numeric($id)) {

            $_query = "
                SELECT 
                    uploads_users.user_id 
                FROM 
                    uploads_users, 
                    uploads, 
                    files 
                WHERE 
                    uploads_users.upload_id = uploads.id
                    AND files.upload_id = uploads.id
                    AND files.id = {$id}
                    AND uploads_users.owner=1
                        ";

            $_ret = $this->query($_query);

            if (array_key_exists(0,$_ret) && is_numeric($_ret[0]['uploads_users']['user_id'])) {

                // Can't query User here automatically because there is no official
                // relationship
                return array_shift($this->query("SELECT * FROM users WHERE id={$_ret[0]['uploads_users']['user_id']}"));
            }
        }

        return array();

    }

}
?>
