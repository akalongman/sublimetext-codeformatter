<?php
/**
 * Getargs.php 
 * 
 * @category  Extension
 * @package   Console_Getargs
 * @author    Bertrand Mansion <bmansion@mamasam.com>
 * @copyright 2004 Bertrand Mansion
 * @license   http://www.php.net/license/3_0.txt PHP License 3.0
 * @version   1.3.5
 * @link      http://pear.php.net/package/Console_Getargs
 */

/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 2004 The PHP Group                                     |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Bertrand Mansion <bmansion@mamasam.com>                      |
// +----------------------------------------------------------------------+
//
// $Id: Getargs.php 304313 2010-10-11 14:37:56Z jespino $
require_once 'PEAR.php';
/**#@+
 * Error Constants
*/
/**
 * Wrong configuration
 *
 * This error will be TRIGGERed when a configuration error is found, 
 * it will also issue a WARNING.
 */
define('CONSOLE_GETARGS_ERROR_CONFIG', -1);

/**
 * User made an error
 *
 * This error will be RETURNed when a bad parameter 
 * is found in the command line, for example an unknown parameter
 * or a parameter with an invalid number of options.
 */
define('CONSOLE_GETARGS_ERROR_USER', -2);

/**
 * Help text wanted
 *
 * This error will be RETURNed when the user asked to 
 * see the help by using <kbd>-h</kbd> or <kbd>--help</kbd> in the command line, you can then print
 * the help ascii art text by using the {@link Console_Getargs::getHelp()} method
 */
define('CONSOLE_GETARGS_HELP', -3);

/**
 * Option name for application "parameters"
 *
 * Parameters are the options an application needs to function.
 * The two files passed to the diff command would be considered
 * the parameters. These are different from other options in that
 * they do not need an option name passed on the command line.
 */
define('CONSOLE_GETARGS_PARAMS', 'parameters');
/**#@-*/

/**
 * Command-line arguments parsing class
 * 
 * This implementation was freely inspired by a python module called
 * getargs by Vinod Vijayarajan and a perl CPAN module called
 * Getopt::Simple by Ron Savage
 *
 * This class implements a Command Line Parser that your cli applications
 * can use to parse command line arguments found in $_SERVER['argv'] or a
 * user defined array.
 * 
 * It gives more flexibility and error checking than Console_Getopt. It also
 * performs some arguments validation and is capable to return a formatted
 * help text to the user, based on the configuration it is given.
 * 
 * The class provides the following capabilities:
 * - Each command line option can take an arbitrary number of arguments.
 * - Makes the distinction between switches (options without arguments) 
 *   and options that require arguments.
 * - Recognizes 'single-argument-options' and 'default-if-set' options.
 * - Switches and options with arguments can be interleaved in the command
 *   line.
 * - You can specify the maximum and minimum number of arguments an option
 *   can take. Use -1 if you don't want to specify an upper bound.
 * - Specify the default arguments to an option
 * - Short options can be more than one letter in length.
 * - A given option may be invoked by multiple names (aliases).
 * - Understands by default the --help, -h options
 * - Can return a formatted help text
 * - Arguments may be specified using the '=' syntax also.
 * - Short option names may be concatenated (-dvw 100 == -d -v -w 100)
 * - Can define a default option that will take any arguments added without
 *   an option name
 * - Can pass in a user defined array of arguments instead of using
 *   $_SERVER['argv']
 * 
 * @todo      Implement the parsing of comma delimited arguments
 * @todo      Implement method for turning assocative arrays into command
 *            line arguments (ex. array('d' => true, 'v' => 2) -->
 *            array('-d', '-v', 2))
 *            
 * @category  Extension
 * @package   Console_Getargs
 * @author    Bertrand Mansion <bmansion@mamasam.com>
 * @copyright 2004 Bertrand Mansion
 * @license   http://www.php.net/license/3_0.txt PHP License 3.0
 * @version   1.3.5
 * @link      http://pear.php.net/package/Console_Getargs
 */
