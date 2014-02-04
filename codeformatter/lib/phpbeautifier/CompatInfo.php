<?php
/**
 * Copyright (c) 2004-2009, Davey Shafik <davey@php.net>
 *                          Laurent Laville <pear@laurent-laville.org>
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the authors nor the names of its contributors
 *       may be used to endorse or promote products derived from this software
 *       without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS
 * BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * PHP versions 4 and 5
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Davey Shafik <davey@php.net>
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version  CVS: $Id: CompatInfo.php,v 1.108 2009/01/02 10:18:47 farell Exp $
 * @link     http://pear.php.net/package/PHP_CompatInfo
 * @since    File available since Release 0.7.0
 */

require_once 'CompatInfo/Parser.php';

/**
 * Check Compatibility of chunk of PHP code
 *
 * This class is the controller in the MVC design pattern of API 1.8.0 (since beta 2)
 *
 * @category  PHP
 * @package   PHP_CompatInfo
 * @author    Davey Shafik <davey@php.net>
 * @author    Laurent Laville <pear@laurent-laville.org>
 * @copyright 2003 Davey Shafik and Synaptic Media. All Rights Reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version   Release: 1.9.0
 * @link      http://pear.php.net/package/PHP_CompatInfo
 * @since     Class available since Release 0.7.0
 */
class PHP_CompatInfo
{
    /**
     * Instance of the parser (model in MVC desing pattern)
     *
     * @var    object
     * @since  1.8.0b2
     * @access protected
     */
    var $parser;

    /**
     * Class constructor (ZE1) for PHP4
     *
     * @param string $render (optional) Type of renderer to show results
     * @param array  $conf   (optional) A hash containing any additional
     *                       configuration a renderer may use
     *
     * @access public
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function PHP_CompatInfo($render = 'array', $conf = array())
    {
        $this->__construct($render, $conf);
    }

    /**
     * Class constructor (ZE2) for PHP5+
     *
     * @param string $render (optional) Type of renderer to show results
     * @param array  $conf   (optional) A hash containing any additional
     *                       configuration a renderer may use
     *
     * @access public
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function __construct($render = 'array', $conf = array())
    {
        $this->parser = new PHP_CompatInfo_Parser();
        $this->parser->setOutputDriver($render, $conf);
    }

    /**
     * Registers a new listener
     *
     * Registers a new listener with the given criteria.
     *
     * @param mixed  $callback A PHP callback
     * @param string $nName    (optional) Expected notification name
     *
     * @access public
     * @return void
     * @since  version 1.8.0b3 (2008-06-07)
     */
    function addListener($callback, $nName = EVENT_DISPATCHER_GLOBAL)
    {
        $this->parser->addListener($callback, $nName);
    }

    /**
     * Removes a registered listener
     *
     * Removes a registered listener that correspond to the given criteria.
     *
     * @param mixed  $callback A PHP callback
     * @param string $nName    (optional) Expected notification name
     *
     * @access public
     * @return bool  True if listener was removed, false otherwise.
     * @since  version 1.8.0b3 (2008-06-07)
     */
    function removeListener($callback, $nName = EVENT_DISPATCHER_GLOBAL)
    {
        return $this->parser->removeListener($callback, $nName);
    }

    /**
     * Load components list
     *
     * Load components list for a PHP version or subset
     *
     * @param string         $min           PHP minimal version
     * @param string|boolean $max           (optional) PHP maximal version
     * @param boolean        $include_const (optional) include constants list
     *                                                 in final result
     * @param boolean        $groupby_vers  (optional) give initial php version
     *                                                 of function or constant
     *
     * @return array         An array of php function/constant names history
     * @access public
     * @static
     * @since  version 1.2.0 (2006-08-23)
     */
    function loadVersion($min, $max = false,
                         $include_const = false, $groupby_vers = false)
    {
        return $this->parser->loadVersion($min, $max, $include_const, $groupby_vers);
    }

