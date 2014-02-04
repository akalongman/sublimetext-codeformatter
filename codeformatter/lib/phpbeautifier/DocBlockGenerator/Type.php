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
 * @version   SVN: $Id: Type.php 30 2007-07-23 16:46:42Z mcorne $
 * @link      http://pear.php.net/package/PHP_DocBlockGenerator
 */

/**
 * Determination of PHP object types for DocBlock tags
 *
 * Determination of the types of: function/method parameters and
 * return statements, class constants and variables, and global variables.
 *
 * @category  PHP
 * @package   PHP_DocBlockGenerator
 * @author    Michel Corne <mcorne@yahoo.com>
 * @copyright 2007 Michel Corne
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_DocBlockGenerator
 */
class PHP_DocBlockGenerator_Type
{
    /**
     * The post-operation variable flag
     */
    const OpAfter = 1;

    /**
     * The pre-operation variable flag
     */
    const OpBefore = -1;

    /**
     * Caches the object types
     *
     * @var    array
     * @access private
     */
    private $cacheTypes = array();

    /**
     * The basic assignment and comparison operators
     *
     * @var    array
     * @access private
     */
    private $operators = array(// /
        '=',
        T_IS_EQUAL, // '=='
        T_IS_NOT_EQUAL, // '!='
        T_IS_IDENTICAL, // '==='
        T_IS_NOT_IDENTICAL, // '!=='
        '<',
        T_IS_SMALLER_OR_EQUAL, // '<='
        T_IS_GREATER_OR_EQUAL, // '>='
        '>',
        T_PLUS_EQUAL, // += , see $typeFromOpCommon
        );

    /**
     * Miscellaneous tokens following a variable
     *
     * @var    array
     * @access private
     */
    private $typeFromMiscAfterOp = array(// /
        '"' => 'string', // $string = "$beer's taste";
        T_ARRAY => 'array', // $array = array();
        T_ARRAY_CAST => 'array', // $array = (array)'foo';
        T_BOOL_CAST => 'boolean', // $bool = (bool)$a;
        T_DOUBLE_CAST => 'double', // $float = (float)$a;
        T_INT_CAST => 'integer', // $int = (int)$a;
        T_NEW => 'object', // $object = new foo;
        T_START_HEREDOC => 'string', // $string = <<<EOT My name is "$name". EOT;
        T_STRING_CAST => 'string', // $string = (string => 'string'$b;
        );

    /**
     * Consolidated array of types coming from operators
     *
     * @var    array
     * @access private
     */
    private $typeFromOp = array();

    /**
     * The operators following a variable
     *
     * @var    array
     * @access private
     */
    private $typeFromOpAfter = array(// /
        '{' => 'string', // $string{0};
        '[' => 'array', // $array["foo"]; note: most likely an array vs a string
        T_OBJECT_OPERATOR => 'object', // $object->do_foo();
        );

    /**
     * The operators preceeding a variable
     *
     * @var    array
     * @access private
     */
    private $typeFromOpBefore = array(// /
        '~' => 'integer', // ~ $int;
        '(' => array(T_FOREACH => 'array'), // foreach ($array as $i => $value);
        );

    /**
     * The arithmetic, bitwise, incrementing, assignment operators
     *
     * @var    array
     * @access private
     */
    private $typeFromOpCommon = array(// /
        // '+' => 'number', // $int + $int; but could also be an array operator
        '-' => 'number', // $int - $int;
        '*' => 'number', // $int * $int;
        '/' => 'number', // $int / $int;
        '%' => 'integer', // $int % $int;
        '&' => 'integer', // $int &$int;
        '|' => 'integer', // $int | $int;
        '^' => 'integer', // $int ^ $int;
        T_SL => 'integer', // $int << $int;
        T_SR => 'integer', // $int >> $int;
        T_INC => 'integer', // ++$int or $int++;
        T_DEC => 'integer', // --$int or $int--;
        '.' => 'string', // $string . $string; note: most likely a string vs a number
        // T_PLUS_EQUAL => 'number', // $int += $int; but could also be an array operator, see $operators
        T_MINUS_EQUAL => 'number', // $int -= $int
        T_MUL_EQUAL => 'number', // $int *= $int
        T_DIV_EQUAL => 'number', // $int /= $int
        T_MOD_EQUAL => 'integer', // $int %= $int
        T_CONCAT_EQUAL => 'string', // $string .= $string;
        T_SL_EQUAL => 'integer', // $int <<= $int
        T_SR_EQUAL => 'integer', // $int >>= $int
        );

