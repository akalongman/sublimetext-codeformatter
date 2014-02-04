<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * Manage compression of many files to one compressed file (gz or bz2)
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
 * Include Archive_Tar
 */
require_once 'Archive/Tar.php';
/**
 * PHP_Beautifier_Batch_FilesGz
 *
 * Manage compression of many files to one compressed file (gz or bz2)
 *
 * @category   PHP
 * @package    PHP_Beautifier
 * @subpackage Batch
 * @author     Claudio Bustos <cdx@users.sourceforge.com>
 * @copyright  2004-2010 Claudio Bustos
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/PHP_Beautifier
 * @link       http://beautifyphp.sourceforge.net
 */
abstract class PHP_Beautifier_Batch_Output_DirectoryTar extends PHP_Beautifier_Batch_Output
{
    /**
     * save 
     * 
     * @access public
     * @return void
     */
    public function save() 
    {
        $aInputFiles = $this->oBatch->getInputFiles();
        $sOutputPath = $this->oBatch->getOutputPath();
        $aOutputFiles = PHP_Beautifier_Common::getSavePath($aInputFiles, $sOutputPath);
        for ($x = 0;$x<count($aInputFiles);$x++) {
            unset($oTar);
            $oTar = $this->getTar($aOutputFiles[$x]);
            $this->beautifierSetInputFile($aInputFiles[$x]);
            $this->beautifierProcess();
            PHP_Beautifier_Common::createDir($aOutputFiles[$x]);
            $oTar->addString(basename($aOutputFiles[$x]), $this->beautifierGet());
        }
        return true;
    }

    /**
     * getTar 
     * 
     * @param mixed $sFileName File name
     *
     * @todo implements this
     * @access protected
     * @return void
     */
    protected function getTar($sFileName) 
    {
    }
}
?>
