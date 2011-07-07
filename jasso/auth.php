<?php
define('_JASSO_AUTH', true);
require_once('xml.php');
require_once('joomla.php');
require_once('swift.php');
/**
 * Kayako/Swift - Joomla authentication bridge
 *
 * @author Christopher Roussel
 * @package JaSSO
 */
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
// 		$this->ip = $this->clean($_REQUEST['ipaddress']);
		$this->site = (isset($_REQUEST['interface'])) ? $this->clean($_REQUEST['interface']) : $this->site;
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

		$this->_joomla = new JKayakoJoomla();
		$ret = $this->_joomla->login($this->login, $this->pass);
		$jUser = $this->_joomla->getUser();

		// successful joomla login
		if ($ret && $jUser) {
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
					echo '<div>This hack is really bad</div>';
					$this->_xml->message = 'Invalid Username or Password';
				}
			}
			else {
				echo '<div>User not found in Swift either</div>';
				$this->_xml->message = 'Invalid Username or Password';
			}
		}
		$this->_xml->result = intval($ret);
		$ret = $this->_xml->buildResponse();
		return $ret;
	}

	/**
	 * Joomla results
	 *
	 * Loads XML data from a Joomla user object
	 */
	function returnJoomla ($user) {
		if ($this->site == 'staff') {
			$name = $user->get('name');
			$pos = strpos($name, ' ');
			// required
			$this->_xml->firstname = ($pos > 0) ? substr($name, 0, $pos) : $name;
			$this->_xml->lastname = ($pos > 0) ? substr($name, $pos) : $name;
			$this->_xml->email = $user->get('email');
			$this->_xml->team = 'Staff';
			// optional
			$this->_xml->designation = '';
			$this->_xml->mobilenumber = '';
			$this->_xml->signature = '';
		}
		else {
			// required
			$this->_xml->fullname = $user->get('name');
			$this->_xml->emails = array($user->get('email'));
			// optional
			$this->_xml->phone = '';
			$this->_xml->designation = '';
			$this->_xml->organization = '';
			$this->_xml->organizationtype = 'restricted';
		}
		return;
	}

	/**
	 * Swift results
	 *
	 * Loads XML data from a Swift user object (only occurs when successfully added user to Joomla from Swift)
	 */
	function returnSwift ($user) {
		if ($this->site == 'staff') {
			$this->_xml->firstname = $user->GetProperty('firstname');
			$this->_xml->lastname = $user->GetProperty('lastname');
			$this->_xml->email = $user->get('email');
			$this->_xml->team = $user->GetProperty('team');
			// optional
			$this->_xml->designation = $user->GetProperty('designation');
			$this->_xml->mobilenumber = $user->GetProperty('mobilenumber');
			$this->_xml->signature = $user->GetProperty('signature');
		}
		else {
			// required
			$this->_xml->fullname = $user->GetFullName();
			$this->_xml->emails = $user->GetEmailList();
			// optional
			$this->_xml->phone = $user->GetProperty('phone');
			$this->_xml->designation = $user->GetProperty('userdesignation');
			$this->_xml->organization = $user->GetProperty('organization');
			$this->_xml->organizationtype = $user->GetProperty('organizationtype');
		}
		return;
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