class Console_Getargs
{
    /**
     * Factory creates a new {@link Console_Getargs_Options} object
     *
     * This method will return a new {@link Console_Getargs_Options}
     * built using the given configuration options. If the configuration
     * or the command line options contain errors, the returned object will 
     * in fact be a PEAR_Error explaining the cause of the error.
     *
     * Factory expects an array as parameter.
     * The format for this array is:
     * <pre>
     * array(
     *  longname => array('short'   => Short option name,
     *                    'max'     => Maximum arguments for option,
     *                    'min'     => Minimum arguments for option,
     *                    'default' => Default option argument,
     *                    'desc'    => Option description)
     * )
     * </pre>
     * 
     * If an option can be invoked by more than one name, they have to be defined
     * by using | as a separator. For example: name1|name2
     * This works both in long and short names.
     *
     * max/min are the most/least number of arguments an option accepts.
     *
     * The 'defaults' field is optional and is used to specify default
     * arguments to an option. These will be assigned to the option if 
     * it is *not* used in the command line.
     * Default arguments can be:
     * - a single value for options that require a single argument,
     * - an array of values for options with more than one possible arguments.
     * Default argument(s) are mandatory for 'default-if-set' options.
     *
     * If max is 0 (option is just a switch), min is ignored.
     * If max is -1, then the option can have an unlimited number of arguments 
     * greater or equal to min.
     * 
     * If max == min == 1, the option is treated as a single argument option.
     * 
     * If max >= 1 and min == 0, the option is treated as a
     * 'default-if-set' option. This implies that it will get the default argument
     * only if the option is used in the command line without any value.
     * (Note: defaults *must* be specified for 'default-if-set' options) 
     *
     * If the option is not in the command line, the defaults are 
     * *not* applied. If an argument for the option is specified on the command
     * line, then the given argument is assigned to the option.
     * Thus:
     * - a --debug in the command line would cause debug = 'default argument'
     * - a --debug 2 in the command line would result in debug = 2
     *  if not used in the command line, debug will not be defined.
     * 
     * Example 1.
     * <code>
     * require_once 'Console/Getargs.php';
     *
     * $args =& Console_Getargs::factory($config);
     * 
     * if (PEAR::isError($args)) {
     *  if ($args->getCode() === CONSOLE_GETARGS_ERROR_USER) {
     *    echo Console_Getargs::getHelp($config, null, $args->getMessage())."\n";
     *  } else if ($args->getCode() === CONSOLE_GETARGS_HELP) {
     *    echo Console_Getargs::getHelp($config)."\n";
     *  }
     *  exit;
     * }
     * 
     * echo 'Verbose: '.$args->getValue('verbose')."\n";
     * if ($args->isDefined('bs')) {
     *  echo 'Block-size: '.(is_array($args->getValue('bs')) ? implode(', ', $args->getValue('bs'))."\n" : $args->getValue('bs')."\n");
     * } else {
     *  echo "Block-size: undefined\n";
     * }
     * echo 'Files: '.($args->isDefined('file') ? implode(', ', $args->getValue('file'))."\n" : "undefined\n");
     * if ($args->isDefined('n')) {
     *  echo 'Nodes: '.(is_array($args->getValue('n')) ? implode(', ', $args->getValue('n'))."\n" : $args->getValue('n')."\n");
     * } else {
     *  echo "Nodes: undefined\n";
     * }
     * echo 'Log: '.$args->getValue('log')."\n";
     * echo 'Debug: '.($args->isDefined('d') ? "YES\n" : "NO\n");
     * 
     * </code>
     *
     * If you don't want to require any option name for a set of arguments,
     * or if you would like any "leftover" arguments assigned by default, 
     * you can create an option named CONSOLE_GETARGS_PARAMS that will
     * grab any arguments that cannot be assigned to another option. The
     * rules for CONSOLE_GETARGS_PARAMS are still the same. If you specify
     * that two values must be passed then two values must be passed. See
     * the example script for a complete example.
     * 
     * @param array $config    associative array with keys being the
     * @param array $arguments numeric array of command line arguments
     *
     * @access public           
     * @return object|PEAR_Error a newly created Console_Getargs_Options
     *                           object or a PEAR_Error object on error
     */
    function &factory($config = array(), $arguments = null)
    {
        // Create the options object.
        $obj = & new Console_Getargs_Options();

        // Try to set up the arguments.
        $err = $obj->init($config, $arguments);
        if ($err !== true) {
            return $err;
        }

        // Try to set up the options.
        $err = $obj->buildMaps();
        if ($err !== true) {
            return $err;
        }

        // Get the options and arguments from the command line.
        $err = $obj->parseArgs();
        if ($err !== true) {
            return $err;
        }

        // Set arguments for options that have defaults.
        $err = $obj->setDefaults();
        if ($err !== true) {
            return $err;
        }

        // Double check that all required options have been passed.
        $err = $obj->checkRequired();
        if ($err !== true) {
            return $err;
        }

        // All is good.
        return $obj;
    }

