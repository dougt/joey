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

class AppController extends Controller
{
    /**
     * Is the request coming from a non-browser client? Set in the constructor.
     */
    var $nbClient = false;

    function __construct() {

        parent::__construct();

        // Check the POST for this special key.  If it exists, the person is using a
        // non-browser client to access the site.  This will change the information
        // we return.
        if (array_key_exists('rest',$_POST)) {
            $this->nbClient = true;
        }
    }

    function beforeFilter() {


        // By default, we're going to secure all pages, and redirect users to the
        // login page if they aren't authenticated.  This array holds the controllers
        // and actions that shouldn't be checked, in the form:
        //      array(controller=>array(action,...))
        $_no_session_check = array(
                                    'users' => array('activate', 'login', 'register')
                                   );
                                        
        $_skip_check = false;

        if (array_key_exists('controller', $this->params) && array_key_exists('action', $this->params)) {
            foreach ($_no_session_check as $_controller => $_actions) {
                foreach ($_actions as $_action) {
                    if ( ($this->params['controller'] == $_controller) && ($this->params['action'] == $_action) ) {
                        $_skip_check = true;
                    }
                }
            }
        }

        if ($_skip_check !== true) {
            $this->checkSession();
        }
    }


    /**
     * Check if the session is active or not.  If it's not, redirect to the login page
     */
    function checkSession()
    {
        if (!$this->Session->check('User')) {
            $this->redirect('/users/login');
            exit;
        }
    }

    /**
     * If the user is using a non-browser client to access the pages, we use this
     * method to print error/success information on the page.  We're not overriding
     * flash() because we usually need to send different data.
     *
     * @param string  Message to print on page
     */
    function nbFlash($message)
    {
        $this->set('message', $message);

        $this->layout = null;

        $this->render(null, false, VIEWS.'layouts'.DS.'nbflash.thtml');
    }

}
?>
