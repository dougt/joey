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
uses('sanitize');

class UsersController extends AppController
{
    var $name = 'Users';
    var $uses = array('Operator', 'Phone', 'User');
    var $helpers = array('Form','Html');


    function getSoftware() {

      // Set the local user variable to the Session's User
      $this->_user = $this->Session->read('User');

      // What kind of phone does the user have.
      $phone = $this->Phone->findById($this->_user['phone_id']);

      // the name in the db is human readable (friendly),
      // the name of the files aren't as friendly to read.
      $phonename = str_replace(" - ", "-", $phone['Phone']['name']);
      $phonename = str_replace(" ", "", $phonename);

      $http_url = str_replace("https://", "http://", FULL_BASE_URL);
      $http_url = $http_url.'/app/webroot/ff/'.$phonename.".jad";

      $this->set('url_to_jad', $http_url);

      $this->set('url_to_xpi', str_replace("https://", "http://", FULL_BASE_URL) . '/app/webroot/ff/joey.xpi');

      
      // They haven't hit submit on the form yet

      // @todo hack.  it seams that i have to pass dummy
      // data here from the view so that the controller can
      // check to see if the submit button was checked or
      // not.  I tried using just the submit button itself
      // (array_key_exists('submit', $_POST)), but that
      // seams to be something cake takes care of.

      if (! array_key_exists('joey', $_POST))
      {
        return;
      }

      // Find out what operator the user is using.
      $operator = $this->Operator->findById($this->_user['operator_id']);

      $username = str_replace( "-", "", $this->_user['phonenumber']);
      
      //@todo very US centric.
      if (empty($operator['Operator']['emaildomain'])) {
        $this->flash("Sorry, we don't know how to send you an SMS.");
        return;
      }

      $email = $username . '@' . $operator['Operator']['emaildomain'];

      //@todo localize
      $message = "go to " . $http_url;

      // Send a mail to the user
      mail($email, 'Want Joey?', $message, "From: ".JOEY_EMAIL_ADDRESS."\r\n");

      $this->flash("Mail sent to ". $email, '/users/getSoftware', 2);

      return;
    }

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
    function edit() {

        $this->pageTitle = 'Edit User';

        $this->_user = $this->Session->read('User');

        $this->set('phones', $this->Phone->generateList(null,null,null,'{n}.Phone.id','{n}.Phone.name'));
        $this->set('operators', $this->Operator->generateList(null,null,null,'{n}.Operator.id','{n}.Operator.provider'));

        // If they just show up here, prefill the data and return
        if (empty($this->data)) {
            $_sanitize = new Sanitize();

            $this->data = $this->User->findByUsername($this->_user['username']);
            $this->data['User']['password'] = '';

            $this->set('username', $_sanitize->html($this->_user['username']));
            return;
        }

        // If a user has submitted form data
        if (!empty($this->data)) {

            // Grab our current values
            $_someone = $this->User->findById($this->_user['id']);

            // We don't accept any other changes (eg. username)
            $changed = array();
            $changed['email'] = $this->data['User']['email'];
            $changed['phonenumber'] = $this->data['User']['phonenumber'];

            // If the passwords aren't the same, they're invalid
            if ($this->data['User']['password'] != $this->data['User']['confirmpassword']) {
                $this->User->invalidate('confirmpassword');
            }
        
            // If they're changing the password, encrypt it
            if (!empty($this->data['User']['password'])) {
                $changed['password'] = sha1($this->data['User']['password']);
            } else {
                $changed['password'] = $_someone['User']['password'];
            }

            // If they're changing their email address, assign a unique confirmation code
            if ($this->data['User']['email'] != $this->_user['email']) {
                $changed['confirmationcode'] = uniqid();
            }

            // Does our data validate?
            if ($this->User->validates(array('User' => $changed)) && $this->Phone->validates($this->data) && $this->Operator->validates($this->data)) {

                $changed['phone_id']    = $this->data['Phone']['name'];
                $changed['operator_id'] = $this->data['Operator']['provider'];

                $this->User->id = $this->_user['id'];
                $this->User->data['User'] = $changed;

                if ($this->User->save()) {

                    $_flash_message = '';

                    if (isset($changed['confirmationcode'])) {

                        // Make an email message. 
                        $_message = "Please click on the following link or use the code {$changed['confirmationcode']} to activate your registration.  ".FULL_BASE_URL."/users/activate/{$changed['confirmationcode']} .";

                        // Send a mail to the user
                        mail($this->data['User']['email'], 'Welcome to Joey', $_message, "From: ".JOEY_EMAIL_ADDRESS."\r\n");

                        $_flash_message = 'Please check your email.';
                    }

                    // Grab their information from the database, and store in the session
                    $_newuser = $this->User->findById($this->_user['id']);

                    $this->Session->write('User', $_newuser['User']);

                    // They're outta here
                    $this->flash('Account Updated. '.$_flash_message, '/users/edit', 1);

                } else {
                    $this->set('error_mesg', 'Update failed.  Please try again.');
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

    /**
     *
     */
    function login() {

        $this->pageTitle = 'Login';

        // @todo this check will change if we are
        // using a net scaler.

        // if the FULL_BASE_URL isn't https, set secure_page to the https url.
        if (strncmp(FULL_BASE_URL, "https://", 8) == -1) 
          $this->set('secure_page', str_replace("http://", "https://", FULL_BASE_URL));


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
                            $this->returnJoeyStatusCode($this->SUCCESS);
                        } else {
                            $this->redirect('/uploads/index');
                            exit();
                        }
                    } else {
                        // This is a password error
                        if ($this->nbClient) {
                            $this->returnJoeyStatusCode($this->ERROR_LOGIN);
                        }
                        $this->set('error_mesg', 'Sorry, cannot login. Please check your username or password.');
                    }
                } else {
                    if ($this->nbClient) {
                        $this->returnJoeyStatusCode($this->ERROR_ACTIVATION);
                    }
                    $this->set('error_mesg', 'Sorry, your account has not been activated. Please check your email.');
                }
            } else {
                // This is a generalized, non-specific error
                if ($this->nbClient) {
                    $this->returnJoeyStatusCode($this->ERROR_LOGIN);
                }
                $this->set('error_mesg', 'Sorry, cannot login. Please check your username or password.');
            }
        }

