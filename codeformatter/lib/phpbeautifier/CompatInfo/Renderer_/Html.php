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
 * @version  CVS: $Id: Html.php,v 1.14 2009/01/02 10:18:47 farell Exp $
 * @link     http://pear.php.net/package/PHP_CompatInfo
 * @since    File available since Release 1.8.0b4
 */

require_once 'HTML/Table.php';
require_once 'HTML/CSS.php';

/**
 * Html renderer for PHP_CompatInfo component.
 *
 * The PHP_CompatInfo_Renderer_Html class is a concrete implementation
 * of PHP_CompatInfo_Renderer abstract class. It simply display results
 * as web/html content with help of PEAR::Html_Table
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version  Release: 1.9.0
 * @link     http://pear.php.net/package/PHP_CompatInfo
 * @since    Class available since Release 1.8.0b4
 */
class PHP_CompatInfo_Renderer_Html extends PHP_CompatInfo_Renderer
{
    /**
     * Style sheet for the custom layout
     *
     * @var    string
     * @access public
     * @since  1.8.0b4
     */
    var $css;

    /**
     * Html Renderer Class constructor (ZE1) for PHP4
     *
     * @param object &$parser Instance of the parser (model of MVC pattern)
     * @param array  $conf    A hash containing any additional configuration
     *
     * @access public
     * @since  version 1.8.0b4 (2008-06-18)
     */
    function PHP_CompatInfo_Renderer_Html(&$parser, $conf)
    {
        $this->__construct($parser, $conf);
    }

