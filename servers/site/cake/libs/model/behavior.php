<?php
/* SVN FILE: $Id: behavior.php 4108 2006-12-19 19:26:02Z nate $ */

/**
 * Model behaviors base class.
 *
 * Adds methods and automagic functionality to Cake Models.
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
 * @subpackage		cake.cake.libs.model
 * @since			CakePHP v 1.2.0.0
 * @version			$Revision: 4108 $
 * @modifiedby		$LastChangedBy: nate $
 * @lastmodified	$Date: 2006-12-19 13:26:02 -0600 (Tue, 19 Dec 2006) $
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class ModelBehavior extends Object {

/**
 * Contains configuration settings for use with individual model objects.  This
 * is used because if multiple models use this Behavior, each will use the same
 * object instance.  Individual model settings should be stored as an
 * associative array, keyed off of the model name.
 *
 * @var array
 * @access public
 */
	var $settings = array();
/**
 * Allows the mapping of preg-compatible regular expressions to public or
 * private methods in this class, where the array key is a /-delimited regular
 * expression, and the value is a class method.  Similar to the functionality of
 * the findBy* / findAllBy* magic methods.
 *
 * @var array
 * @access public
 */
	var $mapMethods = array();

	function setup(&$model, $config = array()) { }

	function beforeFind(&$model, $query) { }

	function afterFind(&$model, $results, $primary) { }

	function beforeSave(&$model) { }

	function afterSave(&$model) { }

	function beforeDelete(&$model) { }

	function afterDelete(&$model) { }

	function onError(&$model, $error) { }
}

?>