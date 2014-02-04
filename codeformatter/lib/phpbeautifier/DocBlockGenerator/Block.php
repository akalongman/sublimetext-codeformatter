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
 * @version   SVN: $Id: Block.php 31 2007-09-13 10:21:01Z mcorne $
 * @link      http://pear.php.net/package/PHP_DocBlockGenerator
 */

require_once 'PHP/DocBlockGenerator/Align.php';

/**
 * Description for require_once
 */
require_once 'PHP/DocBlockGenerator/License.php';

/**
 * Description for require_once
 */
require_once 'PHP/DocBlockGenerator/Type.php';

/**
 * Creation of DocBlocks for PHP objects : includes, defines, globals, functions, classes, vars
 *
 * This class is only meant to be instanciated by the PHP_DocBlockGenerator_Tokens class.
 * The package name is either passed as a parameter, or determined from
 * the name of the first class of the first of all files to be processed.
 *
 * @category  PHP
 * @package   PHP_DocBlockGenerator
 * @author    Michel Corne <mcorne@yahoo.com>
 * @copyright 2007 Michel Corne
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_DocBlockGenerator
 * @see       class PHP_DocBlockGenerator_Tokens
 */

class PHP_DocBlockGenerator_Block
{
    /**
     * The default path to the package link
     */
    const packageLink = 'http://pear.php.net/package/';

    /**
     * The default package name
     */
    const packageName = 'PackageName';

    /**
     * The PHP_DocBlockGenerator_Align instance
     *
     * @var    object
     * @access private
     */
    private $align;

    /**
     * The align-tags-only flag, e.g. DOS/Unix command option "-A"
     *
     * @var    boolean
     * @access private
     */
    private $alignOnly;

    /**
     * The description placeholders
     *
     * @var    array
     * @access private
     */
    private $description = array(// /
        'default' => 'Description for %s',
        'exception' => 'Exception description (if any) ...',
        'long' => 'Long description (if any) ...',
        'param' => 'Parameter description (if any) ...',
        'return' => 'Return description (if any) ...',
        'short' => 'Short description for %s',
        );

    /**
     * The PHP_DocBlockGenerator_License instance
     *
     * @var    object
     * @access private
     */
    private $license;

    /**
     * Description for private
     *
     * @var    array
     * @access private
     */
    private $licenseText = array('The license text...');

    /**
     * The package name
     *
     * @var    string
     * @access private
     */
    private $packageName = self::packageName;

    /**
     * The page-level tags
     *
     * @var    array
     * @access private
     */
    private $pageTags;

    /**
     * The page-level tags placeholders and defaults
     *
     * @var    array
     * @access private
     */
    private $pageTagDefaults = array(// /
        'author' => 'Author\'s name',
        'category' => 'CategoryName',
        'email' => 'author@mail.com',
        'license' => 'bsd',
        'link' => null,
        'package' => null,
        'see' => 'References to other sections (if any)...',
        'version' => null,
        'year' => null,
        );

    /**
     * The PHP version text
     *
     * @var    array
     * @access private
     */
    private $phpVersion = array(// /
		0 => 'PHP version unknown',
        '3' => 'PHP version 3',
        '4' => 'PHP versions 4 and 5',
        '5' => 'PHP version 5',
        '6' => 'PHP version 6',
        );

    /**
     * The PHP_DocBlockGenerator_Tokens instance
     *
     * @var    object
     * @access private
     */
    private $tokens;

    /**
     * The PHP_DocBlockGenerator_Type instance
     *
     * @var    object
     * @access private
     */
    private $type;

    /**
     * The class constructor
     *
     * Sets properties that cannot be pre-set.
     * This class is only meant to be instanciated by the PHP_DocBlockGenerator_Tokens class.
     *
     * @param  object $tokens the PHP_DocBlockGenerator_Tokens instance
     * @return void
     * @access public
     */
    public function __construct($tokens)
    {
        $this->align = new PHP_DocBlockGenerator_Align();
        $this->license = new PHP_DocBlockGenerator_License();
        $this->type = new PHP_DocBlockGenerator_Type();
        // sets the default time zone
        date_default_timezone_set('UTC');
        // defaults the copyright year to the current year
        $this->pageTagDefaults['year'] = date('Y');
        // sets the default version to CVS
        $this->pageTagDefaults['version'] = 'cvs';
        // captures the calling PHP_DocBlockGenerator_Tokens instance
        $this->tokens = $tokens;
    }

