<?php
/**
 * Copyright (c) 2008-2009, Laurent Laville <pear@laurent-laville.org>
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
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version  CVS: $Id: Array.php,v 1.9 2009/01/02 10:18:47 farell Exp $
 * @link     http://pear.php.net/package/PHP_CompatInfo
 * @since    File available since Release 1.8.0b2
 */

/**
 * Array renderer for PHP_CompatInfo component.
 *
 * The PHP_CompatInfo_Renderer_Array class is a concrete implementation
 * of PHP_CompatInfo_Renderer abstract class. It simply display results as
 * a PHP array.
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version  Release: 1.9.0
 * @link     http://pear.php.net/package/PHP_CompatInfo
 * @since    Class available since Release 1.8.0b2
 */
class PHP_CompatInfo_Renderer_Array extends PHP_CompatInfo_Renderer
{
    /**
     * Driver to display results array.
     *
     * Default is PHP::var_export, but you can use PEAR::Var_Dump if available
     *
     * @var    string
     * @access public
     */
    var $driver;

    /**
     * Array Renderer Class constructor (ZE1) for PHP4
     *
     * @param object &$parser Instance of the parser (model of MVC pattern)
     * @param array  $conf    A hash containing any additional configuration
     *
     * @access public
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function PHP_CompatInfo_Renderer_Array(&$parser, $conf)
    {
        $this->__construct($parser, $conf);
    }

    /**
     * Array Renderer Class constructor (ZE2) for PHP5+
     *
     * @param object &$parser Instance of the parser (model of MVC pattern)
     * @param array  $conf    A hash containing any additional configuration
     *
     * @access public
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function __construct(&$parser, $conf)
    {
        parent::PHP_CompatInfo_Renderer($parser, $conf);

        $driver = 'PEAR::Var_Dump';

        if (isset($conf[$driver])) {
            $var_dump = 'Var_Dump.php';
            if (PHP_CompatInfo_Renderer::isIncludable($var_dump)) {
                include_once $var_dump;

                $class_options = $conf['PEAR::Var_Dump'];
                if (isset($class_options['options'])) {
                    $options = $class_options['options'];
                } else {
                    $options = array();
                }
                if (isset($class_options['rendererOptions'])) {
                    $rendererOptions = $class_options['rendererOptions'];
                } else {
                    $rendererOptions = array();
                }
                if (php_sapi_name() == 'cli') {
                    // prevent wrong display on command line interface
                    $options['display_mode'] = 'Text';
                }

                Var_Dump::displayInit($options, $rendererOptions);
                $this->driver = $driver;
            }
        }

        if (!isset($this->driver)) {
            // if optional driver not defined, then use default PHP::var_export
            $this->driver = 'PHP';
        }
    }

    /**
     * Display final results
     *
     * Display final results, when data source parsing is over.
     *
     * @access public
     * @return void
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function display()
    {
        $o    = $this->args['output-level'];
        $v    = $this->args['verbose'];
        $data = $this->parseData;
        $src  = $this->_parser->dataSource;

        if ($data === false) {
            // invalid data source
            if ($this->driver == 'PHP') {
                var_export($data);
            } else {
                Var_Dump::display($data);
            }
            return;
        }

        $options = $this->_parser->options;

        if (isset($this->args['dir'])) {
            $files = $this->_parser->getFilelist($this->args['dir'], $options);
        } elseif (isset($this->args['file'])) {
            $files = array($this->args['file']);
        } elseif ($src['dataType'] == 'directory') {
            $files = $src['dataSource'];
        } elseif ($src['dataType'] == 'file') {
            $files = array($src['dataSource']);
        } else {
            $files = $src['dataSource'];
        }

        if ($options['is_string'] == true) {
            foreach ($files as $k => $str) {
                $files[$k] = 'string_' . ($k+1);
            }
        }

        if ($o & 16) {
            // display Version
        } else {
            unset($data['version'], $data['max_version']);
        }
        if ($o & 1) {
            // display Conditions
        } else {
            unset($data['cond_code']);
        }
        if ($o & 2) {
            // display Extensions
        } else {
            unset($data['extensions']);
        }
        if ($o & 4) {
            if ($o & 8) {
                // display Constants/Tokens
            } else {
                // display Constants
                unset($data['tokens']);
            }
        } else {
            unset($data['constants']);
            if ($o & 8) {
                // display Tokens
            } else {
                unset($data['tokens']);
            }
        }
        if ($v & 4 || $options['debug'] == true) {
            // display Functions
        } else {
            unset($data['functions']);
        }

        if (count($files) > 1) {
            if ($this->args['summarize'] === true) {
                foreach ($files as $file) {
                    unset($data[$file]);
                }
            } else {
                foreach ($files as $file) {
                    if ($o & 16) {
                        // display Version
                    } else {
                        unset($data[$file]['version'], $data[$file]['max_version']);
                    }
                    if ($o & 1) {
                        // display Conditions
                    } else {
                        unset($data[$file]['cond_code']);
                    }
                    if ($o & 2) {
                        // display Extensions
                    } else {
                        unset($data[$file]['extensions']);
                    }
                    if ($o & 4) {
                        if ($o & 8) {
                            // display Constants/Tokens
                        } else {
                            // display Constants
                            unset($data[$file]['tokens']);
                        }
                    } else {
                        unset($data[$file]['constants']);
                        if ($o & 8) {
                            // display Tokens
                        } else {
                            unset($data[$file]['tokens']);
                        }
                    }
                    if ($v & 4 || $options['debug'] == true) {
                        // display Functions
                    } else {
                        unset($data[$file]['functions']);
                    }
                }
            }
        }

        if ($this->driver == 'PHP') {
            var_export($data);
        } else {
            Var_Dump::display($data);
        }
    }
}
?>