<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * Create a list of functions and classes in the script
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
 * Create a list of functions and classes in the script
 * By default, this Filter puts the list at the beggining of the script.
 * If you want it in another position, put a comment like that
 * <pre>
 * // Class and Function List
 * </pre>
 * The script lookup for the string 'Class and Function List' in a comment and
 * replace the entire comment with the list
 * The settings are
 * - list_functions: List functions (0 or 1). Default:1
 * - list_classes:   List classes (0 or 1).   Default:1
 *
 * @todo List functions inside classes as methods
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
class PHP_Beautifier_Filter_ListClassFunction extends PHP_Beautifier_Filter
{
    protected $aFilterTokenFunctions = array(
        T_CLASS => 't_class',
        T_FUNCTION => 't_function',
        T_COMMENT => 't_comment',
        T_OPEN_TAG => 't_open_tag'
    );
    private $_aFunctions = array();
    private $_aClasses = array();
    private $_iComment;
    private $_iOpenTag = null;
    protected $aSettings = array(
        'list_functions' => true,
        'list_classes' => true
    );
    protected $sDescription = 'Create a list of functions and classes in the script';
    private $_aInclude = array(
        'functions' => true,
        'classes' => true
    );
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
        $this->addSettingDefinition('list_functions', 'bool', 'List Functions inside the file');
        $this->addSettingDefinition('list_classes', 'bool', 'List Classes inside the file');
    }
    /**
     * t_function 
     * 
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_function($sTag) 
    {
        if ($this->_aInclude['functions']) {
            $sNext = $this->oBeaut->getNextTokenContent(1);
            if ($sNext == '&') {
                $sNext.= $this->oBeaut->getNextTokenContent(2);
            }
            array_push($this->_aFunctions, $sNext);
        }
        return PHP_Beautifier_Filter::BYPASS;
    }
    /**
     * includeInList 
     * 
     * @param mixed $sTag   The tag to include in the list (classes|functions)
     * @param mixed $sValue The flag to enable or thisable the $sTag list (true|false)
     *
     * @access public
     * @return void
     */
    function includeInList($sTag, $sValue) 
    {
        $this->_aInclude[$sTag] = $sValue;
    }
    /**
     * t_class 
     * 
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_class($sTag) 
    {
        if ($this->_aInclude['classes']) {
            $sClassName = $this->oBeaut->getNextTokenContent(1);
            if ($this->oBeaut->isNextTokenConstant(T_EXTENDS, 2)) {
                $sClassName.= ' extends ' . $this->oBeaut->getNextTokenContent(3);
            }
            array_push($this->_aClasses, $sClassName);
        }
        return PHP_Beautifier_Filter::BYPASS;
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
        if (strpos($sTag, 'Class and Function List') !== false) {
            $this->_iComment = $this->oBeaut->iCount;
        }
        return PHP_Beautifier_Filter::BYPASS;
    }
    /**
     * t_open_tag 
     * 
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    function t_open_tag($sTag) 
    {
        if (is_null($this->_iOpenTag)) {
            $this->_iOpenTag = $this->oBeaut->iCount;
        }
        return PHP_Beautifier_Filter::BYPASS;
    }
    /**
     * postProcess 
     * 
     * @access public
     * @return void
     */
    function postProcess() 
    {
        $sNL = $this->oBeaut->sNewLine;
        $aOut = array(
            "/**",
            "* Class and Function List:"
        );
        if ($this->getSetting('list_functions')) {
            $aOut[] = "* Function list:";
            foreach ($this->_aFunctions as $sFunction) {
                $aOut[] = "* - " . $sFunction . "()";
            }
        }
        if ($this->getSetting('list_classes')) {
            $aOut[] = "* Classes list:";
            foreach ($this->_aClasses as $sClass) {
                $aOut[] = "* - " . $sClass;
            }
        }
        $aOut[] = "*/";
        if ($this->_iComment) {
            // Determine the previous Indent
            $sComment = $this->oBeaut->getTokenAssocText($this->_iComment);
            if (preg_match("/" . addcslashes($sNL, "\r\n") . "([ \t]+)/ms", $sComment, $aMatch)) {
                $sPrevio = $sNL . $aMatch[1];
            } else {
                $sPrevio = $sNL;
            }
            $sText = implode($sPrevio, $aOut) . $sNL;
            $this->oBeaut->replaceTokenAssoc($this->_iComment, $sText);
        } else {
            $sPrevio = $sNL /*.str_repeat($this->oBeaut->sIndentChar, $this->oBeaut->iIndentNumber)*/;
            $sTag = trim($this->oBeaut->getTokenAssocText($this->_iOpenTag)) . "\n";
            $sText = $sPrevio . implode($sPrevio, $aOut);
            $this->oBeaut->replaceTokenAssoc($this->_iOpenTag, rtrim($sTag) . $sText . $sPrevio);
        }
    }
}
?>
