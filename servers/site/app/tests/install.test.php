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
 * Portions created by the Initial Developer are Copyright (C) 2006
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *  Wil Clouser <clouserw@mozilla.com>
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

class InstallationTest extends UnitTestCase {

   /**
    * Tests Apache and modules
    */
    function testApache() {
        $this->assertTrue((strpos(apache_get_version(), 'Apache/2') !== false), 'Apache: Version 2');
        $this->assertTrue(in_array('mod_rewrite', apache_get_modules()), 'Apache: Module mod_rewrite');
    }
    
   /**
    * Tests PHP and extensions
    */
    function testPHP() {
        $this->assertTrue((4 <= phpversion() && phpversion() < 5), 'PHP: Version 4');
        $this->assertTrue(extension_loaded('gettext'), 'PHP: Extension gettext');
    }
	
   /**
    * Tests for PEAR and required modules
    */
    function testPear() {
        $this->assertTrue(include_once('PEAR.php'), 'PEAR: PEAR');
        $this->assertTrue(include_once('Archive/Zip.php'), 'PEAR: Module Archive_Zip');
    }

   /**
    * Tests DB connections
    */
    function testDB() {
        $db = ConnectionManager::getInstance();

        //If the specific config is not even in the database file, we need to fail or PHP will have a fatal error.
        if ($connected = @$db->getDataSource('default')) {
            $this->assertTrue($connected->isConnected(), 'Database: default');
        }
        else {
            $this->fail('Database: default - Your database configuration file is not up to date. Please re-copy the default.');
        }
        if ($connected = @$db->getDataSource('shadow')) {
            $this->assertTrue($connected->isConnected(), 'Database: shadow');
        }
        else {
            $this->fail('Database: shadow - Your database configuration file is not up to date. Please re-copy the default.');
        }
        if ($connected = @$db->getDataSource('test')) {
            $this->assertTrue($connected->isConnected(), 'Database: test');       

            $this->fail('Check for data once data exists...');

/*
            // Data in `addontypes`?
            $r = $connected->one('SELECT count(*) as count FROM `addontypes`');
            $this->assertTrue($r[0]['count']>0,'Data in `addontypes` exists.');
            unset($r);
            */
        }
        else {
            $this->fail('Database: test - Your database configuration file is not up to date. Please re-copy the default.');
        }
    }
}
?>
