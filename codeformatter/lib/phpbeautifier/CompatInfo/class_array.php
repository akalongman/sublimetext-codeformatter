<?php
/**
 * Class dictionary for PHP_CompatInfo 1.9.0a1 or better
 *
 * PHP versions 4 and 5
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Davey Shafik <davey@php.net>
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version  CVS: $Id: class_array.php,v 1.3 2009/01/03 10:52:05 farell Exp $
 * @link     http://pear.php.net/package/PHP_CompatInfo
 * @since    version 1.9.0a1 (2008-11-23)
 */

require_once 'CompatInfo/date_class_array.php';
require_once 'CompatInfo/dom_class_array.php';
require_once 'CompatInfo/libxml_class_array.php';
require_once 'CompatInfo/mysqli_class_array.php';
require_once 'CompatInfo/SimpleXML_class_array.php';
require_once 'CompatInfo/SPL_class_array.php';
require_once 'CompatInfo/SQLite_class_array.php';
require_once 'CompatInfo/standard_class_array.php';
require_once 'CompatInfo/xmlreader_class_array.php';
require_once 'CompatInfo/xmlwriter_class_array.php';
require_once 'CompatInfo/xsl_class_array.php';

/**
 * Predefined Classes
 *
 * > Standard Defined Classes
 *   These classes are defined in the standard set of functions included in
 *   the PHP build.
 * - Directory
 * - stdClass
 * -  __PHP_Incomplete_Class
 *
 * > Predefined classes as of PHP 5
 *   These additional predefined classes were introduced in PHP 5.0.0
 * - Exception
 * - php_user_filter
 *
 * > Miscellaneous extensions
 *   define other classes which are described in their reference.
 *
 * @link http://www.php.net/manual/en/function.get-declared-classes.php
 * @link http://www.php.net/manual/en/reserved.classes.php
 * @global array $GLOBALS['_PHP_COMPATINFO_CLASS']
 */

$GLOBALS['_PHP_COMPATINFO_CLASS'] = array_merge(
    $GLOBALS['_PHP_COMPATINFO_CLASS_DATE'],
    $GLOBALS['_PHP_COMPATINFO_CLASS_DOM'],
    $GLOBALS['_PHP_COMPATINFO_CLASS_LIBXML'],
    $GLOBALS['_PHP_COMPATINFO_CLASS_MYSQLI'],
    $GLOBALS['_PHP_COMPATINFO_CLASS_SIMPLEXML'],
    $GLOBALS['_PHP_COMPATINFO_CLASS_SPL'],
    $GLOBALS['_PHP_COMPATINFO_CLASS_SQLITE'],
    $GLOBALS['_PHP_COMPATINFO_CLASS_STANDARD'],
    $GLOBALS['_PHP_COMPATINFO_CLASS_XMLREADER'],
    $GLOBALS['_PHP_COMPATINFO_CLASS_XMLWRITER'],
    $GLOBALS['_PHP_COMPATINFO_CLASS_XSL']
    );
?>