<?php
/**
 * Joomla User Bridge
 *
 * @author Christopher Roussel
 */

require_once('xml.php');
require_once('joomla.php');
require_once('swift.php');

class JKayakoAuth {
	/**
	 * User login (username or email)
	 * @param str Login passed by Kayako
	 */
	var $login = '';

	/**
	 * User password
	 * @param str Plain text password passed by Kayako
	 */
	var $pass = '';

	/**
	 * User IP address
	 * @param str User IP passed by Kayako
	 */
	var $ip = '';

	/**
	 * Login type (user or staff)
	 * @param str Login type specificed in URL (cannot pass directly from within Kayako)
	 */
	var $site = 'user';

	/**
	 * XML response handler
	 * @param obj JKayakoXml
	 */
	var $_xml = null;

	/**
	 * Joomla user handler
	 * @param obj JKayakoJoomla
	 */
	var $_joomla = null;

	/**
	 * Swift user handler
	 * @param obj JKayakoSwift
	 */
	var $_swift = null;

	/**
	 * Simple Constructor
	 *
	 * Gets and cleans request data
	 */
	function __construct () {
		$this->login = $this->clean($_REQUEST['username']);
		$this->pass = $this->clean($_REQUEST['password']);
		$this->ip = $this->clean($_REQUEST['ipaddress']);
		$this->site = $this->clean($_REQUEST['site']);

		$this->_xml = new JKayakoXml();
		$this->_joomla = new JKayakoJoomla();
	}

	/**
	 * Authentication process
	 *
	 * Tries to login and sync users whenenever possible
	 */
	function process() {
		if (empty($this->login) || empty($this->pass)) {
			$this->_xml->message = 'Missing valid login information';
			return;
		}

		$ret = 0;
		$jUser = $this->_joomla->login($this->login, $this->pass);

		// successful joomla login
		if ($jUser) {
			$ret = 1;
			$this->returnJoomla($jUser);
		}
		// failed joomla login, so try kayako
		else {
			$this->_swift = new JKayakoSwift();
			$kUser = $this->_swift->Authenticate($this->login, $this->pass);
			// there is a kayako user
			if ($kUser) {
				// we can find a joomla user with the same login info
				$jUser = $this->match($kUser);
				if ($jUser) {
					$this->returnJoomla($jUser);
					$ret = 1;
				}
				elseif($this->_joomla->insert($kUser)) {
					$this->returnSwift($kUser);
					$ret = 1;
				}
				else {
					$this->_xml->message = 'Invalid Username or Password';
				}
			}
		}
		$this->_xml->result = $ret;
		return $this->_xml->buildResponse();
	}

	/**
	 * Matching process
	 *
	 * Tries to connect an existing Joomla user with an existing Kayako user
	 */
	function match ($user) {
		$ret = $this->_joomla->match($user);

	}

	/**
	 * Joomla results
	 *
	 * Loads XML data from a Joomla user object
	 */
	function returnJoomla ($user) {
		if ($this->site == 'staff') {

		}
		else {
			$this->_xml->name = $user->get('name');
			$this->_xml->email = $user->get('email');
		}
	}

	/**
	 * Swift results
	 *
	 * Loads XML data from a Swift user object (only occurs when successfully added user to Joomla from Swift)
	 */
	function returnSwift ($user) {
		if ($this->site == 'staff') {

		}
		else {
			$this->_xml->name = $user->GetFullName();
			$this->_xml->emails = $user->GetEmailList();
			$this->_xml->designation = $user->GetProperty('userdesignation');
			$this->_xml->phone = $user->GetProperty('phone');
		}
	}

	/**
	 * Clean variable
	 *
	 * Strips and escapes bad characters from $_REQUEST (bad hacker!)
	 */
	function clean ($var) {

	}
}

// that's it.
$auth = new JKayakoAuth();
echo $auth->process();
die;

/* Dev Comment
Process:
X	1. Get posted data							auth.php
	2. Clean posted data						auth.php
X	3. If there is empty data					auth.php
X		A. FAIL (no login information)			auth.php -> xml.php
X	4. Else (i.e. good data)					auth.php
X		A. Process Joomla authentication		auth.php -> joomla.php
X		B. If success								joomla.php -> auth.php
X			I. WIN (Let Swift handle syncing)	auth.php -> xml.php
X		C. Else (i.e. failure)						joomla.php -> auth.php
X			I. Process Swift authentication				auth.php -> swift.php
X			II. If failure							swift.php -> auth.php
X				a. FAIL (user not found)		auth.php -> xml.php
X			III. Else (i.e. success)				swift.php
X				a. Add user to Joomla					swift.php -> joomla.php
X				b. If success							joomla.php -> swift.php
X					i. WIN						swift.php -> auth.php -> xml.php
X				c. Else (i.e. failure)					swift.php
X					i. Match users in DB				swift.php
X					ii. If strong match					swift.php
X						1. Sync passwords				swift.php
X						2. WIN					swift.php -> auth.php -> xml.php
X					iii. Else						swift.php -> auth.php
X						1. FAIL (user cannot be synced)	auth.php -> xml.php
*/