    /**
     * The basic datatypes
     *
     * @var    array
     * @access private
     */
    private $types = array(// /
        T_CLASS_C => 'string', // $string = __CLASS__;
        T_CONSTANT_ENCAPSED_STRING => 'string', // $string = 'this is a simple string';
        T_DNUMBER => 'double', // $float = 1.234;$float = 1.2e3;$float = 7E-10;
        T_FILE => 'string', // $string = __FILE__;
        T_FUNC_C => 'string', // $string = __FUNCTION__;
        T_LINE => 'integer', // $int = __LINE__;
        T_LNUMBER => 'integer', // $int = 1234;$int = -123;$int = 0123;$int = 0x1A;
        T_METHOD_C => 'string', // $string = __METHOD__;
        T_OBJECT_CAST => 'object', // $object = (object) 'ciao';
        );

    /**
     * The class constructor
     *
     * Builds the consolidated array of types coming from operators
     *
     * @return void
     * @access public
     */
    public function __construct()
    {
        $this->typeFromOp[self::OpBefore] = $this->typeFromOpBefore + $this->typeFromOpCommon;
        $this->typeFromOp[self::OpAfter] = $this->typeFromOpAfter + $this->typeFromOpCommon;

        foreach($this->operators as $operator) {
            $this->typeFromOp[self::OpBefore][$operator] = $this->types;
            $this->typeFromOp[self::OpAfter][$operator] = $this->types + $this->typeFromMiscAfterOp;
        }
        // note: some combinations are not expected from a syntaxically compliant PHP script
    }

    /**
     * Expands a variable to an array corresponding tokens
     *
     * For example, it expands $var to $this->var if the scope is dynamic.
     *
     * @param  array   $var   the variable token, e.g. the token for $var
     * @param  string  $scope the variable scope
     * @return array   the variable corresponding set of tokens,
     *                 e.g. the 3 tokens for $this, ->, var
     * @access private
     */
    private function expandVar($var, $scope = '')
    {
        switch ($scope) {
            case 'static': // looking for a class static variable; e.g. self::$var
                $var = array(array('type' => T_STRING, 'value' => 'self'),
                    array('type' => T_DOUBLE_COLON),
                    array('type' => T_VARIABLE, 'value' => $var['value']));
                break;

            case 'dynamic': // looking for a class dynamic variable, e.g. $this->var
                $var = array(array('type' => T_VARIABLE, 'value' => '$this'),
                    array('type' => T_OBJECT_OPERATOR),
                    array('type' => T_STRING, 'value' => substr($var['value'], 1)));
                break;

            case 'GLOBALS': // looking for a GLOBALS variable, e.g. $GLOBALS['var']
                $var = array(array('type' => T_VARIABLE, 'value' => '$GLOBALS'),
                    array('type' => '['),
                    array('type' => T_CONSTANT_ENCAPSED_STRING, 'value' => substr($var['value'], 1)),
                    array('type' => ']'));
                // note: not encapsulating string within quotes, see isZvar()
                break;

            default:
                // only one token for global variable or function parameters
                $var = array($var);
        }

        return $var;
    }

    /**
     * Extracts the type from a list of types
     *
     * The type is considered identified if the list has one type only.
     * The type is "mixed" if there are different types in the list.
     * The type is "number" if the types are: 'integer', 'float' or 'number'.
     * The type is "unknown" otherwise. The null type is ignored.
     *
     * @param  array   $rawTypes   the list of types
     * @param  boolean $returnType in case the type is unknown:
     *                             if true the default type is returned,
     *                             if false null is returned
     * @return string  the type
     * @access public
     */
    public function extract($types, $returnType = true)
    {
        is_array($types) or $types = array($types);
        // retains unique types
        $types = array_unique($types);

        if (($key = array_search('', $types)) !== false) {
            // removes the empty type/string
            unset($types[$key]);
        }

        if (($key = array_search('NULL', $types)) !== false) {
            // removes the "null" type
            unset($types[$key]);
        }
        // converts "double" to "float"
        $types = str_replace('double', 'float', $types);

        if (empty($types)) {
            // no types guessed
            $type = $returnType? 'unknown' : '';
        } else if (count($types) == 1) {
            // only one type was identified, supposed to be the right one
            $type = current($types);
        } else if (!array_diff($types, array('integer', 'float', 'number'))) {
            // the types in the list are related to a "number"
            $type = 'number';
        } else {
            // there are different types in the list
            $type = 'mixed';
        }

        return $type;
    }

