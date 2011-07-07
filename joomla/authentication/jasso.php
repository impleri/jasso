<?php
/**
 * Joomla login by email (straight from Joomla)
 *
 * @author Joomla Team
 * @package JaSSO
 * @subpackage Joomla
 */

// No direct access
defined('_JEXEC') or die('Invalid access denied.');

jimport('joomla.plugin.plugin');

/**
 * Joomla Authentication plugin
 */
class plgAuthenticationJasso extends JPlugin
{
	/**
	 * This method should handle any authentication and report back to the subject
	 *
	 * @param	array	Array holding the user credentials
	 * @param	array	Array of extra options
	 * @param	object	Authentication response object
	 * @return	boolean
	 */
	public function onUserAuthenticate($credentials, $options, &$response) {
		jimport('joomla.user.helper');

		// Joomla does not like blank passwords
		if (empty($credentials['password'])) {
			$response->status = JAUTHENTICATE_STATUS_FAILURE;
			$response->error_message = JText::_('JGLOBAL_AUTH_EMPTY_PASS_NOT_ALLOWED');
			return false;
		}

		// Get a database object
		$db		= JFactory::getDbo();
		$query	= $db->getQuery(true);

		$query->select('id, password');
		$query->from('#__users');
		$query->where('email=' . $db->Quote($credentials['username']));
		// Here;s the difference: email

		$db->setQuery($query);
		$result = $db->loadObject();

		if ($result) {
			$parts	= explode(':', $result->password);
			$crypt	= $parts[0];
			$salt	= @$parts[1];
			$testcrypt = JUserHelper::getCryptedPassword($credentials['password'], $salt);

			if ($crypt == $testcrypt) {
				$user = JUser::getInstance($result->id); // Bring this in line with the rest of the system
				$response->email = $user->email;
				$response->username = $user->username;
				$response->fullname = $user->name;
				if (JFactory::getApplication()->isAdmin()) {
					$response->language = $user->getParam('admin_language');
				}
				else {
					$response->language = $user->getParam('language');
				}
				$response->status = JAUTHENTICATE_STATUS_SUCCESS;
				$response->error_message = '';
			} else {
				$response->status = JAUTHENTICATE_STATUS_FAILURE;
				$response->error_message = JText::_('JGLOBAL_AUTH_INVALID_PASS');
			}
		} else {
			$response->status = JAUTHENTICATE_STATUS_FAILURE;
			$response->error_message = JText::_('JGLOBAL_AUTH_NO_USER');
		}
	}
}
