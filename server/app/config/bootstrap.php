<?php
/* SVN FILE: $Id: bootstrap.php 2951 2006-05-25 22:12:33Z phpnut $ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.app.config
 * @since			CakePHP v 0.10.8.2117
 * @version			$Revision: 2951 $
 * @modifiedby		$LastChangedBy: phpnut $
 * @lastmodified	$Date: 2006-05-25 17:12:33 -0500 (Thu, 25 May 2006) $
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 *
 * This file is loaded automatically by the app/webroot/index.php file after the core bootstrap.php is loaded
 * This is an application wide file to load any function that is not used within a class define.
 * You can also use this to include or require any files in your application.
 *
 */
/**
 * The settings below can be used to set additional paths to models, views and controllers.
 * This is related to Ticket #470 (https://trac.cakephp.org/ticket/470)
 *
 * $modelPaths = array('full path to models', 'second full path to models', 'etc...');
 * $viewPaths = array('this path to views', 'second full path to views', 'etc...');
 * $controllerPaths = array('this path to controllers', 'second full path to controllers', 'etc...');
 *
 */
//EOF

require_once ROOT.DS.APP_DIR.DS.'config'.DS.'config.php';

/**
 * Define non-browser errors.  These strings are echoed on the page when a request
 * fails.
 */
define('NB_CLIENT_ERROR_UPLOAD_FAIL', '-1');
define('NB_CLIENT_ERROR_OUT_OF_SPACE', '-2');


/**
 * Cake has a class called 'File' that conflicts with our model called 'File'.
 * This means the cake functions that use the File class don't work (but they
 * also don't throw an error...grr...)  CakeLog is one of the classes that
 * uses File.  As a temporary solution, I'm implementing logging with this
 * function.  The only place that should be calling this is the Error
 * component.
 */
function joeylog($message) {
    if (!LOGGING_ENABLED)
        return;
    if (!is_writable(LOGS))
        return;

    $message = date('Y-m-d H:i:s') .": {$message}\n";

    file_put_contents(LOGS.'/error.log', $message, FILE_APPEND);
    
}
?>