    /**
     * Builds the DocBlock
     *
     * Sets the default DocBlock description. Builds the DocBlock frame
     * with "*" (stars). Aligns the tags. Shifts the DocBlock 4 characters
     * to the right for class methods and properties. Adds the DocBlock to the
     * corresponding PHP object.
     *
     * @param  integer $id             the token identification number
     * @param  array   $block          the DocBlock lines: description + tags
     * @param  boolean $setDefautlDesc sets the default description if true
     * @param  string  $name           the DocBlock name, e.g. "file"
     * @return void
     * @access public
     */
    public function build($id, $block = array(), $setDefautlDesc = true, $name = '')
    {
        if (!$this->tokens->hasBlock and !$this->alignOnly) {
            // there is no DocBlock for this token
            $setDefautlDesc and array_unshift($block, $this->description['default']);
            $name or $token = $this->tokens->get($id) and $name = $token['value'];
            // encloses the DocBlock with "/**" ... " /*", prefixes parameters with " * "
            $built = "{$this->tokens->eol}/**{$this->tokens->eol} * ";
            $built .= implode("{$this->tokens->eol} * ", $block);
            $built .= "{$this->tokens->eol} */";
            // adds the token name in the DocBlock description
            $built = sprintf($built, $name);
            // indents block with 4 spaces if the token is within a class
            $this->tokens->inClass and $name != 'class' and
            $built = str_replace($this->tokens->eol, "{$this->tokens->eol}    ", $built);

            $built = $this->align->alignTags($built);

            if ($token = $this->tokens->get($id - 1) and $token['type'] == T_WHITESPACE) {
                // whitespace(s) preceeds the token
                $value = $token['value'];

                if (($lastEOL = strrpos($value, "\r")) !== false or ($lastEOL = strrpos($value, "\n")) !== false) {
                    // a EOL in the whitespace(s), prepends another EOL to the DocBlock
                    (($firstEOL = strpos($value, "\r")) !== false or ($firstEOL = strpos($value, "\n")) !== false) and
                    $firstEOL == $lastEOL and $built = "{$this->tokens->eol}{$built}";
                    // inserts the DocBlock before the EOL
                    $built = substr_replace($value, $built, $lastEOL, 0);
                } else {
                    // there is no EOL in the whitespace sequence, inserts the DocBlock before the whitespace(s)
                    $built = "{$this->tokens->eol}{$built}{$this->tokens->eol}{$value}";
                }

                $this->tokens->set($id - 1, $built);
            } else if ($token = $this->tokens->get($id) and $token['type'] == T_OPEN_TAG) {
                // the PHP open tag, appends the DocBlock
                $this->tokens->set($id, $token['value'] . "{$built}{$this->tokens->eol}");
            } else {
                // there is no whitespace preceeding the token, prepends the DocBlock to the token
                $this->tokens->set($id, "{$built}{$this->tokens->eol}{$token['value']}");
            }
        }
        // resets the current DocBlock, resets the current token data
        $this->tokens->hasBlock = false;
        $this->tokens->id = array();
    }

    /**
     * Initializes the Page-level tags
     *
     * @param  array  $param the tags/parameters values
     * @return void
     * @access public
     */
    public function init($param)
    {
        // sets processing to align DocBlocks tags only
        $this->alignOnly = isset($param['align']);
        // sets/defaults the page-level tags, resets the types cache
        $this->pageTags = $param + $this->pageTagDefaults;
        $this->type->resetCache();
    }

    /**
     * Realignes the DocBlock tags
     *
     * @param  integer $id    the DocBlock token identification number
     * @param  mixed   $value the DocBlock content
     * @return void
     * @access public
     */
    public function realign($id, $value)
    {
        $this->tokens->set($id, $this->align->alignTags($value));
    }