    /**
     * Returns an ascii art version of the help
     *
     * This method uses the given configuration and parameters
     * to create and format an help text for the options you defined
     * in your config parameter. You can supply a header and a footer
     * as well as the maximum length of a line. If you supplied
     * descriptions for your options, they will be used as well.
     *
     * By default, it returns something like this:
     * <pre>
     * Usage: myscript.php [-dv --formats] <-fw --filters>
     * 
     * -f --files values(2)          Set the source and destination image files.
     * -w --width=&lt;value&gt;      Set the new width of the image.
     * -d --debug                    Switch to debug mode.
     * --formats values(1-3)         Set the image destination format. (jpegbig,
     *                               jpegsmall)
     * -fi --filters values(1-...)   Set the filters to be applied to the image upon
     *                               conversion. The filters will be used in the order
     *                               they are set.
     * -v --verbose (optional)value  Set the verbose level. (3)
     * </pre>
     *
     * @param array  $config     your args configuration
     * @param string $helpHeader the header for the help. If it is left null,
     *                           a default header will be used, starting by Usage:
     * @param string $helpFooter the footer for the help. This could be used
     *                           to supply a description of the error the user made
     * @param int    $maxlength  help lines max length
     * @param int    $indent     the indent for the options
     *
     * @access public
     * @return string the formatted help text
     */
    function getHelp($config, $helpHeader = null, $helpFooter = '', $maxlength = 78, $indent = 0)
    {
        // Start with an empty help message and build it piece by piece
        $help = '';

        // If no user defined header, build the default header.
        if (!isset($helpHeader)) {
            // Get the optional, required and "paramter" names for this config.
            list($optional, $required, $params) = Console_Getargs::getOptionalRequired($config);
            // Start with the file name.
            if (isset($_SERVER['SCRIPT_NAME'])) {
                $filename = basename($_SERVER['SCRIPT_NAME']);
            } else {
                $filename = $argv[0];
            }
            $helpHeader = 'Usage: ' . $filename . ' ';
            // Add the optional arguments and required arguments.
            $helpHeader.= $optional . ' ' . $required . ' ';
            // Add any parameters that are needed.
            $helpHeader.= $params . "\n\n";
        }

        // Create an indent string to be prepended to each option row.
        $indentStr = str_repeat(' ', (int)$indent);

        // Go through all of the short options to get a padding value.
        $v = array_values($config);
        $shortlen = 0;
        foreach ($v as $item) {
            if (isset($item['short'])) {
                $shortArr = explode('|', $item['short']);

                if (strlen($shortArr[0]) > $shortlen) {
                    $shortlen = strlen($shortArr[0]);
                }
            }
        }

        // Add two to account for the extra characters we add automatically.
        $shortlen+= 2;

        // Build the list of options and definitions.
        $i = 0;
        foreach ($config as $long => $def) {

            // Break the names up if there is more than one for an option.
            $shortArr = array();
            if (isset($def['short'])) {
                $shortArr = explode('|', $def['short']);
            }
            $longArr = explode('|', $long);

            // Column one is the option name displayed as "-short, --long [additional info]"
            // Start with the indent string.
            $col1[$i] = $indentStr;
            // Add the short option name.
            $col1[$i].= str_pad(!empty($shortArr) ? '-' . $shortArr[0] . ' ' : '', $shortlen);
            // Add the long option name.
            $col1[$i].= '--' . $longArr[0];

            // Get the min and max to show needed/optional values.
            // Cast to int to avoid complications elsewhere.
            $max = (int)$def['max'];
            $min = isset($def['min']) ? (int)$def['min'] : $max;

            if ($max === 1 && $min === 1) {
                // One value required.
                $col1[$i].= '=<value>';
            } else if ($max > 1) {
                if ($min === $max) {
                    // More than one value needed.
                    $col1[$i].= ' values(' . $max . ')';
                } else if ($min === 0) {
                    // Argument takes optional value(s).
                    $col1[$i].= ' values(optional)';
                } else {
                    // Argument takes a range of values.
                    $col1[$i].= ' values(' . $min . '-' . $max . ')';
                }
            } else if ($max === 1 && $min === 0) {
                // Argument can take at most one value.
                $col1[$i].= ' (optional)value';
            } else if ($max === - 1) {
                // Argument can take unlimited values.
                if ($min > 0) {
                    $col1[$i].= ' values(' . $min . '-...)';
                } else {
                    $col1[$i].= ' (optional)values';
                }
            }

            // Column two is the description if available.
            if (isset($def['desc'])) {
                $col2[$i] = $def['desc'];
            } else {
                $col2[$i] = '';
            }
            // Add the default value(s) if there are any/
            if (isset($def['default'])) {
                if (is_array($def['default'])) {
                    $col2[$i].= ' (' . implode(', ', $def['default']) . ')';
                } else {
                    $col2[$i].= ' (' . $def['default'] . ')';
                }
            }
            $i++;
        }

        // Figure out the maximum length for column one.
        $arglen = 0;
        foreach ($col1 as $txt) {
            $length = strlen($txt);
            if ($length > $arglen) {
                $arglen = $length;
            }
        }

        // The maximum length for each description line.
        $desclen = $maxlength - $arglen;
        $padding = str_repeat(' ', $arglen);
        foreach ($col1 as $k => $txt) {
            // Wrap the descriptions.
            if (strlen($col2[$k]) > $desclen) {
                $desc = wordwrap($col2[$k], $desclen, "\n  " . $padding);
            } else {
                $desc = $col2[$k];
            }
            // Push everything together.
            $help.= str_pad($txt, $arglen) . '  ' . $desc . "\n";
        }

        // Put it all together.
        return $helpHeader . $help . $helpFooter;
    }

