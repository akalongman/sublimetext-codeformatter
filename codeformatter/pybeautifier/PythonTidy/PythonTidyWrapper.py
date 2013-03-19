#!/usr/bin/python
# -*- coding: utf-8 -*-

# PythonTidyWrapper.py
# 2007 Mar 06 . ccr

# 2010 Sep 08 . ccr . Add JAVA_STYLE_LIST_DEDENT.
# 2010 Mar 16 . ccr . Add KEEP_UNASSIGNED_CONSTANTS and PARENTHESIZE_TUPLE_DISPLAY.
# 2007 May 25 . ccr . Changed MAX_SEPS.  Add WRAP_DOC_STRINGS and DOC_TAB_REPLACEMENT.
# 2007 May 01 . ccr . Added SINGLE_QUOTED_STRINGS.

"""Wrap PythonTidy.py with a configuration file.

The name of the file containing the input Python script may be
supplied.  If it is \"-\" or omitted, the input will be read from
standard input.

The name of a file to contain the output Python script may be
supplied.  If it is \"-\" or omitted, the output will be written to
standard output.

"""

from __future__ import division
import sys
import os
import optparse

PY_VER = sys.version
if PY_VER[:3] in ['2.5', '2.6', '2.7']:  # 2009 Oct 26
    import xml.etree.ElementTree
    ElementTree = xml.etree.ElementTree
else:
    import elementtree.ElementTree  # from http://effbot.org/zone/element-index.htm
    ElementTree = elementtree.ElementTree

import PythonTidy

ZERO = 0
SPACE = ' '
NULL = ''
NA = -1


class XmlFile(ElementTree.ElementTree):

    """XML document.

    """

    def __init__(
        self,
        file=None,
        tag='global',
        **extra
        ):
        if isinstance(file, basestring):
            file = os.path.expanduser(file)
        if file is None:
            top_level_elt = XmlList(tag=tag, **extra)
            top_level_elt.text = top_level_elt.tail = '\n'
            ElementTree.ElementTree.__init__(self,
                    element=top_level_elt)
        else:
            ElementTree.ElementTree.__init__(self)
            self.parse(source=file,
                       parser=ElementTree.XMLTreeBuilder(target=ElementTree.TreeBuilder(XmlList)))
        return

    def count(self, tag=None):
        return self.getroot().count(tag=tag)

    def write(self, file):
        if isinstance(file, basestring):
            file = os.path.expanduser(file)
        return ElementTree.ElementTree.write(self, file)

    def append(self, xml_elt):
        return self.getroot().append(xml_elt)

    def sort(self, tag=None, key_name='id'):
        return self.getroot().sort(tag=tag, key_name=key_name)

    def index(self, tag=None, key_name='id'):
        return self.getroot().index(tag=tag, key_name=key_name)


class XmlElt(ElementTree._ElementInterface):

    """XML element with attrib, text, and tail.

    """

    def __init__(
        self,
        tag,
        attrib={},
        **extra
        ):
        attrib = attrib.copy()
        attrib.update(extra)
        ElementTree._ElementInterface.__init__(self, tag=tag,
                attrib=attrib)
        return

    def __str__(self):
        return ElementTree.tostring(self)


class XmlList(XmlElt):

    """Subclass an XML eltement to perform summary statistics on and
    retrieve lists (or dicts) of its children.

    """

    def count(self, tag=None):
        result = ZERO
        for child in self:
            if tag in [None, child.tag]:
                result += 1
        return result

    def sort(self, tag=None, key_name='id'):
        result = [(child.attrib[key_name], child) for child in self
                  if tag in [None, child.tag]]
        result.sort()
        return result

    def index(self, tag=None, key_name='id'):
        result = {}
        for child in self:
            if tag in [None, child.tag]:
                insert(result, child.attrib[key_name], child)
        return result


