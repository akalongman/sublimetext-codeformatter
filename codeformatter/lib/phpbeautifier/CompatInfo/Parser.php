<?php
/**
 * Copyright (c) 2008-2009, Davey Shafik <davey@php.net>
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
 * @version  CVS: $Id: Parser.php,v 1.21 2009/01/02 10:18:47 farell Exp $
 * @link     http://pear.php.net/package/PHP_CompatInfo
 * @since    File available since Release 1.8.0b2
 */

require_once 'Event/Dispatcher.php';
require_once 'File/Find.php';

/**
 * An array of class init versions and extension
 */
require_once 'CompatInfo/class_array.php';

/**
 * An array of function init versions and extension
 */
require_once 'CompatInfo/func_array.php';

/**
 * An array of constants and their init versions
 */
require_once 'CompatInfo/const_array.php';

/**
 * An abstract base class for CompatInfo renderers
 */
require_once 'CompatInfo/Renderer.php';

/**
 * Event name of parsing data source start process
 */
define('PHP_COMPATINFO_EVENT_AUDITSTARTED', 'auditStarted');
/**
 * Event name of parsing data source end process
 */
define('PHP_COMPATINFO_EVENT_AUDITFINISHED', 'auditFinished');
/**
 * Event name of parsing a file start process
 */
define('PHP_COMPATINFO_EVENT_FILESTARTED', 'fileStarted');
/**
 * Event name of parsing a file end process
 */
define('PHP_COMPATINFO_EVENT_FILEFINISHED', 'fileFinished');
/**
 * Event name of parsing a file start process
 */
define('PHP_COMPATINFO_EVENT_CODESTARTED', 'codeStarted');
/**
 * Event name of parsing a file end process
 */
define('PHP_COMPATINFO_EVENT_CODEFINISHED', 'codeFinished');

/**
 * Parser logic
 *
 * This class is the model in the MVC design pattern of API 1.8.0 (since beta 2)
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version  Release: 1.9.0
 * @link     http://pear.php.net/package/PHP_CompatInfo
 * @since    Class available since Release 1.8.0b2
 */
class PHP_CompatInfo_Parser
{
    /**
     * Instance of concrete renderer used to show parse results
     *
     * @var    object
     * @since  1.8.0b2
     * @access protected
     */
    var $renderer;

    /**
     * Stores the event dispatcher which handles notifications
     *
     * @var    object
     * @since  1.8.0b2
     * @access protected
     */
    var $dispatcher;

    /**
     * Count the number of observer registered.
     * The Event_Dispatcher will be add on first observer registration, and
     * will be removed with the last observer.
     *
     * @var    integer
     * @since  1.8.0b2
     * @access private
     */
    var $_observerCount;

    /**
     * @var string Earliest version of PHP to use
     * @since  0.7.0
     */
    var $latest_version = '4.0.0';

    /**
     * @var string Last version of PHP to use
     */
    var $earliest_version = '';

    /**
     * @var array Parsing options
     */
    var $options;

    /**
     * @var array Data Source
     * @since  1.8.0b2
     */
    var $dataSource;

    /**
     * @var array Directory list found when parsing data source
     * @since  1.8.0b2
     * @see    getDirlist()
     */
    var $directories;

    /**
     * @var array List of files ignored when parsing data source
     * @since  1.8.0b2
     * @see    getIgnoredFiles()
     */
    var $ignored_files = array();

    /**
     * @var array Result of the latest data source parsing
     * @since  1.9.0b1
     * @see    parseData()
     */
    var $latest_parse = null;

