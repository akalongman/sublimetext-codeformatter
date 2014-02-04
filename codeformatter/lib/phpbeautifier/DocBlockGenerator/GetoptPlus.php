<?php

/**
 * DocBlock Generator
 *
 * PHP version 5
 *
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 * + Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 * + Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation and/or
 * other materials provided with the distribution.
 * + The names of its contributors may not be used to endorse or
 * promote products derived from this software without specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  PHP
 * @package   PHP_DocBlockGenerator
 * @author    Michel Corne <mcorne@yahoo.com>
 * @copyright 2007 Michel Corne
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   SVN: $Id: GetoptPlus.php 30 2007-07-23 16:46:42Z mcorne $
 * @link      http://pear.php.net/package/PHP_DocBlockGenerator
 */

require_once 'Console/Getopt.php';

/**
 * Getopt extension
 *
 * Generate short options, long options, usage/help automaticly
 *
 * @category  PHP
 * @package   PHP_DocBlockGenerator
 * @author    Michel Corne <mcorne@yahoo.com>
 * @copyright 2007 Michel Corne
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_DocBlockGenerator
 */
class PHP_DocBlockGenerator_GetoptPlus
{
    /**
     * The option name padding within the option descrition
     */
    const optionPadding = 30;

    /**
     * The options section title
     */
    const options = 'Options: ';

    /**
     * The parameter section title
     */
    const parameters = 'Parameters: ';

    /**
     * The usage section title
     */
    const usage = 'Usage: ';

    /**
     * The configuration sub-array keys
     *
     * @var    array
     * @access private
     */
    private $configKeys = array('usage', 'header', 'options', 'parameters', 'footer');

    /**
     * The help option description
     *
     * @var    array
     * @access private
     */
    private $help = array(array('h', 'help', '', 'This help.'));

    /**
     * The long options used by Console_Getopt::doGetopt()
     *
     * @var    array
     * @access private
     */
    private $longOptions = array();

    /**
     * The short options used by Console_Getopt::doGetopt()
     *
     * @var    string
     * @access private
     */
    private $shortOptions = '';

    /**
     * The short name to long option name cross-references
     *
     * @var    array
     * @access private
     */
    private $short2long = array();

    /**
     * The long name to short option name cross-references
     *
     * @var    array
     * @access private
     */
    private $long2short = array();

    /**
     * Align a set of lines
     *
     * Additional data is added to the first line.
     * The other lines are padded and aligned to the first one.
     *
     * @param  array   $lines          the set of lines
     * @param  string  $firstLineAddon the additional data to add to the first line
     * @param  integer $paddingLength  the padding length
     * @return array   the aligned lines
     * @access public
     */
    public function alignLines($lines, $firstLineAddon, $paddingLength = 0)
    {
        // defaults the left alignment to the length of the additional information
        $paddingLength or $firstLineAddon and $paddingLength = strlen($firstLineAddon) + 1;
        if ($paddingLength > strlen($firstLineAddon)) {
            // pads the additional data and adds it to the left of the first line
            $firstLineAddon = str_pad($firstLineAddon, $paddingLength);
            $firstLine = $firstLineAddon . array_shift($lines);
        } else {
            // the information on the left is longer than the padding size
            $firstLine = $firstLineAddon;
        }
        // left pads the other lines
        $padding = str_repeat(' ', $paddingLength);
        $callback = create_function('$string', "return '$padding' . \$string;");
        $lines = array_map($callback, $lines);
        // restores the first line
        array_unshift($lines, $firstLine);

        return $lines;
    }