class Config(XmlFile):

    """Configuration parameters.

    """

    def __init__(
        self,
        file=None,
        tag='config',
        **extra
        ):
        XmlFile.__init__(self, file=file, tag=tag, **extra)
        self.root = self.getroot()
        return

    def get_global(self, name):
        return getattr(PythonTidy, name)

    def set_global(self, name, value):
        setattr(PythonTidy, name, value)
        return self

    def from_pythontidy_namespace(self):
        repertoire = [
            ('COL_LIMIT', 'Width of output lines in characters.', 'int'
             ),
            ('INDENTATION', 'String used to indent lines.'),
            ('ASSIGNMENT',
             'This is how the assignment operator is to appear.'),
            ('FUNCTION_PARAM_ASSIGNMENT',
             '... but this is how function-parameter assignment should appear.'
             ),
            ('FUNCTION_PARAM_SEP',
             'This is how function parameters are separated.'),
            ('LIST_SEP', '... and this is how list items are separated.'
             ),
            ('SUBSCRIPT_SEP',
             '... and this is how subscripts are separated.'),
            ('DICT_COLON', 'This separates dictionary keys from values.'
             ),
            ('SLICE_COLON',
             '... but this separates the start:end indices of slices.'
             ),
            ('COMMENT_PREFIX',
             'This is the sentinel that marks the beginning of a commentary string.'
             ),
            ('SHEBANG',
             'Hashbang, a line-one comment naming the Python interpreter to Unix shells.'
             ),
            ('CODING', 'The output character encoding (codec).'),
            ('CODING_SPEC',
             """Source file encoding.

The %s in the value (if any) is replaced by the value of CODING.""",
             'replace', 'CODING'),
            ('BOILERPLATE',
             """Standard code block (if any).

This is inserted after the module doc string on output."""),
            ('BLANK_LINE',
             'This is how a blank line is to appear (up to the newline character).'
             ),
            ('KEEP_BLANK_LINES',
             'If true, preserve one blank where blank(s) are encountered.'
             , 'bool'),
            ('ADD_BLANK_LINES_AROUND_COMMENTS',
             'If true, set off comment blocks with blanks.', 'bool'),
            ('ADD_BLANK_LINE_AFTER_DOC_STRING',
             'If true, add blank line after doc strings.', 'bool'),
            ('MAX_SEPS_FUNC_DEF',
             'Split lines containing longer function definitions.',
             'int'),
            ('MAX_SEPS_FUNC_REF',
             'Split lines containing longer function calls.', 'int'),
            ('MAX_SEPS_SERIES',
             'Split lines containing longer lists or tuples.', 'int'),
            ('MAX_SEPS_DICT',
             'Split lines containing longer dictionary definitions.',
             'int'),
            ('MAX_LINES_BEFORE_SPLIT_LIT',
             'Split string literals containing more newline characters.'
             , 'int'),
            ('LEFT_MARGIN', 'This is how the left margin is to appear.'
             ),
            ('NORMALIZE_DOC_STRINGS',
             'If true, normalize white space in doc strings.', 'bool'),
            ('LEFTJUST_DOC_STRINGS',
             'If true, left justify doc strings.', 'bool'),
            ('WRAP_DOC_STRINGS',
             'If true, wrap doc strings to COL_LIMIT.', 'bool'),
            ('LEFTJUST_COMMENTS',
             'If true, left justify comments.', 'bool'),
            ('WRAP_COMMENTS',
             'If true, wrap comments to COL_LIMIT.', 'bool'),
            ('DOUBLE_QUOTED_STRINGS',
             'If true, use quotes instead of apostrophes for string literals.'
             , 'bool'),
            ('SINGLE_QUOTED_STRINGS',
             'If true, use apostrophes instead of quotes for string literals.'
             , 'bool'),
            ('RECODE_STRINGS',
             """If true, try to decode strings.

Attempt to use the character encoding specified in the input (if any).""",
             'bool'),
            ('OVERRIDE_NEWLINE',
             """This is how the newline sequence should appear.

Normally, the first thing that looks like a newline
sequence on input is captured and used at the end of every
line of output.  If this is not satisfactory, the desired
output newline sequence may be specified here."""),
            ('CAN_SPLIT_STRINGS',
             'If true, longer strings are split at the COL_LIMIT.',
             'bool'),
            ('DOC_TAB_REPLACEMENT',
             'This literal replaces tab characters in doc strings and comments.'
             ),
            ('KEEP_UNASSIGNED_CONSTANTS',
             """Optionally preserve unassigned constants so that code to be tidied
may contain blocks of commented-out lines that have been no-op'ed
with leading and trailing triple quotes.  Python scripts may declare
constants without assigning them to a variables, but PythonTidy
considers this wasteful and normally elides them.""",
             'bool'),
            ('PARENTHESIZE_TUPLE_DISPLAY',
             """Optionally omit parentheses around tuples, which are superfluous
after all.  Normal PythonTidy behavior will be still to include them
as a sort of tuple display analogous to list displays, dict
displays, and yet-to-come set displays.""",
             'bool'),
            ('JAVA_STYLE_LIST_DEDENT',

             """When PythonTidy splits longer lines because MAX_SEPS
are exceeded, the statement normally is closed before the margin is
restored.  The closing bracket, brace, or parenthesis is placed at the
current indent level.  This looks ugly to \"C\" programmers.  When
JAVA_STYLE_LIST_DEDENT is True, the closing bracket, brace, or
parenthesis is brought back left to the indent level of the enclosing
statement.""",
             
             'bool'),
            ]
        for parm in repertoire:
            self.set_parm_from_namespace(*parm)
        repertoire = [
            ('LOCAL_NAME_SCRIPT',
             """The following are name-transformation functions used
on output to filter the local-variable names."""
             ),
            ('GLOBAL_NAME_SCRIPT',
             """The following are name-transformation functions used
on output to filter the global-variable names."""
             ),
            ('CLASS_NAME_SCRIPT',
             """The following are name-transformation functions used
on output to filter class names."""
             ),
            ('FUNCTION_NAME_SCRIPT',
             """The following are name-transformation functions used
on output to filter function names."""
             ),
            ('FORMAL_PARAM_NAME_SCRIPT',
             """The following are name-transformation functions used
on output to filter function-parameter names."""
             ),
            ('ATTR_NAME_SCRIPT',
             """The following are name-transformation functions used
on output to filter class-attribute names."""
             ),
            ]

        for parm in repertoire:
            self.set_script_from_namespace(*parm)
        for parm in PythonTidy.SUBSTITUTE_FOR.iteritems():
            self.set_substitutions_from_namespace(*parm)
        return self

    def set_parm_from_namespace(
        self,
        name,
        desc,
        type=None,
        replacement=None,
        ):
        value = self.get_global(name)
        if type is None:
            if value is None:
                value = 'None'
        elif type == 'int':
            value = 'int(%s)' % value
        elif type == 'bool':
            value = 'bool(%s)' % value
        elif type == 'replace':
            target = self.get_global(replacement)
            value = value.replace(target, '%s')
            value = 'str.replace(%s, "%%s", PythonTidy.%s)' \
                % (repr(value), replacement)
        else:
            raise NotImplementedError
        elt = XmlList(tag='parm', name=name, value=value)
        elt.tail = '''
%s

''' % desc.strip()
        self.append(elt)
        return self

    def set_script_from_namespace(self, name, desc):
        group = XmlList(tag='script', name=name)
        group.text = '''
%s
''' % desc.strip()
        group.tail = '''

'''
        value = self.get_global(name)
        if value is None:
            pass
        else:
            for function in value:
                elt = XmlList(tag='xform', name=function.__name__)
                elt.tail = '\n'
                group.append(elt)
            self.append(group)
        return self

    def set_substitutions_from_namespace(self, target, replacement):
        elt = XmlList(tag='substitute', target=target,
                      replacement=replacement)
        elt.tail = '\n'
        self.append(elt)
        return self

    def to_pythontidy_namespace(self):
        for elt in self.root.findall('parm'):
            self.get_parm_to_namespace(elt)
        for elt in self.root.findall('script'):
            self.get_script_to_namespace(elt)
        substitutions = self.root.findall('substitute')
        if substitutions:
            PythonTidy.SUBSTITUTE_FOR = {}
            for elt in substitutions:
                self.get_substitutions_to_namespace(elt)
        return self

    def get_parm_to_namespace(self, elt):
        name = elt.attrib['name']
        value = elt.attrib['value']
        if value.startswith('int('):
            value = eval(value)
        elif value.startswith('bool('):
            value = eval(value)
        elif value.startswith('str.replace('):
            value = eval(value)
        elif value == 'None':
            value = None
        self.set_global(name, value)
        return self

    def get_script_to_namespace(self, group):
        name = group.attrib['name']
        result = []
        self.set_global(name, result)
        for elt in group.findall('xform'):
            name = elt.attrib['name']
            result.append(self.get_global(name))
        return self

    def get_substitutions_to_namespace(self, elt):
        target = elt.attrib['target']
        replacement = elt.attrib['replacement']
        PythonTidy.SUBSTITUTE_FOR[target] = replacement
        return self