    /**
     * Sets the class DocBlock
     *
     * Extracts the class token. Sets the default package name on the
     * first 2 words of the class. Builds the class DocBlock. Resets the
     * class variables types cache.
     *
     * @param  integer $id the class token identification number
     * @return void
     * @access public
     */
    public function setClass($id)
    {
        // extracts the class declaration tokens
        $tokens = $this->tokens->slice($id, T_CLASS, '{');

        if (count($tokens) >= 3) {
            // a class is found, extracts the class tokens
            $this->classTokens = $this->tokens->slice($id, '{', '}');
            // if the package name is set to its defaults
            // extracts the class name first 2 words, assumed to be the package name
            // sets the package name to the first 2 words of the class name
            $this->packageName == self::packageName and
            $className = next($tokens) and
            $className = $className['value'] and
            $words = explode('_', $className)and
            $words = array_slice($words, 0, 2) and
            $this->packageName = implode('_', $words) or
            $this->packageName = self::packageName;
            // adds the default description and the tags to the DocBlock
            $block = array($this->description['short'], '', $this->description['long']);
            $block = array_merge($block, $this->setPageTags(false));
            // builds the DocBlock before any of the class/final/interface/abstract keywords
            $id = array($id);
            isset($this->tokens->id[T_ABSTRACT]) and $id[] = $this->tokens->id[T_ABSTRACT];
            isset($this->tokens->id[T_FINAL]) and $id[] = $this->tokens->id[T_FINAL];
            $name = $this->tokens->isInterface? 'interface' : 'class';
            $this->build(min($id), $block, false, $name);
            // resets the variables types cache
            $this->type->resetCache('var');
        }
        // else: ignore, not a syntax compliant class declaration
    }

    /**
     * Sets the class constant DocBlock
     *
     * Extracts the constant value. Determines its type.
     * Builds the constant DocBlock.
     *
     * @param  integer $id the constant token identification number
     * @return void
     * @access public
     */
    public function setConst($id)
    {
        // extracts the constant tokens
        $tokens = $this->tokens->slice($id, T_CONST, ';');

        if (count($tokens) >= 5) {
            // expecting at least 4 tokens, e.g foo = 'foo' ; , extracts the constant tokens
            list(, $constName, $separator, $constValue) = $tokens;

            if ($separator['type'] == '=') {
                // a syntax compliant const, e.g. const FOO = 'foo' ..., sets the constant type
                $constType = $this->type->guessConst($constValue, 'const', $constName['value']);

                $this->build($id);
            }
        }
        // else: ignore, not a syntax compliant class constant
    }

    /**
     * Sets the define DocBlock
     *
     * Extracts the constant value. Determines its type.
     * Builds the define DocBlock.
     *
     * @param  integer $id the define token identification number
     * @return void
     * @access public
     */
    public function setDefine($id)
    {
        // extracts the define tokens
        $tokens = $this->tokens->slice($id + 1, '(', ')');

        if (count($tokens) >= 5) {
            // expecting at least 5 tokens, e.g ( 'FOO' , 'foo' ) , extracts the define tokens
            list($openBracket, $defName, $separator, $defValue) = $tokens;

            if ($openBracket['type'] == '(' and $separator['type'] == ',') {
                // a syntax compliant define, e.g. define("FOO",'foo' ..., sets the define type
                $defType = $this->type->guessConst($defValue, 'define', $defName['value']);

                $this->build($id);
                // note: complexe defines, e.g. define($a . $b, "$c foo $d") will not be captured nor documented
                // this is to prevent the capture of ordinary 'define' word/string within a double quoted string
            }
        }
        // else: ignore, not a syntax compliant define
    }

