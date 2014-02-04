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
 * @version  CVS: $Id: Cli.php,v 1.75 2009/01/02 10:18:47 farell Exp $
 * @link     http://pear.php.net/package/PHP_CompatInfo
 * @since    File available since Release 0.8.0
 */

require_once 'CompatInfo.php';
require_once 'Console/Getargs.php';

/**
 * CLI Script to Check Compatibility of chunk of PHP code
 *
 * <code>
 * <?php
 *     require_once 'PHP/CompatInfo/Cli.php';
 *     $cli = new PHP_CompatInfo_Cli();
 *     $cli->run();
 * ?>
 * </code>
 *
 * @category  PHP
 * @package   PHP_CompatInfo
 * @author    Davey Shafik <davey@php.net>
 * @author    Laurent Laville <pear@laurent-laville.org>
 * @copyright 2003 Davey Shafik and Synaptic Media. All Rights Reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version   Release: 1.9.0
 * @link      http://pear.php.net/package/PHP_CompatInfo
 * @since     Class available since Release 0.8.0
 */

class PHP_CompatInfo_Cli
{
    /**
     * @var    array    Current CLI Flags
     * @since  0.8.0
     */
    var $opts = array();

    /**
     * Unified data source reference
     *
     * @var    string   Directory, File or String to be processed
     * @since  1.8.0b3
     */
    var $dataSource;

    /**
     * @var    array    Current parser options
     * @since  1.4.0
     */
    var $options = array();


    /**
     * Command-Line Class constructor
     *
     * Command-Line Class constructor (ZE2) for PHP5+
     *
     * @since  version 0.8.0 (2004-04-22)
     */
    function __construct()
    {
        $this->opts = array(
            'dir' =>
                array('short' => 'd',
                      'desc'  => 'Parse DIR to get its compatibility info',
                      'default' => '',
                      'min'   => 0 , 'max' => 1),
            'file' =>
                array('short' => 'f',
                      'desc' => 'Parse FILE to get its compatibility info',
                      'default' => '',
                      'min'   => 0 , 'max' => 1),
            'string' =>
                array('short' => 's',
                      'desc' => 'Parse STRING to get its compatibility info',
                      'default' => '',
                      'min'   => 0 , 'max' => 1),
            'verbose' =>
                array('short'   => 'v',
                      'desc'    => 'Set the verbose level',
                      'default' => 1,
                      'min'     => 0 , 'max' => 1),
            'no-recurse' =>
                array('short' => 'n',
                      'desc'  => 'Do not recursively parse files when using --dir',
                      'max'   => 0),
            'ignore-files' =>
                array('short'   => 'if',
                      'desc'    => 'Data file name which contains a list of '
                                 . 'file to ignore',
                      'default' => 'files.txt',
                      'min'     => 0 , 'max' => 1),
            'ignore-dirs' =>
                array('short'   => 'id',
                      'desc'    => 'Data file name which contains a list of '
                                 . 'directory to ignore',
                      'default' => 'dirs.txt',
                      'min'     => 0 , 'max' => 1),
            'ignore-functions' =>
                array('short'   => 'in',
                      'desc'    => 'Data file name which contains a list of '
                                 . 'php function to ignore',
                      'default' => 'functions.txt',
                      'min'     => 0 , 'max' => 1),
            'ignore-constants' =>
                array('short'   => 'ic',
                      'desc'    => 'Data file name which contains a list of '
                                 . 'php constant to ignore',
                      'default' => 'constants.txt',
                      'min'     => 0 , 'max' => 1),
            'ignore-extensions' =>
                array('short'   => 'ie',
                      'desc'    => 'Data file name which contains a list of '
                                 . 'php extension to ignore',
                      'default' => 'extensions.txt',
                      'min'     => 0 , 'max' => 1),
            'ignore-versions' =>
                array('short'   => 'iv',
                      'desc'    => 'PHP versions - functions to exclude '
                                 . 'when parsing source code',
                      'default' => '5.0.0',
                      'min'     => 0 , 'max' => 2),
            'ignore-functions-match' =>
                array('short'   => 'inm',
                      'desc'    => 'Data file name which contains a list of '
                                 . 'php function pattern to ignore',
                      'default' => 'functions-match.txt',
                      'min'     => 0 , 'max' => 1),
            'ignore-extensions-match' =>
                array('short'   => 'iem',
                      'desc'    => 'Data file name which contains a list of '
                                 . 'php extension pattern to ignore',
                      'default' => 'extensions-match.txt',
                      'min'     => 0 , 'max' => 1),
            'ignore-constants-match' =>
                array('short'   => 'icm',
                      'desc'    => 'Data file name which contains a list of '
                                 . 'php constant pattern to ignore',
                      'default' => 'constants-match.txt',
                      'min'     => 0 , 'max' => 1),
            'file-ext' =>
                array('short'   => 'fe',
                      'desc'    => 'A comma separated list of file extensions '
                                 . 'to parse (only valid if parsing a directory)',
                      'default' => 'php, php4, inc, phtml',
                      'min'     => 0 , 'max' => 1),
            'report' =>
                array('short' => 'r',
                      'desc' => 'Print either "xml" or "csv" report',
                      'default' => 'text',
                      'min'   => 0 , 'max' => 1),
            'output-level' =>
                array('short' => 'o',
                      'desc' => 'Print Path/File + Version with additional data',
                      'default' => 31,
                      'min'   => 0 , 'max' => 1),
            'tab' =>
                array('short' => 't',
                      'desc'  => 'Columns width',
                      'default' => '29,12,20',
                      'min'   => 0 , 'max' => 1),
            'progress' =>
                array('short' => 'p',
                      'desc' => 'Show a wait message [text] or a progress bar [bar]',
                      'default' => 'bar',
                      'min'   => 0 , 'max' => 1),
            'summarize' =>
                array('short' => 'S',
                      'desc' => 'Print only summary when parsing directory',
                      'max'   => 0),
            'version' =>
                array('short' => 'V',
                      'desc'  => 'Print version information',
                      'max'   => 0),
            'help' =>
                array('short' => 'h',
                      'desc'  => 'Show this help',
                      'max'   => 0),
        );
    }