    /**
     * Class constructor (ZE1) for PHP4
     *
     * @access public
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function PHP_CompatInfo_Parser()
    {
        $this->__construct();
    }

    /**
     * Class constructor (ZE2) for PHP5+
     *
     * @access public
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function __construct()
    {
        $this->options = array(
            'file_ext' => array('php', 'php4', 'inc', 'phtml'),
            'recurse_dir' => true,
            'debug' => false,
            'is_string' => false,
            'ignore_files' => array(),
            'ignore_dirs' => array()
            );
    }

    /**
     * Set up driver to be used
     *
     * Set up driver to be used, dependant on specified type.
     *
     * @param string $type Name the type of driver (html, text...)
     * @param array  $conf A hash containing any additional configuration
     *
     * @access public
     * @return void
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function setOutputDriver($type, $conf = array())
    {
        $this->renderer =& PHP_CompatInfo_Renderer::factory($this, $type, $conf);
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
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function addListener($callback, $nName = EVENT_DISPATCHER_GLOBAL)
    {
        $this->dispatcher =& Event_Dispatcher::getInstance();
        // $this->dispatcher->setNotificationClass('PHP_CompatInfo_Audit');
        $this->dispatcher->addObserver($callback, $nName);
        $this->_observerCount++;
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
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function removeListener($callback, $nName = EVENT_DISPATCHER_GLOBAL)
    {
        $result = $this->dispatcher->removeObserver($callback, $nName);

        if ($result) {
            $this->_observerCount--;
            if ($this->_observerCount == 0) {
                unset($this->dispatcher);
            }
        }
        return $result;
    }

    /**
     * Post a new notification to all listeners registered.
     *
     * This notification occured only if a dispatcher exists. That means if
     * at least one listener was registered.
     *
     * @param string $event Name of the notification handler
     * @param array  $info  (optional) Additional information about the notification
     *
     * @access public
     * @return void
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function notifyListeners($event, $info = array())
    {
        if (isset($this->dispatcher)) {
            $this->dispatcher->post($this, $event, $info);
        }
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
        $keys = array();
        foreach ($GLOBALS['_PHP_COMPATINFO_FUNCS'] as $func => $arr) {
            if (isset($arr['pecl']) && $arr['pecl'] === true) {
                continue;
            }
            $vmin = $arr['init'];
            if (version_compare($vmin, $min) < 0) {
                continue;
            }
            if ($max) {
                $end = (isset($arr['end'])) ? $arr['end'] : $vmin;

                if (version_compare($end, $max) < 1) {
                    if ($groupby_vers === true) {
                        $keys[$vmin][] = $func;
                    } else {
                        $keys[] = $func;
                    }
                }
            } else {
                if ($groupby_vers === true) {
                    $keys[$vmin][] = $func;
                } else {
                    $keys[] = $func;
                }
            }
        }
        if ($groupby_vers === true) {
            foreach ($keys as $vmin => $func) {
                sort($keys[$vmin]);
            }
            ksort($keys);
        } else {
            sort($keys);
        }

        if ($include_const === true) {
            $keys = array('functions' => $keys, 'constants' => array());
            foreach ($GLOBALS['_PHP_COMPATINFO_CONST'] as $const => $arr) {
                $vmin = $arr['init'];
                if (version_compare($vmin, $min) < 0) {
                    continue;
                }
                if ($max) {
                    $end = (isset($arr['end'])) ? $arr['end'] : $vmin;

                    if (version_compare($end, $max) < 1) {
                        if ($groupby_vers === true) {
                            $keys['constants'][$vmin][] = $arr['name'];
                        } else {
                            $keys['constants'][] = $arr['name'];
                        }
                    }
                } else {
                    if ($groupby_vers === true) {
                        $keys['constants'][$vmin][] = $arr['name'];
                    } else {
                        $keys['constants'][] = $arr['name'];
                    }
                }
            }
            ksort($keys['constants']);
        }
        return $keys;
    }

    /**
     * Returns list of directory parsed
     *
     * Returns list of directory parsed, depending of restrictive parser options.
     *
     * @param mixed $dir     The directory name
     * @param array $options An array of parser options. See parseData() method.
     *
     * @access public
     * @return array   list of directories that should be parsed
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function getDirlist($dir, $options)
    {
        if (!isset($this->directories)) {
            $this->getFilelist($dir, $options);
        }

        return $this->directories;
    }

    /**
     * Returns list of files parsed
     *
     * Returns list of files parsed, depending of restrictive parser options.
     *
     * @param mixed $dir     The directory name where to look files
     * @param array $options An array of parser options. See parseData() method.
     *
     * @access public
     * @return array   list of files that should be parsed
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function getFilelist($dir, $options)
    {
        $skipped = array();
        $ignored = array();

        $options             = array_merge($this->options, $options);
        $options['file_ext'] = array_map('strtolower', $options['file_ext']);

        if ($dir{strlen($dir)-1} == '/' || $dir{strlen($dir)-1} == '\\') {
            $dir = substr($dir, 0, -1);
        }

        // use system directory separator rather than forward slash by default
        $ff         = new File_Find();
        $ff->dirsep = DIRECTORY_SEPARATOR;

        // get directory list that should be ignored from scope
        $ignore_dirs = array();
        if (count($options['ignore_dirs']) > 0) {
            foreach ($options['ignore_dirs'] as $cond) {
                $cond        = str_replace('\\', "\\\\", $cond);
                $dirs        = $ff->search('`'.$cond.'`', $dir, 'perl',
                                           true, 'directories');
                $ignore_dirs = array_merge($ignore_dirs, $dirs);
            }
        }

        // get file list that should be ignored from scope
        $ignore_files = array();
        if (count($options['ignore_files']) > 0) {
            foreach ($options['ignore_files'] as $cond) {
                $cond         = str_replace('\\', "\\\\", $cond);
                $files        = $ff->search('`'.$cond.'`', $dir, 'perl',
                                            true, 'files');
                $ignore_files = array_merge($ignore_files, $files);
            }
        }

        list($directories, $files) = $ff->maptree($dir);

        foreach ($files as $file) {

            $file_info = pathinfo($file);
            if ($options['recurse_dir'] == false
                && $file_info['dirname'] != $dir) {
                $skipped[] = $file;
                continue;
            }
            if (in_array($file_info['dirname'], $ignore_dirs)) {
                $ignored[] = $file;

            } elseif (in_array($file, $ignore_files)) {
                $ignored[] = $file;

            } else {
                if (isset($file_info['extension'])
                    && in_array(strtolower($file_info['extension']),
                                $options['file_ext'])) {
                    continue;
                }
                $ignored[] = $file;
            }
        }

        $files = PHP_CompatInfo_Parser::_arrayDiff($files,
                                                   array_merge($ignored, $skipped));
        $this->directories
               = PHP_CompatInfo_Parser::_arrayDiff($directories, $ignore_dirs);
        $this->ignored_files
               = $ignored;

        return $files;
    }

    /**
     * Returns list of files ignored
     *
     * Returns list of files ignored while parsing directories
     *
     * @access public
     * @return array or false on error
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function getIgnoredFiles()
    {
        return $this->ignored_files;
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
        if (!is_array($this->latest_parse)) {
            // no code analysis found
            $functions = null;
        } elseif ($file === false) {
            $functions = $this->latest_parse['ignored_functions'];
        } elseif (isset($this->latest_parse[$file])) {
            $functions = $this->latest_parse[$file]['ignored_functions'];
        } else {
            $functions = null;
        }

        return $functions;
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
        if (!is_array($this->latest_parse)) {
            // no code analysis found
            $extensions = null;
        } elseif ($file === false) {
            $extensions = $this->latest_parse['ignored_extensions'];
        } elseif (isset($this->latest_parse[$file])) {
            $extensions = $this->latest_parse[$file]['ignored_extensions'];
        } else {
            $extensions = null;
        }

        return $extensions;
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
        if (!is_array($this->latest_parse)) {
            // no code analysis found
            $constants = null;
        } elseif ($file === false) {
            $constants = $this->latest_parse['ignored_constants'];
        } elseif (isset($this->latest_parse[$file])) {
            $constants = $this->latest_parse[$file]['ignored_constants'];
        } else {
            $constants = null;
        }

        return $constants;
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
        $key = ($max === true) ? 'max_version' : 'version';

        if (!is_array($this->latest_parse)) {
            // no code analysis found
            $version = null;
        } elseif ($file === false) {
            $version = $this->latest_parse[$key];
        } elseif (isset($this->latest_parse[$file])) {
            $version = $this->latest_parse[$file][$key];
        } else {
            $version = null;
        }

        return $version;
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
        if (!is_array($this->latest_parse)) {
            // no code analysis found
            $classes = null;
        } elseif ($file === false) {
            $classes = $this->latest_parse['classes'];
        } elseif (isset($this->latest_parse[$file])) {
            $classes = $this->latest_parse[$file]['classes'];
        } else {
            $classes = null;
        }

        return $classes;
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
        if (!is_array($this->latest_parse)) {
            // no code analysis found
            $functions = null;
        } elseif ($file === false) {
            $functions = $this->latest_parse['functions'];
        } elseif (isset($this->latest_parse[$file])) {
            $functions = $this->latest_parse[$file]['functions'];
        } else {
            $functions = null;
        }

        return $functions;
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
        if (!is_array($this->latest_parse)) {
            // no code analysis found
            $extensions = null;
        } elseif ($file === false) {
            $extensions = $this->latest_parse['extensions'];
        } elseif (isset($this->latest_parse[$file])) {
            $extensions = $this->latest_parse[$file]['extensions'];
        } else {
            $extensions = null;
        }

        return $extensions;
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
        if (!is_array($this->latest_parse)) {
            // no code analysis found
            $constants = null;
        } elseif ($file === false) {
            $constants = $this->latest_parse['constants'];
        } elseif (isset($this->latest_parse[$file])) {
            $constants = $this->latest_parse[$file]['constants'];
        } else {
            $constants = null;
        }

        return $constants;
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
        if (!is_array($this->latest_parse)) {
            // no code analysis found
        } elseif ($file === false) {
            $tokens = $this->latest_parse['tokens'];
        } elseif (isset($this->latest_parse[$file])) {
            $tokens = $this->latest_parse[$file]['tokens'];
        } else {
            $tokens = null;
        }

        return $tokens;
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
        if (!is_array($this->latest_parse)) {
            // no code analysis found
            $conditions = null;
        } elseif ($file === false) {
            $conditions = $this->latest_parse['cond_code'];
        } elseif (isset($this->latest_parse[$file])) {
            $conditions = $this->latest_parse[$file]['cond_code'];
        } else {
            $conditions = null;
        }

        if (is_array($conditions) && $levelOnly === true) {
            $conditions = $conditions[0];
        }
        return $conditions;
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
     * @param mixed $dataSource The data source (may be file, dir, string, or array)
     * @param array $options    An array of options. See above.
     *
     * @access public
     * @return array or false on error
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function parseData($dataSource, $options = array())
    {
        $this->options = array_merge($this->options, $options);

        $dataType  = gettype($dataSource);
        $dataCount = 0;
        // - when array source with mixed content incompatible
        // - if all directories are not readable
        // - if data source invalid type: other than file, directory, string

        if ($dataType == 'string' || $dataType == 'array') {
            if (is_array($dataSource)) {
                //$dataType = 'array';
            } elseif (is_dir($dataSource)) {
                $dataType   = 'directory';
                $dataSource = array($dataSource);
            } elseif (is_file($dataSource)) {
                $dataType   = 'file';
                $dataSource = array($dataSource);
            } elseif (substr($dataSource, 0, 5) == '<?php') {
                //$dataType = 'string';
                $this->options = array_merge($this->options,
                                             array('is_string' => true));
                $dataSource    = array($dataSource);
            } else {
                //$dataType = 'string';
                // directory or file are misspelled
            }
            if (is_array($dataSource)) {
                $dataSource = $this->_validateDataSource($dataSource,
                                                         $this->options);
                $dataCount  = count($dataSource);
            }
        }

        $this->dataSource = array('dataSource' => $dataSource,
                                  'dataType' => $dataType,
                                  'dataCount' => $dataCount);

        $eventInfo = array_merge($this->dataSource,
                                 array('parseOptions' => $this->options));

        // notify all observers that parsing data source begin
        $this->notifyListeners(PHP_COMPATINFO_EVENT_AUDITSTARTED, $eventInfo);

        if ($dataCount == 0) {
            $parseData = false;
        } else {
            switch ($dataType) {
            case 'array' :
                $parseData = $this->_parseArray($dataSource, $this->options);
                break;
            case 'string' :
                $parseData = $this->_parseString($dataSource, $this->options);
                break;
            case 'file' :
                $parseData = $this->_parseFile($dataSource, $this->options);
                break;
            case 'directory' :
                $parseData = $this->_parseDir($dataSource, $this->options);
                break;
            }
        }

        // notify all observers that parsing data source is over
        $this->notifyListeners(PHP_COMPATINFO_EVENT_AUDITFINISHED, $parseData);

        $this->latest_parse = $parseData;
        return $parseData;
    }

    /**
     * Validate content of data source
     *
     * Validate content of data source list, before parsing each source
     *
     * @param mixed $dataSource The data source (may be file, dir, or string)
     * @param array $options    Parser options (see parseData() method for details)
     *
     * @access private
     * @return array   empty array on error
     * @since  version 1.8.0b3 (2008-06-07)
     */
    function _validateDataSource($dataSource, $options = array())
    {
        /**
         * Array by default expect to contains list of files and/or directories.
         * If you want a list of chunk of code (strings), 'is_string' option
         * must be set to true.
         */
        $list = array();

        foreach ($dataSource as $source) {
            if ($options['is_string'] === true) {
                if (is_string($source)) {
                    $list[] = $source;
                } else {
                    /**
                     * One of items is not a string (chunk of code). All
                     * data sources parsing are stopped and considered as invalid.
                     */
                    $list = array();
                    break;
                }
            } else {
                if (is_dir($source) && is_readable($source)) {
                    $files = $this->getFilelist($source, $options);
                    $list  = array_merge($list, $files);
                } elseif (is_file($source)) {
                    $list[] = $source;
                } else {
                    /**
                     * One of items is not a valid file or directory. All
                     * data sources parsing are stopped and considered as invalid.
                     */
                    $list = array();
                    break;
                }
            }
        }

        return $list;
    }

