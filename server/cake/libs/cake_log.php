<?php
/* SVN FILE: $Id: cake_log.php 3963 2006-11-25 14:01:03Z phpnut $ */
/**
 * Logging.
 *
 * Log messages to text files.
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
 * @subpackage		cake.cake.libs
 * @since			CakePHP v 0.2.9
 * @version			$Revision: 3963 $
 * @modifiedby		$LastChangedBy: phpnut $
 * @lastmodified	$Date: 2006-11-25 08:01:03 -0600 (Sat, 25 Nov 2006) $
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Included libraries.
 *
 */
	if (!class_exists('File')) {
		 uses('file');
	}
/**
 * Logs messages to text files
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class CakeLog{
/**
 * Writes given message to a log file in the logs directory.
 *
 * @param string $type Type of log, becomes part of the log's filename
 * @param string $msg  Message to log
 * @return boolean Success
 */
	function write($type, $msg) {
		$filename = LOGS . $type . '.log';
		$output = date('Y-m-d H:i:s') . ' ' . ucfirst($type) . ': ' . $msg . "\n";
		$log = new File($filename);
		return $log->append($output);
	}
}
?>