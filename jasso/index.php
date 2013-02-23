<?php
/**
 * Redirect to main interface
 *
 * @author Christopher Roussel
 * @package JaSSO
 * @version 0.8.7
 */

// Load Swift API
define('SWIFT_INTERFACE', 'client');
define('SWIFT_INTERFACEFILE', __FILE__);
$path = (defined("SWIFT_CUSTOMPATH")) ? SWIFT_CUSTOMPATH : './../__swift/';
chdir($path);
require_once ('swift.php');
