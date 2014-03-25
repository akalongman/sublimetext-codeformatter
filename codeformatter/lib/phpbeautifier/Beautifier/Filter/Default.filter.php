<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * Default Filter: Handle all the tokens. Uses K & R style
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   PHP
 * @package    PHP_Beautifier
 * @subpackage Filter
 * @author     Claudio Bustos <cdx@users.sourceforge.com>
 * @copyright  2004-2010 Claudio Bustos
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id:$
 * @link       http://pear.php.net/package/PHP_Beautifier
 * @link       http://beautifyphp.sourceforge.net
 */
/**
 * Default Filter: Handle all the tokens. Uses K & R style
 *
 * This filters is loaded by default in {@link PHP_Beautifier}. Can handle all the tokens.
 * If one of the tokens doesn't have a function, is added without modification (See {@link __call()})
 * The most important modifications are:
 * - All the statements inside control structures, functions and class are indented with K&R style
 * <CODE>
 * function myFunction() {
 *     echo 'hi';
 * }
 * </CODE>
 * - All the comments in new lines are indented. In multi-line comments, all the lines are indented, too.
 * This class is final, so don't try to extend it!
 *
 * @category   PHP
 * @package    PHP_Beautifier
 * @subpackage Filter
 * @author     Claudio Bustos <cdx@users.sourceforge.com>
 * @copyright  2004-2010 Claudio Bustos
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/PHP_Beautifier
 * @link       http://beautifyphp.sourceforge.net
 */
