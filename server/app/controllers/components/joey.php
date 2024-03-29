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


/**
 * A "misc" component
 */
class JoeyComponent extends Object
{

    /**
     * Save a reference to the controller on startup
     * @param object &$controller the controller using this component
     */
    function startup(&$controller) {
        $this->controller =& $controller;
    }


    /**
     * Pretty much stolen from cake's NumberHelper, but we need to use it in a
     * controller instead of a view
     */
    function bytesToReadableSize($size) {
        switch($size)
        {
            case 0:
                return '0 Bytes';

            case 1: 
                return '1 Byte';

            case $size < 1024: 
                return $size . ' Bytes';

            case $size < 1024 * 1024: 
                return sprintf("%01.3f", $size / 1024, 0) . ' KB';

            case $size < 1024 * 1024 * 1024: 
                return sprintf("%01.3f", $size / 1024 / 1024) . ' MB';

            case $size < 1024 * 1024 * 1024 * 1024: 
                return sprintf("%01.3f", $size / 1024 / 1024 / 1024) . ' GB';

            case $size < 1024 * 1024 * 1024 * 1024 * 1024:
                return sprintf("%01.3f", $size / 1024 / 1024 / 1024 / 1024) . ' TB';
        }
    }

    function getJ2MEMidletVersion() {

      $version_string = "0.0";  // default if something bad happens below

      //@todo maybe we shouldn't use __FILE__ and instead use a built in cake value.
      $filename = dirname(__FILE__) . '/../../webroot/ff/build.properties';
      
      if(($handle = fopen($filename,'r')) != FALSE) 
      {
      
        while (!feof($handle)) 
        {
          // look for just the version string in the build.properties file
          $version = fscanf($handle, "joey.version=%s\n");
          
          // great, found it.
          if (isset($version[0])) {
            $version_string =  $version[0];
            break;
          }
        }

        fclose($handle);
      }
      
      return $version_string;
    }


}
?>
