<?php
/**
 * Joomla user class
 *
 * @author Christopher Roussel
 */

if (!defined('_JEXEC')) {
	define('JPATH_BASE', '/home/mysite/public_html/' ); // change this for your site
	define( 'DS', DIRECTORY_SEPARATOR );
	define( '_JEXEC', 1 );
}

class JKayakoJoomla {
	function __construct() {
		if (!defined('_JEXEC')) {
			self::loadJoomla();
		}
	}

	function loadJoomla() {
		// load J! -- taken from J!'s index.php
		require_once ( JPATH_BASE .DS.'includes'.DS.'defines.php' );
		require_once ( JPATH_BASE .DS.'includes'.DS.'framework.php' );
		$mainframe = JFactory::getApplication('site');
		$mainframe->initialise();
	}

	function login ($login, $password) {
		if (!defined('_JEXEC')) {
			self::loadJoomla();
		}
		$mainframe = JFactory::getApplication('site');
		return $mainframe->login(array('username' => $login, 'password' => $password),array('silent' => true));
	}

	function insert ($login, $password) {
		if (!defined('_JEXEC')) {
			self::loadJoomla();
		}
		$jUser = JFactory::getUser();
		$db = JFactory::getDBO();
		$id = false;

		// check for already existing user
		if (strpos($login, '@')) {
			$sql = 'SELECT `id` from #__users WHERE `email` = "' . $login . '"';
			$isEmail = true;
		}
		else {
			$sql = 'SELECT `id` from #__users WHERE `username` = "' . $login . '"';
			$isEmail = false;
		}
		$db->setQuery($sql);
		$id = $db->loadResult();

		// still not found, so register the user!
		if (!$id && $isEmail) {
			$cbFile = JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_comprofiler' . DS . 'plugin.foundation.php';

			// try to use CB if it is available
			if (file_exists($cbFile)) {
				require_once($cbFile);
				$method = 'insertCb';
			}
			// else use the regular J!
			else {
				$method = 'insertCore';
			}
			$id = self::$method($login, $password);
		}

		return $id;
	}

	// adapted from comprofiler.php
	function insertCb( $email, $password, $fullname='') {
		global $_CB_framework, $_CB_database, $mainframe;
		cbimport('cb.plugins');
		cbimport('cb.database');
		cbimport('cb.tables');
		cbimport('cb.tabs');
		cbimport( 'language.front' );

		$mainframe = JFactory::getApplication('site');
		$cbUser = new moscomprofilerUser($_CB_database);
		$id =  false;

		// check CB for an existing user that matches the given email address
		$emailExists = $cbUser->loadByEmail($email);
		if ( $emailExists ) {
			// Error
			return $id;
		}

		$fields = array(
			'name' => (empty($fullname)) ? $email : $fullname,
			'username' => $email,
			'password' => $password,
			'password__verify' => $password,
			'email' => $email
		);

		$cbUser->sendEmail = $cbUser->block = 0;
		$cbUser->approved = $cbUser->confirmed = 1;
		$cbUser->usertype = $_CB_framework->getCfg('new_usertype');
		$cbUser->gid = $_CB_framework->acl->get_group_id($cbUser->usertype, 'ARO');
		$cbUser->registeripaddr = self::_ip();
		$cbUser->password = $password;

		if ($cbUser->saveSafely( $fields, 0, 'register' )) {
			$id = $cbUser->id;
			$cbUser->approved = $cbUser->confirmed = 1;
			$cbUser->storeApproved();
			$cbUser->storeConfirmed();
			activateUser($cbUser, 0, 'UserRegistration');
		}
		return $id;
	}

	function insertCore ($email, $password) {
		$mainframe = JFactory::getApplication('site');
		$user = JFactory::getUser();
		$pathway = $mainframe->getPathway();
		$config = JFactory::getConfig();
		$authorize = JFactory::getACL();
		$usersConfig = JComponentHelper::getParams('com_users');
		$date = JFactory::getDate();

		// Initialize new usertype setting
		$newUsertype = $usersConfig->get('new_usertype');
		if (!$newUsertype) {
			$newUsertype = 'Registered';
		}

		// Set some initial user values
		$user->set('id', 0);
		$user->set('usertype', $newUsertype);
		$user->set('gid', $authorize->get_group_id( '', $newUsertype, 'ARO' ));
		$user->set('block', '0');
		$user->set('email', $email);
		$user->set('username', $email);
		$user->set('name', $email);

		$array = array (
				'email'		=> $email,
				'username'	=> $email,
				'password'	=> $password,
				'password2'	=> $password,
				'block'		=> 0,
			);

		// Bind the post array to the user object
		$user->bind( $array, 'usertype' );
		$user->set('registerDate', $date->toMySQL());
		// If there was an error with registration, set the message and display form
		$user->save();
		return $user->get('id');
	}

	function _ip() {
		global $_SERVER;

		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip_adr_array		=	explode(',',$_SERVER['HTTP_X_FORWARDED_FOR']);
		} else {
			$ip_adr_array		=	array();
		}
		$ip_adr_array[]			=	$_SERVER['REMOTE_ADDR'];
		return addslashes(implode(",",$ip_adr_array));
	}
}