# @author 		Avtandil Kikabidze
# @copyright 		Copyright (c) 2008-2013, Avtandil Kikabidze (akalongman@gmail.com)
# @link 			http://long.ge
# @license 		GNU General Public License version 2 or later;

import os
import sys
import re
import sublime

try:
	# Python 3
	from .phpbeautifier import Beautifier
except (ValueError):
	# Python 2
	from phpbeautifier import Beautifier

class PhpFormatter:
	def __init__(self, formatter):
		self.formatter = formatter



	def format(self, text):
		opts = self.formatter.settings.get('codeformatter_php_options')


		# Filters
		filters = []

		# Pear
		if (opts["pear"]):
			pear = []
			add_header = opts["pear_add_header"] if opts["pear_add_header"] else "false"
			pear.append("add-header="+add_header)
			newline_class = "true" if opts["pear_newline_class"] else "false"
			pear.append("newline_class="+newline_class)
			newline_function = "true" if opts["pear_newline_function"] else "false"
			pear.append("newline_function="+newline_function)
			pear = ",".join(map(str, pear))
			filters.append("Pear("+pear+")")


		# Line filters
		new_line_before = ""
		if (opts["new_line_before"]):
			new_line_before = opts["new_line_before"].replace(",", ":")

		new_line_after = ""
		if (opts["new_line_after"]):
			new_line_after = opts["new_line_after"].replace(",", ":")

		new_lines = ""
		if (new_line_before and new_line_after):
			new_lines += "NewLines(before="+new_line_before+",after="+new_line_after+")"
		elif (new_line_before != ""):
			new_lines += "NewLines(before="+new_line_before+")"
		elif (new_line_after != ""):
			new_lines += "NewLines(after="+new_line_after+")"
		filters.append(new_lines)


		# Array Nested
		if (opts["format_array_nested"]):
			filters.append("ArrayNested()")

		# Lowercase
		if (opts["lowercase"]):
			filters.append("Lowercase()")


		# Fluent
		if (opts["fluent"]):
			filters.append("Fluent()")


		# phpBB
		if (opts["phpbb"]):
			filters.append("phpBB()")

		# ListClassFunction
		if (opts["list_class_function"]):
			filters.append("ListClassFunction()")

		# EqualsAlign
		if (opts["equals_align"]):
			filters.append("EqualsAlign()")


		# Identation
		ident_type = "t" if opts["indent_with_tabs"] else "s"
		indent_size = str(opts["indent_size"]) if opts["indent_size"] else "1"
		indent = "-"+ident_type+indent_size

		# Indent style
		indent_style = opts["indent_style"] if opts["indent_style"] else "k&r"
		filters.append("IndentStyles(style="+indent_style+")")


		filters = " ".join(map(str, filters))

		beautifier = Beautifier(self.formatter)
		stdout, stderr = beautifier.beautify(text, indent, filters)


		return stdout, stderr
