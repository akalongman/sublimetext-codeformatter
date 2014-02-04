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
 * @version  CVS: $Id: Text.php,v 1.13 2009/01/02 10:18:47 farell Exp $
 * @link     http://pear.php.net/package/PHP_CompatInfo
 * @since    File available since Release 1.8.0b3
 */

require_once 'Console/Table.php';

/**
 * Text renderer for PHP_CompatInfo component.
 *
 * The PHP_CompatInfo_Renderer_Text class is a concrete implementation
 * of PHP_CompatInfo_Renderer abstract class. It simply display results
 * for the command line interface with help of PEAR::Console_Table
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version  Release: 1.9.0
 * @link     http://pear.php.net/package/PHP_CompatInfo
 * @since    Class available since Release 1.8.0b3
 */
class PHP_CompatInfo_Renderer_Text extends PHP_CompatInfo_Renderer
{
    /**
     * Text Renderer Class constructor (ZE1) for PHP4
     *
     * @param object &$parser Instance of the parser (model of MVC pattern)
     * @param array  $conf    A hash containing any additional configuration
     *
     * @access public
     * @since  version 1.8.0b3 (2008-06-07)
     */
    function PHP_CompatInfo_Renderer_Text(&$parser, $conf)
    {
        $this->__construct($parser, $conf);
    }

    /**
     * Text Renderer Class constructor (ZE2) for PHP5+
     *
     * @param object &$parser Instance of the parser (model of MVC pattern)
     * @param array  $conf    A hash containing any additional configuration
     *
     * @access public
     * @since  version 1.8.0b3 (2008-06-07)
     */
    function __construct(&$parser, $conf)
    {
        $defaults = array('colwidth' => array('f' => 29, 'e' => 12, 'c' => 20));
        $conf     = array_merge($defaults, $conf);

        parent::PHP_CompatInfo_Renderer($parser, $conf);
    }

