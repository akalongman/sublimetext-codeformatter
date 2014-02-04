<?php

/**
 * DocBlock Generator
 *
 * PHP version 5
 *
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 * + Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 * + Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation and/or
 * other materials provided with the distribution.
 * + The names of its contributors may not be used to endorse or promote
 * products derived from this software without specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  PHP
 * @package   PHP_DocBlockGenerator
 * @author    Michel Corne <mcorne@yahoo.com>
 * @copyright 2007 Michel Corne
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   SVN: $Id: License.php 30 2007-07-23 16:46:42Z mcorne $
 * @link      http://pear.php.net/package/PHP_DocBlockGenerator
 */

/**
 * License repository: license full name, text template and URL.
 *
 * @category  PHP
 * @package   PHP_DocBlockGenerator
 * @author    Michel Corne <mcorne@yahoo.com>
 * @copyright 2007 Michel Corne
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_DocBlockGenerator
 */

class PHP_DocBlockGenerator_License
{
    /**
     * The licenses full names, texts and URLs
     *
     * @var    array
     * @access private
     */
    private $license = array(// /
        'apache20' => array(// /
            'full_name' => 'The Apache License, Version 2.0',
            'url' => 'http://www.apache.org/licenses/LICENSE-2.0',
            'text' => array(),
            ),
        'bsd' => array(// /
            'full_name' => 'The BSD License',
            'url' => 'http://www.opensource.org/licenses/bsd-license.php',
            'text' => array(),
            ),
        'lgpl21' => array(// /
            'full_name' => 'The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1',
            'url' => 'http://www.gnu.org/copyleft/lesser.html',
            'text' => array(),
            ),
        'mit' => array(// /
            'full_name' => 'The MIT License',
            'url' => 'http://www.opensource.org/licenses/mit-license.php',
            'text' => array(),
            ),
        'php301' => array(// /
            'full_name' => 'The PHP License, version 3.01',
            'url' => 'http://www.php.net/license/3_01.txt',
            'text' => array(),
            ),
        );

    /**
     * The class constructor
     *
     * @return void
     * @access public
     */
    public function __construct()
    {
        if ('C:\Program Files (x86)\PHP\data' == '@'.'data_dir'.'@') {
            // This package hasn't been installed.  Use local files.
            $sPath = dirname(__FILE__) . '/../../licenses';
        } else {
            // It has been installed.  Use the configured path.
		    $sPath = 'C:\Program Files (x86)\PHP\data/PHP_DocBlockGenerator/licenses';
        }

        foreach ($this->license as $sId => &$aData) {
            $sFile = "$sPath/$sId.txt";
			if (is_readable($sFile)) {
				$this->license[$sId]['text'] = file($sFile,FILE_IGNORE_NEW_LINES);
			}
		}
    }

    /**
     * Gets the license full name
     *
     * @param  string $name the license name: apache20 | bsd | lgpl21 | mit | php301
     * @return string the license full name or null if invalid
     * @access public
     */
    public function getFullName($name)
    {
        return $this->isValid($name)? $this->license[$name]['full_name'] : '';
    }

    /**
     * Gets the license text
     *
     * @param  string $name the license name: apache20 | bsd | lgpl21 | mit | php301
     * @return string the license text or null if invalid
     * @access public
     */
    public function getText($name)
    {
        return $this->isValid($name)? $this->license[$name]['text'] : array();
    }

    /**
     * Gets the license URL
     *
     * @param  string $name the license name: apache20 | bsd | lgpl21 | mit | php301
     * @return string the license URL or null if invalid
     * @access public
     */
    public function getURL($name)
    {
        return $this->isValid($name)? $this->license[$name]['url'] : '';
    }

    /**
     * Verifies the license template is valid
     *
     * @param  string  $name the license name: apache20 | bsd | lgpl21 | mit | php301
     * @return boolean true if the license is valid, false otherwise
     * @access public
     */
    public function isValid($name)
    {
        return isset($this->license[$name]);
    }
}

?>