    /**
     * Parse an Array of Files
     *
     * You can parse an array of Files or Strings, to parse
     * strings, $options['is_string'] must be set to true
     *
     * @param array $dataSource Array of file &| directory names or code strings
     * @param array $options    Parser options (see parseData() method for details)
     *
     * @access private
     * @return array or false on error
     * @since  version 0.7.0 (2004-03-09)
     * @see    parseData()
     */
    function _parseArray($dataSource, $options = array())
    {
        // Each data source have been checked before (see _validateDataSource() )
        if (is_file($dataSource[0])) {
            $parseData = $this->_parseDir($dataSource, $options);
        } else {
            $parseData = $this->_parseString($dataSource, $options);
        }

        return $parseData;
    }

    /**
     * Parse a string
     *
     * Parse a string for its compatibility info.
     *
     * @param array $strings PHP Code to parse
     * @param array $options Parser options (see parseData() method for details)
     *
     * @access private
     * @return array or false on error
     * @since  version 0.7.0 (2004-03-09)
     * @see    parseData()
     */
    function _parseString($strings, $options = array())
    {
        $results = $this->_parseElements($strings, $options);
        return $results;
    }

    /**
     * Parse a single file
     *
     * Parse a single file for its compatibility info.
     *
     * @param string $file    File to parse
     * @param array  $options Parser options (see parseData() method for details)
     *
     * @access private
     * @return array or false on error
     * @since  version 0.7.0 (2004-03-09)
     * @see    parseData()
     */
    function _parseFile($file, $options = array())
    {
        $results = $this->_parseElements($file, $options);
        return $results;
    }

