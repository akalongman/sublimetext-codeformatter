<?php

/**
 * DocBlock Generator
 *
 * Creates the file Page block and the DocBlocks for includes, global variables,
 * functions, parameters, classes, constants, properties and methods.
 * Accepts parameters to set the category name, the package name, the author's
 * name and email, the license, the package link, etc...
 * Attempts to guess variable and parameters types.
 * Aligns the DocBlock tags.
 * Tags are not updated or added to existing DocBlocks but only realigned.
 * The package can be run by: calling the "PHP_DocBlockGenerator" class, or
 * by running the "docblockgen" DOS/Unix command.
 * Fully tested with phpUnit. Code coverage test close to 100%.
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
 * @version   SVN: $Id: DocBlockGenerator.php 30 2007-07-23 16:46:42Z mcorne $
 * @link      http://pear.php.net/package/PHP_DocBlockGenerator
 * @see       http://manual.phpdoc.org
 * @todo      process VIM header/footer
 * @todo      process multiple files/directories
 * @todo      build other files page DocBlocks from the main file/class page DocBlock
 * @todo      write a simple web interface
 */

require_once 'DocBlockGenerator/Tokens.php';

/**
 * Creation of Documentation blocks or DocBlocks in a PHP file
 *
 * Creates the file Page block and the DocBlocks for includes, global variables,
 * functions, parameters, classes, constants, properties and methods.
 * Accepts parameters to set the category name, the package name, the author's
 * name and email, the license, the package link, etc...
 * Attempts to guess variable and parameters types.
 * Aligns the DocBlock tags.
 * Tags are not updated or added to existing DocBlocks but only realigned.
 *
 * The package can be run by: calling the "PHP_DocBlockGenerator" class, or
 * by running the "docblockgen" DOS/Unix command.
 *
 * <pre>
 * Example 1: Creates DocBlocks in foo.php with default license, package name, etc...
 * $docblockgen = new PHP_DocBlockGenerator();
 * $docblockgen->generate('foo.php');
 *
 * Example 2: Creates Docblocks for foo.php in docfoo.php with some specific Page tags
 * $param = array('license' => 'apache20', 'category' => 'PHP',
 * 'author' => 'John Foo', 'email' => 'jfoo@mail.com', 'year' => '1999-2007');
 * $docblockgen = new PHP_DocBlockGenerator();
 * $docblockgen->generate('foo.php', $param, 'docfoo.php');
 *
 * Example 3: Realigns DocBlock tags
 * #docblockgen -A foo.php
 *
 * Example 4: Creates Docblocks for foo.php in docfoo.php with some specific Page tags
 * #docblockgen -la -c PHP -a "John Foo" -e 'jfoo@mail.com' -y 1999-2007 foo.php docfoo.php
 * </pre>
 *
 * @category  PHP
 * @package   PHP_DocBlockGenerator
 * @author    Michel Corne <mcorne@yahoo.com>
 * @copyright 2007 Michel Corne
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_DocBlockGenerator
 */

class PHP_DocBlockGenerator
{
    /**
     * The PHP_DocBlockGenerator_Tokens class instance
     *
     * @var    object
     * @access private
     */
    private $tokens;

    /**
     * The class constructor
     *
     * @return void
     * @access public
     */
    public function __construct()
    {
        $this->tokens = new PHP_DocBlockGenerator_Tokens();
    }

    /**
     * Generates the DocBlocks
     *
     * Reads the input file. Creates and aligns the DocBlocks. Writes the ouput file.
     * The license name is expected to be a key of the self::$license property,
     * otherwise it is silently ignored.
     * The parameters are optional. Their default values is documented in
     * the command usage property self::$cliUsage
     *
     * @param  string  $infile  the input file name
     * @param  array   $param   the parameters/options
     *                          <pre>
     *                          author => "the author's name",
     *                          category => 'the category name'
     *                          email => "the author's email"
     *                          license => apache20 | bsd | lgpl21 | mit | php301,
     *                          link => 'the package link',
     *                          package => 'the package name',
     *                          see => 'a reference to other things',
     *                          version => 'the file version',
     *                          year => 'the copy right year'
     *                          </pre>
     * @param  string  $outfile the output file name, default: the input file name
     * @return boolean true on success, false on failure
     * @access public
     * @see    PHP_DocBlockGenerator_License::$license, PHP_DocBlockGenerator_Block::$pageTagDefaults
     */
    public function generate($infile, $param = array(), $outfile = '')
    {
        // defaults the outfile to the infile
        $outfile or $outfile = $infile;
        // reads the PHP source code file, creates/aligns the tokens DocbBlocks, writes the PHP source code file
        $data = @file_get_contents($infile) and
        $data = $this->tokens->process($data, $param) and
        $data = @file_put_contents($outfile, $data);

        return (bool)$data;
    }
}

?>
