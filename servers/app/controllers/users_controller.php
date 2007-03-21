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
    var $uses = array('Operator', 'Phone', 'User');
    var $helpers = array('Form','Html');

    /**
     *
     */
    function activate () {

        // They clicked on the link
        if (array_key_exists('pass', $this->params) && array_key_exists(0, $this->params['pass'])) {
            $_confirmationcode = $this->params['pass'][0];
        }

        // They hit submit on the form
        if (isset($this->data) && array_key_exists('User', $this->data) && array_key_exists('Confirmationcode', $this->data['User'])) {
            $_confirmationcode = $this->data['User']['Confirmationcode'];
        }

        // Why are they here?
        if (!isset($_confirmationcode) || empty($_confirmationcode)) {
            $this->redirect('/');
            exit;
        }

        $_someone = $this->User->findByConfirmationcode($_confirmationcode); 

        if(!empty($_someone['User']['id'])) {
            $this->User->id = $_someone['User']['id'];
            $this->User->saveField('confirmationcode', null);

            // Send them on
            $this->flash('Account activated, please login', '/users/login', 2);

        } else {
            // Invalid code, give them an error.
            $this->set('error_mesg', 'Wrong activation code!');
        }
    }

    /**
     *
     */
    function login() {

        $this->pageTitle = 'Login';

        // Remove their old session
        $this->Session->delete('User');


        // If a user has submitted form data:
        if (!empty($this->data)) {

            $this->data['User']['username'] = strtolower($this->data['User']['username']);

            $_someone = $this->User->findByUsername($this->data['User']['username']);

            // They're in the database
            if(!empty($_someone['User']['id'])) {

                if (empty($_someone['User']['confirmationcode'])) {
                    if ($_someone['User']['password'] == sha1($this->data['User']['password']))
                    {
                        $this->Session->write('User', $_someone['User']);

                        if ($this->nbClient) {
                            $this->nbFlash($_someone['User']['id']);
                        } else {
                            $this->redirect('/uploads/index');
                            exit;
                        }
                    }
                } else {
                    $this->set('error_mesg', 'Sorry, your account has not been activated. Please check your email.');
                }
            } else {
                // This is a generalized, non-specific error
                $this->set('error_mesg', 'Sorry cannot login. please check your username or password.');
            }
        }

        if (BrowserAgent::isMobile()) {
            // We're not using $this->render() here, because it would conflict with nbFlash()
            // above (it would render both, instead of just one)
            $this->action = 'mp_login';
            $this->layout = 'mp';
        } else {
            $this->action = 'login';
        }


    }

    /**
     *
     */
    function logout() {

        $this->Session->delete('User');

        $this->redirect('/');

        exit;
    }

    /**
     *
     */
    function register() {

        $this->pageTitle = 'Register';

        $this->set('phones', $this->Phone->generateList(null,null,null,'{n}.Phone.id','{n}.Phone.name'));
        $this->set('operators', $this->Operator->generateList(null,null,null,'{n}.Operator.id','{n}.Operator.provider'));

        // If a user has submitted form data:
        if (!empty($this->data)) {

            $this->data['User']['username'] = strtolower ($this->data['User']['username']);

            $test1 = $this->User->findByUsername($this->data['User']['username']);
            $test2 = $this->User->findByEmail($this->data['User']['email']);

            // Do some special validation
                // The username is already in use
                if (!empty($test1['User']['id'])) {
                    $this->User->invalidate('username');
                    $this->set('error_username', 'Your username is already in use, please choose a different username.');
                }
                // The email address is already in use
                if (!empty($test2['User']['id'])) {
                    $this->User->invalidate('email');
                    $this->set('error_email', 'Your email address is already in use, please choose a different email address.');
                }
                // The passwords don't match
                if ($this->data['User']['password'] != $this->data['User']['confirmpassword']) {
                    $this->User->invalidate('confirmpassword');
                }

            // If all our data validates
            if ($this->User->validates($this->data) && $this->Phone->validates($this->data) && $this->Operator->validates($this->data)) {

                // Encrypt the password
                $this->data['User']['password'] = sha1($this->data['User']['password']);

                // Assign a unique confirmation code
                $this->data['User']['confirmationcode'] = uniqid();

                // Fill in the FKs.  Shouldn't cake do this for me?
                $this->data['User']['phone_id']         = $this->data['Phone']['name'];
                $this->data['User']['operator_id'] = $this->data['Operator']['provider'];

                // Save the info.  We already validated it, so this should never
                // fail.  If it does fail, I'm betting someone messed with the form
                // data manually and an FK isn't lining up...that's a shame.
                if ($this->User->save($this->data)) {

                    $_user_id = $this->User->id;

                    // Create directories on the disk for the user to store their uploads
                    if (! (mkdir(UPLOAD_DIR."/{$_user_id}") && mkdir(UPLOAD_DIR."/{$_user_id}/previews"))) {
                        // I sincerely hope it's a rare case that this fails.  At
                        // this point, the user is in the database, but we can't
                        // create directories for them to put their stuff. We can't
                        // use a transaction here, because we wouldn't have the
                        // user's id (since it comes from the database), so we can't
                        // rollback our changes.  Instead, we'll make a last ditch
                        // effort to whack the user, and then set an error message.

                        $this->User->del($_user_id);

                        $this->set('error_mesg', 'Registration failed.  Please try again.');
                    } else {
                        // Make an email message.  @todo This should really be
                        // in a config var, or a view somewhere.
                        // @todo site is hardcoded here to the base URL.  This will
                        // work for production but needs to be fixed for other URLs
                        $_message = "Please click on the following link or use the code {$this->data['User']['confirmationcode']} to activate your registration.  ".FULL_BASE_URL."/users/activate/{$this->data['User']['confirmationcode']} .";

                        // Send a mail to the user
                        mail($this->data['User']['email'], 'Welcome to Joey', $_message);

                        // Grab their information from the database, and store in the session
                        $_newuser = $this->User->findByEmail($this->data['User']['email']);
                        $this->Session->write('User', $_newuser['User']);

                        // They're outta here
                        $this->flash('Registration successful.  Please check your email.', '/uploads/index', 2);
                    }
                } else {
                    $this->set('error_mesg', 'Registration failed.  Please try again.');
                }
            } else {
                // Since we're using &&'s in the if() statement above, there is a
                // chance some of these didn't run.  If we run them all manually, we
                // can provide a complete set of error messages to the user all in
                // one go.
                    $this->User->validates($this->data);
                    $this->Phone->validates($this->data);
                    $this->Operator->validates($this->data);

                // Send the errors to the form 
                $this->validateErrors($this->User, $this->Phone, $this->Operator);
            }
        }
    }

}
?>