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
 * + The names of its contributors may not be used to endorse or
 * promote products derived from this software without specific prior written permission.
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
 * @version   SVN: $Id: Tokens.php 31 2007-09-13 10:21:01Z mcorne $
 * @link      http://pear.php.net/package/PHP_DocBlockGenerator
 */

require_once 'DocBlockGenerator/Block.php';
require_once 'CompatInfo.php';

/**
 * Extraction of the PHP objects/tokens of the source code and creation of the DocBlocks
 *
 * @category  PHP
 * @package   PHP_DocBlockGenerator
 * @author    Michel Corne <mcorne@yahoo.com>
 * @copyright 2007 Michel Corne
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_DocBlockGenerator
 */

class PHP_DocBlockGenerator_Tokens
{
    /**
     * The PHP_DocBlockGenerator_Block instance
     *
     * @var    object
     * @access private
     */
    private $block;

    /**
     * The source code End-Of-Line character
     *
     * EOL = "\r\n" for DOS/Windows, "\r" for MAC, "\n" for Unix
     *
     * @var    string
     * @access public
     */
    public $eol;

    /**
     * Flag indicating if the current token has a DocBlock or not
     *
     * @var    boolean
     * @access public
     */
    public $hasBlock;

    /**
     * The current token information,  including the token type, access type...
     *
     * @var    array
     * @access public
     */
    public $id;

    /**
     * Flag indicating if the current token is within a class
     *
     * @var    integer
     * @access public
     */
    public $inClass;

    /**
     * Flag indicating if the class is an interface
     *
     * @var    boolean
     * @access public
     */
    public $isInterface;
    /**
     * The none relevant PHP code tokens
     *
     * @var    array
     * @access private
     */
    private $noPHPCode = array(// /
        T_WHITESPACE, T_ENCAPSED_AND_WHITESPACE, // spaces
        T_DOC_COMMENT, T_COMMENT, // comments
        T_INLINE_HTML, // none PHP code
        );

    /**
     * Source code tokens excluding none relevant PHP code tokens
     *
     * @var    array
     * @access private
     */
    private $phpTokens = array();

    /**
     * The file PHP version
     *
     * @var    string
     * @access public
     */
    public $phpVersion = '';

    /**
     * Source code tokens
     *
     * @var    array
     * @access private
     */
    private $tokens = array();

    /**
     * The class constructor
     *
     * @return void
     * @access public
     */
    public function __construct()
    {
        $this->block = new PHP_DocBlockGenerator_Block($this);
        $this->info = new PHP_CompatInfo('null');
    }

    /**
     * Gets the current token
     *
     * @param  integer $id the token identification number
     * @return array   the token type and value, false if invalid ID
     * @access public
     */
    public function get($id)
    {
        return $this->isValid($id)? $this->tokens[$id] : false;
    }

    /**
     * Gets all the source code tokens and tidies them
     *
     * @param  string  $data the source code
     * @return boolean true on success, false on failure
     * @access private
     */
    private function getAll($data)
    {
        // extracts the tokens, tidies the tokens, extracts the PHP code only tokens
        $this->tokens = token_get_all($data) and array_walk($this->tokens, array($this, 'tidy')) and
        $this->phpTokens = array_filter($this->tokens, array($this, 'isPHPCode'));
        // /
        // dump of all the tokens: un-comment for debugging purposes only
        //file_put_contents('tokens.txt', var_export($this->tokens, true));
        // /
        return (bool)$this->tokens;
    }

    /**
     * Determines the source code End Of Line character
     *
     * EOL = "\r\n" for DOS/Windows, "\r" for MAC, "\n" for Unix
     *
     * @access private
     */
    private function getEOL($data)
    {
        if (strpos($data, "\r\n") !== false) { // DOS/Windows EOL
            $this->eol = "\r\n";
        } else if (strpos($data, "\r") !== false) { // MAC EOL
            $this->eol = "\r";
        } else { // Unix EOL
            $this->eol = "\n";
        }
    }

    /**
     * Verifies that the token is a valid source code token
     *
     * @param  integer $id the token identification number
     * @return boolean true if valid, false otherwise
     * @access private
     */
    private function isValid($id)
    {
        return isset($this->tokens[$id]);
    }