        if (BrowserAgent::isMobile()) {
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

    // When the user clicks on the reset password email link ...
    function resetpassword()
    {
        // They clicked on the link
        if (array_key_exists('pass', $this->params) && array_key_exists(0, $this->params['pass']) && array_key_exists(1, $this->params['pass'])) {
            $_username = $this->params['pass'][0];
            $_epw = $this->params['pass'][1];
            $this->set("username", $_username);
            $this->set("epw", $_epw);
        }

        // They hit submit on the form
        if (isset($this->data)) {
            // $_username = $_POST['username'];
            // $_epw = $_POST['epw'];
            $_newpass = $this->data['User']['newpass'];
            $_newpass2 = $this->data['User']['newpass2'];
        }

        // Why are they here?
        if (!isset($_username) || empty($_username) || !isset($_epw) || empty($_epw)) {
            $this->redirect('/');
            exit;
        } else {
          // Find the user by username
          $_someone = $this->User->findByUsername(strtolower($_username));
          if(!empty($_someone['User']['id'])) {
            if ($_epw == md5($_someone['User']['password'])) {
              // if the password is set
              if (!empty($_newpass)) {
                if ($_newpass == $_newpass2) {
                  // do the reset
                  $this->User->id = $_someone['User']['id'];
                  $this->User->saveField('password', sha1($_newpass));
                  $this->redirect('/');
                } else {
                  // Display Form with error
                  $this->set('error_mesg', 'The password and confirmation do not match!');
                }
              }
            } else {
              // The epw does not match the username. Possible spoof. Fail
              $this->redirect('/');
              exit;
            }
          } else {
            // The username does not exist. Possible spoof. Fail
            $this->redirect('/');
            exit;
          }
        }

    }

    // Ask the username / email to reset password
    function resetpasswordemail()
    {
      // If a user has submitted form data:
      if (!empty($this->data)) {
       
        // Find the user by username
        $_someone = $this->User->findByUsername(strtolower($this->data['User']['username']));
        // If not, find her by email address
        if(empty($_someone['User']['id'])) {
          $_someone = $this->User->findByEmail(strtolower($this->data['User']['email']));
        }

        // They're in the database
        if(!empty($_someone['User']['id'])) {
          
          $epw = md5($_someone['User']['password']);

          $this->User->id = $_someone['User']['id'];
          $this->User->saveField('confirmationcode', null);

          // Make an email message.  
          $_message = "Please click on the following link to reset your password:\n\n ".FULL_BASE_URL."/users/resetpassword/".$_someone['User']['username']."/".$epw." \n";

          // Send a mail to the user
          mail($_someone['User']['email'], 'Joey password reset', $_message, "From: ".JOEY_EMAIL_ADDRESS."\r\n");

          $this->flash('Please check your email to reset password.', '/', 2);
          exit;
        }
      }
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
                    if (! (mkdir(UPLOAD_DIR."/{$_user_id}") && mkdir(UPLOAD_DIR."/{$_user_id}/previews") && mkdir(UPLOAD_DIR."/{$_user_id}/originals"))) {
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
                        // Make an email message. 
                        $_message = "Please click on the following link or use the code {$this->data['User']['confirmationcode']} to activate your registration.  ".FULL_BASE_URL."/users/activate/{$this->data['User']['confirmationcode']} .";

                        // Send a mail to the user
                        mail($this->data['User']['email'], 'Welcome to Joey', $_message, "From: ".JOEY_EMAIL_ADDRESS."\r\n");

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
