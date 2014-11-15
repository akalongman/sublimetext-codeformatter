<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * Space in Square: Add padding spaces within square brackets if contains a variable, ie. [ $a ],["key"],[]
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
 * @author     Davide Pedone <davide.pedone@gmail.com>
 * @copyright  2014 Davide Pedone
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 *
 */

class PHP_Beautifier_Filter_SpaceInSquare extends PHP_Beautifier_Filter
{

    /**
     * t_open_square_brace
     *
     * @param mixed $sTag The tag to be procesed
     *
     * @access public
     * @return void
     */
    
    public function t_open_square_brace( $sTag ) {

        if ( !$this->oBeaut->isNextTokenConstant(T_CLOSE_SQUARE_BRACE) && $this->oBeaut->isNextTokenConstant(T_VARIABLE) ) {

            $this->oBeaut->add( $sTag . ' ' );

        }else{

            return BYPASS;

        }

    }

    /**
     * t_close_square_brace
     *
     * @param mixed $sTag The tag to be procesed
     *
     * @access public
     * @return void
     */
    
    public function t_close_square_brace( $sTag ) {

        if ( !$this->oBeaut->isPreviousTokenConstant(T_OPEN_SQUARE_BRACE) && $this->oBeaut->isPreviousTokenConstant(T_VARIABLE) ) {

            $this->oBeaut->add( ' '. $sTag );

        }else{

            return BYPASS;

        }

    }

}