    /**
     * Parse a directory
     *
     * Parse a directory recursively for its compatibility info
     *
     * @param array $files   Files list of folder to parse
     * @param array $options Parser options (see parseData() method for details)
     *
     * @access private
     * @return array or false on error
     * @since  version 0.8.0 (2004-04-22)
     * @see    parseData()
     */
    function _parseDir($files, $options = array())
    {
        $results = $this->_parseElements($files, $options);
        return $results;
    }

    /**
     * Parse a list of elements
     *
     * Parse a list of directory|file elements, or chunk of code (strings)
     *
     * @param array $elements Array of file &| directory names or code strings
     * @param array $options  Parser options (see parseData() method for details)
     *
     * @access private
     * @return array
     * @since  version 1.8.0b3 (2008-06-07)
     * @see    _parseString(), _parseDir()
     */
    function _parseElements($elements, $options = array())
    {
        $files_parsed       = array();
        $latest_version     = $this->latest_version;
        $earliest_version   = $this->earliest_version;
        $all_functions      = array();
        $classes            = array();
        $functions          = array();
        $extensions         = array();
        $constants          = array();
        $tokens             = array();
        $ignored_functions  = array();
        $ignored_extensions = array();
        $ignored_constants  = array();
        $function_exists    = array();
        $extension_loaded   = array();
        $defined            = array();
        $cond_code          = 0;

        foreach ($elements as $p => $element) {
            $index = $p + 1;
            if (is_file($element)) {
                if (in_array($element, $options['ignore_files'])) {
                    $this->ignored_files[] = $element;
                    continue;
                }
                $eventInfo
                    = array('filename' => $element, 'fileindex' => $index);
                $this->notifyListeners(PHP_COMPATINFO_EVENT_FILESTARTED, $eventInfo);

                $tokens_list          = $this->_tokenize($element);
                $kfile                = $element;
                $files_parsed[$kfile] = $this->_parseTokens($tokens_list, $options);

                $this->notifyListeners(PHP_COMPATINFO_EVENT_FILEFINISHED);
            } else {
                $eventInfo
                    = array('stringdata' => $element, 'stringindex' => $index);
                $this->notifyListeners(PHP_COMPATINFO_EVENT_CODESTARTED, $eventInfo);

                $tokens_list          = $this->_tokenize($element, true);
                $kfile                = 'string_' . $index;
                $files_parsed[$kfile] = $this->_parseTokens($tokens_list, $options);

                $this->notifyListeners(PHP_COMPATINFO_EVENT_CODEFINISHED);
            }
        }

        foreach ($files_parsed as $fn => $file) {
            $cmp = version_compare($latest_version, $file['version']);
            if ($cmp === -1) {
                $latest_version = $file['version'];
            }
            if ($file['max_version'] != '') {
                $cmp = version_compare($earliest_version, $file['max_version']);
                if ($earliest_version == '' || $cmp === 1) {
                    $earliest_version = $file['max_version'];
                }
            }
            foreach ($file['classes'] as $class) {
                if (!in_array($class, $classes)) {
                    $classes[] = $class;
                }
            }
            foreach ($file['functions'] as $func) {
                if (!in_array($func, $functions)) {
                    $functions[] = $func;
                }
            }
            foreach ($file['extensions'] as $ext) {
                if (!in_array($ext, $extensions)) {
                    $extensions[] = $ext;
                }
            }
            foreach ($file['constants'] as $const) {
                if (!in_array($const, $constants)) {
                    $constants[] = $const;
                }
            }
            foreach ($file['tokens'] as $token) {
                if (!in_array($token, $tokens)) {
                    $tokens[] = $token;
                }
            }
            foreach ($file['ignored_functions'] as $if) {
                if (!in_array($if, $ignored_functions)) {
                    $ignored_functions[] = $if;
                }
            }
            foreach ($file['ignored_extensions'] as $ie) {
                if (!in_array($ie, $ignored_extensions)) {
                    $ignored_extensions[] = $ie;
                }
            }
            foreach ($file['ignored_constants'] as $ic) {
                if (!in_array($ic, $ignored_constants)) {
                    $ignored_constants[] = $ic;
                }
            }
            foreach ($file['cond_code'][1][0] as $ccf) {
                if (!in_array($ccf, $function_exists)) {
                    $function_exists[] = $ccf;
                }
            }
            foreach ($file['cond_code'][1][1] as $cce) {
                if (!in_array($cce, $extension_loaded)) {
                    $extension_loaded[] = $cce;
                }
            }
            foreach ($file['cond_code'][1][2] as $ccc) {
                if (!in_array($ccc, $defined)) {
                    $defined[] = $ccc;
                }
            }
            if ($options['debug'] === false) {
                unset($files_parsed[$fn]['cond_code'][1]);
            } else {
                unset($file['ignored_functions']);
                unset($file['ignored_extensions']);
                unset($file['ignored_constants']);
                unset($file['max_version']);
                unset($file['version']);
                unset($file['classes']);
                unset($file['functions']);
                unset($file['extensions']);
                unset($file['constants']);
                unset($file['tokens']);
                unset($file['cond_code']);

                foreach ($file as $version => $file_functions) {
                    // extra information available only when debug mode is on
                    if (isset($all_functions[$version])) {
                        foreach ($file_functions as $func) {
                            $k = array_search($func, $all_functions[$version]);
                            if ($k === false) {
                                $all_functions[$version][] = $func;
                            }
                        }
                    } else {
                        $all_functions[$version] = $file_functions;
                    }
                }
            }
        }

        if (count($files_parsed) == 0) {
            return false;
        }

        if (count($function_exists) > 0) {
            $cond_code += 1;
        }
        if (count($extension_loaded) > 0) {
            $cond_code += 2;
        }
        if (count($defined) > 0) {
            $cond_code += 4;
        }
        if ($options['debug'] === false) {
            $cond_code = array($cond_code);
        } else {
            sort($function_exists);
            sort($extension_loaded);
            sort($defined);
            $cond_code = array($cond_code, array($function_exists,
                                                 $extension_loaded,
                                                 $defined));
        }

        sort($ignored_functions);
        sort($ignored_extensions);
        sort($ignored_constants);
        sort($classes);
        sort($functions);
        natcasesort($extensions);
        sort($constants);
        sort($tokens);
        $main_info = array('ignored_files'      => $this->getIgnoredFiles(),
                           'ignored_functions'  => $ignored_functions,
                           'ignored_extensions' => $ignored_extensions,
                           'ignored_constants'  => $ignored_constants,
                           'max_version'   => $earliest_version,
                           'version'       => $latest_version,
                           'classes'       => $classes,
                           'functions'     => $functions,
                           'extensions'    => array_values($extensions),
                           'constants'     => $constants,
                           'tokens'        => $tokens,
                           'cond_code'     => $cond_code);

        if (count($files_parsed) == 1) {
            if ($options['debug'] === false) {
                $parseData = $main_info;
            } else {
                $main_info = array('ignored_files' => $this->getIgnoredFiles());
                $parseData = array_merge($main_info,
                                         $files_parsed[$kfile], $all_functions);
            }
        } else {
            if ($options['debug'] === false) {
                $parseData = array_merge($main_info, $files_parsed);
            } else {
                $parseData = array_merge($main_info, $all_functions, $files_parsed);
            }
        }

        $this->notifyListeners(PHP_COMPATINFO_EVENT_FILEFINISHED, $parseData);
        return $parseData;
    }

