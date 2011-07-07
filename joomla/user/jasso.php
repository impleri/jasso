<?php
/**
 * JaSSO Kayako extension
 *
 * @author Christopher Roussel
 * @package JaSSO
 * @subpackage Joomla
 */

// No direct access
defined('_JEXEC') or die('Invalid access denied.');

jimport('joomla.plugin.plugin');

/**
 * JaSSO User Sync plugin
 */
class plgUserJasso extends JPlugin
{
	/**
	 * Syncs user details here with Kayako's user details
	 *
	 * @param array $user user data
	 * @param boolean $isnew true if new user
	 * @param boolean $success true if successful
	 * @param string $void (unused)
	 */
	public function onUserAfterSave($user, $isnew, $success, $void='') {
		// registration/update was unsuccessful, so why bother?
		if (!$success) {
			return;
		}

		// registration sync disabled
		if ($isnew && $this->params->get('addnew',0)) {
			return;
		}

		$kUser = $this->_getSwift();
		if (is_object($kUser)) {
			$kUser->sync($user, $isnew);
		}
	}

	/**
	 * Create a user session in Kayako on a successful Joomla login
	 *
	 * @param array $user User data
	 * @param array $options login options
	 * @return	bool true on success
	 */
	public function onUserLogin($user, $options = array())
	{
		$kUser = $this->_getSwift();
		if (!is_object($kUser)) {
			return false;
		}

		return $kUser->loadSession($user['email']);
	}

	/**
	 * Load JKayakoSwift
	 *
	 * @return	mixed object JKayakoSwift if available, false if error
	 */
	private function &_getSwift() {
		$path = $this->params->get('path', JPATH_BASE . DS . 'kayako' . DS . 'jasso') . DS . 'swift.php';
		if (file_exists($path)) {
			require_once($path);
			$kUser = new JKayakoSwift;
			return $kUser;
		}

		return false;
	}
}