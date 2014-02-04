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
 * @version  CVS: $Id: Xml.php,v 1.13 2009/01/02 10:18:47 farell Exp $
 * @link     http://pear.php.net/package/PHP_CompatInfo
 * @since    File available since Release 1.8.0b2
 */

require_once 'XML/Util.php';

/**
 * Array renderer for PHP_CompatInfo component.
 *
 * The PHP_CompatInfo_Renderer_Xml class is a concrete implementation
 * of PHP_CompatInfo_Renderer abstract class. It simply display results as
 * an XML stream.
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version  Release: 1.9.0
 * @link     http://pear.php.net/package/PHP_CompatInfo
 * @since    Class available since Release 1.8.0b2
 */
class PHP_CompatInfo_Renderer_Xml extends PHP_CompatInfo_Renderer
{
    /**
     * Xml Renderer Class constructor (ZE1) for PHP4
     *
     * @param object &$parser Instance of the parser (model of MVC pattern)
     * @param array  $conf    A hash containing any additional configuration
     *
     * @access public
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function PHP_CompatInfo_Renderer_Xml(&$parser, $conf)
    {
        $this->__construct($parser, $conf);
    }

    /**
     * Xml Renderer Class constructor (ZE2) for PHP5+
     *
     * @param object &$parser Instance of the parser (model of MVC pattern)
     * @param array  $conf    A hash containing any additional configuration
     *
     * @access public
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function __construct(&$parser, $conf)
    {
        $defaults = array('use-beautifier' => 'auto');
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
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function display()
    {
        if ($this->parseData === false) {
            // invalid data source
            return;
        }

        $version    = isset($this->conf['xml']['version'])
                    ? $this->conf['xml']['version'] : '1.0';
        $encoding   = isset($this->conf['xml']['encoding'])
                    ? $this->conf['xml']['encoding'] : 'UTF-8';
        $standalone = isset($this->conf['xml']['standalone'])
                    ? $this->conf['xml']['standalone'] : null;

        $msg  = XML_Util::getXMLDeclaration($version, $encoding, $standalone);
        $msg .= PHP_EOL;
        $msg .= XML_Util::createStartElement('pci',
                                       array('version' => '1.9.0'));

        $o = $this->args['output-level'];
        $v = $this->args['verbose'];

        $dataSource = $this->_parser->dataSource['dataSource'];
        $dataType   = $this->_parser->dataSource['dataType'];
        $options    = $this->_parser->options;

        if ($dataType == 'directory'
            || $dataType == 'array'
            || $dataType == 'file') {
            // parsing a directory or a list of files, chunks of code

            if ($options['is_string'] == false) {
                if ($dataType == 'directory') {
                    // print <dir> tag
                    $tag = array('qname' => 'dir',
                                 'content' => dirname($dataSource[0]));
                } else {
                    // print <file> tag
                    $tag = array('qname' => 'file',
                                 'content' => $dataSource[0]);
                }
                $msg .= XML_Util::createTagFromArray($tag);
                $msg .= PHP_EOL;
            }

            // print global <version> tag
            if ($o & 16) {
                if (empty($this->parseData['max_version'])) {
                    $attr = array();
                } else {
                    $attr = array('max' => $this->parseData['max_version']);
                }
                $tag  = array('qname' => 'version',
                              'attributes' => $attr,
                              'content' => $this->parseData['version']);
                $msg .= XML_Util::createTagFromArray($tag);
                $msg .= PHP_EOL;
            }

            // print global <conditions> tag group
            if ($o & 1) {
                $msg .= $this->_printTagList($this->parseData['cond_code'],
                                             'condition');
            }
            // print global <extensions> tag group
            if ($o & 2) {
                $msg .= $this->_printTagList($this->parseData['extensions'],
                                             'extension');
            }
            // print global <constants> tag group
            if ($o & 4) {
                $msg .= $this->_printTagList($this->parseData['constants'],
                                             'constant');
            }
            // print global <tokens> tag group
            if ($o & 8) {
                $msg .= $this->_printTagList($this->parseData['tokens'],
                                             'token');
            }

            // print global <ignored> tag group
            $msg .= XML_Util::createStartElement('ignored');
            $msg .= PHP_EOL;
            // with children groups <files>, <functions>, <extensions>, <constants>
            $ignored = array('file' => $this->parseData['ignored_files'],
                             'function' => $this->parseData['ignored_functions'],
                             'extension' => $this->parseData['ignored_extensions'],
                             'constant' => $this->parseData['ignored_constants']);
            foreach ($ignored as $tag => $data) {
                $msg .= $this->_printTagList($data, $tag);
            }
            $msg .= XML_Util::createEndElement('ignored');
            $msg .= PHP_EOL;

            // remove summary data
            unset($this->parseData['ignored_files']);
            unset($this->parseData['ignored_functions']);
            unset($this->parseData['ignored_extensions']);
            unset($this->parseData['ignored_constants']);
            unset($this->parseData['max_version']);
            unset($this->parseData['version']);
            unset($this->parseData['classes']);
            unset($this->parseData['functions']);
            unset($this->parseData['extensions']);
            unset($this->parseData['constants']);
            unset($this->parseData['tokens']);
            unset($this->parseData['cond_code']);

            if ($v & 4 || $options['debug'] == true) {
                // print local <functions> tag group
                $msg .= $this->_printTagList($this->parseData, 'function');

                $entries = array_keys($this->parseData);
                foreach ($entries as $k) {
                    if (is_numeric($k{0})) {
                        unset($this->parseData[$k]);
                    }
                }
            }

            if ($dataType == 'file') {
                // parsing a single file
                $files = array($dataSource[0] => $this->parseData);
            } else {
                $files = $this->parseData;
            }
        } else {
            // ... or a chunk of code (string)
            $files = array($this->parseData);
        }

        if ($this->args['summarize'] === false
            && count($files) > 1) {

            if ($options['is_string'] == false) {
                // print <files> tag group
                $msg .= XML_Util::createStartElement('files',
                                               array('count' => count($files)));
                $msg .= PHP_EOL;
            }

            foreach ($files as $file => $this->parseData) {
                if ($options['is_string'] == true) {
                    $msg .= XML_Util::createStartElement('string',
                                                   array('name' => $file));
                } else {
                    // print local <file> tag
                    $msg .= XML_Util::createStartElement('file',
                                                   array('name' => $file));
                }
                $msg .= PHP_EOL;

                // print local <version> tag
                if ($o & 16) {
                    if (empty($this->parseData['max_version'])) {
                        $attr = array();
                    } else {
                        $attr = array('max' => $this->parseData['max_version']);
                    }
                    $tag  = array('qname' => 'version',
                                  'attributes' => $attr,
                                  'content' => $this->parseData['version']);
                    $msg .= XML_Util::createTagFromArray($tag);
                    $msg .= PHP_EOL;
                }

                // print local <conditions> tag group
                if ($o & 1) {
                    $msg .= $this->_printTagList($this->parseData['cond_code'],
                                                 'condition');
                }
                // print local <extensions> tag group
                if ($o & 2) {
                    $msg .= $this->_printTagList($this->parseData['extensions'],
                                                 'extension');
                }
                // print local <constants> tag group
                if ($o & 4) {
                    $msg .= $this->_printTagList($this->parseData['constants'],
                                                 'constant');
                }
                // print local <tokens> tag group
                if ($o & 8) {
                    $msg .= $this->_printTagList($this->parseData['tokens'],
                                                 'token');
                }

                // print local <ignored> tag group
                $msg .= XML_Util::createStartElement('ignored');
                $msg .= PHP_EOL;
                // with children groups <functions>, <extensions>, <constants>
                $ignored = array(
                    'function' => $this->parseData['ignored_functions'],
                    'extension' => $this->parseData['ignored_extensions'],
                    'constant' => $this->parseData['ignored_constants']
                    );
                foreach ($ignored as $tag => $data) {
                    $msg .= $this->_printTagList($data, $tag);
                }
                $msg .= XML_Util::createEndElement('ignored');
                $msg .= PHP_EOL;

                // extra information only if verbose mode >= 4
                if ($v & 4 || $options['debug'] == true) {
                    unset($this->parseData['ignored_files']);
                    unset($this->parseData['ignored_functions']);
                    unset($this->parseData['ignored_extensions']);
                    unset($this->parseData['ignored_constants']);
                    unset($this->parseData['max_version']);
                    unset($this->parseData['version']);
                    unset($this->parseData['classes']);
                    unset($this->parseData['functions']);
                    unset($this->parseData['extensions']);
                    unset($this->parseData['constants']);
                    unset($this->parseData['tokens']);
                    unset($this->parseData['cond_code']);

                    // print local <functions> tag group
                    $msg .= $this->_printTagList($this->parseData, 'function');
                }

                if ($options['is_string'] == true) {
                    $msg .= XML_Util::createEndElement('string');
                } else {
                    $msg .= XML_Util::createEndElement('file');
                }
                $msg .= PHP_EOL;
            }
            if ($options['is_string'] == false) {
                $msg .= XML_Util::createEndElement('files');
                $msg .= PHP_EOL;
            }
        }
        $msg .= XML_Util::createEndElement('pci');
        $msg .= PHP_EOL;

        if (strtolower($this->conf['use-beautifier']) != 'no') {
            // try to see if we can improve XML render
            $beautifier = 'XML/Beautifier.php';
            if (PHP_CompatInfo_Renderer::isIncludable($beautifier)) {
                include_once $beautifier;
                $def = array();
                $opt = isset($this->conf['beautifier'])
                     ? $this->conf['beautifier'] : $def;
                $fmt = new XML_Beautifier($opt);
                $msg = $fmt->formatString($msg);
            }
        }

        echo $msg;
    }

    /**
     * Print a group of same tag in the XML report.
     *
     * Groups list are : extension(s), constant(s), token(s)
     *
     * @param array  $dataSrc Data source
     * @param string $tagName Name of the XML tag
     *
     * @return string
     * @access private
     * @since  version 1.7.0b4 (2008-04-03)
     */
    function _printTagList($dataSrc, $tagName)
    {
        $msg = '';

        if ($tagName == 'function') {
            $c = 0;
            foreach ($dataSrc as $version => $functions) {
                $c += count($functions);
            }
            $attributes = array('count' => $c);
        } elseif ($tagName == 'condition') {
            if ($this->_parser->options['debug'] === true) {
                $c = 0;
                foreach ($dataSrc[1] as $cond => $elements) {
                    $c += count($elements);
                }
                $attributes = array('count' => $c, 'level' => $dataSrc[0]);
            } else {
                $attributes = array('level' => $dataSrc[0]);
            }
        } else {
            $attributes = array('count' => count($dataSrc));
        }

        $msg .= XML_Util::createStartElement($tagName.'s', $attributes);
        $msg .= PHP_EOL;

        if ($tagName == 'function') {
            foreach ($dataSrc as $version => $functions) {
                foreach ($functions as $data) {
                    $attr = array('version' => $version);
                    if (!empty($data['extension'])) {
                        $attr['extension'] = $data['extension'];
                        $attr['pecl']      = $data['pecl'] === true ?
                                                'true' : 'false';
                    }
                    $tag  = array('qname' => $tagName,
                                  'attributes' => $attr,
                                  'content' => $data['function']);
                    $msg .= XML_Util::createTagFromArray($tag);
                    $msg .= PHP_EOL;
                }
            }
        } elseif ($tagName == 'condition') {
            if ($this->_parser->options['debug'] == true) {
                foreach ($dataSrc[1] as $cond => $elements) {
                    $cond = ($cond == 0) ? 1 : ($cond * 2);
                    foreach ($elements as $data) {
                        $tag  = array('qname' => $tagName,
                                      'attributes' => array('level' => $cond),
                                      'content' => $data);
                        $msg .= XML_Util::createTagFromArray($tag);
                        $msg .= PHP_EOL;
                    }
                }
            }
        } else {
            foreach ($dataSrc as $data) {
                $tag  = array('qname' => $tagName,
                              'attributes' => array(),
                              'content' => $data);
                $msg .= XML_Util::createTagFromArray($tag);
                $msg .= PHP_EOL;
            }
        }

        $msg .= XML_Util::createEndElement($tagName.'s');
        $msg .= PHP_EOL;

        return $msg;
    }
}
?>