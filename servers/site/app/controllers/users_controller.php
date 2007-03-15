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

        // Remove their old session
        $this->Session->delete('User');

        $this->pageTitle = 'Login';

        // If a user has submitted form data:
        if (!empty($this->data)) {
            $this->data['User']['username'] = strtolower ($this->data['User']['username']);

            $someone = $this->User->findByUsername($this->data['User']['username']);

            if(!empty($someone['User']['id'])) {
                // @todo bind with ldap and check the password!
              if (empty($someone['User']['confirmationcode'])) {
                if ($someone['User']['password'] == sha1($this->data['User']['password']))
                {
                    $this->Session->write('User', $someone['User']);

                    // The uploads controller will detect the browser 
                    $this->redirect('/uploads/index');
                    exit ();
                }
              } else {
                $this->set('error', true);
                $this->set('error_mesg', 'Sorry, your account has not been activated');
              }
            } else {
              // This is a generalized, non-specific error
              $this->set('error', true);
              $this->set('error_mesg', 'Sorry cannot login, please check your username or password');
            }
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

    function activate () {
      if (isset($_POST['code'])) {
        $confirmationcode = $_POST['code'];
      } elseif (isset($_GET['code'])) {
        $confirmationcode = $_GET['code'];
      } else {
        $this->render('activate');
        exit ();
      }

      $test = $this->User->findByConfirmationcode($confirmationcode); 
      if(!empty($test['User']['id'])) {
        $this->User->id = $test['User']['id'];
        $this->User->saveField('confirmationcode', null);

        $this->set('error', true);
        $this->set('error_mesg', 'Account activated, please login');
        $this->redirect('/users/login');
        exit ();
      } else {
        $this->set('error', true);
        $this->set('error_mesg', 'Wrong activation code!');
      }
    }

    function register() {

        // If a user has submitted form data:
        if (!empty($this->data)) {

            $this->data['User']['username'] = strtolower ($this->data['User']['username']);

            $test1 = $this->User->findByUsername($this->data['User']['username']);
            $test2 = $this->User->findByEmail($this->data['User']['email']);

            if(empty($test1['User']['id']) && empty($test2['User']['id'])) {

                // Encrypt the database
                $this->data['User']['password'] = sha1($this->data['User']['password']);

                $confirmationcode = uniqid();
                $this->data['User']['confirmationcode'] = $confirmationcode;
                $mesg = 'Please click on the following link or use the code '. $confirmationcode .' to activate your registration.  http://joey.labs.mozilla.org/users/activate?code='. $confirmationcode .' ';

                if (mail($this->data['User']['email'], 'Welcome to Joey', $mesg)) {

                  if ($this->User->save($this->data)) {
                    $newuser = $this->User->findByEmail($this->data['User']['email']);

                    $this->Session->write('User', $newuser['User']);

                    $this->redirect('/uploads/index');
                  } else {
                    // database error 
                    $this->set('error', true);
                    $this->set('error_mesg', 'There is an unexpected server error, please try again later!');
                    $this->render('register');
                    exit ();
                  }
                } else {
                  // email error 
                  $this->set('error', true);
                  $this->set('error_mesg', 'An error has occured while we try to send you email, please double check your email address!');
                  $this->render('register');
                  exit ();
                }
            } else {
                // username already exists
                $this->set('error', true);
                $this->set('error_mesg', 'Your username or email is already used by someoneelse, please choose a different username!');
                $this->render('register');
                exit ();
            }
        }
    }

}
?>
