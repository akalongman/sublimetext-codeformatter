# @author 		Avtandil Kikabidze
# @copyright 		Copyright (c) 2008-2014, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link 			http://long.ge
# @license 		GNU General Public License version 2 or later;

import os
import sys
import re
import sublime


st_version = 2
if sublime.version() == '' or int(sublime.version()) > 3000:
	st_version = 3

if (st_version == 2):
	from pybeautifier import Beautifier
else:
	#from .pybeautifier import Beautifier
	print('CodeFormatter: formatting python files on ST3 not supported.')


class PyFormatter:
	def __init__(self, formatter):
		self.formatter = formatter



	def format(self, text):
		if (self.formatter.st_version == 3):
			stdout = ""
			stderr = "formatting python files on ST3 not supported!"
			return stdout, stderr

		opts = self.formatter.settings.get('codeformatter_python_options')


		# Options
		options = {}

		# indent_size
		if (opts["indent_size"]):
			indent_size = opts["indent_size"]
		else:
			indent_size = 1

		# indent_with_tabs
		if (opts["indent_with_tabs"]):
			indent_with_tabs = True
		else:
			indent_with_tabs = False
		if indent_with_tabs:
			indentation = '	' * indent_size
		else:
			indentation = ' ' * indent_size
		options['INDENTATION'] = indentation

		# max_char
		if (opts["max_char"]):
			col_limit = opts["max_char"]
		else:
			col_limit = 80
		options['COL_LIMIT'] = col_limit

		# assignment
		if (opts["assignment"]):
			assignment = opts["assignment"]
		else:
			assignment = " = "
		options['ASSIGNMENT'] = assignment

		# function_param_assignment
		if (opts["function_param_assignment"]):
			function_param_assignment = opts["function_param_assignment"]
		else:
			function_param_assignment = "="
		options['FUNCTION_PARAM_ASSIGNMENT'] = function_param_assignment

		# function_param_sep
		if (opts["function_param_sep"]):
			function_param_sep = opts["function_param_sep"]
		else:
			function_param_sep = ", "
		options['FUNCTION_PARAM_SEP'] = function_param_sep

		# list_sep
		if (opts["list_sep"]):
			list_sep = opts["list_sep"]
		else:
			list_sep = ", "
		options['LIST_SEP'] = list_sep

		# subscript_sep
		if (opts["subscript_sep"]):
			subscript_sep = opts["subscript_sep"]
		else:
			subscript_sep = "="
		options['SUBSCRIPT_SEP'] = subscript_sep

		# dict_colon
		if (opts["dict_colon"]):
			dict_colon = opts["dict_colon"]
		else:
			dict_colon = ": "
		options['DICT_COLON'] = dict_colon

		# slice_colon
		if (opts["slice_colon"]):
			slice_colon = opts["slice_colon"]
		else:
			slice_colon = ": "
		options['SLICE_COLON'] = slice_colon

		# comment_prefix
		if (opts["comment_prefix"]):
			comment_prefix = opts["comment_prefix"]
		else:
			comment_prefix = "# "
		options['COMMENT_PREFIX'] = comment_prefix

		# shebang
		if (opts["shebang"]):
			shebang = opts["shebang"]
		else:
			shebang = "#!/usr/bin/env python"
		options['SHEBANG'] = shebang

		# boilerplate
		if (opts["boilerplate"]):
			boilerplate = opts["boilerplate"]
		else:
			boilerplate = ""
		options['BOILERPLATE'] = boilerplate

		# blank_line
		if (opts["blank_line"]):
			blank_line = opts["blank_line"]
		else:
			blank_line = ""
		options['BLANK_LINE'] = blank_line

		# keep_blank_lines
		if (opts["keep_blank_lines"]):
			keep_blank_lines = opts["keep_blank_lines"]
		else:
			keep_blank_lines = True
		options['KEEP_BLANK_LINES'] = keep_blank_lines

		# add_blank_lines_around_comments
		if (opts["add_blank_lines_around_comments"]):
			add_blank_lines_around_comments = opts["add_blank_lines_around_comments"]
		else:
			add_blank_lines_around_comments = True
		options['ADD_BLANK_LINES_AROUND_COMMENTS'] = add_blank_lines_around_comments

		# add_blank_line_after_doc_string
		if (opts["add_blank_line_after_doc_string"]):
			add_blank_line_after_doc_string = opts["add_blank_line_after_doc_string"]
		else:
			add_blank_line_after_doc_string = True
		options['ADD_BLANK_LINE_AFTER_DOC_STRING'] = add_blank_line_after_doc_string

		# max_seps_func_def
		if (opts["max_seps_func_def"]):
			max_seps_func_def = opts["max_seps_func_def"]
		else:
			max_seps_func_def = 3
		options['MAX_SEPS_FUNC_DEF'] = max_seps_func_def

		# max_seps_func_ref
		if (opts["max_seps_func_ref"]):
			max_seps_func_ref = opts["max_seps_func_ref"]
		else:
			max_seps_func_ref = 5
		options['MAX_SEPS_FUNC_REF'] = max_seps_func_ref

		# max_seps_series
		if (opts["max_seps_series"]):
			max_seps_series = opts["max_seps_series"]
		else:
			max_seps_series = 5
		options['MAX_SEPS_SERIES'] = max_seps_series

		# max_seps_dict
		if (opts["max_seps_dict"]):
			max_seps_dict = opts["max_seps_dict"]
		else:
			max_seps_dict = 3
		options['MAX_SEPS_DICT'] = max_seps_dict

		# max_lines_before_split_lit
		if (opts["max_lines_before_split_lit"]):
			max_lines_before_split_lit = opts["max_lines_before_split_lit"]
		else:
			max_lines_before_split_lit = 2
		options['MAX_LINES_BEFORE_SPLIT_LIT'] = max_lines_before_split_lit

		# left_margin
		if (opts["left_margin"]):
			left_margin = opts["left_margin"]
		else:
			left_margin = ""
		options['LEFT_MARGIN'] = left_margin

		# normalize_doc_strings
		if (opts["normalize_doc_strings"]):
			normalize_doc_strings = opts["normalize_doc_strings"]
		else:
			normalize_doc_strings = False
		options['NORMALIZE_DOC_STRINGS'] = normalize_doc_strings

		# leftjust_doc_strings
		if (opts["leftjust_doc_strings"]):
			leftjust_doc_strings = opts["leftjust_doc_strings"]
		else:
			leftjust_doc_strings = False
		options['LEFTJUST_DOC_STRINGS'] = leftjust_doc_strings

		# wrap_doc_strings
		if (opts["wrap_doc_strings"]):
			wrap_doc_strings = opts["wrap_doc_strings"]
		else:
			wrap_doc_strings = False
		options['WRAP_DOC_STRINGS'] = wrap_doc_strings

		# leftjust_comments
		if (opts["leftjust_comments"]):
			leftjust_comments = opts["leftjust_comments"]
		else:
			leftjust_comments = False
		options['LEFTJUST_COMMENTS'] = leftjust_comments

		# wrap_comments
		if (opts["wrap_comments"]):
			wrap_comments = opts["wrap_comments"]
		else:
			wrap_comments = False
		options['WRAP_COMMENTS'] = wrap_comments

		# double_quoted_strings
		if (opts["double_quoted_strings"]):
			double_quoted_strings = opts["double_quoted_strings"]
		else:
			double_quoted_strings = False
		options['DOUBLE_QUOTED_STRINGS'] = double_quoted_strings

		# single_quoted_strings
		if (opts["single_quoted_strings"]):
			single_quoted_strings = opts["single_quoted_strings"]
		else:
			single_quoted_strings = False
		options['SINGLE_QUOTED_STRINGS'] = single_quoted_strings

		# can_split_strings
		if (opts["can_split_strings"]):
			can_split_strings = opts["can_split_strings"]
		else:
			can_split_strings = False
		options['CAN_SPLIT_STRINGS'] = can_split_strings

		# doc_tab_replacement
		if (opts["doc_tab_replacement"]):
			doc_tab_replacement = opts["doc_tab_replacement"]
		else:
			doc_tab_replacement = "...."
		options['DOC_TAB_REPLACEMENT'] = doc_tab_replacement

		# keep_unassigned_constants
		if (opts["keep_unassigned_constants"]):
			keep_unassigned_constants = opts["keep_unassigned_constants"]
		else:
			keep_unassigned_constants = False
		options['KEEP_UNASSIGNED_CONSTANTS'] = keep_unassigned_constants

		# parenthesize_tuple_display
		if (opts["parenthesize_tuple_display"]):
			parenthesize_tuple_display = opts["parenthesize_tuple_display"]
		else:
			parenthesize_tuple_display = True
		options['PARENTHESIZE_TUPLE_DISPLAY'] = parenthesize_tuple_display

		# java_style_list_dedent
		if (opts["java_style_list_dedent"]):
			java_style_list_dedent = opts["java_style_list_dedent"]
		else:
			java_style_list_dedent = False
		options['JAVA_STYLE_LIST_DEDENT'] = java_style_list_dedent



		beautifier = Beautifier(self.formatter)
		stdout, stderr = beautifier.beautify(text, options)


		return stdout, stderr