    /**
     * Checks if the token is relevant PHP code, excluding spaces and comments.
     *
     * This is an array_filter() callback function.
     *
     * @param  array   $token the token
     * @return boolean true if relevant PHP code, false otherwise
     * @access private
     */
    private function isPHPCode($token)
    {
        return !in_array($token['type'], $this->noPHPCode);
    }

    /**
     * Processes the source code tokens
     *
     * Gets all the source code tokens. Determines the source code EOL.
     * Determines the file PHP version. Initializes the Page-level tags.
     * Parses the file tokens and creates their DocBlocks.
     * Re-assembles all the tokens with their DocBlocks.
     *
     * @param  string  $data  the source code
     * @param  array   $param the tags/parameters values
     * @return boolean true on success, false on failure
     * @access public
     */
    public function process($data, $param)
    {
        // get all the tokens
        if ($result = $this->getAll($data)) {
            // determines the source code EOL
            $this->getEOL($data);
            // extracts all the tokens
            $allTokens = $this->slice();
            // initializes the Page-level tags
            $this->block->init($param);

            $this->hasBlock = 0;
            $this->id = array();
            $this->inClass = null;
            $this->isInterface = false;
            $inFunct = null;
            $openTagID = null;
            $isPageBlock = false;

            foreach($this->tokens as $id => $token) {
                $value = $token['value'];

                switch ($type = $token['type']) {
                    case '{': // class or function opening curly brace
                    // note: none matching open and close curly braces will cause issues
                    case T_CURLY_OPEN:
                    case T_DOLLAR_OPEN_CURLY_BRACES: // ${
                        // counting braces within the function, and the class
                        is_null($inFunct) or $inFunct++;
                        is_null($this->inClass) or $this->inClass++;
                        break;

                    case '}': // class or function closing curly brace
                        // reached end of the function or class
                        is_null($inFunct) or --$inFunct or $inFunct = null;
                        is_null($this->inClass) or --$this->inClass or
                        $this->inClass = null or $this->isInterface = false;
                        break;

                    case T_ABSTRACT: // abstract, class or function abstraction
                    case T_FINAL: // final class or function
                        $this->id[$type] = $id;
                        break;

                    case T_CONST: // const
                        // sets the const DocBlock
                        is_null($this->inClass) or $this->block->setConst($id);
                        break;

                    case T_CONSTANT_ENCAPSED_STRING: // "foo" or 'bar' string syntax
                        // sets the include DocBlock
                        isset($this->id[T_INCLUDE]) and $this->block->build($this->id[T_INCLUDE]);
                        break;

                    case T_DOC_COMMENT: // /**     */ PHPDoc style comments (PHP 5 only)
                        // spots the page block, realigns the DocBlock tags
                        $this->hasBlock = true;
                        $isPageBlock or $isPageBlock = (strpos($value, '@package') !== false);
                        $this->block->realign($id, $value);
                        break;

                    case T_FUNCTION: // function or cfunction functions
                        $inFunct = 0;
                        $this->block->setFunction($id); // sets function DocBlock
                        break;

                    case T_INCLUDE: // include()
                    case T_INCLUDE_ONCE: // include_once()
                    case T_REQUIRE: // require()
                    case T_REQUIRE_ONCE: // require_once()
                        $type = T_INCLUDE;
                    case T_GLOBAL: // global variable scope
                        // only capturing includes and globals outside of classes and functions
                        is_null($this->inClass) and is_null($inFunct) and $this->id[$type] = $id;
                        break;

                    case T_INTERFACE: // interface, Object Interface
                        $this->isInterface = true;
                    case T_CLASS: // class, classes and objects
                        $this->inClass = 0;
                        $this->block->setClass($id);
                        break;

                    case T_OPEN_TAG: // <?php, <? or <%
                        $openTagID = $id;
                        break;

                    case T_PRIVATE: // private classes and objects. PHP 5 only.
                    case T_PROTECTED: // protected classes and objects. PHP 5 only.
                    case T_PUBLIC: // public classes and objects. PHP 5 only.
                        // captures the class visibilty
                        is_null($this->inClass) or $this->id['access'] = array($id, $value);
                        break;

                    case T_STATIC: // static variable scope
                        if (!is_null($this->inClass) and is_null($inFunct)) {
                            // captures the static property within a class and
							// outside of a function
                            $this->id[$type] = $id;
                        }
                        break;

                    case T_VAR: // var classes and objects
                        // captures the class property
                        is_null($this->inClass) or $this->id[$type] = $id;
                        break;

                    case T_STRING:
                        // sets define DocBlock
                        $value == 'define' and $this->block->setDefine($id);
                        break;

                    case T_VARIABLE: // $foo variables
                        if (isset($this->id[T_INCLUDE])) {
                            // including a variable instead of a string, sets the include DocBlock
                            $this->block->build($this->id[T_INCLUDE]);
                        } else if (isset($this->id[T_GLOBAL])) {
                            // a global variable,  sets the global DocBlock, e.g. global $var
                            $this->block->setGlobal($this->id[T_GLOBAL], $token, $allTokens);
                        } else if (is_null($this->inClass) and is_null($inFunct) and $value == '$GLOBALS') {
                            // a GLOBALS variable outside of a class and function
                            // sets the global variable DocBlock, e.g. $GLOBALS['foo']
                            $this->block->setGLOBALS($id, $allTokens);
                        } else if (isset($this->id['access']) or
						    isset($this->id[T_VAR])or isset($this->id[T_STATIC])) {
                            // a class property, sets the class variable
                            $this->block->setVar($token);
                        }
                        break;
                }
            }
            if (!$isPageBlock) {
                // no Page-level DocBlock, determines the PHP version, sets the Page-level DocBlock
                $info = $this->info->parseString($data);
                list($this->phpVersion) = explode('.', $info['version']);
                $this->block->setPage($openTagID);
            }
            // re-assembles all the tokens
            $result = $this->putAll();
        }

        return $result;
    }

