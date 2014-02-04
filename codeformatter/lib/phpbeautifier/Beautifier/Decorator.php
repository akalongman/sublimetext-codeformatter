<?php
/**
 * Pattern: Decorator
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category  PHP
 * @package   PHP_Beautifier
 * @author    Claudio Bustos <cdx@users.sourceforge.com>
 * @copyright 2004-2010 Claudio Bustos
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version   CVS: $Id:$
 * @link      http://pear.php.net/package/PHP_Beautifier
 * @link      http://beautifyphp.sourceforge.net
 */
/**
 * Implements the Decorator Pattern
 *
 * @category  PHP
 * @package   PHP_Beautifier
 * @author    Claudio Bustos <cdx@users.sourceforge.com>
 * @copyright 2004-2006 Claudio Bustos
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_Beautifier
 * @link      http://beautifyphp.sourceforge.net
 */
abstract class PHP_Beautifier_Decorator implements PHP_Beautifier_Interface
{
    protected $oBeaut;
    /**
     * __construct 
     * 
     * @param PHP_Beautifier_Interface $oBeaut PHP_Beautifier Object
     *
     * @access protected
     * @return void
     */
    function __construct(PHP_Beautifier_Interface $oBeaut) 
    {
        $this->oBeaut = $oBeaut;
    }

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
        if (!method_exists($this->oBeaut, $sMethod)) {
            throw (new Exception("Method '$sMethod' doesn't exists"));
        } else {
            return call_user_func_array(
                array(
                    $this->oBeaut,
                    $sMethod
                ),
                $aArgs
            );
        }
    }
}
?>