    /**
     * Parse the config array to determine which flags are
     * optional and which are required.
     *
     * To make the help header more descriptive, the options
     * are shown seperated into optional and required flags.
     * When possible the short flag is used for readability.
     * Optional items (including "parameters") are surrounded
     * in square braces ([-vd]). Required flags are surrounded
     * in angle brackets (<-wf>). 
     *
     * This method may be called statically.
     *
     * @param array &$config The config array.
     *
     * @access  public  
     * @return  array   
     * @author  Scott Mattocks
     * @package Console_Getargs
     */
    function getOptionalRequired(&$config)
    {
        // Parse the config array and look for optional/required
        // tags.
        $optional = '';
        $optionalHasShort = false;
        $required = '';
        $requiredHasShort = false;

        ksort($config);
        foreach ($config as $long => $def) {

            // We only really care about the first option name.
            $long = explode('|', $long);
            $long = reset($long);

            // Treat the "parameters" specially.
            if ($long == CONSOLE_GETARGS_PARAMS) {
                continue;
            }
            if (isset($def['short'])) {
                // We only really care about the first option name.
                $def['short'] = explode('|', $def['short']);
                $def['short'] = reset($def['short']);
            }

            if (!isset($def['min']) || $def['min'] == 0 || isset($def['default'])) {
                // This argument is optional.
                if (isset($def['short']) && strlen($def['short']) == 1) {
                    $optional = $def['short'] . $optional;
                    $optionalHasShort = true;
                } else {
                    $optional.= ' --' . $long;
                }
            } else {
                // This argument is required.
                if (isset($def['short']) && strlen($def['short']) == 1) {
                    $required = $def['short'] . $required;
                    $requiredHasShort = true;
                } else {
                    $required.= ' --' . $long;
                }
            }
        }

        // Check for "parameters" option.
        $params = '';
        if (isset($config[CONSOLE_GETARGS_PARAMS])) {
            for ($i = 1; $i <= max($config[CONSOLE_GETARGS_PARAMS]['max'], $config[CONSOLE_GETARGS_PARAMS]['min']); ++$i) {
                if ($config[CONSOLE_GETARGS_PARAMS]['max'] == - 1 || ($i > $config[CONSOLE_GETARGS_PARAMS]['min'] && $i <= $config[CONSOLE_GETARGS_PARAMS]['max']) || isset($config[CONSOLE_GETARGS_PARAMS]['default'])) {
                    // Parameter is optional.
                    $params.= '[param' . $i . '] ';
                } else {
                    // Parameter is required.
                    $params.= 'param' . $i . ' ';
                }
            }
        }
        // Add a leading - if needed.
        if ($optionalHasShort) {
            $optional = '-' . $optional;
        }

        if ($requiredHasShort) {
            $required = '-' . $required;
        }

        // Add the extra characters if needed.
        if (!empty($optional)) {
            $optional = '[' . $optional . ']';
        }
        if (!empty($required)) {
            $required = '<' . $required . '>';
        }

        return array($optional, $required, $params);
    }
} // end class Console_Getargs

/**
 * This class implements a wrapper to the command line options and arguments.
 *
 * @category Extension
 * @package  Console_Getargs
 * @author   Bertrand Mansion <bmansion@mamasam.com>
 * @license  http://www.php.net/license/3_0.txt PHP License 3.0
 * @link     http://pear.php.net/package/Console_Getargs
 */
class Console_Getargs_Options
{

    /**
     * Lookup to match short options name with long ones
     * @var    array  
     * @access private
     */
    var $_shortLong = array();

    /**
     * Lookup to match alias options name with long ones
     * @var    array  
     * @access private
     */
    var $_aliasLong = array();

    /**
     * Arguments set for the options
     * @var    array  
     * @access private
     */
    var $_longLong = array();

    /**
     * If arguments have been defined on cmdline
     * @var    array  
     * @access private
     */
    var $_defined = array();

    /**
     * Configuration set at initialization time
     * @var    array  
     * @access private
     */
    var $_config = array();

    /**
     * A read/write copy of argv
     * @var    array  
     * @access private
     */
    var $args = array();