    /**
     * Command-Line Class constructor
     *
     * Command-Line Class constructor (ZE1) for PHP4
     *
     * @since  version 0.8.0 (2004-04-22)
     */
    function PHP_CompatInfo_Cli()
    {
        $this->__construct();
    }

    /**
     * Run the CLI version
     *
     * Run the CLI version of PHP_CompatInfo
     *
     * @return void
     * @access public
     * @since  version 0.8.0 (2004-04-22)
     */
    function run()
    {
        $args = & Console_Getargs::factory($this->opts);
        if (PEAR::isError($args)) {
            if ($args->getCode() === CONSOLE_GETARGS_HELP) {
                $error = '';
            } else {
                $error = $args->getMessage();
            }
            $this->_printUsage($error);
            return;
        }

        // default parser options
        $this->options = array(
            'file_ext' => array('php', 'php4', 'inc', 'phtml'),
            'recurse_dir' => true,
            'debug' => false,
            'is_string' => false,
            'ignore_files' => array(),
            'ignore_dirs' => array()
            );

        // version
        $V = $args->getValue('V');
        if (isset($V)) {
            $error = 'PHP_CompatInfo (cli) version 1.9.0'
                   . ' (http://pear.php.net/package/PHP_CompatInfo)';
            echo $error;
            return;
        }

        // debug
        if ($args->isDefined('v')) {
            $v = $args->getValue('v');
            if ($v > 3) {
                $this->options['debug'] = true;
            }
        }

        // no-recurse
        if ($args->isDefined('n')) {
            $this->options['recurse_dir'] = false;
        }

        // dir
        if ($args->isDefined('d')) {
            $d = $args->getValue('d');
            if (file_exists($d)) {
                if ($d{strlen($d)-1} == '/' || $d{strlen($d)-1} == '\\') {
                    $d = substr($d, 0, -1);
                }
                $this->dataSource = realpath($d);
            } else {
                $error = 'Failed opening directory "' . $d
                       . '". Please check your spelling and try again.';
                $this->_printUsage($error);
                return;
            }
        }

        // file
        if ($args->isDefined('f')) {
            $f = $args->getValue('f');
            if (file_exists($f)) {
                $this->dataSource = $f;
            } else {
                $error = 'Failed opening file "' . $f
                       . '". Please check your spelling and try again.';
                $this->_printUsage($error);
                return;
            }
        }

        // string
        if ($args->isDefined('s')) {
            $s = $args->getValue('s');
            if (!empty($s)) {
                $this->dataSource           = sprintf("<?php %s ?>", $s);
                $this->options['is_string'] = true;
            } else {
                $error = 'Failed opening string "' . $s
                       . '". Please check your spelling and try again.';
                $this->_printUsage($error);
                return;
            }
        }

        // ignore-files
        $if = $args->getValue('if');
        if (isset($if)) {
            if (file_exists($if)) {
                $options                       = $this->_parseParamFile($if);
                $this->options['ignore_files'] = $options['std'];
            } else {
                $error = 'Failed opening file "' . $if
                       . '" (ignore-files option). '
                       . 'Please check your spelling and try again.';
                $this->_printUsage($error);
                return;
            }
        }

        // ignore-dirs
        $id = $args->getValue('id');
        if (isset($id)) {
            if (file_exists($id)) {
                $options                      = $this->_parseParamFile($id);
                $this->options['ignore_dirs'] = $options['std'];
            } else {
                $error = 'Failed opening file "' . $id
                       . '" (ignore-dirs option). '
                       . 'Please check your spelling and try again.';
                $this->_printUsage($error);
                return;
            }
        }

        // ignore-functions
        $in = $args->getValue('in');
        if (isset($in)) {
            if (file_exists($in)) {
                $options                           = $this->_parseParamFile($in);
                $this->options['ignore_functions'] = $options['std'];
            } else {
                $error = 'Failed opening file "' . $in
                       . '" (ignore-functions option). '
                       . 'Please check your spelling and try again.';
                $this->_printUsage($error);
                return;
            }
        }

        // ignore-constants
        $ic = $args->getValue('ic');
        if (isset($ic)) {
            if (file_exists($ic)) {
                $options                           = $this->_parseParamFile($ic);
                $this->options['ignore_constants'] = $options['std'];
            } else {
                $error = 'Failed opening file "' . $ic
                       . '" (ignore-constants option). '
                       . 'Please check your spelling and try again.';
                $this->_printUsage($error);
                return;
            }
        }

        // ignore-extensions
        $ie = $args->getValue('ie');
        if (isset($ie)) {
            if (file_exists($ie)) {
                $options                            = $this->_parseParamFile($ie);
                $this->options['ignore_extensions'] = $options['std'];
            } else {
                $error = 'Failed opening file "' . $ie
                       . '" (ignore-extensions option). '
                       . 'Please check your spelling and try again.';
                $this->_printUsage($error);
                return;
            }
        }

        // ignore-versions
        $iv = $args->getValue('iv');
        if (isset($iv)) {
            if (!is_array($iv)) {
                $iv = array($iv);
            }
            $this->options['ignore_versions'] = $iv;
        }

        // ignore-functions-match
        $inm = $args->getValue('inm');
        if (isset($inm)) {
            if (file_exists($inm)) {
                $patterns = $this->_parseParamFile($inm, true);
                if (count($patterns['std']) > 0
                    && count($patterns['reg']) > 0) {
                    $error = 'Mixed "function_exists" and '
                           . '"preg_match" conditions are not allowed. '
                           . 'Please check your spelling and try again.';
                    $this->_printUsage($error);
                    return;

                } elseif (count($patterns['std']) > 0) {
                    $this->options['ignore_functions_match']
                        = array('function_exists', $patterns['std']);
                } elseif (count($patterns['reg']) > 0) {
                    $this->options['ignore_functions_match']
                        = array('preg_match', $patterns['reg']);
                }
            } else {
                $error = 'Failed opening file "' . $inm
                       . '" (ignore-functions-match option). '
                       . 'Please check your spelling and try again.';
                $this->_printUsage($error);
                return;
            }
        }

        // ignore-extensions-match
        $iem = $args->getValue('iem');
        if (isset($iem)) {
            if (file_exists($iem)) {
                $patterns = $this->_parseParamFile($iem, true);
                if (count($patterns['std']) > 0
                    && count($patterns['reg']) > 0) {
                    $error = 'Mixed "extension_loaded" and '
                           . '"preg_match" conditions are not allowed. '
                           . 'Please check your spelling and try again.';
                    $this->_printUsage($error);
                    return;

                } elseif (count($patterns['std']) > 0) {
                    $this->options['ignore_extensions_match']
                        = array('extension_loaded', $patterns['std']);
                } elseif (count($patterns['reg']) > 0) {
                    $this->options['ignore_extensions_match']
                        = array('preg_match', $patterns['reg']);
                }
            } else {
                $error = 'Failed opening file "' . $iem
                       . '" (ignore-extensions-match option). '
                       . 'Please check your spelling and try again.';
                $this->_printUsage($error);
                return;
            }
        }

        // ignore-constants-match
        $icm = $args->getValue('icm');
        if (isset($icm)) {
            if (file_exists($icm)) {
                $patterns = $this->_parseParamFile($icm, true);
                if (count($patterns['std']) > 0
                    && count($patterns['reg']) > 0) {
                    $error = 'Mixed "defined" and '
                           . '"preg_match" conditions are not allowed. '
                           . 'Please check your spelling and try again.';
                    $this->_printUsage($error);
                    return;

                } elseif (count($patterns['std']) > 0) {
                    $this->options['ignore_constants_match']
                        = array('defined', $patterns['std']);
                } elseif (count($patterns['reg']) > 0) {
                    $this->options['ignore_constants_match']
                        = array('preg_match', $patterns['reg']);
                }
            } else {
                $error = 'Failed opening file "' . $icm
                       . '" (ignore-constants-match option). '
                       . 'Please check your spelling and try again.';
                $this->_printUsage($error);
                return;
            }
        }

        // file-ext
        if ($args->isDefined('d') && $args->isDefined('fe')) {
            $fe = $args->getValue('fe');
            if (is_string($fe)) {
                $this->options['file_ext'] = explode(',', $fe);
            } else {
                $error = 'No valid file extensions provided "'
                       . '". Please check your spelling and try again.';
                $this->_printUsage($error);
                return;
            }
        }

        // file or directory options are minimum required to work
        if (!$args->isDefined('f')
            && !$args->isDefined('d')
            && !$args->isDefined('s')) {
            $error = 'ERROR: You must supply at least '
                   . 'one string, file or directory to process';
            $this->_printUsage($error);
            return;
        }

        if ($args->isDefined('r')) {
            $report = $args->getValue('r');
        } else {
            $report = 'text';
        }

        if ($args->isDefined('t')) {
            $defs = array('f' => 29, 'e' => 12, 'c' => 20);
            $tabs = $args->getValue('t');
            $tabs = explode(',', $tabs);
            for ($t = 0; $t < 3; $t++) {
                if (isset($tabs[$t])) {
                    if ($t == 0) {
                        $defs['f'] = (int)$tabs[$t];
                    } elseif ($t == 1) {
                        $defs['e'] = (int)$tabs[$t];
                    } else {
                        $defs['c'] = (int)$tabs[$t];
                    }
                }
            }
            $conf = array('colwidth' => $defs);
        } else {
            $conf = array();
        }
        $conf = array_merge($conf, array('args' => $args->getValues()));

        $compatInfo = new PHP_CompatInfo($report, $conf);

        // dir
        if ($args->isDefined('d')) {
            $d     = $args->getValue('d');
            $files = $compatInfo->parser->getFilelist($d, $this->options);
            if (count($files) == 0) {
                $error = 'No valid files into directory "'. $d
                       . '". Please check your spelling and try again.';
                $this->_printUsage($error);
                return;
            }
        }
        $compatInfo->parseData($this->dataSource, $this->options);
    }

