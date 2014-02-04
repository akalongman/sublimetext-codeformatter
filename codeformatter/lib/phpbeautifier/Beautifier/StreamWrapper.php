<?php
/**
 * Interface for StreamWrappers
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
 * @subpackage StreamWrapper
 * @author     Claudio Bustos <cdx@users.sourceforge.com>
 * @copyright  2004-2010 Claudio Bustos
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id:$
 * @link       http://pear.php.net/package/PHP_Beautifier
 * @link       http://beautifyphp.sourceforge.net
 */
/**
 * Interface for StreamWrapper
 * Read the documentation for streams wrappers on php manual.
 *
 * @category   PHP
 * @package    PHP_Beautifier
 * @subpackage StreamWrapper
 * @author     Claudio Bustos <cdx@users.sourceforge.com>
 * @copyright  2004-2010 Claudio Bustos
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/PHP_Beautifier
 * @link       http://beautifyphp.sourceforge.net
 */
interface PHP_Beautifier_StreamWrapper_Interface
{
    /**
     * stream_open 
     * 
     * @param mixed $sPath        Path
     * @param mixed $sMode        Mode
     * @param mixed $iOptions     Opitions 
     * @param mixed &$sOpenedPath Opened Path
     *
     * @access public
     * @return void
     */
    function stream_open($sPath, $sMode, $iOptions, &$sOpenedPath);
    /**
     * stream_close 
     * 
     * @access public
     * @return void
     */
    function stream_close();
    /**
     * stream_read 
     * 
     * @param mixed $iCount Count
     *
     * @access public
     * @return void
     */
    function stream_read($iCount);
    /**
     * stream_write 
     * 
     * @param mixed $sData Data
     *
     * @access public
     * @return void
     */
    function stream_write($sData);
    /**
     * stream_eof 
     * 
     * @access public
     * @return void
     */
    function stream_eof();
    /**
     * stream_tell 
     * 
     * @access public
     * @return void
     */
    function stream_tell();
    /**
     * stream_seek 
     * 
     * @param mixed $iOffset Offset
     * @param mixed $iWhence Whence
     *
     * @access public
     * @return void
     */
    function stream_seek($iOffset, $iWhence);
    /**
     * stream_flush 
     * 
     * @access public
     * @return void
     */
    function stream_flush();
    /**
     * stream_stat 
     * 
     * @access public
     * @return void
     */
    function stream_stat();
    /**
     * unlink 
     * 
     * @param mixed $sPath Path
     *
     * @access public
     * @return void
     */
    function unlink($sPath);
    /**
     * dir_opendir 
     * 
     * @param mixed $sPath    Path to dir
     * @param mixed $iOptions Options
     *
     * @access public
     * @return void
     */
    function dir_opendir($sPath, $iOptions);
    /**
     * dir_readdir 
     * 
     * @access public
     * @return void
     */
    function dir_readdir();
    /**
     * dir_rewinddir 
     * 
     * @access public
     * @return void
     */
    function dir_rewinddir();
    /**
     * dir_closedir 
     * 
     * @access public
     * @return void
     */
    function dir_closedir();
}
require_once 'StreamWrapper/Tarz.php';
?>