    /**
     * Guesses the class constant or define statement type
     *
     * @param  array   $constValue the class constant or define value
     * @param  integer $object     the object, e.g. 'const'
     * @param  string  $name       the object name
     * @return string  the class constant or define statement type
     * @access public
     */
    public function guessConst($constValue, $object, $name)
    {
        $type = $constValue['type'];
        $constType = '';

        if (isset($this->typeFromOp[self::OpAfter]['='][$type])) {
            // the datatype derives from this operator/assignment
            // captures the datatype, e.g. "== 123" means an "integer"
            $constType = $this->typeFromOp[self::OpAfter]['='][$type];
        } else if ($type == T_STRING) {
            // a possible constant, e.g. define('PI', M_PI)
            $string = $constValue['value'];
            if (isset($this->cacheTypes['define'][$string])) {
                // the value begins with an already defined value, e.g. define('FOO', PI * 2)
                // assuming the value type is the same
                $constType = $this->cacheTypes['define'][$string];
            } else if (defined($string)) {
                // the value begins with an already PHP predefined value, e.g. define('FOO', M_PI * 2)
                // get the value type, assuming the value type is the same
                $constType = gettype(constant($string));
            } // else: undefined value
        }
        // else: complex defines, e.g. define('FOO', $foo)
        // trims the define name from its enclosing quotes, caches the class constant or define type
        $constType and $name = trim($name, '"\'') and $this->cacheTypes[$object][$name] = $constType;

        return $this->extract($constType);
    }

    /**
     * Guesses the type of data the function returns
     *
     * This method looks at all variables following the return statement
     * and guesses their type.
     *
     * @param  array  $tokens  the list of tokens of the return statement
     * @param  array  $targets the list of variables following the return statement
     * @return string the type of data the function returns
     * @access public
     * @todo   Redesign/improve the determination of the return type
     */
    public function guessReturn($tokens, $targets)
    {
        $types = array();
        $next = 0;

        foreach(array_keys($targets) as $id) {
            if ($id >= $next) {
                // the token is not processed yet
                if ($scope = $this->isAVar($targets, $id, $var)) {
                    // the token is (the begining of) a variable
                    if (isset($this->cacheTypes['var'][$var['value']])) {
                        // a class variable, captures the class variable type
                        $types[] = $this->cacheTypes['var'][$var['value']];
                    } else if (isset($this->cacheTypes['param'][$var['value']])) {
                        // a function parameter, captures the function parameter type
                        $types[] = $this->cacheTypes['param'][$var['value']];
                    } else if (isset($this->cacheTypes['global'][$var['value']])) {
                        // a global variable, captures the global variable type
                        $types[] = $this->cacheTypes['global'][$var['value']];
                    } else {
                        // guesses the type of the variable, captures the next token to process
                        $types[] = $this->guessVar($tokens, $var, $scope, null, false, false);
                        $next = $id + 1;
                    }
                } else {
                    // the token is not a variable, guesses type from token itself
                    $types[] = $this->typeFromToken($targets, $id);
                }
            }
        }

        return $this->extract($types, false);
    }