    /**
     * Parse content of parameter files
     *
     * Parse content of parameter files used by switches
     * <ul>
     * <li>ignore-files
     * <li>ignore-dirs
     * <li>ignore-functions
     * <li>ignore-constants
     * <li>ignore-extensions
     * <li>ignore-functions-match
     * <li>ignore-extensions-match
     * <li>ignore-constants-match
     * </ul>
     *
     * @param string $fn          Parameter file name
     * @param bool   $withPattern TRUE if the file may contain regular expression
     *
     * @return array
     * @access private
     * @since  version 1.7.0b4 (2008-04-03)
     */
    function _parseParamFile($fn, $withPattern = false)
    {
        $lines    = file($fn);
        $patterns = array('std' => array(), 'reg' => array());
        foreach ($lines as $line) {
            $line = rtrim($line);  // remove line ending
            if (strlen($line) == 0) {
                continue;  // skip empty lines
            }
            if ($line{0} == ';') {
                continue;  // skip this pattern: consider as comment line
            }
            if ($line{0} == '=') {
                list($p, $s)       = explode('=', $line);
                $patterns['reg'][] = '/'.$s.'/';
            } else {
                if ($withPattern === true) {
                    $patterns['std'][] = '/'.$line.'/';
                } else {
                    $patterns['std'][] = $line;
                }
            }
        }
        return $patterns;
    }

    /**
     * Show full help information
     *
     * @param string $footer (optional) page footer content
     *
     * @return void
     * @access private
     * @since  version 0.8.0 (2004-04-22)
     */
    function _printUsage($footer = '')
    {
        $header = 'Usage: '
            . basename($_SERVER['SCRIPT_NAME']) . " [options]\n\n";
        echo Console_Getargs::getHelp($this->opts, $header,
            "\n$footer\n", 78, 2)."\n";
    }
}
?>