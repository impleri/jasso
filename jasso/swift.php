<?php
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
	public function loadSwift() {
		// Load Swift API
		if (defined(SWIFT_INTERFACE)) {
			return;
		}

		define('SWIFT_INTERFACE', 'api');
		define('SWIFT_INTERFACEFILE', __FILE__);
		$path = (defined("SWIFT_CUSTOMPATH")) ? SWIFT_CUSTOMPATH : dirname(__FILE__) . '/../__swift/';
		chdir($path);
		require_once ('swift.php');
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
		if (!defined('SWIFT_INTERFACE')) {
			self::loadSwift();
		}

		// user must have tried a Joomla login or forgotten to put a password, so don't bother with Kayako
		if (!IsEmailValid($email)) {
			return false;
		}

		// make sure the Kayako libraries are loaded
		SWIFT_Loader::LoadLibrary('User');
		SWIFT_Loader::LoadLibrary('User:UserEmail');

		// try to get a user id from the email ...
		$userId = SWIFT_UserEmail::RetrieveUserIDOnUserEmail($email);
		if ($userId) {
			// ... and load the user if one exists
			$kUser = new SWIFT_User($userId);
			if ( $kUser instanceof SWIFT_User && $kUser->GetIsClassLoaded() && $kUser->GetProperty('isenabled') == '0' && $kUser->GetProperty('isvalidated') == '0' && ($kUser->GetProperty('userexpirytimeline') == '0' || $kUser->GetProperty('userexpirytimeline') > time())
			) {
				// hash password if needed and check password against record
				$password = ($kUser->GetProperty('islegacypassword') == '1' && $hash == true) ? md5($password) : ($hash == true) ? SWIFT_User::GetComputedPassword($password) : $password;
				if ($kUser->GetProperty('userpassword') == $password) {
					// login works yay!
					$kUser->UpdateLastVisit();
					return $kUser;
				}
			}
		}

		return false;
	}

	/**
	 * Insert user into Kayako (for Joomla extension)
	 * Let the LoginShare do this for LS auth!
	 *
	 * @param array user details to update
	 * @return bool true on success
	 */
	public function insert ($data) {
		SWIFT_Loader::LoadLibrary('User');
		SWIFT_Loader::LoadLibrary('User:UserGroup');

		if (!IsEmailValid($data['email'])) {
			return false;
		}

		$email = mb_strtolower($data['email']);
		$name = (empty($data['name'])) ? $data['username'] : $data['name'];
		$group = SWIFT_UserGroup::RetrieveOnTitle('Registered');
		$kUser = SWIFT_User::Create($group, 0, SWIFT_User::SALUTATION_NONE, $name, '', '', true, SWIFT_User::ROLE_USER, array($email), $data['password_clear'], false, false, false, false, false, false, false, true, false);

		return (is_object($kUser));
	}

	/**
	 * Wrapper to update user in Kayako (for changing information in Joomla)
	 *
	 * @param array User details to update
	 * @return bool true on success
	 */
	public function update ($data) {
		$userId = SWIFT_UserEmail::RetrieveUserIDOnUserEmail($data['email']);

		if (!$userId) {
			return false;
		}

		SWIFT_Loader::LoadLibrary('User:UserEmail');

		$kUser = new SWIFT_User($userId);
		$kUser->UpdatePool('fullname', $data['name']);
		$kUser->ChangePassword($data['password_clear']);

		if (IsEmailValid($data['email'])) {
			SWIFT_UserEmail::DeleteOnUser(array($kUser->GetUserID()));
			SWIFT_UserEmail::Create($kUser, $data['email'], true);
		}

	}
}