    /**
     * Parse a data source
     *
     * Parse a data source with auto detect ability. This data source, may be
     * one of these follows: a directory, a file, a string (chunk of code),
     * an array of multiple origin.
     *
     * Each of five parsing functions support common and specifics options.
     *
     *  * Common options :
     *  - 'debug'                   Contains a boolean to control whether
     *                              extra ouput is shown.
     *  - 'ignore_functions'        Contains an array of functions to ignore
     *                              when calculating the version needed.
     *  - 'ignore_constants'        Contains an array of constants to ignore
     *                              when calculating the version needed.
     *  - 'ignore_extensions'       Contains an array of php extensions to ignore
     *                              when calculating the version needed.
     *  - 'ignore_versions'         Contains an array of php versions to ignore
     *                              when calculating the version needed.
     *  - 'ignore_functions_match'  Contains an array of function patterns to ignore
     *                              when calculating the version needed.
     *  - 'ignore_extensions_match' Contains an array of extension patterns to ignore
     *                              when calculating the version needed.
     *  - 'ignore_constants_match'  Contains an array of constant patterns to ignore
     *                              when calculating the version needed.
     *
     *  * parseArray, parseDir|parseFolder, specific options :
     *  - 'file_ext'                Contains an array of file extensions to parse
     *                              for PHP code. Default: php, php4, inc, phtml
     *  - 'ignore_files'            Contains an array of files to ignore.
     *                              File names are case insensitive.
     *
     *  * parseArray specific options :
     *  - 'is_string'               Contains a boolean which says if the array values
     *                              are strings or file names.
     *
     *  * parseDir|parseFolder specific options :
     *  - 'recurse_dir'             Boolean on whether to recursively find files
     *  - 'ignore_dirs'             Contains an array of directories to ignore.
     *                              Directory names are case insensitive.
     *
     * @param mixed $data    Data source (may be file, dir, string, or array)
     * @param array $options An array of options. See above.
     *
     * @access public
     * @return array or false on error
     * @since  version 1.8.0b2 (2008-06-03)
     * @see    PHP_CompatInfo_Parser::parseData()
     */
    function parseData($data, $options = array())
    {
        return $this->parser->parseData($data, $options);
    }

    /**
     * Parse an Array of Files or Strings
     *
     * You can parse an array of Files or Strings, to parse
     * strings, $options['is_string'] must be set to true.
     *
     * This recommandation is no more valid since version 1.8.0b2
     * Array my contains multiple and mixed origin (file, dir, string).
     *
     * @param array $array   Array of data sources
     * @param array $options Parser options (see parseData() method for details)
     *
     * @access public
     * @return array or false on error
     * @since  version 0.7.0 (2004-03-09)
     * @see    parseData()
     */
    function parseArray($array, $options = array())
    {
        return $this->parser->parseData($array, $options);
    }

    /**
     * Parse a string
     *
     * Parse a string for its compatibility info
     *
     * @param string $string  PHP Code to parse
     * @param array  $options Parser options (see parseData() method for details)
     *
     * @access public
     * @return array or false on error
     * @since  version 0.7.0 (2004-03-09)
     * @see    parseData()
     */
    function parseString($string, $options = array())
    {
        return $this->parser->parseData($string, $options);
    }

    /**
     * Parse a single file
     *
     * Parse a file for its compatibility info
     *
     * @param string $file    Path of File to parse
     * @param array  $options Parser options (see parseData() method for details)
     *
     * @access public
     * @return array or false on error
     * @since  version 0.7.0 (2004-03-09)
     * @see    parseData()
     */
    function parseFile($file, $options = array())
    {
        return $this->parser->parseData($file, $options);
    }

    /**
     * Parse a directory
     *
     * Parse a directory recursively for its compatibility info
     *
     * @param string $dir     Path of folder to parse
     * @param array  $options Parser options (see parseData() method for details)
     *
     * @access public
     * @return array or false on error
     * @since  version 0.8.0 (2004-04-22)
     * @see    parseData()
     */
    function parseDir($dir, $options = array())
    {
        return $this->parser->parseData($dir, $options);
    }

    /**
     * Alias of parseDir
     *
     * Alias of parseDir function
     *
     * @param string $folder  Path of folder to parse
     * @param array  $options Parser options (see parseData() method for details)
     *
     * @access public
     * @return array or false on error
     * @since  version 0.7.0 (2004-03-09)
     * @see    parseDir(), parseData()
     */
    function parseFolder($folder, $options = array())
    {
        return $this->parser->parseData($folder, $options);
    }

    /**
     * Returns list of files ignored
     *
     * Returns list of files ignored while parsing directories
     *
     * @access public
     * @return array or false on error
     * @since  version 1.9.0b2 (2008-12-19)
     */
    function getIgnoredFiles()
    {
        return $this->parser->getIgnoredFiles();
    }

    /**
     * Returns the latest parse data source ignored functions
     *
     * Returns the latest parse data source ignored functions list
     *
     * @param mixed $file (optional) A specific filename or not (false)
     *
     * @access public
     * @return mixed Null on error or if there were no previous data parsing
     * @since  version 1.9.0b2 (2008-12-19)
     */
    function getIgnoredFunctions($file = false)
    {
        return $this->parser->getIgnoredFunctions($file);
    }

    /**
     * Returns the latest parse data source ignored extensions
     *
     * Returns the latest parse data source ignored extensions list
     *
     * @param mixed $file (optional) A specific filename or not (false)
     *
     * @access public
     * @return mixed Null on error or if there were no previous data parsing
     * @since  version 1.9.0b2 (2008-12-19)
     */
    function getIgnoredExtensions($file = false)
    {
        return $this->parser->getIgnoredExtensions($file);
    }

