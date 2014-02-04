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
 * @version  CVS: $Id: Csv.php,v 1.6 2009/01/02 10:18:47 farell Exp $
 * @link     http://pear.php.net/package/PHP_CompatInfo
 * @since    File available since Release 1.8.0b3
 */

/**
 * Csv renderer for PHP_CompatInfo component.
 *
 * The PHP_CompatInfo_Renderer_Csv class is a concrete implementation
 * of PHP_CompatInfo_Renderer abstract class. It simply output informations
 * in Comma Seperated Value style.
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version  Release: 1.9.0
 * @link     http://pear.php.net/package/PHP_CompatInfo
 * @since    Class available since Release 1.8.0b4
 */
class PHP_CompatInfo_Renderer_Csv extends PHP_CompatInfo_Renderer
{
    /**
     * Csv Renderer Class constructor (ZE1) for PHP4
     *
     * @param object &$parser Instance of the parser (model of MVC pattern)
     * @param array  $conf    A hash containing any additional configuration
     *
     * @access public
     * @since  version 1.8.0b4 (2008-06-18)
     */
    function PHP_CompatInfo_Renderer_Csv(&$parser, $conf)
    {
        $this->__construct($parser, $conf);
    }

    /**
     * Csv Renderer Class constructor (ZE2) for PHP5+
     *
     * @param object &$parser Instance of the parser (model of MVC pattern)
     * @param array  $conf    A hash containing any additional configuration
     *
     * @access public
     * @since  version 1.8.0b4 (2008-06-18)
     */
    function __construct(&$parser, $conf)
    {
        $defaults = array('fields-values-separated-by' => ',',
                          'fields-terminated-by' => ';',
                          'fields-enclosed-by' => '"',
                          'lines-terminated-by' => PHP_EOL);
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
     * @since  version 1.8.0b4 (2008-06-18)
     */
    function display()
    {
        $fvsb = $this->conf['fields-values-separated-by'];
        $o    = $this->args['output-level'];
        $info = $this->parseData;
        $hdr  = array();
        $src  = $this->_parser->dataSource;

        if ($info === false) {
            // invalid data source
            return;
        }

        $options = $this->_parser->options;

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
            if ($options['is_string'] == true) {
                $string = $src['dataSource'];
                $hdr[]  = 'Source code';
            } else {
                $dir   = $src['dataSource'];
                $hdr[] = 'Files';
            }
        }

        if ($o & 16) {
            $hdr[] = 'Version';
        }
        if ($o & 1) {
            $hdr[] = 'C';
        }
        if ($o & 2) {
            $hdr[] = 'Extensions';
        }
        if ($o & 4) {
            if ($o & 8) {
                $hdr[] = 'Constants/Tokens';
            } else {
                $hdr[] = 'Constants';
            }
        } else {
            if ($o & 8) {
                $hdr[] = 'Tokens';
            }
        }
        // print headers
        $this->_printf($hdr);

        $ext   = implode($fvsb, $info['extensions']);
        $const = implode($fvsb, array_merge($info['constants'], $info['tokens']));
        if (isset($dir)) {
            $ds = DIRECTORY_SEPARATOR;
            if (is_array($dir)) {
                $data = array(dirname($dir[0]));
            } else {
                $dir  = str_replace(array('\\', '/'), $ds, $dir);
                $data = array($dir);
            }
        } elseif (isset($file)) {
            $data = array($file);
        } else {
            $data = array('<?php ... ?>');
        }

        if ($o & 16) {
            if (empty($info['max_version'])) {
                $data[] = $info['version'];
            } else {
                $data[] = implode($fvsb, array($info['version'],
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
                $data[] = implode($fvsb, $info['constants']);
            }
        } else {
            if ($o & 8) {
                $data[] = implode($fvsb, $info['tokens']);
            }
        }

        $this->_printf($data);

        // summarize : print only summary for directory without files details
        if ($this->args['summarize'] === false && isset($dir)) {

            unset($info['max_version']);
            unset($info['version']);
            unset($info['classes']);
            unset($info['functions']);
            unset($info['extensions']);
            unset($info['constants']);
            unset($info['tokens']);
            unset($info['cond_code']);

            $ignored = $info['ignored_files'];

            unset($info['ignored_files']);
            unset($info['ignored_functions']);
            unset($info['ignored_extensions']);
            unset($info['ignored_constants']);

            foreach ($info as $file => $info) {
                if ($info === false) {
                    continue;  // skip this (invalid) file
                }
                $ext   = implode($fvsb, $info['extensions']);
                $const = implode($fvsb, array_merge($info['constants'],
                                                    $info['tokens']));

                $file = str_replace(array('\\', '/'), $ds, $file);

                $data = array($file);
                if ($o & 16) {
                    if (empty($info['max_version'])) {
                        $data[] = $info['version'];
                    } else {
                        $data[] = implode($fvsb, array($info['version'],
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
                        $data[] = implode($fvsb, $info['constants']);
                    }
                } else {
                    if ($o & 8) {
                        $data[] = implode($fvsb, $info['tokens']);
                    }
                }

                $this->_printf($data);
            }
        }
    }

    /**
     * Print a single line of CSV report
     *
     * @param array $data Data list to print
     *
     * @return void
     * @access private
     * @since  1.8.0b4 (2008-06-18)
     */
    function _printf($data)
    {
        $string = '';

        foreach ($data as $i => $d) {
            if ($i > 0) {
                $string .= $this->conf['fields-terminated-by'];
            }
            $string .= $this->conf['fields-enclosed-by'] . $d .
                       $this->conf['fields-enclosed-by'];
        }
        $string .= $this->conf['lines-terminated-by'];

        echo $string;
    }
}
?>