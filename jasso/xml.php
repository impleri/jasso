<?php
defined('_JASSO_AUTH') or die('Invalid access denied.');
/**
 * XML handler class
 *
 * Creates an XML response string for auth requests. Most properties and methods are overloaded.
 * @package JaSSO
 * @author Christopher Roussel
 */
class JKayakoXml {
	/**
	 * @var string XML response field header
	 */
	private $_xml = "<?xml version='1.0' encoding='UTF-8'?>\n<loginshare>\n%s</loginshare>";

	/**
	 * @var string XML response field template
	 */
	private $_tpl = "<%s>%s</%s>\n";

	/**
	 * @var array field data
	 */
	private $_data = array(
		'usergroup' => 'Registered',
		'team' => 'Registered',
	);

	/**
	 * @var bool auth result response
	 */
	public $result = 0;

	/**
	 * @var string auth response message (if any)
	 */
	public $message = '';

	/**
	 * @var array user emails
	 */
	public $emails = array();

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
	 * @param array Arguments passed to method (add* methods expect one argument: the value to add to the XML node)
	 * @return string XML formatted node
	 */
	public function __call ($name, $args=array()) {
		// Not an add* method so don't bother
		if (strpos($name, 'add') !== 0) {
			return '';
		}
		$key = strtolower(substr($name, 3));
		return (!empty($args[0])) ? sprintf($this->_tpl, $key, $args[0], $key) : '';
	}

	/**
	 * Build an XML response
	 *
	 * @param string Response type (user or staff)
	 * @return string XML formatted response
	 */
	public function buildResponse ($type='user') {
		$res = $this->addResult() . $this->addMessage();
		if ($this->result) {
			if ($type == 'user') {
				$res .= $this->formatEmails();
			}
			foreach ($this->_data as $key => $val) {
				$method = 'add' . $key;
				$res .= $this->$method($val);
			}
		}
		return sprintf($this->_xml, sprintf($this->_tpl, $type, $res, $type));
	}

	/**
	 * Formats a user emails node
	 *
	 * @return string XML formatted node
	 */
	private function formatEmails () {
		$include = "\n";
		foreach ($this->emails as $email) {
			$include .= $this->addEmail($email);
		}
		return sprintf($this->_tpl, 'emails', $include, 'emails');
	}

}
