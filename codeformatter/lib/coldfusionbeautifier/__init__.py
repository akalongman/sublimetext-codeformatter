from __future__ import print_function
import sys
import re
import sublime
try:
    # Python 3
    from .__version__ import __version__
except (ValueError):
    # Python 2
    from __version__ import __version__

class BeautifierOptions:
    def __init__(self):
        self.indent_size = 4
        self.indent_char = ' '
        self.indent_with_tabs = False
        self.expand_tags = False
        # self.expand_javascript = False
        self.minimum_attribute_count = 2
        self.first_attribute_on_new_line = False
        self.reduce_empty_tags = False
        self.reduce_whole_word_tags = False
        self.exception_on_tag_mismatch = False
        self.custom_singletons = ''

    def __repr__(self):
        return """indent_size = %d
indent_char = [%s]
indent_with_tabs = [%s]
expand_tags = [%s]
minimum_attribute_count = %d
first_attribute_on_new_line = [%s]
reduce_empty_tags = [%s]
reduce_whole_word_tags = [%s]
exception_on_tag_mismatch = [%s]
custom_singletons = [%s]""" % (self.indent_size, self.indent_char, self.indent_with_tabs, self.expand_tags, self.minimum_attribute_count, self.first_attribute_on_new_line, self.reduce_empty_tags, self.reduce_whole_word_tags, self.exception_on_tag_mismatch, self.custom_singletons)

def default_options():
    return BeautifierOptions()

def beautify(string, opts=default_options()):
    b = Beautifier(string, opts)
    return b.beautify()

def beautify_file(file_name, opts=default_options()):
    if file_name == '-':  # stdin
        stream = sys.stdin
    else:
        stream = open(file_name)
    content = ''.join(stream.readlines())
    b = Beautifier(content, opts)
    return b.beautify()

def usage(stream=sys.stdout):
    print("coldfusionbeautifier.py@" + __version__ + "\nColdfusion beautifier (http://jsbeautifier.org/)\n", file=stream)
    return stream == sys.stderr
    if stream == sys.stderr: return 1
    else: return 0

