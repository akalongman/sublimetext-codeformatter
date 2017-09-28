# @author             Avtandil Kikabidze
# @copyright         Copyright (c) 2008-2015, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link             http://longman.me
# @license         The MIT License (MIT)

import re
import sublime


st_version = 2
if sublime.version() == '' or int(sublime.version()) > 3000:
    st_version = 3

if (st_version == 2):
    from pybeautifier import Beautifier
else:
    # from .pybeautifier import Beautifier
    print('CodeFormatter: formatting python files on ST3 not supported.')


class PyFormatter:

    def __init__(self, formatter):
        self.formatter = formatter
        self.opts = formatter.settings.get('codeformatter_python_options')

    def format(self, text):
        if (self.formatter.st_version == 3):
            stdout = ''
            stderr = 'formatting python files on ST3 not supported!'
            return stdout, stderr

        # Options
        options = {}

        # indent_size
        if (self.opts['indent_size']):
            indent_size = self.opts['indent_size']
        else:
            indent_size = 1

        # indent_with_tabs
        if (self.opts['indent_with_tabs']):
            indent_with_tabs = True
        else:
            indent_with_tabs = False
        if indent_with_tabs:
            indentation = '    ' * indent_size
        else:
            indentation = ' ' * indent_size
        options['INDENTATION'] = indentation

        # max_char
        if (self.opts['max_char']):
            col_limit = self.opts['max_char']
        else:
            col_limit = 80
        options['COL_LIMIT'] = col_limit

        # assignment
        if (self.opts['assignment']):
            assignment = self.opts['assignment']
        else:
            assignment = ' = '
        options['ASSIGNMENT'] = assignment

        # function_param_assignment
        if (self.opts['function_param_assignment']):
            function_param_assignment = self.opts['function_param_assignment']
        else:
            function_param_assignment = '='
        options['FUNCTION_PARAM_ASSIGNMENT'] = function_param_assignment

        # function_param_sep
        if (self.opts['function_param_sep']):
            function_param_sep = self.opts['function_param_sep']
        else:
            function_param_sep = ', '
        options['FUNCTION_PARAM_SEP'] = function_param_sep

        # list_sep
        if (self.opts['list_sep']):
            list_sep = self.opts['list_sep']
        else:
            list_sep = ', '
        options['LIST_SEP'] = list_sep

        # subscript_sep
        if (self.opts['subscript_sep']):
            subscript_sep = self.opts['subscript_sep']
        else:
            subscript_sep = '='
        options['SUBSCRIPT_SEP'] = subscript_sep

        # dict_colon
        if (self.opts['dict_colon']):
            dict_colon = self.opts['dict_colon']
        else:
            dict_colon = ': '
        options['DICT_COLON'] = dict_colon

        # slice_colon
        if (self.opts['slice_colon']):
            slice_colon = self.opts['slice_colon']
        else:
            slice_colon = ': '
        options['SLICE_COLON'] = slice_colon

        # comment_prefix
        if (self.opts['comment_prefix']):
            comment_prefix = self.opts['comment_prefix']
        else:
            comment_prefix = '# '
        options['COMMENT_PREFIX'] = comment_prefix

        # shebang
        if (self.opts['shebang']):
            shebang = self.opts['shebang']
        else:
            shebang = '#!/usr/bin/env python'
        options['SHEBANG'] = shebang

        # boilerplate
        if (self.opts['boilerplate']):
            boilerplate = self.opts['boilerplate']
        else:
            boilerplate = ''
        options['BOILERPLATE'] = boilerplate

        # blank_line
        if (self.opts['blank_line']):
            blank_line = self.opts['blank_line']
        else:
            blank_line = ''
        options['BLANK_LINE'] = blank_line

        # keep_blank_lines
        if (self.opts['keep_blank_lines']):
            keep_blank_lines = self.opts['keep_blank_lines']
        else:
            keep_blank_lines = True
        options['KEEP_BLANK_LINES'] = keep_blank_lines

        # add_blank_lines_around_comments
        if (self.opts['add_blank_lines_around_comments']):
            add_blank_lines_around_comments = (
                self.opts['add_blank_lines_around_comments']
            )
        else:
            add_blank_lines_around_comments = True
        options['ADD_BLANK_LINES_AROUND_COMMENTS'] = (
            add_blank_lines_around_comments
        )

        # add_blank_line_after_doc_string
        if (self.opts['add_blank_line_after_doc_string']):
            add_blank_line_after_doc_string = (
                self.opts['add_blank_line_after_doc_string']
            )
        else:
            add_blank_line_after_doc_string = True
        options['ADD_BLANK_LINE_AFTER_DOC_STRING'] = (
            add_blank_line_after_doc_string
        )

        # max_seps_func_def
        if (self.opts['max_seps_func_def']):
            max_seps_func_def = self.opts['max_seps_func_def']
        else:
            max_seps_func_def = 3
        options['MAX_SEPS_FUNC_DEF'] = max_seps_func_def

        # max_seps_func_ref
        if (self.opts['max_seps_func_ref']):
            max_seps_func_ref = self.opts['max_seps_func_ref']
        else:
            max_seps_func_ref = 5
        options['MAX_SEPS_FUNC_REF'] = max_seps_func_ref

        # max_seps_series
        if (self.opts['max_seps_series']):
            max_seps_series = self.opts['max_seps_series']
        else:
            max_seps_series = 5
        options['MAX_SEPS_SERIES'] = max_seps_series

        # max_seps_dict
        if (self.opts['max_seps_dict']):
            max_seps_dict = self.opts['max_seps_dict']
        else:
            max_seps_dict = 3
        options['MAX_SEPS_DICT'] = max_seps_dict

        # max_lines_before_split_lit
        if (self.opts['max_lines_before_split_lit']):
            max_lines_before_split_lit = (
                self.opts['max_lines_before_split_lit']
            )
        else:
            max_lines_before_split_lit = 2
        options['MAX_LINES_BEFORE_SPLIT_LIT'] = max_lines_before_split_lit

        # left_margin
        if (self.opts['left_margin']):
            left_margin = self.opts['left_margin']
        else:
            left_margin = ''
        options['LEFT_MARGIN'] = left_margin

        # normalize_doc_strings
        if (self.opts['normalize_doc_strings']):
            normalize_doc_strings = self.opts['normalize_doc_strings']
        else:
            normalize_doc_strings = False
        options['NORMALIZE_DOC_STRINGS'] = normalize_doc_strings

        # leftjust_doc_strings
        if (self.opts['leftjust_doc_strings']):
            leftjust_doc_strings = self.opts['leftjust_doc_strings']
        else:
            leftjust_doc_strings = False
        options['LEFTJUST_DOC_STRINGS'] = leftjust_doc_strings

        # wrap_doc_strings
        if (self.opts['wrap_doc_strings']):
            wrap_doc_strings = self.opts['wrap_doc_strings']
        else:
            wrap_doc_strings = False
        options['WRAP_DOC_STRINGS'] = wrap_doc_strings

        # leftjust_comments
        if (self.opts['leftjust_comments']):
            leftjust_comments = self.opts['leftjust_comments']
        else:
            leftjust_comments = False
        options['LEFTJUST_COMMENTS'] = leftjust_comments

        # wrap_comments
        if (self.opts['wrap_comments']):
            wrap_comments = self.opts['wrap_comments']
        else:
            wrap_comments = False
        options['WRAP_COMMENTS'] = wrap_comments

        # double_quoted_strings
        if (self.opts['double_quoted_strings']):
            double_quoted_strings = self.opts['double_quoted_strings']
        else:
            double_quoted_strings = False
        options['DOUBLE_QUOTED_STRINGS'] = double_quoted_strings

        # single_quoted_strings
        if (self.opts['single_quoted_strings']):
            single_quoted_strings = self.opts['single_quoted_strings']
        else:
            single_quoted_strings = False
        options['SINGLE_QUOTED_STRINGS'] = single_quoted_strings

        # can_split_strings
        if (self.opts['can_split_strings']):
            can_split_strings = self.opts['can_split_strings']
        else:
            can_split_strings = False
        options['CAN_SPLIT_STRINGS'] = can_split_strings

        # doc_tab_replacement
        if (self.opts['doc_tab_replacement']):
            doc_tab_replacement = self.opts['doc_tab_replacement']
        else:
            doc_tab_replacement = '....'
        options['DOC_TAB_REPLACEMENT'] = doc_tab_replacement

        # keep_unassigned_constants
        if (self.opts['keep_unassigned_constants']):
            keep_unassigned_constants = self.opts['keep_unassigned_constants']
        else:
            keep_unassigned_constants = False
        options['KEEP_UNASSIGNED_CONSTANTS'] = keep_unassigned_constants

        # parenthesize_tuple_display
        if (self.opts['parenthesize_tuple_display']):
            parenthesize_tuple_display = (
                self.opts['parenthesize_tuple_display']
            )
        else:
            parenthesize_tuple_display = True
        options['PARENTHESIZE_TUPLE_DISPLAY'] = parenthesize_tuple_display

        # java_style_list_dedent
        if (self.opts['java_style_list_dedent']):
            java_style_list_dedent = self.opts['java_style_list_dedent']
        else:
            java_style_list_dedent = False
        options['JAVA_STYLE_LIST_DEDENT'] = java_style_list_dedent

        beautifier = Beautifier(self.formatter)
        stdout, stderr = beautifier.beautify(text, options)

        return stdout, stderr

    def format_on_save_enabled(self, file_name):
        format_on_save = False
        if ('format_on_save' in self.opts and self.opts['format_on_save']):
            format_on_save = self.opts['format_on_save']
        if (isinstance(format_on_save, str)):
            format_on_save = re.search(format_on_save, file_name) is not None
        return format_on_save
