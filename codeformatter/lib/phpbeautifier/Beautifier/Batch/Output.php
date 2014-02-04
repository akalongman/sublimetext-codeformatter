<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * Abstract class to superclass all batch class
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
 * @subpackage Batch
 * @author     Claudio Bustos <cdx@users.sourceforge.com>
 * @copyright  2004-2010 Claudio Bustos
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id:$
 * @link       http://pear.php.net/package/PHP_Beautifier
 * @link       http://beautifyphp.sourceforge.net
 */
/**
 * Abstract class to superclass all batch class
 *
 * @category  PHP
 * @package   PHP_Beautifier
 * @author    Claudio Bustos <cdx@users.sourceforge.com>
 * @copyright 2004-2010 Claudio Bustos
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_Beautifier
 * @link      http://beautifyphp.sourceforge.net
 */
abstract class PHP_Beautifier_Batch_Output
{
    protected $oBatch;
    /**
     * __construct 
     * 
     * @param PHP_Beautifier_Batch $oBatch PHP_Beautifier_Batch Object
     *
     * @access public
     * @return void
     */
    public function __construct(PHP_Beautifier_Batch $oBatch)
    {
        $this->oBatch = $oBatch;
    }
    /**
     * beautifierSetInputFile 
     * 
     * @param mixed $sFile Input file
     *
     * @access protected
     * @return void
     */
    protected function beautifierSetInputFile($sFile)
    {
        return $this->oBatch->callBeautifier(
            $this,
            'setInputFile',
            array($sFile)
        );
    }
    /**
     * beautifierProcess 
     * 
     * @access protected
     * @return void
     */
    protected function beautifierProcess()
    {
        return $this->oBatch->callBeautifier($this, 'process');
    }
    /**
     * beautifierGet 
     * 
     * @access protected
     * @return void
     */
    protected function beautifierGet()
    {
        return $this->oBatch->callBeautifier($this, 'get');
    }
    /**
     * beautifierSave 
     * 
     * @param mixed $sFile Output file
     *
     * @access protected
     * @return void
     */
    protected function beautifierSave($sFile)
    {
        return $this->oBatch->callBeautifier(
            $this,
            'save',
            array($sFile)
        );
    }
    /**
     * get 
     * 
     * @access public
     * @return void
     */
    public function get()
    {
    }
    /**
     * save 
     * 
     * @access public
     * @return void
     */
    public function save()
    {
    }
}
?>
