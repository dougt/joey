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
 * Update script for the Joey Project. 
 *
 * This script runs through the uploads table for joey and updates the appropriate
 * files according to the time they were last updated.
 *
 * This script should only ever be run from the CLI. (probably by cron)
 *
 * @package Joey
 * @subpackage bin
 */

 // Grab our defines
 require_once dirname(__FILE__).'/../config/config.php';


 // Before doing anything, test to see if we are calling this from the command
 // line.  If this is being called from the web, HTTP environment variables will
 // be automatically set by Apache.  If these are found, exit immediately.
 if (isset($_SERVER['HTTP_HOST'])) {
     exit;
 }


 // Lets change into the cache directory so that we can
 // ensure that we have a place that we can write to.
 if (!chdir(UPLOAD_DIR."/cache/"))
 {
   echo "Could not change directory into cache directory\n";
   exit;
 }

 // Some transcoding requires that the cwd is writable.
 // This usually isn't a problem, but when running as
 // another user (like sudo -u <> php -f update.php) it can
 // be an issue.

 if (!is_writable(getcwd()))
 {
   echo "CWD is not writable\n";
   exit;
 }

 // Lets check if we are already running.  If so, we need to bail.
 $joey_lock_file = fopen(UPLOAD_DIR."/cache/joey.update.lock", "w");

 if (!$joey_lock_file) {
   echo "Could not open lock file!";
   exit;
 }

 if (!flock($joey_lock_file, LOCK_NB+LOCK_EX)) { 
   fclose($joey_lock_file);
   echo "Could not aquire lock!";
   exit;
 }
 //@todo settimelimit/memorylimit

 ini_set('memory_limit', '32M');
 set_time_limit(0);

 // This will let us access the functions we need to (ie. bypass authentication)
 define('MAINTENANCE_ACCESS', TRUE);

 // CakePHP is expecting a URL to load.  We're going to load the appropriate function
 // for updating all the current uploads, and all logic will be handled in there.
 $_GET['url'] = '/uploads/updateAllUploads';

 require dirname(__FILE__).'/../webroot/index.php';
 
 echo "done.\n";

 // Clean up our lockfile
 flock($joey_lock_file, LOCK_UN);
 fclose($joey_lock_file);


?>
