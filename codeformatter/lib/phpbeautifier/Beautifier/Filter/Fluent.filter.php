<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * Filter Fluent: Create fluent style for multi-level object access.
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
 * @author     Jesús Espino <jespinog@gmail.com>
 * @copyright  2010 Jesús Espino
 * @license    http://www.php.net/license/3_0.txt PHP License 3.0
 * @version    CVS: $Id:$
 * @link       http://pear.php.net/package/PHP_Beautifier
 * @link       http://beautifyphp.sourceforge.net
 */
/**
 * Filter Fluent: Create fluent style for multi-level object access.
 * Ex.
 * <CODE>
 * $this
 *     ->addFile("a.txt")
 *     ->addFile("b.txt")
 *     ->addFile("c.txt");
 * $this->addFile("d.txt");
 * </CODE>
 *
 * @category   PHP
 * @package    PHP_Beautifier
 * @subpackage Filter
 * @author     Jesús Espino <jespinog@gmail.com>
 * @copyright  2010 Jesús Espino
 * @license    http://www.php.net/license/3_0.txt PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/PHP_Beautifier
 * @link       http://beautifyphp.sourceforge.net
 */
class PHP_Beautifier_Filter_Fluent extends PHP_Beautifier_Filter
{
    /**
     * t_object_operator 
     * 
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    public function t_object_operator($sTag)
    {
        $counter = 1;
        $next = $this->oBeaut->getToken($this->oBeaut->iCount + $counter);
        while (($next[0] != T_OBJECT_OPERATOR) && ($next != ";")) {
            $counter++;
            $next = $this->oBeaut->getToken($this->oBeaut->iCount + $counter);
        }
        $counter = 1;
        $prev = $this->oBeaut->getToken($this->oBeaut->iCount - $counter);
        while (($prev[0] != T_OBJECT_OPERATOR) && ($prev[0] != T_VARIABLE)) {
            $counter++;
            $prev = $this->oBeaut->getToken($this->oBeaut->iCount - $counter);
        }
        $this->oBeaut->removeWhiteSpace();
        if ($next[0] == T_OBJECT_OPERATOR || $prev[0] == T_OBJECT_OPERATOR) {
            $this->oBeaut->addNewLineIndent();
            for ($x = 0;$x < $this->oBeaut->getIndentNumber();$x++) {
                $this->oBeaut->add($this->oBeaut->getIndentChar());
            }
        }
        $this->oBeaut->add($sTag);
    }
}
?>
