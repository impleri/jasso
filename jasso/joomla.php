<?php
/**
 * Joomla user class
 *
 * @author Christopher Roussel
 * @package JaSSO
 */

class JKayakoJoomla {
	/**
	 * @var Path to your Joomla (can be relative, but beware!)
	 */
	var $jPath = '/home/mysite/public_html/';

	/**
	 * @var Boolean switch for whether CB is accessible or not
	 */
	var $cbInstalled = false;

	/**
	 * @var CB plugin core file (relative to JPATH_ADMINISTRATOR/components/)
	 */
	var $cbFile = 'com_comprofiler/plugin.foundation.php';

	/**
	 * Constructor. Loads Joomla if not already done.
	 */
	public function __construct() {
		if (!defined('_JEXEC')) {
			self::loadJoomla();
		}
	}

	/**
	 * Loads Joomla if needed.
	 */
	public function loadJoomla() {
		if (defined('_JEXEC')) {
			return;
		}

		define('JPATH_BASE', self::$jPath );
		define( 'DS', DIRECTORY_SEPARATOR );
		define( '_JEXEC', 1 );

		// load J! -- taken from J!'s index.php
		require_once ( JPATH_BASE.DS.'includes'.DS.'defines.php' );
		require_once ( JPATH_BASE.DS.'includes'.DS.'framework.php' );
		$mainframe = JFactory::getApplication('site');
		$mainframe->initialise();
		self::_loadCB();
	}

	/**
	 * Log user into Joomla
	 *
	 * @param string $login email or username (from form)
	 * @param string $password password (from form)
	 * @return bool true on success
	 */
	public function login ($login, $password) {
		if (!defined('_JEXEC')) {
			self::loadJoomla();
		}

		$mainframe = JFactory::getApplication('site');
		return $mainframe->login(array('username' => $login, 'password' => $password),array('silent' => true));
	}

	/**
	 * Check for a user in Joomla that matches the email
	 *
	 * @param string $email email from Kayako
	 * @return mixed JUser object on success, false othewise
	 */
	public function match ($email) {
		if (!strpos($email, '@')) {
			return false;
		}

		// first line of matching is regular J!
		$db = JFactory::getDBO();
		$sql = 'SELECT `id` from #__users WHERE `email` = "' . $email . '"';
		$db->setQuery($sql);
		$id = $db->loadResult();

		if (intval($id) > 1) {
			return JFactory::getUser($id);
		}

		// second level of matching is CB-specific
		if (self::$cbInstalled) {
			global $_CB_database;

			$cbUser = new moscomprofilerUser($_CB_database);
			if ($cbUser->loadByEmail($email)) {
				return $cbUser->$id;
			}
		}
	}

	/**
	 * Insert user into Joomla
	 *
	 * @param string $kUser SWIFT_User object from Kayako
	 * @param string $email email from form
	 * @param string $password password from form
	 * @return bool true on success
	 */
	public function insert ($kUser, $email, $password) {
		if (!defined('_JEXEC')) {
			self::loadJoomla();
		}

		// not an email, so no registration -- perhaps user is trying to use a joomla username?
		if (!strpos($email, '@')) {
			return false;
		}

		// insert via CB or J!?
		if (self::$cbInstalled) {
			$method = '_insertCb';
		}
		// else use the regular J!
		else {
			$method = '_insertCore';
		}
		return self::$method($email, $password, $kUser->GetFullName());
	}

	/**
	 * Insert user into Joomla (CommunityBuilder method)
	 * Adapted from comprofiler.php -> saveRegistration()
	 *
	 * @param string $email email from Kayako
	 * @param string $password password from form
	 * @param string $fullname full name of user from Kayako
	 * @return mixed int userID on success, false otherwise
	 * @private
	 */
	private function _insertCb( $email, $password, $fullname='') {
		global $_CB_framework, $_CB_database, $mainframe;

		$cbUser = new moscomprofilerUser($_CB_database);
		$cbUser->id = 0;
		$cbUser->registeripaddr = self::_ip();

		$fields = array(
			'name' => (empty($fullname)) ? $email : $fullname,
			'username' => $email,
			'password' => $password,
			'password__verify' => $password,
			'email' => $email,
			'registeripaddr' => self::_ip(),
			'usertype' => $_CB_framework->getCfg('new_usertype')
		);

		if (!$cbUser->saveSafely($fields, 0, 'register')) {
			return false;
		}

		$cbUser->sendEmail = $cbUser->block = 0;
 		$cbUser->approved = $cbUser->confirmed = 1;
// 		$cbUser->usertype = $_CB_framework->getCfg('new_usertype');
		$cbUser->store();
// 		$cbUser->storeApproved();
// 		$cbUser->storeConfirmed();
// 		activateUser($cbUser, 0, 'UserRegistration');
		return $cbUser->id;
	}

	/**
	 * Insert user into Joomla (Joomla core method)
	 * Adapted from UsersModelRegistration::register()
	 *
	 * @param string $email email from Kayako
	 * @param string $password password from form
	 * @param string $fullname full name of user from Kayako
	 * @return mixed int userID on success, false otherwise
	 * @private
	 */
	private function _insertCore ($email, $password, $fullname='') {
		$user = JFactory::getUser();
		$usersConfig = JComponentHelper::getParams('com_users');
		JPluginHelper::importPlugin('user');

		$data = array(
			'email' => $email,
			'username' => $email,
			'name' => $fullname,
			'password' => $password,
			'groups' => array($usersConfig->get('new_usertype')),
			'block' => 0,
		);

		// Bind the data.
		if (!$user->bind($data)) {
			// error
			return false;
		}

		// Store the data.
		if (!$user->save()) {
			//error
			return false;
		}

		return $user->get('id');
	}

	/**
	 * Checks to see if CB is accessible and load it if necessary
	 *
	 * @return bool true on success
	 * @private
	 */
	private function _checkCB() {
		$cbFile = JPATH_ADMINISTRATOR . DS . 'components' . DS . self::$cbFile;
		if (file_exists($cbFile)) {
			require_once($cbFile);
			self::$cbInstalled = true;
			return true;
		}
		return false;
	}

	/**
	 * Loads CB if needed.
	 * @private
	 */
	private function _loadCB() {
		global $_CB_framework, $_CB_database, $mainframe, $_PLUGINS;
		if (!self::$cbInstalled) {
			if (self::_checkCB()) {
				$mainframe = JFactory::getApplication('site');
				cbimport('cb.database');
				cbimport('cb.tables');
				cbimport('cb.tabs');
				cbimport('language.front');
			}
		}
	}

	/**
	 * Discover IP address (for CB)
	 * Adapted from
	 *
	 * @return string list of ip addresses
	 * @private
	 */
	private function _ip() {
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