    /**
     * Gets the options and parameters from the command arguments
     *
     * Extracts the command arguments. Tidies the options.
     * Builds the help/usage text.
     *
     * @param  array   $config      the command configuration
     * @param  boolean $toLongNames Converts options names to long names if true,
     *                              e.g. "align", or to short names if false,
     *                              e.g. "A", default is true
     * @param  boolean $asKeys      return options with the names used as array
     *                              keys if true, or leave them as returned by
     *                              Console_Getopt::doGetopt(), default is true
     * @param  integer $version     Console_Getopt::doGetopt version
     * @return array   the options and parameters
     * @access public
     * @static
     */
    public static function getopt($config = array(), $toLongNames = true, $asKeys = true, $version = 2)
    {
        $getopt = new self;
        // silently ignore invalid configuration arrays, defaults the absent ones to empty arrays
        $default = array_combine($getopt->configKeys, array_fill(0, count($getopt->configKeys), array()));
        $config = array_intersect_key($config, $default);
        $config = array_merge($default, $config);
        // extracts the command arguments, including the command name
        $args = Console_Getopt::readPHPArgv();
        $command = array_shift($args);
        // tidies the options, sets the short and long options, and calls getopt
        $config['options'] = $getopt->tidyOptions($config['options']);
        $options = Console_Getopt::doGetopt($version, $args, $getopt->shortOptions, $getopt->longOptions);
        // a syntax error, prints out the error message
        PEAR::isError($options) and exit($options->getMessage());
        // tidies the arguments
        $options[0] = $getopt->tidyArgs($options[0], $toLongNames, $asKeys);
        // a request for help/usage, prints the command usage
        $options[0] == 'help' and exit(implode("\n", $getopt->setHelp($config, $command)));

        return $options;
    }

    /**
     * Set the help/usage text
     *
     * @param  array  $config  the command configuration
     * @param  string $command the command name
     * @return array  the help/usage text
     * @access public
     */
    public function setHelp($config, $command)
    {
        // sets all the help/usage section texts
        $config['parameters'] = $this->alignLines($config['parameters'], self::parameters) ;
        $config['usage'] = $this->setUsage($config['usage'], $command, $config['options'], $config['parameters']);
        $config['options'] = $this->setOptions($config['options']);
        $config['header'] = $this->tidyArray($config['header']);
        $config['footer'] = $this->tidyArray($config['footer']);
        // merges the section texts together
        $callback = create_function('$array, $array1', '$array or $array = array(); return array_merge($array, $array1);');
        $help = array_reduce($config, $callback, array());

        return $help;
    }

    /**
     * Sets the options help text section
     *
     * @param  array  $options the options descriptions
     * @return array  the options help text section
     * @access public
     */
    public function setOptions($options)
    {
        $padding = str_repeat(' ', self::optionPadding);
        $callback = create_function('$string', "return '$padding' . \$string;");

        $lines = array();
        foreach($options as $option) {
            // expecting tidy options
            list($shortName, $longName, $condition, $desc) = $option;
            // extracts the option example value from the description
            // encloses with angle/square brackets if mandatory/optional
            $condition == ':' and $value = '<' . array_shift($desc) . '>' or
            $condition == '::' and $value = '[' . array_shift($desc) . ']' or
            $value = '';
            // sets the option names
            $optionNames = array();
            $shortName and $optionNames[] = "-$shortName";
            $longName and $optionNames[] = "--$longName";
            $value and $optionNames[] = $value;
            $optionNames = implode(' ', $optionNames);
            // adds the option names to the description
            $desc = $this->alignLines($desc, $optionNames, self::optionPadding);
            $lines = array_merge($lines, $desc);
        }
        // prefix the options with e.g. "Options:"
        $lines and array_unshift($lines, self::options);

        return $lines;
    }

    /**
     * Sets the usage help text section
     *
     * @param  array  $usages     the usages descriptions
     * @param  string $command    the command name
     * @param  array  $options    the options descriptions
     * @param  array  $parameters the parameters descriptions
     * @return array  the usage help text section
     * @access public
     */
    public function setUsage($usages, $command, $options, $parameters)
    {
        if (empty($usages)) {
            // usage is empty, defaults to a one line usage, e.g. [options] [parameters]
            empty($options) or $usages[] = '[options]';
            empty($parameters) or $usages[] = '[parameters]';
            $usages = implode(' ', $usages);
        }
        // expecting an array of arrays of usage lines, or possibly a single usage line,
        is_array($usages) or $usages = array($usages);

        $lines = array();
        $padding = str_repeat(' ', strlen(self::usage));
        foreach($usages as $idx => $usage) {
            $usage = $this->tidyArray($usage);
            // adds the usage keywork to the first usage, e.g. "Usage:"
            $prefix = $idx? $padding : self::usage;
            // adds the command to each usage, e.g. command [options] [parameters]
            $prefix .= basename($command);
            $usage = $this->alignLines($usage, $prefix);
            $lines = array_merge($lines, $usage);
        }

        return $lines;
    }