    /**
     * Display final results
     *
     * Display final results, when data source parsing is over.
     *
     * @access public
     * @return void
     * @since  version 1.8.0b3 (2008-06-07)
     */
    function display()
    {
        $o    = $this->args['output-level'];
        $info = $this->parseData;
        $hdr  = array();
        $src  = $this->_parser->dataSource;

        if (isset($this->args['dir'])) {
            $dir   = $this->args['dir'];
            $hdr[] = 'Files';
        } elseif (isset($this->args['file'])) {
            $file  = $this->args['file'];
            $hdr[] = 'File';
        } elseif (isset($this->args['string'])) {
            $string = $this->args['string'];
            $hdr[]  = 'Source code';
        } elseif ($src['dataType'] == 'directory') {
            $dir   = $src['dataSource'];
            $hdr[] = 'Files';
        } elseif ($src['dataType'] == 'file') {
            $file  = $src['dataSource'];
            $hdr[] = 'File';
        } else {
            $string = $src['dataSource'];
            $hdr[]  = 'Source code';
        }

        $table = new Console_Table();
        $f     = 0;
        if ($o & 16) {
            $hdr[] = 'Version';
            $f++;
        }
        if ($o & 1) {
            $hdr[] = 'C';
            $f++;
        }
        if ($o & 2) {
            $hdr[]   = 'Extensions';
            $filter2 = array(&$this, '_splitExtname');
            $table->addFilter($f+1, $filter2);
            $f++;
        }
        if ($o & 4) {
            if ($o & 8) {
                $hdr[] = 'Constants/Tokens';
            } else {
                $hdr[] = 'Constants';
            }
            $f++;
        } else {
            if ($o & 8) {
                $hdr[] = 'Tokens';
                $f++;
            }
        }
        $table->setHeaders($hdr);
        $filter0 = array(&$this, '_splitFilename');
        $table->addFilter(0, $filter0);
        if ($o > 3 && $o < 16 || $o > 19) {
            $filter3 = array(&$this, '_splitConstant');
            $table->addFilter($f, $filter3);
        }

        $ext   = implode("\r\n", $info['extensions']);
        $const = implode("\r\n", array_merge($info['constants'], $info['tokens']));
        if (isset($dir)) {
            $ds   = DIRECTORY_SEPARATOR;
            $dir  = str_replace(array('\\', '/'), $ds, $dir);
            $data = array($dir . $ds . '*');
        } elseif (isset($file)) {
            $data = array($file);
        } else {
            $data = array('<?php ... ?>');
        }

        if ($o & 16) {
            if (empty($info['max_version'])) {
                $data[] = $info['version'];
            } else {
                $data[] = implode("\r\n", array($info['version'],
                                                $info['max_version']));
            }
        }
        if ($o & 1) {
            $data[] = $info['cond_code'][0];
        }
        if ($o & 2) {
            $data[] = $ext;
        }
        if ($o & 4) {
            if ($o & 8) {
                $data[] = $const;
            } else {
                $data[] = implode("\r\n", $info['constants']);
            }
        } else {
            if ($o & 8) {
                $data[] = implode("\r\n", $info['tokens']);
            }
        }

        $table->addRow($data);

        unset($info['max_version']);
        unset($info['version']);
        unset($info['classes']);
        unset($info['functions']);
        unset($info['extensions']);
        unset($info['constants']);
        unset($info['tokens']);
        unset($info['cond_code']);

        $ignored       = $info['ignored_files'];
        $all_functions = array();

        unset($info['ignored_files']);
        unset($info['ignored_functions']);
        unset($info['ignored_extensions']);
        unset($info['ignored_constants']);

        // summarize : print only summary for directory without files details
        if ($this->args['summarize'] === false && isset($dir)) {

            foreach ($info as $file => $info) {
                if (is_numeric($file{0})) {
                    // extra information available only when debug mode is on
                    $all_functions[$file] = $info;
                    continue;
                }
                if ($info === false) {
                    continue;  // skip this (invalid) file
                }
                $ext   = implode("\r\n", $info['extensions']);
                $const = implode("\r\n", array_merge($info['constants'],
                                                     $info['tokens']));

                $file = str_replace(array('\\', '/'), $ds, $file);
                $table->addSeparator();

                $data = array($file);
                if ($o & 16) {
                    if (empty($info['max_version'])) {
                        $data[] = $info['version'];
                    } else {
                        $data[] = implode("\r\n", array($info['version'],
                                                        $info['max_version']));
                    }
                }
                if ($o & 1) {
                    $data[] = $info['cond_code'][0];
                }
                if ($o & 2) {
                    $data[] = $ext;
                }
                if ($o & 4) {
                    if ($o & 8) {
                        $data[] = $const;
                    } else {
                        $data[] = implode("\r\n", $info['constants']);
                    }
                } else {
                    if ($o & 8) {
                        $data[] = implode("\r\n", $info['tokens']);
                    }
                }

                $table->addRow($data);
            }
        } else {
            foreach ($info as $file => $info) {
                if (is_numeric($file{0})) {
                    // extra information available only when debug mode is on
                    $all_functions[$file] = $info;
                }
            }
        }

        $output = $table->getTable();

        // verbose level
        $v = isset($this->args['verbose']) ? $this->args['verbose'] : 0;

        // command line resume
        if ($v & 1) {
            $output .= "\nCommand Line resume :\n\n";

            $table = new Console_Table();
            $table->setHeaders(array('Option', 'Value'));

            $filter0 = array(&$this, '_splitOption');
            $table->addFilter(0, $filter0);
            $filter1 = array(&$this, '_splitValue');
            $table->addFilter(1, $filter1);

            if (is_array($this->args)) {
                foreach ($this->args as $key => $raw) {
                    if ($key == 'summarize') {
                        $raw = ($raw === true) ? 'TRUE' : 'FALSE';
                    }
                    if (is_array($raw)) {
                        $raw = implode(', ', $raw);
                    }
                    $contents = array($key, $raw);
                    $table->addRow($contents);
                }
            }

            $output .= $table->getTable();
        }

        // parser options resume
        if ($v & 2) {
            $output .= "\nParser options :\n\n";

            $table = new Console_Table();
            $table->setHeaders(array('Option', 'Value'));

            $filter0 = array(&$this, '_splitOption');
            $table->addFilter(0, $filter0);
            $filter1 = array(&$this, '_splitValue');
            $table->addFilter(1, $filter1);

            $opts = $this->_parser->options;
            if (is_array($opts)) {
                foreach ($opts as $key => $raw) {
                    if ($key == 'debug'
                        || $key == 'recurse_dir'
                        || $key == 'is_string') {
                        $raw = ($raw === true) ? 'TRUE' : 'FALSE';
                    }
                    if (substr($key, -6) == '_match') {
                        $val = array_values($raw[1]);
                        array_unshift($val, $raw[0]);
                        $raw = implode("\r\n", $val);
                    } else {
                        if (is_array($raw)) {
                            $raw = implode("\r\n", $raw);
                        }
                    }
                    $contents = array($key, $raw);
                    $table->addRow($contents);
                }
            }

            $output .= $table->getTable();
        }

        // extra information
        if ($v & 4) {
            $output .= "\nDebug:\n\n";

            $table = new Console_Table();
            $table->setHeaders(array('Version', 'Function', 'Extension', 'PECL'));

            foreach ($all_functions as $version => $functions) {
                foreach ($functions as $func) {
                    $table->addRow(array($version,
                        $func['function'], $func['extension'],
                        (isset($func['pecl']) ?
                        (($func['pecl'] === true) ? 'yes' : 'no') : '')));
                }
            }

            $output .= $table->getTable();
        }
        echo $output;
    }

