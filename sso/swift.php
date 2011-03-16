<?php
/**
 * Simple Kayako/Swift user class
 *
 * @author Christopher Roussel
 */

// Load Swift API
define('SWIFT_INTERFACE', 'api');
define('SWIFT_INTERFACEFILE', __FILE__);
$path = (defined("SWIFT_CUSTOMPATH")) ? (SWIFT_CUSTOMPATH) : './../__swift/';
chdir($path);
require_once ('swift.php');

/**
 * Wrapper for Swift's User model SWIFT_User
 * @author Christopher Roussel
 */
class JKayakoSwift extends SWIFT_User {
	/**
	 * Authentication method
	 *
	 * Method is straight from Kayako.
	 * @author Varun Shoor, Christopher Roussel
	 *
	 * @param string $_email The User Email Address
	 * @param string $_userPassword The User Password
	 * @return mixed "_SWIFT_UserObject" (OBJECT) on Success, "false" otherwise
	 */
	static public function Authenticate($_email, $_userPassword, $_computeHash = true) {
		$_SWIFT = SWIFT::GetInstance();

		$_finalPassword = $_userPassword;
		if ($_computeHash == true) {
			$_finalPassword = self::GetComputedPassword($_userPassword);
		}

		// This is there the LoginShare would be

		SWIFT_Loader::LoadLibrary('User:UserEmail');

		// First retrieve the user email address
		$_userID = SWIFT_UserEmail::RetrieveUserIDOnUserEmail($_email);
		if (!$_userID) {
			// No user found with that email?
			SWIFT::Set('errorstring', $_SWIFT->Language->Get('invaliduser'));
			return false;
		}

		// Now that we have the user id.. we need to get the user.
		$_SWIFT_UserObject = new SWIFT_User($_userID);
		if (!$_SWIFT_UserObject instanceof SWIFT_User || !$_SWIFT_UserObject->GetIsClassLoaded()) {
			// How did this happen?
			throw new SWIFT_Exception(SWIFT_INVALIDDATA);
			return false;
		}

		// Is user disabled?
		if ($_SWIFT_UserObject->GetProperty('isenabled') == '0' || $_SWIFT_UserObject->GetProperty('isvalidated') == '0') {
			SWIFT::Set('errorstring', $_SWIFT->Language->Get('invaliduserdisabled'));
			return false;

		// Has user expired?
		} else if ($_SWIFT_UserObject->GetProperty('userexpirytimeline') != '0' && $_SWIFT_UserObject->GetProperty('userexpirytimeline') < DATENOW) {
			SWIFT::Set('errorstring', $_SWIFT->Language->Get('invaliduserexpired'));
			return false;
		}

		// Legacy password support
		if ($_SWIFT_UserObject->GetProperty('islegacypassword') == '1' && $_computeHash == true) {
			$_finalPassword = md5($_userPassword);
		}

		// Authenticate now...
		if ($_SWIFT_UserObject->GetProperty('userpassword') != $_finalPassword || empty($_finalPassword)) {
			SWIFT::Set('errorstring', $_SWIFT->Language->Get('invaliduser'));
			return false;
		}

		// User should be authenticated by now..
		$_SWIFT_UserObject->LoadIntoSWIFTNameSpace();
		$_SWIFT_UserObject->UpdateLastVisit();
		return $_SWIFT_UserObject;
	}
}