    /**
     * Token a file or string
     *
     * @param string  $input     Filename or PHP code
     * @param boolean $is_string Whether or note the input is a string
     * @param boolean $debug     add token names for human read
     *
     * @access private
     * @return array
     * @since  version 0.7.0 (2004-03-09)
     */
    function _tokenize($input, $is_string = false, $debug = false)
    {
        if ($is_string === false) {
            $input = file_get_contents($input, true);
        }
        $tokens = token_get_all($input);

        if ($debug === true) {
            $r = array();
            foreach ($tokens as $token) {
                if (is_array($token)) {
                    $token[] = token_name($token[0]);
                } else {
                    $token = $token[0];
                }
                $r[] = $token;
            }
        } else {
            $r = $tokens;
        }
        return $r;
    }

    /**
     * Parse the given Tokens
     *
     * The tokens are those returned by token_get_all() which is nicely
     * wrapped in PHP_CompatInfo::_tokenize
     *
     * @param array   $tokens  Array of PHP Tokens
     * @param boolean $options Show Extra Output
     *
     * @access private
     * @return array
     * @since  version 0.7.0 (2004-03-09)
     */
    function _parseTokens($tokens, $options)
    {
        static $akeys;

        $classes            = array();
        $functions          = array();
        $functions_version  = array();
        $latest_version     = $this->latest_version;
        $earliest_version   = $this->earliest_version;
        $extensions         = array();
        $constants          = array();
        $constant_names     = array();
        $token_names        = array();
        $udf                = array();
        $ignore_functions   = array();
        $ignored_functions  = array();
        $ignore_extensions  = array();
        $ignored_extensions = array();
        $ignore_constants   = array();
        $ignored_constants  = array();
        $function_exists    = array();
        $extension_loaded   = array();
        $defined            = array();
        $cond_code          = 0;

        if (isset($options['ignore_constants'])) {
            $options['ignore_constants']
                = array_map('strtoupper', $options['ignore_constants']);
        } else {
            $options['ignore_constants'] = array();
        }
        if (isset($options['ignore_extensions'])) {
            $options['ignore_extensions']
                = array_map('strtolower', $options['ignore_extensions']);
        } else {
            $options['ignore_extensions'] = array();
        }
        if (isset($options['ignore_versions'][0])) {
            $min_ver = $options['ignore_versions'][0];
        } else {
            $min_ver = false;
        }
        if (isset($options['ignore_versions'][1])) {
            $max_ver = $options['ignore_versions'][1];
        } else {
            $max_ver = false;
        }

        if (isset($options['ignore_functions_match'])) {
            list($ifm_compare, $ifm_patterns) = $options['ignore_functions_match'];
        } else {
            $ifm_compare = false;
        }
        if (isset($options['ignore_extensions_match'])) {
            list($iem_compare, $iem_patterns) = $options['ignore_extensions_match'];
        } else {
            $iem_compare = false;
        }
        if (isset($options['ignore_constants_match'])) {
            list($icm_compare, $icm_patterns) = $options['ignore_constants_match'];
        } else {
            $icm_compare = false;
        }

        $token_count = sizeof($tokens);
        $i           = 0;
        $found_class = false;
        while ($i < $token_count) {
            if ($this->_isToken($tokens[$i], 'T_FUNCTION')) {
                $found_func = false;
            } else {
                $found_func = true;
            }
            while ($found_func == false) {
                $i += 1;
                if ($this->_isToken($tokens[$i], 'T_STRING')) {
                    $found_func = true;
                    $func       = $tokens[$i][1];
                    if ($found_class === false
                        || in_array($func, $function_exists)) {
                        $udf[] = $func;
                    }
                }
            }

            // Try to detect PHP method chaining implementation
            if ($this->_isToken($tokens[$i], 'T_VARIABLE')
                && $this->_isToken($tokens[$i+1], 'T_OBJECT_OPERATOR')
                && $this->_isToken($tokens[$i+2], 'T_STRING')
                && $this->_isToken($tokens[$i+3], '(')) {

                $i                   += 3;
                $php5_method_chaining = false;
                while (((!is_array($tokens[$i]) && $tokens[$i] == ';') === false)
                    && (!$this->_isToken($tokens[$i], 'T_CLOSE_TAG'))
                    ) {
                    $i += 1;
                    if ((($this->_isToken($tokens[$i], ')'))
                        || ($this->_isToken($tokens[$i], 'T_WHITESPACE')))
                        && $this->_isToken($tokens[$i+1], 'T_OBJECT_OPERATOR')) {

                        $php5_method_chaining = true;
                    }
                }
            }

            // Compare "ignore_functions_match" pre-condition
            if (is_string($ifm_compare)) {
                if (strcasecmp('preg_match', $ifm_compare) != 0) {
                    // Try to catch function_exists() condition
                    if ($this->_isToken($tokens[$i], 'T_STRING')
                        && (strcasecmp($tokens[$i][1], $ifm_compare) == 0)) {

                        while ((!$this->_isToken($tokens[$i],
                                                 'T_CONSTANT_ENCAPSED_STRING'))) {
                            $i += 1;
                        }
                        $func = trim($tokens[$i][1], "'");

                        /**
                         * try if function_exists()
                         * match one or more pattern condition
                         */
                        foreach ($ifm_patterns as $pattern) {
                            if (preg_match($pattern, $func) === 1) {
                                $ignore_functions[] = $func;
                            }
                        }
                    }
                }
            }

            // Compare "ignore_extensions_match" pre-condition
            if (is_string($iem_compare)) {
                if (strcasecmp('preg_match', $iem_compare) != 0) {
                    // Try to catch extension_loaded() condition
                    if ($this->_isToken($tokens[$i], 'T_STRING')
                        && (strcasecmp($tokens[$i][1], $iem_compare) == 0)) {

                        while ((!$this->_isToken($tokens[$i],
                                                 'T_CONSTANT_ENCAPSED_STRING'))) {
                            $i += 1;
                        }
                        $ext = trim($tokens[$i][1], "'");

                        /**
                         * try if extension_loaded()
                         * match one or more pattern condition
                         */
                        foreach ($iem_patterns as $pattern) {
                            if (preg_match($pattern, $ext) === 1) {
                                $ignore_extensions[] = $ext;
                            }
                        }
                    }
                }
            }

            // Compare "ignore_constants_match" pre-condition
            if (is_string($icm_compare)) {
                if (strcasecmp('preg_match', $icm_compare) != 0) {
                    // Try to catch defined() condition
                    if ($this->_isToken($tokens[$i], 'T_STRING')
                        && (strcasecmp($tokens[$i][1], $icm_compare) == 0)) {

                        while ((!$this->_isToken($tokens[$i],
                                                 'T_CONSTANT_ENCAPSED_STRING'))) {
                            $i += 1;
                        }
                        $cst = trim($tokens[$i][1], "'");

                        /**
                         * try if defined()
                         * match one or more pattern condition
                         */
                        foreach ($icm_patterns as $pattern) {
                            if (preg_match($pattern, $cst) === 1) {
                                $ignore_constants[] = $cst;
                            }
                        }
                    }
                }
            }

            // try to detect class instantiation
            if ($this->_isToken($tokens[$i], 'T_STRING')
                && (isset($tokens[$i-2]))
                && $this->_isToken($tokens[$i-2], 'T_NEW')) {

                $is_class  = true;
                $classes[] = $tokens[$i][1];
            } else {
                $is_class = false;
            }

            if ($this->_isToken($tokens[$i], 'T_STRING')
                && $is_class == false
                && (isset($tokens[$i+1]))
                && $this->_isToken($tokens[$i+1], '(')) {

                $is_function = false;

                if (isset($tokens[$i-1])
                    && !$this->_isToken($tokens[$i-1], 'T_DOUBLE_COLON')
                    && !$this->_isToken($tokens[$i-1], 'T_OBJECT_OPERATOR')) {

                    if (isset($tokens[$i-2])
                        && $this->_isToken($tokens[$i-2], 'T_FUNCTION')) {
                        // its a function declaration
                    } else {
                        $is_function = true;
                    }
                }
                if ($is_function == true || !is_array($tokens[$i-1])) {
                    $functions[] = strtolower($tokens[$i][1]);
                }
            }

            // try to detect condition function_exists()
            if ($this->_isToken($tokens[$i], 'T_STRING')
                && (strcasecmp($tokens[$i][1], 'function_exists') == 0)) {

                $j = $i;
                while ((!$this->_isToken($tokens[$j], ')'))) {
                    if ($this->_isToken($tokens[$j], 'T_CONSTANT_ENCAPSED_STRING')) {
                        $t_string          = $tokens[$j][1];
                        $t_string          = trim($t_string, "'");
                        $t_string          = trim($t_string, '"');
                        $function_exists[] = $t_string;
                    }
                    $j++;
                }
            }
            // try to detect condition extension_loaded()
            if ($this->_isToken($tokens[$i], 'T_STRING')
                && (strcasecmp($tokens[$i][1], 'extension_loaded') == 0)) {

                $j = $i;
                while ((!$this->_isToken($tokens[$j], ')'))) {
                    if ($this->_isToken($tokens[$j], 'T_CONSTANT_ENCAPSED_STRING')) {
                        $t_string           = $tokens[$j][1];
                        $t_string           = trim($t_string, "'");
                        $t_string           = trim($t_string, '"');
                        $extension_loaded[] = $t_string;
                    }
                    $j++;
                }
            }
            // try to detect condition defined()
            if ($this->_isToken($tokens[$i], 'T_STRING')
                && (strcasecmp($tokens[$i][1], 'defined') == 0)) {

                $j = $i;
                while ((!$this->_isToken($tokens[$j], ')'))) {
                    if ($this->_isToken($tokens[$j], 'T_CONSTANT_ENCAPSED_STRING')) {
                        $t_string  = $tokens[$j][1];
                        $t_string  = trim($t_string, "'");
                        $t_string  = trim($t_string, '"');
                        $defined[] = $t_string;
                    }
                    $j++;
                }
            }

            // try to detect beginning of a class
            if ($this->_isToken($tokens[$i], 'T_CLASS')) {
                $found_class = true;
            }

            if (is_array($tokens[$i])) {
                if (!isset($akeys)) {
                    // build contents one time only (static variable)
                    $akeys = array_keys($GLOBALS['_PHP_COMPATINFO_CONST']);
                }
                $const = strtoupper($tokens[$i][1]);
                $found = array_search($const, $akeys);
                if ($found !== false) {
                    if ($this->_isToken($tokens[$i], 'T_ENCAPSED_AND_WHITESPACE')) {
                        // PHP 5 constant tokens found into a string
                    } else {
                        // Compare "ignore_constants_match" free condition
                        $icm_preg_match = false;
                        if (is_string($icm_compare)) {
                            if (strcasecmp('preg_match', $icm_compare) == 0) {
                                /**
                                 * try if preg_match()
                                 * match one or more pattern condition
                                 */
                                foreach ($icm_patterns as $pattern) {
                                    if (preg_match($pattern, $const) === 1) {
                                        $icm_preg_match = true;
                                        break;
                                    }
                                }
                            }
                        }

                        $init = $GLOBALS['_PHP_COMPATINFO_CONST'][$const]['init'];
                        if (!PHP_CompatInfo_Parser::_ignore($init,
                                                            $min_ver, $max_ver)) {
                            $constants[] = $const;
                            if (in_array($const, $ignore_constants)
                                || in_array($const, $options['ignore_constants'])
                                || $icm_preg_match) {
                                $ignored_constants[] = $const;
                            } else {
                                $latest_version = $init;
                            }
                        }
                    }
                }
            }
            $i += 1;
        }

        $classes   = array_unique($classes);
        $functions = array_unique($functions);
        if (isset($options['ignore_functions'])) {
            $options['ignore_functions']
                = array_map('strtolower', $options['ignore_functions']);
        } else {
            $options['ignore_functions'] = array();
        }
        if (count($ignore_functions) > 0) {
            $ignore_functions = array_map('strtolower', $ignore_functions);
            $options['ignore_functions']
                = array_merge($options['ignore_functions'], $ignore_functions);
            $options['ignore_functions']
                = array_unique($options['ignore_functions']);
        }
        if (count($ignore_extensions) > 0) {
            $options['ignore_extensions']
                = array_merge($options['ignore_extensions'], $ignore_extensions);
            $options['ignore_extensions']
                = array_unique($options['ignore_extensions']);
        }

        foreach ($classes as $name) {
            if (!isset($GLOBALS['_PHP_COMPATINFO_CLASS'][$name])) {
                continue;  // skip this unknown class
            }
            $class = $GLOBALS['_PHP_COMPATINFO_CLASS'][$name];
            if (PHP_CompatInfo_Parser::_ignore($class['init'], $min_ver, $max_ver)) {
                continue;  // skip this class version
            }

            $cmp = version_compare($latest_version, $class['init']);
            if ($cmp === -1) {
                $latest_version = $class['init'];
            }
            if (array_key_exists('end', $class)) {
                $cmp = version_compare($earliest_version, $class['end']);
                if ($earliest_version == '' || $cmp === 1) {
                    $earliest_version = $class['end'];
                }
            }

            if (array_key_exists('ext', $class)) {
                // this class depends of an extension
                $extensions[] = $class['ext'];
            }
        }

        foreach ($functions as $name) {
            if (!isset($GLOBALS['_PHP_COMPATINFO_FUNCS'][$name])) {
                continue;  // skip this unknown function
            }
            $func = $GLOBALS['_PHP_COMPATINFO_FUNCS'][$name];

            // retrieve if available the extension name
            if ((isset($func['ext']))
                && ($func['ext'] != 'standard')
                && ($func['ext'] != 'zend')) {
                $extension = $func['ext'];
            } else {
                $extension = false;
            }

            // Compare "ignore_functions_match" free condition
            $ifm_preg_match = false;
            if (is_string($ifm_compare)) {
                if (strcasecmp('preg_match', $ifm_compare) == 0) {
                    /**
                     * try if preg_match()
                     * match one or more pattern condition
                     */
                    foreach ($ifm_patterns as $pattern) {
                        if (preg_match($pattern, $name) === 1) {
                            $ifm_preg_match = true;
                            break;
                        }
                    }
                }
            }

            if ((!in_array($name, $udf))
                && (!in_array($name, $options['ignore_functions']))
                && ($ifm_preg_match === false)) {

                if ($extension && !in_array($extension, $extensions)) {
                    $extensions[] = $extension;
                }

                // Compare "ignore_extensions_match" free condition
                $iem_preg_match = false;
                if (is_string($iem_compare)) {
                    if (strcasecmp('preg_match', $iem_compare) == 0) {
                        /**
                         * try if preg_match()
                         * match one or more pattern condition
                         */
                        foreach ($iem_patterns as $pattern) {
                            if (preg_match($pattern, $extension) === 1) {
                                $iem_preg_match = true;
                                break;
                            }
                        }
                    }
                }

                if ($extension
                    && (in_array($extension, $options['ignore_extensions'])
                        || $iem_preg_match)) {
                    if (!in_array($extension, $ignored_extensions)) {
                        // extension is ignored (only once)
                        $ignored_extensions[] = $extension;
                    }
                    // all extension functions are also ignored
                    $ignored_functions[] = $name;
                    continue;  // skip this extension function
                }

                if (PHP_CompatInfo_Parser::_ignore($func['init'],
                                                   $min_ver, $max_ver)) {
                    continue;  // skip this function version
                }

                if ($options['debug'] == true) {
                    $functions_version[$func['init']][] = array(
                        'function' => $name,
                        'extension' => $extension,
                        'pecl' => $func['pecl']
                        );
                }
                if ($extension === false
                    || (isset($func['pecl']) && $func['pecl'] === false) ) {
                    $cmp = version_compare($latest_version, $func['init']);
                    if ($cmp === -1) {
                        $latest_version = $func['init'];
                    }
                    if (array_key_exists('end', $func)) {
                        $cmp = version_compare($earliest_version, $func['end']);
                        if ($earliest_version == '' || $cmp === 1) {
                            $earliest_version = $func['end'];
                        }
                    }
                }

            } else {
                // function is ignored
                $ignored_functions[] = $name;
            }
        }

        $ignored_constants = array_unique($ignored_constants);
        $constants         = array_unique($constants);
        foreach ($constants as $constant) {
            $const = $GLOBALS['_PHP_COMPATINFO_CONST'][$constant];
            if (PHP_CompatInfo_Parser::_ignore($const['init'], $min_ver, $max_ver)) {
                continue;  // skip this constant version
            }
            if (!in_array($constant, $ignored_constants)) {
                $cmp = version_compare($latest_version, $const['init']);
                if ($cmp === -1) {
                    $latest_version = $const['init'];
                }
                if (array_key_exists('end', $const)) {
                    $cmp = version_compare($earliest_version, $const['end']);
                    if ($earliest_version == '' || $cmp === 1) {
                        $earliest_version = $const['end'];
                    }
                }
            }
            if (!in_array($const['name'], $constant_names)) {
                // split PHP5 tokens and pure PHP constants
                if ($const['name'] == strtolower($const['name'])) {
                    $token_names[] = $const['name'];
                } else {
                    $constant_names[] = $const['name'];
                }
            }
        }

        if (isset($php5_method_chaining)
            && $php5_method_chaining === true
            && version_compare($latest_version, '5.0.0') < 0) {
            // when PHP Method chaining is detected, only available for PHP 5
            $latest_version = '5.0.0';
        }

        ksort($functions_version);

        if (count($function_exists) > 0) {
            $function_exists = array_unique($function_exists);
            $cond_code      += 1;
        }
        if (count($extension_loaded) > 0) {
            $extension_loaded = array_unique($extension_loaded);
            $cond_code       += 2;
        }
        if (count($defined) > 0) {
            $defined    = array_unique($defined);
            $cond_code += 4;
        }
        $cond_code = array($cond_code, array($function_exists,
                                             $extension_loaded,
                                             $defined));

        sort($ignored_functions);
        sort($ignored_extensions);
        sort($ignored_constants);
        sort($classes);
        sort($functions);
        natcasesort($extensions);
        sort($constant_names);
        sort($token_names);
        $main_info = array('ignored_functions'  => $ignored_functions,
                           'ignored_extensions' => $ignored_extensions,
                           'ignored_constants'  => $ignored_constants,
                           'max_version' => $earliest_version,
                           'version'     => $latest_version,
                           'classes'     => $classes,
                           'functions'   => $functions,
                           'extensions'  => array_values($extensions),
                           'constants'   => $constant_names,
                           'tokens'      => $token_names,
                           'cond_code'   => $cond_code);

        $functions_version = array_merge($main_info, $functions_version);
        return $functions_version;
    }

