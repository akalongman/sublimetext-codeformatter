<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Filter EqualsAlign: Align the equals symbols in contiguous lines.
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
 * Filter EqualsAlign: Align the equals symbols in contiguous lines.
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
class PHP_Beautifier_Filter_EqualsAlign extends PHP_Beautifier_Filter
{
    var $maxVarSize = 0;
    var $equalsToModify = array();
    /**
     * t_assigment 
     * 
     * @param mixed $sTag The tag to be processed
     *
     * @access public
     * @return void
     */
    public function t_assigment($sTag)
    {
        $var_size = 0;
        $counter = 1;
        $next = $this->oBeaut->getToken($this->oBeaut->iCount+$counter);
        $ends = 0;
        while ($next!="=" && $ends<2 && $next!=null) {
            if ($next == ";") {
                $ends++;
            }
            $counter++;
            $next = $this->oBeaut->getToken($this->oBeaut->iCount+$counter);
        }

        $counter = 1;
        $prev = $this->oBeaut->getToken($this->oBeaut->iCount-$counter);
        while ($prev[0]==T_WHITESPACE) {
            $counter++;
            $prev = $this->oBeaut->getToken($this->oBeaut->iCount-$counter);
        }
        while (($prev[0]==T_VARIABLE || $prev[0]==T_OBJECT_OPERATOR || $prev[0]==T_STRING ) && $prev!=null) {
            $var_size+=strlen($prev[1]);
            $counter++;
            $prev = $this->oBeaut->getToken($this->oBeaut->iCount-$counter);
        }

        if ($this->maxVarSize<$var_size) {
            $this->maxVarSize = $var_size;
        }
        $this->equalsToModify[] = array('position'=>count($this->oBeaut->aOut)+1,'size'=>$var_size);
        if ($next!="=") {
            foreach ($this->equalsToModify as $equal) {
                $this->oBeaut->aOut[$equal['position']-2]=$this->oBeaut->aOut[$equal['position']-2].str_repeat(" ", $this->maxVarSize-$equal['size']);
            }
            $this->maxVarSize = 0;
            $this->equalsToModify = array();
        }

        $this->oBeaut->add(" ".$sTag." ");
    }
}
?>