    /**
     * Tidies the command arguments
     *
     * @param  array   $options     the options arguments
     * @param  boolean $toLongNames Converts options names to long names if true,
     *                              e.g. "align", or to short names if false,
     *                              e.g. "A", default is true
     * @param  boolean $asKeys      return options with the names used as array
     *                              keys if true, or leave them as returned by
     *                              Console_Getopt::doGetopt(), default is true
     * @return array   the tidy options arguments
     * @access public
     */
    public function tidyArgs($options, $toLongNames = true, $asKeys = true)
    {
        $tidied = array();
        foreach($options as $option) {
            // extracs the argument name and value
            list($name, $value) = $option;
            // removes the "--" prefix from long arguments
            $isLongName = substr($name, 0, 2) == '--' and $name = substr($name, 2);

            if (in_array($name, array('h', 'help'))) {
                // the help/usage option
                return 'help';
            }

            if ($toLongNames) {
                // changes all argument names to long names
                isset($this->short2long[$name]) and $name = $this->short2long[$name];
            } else {
                // changes all argument names to short names
                isset($this->long2short[$name]) and $name = $this->long2short[$name];
            }

            if ($asKeys) {
                // converts the arguments to an associative array with the argument name as the key
                $tidied[$name] = is_null($value)? '' : $value;
            } else {
                // leaves the arguments as per the Console_Getopt::doGetopt() format
                $tidied[] = array($name, $value);
            }
        }

        return $tidied;
    }

    /**
     * Tidies an array
     *
     * Makes an array if passed as a string.
     * Optionally forces the values to strings if there are not.
     *
     * @param  array   $array      the array
     * @param  boolean $tidyString forces the values to string if true,
     *                             or leave them untouched if false
     * @return array   the tidy array
     * @access public
     */
    public function tidyArray($array, $tidyString = true)
    {
        // if not an array converts to an array
        is_array($array) or $array = array($array);
        // tidies the array string values
        $tidyString and $array = array_map(array($this, 'tidyString'), $array);

        return $array;
    }

    /**
     * Tydies the options
     *
     * @param  array  $options the options descriptions
     * @return array  the tidy options
     * @access public
     */
    public function tidyOptions($options)
    {
        // expecting an array of arrays of option settings, or possibly a single option short name,
        // or possibly a single option settings
        is_array($options) or $options = array($options);
        is_array(current($options)) or $options = array($options);
        // adds the default help/usage option
        $options = array_merge($options, $this->help);

        $tidied = array();
        foreach($options as $option) {
            $option = $this->tidyArray($option, false);
            // extracts the first letter of the option short name, ignores the others letters silently
            $shortName = $this->tidyString(current($option)) and $shortName = $shortName{0};
            // extracts the option long name
            $longName = $this->tidyString(next($option));

            if ($shortName and $longName) {
                // extracts the condition, silently ignores conditions different from ":" and "::"
                $condition = $this->tidyString(next($option));
                in_array($condition, array(':', '::')) or $condition = '';
                // extracts the description
                $desc = $this->tidyArray(next($option));
                // adds to the tidy options
                $tidied[] = array($shortName, $longName, $condition, $desc);
                // builds the short and long options lists used by Console_Getopt::doGetopt()
                $this->shortOptions .= $shortName . $condition;
                $this->longOptions[] = $longName . str_replace(':', '=', $condition);
                // creates cross-references between short and long option names
                if ($shortName and $longName) {
                    $this->short2long[$shortName] = $longName; // check that there is no duplicates !!!
                    $this->long2short[$longName] = $shortName;
                }
            }
            // else: silently ignores empty option names
        }

        return $tidied;
    }

    /**
     * Tidies a string
     *
     * Retains only the first value if passed as an array.
     *
     * @param  string $string the string
     * @return string the tidy string
     * @access public
     */
    public function tidyString($string)
    {
        // if an array: captures the first value and converts it to a string
        // silently ignores the other values
        is_array($string) and $string = current($string);

        return trim($string);
    }
}

?>