    /**
     * Guesses a variable type
     *
     * @param  array   $tokens     the list of tokens
     * @param  array   $var        the variable
     * @param  string  $scope      the variable scope
     * @param  integer $object     the PHP object, e.g. 'var'
     * @param  boolean $reference  true if the variable is a reference, e.g. &$var,
     *                             false otherwise
     * @param  boolean $returnType in case the type is unknown,
     *                             if true: the default type is returned,
     *                             if false null is returned
     * @return string  the variable type
     * @access public
     * @see    self::extract()
     * @todo   Refine the handling ". $array[0]"
     */
    public function guessVar($tokens, $var, $scope, $object, $reference = false, $returnType = true)
    {
        $types = array();
        // expands the variable declaration to its use, e.g. $this->var
        $expanded = $this->expandVar($var, $scope);

        foreach($tokens as $id => $token) {
            if ($token['id'] == $var['id']) {
                // the variable declaration, guesses the type from the operation after
                $types[] = $this->typeFromOp($tokens, $id + 1, self::OpAfter);
            } else if ($token['id'] > $var['id']) {
                // looking for the variable wherever it is used
                if ($varLen = $this->isZVar($tokens, $id, $expanded)) {
                    // the variable is found, guesses the type from the operation after
                    $types[] = $guessed = $this->typeFromOp($tokens, $id + $varLen, self::OpAfter);
                    // no check left of variable if already identified as 'array' because of  ". $array[0]"
                    // guesses the type from the operation before
                    $guessed != 'array' and $types[] = $this->typeFromOp($tokens, $id - 1, self::OpBefore, $reference);
                }
            }
        }
        // extracts the guessed type, caches the variable type
        $varType = $this->extract($types, false) and $object and $this->cacheTypes[$object][$var['value']] = $varType;

        return $this->extract($varType, $returnType);
    }

    /**
     * Checks if the current token(s) is a variable and returns its scope
     *
     * @param  array   $tokens list of tokens
     * @param  integer &$id    the current token identification number to process,
     *                         returns the next token identification number
     * @param  array   &$var   the list of tokens describing the variable,
     *                         returns the token variable as for $var
     *                         and strips off $this-> or self::
     * @return string  the variable scope
     * @access public
     */
    public function isAVar($tokens, &$id, &$var)
    {
        $scope = '';
        $var = array();

        if ($tokens[$id]['type'] == T_VARIABLE) {
            // a "variable", e.g. $var
            if ($tokens[$id]['value'] == '$this' and
                isset($tokens[++$id]) and $tokens[$id]['type'] == T_OBJECT_OPERATOR and
                    isset($tokens[++$id]) and $tokens[$id]['type'] == T_STRING) {
                // a class dynamic variable, e.g. $this->var, sets the variable scope to "dynamic"
                $scope = 'dynamic';
                // prepends the variable name with "$", e.g. $var
                $var['value'] = '$' . $tokens[$id]['value'];
            } else if ($tokens[$id]['value'] == '$GLOBALS' and
                isset($tokens[++$id]) and $tokens[$id]['type'] == '[' and
                    isset($tokens[++$id]) and $tokens[$id]['type'] == T_CONSTANT_ENCAPSED_STRING) {
                $scope = 'GLOBALS';
                // captures the variable name, e.g. $var
                $var['value'] = '$' . trim($tokens[$id]['value'], '"\'');
            } else {
                // a global or function variable/parameter, sets the variable scope to "variable"
                $scope = 'var';
                // captures the variable token details
                $var = $tokens[$id];
            }
        } else if ($tokens[$id]['type'] == T_STRING) {
            // a "string"
            if ($tokens[$id]['value'] == 'self' and
                isset($tokens[++$id]) and $tokens[$id]['type'] == T_DOUBLE_COLON and
                    isset($tokens[++$id])) {
                // a class static variable, e.g. self::$var, sets the variable scope to "static"
                $scope = 'static';
                // captures the variable name, e.g. $var
                $var['value'] = $tokens[$id]['value'];
            }
        }
        // sets the variable type, resets the variable token ID to 0
        $var['type'] = T_VARIABLE;
        $var['id'] = 0;

        return $scope;
    }

    /**
     * Verifies the current list of tokens are those of the targeted variable
     *
     * @param  array   $tokens the list of tokens
     * @param  integer $id     the identification number of the next token to process
     * @param  array   $var    the list of tokens describing the targeted variable
     * @return integer the number of tokens describing the variable
     * @access private
     */
    private function isZVar($tokens, $id, $var)
    {
        foreach($var as $varToken) {
            // scans the variable tokens, note: 1 or 3 tokens
            if (!isset($tokens[$id]['type']) or $tokens[$id]['type'] != $varToken['type'] or
                    isset($varToken['value']) and
                    trim($tokens[$id]['value'], '"\'') != trim($varToken['value'], '"\'')) {
                // the current token and current variable token types and values are different
                // trimming encapsulated string values
                // the current token(s) are not a variable
                return false;
            }
            // increments the current token index
            $id++;
        }

        return count($var);
    }

