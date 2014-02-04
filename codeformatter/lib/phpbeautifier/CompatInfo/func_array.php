<?php
/**
 * Function dictionary for PHP_CompatInfo 1.9.0a1 or better
 *
 * PHP versions 4 and 5
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Davey Shafik <davey@php.net>
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version  CVS: $Id: func_array.php,v 1.16 2009/01/03 10:52:05 farell Exp $
 * @link     http://pear.php.net/package/PHP_CompatInfo
 * @since    version 1.9.0a1 (2008-11-23)
 */

require_once 'CompatInfo/bcmath_func_array.php';
require_once 'CompatInfo/calendar_func_array.php';
require_once 'CompatInfo/ctype_func_array.php';
require_once 'CompatInfo/date_func_array.php';
require_once 'CompatInfo/dom_func_array.php';
require_once 'CompatInfo/filter_func_array.php';
require_once 'CompatInfo/ftp_func_array.php';
require_once 'CompatInfo/gd_func_array.php';
require_once 'CompatInfo/gettext_func_array.php';
require_once 'CompatInfo/hash_func_array.php';
require_once 'CompatInfo/iconv_func_array.php';
require_once 'CompatInfo/json_func_array.php';
require_once 'CompatInfo/libxml_func_array.php';
require_once 'CompatInfo/mbstring_func_array.php';
require_once 'CompatInfo/mysql_func_array.php';
require_once 'CompatInfo/mysqli_func_array.php';
require_once 'CompatInfo/openssl_func_array.php';
require_once 'CompatInfo/pcre_func_array.php';
require_once 'CompatInfo/pgsql_func_array.php';
require_once 'CompatInfo/session_func_array.php';
require_once 'CompatInfo/SimpleXML_func_array.php';
require_once 'CompatInfo/SPL_func_array.php';
require_once 'CompatInfo/SQLite_func_array.php';
require_once 'CompatInfo/standard_func_array.php';
require_once 'CompatInfo/tokenizer_func_array.php';
require_once 'CompatInfo/wddx_func_array.php';
require_once 'CompatInfo/xml_func_array.php';
require_once 'CompatInfo/xmlwriter_func_array.php';

/**
 * Predefined Functions
 *
 * @global array $GLOBALS['_PHP_COMPATINFO_FUNCS']
 */

$GLOBALS['_PHP_COMPATINFO_FUNCS'] = array_merge(
    $GLOBALS['_PHP_COMPATINFO_FUNC_BCMATH'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_CALENDAR'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_CTYPE'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_DATE'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_DOM'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_FILTER'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_FTP'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_GD'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_GETTEXT'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_HASH'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_ICONV'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_JSON'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_LIBXML'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_MBSTRING'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_MYSQL'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_MYSQLI'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_OPENSSL'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_PCRE'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_PGSQL'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_SESSION'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_SIMPLEXML'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_SPL'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_SQLITE'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_STANDARD'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_TOKENIZER'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_WDDX'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_XML'],
    $GLOBALS['_PHP_COMPATINFO_FUNC_XMLWRITER']
    );
?>