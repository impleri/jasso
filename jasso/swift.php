<?php
if (!(defined('_JEXEC') || defined('_JASSO_AUTH'))) die('Invalid access denied.');

/**
 * Simple Kayako/Swift user class
 *
 * @author Christopher Roussel
 * @package JaSSO
 */
class JKayakoSwift {
	/**
	 * Constructor. Loads Kayako/Swift if not already done.
	 */
	public function __construct() {
		if (!defined('SWIFT_INTERFACE')) {
			self::loadSwift();
		}
	}

	/**
	 * Loads Kayako/Swift (if not already done).
	 */
	private function loadSwift() {
		if (!defined('SWIFT_INTERFACE')) {
			$path = (defined('SWIFT_CUSTOMPATH')) ? SWIFT_CUSTOMPATH : dirname(__FILE__) . '/../__swift/';
			$realpath = realpath($path);
			define('SWIFT_INTERFACE', 'cron');
			define('SWIFT_INTERFACEFILE', __FILE__);
			chdir($realpath);
			require_once ('./swift.php');
		}
	}

	/**
	 * Get a Kayako user
	 * Method is adapted from Kayako's SWIFT_User::Authenticate()
	 *
	 * @param string $email User email
	 * @param bool run ban check for user (default = true)
	 * @return mixed SWIFT_User on success, false otherwise
	 */
	private function &getUser ($email, $check=true) {
		static $kUser = false;

		if (!defined('SWIFT_INTERFACE')) {
			self::loadSwift();
		}

		// load user if not already done
		if (!is_object($kUser)) {
			// load Kayako libraries
			SWIFT_Loader::LoadLibrary('User');
			SWIFT_Loader::LoadLibrary('User:UserEmail');

			if (IsEmailValid($email)) {
				// try to get a user id from the email ...
				$userId = SWIFT_UserEmail::RetrieveUserIDOnUserEmail($email);
				if ($userId) {
					// ... and load the user if one exists
					$kUser = new SWIFT_User($userId);
					// stop if an error occurred
					if ( !($kUser instanceof SWIFT_User && $kUser->GetIsClassLoaded()) ) {
						$kUser = false;
					}
				}
			}
		}

		// check if user is enabled/valid/unexpired
		if ($check && is_object($kUser)) {
			if ( !( $kUser->GetProperty('isenabled')=='1' && $kUser->GetProperty('isvalidated')=='1' && ($kUser->GetProperty('userexpirytimeline')=='0' || $kUser->GetProperty('userexpirytimeline')>time()) ) ) {
				return false;
			}
		}

		return $kUser;
	}

	/**
	 * Log user into Kayako, bypassing the LoginShare
	 * Method is adapted from Kayako's SWIFT_User::Authenticate()
	 *
	 * @param string $login User email (hopefully) from form
	 * @param string $password Password from form
	 * @param bool $hash Should the password be hashed by Kayako? Default = true
	 * @return mixed SWIFT_User on Success, false otherwise
	 */
	public function login ($email, $password, $hash=true) {
		$kUser = self::getUser($email);

		if (is_object($kUser)) {
			// hash password if needed and check password against record
			$password = ($kUser->GetProperty('islegacypassword') == '1' && $hash == true) ? md5($password) : ($hash == true) ? SWIFT_User::GetComputedPassword($password) : $password;
			if ($kUser->GetProperty('userpassword') == $password) {
				// login works yay!
				$kUser->LoadIntoSWIFTNameSpace();
				$kUser->UpdateLastVisit();
				return $kUser;
			}
		}

		return false;
	}

	/**
	 * Start a Kayako session, bypassing the LoginShare
	 * Difference between this and self::login is that login checks password (careful in using this!)
	 *
	 * @param string $email User email
	 * @return bool true on success
	 */
	public function loadSession ($email) {
		$kUser = self::getUser($email, false);

		if (is_object($kUser)) {
			$kUser->Enable();
			$kUser->LoadIntoSWIFTNameSpace();
			$kUser->UpdateLastVisit();
			return true;
		}
	}

	/**
	 * Sync Kayako user with external source (i.e. Joomla)
	 * Let the LoginShare do this for Kayako-side logins!
	 *
	 * @param array User details to update
	 * @return bool true on success
	 */
	public function sync ($data) {
		$kUser = self::getUser($data['email'], false);

		// user exists, so just update
		if (is_object($kUser)) {
			self::update($data);
		}
		else { // user doesn't exist so try to create one
			$kUser = self::insert($data);
		}

		return is_object($kUser);
	}

	/**
	 * Insert user into Kayako (for changing information in Joomla)
	 *
	 * @param array User details to update
	 * @return bool true on success
	 */
	public function &insert ($data) {
		$kUser = false;
		if (IsEmailValid($data['email'])) {
			SWIFT_Loader::LoadLibrary('User:UserGroup');
			$email = mb_strtolower($data['email']);
			$name = (empty($data['name'])) ? $data['username'] : $data['name'];
			$group = SWIFT_UserGroup::RetrieveOnTitle('Registered');
			$data['password_clear'] = ($data['password_clear']) ? $data['password_clear'] : false;
			$kUser = SWIFT_User::Create($group, 0, SWIFT_User::SALUTATION_NONE, $name, '', '', true, SWIFT_User::ROLE_USER, array($email), $data['password_clear'], false, false, false, false, false, false, false, true, false);
			$kUser = ($kUser instanceof SWIFT_User && $kUser->GetIsClassLoaded()) ? $kUser : false;
		}

		return $kUser;
	}

	/**
	 * Update user in Kayako (for changing information in Joomla)
	 *
	 * @param array User details to update
	 * @return bool true on success
	 */
	public function update ($data) {
		$kUser = self::getUser($data['email'], false);

		if (is_object($kUser)) {
			$kUser->UpdatePool('fullname', $data['name']);
			if (isset($data['password_clear'])) {
				$kUser->ChangePassword($data['password_clear']);
			}

			SWIFT_UserEmail::DeleteOnUser(array($kUser->GetUserID()));
			SWIFT_UserEmail::Create($kUser, $data['email'], true);

			return true;
		}

		return false;
	}
}