final class PHP_Beautifier_Filter_Default extends PHP_Beautifier_Filter
{
    protected $sDescription = 'Default Filter for PHP_Beautifier';
    protected $aSettings = array('newline_before_comment' => false);
    /**
     * __call
     *
     * @param mixed $sMethod Method name
     * @param mixed $aArgs   Method arguments
     *
     * @access protected
     * @return void
     */
    function __call($sMethod, $aArgs)
    {
        if (!is_array($aArgs) or count($aArgs) != 1) {
            throw (new Exception('Call to Filter::__call with wrong argument'));
        }
        PHP_Beautifier_Common::getLog()->log('Default Filter:unhandled[' . $aArgs[0] . ']', PEAR_LOG_DEBUG);
        $this->oBeaut->add($aArgs[0]);
    }
    // Bypass the function!
    /**
     * off
     *
     * @access public
     * @return void
     */
    public function off()
    {
    }
    /**
     * t_access
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    public function t_access($sTag)
    {
        $this->oBeaut->add($sTag . ' ');
    }
    /**
     * t_end_heredoc
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    public function t_end_heredoc($sTag)
    {
        $this->oBeaut->add(trim($sTag));
        $this->oBeaut->addNewLineIndent();
    }
    /**
     * t_open_tag
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    public function t_open_tag($sTag)
    {
        $this->oBeaut->add(trim($sTag));
        preg_match("/([\s\r\n\t]+)$/", $sTag, $aMatch);
        $aNextToken = $this->oBeaut->getToken($this->oBeaut->iCount+1);
        $sNextWhitespace = ($aNextToken[0] == T_WHITESPACE) ? $aNextToken[1] : '';
        $sWhitespace = @$aMatch[1] . $sNextWhitespace;
        if (preg_match("/[\r\n]+/", $sWhitespace)) {
            $this->oBeaut->addNewLineIndent();
        } else {
            $this->oBeaut->add(" ");
        }
    }
    /**
     * t_close_tag
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_close_tag($sTag)
    {
        $this->oBeaut->removeWhitespace();
        if (preg_match("/\r|\n/", $this->oBeaut->getPreviousWhitespace())) {
            $this->oBeaut->addNewLine();
            $this->oBeaut->add($sTag);
        } else {
            $this->oBeaut->add(" " . $sTag);
        }
    }
    /**
     * t_switch
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_switch($sTag)
    {
        $this->t_control($sTag);
    }
    /**
     * t_control
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_control($sTag)
    {
        $this->oBeaut->add($sTag . ' ');
    }
    /**
     * t_case
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_case($sTag)
    {
        $this->oBeaut->removeWhitespace();
        $this->oBeaut->decIndent();
        $this->oBeaut->addNewLineIndent();
        $this->oBeaut->add($sTag . ' ');
        //$this->oBeaut->incIndent();

    }
    /**
     * t_parenthesis_open
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_parenthesis_open($sTag)
    {
        $this->oBeaut->add($sTag);
    }
    /**
     * t_parenthesis_close
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_parenthesis_close($sTag)
    {
        if (!$this->oBeaut->isPreviousTokenConstant(T_COMMENT) and !$this->oBeaut->isPreviousTokenConstant(T_END_HEREDOC)) {
            $this->oBeaut->removeWhitespace();
        }
        $this->oBeaut->add($sTag);
        if (!$this->oBeaut->isNextTokenContent(';')) {
            $this->oBeaut->add(' ');
        }

    }
    /**
     * t_open_brace
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_open_brace($sTag)
    {
        if ($this->oBeaut->openBraceDontProcess()) {
            $this->oBeaut->add($sTag);
        } else {
            if ($this->oBeaut->removeWhiteSpace()) {
                $this->oBeaut->add(' ' . $sTag);
            } else {
                $this->oBeaut->add($sTag);
            }
            $this->oBeaut->incIndent();
            if ($this->oBeaut->getControlSeq() == T_SWITCH) {
                $this->oBeaut->incIndent();
            }
            $this->oBeaut->addNewLineIndent();
        }
    }

    /**
     * t_close_brace
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_close_brace($sTag)
    {
        if ($this->oBeaut->getMode('string_index') or $this->oBeaut->getMode('double_quote')) {
            $this->oBeaut->add($sTag);

        } else {
            $this->oBeaut->removeWhitespace();
            $this->oBeaut->decIndent();
            if ($this->oBeaut->getControlSeq() == T_SWITCH) {
                $this->oBeaut->decIndent();
            }
            $this->oBeaut->addNewLineIndent();
            $this->oBeaut->add($sTag);
            if ($this->oBeaut->getControlSeq() == T_DO) {
                $this->oBeaut->add(' ');
            } else {
                $this->oBeaut->addNewLineIndent();
            }
        }
    }
    /**
     * t_semi_colon
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_semi_colon($sTag)
    {
        $this->oBeaut->removeWhitespace();
        $this->oBeaut->add($sTag);
        if ($this->oBeaut->getControlParenthesis() != T_FOR) {
            $this->oBeaut->addNewLineIndent();
        }
    }
    /**
     * t_as
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_as($sTag)
    {
        $this->oBeaut->removeWhitespace();
        $this->oBeaut->add(' ' . $sTag . ' ');
    }
    /**
     * t_new
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_new($sTag)
    {
        $this->oBeaut->add(' ' . $sTag . ' ');
    }
    /**
     * t_whitespace
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_whitespace($sTag)
    {
        $matches = "";
        $minNL = 2;
        if($this->oBeaut->isPreviousTokenConstant(T_COMMENT)) {
            $prevToken = $this->oBeaut->getPreviousTokenContent(1);
            $tokenEnd = substr($prevToken,strlen($prevToken)-2);
            if($tokenEnd=="*/") {
                $minNL = 2;
            }else{
                $minNL = 1;
            }
        }
        if(preg_match_all("/\r\n|\r|\n/",$sTag,$matches)>=$minNL){
            $this->oBeaut->addNewLineIndent();
            $this->oBeaut->addedBlankLine = true;
        } else {
            $this->oBeaut->addedBlankLine = false;
        }
    }
    /**
     * t_doc_comment
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_doc_comment($sTag)
    {
        $this->oBeaut->removeWhiteSpace();
        $this->oBeaut->addNewLineIndent();
        if ($this->getSetting('newline_before_comment')) {
	        $this->oBeaut->addNewLineIndent();
        }

        // process doc
        preg_match("/(\/\*\*[^\r\n]*)(.*?)(\*\/)/sm", $sTag, $aMatch);
        $sDoc = $aMatch[2];
        // if is a one-line-doc, leave as-is
        if (!preg_match("/\r\n|\r|\n/", $sDoc)) {
            $this->add($sTag);
            $this->oBeaut->addNewLineIndent();
        } else { // is a multi line doc...
            $aLines = preg_split("/\r\n|\r|\n/", $sDoc);
            $this->oBeaut->add($aMatch[1]);
            foreach ($aLines as $sLine) {
                if ($sLine = trim($sLine)) {
                    $this->oBeaut->addNewLineIndent();
                    $this->oBeaut->add(" " . $sLine);
                }
            }
            $this->oBeaut->addNewLineIndent();
            $this->oBeaut->add(" " . $aMatch[3]);
            $this->oBeaut->addNewLineIndent();
        }
    }
    /**
     * t_comment
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_comment($sTag)
    {
        if ($this->oBeaut->removeWhitespace()) {
	        if ($this->getSetting('newline_before_comment')) {
		        $this->oBeaut->addNewLineIndent();
	        }
           if (preg_match("/\r|\n/", $this->oBeaut->getPreviousWhitespace())) {
                $this->oBeaut->addNewLineIndent();
            } else {
                $this->oBeaut->add(' ');
            }
        }
        if (substr($sTag, 0, 2) == '/*') { // Comentario largo
            $this->comment_large($sTag);
        } else { // comentario corto
            $this->comment_short($sTag);
        }
    }
    /**
     * comment_short
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function comment_short($sTag)
    {
        $this->oBeaut->add(trim($sTag));
        $this->oBeaut->addNewLineIndent();
    }
    /**
     * comment_large
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function comment_large($sTag)
    {
        if ($sTag == '/*{{{*/' or $sTag == '/*}}}*/') { // folding markers
            $this->oBeaut->add(' ' . $sTag);
            $this->oBeaut->addNewLineIndent();
        } else {
            $aLines = explode("\n", $sTag);

            $allWithAsterisk=true;
            for ($x=1;$x<(count($aLines)-1);$x++) {
                if (substr(trim($aLines[$x]), 0, 1)!="*") {
                    $allWithAsterisk=false;
                }
            }

            foreach ($aLines as $sLinea) {
                if (substr(trim($sLinea), 0, 2)=="/*") {
                    $this->oBeaut->add(trim($sLinea));
                } elseif (substr(trim($sLinea), 0, 2)=="*/") {
                    $this->oBeaut->add(trim($sLinea));
                } elseif ($allWithAsterisk) {
                    $this->oBeaut->add(" ".trim($sLinea));
                } else {
                    if (trim(substr($sLinea, 0, $this->oBeaut->getIndent()))=="") {
                        $this->oBeaut->add(substr($sLinea, $this->oBeaut->getIndent()));
                    } else {
                        $this->oBeaut->add(trim($sLinea));
                    }
                }
                $this->oBeaut->addNewLineIndent();
            }
        }
    }
    /**
     * t_else
     *
     * @param mixed $sTag The tag to be processed
     *
     * @uses detect_colon_after_parenthesis
     * @access public
     * @return void
     */
    function t_else($sTag)
    {
        if ($this->oBeaut->isPreviousTokenConstant(T_COMMENT)) {
            // do nothing!

        } elseif ($this->oBeaut->isPreviousTokenContent('}')) {
            $this->oBeaut->removeWhitespace();
            $this->oBeaut->add(' ');
        } else {
            $this->oBeaut->removeWhitespace();
            if ($this->oBeaut->isNextTokenContent(':') or ($sTag == 'elseif' and $this->_detectColonAfterParenthesis())) {
                $this->oBeaut->decIndent();
            }
            $this->oBeaut->addNewLineIndent();
        }
        $this->oBeaut->add($sTag . ' ');
    }

    /**
     * _detectColonAfterParenthesis Detect structure elseif($something):
     *
     * @access private
     * @return void
     */
    private function _detectColonAfterParenthesis()
    {
        $iPar = 1;
        $x = 2;
        while ($iPar and $x < 100) {
            if ($this->oBeaut->isNextTokenContent('(', $x)) {
                $iPar++;
            } elseif ($this->oBeaut->isNextTokenContent(')', $x)) {
                $iPar--;
            }
            $x++;
        }
        if ($x == 100) {
            throw new Exception("Elseif doesn't have an ending parenthesis");
        }
        return $this->oBeaut->isNextTokenContent(':', $x);
    }
    /**
     * t_equal
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_equal($sTag)
    {
        $this->oBeaut->removeWhitespace();
        $this->oBeaut->add(' ' . $sTag . ' ');
    }
    /**
     * t_logical
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_logical($sTag)
    {
        $this->oBeaut->removeWhitespace();
        $this->oBeaut->add(' ' . $sTag . ' ');
    }
    /**
     * t_foreach
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_foreach($sTag)
    {
        $this->oBeaut->add($sTag . ' ');
    }
    /**
     * t_for
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_for($sTag)
    {
        $this->oBeaut->add($sTag . ' ');
    }
    /**
     * t_comma
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_comma($sTag)
    {
        $this->oBeaut->removeWhitespace();
        $this->oBeaut->add($sTag . ' ');
    }
    /**
     * t_dot
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_dot($sTag)
    {
        $this->oBeaut->removeWhitespace();
        $this->oBeaut->add(' ' . $sTag . ' ');
    }
    /**
     * t_include
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_include($sTag)
    {
        $this->oBeaut->add($sTag . ' ');
    }
    /**
     * t_language_construct
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_language_construct($sTag)
    {
        $this->oBeaut->add($sTag . ' ');
    }
    /**
     * t_constant_encapsed_string
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_constant_encapsed_string($sTag)
    {
        $this->oBeaut->add($sTag);
    }
    /**
     * t_variable
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_variable($sTag)
    {
        if ($this->oBeaut->isPreviousTokenConstant(T_STRING) and !$this->oBeaut->getMode("double_quote")) {
            $this->oBeaut->add(' ');
        }
        $this->oBeaut->add($sTag);
    }
    /**
     * t_question
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_question($sTag)
    {
        $this->oBeaut->removeWhitespace();
        $this->oBeaut->add(' ' . $sTag . ' ');
    }
    /**
     * t_colon
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_colon($sTag)
    {
        $this->oBeaut->removeWhitespace();
        if ($this->oBeaut->getMode('ternary_operator')) {
            $this->oBeaut->add(' ' . $sTag . ' ');
        } else {
            $this->oBeaut->add($sTag);
            $this->oBeaut->incIndent();
            $this->oBeaut->addNewLineIndent();
        }
    }
    /**
     * t_double_colon
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_double_colon($sTag)
    {
        $this->oBeaut->add($sTag);
    }
    /**
     * t_break
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_break($sTag)
    {
        if ($this->oBeaut->getControlSeq() == T_SWITCH) {
            $this->oBeaut->removeWhitespace();
            $this->oBeaut->decIndent();
            $this->oBeaut->addNewLineIndent();
            $this->oBeaut->add($sTag);
            $this->oBeaut->incIndent();
        } else {
            $this->oBeaut->add($sTag);
        }
        if ($this->oBeaut->isNextTokenConstant(T_LNUMBER)) {
            $this->oBeaut->add(" ");
        }
    }
    /**
     * t_continue
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_continue($sTag)
    {
        $this->oBeaut->add($sTag);
        if ($this->oBeaut->isNextTokenConstant(T_LNUMBER)) {
            $this->oBeaut->add(" ");
        }
    }
    /**
     * t_default
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_default($sTag)
    {
        $this->t_case($sTag);
    }
    /**
     * t_end_suffix
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_end_suffix($sTag)
    {
        $this->oBeaut->removeWhitespace();
        $this->oBeaut->decIndent();
        $this->oBeaut->addNewLineIndent();
        $this->oBeaut->add($sTag);
    }
    /**
     * t_extends
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_extends($sTag)
    {
        $this->oBeaut->removeWhitespace();
        $this->oBeaut->add(' ' . $sTag . ' ');
    }
    /**
     * t_implements
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_implements($sTag)
    {
        $this->oBeaut->removeWhitespace();
        $this->oBeaut->add(' ' . $sTag . ' ');
    }
    /**
     * t_instanceof
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_instanceof($sTag)
    {
        $this->oBeaut->removeWhitespace();
        $this->oBeaut->add(' ' . $sTag . ' ');
    }
    /**
     * t_equal_sign
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_equal_sign($sTag)
    {
        $this->oBeaut->removeWhitespace();
        $this->oBeaut->add(' ' . $sTag . ' ');
    }
    /**
     * t_assigment
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_assigment($sTag)
    {
        $this->oBeaut->removeWhitespace();
        $this->oBeaut->add(' ' . $sTag . ' ');
    }
    /**
     * t_assigment_pre
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_assigment_pre($sTag)
    {
        $this->oBeaut->removeWhitespace();
        $this->oBeaut->add($sTag . ' ');
    }
    /**
     * t_clone
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_clone($sTag)
    {
        $this->oBeaut->add($sTag.' ');
    }
    /**
     * t_array
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_array($sTag)
    {
        $this->oBeaut->add($sTag);
        // Check this, please!
        if (!$this->oBeaut->isNextTokenContent('(')) {
            $this->oBeaut->add(" ");
        }
    }
    /**
     * t_object_operator
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_object_operator($sTag)
    {
        $this->oBeaut->removeWhitespace();
        $this->oBeaut->add($sTag);
    }
    /**
     * t_operator
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_operator($sTag)
    {
        $this->oBeaut->removeWhitespace();
        // binary operators should have a space before and after them.  unary ones should just have a space before them.
        switch ($this->oBeaut->getTokenFunction($this->oBeaut->getPreviousTokenConstant())) {
        case 't_question':
        case 't_colon':
        case 't_comma':
        case 't_dot':
        case 't_case':
        case 't_echo':
        case 't_language_construct': // print, echo, return, etc.
        case 't_operator':
            $this->oBeaut->add(' ' . $sTag);
            break;
        case 't_parenthesis_open':
        case 't_open_square_brace':
        case 't_open_brace':
            $this->oBeaut->add($sTag);
            break;
        default:
            $this->oBeaut->add(' ' . $sTag . ' ');
        }
    }
}

