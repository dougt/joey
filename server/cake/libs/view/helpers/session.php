<?php
/* SVN FILE: $Id: session.php 4050 2006-12-02 03:49:35Z phpnut $ */
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
 * @subpackage		cake.cake.libs.view.helpers
 * @since			CakePHP v 1.1.7.3328
 * @version			$Revision: 4050 $
 * @modifiedby		$LastChangedBy: phpnut $
 * @lastmodified	$Date: 2006-12-01 21:49:35 -0600 (Fri, 01 Dec 2006) $
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Session Helper.
 *
 * Session reading from the view.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.view.helpers
 *
 */
class SessionHelper extends CakeSession {
/**
 * Used to determine if methods implementation is used, or bypassed
 *
 * @var boolean
 */
	var $__active = true;
/**
 * Class constructor
 *
 * @param string $base
 */
	function __construct($base = null) {
		if (!defined('AUTO_SESSION') || AUTO_SESSION === true) {
			parent::__construct($base, false);
		} else {
			$this->__active = false;
		}
	}
/**
 * Used to read a session values set in a controller for a key or return values for all keys.
 *
 * In your view: $session->read('Controller.sessKey');
 * Calling the method without a param will return all session vars
 *
 * @param string $name the name of the session key you want to read
 * @return values from the session vars
 * @access public
 */
	function read($name = null) {
		if ($this->__active === true) {
			return $this->readSessionVar($name);
		}
		return false;
	}
/**
 * Used to check is a session key has been set
 *
 * In your view: $session->check('Controller.sessKey');
 *
 * @param string $name
 * @return boolean
 * @access public
 */
	function check($name) {
		if ($this->__active === true) {
			return $this->checkSessionVar($name);
		}
		return false;
	}
/**
 * Returns last error encountered in a session
 *
 * In your view: $session->error();
 *
 * @return string last error
 * @access public
 */
	function error() {
		if ($this->__active === true) {
			return $this->getLastError();
		}
		return false;
	}
/**
 * Used to render the message set in Controller::Session::setFlash()
 *
 * In your view: $session->flash('somekey');
 * 					Will default to flash if no param is passed
 *
 * @param string $key The [Message.]key you are rendering in the view.
 * @return string Will echo the value if $key is set, or false if not set.
 * @access public
 */
	function flash($key = 'flash') {
		if ($this->__active === true) {
			if ($this->checkSessionVar('Message.' . $key)) {
				e($this->readSessionVar('Message.' . $key));
				$this->delSessionVar('Message.' . $key);
			} else {
				return false;
			}
		}
		return false;
	}

/**
 * Used to check is a session is valid in a view
 *
 * @return boolean
 * @access public
 */
	function valid() {
		if ($this->__active === true) {
		return $this->isValid();
		}
	}
}

?>