    /**
     * Sets the function/method DocBlock
     *
     * Extracts the function/method tokens. Extracts the function/method name.
     * Extracts the function/method parameters and determines their type.
     * Determine the function/method of data the function returns.
     * Extracts the exceptions the function/method throws.
     * Determines the scope and visibility of the method.
     * Builds the function/method DocBlock.
     *
     * @param  integer $id the function/method token identification number
     * @return void
     * @access public
     */
    public function setFunction($id)
    {
        // adds the descriptions to the DocBlock
        $block = array($this->description['short'], '', $this->description['long'], '');
        // extracts the function tokens, parameters, and name
        $param = $this->tokens->slice($id + 1, '(', ')');
        $functTokens = ($this->tokens->isInterface or isset($this->tokens->id[T_ABSTRACT]))?
		$param : $this->tokens->slice($id, '{', '}');
        $functName = array_shift($param);
        // extracts the function name if the function returns by reference
        $functName['value'] == '&' and $functName = array_shift($param);
        // /
        // determines the parameters type
        // /
        // removes the parameters enclosing parenthesis
        $param = array_slice($param, 1, -1);
        foreach($param as $pid => $var) {
            if ($var['type'] == T_VARIABLE) {
                // a function parameter
                // checks if the parameter is passed by reference
                $reference = (isset($param[$pid-1]) and $param[$pid-1]['value'] == '&');
                $name = $var['value'];
                // guesses the parameter type
                $type = $this->type->guessVar($functTokens, $var, 'param', 'param', $reference);
                // adds the "&" to the parameter name if a reference
                $reference and $name = "&{$name}";
                // adds the parameter tag in the DocBlock
                $block[] = "@param $type {$name} {$this->description['param']}";
            }
        }
        // /
        // determines the return statement type
        // /
        $types = array();
        $isReturn = false;
        foreach($functTokens as $token) {
            if ($token['type'] == T_RETURN) {
                // the function returns something
                $isReturn = true;
                // extracts the return statement, removes the "return" and ";" tokens, guesses the return type
                $returnTokens = $this->tokens->slice($token['id'], T_RETURN, ';');
                $returnTokens = array_slice($returnTokens, 1, -1);
                $types[] = $this->type->guessReturn($functTokens, $returnTokens);
            }
        }

        if ($isReturn) {
            // the function returns something, extracts the return type, sets the return tags
            $type = $this->type->extract($types);
            $tag = "@return $type {$this->description['return']}";
        } else {
            // the function does not return anything, sets the return type to "void"
            $tag = '@return void';
        }
        // adds the return tag into the DocBlock
        $this->tokens->isInterface or isset($this->tokens->id[T_ABSTRACT]) or $block[] = $tag;
        // resets the parameters types cache
        $this->type->resetCache('param');
        // /
        // determines the function access level
        // /
        if (isset($this->tokens->id['access'])) {
            // the visibility property is set
            $access = $this->tokens->id['access'][1];
        } else if (isset($this->tokens->id[T_STATIC])) {
            // the static property is set, defaults the visibility to public
            $access = 'public';
        } else if ($this->tokens->inClass) {
            // no visibility nor static property is set but the function is in a class: assuming this is PHP 4
            // extracts the function/method name
            $name = $functName['value'];
            // the name is prefixed with '_', meaning private
            $access = ($name{0} == '_' and $name{1} != '_')? 'private' : 'public';
        }
        // adds the access tag into the DocBlock
        isset($access) and $block[] = "@access $access";
        // /
        // processes the throw statements
        // /
        foreach($functTokens as $tid => $token) {
            if ($token['type'] == T_THROW and
                isset($functTokens[$tid + 1]) and $functTokens[$tid + 1]['type'] == T_NEW and
                    isset($functTokens[$tid + 2]) and $functTokens[$tid + 2]['type'] == T_STRING) {
                // a syntax compliant thrown exception, e.g. throw new Exception($error);
                // sets the exception tag with the exception class
                $tag = "@throws {$functTokens[$tid + 2]['value']} ";
                // adds the exception tag in the DocBlock
                $block[] = "$tag {$this->description['exception']}";
            }
        }
        // adds the static tag into the DocBlock
        isset($this->tokens->id[T_STATIC]) and $block[] = '@static';
        // builds the DocBlock before any of the final/static/public/private/abstract keywords
        $id = array($id);
        isset($this->tokens->id['access']) and $id[] = $this->tokens->id['access'][0];
        isset($this->tokens->id[T_ABSTRACT]) and $id[] = $this->tokens->id[T_ABSTRACT];
        isset($this->tokens->id[T_FINAL]) and $id[] = $this->tokens->id[T_FINAL];
        isset($this->tokens->id[T_STATIC]) and $id[] = $this->tokens->id[T_STATIC];
        $this->build(min($id), $block, false, 'function');
    }

    /**
     * Sets the global variable DocBlock
     *
     * Extracts the global variable. Determines its type.
     * Builds the global variable DocBlock.
     *
     * @param  integer $id        the global variable token identification number
     * @param  array   $global    the global variable token
     * @param  array   $allTokens the file tokens
     * @return void
     * @access public
     */
    public function setGlobal($id, $global, $allTokens)
    {
        // guesses the variable type, adds the type into the DocBlock
        $type = $this->type->guessVar($allTokens, $global, '', 'global');
        $block[] = "@global $type";
        $this->build($id, $block);
    }

    /**
     * Sets the GLOBALS variable DocBlock
     *
     * Extracts the global variable. Determines its type.
     * Builds the global variable DocBlock.
     *
     * @param  integer $id        the GLOBALS variable identification number
     * @param  array   $allTokens the file tokens
     * @return void
     * @access public
     */
    public function setGLOBALS($id, $allTokens)
    {
        // extracts the GLOBALS tokens
        $tokens = $this->tokens->slice($id, '[', ']');
        $currID = 0;
        if ($this->type->isAVar($tokens, $currID, $var) == 'GLOBALS') {
            // a syntax compliant $GLOBALS, guesses the global variable type
            $type = $this->type->guessVar($allTokens, $var, 'GLOBALS', 'global');
            // adds the type into the DocBlock and the variable name
            $block[] = "@global $type \$GLOBALS[{$tokens[2]['value']}]";
            $block[] = "@name {$var['value']}";
            $this->build($id, $block);
        }
        // else: ignore, not a syntax compliant GLOBALS
    }