    /**
     * The Console_Table filter callback limits table output to 80 columns,
     * and Path column to 29 characters
     * (27 + 1 blank margin left + 1 blank margin right).
     *
     * @param string $data Content of filename column (0)
     *
     * @return string
     * @access private
     * @since  1.3.0
     */
    function _splitFilename($data)
    {
        $w = $this->conf['colwidth']['f'] - 2;

        if (strlen($data) <= $w) {
            $str = str_pad($data, $w);
        } else {
            $str = '...' . substr($data, (strlen($data) - ($w - 3)));
        }
        return $str;
    }

    /**
     * The Console_Table filter callback limits table output to 80 columns,
     * and Extensions column to 12 characters
     * (10 + 1 blank margin left + 1 blank margin right).
     *
     * @param string $data Content of extensions column
     *
     * @return string
     * @access private
     * @since  1.7.0
     */
    function _splitExtname($data)
    {
        $szlim  = $this->conf['colwidth']['e'] - 2;
        $extArr = explode("\r\n", $data);
        $str    = '';
        foreach ($extArr as $ext) {
            if (strlen($ext) <= $szlim) {
                $str .= str_pad($ext, $szlim);
            } else {
                $str .= '...' . substr($ext, (strlen($ext) - ($szlim - 3)));
            }
            $str .= "\r\n";
        }
        $str = rtrim($str, "\r\n");
        return $str;
    }

    /**
     * The Console_Table filter callback limits table output to 80 columns,
     * and Constants/Tokens column to 20 characters
     * (18 + 1 blank margin left + 1 blank margin right)
     *
     * @param string $data Content of constants/tokens column
     *
     * @return string
     * @access private
     * @since  1.7.0
     */
    function _splitConstant($data)
    {
        $szlim  = $this->conf['colwidth']['c'] - 2;
        $cstArr = explode("\r\n", $data);
        $str    = '';
        foreach ($cstArr as $cst) {
            if (strlen($cst) <= $szlim) {
                $str .= str_pad($cst, $szlim);
            } else {
                $str .= '...' . substr($cst, (strlen($cst) - ($szlim - 3)));
            }
            $str .= "\r\n";
        }
        $str = rtrim($str, "\r\n");
        return $str;
    }

    /**
     * The Console_Table filter callback limits table output to 80 columns,
     * and Command line Option column to 25 characters
     * (23 + 1 blank margin left + 1 blank margin right).
     *
     * @param string $data Content of option column (0)
     *
     * @return string
     * @access private
     * @since  1.7.0
     */
    function _splitOption($data)
    {
        if (strlen($data) <= 23) {
            $str = str_pad($data, 23);
        } else {
            $str = '...' . substr($data, (strlen($data) - 20));
        }
        return $str;
    }

    /**
     * The Console_Table filter callback limits table output to 80 columns,
     * and Command line Value column to 51 characters
     * (49 + 1 blank margin left + 1 blank margin right)
     *
     * @param string $data Content of value column (1)
     *
     * @return string
     * @access private
     * @since  1.7.0
     */
    function _splitValue($data)
    {
        $cstArr = explode("\r\n", $data);
        $str    = '';
        foreach ($cstArr as $cst) {
            if (strlen($cst) <= 49) {
                $str .= str_pad($cst, 49);
            } else {
                $str .= '...' . substr($cst, (strlen($cst) - 46));
            }
            $str .= "\r\n";
        }
        $str = rtrim($str, "\r\n");
        return $str;
    }
}
?>