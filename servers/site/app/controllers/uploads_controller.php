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

class UploadsController extends AppController
{
    var $name = 'Uploads';

    //var $scaffold;   

    function index()
    {
        include 'BrowserAgent.class.php';

	$user = $this->Session->read('User');
	$this->set('uploads', $this->Upload->findAllByOwner($user['id']));

        if (BrowserAgent::isMobile()) {
          $this->render ('mp_index', 'mp');
        }
    }

    function view()
    {
        $this->layout = null;
	$user = $this->Session->read('User');
        $this->set('user', $user['id']);
	
    }

    function delete($id)
    {
        // do we have to verify the users here??

	if(! (isset($id) && is_numeric($id)))
        {
           $this->redirect('/uploads');
	   exit();
        }
	
	$user = $this->Session->read('User');
	$item = $this->Upload->findById($id);

	if ($item['Upload']['owner'] == $user['id'])
		$this->Upload->delete($id);

	$this->redirect('/uploads');	
    }

    function rss()
    {
	$this->layout = 'xml'; 
        $user = $this->Session->read('User');
	$this->set('uploads', $this->Upload->findAllByOwner($user['id']));
    }

}
?>