    /**
     * Sets the Page-level DocBlock
     *
     * Gets the license text. Sets the description placeholders.
     * Sets the Page-level tags. Builds the Page-level DocBlock.
     *
     * @param  integer $id the PHP open tag token identification number
     * @return void
     * @access public
     */
    public function setPage($id)
    {
        // captures the license text
        $text = $this->license->getText($this->pageTags['license']) or
        $text = $this->licenseText;
        // sets the PHP version test
        $phpVersion = isset($this->phpVersion[$this->tokens->phpVersion])?
        $this->phpVersion[$this->tokens->phpVersion] : $this->phpVersion[0];
        // adds the default description and the PHP file version to the DocBlock
        $block = array($this->description['short'], '', $this->description['long'], '', $phpVersion, '');
        // adds the license text and the tags to the DocBlock
        $block = array_merge($block, $text, $this->setPageTags(true));
        $this->build($id, $block, false, 'file');
    }

    /**
     * Sets the Page-level or class common tags
     *
     * @param  boolean $isPageVersion a page version tag if true,
     *                                a class version tag if false
     * @return array   the Page-level or class common tags
     * @access private
     */
    private function setPageTags($isPageVersion)
    {
        // captures the license full name and URL
        $licenseFullName = $this->license->getFullName($this->pageTags['license']);
        $licenseURL = $this->license->getURL($this->pageTags['license']) or
        $licenseURL = $this->pageTags['license'];
        // sets the package tag default
        $this->pageTags['package'] or $this->pageTags['package'] = $this->packageName;
        // sets the link tag default
        $this->pageTags['link'] or $this->pageTags['link'] = self::packageLink . $this->pageTags['package'];
        if ($isPageVersion) {
            // sets the file version tag to CVS or SVN if applicable
            // note: this cannot be assigned simply as a string otherwise
            // it would get changed by CVS/SVN when this file is committed
            $version = $this->pageTags['version'];
            in_array($version, array('cvs', 'svn')) and
            $version = sprintf(strtoupper($version) . ': $%s$', 'Id:');
        } else {
            // a class version tag
            $version = 'Release: @package_version@';
        }
        // adds the page tags to the DocBlock
        $tags = array('',
            "@category {$this->pageTags['category']}",
            "@package {$this->pageTags['package']}",
            "@author {$this->pageTags['author']} <{$this->pageTags['email']}>",
            "@copyright {$this->pageTags['year']} {$this->pageTags['author']}",
            "@license $licenseURL $licenseFullName",
            "@version $version",
            "@link {$this->pageTags['link']}",
            "@see {$this->pageTags['see']}",
            );
        return $tags;
    }

    /**
     * Sets the class variable DocBlock
     *
     * Extracts the class variable name. Determines its type.
     * Determines the scope and visibility of the class variable.
     * Builds the variable DocBlock.
     *
     * @param  array  $var the variable token
     * @return void
     * @access public
     */
    public function setVar($var)
    {
        if (isset($this->tokens->id[T_VAR])) {
            // a PHP4 variable, extracts the variable name
			// capture the visibility: name prefixed with '_' means private
            $name = $var['value'];
            $access = ($name{1} == '_' and $name{2} != '_')? 'private' : 'public';
        } else if (isset($this->tokens->id['access'])) {
            // the visibility property is set, captures the visibility
            $access = $this->tokens->id['access'][1];
        } else if (isset($this->tokens->id[T_STATIC])) {
            // the static property is set, defaults the visibility to "public"
            $access = 'public';
        }
        // sets the variable scope, guesses the variable type
        $scope = isset($this->tokens->id[T_STATIC])? 'static' : 'dynamic';
        $type = $this->type->guessVar($this->classTokens, $var, $scope, 'var');
        // adds the type, access, and static tags into the DocBlock
        $block[] = "@var $type";
        $block[] = "@access $access";
        isset($this->tokens->id[T_STATIC]) and $block[] = '@static';
        // builds the DocBlock before any of the static/public/private/var keywords
        isset($this->tokens->id[T_VAR]) and $id[] = $this->tokens->id[T_VAR];
        isset($this->tokens->id['access']) and $id[] = $this->tokens->id['access'][0];
        isset($this->tokens->id[T_STATIC]) and $id[] = $this->tokens->id[T_STATIC];
        $this->build(min($id), $block);
    }
}

?>