    /**
     * Returns the latest parse data source ignored constants
     *
     * Returns the latest parse data source ignored constants list
     *
     * @param mixed $file (optional) A specific filename or not (false)
     *
     * @access public
     * @return mixed Null on error or if there were no previous data parsing
     * @since  version 1.9.0b2 (2008-12-19)
     */
    function getIgnoredConstants($file = false)
    {
        return $this->parser->getIgnoredConstants($file);
    }

    /**
     * Returns the latest parse data source version
     *
     * Returns the latest parse data source version, minimum and/or maximum
     *
     * @param mixed $file (optional) A specific filename or not (false)
     * @param bool  $max  (optional) Level with or without contextual data
     *
     * @access public
     * @return mixed Null on error or if there were no previous data parsing
     * @since  version 1.9.0b1 (2008-11-30)
     */
    function getVersion($file = false, $max = false)
    {
        return $this->parser->getVersion($file, $max);
    }

    /**
     * Returns the latest parse data source classes declared
     *
     * Returns the latest parse data source classes declared (internal or
     * end-user defined)
     *
     * @param mixed $file (optional) A specific filename or not (false)
     *
     * @access public
     * @return mixed Null on error or if there were no previous data parsing
     * @since  version 1.9.0b1 (2008-11-30)
     */
    function getClasses($file = false)
    {
        return $this->parser->getClasses($file);
    }

    /**
     * Returns the latest parse data source functions declared
     *
     * Returns the latest parse data source functions declared (internal or
     * end-user defined)
     *
     * @param mixed $file (optional) A specific filename or not (false)
     *
     * @access public
     * @return mixed Null on error or if there were no previous data parsing
     * @since  version 1.9.0b1 (2008-11-30)
     */
    function getFunctions($file = false)
    {
        return $this->parser->getFunctions($file);
    }

    /**
     * Returns the latest parse data source extensions used
     *
     * Returns the latest parse data source extensions used
     *
     * @param mixed $file (optional) A specific filename or not (false)
     *
     * @access public
     * @return mixed Null on error or if there were no previous data parsing
     * @since  version 1.9.0b1 (2008-11-30)
     */
    function getExtensions($file = false)
    {
        return $this->parser->getExtensions($file);
    }

    /**
     * Returns the latest parse data source constants declared
     *
     * Returns the latest parse data source constants declared (internal or
     * end-user defined)
     *
     * @param mixed $file (optional) A specific filename or not (false)
     *
     * @access public
     * @return mixed Null on error or if there were no previous data parsing
     * @since  version 1.9.0b1 (2008-11-30)
     */
    function getConstants($file = false)
    {
        return $this->parser->getConstants($file);
    }

    /**
     * Returns the latest parse data source tokens declared
     *
     * Returns the latest parse data source PHP5+ tokens declared
     *
     * @param mixed $file (optional) A specific filename or not (false)
     *
     * @access public
     * @return mixed Null on error or if there were no previous data parsing
     * @since  version 1.9.0b1 (2008-11-30)
     */
    function getTokens($file = false)
    {
        return $this->parser->getTokens($file);
    }

    /**
     * Returns the latest parse data source conditions
     *
     * Returns the latest parse data source conditions, with or without
     * contextual data
     *
     * @param mixed $file      (optional) A specific filename or not (false)
     * @param bool  $levelOnly (optional) Level with or without contextual data
     *
     * @access public
     * @return mixed Null on error or if there were no previous data parsing
     * @since  version 1.9.0b1 (2008-11-30)
     */
    function getConditions($file = false, $levelOnly = false)
    {
        return $this->parser->getConditions($file, $levelOnly);
    }

    /**
     * Returns the summary of parsing info
     *
     * Returns only summary when parsing a directory or multiple data sources
     *
     * @access public
     * @return array
     * @since  version 1.9.0 (2009-01-19)
     */
    function getSummary()
    {
        $summary = array('ignored_files'      => $this->getIgnoredFiles(),
                         'ignored_functions'  => $this->getIgnoredFunctions(),
                         'ignored_extensions' => $this->getIgnoredExtensions(),
                         'ignored_constants'  => $this->getIgnoredConstants(),
                         'max_version'        => $this->getVersion(false, true),
                         'version'            => $this->getVersion(),
                         'classes'            => $this->getClasses(),
                         'functions'          => $this->getFunctions(),
                         'extensions'         => $this->getExtensions(),
                         'constants'          => $this->getConstants(),
                         'tokens'             => $this->getTokens(),
                         'cond_code'          => $this->getConditions()
                         );
        if ($this->parser->options['debug'] == false) {
            unset($summary['functions']);
        }
        return $summary;
    }
}
?>