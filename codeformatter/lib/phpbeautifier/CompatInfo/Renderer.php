<?php
/**
 * Copyright (c) 2008-2009, Laurent Laville <pear@laurent-laville.org>
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the authors nor the names of its contributors
 *       may be used to endorse or promote products derived from this software
 *       without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS
 * BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * PHP versions 4 and 5
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version  CVS: $Id: Renderer.php,v 1.9 2009/01/02 10:18:47 farell Exp $
 * @link     http://pear.php.net/package/PHP_CompatInfo
 * @since    File available since Release 1.8.0b2
 */

/**
 * Base class used by all renderers
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version  Release: 1.9.0
 * @link     http://pear.php.net/package/PHP_CompatInfo
 * @since    Class available since Release 1.8.0b2
 * @abstract
 */
class PHP_CompatInfo_Renderer
{
    /**
     * PHP_CompatInfo_Parser instance
     *
     * @var    object
     * @access private
     */
    var $_parser;

    /**
     * @var    mixed    Progress bar render options (available only on CLI sapi)
     * @since  1.8.0b1
     * @access private
     */
    var $_pbar;

    /**
     * @var    string   End of line string (depending of server API)
     * @access public
     */
    var $eol;

    /**
     * Silent mode. Display or not extra info messages.
     *
     * @var    boolean
     * @access public
     */
    var $silent;

    /**
     * Data source parsed final results
     *
     * @var    array
     * @access public
     */
    var $parseData;

    /**
     * All console arguments that have been parsed and recognized
     *
     * @var   array
     * @since 1.8.0RC1
     * @access public
     */
    var $args;

    /**
     * A hash containing any additional configuration of specific driver
     *
     * @var    array
     * @since  1.8.0RC1
     * @access public
     */
    var $conf;

    /**
     * Base Renderer Class constructor
     *
     * Base Renderer Class constructor (ZE1) for PHP4
     *
     * @param object &$parser Instance of the parser (model of MVC pattern)
     * @param array  $conf    A hash containing any additional configuration
     *
     * @access public
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function PHP_CompatInfo_Renderer(&$parser, $conf)
    {
        PHP_CompatInfo_Renderer::__construct($parser, $conf);
    }

    /**
     * Base Renderer Class constructor
     *
     * Base Renderer Class constructor (ZE2) for PHP5+
     *
     * @param object &$parser Instance of the parser (model of MVC pattern)
     * @param array  $conf    A hash containing any additional configuration
     *
     * @access public
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function __construct(&$parser, $conf)
    {
        $this->_parser = $parser;

        $args = array(
            'summarize' => false,
            'output-level' => 31,
            'verbose' => 0
            );
        if (isset($conf['args']) && is_array($conf['args'])) {
            $this->args = array_merge($args, $conf['args']);
            unset($conf['args']);
        } else {
            $this->args = $args;
        }
        $this->conf = $conf;

        if (php_sapi_name() == 'cli') {
            // when running the CLI version, take arguments from console
            if (isset($this->args['progress'])) {
                $conf['progress'] = $this->args['progress'];
                $conf['silent']   = false;
            }
            $this->eol = PHP_EOL;
        } else {
            $this->eol = '<br/>'. PHP_EOL;
        }

        // activate (or not) the silent mode
        if (!isset($conf['silent'])) {
            $this->silent = true;  // default behavior
        } else {
            $this->silent = (bool) $conf['silent'];
        }

        if (isset($conf['progress']) && $conf['progress'] == 'bar') {
            // wait style = progress bar prefered (if available)
            $progressBar = 'Console/ProgressBar.php';
            if (php_sapi_name() == 'cli'
                && PHP_CompatInfo_Renderer::isIncludable($progressBar)) {

                include_once $progressBar;

                // default progress bar render options
                $default = array('formatString' => '- %fraction% files' .
                                                   ' [%bar%] %percent%' .
                                                   ' Elapsed Time: %elapsed%',
                                 'barfill' => '=>',
                                 'prefill' => '-',
                                 'options' => array());

                // apply custom render options if given
                if (isset($conf['progressbar'])) {
                    $pbar = $conf['progressbar'];
                } else {
                    $pbar = array();
                }
                $this->_pbar = array_merge($default, $pbar);
            } else {
                // no progress bar available
                $this->_pbar = false;
            }
        } else {
            // wait style = text prefered
            $this->_pbar = false;
        }

        // register the compatInfo view as observer
        $parser->addListener(array(&$this, 'update'));
    }

    /**
     * Create required instance of the Output 'driver'.
     *
     * Creates a concrete instance of the renderer depending of $type
     *
     * @param object &$parser A concrete instance of the parser
     * @param string $type    (optional) Type of instance required, case insensitive
     * @param array  $conf    (optional) A hash containing any additional
     *                        configuration information that a subclass might need.
     *
     * @return object PHP_CompatInfo_Renderer A concrete PHP_CompatInfo_Renderer
     *                                        instance, or null on error.
     * @access public
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function &factory(&$parser, $type = 'array', $conf = array())
    {
        $class = 'PHP_CompatInfo_Renderer_' . ucfirst(strtolower($type));
        $file  = str_replace('_', '/', $class) . '.php';

        /**
         * Attempt to include our version of the named class, but don't treat
         * a failure as fatal.  The caller may have already included their own
         * version of the named class.
         */
        if (!PHP_CompatInfo_Renderer::_classExists($class)) {
            include_once $file;
        }