class Beautifier:
    def __init__(self, source_text, opts=default_options()):
        self.source_text = source_text
        self.opts = opts
        self.exception_on_tag_mismatch = opts.exception_on_tag_mismatch
        self.expand_tags = opts.expand_tags
        self.expand_javascript = opts.expand_javascript
        self.minimum_attribute_count = opts.minimum_attribute_count
        self.first_attribute_on_new_line = opts.first_attribute_on_new_line
        self.reduce_empty_tags = opts.reduce_empty_tags
        self.reduce_whole_word_tags = opts.reduce_whole_word_tags
        self.indent_size = opts.indent_size
        self.indent_char = opts.indent_char
        self.indent_with_tabs = opts.indent_with_tabs
        if self.indent_with_tabs:
            self.indent_char = "\t"
            self.indent_size = 1
            self.tab_size = sublime.load_settings('Preferences.sublime-settings').get('tab_size',4)
        self.indent_level = 0
        # These are the tags that are currently defined as being void by the HTML5 spec, and should be self-closing (a.k.a. singletons)
        self.singletons = r'<(area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr|cf(?:abort|admin|applet|argument|associate|authenticate|break|content|continue|cookie|directory|document|documentitem|documentsection|dump|error|execute|exit|file|flush|header|httpparam|import|include|index|invoke|invokeargument|ldap|location|log|mailparam|object|objectcache|param|processingdirective|property|queryparam|rethrow|return|retry|schedule|set|setting|thread|throw)<%= custom %>)([^>]*?)/?>(?:\s*?</\1>)?'
        if not opts.custom_singletons == '':
            self.singletons = re.sub(r'<%= custom %>','|' + opts.custom_singletons,self.singletons)
        else:
            self.singletons = re.sub(r'<%= custom %>','',self.singletons)
        self.midle_tags = r'<cf(else|elseif)([^>]*)>'
        # Compile singletons regex since it's used so often (twice before the loop, then once per loop iteration)
        self.singletons = re.compile(self.singletons,re.I)
        self.removed_css = []
        self.removed_js = []
        self.removed_comments = []

    def expand_tag(self,str):
        _str = str.group(0) # cache the original string in a variable for faster access
        s = re.findall(r'(<[\S]+(?:=(?:"[^"]*"|\'[^\']*\'))?)',_str)
        # If the tag has fewer than "minimum_attribute_count" attributes, leave it alone
        if len(s) <= self.minimum_attribute_count: return _str
        tagEnd = re.search(r'/?>$',_str)
        if not tagEnd == None: s += [tagEnd.group(0)] # Append the end of the tag to the array of attributes
        tag = '<' + s[0] # The '<' at the beginning of a tag is not included in the regex match
        indent = len(tag) + 1 # include the space after the tag name, this is not included in the regex
        s = s[1:] # pop the tag name off of the attribute array - we don't need it any more
        # Calculate how much to indent each line
        if self.first_attribute_on_new_line: # If we're putting all the attributes on their own line, only use 1 indentation unit
            if self.indent_with_tabs:
                indent = 0
                extra_tabs = 1
            else:
                indent = self.indent_size
                extra_tabs = 0
        else: # Otherwise, align the attributes with the beginning of the first attribute after the tag name
            if self.indent_with_tabs:
                extra_tabs = int(indent / self.tab_size)
                indent = indent % self.tab_size
            else:
                extra_tabs = 0
            tag += ' ' + s[0]
            s = s[1:] # Go ahead and pop the first attribute off the array so that we don't duplicate it in the loop below
        # For each attribute in the list, append a newline and indentation followed by the attribute (or the end of the tag)
        for l in s:
            tag += '\n' + (((self.indent_level * self.indent_size) + extra_tabs) * self.indent_char) + (indent * ' ') + l
        return tag

    def remove_newlines(self,ch=''): return lambda str: re.sub(r'\n\s*',ch,str.group(0))

    def remove(self,pattern,replacement,findList,raw):
        pattern = re.compile(r'(?<=\n)\s*?' + pattern,re.S|re.I)
        findList.extend(pattern.findall(raw))
        return pattern.sub((lambda match: match.group(0)[:-len(match.group(0).lstrip())] + replacement),raw) # Preserve the indentation from the beginning of the match

    def remove_js(self,raw): return self.remove(r'<script[^>]*>.*?</script>','/* SCRIPT */',self.removed_js,raw)
    def remove_css(self,raw): return self.remove(r'<style[^>]*>.*?</style>','/* STYLE */',self.removed_css,raw)
    def remove_comments(self,raw): return self.remove(r'<!---.*?--->','/* COMMENT */',self.removed_comments,raw)

    def reindent(self,raw,match):
        prev_newline = r'(?<=\n)'
        lowest_indent = -1
        for l in re.split(r'\n',raw):
            indent = len(l) - len(l.strip())
            if lowest_indent == -1 or lowest_indent > indent:
                lowest_indent = indent
        indent = len(match.group(1)) * self.indent_char
        return indent + re.sub(prev_newline,indent,re.sub(prev_newline + (lowest_indent * self.indent_char),'',raw.lstrip())); # Force new indentation

    def getNextFrom(self,_list):
        it = iter(_list)
        return lambda match: self.reindent(next(it),match)

    def replace(self,pattern,replaceList,raw): return re.compile(r'(?<=\n)(\s*?)' + pattern,re.S|re.I).sub(self.getNextFrom(replaceList),raw)
    def replace_comments(self,raw): return self.replace(r'/\* COMMENT \*/',self.removed_comments,raw)
    def replace_css(self,raw): return self.replace(r'/\* STYLE \*/',self.removed_css,raw)
    def replace_js(self,raw): return self.replace(r'/\* SCRIPT \*/',self.removed_js,raw)

    def beautify(self):
        beautiful = ''

        replaceWithSpace = self.remove_newlines(' ')

        raw = self.source_text

        # Remove JS, CSS, and comments from raw source
        raw = self.remove_js(raw)
        raw = self.remove_css(raw)
        raw = self.remove_comments(raw)

        # Add newlines before/after tags (excluding CDATA). This separates single-line HTML comments into 3 lines as well
        raw = re.sub(r'(<[^! ]|(?<!/\*|//)\]\]>|(?<!<!\[endif\])--->)',r'\n\1',raw)
        raw = re.sub(r'(>|(?<!/\*|//)<!\[CDATA\[|<!---(?!\[if .+?\]>))',r'\1\n',raw)

        # Fix AngularJS/Blade/etc brace ({{}}, {{::}}, etc) templates that will have been broken into multiple lines
        raw = re.sub(r'(\{{2,}(?:::)?)\s?(.*?)\s?(\}{2,})',r'\1 \2 \3',re.sub(r'\{(?:\s*\{)+\s?[\s\S]*?\s?\}(?:\s*\})+',self.remove_newlines(),raw))

        raw = re.sub(r'"[^"]*"',replaceWithSpace,raw)                   # Put all content between double-quote marks back on the same line

        # Re-join start tags that are already on multiple lines (ignore end tags)
        raw = re.compile(r'(?<=\n)<(?!/).*?>(?=\n)',re.S).sub(replaceWithSpace,raw)

        raw = self.singletons.sub(r'<\1\2/>',raw)                       # Replace all singleton tags with /-delimited tags (XHTML style)
        raw = self.singletons.sub(replaceWithSpace,raw)
        raw = re.sub(r'(?<!\s)\s(?=/?>)','',raw)
        raw = re.sub(r'\n{2,}',r'\n',raw)                               # Replace multiple newlines with just one

        for l in re.split('\n',raw):
            l = l.strip()                                               # Trim whitespace from the line
            if l == '': continue                                        # If the line has no content, skip

            # If the line starts with </, or an end CDATA/block comment tag, reduce indentation
            if re.match(r'</|]]>|(?:<!\[endif\])?--->',l) or re.search(self.midle_tags,l): self.indent_level -= 1

            beautiful += (self.indent_char * self.indent_level * self.indent_size)
            if self.expand_tags:
                beautiful += re.sub(r'^<.*>$',self.expand_tag,l)
            else:
                beautiful += l
            beautiful += '\n'

            if self.singletons.search(l): pass                          # If the tag is a singleton, indentation stays the same
            elif re.search(self.midle_tags,l): self.indent_level += 1
            else:
                # If the line starts with a begin CDATA/block comment tag or a tag, indent the next line
                if re.match(r'<!---|<!\[CDATA\[|<[^/?! ]',l): self.indent_level += 1

        # If the end of the document is not at the same indentation as the beginning, the tags aren't matched
        if not self.indent_level == 0 and self.exception_on_tag_mismatch:
            raise Exception("Mismatched tags")

        # Put all matched start/end tags with no content between them on the same line and return
        if self.reduce_empty_tags:
            beautiful = re.sub(r'<(\S+)([^>]*)>\s+</\1>',r'<\1\2></\1>',beautiful)
        if self.reduce_whole_word_tags:
            beautiful = re.sub(r'<(\S+)([^>]*)>\s+([^<\n]+)\s+</\1>',r'<\1\2>\3</\1>',beautiful)

        # Replace JS, CSS, and comments in the opposite order of their removal
        beautiful = self.replace_comments(beautiful)
        beautiful = self.replace_css(beautiful)
        beautiful = self.replace_js(beautiful)

        return beautiful