    /**
     * Initializes the Console_Getargs_Options object
     *
     * @param array $config    configuration options
     * @param array $arguments arguments
     *
     * @access private                     
     * @throws CONSOLE_GETARGS_ERROR_CONFIG
     * @return true|PEAR_Error             
     */
    function init($config, $arguments = null)
    {
        if (is_array($arguments)) {
            // Use the user defined argument list.
            $this->args = $arguments;
        } else {
            // Command line arguments must be available.
            if (!isset($_SERVER['argv']) || !is_array($_SERVER['argv'])) {
                return PEAR::raiseError("Could not read argv", CONSOLE_GETARGS_ERROR_CONFIG, PEAR_ERROR_TRIGGER, E_USER_WARNING, 'Console_Getargs_Options::init()');
            }
            $this->args = $_SERVER['argv'];
        }

        // Drop the first argument if it doesn't begin with a '-'.
        if (isset($this->args[0] { 0 })
            && $this->args[0] { 0 } != '-'
        ) {
            array_shift($this->args);
        }
        $this->_config = $config;
        return true;
    }

    /**
     * Makes the lookup arrays for alias and short name mapping with long names
     *
     * @access private                     
     * @throws CONSOLE_GETARGS_ERROR_CONFIG
     * @return true|PEAR_Error             
     */
    function buildMaps()
    {
        foreach ($this->_config as $long => $def) {

            $longArr = explode('|', $long);
            $longname = $longArr[0];

            if (count($longArr) > 1) {
                // The fisrt item in the list is "the option".
                // The rest are aliases.
                array_shift($longArr);
                foreach ($longArr as $alias) {
                    // Watch out for duplicate aliases.
                    if (isset($this->_aliasLong[$alias])) {
                        return PEAR::raiseError('Duplicate alias for long option ' . $alias, CONSOLE_GETARGS_ERROR_CONFIG, PEAR_ERROR_TRIGGER, E_USER_WARNING, 'Console_Getargs_Options::buildMaps()');
                    }
                    $this->_aliasLong[$alias] = $longname;
                }
                // Add the real option name and defintion.
                $this->_config[$longname] = $def;
                // Get rid of the old version (name|alias1|...)
                unset($this->_config[$long]);
            }

            // Add the (optional) short option names.
            if (!empty($def['short'])) {
                // Short names
                $shortArr = explode('|', $def['short']);
                $short = $shortArr[0];
                if (count($shortArr) > 1) {
                    // The first item is "the option".
                    // The rest are aliases.
                    array_shift($shortArr);
                    foreach ($shortArr as $alias) {
                        // Watch out for duplicate aliases.
                        if (isset($this->_shortLong[$alias])) {
                            return PEAR::raiseError('Duplicate alias for short option ' . $alias, CONSOLE_GETARGS_ERROR_CONFIG, PEAR_ERROR_TRIGGER, E_USER_WARNING, 'Console_Getargs_Options::buildMaps()');
                        }
                        $this->_shortLong[$alias] = $longname;
                    }
                }
                // Add the real short option name.
                $this->_shortLong[$short] = $longname;
            }
        }
        return true;
    }

    /**
     * Parses the given options/arguments one by one
     *
     * @access private                   
     * @throws CONSOLE_GETARGS_HELP      
     * @throws CONSOLE_GETARGS_ERROR_USER
     * @return true|PEAR_Error           
     */
    function parseArgs()
    {
        // Go through the options and parse the arguments for each.
        for ($i = 0, $count = count($this->args); $i < $count; $i++) {
            $arg = $this->args[$i];
            if ($arg === '--help' || $arg === '-h') {
                // Asking for help breaks the loop.
                return PEAR::raiseError(null, CONSOLE_GETARGS_HELP, PEAR_ERROR_RETURN);

            }
            if ($arg === '--') {
                // '--' alone signals the start of "parameters"
                $err = $this->parseArg(CONSOLE_GETARGS_PARAMS, true, ++$i);
            } elseif (strlen($arg) > 1 && $arg{0} == '-' && $arg{1} == '-') {
                // Long name used (--option)
                $err = $this->parseArg(substr($arg, 2), true, $i);
            } else if (strlen($arg) > 1 && $arg{0} == '-') {
                // Short name used (-o)
                $err = $this->parseArg(substr($arg, 1), false, $i);
                if ($err === - 1) {
                    break;
                }
            } elseif (isset($this->_config[CONSOLE_GETARGS_PARAMS])) {
                // No flags at all. Try the parameters option.
                $tempI = & $i - 1;
                $err = $this->parseArg(CONSOLE_GETARGS_PARAMS, true, $tempI);
            } else {
                $err = PEAR::raiseError('Unknown argument ' . $arg, CONSOLE_GETARGS_ERROR_USER, PEAR_ERROR_RETURN, null, 'Console_Getargs_Options::parseArgs()');
            }
            if ($err !== true) {
                return $err;
            }
        }
        // Check to see if we need to reload the arguments
        // due to concatenated short names.
        if (isset($err) && $err === - 1) {
            return $this->parseArgs();
        }

        return true;
    }

