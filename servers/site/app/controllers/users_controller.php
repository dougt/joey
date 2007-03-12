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

class UsersController extends AppController
{
    var $name = 'Users';
    var $helpers = array('Form','Html');

    function login() {


        $this->pageTitle = 'Login';

        // If a user has submitted form data:
        if (!empty($this->data)) {
            $this->data['User']['username'] = strtolower ($this->data['User']['username']);

            $someone = $this->User->findByUsername($this->data['User']['username']);

            if(!empty($someone['User']['id'])) {
                // @todo bind with ldap and check the password!
                if ($someone['User']['password'] == sha1($this->data['User']['password']))
                {
                    $this->Session->write('User', $someone['User']);

                    // The uploads controller will detect the browser 
                    $this->redirect('/uploads/index');
                }
            }

            // This is a generalized, non-specific error
            $this->set('error', true);
        }

        if (BrowserAgent::isMobile()) {
            $this->render ('mp_login', 'mp');
        } else {
            $this->render ('login');
        }

    }

    function logout() {

        $this->Session->delete('User');

        $this->redirect('/');
    }

    function register() {

        // If a user has submitted form data:
        if (!empty($this->data)) {

            $this->data['User']['username'] = strtolower ($this->data['User']['username']);

            $someone = $this->User->findByUsername($this->data['User']['username']);

            if(empty($someone['User']['id'])) {

                // Encrypt the database
                $this->data['User']['password'] = sha1($this->data['User']['password']);

                if ($this->User->save($this->data)) {
                    $someone = $this->User->findByEmail($this->data['User']['email']);

                    $this->Session->write('User', $someone['User']);

                    $this->redirect('/uploads/index');
                }
            }

            $this->set('error', true);

        }
    }

}
?>