    /**
     * Html Renderer Class constructor (ZE2) for PHP5+
     *
     * @param object &$parser Instance of the parser (model of MVC pattern)
     * @param array  $conf    A hash containing any additional configuration
     *
     * @access public
     * @since  version 1.8.0b4 (2008-06-18)
     */
    function __construct(&$parser, $conf)
    {
        $defaults = array('tdwidth' => array(18, 4, 2, 7, 13));
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
        $o    = $this->args['output-level'];
        $info = $this->parseData;

        if ($info == false) {
            // protect against invalid data source
            print 'Invalid data source';
            return;
        }

        $src = $this->_parser->dataSource;
        if ($src['dataType'] == 'directory') {
            $dir      = $src['dataSource'];
            $hdr_col1 = 'Directory';
        } elseif ($src['dataType'] == 'file') {
            $file     = $src['dataSource'];
            $hdr_col1 = 'File';
        } else {
            $string   = $src['dataSource'];
            $hdr_col1 = 'Source code';
        }

        $dataTable = new HTML_Table();
        $thead     =& $dataTable->getHeader();
        $tbody     =& $dataTable->getBody();
        $tfoot     =& $dataTable->getFooter();

        $hdr = array($hdr_col1);
        $atr = array('scope="col"');
        if ($o & 16) {
            $hdr[] = 'Version';
            $atr[] = 'scope="col"';
        }
        if ($o & 1) {
            $hdr[] = 'C';
            $atr[] = 'scope="col"';
        }
        if ($o & 2) {
            $hdr[] = 'Extensions';
            $atr[] = 'scope="col"';
        }
        if ($o & 4) {
            if ($o & 8) {
                $hdr[] = 'Constants/Tokens';
                $atr[] = 'scope="col"';
            } else {
                $hdr[] = 'Constants';
                $atr[] = 'scope="col"';
            }
        } else {
            if ($o & 8) {
                $hdr[] = 'Tokens';
                $atr[] = 'scope="col"';
            }
        }

        $thead->addRow($hdr, $atr);

        $ext   = implode("<br/>", $info['extensions']);
        $const = implode("<br/>", array_merge($info['constants'], $info['tokens']));
        if (isset($dir)) {
            $ds    = DIRECTORY_SEPARATOR;
            $dir   = str_replace(array('\\', '/'), $ds, $dir);
            $title = $src['dataCount'] . ' file';
            if ($src['dataCount'] > 1) {
                $title .= 's'; // plural
            }
        } elseif (isset($file)) {
            $title = '1 file';
        } else {
            $title = '1 chunk of code';
        }
        $data = array('Summary: '. $title . ' parsed');

        if ($o & 16) {
            if (empty($info['max_version'])) {
                $data[] = $info['version'];
            } else {
                $data[] = implode("<br/>", array($info['version'],
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
                $data[] = implode("<br/>", $info['constants']);
            }
        } else {
            if ($o & 8) {
                $data[] = implode("<br/>", $info['tokens']);
            }
        }

        // summary informations
        $tfoot->addRow($data);

        // summarize : print only summary for directory without files details
        if ($this->args['summarize'] === false && isset($dir)) {
            // display result of parsing multiple files

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
                $ext   = implode("<br/>", $info['extensions']);
                $const = implode("<br/>", array_merge($info['constants'],
                                                      $info['tokens']));

                $file = str_replace(array('\\', '/'), $ds, $file);

                $path = dirname($file);
                $tbody->addRow(array($path), array('class' => 'dirname',
                                                   'colspan' => count($hdr)));

                $data = array(basename($file));
                if ($o & 16) {
                    if (empty($info['max_version'])) {
                        $data[] = $info['version'];
                    } else {
                        $data[] = implode("<br/>", array($info['version'],
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
                        $data[] = implode("<br/>", $info['constants']);
                    }
                } else {
                    if ($o & 8) {
                        $data[] = implode("<br/>", $info['tokens']);
                    }
                }

                $tbody->addRow($data);
            }
        } elseif ($this->args['summarize'] === false && !isset($dir)) {
            // display result of parsing a single file, or a chunk of code
            if (isset($file)) {
                $path = dirname($file);
            } else {
                $path = '.';
            }
            $tbody->addRow(array($path), array('class' => 'dirname',
                                               'colspan' => count($hdr)));
            if (isset($file)) {
                $data[0] = basename($file);
            } else {
                $data[0] = htmlspecialchars('<?php ... ?>');
            }
            $tbody->addRow($data);
        } else {
            // display only result summary of parsing a data source
            if (isset($dir)) {
                $path = dirname($dir[0]);
            } elseif (isset($file)) {
                $path = dirname($file);
            } else {
                $path = '.';
            }
            $tbody->addRow(array($path), array('class' => 'dirname',
                                               'colspan' => count($hdr)));
        }

        $evenRow = array('class' => 'even');
        $oddRow  = null;
        $tbody->altRowAttributes(1, $evenRow, $oddRow, true);

        echo $this->toHtml($dataTable);
    }

    /**
     * Returns the custom style sheet
     *
     * Returns the custom style sheet to use for layout
     *
     * @param int   $destination (optional) Destination of css content
     * @param mixed $extra       (optional) Additional data depending of destination
     *
     * @return mixed
     * @access public
     * @since  version 1.8.0b4 (2008-06-18)
     */
    function getStyleSheet($destination = 1, $extra = null)
    {
        $css = new HTML_CSS();
        $css->parseFile($this->css);

        $tdw = $this->conf['tdwidth'];
        $em  = array_sum($tdw);
        $td  = 'td';
        $o   = $this->args['output-level'];

        $css->setStyle('.outer td.dirname', 'width', $em.'em');
        if ($o & 16) {
            $td .= '+td';
            $css->setStyle('.outer '.$td, 'width', $tdw[1].'em');
            $em = $em - $tdw[1];
        }
        if ($o & 1) {
            $td .= '+td';
            $css->setStyle('.outer '.$td, 'width', $tdw[2].'em');
            $em = $em - $tdw[2];
        }
        if ($o & 2) {
            $td .= '+td';
            $css->setStyle('.outer '.$td, 'width', $tdw[3].'em');
            $em = $em - $tdw[3];
        }
        if ($o & 12) {
            $td .= '+td';
            $css->setStyle('.outer '.$td, 'width', $tdw[4].'em');
            $em = $em - $tdw[4];
        }
        $css->setStyle('.outer td', 'width', $em .'em');

        $styles = '';

        switch ($destination) {
        case 1:  // embedded styles
            $styles = $css->toString();
            break;
        case 2:  // save only to file
            $css->toFile($extra);
            $styles = $extra;
            break;
        case 3:  // apply a user function
            if (is_callable($extra)) {
                $styles = call_user_func_array($extra, array($css));
            }
            break;
        default:
            break;
        }
        return $styles;
    }

    /**
     * Set a custom style sheet
     *
     * Set a custom style sheet to use your own styles
     *
     * @param string $css (optional) File to read user-defined styles from
     *
     * @return bool    True if custom styles, false if default styles applied
     * @access public
     * @since  version 1.8.0b4 (2008-06-18)
     */
    function setStyleSheet($css = null)
    {
        // default stylesheet is into package data directory
        if (!isset($css)) {
            $css = 'C:\Program Files (x86)\PHP\data' . DIRECTORY_SEPARATOR
                 . 'PHP_CompatInfo' . DIRECTORY_SEPARATOR
                 . 'pci.css';
        }

        $res = isset($css) && file_exists($css);
        if ($res) {
            $this->css = $css;
        }
        return $res;
    }

    /**
     * Returns HTML code
     *
     * Returns HTML code of parsing result
     *
     * @param object $obj instance of HTML_Table
     *
     * @access public
     * @return string
     * @since  version 1.8.0b4 (2008-06-18)
     */
    function toHtml($obj)
    {
        if (!isset($this->css)) {
            // when no user-styles defined, used the default values
            $this->setStyleSheet();
        }
        $styles = $this->getStyleSheet();

        $body = $obj->toHtml();

        $html = <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3c.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>PHP_CompatInfo</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<style type="text/css">
<!--
$styles
 -->
</style>
</head>
<body>
<div class="outer">
<div class="inner">
$body
</div>
</div>
</body>
</html>
HTML;
        return $html;
    }
}
?>