    /**
     * Parses one option/argument
     *
     * @access private                   
     * @throws CONSOLE_GETARGS_ERROR_USER
     * @return true|PEAR_Error           
     */
    function parseArg($arg, $isLong, &$pos)
    {
        // If the whole short option isn't in the shortLong array
        // then break it into a bunch of switches.
        if (!$isLong && !isset($this->_shortLong[$arg]) && strlen($arg) > 1) {
            $newArgs = array();
            for ($i = 0; $i < strlen($arg); $i++) {
                if (array_key_exists($arg{$i}, $this->_shortLong)) {
                    $newArgs[] = '-' . $arg{$i};
                } else {
                    $newArgs[] = $arg{$i};
                }
            }
            // Add the new args to the array.
            array_splice($this->args, $pos, 1, $newArgs);

            // Reset the option values.
            $this->_longLong = array();
            $this->_defined = array();

            // Then reparse the arguments.
            return -1;
        }

        $opt = '';
        for ($i = 0; $i < strlen($arg); $i++) {
            // Build the option name one char at a time looking for a match.
            $opt.= $arg{$i};
            if ($isLong === false && isset($this->_shortLong[$opt])) {
                // Found a match in the short option names.
                $cmp = $opt;
                $long = $this->_shortLong[$opt];
            } elseif ($isLong === true && isset($this->_config[$opt])) {
                // Found a match in the long option names.
                $long = $cmp = $opt;
            } elseif ($isLong === true && isset($this->_aliasLong[$opt])) {
                // Found a match in the long option names.
                $long = $this->_aliasLong[$opt];
                $cmp = $opt;
            }
            if ($arg{$i} === '=') {
                // End of the option name when '=' is found.
                break;
            }
        }

        // If no option name is found, assume -- was passed.
        if ($opt == '') {
            $long = CONSOLE_GETARGS_PARAMS;
        }

        if (isset($long)) {
            // A match was found.
            if (strlen($arg) > strlen($cmp)) {
                // Seperate the argument from the option.
                // Ex: php test.php -f=image.png
                //     $cmp = 'f'
                //     $arg = 'f=image.png'
                $arg = substr($arg, strlen($cmp));
                // Now $arg = '=image.png'
                if ($arg{0} === '=') {
                    $arg = substr($arg, 1);
                    // Now $arg = 'image.png'
                }
            } else {
                // No argument passed for option.
                $arg = '';
            }
            // Set the options value.
            return $this->setValue($long, $arg, $pos);
        }
        return PEAR::raiseError('Unknown argument ' . $opt, CONSOLE_GETARGS_ERROR_USER, PEAR_ERROR_RETURN, null, 'Console_Getargs_Options::parseArg()');
    }