    /**
     * Checks if function which has $init version should be keep
     * or ignore (version is between $min_ver and $max_ver).
     *
     * @param string $init    version of current function
     * @param string $min_ver minimum version of function to ignore
     * @param string $max_ver maximum version of function to ignore
     *
     * @access private
     * @return boolean True to ignore function/constant, false otherwise
     * @since  version 1.4.0 (2006-09-27)
     * @static
     */
    function _ignore($init, $min_ver, $max_ver)
    {
        if ($min_ver) {
            $cmp = version_compare($init, $min_ver);
            if ($max_ver && $cmp >= 0) {
                $cmp = version_compare($init, $max_ver);
                if ($cmp < 1) {
                    return true;
                }
            } elseif ($cmp === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the given token is of this symbolic name
     *
     * @param mixed  $token    Single PHP token to test
     * @param string $symbolic Symbolic name of the given token
     *
     * @access private
     * @return bool
     * @since  version 1.7.0b4 (2008-04-03)
     */
    function _isToken($token, $symbolic)
    {
        if (is_array($token)) {
            $t = token_name($token[0]);
        } else {
            $t = $token;
        }
        return ($t == $symbolic);
    }

    /**
     * Computes the difference of arrays
     *
     * Computes the difference of arrays and returns result without original keys
     *
     * @param array $array1 The array to compare from
     * @param array $array2 The array to compare against
     *
     * @access private
     * @static
     * @link   http://www.php.net/manual/en/function.array-diff.php#82297
     * @return array
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function _arrayDiff($array1, $array2)
    {
        // This wrapper for array_diff rekeys the array returned
        $valid_array = array_diff($array1, $array2);

        // reinstantiate $array1 variable
        $array1 = array();

        // loop through the validated array and move elements to $array1
        // this is necessary because the array_diff function
        // returns arrays that retain their original keys
        foreach ($valid_array as $valid) {
            $array1[] = $valid;
        }
        return $array1;
    }
}
?>