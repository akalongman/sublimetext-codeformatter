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
 * @version   SVN: $Id: Align.php 30 2007-07-23 16:46:42Z mcorne $
 * @link      http://pear.php.net/package/PHP_DocBlockGenerator
 */

/**
 * Alignment of DocBlocks tags in a PHP file
 *
 * @category  PHP
 * @package   PHP_DocBlockGenerator
 * @author    Michel Corne <mcorne@yahoo.com>
 * @copyright 2007 Michel Corne
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_DocBlockGenerator
 */

class PHP_DocBlockGenerator_Align
{
    /**
     * Pattern to extract a tag with its type and its variable, e.g. *
     *
     * @param array $block
     */
    const tags = '~^( *\*)( *)(@[^ \n\r]+)( *)([^ \n\r]*)( *)([^ \n\r]*)( *)(.*)$~m';

    /**
     * Pattern to extract a tag with its lines details
     */
    const tagParts = '~^( *\* @)~m';

    /**
     * Pattern to extract a tag line, excluding the tag first line
     */
    const tagLines = '~^( *\*)(?!/)~m';

    /**
     * The padding after the tag
     *
     * @var    integer
     * @access private
     */
    private $tagPadding;

    /**
     * The padding after the tag type
     *
     * @var    integer
     * @access private
     */
    private $typePadding;

    /**
     * The tags with a type to align within a DocBlock
     *
     * @var    array
     * @access private
     */
    private $typeToAlign = array('@global', '@param', '@return', '@var', '@throws', '@access');

    /**
     * The padding after the tag variable, e.g. typically a function parameter
     *
     * @var    integer
     * @access private
     */
    private $varPadding;

    /**
     * The tags with a variable to align within a DocBlock
     *
     * @var    array
     * @access private
     */
    private $varToAlign = array('@param');

	/**
     * Are we handling a PageBlock?
     *
     * @var    bool
     * @access private
     */
    private $isPageBlock = false;

    /**
     * Aligns the tags within a DocBlock
     *
     * Calculates the necessary padding/alignments, aligns the tag first line,
     * then the additional lines. The text preceeding the first tag is
     * left untouched.
     *
     * @param  string $block the DocBlock
     * @return string the aligned DocBlock
     * @access public
     */
    public function alignTags($block)
    {
        // fixes PHPEdit 2.x beautifier bug!
        $block = preg_replace('~\$ +&~', '&', $block);

		// is it a PageBlock?
		$this->isPageBlock = (strpos($block, '@package') !== false);

        if (preg_match_all(self::tags, $block, $matches)) {
            // extracts the tags from the DocBlock
            list(, , , $tags, , $types, , $vars) = array_pad($matches, 10, '');
            // finds out the length of the longest tag
            $this->tagPadding = max(array_map('strlen', $tags));

            if ($types) {
                // selects the tags with data/types to align
                $tagsToAlign = array_intersect($tags, $this->typeToAlign);
                // selects the data/types to align
                $types = array_intersect_key($types, $tagsToAlign);
                // finds out the length of the longest data/type
                $this->typePadding = $types? max(array_map('strlen', $types)) : 0;
            }

            if ($vars) {
                // selects the tags with variables to align
                $tagsToAlign = array_intersect($tags, $this->varToAlign);
                // selects the variables to align
                $vars = array_intersect_key($vars, $tagsToAlign);
                // finds out the length of the longest variables
                $this->varPadding = $vars? max(array_map('strlen', $vars)) : 0;
            }
            // aligns the tag first line
            $block = preg_replace_callback(self::tags, array($this, 'padTags'), $block);
            // aligns the tag additional lines, splits the DocBlock into tag parts
            $tagParts = preg_split(self::tagParts, $block, -1, PREG_SPLIT_DELIM_CAPTURE);

			foreach($tagParts as $i => &$part) {
                // aligns the tag additional lines
                if ($i != 0 and $i % 2 == 0) {
                    // tag data: not the DocBlock content before the first tag, neither a tag separator
                    list($tag) = explode(' ', $part, 2); // extracts the tag
                    $tag = "@$tag";
                    // splits the tag into its lines
                    $tagLines = preg_split(self::tagLines, $part, -1, PREG_SPLIT_DELIM_CAPTURE);

                    foreach($tagLines as $j => &$line) {
                        if ($j != 0 and $j % 2 == 0) {
                            // tag line data: not the first line of the tag, neither a tag line separator
                            // calculates the padding before the tag line data
                            $padding = $this->tagPadding + 2;
                            in_array($tag, $this->typeToAlign) and $padding += $this->typePadding + 1;
                            in_array($tag, $this->varToAlign) and $padding += $this->varPadding + 1;
                            $data = preg_match('~(?: *)(.*)~s', $line, $matches)? next($matches) : '';
                            $line = str_repeat(' ', $padding) . $data;
                        } // else: the first line of the tag
                    }
                    $part = implode('', $tagLines);
                }
            }
			$block = implode('', $tagParts);
			if ($this->isPageBlock) {
				// Ordering for Page DocBlock
				$tagParts = preg_split(self::tagParts, $block, -1, PREG_SPLIT_DELIM_CAPTURE);
				$firstTag = '';
				$orderedTags = array();
				$otherTags = array();
				$lastTag = '';
				foreach($tagParts as $i => $part) {
					list($tag) = explode(' ', $part, 2); // extracts the tag
					if ($part == $tagParts[0]) { $firstTag=$part; }
					elseif ($part == $tagParts[count($tagParts)-1]) { $lastTag=$tagParts[$i-1].$part; }
					elseif ($tag == 'category') { $orderedTags[1]=$tagParts[$i-1].$part; }
					elseif ($tag == 'package') { $orderedTags[2]=$tagParts[$i-1].$part; }
					elseif ($tag == 'author') { $orderedTags[3]=$tagParts[$i-1].$part; }
					elseif ($tag == 'copyright') { $orderedTags[4]=$tagParts[$i-1].$part; }
					elseif ($tag == 'license') { $orderedTags[5]=$tagParts[$i-1].$part; }
					elseif ($tag == 'version') { $orderedTags[6]=$tagParts[$i-1].$part; }
					elseif ($tag == 'link') { $orderedTags[7]=$tagParts[$i-1].$part; }
					elseif ($tag == 'see') { $orderedTags[8]=$tagParts[$i-1].$part; }
					elseif ($part[0] != ' ') { $otherTags[]=$tagParts[$i-1].$part; }
				}
				ksort($orderedTags);
				sort($otherTags);
				$block = $firstTag.implode('', $orderedTags).implode('', $otherTags).$lastTag;
			}
        }

        return $block;
    }

    /**
     * Pads the tag first line
     *
     * A preg_replace_callback() callback function.
     *
     * @param  array   $matches the tags first line splitted by items
     * @return string  the aligned tag first line
     * @access private
     */
    private function padTags($matches)
    {
        list(, $star , , $tag, , $type, , $var, , $rest) = array_pad($matches, 10, '');
        // adds the "*" and the padded tag
        $string = $star . ' ';
		$string .= $this->isPageBlock ? str_pad($tag, $this->tagPadding) : $tag;

        if ($type) {
            // pads the tag data/type, adds tag additional data
            in_array($tag, $this->typeToAlign) and $type = str_pad($type, $this->typePadding);
            $string .= ' ' . $type;
        }

        if ($var) {
            // pads the tag variable, adds tag additional data
            in_array($tag, $this->varToAlign) and $var = str_pad($var, $this->varPadding);
            $string .= ' ' . $var;
        }
        // adds the rest of the data
        $rest and $string .= ' ' . $rest;

        return $string;
    }
}

?>