    /**
     * Set the option arguments
     *
     * @access private                     
     * @throws CONSOLE_GETARGS_ERROR_CONFIG
     * @throws CONSOLE_GETARGS_ERROR_USER  
     * @return true|PEAR_Error             
     */
    function setValue($optname, $value, &$pos)
    {
        if (!isset($this->_config[$optname]['max'])) {
            // Max must be set for every option even if it is zero or -1.
            return PEAR::raiseError('No max parameter set for ' . $optname, CONSOLE_GETARGS_ERROR_CONFIG, PEAR_ERROR_TRIGGER, E_USER_WARNING, 'Console_Getargs_Options::setValue()');
        }

        $max = (int)$this->_config[$optname]['max'];
        $min = isset($this->_config[$optname]['min']) ? (int)$this->_config[$optname]['min'] : $max;

        // A value was passed after the option.
        if ($value !== '') {
            // Argument is like -v5
            if ($min == 1 && $max > 0) {
                // At least one argument is required for option.
                $this->updateValue($optname, $value);
                return true;
            }
            if ($max === 0) {
                // Argument passed but not expected.
                return PEAR::raiseError('Argument ' . $optname . ' does not take any value', CONSOLE_GETARGS_ERROR_USER, PEAR_ERROR_RETURN, null, 'Console_Getargs_Options::setValue()');
            }
            // Not enough arguments passed for this option.
            return PEAR::raiseError('Argument ' . $optname . ' expects more than one value', CONSOLE_GETARGS_ERROR_USER, PEAR_ERROR_RETURN, null, 'Console_Getargs_Options::setValue()');
        }

        if ($min === 1 && $max === 1) {
            // Argument requires 1 value
            // If optname is "parameters" take a step back.
            if ($optname == CONSOLE_GETARGS_PARAMS) {
                $pos--;
            }
            if (isset($this->args[$pos + 1]) && $this->isValue($this->args[$pos + 1])) {
                // Set the option value and increment the position.
                $this->updateValue($optname, $this->args[$pos + 1]);
                $pos++;
                return true;
            }
            // What we thought was the argument was really the next option.
            return PEAR::raiseError('Argument ' . $optname . ' expects one value', CONSOLE_GETARGS_ERROR_USER, PEAR_ERROR_RETURN, null, 'Console_Getargs_Options::setValue()');
        } else if ($max === 0) {
            // Argument is a switch
            if (isset($this->args[$pos + 1]) && $this->isValue($this->args[$pos + 1])) {
                // What we thought was the next option was really an argument for this option.
                // First update the value
                $this->updateValue($optname, true);
                // Then try to assign values to parameters.
                if (isset($this->_config[CONSOLE_GETARGS_PARAMS])) {
                    return $this->setValue(CONSOLE_GETARGS_PARAMS, '', ++$pos);
                } else {
                    return PEAR::raiseError('Argument ' . $optname . ' does not take any value', CONSOLE_GETARGS_ERROR_USER, PEAR_ERROR_RETURN, null, 'Console_Getargs_Options::setValue()');
                }
            }
            // Set the switch to on.
            $this->updateValue($optname, true);
            return true;

        } else if ($max >= 1 && $min === 0) {
            // Argument has a default-if-set value
            if (!isset($this->_config[$optname]['default'])) {
                // A default value MUST be assigned when config is loaded.
                return PEAR::raiseError('No default value defined for ' . $optname, CONSOLE_GETARGS_ERROR_CONFIG, PEAR_ERROR_TRIGGER, E_USER_WARNING, 'Console_Getargs_Options::setValue()');
            }
            if (is_array($this->_config[$optname]['default'])) {
                // Default value cannot be an array.
                return PEAR::raiseError('Default value for ' . $optname . ' must be scalar', CONSOLE_GETARGS_ERROR_CONFIG, PEAR_ERROR_TRIGGER, E_USER_WARNING, 'Console_Getargs_Options::setValue()');
            }

            // If optname is "parameters" take a step back.
            if ($optname == CONSOLE_GETARGS_PARAMS) {
                $pos--;
            }

            if (isset($this->args[$pos + 1]) && $this->isValue($this->args[$pos + 1])) {
                // Assign the option the value from the command line if there is one.
                $this->updateValue($optname, $this->args[$pos + 1]);
                $pos++;
                return true;
            }
            // Otherwise use the default value.
            $this->updateValue($optname, $this->_config[$optname]['default']);
            return true;
        }

        // Argument takes one or more values
        $added = 0;
        // If trying to assign values to parameters, must go back one position.
        if ($optname == CONSOLE_GETARGS_PARAMS) {
            $pos = max($pos - 1, -1);
        }
        for ($i = $pos + 1; $i <= count($this->args); $i++) {
            $paramFull = $max <= count($this->getValue($optname)) && $max != - 1;
            if (isset($this->args[$i]) && $this->isValue($this->args[$i]) && !$paramFull) {
                // Add the argument value until the next option is hit.
                $this->updateValue($optname, $this->args[$i]);
                $added++;
                $pos++;
                // Only keep trying if we haven't filled up yet.
                // or there is no limit
                if (($added < $max || $max < 0) && ($max < 0 || !$paramFull)) {
                    continue;
                }
            }
            if ($min > $added && !$paramFull) {
                // There aren't enough arguments for this option.
                return PEAR::raiseError('Argument ' . $optname . ' expects at least ' . $min . (($min > 1) ? ' values' : ' value'), CONSOLE_GETARGS_ERROR_USER, PEAR_ERROR_RETURN, null, 'Console_Getargs_Options::setValue()');
            } elseif ($max !== - 1 && $paramFull) {
                // Too many arguments for this option.
                // Try to add the extra options to parameters.
                if (isset($this->_config[CONSOLE_GETARGS_PARAMS]) && $optname != CONSOLE_GETARGS_PARAMS) {
                    return $this->setValue(CONSOLE_GETARGS_PARAMS, '', ++$pos);
                } elseif ($optname == CONSOLE_GETARGS_PARAMS && empty($this->args[$i])) {
                    $pos+= $added;
                    break;
                } else {
                    return PEAR::raiseError('Argument ' . $optname . ' expects maximum ' . $max . ' values', CONSOLE_GETARGS_ERROR_USER, PEAR_ERROR_RETURN, null, 'Console_Getargs_Options::setValue()');
                }
            }
            break;
        }
        // Everything went well.
        return true;
    }

