<?php
/**
 * Kayako/Swift - Joomla authentication bridge
 *
 * @author Christopher Roussel
 * @package JaSSO
 */

require_once('xml.php');
require_once('joomla.php');
require_once('swift.php');

class JKayakoAuth {
	/**
	 * User login (username or email)
	 * @var Login passed by Kayako in request
	 */
	var $login = '';

	/**
	 * User password
	 * @var Plain text password passed by Kayako in request
	 */
	var $pass = '';

	/**
	 * User IP address
	 * @var User IP passed by Kayako in request
	 */
	var $ip = '';

	/**
	 * Login type (user or staff)
	 * @var Login type specificed in reuqest (cannot pass directly from within Kayako)
	 */
	var $site = 'user';

	/**
	 * XML response handler
	 * @var JKayakoXml object
	 */
	var $_xml = null;

	/**
	 * Joomla user handler
	 * @var JKayakoJoomla object
	 */
	var $_joomla = null;

	/**
	 * Swift user handler
	 * @var JKayakoSwift object
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
		$this->_joomla = new JKayakoJoomla();
		$jUser = $this->_joomla->login($this->login, $this->pass);

		// successful joomla login
		if ($jUser) {
			$ret = 1;
			$this->returnJoomla($jUser);
		}
		// failed joomla login, so try kayako
		else {
			$this->_swift = new JKayakoSwift();
			$kUser = $this->_swift->login($this->login, $this->pass);
			// there is a kayako user
			if ($kUser) {
				$jUser = $this->_joomla->match($this->login);
				// email also exists in joomla, so map the two for future (will overwrite all but password in Kayako)
				if ($jUser) {
					$ret = $this->returnJoomla($jUser);
				}
				// email not in joomla, so register user there
				elseif($this->_joomla->insert($kUser, $this->login, $this->pass)) {
					$ret = $this->returnSwift($kUser);
				}
				// something went horribly wrong
				else {
					$this->_xml->message = 'Invalid Username or Password';
				}
			}
		}
		$this->_xml->result = $ret;
		return $this->_xml->buildResponse();
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
		return 1;
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
		return 1;
	}

	/**
	 * Clean variable
	 *
	 * Strips and escapes bad characters from $_REQUEST (bad hacker!)
	 */
	function clean ($var) {
		// TODO
		return $var;
	}
}

// that's it.
$auth = new JKayakoAuth();
echo $auth->process();
die;
