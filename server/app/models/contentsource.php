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

class Contentsource extends AppModel
{
    var $name = 'Contentsource';

    var $belongsTo = array(
                        'File' =>
                           array('className'  => 'File',
                                 'conditions' => '',
                                 'order'      => ''
                                ),
                        'Contentsourcetype' =>
                           array('className'  => 'Contentsourcetype',
                                 'conditions' => '',
                                 'order'      => ''
                                )
                          );

    var $validate = array(
                            'source'               => VALID_NOT_EMPTY,
                            'contentsourcetype_id' => VALID_NOT_EMPTY
                         );

    function is_duplicate($user, $source)
    {
      if (!is_numeric($user))
        return false;

      // maybe optimize
      $_query = "SELECT COUNT(*) FROM  uploads_users 
                 JOIN uploads as Upload ON uploads_users.upload_id = Upload.id 
                 LEFT JOIN files as File ON Upload.id = File.upload_id
                 LEFT JOIN contentsources as Contentsource ON File.id = Contentsource.file_id
                 LEFT JOIN contentsourcetypes as Contentsourcetype ON Contentsource.contentsourcetype_id = Contentsourcetype.id 
                 WHERE uploads_users.owner=1 AND uploads_users.user_id = '{$user}' AND source = '{$source}'";


      // maybe not a full query?  wil knows.
      $_ret = $this->query($_query);

      if ($_ret[0][0]['COUNT(*)'] > 0)
        return true;

      return false;
    }

}
?>