    /**
     * Checks whether the given parameter is an argument or an option
     *
     * @access private
     * @return boolean
     */
    function isValue($arg)
    {
        if ((strlen($arg) > 1 && $arg{0} == '-' && $arg{1} == '-') || (strlen($arg) > 1 && $arg{0} == '-')) {
            // The next argument is really an option.
            return false;
        }
        return true;
    }

    /**
     * Adds the argument to the option
     *
     * If the argument for the option is already set,
     * the option arguments will be changed to an array
     *
     * @access private
     * @return void   
     */
    function updateValue($optname, $value)
    {
        if (isset($this->_longLong[$optname])) {
            if (is_array($this->_longLong[$optname])) {
                // Add this value to the list of values for this option.
                $this->_longLong[$optname][] = $value;
            } else {
                // There is already one value set. Turn everything into a list of values.
                $prevValue = $this->_longLong[$optname];
                $this->_longLong[$optname] = array($prevValue);
                $this->_longLong[$optname][] = $value;
            }
        } else {
            // This is the first value for this option.
            $this->_longLong[$optname] = $value;
        }
        $this->_defined[$optname] = true;
    }

    /**
     * Sets the option default arguments when necessary
     *
     * @access private
     * @return true   
     */
    function setDefaults()
    {
        foreach ($this->_config as $longname => $def) {
            // Add the default value only if the default is defined
            // and the option requires at least one argument.
            if (isset($def['default']) && ((isset($def['min']) && $def['min'] !== 0) || (!isset($def['min']) & isset($def['max']) && $def['max'] !== 0)) && !isset($this->_longLong[$longname])) {
                $this->_longLong[$longname] = $def['default'];
                $this->_defined[$longname] = false;
            }
        }
        return true;
    }

    /**
     * Checks whether the given option is defined
     *
     * An option will be defined if an argument was assigned to it using
     * the command line options. You can use the short, the long or
     * an alias name as parameter.
     *
     * @param string $optname the name of the option to be checked
     *
     * @access public 
     * @return boolean true if the option is defined
     */
    function isDefined($optname)
    {
        $longname = $this->getLongName($optname);
        return isset($this->_defined[$longname]) && $this->_defined[$longname];
    }

    /**
     * Returns the long version of the given parameter
     *
     * If the given name is not found, it will return the name that
     * was given, without further ensuring that the option
     * actually exists
     *
     * @param string $optname the name of the option
     *
     * @access private
     * @return string long version of the option name
     */
    function getLongName($optname)
    {
        if (isset($this->_shortLong[$optname])) {
            // Short version was passed.
            $longname = $this->_shortLong[$optname];
        } else if (isset($this->_aliasLong[$optname])) {
            // An alias was passed.
            $longname = $this->_aliasLong[$optname];
        } else {
            // No further validation is done.
            $longname = $optname;
        }
        return $longname;
    }

    /**
     * Returns the argument of the given option
     *
     * You can use the short, alias or long version of the option name.
     * This method will try to find the argument(s) of the given option name.
     * If it is not found it will return null. If the arg has more than
     * one argument, an array of arguments will be returned.
     *
     * @param string $optname the name of the option
     *
     * @access public           
     * @return array|string|null argument(s) associated with the option
     */
    function getValue($optname)
    {
        $longname = $this->getLongName($optname);
        if (isset($this->_longLong[$longname])) {
            // Option is defined. Return its value
            return $this->_longLong[$longname];
        }
        // Option is not defined.
        return null;
    }

    /**
     * Returns all arguments that have been parsed and recognized
     *
     * The name of the options are stored in the keys of the array.
     * You may choose whether you want to use the long or the short
     * option names
     *
     * @param string $optionNames option names to use for the keys (long or short)
     *
     * @access public
     * @return array values for all options
     */
    function getValues($optionNames = 'long')
    {
        switch ($optionNames) {
        case 'short':
            $values = array();
            foreach ($this->_shortLong as $short => $long) {
                if (isset($this->_longLong[$long])) {
                    $values[$short] = $this->_longLong[$long];
                }
            }
            if (isset($this->_longLong['parameters'])) {
                $values['parameters'] = $this->_longLong['parameters'];
            }
            return $values;
        case 'long':
        default:
            return $this->_longLong;
        }
    }

    /**
     * checkRequired 
     * 
     * @access public
     * @return void
     */
    function checkRequired()
    {
        foreach ($this->_config as $optName => $opt) {
            if (isset($opt['min']) && $opt['min'] == 1 && $this->getValue($optName) === null) {
                $err = PEAR::raiseError($optName . ' is required', CONSOLE_GETARGS_ERROR_USER, PEAR_ERROR_RETURN, null, 'Console_Getargs_Options::parseArgs()');
                return $err;
            }
        }
        return true;
    }
} // end class Console_Getargs_Options
/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
*/
?>
