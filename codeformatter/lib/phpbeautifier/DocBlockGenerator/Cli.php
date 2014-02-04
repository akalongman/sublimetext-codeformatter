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
 * @version   SVN: $Id: Cli.php 30 2007-07-23 16:46:42Z mcorne $
 * @link      http://pear.php.net/package/PHP_DocBlockGenerator
 */

require_once 'DocBlockGenerator.php';

/**
 * Description for require_once
 */
require_once 'DocBlockGenerator/GetoptPlus.php';

/**
 * Command Line Interface to create DocBlocks in a PHP file
 *
 * @category  PHP
 * @package   PHP_DocBlockGenerator
 * @author    Michel Corne <mcorne@yahoo.com>
 * @copyright 2007 Michel Corne
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_DocBlockGenerator
 */

class PHP_DocBlockGenerator_Cli
{
    /**
     * Description for private
     *
     * @var    array
     * @access private
     */
    private static $config = array(// /
        'usage' => array('[options] [infile] [,outfile]', '-A [infile] [,outfile]'),
        // 'header' => array(),
        'options' => array(// /
            array('a', 'author', ':', array('name', 'The author\'s name, e.g. "John Foo".')),
            array('c', 'category', ':', array('name', 'The category name, e.g. PHP.', 'See http://pear.php.net/packages.php.')),
            array('e', 'email', ':', array('name@example.com', 'The author\'s email address.')),
            array('i', 'infile', ':', array('name', 'The input PHP file to process. Default: STDIN.')),
            array('l', 'license', ':', array('apache20|bsd|lgpl21|mit|php301|*', 'The license. Default: bsd.')),
            array('o', 'outfile', ':', array('name', 'The output file. Default: infile', 'or STDOUT if infile is STDIN.')),
            array('p', 'package', ':', array('name', 'The package name.', 'Default: the first 2 words of the class name.')),
            array('u', 'link', ':', array('http://...', 'The package link.', 'Default: http://pear.php.net/package/name.')),
            array('v', 'version', ':', array('cvs|svn|*', 'The file version. Default: CVS keyword.')),
            array('y', 'year', ':', array('yyyy', 'The copyright year. Default: the current year.')),
            array('A', 'align', '', array('Aligns existing DocBlock tags.', 'Other options are ignored.')),
            ),
        'parameters' => array('[infile] [,outfile]', 'The input and output files. See the -i and -o options.'),
        'footer' => array(// /
            'Note: Option values requiring space separated words, for example the author\'s',
            'name,must be enclosed with double-quotes.',
            ),
        );
    /**
     * The command line interface for DOS/Unix command
     *
     * Primarily meant to be called by the "docblockgen" shell script.
     * Processes the DOS/Unix command options/arguments,
     * creates an instance of the class itself and starts the process.
     * The default input file is the standard input and the default ouput file
     * is the standard output.
     * Exits with 0 on success, or dies and displays an error message
     * on the CLI on failure.
     *
     * <pre>
     * Example 1: Realigns DocBlock tags
     * #docblockgen -A foo.php
     *
     * Example 2: Creates Docblocks for foo.php in docfoo.php with some specific Page tags
     * #docblockgen -la -c PHP -a "John Foo" -e 'jfoo@mail.com' -y 1999-2007 foo.php docfoo.php
     *
     * Example 3: Displays the command usage
     * #docblockgen -h
     * This displays:
     * <pre>
     * Usage: docblockgen [options] [infile] [,outfile]
     *         docblockgen -A [infile] [,outfile]
     * Options:
     * -a --author <name>            The author's name, e.g. "John Foo".
     * -c --category <name>          The category name, e.g. PHP.
     *                                See http://pear.php.net/packages.php.
     * -e --email <name@example.com> The author's email address.
     * -i --infile <name>            The input PHP file to process. Default: STDIN.
     * -l --license <apache20|bsd|lgpl21|mit|php301|*>
     *                                The license. Default: bsd.
     * -o --outfile <name>           The output file. Default: infile
     *                                or STDOUT if infile is STDIN.
     * -p --package <name>           The package name.
     *                                Default: the first 2 words of the class name.
     * -u --link <http://...>        The package link.
     *                                Default: http://pear.php.net/package/name.
     * -v --version <cvs|svn|*>      The file version. Default: CVS keyword.
     * -y --year <yyyy>              The copyright year. Default: the current year.
     * -A --align                    Aligns existing DocBlock tags.
     *                                Other options are ignored.
     * -h --help                     This help.
     * Parameters:  [infile] [,outfile]
     *               The input and output files. See the -i and -o options.
     * Note: Option values requiring space separated words, for example the author's
     * name,must be enclosed with double-quotes.
     * </pre>
     *
     * @return void
     * @access public
     * @static
     * @see    scripts/gendocblock, scripts/gendocblock.bat
     */
    public static function generate()
    {
        // extracts the options and parameters
        list($options, $parameters) = PHP_DocBlockGenerator_GetoptPlus::getopt(self::$config);
        // extracts the infile and outfile as parameters, over-rides the "-i" and "-o" options
        $infile = current($parameters) and $options['infile'] = $infile;
        $outfile = next($parameters) and $options['outfile'] = $outfile;
        // captures the outfile or defaults the outfile to the infile or the standard output
        isset($options['outfile']) and $outfile = $options['outfile'] or
        $outfile = isset($options['infile'])? $options['infile'] : 'php://stdout';
        // captures the infile or defaults to the standard input
        $infile = isset($options['infile'])? $options['infile'] : 'php://stdin';

        $docblockgen = new PHP_DocBlockGenerator();
        if (!$docblockgen->generate($infile, $options, $outfile)) {
            // error when accessing the file(s)
            $infile != $outfile and $infile .= " and/or the file: $outfile";
            die("Error! Cannot access|read|write the file: $infile");
        }

        exit(0);
    }
}

?>