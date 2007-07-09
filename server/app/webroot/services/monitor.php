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
 * This is a lightweight page designed to be monitored with a program like nagios.
 * If there is a problem, this will throw a 500 error.
 *
 */

// Never cache this page
header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0, private');
header('Pragma: no-cache');

// Grab the site config
require_once '../../config/config.php';

// Check Main Database
    $dbh = @mysql_connect(DB_HOST.':'.DB_PORT,DB_USER,DB_PASS);
    testo('Connect to MAIN database ('.DB_HOST.')', is_resource($dbh));
    testo('Select MAIN database ('.DB_NAME.')', @mysql_select_db(DB_NAME, $dbh));
    unset ($dbh);

// Verify commands exist
    testo('Convert command exists ('.CONVERT_CMD.')', is_file(CONVERT_CMD));
    testo('FFMPEG command exists ('.FFMPEG_CMD.')', is_file(FFMPEG_CMD));

// Verify permissions are correct
    testo('Upload directory exists ('.UPLOAD_DIR.')', is_dir(UPLOAD_DIR));
    testo('Upload directory is writable ('.UPLOAD_DIR.')', is_writable(UPLOAD_DIR));

// Print out all our results
    foreach ($results as $result) {

        if ($result['result'] === 'FAILED') {
            echo "<b style=\"color:red;\">{$result['message']}: {$result['result']}</b><br />\n";
        } else {
            echo "{$result['message']}: {$result['result']}<br />\n";
        }

    }

    echo '<hr />';
    echo '<p>What are we actually testing? <a href="http://viewvc.svn.mozilla.org/vc/labs/joey/trunk/server/app/webroot/services/monitor.php?view=markup">Check the source</a>';


// Functions
    /**
     * To use as a general message function, pass two strings
     * To use to trigger errors, pass a message and a boolean
     */
    function testo($message, $result) {
        global $results;

        // If they passed in a boolean, we convert it to a string
        if (is_bool($result)) {
            $result = ($result ? 'success' : 'FAILED');
        }

        $results[] = array( 'message' => $message, 'result'  => $result );

        if ($result === 'FAILED') {
            header("HTTP/1.0 500 Internal Server Error");
        }
    }
?>
