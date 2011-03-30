<?php
/**
 * JaSSO Kayako extension
 *
 * @author Christopher Roussel
 * @package JaSSO
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

/**
 * JaSSO User Sync plugin
 */
class plgUserJasso extends JPlugin
{
	public function __construct(&$subject, $config=array()) {
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}
	/**
	 * Syncs user details here with Kayako's user details
	 *
	 * @param array $user user data
	 * @param boolean $isnew true if new user
	 * @param boolean $success true if successful
	 * @param string $void (unused)
	 */
	public function onUserAfterSave($user, $isnew, $success, $void='') {
		// Initialise variables.
		$method = 'update';

		// registration/update was unsuccessful, so why bother?
		if (!$success) {
			return;
		}

		// no need to sync if we have a new user and have registration sync disabled
		if ( $isnew && !$this->params->get('addnew',0) ) {
			return;
		}

		// syncing even at registration (why?!?)
		elseif ( $isnew && $this->params->get('addnew',0) ) {
			$method = 'insert';
		}

		$kUser = $this->_getSwift();
		if (is_object($kUser)) {
			$kUser->$method($user);
		}
	}

	/**
	 * (WIP) This will create a user session in Kayako on a successful login
	 *
	 * @param array $user User data
	 * @param array $options login options
	 * @return	bool true on success
	 */
	public function onUserLogin($user, $options = array())
	{
		return true;
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