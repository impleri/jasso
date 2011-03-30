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
	var $_xml = '<?xml version="1.0" encoding="UTF-8" ?><loginshare>%s</loginshare>';

	/**
	 * XML response field template
	 */
	var $_tpl = '<%s>%s<\%s>';

	/**
	 * field data array
	 */
	var $_data = array();

	/**
	 * Simple constructor
	 */
	public function __construct () {
		// Default values
		$this->usergroup = 'Registered';
		$this->result = 0;
		$this->message = '';
	}

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
	 * @param array Arguments (if any) passed to method
	 * @return string XML formatted node
	 */
	function __call ($name, $args) {
		// Not an add* method so don't bother
		if (strpos($name, 'add') !== 0) {
			return '';
		}
		$var = strtolower(substr($name, 4));
		$res = (empty($args[0])) ? $this->$var : $args[0];
		return (empty($res)) ? '' : sprintf($this->_tpl, $var, $res, $var);
	}

	/**
	 * Response output
	 *
	 * @param string Response type (user or staff)
	 * @return string XML formatted node
	 */
	function buildResponse ($type='user') {
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
	function addUser () {
		return sprintf($this->_tpl, 'user', $this->addUsergroup() . $this->addFullname() . $this->addDesignation() . $this->addEmails() . $this->addPhone(), 'user');
	}

	/**
	 * Staff response node
	 *
	 * @return string XML formatted node
	 */
	function addStaff () {
		return sprintf($this->_tpl, 'user', $this->addTeam() . $this->addFirstname() . $this->addLastname() . $this->addDesignation() . $this->addEmail() . $this->addMobilenumber() . $this->addSignature(), 'user');
	}

	/**
	 * User emails node
	 *
	 * @return string XML formatted node
	 */
	function addEmails ($emails=array()) {
		$include = '';
		$emails = empty($emails) ? $this->emails : $emails;

		if (!is_array($emails)) {
			$include = $this->addEmail($emails);
		} else {
			foreach ($emails as $email) {
				$include .= $this->addEmail($email);
			}
		}
		return sprintf($this->_tpl, 'emails', $include, 'emails');
	}
}
