<?php
/**
 * XML Bridge
 *
 * @author Christopher Roussel
 * @package JaSSO
 */

/**
 * XML handler class
 *
 * Mostly overloaded methods.
 * @author Christopher Roussel
 */
class JKayakoXml {
	/**
	 * XML response field header
	 */
	private $_xml = "<?xml version='1.0' encoding='UTF-8'?>\n<loginshare>\n%s</loginshare>";

	/**
	 * XML response field template
	 */
	private $_tpl = "<%s>%s</%s>\n";

	/**
	 * field data array
	 */
	private $_data = array(
		'usergroup' => 'Registered',
		'team' => 'Registered',
		'result' => 0,
		'message' => '',
	);

	/**
	 * Overloaded set method
	 *
	 * @param string Key
	 * @param mixed Value
	 */
	public function __set($name, $value) {
		$this->_data[$name] = $value;
	}

	/**
	 * Overloaded get method
	 *
	 * @param string Key
	 * @return mixed Value
	 */
	public function __get($name) {
		$ret = null;
		if (array_key_exists($name, $this->_data)) {
			$ret = $this->_data[$name];
		}
		return $ret;
	}

	/**
	 * Overloaded add method for building the XML string
	 *
	 * @param string Method name
	 * @param array Arguments passed to method (not used)
	 * @return string XML formatted node
	 */
	public function __call ($name, $args=array()) {
		if (method_exists('self', $name)) {
			return $this->$name();
		}
		// Not an add* method so don't bother
		if (strpos($name, 'add') !== 0) {
			return '';
		}
		$key = strtolower(substr($name, 3));
		$val = $this->$key;
		return (empty($val)) ? '' : sprintf($this->_tpl, $key, $val, $key);
	}

	/**
	 * Response output
	 *
	 * @param string Response type (user or staff)
	 * @return string XML formatted node
	 */
	public function buildResponse ($type='user') {
		$method = 'add' . ucfirst($type);
		$res = $this->addResult() . $this->addMessage();
		$res .= ($this->result) ? $this->$method() : '';
		return sprintf($this->_xml, $res);
	}

	/**
	 * User response node
	 *
	 * @return string XML formatted node
	 */
	private function addUser () {
		$string = "\n" . $this->addUsergroup() . $this->addFullname() . $this->addDesignation() . $this->addEmails() . $this->addPhone();
		return sprintf($this->_tpl, 'user', $string, 'user');
	}

	/**
	 * Staff response node
	 *
	 * @return string XML formatted node
	 */
	private function addStaff () {
		return sprintf($this->_tpl, 'user', $this->addTeam() . $this->addFirstname() . $this->addLastname() . $this->addDesignation() . $this->addEmail() . $this->addMobilenumber() . $this->addSignature(), 'user');
	}

	/**
	 * Single email node
	 *
	 * @return string XML formatted node
	 */
	private function addEmail ($email) {
		return sprintf($this->_tpl, 'email', $email, 'email');
	}

	/**
	 * User emails node
	 *
	 * @return string XML formatted node
	 */
	private function addEmails () {
		$include = "\n";
		foreach ($this->emails as $email) {
			$include .= $this->addEmail($email);
		}
		return sprintf($this->_tpl, 'emails', $include, 'emails');
	}
}