        // If the class exists, return a new instance of it.
        if (PHP_CompatInfo_Renderer::_classExists($class)) {
            $instance =& new $class($parser, $conf);
        } else {
            $instance = null;
        }

        return $instance;
    }

    /**
     * Update the current view
     *
     * Interface to update the view with current information.
     * Listen events produced by Event_Dispatcher and the PHP_CompatInfo_Parser
     *
     * @param object &$auditEvent Instance of Event_Dispatcher
     *
     * @return void
     * @access public
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function update(&$auditEvent)
    {
        $notifyName = $auditEvent->getNotificationName();
        $notifyInfo = $auditEvent->getNotificationInfo();

        switch ($notifyName) {
        case PHP_COMPATINFO_EVENT_AUDITSTARTED :
            $this->startWaitProgress($notifyInfo['dataCount']);
            break;
        case PHP_COMPATINFO_EVENT_AUDITFINISHED :
            if (!isset($this->parseData)) {
                // invalid data source
                $this->parseData = false;
            }
            $this->endWaitProgress();
            $this->display();
            break;
        case PHP_COMPATINFO_EVENT_FILESTARTED :
            $this->stillWaitProgress($notifyInfo['filename'],
                                     $notifyInfo['fileindex']);
            break;
        case PHP_COMPATINFO_EVENT_CODESTARTED :
            $this->stillWaitProgress($notifyInfo['stringdata'],
                                     $notifyInfo['stringindex']);
            break;
        case PHP_COMPATINFO_EVENT_FILEFINISHED :
        case PHP_COMPATINFO_EVENT_CODEFINISHED :
            $this->parseData = $notifyInfo;
            break;
        }
    }

    /**
     * Initialize the wait process
     *
     * Initialize the wait process, with a simple message or a progress bar.
     *
     * @param integer $maxEntries Number of source to parse
     *
     * @return void
     * @access public
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function startWaitProgress($maxEntries)
    {
        if ($this->silent == false) {
            // obey at silent mode protocol
            if ($maxEntries == 0) {
                // protect against invalid data source
                $this->_pbar = false;
            }
            if ($this->_pbar) {
                $this->_pbar = new Console_ProgressBar($this->_pbar['formatString'],
                                               $this->_pbar['barfill'],
                                               $this->_pbar['prefill'],
                                               78,
                                               $maxEntries,
                                               $this->_pbar['options']);
            } else {
                echo 'Wait while parsing data source ...'
                   . $this->eol;
            }
        }
    }

    /**
     * Update the wait message
     *
     * Update the wait message, or status of the progress bar
     *
     * @param string $source Source (file, string) currently parsing
     * @param string $index  Position of the $source in the data source list
     *                       to parse
     *
     * @return void
     * @access public
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function stillWaitProgress($source, $index)
    {
        if ($this->silent == false) {
            // obey at silent mode protocol
            if ($this->_pbar) {
                // update the progress bar
                $this->_pbar->update($index);
            } else {
                if (is_file($source)) {
                    echo 'Wait while parsing file "' . $source . '"'
                       . $this->eol;
                } else {
                    echo 'Wait while parsing string "' . $index . '"'
                       . $this->eol;
                }
            }
        }
    }

    /**
     * Finish the wait process
     *
     * Finish the wait process, by erasing the progress bar
     *
     * @return void
     * @access public
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function endWaitProgress()
    {
        if ($this->silent == false) {
            // obey at silent mode protocol
            if ($this->_pbar) {
                // remove the progress bar
                $this->_pbar->erase(true);
            }
        }
    }

    /**
     * Checks if in the include path
     *
     * Returns whether or not a file is in the include path
     *
     * @param string $file Path to filename to check if includable
     *
     * @static
     * @access public
     * @return boolean True if the file is in the include path, false otherwise
     * @since  version 1.7.0b4 (2008-04-03)
     */
    function isIncludable($file)
    {
        foreach (explode(PATH_SEPARATOR, get_include_path()) as $ip) {
            if (file_exists($ip . DIRECTORY_SEPARATOR . $file)
                && is_readable($ip . DIRECTORY_SEPARATOR . $file)
                ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Utility function which wraps PHP's class_exists() function to ensure
     * consistent behavior between PHP versions 4 and 5. Autoloading behavior
     * is always disabled.
     *
     * @param string $class The name of the class whose existence should be tested.
     *
     * @return bool         True if the class exists, false otherwiser.
     *
     * @static
     * @access private
     * @since  version 1.8.0b2 (2008-06-03)
     */
    function _classExists($class)
    {
        if (version_compare(PHP_VERSION, '5.0.0', 'ge')) {
            return class_exists($class, false);
        }

        return class_exists($class);
    }
}
?>