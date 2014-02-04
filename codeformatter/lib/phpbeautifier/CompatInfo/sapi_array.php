<?php
/**
 * SAPI dictionary for PHP_CompatInfo 1.9.0b2 or better
 *
 * PHP versions 4 and 5
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Davey Shafik <davey@php.net>
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version  CVS: $Id: sapi_array.php,v 1.1 2008/12/14 17:29:15 farell Exp $
 * @link     http://pear.php.net/package/PHP_CompatInfo
 * @since    version 1.9.0b2 (2008-12-19)
 */

$sapi_name    = php_sapi_name();
$sapi_runtime = '_PHP_COMPATINFO_SAPI_' . strtoupper($sapi_name);

require_once 'PHP/CompatInfo/'.$sapi_name.'_sapi_array.php';

/**
 * Predefined SAPI Functions
 *
 * @global array $GLOBALS['_PHP_COMPATINFO_SAPI']
 */

$GLOBALS['_PHP_COMPATINFO_SAPI'] = $GLOBALS[$sapi_runtime];
?>