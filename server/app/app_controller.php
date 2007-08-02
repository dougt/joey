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

    var $components = array('Error');
    //@ todo maybe we should change these value so that they
    //do not resemble http status codes?

    var $SUCCESS          = "200";  //  OK
    var $ERROR_NO_SESSION = "511";  //  No Active Session
    var $ERROR_LOGIN      = "512";  //  Login Error
    var $ERROR_ACTIVATION = "513";  //  User not Activated
    var $ERROR_DELETE     = "514";  //  Cannot Delete
    var $ERROR_NOAUTH     = "515";  //  Not Permitted for This User
    var $ERROR_FILE       = "516";  //  File Access Error
    var $ERROR_NO_SPACE   = "517";  //  Out of Space for Upload
    var $ERROR_UPLOAD     = "518";  //  Generic Upload Error
    var $ERROR_DUPLICATE  = "519";  //  Duplicate Found

    /**     
     * Used to determine the current security level for the class
     *          
     * @var string 'high' or 'low'
     */         
    var $securityLevel = 'high';


    function __construct() {

        parent::__construct();

        $this->setSecurityLevel($this->securityLevel);

        // Check both the POST and GET for this special key.
        // If it exists, the person is using a non-browser
        // client to access the site.  This will change the
        // information we return.

        if (array_key_exists('rest',$_POST) || array_key_exists('rest',$_GET)) {
            $this->nbClient = true;
        }
    }

    function beforeFilter() {


        // By default, we're going to secure all pages, and redirect users to the
        // login page if they aren't authenticated.  This array holds the controllers
        // and actions that shouldn't be checked, in the form:
        //      array(controller=>array(action,...))
        $_no_session_check = array(
                                   'users' => array('activate', 'login', 'register', 'resetpasswordemail', 'resetpassword'),
                                   'uploads' => array('updateAllUploads')
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
            if ($this->nbClient) {
                $this->returnJoeyStatusCode($this->ERROR_NO_SESSION);
            }

            // If multiple requests come in, we only want to
            // remember the first one as the login_referrer.
            // What can happen is that the first (real)
            // request occurs, then we get extra requests
            // for the favicon and stuff like that. We want
            // to ignore the fluff.

            $ref = $this->Session->read('login_referrer');
            if (empty($ref)) {              

              // Cake hides this from us.  We want to remember
              // the full request URI including any query
              // string.
              $ref = $_SERVER['REQUEST_URI'];
              
              if (!in_array($ref, array('','/','/img/favicon.ico','/users/login'))) {
                $this->Session->write('login_referrer', $ref);
              }
            }

            $this->redirect('/users/login');
            exit;
        }
    }

    function returnJoeyStatusCode($statusCode)
    {
        header ("X-joey-status: " . $statusCode, true);

        $this->layout = null;
        exit;
    }

    function fromXMLString($in)
    {
      $out = str_replace ("&amp;", "&", $in);
      $out = str_replace ("&gt;",  ">", $out);
      $out = str_replace ("&lt;",  "<", $out);
      $out = str_replace ("&apos;","\'", $out);
      $out = str_replace ("&quot;","\"", $out);
      return $out;
    }

    function toXMLString($in)
    {
      $out = str_replace ("&", "&amp;", $in);
      $out = str_replace (">",  "&gt;", $out);
      $out = str_replace ("<",  "&lt;", $out);
      $out = str_replace ("\'","&apos;", $out);
      $out = str_replace ("\"","&quot;", $out);
      return $out;
    }

    /**
     * When CAKE_SECURITY is set to high, cake will automatically set
     * session.referer_check to the current host.  This is good for some of our
     * pages, but not good for others.  Since the Session component is
     * automatically-included-no-matter-what, we can't override that, so we'll change
     * the ini setting ourselves here.  Default is high, but we'll override it in all
     * the controllers that can use a more relaxed level.
     *
     * @param string level to set the security at, 'low' or 'high'
     * @return void
     */ 
    function setSecurityLevel($level) {
        if (defined('CAKE_SECURITY')) return;
        switch ($level) {
    
            case 'low':
                    define('CAKE_SECURITY', 'low');
                    break;

            case 'high': 
            default:
                    define('CAKE_SECURITY', 'high');
                    break;
        }
    }
    

}
?>