    /**
     * Resets the types cache
     *
     * @param  string $object the object types cache to reset, e.g. function 'param',
     *                        resets all the object types caches by default
     * @return void
     * @access public
     */
    public function resetCache($object = null)
    {
        // resets all caches, typically for a new file or the object cache, e.g. 'param'
        is_null($object) and $this->cacheTypes = array() or $this->cacheTypes[$object] = array();
    }

    /**
     * Guesses the variable type based on operators before or after.
     *
     * For example, a "." following a variable means it is a string.
     *
     * @param  array   $tokens    the list of tokens
     * @param  integer $id        the identification number of the next
     *                            token following the variable
     * @param  integer $opPos     the position of the operator
     *                            before/-1 or after/+1
     * @param  boolean $reference the variable may be passed by reference
     *                            and then prefixed by "&"
     * @return string  the variable type
     * @access private
     */
    private function typeFromOp($tokens, $id, $opPos, $reference = false)
    {
        $varType = '';
        if (isset($tokens[$id])) {
            // there is a token following the variable, extracts the following token type
            $opType = $tokens[$id]['type'];

            if (isset($this->typeFromOp[$opPos][$opType])) {
                // the following token is an operator
                if (is_array($this->typeFromOp[$opPos][$opType])) {
                    // a basic assignent or comparison operator, e.g. "=" or "=="
                    // there is a token following the operator, guesses the type from the token itself
                    isset($tokens[$id += $opPos]) and $varType = $this->typeFromToken($tokens, $id, $opPos, $opType);
                } else {
                    // an arithmetic, bitwise, incrementing, assignment operators, e.g. ".="
                    // note: ignores "&" if variable is passed by reference, e.g. &$var
                    // captures the datatype, e.g. ".=" means a  "string"
                    ($opType != '&' or !$reference) and $varType = $this->typeFromOp[$opPos][$opType];
                }
            }
        }

        return $varType;
    }

    /**
     * Guesses the type from the token itself
     *
     * For example, T_INTEGER means it is an integer.
     * If the token is a string, the method tries to find out if the string
     * is a define or a class constant.
     *
     * @param  array   $tokens the list of tokens
     * @param  integer $id     the identification number of the next
     *                         token following the variable
     * @param  integer $opPos  the position of the operator
     *                         before/-1 or after/+1
     * @param  string  $opType the operator's type, e.g. T_IS_EQUAL
     * @return string  the token type
     * @access private
     */
    private function typeFromToken($tokens, $id, $opPos = self::OpAfter, $opType = '=')
    {
        $varType = '';
        // extracts the token type
        $type = $tokens[$id]['type'];

        if (isset($this->typeFromOp[$opPos][$opType][$type])) {
            // the datatype derives from this operator/assignment
            // captures the datatype, e.g. "== 123" means an "integer"
            $varType = $this->typeFromOp[$opPos][$opType][$type];
        } else if ($type == T_STRING) {
            // the following token is a "string", extracts the value of the "string" token
            $string = $tokens[$id]['value'];

            if (isset($this->cacheTypes['define'][$string])) {
                // the token is a constant coming from a define statement
                // captures the datatype, e.g. "MY_PI_VALUE" is a "float"
                $varType = $this->cacheTypes['define'][$string];
            } else if (defined($string)) {
                // the token is a PHP predefined constant, e.g. "M_PI"
                // captures the datatype, e.g. "M_PI" is a "float"
                $varType = gettype(constant($string));
            } else if ($string == 'self' and
                isset($tokens[$id += $opPos]) and $tokens[$id]['type'] == T_DOUBLE_COLON and
                    isset($tokens[$id += $opPos]) and isset($this->cacheTypes['const'][$tokens[$id]['value']])) {
                // the token is class constant, e.g. "self::myPiValue"
                // captures the datatype, e.g. "self::myPiValue" is a  "float"
                $varType = $this->cacheTypes['const'][$tokens[$id]['value']];
            }
        }

        return $varType;
    }
}

?>