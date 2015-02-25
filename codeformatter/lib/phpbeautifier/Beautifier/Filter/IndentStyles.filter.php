<?php
/**
 * Filter Indent Styles: Indent the code in k&r, allman, gnu or whitesmiths
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
 * Filter Indent Styles: Indent the code in k&r, allman, gnu or whitesmiths
 *
 * Use 'style' setting to select the style. You can change the style inside
 * the file, using the callbacks features.
 * The following is a description taken from {@link
 * http://catb.org/~esr/jargon/html/I/indent-style.html }
 *
 * K&R style <b>['k&r']</b> Named after Kernighan & Ritchie, because the
 * examples in K&R are formatted this way. Also called kernel style because the
 * Unix kernel is written in it, and the "One True Brace Style" (abbrev. 1TBS)
 * by its partisans. In C code, the body is typically indented by eight spaces
 * (or one tab) per level, as shown here. Four spaces are occasionally seen in
 * C, but in C++ and Java four tends to be the rule rather than the exception.
 *
 *<CODE>
 *if (<cond>) {
 *   <body>
 *   }
 *</CODE>
 *
 *   Allman style <b>['allman' or 'bsd']</b> Named for Eric Allman, a Berkeley
 *   hacker who wrote a lot of the BSD utilities in it (it is sometimes called
 *   BSD style). Resembles normal indent style in Pascal and Algol. It is the
 *   only style other than K&R in widespread use among Java programmers. Basic
 *   indent per level shown here is eight spaces, but four (or sometimes three)
 *   spaces are generally preferred by C++ and Java programmers.
 *
 *<CODE>
 *   if (<cond>)
 *   {
 *        <body>
 *   }
 * </CODE>
 *
 *
 * Whitesmiths style <b>['whitesmiths']</b>? popularized by the examples that
 * came with Whitesmiths C, an early commercial C compiler. Basic indent per
 * level shown here is eight spaces, but four spaces are occasionally seen.
 *
 * <CODE>
 * if (<cond>)
 *     {
 *     <body>
 *     }
 * </CODE>
 *
 * GNU style <b>['gnu']</b>  Used throughout GNU EMACS and the Free Software
 * Foundation code, and just about nowhere else. Indents are always four spaces
 * per level, with { and } halfway between the outer and inner indent levels.
 *
 *<CODE>
 * if (<cond>)
 *  {
 *    <body>
 *  }
 * </CODE>
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
class PHP_Beautifier_Filter_IndentStyles extends PHP_Beautifier_Filter
{
    protected $aIndentTag = array();
    protected $aSettings = array(
        'style' => 'K&R'
    );
    public $aAllowedStyles = array(
        "k&r" => "kr",
        "allman" => "bsd",
        "bsd" => "bsd",
        "gnu" => "gnu",
        "whitesmiths" => "ws",
        "ws" => "ws"
    );
    protected $sDescription = 'Filter the code in 4 different indent styles: K&R, Allman, Whitesmiths and GNU';
    /**
     * __construct
     *
     * @param PHP_Beautifier $oBeaut    PHP_Beautifier Object
     * @param array          $aSettings Settings for the PHP_Beautifier
     *
     * @access public
     * @return void
     */
    public function __construct(PHP_Beautifier $oBeaut, $aSettings = array())
    {
        parent::__construct($oBeaut, $aSettings);
        $this->addSettingDefinition('style', 'text', 'Style for indent: K&R, Allman, Whitesmiths, GNU');
    }
    /**
     * __call
     *
     * @param mixed $sMethod Method name
     * @param mixed $aArgs   Method arguments
     *
     * @access public
     * @return void
     */
    public function __call($sMethod, $aArgs)
    {
        if (strtolower($this->getSetting('style')) == 'k&r') {
            return PHP_Beautifier_Filter::BYPASS;
        }
        $sNewMethod = $this->_getFunctionForStyle($sMethod);
        if (method_exists($this, $sNewMethod)) {
            call_user_func_array(
                array(
                    $this,
                    $sNewMethod
                ),
                $aArgs
            );
        } else {
            return PHP_Beautifier_Filter::BYPASS;
        }
    }
    /**
     * t_open_brace_bsd: Open braces in BSD style
     *
     * @param mixed $sTag The tag to be processed (})
     *
     * @access public
     * @return void
     */
    function t_open_brace_bsd($sTag)
    {
        $this->oBeaut->addNewLineIndent();
        $this->oBeaut->add($sTag);
        $this->oBeaut->incIndent();
        $this->_switchOpenBracketFix();
        $this->oBeaut->addNewLineIndent();
    }
    /**
     * t_close_brace_bsd: Close braces in BSD style
     *
     * @param mixed $sTag The tag to be processed ({)
     *
     * @access public
     * @return void
     */
    function t_close_brace_bsd($sTag)
    {
        if ($this->oBeaut->getMode('string_index') or $this->oBeaut->getMode('double_quote')) {
            $this->oBeaut->add($sTag);
        } else {
            $this->oBeaut->removeWhitespace();
            $this->oBeaut->decIndent();
            $this->_switchCloseBracketFix();
            $this->oBeaut->addNewLineIndent();
            $this->oBeaut->add($sTag);
            $this->oBeaut->addNewLineIndent();
        }
    }
    /**
     * t_open_brace_ws: Open braces in Whitesmiths style
     *
     * @param mixed $sTag The tag to be processed ({)
     *
     * @access public
     * @return void
     */
    function t_open_brace_ws($sTag)
    {
        $this->oBeaut->addNewLine();
        $this->oBeaut->incIndent();
        $this->oBeaut->addIndent();
        $this->_switchOpenBracketFix();
        $this->oBeaut->add($sTag);
        $this->oBeaut->addNewLineIndent();
    }
    /**
     * t_close_brace_ws: Close braces in Whitesmiths style
     *
     * @param mixed $sTag The tag to be processed (})
     *
     * @access public
     * @return void
     */
    function t_close_brace_ws($sTag)
    {
        if ($this->oBeaut->getMode('string_index') or $this->oBeaut->getMode('double_quote')) {
            $this->oBeaut->add($sTag);
        } else {
            $this->oBeaut->removeWhitespace();
            $this->_switchCloseBracketFix();
            $this->oBeaut->addNewLineIndent();
            $this->oBeaut->add($sTag);
            $this->oBeaut->decIndent();
            $this->oBeaut->addNewLineIndent();
        }
    }
    /**
     * t_close_brace_gnu: Close braces in GNU style
     *
     * @param mixed $sTag The tag to be processed (})
     *
     * @access public
     * @return void
     */
    function t_close_brace_gnu($sTag)
    {
        if ($this->oBeaut->getMode('string_index') or $this->oBeaut->getMode('double_quote')) {
            $this->oBeaut->add($sTag);
        } else {
            $iHalfSpace = floor($this->oBeaut->iIndentNumber/2);
            $this->oBeaut->removeWhitespace();
            $this->oBeaut->decIndent();
            $this->_switchCloseBracketFix();
            $this->oBeaut->addNewLineIndent();
            $this->oBeaut->add(str_repeat($this->oBeaut->sIndentChar, $iHalfSpace));
            $this->oBeaut->add($sTag);
            $this->oBeaut->addNewLineIndent();
        }
    }
    /**
     * t_open_brace_gnu: Open braces in GNU style
     *
     * @param mixed $sTag The tag to be processed ({)
     *
     * @access public
     * @return void
     */
    function t_open_brace_gnu($sTag)
    {
        $iHalfSpace = floor($this->oBeaut->iIndentNumber/2);
        $this->oBeaut->addNewLineIndent();
        $this->oBeaut->add(str_repeat($this->oBeaut->sIndentChar, $iHalfSpace));
        $this->oBeaut->add($sTag);
        $this->oBeaut->incIndent();
        $this->_switchOpenBracketFix();
        $this->oBeaut->addNewLineIndent();
    }
    /**
     * t_else: Else for bds, gnu & ws
     *
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void|PHP_Beautifier_Filter::BYPASS
     */
    function t_else($sTag)
    {
        if ($this->oBeaut->getPreviousTokenContent() == '}') {
            $this->oBeaut->removeWhitespace();
            $this->oBeaut->add(' ');
            $this->oBeaut->addNewLineIndent();
            $this->oBeaut->add(trim($sTag));
            if (!$this->oBeaut->isNextTokenContent('{')) {
                    $this->oBeaut->add(' ');
            }
        } else {
            return PHP_Beautifier_Filter::BYPASS;
        }
    }
    /**
     * Return the method for the defined style
     *
     * @param string $sMethod method to search
     *
     * @access private
     * @return string method renamed for the defined style
     */
    private function _getFunctionForStyle($sMethod)
    {
        $sStyle = strtolower($this->getSetting('style'));
        if (!array_key_exists($sStyle, $this->aAllowedStyles)) {
            throw (new Exception("Style " . $sStyle . " doesn't exists"));
        }
        return $sMethod . "_" . $this->aAllowedStyles[$sStyle];
    }

    /**
     * Fix PHP_Beautifier Bug #10019 (switch indent)
     * Patch     switch-indentation-fix
     * Developer arturkotyrba
     * @see http://pear.php.net/bugs/bug.php?id=10019&edit=12&patch=switch-indentation-fix&revision=latest
     * @return void
     */
    private function _switchOpenBracketFix()
    {
        if ($this->oBeaut->getControlSeq()==T_SWITCH) {
            $this->oBeaut->incIndent();
            $this->aIndentTag[] = 'switch';        
        } else { 
            $this->aIndentTag[] = 'non-switch';        
        }
    }
    /**
     * Fix PHP_Beautifier Bug #10019 (switch indent)
     * Patch     switch-indentation-fix
     * Developer arturkotyrba
     * @see http://pear.php.net/bugs/bug.php?id=10019&edit=12&patch=switch-indentation-fix&revision=latest
     * @return void
     */
    private function _switchCloseBracketFix()
    {
        if (array_pop($this->aIndentTag)=='switch') {
            $this->oBeaut->decIndent();            
        }
    }
}
?>
