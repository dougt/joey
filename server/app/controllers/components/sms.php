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
 *
 * The Initial Developer of the Original Code is
 * The Mozilla Foundation.
 * Portions created by the Initial Developer are Copyright (C) 2007
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *   Doug Turner <dougt@meer.net>
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


/**
 * A sms component
 */
class SmsComponent extends Object
{

    /**
     * Save a reference to the controller on startup
     * @param object &$controller the controller using this component
     */
    function startup(&$controller) {
        $this->controller =& $controller;
    }

    /**
     * Send the user id a message.
     *
     *  Note: the controller has to have Operator and Session
     *  in the |uses| array!
     */
    function sendCurrentUserSMS($subject, $message) {

      // Set the local user variable to the Session's User
      $user = $this->controller->Session->read('User');

      // Find out what operator the user is using.
      $operator = $this->controller->Operator->findById($user['operator_id']);

      $username = str_replace( "-", "", $user['phonenumber']);
      

      //@todo very US centric.
      if (empty($operator['Operator']['emaildomain'])) {
        return -1;
      }

      $email = $username . '@' . $operator['Operator']['emaildomain'];

      // Send a mail to the user
      mail($email, $subject, $message, "From: ".JOEY_EMAIL_ADDRESS."\r\n");
      
      return 0;
    }
}


?>
