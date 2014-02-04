<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * New Lines: Add extra new lines after o before specific contents
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
 * @since      File available since Release 0.1.2
 */
/**
 * New Lines: Add new lines after o before specific contents
 * The settings are 'before' and 'after'. As value, use a colon separated
 * list of contents or tokens
 *
 * Command line example:
 *
 * <code>php_beautifier --filters "NewLines(before=if:switch:T_CLASS,after=T_COMMENT:function)"</code>
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
 * @since      Class available since Release 0.1.2
 */
class PHP_Beautifier_Filter_NewLines extends PHP_Beautifier_Filter
{
    protected $aSettings = array(
        'before' => false,
        'after' => false
    );
    protected $sDescription = 'Add new lines after or before specific contents';
    private $_aBeforeToken = array();
    private $_aBeforeContent = array();
    private $_aAfterToken = array();
    private $_aAfterContent = array();
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
        $this->addSettingDefinition('before', 'text', 'List of contents to put new lines before, separated by colons');
        $this->addSettingDefinition('after', 'text', 'List of contents to put new lines after, separated by colons');
        if (!empty($this->aSettings['before'])) {
            $aBefore = explode(':', str_replace(' ', '', $this->aSettings['before']));
            foreach ($aBefore as $sBefore) {
                if (defined($sBefore)) {
                    $this->_aBeforeToken[] = constant($sBefore);
                } else {
                    $this->_aBeforeContent[] = $sBefore;
                }
            }
        }
        if (!empty($this->aSettings['after'])) {
            $aAfter = explode(':', str_replace(' ', '', $this->aSettings['after']));
            foreach ($aAfter as $sAfter) {
                if (defined($sAfter)) {
                    $this->_aAfterToken[] = constant($sAfter);
                } else {
                    $this->_aAfterContent[] = $sAfter;
                }
            }
        }
        $this->oBeaut->setNoDeletePreviousSpaceHack();
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
        $iToken = $this->aToken[0];
        $sContent = $this->aToken[1];
        if (in_array($sContent, $this->_aBeforeContent) or in_array($iToken, $this->_aBeforeToken)) {
            $this->oBeaut->addNewLineIndent();
        }
        if (in_array($sContent, $this->_aAfterContent) or in_array($iToken, $this->_aAfterToken)) {
            $this->oBeaut->setBeforeNewLine($this->oBeaut->sNewLine . '/**ndps**/');
        }
        return PHP_Beautifier_Filter::BYPASS;
    }
}
?>