def main():
    PARSER = optparse.OptionParser(usage='%prog [options] [input [output]]'
                                   , description=__doc__)
    PARSER.add_option('-u', '--ini_file',
                      help='''Read configuration parameters from an ini_file.'''
                      , default=None)
    PARSER.add_option('-U', '--dump',
                      help='''Dump default PythonTidy configuration parameters out to a file.'''
                      , default=None)
    (OPTS, ARGS) = PARSER.parse_args()
    if len(ARGS) > 2:
        PARSER.error('At most, only two arguments are allowed.')
    if len(ARGS) > 1:
        FILE_OUTPUT = ARGS[1]
    else:
        FILE_OUTPUT = '-'
    if FILE_OUTPUT in ['-']:
        FILE_OUTPUT = sys.stdout
    if len(ARGS) > ZERO:
        FILE_INPUT = ARGS[ZERO]
    else:
        FILE_INPUT = '-'
    if FILE_INPUT in ['-']:
        FILE_INPUT = sys.stdin
    if OPTS.dump is None:
        pass
    else:
        CONFIG = Config()
        CONFIG.from_pythontidy_namespace()
        CONFIG.write(file=OPTS.dump)
        sys.exit('Dump complete!')
    if OPTS.ini_file is None:
        pass
    else:
        CONFIG = Config(file=OPTS.ini_file)
        CONFIG.to_pythontidy_namespace()
        del CONFIG
    PythonTidy.tidy_up(FILE_INPUT, FILE_OUTPUT)


if __name__ == "__main__":
    main()

# Fin
