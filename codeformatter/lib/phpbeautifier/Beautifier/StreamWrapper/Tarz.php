<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * Custom stream to handle Tar files (compressed and uncompressed)
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
 * Require Archive_Tar
 */
require_once 'Archive/Tar.php';
/**
 * Custom stream to handle Tar files (compressed and uncompressed)
 * Use URL tarz://myfile.tgz#myfile.php
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
class PHP_Beautifier_StreamWrapper_Tarz implements PHP_Beautifier_StreamWrapper_Interface
{
    public $oTar;
    public $sTar;
    public $sPath;
    public $sText;
    public $iSeek = 0;
    public $iSeekDir = 0;
    public $aContents = array();

    /**
     * stream_open 
     * 
     * @param mixed $sPath        Path
     * @param mixed $sMode        Mode
     * @param mixed $iOptions     Options
     * @param mixed &$sOpenedPath Opended Path
     *
     * @access public
     * @return void
     */
    function stream_open($sPath, $sMode, $iOptions, &$sOpenedPath) 
    {
        if ($this->getTar($sPath)) {
            //ArrayNested->off()
            $aContents = $this->oTar->listContent();
            if (array_filter($aContents, array($this, 'tarFileExists'))) {
                $this->sText = $this->oTar->extractInString($this->sPath);
                return true;
            }
        }
        //ArrayNested->on()
        return false;
    }
    /**
     * getTar 
     * 
     * @param mixed $sPath Path
     *
     * @access public
     * @return void
     */
    function getTar($sPath) 
    {
        if (preg_match("/tarz:\/\/(.*?(\.tgz|\.tar\.gz|\.tar\.bz2|\.tar)+)(?:#\/?(.*))*/", $sPath, $aMatch)) {
            $this->sTar = $aMatch[1];
            if (strpos($aMatch[2], 'gz') !== false) {
                $sCompress = 'gz';
            } elseif (strpos($aMatch[2], 'bz2') !== false) {
                $sCompress = 'bz2';
            } elseif (strpos($aMatch[2], 'tar') !== false) {
                $sCompress = false;
            } else {
                return false;
            }
            if (isset($aMatch[3])) {
                $this->sPath = $aMatch[3];
            }
            if (file_exists($this->sTar)) {
                $this->oTar = new Archive_Tar($this->sTar, $sCompress);
                return true;
            }
        } else {
            return false;
        }
    }
    /**
     * stream_close 
     * 
     * @access public
     * @return void
     */
    function stream_close() 
    {
        unset($this->oTar, $this->sText, $this->sPath, $this->iSeek);
    }
    /**
     * stream_read 
     * 
     * @param mixed $iCount Count
     *
     * @access public
     * @return void
     */
    function stream_read($iCount) 
    {
        $sRet = substr($this->sText, $this->iSeek, $iCount);
        $this->iSeek+= strlen($sRet);
        return $sRet;
    }
    /**
     * stream_write 
     * 
     * @param mixed $sData Data
     *
     * @access public
     * @return void
     */
    function stream_write($sData) 
    {
    }
    /**
     * stream_eof 
     * 
     * @access public
     * @return void
     */
    function stream_eof() 
    {
        // BUG in 5.0.0RC1<PHP<5.0.0.0RC4
        // DON'T USE EOF. Use ... another option :P
        if (version_compare(PHP_VERSION, '5.0.0.RC.1', ">=") and version_compare(PHP_VERSION, '5.0.0.RC.4', "<")) {
            return !($this->iSeek >= strlen($this->sText));
        } else {
            return $this->iSeek >= strlen($this->sText);
        }
    }
    /**
     * stream_tell 
     * 
     * @access public
     * @return void
     */
    function stream_tell() 
    {
        return $this->iSeek;
    }
    /**
     * stream_seek 
     * 
     * @param mixed $iOffset Offset
     * @param mixed $iWhence Whence
     *
     * @access public
     * @return void
     */
    function stream_seek($iOffset, $iWhence) 
    {
        switch ($iWhence) {
        case SEEK_SET:
            if ($iOffset<strlen($this->sText) and $iOffset >= 0) {
                $this->iSeek = $iOffset;
                return true;
            } else {
                return false;
            }
            break;

        case SEEK_CUR:
            if ($iOffset >= 0) {
                $this->iSeek+= $iOffset;
                return true;
            } else {
                return false;
            }
            break;

        case SEEK_END:
            if (strlen($this->sText) +$iOffset >= 0) {
                $this->iSeek = strlen($this->sText) +$iOffset;
                return true;
            } else {
                return false;
            }
            break;

        default:
            return false;
        }
    }
    /**
     * stream_flush 
     * 
     * @access public
     * @return void
     */
    function stream_flush() 
    {
    }
    /**
     * stream_stat 
     * 
     * @access public
     * @return void
     */
    function stream_stat() 
    {
    }
    /**
     * unlink 
     * 
     * @param mixed $sPath Path
     *
     * @access public
     * @return void
     */
    function unlink($sPath) 
    {
    }
    /**
     * dir_opendir 
     * 
     * @param mixed $sPath    Path
     * @param mixed $iOptions Options
     *
     * @access public
     * @return void
     */
    function dir_opendir($sPath, $iOptions) 
    {
        if ($this->getTar($sPath)) {
            array_walk(
                $this->oTar->listContent(),
                array(
                    $this,
                    'getFileList'
                )
            );
            return true;
        } else {
            return false;
        }
    }
    /**
     * dir_readdir 
     * 
     * @access public
     * @return void
     */
    function dir_readdir() 
    {
        if ($this->iSeekDir >= count($this->aContents)) {
            return false;
        } else {
            return $this->aContents[$this->iSeekDir++];
        }
    }
    /**
     * dir_rewinddir 
     * 
     * @access public
     * @return void
     */
    function dir_rewinddir() 
    {
        $this->iSeekDir = 0;
    }
    /**
     * dir_closedir 
     * 
     * @access public
     * @return void
     */
    function dir_closedir() 
    {
        //unset($this->oTar, $this->aContents, $this->sPath, $this->iSeekDir);
        //return true;
        
    }
    /**
     * getFileList 
     * 
     * @param mixed $aInput Input
     *
     * @access public
     * @return void
     */
    function getFileList($aInput) 
    {
        $this->aContents[] = $aInput['filename'];
    }
    /**
     * tarFileExists 
     * 
     * @param mixed $aInput Input
     *
     * @access public
     * @return void
     */
    function tarFileExists($aInput) 
    {
        return ($aInput['filename'] == $this->sPath and empty($aInput['typeflag']));
    }
}
stream_wrapper_register("tarz", "PHP_Beautifier_StreamWrapper_Tarz");

?>
