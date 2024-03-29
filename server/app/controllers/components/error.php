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
 * The Original Code is addons.mozilla.org site.
 *
 * The Initial Developer of the Original Code is
 * Justin Scott <fligtar@gmail.com>.
 * Portions created by the Initial Developer are Copyright (C) 2007
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Wil Clouser <clouserw@mozilla.com>
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
class ErrorComponent extends Object {
    var $controller;
    var $errors = array();

   /**
    * Save a reference to the controller on startup and reset errors
    * @param object &$controller the controller using this component
    */
    function startup(&$controller) {
        $this->controller =& $controller;
        $this->resetErrors();
    }

   /**
    * Resets errors to defaults to prevent notices
    */
    function resetErrors() {
        $this->errors = array( 'general' => '' );
    }

   /**
    * Add an error to the errors array
    * @param string $error The error message
    * @param string $field The offending field
    * @param bool $invalidate Should we attempt to invalidate the field in the controller?
    * @param bool $report Should we log the error?
    */
    function addError($error, $field = 'general', $invalidate = true, $report = false) {
        $this->errors[$field] = $error;

        if ($invalidate && ($field != 'general')) {
            list($model, $field) = explode('/',$field,2);

            if (is_object($this->controller->$model) && method_exists($this->controller->$model, 'invalidate')) {
                $this->controller->$model->invalidate($field);
            }
        }

        if ($report) {
            joeylog("({$field}) {$error}");
        }

    }
   
   /**
    * Check if any errors have been logged
    * @param object &$controller The controller
    */
    function noErrors() {
        $errors = false;
        
        if (count($this->errors) > 0) {
            foreach ($this->errors as $error) {
                if (!empty($error)) {
                    $errors = true;
                }
            }
        }
        
        if ($errors == false) {
            return true;
        } else { 
            return false;
        }
    }
}
?>
