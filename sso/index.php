<?php
/**
 * Joomla autologin wrapper for Kayako/Swift
 *
 * @author Christopher Roussel
 */

require_once('joomla.php');

JKayakoJoomla::loadJoomla();
$jSession = JFactory::getApplication();
if (defined('IN_WRAPPER')) {
	$jSession->setUserState('kayako.inwrapper', true);
}
$wrap_session = $jSession->getUserState('kayako.inwrapper');
if ($wrap_session && !defined('IN_WRAPPER')) {
	define('IN_WRAPPER', true);
}

// Load Swift API
define('SWIFT_INTERFACE', 'client');
define('SWIFT_INTERFACEFILE', __FILE__);
$path = (defined("SWIFT_CUSTOMPATH")) ? (SWIFT_CUSTOMPATH) : './../__swift/';
chdir($path);
require_once ('swift.php');