    /**
     * Re-assembles the source code tokens into a string
     *
     * @return boolean true on success, false on failure
     * @access private
     */
    private function putAll()
    {
        // creates the array_reduce callback, reduces the array to a string made of token values
        $callback = create_function('$string, $token', 'return $string .= $token[\'value\'];');

        return array_reduce($this->tokens, $callback);
    }

    /**
     * Sets the token value
     *
     * @param  integer $id    the token identification number
     * @param  string  $value the token value
     * @return boolean true if the token is valid, false otherwise
     * @access public
     */
    public function set($id, $value)
    {
        $isValid = $this->isValid($id) and $this->tokens[$id]['value'] = $value;
        return $isValid;
    }

    /**
     * Slices a subset of tokens
     *
     * @param  integer $offset       the token identification number to start looking at
     * @param  string  $openBracket  the delimiter to start slicing at
     * @param  mixed   $closeBracket the delimiter to stop slicing at
     * @param  integer $bracketCount to set to 1 if the offset is past the first delimiter
     * @return array   the sliced tokens
     * @access private
     */
    public function slice($offset = 0, $openBracket = null, $closeBracket = null, $bracketCount = null)
    {
        $tokens = array();
        foreach($this->phpTokens as $token) {
            if ($token['id'] >= $offset) {
                // processes tokens after the offset
                $tokens[] = $token; // captures the token
                if ($openBracket !== null) {
                    // only captures tokens delimited by the brackets
                    // captures all types of open curly braces
                    $openBracket == '{' and
                    in_array($token['type'], array(T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES)) and
                    $token['type'] = '{';
                    // counts enclosed opening and closing brackets
                    $token['type'] == $openBracket and $bracketCount++ or
                    $token['type'] == $closeBracket and $bracketCount--;
                    if ($bracketCount === 0) {
                        // reached the last closing bracket
                        break;
                    }
                }
            }
        }
        return $tokens;
    }

    /**
     * Tidies a token
     *
     * An array_walk() callback function.
     *
     * @param  array   &$token the token
     * @param  integer $id     the token identification number
     * @return void
     * @access private
     * @see    self::getAll()
     */
    private function tidy(&$token, $id)
    {
        if (is_array($token)) {
            // a PHP token
            // extracts the token type, e.g. T_CONSTANT_ENCAPSED_STRING
            $tidied['type'] = current($token);
            $tidied['type'] == T_PAAMAYIM_NEKUDOTAYIM and $tidied['type'] = T_DOUBLE_COLON;
            // extracts the token value, e.g. "foo"
            $tidied['value'] = next($token);
            // captures the token type as a string, note: only useful for debugging purposes
            $tidied['name'] = token_name($tidied['type']);
        } else {
            // a single character, e.g. ";"
            $tidied['type'] = $tidied['value'] = $token;
        }
        // captures the token position
        $tidied['id'] = $id;
        $token = $tidied;
    }
}

?>
